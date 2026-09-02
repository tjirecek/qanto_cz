<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function rep_akce_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_akce_user(): string
{
    return function_exists('admin_session_user') ? admin_session_user() : 'system';
}

function rep_akce_bool_label(mixed $value): string
{
    return (int)$value === 1 ? 'ANO' : 'NE';
}

function rep_akce_bool_badge(mixed $value): string
{
    $isEnabled = (int)$value === 1;
    return '<span class="badge text-bg-' . ($isEnabled ? 'success' : 'secondary') . '">' . ($isEnabled ? 'ANO' : 'NE') . '</span>';
}

function rep_akce_badge_class(string $class): string
{
    $class = trim($class);
    if ($class === '') {
        return 'text-bg-light border';
    }

    $classes = preg_split('~\s+~', $class) ?: [];
    $safeClasses = array_filter($classes, static function (string $item): bool {
        return (bool)preg_match('~^[a-zA-Z0-9_-]+$~', $item);
    });

    return $safeClasses === [] ? 'text-bg-light border' : implode(' ', $safeClasses);
}

function rep_akce_type_badge(array $type): string
{
    $label = trim((string)($type['nazev_cz'] ?? ''));
    if ($label === '') {
        $label = trim((string)($type['code'] ?? ''));
    }
    if ($label === '') {
        $label = 'typ';
    }

    return '<span class="badge ' . rep_akce_e(rep_akce_badge_class((string)($type['color'] ?? ''))) . '">' . rep_akce_e($label) . '</span>';
}

function rep_akce_newsletter_group(mixed $value): string
{
    $group = trim(mb_strtolower((string)$value, 'UTF-8'));
    return in_array($group, ['maloobchod', 'velkoobchod', 'obe_skupiny'], true) ? $group : '';
}

function rep_akce_newsletter_group_label(mixed $value): string
{
    return match (rep_akce_newsletter_group($value)) {
        'maloobchod' => 'Maloobchodní odběratelé',
        'velkoobchod' => 'Velkoobchodní odběratelé',
        'obe_skupiny' => 'Maloobchodní i velkoobchodní odběratelé',
        default => 'Neodesílat',
    };
}

function rep_akce_parse_ids(mixed $value): array
{
    $values = is_array($value) ? $value : [$value];
    $ids = [];
    foreach ($values as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function rep_akce_date_db(mixed $value): ?string
{
    $value = trim((string)($value ?? ''));
    if ($value === '') {
        return null;
    }
    if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $value) === 1) {
        return $value;
    }
    if (function_exists('format_date_db')) {
        $formatted = format_date_db($value);
        return $formatted !== '' ? (string)$formatted : null;
    }
    return null;
}

function rep_akce_date_form(mixed $value): string
{
    $value = trim((string)($value ?? ''));
    return $value === '' ? '' : substr($value, 0, 10);
}

function rep_akce_date_www(mixed $value): string
{
    $value = rep_akce_date_form($value);
    if ($value === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($value))->format('d.m.Y');
    } catch (Throwable) {
        return $value;
    }
}

function rep_akce_format_updated(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value;
}

/** @param array<string, mixed> $row */
function rep_akce_updated_cell(array $row): string
{
    $date = rep_akce_format_updated($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_akce_e($user) . '</small>' : '';
    }

    return rep_akce_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_akce_e($user) . '</small>' : '');
}

function rep_akce_file_url(string $relativePath): string
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '') {
        return '';
    }
    $base = defined('BASE_URL') ? (string)BASE_URL : '/';
    return rtrim($base, '/') . '/' . $relativePath;
}

function rep_akce_file_exists(string $relativePath): bool
{
    $relativePath = ltrim(trim($relativePath), '/');
    return $relativePath !== '' && is_file(ROOT_DIR . '/' . $relativePath);
}

function rep_akce_media_root(): string
{
    return ROOT_DIR . '/media/akce';
}

function rep_akce_slug(string $value, string $fallback = 'akce'): string
{
    $slug = function_exists('text_str') ? text_str($value) : strtolower(preg_replace('~[^a-z0-9]+~i', '-', $value) ?? '');
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : $fallback;
}

function rep_akce_offer_relative_dir(int $id, string $title): string
{
    return 'media/akce/' . $id . '-' . rep_akce_slug($title, 'akce');
}

function rep_akce_ensure_offer_dir(int $id, string $title): string
{
    $relativeDir = rep_akce_offer_relative_dir($id, $title);
    $absoluteDir = ROOT_DIR . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodařilo se vytvořit adresář pro akční nabídku.');
    }
    return $relativeDir;
}

function rep_akce_safe_delete_media_file(string $relativePath): void
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '') {
        return;
    }
    $base = realpath(rep_akce_media_root());
    $path = realpath(ROOT_DIR . '/' . $relativePath);
    if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }
    if (is_file($path)) {
        @unlink($path);
    }
}

function rep_akce_upload_pdf(?array $file, int $id, string $title, string $existingPath = ''): array
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => $existingPath, 'original_name' => '', 'filesize' => 0, 'changed' => false];
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('PDF se nepodařilo nahrát.');
    }
    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Dočasný PDF soubor není dostupný.');
    }

    return rep_akce_store_pdf_file($tmpName, (string)($file['name'] ?? 'nabidka.pdf'), $id, $title, $existingPath, true);
}

function rep_akce_store_pdf_file(
    string $sourcePath,
    string $originalName,
    int $id,
    string $title,
    string $existingPath = '',
    bool $isUploadedFile = false,
    bool $deleteExisting = true
): array
{
    if ($sourcePath === '' || !is_file($sourcePath)) {
        throw new RuntimeException('PDF soubor není dostupný.');
    }
    $handle = fopen($sourcePath, 'rb');
    $header = $handle ? fread($handle, 4) : '';
    if (is_resource($handle)) {
        fclose($handle);
    }
    if ($header !== '%PDF') {
        throw new RuntimeException('Nahraný soubor není platné PDF.');
    }

    $relativeDir = rep_akce_ensure_offer_dir($id, $title);
    $baseName = rep_akce_slug(pathinfo($originalName !== '' ? $originalName : 'nabidka.pdf', PATHINFO_FILENAME), 'nabidka');
    $targetName = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    $relativePath = $relativeDir . '/' . $targetName;
    $targetPath = ROOT_DIR . '/' . $relativePath;
    $stored = $isUploadedFile ? move_uploaded_file($sourcePath, $targetPath) : rename($sourcePath, $targetPath);
    if (!$stored && !$isUploadedFile && copy($sourcePath, $targetPath)) {
        @unlink($sourcePath);
        $stored = true;
    }
    if (!$stored) {
        throw new RuntimeException('PDF se nepodařilo uložit.');
    }
    if ($deleteExisting && $existingPath !== '' && $existingPath !== $relativePath) {
        rep_akce_safe_delete_media_file($existingPath);
    }

    return [
        'path' => $relativePath,
        'original_name' => $originalName,
        'filesize' => (int)filesize($targetPath),
        'changed' => true,
    ];
}

function rep_akce_upload_cover(?array $file, int $id, string $title, string $existingPath = ''): array
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => $existingPath, 'changed' => false];
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Obrázek se nepodařilo nahrát.');
    }
    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Dočasný soubor obrázku není dostupný.');
    }
    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('Soubor není podporovaný obrázek.');
    }
    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensionMap[$mime])) {
        throw new RuntimeException('Podporované formáty obálky jsou JPG, PNG a WebP.');
    }

    $relativeDir = rep_akce_ensure_offer_dir($id, $title);
    $baseName = rep_akce_slug(pathinfo((string)($file['name'] ?? 'obalka'), PATHINFO_FILENAME), 'obalka');
    $targetName = $baseName . '-' . date('YmdHis') . '.' . $extensionMap[$mime];
    $relativePath = $relativeDir . '/' . $targetName;
    if (!move_uploaded_file($tmpName, ROOT_DIR . '/' . $relativePath)) {
        throw new RuntimeException('Obrázek se nepodařilo uložit.');
    }
    if ($existingPath !== '' && $existingPath !== $relativePath) {
        rep_akce_safe_delete_media_file($existingPath);
    }

    return ['path' => $relativePath, 'changed' => true];
}

function rep_akce_image_extension(string $mime): ?string
{
    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    return $extensionMap[strtolower($mime)] ?? null;
}

function rep_akce_page_webp_quality(): int
{
    return rep_akce_page_image_quality();
}

function rep_akce_page_image_quality(): int
{
    $quality = function_exists('sp_hodnota') ? (int)(sp_hodnota('rep_akce_page_image_quality') ?? 0) : 0;
    if ($quality <= 0) {
        $quality = 82;
    }

    return max(1, min(100, $quality));
}

function rep_akce_page_target_kb(): int
{
    $targetKb = function_exists('sp_hodnota') ? (int)(sp_hodnota('rep_akce_page_target_kb') ?? 0) : 0;
    if ($targetKb <= 0) {
        $targetKb = 400;
    }

    return max(100, min(2048, $targetKb));
}

function rep_akce_page_output_format(): string
{
    $format = function_exists('sp_hodnota_text') ? strtolower(trim((string)(sp_hodnota_text('rep_akce_page_output_format') ?? ''))) : '';
    if ($format === '') {
        $format = 'webp';
    }
    if ($format === 'jpeg') {
        $format = 'jpg';
    }

    return in_array($format, ['webp', 'jpg', 'png'], true) ? $format : 'webp';
}

function rep_akce_page_output_mime(string $format): string
{
    return match ($format) {
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        default => 'image/webp',
    };
}

function rep_akce_load_page_image(string $sourcePath, string $mime): GdImage
{
    $image = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        'image/webp' => imagecreatefromwebp($sourcePath),
        default => false,
    };

    if (!$image instanceof GdImage) {
        throw new RuntimeException('Stránku akční nabídky se nepodařilo načíst pro převod do WebP.');
    }

    return $image;
}

function rep_akce_output_page_image_for_format(GdImage $image, string $format): GdImage
{
    imagepalettetotruecolor($image);

    if ($format === 'jpg') {
        $width = imagesx($image);
        $height = imagesy($image);
        $target = imagecreatetruecolor($width, $height);
        if (!$target instanceof GdImage) {
            throw new RuntimeException('Stránku akční nabídky se nepodařilo připravit pro JPG výstup.');
        }
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $width, $height, $white);
        imagecopy($target, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);
        return $target;
    }

    imagealphablending($image, true);
    imagesavealpha($image, true);
    return $image;
}

function rep_akce_save_page_image(string $sourcePath, string $mime, string $targetPath, string $format): void
{
    if ($format === 'webp' && !function_exists('imagewebp')) {
        throw new RuntimeException('Server nemá podporu pro ukládání WebP obrázků.');
    }
    if ($format === 'jpg' && !function_exists('imagejpeg')) {
        throw new RuntimeException('Server nemá podporu pro ukládání JPG obrázků.');
    }
    if ($format === 'png' && !function_exists('imagepng')) {
        throw new RuntimeException('Server nemá podporu pro ukládání PNG obrázků.');
    }

    if (strtolower($mime) === rep_akce_page_output_mime($format)) {
        if (!copy($sourcePath, $targetPath) || !is_file($targetPath)) {
            throw new RuntimeException('Stránku akční nabídky se nepodařilo uložit v cílovém formátu.');
        }
        return;
    }

    $image = rep_akce_load_page_image($sourcePath, $mime);
    $image = rep_akce_output_page_image_for_format($image, $format);

    $quality = rep_akce_page_image_quality();
    $saved = match ($format) {
        'jpg' => imagejpeg($image, $targetPath, $quality),
        'png' => imagepng($image, $targetPath, (int)round((100 - $quality) / 100 * 9)),
        default => imagewebp($image, $targetPath, $quality),
    };
    imagedestroy($image);

    if (!$saved || !is_file($targetPath)) {
        throw new RuntimeException('Stránku akční nabídky se nepodařilo uložit ve výstupním formátu.');
    }
}

function rep_akce_save_page_webp(string $sourcePath, string $mime, string $targetPath): void
{
    rep_akce_save_page_image($sourcePath, $mime, $targetPath, 'webp');
}

function rep_akce_page_variant_relative_path(string $imagePath, string $variant): string
{
    $imagePath = ltrim(trim($imagePath), '/');
    $variant = strtolower(trim($variant));
    if ($imagePath === '' || !in_array($variant, ['small', 'medium', 'thumbs'], true)) {
        return '';
    }

    $directory = dirname($imagePath);
    $baseName = pathinfo($imagePath, PATHINFO_FILENAME);
    return $directory . '/' . $variant . '/' . $baseName . '.webp';
}

function rep_akce_page_thumbnail_relative_path(string $imagePath): string
{
    return rep_akce_page_variant_relative_path($imagePath, 'thumbs');
}

function rep_akce_save_webp_with_size_limit(GdImage $image, string $path, int $maximumBytes, int $maximumQuality = 90): bool
{
    $minimumQuality = 45;
    $maximumQuality = max($minimumQuality, min(100, $maximumQuality));
    $temporaryPath = $path . '.tmp-' . bin2hex(random_bytes(5));
    $bestContents = null;
    $low = $minimumQuality;
    $high = $maximumQuality;

    while ($low <= $high) {
        $quality = (int)floor(($low + $high) / 2);
        if (!imagewebp($image, $temporaryPath, $quality) || !is_file($temporaryPath)) {
            @unlink($temporaryPath);
            return false;
        }
        $contents = file_get_contents($temporaryPath);
        @unlink($temporaryPath);
        if (!is_string($contents)) {
            return false;
        }

        if (strlen($contents) <= $maximumBytes || $quality === $minimumQuality) {
            $bestContents = $contents;
            $low = $quality + 1;
        } else {
            $high = $quality - 1;
        }
    }

    return is_string($bestContents) && file_put_contents($path, $bestContents, LOCK_EX) !== false;
}

function rep_akce_create_page_variant(string $imagePath, string $variant, float $scale, int $maximumLongEdge, int $maximumBytes, int $maximumQuality): string
{
    if (!function_exists('imagewebp')) {
        return '';
    }

    $imagePath = ltrim(trim($imagePath), '/');
    $sourcePath = ROOT_DIR . '/' . $imagePath;
    $imageInfo = @getimagesize($sourcePath);
    if ($imagePath === '' || $imageInfo === false) {
        return '';
    }

    $width = max(1, (int)($imageInfo[0] ?? 0));
    $height = max(1, (int)($imageInfo[1] ?? 0));
    $longEdge = max($width, $height);
    $targetLongEdge = max(1, min($longEdge, $maximumLongEdge, (int)round($longEdge * $scale)));
    $resizeScale = $targetLongEdge / $longEdge;
    $targetWidth = max(1, (int)round($width * $resizeScale));
    $targetHeight = max(1, (int)round($height * $resizeScale));
    $source = rep_akce_load_page_image($sourcePath, strtolower((string)($imageInfo['mime'] ?? '')));
    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target instanceof GdImage) {
        imagedestroy($source);
        throw new RuntimeException('Náhled stránky akční nabídky se nepodařilo vytvořit.');
    }

    $white = imagecolorallocate($target, 255, 255, 255);
    imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);
    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
    imagedestroy($source);

    $relativePath = rep_akce_page_variant_relative_path($imagePath, $variant);
    $absolutePath = ROOT_DIR . '/' . $relativePath;
    $absoluteDir = dirname($absolutePath);
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        imagedestroy($target);
        throw new RuntimeException('Adresář variant stránek se nepodařilo vytvořit.');
    }

    $saved = rep_akce_save_webp_with_size_limit($target, $absolutePath, $maximumBytes, $maximumQuality);
    imagedestroy($target);
    if (!$saved || !is_file($absolutePath)) {
        throw new RuntimeException('Variantní obrázek stránky akční nabídky se nepodařilo uložit.');
    }

    return $relativePath;
}

/** @return array<string, string> */
function rep_akce_create_page_variants(string $imagePath): array
{
    return [
        'medium' => rep_akce_create_page_variant($imagePath, 'medium', 0.75, 1800, 300 * 1024, 90),
        'small' => rep_akce_create_page_variant($imagePath, 'small', 0.50, 1200, 160 * 1024, 88),
        'thumb' => rep_akce_create_page_variant($imagePath, 'thumbs', 1.0, 280, 25 * 1024, 78),
    ];
}

function rep_akce_pages_relative_dir(int $id, string $title): string
{
    return rep_akce_offer_relative_dir($id, $title) . '/pages';
}

function rep_akce_ensure_pages_dir(int $id, string $title): string
{
    $relativeDir = rep_akce_pages_relative_dir($id, $title);
    $absoluteDir = ROOT_DIR . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodařilo se vytvořit adresář pro stránky akční nabídky.');
    }
    return $relativeDir;
}

function rep_akce_page_count(PDO $pdo, int $offerId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_akce_strany WHERE akce_id = :akce_id AND valid = 1');
    $stmt->execute([':akce_id' => $offerId]);
    return (int)$stmt->fetchColumn();
}

function rep_akce_next_page_order(PDO $pdo, int $offerId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(poradi), 0) + 1 FROM rep_akce_strany WHERE akce_id = :akce_id');
    $stmt->execute([':akce_id' => $offerId]);
    return max(1, (int)$stmt->fetchColumn());
}

function rep_akce_normalize_multi_upload(?array $files): array
{
    if (!$files || !isset($files['name']) || !is_array($files['name'])) {
        return [];
    }
    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $normalized[] = [
            'name' => (string)$name,
            'type' => (string)($files['type'][$index] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$index] ?? ''),
            'error' => $error,
            'size' => (int)($files['size'][$index] ?? 0),
        ];
    }
    usort($normalized, static fn(array $a, array $b): int => strnatcasecmp((string)$a['name'], (string)$b['name']));
    return $normalized;
}

function rep_akce_remove_pages(PDO $pdo, int $offerId): int
{
    $offer = rep_akce_offer($pdo, $offerId);
    $coverImage = trim((string)($offer['cover_image'] ?? ''));
    $removedPaths = [];
    $pages = rep_akce_pages($pdo, $offerId);
    foreach ($pages as $page) {
        $imagePath = trim((string)($page['image_path'] ?? ''));
        if ($imagePath !== '') {
            $removedPaths[$imagePath] = true;
            foreach (['small', 'medium', 'thumbs'] as $variant) {
                rep_akce_safe_delete_media_file(rep_akce_page_variant_relative_path($imagePath, $variant));
            }
            rep_akce_safe_delete_media_file($imagePath);
        }
    }
    $stmt = $pdo->prepare('DELETE FROM rep_akce_strany WHERE akce_id = :akce_id');
    $stmt->execute([':akce_id' => $offerId]);
    if ($coverImage !== '' && isset($removedPaths[$coverImage])) {
        $stmt = $pdo->prepare('UPDATE rep_akce SET cover_image = \'\', user_u = :user_u WHERE id = :id');
        $stmt->execute([':user_u' => rep_akce_user(), ':id' => $offerId]);
    }
    return count($pages);
}

function rep_akce_store_page(PDO $pdo, int $offerId, string $title, string $sourcePath, string $originalName, int $order, bool $uploadedFile): string
{
    if ($sourcePath === '' || !is_file($sourcePath)) {
        throw new RuntimeException('Zdrojový obrázek stránky není dostupný.');
    }

    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        throw new RuntimeException('Soubor stránky není podporovaný obrázek.');
    }
    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    $extension = rep_akce_image_extension($mime);
    if ($extension === null) {
        throw new RuntimeException('Podporované formáty stránek jsou JPG, PNG a WebP.');
    }

    $relativeDir = rep_akce_ensure_pages_dir($offerId, $title);
    $baseName = rep_akce_slug(pathinfo($originalName, PATHINFO_FILENAME), 'strana');
    $outputFormat = rep_akce_page_output_format();
    $targetName = sprintf('%04d-%s.%s', $order, $baseName, $outputFormat);
    $relativePath = $relativeDir . '/' . $targetName;
    $absolutePath = ROOT_DIR . '/' . $relativePath;
    $suffix = 1;
    while (is_file($absolutePath)) {
        $targetName = sprintf('%04d-%s-%d.%s', $order, $baseName, $suffix, $outputFormat);
        $relativePath = $relativeDir . '/' . $targetName;
        $absolutePath = ROOT_DIR . '/' . $relativePath;
        $suffix++;
    }

    rep_akce_save_page_image($sourcePath, $mime, $absolutePath, $outputFormat);
    try {
        rep_akce_create_page_variants($relativePath);
    } catch (Throwable $error) {
        foreach (['small', 'medium', 'thumbs'] as $variant) {
            rep_akce_safe_delete_media_file(rep_akce_page_variant_relative_path($relativePath, $variant));
        }
        rep_akce_safe_delete_media_file($relativePath);
        throw $error;
    }

    $storedImageInfo = @getimagesize($absolutePath) ?: $imageInfo;

    $stmt = $pdo->prepare('INSERT INTO rep_akce_strany (akce_id, poradi, image_file, image_path, mime_type, width, height, filesize, valid, user_i, user_u) VALUES (:akce_id, :poradi, :image_file, :image_path, :mime_type, :width, :height, :filesize, 1, :user_i, :user_u)');
    $stmt->execute([
        ':akce_id' => $offerId,
        ':poradi' => $order,
        ':image_file' => $targetName,
        ':image_path' => $relativePath,
        ':mime_type' => rep_akce_page_output_mime($outputFormat),
        ':width' => (int)($storedImageInfo[0] ?? 0),
        ':height' => (int)($storedImageInfo[1] ?? 0),
        ':filesize' => (int)filesize($absolutePath),
        ':user_i' => rep_akce_user(),
        ':user_u' => rep_akce_user(),
    ]);

    return $relativePath;
}

function rep_akce_set_cover_from_first_page(PDO $pdo, int $offerId): void
{
    $offer = rep_akce_offer($pdo, $offerId);
    if (!$offer) {
        return;
    }

    $coverImage = trim((string)($offer['cover_image'] ?? ''));
    if ($coverImage !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_akce_strany WHERE akce_id = :akce_id AND image_path = :image_path AND valid = 1');
        $stmt->execute([':akce_id' => $offerId, ':image_path' => $coverImage]);
        if ((int)$stmt->fetchColumn() === 0) {
            return;
        }
    }

    $pages = rep_akce_pages($pdo, $offerId, 1);
    $firstPage = trim((string)($pages[0]['image_path'] ?? ''));
    if ($firstPage === '' || $firstPage === $coverImage) {
        return;
    }
    $stmt = $pdo->prepare('UPDATE rep_akce SET cover_image = :cover_image, user_u = :user_u WHERE id = :id');
    $stmt->execute([':cover_image' => $firstPage, ':user_u' => rep_akce_user(), ':id' => $offerId]);
}

function rep_akce_page_sort_key(string $imageFile): string
{
    $name = pathinfo($imageFile, PATHINFO_FILENAME);
    $name = preg_replace('~^\d{4}-~', '', $name) ?? $name;
    return strtolower($name);
}

function rep_akce_reorder_pages_by_filename(PDO $pdo, int $offerId): void
{
    $stmt = $pdo->prepare('SELECT id, image_file FROM rep_akce_strany WHERE akce_id = :akce_id AND valid = 1 ORDER BY poradi ASC, id ASC');
    $stmt->execute([':akce_id' => $offerId]);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$pages) {
        return;
    }

    usort($pages, static function (array $a, array $b): int {
        $result = strnatcasecmp(
            rep_akce_page_sort_key((string)($a['image_file'] ?? '')),
            rep_akce_page_sort_key((string)($b['image_file'] ?? ''))
        );
        return $result !== 0 ? $result : ((int)$a['id'] <=> (int)$b['id']);
    });

    $update = $pdo->prepare('UPDATE rep_akce_strany SET poradi = :poradi, user_u = :user_u WHERE id = :id');
    $order = 1;
    foreach ($pages as $page) {
        $update->execute([
            ':poradi' => $order,
            ':user_u' => rep_akce_user(),
            ':id' => (int)$page['id'],
        ]);
        $order++;
    }
}

function rep_akce_after_page_upload(PDO $pdo, int $offerId): void
{
    rep_akce_reorder_pages_by_filename($pdo, $offerId);

    $stmt = $pdo->prepare('UPDATE rep_akce SET viewer_mode = :viewer_mode, user_u = :user_u WHERE id = :id');
    $stmt->execute([':viewer_mode' => 'images', ':user_u' => rep_akce_user(), ':id' => $offerId]);

    rep_akce_set_cover_from_first_page($pdo, $offerId);
}

function rep_akce_upload_pages(PDO $pdo, int $offerId, string $title, ?array $files, bool $replace): int
{
    $uploads = rep_akce_normalize_multi_upload($files);
    if ($uploads === []) {
        return 0;
    }
    if ($replace) {
        rep_akce_remove_pages($pdo, $offerId);
    }
    $order = rep_akce_next_page_order($pdo, $offerId);
    $created = 0;
    foreach ($uploads as $file) {
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Některou stránku se nepodařilo nahrát.');
        }
        $tmpName = (string)$file['tmp_name'];
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Dočasný soubor stránky není dostupný.');
        }
        rep_akce_store_page($pdo, $offerId, $title, $tmpName, (string)$file['name'], $order, true);
        $order++;
        $created++;
    }
    rep_akce_after_page_upload($pdo, $offerId);
    return $created;
}

function rep_akce_flip_mobile_dir(array $offer): string
{
    $legacyFlipPath = trim((string)($offer['legacy_flip_path'] ?? ''));
    if ($legacyFlipPath !== '') {
        $flipDir = dirname(ltrim($legacyFlipPath, '/'));
        $mobileDir = ROOT_DIR . '/' . $flipDir . '/files/mobile';
        if (is_dir($mobileDir)) {
            return $mobileDir;
        }
    }

    $legacyFlip = trim((string)($offer['legacy_flip'] ?? ''), "/ \t\n\r\0\x0B");
    if ($legacyFlip === '') {
        return '';
    }
    $mobileDir = ROOT_DIR . '/_files/akce_old/_flip/' . $legacyFlip . '/files/mobile';
    return is_dir($mobileDir) ? $mobileDir : '';
}

function rep_akce_import_flip_pages(PDO $pdo, int $offerId, bool $replace = true): array
{
    $offer = rep_akce_offer($pdo, $offerId);
    if (!$offer) {
        throw new RuntimeException('Akční nabídka nebyla nalezena.');
    }
    $mobileDir = rep_akce_flip_mobile_dir($offer);
    if ($mobileDir === '') {
        throw new RuntimeException('Legacy _flip/files/mobile pro tuto nabídku není dostupný.');
    }
    $files = glob($mobileDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    natsort($files);
    $files = array_values($files);
    if ($files === []) {
        throw new RuntimeException('Legacy _flip/files/mobile neobsahuje žádné použitelné obrázky.');
    }

    if ($replace) {
        rep_akce_remove_pages($pdo, $offerId);
    }
    $title = (string)($offer['nazev_cz'] ?? 'akce');
    $order = rep_akce_next_page_order($pdo, $offerId);
    $created = 0;
    foreach ($files as $file) {
        rep_akce_store_page($pdo, $offerId, $title, $file, basename($file), $order, false);
        $order++;
        $created++;
    }
    $stmt = $pdo->prepare('UPDATE rep_akce SET viewer_mode = :viewer_mode, user_u = :user_u WHERE id = :id');
    $stmt->execute([':viewer_mode' => 'images', ':user_u' => rep_akce_user(), ':id' => $offerId]);
    if (trim((string)($offer['legacy_flip_path'] ?? '')) === '' && trim((string)($offer['legacy_flip'] ?? '')) !== '') {
        $legacyFlip = trim((string)$offer['legacy_flip'], "/ \t\n\r\0\x0B");
        $indexPath = '_files/akce_old/_flip/' . $legacyFlip . '/index.html';
        if (is_file(ROOT_DIR . '/' . $indexPath)) {
            $stmt = $pdo->prepare('UPDATE rep_akce SET legacy_flip_path = :legacy_flip_path, user_u = :user_u WHERE id = :id');
            $stmt->execute([':legacy_flip_path' => $indexPath, ':user_u' => rep_akce_user(), ':id' => $offerId]);
        }
    }
    rep_akce_set_cover_from_first_page($pdo, $offerId);

    return ['imported' => $created, 'source_dir' => $mobileDir];
}

function rep_akce_primary_pdf_path(array $row): string
{
    $pdf = trim((string)($row['pdf_file'] ?? ''));
    if ($pdf !== '') {
        return $pdf;
    }
    return trim((string)($row['legacy_pdf_path'] ?? ''));
}

function rep_akce_primary_cover_path(array $row): string
{
    $cover = trim((string)($row['cover_image'] ?? ''));
    if ($cover !== '') {
        return $cover;
    }
    $legacyCover = trim((string)($row['legacy_cover_image'] ?? ''));
    if ($legacyCover === '') {
        return '';
    }
    $path = '_files/akce_old/_images/_akce/_main/' . $legacyCover;
    return rep_akce_file_exists($path) ? $path : '';
}

function rep_akce_count(PDO $pdo, string $table, int $valid): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE valid = :valid");
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

function rep_akce_type_count(PDO $pdo, int $valid): int
{
    return rep_akce_count($pdo, 'rep_akce_typ', $valid);
}

function rep_akce_offer_count(PDO $pdo, int $valid): int
{
    return rep_akce_count($pdo, 'rep_akce', $valid);
}

function rep_akce_types(PDO $pdo, ?int $valid = 1): array
{
    $sql = 'SELECT t.*, COUNT(a.id) AS akce_count
            FROM rep_akce_typ t
            LEFT JOIN rep_akce a ON a.typ_id = t.id AND a.valid = 1';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE t.valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' GROUP BY t.id ORDER BY t.poradi ASC, t.nazev_cz ASC, t.id ASC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_akce_type(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM rep_akce_typ WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rep_akce_save_type(PDO $pdo, array $data): int
{
    $id = (int)($data['id'] ?? 0);
    $newsletterGroup = rep_akce_newsletter_group($data['newsletter_group'] ?? '');
    $payload = [
        ':code' => trim((string)($data['code'] ?? '')),
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':color' => trim((string)($data['color'] ?? '')),
        ':newsletter_group' => $newsletterGroup !== '' ? $newsletterGroup : null,
        ':valid' => isset($data['valid']) ? 1 : 0,
        ':user_u' => rep_akce_user(),
    ];
    if ($payload[':nazev_cz'] === '') {
        throw new RuntimeException('Vyplňte název typu.');
    }
    if ($id > 0) {
        $payload[':id'] = $id;
        $stmt = $pdo->prepare('UPDATE rep_akce_typ SET code = :code, poradi = :poradi, nazev_cz = :nazev_cz, nazev_en = :nazev_en, color = :color, newsletter_group = :newsletter_group, valid = :valid, user_u = :user_u WHERE id = :id');
        $stmt->execute($payload);
        admin_auto_translate_record('rep_akce.type', $id, [
            'nazev_cz' => $payload[':nazev_cz'],
            'nazev_en' => $payload[':nazev_en'],
        ] + $data);
        return $id;
    }
    $payload[':user_i'] = rep_akce_user();
    $stmt = $pdo->prepare('INSERT INTO rep_akce_typ (code, poradi, nazev_cz, nazev_en, color, newsletter_group, valid, user_i, user_u) VALUES (:code, :poradi, :nazev_cz, :nazev_en, :color, :newsletter_group, :valid, :user_i, :user_u)');
    $stmt->execute($payload);
    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('rep_akce.type', $newId, [
        'nazev_cz' => $payload[':nazev_cz'],
        'nazev_en' => $payload[':nazev_en'],
    ] + $data);

    return $newId;
}

function rep_akce_set_valid(PDO $pdo, string $table, int $id, int $valid): void
{
    if (!in_array($table, ['rep_akce', 'rep_akce_typ'], true) || $id <= 0) {
        throw new RuntimeException('Neplatný záznam.');
    }
    $stmt = $pdo->prepare("UPDATE `{$table}` SET valid = :valid, user_u = :user_u WHERE id = :id");
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0, ':user_u' => rep_akce_user(), ':id' => $id]);
}

function rep_akce_years(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT YEAR(COALESCE(datum_od, datum_do)) AS rok, COUNT(*) AS total FROM rep_akce WHERE COALESCE(datum_od, datum_do) IS NOT NULL GROUP BY rok ORDER BY rok DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_akce_offers(PDO $pdo, array $typeIds = [], ?int $year = null, int $valid = 1, ?int $visible = null): array
{
    $sql = 'SELECT a.*, t.nazev_cz AS typ_nazev_cz, t.code AS typ_code, t.color AS typ_color, COALESCE(s.page_count, 0) AS page_count
            FROM rep_akce a
            LEFT JOIN rep_akce_typ t ON t.id = a.typ_id
            LEFT JOIN (SELECT akce_id, COUNT(*) AS page_count FROM rep_akce_strany WHERE valid = 1 GROUP BY akce_id) s ON s.akce_id = a.id
            WHERE a.valid = :valid';
    $params = [':valid' => $valid === 1 ? 1 : 0];
    if ($visible !== null) {
        $sql .= ' AND a.visible = :visible';
        $params[':visible'] = $visible === 1 ? 1 : 0;
    }
    if ($year !== null && $year > 0) {
        $sql .= ' AND YEAR(COALESCE(a.datum_od, a.datum_do)) = :year';
        $params[':year'] = $year;
    }
    if ($typeIds !== []) {
        $placeholders = [];
        foreach (array_values($typeIds) as $index => $typeId) {
            $key = ':type' . $index;
            $placeholders[] = $key;
            $params[$key] = $typeId;
        }
        $sql .= ' AND a.typ_id IN (' . implode(', ', $placeholders) . ')';
    }
    $sql .= ' ORDER BY COALESCE(a.datum_od, a.datum_do) DESC, a.id DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_akce_offer(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM rep_akce WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rep_akce_default_offer(): array
{
    return [
        'id' => 0,
        'typ_id' => 0,
        'nazev_cz' => '',
        'nazev_en' => '',
        'datum_od' => '',
        'datum_do' => '',
        'text_cz' => '',
        'text_en' => '',
        'viewer_mode' => 'images',
        'is_primary' => 0,
        'cover_image' => '',
        'pdf_file' => '',
        'pdf_original_name' => '',
        'pdf_filesize' => 0,
        'legacy_cover_image' => '',
        'legacy_pdf_path' => '',
        'legacy_flip' => '',
        'legacy_flip_path' => '',
        'legacy_image_dir' => '',
        'visible' => 1,
        'valid' => 1,
    ];
}

function rep_akce_save_offer(PDO $pdo, array $data, array $files): int
{
    $id = (int)($data['id'] ?? 0);
    $title = trim((string)($data['nazev_cz'] ?? ''));
    if ($title === '') {
        throw new RuntimeException('Vyplňte název akční nabídky.');
    }
    $payload = [
        ':typ_id' => (int)($data['typ_id'] ?? 0) > 0 ? (int)$data['typ_id'] : null,
        ':nazev_cz' => $title,
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':datum_od' => rep_akce_date_db($data['datum_od'] ?? null),
        ':datum_do' => rep_akce_date_db($data['datum_do'] ?? null),
        ':text_cz' => editor_html((string)($data['text_cz'] ?? '')),
        ':text_en' => editor_html((string)($data['text_en'] ?? '')),
        ':viewer_mode' => in_array((string)($data['viewer_mode'] ?? 'pdf'), ['pdf', 'images', 'legacy_flip'], true) ? (string)$data['viewer_mode'] : 'pdf',
        ':is_primary' => isset($data['is_primary']) ? 1 : 0,
        ':visible' => isset($data['visible']) ? 1 : 0,
        ':valid' => isset($data['valid']) ? 1 : 0,
        ':user_u' => rep_akce_user(),
    ];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE rep_akce SET typ_id = :typ_id, nazev_cz = :nazev_cz, nazev_en = :nazev_en, datum_od = :datum_od, datum_do = :datum_do, text_cz = :text_cz, text_en = :text_en, viewer_mode = :viewer_mode, is_primary = :is_primary, visible = :visible, valid = :valid, user_u = :user_u WHERE id = :id');
        $payload[':id'] = $id;
        $stmt->execute($payload);
    } else {
        $stmt = $pdo->prepare('INSERT INTO rep_akce (typ_id, nazev_cz, nazev_en, datum_od, datum_do, text_cz, text_en, viewer_mode, is_primary, visible, valid, user_i, user_u) VALUES (:typ_id, :nazev_cz, :nazev_en, :datum_od, :datum_do, :text_cz, :text_en, :viewer_mode, :is_primary, :visible, :valid, :user_i, :user_u)');
        $payload[':user_i'] = rep_akce_user();
        $stmt->execute($payload);
        $id = (int)$pdo->lastInsertId();
    }

    admin_auto_translate_record('rep_akce.offer', $id, [
        'nazev_cz' => $payload[':nazev_cz'],
        'nazev_en' => $payload[':nazev_en'],
        'text_cz' => $payload[':text_cz'],
        'text_en' => $payload[':text_en'],
    ] + $data);

    $current = rep_akce_offer($pdo, $id) ?? rep_akce_default_offer();
    $pdfUpload = rep_akce_upload_pdf($files['pdf_file'] ?? null, $id, $title, (string)($current['pdf_file'] ?? ''));
    $coverUpload = rep_akce_upload_cover($files['cover_image'] ?? null, $id, $title, (string)($current['cover_image'] ?? ''));
    $uploadedPages = rep_akce_upload_pages($pdo, $id, $title, $files['page_images'] ?? null, isset($data['replace_pages']));

    if ($pdfUpload['changed'] || $coverUpload['changed']) {
        $stmt = $pdo->prepare('UPDATE rep_akce SET cover_image = :cover_image, pdf_file = :pdf_file, pdf_original_name = :pdf_original_name, pdf_filesize = :pdf_filesize, user_u = :user_u WHERE id = :id');
        $stmt->execute([
            ':cover_image' => $coverUpload['path'],
            ':pdf_file' => $pdfUpload['path'],
            ':pdf_original_name' => $pdfUpload['changed'] ? $pdfUpload['original_name'] : (string)($current['pdf_original_name'] ?? ''),
            ':pdf_filesize' => $pdfUpload['changed'] ? (int)$pdfUpload['filesize'] : (int)($current['pdf_filesize'] ?? 0),
            ':user_u' => rep_akce_user(),
            ':id' => $id,
        ]);
    }
    if ($uploadedPages > 0) {
        $stmt = $pdo->prepare('UPDATE rep_akce SET viewer_mode = :viewer_mode, user_u = :user_u WHERE id = :id');
        $stmt->execute([':viewer_mode' => 'images', ':user_u' => rep_akce_user(), ':id' => $id]);
    }

    return $id;
}

function rep_akce_remove_file(PDO $pdo, int $id, string $field): void
{
    if (!in_array($field, ['pdf_file', 'cover_image'], true)) {
        throw new RuntimeException('Neplatný typ souboru.');
    }
    $row = rep_akce_offer($pdo, $id);
    if (!$row) {
        throw new RuntimeException('Akční nabídka nebyla nalezena.');
    }
    rep_akce_safe_delete_media_file((string)($row[$field] ?? ''));
    $extra = $field === 'pdf_file' ? ', pdf_original_name = \'\', pdf_filesize = 0' : '';
    $stmt = $pdo->prepare("UPDATE rep_akce SET {$field} = ''{$extra}, user_u = :user_u WHERE id = :id");
    $stmt->execute([':user_u' => rep_akce_user(), ':id' => $id]);
}

function rep_akce_pages(PDO $pdo, int $offerId, int $limit = 0): array
{
    $sql = 'SELECT * FROM rep_akce_strany WHERE akce_id = :akce_id AND valid = 1 ORDER BY poradi ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':akce_id', $offerId, PDO::PARAM_INT);
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array<string, mixed> $offer
 * @param array<int, array<string, mixed>> $dbPages
 * @return array<int, array<string, mixed>>
 */
function rep_akce_viewer_pages(array $offer, array $dbPages): array
{
    $pages = [];
    foreach ($dbPages as $page) {
        $path = trim((string)($page['image_path'] ?? ''));
        if ($path === '' || !rep_akce_file_exists($path)) {
            continue;
        }
        $order = (int)($page['poradi'] ?? 0);
        $thumbPath = rep_akce_page_thumbnail_relative_path($path);
        $sources = [];
        foreach (['small', 'medium'] as $variant) {
            $variantPath = rep_akce_page_variant_relative_path($path, $variant);
            if (!rep_akce_file_exists($variantPath)) {
                continue;
            }
            $variantInfo = @getimagesize(ROOT_DIR . '/' . $variantPath);
            $sources[] = [
                'src' => rep_akce_file_url($variantPath),
                'width' => (int)($variantInfo[0] ?? 0),
            ];
        }
        $sources[] = [
            'src' => rep_akce_file_url($path),
            'width' => (int)($page['width'] ?? 0),
        ];
        $pages[] = [
            'src' => rep_akce_file_url($path),
            'thumb' => rep_akce_file_url(rep_akce_file_exists($thumbPath) ? $thumbPath : $path),
            'label' => 'Strana ' . ($order > 0 ? $order : count($pages) + 1),
            'width' => (int)($page['width'] ?? 0),
            'height' => (int)($page['height'] ?? 0),
            'sources' => $sources,
        ];
    }

    if ($pages !== []) {
        return $pages;
    }

    $mobileDir = rep_akce_flip_mobile_dir($offer);
    if ($mobileDir === '') {
        return [];
    }

    $files = glob($mobileDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
    natsort($files);

    foreach (array_values($files) as $index => $file) {
        $src = ltrim(str_replace(ROOT_DIR, '', $file), '/');
        $pages[] = [
            'src' => rep_akce_file_url($src),
            'thumb' => rep_akce_file_url($src),
            'label' => 'Strana ' . ($index + 1),
            'width' => 0,
            'height' => 0,
            'sources' => [],
        ];
    }

    return $pages;
}
