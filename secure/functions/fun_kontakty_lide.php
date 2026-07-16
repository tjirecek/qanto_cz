<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function kontakty_lide_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kontakty_lide_bool_label(mixed $value): string
{
    return (int)$value === 1 ? 'ANO' : 'NE';
}

function kontakty_lide_page_url(string $subpage = '06', array $params = []): string
{
    $query = array_merge([
        'section' => '01',
        'page' => '04',
        'sec_page' => $subpage === '07' ? '07' : '06',
    ], $params);

    return 'index.php?' . http_build_query($query, '', '&amp;');
}

function kontakty_lide_image_relative_dir(): string
{
    return 'media/kontakty-lide';
}

function kontakty_lide_default_person(): array
{
    return [
        'skupina_id' => null,
        'poradi' => 0,
        'jmeno' => '',
        'email' => '',
        'mobil' => '',
        'web' => '',
        'image' => '',
        'funkce_cz' => '',
        'funkce_en' => '',
        'popis_cz' => '',
        'popis_en' => '',
        'visible' => 1,
        'valid' => 1,
    ];
}

function kontakty_lide_default_group(): array
{
    return [
        'poradi' => 0,
        'nazev_cz' => '',
        'nazev_en' => '',
        'visible' => 1,
        'valid' => 1,
    ];
}

function kontakty_lide_count(PDO $pdo, string $table, ?int $valid = 1): int
{
    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

function kontakty_lide_groups(PDO $pdo, ?int $valid = null, int $limit = 0): array
{
    $sql = 'SELECT s.*, COALESCE(p.people_count, 0) AS people_count
            FROM kontakty_lide_skupiny s
            LEFT JOIN (
                SELECT skupina_id, COUNT(*) AS people_count
                FROM kontakty_lide
                WHERE valid = 1
                GROUP BY skupina_id
            ) p ON p.skupina_id = s.id';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE s.valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' ORDER BY s.poradi ASC, s.nazev_cz ASC, s.id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function kontakty_lide_persons(PDO $pdo, ?int $valid = 1, int $limit = 500): array
{
    $sql = 'SELECT l.*, s.nazev_cz AS skupina_nazev
            FROM kontakty_lide l
            LEFT JOIN kontakty_lide_skupiny s ON s.id = l.skupina_id';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE l.valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' ORDER BY COALESCE(s.poradi, 999999) ASC, s.nazev_cz ASC, l.poradi ASC, l.jmeno ASC, l.id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function kontakty_lide_person_get(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM kontakty_lide WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function kontakty_lide_group_get(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM kontakty_lide_skupiny WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function kontakty_lide_group_options(PDO $pdo, ?int $selected = null): string
{
    $rows = kontakty_lide_groups($pdo, 1, 0);
    $html = '<option value="">bez skupiny</option>';
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $isSelected = $selected !== null && $id === $selected ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $isSelected . '>' . kontakty_lide_e($row['nazev_cz'] ?? '') . '</option>';
    }

    return $html;
}

function kontakty_lide_normalize_person(array $data): array
{
    $groupId = trim((string)($data['skupina_id'] ?? ''));

    return [
        'skupina_id' => $groupId === '' ? null : max(0, (int)$groupId),
        'poradi' => (int)($data['poradi'] ?? 0),
        'jmeno' => trim((string)($data['jmeno'] ?? '')),
        'email' => trim((string)($data['email'] ?? '')),
        'mobil' => trim((string)($data['mobil'] ?? '')),
        'web' => trim((string)($data['web'] ?? '')),
        'funkce_cz' => trim((string)($data['funkce_cz'] ?? '')),
        'funkce_en' => trim((string)($data['funkce_en'] ?? '')),
        'popis_cz' => editor_html((string)($data['popis_cz'] ?? '')),
        'popis_en' => editor_html((string)($data['popis_en'] ?? '')),
        'visible' => isset($data['visible']) ? 1 : 0,
        'valid' => isset($data['valid']) ? 1 : 0,
    ];
}

function kontakty_lide_normalize_group(array $data): array
{
    return [
        'poradi' => (int)($data['poradi'] ?? 0),
        'nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        'nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        'visible' => isset($data['visible']) ? 1 : 0,
        'valid' => isset($data['valid']) ? 1 : 0,
    ];
}

function kontakty_lide_validate_person(PDO $pdo, array $data): void
{
    if ((string)$data['jmeno'] === '') {
        throw new RuntimeException('Jméno osoby je povinné.');
    }

    if ((string)$data['email'] !== '' && filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('E-mail osoby není platný.');
    }

    if ($data['skupina_id'] !== null && (int)$data['skupina_id'] > 0) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM kontakty_lide_skupiny WHERE id = :id AND valid = 1');
        $stmt->execute([':id' => (int)$data['skupina_id']]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('Vybraná skupina neexistuje nebo není validní.');
        }
    }
}

function kontakty_lide_validate_group(array $data): void
{
    if ((string)$data['nazev_cz'] === '') {
        throw new RuntimeException('Název skupiny je povinný.');
    }
}

function kontakty_lide_image_upload(?array $file, string $existingImage = ''): string
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingImage;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nepodařilo se nahrát fotku.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Nahraný soubor není platný.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('Soubor není podporovaný obrázek.');
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = (string)($imageInfo['mime'] ?? '');
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Podporované formáty fotek jsou JPG, PNG, WebP a GIF.');
    }

    $baseName = function_exists('text_str') ? text_str(pathinfo((string)($file['name'] ?? 'kontakt'), PATHINFO_FILENAME)) : 'kontakt';
    if ($baseName === '') {
        $baseName = 'kontakt';
    }

    $relativeDir = kontakty_lide_image_relative_dir();
    $absoluteDir = ROOT_DIR . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodařilo se vytvořit adresář pro fotky.');
    }

    $relativePath = $relativeDir . '/' . $baseName . '-' . date('YmdHis') . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmpName, ROOT_DIR . '/' . $relativePath)) {
        throw new RuntimeException('Nepodařilo se uložit fotku.');
    }

    return $relativePath;
}

function kontakty_lide_image_delete_file_if_unused(PDO $pdo, string $relativePath, int $excludeId = 0): void
{
    $relativePath = trim($relativePath);
    if ($relativePath === '') {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM kontakty_lide WHERE image = :image AND id <> :id');
    $stmt->execute([':image' => $relativePath, ':id' => $excludeId]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $absoluteBase = realpath(ROOT_DIR . '/' . kontakty_lide_image_relative_dir());
    $absolutePath = realpath(ROOT_DIR . '/' . ltrim($relativePath, '/'));
    if ($absoluteBase === false || $absolutePath === false || strpos($absolutePath, $absoluteBase . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }

    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function kontakty_lide_person_save(PDO $pdo, array $data, ?array $file, ?int $id = null): int
{
    $normalized = kontakty_lide_normalize_person($data);
    kontakty_lide_validate_person($pdo, $normalized);

    $existingImage = '';
    if ($id !== null && $id > 0) {
        $existing = kontakty_lide_person_get($pdo, $id);
        $existingImage = (string)($existing['image'] ?? '');
    }

    if (!empty($data['delete_image'])) {
        kontakty_lide_image_delete_file_if_unused($pdo, $existingImage, (int)($id ?? 0));
        $normalized['image'] = '';
    } else {
        $normalized['image'] = kontakty_lide_image_upload($file, $existingImage);
        if ($existingImage !== '' && $normalized['image'] !== $existingImage) {
            kontakty_lide_image_delete_file_if_unused($pdo, $existingImage, (int)($id ?? 0));
        }
    }

    $user = admin_session_user();
    if ($id !== null && $id > 0) {
        $stmt = $pdo->prepare('UPDATE kontakty_lide SET
            skupina_id = :skupina_id, poradi = :poradi, jmeno = :jmeno, email = :email,
            mobil = :mobil, web = :web, image = :image, funkce_cz = :funkce_cz,
            funkce_en = :funkce_en, popis_cz = :popis_cz, popis_en = :popis_en,
            visible = :visible, valid = :valid, user_u = :user_u
            WHERE id = :id');
        $stmt->execute([
            ':skupina_id' => $normalized['skupina_id'], ':poradi' => $normalized['poradi'], ':jmeno' => $normalized['jmeno'],
            ':email' => $normalized['email'], ':mobil' => $normalized['mobil'], ':web' => $normalized['web'], ':image' => $normalized['image'],
            ':funkce_cz' => $normalized['funkce_cz'], ':funkce_en' => $normalized['funkce_en'], ':popis_cz' => $normalized['popis_cz'],
            ':popis_en' => $normalized['popis_en'], ':visible' => $normalized['visible'], ':valid' => $normalized['valid'],
            ':user_u' => $user, ':id' => $id,
        ]);
        admin_auto_translate_record('kontakty_lide.person', $id, $normalized + $data);
        return $id;
    }

    $stmt = $pdo->prepare('INSERT INTO kontakty_lide
        (skupina_id, poradi, jmeno, email, mobil, web, image, funkce_cz, funkce_en, popis_cz, popis_en, visible, valid, user_i, user_u)
        VALUES (:skupina_id, :poradi, :jmeno, :email, :mobil, :web, :image, :funkce_cz, :funkce_en, :popis_cz, :popis_en, :visible, :valid, :user_i, :user_u)');
    $stmt->execute([
        ':skupina_id' => $normalized['skupina_id'], ':poradi' => $normalized['poradi'], ':jmeno' => $normalized['jmeno'],
        ':email' => $normalized['email'], ':mobil' => $normalized['mobil'], ':web' => $normalized['web'], ':image' => $normalized['image'],
        ':funkce_cz' => $normalized['funkce_cz'], ':funkce_en' => $normalized['funkce_en'], ':popis_cz' => $normalized['popis_cz'],
        ':popis_en' => $normalized['popis_en'], ':visible' => $normalized['visible'], ':valid' => $normalized['valid'],
        ':user_i' => $user, ':user_u' => $user,
    ]);

    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('kontakty_lide.person', $newId, $normalized + $data);

    return $newId;
}

function kontakty_lide_group_save(PDO $pdo, array $data, ?int $id = null): int
{
    $normalized = kontakty_lide_normalize_group($data);
    kontakty_lide_validate_group($normalized);
    $user = admin_session_user();

    if ($id !== null && $id > 0) {
        $stmt = $pdo->prepare('UPDATE kontakty_lide_skupiny SET poradi = :poradi, nazev_cz = :nazev_cz, nazev_en = :nazev_en, visible = :visible, valid = :valid, user_u = :user_u WHERE id = :id');
        $stmt->execute([
            ':poradi' => $normalized['poradi'], ':nazev_cz' => $normalized['nazev_cz'], ':nazev_en' => $normalized['nazev_en'],
            ':visible' => $normalized['visible'], ':valid' => $normalized['valid'], ':user_u' => $user, ':id' => $id,
        ]);
        admin_auto_translate_record('kontakty_lide.group', $id, $normalized + $data);
        return $id;
    }

    $stmt = $pdo->prepare('INSERT INTO kontakty_lide_skupiny (poradi, nazev_cz, nazev_en, visible, valid, user_i, user_u) VALUES (:poradi, :nazev_cz, :nazev_en, :visible, :valid, :user_i, :user_u)');
    $stmt->execute([
        ':poradi' => $normalized['poradi'], ':nazev_cz' => $normalized['nazev_cz'], ':nazev_en' => $normalized['nazev_en'],
        ':visible' => $normalized['visible'], ':valid' => $normalized['valid'], ':user_i' => $user, ':user_u' => $user,
    ]);

    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('kontakty_lide.group', $newId, $normalized + $data);

    return $newId;
}

function kontakty_lide_person_set_valid(PDO $pdo, int $id, int $valid): void
{
    $stmt = $pdo->prepare('UPDATE kontakty_lide SET valid = :valid, visible = IF(:valid = 1, visible, 0), user_u = :user_u WHERE id = :id');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0, ':user_u' => admin_session_user(), ':id' => $id]);
}

function kontakty_lide_person_delete_image(PDO $pdo, int $id): void
{
    $person = kontakty_lide_person_get($pdo, $id);
    if (!$person) {
        return;
    }

    kontakty_lide_image_delete_file_if_unused($pdo, (string)($person['image'] ?? ''), $id);

    $stmt = $pdo->prepare('UPDATE kontakty_lide SET image = "", user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

function kontakty_lide_group_set_valid(PDO $pdo, int $id, int $valid): void
{
    $stmt = $pdo->prepare('UPDATE kontakty_lide_skupiny SET valid = :valid, visible = IF(:valid = 1, visible, 0), user_u = :user_u WHERE id = :id');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0, ':user_u' => admin_session_user(), ':id' => $id]);
}
