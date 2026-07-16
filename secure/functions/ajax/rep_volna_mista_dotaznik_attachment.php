<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_rep_volna_mista.php';

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

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo 'Neplatne ID dotazniku.';
        exit;
    }

    $application = rep_volna_mista_application($pdo, $id);
    if (!$application) {
        http_response_code(404);
        echo 'Dotaznik nebyl nalezen.';
        exit;
    }

    $attachmentId = (int)($_GET['attachment_id'] ?? 0);
    $attachment = null;
    if ($attachmentId > 0) {
        $attachments = rep_volna_mista_application_attachments($pdo, (int)$application['id']);
        foreach ($attachments as $row) {
            if ((int)($row['id'] ?? 0) === $attachmentId) {
                $attachment = $row;
                break;
            }
        }
        if ($attachment === null) {
            http_response_code(404);
            echo 'Priloha neni dostupna.';
            exit;
        }
    }

    $storedPath = $attachment !== null
        ? trim((string)($attachment['file_path'] ?? ''))
        : trim((string)($application['dot_priloha_file'] ?? ''));
    if ($storedPath === '') {
        http_response_code(404);
        echo 'Priloha neni dostupna.';
        exit;
    }

    if (str_starts_with($storedPath, 'private://')) {
        $relativePath = ltrim(substr($storedPath, strlen('private://')), '/');
        $storageRoot = dirname(ROOT_DIR) . '/qanto_cz_private';
    } elseif (str_starts_with($storedPath, 'protected://')) {
        $relativePath = ltrim(substr($storedPath, strlen('protected://')), '/');
        $storageRoot = ROOT_DIR . '/_files';
    } else {
        http_response_code(404);
        echo 'Priloha neni dostupna.';
        exit;
    }

    if ($relativePath === '' || str_contains($relativePath, '..')) {
        http_response_code(404);
        echo 'Priloha neni dostupna.';
        exit;
    }

    $baseDir = realpath($storageRoot);
    $filePath = realpath($storageRoot . '/' . $relativePath);
    if ($baseDir === false || $filePath === false || !is_file($filePath) || !str_starts_with($filePath, $baseDir . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        echo 'Priloha neni dostupna.';
        exit;
    }

    $filename = $attachment !== null
        ? rep_volna_mista_application_attachment_row_label($attachment)
        : rep_volna_mista_application_attachment_label($application);
    if ($filename === '') {
        $filename = basename($filePath);
    }
    $filename = preg_replace('/[\r\n]+/', '', basename($filename)) ?: 'priloha';
    $mime = $attachment !== null
        ? trim((string)($attachment['mime_type'] ?? ''))
        : trim((string)($application['dot_priloha_mime'] ?? ''));
    if ($mime === '') {
        $mime = 'application/octet-stream';
    }

    if (ob_get_length() !== false) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($filePath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Prilohu se nepodarilo stahnout: ' . $e->getMessage();
}
