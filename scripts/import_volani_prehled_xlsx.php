<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$rootDir = dirname(__DIR__);

require_once $rootDir . '/vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

function usage(): void
{
    echo "Import prehledu volani z Vodafone XLSX do volani_preuctovani.\n";
    echo "\n";
    echo "Pouziti:\n";
    echo "  php scripts/import_volani_prehled_xlsx.php --file=/Users/tjirecek/Downloads/vodafone/prehled.xlsx\n";
    echo "  php scripts/import_volani_prehled_xlsx.php --file=/Users/tjirecek/Downloads/vodafone/prehled.xlsx --run\n";
    echo "  php scripts/import_volani_prehled_xlsx.php --file=/Users/tjirecek/Downloads/vodafone/prehled.xlsx --obdobi=2026-06 --run\n";
    echo "\n";
    echo "Bez --run probiha jen dry-run. Importuje pouze radky s nenulovym sloupcem sdph.\n";
    echo "Obdobi bere ze sloupce obdobi ve formatu MM.RRRR, napr. 06.2026; --obdobi=YYYY-MM slouzi jako volitelny override.\n";
}

function option_value(array $argv, string $name, ?string $default = null): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return $default;
}

function normalize_header(mixed $value): string
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    $value = str_replace(["\xc2\xa0", ' '], '', $value);
    $value = strtr($value, [
        'ě' => 'e',
        'š' => 's',
        'č' => 'c',
        'ř' => 'r',
        'ž' => 'z',
        'ý' => 'y',
        'á' => 'a',
        'í' => 'i',
        'é' => 'e',
        'ú' => 'u',
        'ů' => 'u',
        'ó' => 'o',
        'ď' => 'd',
        'ť' => 't',
        'ň' => 'n',
    ]);

    return preg_replace('~[^a-z0-9]+~', '', $value) ?? '';
}

function normalize_text(mixed $value): string
{
    return trim((string)$value);
}

function normalize_mobile(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('~\.0$~', '', $value) ?? $value;

    return preg_replace('~[^0-9+]~', '', $value) ?? '';
}

function normalize_decimal(mixed $value): string
{
    if ($value === null) {
        return '0.00';
    }

    if (is_int($value) || is_float($value)) {
        return number_format((float)$value, 2, '.', '');
    }

    $text = trim((string)$value);
    if ($text === '') {
        return '0.00';
    }

    $text = str_replace(["\xc2\xa0", ' '], '', $text);
    $text = str_replace(',', '.', $text);
    if (!is_numeric($text)) {
        return '0.00';
    }

    return number_format((float)$text, 2, '.', '');
}

function is_nonzero_decimal(string $value): bool
{
    return abs((float)$value) > 0.00001;
}

function load_header_map(Worksheet $sheet): array
{
    $highestColumn = $sheet->getHighestDataColumn();
    $headers = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0] ?? [];
    $map = [];
    foreach ($headers as $index => $header) {
        $map[normalize_header($header)] = $index + 1;
    }

    return $map;
}

function cell_value(Worksheet $sheet, int $row, int $column): mixed
{
    return $sheet->getCell([$column, $row])->getCalculatedValue();
}

function require_column(array $map, string $header): int
{
    $normalized = normalize_header($header);
    if (!isset($map[$normalized])) {
        throw new RuntimeException('V XLSX chybí sloupec "' . $header . '".');
    }

    return (int)$map[$normalized];
}

function optional_column(array $map, string $header): ?int
{
    $normalized = normalize_header($header);

    return isset($map[$normalized]) ? (int)$map[$normalized] : null;
}

function normalize_period(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m');
    }

    if (is_int($value) || is_float($value)) {
        $text = trim((string)$value);
        if (preg_match('~^(\d{6})$~', $text, $match)) {
            $month = substr($match[1], 0, 2);
            $year = substr($match[1], 2, 4);
            if ((int)$month >= 1 && (int)$month <= 12) {
                return $year . '-' . $month;
            }
        }
    }

    $text = trim((string)$value);
    if ($text === '') {
        return '';
    }

    $text = str_replace(["\xc2\xa0", ' '], '', $text);
    if (preg_match('~^(\d{1,2})[./-](\d{4})$~', $text, $match)) {
        $month = (int)$match[1];
        if ($month >= 1 && $month <= 12) {
            return $match[2] . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        }
    }

    if (preg_match('~^(\d{4})-(\d{2})$~', $text, $match)) {
        $month = (int)$match[2];
        if ($month >= 1 && $month <= 12) {
            return $text;
        }
    }

    return '';
}

function create_token(PDO $pdo): string
{
    do {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('SELECT 1 FROM volani_preuctovani WHERE unify = :unify LIMIT 1');
        $stmt->execute([':unify' => $token]);
    } while ((bool)$stmt->fetchColumn());

    return $token;
}

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    usage();
    exit(0);
}

$file = option_value($argv, 'file', '/Users/tjirecek/Downloads/vodafone/prehled.xlsx');
$periodOverride = normalize_period(option_value($argv, 'obdobi', ''));
$run = in_array('--run', $argv, true);

if ((string)option_value($argv, 'obdobi', '') !== '' && $periodOverride === '') {
    fwrite(STDERR, "Neplatne --obdobi. Pouzij YYYY-MM nebo MM.RRRR.\n\n");
    usage();
    exit(1);
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "XLSX soubor neexistuje: " . (string)$file . "\n");
    exit(1);
}

$config = parse_ini_file($rootDir . '/ini/config_local.ini', false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist ini/config_local.ini\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . (string)$config['host'] . ';port=' . (int)$config['port'] . ';dbname=' . (string)$config['dbname'] . ';charset=utf8mb4',
    (string)$config['user'],
    (string)$config['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);
$sheet = $spreadsheet->getActiveSheet();
$headerMap = load_header_map($sheet);

$columns = [
    'obdobi' => optional_column($headerMap, 'obdobi'),
    'jmeno' => require_column($headerMap, 'zamestnanec'),
    'email' => require_column($headerMap, 'email'),
    'mobil' => require_column($headerMap, 'mobil'),
    'zaklad0' => require_column($headerMap, '0dph'),
    'zaklad21' => require_column($headerMap, '21dph'),
    'zakladcelkem' => require_column($headerMap, 'bdph'),
    'celkem' => require_column($headerMap, 'sdph'),
];

if ($periodOverride === '' && $columns['obdobi'] === null) {
    fwrite(STDERR, "V XLSX chybi sloupec obdobi a nebyl zadan --obdobi.\n\n");
    usage();
    exit(1);
}

$existingKeys = [];
foreach ($pdo->query('SELECT obdobi, mobil FROM volani_preuctovani')->fetchAll() ?: [] as $row) {
    $existingKeys[(string)$row['obdobi'] . '|' . (string)$row['mobil']] = true;
}

$rows = [];
$totalRows = 0;
$nonzeroRows = 0;
$skippedZero = 0;
$skippedMissingMobile = 0;
$skippedMissingPeriod = 0;
$insertCandidates = 0;
$updateCandidates = 0;
$sumSdph = 0.0;
$periods = [];

for ($row = 2, $max = $sheet->getHighestDataRow(); $row <= $max; $row++) {
    $totalRows++;
    $celkem = normalize_decimal(cell_value($sheet, $row, $columns['celkem']));
    if (!is_nonzero_decimal($celkem)) {
        $skippedZero++;
        continue;
    }

    $mobil = normalize_mobile(cell_value($sheet, $row, $columns['mobil']));
    if ($mobil === '') {
        $skippedMissingMobile++;
        continue;
    }

    $period = $periodOverride;
    if ($period === '' && $columns['obdobi'] !== null) {
        $period = normalize_period(cell_value($sheet, $row, $columns['obdobi']));
    }
    if ($period === '') {
        $skippedMissingPeriod++;
        continue;
    }

    $nonzeroRows++;
    $sumSdph += (float)$celkem;
    $key = $period . '|' . $mobil;
    if (isset($existingKeys[$key])) {
        $updateCandidates++;
    } else {
        $insertCandidates++;
        $existingKeys[$key] = true;
    }
    $periods[$period] = ($periods[$period] ?? 0) + 1;

    $rows[] = [
        ':obdobi' => $period,
        ':mobil' => $mobil,
        ':jmeno' => normalize_text(cell_value($sheet, $row, $columns['jmeno'])),
        ':email' => normalize_text(cell_value($sheet, $row, $columns['email'])),
        ':zaklad0' => normalize_decimal(cell_value($sheet, $row, $columns['zaklad0'])),
        ':zaklad21' => normalize_decimal(cell_value($sheet, $row, $columns['zaklad21'])),
        ':zakladcelkem' => normalize_decimal(cell_value($sheet, $row, $columns['zakladcelkem'])),
        ':celkem' => $celkem,
    ];
}

echo $run ? "RUN - zapisuji do DB.\n" : "DRY RUN - pro zapis pouzij --run.\n";
echo "Soubor: {$file}\n";
echo "Obdobi: " . implode(', ', array_map(static fn (string $period, int $count): string => $period . ' (' . $count . ')', array_keys($periods), array_values($periods))) . "\n";
echo "Radku v XLSX bez hlavicky: {$totalRows}\n";
echo "Nenulove sdph k importu: {$nonzeroRows}\n";
echo "K vlozeni: {$insertCandidates}\n";
echo "K aktualizaci: {$updateCandidates}\n";
echo "Preskoceno sdph=0: {$skippedZero}\n";
echo "Preskoceno bez mobilu: {$skippedMissingMobile}\n";
echo "Preskoceno bez obdobi: {$skippedMissingPeriod}\n";
echo "Soucet sdph: " . number_format($sumSdph, 2, ',', ' ') . " Kč\n";

if (!$run) {
    exit(0);
}

$insert = $pdo->prepare('
    INSERT INTO volani_preuctovani
        (obdobi, mobil, jmeno, email, zaklad0, zaklad21, zakladcelkem, celkem, unify, valid, imported_at, user_i, user_u)
    VALUES
        (:obdobi, :mobil, :jmeno, :email, :zaklad0, :zaklad21, :zakladcelkem, :celkem, :unify, 1, NOW(), :user_i, :user_u)
    ON DUPLICATE KEY UPDATE
        jmeno = VALUES(jmeno),
        email = VALUES(email),
        zaklad0 = VALUES(zaklad0),
        zaklad21 = VALUES(zaklad21),
        zakladcelkem = VALUES(zakladcelkem),
        celkem = VALUES(celkem),
        valid = 1,
        imported_at = NOW(),
        user_u = VALUES(user_u)
');

$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        $row[':unify'] = create_token($pdo);
        $row[':user_i'] = 'volani-xlsx-import';
        $row[':user_u'] = 'volani-xlsx-import';
        $insert->execute($row);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

echo "Import dokoncen: " . count($rows) . " radku.\n";
