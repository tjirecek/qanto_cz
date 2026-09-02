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
    $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json) || file_put_contents(rep_akce_pdf_upload_meta_path($id), $json, LOCK_EX) === false) {
        rep_akce_pdf_upload_fail('Nepodařilo se uložit stav uploadu.', 500);
    }
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

function rep_akce_pdf_upload_assert_transfer_offer(array $meta, int $offerId): void
{
    if ((int)($meta['offer_id'] ?? 0) !== $offerId) {
        rep_akce_pdf_upload_fail('Upload nepatří k této akční nabídce.', 409);
    }
}

function rep_akce_pdf_upload_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function rep_akce_pdf_upload_finalize(PDO $pdo, int $offerId, array $stored): void
{
    $offer = rep_akce_pdf_upload_offer($pdo, $offerId);
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

    $existingPath = trim((string)($offer['pdf_file'] ?? ''));
    $storedPath = trim((string)($stored['path'] ?? ''));
    if ($existingPath !== '' && $existingPath !== $storedPath) {
        rep_akce_safe_delete_media_file($existingPath);
    }
}

function rep_akce_pdf_upload_store_completed_file(PDO $pdo, string $id, array $meta): array
{
    $status = (string)($meta['status'] ?? 'uploading');
    if (in_array($status, ['file_stored', 'finalized'], true)) {
        $stored = is_array($meta['stored'] ?? null) ? $meta['stored'] : [];
        $storedPath = trim((string)($stored['path'] ?? ''));
        if ($storedPath === '' || !is_file(ROOT_DIR . '/' . ltrim($storedPath, '/'))) {
            rep_akce_pdf_upload_fail('Uložené PDF nebylo nalezeno.', 500);
        }
        if ($status === 'finalized') {
            $offer = rep_akce_pdf_upload_offer($pdo, (int)($meta['offer_id'] ?? 0));
            if (trim((string)($offer['pdf_file'] ?? '')) !== $storedPath) {
                rep_akce_pdf_upload_fail('K nabídce už bylo mezitím uloženo jiné PDF.', 409);
            }
        }
        return $stored;
    }

    $offerId = (int)($meta['offer_id'] ?? 0);
    $offer = rep_akce_pdf_upload_offer($pdo, $offerId);
    $title = trim((string)($offer['nazev_cz'] ?? ''));
    if ($title === '') {
        $title = 'akce-' . $offerId;
    }

    $sourcePath = rep_akce_pdf_upload_chunk_path($id);
    $originalName = trim((string)($meta['upload_name'] ?? 'nabidka.pdf'));
    $stored = rep_akce_store_pdf_file(
        $sourcePath,
        $originalName !== '' ? $originalName : 'nabidka.pdf',
        $offerId,
        $title,
        (string)($offer['pdf_file'] ?? ''),
        false,
        false
    );

    $meta['status'] = 'file_stored';
    $meta['stored'] = $stored;
    $meta['stored_at'] = time();
    rep_akce_pdf_upload_save_meta($id, $meta);

    return $stored;
}

function rep_akce_pdf_upload_handle_finalize(PDO $pdo): never
{
    $offerId = rep_akce_pdf_upload_offer_id();
    rep_akce_pdf_upload_offer($pdo, $offerId);

    $id = rep_akce_pdf_upload_transfer_id();
    $meta = rep_akce_pdf_upload_meta($id);
    rep_akce_pdf_upload_assert_transfer_offer($meta, $offerId);

    if ((string)($meta['status'] ?? '') === 'finalized') {
        $stored = rep_akce_pdf_upload_store_completed_file($pdo, $id, $meta);
        rep_akce_pdf_upload_json([
            'ok' => true,
            'path' => (string)($stored['path'] ?? ''),
            'original_name' => (string)($stored['original_name'] ?? $meta['upload_name'] ?? ''),
            'filesize' => (int)($stored['filesize'] ?? 0),
        ]);
    }

    $uploadLength = (int)($meta['upload_length'] ?? 0);
    $path = rep_akce_pdf_upload_chunk_path($id);
    $actualSize = is_file($path) ? (int)filesize($path) : 0;
    if ((string)($meta['status'] ?? 'uploading') === 'uploading' && ($uploadLength <= 0 || $actualSize !== $uploadLength)) {
        header('Upload-Offset: ' . $actualSize);
        rep_akce_pdf_upload_fail('PDF není kompletní. Přijato ' . $actualSize . ' z ' . $uploadLength . ' bajtů.', 409);
    }

    $stored = rep_akce_pdf_upload_store_completed_file($pdo, $id, $meta);

    try {
        rep_akce_pdf_upload_finalize($pdo, $offerId, $stored);
    } catch (Throwable $e) {
        rep_akce_pdf_upload_fail('PDF je přenesené, ale nepodařilo se zapsat k nabídce: ' . $e->getMessage(), 500);
    }

    $meta = rep_akce_pdf_upload_meta($id);
    $meta['status'] = 'finalized';
    $meta['finalized_at'] = time();
    rep_akce_pdf_upload_save_meta($id, $meta);

    rep_akce_pdf_upload_json([
        'ok' => true,
        'path' => (string)($stored['path'] ?? ''),
        'original_name' => (string)($stored['original_name'] ?? $meta['upload_name'] ?? ''),
        'filesize' => (int)($stored['filesize'] ?? 0),
    ]);
}

function rep_akce_pdf_upload_handle_post(PDO $pdo): never
{
    rep_akce_pdf_upload_assert_access();
    rep_akce_pdf_upload_cleanup_old_transfers();

    if ((string)($_POST['action'] ?? '') === 'finalize') {
        rep_akce_pdf_upload_handle_finalize($pdo);
    }

    $offerId = rep_akce_pdf_upload_offer_id();
    $offer = rep_akce_pdf_upload_offer($pdo, $offerId);

    $uploadLength = rep_akce_pdf_upload_int_header('Upload-Length');
    if ($uploadLength > 0 && empty($_FILES)) {
        $id = bin2hex(random_bytes(16));
        $chunkSize = rep_akce_pdf_upload_int_header('X-Chunk-Size');
        rep_akce_pdf_upload_save_meta($id, [
            'offer_id' => $offerId,
            'upload_length' => $uploadLength,
            'chunk_size' => $chunkSize,
            'status' => 'uploading',
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

function rep_akce_pdf_upload_handle_head(PDO $pdo): never
{
    rep_akce_pdf_upload_assert_access();
    $id = rep_akce_pdf_upload_transfer_id();
    $meta = rep_akce_pdf_upload_meta($id);
    $offerId = rep_akce_pdf_upload_offer_id();
    rep_akce_pdf_upload_offer($pdo, $offerId);
    rep_akce_pdf_upload_assert_transfer_offer($meta, $offerId);
    $path = rep_akce_pdf_upload_chunk_path($id);
    header('Upload-Offset: ' . (is_file($path) ? (int)filesize($path) : 0));
    header('Upload-Length: ' . (int)($meta['upload_length'] ?? 0));
    header('Cache-Control: no-store');
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
    rep_akce_pdf_upload_assert_transfer_offer($meta, rep_akce_pdf_upload_offer_id());
    if ((string)($meta['status'] ?? 'uploading') !== 'uploading') {
        rep_akce_pdf_upload_fail('Upload už byl dokončen.', 409);
    }

    $offset = rep_akce_pdf_upload_int_header('Upload-Offset');
    $requestLength = rep_akce_pdf_upload_int_header('Upload-Length');
    $length = (int)($meta['upload_length'] ?? 0);
    $name = rep_akce_pdf_upload_header('Upload-Name', (string)($meta['upload_name'] ?? 'nabidka.pdf'));
    if ($length <= 0) {
        rep_akce_pdf_upload_fail('Chybí velikost uploadu.', 400);
    }
    if ($requestLength > 0 && $requestLength !== $length) {
        rep_akce_pdf_upload_fail('Velikost uploadu se během přenosu změnila.', 409);
    }

    $path = rep_akce_pdf_upload_chunk_path($id);
    $currentSize = is_file($path) ? (int)filesize($path) : 0;
    if ($offset !== $currentSize) {
        header('Upload-Offset: ' . $currentSize);
        rep_akce_pdf_upload_fail('Nesouhlasí offset chunk uploadu.', 409);
    }

    $contentLength = max(0, (int)($_SERVER['CONTENT_LENGTH'] ?? 0));
    $remaining = $length - $currentSize;
    if ($remaining <= 0) {
        if ($remaining === 0 && $contentLength === 0) {
            header('Upload-Offset: ' . $currentSize);
            header('Cache-Control: no-store');
            http_response_code(204);
            exit;
        }
        rep_akce_pdf_upload_fail('Upload už obsahuje deklarovaný počet bajtů.', 409);
    }

    $declaredChunkSize = rep_akce_pdf_upload_int_header('X-Chunk-Size');
    $storedChunkSize = (int)($meta['chunk_size'] ?? 0);
    if ($storedChunkSize <= 0) {
        $storedChunkSize = $declaredChunkSize > 0 ? $declaredChunkSize : $contentLength;
    }
    if ($storedChunkSize <= 0) {
        rep_akce_pdf_upload_fail('Chybí velikost chunku.', 400);
    }
    if ($declaredChunkSize > 0 && $declaredChunkSize !== $storedChunkSize) {
        rep_akce_pdf_upload_fail('Velikost chunku se během přenosu změnila.', 409);
    }

    $expectedBytes = min($storedChunkSize, $remaining);
    if ($contentLength > 0 && $contentLength !== $expectedBytes) {
        header('Upload-Offset: ' . $currentSize);
        rep_akce_pdf_upload_fail('Chunk má neplatnou délku. Očekáváno ' . $expectedBytes . ' bajtů, přijato ' . $contentLength . '.', 400);
    }

    $input = fopen('php://input', 'rb');
    $output = fopen($path, 'c+b');
    if (!$input || !$output) {
        rep_akce_pdf_upload_fail('Chunk se nepodařilo uložit.', 500);
    }

    if (!flock($output, LOCK_EX)) {
        fclose($input);
        fclose($output);
        rep_akce_pdf_upload_fail('Chunk se nepodařilo uzamknout pro zápis.', 500);
    }
    $lockedSize = (int)(fstat($output)['size'] ?? 0);
    if ($lockedSize !== $currentSize || $offset !== $lockedSize || fseek($output, 0, SEEK_END) !== 0) {
        flock($output, LOCK_UN);
        fclose($input);
        fclose($output);
        header('Upload-Offset: ' . $lockedSize);
        rep_akce_pdf_upload_fail('Nesouhlasí offset chunk uploadu.', 409);
    }

    $written = stream_copy_to_stream($input, $output, $expectedBytes + 1);
    fflush($output);
    if ($written !== $expectedBytes) {
        ftruncate($output, $currentSize);
        fflush($output);
        flock($output, LOCK_UN);
        fclose($input);
        fclose($output);
        header('Upload-Offset: ' . $currentSize);
        rep_akce_pdf_upload_fail(
            'Chunk se nepodařilo přijmout celý. Očekáváno ' . $expectedBytes . ' bajtů, uloženo ' . (int)$written . '. Upload bude opakován.',
            500
        );
    }

    flock($output, LOCK_UN);
    fclose($input);
    fclose($output);

    clearstatcache(true, $path);
    $newSize = is_file($path) ? (int)filesize($path) : 0;
    if ($newSize !== $currentSize + $expectedBytes || $newSize > $length) {
        header('Upload-Offset: ' . $currentSize);
        rep_akce_pdf_upload_fail('Po uložení chunku nesouhlasí velikost souboru.', 500);
    }
    $meta['upload_name'] = $name;
    $meta['upload_length'] = $length;
    $meta['chunk_size'] = $storedChunkSize;
    $meta['updated_at'] = time();
    rep_akce_pdf_upload_save_meta($id, $meta);

    header('Upload-Offset: ' . $newSize);
    header('Cache-Control: no-store');
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
        rep_akce_pdf_upload_handle_head($pdo);
    }

    rep_akce_pdf_upload_fail('Method not allowed', 405);
} catch (Throwable $e) {
    rep_akce_pdf_upload_fail($e->getMessage(), 500);
}
