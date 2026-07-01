<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');

$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/005_news_links_report_' . date('Ymd_His') . '.md';

if (!is_dir($reportDir) && !mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
    fwrite(STDERR, "Nelze vytvorit report adresar: {$reportDir}\n");
    exit(1);
}

$configPath = $rootDir . '/ini/config_local.ini';
$config = parse_ini_file($configPath, false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist {$configPath}\n");
    exit(1);
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$dbName = (string)($config['dbname'] ?? '');
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

function link_report_extract(string $html): array
{
    $html = link_report_normalize_html($html);

    preg_match_all('~\b(?:src|href)\s*=\s*(["\'])(.*?)\1~i', $html, $matches);
    $links = [];
    foreach ($matches[2] ?? [] as $url) {
        $url = html_entity_decode(trim((string)$url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($url === '' || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || str_starts_with($url, '#')) {
            continue;
        }
        $links[] = $url;
    }

    return array_values(array_unique($links));
}

function link_report_normalize_html(string $html): string
{
    $html = str_replace('\\"', '"', $html);

    return preg_replace_callback(
        '~\b(src|href|alt|title|style)=([\'"])(.*?)\2~is',
        static function (array $match): string {
            $attribute = strtolower((string)$match[1]);
            $attributeValue = (string)$match[3];
            $attributeValue = str_replace(['\\&quot;', '\\&#34;', '\\&#034;', '\\"'], '"', $attributeValue);
            $attributeValue = html_entity_decode($attributeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $attributeValue = trim($attributeValue);

            while (strlen($attributeValue) >= 2) {
                $first = $attributeValue[0];
                $last = $attributeValue[strlen($attributeValue) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $attributeValue = trim(substr($attributeValue, 1, -1));
                    continue;
                }
                break;
            }

            if ($attribute === 'style') {
                $attributeValue = preg_replace('~(^|;\s*)0(?=[a-z-]+\s*:)~i', '$1', $attributeValue) ?? $attributeValue;
            }

            return $attribute . '="' . htmlspecialchars($attributeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
        },
        $html
    ) ?? $html;
}

function link_report_url_path(string $url): string
{
    $path = trim($url);
    if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $path)) {
        $path = preg_replace('~^[a-z][a-z0-9+.-]*://[^/]*~i', '', $path) ?? $path;
    }
    $path = substr($path, 0, strcspn($path, '?#'));

    return ltrim(link_report_percent_decode_utf8($path), '/');
}

function link_report_percent_decode_utf8(string $value): string
{
    if (!preg_match('~%[0-9a-f]{2}~i', $value)) {
        return $value;
    }

    $decoded = rawurldecode($value);
    if (mb_check_encoding($decoded, 'UTF-8')) {
        return $decoded;
    }

    foreach (['Windows-1250', 'ISO-8859-2'] as $encoding) {
        $converted = @iconv($encoding, 'UTF-8//IGNORE', $decoded);
        if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
    }

    return preg_replace('/[^\x20-\x7E]/', '?', $decoded) ?? $value;
}

function link_report_markdown_text(string $value): string
{
    if (!mb_check_encoding($value, 'UTF-8')) {
        $value = link_report_percent_decode_utf8($value);
    }

    return str_replace(['|', "\r", "\n"], ['\\|', ' ', ' '], $value);
}

function link_report_existing_file(string $rootDir, string $target): ?string
{
    $path = $rootDir . '/' . $target;
    if (is_file($path)) {
        return '/' . $target;
    }

    $dir = dirname($path);
    $base = basename($target);
    if (!is_dir($dir)) {
        return null;
    }

    $lowerBase = mb_strtolower($base, 'UTF-8');
    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (mb_strtolower($file, 'UTF-8') === $lowerBase && is_file($dir . '/' . $file)) {
            return '/' . dirname($target) . '/' . $file;
        }
    }

    return null;
}

function link_report_after_marker(string $path, string $marker): ?string
{
    $pos = stripos($path, $marker);
    if ($pos === false) {
        return null;
    }

    $rest = substr($path, $pos + strlen($marker));
    $rest = ltrim($rest, '/');

    return $rest !== '' ? $rest : null;
}

function link_report_classify(string $url, string $rootDir): array
{
    $path = link_report_url_path($url);
    $lowerUrl = strtolower($url);
    $lowerPath = strtolower($path);

    $isExternal = preg_match('~^https?://~i', $url)
        && !str_contains($lowerUrl, 'qanto.cz')
        && !str_contains($lowerUrl, 'qanto.local');

    if ($isExternal) {
        return [
            'status' => 'external',
            'target' => '',
            'exists' => null,
            'note' => 'Externi odkaz, nemapovat do media.',
        ];
    }

    if (str_starts_with($lowerPath, 'media/library/') || str_starts_with($lowerPath, 'media/download/')) {
        $existingTarget = link_report_existing_file($rootDir, $path);
        return [
            'status' => 'media_current',
            'target' => $existingTarget ?? '/' . $path,
            'exists' => $existingTarget !== null,
            'note' => 'Aktualni media odkaz po migraci.',
        ];
    }

    $libraryFile = link_report_after_marker($path, '_images/_library/');
    if ($libraryFile !== null) {
        $target = 'media/library/' . $libraryFile;
        $existingTarget = link_report_existing_file($rootDir, $target);
        return [
            'status' => 'media_library',
            'target' => $existingTarget ?? '/' . $target,
            'exists' => $existingTarget !== null,
            'note' => 'Mapovat z _images/_library do media/library.',
        ];
    }

    $downloadFile = link_report_after_marker($path, 'download/');
    if ($downloadFile !== null) {
        $target = 'media/download/' . $downloadFile;
        $existingTarget = link_report_existing_file($rootDir, $target);
        return [
            'status' => 'media_download',
            'target' => $existingTarget ?? '/' . $target,
            'exists' => $existingTarget !== null,
            'note' => 'Mapovat z download do media/download.',
        ];
    }

    $newsFile = link_report_after_marker($path, '_images/_news/');
    if ($newsFile !== null) {
        $target = 'media/library/x_news/' . $newsFile;
        $existingTarget = link_report_existing_file($rootDir, $target);
        return [
            'status' => 'media_news',
            'target' => $existingTarget ?? '/' . $target,
            'exists' => $existingTarget !== null,
            'note' => 'Mapovat z _images/_news do media/library/x_news.',
        ];
    }

    if (str_contains($lowerPath, '_images/')) {
        return [
            'status' => 'other_old_images',
            'target' => '',
            'exists' => null,
            'note' => 'Jiny stary _images odkaz; nutne rucne rozhodnout.',
        ];
    }

    if (str_contains($lowerUrl, 'qanto.cz') || str_contains($lowerUrl, 'qanto.local') || str_starts_with($url, '/')) {
        return [
            'status' => 'internal_page_or_file',
            'target' => '',
            'exists' => null,
            'note' => 'Interni odkaz mimo jasne media mapovani; vyresit frontend redirect nebo rucne prepsat.',
        ];
    }

    if (preg_match('~\.(pdf|doc|docx|xls|xlsx|ppt|pptx|jpg|jpeg|png|gif|webp)(\?|#|$)~i', $url)) {
        return [
            'status' => 'relative_file_unknown',
            'target' => '',
            'exists' => null,
            'note' => 'Relativni soubor bez jasneho zdrojoveho adresare; rucne proverit.',
        ];
    }

    return [
        'status' => 'other',
        'target' => '',
        'exists' => null,
        'note' => 'Ostatni odkaz.',
    ];
}

$rows = $pdo->query('SELECT id, datum, nazev_cz, perex_cz, perex_en, text_cz, text_en FROM news ORDER BY id')->fetchAll() ?: [];
$items = [];
$summary = [];
$existsSummary = [
    'exists' => 0,
    'missing' => 0,
    'not_checked' => 0,
];

foreach ($rows as $row) {
    foreach (['perex_cz', 'perex_en', 'text_cz', 'text_en'] as $field) {
        foreach (link_report_extract((string)($row[$field] ?? '')) as $url) {
            $classified = link_report_classify($url, $rootDir);
            $status = $classified['status'];
            $summary[$status] = ($summary[$status] ?? 0) + 1;

            if ($classified['exists'] === true) {
                $existsSummary['exists']++;
            } elseif ($classified['exists'] === false) {
                $existsSummary['missing']++;
            } else {
                $existsSummary['not_checked']++;
            }

            $items[] = [
                'news_id' => (int)$row['id'],
                'datum' => (string)($row['datum'] ?? ''),
                'nazev_cz' => (string)($row['nazev_cz'] ?? ''),
                'field' => $field,
                'url' => $url,
                'status' => $status,
                'target' => $classified['target'],
                'exists' => $classified['exists'],
                'note' => $classified['note'],
            ];
        }
    }
}

ksort($summary);

$report = [];
$report[] = '# 005 News Links Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- DB: `' . $dbName . '`';
$report[] = '- Tabulka: `news`';
$report[] = '- Radku news: ' . count($rows);
$report[] = '- Nalezenych odkazu: ' . count($items);
$report[] = '';
$report[] = '## Souhrn Podle Kategorie';
$report[] = '';
$report[] = '| Kategorie | Pocet |';
$report[] = '| --- | ---: |';
foreach ($summary as $status => $count) {
    $report[] = '| `' . $status . '` | ' . $count . ' |';
}
$report[] = '';
$report[] = '## Kontrola Souboru Pro Jasne Mapovani';
$report[] = '';
$report[] = '| Stav | Pocet |';
$report[] = '| --- | ---: |';
$report[] = '| Existuje v `media/*` | ' . $existsSummary['exists'] . ' |';
$report[] = '| Chybi v `media/*` | ' . $existsSummary['missing'] . ' |';
$report[] = '| Nekontrolovano | ' . $existsSummary['not_checked'] . ' |';
$report[] = '';
$report[] = '## Chybejici Soubory V Jasnem Mapovani';
$report[] = '';
$missing = array_values(array_filter($items, static fn (array $item): bool => $item['exists'] === false));
if ($missing === []) {
    $report[] = '- Zadny chybejici soubor pro `_images/_library` nebo `download` mapovani.';
} else {
    $report[] = '| News ID | Pole | Puvodni URL | Navrzeny cil |';
    $report[] = '| ---: | --- | --- | --- |';
    foreach ($missing as $item) {
        $report[] = '| ' . $item['news_id'] . ' | `' . link_report_markdown_text($item['field']) . '` | `' . link_report_markdown_text($item['url']) . '` | `' . link_report_markdown_text($item['target']) . '` |';
    }
}
$report[] = '';
$report[] = '## Odkazy K Rucnimu Rozhodnuti';
$report[] = '';
$manual = array_values(array_filter($items, static fn (array $item): bool => in_array($item['status'], ['other_old_images', 'internal_page_or_file', 'relative_file_unknown'], true)));
if ($manual === []) {
    $report[] = '- Zadny odkaz k rucnimu rozhodnuti.';
} else {
    $report[] = '| News ID | Pole | Kategorie | URL | Poznamka |';
    $report[] = '| ---: | --- | --- | --- | --- |';
    foreach (array_slice($manual, 0, 500) as $item) {
        $report[] = '| ' . $item['news_id'] . ' | `' . link_report_markdown_text($item['field']) . '` | `' . link_report_markdown_text($item['status']) . '` | `' . link_report_markdown_text($item['url']) . '` | ' . link_report_markdown_text($item['note']) . ' |';
    }
    if (count($manual) > 500) {
        $report[] = '| ... | ... | ... | dalsich ' . (count($manual) - 500) . ' odkazu | ... |';
    }
}
$report[] = '';
$report[] = '## Vsechny Odkazy';
$report[] = '';
$report[] = '| News ID | Datum | Pole | Kategorie | Exists | Puvodni URL | Navrzeny cil |';
$report[] = '| ---: | --- | --- | --- | --- | --- | --- |';
foreach ($items as $item) {
    $exists = $item['exists'] === null ? '-' : ($item['exists'] ? 'ano' : 'ne');
    $report[] = '| ' . $item['news_id'] . ' | ' . link_report_markdown_text($item['datum']) . ' | `' . link_report_markdown_text($item['field']) . '` | `' . link_report_markdown_text($item['status']) . '` | ' . $exists . ' | `' . link_report_markdown_text($item['url']) . '` | `' . link_report_markdown_text($item['target']) . '` |';
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo "REPORT HOTOV\n";
echo "News: " . count($rows) . "\n";
echo "Odkazy: " . count($items) . "\n";
echo "Existuje: {$existsSummary['exists']}\n";
echo "Chybi: {$existsSummary['missing']}\n";
echo "Nekontrolovano: {$existsSummary['not_checked']}\n";
echo "Report: {$reportFile}\n";
