<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function rep_bannery_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_bannery_user(): string
{
    return function_exists('admin_session_user') ? admin_session_user() : 'system';
}

function rep_bannery_positions(): array
{
    return [
        'main_carousel' => 'Hlavní carousel',
        'secondary_links' => 'Sekundární odkazy',
    ];
}

function rep_bannery_colors(): array
{
    return [
        'dark' => 'Tmavá',
        'light' => 'Světlá',
    ];
}

function rep_bannery_validity_filters(): array
{
    return [
        'yes' => 'Ano',
        'no' => 'Ne',
        'all' => 'Všechny',
    ];
}

function rep_bannery_background_themes(): array
{
    return [
        'brand-red' => 'Qanto červená',
        'brand-dark' => 'Qanto tmavá',
        'graphite' => 'Grafit',
        'silver' => 'Světlá šedá',
        'wholesale-green' => 'Velkoobchod zelená',
        'qantoplus-orange' => 'Qanto+ oranžová',
        'ocean-blue' => 'Modrá',
        'cream-paper' => 'Světlý papír',
        'warm-sand' => 'Teplý písek',
        'line-pattern' => 'Jemné linky',
        'photo-zdenek' => 'Foto Zdeněk',
        'photo-vodicka' => 'Foto Vodička',
        'photo-mirek' => 'Foto Mirek',
        'photo-verka' => 'Foto Verka',
        'photo-zaneta' => 'Foto Žaneta',
        'photo-vodickova' => 'Foto Vodičková',
        'photo-standa' => 'Foto Standa',
        'photo-matej' => 'Foto Matěj',
    ];
}

function rep_bannery_position(string $value): string
{
    return array_key_exists($value, rep_bannery_positions()) ? $value : 'main_carousel';
}

function rep_bannery_color(string $value): string
{
    return array_key_exists($value, rep_bannery_colors()) ? $value : 'dark';
}

function rep_bannery_validity_filter(mixed $value): string
{
    $value = (string)$value;
    return array_key_exists($value, rep_bannery_validity_filters()) ? $value : 'yes';
}

function rep_bannery_background_theme(mixed $value): string
{
    $value = (string)$value;
    return array_key_exists($value, rep_bannery_background_themes()) ? $value : 'brand-red';
}

function rep_bannery_clean_text(mixed $value, int $limit): string
{
    $text = function_exists('plain_text') ? plain_text((string)$value) : trim(strip_tags((string)$value));
    $text = preg_replace('~\s+~u', ' ', $text) ?? $text;
    if (function_exists('mb_substr')) {
        return trim(mb_substr($text, 0, $limit, 'UTF-8'));
    }
    return trim(substr($text, 0, $limit));
}

function rep_bannery_date_db(mixed $value): ?string
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

function rep_bannery_date_form(mixed $value): string
{
    $value = trim((string)($value ?? ''));
    return $value === '' ? '' : substr($value, 0, 10);
}

function rep_bannery_date_www(mixed $value): string
{
    $value = rep_bannery_date_form($value);
    if ($value === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($value))->format('d.m.Y');
    } catch (Throwable) {
        return $value;
    }
}

function rep_bannery_bool_label(mixed $value): string
{
    return (int)$value === 1 ? 'ANO' : 'NE';
}

function rep_bannery_bool_badge(mixed $value): string
{
    $isEnabled = (int)$value === 1;
    return '<span class="badge text-bg-' . ($isEnabled ? 'success' : 'secondary') . '">' . ($isEnabled ? 'ANO' : 'NE') . '</span>';
}

function rep_bannery_updated_cell(array $row): string
{
    $value = trim((string)($row['ts_u'] ?? ''));
    $date = $value !== ''
        ? (function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value)
        : '';
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_bannery_e($user) . '</small>' : '';
    }

    return rep_bannery_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_bannery_e($user) . '</small>' : '');
}

function rep_bannery_file_url(string $relativePath): string
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '') {
        return '';
    }
    $base = defined('BASE_URL') ? (string)BASE_URL : '/';
    return rtrim($base, '/') . '/' . $relativePath;
}

function rep_bannery_media_root(): string
{
    return ROOT_DIR . '/media/bannery';
}

function rep_bannery_slug(string $value, string $fallback = 'banner'): string
{
    $slug = function_exists('text_str') ? text_str($value) : strtolower(preg_replace('~[^a-z0-9]+~i', '-', $value) ?? '');
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : $fallback;
}

function rep_bannery_safe_url(mixed $value): string
{
    $url = trim((string)$value);
    if ($url === '') {
        return '';
    }
    if (preg_match('~^(javascript|data):~i', $url) === 1) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($url, 0, 500, 'UTF-8');
    }
    return substr($url, 0, 500);
}

function rep_bannery_safe_delete_file(PDO $pdo, string $relativePath, int $excludeId = 0): void
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '') {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_bannery WHERE image = :image AND id <> :id');
    $stmt->execute([':image' => $relativePath, ':id' => $excludeId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $base = realpath(rep_bannery_media_root());
    $path = realpath(ROOT_DIR . '/' . $relativePath);
    if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }
    if (is_file($path)) {
        @unlink($path);
    }
}

function rep_bannery_upload_image(?array $file, string $existingPath = ''): array
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
    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    if (!isset($extensionMap[$mime])) {
        throw new RuntimeException('Podporované formáty banneru jsou JPG, PNG a WebP.');
    }

    $absoluteDir = rep_bannery_media_root();
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodařilo se vytvořit adresář pro bannery.');
    }

    $baseName = rep_bannery_slug(pathinfo((string)($file['name'] ?? 'banner'), PATHINFO_FILENAME), 'banner');
    $relativePath = 'media/bannery/' . $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extensionMap[$mime];
    if (!move_uploaded_file($tmpName, ROOT_DIR . '/' . $relativePath)) {
        throw new RuntimeException('Obrázek se nepodařilo uložit.');
    }

    return ['path' => $relativePath, 'changed' => true];
}

function rep_bannery_default(): array
{
    return [
        'id' => 0,
        'position_key' => 'main_carousel',
        'poradi' => 0,
        'valid_to' => '',
        'image' => '',
        'url' => '',
        'popis_cz' => '',
        'popis_en' => '',
        'link_text_cz' => '',
        'link_text_en' => '',
        'text_color' => 'dark',
        'background_theme' => 'brand-red',
        'visible' => 1,
        'valid' => 1,
    ];
}

function rep_bannery_get(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM rep_bannery WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function rep_bannery_list(PDO $pdo, int $valid = 1, ?string $position = null, string $validityFilter = 'yes'): array
{
    $where = ['valid = :valid'];
    $params = [':valid' => $valid];
    if ($position !== null && $position !== '') {
        $where[] = 'position_key = :position_key';
        $params[':position_key'] = rep_bannery_position($position);
    }
    $validityFilter = rep_bannery_validity_filter($validityFilter);
    if ($validityFilter === 'yes') {
        $where[] = '(valid_to IS NULL OR valid_to >= CURDATE())';
    } elseif ($validityFilter === 'no') {
        $where[] = 'valid_to IS NOT NULL AND valid_to < CURDATE()';
    }
    $sql = 'SELECT * FROM rep_bannery WHERE ' . implode(' AND ', $where) . ' ORDER BY position_key ASC, poradi ASC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_bannery_count(PDO $pdo, int $valid): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_bannery WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function rep_bannery_save(PDO $pdo, array $data, array $files): int
{
    $id = (int)($data['id'] ?? 0);
    $existing = $id > 0 ? rep_bannery_get($pdo, $id) : null;
    if ($id > 0 && !$existing) {
        throw new RuntimeException('Banner nebyl nalezen.');
    }

    $image = (string)($existing['image'] ?? '');
    if (!empty($data['delete_image']) && $image !== '') {
        rep_bannery_safe_delete_file($pdo, $image, $id);
        $image = '';
    }

    $upload = rep_bannery_upload_image($files['image'] ?? null, $image);
    if ((bool)$upload['changed']) {
        if ($image !== '') {
            rep_bannery_safe_delete_file($pdo, $image, $id);
        }
        $image = (string)$upload['path'];
    }

    $row = [
        ':position_key' => rep_bannery_position((string)($data['position_key'] ?? '')),
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':valid_to' => rep_bannery_date_db($data['valid_to'] ?? null),
        ':image' => $image,
        ':url' => rep_bannery_safe_url($data['url'] ?? ''),
        ':popis_cz' => rep_bannery_clean_text($data['popis_cz'] ?? '', 255),
        ':popis_en' => rep_bannery_clean_text($data['popis_en'] ?? '', 255),
        ':link_text_cz' => rep_bannery_clean_text($data['link_text_cz'] ?? '', 120),
        ':link_text_en' => rep_bannery_clean_text($data['link_text_en'] ?? '', 120),
        ':text_color' => rep_bannery_color((string)($data['text_color'] ?? 'dark')),
        ':background_theme' => rep_bannery_background_theme($data['background_theme'] ?? 'brand-red'),
        ':visible' => isset($data['visible']) ? 1 : 0,
        ':valid' => isset($data['valid']) ? 1 : 0,
        ':user' => rep_bannery_user(),
    ];

    if ($row[':popis_cz'] === '') {
        throw new RuntimeException('Vyplň popis banneru v češtině.');
    }

    if ($id > 0) {
        $row[':id'] = $id;
        $stmt = $pdo->prepare('UPDATE rep_bannery SET position_key = :position_key, poradi = :poradi, valid_to = :valid_to, image = :image, url = :url, popis_cz = :popis_cz, popis_en = :popis_en, link_text_cz = :link_text_cz, link_text_en = :link_text_en, text_color = :text_color, background_theme = :background_theme, visible = :visible, valid = :valid, user_u = :user WHERE id = :id');
        $stmt->execute($row);
        admin_auto_translate_record('rep_bannery.banner', $id, [
            'popis_cz' => $row[':popis_cz'],
            'popis_en' => $row[':popis_en'],
            'link_text_cz' => $row[':link_text_cz'],
            'link_text_en' => $row[':link_text_en'],
        ] + $data);
        return $id;
    }

    $row[':user_i'] = $row[':user'];
    $row[':user_u'] = $row[':user'];
    unset($row[':user']);
    $stmt = $pdo->prepare('INSERT INTO rep_bannery (position_key, poradi, valid_to, image, url, popis_cz, popis_en, link_text_cz, link_text_en, text_color, background_theme, visible, valid, user_i, user_u) VALUES (:position_key, :poradi, :valid_to, :image, :url, :popis_cz, :popis_en, :link_text_cz, :link_text_en, :text_color, :background_theme, :visible, :valid, :user_i, :user_u)');
    $stmt->execute($row);
    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('rep_bannery.banner', $newId, [
        'popis_cz' => $row[':popis_cz'],
        'popis_en' => $row[':popis_en'],
        'link_text_cz' => $row[':link_text_cz'],
        'link_text_en' => $row[':link_text_en'],
    ] + $data);

    return $newId;
}

function rep_bannery_set_valid(PDO $pdo, int $id, int $valid): void
{
    if ($id <= 0) {
        throw new RuntimeException('Chybí ID banneru.');
    }
    $stmt = $pdo->prepare('UPDATE rep_bannery SET valid = :valid, user_u = :user WHERE id = :id');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0, ':user' => rep_bannery_user(), ':id' => $id]);
}
