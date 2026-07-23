<?php
declare(strict_types=1);

function rep_volani_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_volani_money(mixed $value): string
{
    return number_format((float)$value, 2, ',', ' ') . ' Kč';
}

function rep_volani_money_plain(mixed $value): string
{
    return number_format((float)$value, 0, ',', ' ') . ',- Kč';
}

function rep_volani_number(mixed $value): string
{
    $number = (float)$value;
    return rtrim(rtrim(number_format($number, 2, ',', ' '), '0'), ',');
}

function rep_volani_decimal(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '0';
    }

    $value = str_replace(["\xc2\xa0", ' '], '', $value);
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value)) {
        return '0';
    }

    return (string)(float)$value;
}

function rep_volani_period_label(string $period): string
{
    $period = trim($period);
    if ($period === '') {
        return '';
    }

    if (is_numeric($period)) {
        $days = (int)floor((float)$period);
        try {
            $date = new DateTimeImmutable('1900-01-01', new DateTimeZone('Europe/Prague'));
            return $date->modify('+' . $days . ' days')->format('n.Y');
        } catch (Throwable) {
            return $period;
        }
    }

    return $period;
}

function rep_volani_datetime_label(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (is_numeric($value)) {
        $numeric = (float)$value;
        $days = (int)floor($numeric);
        $fraction = $numeric - $days;
        try {
            $date = new DateTimeImmutable('1900-01-01', new DateTimeZone('Europe/Prague'));
            $date = $date->modify('+' . $days . ' days');
            $seconds = (int)floor($fraction * 86400);
            $date = $date->setTime(0, 0)->modify('+' . $seconds . ' seconds');
            return $date->format('j.n.Y H:i:s');
        } catch (Throwable) {
            return $value;
        }
    }

    return $value;
}

function rep_volani_token(PDO $pdo): string
{
    do {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('SELECT 1 FROM volani_preuctovani WHERE unify = :unify LIMIT 1');
        $stmt->execute([':unify' => $token]);
    } while ((bool)$stmt->fetchColumn());

    return $token;
}

function rep_volani_uploaded_file(string $input): ?array
{
    $file = $_FILES[$input] ?? null;
    if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    return [
        'name' => (string)($file['name'] ?? ''),
        'tmp_name' => (string)($file['tmp_name'] ?? ''),
        'error' => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE),
    ];
}

function rep_volani_assert_xlsx_upload(array $file): void
{
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Soubor "' . (string)($file['name'] ?? '') . '" se nepodařilo nahrát.');
    }

    $name = strtolower((string)($file['name'] ?? ''));
    if (!str_ends_with($name, '.xlsx')) {
        throw new RuntimeException('Soubor "' . (string)($file['name'] ?? '') . '" není XLSX.');
    }

    if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Nahraný soubor není dostupný.');
    }
}

function rep_volani_normalize_header(mixed $value): string
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

function rep_volani_normalize_mobile(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('~\.0$~', '', $value) ?? $value;

    return preg_replace('~[^0-9+]~', '', $value) ?? '';
}

function rep_volani_period_value(mixed $value): string
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

function rep_volani_xlsx_header_map(PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
{
    $highestColumn = $sheet->getHighestDataColumn();
    $headers = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0] ?? [];
    $map = [];
    foreach ($headers as $index => $header) {
        $map[rep_volani_normalize_header($header)] = $index + 1;
    }

    return $map;
}

function rep_volani_xlsx_column(array $map, string $header): int
{
    $normalized = rep_volani_normalize_header($header);
    if (!isset($map[$normalized])) {
        throw new RuntimeException('V XLSX chybí sloupec "' . $header . '".');
    }

    return (int)$map[$normalized];
}

function rep_volani_xlsx_cell(PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $column): mixed
{
    return $sheet->getCell([$column, $row])->getCalculatedValue();
}

function rep_volani_row_hash(array $row): string
{
    return md5(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize($row));
}

function rep_volani_xlsx_load_sheet(string $path, int $startRow, int $endRow): PhpOffice\PhpSpreadsheet\Spreadsheet
{
    $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $reader->setReadFilter(new class($startRow, $endRow) implements PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
        public function __construct(private readonly int $startRow, private readonly int $endRow)
        {
        }

        public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
        {
            return $row >= $this->startRow && $row <= $this->endRow;
        }
    });

    return $reader->load($path);
}

function rep_volani_xlsx_total_rows(string $path): int
{
    $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $info = $reader->listWorksheetInfo($path);

    return (int)($info[0]['totalRows'] ?? 0);
}

function rep_volani_xlsx_prepare(string $path): array
{
    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer vendor/autoload.php není dostupný. Spusť composer install.');
    }
    require_once $autoload;
    @ini_set('memory_limit', '512M');

    $totalRows = rep_volani_xlsx_total_rows($path);
    $spreadsheet = rep_volani_xlsx_load_sheet($path, 1, 1);
    $headerMap = rep_volani_xlsx_header_map($spreadsheet->getActiveSheet());
    $spreadsheet->disconnectWorksheets();

    return [$totalRows, $headerMap];
}

function rep_volani_datetime_value(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if (is_int($value) || is_float($value)) {
        try {
            return PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return trim((string)$value);
        }
    }

    $value = trim((string)$value);

    return preg_replace('~\.0$~', '', $value) ?? $value;
}

function rep_volani_import_prehled_xlsx(PDO $pdo, string $path, string $sourceFile): array
{
    [$totalRows, $headerMap] = rep_volani_xlsx_prepare($path);
    $columns = [
        'obdobi' => rep_volani_xlsx_column($headerMap, 'obdobi'),
        'jmeno' => rep_volani_xlsx_column($headerMap, 'zamestnanec'),
        'email' => rep_volani_xlsx_column($headerMap, 'email'),
        'mobil' => rep_volani_xlsx_column($headerMap, 'mobil'),
        'zaklad0' => rep_volani_xlsx_column($headerMap, '0dph'),
        'zaklad21' => rep_volani_xlsx_column($headerMap, '21dph'),
        'zakladcelkem' => rep_volani_xlsx_column($headerMap, 'bdph'),
        'celkem' => rep_volani_xlsx_column($headerMap, 'sdph'),
    ];

    $existingKeys = [];
    foreach ($pdo->query('SELECT obdobi, mobil FROM volani_preuctovani')->fetchAll() ?: [] as $row) {
        $existingKeys[(string)$row['obdobi'] . '|' . (string)$row['mobil']] = true;
    }

    $rows = [];
    $summary = [
        'source_file' => $sourceFile,
        'total_rows' => 0,
        'imported' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped_zero' => 0,
        'skipped_missing_mobile' => 0,
        'skipped_missing_period' => 0,
        'sum_sdph' => 0.0,
        'periods' => [],
    ];

    $chunkSize = 100000;
    for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
        $endRow = min($totalRows, $startRow + $chunkSize - 1);
        $spreadsheet = rep_volani_xlsx_load_sheet($path, $startRow, $endRow);
        $sheet = $spreadsheet->getActiveSheet();
        for ($row = $startRow; $row <= $endRow; $row++) {
            $summary['total_rows']++;
            $celkem = rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['celkem']));
            if (abs((float)$celkem) <= 0.00001) {
                $summary['skipped_zero']++;
                continue;
            }

            $mobil = rep_volani_normalize_mobile(rep_volani_xlsx_cell($sheet, $row, $columns['mobil']));
            if ($mobil === '') {
                $summary['skipped_missing_mobile']++;
                continue;
            }

            $obdobi = rep_volani_period_value(rep_volani_xlsx_cell($sheet, $row, $columns['obdobi']));
            if ($obdobi === '') {
                $summary['skipped_missing_period']++;
                continue;
            }

            $key = $obdobi . '|' . $mobil;
            if (isset($existingKeys[$key])) {
                $summary['updated']++;
            } else {
                $summary['inserted']++;
                $existingKeys[$key] = true;
            }

            $summary['imported']++;
            $summary['sum_sdph'] += (float)$celkem;
            $summary['periods'][$obdobi] = ((int)($summary['periods'][$obdobi] ?? 0)) + 1;
            $rows[] = [
                ':obdobi' => $obdobi,
                ':mobil' => $mobil,
                ':jmeno' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['jmeno'])),
                ':email' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['email'])),
                ':zaklad0' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['zaklad0'])),
                ':zaklad21' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['zaklad21'])),
                ':zakladcelkem' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['zakladcelkem'])),
                ':celkem' => $celkem,
            ];
        }
        $spreadsheet->disconnectWorksheets();
    }

    $stmt = $pdo->prepare('
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

    $user = admin_session_user();
    foreach ($rows as $row) {
        $row[':unify'] = rep_volani_token($pdo);
        $row[':user_i'] = $user;
        $row[':user_u'] = $user;
        $stmt->execute($row);
    }

    return $summary;
}

function rep_volani_import_souhrn_xlsx(PDO $pdo, string $path, string $sourceFile): array
{
    [$totalRows, $headerMap] = rep_volani_xlsx_prepare($path);
    $columns = [
        'obdobi' => rep_volani_xlsx_column($headerMap, 'Období'),
        'mobil' => rep_volani_xlsx_column($headerMap, 'Mobil'),
        'produkt' => rep_volani_xlsx_column($headerMap, 'Produktová řada'),
        'polozka' => rep_volani_xlsx_column($headerMap, 'Položka'),
        'sluzba' => rep_volani_xlsx_column($headerMap, 'Služba'),
        'pocet' => rep_volani_xlsx_column($headerMap, 'Počet'),
        'trvani' => rep_volani_xlsx_column($headerMap, 'Celkové trvání (s)'),
        'uctovano' => rep_volani_xlsx_column($headerMap, 'Účtovaná doba (s)'),
        'objem' => rep_volani_xlsx_column($headerMap, 'Objem dat (MB)'),
        'celkem_bez_dph' => rep_volani_xlsx_column($headerMap, 'CELKEM_BEZ_DPH'),
        'dph' => rep_volani_xlsx_column($headerMap, 'DPH'),
        'celkem_s_dph' => rep_volani_xlsx_column($headerMap, 'CELKEM_S_DPH'),
    ];

    $existingKeys = [];
    foreach ($pdo->query('SELECT obdobi, mobil, row_hash FROM volani_souhrn')->fetchAll() ?: [] as $row) {
        $existingKeys[(string)$row['obdobi'] . '|' . (string)$row['mobil'] . '|' . (string)$row['row_hash']] = true;
    }

    $stmt = $pdo->prepare('
        INSERT INTO volani_souhrn
            (obdobi, mobil, produkt, polozka, sluzba, pocet, trvani, uctovano, objem, celkem_bez_dph, dph, celkem_s_dph, row_hash, source_file, imported_at)
        VALUES
            (:obdobi, :mobil, :produkt, :polozka, :sluzba, :pocet, :trvani, :uctovano, :objem, :celkem_bez_dph, :dph, :celkem_s_dph, :row_hash, :source_file, NOW())
        ON DUPLICATE KEY UPDATE
            source_file = VALUES(source_file),
            imported_at = NOW()
    ');

    $summary = rep_volani_summary_template($sourceFile);
    $chunkSize = 100000;
    for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
        $endRow = min($totalRows, $startRow + $chunkSize - 1);
        $spreadsheet = rep_volani_xlsx_load_sheet($path, $startRow, $endRow);
        $sheet = $spreadsheet->getActiveSheet();
        for ($row = $startRow; $row <= $endRow; $row++) {
            $summary['total_rows']++;
            $data = [
                'obdobi' => rep_volani_period_value(rep_volani_xlsx_cell($sheet, $row, $columns['obdobi'])),
                'mobil' => rep_volani_normalize_mobile(rep_volani_xlsx_cell($sheet, $row, $columns['mobil'])),
                'produkt' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['produkt'])),
                'polozka' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['polozka'])),
                'sluzba' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['sluzba'])),
                'pocet' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['pocet'])),
                'trvani' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['trvani'])),
                'uctovano' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['uctovano'])),
                'objem' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['objem'])),
                'celkem_bez_dph' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['celkem_bez_dph'])),
                'dph' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['dph'])),
                'celkem_s_dph' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['celkem_s_dph'])),
            ];

            rep_volani_import_row_data($stmt, $existingKeys, $summary, $data, $sourceFile);
        }
        $spreadsheet->disconnectWorksheets();
    }

    return $summary;
}

function rep_volani_import_detail_xlsx(PDO $pdo, string $path, string $sourceFile): array
{
    [$totalRows, $headerMap] = rep_volani_xlsx_prepare($path);
    $columns = [
        'obdobi' => rep_volani_xlsx_column($headerMap, 'Období'),
        'mobil' => rep_volani_xlsx_column($headerMap, 'Mobil'),
        'produkt' => rep_volani_xlsx_column($headerMap, 'Produktová řada'),
        'polozka' => rep_volani_xlsx_column($headerMap, 'Položka'),
        'datumcas' => rep_volani_xlsx_column($headerMap, 'Datum čas'),
        'smer' => rep_volani_xlsx_column($headerMap, 'Směr'),
        'cislo' => rep_volani_xlsx_column($headerMap, 'Volané číslo'),
        'trvani' => rep_volani_xlsx_column($headerMap, 'Celkové trvání (s)'),
        'uctovano' => rep_volani_xlsx_column($headerMap, 'Účtovaná doba (s)'),
        'objem' => rep_volani_xlsx_column($headerMap, 'Objem dat (MB)'),
        'celkem_bez_dph' => rep_volani_xlsx_column($headerMap, 'CELKEM_BEZ_DPH'),
        'celkem_s_dph' => rep_volani_xlsx_column($headerMap, 'CELKEM_S_DPH'),
    ];

    $existingKeys = [];
    foreach ($pdo->query('SELECT obdobi, mobil, row_hash FROM volani_detail')->fetchAll() ?: [] as $row) {
        $existingKeys[(string)$row['obdobi'] . '|' . (string)$row['mobil'] . '|' . (string)$row['row_hash']] = true;
    }

    $stmt = $pdo->prepare('
        INSERT INTO volani_detail
            (obdobi, mobil, produkt, polozka, datumcas, smer, cislo, trvani, uctovano, objem, celkem_bez_dph, celkem_s_dph, row_hash, source_file, imported_at)
        VALUES
            (:obdobi, :mobil, :produkt, :polozka, :datumcas, :smer, :cislo, :trvani, :uctovano, :objem, :celkem_bez_dph, :celkem_s_dph, :row_hash, :source_file, NOW())
        ON DUPLICATE KEY UPDATE
            source_file = VALUES(source_file),
            imported_at = NOW()
    ');

    $summary = rep_volani_summary_template($sourceFile);
    $chunkSize = 100000;
    for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
        $endRow = min($totalRows, $startRow + $chunkSize - 1);
        $spreadsheet = rep_volani_xlsx_load_sheet($path, $startRow, $endRow);
        $sheet = $spreadsheet->getActiveSheet();
        for ($row = $startRow; $row <= $endRow; $row++) {
            $summary['total_rows']++;
            $data = [
                'obdobi' => rep_volani_period_value(rep_volani_xlsx_cell($sheet, $row, $columns['obdobi'])),
                'mobil' => rep_volani_normalize_mobile(rep_volani_xlsx_cell($sheet, $row, $columns['mobil'])),
                'produkt' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['produkt'])),
                'polozka' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['polozka'])),
                'datumcas' => rep_volani_datetime_value(rep_volani_xlsx_cell($sheet, $row, $columns['datumcas'])),
                'smer' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['smer'])),
                'cislo' => trim((string)rep_volani_xlsx_cell($sheet, $row, $columns['cislo'])),
                'trvani' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['trvani'])),
                'uctovano' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['uctovano'])),
                'objem' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['objem'])),
                'celkem_bez_dph' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['celkem_bez_dph'])),
                'celkem_s_dph' => rep_volani_decimal(rep_volani_xlsx_cell($sheet, $row, $columns['celkem_s_dph'])),
            ];

            rep_volani_import_row_data($stmt, $existingKeys, $summary, $data, $sourceFile);
        }
        $spreadsheet->disconnectWorksheets();
    }

    return $summary;
}

function rep_volani_summary_template(string $sourceFile): array
{
    return [
        'source_file' => $sourceFile,
        'total_rows' => 0,
        'imported' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped_missing_mobile' => 0,
        'skipped_missing_period' => 0,
        'sum_s_dph' => 0.0,
        'periods' => [],
    ];
}

function rep_volani_import_row_data(PDOStatement $stmt, array &$existingKeys, array &$summary, array $data, string $sourceFile): void
{
    if ($data['obdobi'] === '') {
        $summary['skipped_missing_period']++;
        return;
    }
    if ($data['mobil'] === '') {
        $summary['skipped_missing_mobile']++;
        return;
    }

    $rowHash = rep_volani_row_hash($data);
    $key = $data['obdobi'] . '|' . $data['mobil'] . '|' . $rowHash;
    if (isset($existingKeys[$key])) {
        $summary['updated']++;
    } else {
        $summary['inserted']++;
        $existingKeys[$key] = true;
    }

    $summary['imported']++;
    $summary['sum_s_dph'] += (float)($data['celkem_s_dph'] ?? 0);
    $summary['periods'][$data['obdobi']] = ((int)($summary['periods'][$data['obdobi']] ?? 0)) + 1;
    $data['row_hash'] = $rowHash;
    $data['source_file'] = $sourceFile;

    $stmt->execute(array_combine(
        array_map(static fn (string $key): string => ':' . $key, array_keys($data)),
        array_values($data)
    ));
}

function rep_volani_periods(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT obdobi, COUNT(*) AS total, SUM(celkem) AS total_amount
        FROM volani_preuctovani
        WHERE valid = 1
        GROUP BY obdobi
        ORDER BY obdobi DESC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volani_counts(PDO $pdo): array
{
    return [
        'preuctovani' => (int)$pdo->query('SELECT COUNT(*) FROM volani_preuctovani')->fetchColumn(),
        'souhrn' => (int)$pdo->query('SELECT COUNT(*) FROM volani_souhrn')->fetchColumn(),
        'detail' => (int)$pdo->query('SELECT COUNT(*) FROM volani_detail')->fetchColumn(),
    ];
}

function rep_volani_delete_period(PDO $pdo, string $period): array
{
    $period = trim($period);
    if (!preg_match('~^\d{4}-\d{2}$~', $period)) {
        throw new RuntimeException('Vyber období, které se má smazat.');
    }

    $deleted = [
        'preuctovani' => 0,
        'souhrn' => 0,
        'detail' => 0,
    ];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM volani_detail WHERE obdobi = :period');
        $stmt->execute([':period' => $period]);
        $deleted['detail'] = $stmt->rowCount();

        $stmt = $pdo->prepare('DELETE FROM volani_souhrn WHERE obdobi = :period');
        $stmt->execute([':period' => $period]);
        $deleted['souhrn'] = $stmt->rowCount();

        $stmt = $pdo->prepare('DELETE FROM volani_preuctovani WHERE obdobi = :period');
        $stmt->execute([':period' => $period]);
        $deleted['preuctovani'] = $stmt->rowCount();

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $deleted;
}

function rep_volani_rows(PDO $pdo, string $period = '', string $email = '', string $mobil = '', string $sent = ''): array
{
    $sql = 'SELECT * FROM volani_preuctovani WHERE valid = 1';
    $params = [];
    if ($period !== '') {
        $sql .= ' AND obdobi = :period';
        $params[':period'] = $period;
    }
    if ($email !== '') {
        $sql .= ' AND email LIKE :email';
        $params[':email'] = '%' . $email . '%';
    }
    if ($mobil !== '') {
        $sql .= ' AND mobil LIKE :mobil';
        $params[':mobil'] = '%' . $mobil . '%';
    }
    if ($sent === 'yes') {
        $sql .= ' AND email_sent_at IS NOT NULL';
    } elseif ($sent === 'no') {
        $sql .= ' AND email_sent_at IS NULL';
    }

    $sql .= ' ORDER BY obdobi DESC, email, mobil';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volani_public_url(string $unify, int $type = 1): string
{
    return '/volani/index.php?typ=' . (int)$type . '&unify=' . rawurlencode($unify);
}

function rep_volani_public_email_url(string $email): string
{
    return '/volani/index.php?typ=3&identify=' . rawurlencode($email);
}

function rep_volani_public_base_url(): string
{
    if (function_exists('app_is_local_environment') && app_is_local_environment()) {
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            $scheme = function_exists('app_is_https') && app_is_https() ? 'https' : 'http';
            return $scheme . '://' . $host;
        }

        return 'https://qanto.test';
    }

    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $baseUrl = trim((string)($config['newsletter_public_base_url'] ?? ''));
    if ($baseUrl === '') {
        $baseUrl = 'https://www.qanto.cz';
    }

    return rtrim($baseUrl, '/');
}

function rep_volani_absolute_url(string $url): string
{
    if (preg_match('~^https?://~i', $url) === 1) {
        return $url;
    }

    return rep_volani_public_base_url() . '/' . ltrim($url, '/');
}

function rep_volani_invoice_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM volani_preuctovani WHERE id = :id AND valid = 1 LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function rep_volani_invoices_by_email_period(PDO $pdo, string $email, string $period, bool $onlyUnsent = false): array
{
    $sql = '
        SELECT *
        FROM volani_preuctovani
        WHERE valid = 1
          AND email = :email
          AND obdobi = :period
    ';
    if ($onlyUnsent) {
        $sql .= ' AND email_sent_at IS NULL';
    }
    $sql .= ' ORDER BY mobil, id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':period' => $period,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volani_normalize_invoice_rows(array $invoiceOrRows): array
{
    if (isset($invoiceOrRows[0]) && is_array($invoiceOrRows[0])) {
        return $invoiceOrRows;
    }

    return $invoiceOrRows === [] ? [] : [$invoiceOrRows];
}

function rep_volani_invoice_ids(array $rows): array
{
    $ids = [];
    foreach ($rows as $row) {
        $id = (int)($row['id'] ?? 0);
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

function rep_volani_setting_text(PDO $pdo, string $name, string $default = ''): string
{
    if (function_exists('sp_hodnota_text')) {
        $value = sp_hodnota_text($name);
        return trim((string)$value) !== '' ? (string)$value : $default;
    }

    $stmt = $pdo->prepare('SELECT hodnota_text FROM settings WHERE name = :name AND valid = 1 LIMIT 1');
    $stmt->execute([':name' => $name]);
    $value = $stmt->fetchColumn();

    return $value !== false && trim((string)$value) !== '' ? (string)$value : $default;
}

function rep_volani_static_email_text(): string
{
    $text = function_exists('stat_text') ? (string)(stat_text('volani') ?? '') : '';
    if ($text === '') {
        global $pdo;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT text_cz FROM stat_texty WHERE code = :code AND valid = 1 ORDER BY id DESC LIMIT 1');
            $stmt->execute([':code' => 'volani']);
            $value = $stmt->fetchColumn();
            $text = $value !== false ? (string)$value : '';
        }
    }

    $text = function_exists('editor_html') ? editor_html($text) : $text;
    if (trim(strip_tags($text)) !== '') {
        return $text;
    }

    return '<p>Na odkazu si můžete přepnout v menu mezi souhrnným či podrobným výpisem. Stejně tak si můžete stáhnout vyúčtování v PDF kliknutím na odkaz <strong>EXPORT DO PDF</strong>.</p>'
        . '<p><strong>Důležité: odkaz na souhrnné a podrobné výpisy bude aktivní minimálně 14 dní, maximálně do dalšího vyúčtování.</strong></p>'
        . '<p><strong>Stále přijímáme nová čísla na převod, kontaktujte nás na tomto e-mailu a využijte možnosti levnějšího volání pro Vás či Vaše rodinné příslušníky.</strong></p>';
}

function rep_volani_config_string(string $key, string $default = ''): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $value = trim((string)($config[$key] ?? ''));

    return $value !== '' ? $value : $default;
}

function rep_volani_logo_url(): string
{
    return rep_volani_absolute_url(rep_volani_config_string('newsletter_logo_url', '/img/design/logo_admin_login.png'));
}

function rep_volani_brand_name(): string
{
    return rep_volani_config_string('newsletter_brand_name', 'Qanto');
}

function rep_volani_accent_color(): string
{
    $color = rep_volani_config_string('newsletter_accent_color', '#e30613');

    return preg_match('~^#[0-9a-f]{6}$~i', $color) === 1 ? $color : '#e30613';
}

function rep_volani_email_text(string $html): string
{
    $text = preg_replace('~<(br|/p|/div|/h[1-6]|/li|/tr)\b[^>]*>~i', "\n", $html) ?? $html;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function rep_volani_email_subject(array $invoiceOrRows): string
{
    $rows = rep_volani_normalize_invoice_rows($invoiceOrRows);
    $first = $rows[0] ?? [];
    $period = rep_volani_period_label((string)($first['obdobi'] ?? ''));

    return 'Astur & Qanto, výpisy volání' . ($period !== '' ? ' ' . $period : '');
}

function rep_volani_email_title(array $invoiceOrRows): string
{
    $rows = rep_volani_normalize_invoice_rows($invoiceOrRows);
    $first = $rows[0] ?? [];

    return 'Vyúčtování volání Qanto ' . rep_volani_period_label((string)($first['obdobi'] ?? ''));
}

function rep_volani_email_body_html(array $invoiceOrRows): string
{
    $rows = rep_volani_normalize_invoice_rows($invoiceOrRows);
    $first = $rows[0] ?? [];
    $period = rep_volani_period_label((string)($first['obdobi'] ?? ''));
    $email = (string)($first['email'] ?? '');
    $emailOverviewUrl = rep_volani_absolute_url(rep_volani_public_email_url($email) . '&obdobi=' . rawurlencode((string)($first['obdobi'] ?? '')));
    $logoUrl = rep_volani_logo_url();
    $brandName = rep_volani_brand_name();
    $accentColor = rep_volani_accent_color();
    $title = rep_volani_email_title($rows);
    $year = date('Y');
    $total = 0.0;
    $bodyRows = '';
    foreach ($rows as $invoice) {
        $summaryUrl = rep_volani_absolute_url(rep_volani_public_url((string)($invoice['unify'] ?? ''), 1));
        $detailUrl = rep_volani_absolute_url(rep_volani_public_url((string)($invoice['unify'] ?? ''), 2));
        $total += (float)($invoice['celkem'] ?? 0);
        $bodyRows .= '<tr>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#64748b;white-space:nowrap;">' . rep_volani_e($period) . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#26323f;font-weight:700;">' . rep_volani_e($invoice['jmeno'] ?? '') . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#26323f;white-space:nowrap;">' . rep_volani_e($invoice['mobil'] ?? '') . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;white-space:nowrap;">' . rep_volani_e(rep_volani_money_plain($invoice['zaklad0'] ?? 0)) . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;white-space:nowrap;">' . rep_volani_e(rep_volani_money_plain($invoice['zaklad21'] ?? 0)) . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;white-space:nowrap;">' . rep_volani_e(rep_volani_money_plain($invoice['zakladcelkem'] ?? 0)) . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;color:#17212f;text-align:right;font-weight:800;white-space:nowrap;">' . rep_volani_e(rep_volani_money_plain($invoice['celkem'] ?? 0)) . '</td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;text-align:center;"><a href="' . rep_volani_e($summaryUrl) . '" style="display:inline-block;padding:7px 11px;background:#eef2f7;color:#17212f;border-radius:999px;font-size:12px;font-weight:800;text-decoration:none;">Souhrn</a></td>'
            . '<td style="padding:13px 14px;border-bottom:1px solid #e5e7eb;text-align:center;"><a href="' . rep_volani_e($detailUrl) . '" style="display:inline-block;padding:7px 11px;background:' . rep_volani_e($accentColor) . ';color:#ffffff;border-radius:999px;font-size:12px;font-weight:800;text-decoration:none;">Detail</a></td>'
            . '</tr>';
    }

    return '<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . rep_volani_e(rep_volani_email_subject($rows)) . '</title>
</head>
<body style="margin:0;padding:0;background:#eef1f4;color:#26323f;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;color:#eef1f4;font-size:1px;line-height:1px;">' . rep_volani_e($title) . '</div>
  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef1f4;margin:0;padding:28px 0;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="760" style="width:760px;max-width:100%;background:#ffffff;border-collapse:collapse;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);">
          <tr>
            <td style="padding:28px 34px 22px 34px;background:#ffffff;border-top:8px solid ' . rep_volani_e($accentColor) . ';">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img src="' . rep_volani_e($logoUrl) . '" width="214" alt="' . rep_volani_e($brandName) . '" style="display:block;width:214px;max-width:70%;height:auto;border:0;">
                  </td>
                  <td align="right" style="vertical-align:middle;color:#6b7280;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                    Vyúčtování volání
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#17212f;color:#ffffff;padding:34px 38px 36px 38px;">
              <div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">Astur &amp; Qanto</div>
              <h1 style="margin:10px 0 0 0;font-size:32px;line-height:1.22;font-weight:800;">' . rep_volani_e($title) . '</h1>
              <p style="margin:14px 0 0 0;color:#d8dee8;font-size:16px;line-height:1.5;">Přehled Vašich telefonních čísel za období ' . rep_volani_e($period) . '.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:34px 38px 18px 38px;font-size:16px;line-height:1.68;color:#26323f;">
              <div style="margin:0 0 24px 0;">' . rep_volani_static_email_text() . '</div>
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:0 0 30px 0;"><tr>
                <td bgcolor="' . rep_volani_e($accentColor) . '" style="border-radius:999px;"><a href="' . rep_volani_e($emailOverviewUrl) . '" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;">Přehled všech čísel na webu</a></td>
              </tr></table>
              <div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:800;margin:0 0 12px 0;">Rozpis vyúčtování</div>
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;font-size:13px;line-height:1.35;">
                <thead>
                  <tr>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Období</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Jméno</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Mobil</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Základ 0%</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Základ 21%</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Bez DPH</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">S DPH</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Souhrn</th>
                    <th style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb;color:#64748b;text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">Detail</th>
                  </tr>
                </thead>
                <tbody>
                  ' . $bodyRows . '
                  <tr>
                    <td colspan="6" style="padding:18px 14px;background:#17212f;color:#ffffff;font-weight:800;text-align:right;">Celkem k úhradě za uvedená čísla</td>
                    <td colspan="3" style="padding:18px 14px;background:#17212f;color:#ffffff;font-size:20px;font-weight:900;text-align:right;white-space:nowrap;">' . rep_volani_e(rep_volani_money_plain($total)) . ' s DPH</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:24px 38px;font-size:13px;line-height:1.55;color:#64748b;">
              <p style="margin:0 0 8px 0;">Tento e-mail obsahuje vyúčtování telefonních služeb vedených u Astur &amp; Qanto.</p>
              <p style="margin:0;">&copy; ' . rep_volani_e($brandName) . ' :: Astur &amp; Qanto s.r.o. ' . rep_volani_e($year) . '</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function rep_volani_email_body_text(array $invoiceOrRows): string
{
    return rep_volani_email_text(rep_volani_email_body_html($invoiceOrRows));
}

function rep_volani_mail_config(PDO $pdo): array
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $defaultFromEmail = 'volani@qanto.cz';
    $fromEmail = rep_volani_setting_text($pdo, 'volani_from_email', $defaultFromEmail);
    $fromEmail = function_exists('plain_text') ? plain_text($fromEmail) : trim(strip_tags($fromEmail));
    if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
        $fromEmail = $defaultFromEmail;
    }

    if (function_exists('mailer_is_local_environment') && mailer_is_local_environment()) {
        $config['smtp_reply_to'] = $fromEmail;
    } else {
        $config['smtp_from'] = $fromEmail;
    }

    return $config;
}

function rep_volani_mark_email_success(PDO $pdo, array $ids, ?int $emailLogId): void
{
    $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
    if ($ids === []) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        UPDATE volani_preuctovani
        SET email_sent_at = NOW(),
            email_sent_by = ?,
            email_send_attempts = email_send_attempts + 1,
            email_last_error = NULL,
            email_log_id = ?,
            user_u = ?
        WHERE id IN ({$placeholders})
    ");
    $user = admin_session_user();
    $stmt->execute(array_merge([$user, $emailLogId, $user], $ids));
}

function rep_volani_mark_email_failure(PDO $pdo, array $ids, string $error): void
{
    $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
    if ($ids === []) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        UPDATE volani_preuctovani
        SET email_send_attempts = email_send_attempts + 1,
            email_last_error = ?,
            user_u = ?
        WHERE id IN ({$placeholders})
    ");
    $stmt->execute(array_merge([mb_substr($error, 0, 5000, 'UTF-8'), admin_session_user()], $ids));
}

function rep_volani_send_invoice_email(PDO $pdo, int $id, bool $onlyUnsent = false): array
{
    require_once ROOT_DIR . '/functions/fun_mailer.php';

    $invoice = rep_volani_invoice_by_id($pdo, $id);
    if (!$invoice) {
        throw new RuntimeException('Vyúčtování nebylo nalezeno.');
    }

    $recipient = trim((string)($invoice['email'] ?? ''));
    if ($recipient === '') {
        rep_volani_mark_email_failure($pdo, [$id], 'U vyúčtování není vyplněný e-mail.');
        throw new RuntimeException('U vyúčtování není vyplněný e-mail.');
    }

    $period = (string)($invoice['obdobi'] ?? '');
    $invoices = rep_volani_invoices_by_email_period($pdo, $recipient, $period, $onlyUnsent);
    if ($invoices === []) {
        $invoices = [$invoice];
    }
    $invoiceIds = rep_volani_invoice_ids($invoices);

    try {
        $result = mailer_send_smtp_logged($pdo, rep_volani_mail_config($pdo), [
            'recipient_email' => $recipient,
            'recipient_name' => (string)($invoice['jmeno'] ?? ''),
            'subject' => rep_volani_email_subject($invoices),
            'body_html' => rep_volani_email_body_html($invoices),
            'body_text' => rep_volani_email_body_text($invoices),
        ], [
            'context' => 'volani',
            'template_code' => 'volani_prehled',
            'related_table' => 'volani_preuctovani',
            'related_id' => $id,
            'payload' => [
                'obdobi' => $period,
                'row_ids' => $invoiceIds,
                'mobily' => array_values(array_map(static fn (array $row): string => (string)($row['mobil'] ?? ''), $invoices)),
                'email' => $recipient,
            ],
        ]);
        rep_volani_mark_email_success($pdo, $invoiceIds, isset($result['email_log_id']) ? (int)$result['email_log_id'] : null);

        return $result + ['invoice' => $invoice, 'invoices' => $invoices, 'recipient' => $recipient];
    } catch (Throwable $e) {
        rep_volani_mark_email_failure($pdo, $invoiceIds, $e->getMessage());
        throw $e;
    }
}

function rep_volani_send_unsent_filtered(PDO $pdo, string $period = '', string $email = '', string $mobil = ''): array
{
    $rows = rep_volani_rows($pdo, $period, $email, $mobil, 'no');
    $result = ['sent' => 0, 'failed' => 0, 'errors' => []];
    $processedGroups = [];
    foreach ($rows as $row) {
        $rowEmail = trim((string)($row['email'] ?? ''));
        $groupKey = $rowEmail === ''
            ? (string)($row['obdobi'] ?? '') . "\n#row:" . (int)($row['id'] ?? 0)
            : (string)($row['obdobi'] ?? '') . "\n" . mb_strtolower($rowEmail, 'UTF-8');
        if (isset($processedGroups[$groupKey])) {
            continue;
        }
        $processedGroups[$groupKey] = true;
        try {
            $sendResult = rep_volani_send_invoice_email($pdo, (int)$row['id'], true);
            $result['sent_groups'] = (int)($result['sent_groups'] ?? 0) + 1;
            $result['sent_rows'] = (int)($result['sent_rows'] ?? 0) + count((array)($sendResult['invoices'] ?? []));
            $result['sent']++;
        } catch (Throwable $e) {
            $result['failed']++;
            $result['errors'][] = '#' . (int)$row['id'] . ' ' . (string)($row['email'] ?? '') . ': ' . $e->getMessage();
        }
    }

    return $result;
}
