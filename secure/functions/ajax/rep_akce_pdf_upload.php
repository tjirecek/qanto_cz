<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_default.php';
require_once SEC_DIR . '/functions/fun_rep_akce.php';

function rep_akce_pdf_upload_fail(string $message, int $status = 400): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function rep_akce_pdf_upload_header(string $name, string $default = ''): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return trim((string)($_SERVER[$key] ?? $default));
}

function rep_akce_pdf_upload_int_header(string $name): int
{
    $value = rep_akce_pdf_upload_header($name);
    return preg_match('~^\d+$~', $value) === 1 ? (int)$value : 0;
}

function rep_akce_pdf_upload_tmp_root(): string
{
    $dir = ROOT_DIR . '/_files/tmp/rep_akce_pdf_uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        rep_akce_pdf_upload_fail('Nepodařilo se vytvořit dočasný adresář.', 500);
    }
    return $dir;
}

function rep_akce_pdf_upload_transfer_id(): string
{
    $id = (string)($_GET['patch'] ?? $_POST['transfer_id'] ?? '');
    if (!preg_match('~^[a-f0-9]{32}$~', $id)) {
        rep_akce_pdf_upload_fail('Neplatné ID uploadu.', 400);
    }
    return $id;
}

function rep_akce_pdf_upload_transfer_dir(string $id): string
{
    return rep_akce_pdf_upload_tmp_root() . '/' . $id;
}

function rep_akce_pdf_upload_meta_path(string $id): string
{
    return rep_akce_pdf_upload_transfer_dir($id) . '/meta.json';
}

function rep_akce_pdf_upload_chunk_path(string $id): string
{
    return rep_akce_pdf_upload_transfer_dir($id) . '/upload.part';
}

function rep_akce_pdf_upload_meta(string $id): array
{
    $path = rep_akce_pdf_upload_meta_path($id);
    if (!is_file($path)) {
        rep_akce_pdf_upload_fail('Upload nebyl nalezen.', 404);
    }
    $meta = json_decode((string)file_get_contents($path), true);
    if (!is_array($meta)) {
        rep_akce_pdf_upload_fail('Upload má neplatná metadata.', 500);
    }
    return $meta;
}

function rep_akce_pdf_upload_save_meta(string $id, array $meta): void
{
    $dir = rep_akce_pdf_upload_transfer_dir($id);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        rep_akce_pdf_upload_fail('Nepodařilo se vytvořit dočasný adresář uploadu.', 500);
    }
    file_put_contents(rep_akce_pdf_upload_meta_path($id), json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function rep_akce_pdf_upload_cleanup_old_transfers(): void
{
    $root = rep_akce_pdf_upload_tmp_root();
    $threshold = time() - 86400;
    foreach (glob($root . '/*') ?: [] as $dir) {
        if (!is_dir($dir) || filemtime($dir) >= $threshold) {
            continue;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }
}

function rep_akce_pdf_upload_assert_access(): void
{
    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        rep_akce_pdf_upload_fail('Forbidden', 403);
    }

    $csrf = rep_akce_pdf_upload_header('X-CSRF-Token', (string)($_POST['csrf_token'] ?? ''));
    $sessionCsrf = (string)admin_session_get('rep_akce_csrf_token', '');
    if ($sessionCsrf === '' || !hash_equals($sessionCsrf, $csrf)) {
        rep_akce_pdf_upload_fail('Neplatný bezpečnostní token.', 403);
    }
}

function rep_akce_pdf_upload_offer_id(): int
{
    $id = rep_akce_pdf_upload_header('X-Offer-Id', (string)($_POST['offer_id'] ?? '0'));
    $id = (int)$id;
    if ($id <= 0) {
        rep_akce_pdf_upload_fail('Nejdříve uložte akční nabídku, potom nahrajte PDF.', 400);
    }
    return $id;
}

function rep_akce_pdf_upload_offer(PDO $pdo, int $offerId): array
{
    $offer = rep_akce_offer($pdo, $offerId);
    if (!$offer) {
        rep_akce_pdf_upload_fail('Akční nabídka nebyla nalezena.', 404);
    }
    return $offer;
}

function rep_akce_pdf_upload_finalize(PDO $pdo, int $offerId, string $sourcePath, string $originalName): string
{
    $offer = rep_akce_pdf_upload_offer($pdo, $offerId);
    $title = trim((string)($offer['nazev_cz'] ?? ''));
    if ($title === '') {
        $title = 'akce-' . $offerId;
    }

    $stored = rep_akce_store_pdf_file($sourcePath, $originalName, $offerId, $title, (string)($offer['pdf_file'] ?? ''), false);

    $stmt = $pdo->prepare('UPDATE rep_akce
        SET pdf_file = :pdf_file,
            pdf_original_name = :pdf_original_name,
            pdf_filesize = :pdf_filesize,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':pdf_file' => $stored['path'],
        ':pdf_original_name' => $stored['original_name'],
        ':pdf_filesize' => (int)$stored['filesize'],
        ':user_u' => rep_akce_user(),
        ':id' => $offerId,
    ]);

    return (string)$stored['path'];
}

function rep_akce_pdf_upload_handle_post(PDO $pdo): never
{
    rep_akce_pdf_upload_assert_access();
    rep_akce_pdf_upload_cleanup_old_transfers();

    $offerId = rep_akce_pdf_upload_offer_id();
    $offer = rep_akce_pdf_upload_offer($pdo, $offerId);

    $uploadLength = rep_akce_pdf_upload_int_header('Upload-Length');
    if ($uploadLength > 0 && empty($_FILES)) {
        $id = bin2hex(random_bytes(16));
        rep_akce_pdf_upload_save_meta($id, [
            'offer_id' => $offerId,
            'upload_length' => $uploadLength,
            'created_at' => time(),
        ]);

        header('Content-Type: text/plain; charset=utf-8');
        echo $id;
        exit;
    }

    $file = $_FILES['pdf_file'] ?? null;
    if (!is_array($file)) {
        rep_akce_pdf_upload_fail('PDF soubor nebyl přijat.', 400);
    }
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        rep_akce_pdf_upload_fail('PDF soubor se nepodařilo nahrát.', 400);
    }
    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        rep_akce_pdf_upload_fail('Dočasný PDF soubor není dostupný.', 400);
    }

    $storedPath = rep_akce_upload_pdf($file, $offerId, (string)($offer['nazev_cz'] ?? ''), (string)($offer['pdf_file'] ?? ''));
    $stmt = $pdo->prepare('UPDATE rep_akce SET pdf_file = :pdf_file, pdf_original_name = :pdf_original_name, pdf_filesize = :pdf_filesize, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':pdf_file' => $storedPath['path'],
        ':pdf_original_name' => $storedPath['original_name'],
        ':pdf_filesize' => (int)$storedPath['filesize'],
        ':user_u' => rep_akce_user(),
        ':id' => $offerId,
    ]);

    header('Content-Type: text/plain; charset=utf-8');
    echo $storedPath['path'];
    exit;
}

function rep_akce_pdf_upload_handle_head(): never
{
    rep_akce_pdf_upload_assert_access();
    $id = rep_akce_pdf_upload_transfer_id();
    $path = rep_akce_pdf_upload_chunk_path($id);
    header('Upload-Offset: ' . (is_file($path) ? (int)filesize($path) : 0));
    http_response_code(204);
    exit;
}

function rep_akce_pdf_upload_handle_patch(PDO $pdo): never
{
    rep_akce_pdf_upload_assert_access();

    $id = rep_akce_pdf_upload_transfer_id();
    $meta = rep_akce_pdf_upload_meta($id);
    $offerId = (int)($meta['offer_id'] ?? 0);
    rep_akce_pdf_upload_offer($pdo, $offerId);

    $offset = rep_akce_pdf_upload_int_header('Upload-Offset');
    $length = rep_akce_pdf_upload_int_header('Upload-Length');
    $name = rep_akce_pdf_upload_header('Upload-Name', (string)($meta['upload_name'] ?? 'nabidka.pdf'));
    if ($length <= 0) {
        $length = (int)($meta['upload_length'] ?? 0);
    }
    if ($length <= 0) {
        rep_akce_pdf_upload_fail('Chybí velikost uploadu.', 400);
    }

    $path = rep_akce_pdf_upload_chunk_path($id);
    $currentSize = is_file($path) ? (int)filesize($path) : 0;
    if ($offset !== $currentSize) {
        header('Upload-Offset: ' . $currentSize);
        rep_akce_pdf_upload_fail('Nesouhlasí offset chunk uploadu.', 409);
    }

    $input = fopen('php://input', 'rb');
    $output = fopen($path, 'ab');
    if (!$input || !$output) {
        rep_akce_pdf_upload_fail('Chunk se nepodařilo uložit.', 500);
    }
    stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);

    clearstatcache(true, $path);
    $newSize = is_file($path) ? (int)filesize($path) : 0;
    $meta['upload_name'] = $name;
    $meta['upload_length'] = $length;
    $meta['updated_at'] = time();
    rep_akce_pdf_upload_save_meta($id, $meta);

    if ($newSize >= $length) {
        $storedPath = rep_akce_pdf_upload_finalize($pdo, $offerId, $path, $name);
        @unlink(rep_akce_pdf_upload_meta_path($id));
        @rmdir(rep_akce_pdf_upload_transfer_dir($id));
        header('Content-Type: text/plain; charset=utf-8');
        echo $storedPath;
        exit;
    }

    header('Upload-Offset: ' . $newSize);
    http_response_code(204);
    exit;
}

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        rep_akce_pdf_upload_fail('PDO připojení není dostupné.', 500);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES utf8mb4');

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'POST') {
        rep_akce_pdf_upload_handle_post($pdo);
    }
    if ($method === 'PATCH') {
        rep_akce_pdf_upload_handle_patch($pdo);
    }
    if ($method === 'HEAD') {
        rep_akce_pdf_upload_handle_head();
    }

    rep_akce_pdf_upload_fail('Method not allowed', 405);
} catch (Throwable $e) {
    rep_akce_pdf_upload_fail($e->getMessage(), 500);
}
