<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function galerie_media_root(): string
{
    return ROOT_DIR . '/media/galerie';
}

function galerie_gallery_dir(int $galleryId): string
{
    return galerie_media_root() . '/' . $galleryId . '-galerie';
}

function galerie_gallery_small_dir(int $galleryId): string
{
    return galerie_gallery_dir($galleryId) . '/small';
}

function galerie_media_url(int $galleryId, string $file, bool $small = false): string
{
    $base = defined('BASE_URL') ? BASE_URL : '/';
    $path = 'media/galerie/' . $galleryId . '-galerie/' . ($small ? 'small/' : '') . ltrim($file, '/');
    return rtrim((string)$base, '/') . '/' . $path;
}

function galerie_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function galerie_user(): string
{
    return function_exists('admin_session_user') ? admin_session_user() : 'system';
}

function galerie_int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $int = (int)$value;
    return $int > 0 ? $int : null;
}

function galerie_date_db(?string $date): ?string
{
    $date = trim((string)$date);
    if ($date === '') {
        return null;
    }

    if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $date) === 1) {
        return $date;
    }

    if (function_exists('format_date_db')) {
        return format_date_db($date);
    }

    return null;
}

function galerie_date_form(?string $date): string
{
    $date = trim((string)$date);
    if ($date === '') {
        return date('Y-m-d');
    }

    return substr($date, 0, 10);
}

function galerie_datetime_www(mixed $date): string
{
    $date = trim((string)$date);
    if ($date === '') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($date) : $date;
}

function galerie_setting_int(string $name, int $default): int
{
    if (!function_exists('sp_hodnota')) {
        return $default;
    }

    $value = (int)(sp_hodnota($name) ?? 0);
    return $value > 0 ? $value : $default;
}

function galerie_image_quality(): int
{
    return max(1, min(100, galerie_setting_int('galerie_image_quality', 85)));
}

function galerie_orig_width_limit(): int
{
    return galerie_setting_int('galerie_orig_width', 1920);
}

function galerie_orig_height_limit(): int
{
    return galerie_setting_int('galerie_orig_height', 1920);
}

function galerie_thumb_width_limit(): int
{
    return galerie_setting_int('galerie_thumb_width', 480);
}

function galerie_thumb_height_limit(): int
{
    return galerie_setting_int('galerie_thumb_height', 480);
}

function galerie_ensure_directories(int $galleryId): void
{
    foreach ([galerie_media_root(), galerie_gallery_dir($galleryId), galerie_gallery_small_dir($galleryId)] as $dir) {
        if (is_dir($dir)) {
            continue;
        }

        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Nepodarilo se vytvorit adresar: ' . $dir);
        }
    }
}

function galerie_bind_limit(PDOStatement $stmt, int $limit): void
{
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
}

function galerie_types_count(int $valid = 1): int
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie_typ WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function galerie_types_all(bool $onlyValid = true, int $limit = 0, int $valid = 1): array
{
    global $pdo;

    $params = [];
    $sql = 'SELECT * FROM galerie_typ';
    if ($onlyValid) {
        $sql .= ' WHERE valid = :valid';
        $params[':valid'] = $valid;
    }
    $sql .= ' ORDER BY poradi ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    galerie_bind_limit($stmt, $limit);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function galerie_type_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM galerie_typ WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function galerie_typ_option_form(int $selected = 0): void
{
    echo '<option value="0">Bez typu</option>';
    foreach (galerie_types_all() as $type) {
        $id = (int)$type['id'];
        $sel = $id === $selected ? ' selected' : '';
        echo '<option value="' . $id . '"' . $sel . '>' . galerie_e($type['nazev_cz'] ?? '') . '</option>';
    }
}

function galerie_type_save(array $data, ?int $id = null): int
{
    global $pdo;

    $user = galerie_user();
    $payload = [
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':popis_cz' => trim((string)($data['popis_cz'] ?? '')),
        ':popis_en' => trim((string)($data['popis_en'] ?? '')),
        ':user_u' => $user,
    ];

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO galerie_typ (poradi, nazev_cz, nazev_en, popis_cz, popis_en, user_i, user_u)
            VALUES (:poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :user_i, :user_u)');
        $payload[':user_i'] = $user;
        $stmt->execute($payload);
        $newId = (int)$pdo->lastInsertId();
        admin_auto_translate_record('galerie.type', $newId, $data);
        return $newId;
    }

    $payload[':id'] = $id;
    $payload[':valid'] = isset($data['valid']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE galerie_typ
        SET poradi = :poradi, nazev_cz = :nazev_cz, nazev_en = :nazev_en, popis_cz = :popis_cz, popis_en = :popis_en, valid = :valid, user_u = :user_u
        WHERE id = :id');
    $stmt->execute($payload);
    admin_auto_translate_record('galerie.type', $id, $data);

    return $id;
}

function galerie_type_delete(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE galerie_typ SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([':user_u' => galerie_user(), ':id' => $id]);
}

function galerie_count(?int $typeId = null, int $valid = 1): int
{
    global $pdo;

    $params = [':valid' => $valid];
    $where = 'valid = :valid';
    if ($typeId !== null && $typeId > 0) {
        $where .= ' AND galerie_typ = :type_id';
        $params[':type_id'] = $typeId;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM galerie WHERE $where");
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function galerie_all(?int $typeId = null, int $valid = 1, int $limit = 0): array
{
    global $pdo;

    $params = [':valid' => $valid];
    $where = 'g.valid = :valid';
    if ($typeId !== null && $typeId > 0) {
        $where .= ' AND g.galerie_typ = :type_id';
        $params[':type_id'] = $typeId;
    }

    $stmt = $pdo->prepare("SELECT g.*, gt.nazev_cz AS typ_nazev,
            (SELECT COUNT(*) FROM galerie_photo gp WHERE gp.galerie_id = g.id AND gp.valid = 1) AS photo_count
        FROM galerie g
        LEFT JOIN galerie_typ gt ON gt.id = g.galerie_typ
        WHERE $where
        ORDER BY g.datum DESC, g.id DESC" . ($limit > 0 ? ' LIMIT :limit' : ''));
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    galerie_bind_limit($stmt, $limit);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function galerie_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM galerie WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function galerie_save(array $data, ?int $id = null): int
{
    global $pdo;

    $user = galerie_user();
    $payload = [
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':datum' => galerie_date_db((string)($data['datum'] ?? '')),
        ':galerie_typ' => galerie_int_or_null($data['galerie_typ'] ?? null),
        ':popis_cz' => editor_html((string)($data['popis_cz'] ?? '')),
        ':popis_en' => editor_html((string)($data['popis_en'] ?? '')),
        ':user_u' => $user,
    ];

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO galerie (nazev_cz, nazev_en, datum, galerie_typ, popis_cz, popis_en, user_i, user_u)
            VALUES (:nazev_cz, :nazev_en, :datum, :galerie_typ, :popis_cz, :popis_en, :user_i, :user_u)');
        $payload[':user_i'] = $user;
        $stmt->execute($payload);
        $id = (int)$pdo->lastInsertId();
        galerie_ensure_directories($id);
        admin_auto_translate_record('galerie.record', $id, $data);
        return $id;
    }

    $payload[':id'] = $id;
    $payload[':valid'] = isset($data['valid']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE galerie
        SET nazev_cz = :nazev_cz, nazev_en = :nazev_en, datum = :datum, galerie_typ = :galerie_typ,
            popis_cz = :popis_cz, popis_en = :popis_en, valid = :valid, user_u = :user_u
        WHERE id = :id');
    $stmt->execute($payload);
    galerie_ensure_directories($id);
    admin_auto_translate_record('galerie.record', $id, $data);

    return $id;
}

function galerie_delete(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE galerie SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([':user_u' => galerie_user(), ':id' => $id]);
}

function galerie_photos_count(int $galleryId, int $valid = 1): int
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie_photo WHERE galerie_id = :gallery_id AND valid = :valid');
    $stmt->execute([':gallery_id' => $galleryId, ':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function galerie_photos(int $galleryId, int $valid = 1, int $limit = 0): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM galerie_photo WHERE galerie_id = :gallery_id AND valid = :valid ORDER BY poradi ASC, id ASC' . ($limit > 0 ? ' LIMIT :limit' : ''));
    $stmt->bindValue(':gallery_id', $galleryId, PDO::PARAM_INT);
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    galerie_bind_limit($stmt, $limit);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function galerie_photo_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM galerie_photo WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function galerie_next_photo_order(int $galleryId): int
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(poradi), 0) + 1 FROM galerie_photo WHERE galerie_id = :gallery_id');
    $stmt->execute([':gallery_id' => $galleryId]);

    return (int)$stmt->fetchColumn();
}

function galerie_photo_save_meta(int $photoId, array $data): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE galerie_photo
        SET poradi = :poradi, nazev_cz = :nazev_cz, nazev_en = :nazev_en, valid = :valid, user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':valid' => isset($data['valid']) ? 1 : 0,
        ':user_u' => galerie_user(),
        ':id' => $photoId,
    ]);
    admin_auto_translate_record('galerie.photo', $photoId, $data);
}

function galerie_photo_save_order(int $galleryId, array $photoIds): int
{
    global $pdo;

    $ids = [];
    foreach ($photoIds as $photoId) {
        $id = (int)$photoId;
        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    if ($ids === []) {
        return 0;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE galerie_photo SET poradi = :poradi, user_u = :user_u WHERE id = :id AND galerie_id = :gallery_id');
        $order = 1;
        $updated = 0;
        foreach ($ids as $id) {
            $stmt->execute([
                ':poradi' => $order,
                ':user_u' => galerie_user(),
                ':id' => $id,
                ':gallery_id' => $galleryId,
            ]);
            $updated += $stmt->rowCount();
            $order++;
        }
        $pdo->commit();

        return $updated;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function galerie_photo_delete(int $photoId, bool $deleteFiles = false): void
{
    global $pdo;

    $photo = galerie_photo_get($photoId);
    if ($photo === null) {
        return;
    }

    // Standard admin delete is a recoverable soft-delete. Physical cleanup must be an explicit maintenance action.
    if ($deleteFiles) {
        $galleryId = (int)$photo['galerie_id'];
        $file = basename((string)$photo['soubor']);
        @unlink(galerie_gallery_dir($galleryId) . '/' . $file);
        @unlink(galerie_gallery_small_dir($galleryId) . '/' . $file);
    }

    $stmt = $pdo->prepare('UPDATE galerie_photo SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([':user_u' => galerie_user(), ':id' => $photoId]);
}

function galerie_photo_file_has_valid_reference(int $galleryId, string $file): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM galerie_photo WHERE galerie_id = :gallery_id AND soubor = :soubor AND valid = 1');
    $stmt->execute([
        ':gallery_id' => $galleryId,
        ':soubor' => $file,
    ]);

    return (int)$stmt->fetchColumn() > 0;
}

function galerie_invalid_photo_files_delete(int $galleryId): array
{
    $result = [
        'photos' => 0,
        'files_deleted' => 0,
        'files_missing' => 0,
        'files_skipped' => 0,
        'errors' => [],
    ];

    $handled = [];
    foreach (galerie_photos($galleryId, 0) as $photo) {
        $result['photos']++;

        $file = basename((string)($photo['soubor'] ?? ''));
        if ($file === '' || isset($handled[$file])) {
            continue;
        }
        $handled[$file] = true;

        if (galerie_photo_file_has_valid_reference($galleryId, $file)) {
            $result['files_skipped']++;
            continue;
        }

        foreach ([galerie_gallery_dir($galleryId) . '/' . $file, galerie_gallery_small_dir($galleryId) . '/' . $file] as $path) {
            if (!is_file($path)) {
                $result['files_missing']++;
                continue;
            }

            if (@unlink($path)) {
                $result['files_deleted']++;
            } else {
                $result['errors'][] = 'Soubor se nepodarilo smazat: ' . $path;
            }
        }
    }

    return $result;
}

function galerie_safe_filename(string $name): string
{
    $info = pathinfo($name);
    $base = text_str((string)($info['filename'] ?? 'foto'));
    $ext = strtolower((string)($info['extension'] ?? 'jpg'));
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;

    if ($base === '') {
        $base = 'foto';
    }

    return $base . '.' . $ext;
}

function galerie_unique_filename(int $galleryId, string $filename): string
{
    $info = pathinfo($filename);
    $base = (string)($info['filename'] ?? 'foto');
    $ext = strtolower((string)($info['extension'] ?? 'jpg'));
    $candidate = $base . '.' . $ext;
    $i = 1;

    while (is_file(galerie_gallery_dir($galleryId) . '/' . $candidate)) {
        $candidate = $base . '-' . $i . '.' . $ext;
        $i++;
    }

    return $candidate;
}

function galerie_allowed_upload(string $path): array
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($path);
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($map[$mime])) {
        throw new RuntimeException('Nepodporovany format obrazku: ' . $mime . '. Povoleno je JPG, PNG a WebP.');
    }

    if ($mime === 'image/webp' && (!function_exists('imagewebp') || !function_exists('imagecreatefromwebp'))) {
        throw new RuntimeException('Server nema podporu pro WebP v GD.');
    }

    return [$mime, $map[$mime]];
}

function galerie_load_image(string $path, string $mime): GdImage
{
    $image = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png' => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        default => false,
    };

    if (!$image instanceof GdImage) {
        throw new RuntimeException('Obrazek se nepodarilo nacist.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int)($exif['Orientation'] ?? 0) : 0;
        if ($orientation === 3) {
            $image = imagerotate($image, 180, 0);
        } elseif ($orientation === 6) {
            $image = imagerotate($image, -90, 0);
        } elseif ($orientation === 8) {
            $image = imagerotate($image, 90, 0);
        }
    }

    return $image;
}

function galerie_resize_to_file(string $sourcePath, string $targetPath, string $mime, int $maxWidth, int $maxHeight): array
{
    $source = galerie_load_image($sourcePath, $mime);
    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);

    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
    $targetWidth = max(1, (int)round($sourceWidth * $ratio));
    $targetHeight = max(1, (int)round($sourceHeight * $ratio));

    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (in_array($mime, ['image/png', 'image/webp'], true)) {
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

    $quality = galerie_image_quality();
    $saved = match ($mime) {
        'image/jpeg' => imagejpeg($target, $targetPath, $quality),
        'image/png' => imagepng($target, $targetPath, 6),
        'image/webp' => imagewebp($target, $targetPath, $quality),
        default => false,
    };

    imagedestroy($source);
    imagedestroy($target);

    if (!$saved) {
        throw new RuntimeException('Obrazek se nepodarilo ulozit: ' . $targetPath);
    }

    return [$targetWidth, $targetHeight, filesize($targetPath) ?: 0];
}

function galerie_insert_photo(int $galleryId, string $file, array $meta): int
{
    global $pdo;

    $title = trim((string)($meta['title'] ?? pathinfo($file, PATHINFO_FILENAME)));
    $order = (int)($meta['poradi'] ?? galerie_next_photo_order($galleryId));
    $user = galerie_user();

    $stmt = $pdo->prepare('INSERT INTO galerie_photo
        (galerie_id, poradi, nazev_cz, nazev_en, soubor, mime_type, width, height, filesize, user_i, user_u)
        VALUES (:galerie_id, :poradi, :nazev_cz, :nazev_en, :soubor, :mime_type, :width, :height, :filesize, :user_i, :user_u)');
    $stmt->execute([
        ':galerie_id' => $galleryId,
        ':poradi' => $order,
        ':nazev_cz' => $title,
        ':nazev_en' => '',
        ':soubor' => $file,
        ':mime_type' => (string)($meta['mime_type'] ?? ''),
        ':width' => (int)($meta['width'] ?? 0),
        ':height' => (int)($meta['height'] ?? 0),
        ':filesize' => (int)($meta['filesize'] ?? 0),
        ':user_i' => $user,
        ':user_u' => $user,
    ]);

    return (int)$pdo->lastInsertId();
}

function galerie_upload_photos(int $galleryId, array $files): array
{
    galerie_ensure_directories($galleryId);

    $results = ['ok' => [], 'error' => []];
    $names = $files['name'] ?? [];
    $count = is_array($names) ? count($names) : 0;
    $order = galerie_next_photo_order($galleryId);

    for ($i = 0; $i < $count; $i++) {
        $error = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $originalName = (string)($files['name'][$i] ?? '');
        $tmpName = (string)($files['tmp_name'][$i] ?? '');

        try {
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload chyba ' . $error . '.');
            }

            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                throw new RuntimeException('Dočasný soubor uploadu není dostupný.');
            }

            [$mime, $ext] = galerie_allowed_upload($tmpName);
            $safeName = galerie_safe_filename($originalName);
            $safeName = preg_replace('~\.[a-z0-9]+$~', '.' . $ext, $safeName) ?: ('foto.' . $ext);
            $file = galerie_unique_filename($galleryId, $safeName);
            $originalPath = galerie_gallery_dir($galleryId) . '/' . $file;
            $thumbPath = galerie_gallery_small_dir($galleryId) . '/' . $file;

            [$width, $height, $filesize] = galerie_resize_to_file(
                $tmpName,
                $originalPath,
                $mime,
                galerie_orig_width_limit(),
                galerie_orig_height_limit()
            );
            galerie_resize_to_file(
                $originalPath,
                $thumbPath,
                $mime,
                galerie_thumb_width_limit(),
                galerie_thumb_height_limit()
            );

            galerie_insert_photo($galleryId, $file, [
                'poradi' => $order,
                'title' => pathinfo($originalName, PATHINFO_FILENAME),
                'mime_type' => $mime,
                'width' => $width,
                'height' => $height,
                'filesize' => $filesize,
            ]);
            $results['ok'][] = $originalName;
            $order++;
        } catch (Throwable $e) {
            $results['error'][] = $originalName . ': ' . $e->getMessage();
        }
    }

    return $results;
}

function galerie_regenerate_thumbnails(int $galleryId): array
{
    $results = ['ok' => 0, 'error' => []];
    galerie_ensure_directories($galleryId);

    foreach (galerie_photos($galleryId) as $photo) {
        $file = basename((string)$photo['soubor']);
        $originalPath = galerie_gallery_dir($galleryId) . '/' . $file;
        $thumbPath = galerie_gallery_small_dir($galleryId) . '/' . $file;

        try {
            if (!is_file($originalPath)) {
                throw new RuntimeException('Soubor neexistuje: ' . $file);
            }

            [$mime] = galerie_allowed_upload($originalPath);
            galerie_resize_to_file(
                $originalPath,
                $thumbPath,
                $mime,
                galerie_thumb_width_limit(),
                galerie_thumb_height_limit()
            );
            $results['ok']++;
        } catch (Throwable $e) {
            $results['error'][] = $file . ': ' . $e->getMessage();
        }
    }

    return $results;
}

function galerie_photo_poradi_update(int $galleryId): void
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM galerie_photo WHERE galerie_id = :gallery_id AND valid = 1 ORDER BY soubor ASC, id ASC');
    $stmt->execute([':gallery_id' => $galleryId]);

    $order = 1;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $photoId) {
        $update = $pdo->prepare('UPDATE galerie_photo SET poradi = :poradi WHERE id = :id');
        $update->execute([':poradi' => $order, ':id' => (int)$photoId]);
        $order++;
    }
}

function galerie_photo_duplicity_delete(int $galleryId): int
{
    global $pdo;

    $sql = 'UPDATE galerie_photo gp1
        INNER JOIN galerie_photo gp2
            ON gp1.id > gp2.id
            AND gp1.soubor = gp2.soubor
            AND gp1.galerie_id = gp2.galerie_id
            AND gp2.valid = 1
        SET gp1.valid = 0, gp1.user_u = :user_u
        WHERE gp1.galerie_id = :gallery_id AND gp1.valid = 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_u' => galerie_user(), ':gallery_id' => $galleryId]);

    return $stmt->rowCount();
}
