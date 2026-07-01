<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_news_users.php';

function news_users_xlsx_filename(string $base): string
{
    $base = preg_replace('~[^a-z0-9_-]+~i', '-', $base) ?: 'news-users';
    return trim($base, '-') . '-' . date('Ymd-His') . '.xlsx';
}

try {
    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    news_users_require_spreadsheet();

    $action = (string)($_GET['action'] ?? 'template');
    if ($action !== 'template') {
        throw new InvalidArgumentException('Neznámá XLSX akce.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('news_users_import');

    $columns = ['name', 'email', 'datum_od', 'datum_do', 'registered', 'valid'];
    foreach ($columns as $index => $column) {
        $sheet->setCellValue([$index + 1, 1], $column);
    }

    $sheet->fromArray([
        ['Jan Novak', 'jan.novak@example.com', date('Y-m-d'), '', 1, 1],
    ], null, 'A2');

    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->setAutoFilter('A1:F2');
    $sheet->freezePane('A2');
    for ($i = 1; $i <= count($columns); $i++) {
        $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    $notes = $spreadsheet->createSheet();
    $notes->setTitle('popis');
    $notes->fromArray([
        ['Sloupec', 'Popis'],
        ['name', 'Jméno nebo interní popis uživatele.'],
        ['email', 'Povinný e-mail. Existující e-mail se při importu aktualizuje.'],
        ['datum_od', 'Datum přihlášení ve formátu YYYY-MM-DD nebo DD.MM.YYYY. Prázdné = dnešní datum.'],
        ['datum_do', 'Datum ukončení. U aktivního odběru nechat prázdné.'],
        ['registered', '1/ano = aktivní odběr, 0/ne = ukončeno.'],
        ['valid', '1/ano = validní záznam, 0/ne = smazaný/nevalidní záznam.'],
    ], null, 'A1');
    $notes->getStyle('A1:B1')->getFont()->setBold(true);
    $notes->getColumnDimension('A')->setAutoSize(true);
    $notes->getColumnDimension('B')->setAutoSize(true);

    $spreadsheet->setActiveSheetIndex(0);

    if (ob_get_length() !== false) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . news_users_xlsx_filename('news-users-import-template') . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'XLSX šablonu se nepodařilo vytvořit: ' . $e->getMessage();
}
