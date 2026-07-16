<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function obchodni_zastupci_page_url(array $params = []): string
{
    $query = array_merge(
        [
            'section' => '01',
            'page' => '04',
            'sec_page' => '04',
        ],
        $params
    );

    return 'index.php?' . http_build_query($query);
}

function obchodni_zastupci_redirect(array $params = []): void
{
    $url = obchodni_zastupci_page_url($params);
    $jsUrl = json_encode($url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<script type='text/javascript'>document.location.href={$jsUrl};</script>";
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '">';
}

function obchodni_zastupci_default_form_data(): array
{
    return [
        'pobocka_id' => 0,
        'oblast_id' => null,
        'poradi' => 0,
        'jmeno' => '',
        'mobil' => '',
        'email' => '',
        'web' => '',
        'image' => '',
        'popis_cz' => '',
        'popis_en' => '',
        'valid' => 1,
    ];
}

function obchodni_zastupci_fetch_pobocky(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, typ, nazev_cz, adresa
         FROM pobocky
         WHERE valid = 1
         ORDER BY FIELD(typ, "velkoobchod", "prodejna", "market"), poradi ASC, nazev_cz ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obchodni_zastupci_pobocka_label(array $row): string
{
    $typeLabels = [
        'market' => 'Market',
        'prodejna' => 'Prodejna',
        'velkoobchod' => 'Velkoobchod',
    ];
    $type = (string)($row['typ'] ?? '');
    $label = (string)($typeLabels[$type] ?? $type);
    $name = trim((string)($row['nazev_cz'] ?? ''));
    $address = trim((string)($row['adresa'] ?? ''));

    return trim($label . ': ' . $name . ($address !== '' ? ' - ' . $address : ''));
}

function obchodni_zastupci_normalize_form_data(array $source): array
{
    $default = obchodni_zastupci_default_form_data();
    $oblastId = isset($source['oblast_id']) && trim((string)$source['oblast_id']) !== ''
        ? max(0, (int)$source['oblast_id'])
        : null;

    return [
        'pobocka_id' => max(0, (int)($source['pobocka_id'] ?? $default['pobocka_id'])),
        'oblast_id' => $oblastId !== 0 ? $oblastId : null,
        'poradi' => (int)($source['poradi'] ?? $default['poradi']),
        'jmeno' => trim((string)($source['jmeno'] ?? $default['jmeno'])),
        'mobil' => trim((string)($source['mobil'] ?? $default['mobil'])),
        'email' => trim((string)($source['email'] ?? $default['email'])),
        'web' => trim((string)($source['web'] ?? $default['web'])),
        'image' => trim((string)($source['image'] ?? $default['image'])),
        'popis_cz' => (string)($source['popis_cz'] ?? $default['popis_cz']),
        'popis_en' => (string)($source['popis_en'] ?? $default['popis_en']),
        'valid' => !empty($source['valid']) ? 1 : 0,
    ];
}

function obchodni_zastupci_validate(PDO $pdo, array $data): void
{
    if ((int)$data['pobocka_id'] <= 0) {
        throw new InvalidArgumentException('Vyberte pobocku.');
    }

    if (trim((string)$data['jmeno']) === '') {
        throw new InvalidArgumentException('Vyplnte jmeno obchodniho zastupce.');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM pobocky WHERE id = :id AND valid = 1');
    $stmt->execute([':id' => (int)$data['pobocka_id']]);
    if ((int)$stmt->fetchColumn() === 0) {
        throw new InvalidArgumentException('Vybrana pobocka neexistuje nebo neni aktivni.');
    }
}

function obchodni_zastupci_image_relative_dir(): string
{
    return 'media/obchodni-zastupci';
}

function obchodni_zastupci_image_upload(?array $file, string $existingImage = ''): string
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingImage;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nepodarilo se nahrat obrazek.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Nahrany soubor neni platny.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('Soubor neni podporovany obrazek.');
    }

    $mime = (string)($imageInfo['mime'] ?? '');
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Podporovane formaty obrazku jsou JPG, PNG a WebP.');
    }

    $baseName = text_str(pathinfo((string)($file['name'] ?? 'obchodni-zastupce'), PATHINFO_FILENAME));
    if ($baseName === '') {
        $baseName = 'obchodni-zastupce';
    }

    $relativeDir = obchodni_zastupci_image_relative_dir();
    $absoluteDir = ROOT_DIR . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodarilo se vytvorit adresar pro obrazky.');
    }

    $targetName = $baseName . '-' . date('YmdHis') . '.' . $extensions[$mime];
    $relativePath = $relativeDir . '/' . $targetName;
    $targetPath = ROOT_DIR . '/' . $relativePath;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Nepodarilo se ulozit obrazek.');
    }

    return $relativePath;
}

function obchodni_zastupci_delete_image_file_if_unused(PDO $pdo, string $relativePath, int $excludeId = 0): void
{
    $relativePath = trim($relativePath);
    if ($relativePath === '') {
        return;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM obchodni_zastupci WHERE image = :image AND id <> :id');
    $stmt->execute([
        ':image' => $relativePath,
        ':id' => $excludeId,
    ]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $absoluteBase = realpath(ROOT_DIR . '/' . obchodni_zastupci_image_relative_dir());
    $absolutePath = realpath(ROOT_DIR . '/' . ltrim($relativePath, '/'));
    if ($absoluteBase === false || $absolutePath === false) {
        return;
    }

    if (strpos($absolutePath, $absoluteBase . DIRECTORY_SEPARATOR) !== 0) {
        return;
    }

    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function obchodni_zastupci_count(PDO $pdo, ?int $valid = null): int
{
    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM obchodni_zastupci')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM obchodni_zastupci WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function obchodni_zastupci_fetch_one(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM obchodni_zastupci WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function obchodni_zastupci_add(PDO $pdo, array $data): int
{
    obchodni_zastupci_validate($pdo, $data);

    $stmt = $pdo->prepare(
        'INSERT INTO obchodni_zastupci (
            pobocka_id, oblast_id, poradi, jmeno, mobil, email, web, image,
            popis_cz, popis_en, valid, user_i, user_u
        ) VALUES (
            :pobocka_id, :oblast_id, :poradi, :jmeno, :mobil, :email, :web, :image,
            :popis_cz, :popis_en, :valid, :user_i, :user_u
        )'
    );
    $stmt->execute([
        ':pobocka_id' => (int)$data['pobocka_id'],
        ':oblast_id' => $data['oblast_id'],
        ':poradi' => (int)$data['poradi'],
        ':jmeno' => (string)$data['jmeno'],
        ':mobil' => (string)$data['mobil'],
        ':email' => (string)$data['email'],
        ':web' => (string)$data['web'],
        ':image' => (string)$data['image'],
        ':popis_cz' => editor_html((string)$data['popis_cz']),
        ':popis_en' => editor_html((string)$data['popis_en']),
        ':valid' => (int)$data['valid'],
        ':user_i' => (string)($_SESSION['user_admin'] ?? ''),
        ':user_u' => (string)($_SESSION['user_admin'] ?? ''),
    ]);

    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('obchodni_zastupci.record', $newId, [
        'popis_cz' => editor_html((string)$data['popis_cz']),
        'popis_en' => editor_html((string)$data['popis_en']),
    ] + $data);

    return $newId;
}

function obchodni_zastupci_edit(PDO $pdo, int $id, array $data): void
{
    obchodni_zastupci_validate($pdo, $data);

    $stmt = $pdo->prepare(
        'UPDATE obchodni_zastupci SET
            pobocka_id = :pobocka_id,
            oblast_id = :oblast_id,
            poradi = :poradi,
            jmeno = :jmeno,
            mobil = :mobil,
            email = :email,
            web = :web,
            image = :image,
            popis_cz = :popis_cz,
            popis_en = :popis_en,
            valid = :valid,
            user_u = :user_u
         WHERE id = :id'
    );
    $stmt->execute([
        ':pobocka_id' => (int)$data['pobocka_id'],
        ':oblast_id' => $data['oblast_id'],
        ':poradi' => (int)$data['poradi'],
        ':jmeno' => (string)$data['jmeno'],
        ':mobil' => (string)$data['mobil'],
        ':email' => (string)$data['email'],
        ':web' => (string)$data['web'],
        ':image' => (string)$data['image'],
        ':popis_cz' => editor_html((string)$data['popis_cz']),
        ':popis_en' => editor_html((string)$data['popis_en']),
        ':valid' => (int)$data['valid'],
        ':user_u' => (string)($_SESSION['user_admin'] ?? ''),
        ':id' => $id,
    ]);

    admin_auto_translate_record('obchodni_zastupci.record', $id, [
        'popis_cz' => editor_html((string)$data['popis_cz']),
        'popis_en' => editor_html((string)$data['popis_en']),
    ] + $data);
}

function obchodni_zastupci_delete(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare(
        'UPDATE obchodni_zastupci
         SET valid = 0, user_u = :user_u
         WHERE id = :id'
    );
    $stmt->execute([
        ':user_u' => (string)($_SESSION['user_admin'] ?? ''),
        ':id' => $id,
    ]);
}

function obchodni_zastupci_vypis(PDO $pdo, int $limit, int $valid): void
{
    $sqlLimit = ($limit === 0) ? 999999 : max(1, $limit);

    $stmt = $pdo->prepare(
        'SELECT oz.id, oz.poradi, oz.jmeno, oz.mobil, oz.email, oz.oblast_id, oz.valid, oz.user_u, oz.ts_u,
                p.typ AS pobocka_typ, p.nazev_cz AS pobocka_nazev
         FROM obchodni_zastupci oz
         LEFT JOIN pobocky p ON p.id = oz.pobocka_id
         WHERE oz.valid = :valid
         ORDER BY oz.poradi ASC, oz.jmeno ASC, oz.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqlLimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $editUrl = obchodni_zastupci_page_url([
            'edit' => (int)$row['id'],
            'limit' => $limit,
            'valid' => $valid,
            'show' => 2,
        ]);
        $deleteUrl = obchodni_zastupci_page_url([
            'del' => (int)$row['id'],
            'limit' => $limit,
            'valid' => $valid,
        ]);
        $validBadge = ((int)($row['valid'] ?? 0) === 1)
            ? '<span class="badge text-bg-success">ANO</span>'
            : '<span class="badge text-bg-secondary">NE</span>';
        $pobocka = trim((string)($row['pobocka_nazev'] ?? ''));
        $oblast = isset($row['oblast_id']) && (int)$row['oblast_id'] > 0 ? (string)(int)$row['oblast_id'] : '';

        echo '<tr>';
        echo '<td>' . (int)($row['poradi'] ?? 0) . '</td>';
        echo '<td class="text-truncate" title="' . htmlspecialchars((string)($row['jmeno'] ?? ''), ENT_QUOTES) . '">' . htmlspecialchars((string)($row['jmeno'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td class="text-truncate" title="' . htmlspecialchars($pobocka, ENT_QUOTES) . '">' . htmlspecialchars($pobocka, ENT_QUOTES) . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($oblast, ENT_QUOTES) . '</td>';
        echo '<td class="text-truncate" title="' . htmlspecialchars((string)($row['mobil'] ?? ''), ENT_QUOTES) . '">' . htmlspecialchars((string)($row['mobil'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td class="text-truncate" title="' . htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES) . '">' . htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td class="text-center">' . $validBadge . '</td>';
        echo '<td>' .
            htmlspecialchars((string)format_datetime_www((string)($row['ts_u'] ?? '')), ENT_QUOTES) .
            '<br><small class="text-muted">' . htmlspecialchars((string)($row['user_u'] ?? ''), ENT_QUOTES) . '</small></td>';
        echo '<td class="text-center">';
        echo '<div class="d-inline-flex gap-1">';
        echo '<a class="btn btn-success btn-circle btn-sm" href="' . htmlspecialchars($editUrl, ENT_QUOTES) . '"><i class="bi bi-pencil"></i></a>';
        echo '<a class="btn btn-danger btn-circle btn-sm" href="' . htmlspecialchars($deleteUrl, ENT_QUOTES) . '" onclick="return confirm(\'Opravdu smazat tento zaznam?\')"><i class="bi bi-trash"></i></a>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
}
