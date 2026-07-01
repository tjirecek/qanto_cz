<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
date_default_timezone_set('Europe/Prague');

$oldDbName = 'xqanto_cz_old';
$reportDir = __DIR__ . '/reports';
$reportFile = $reportDir . '/004_news_migrate_' . date('Ymd_His') . '.md';
$reset = in_array('--reset', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

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
$targetDbName = (string)($config['dbname'] ?? '');
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$target = new PDO("mysql:host={$host};port={$port};dbname={$targetDbName};charset=utf8mb4", $user, $password, $options);
$old = new PDO("mysql:host={$host};port={$port};dbname={$oldDbName};charset=utf8mb4", $user, $password, $options);

$target->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', ''), 'NO_ZERO_DATE', '')");

function news_migrate_count(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function news_migrate_text(mixed $value): string
{
    return (string)($value ?? '');
}

function news_migrate_normalize_html(mixed $value, array &$stats, string $field): string
{
    $html = news_migrate_text($value);
    $original = $html;

    // Legacy admin stored literal backslash-escaped quotes inside HTML.
    $html = str_replace('\\"', '"', $html);

    $html = preg_replace_callback(
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

    if ($html !== $original) {
        $stats[$field] = ($stats[$field] ?? 0) + 1;
    }

    return $html;
}

function news_migrate_rewrite_media_links(string $html, string $rootDir, array &$stats, string $field): string
{
    if ($html === '') {
        return $html;
    }

    return preg_replace_callback(
        '~\b(src|href)=([\'"])(.*?)\2~is',
        static function (array $match) use ($rootDir, &$stats, $field): string {
            $attribute = strtolower((string)$match[1]);
            $quote = (string)$match[2];
            $url = html_entity_decode(trim((string)$match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $target = news_migrate_media_target($url, $rootDir);

            if ($target === null) {
                return $match[0];
            }

            $stats[$field] = ($stats[$field] ?? 0) + 1;

            return $attribute . '=' . $quote . htmlspecialchars($target, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $quote;
        },
        $html
    ) ?? $html;
}

function news_migrate_media_target(string $url, string $rootDir): ?string
{
    if ($url === '' || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || str_starts_with($url, '#')) {
        return null;
    }

    $path = news_migrate_url_path($url);

    $libraryFile = news_migrate_after_marker($path, '_images/_library/');
    if ($libraryFile !== null) {
        return news_migrate_existing_media_path($rootDir, 'media/library/' . $libraryFile);
    }

    $newsFile = news_migrate_after_marker($path, '_images/_news/');
    if ($newsFile !== null) {
        return news_migrate_existing_media_path($rootDir, 'media/library/x_news/' . $newsFile);
    }

    $downloadFile = news_migrate_after_marker($path, 'download/');
    if ($downloadFile !== null) {
        return news_migrate_existing_media_path($rootDir, 'media/download/' . $downloadFile);
    }

    return null;
}

function news_migrate_url_path(string $url): string
{
    $path = trim($url);
    if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $path)) {
        $path = preg_replace('~^[a-z][a-z0-9+.-]*://[^/]*~i', '', $path) ?? $path;
    }
    $path = substr($path, 0, strcspn($path, '?#'));

    return ltrim(news_migrate_percent_decode_utf8($path), '/');
}

function news_migrate_percent_decode_utf8(string $value): string
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

function news_migrate_after_marker(string $path, string $marker): ?string
{
    $pos = stripos($path, $marker);
    if ($pos === false) {
        return null;
    }

    $rest = substr($path, $pos + strlen($marker));
    $rest = ltrim($rest, '/');

    return $rest !== '' ? $rest : null;
}

function news_migrate_existing_media_path(string $rootDir, string $target): string
{
    $path = $rootDir . '/' . $target;
    if (is_file($path)) {
        return '/' . $target;
    }

    $dir = dirname($path);
    $base = basename($target);
    if (!is_dir($dir)) {
        return '/' . $target;
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

    return '/' . $target;
}

function news_migrate_date(mixed $value, string $fallback = '0000-00-00'): string
{
    $date = trim((string)($value ?? ''));
    return $date !== '' ? $date : $fallback;
}

function news_migrate_gallery_id(PDO $target, mixed $galleryId): int
{
    $id = (int)($galleryId ?? 0);
    if ($id <= 0) {
        return 0;
    }

    static $validGalleryIds = null;
    if ($validGalleryIds === null) {
        $ids = $target->query('SELECT id FROM galerie')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $validGalleryIds = array_flip(array_map('intval', $ids));
    }

    return isset($validGalleryIds[$id]) ? $id : 0;
}

function news_migrate_extract_internal_links(string $html): array
{
    if ($html === '') {
        return [];
    }

    preg_match_all('~\b(?:src|href)\s*=\s*(["\'])(.*?)\1~i', $html, $matches);
    $links = [];
    foreach ($matches[2] ?? [] as $url) {
        $url = html_entity_decode(trim((string)$url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($url === '' || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || str_starts_with($url, '#')) {
            continue;
        }

        $isInternal = str_starts_with($url, '/')
            || str_starts_with($url, '../')
            || str_starts_with($url, './')
            || str_contains($url, 'qanto.cz')
            || str_contains($url, '_images')
            || str_contains($url, 'images/')
            || str_contains($url, 'img/')
            || str_contains($url, 'files/')
            || str_contains($url, 'download')
            || preg_match('~\.(pdf|doc|docx|xls|xlsx|ppt|pptx|jpg|jpeg|png|gif|webp)(\?|#|$)~i', $url);

        if ($isInternal) {
            $links[] = $url;
        }
    }

    return array_values(array_unique($links));
}

function news_migrate_upsert_tag(PDO $target, array $tag, bool $dryRun): int
{
    $stmt = $target->prepare('SELECT id FROM news_tag WHERE slug_cz = :slug LIMIT 1');
    $stmt->execute([':slug' => $tag['slug_cz']]);
    $existingId = (int)($stmt->fetchColumn() ?: 0);

    if ($dryRun) {
        return $existingId > 0 ? $existingId : -1;
    }

    if ($existingId > 0) {
        $update = $target->prepare('UPDATE news_tag
            SET poradi = :poradi,
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                slug_en = :slug_en,
                color = :color,
                valid = 1,
                user_u = :user_u
            WHERE id = :id');
        $update->execute([
            ':poradi' => $tag['poradi'],
            ':nazev_cz' => $tag['nazev_cz'],
            ':nazev_en' => $tag['nazev_en'],
            ':slug_en' => $tag['slug_en'],
            ':color' => $tag['color'],
            ':user_u' => 'migration',
            ':id' => $existingId,
        ]);

        return $existingId;
    }

    $insert = $target->prepare('INSERT INTO news_tag
        (poradi, nazev_cz, nazev_en, slug_cz, slug_en, color, valid, user_i, user_u)
        VALUES (:poradi, :nazev_cz, :nazev_en, :slug_cz, :slug_en, :color, 1, :user_i, :user_u)');
    $insert->execute([
        ':poradi' => $tag['poradi'],
        ':nazev_cz' => $tag['nazev_cz'],
        ':nazev_en' => $tag['nazev_en'],
        ':slug_cz' => $tag['slug_cz'],
        ':slug_en' => $tag['slug_en'],
        ':color' => $tag['color'],
        ':user_i' => 'migration',
        ':user_u' => 'migration',
    ]);

    return (int)$target->lastInsertId();
}

$requiredTags = [
    'qanto' => [
        'poradi' => 1,
        'nazev_cz' => 'Qanto',
        'nazev_en' => 'Qanto',
        'slug_cz' => 'qanto',
        'slug_en' => 'qanto',
        'color' => 'text-bg-qanto',
    ],
    'maloobchod' => [
        'poradi' => 2,
        'nazev_cz' => 'Maloobchod',
        'nazev_en' => 'Retail',
        'slug_cz' => 'maloobchod',
        'slug_en' => 'retail',
        'color' => 'text-bg-qanto-markety',
    ],
    'velkoobchod' => [
        'poradi' => 3,
        'nazev_cz' => 'Velkoobchod',
        'nazev_en' => 'Wholesale',
        'slug_cz' => 'velkoobchod',
        'slug_en' => 'wholesale',
        'color' => 'text-bg-qanto-velkoobchod',
    ],
];

$typeToTagSlugs = [
    1 => ['qanto'],
    2 => ['qanto', 'maloobchod'],
    3 => ['qanto', 'velkoobchod'],
];

$oldRows = $old->query('SELECT * FROM news ORDER BY ID ASC')->fetchAll();
$oldCount = count($oldRows);
$targetBefore = news_migrate_count($target, 'news');
$relationBefore = news_migrate_count($target, 'news_tag_rel');
$targetTagsBefore = news_migrate_count($target, 'news_tag');

if ($targetBefore > 0 && !$reset && !$dryRun) {
    fwrite(STDERR, "Cilova tabulka news neni prazdna ({$targetBefore}). Pouzij --reset nebo nejdriv pust --dry-run.\n");
    exit(1);
}

$rowsToInsert = [];
$typeCounts = [];
$validCounts = [0 => 0, 1 => 0];
$visibleCounts = [];
$galleryMissing = [];
$sourceIcons = 0;
$internalLinks = [];
$tagAssignments = [];
$unknownTypes = [];
$normalizedHtmlCounts = [];
$rewrittenMediaLinkCounts = [];

foreach ($oldRows as $row) {
    $newsId = (int)$row['ID'];
    $typeId = (int)($row['news_typ'] ?? 0);
    $typeCounts[$typeId] = ($typeCounts[$typeId] ?? 0) + 1;

    $valid = (int)($row['valid'] ?? 0) === 1 ? 1 : 0;
    $validCounts[$valid]++;
    $visible = (int)($row['visible'] ?? 0);
    $visibleCounts[$visible] = ($visibleCounts[$visible] ?? 0) + 1;

    $sourceGalleryId = (int)($row['galerie_id'] ?? 0);
    $targetGalleryId = news_migrate_gallery_id($target, $sourceGalleryId);
    if ($sourceGalleryId > 0 && $targetGalleryId === 0) {
        $galleryMissing[] = ['news_id' => $newsId, 'gallery_id' => $sourceGalleryId];
    }

    if (trim(news_migrate_text($row['news_ico'] ?? '')) !== '') {
        $sourceIcons++;
    }

    $htmlParts = [
        'perex_cz' => news_migrate_normalize_html($row['perex_cz'] ?? '', $normalizedHtmlCounts, 'perex_cz'),
        'perex_en' => news_migrate_normalize_html($row['perex_en'] ?? '', $normalizedHtmlCounts, 'perex_en'),
        'text_cz' => news_migrate_normalize_html($row['text_cz'] ?? '', $normalizedHtmlCounts, 'text_cz'),
        'text_en' => news_migrate_normalize_html($row['text_en'] ?? '', $normalizedHtmlCounts, 'text_en'),
    ];
    foreach ($htmlParts as $field => $html) {
        $htmlParts[$field] = news_migrate_rewrite_media_links($html, $rootDir, $rewrittenMediaLinkCounts, $field);
    }
    foreach ($htmlParts as $field => $html) {
        foreach (news_migrate_extract_internal_links($html) as $link) {
            $internalLinks[] = ['news_id' => $newsId, 'field' => $field, 'url' => $link];
        }
    }

    $tagSlugs = $typeToTagSlugs[$typeId] ?? [];
    if ($tagSlugs === []) {
        $unknownTypes[$typeId] = true;
    }
    $tagAssignments[$newsId] = $tagSlugs;

    $rowsToInsert[] = [
        'id' => $newsId,
        'url_cz' => news_migrate_text($row['url_cz'] ?? ''),
        'url_en' => news_migrate_text($row['url_en'] ?? ''),
        'datum' => news_migrate_date($row['datum'] ?? null, date('Y-m-d')),
        'news_typ' => $typeId,
        'nazev_cz' => news_migrate_text($row['nazev_cz'] ?? ''),
        'nazev_en' => news_migrate_text($row['nazev_en'] ?? ''),
        'perex_cz' => $htmlParts['perex_cz'],
        'perex_en' => $htmlParts['perex_en'],
        'text_cz' => $htmlParts['text_cz'],
        'text_en' => $htmlParts['text_en'],
        'seo_title_cz' => '',
        'seo_title_en' => '',
        'seo_description_cz' => null,
        'seo_description_en' => null,
        'galerie_id' => $targetGalleryId,
        'news_ico' => '',
        'info_send' => news_migrate_date($row['info_send'] ?? null),
        'visible' => $visible,
        'valid' => $valid,
        'user_i' => 'migration',
        'user_u' => 'migration',
    ];
}

$tagIdsBySlug = [];
if (!$dryRun) {
    $target->beginTransaction();
    try {
        if ($reset) {
            $target->exec('DELETE FROM news_tag_rel');
            $target->exec('DELETE FROM news');
        }

        foreach ($requiredTags as $slug => $tag) {
            $tagIdsBySlug[$slug] = news_migrate_upsert_tag($target, $tag, false);
        }

        $insert = $target->prepare('INSERT INTO news
            (id, url_cz, url_en, datum, news_typ, nazev_cz, nazev_en, perex_cz, perex_en, text_cz, text_en,
             seo_title_cz, seo_title_en, seo_description_cz, seo_description_en, galerie_id, news_ico, info_send, visible, valid, user_i, user_u)
            VALUES
            (:id, :url_cz, :url_en, :datum, :news_typ, :nazev_cz, :nazev_en, :perex_cz, :perex_en, :text_cz, :text_en,
             :seo_title_cz, :seo_title_en, :seo_description_cz, :seo_description_en, :galerie_id, :news_ico, :info_send, :visible, :valid, :user_i, :user_u)');
        $relInsert = $target->prepare('INSERT INTO news_tag_rel (news_id, tag_id, user_i) VALUES (:news_id, :tag_id, :user_i)');

        foreach ($rowsToInsert as $row) {
            $insert->execute([
                ':id' => $row['id'],
                ':url_cz' => $row['url_cz'],
                ':url_en' => $row['url_en'],
                ':datum' => $row['datum'],
                ':news_typ' => $row['news_typ'],
                ':nazev_cz' => $row['nazev_cz'],
                ':nazev_en' => $row['nazev_en'],
                ':perex_cz' => $row['perex_cz'],
                ':perex_en' => $row['perex_en'],
                ':text_cz' => $row['text_cz'],
                ':text_en' => $row['text_en'],
                ':seo_title_cz' => $row['seo_title_cz'],
                ':seo_title_en' => $row['seo_title_en'],
                ':seo_description_cz' => $row['seo_description_cz'],
                ':seo_description_en' => $row['seo_description_en'],
                ':galerie_id' => $row['galerie_id'],
                ':news_ico' => $row['news_ico'],
                ':info_send' => $row['info_send'],
                ':visible' => $row['visible'],
                ':valid' => $row['valid'],
                ':user_i' => $row['user_i'],
                ':user_u' => $row['user_u'],
            ]);

            foreach ($tagAssignments[$row['id']] ?? [] as $slug) {
                if (!isset($tagIdsBySlug[$slug])) {
                    continue;
                }
                $relInsert->execute([
                    ':news_id' => $row['id'],
                    ':tag_id' => $tagIdsBySlug[$slug],
                    ':user_i' => 'migration',
                ]);
            }
        }

        $target->commit();
    } catch (Throwable $e) {
        if ($target->inTransaction()) {
            $target->rollBack();
        }
        throw $e;
    }
} else {
    foreach ($requiredTags as $slug => $tag) {
        $tagIdsBySlug[$slug] = news_migrate_upsert_tag($target, $tag, true);
    }
}

$targetAfter = $dryRun ? $targetBefore : news_migrate_count($target, 'news');
$relationAfter = $dryRun ? $relationBefore : news_migrate_count($target, 'news_tag_rel');
$targetTagsAfter = $dryRun ? $targetTagsBefore : news_migrate_count($target, 'news_tag');

ksort($typeCounts);
ksort($visibleCounts);

$report = [];
$report[] = '# 004 Novinky Migrace Report';
$report[] = '';
$report[] = '- Datum: ' . date('Y-m-d H:i:s');
$report[] = '- Zdroj DB: `' . $oldDbName . '`';
$report[] = '- Zdroj tabulka: `news`';
$report[] = '- Cil DB: `' . $targetDbName . '`';
$report[] = '- Cil tabulky: `news`, `news_tag`, `news_tag_rel`';
$report[] = '- Rezim: ' . ($dryRun ? 'dry-run' : 'zapis');
$report[] = '- Reset cilove tabulky `news`: ' . ($reset ? 'ano' : 'ne');
$report[] = '';
$report[] = '## Pocty';
$report[] = '';
$report[] = '| Oblast | Pocet |';
$report[] = '| --- | ---: |';
$report[] = '| Old `news` | ' . $oldCount . ' |';
$report[] = '| Cil pred `news` | ' . $targetBefore . ' |';
$report[] = '| Cil po `news` | ' . $targetAfter . ' |';
$report[] = '| Cil pred `news_tag` | ' . $targetTagsBefore . ' |';
$report[] = '| Cil po `news_tag` | ' . $targetTagsAfter . ' |';
$report[] = '| Cil pred `news_tag_rel` | ' . $relationBefore . ' |';
$report[] = '| Cil po `news_tag_rel` | ' . $relationAfter . ' |';
$report[] = '| Nemigrovane zdrojove ikony `news_ico` | ' . $sourceIcons . ' |';
$report[] = '| Interni odkazy v HTML obsahu | ' . count($internalLinks) . ' |';
$report[] = '| Galerie chybejici v cili | ' . count($galleryMissing) . ' |';
$report[] = '';
$report[] = '## Normalizace HTML';
$report[] = '';
$report[] = 'Migrace neupravuje obsahove cesty odkazu, ale opravuje legacy escapovani HTML atributu (`\\"`, `\\&quot;`) a rozbite `style="0height:..."`, aby obsah fungoval v editoru.';
$report[] = '';
if ($normalizedHtmlCounts === []) {
    $report[] = '- Zadna normalizace HTML nebyla potreba.';
} else {
    ksort($normalizedHtmlCounts);
    $report[] = '| Pole | Pocet upravenych zaznamu |';
    $report[] = '| --- | ---: |';
    foreach ($normalizedHtmlCounts as $field => $count) {
        $report[] = '| `' . $field . '` | ' . $count . ' |';
    }
}
$report[] = '';
$report[] = '## Prepis Media Odkazu';
$report[] = '';
$report[] = 'Jasne mapovatelne media odkazy se prepisuji na root-relative cesty bez domeny: `_images/_library/*` -> `/media/library/*`, `_images/_news/*` -> `/media/library/x_news/*`, `download/*` -> `/media/download/*`. Odkazy na stranky se neprepisuji.';
$report[] = '';
if ($rewrittenMediaLinkCounts === []) {
    $report[] = '- Zadny media odkaz nebyl prepsan.';
} else {
    ksort($rewrittenMediaLinkCounts);
    $report[] = '| Pole | Pocet prepsanych odkazu |';
    $report[] = '| --- | ---: |';
    foreach ($rewrittenMediaLinkCounts as $field => $count) {
        $report[] = '| `' . $field . '` | ' . $count . ' |';
    }
}
$report[] = '';
$report[] = '## Typy Novinek';
$report[] = '';
$report[] = '| news_typ | Pocet | Stitky |';
$report[] = '| ---: | ---: | --- |';
foreach ($typeCounts as $typeId => $count) {
    $report[] = '| ' . $typeId . ' | ' . $count . ' | `' . implode('`, `', $typeToTagSlugs[$typeId] ?? []) . '` |';
}
$report[] = '';
$report[] = '## Visible';
$report[] = '';
$report[] = '| visible | Pocet |';
$report[] = '| ---: | ---: |';
foreach ($visibleCounts as $visible => $count) {
    $report[] = '| ' . $visible . ' | ' . $count . ' |';
}
$report[] = '';
$report[] = '## Valid';
$report[] = '';
$report[] = '| valid | Pocet |';
$report[] = '| ---: | ---: |';
$report[] = '| 1 | ' . $validCounts[1] . ' |';
$report[] = '| 0 | ' . $validCounts[0] . ' |';
$report[] = '';
$report[] = '## Stitky';
$report[] = '';
$report[] = '| Slug | Cilove ID | Nazev CZ | CSS |';
$report[] = '| --- | ---: | --- | --- |';
foreach ($requiredTags as $slug => $tag) {
    $report[] = '| `' . $slug . '` | ' . ($tagIdsBySlug[$slug] ?? 'n/a') . ' | ' . $tag['nazev_cz'] . ' | `' . $tag['color'] . '` |';
}
$report[] = '';
$report[] = '## Chybejici Galerie V Cili';
$report[] = '';
if ($galleryMissing === []) {
    $report[] = '- Zadna.';
} else {
    $report[] = '| News ID | Galerie ID |';
    $report[] = '| ---: | ---: |';
    foreach (array_slice($galleryMissing, 0, 200) as $item) {
        $report[] = '| ' . $item['news_id'] . ' | ' . $item['gallery_id'] . ' |';
    }
    if (count($galleryMissing) > 200) {
        $report[] = '| ... | dalsich ' . (count($galleryMissing) - 200) . ' |';
    }
}
$report[] = '';
$report[] = '## Interni Odkazy V HTML Obsahu';
$report[] = '';
$report[] = 'Tyto odkazy nejsou prepisovane migraci. Pred produkci je nutne rozhodnout kopirovani souboru do `media/*` a frontend redirecty.';
$report[] = '';
if ($internalLinks === []) {
    $report[] = '- Nenalezeny zadne interni odkazy.';
} else {
    $report[] = '| News ID | Pole | URL |';
    $report[] = '| ---: | --- | --- |';
    foreach (array_slice($internalLinks, 0, 250) as $item) {
        $url = str_replace('|', '\\|', $item['url']);
        $report[] = '| ' . $item['news_id'] . ' | `' . $item['field'] . '` | `' . $url . '` |';
    }
    if (count($internalLinks) > 250) {
        $report[] = '| ... | ... | dalsich ' . (count($internalLinks) - 250) . ' odkazu |';
    }
}
$report[] = '';
$report[] = '## Nezname Typy';
$report[] = '';
if ($unknownTypes === []) {
    $report[] = '- Zadny neznamy typ.';
} else {
    foreach (array_keys($unknownTypes) as $typeId) {
        $report[] = '- `news_typ=' . $typeId . '` nema mapovani na stitky.';
    }
}

file_put_contents($reportFile, implode("\n", $report) . "\n");

echo ($dryRun ? "DRY RUN" : "MIGRACE HOTOVA") . "\n";
echo "Old: {$oldCount}\n";
echo "Cil pred: {$targetBefore}\n";
echo "Cil po: {$targetAfter}\n";
echo "Vazby stitku po: {$relationAfter}\n";
echo "Interni odkazy: " . count($internalLinks) . "\n";
echo "Nemigrovane ikony: {$sourceIcons}\n";
echo "Report: {$reportFile}\n";
