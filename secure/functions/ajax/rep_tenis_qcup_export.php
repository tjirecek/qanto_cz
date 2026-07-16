<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_rep_tenis_qcup.php';

$autoload = ROOT_DIR . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Composer vendor/autoload.php neni dostupny. Spust composer install.';
    exit;
}
require_once $autoload;

/**
 * @return array<string, string>
 */
function rep_tenis_qcup_export_columns(): array
{
    return [
        'id' => 'ID',
        'rok' => 'Rok',
        'datum' => 'Datum',
        'team_name' => 'Tym',
        'name1' => 'Jmeno 1',
        'surname1' => 'Prijmeni 1',
        'email1' => 'E-mail 1',
        'mobil1' => 'Mobil 1',
        'name2' => 'Jmeno 2',
        'surname2' => 'Prijmeni 2',
        'email2' => 'E-mail 2',
        'mobil2' => 'Mobil 2',
        'pozval' => 'Pozval',
        'poznamka' => 'Poznamka',
        'valid' => 'Valid',
    ];
}

/**
 * @param array<int, int> $years
 */
function rep_tenis_qcup_export_filename(array $years, bool $none, int $valid): string
{
    $suffix = $none ? 'zadne-roky' : ($years === [] ? 'vsechny-roky' : 'roky-' . implode('-', $years));
    $validSuffix = $valid === 1 ? 'validni' : 'nevalidni';
    return 'tenis-qcup-registrace-' . $validSuffix . '-' . $suffix . '-' . date('Ymd-His') . '.xlsx';
}

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES utf8mb4');

    $showNoYears = isset($_GET['none']) && (string)$_GET['none'] === '1';
    $valid = isset($_GET['valid']) && (string)$_GET['valid'] === '0' ? 0 : 1;
    $years = rep_tenis_qcup_parse_years($_GET['years'] ?? []);
    $rows = $showNoYears ? [] : rep_tenis_qcup_rows($pdo, $years, $valid);
    $columns = rep_tenis_qcup_export_columns();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('TenisQcup');

    $colIndex = 1;
    foreach ($columns as $label) {
        $sheet->setCellValue([$colIndex, 1], $label);
        $colIndex++;
    }

    $rowIndex = 2;
    foreach ($rows as $row) {
        $colIndex = 1;
        foreach ($columns as $column => $label) {
            $sheet->setCellValue([$colIndex, $rowIndex], $row[$column] ?? null);
            $colIndex++;
        }
        $rowIndex++;
    }

    $highestColumn = $sheet->getHighestColumn();
    $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
    $sheet->setAutoFilter('A1:' . $highestColumn . max(1, $rowIndex - 1));
    $sheet->freezePane('A2');
    for ($i = 1; $i <= count($columns); $i++) {
        $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    if (ob_get_length() !== false) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . rep_tenis_qcup_export_filename($years, $showNoYears, $valid) . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Export se nepodarilo vytvorit: ' . $e->getMessage();
}
