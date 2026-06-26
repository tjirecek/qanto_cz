<?php
declare(strict_types=1);

function pobocky_type_definitions(): array
{
    return [
        'market' => [
            'label' => 'Markety',
            'single' => 'Market',
            'sec_page' => '02',
            'aliases' => ['market', 'markety'],
        ],
        'prodejna' => [
            'label' => 'Prodejny',
            'single' => 'Prodejna',
            'sec_page' => '01',
            'aliases' => ['prodejna', 'prodejny'],
        ],
        'velkoobchod' => [
            'label' => 'Velkoobchody',
            'single' => 'Velkoobchod',
            'sec_page' => '03',
            'aliases' => ['velkoobchod', 'velkoobchody'],
        ],
    ];
}

function pobocky_normalize_type(string $type, string $default = 'prodejna'): string
{
    $needle = strtolower(trim($type));
    if ($needle === '') {
        return $default;
    }

    foreach (pobocky_type_definitions() as $normalized => $config) {
        foreach (($config['aliases'] ?? []) as $alias) {
            if ($needle === strtolower((string)$alias)) {
                return $normalized;
            }
        }
    }

    return $default;
}

function pobocky_type_label(string $type): string
{
    $normalized = pobocky_normalize_type($type);
    $definitions = pobocky_type_definitions();

    return (string)($definitions[$normalized]['label'] ?? $normalized);
}

function pobocky_type_single_label(string $type): string
{
    $normalized = pobocky_normalize_type($type);
    $definitions = pobocky_type_definitions();

    return (string)($definitions[$normalized]['single'] ?? $normalized);
}

function pobocky_page_url(string $type, array $params = []): string
{
    $normalized = pobocky_normalize_type($type);
    $definitions = pobocky_type_definitions();
    $secPage = (string)($definitions[$normalized]['sec_page'] ?? '01');

    $query = array_merge(
        [
            'section' => '01',
            'page' => '03',
            'sec_page' => $secPage,
        ],
        $params
    );

    return 'index.php?' . http_build_query($query);
}

function pobocky_redirect(string $type, array $params = []): void
{
    $url = pobocky_page_url($type, $params);
    $jsUrl = json_encode($url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<script type='text/javascript'>document.location.href={$jsUrl};</script>";
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '">';
}

function pobocky_schema_sql(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS pobocky (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            typ ENUM('market', 'prodejna', 'velkoobchod') NOT NULL,
            poradi INT NOT NULL DEFAULT 0,
            stredisko VARCHAR(50) DEFAULT NULL,
            galerie_id INT UNSIGNED DEFAULT NULL,
            nazev_cz VARCHAR(255) NOT NULL,
            nazev_en VARCHAR(255) DEFAULT NULL,
            mobil VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            adresa TEXT DEFAULT NULL,
            gps VARCHAR(100) DEFAULT NULL,
            vedouci VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            sluzby_cz TEXT DEFAULT NULL,
            sluzby_en TEXT DEFAULT NULL,
            valid TINYINT(1) NOT NULL DEFAULT 1,
            user_i VARCHAR(100) NOT NULL DEFAULT '',
            user_u VARCHAR(100) NOT NULL DEFAULT '',
            ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pobocky_typ_valid_poradi (typ, valid, poradi),
            KEY idx_pobocky_stredisko (stredisko),
            KEY idx_pobocky_galerie_id (galerie_id),
            KEY idx_pobocky_nazev_cz (nazev_cz)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS pobocky_otevdoba (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            pobocka_id INT UNSIGNED NOT NULL,
            den TINYINT UNSIGNED NOT NULL,
            zavreno TINYINT(1) NOT NULL DEFAULT 0,
            od1 TIME DEFAULT NULL,
            do1 TIME DEFAULT NULL,
            od2 TIME DEFAULT NULL,
            do2 TIME DEFAULT NULL,
            poznamka_cz VARCHAR(255) DEFAULT NULL,
            poznamka_en VARCHAR(255) DEFAULT NULL,
            sync_lock TINYINT(1) NOT NULL DEFAULT 0,
            valid TINYINT(1) NOT NULL DEFAULT 1,
            user_i VARCHAR(100) NOT NULL DEFAULT '',
            user_u VARCHAR(100) NOT NULL DEFAULT '',
            ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_pobocky_otevdoba_pobocka_den (pobocka_id, den),
            KEY idx_pobocky_otevdoba_den (den),
            KEY idx_pobocky_otevdoba_valid (valid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS pobocky_otevdoba_vyjimky (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            pobocka_id INT UNSIGNED NOT NULL,
            datum DATE NOT NULL,
            zavreno TINYINT(1) NOT NULL DEFAULT 0,
            od1 TIME DEFAULT NULL,
            do1 TIME DEFAULT NULL,
            od2 TIME DEFAULT NULL,
            do2 TIME DEFAULT NULL,
            poznamka_cz VARCHAR(255) DEFAULT NULL,
            poznamka_en VARCHAR(255) DEFAULT NULL,
            valid TINYINT(1) NOT NULL DEFAULT 1,
            user_i VARCHAR(100) NOT NULL DEFAULT '',
            user_u VARCHAR(100) NOT NULL DEFAULT '',
            ts_i TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ts_u TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_pobocky_otevdoba_vyjimky_pobocka_datum (pobocka_id, datum),
            KEY idx_pobocky_otevdoba_vyjimky_datum (datum),
            KEY idx_pobocky_otevdoba_vyjimky_valid (valid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

function pobocky_prepare_tables(PDO $pdo): void
{
    foreach (pobocky_schema_sql() as $sql) {
        $pdo->exec($sql);
    }
}

function pobocky_default_form_data(string $type = 'prodejna'): array
{
    return [
        'typ' => pobocky_normalize_type($type),
        'poradi' => 0,
        'stredisko' => '',
        'galerie_id' => null,
        'nazev_cz' => '',
        'nazev_en' => '',
        'mobil' => '',
        'email' => '',
        'adresa' => '',
        'gps' => '',
        'vedouci' => '',
        'image' => '',
        'sluzby_cz' => '',
        'sluzby_en' => '',
        'valid' => 1,
    ];
}

function pobocky_day_definitions(): array
{
    return [
        1 => 'Pondělí',
        2 => 'Úterý',
        3 => 'Středa',
        4 => 'Čtvrtek',
        5 => 'Pátek',
        6 => 'Sobota',
        7 => 'Neděle',
    ];
}

function pobocky_day_label(int $day): string
{
    $definitions = pobocky_day_definitions();
    return (string)($definitions[$day] ?? ('Den ' . $day));
}

function pobocky_time_to_db(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^(2[0-3]|[01]?\d):([0-5]\d)(?::([0-5]\d))?$/', $value, $match)) {
        throw new InvalidArgumentException('Neplatny format casu.');
    }

    return sprintf('%02d:%02d:00', (int)$match[1], (int)$match[2]);
}

function pobocky_time_to_input(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return substr($value, 0, 5);
}

function pobocky_otevdoba_default_row(int $day): array
{
    return [
        'den' => $day,
        'zavreno' => 0,
        'od1' => '',
        'do1' => '',
        'od2' => '',
        'do2' => '',
        'poznamka_cz' => '',
        'poznamka_en' => '',
        'sync_lock' => 0,
        'valid' => 1,
    ];
}

function pobocky_otevdoba_default_week(): array
{
    $rows = [];
    foreach (array_keys(pobocky_day_definitions()) as $day) {
        $rows[$day] = pobocky_otevdoba_default_row($day);
    }

    return $rows;
}

function pobocky_otevdoba_validate_intervals(array $row): array
{
    if ((int)($row['zavreno'] ?? 0) === 1) {
        $row['od1'] = '';
        $row['do1'] = '';
        $row['od2'] = '';
        $row['do2'] = '';
        return $row;
    }

    $pairs = [
        ['od1', 'do1', 'prvni interval'],
        ['od2', 'do2', 'druhy interval'],
    ];

    foreach ($pairs as [$fromKey, $toKey, $label]) {
        $from = trim((string)($row[$fromKey] ?? ''));
        $to = trim((string)($row[$toKey] ?? ''));

        if (($from === '' && $to !== '') || ($from !== '' && $to === '')) {
            throw new InvalidArgumentException('Casy pro ' . $label . ' musi byt vyplnene oba.');
        }

        if ($from !== '' && $to !== '' && strcmp($from, $to) >= 0) {
            throw new InvalidArgumentException('Cas od musi byt mensi nez cas do pro ' . $label . '.');
        }
    }

    $od1 = trim((string)($row['od1'] ?? ''));
    $do1 = trim((string)($row['do1'] ?? ''));
    $od2 = trim((string)($row['od2'] ?? ''));
    if ($do1 !== '' && $od2 !== '' && strcmp($do1, $od2) > 0) {
        throw new InvalidArgumentException('Druhy interval musi navazovat az po konci prvniho intervalu.');
    }

    return $row;
}

function pobocky_otevdoba_normalize_row(int $day, array $source): array
{
    if (!isset(pobocky_day_definitions()[$day])) {
        throw new InvalidArgumentException('Neplatny den v tydnu.');
    }

    $row = [
        'den' => $day,
        'zavreno' => !empty($source['zavreno']) ? 1 : 0,
        'od1' => trim((string)($source['od1'] ?? '')),
        'do1' => trim((string)($source['do1'] ?? '')),
        'od2' => trim((string)($source['od2'] ?? '')),
        'do2' => trim((string)($source['do2'] ?? '')),
        'poznamka_cz' => trim((string)($source['poznamka_cz'] ?? '')),
        'poznamka_en' => trim((string)($source['poznamka_en'] ?? '')),
        'sync_lock' => !empty($source['sync_lock']) ? 1 : 0,
        'valid' => !empty($source['valid']) ? 1 : 0,
    ];

    $row = pobocky_otevdoba_validate_intervals($row);
    foreach (['od1', 'do1', 'od2', 'do2'] as $key) {
        $row[$key] = pobocky_time_to_input((string)($row[$key] ?? ''));
    }

    return $row;
}

function pobocky_otevdoba_normalize_week(array $source): array
{
    $rows = pobocky_otevdoba_default_week();
    foreach ($rows as $day => $defaultRow) {
        $rows[$day] = pobocky_otevdoba_normalize_row($day, (array)($source[$day] ?? []));
    }

    return $rows;
}

function pobocky_otevdoba_fetch_week(PDO $pdo, int $pobockaId): array
{
    $rows = pobocky_otevdoba_default_week();
    $stmt = $pdo->prepare(
        'SELECT den, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en, sync_lock, valid
         FROM pobocky_otevdoba
         WHERE pobocka_id = :pobocka_id
         ORDER BY den ASC'
    );
    $stmt->execute([':pobocka_id' => $pobockaId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $day = (int)($row['den'] ?? 0);
        if (!isset($rows[$day])) {
            continue;
        }

        $rows[$day] = [
            'den' => $day,
            'zavreno' => (int)($row['zavreno'] ?? 0),
            'od1' => pobocky_time_to_input((string)($row['od1'] ?? '')),
            'do1' => pobocky_time_to_input((string)($row['do1'] ?? '')),
            'od2' => pobocky_time_to_input((string)($row['od2'] ?? '')),
            'do2' => pobocky_time_to_input((string)($row['do2'] ?? '')),
            'poznamka_cz' => (string)($row['poznamka_cz'] ?? ''),
            'poznamka_en' => (string)($row['poznamka_en'] ?? ''),
            'sync_lock' => (int)($row['sync_lock'] ?? 0),
            'valid' => (int)($row['valid'] ?? 1),
        ];
    }

    return $rows;
}

function pobocky_otevdoba_save_week(PDO $pdo, int $pobockaId, array $rows): void
{
    $qnUser = admin_session_user();
    $stmt = $pdo->prepare(
        'INSERT INTO pobocky_otevdoba (
            pobocka_id, den, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en, sync_lock, valid, user_i, user_u
         ) VALUES (
            :pobocka_id, :den, :zavreno, :od1, :do1, :od2, :do2, :poznamka_cz, :poznamka_en, :sync_lock, :valid, :user_i, :user_u
         )
         ON DUPLICATE KEY UPDATE
            zavreno = VALUES(zavreno),
            od1 = VALUES(od1),
            do1 = VALUES(do1),
            od2 = VALUES(od2),
            do2 = VALUES(do2),
            poznamka_cz = VALUES(poznamka_cz),
            poznamka_en = VALUES(poznamka_en),
            valid = VALUES(valid),
            user_u = VALUES(user_u)'
    );

    foreach ($rows as $row) {
        $stmt->execute([
            ':pobocka_id' => $pobockaId,
            ':den' => (int)$row['den'],
            ':zavreno' => (int)$row['zavreno'],
            ':od1' => pobocky_time_to_db($row['od1'] ?? null),
            ':do1' => pobocky_time_to_db($row['do1'] ?? null),
            ':od2' => pobocky_time_to_db($row['od2'] ?? null),
            ':do2' => pobocky_time_to_db($row['do2'] ?? null),
            ':poznamka_cz' => ($row['poznamka_cz'] ?? '') !== '' ? (string)$row['poznamka_cz'] : null,
            ':poznamka_en' => ($row['poznamka_en'] ?? '') !== '' ? (string)$row['poznamka_en'] : null,
            ':sync_lock' => (int)($row['sync_lock'] ?? 0),
            ':valid' => (int)($row['valid'] ?? 1),
            ':user_i' => $qnUser,
            ':user_u' => $qnUser,
        ]);
    }
}

function pobocky_otevdoba_default_exception(): array
{
    return [
        'id' => 0,
        'datum' => '',
        'zavreno' => 0,
        'od1' => '',
        'do1' => '',
        'od2' => '',
        'do2' => '',
        'poznamka_cz' => '',
        'poznamka_en' => '',
        'valid' => 1,
    ];
}

function pobocky_otevdoba_normalize_exception(array $source): array
{
    $row = [
        'id' => (int)($source['id'] ?? 0),
        'datum' => trim((string)($source['datum'] ?? '')),
        'zavreno' => !empty($source['zavreno']) ? 1 : 0,
        'od1' => trim((string)($source['od1'] ?? '')),
        'do1' => trim((string)($source['do1'] ?? '')),
        'od2' => trim((string)($source['od2'] ?? '')),
        'do2' => trim((string)($source['do2'] ?? '')),
        'poznamka_cz' => trim((string)($source['poznamka_cz'] ?? '')),
        'poznamka_en' => trim((string)($source['poznamka_en'] ?? '')),
        'valid' => !empty($source['valid']) ? 1 : 0,
    ];

    if ($row['datum'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['datum'])) {
        throw new InvalidArgumentException('Datum vyjimky je povinne.');
    }

    $row = pobocky_otevdoba_validate_intervals($row);
    foreach (['od1', 'do1', 'od2', 'do2'] as $key) {
        $row[$key] = pobocky_time_to_input((string)($row[$key] ?? ''));
    }

    return $row;
}

function pobocky_otevdoba_fetch_exceptions(PDO $pdo, int $pobockaId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, datum, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en, valid, ts_u, user_u
         FROM pobocky_otevdoba_vyjimky
         WHERE pobocka_id = :pobocka_id AND valid = 1
         ORDER BY datum DESC, id DESC'
    );
    $stmt->execute([':pobocka_id' => $pobockaId]);

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'id' => (int)($row['id'] ?? 0),
            'datum' => (string)($row['datum'] ?? ''),
            'zavreno' => (int)($row['zavreno'] ?? 0),
            'od1' => pobocky_time_to_input((string)($row['od1'] ?? '')),
            'do1' => pobocky_time_to_input((string)($row['do1'] ?? '')),
            'od2' => pobocky_time_to_input((string)($row['od2'] ?? '')),
            'do2' => pobocky_time_to_input((string)($row['do2'] ?? '')),
            'poznamka_cz' => (string)($row['poznamka_cz'] ?? ''),
            'poznamka_en' => (string)($row['poznamka_en'] ?? ''),
            'valid' => (int)($row['valid'] ?? 1),
            'ts_u' => (string)($row['ts_u'] ?? ''),
            'user_u' => (string)($row['user_u'] ?? ''),
        ];
    }

    return $rows;
}

function pobocky_otevdoba_save_exception(PDO $pdo, int $pobockaId, array $row): void
{
    $qnUser = admin_session_user();
    if ((int)($row['id'] ?? 0) > 0) {
        $stmt = $pdo->prepare(
            'UPDATE pobocky_otevdoba_vyjimky
             SET datum = :datum,
                 zavreno = :zavreno,
                 od1 = :od1,
                 do1 = :do1,
                 od2 = :od2,
                 do2 = :do2,
                 poznamka_cz = :poznamka_cz,
                 poznamka_en = :poznamka_en,
                 valid = :valid,
                 user_u = :user_u
             WHERE id = :id AND pobocka_id = :pobocka_id'
        );
        $stmt->execute([
            ':id' => (int)$row['id'],
            ':pobocka_id' => $pobockaId,
            ':datum' => (string)$row['datum'],
            ':zavreno' => (int)$row['zavreno'],
            ':od1' => pobocky_time_to_db($row['od1'] ?? null),
            ':do1' => pobocky_time_to_db($row['do1'] ?? null),
            ':od2' => pobocky_time_to_db($row['od2'] ?? null),
            ':do2' => pobocky_time_to_db($row['do2'] ?? null),
            ':poznamka_cz' => ($row['poznamka_cz'] ?? '') !== '' ? (string)$row['poznamka_cz'] : null,
            ':poznamka_en' => ($row['poznamka_en'] ?? '') !== '' ? (string)$row['poznamka_en'] : null,
            ':valid' => (int)$row['valid'],
            ':user_u' => $qnUser,
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO pobocky_otevdoba_vyjimky (
            pobocka_id, datum, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en, valid, user_i, user_u
         ) VALUES (
            :pobocka_id, :datum, :zavreno, :od1, :do1, :od2, :do2, :poznamka_cz, :poznamka_en, :valid, :user_i, :user_u
         )
         ON DUPLICATE KEY UPDATE
            zavreno = VALUES(zavreno),
            od1 = VALUES(od1),
            do1 = VALUES(do1),
            od2 = VALUES(od2),
            do2 = VALUES(do2),
            poznamka_cz = VALUES(poznamka_cz),
            poznamka_en = VALUES(poznamka_en),
            valid = VALUES(valid),
            user_u = VALUES(user_u)'
    );
    $stmt->execute([
        ':pobocka_id' => $pobockaId,
        ':datum' => (string)$row['datum'],
        ':zavreno' => (int)$row['zavreno'],
        ':od1' => pobocky_time_to_db($row['od1'] ?? null),
        ':do1' => pobocky_time_to_db($row['do1'] ?? null),
        ':od2' => pobocky_time_to_db($row['od2'] ?? null),
        ':do2' => pobocky_time_to_db($row['do2'] ?? null),
        ':poznamka_cz' => ($row['poznamka_cz'] ?? '') !== '' ? (string)$row['poznamka_cz'] : null,
        ':poznamka_en' => ($row['poznamka_en'] ?? '') !== '' ? (string)$row['poznamka_en'] : null,
        ':valid' => (int)$row['valid'],
        ':user_i' => $qnUser,
        ':user_u' => $qnUser,
    ]);
}

function pobocky_otevdoba_delete_exception(PDO $pdo, int $pobockaId, int $exceptionId): void
{
    $qnUser = admin_session_user();
    $stmt = $pdo->prepare(
        'UPDATE pobocky_otevdoba_vyjimky
         SET valid = 0, user_u = :user_u
         WHERE id = :id AND pobocka_id = :pobocka_id'
    );
    $stmt->execute([
        ':user_u' => $qnUser,
        ':id' => $exceptionId,
        ':pobocka_id' => $pobockaId,
    ]);
}

function pobocky_otevdoba_time_range_label(array $row): string
{
    if ((int)($row['zavreno'] ?? 0) === 1) {
        return 'Zavřeno';
    }

    $parts = [];
    foreach ([['od1', 'do1'], ['od2', 'do2']] as [$fromKey, $toKey]) {
        $from = trim((string)($row[$fromKey] ?? ''));
        $to = trim((string)($row[$toKey] ?? ''));
        if ($from !== '' && $to !== '') {
            $parts[] = $from . ' - ' . $to;
        }
    }

    return $parts === [] ? '' : implode(', ', $parts);
}

function pobocky_image_relative_dir(): string
{
    return 'media/pobocky';
}

function pobocky_image_upload(?array $file, string $existingImage = ''): string
{
    if (!is_array($file) || !isset($file['error'])) {
        return $existingImage;
    }

    $errorCode = (int)$file['error'];
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return $existingImage;
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Obrazek se nepodarilo nahrat.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Docasny soubor obrazku neni dostupny.');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('Nahrany soubor neni platny obrazek.');
    }

    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $extension = (string)($extensionMap[$mime] ?? '');
    }
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }
    if ($extension === '') {
        throw new RuntimeException('Nepodporovany format obrazku.');
    }

    $baseName = text_str(pathinfo((string)($file['name'] ?? 'pobocka'), PATHINFO_FILENAME));
    if ($baseName === '') {
        $baseName = 'pobocka';
    }

    $relativeDir = pobocky_image_relative_dir();
    $absoluteDir = ROOT_DIR . '/' . $relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nepodarilo se vytvorit adresar pro obrazky pobocek.');
    }

    $targetFile = $baseName . '-' . date('YmdHis');
    try {
        $targetFile .= '-' . bin2hex(random_bytes(3));
    } catch (Throwable $e) {
        $targetFile .= '-' . uniqid('', true);
    }
    $targetFile .= '.' . $extension;

    $relativePath = $relativeDir . '/' . $targetFile;
    $absolutePath = ROOT_DIR . '/' . $relativePath;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new RuntimeException('Obrazek se nepodarilo ulozit.');
    }

    if ($existingImage !== '' && $existingImage !== $relativePath) {
        $existingAbsolutePath = ROOT_DIR . '/' . ltrim($existingImage, '/');
        if (is_file($existingAbsolutePath)) {
            @unlink($existingAbsolutePath);
        }
    }

    return $relativePath;
}

function pobocky_normalize_form_data(array $source, string $defaultType = 'prodejna'): array
{
    $default = pobocky_default_form_data($defaultType);

    $galerieIdRaw = trim((string)($source['galerie_id'] ?? ''));
    $email = trim((string)($source['email'] ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('E-mail nema platny format.');
    }

    $data = [
        'typ' => pobocky_normalize_type((string)($source['typ'] ?? $default['typ']), $default['typ']),
        'poradi' => (int)($source['poradi'] ?? $default['poradi']),
        'stredisko' => trim((string)($source['stredisko'] ?? $default['stredisko'])),
        'galerie_id' => ($galerieIdRaw === '') ? null : (int)$galerieIdRaw,
        'nazev_cz' => trim((string)($source['nazev_cz'] ?? $default['nazev_cz'])),
        'nazev_en' => trim((string)($source['nazev_en'] ?? $default['nazev_en'])),
        'mobil' => trim((string)($source['mobil'] ?? $default['mobil'])),
        'email' => $email,
        'adresa' => trim((string)($source['adresa'] ?? $default['adresa'])),
        'gps' => trim((string)($source['gps'] ?? $default['gps'])),
        'vedouci' => trim((string)($source['vedouci'] ?? $default['vedouci'])),
        'image' => trim((string)($source['image'] ?? $default['image'])),
        'sluzby_cz' => trim((string)($source['sluzby_cz'] ?? $default['sluzby_cz'])),
        'sluzby_en' => trim((string)($source['sluzby_en'] ?? $default['sluzby_en'])),
        'valid' => isset($source['valid']) ? 1 : 0,
    ];

    if ($data['nazev_cz'] === '') {
        throw new InvalidArgumentException('Nazev CZ je povinny.');
    }

    return $data;
}

function pobocky_count(PDO $pdo, string $type, ?int $valid = null): int
{
    $normalized = pobocky_normalize_type($type);

    if ($valid === null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pobocky WHERE typ = :typ');
        $stmt->execute([':typ' => $normalized]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pobocky WHERE typ = :typ AND valid = :valid');
        $stmt->execute([
            ':typ' => $normalized,
            ':valid' => (int)$valid,
        ]);
    }

    return (int)$stmt->fetchColumn();
}

function pobocky_fetch_one(PDO $pdo, int $id, string $type): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM pobocky WHERE id = :id AND typ = :typ LIMIT 1');
    $stmt->execute([
        ':id' => $id,
        ':typ' => pobocky_normalize_type($type),
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function pobocky_add(PDO $pdo, array $data): int
{
    $qnUser = admin_session_user();
    $stmt = $pdo->prepare(
        'INSERT INTO pobocky (
            typ, poradi, stredisko, galerie_id, nazev_cz, nazev_en, mobil, email, adresa, gps,
            vedouci, image, sluzby_cz, sluzby_en, valid, user_i, user_u
        ) VALUES (
            :typ, :poradi, :stredisko, :galerie_id, :nazev_cz, :nazev_en, :mobil, :email, :adresa, :gps,
            :vedouci, :image, :sluzby_cz, :sluzby_en, :valid, :user_i, :user_u
        )'
    );

    $stmt->execute([
        ':typ' => pobocky_normalize_type((string)$data['typ']),
        ':poradi' => (int)$data['poradi'],
        ':stredisko' => $data['stredisko'] !== '' ? (string)$data['stredisko'] : null,
        ':galerie_id' => $data['galerie_id'],
        ':nazev_cz' => (string)$data['nazev_cz'],
        ':nazev_en' => $data['nazev_en'] !== '' ? (string)$data['nazev_en'] : null,
        ':mobil' => $data['mobil'] !== '' ? (string)$data['mobil'] : null,
        ':email' => $data['email'] !== '' ? (string)$data['email'] : null,
        ':adresa' => $data['adresa'] !== '' ? (string)$data['adresa'] : null,
        ':gps' => $data['gps'] !== '' ? (string)$data['gps'] : null,
        ':vedouci' => $data['vedouci'] !== '' ? (string)$data['vedouci'] : null,
        ':image' => $data['image'] !== '' ? (string)$data['image'] : null,
        ':sluzby_cz' => $data['sluzby_cz'] !== '' ? (string)$data['sluzby_cz'] : null,
        ':sluzby_en' => $data['sluzby_en'] !== '' ? (string)$data['sluzby_en'] : null,
        ':valid' => (int)$data['valid'],
        ':user_i' => $qnUser,
        ':user_u' => $qnUser,
    ]);

    return (int)$pdo->lastInsertId();
}

function pobocky_edit(PDO $pdo, int $id, string $type, array $data): void
{
    $qnUser = admin_session_user();
    $stmt = $pdo->prepare(
        'UPDATE pobocky SET
            typ = :typ,
            poradi = :poradi,
            stredisko = :stredisko,
            galerie_id = :galerie_id,
            nazev_cz = :nazev_cz,
            nazev_en = :nazev_en,
            mobil = :mobil,
            email = :email,
            adresa = :adresa,
            gps = :gps,
            vedouci = :vedouci,
            image = :image,
            sluzby_cz = :sluzby_cz,
            sluzby_en = :sluzby_en,
            valid = :valid,
            user_u = :user_u
         WHERE id = :id AND typ = :scope_typ'
    );

    $stmt->execute([
        ':typ' => pobocky_normalize_type((string)$data['typ']),
        ':poradi' => (int)$data['poradi'],
        ':stredisko' => $data['stredisko'] !== '' ? (string)$data['stredisko'] : null,
        ':galerie_id' => $data['galerie_id'],
        ':nazev_cz' => (string)$data['nazev_cz'],
        ':nazev_en' => $data['nazev_en'] !== '' ? (string)$data['nazev_en'] : null,
        ':mobil' => $data['mobil'] !== '' ? (string)$data['mobil'] : null,
        ':email' => $data['email'] !== '' ? (string)$data['email'] : null,
        ':adresa' => $data['adresa'] !== '' ? (string)$data['adresa'] : null,
        ':gps' => $data['gps'] !== '' ? (string)$data['gps'] : null,
        ':vedouci' => $data['vedouci'] !== '' ? (string)$data['vedouci'] : null,
        ':image' => $data['image'] !== '' ? (string)$data['image'] : null,
        ':sluzby_cz' => $data['sluzby_cz'] !== '' ? (string)$data['sluzby_cz'] : null,
        ':sluzby_en' => $data['sluzby_en'] !== '' ? (string)$data['sluzby_en'] : null,
        ':valid' => (int)$data['valid'],
        ':user_u' => $qnUser,
        ':id' => $id,
        ':scope_typ' => pobocky_normalize_type($type),
    ]);
}

function pobocky_delete(PDO $pdo, int $id, string $type): void
{
    $qnUser = admin_session_user();
    $stmt = $pdo->prepare(
        'UPDATE pobocky
         SET valid = 0, user_u = :user_u
         WHERE id = :id AND typ = :typ'
    );
    $stmt->execute([
        ':user_u' => $qnUser,
        ':id' => $id,
        ':typ' => pobocky_normalize_type($type),
    ]);
}

function pobocky_vypis(PDO $pdo, int $limit, int $valid, string $type): void
{
    $normalized = pobocky_normalize_type($type);
    $sqlLimit = ($limit === 0) ? 999999 : max(1, $limit);

    $stmt = $pdo->prepare(
        'SELECT
            id, poradi, stredisko, galerie_id, nazev_cz, nazev_en, mobil, email, adresa,
            vedouci, valid, user_u, ts_u
         FROM pobocky
         WHERE typ = :typ AND valid = :valid
         ORDER BY poradi ASC, nazev_cz ASC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':typ', $normalized, PDO::PARAM_STR);
    $stmt->bindValue(':valid', (int)$valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqlLimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $editUrl = pobocky_page_url($normalized, [
            'edit' => (int)$row['id'],
            'limit' => $limit,
            'valid' => $valid,
            'show' => 2,
        ]);
        $deleteUrl = pobocky_page_url($normalized, [
            'del' => (int)$row['id'],
            'limit' => $limit,
            'valid' => $valid,
        ]);

        $address = trim((string)($row['adresa'] ?? ''));
        $address = $address !== '' ? nl2br(htmlspecialchars($address, ENT_QUOTES)) : '';
        $validBadge = ((int)($row['valid'] ?? 0) === 1)
            ? '<span class="badge text-bg-success">ANO</span>'
            : '<span class="badge text-bg-secondary">NE</span>';

        echo '<tr>';
        echo '<td>' . (int)$row['id'] . '</td>';
        echo '<td>' . (int)($row['poradi'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['stredisko'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['nazev_cz'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['nazev_en'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['mobil'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . $address . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['vedouci'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td>' . htmlspecialchars((string)($row['galerie_id'] ?? ''), ENT_QUOTES) . '</td>';
        echo '<td class="text-center">' . $validBadge . '</td>';
        echo '<td>' .
            htmlspecialchars((string)format_datetime_www((string)($row['ts_u'] ?? '')), ENT_QUOTES) .
            '<br><small class="text-muted">' . htmlspecialchars((string)($row['user_u'] ?? ''), ENT_QUOTES) . '</small></td>';
        echo '<td class="text-center"><a class="btn btn-success btn-circle btn-sm" href="' . htmlspecialchars($editUrl, ENT_QUOTES) . '"><i class="bi bi-pencil"></i></a></td>';
        echo '<td class="text-center"><a class="btn btn-danger btn-circle btn-sm" href="' . htmlspecialchars($deleteUrl, ENT_QUOTES) . '" onclick="return confirm(\'Opravdu smazat tento zaznam?\')"><i class="bi bi-trash"></i></a></td>';
        echo '</tr>';
    }
}

function pobocky_table_counts(PDO $pdo): array
{
    $definitions = pobocky_type_definitions();
    $counts = [
        'total' => 0,
        'valid_total' => 0,
    ];

    foreach (array_keys($definitions) as $type) {
        $counts[$type] = 0;
        $counts[$type . '_valid'] = 0;
    }

    $stmt = $pdo->query(
        "SELECT
            typ,
            COUNT(*) AS total_count,
            SUM(CASE WHEN valid = 1 THEN 1 ELSE 0 END) AS valid_count
         FROM pobocky
         GROUP BY typ"
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $type = pobocky_normalize_type((string)($row['typ'] ?? ''), '');
        if ($type === '' || !isset($definitions[$type])) {
            continue;
        }

        $typeTotal = (int)($row['total_count'] ?? 0);
        $typeValid = (int)($row['valid_count'] ?? 0);

        $counts[$type] = $typeTotal;
        $counts[$type . '_valid'] = $typeValid;
        $counts['total'] += $typeTotal;
        $counts['valid_total'] += $typeValid;
    }

    return $counts;
}
