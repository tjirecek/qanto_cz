<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';

$autoload = ROOT_DIR . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Composer vendor/autoload.php neni dostupny. Spust composer install.';
    exit;
}
require_once $autoload;

function galerie_export_columns(PDO $pdo, string $table): array
{
    return match ($table) {
        'galerie' => [
            'id', 'nazev_cz', 'nazev_en', 'datum', 'galerie_typ', 'popis_cz', 'popis_en',
            'valid', 'user_i', 'user_u', 'ts_i', 'ts_u',
        ],
        'galerie_photo' => [
            'id', 'poradi', 'galerie_id', 'nazev_cz', 'nazev_en', 'soubor', 'mime_type',
            'width', 'height', 'filesize', 'valid', 'user_i', 'user_u', 'ts_i', 'ts_u',
        ],
        default => throw new InvalidArgumentException('Neplatna exportni tabulka.'),
    };
}

function galerie_export_rows(PDO $pdo, string $type): array
{
    if ($type === 'galleries_valid') {
        $params = [':valid' => 1];
        $where = 'valid = :valid';
        $typeId = (int)($_GET['type_id'] ?? 0);
        if ($typeId > 0) {
            $where .= ' AND galerie_typ = :type_id';
            $params[':type_id'] = $typeId;
        }

        $stmt = $pdo->prepare("SELECT * FROM galerie WHERE {$where} ORDER BY datum DESC, id DESC");
        $stmt->execute($params);

        return ['galerie', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'validni-galerie'];
    }

    if ($type === 'photos_valid') {
        $galleryId = (int)($_GET['gallery_id'] ?? 0);
        if ($galleryId <= 0) {
            throw new InvalidArgumentException('Chybi ID galerie pro export fotografii.');
        }

        $stmt = $pdo->prepare('SELECT * FROM galerie_photo WHERE galerie_id = :gallery_id AND valid = 1 ORDER BY poradi ASC, id ASC');
        $stmt->execute([':gallery_id' => $galleryId]);

        return ['galerie_photo', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'validni-fotografie-galerie-' . $galleryId];
    }

    throw new InvalidArgumentException('Neznamy typ exportu.');
}

function galerie_export_filename(string $base): string
{
    $base = preg_replace('~[^a-z0-9_-]+~i', '-', $base) ?: 'export';
    $base = trim($base, '-');

    return $base . '-' . date('Ymd-His') . '.xlsx';
}

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    if (!admin_session_is_logged() || (int)admin_session_prava() !== 1) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES utf8mb4');

    $type = (string)($_GET['type'] ?? '');
    [$table, $rows, $filenameBase] = galerie_export_rows($pdo, $type);
    $columns = galerie_export_columns($pdo, $table);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($table, 0, 31));

    $colIndex = 1;
    foreach ($columns as $column) {
        $sheet->setCellValue([$colIndex, 1], $column);
        $colIndex++;
    }

    $rowIndex = 2;
    foreach ($rows as $row) {
        $colIndex = 1;
        foreach ($columns as $column) {
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
    header('Content-Disposition: attachment; filename="' . galerie_export_filename($filenameBase) . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Export se nepodarilo vytvorit: ' . $e->getMessage();
}
