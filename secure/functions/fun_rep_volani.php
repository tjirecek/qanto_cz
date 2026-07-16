<?php
declare(strict_types=1);

function rep_volani_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_volani_money(mixed $value): string
{
    return number_format((float)$value, 2, ',', ' ') . ' Kč';
}

function rep_volani_number(mixed $value): string
{
    $number = (float)$value;
    return rtrim(rtrim(number_format($number, 2, ',', ' '), '0'), ',');
}

function rep_volani_decimal(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '0';
    }

    $value = str_replace(["\xc2\xa0", ' '], '', $value);
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value)) {
        return '0';
    }

    return (string)(float)$value;
}

function rep_volani_xml_string(SimpleXMLElement $row, string $field): string
{
    return trim((string)($row->{$field} ?? ''));
}

function rep_volani_period_label(string $period): string
{
    $period = trim($period);
    if ($period === '') {
        return '';
    }

    if (is_numeric($period)) {
        $days = (int)floor((float)$period);
        try {
            $date = new DateTimeImmutable('1900-01-01', new DateTimeZone('Europe/Prague'));
            return $date->modify('+' . $days . ' days')->format('n.Y');
        } catch (Throwable) {
            return $period;
        }
    }

    return $period;
}

function rep_volani_datetime_label(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (is_numeric($value)) {
        $numeric = (float)$value;
        $days = (int)floor($numeric);
        $fraction = $numeric - $days;
        try {
            $date = new DateTimeImmutable('1900-01-01', new DateTimeZone('Europe/Prague'));
            $date = $date->modify('+' . $days . ' days');
            $seconds = (int)floor($fraction * 86400);
            $date = $date->setTime(0, 0)->modify('+' . $seconds . ' seconds');
            return $date->format('j.n.Y H:i:s');
        } catch (Throwable) {
            return $value;
        }
    }

    return $value;
}

function rep_volani_token(PDO $pdo): string
{
    do {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('SELECT 1 FROM volani_preuctovani WHERE unify = :unify LIMIT 1');
        $stmt->execute([':unify' => $token]);
    } while ((bool)$stmt->fetchColumn());

    return $token;
}

function rep_volani_load_xml(string $path): SimpleXMLElement
{
    if (!is_file($path)) {
        throw new RuntimeException('Soubor neexistuje.');
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($path, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
    if (!$xml instanceof SimpleXMLElement) {
        $errors = array_map(static fn (LibXMLError $error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        throw new RuntimeException('XML soubor se nepodařilo načíst: ' . implode('; ', array_filter($errors)));
    }
    libxml_clear_errors();

    return $xml;
}

function rep_volani_uploaded_files(string $input): array
{
    $files = $_FILES[$input] ?? null;
    if (!is_array($files)) {
        return [];
    }

    if (!is_array($files['name'] ?? null)) {
        if ((int)($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [[
            'name' => (string)($files['name'] ?? ''),
            'tmp_name' => (string)($files['tmp_name'] ?? ''),
            'error' => (int)($files['error'] ?? UPLOAD_ERR_NO_FILE),
        ]];
    }

    $result = [];
    foreach ($files['name'] as $index => $name) {
        if ((int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result[] = [
            'name' => (string)$name,
            'tmp_name' => (string)($files['tmp_name'][$index] ?? ''),
            'error' => (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
        ];
    }

    return $result;
}

function rep_volani_assert_upload(array $file): void
{
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Soubor "' . (string)($file['name'] ?? '') . '" se nepodařilo nahrát.');
    }

    $name = strtolower((string)($file['name'] ?? ''));
    if (!str_ends_with($name, '.xml')) {
        throw new RuntimeException('Soubor "' . (string)($file['name'] ?? '') . '" není XML.');
    }

    if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Nahraný soubor není dostupný.');
    }
}

function rep_volani_import_prehled(PDO $pdo, string $path, string $sourceFile): int
{
    $xml = rep_volani_load_xml($path);
    $stmt = $pdo->prepare('
        INSERT INTO volani_preuctovani
            (obdobi, mobil, jmeno, email, zaklad0, zaklad21, zakladcelkem, celkem, unify, valid, imported_at, user_i, user_u)
        VALUES
            (:obdobi, :mobil, :jmeno, :email, :zaklad0, :zaklad21, :zakladcelkem, :celkem, :unify, 1, NOW(), :user_i, :user_u)
        ON DUPLICATE KEY UPDATE
            jmeno = VALUES(jmeno),
            email = VALUES(email),
            zaklad0 = VALUES(zaklad0),
            zaklad21 = VALUES(zaklad21),
            zakladcelkem = VALUES(zakladcelkem),
            celkem = VALUES(celkem),
            valid = 1,
            imported_at = NOW(),
            user_u = VALUES(user_u)
    ');

    $count = 0;
    $user = admin_session_user();
    foreach ($xml->TM as $row) {
        $obdobi = rep_volani_xml_string($row, 'OBDOBI');
        $mobil = rep_volani_xml_string($row, 'MOBIL');
        if ($obdobi === '' || $mobil === '') {
            continue;
        }

        $stmt->execute([
            ':obdobi' => $obdobi,
            ':mobil' => $mobil,
            ':jmeno' => rep_volani_xml_string($row, 'JMENO'),
            ':email' => rep_volani_xml_string($row, 'EMAIL'),
            ':zaklad0' => rep_volani_decimal(rep_volani_xml_string($row, 'ZAKLAD0')),
            ':zaklad21' => rep_volani_decimal(rep_volani_xml_string($row, 'ZAKLAD21')),
            ':zakladcelkem' => rep_volani_decimal(rep_volani_xml_string($row, 'ZAKLADCELKEM')),
            ':celkem' => rep_volani_decimal(rep_volani_xml_string($row, 'CELKEM')),
            ':unify' => rep_volani_token($pdo),
            ':user_i' => $user,
            ':user_u' => $user,
        ]);
        $count++;
    }

    return $count;
}

function rep_volani_row_hash(array $row): string
{
    return md5(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize($row));
}

function rep_volani_import_souhrn(PDO $pdo, string $path, string $sourceFile): int
{
    $xml = rep_volani_load_xml($path);
    $stmt = $pdo->prepare('
        INSERT INTO volani_souhrn
            (obdobi, mobil, produkt, polozka, sluzba, pocet, trvani, uctovano, objem, celkem_bez_dph, dph, celkem_s_dph, row_hash, source_file, imported_at)
        VALUES
            (:obdobi, :mobil, :produkt, :polozka, :sluzba, :pocet, :trvani, :uctovano, :objem, :celkem_bez_dph, :dph, :celkem_s_dph, :row_hash, :source_file, NOW())
        ON DUPLICATE KEY UPDATE
            source_file = VALUES(source_file),
            imported_at = NOW()
    ');

    $count = 0;
    foreach ($xml->TM as $row) {
        $data = [
            'obdobi' => rep_volani_xml_string($row, 'OBDOBI'),
            'mobil' => rep_volani_xml_string($row, 'MOBIL'),
            'produkt' => rep_volani_xml_string($row, 'PRODUKT'),
            'polozka' => rep_volani_xml_string($row, 'POLOZKA'),
            'sluzba' => rep_volani_xml_string($row, 'SLUZBA'),
            'pocet' => rep_volani_decimal(rep_volani_xml_string($row, 'POCET')),
            'trvani' => rep_volani_decimal(rep_volani_xml_string($row, 'TRVANI')),
            'uctovano' => rep_volani_decimal(rep_volani_xml_string($row, 'UCTOVANO')),
            'objem' => rep_volani_decimal(rep_volani_xml_string($row, 'OBJEM')),
            'celkem_bez_dph' => rep_volani_decimal(rep_volani_xml_string($row, 'CELKEM_BEZ_DPH')),
            'dph' => rep_volani_decimal(rep_volani_xml_string($row, 'DPH')),
            'celkem_s_dph' => rep_volani_decimal(rep_volani_xml_string($row, 'CELKEM_S_DPH')),
        ];

        if ($data['obdobi'] === '' || $data['mobil'] === '') {
            continue;
        }

        $data['row_hash'] = rep_volani_row_hash($data);
        $data['source_file'] = $sourceFile;
        $stmt->execute(array_combine(array_map(static fn (string $key): string => ':' . $key, array_keys($data)), array_values($data)));
        $count++;
    }

    return $count;
}

function rep_volani_import_detail(PDO $pdo, string $path, string $sourceFile): int
{
    $xml = rep_volani_load_xml($path);
    $stmt = $pdo->prepare('
        INSERT INTO volani_detail
            (obdobi, mobil, produkt, polozka, datumcas, smer, cislo, trvani, uctovano, objem, celkem_bez_dph, celkem_s_dph, row_hash, source_file, imported_at)
        VALUES
            (:obdobi, :mobil, :produkt, :polozka, :datumcas, :smer, :cislo, :trvani, :uctovano, :objem, :celkem_bez_dph, :celkem_s_dph, :row_hash, :source_file, NOW())
        ON DUPLICATE KEY UPDATE
            source_file = VALUES(source_file),
            imported_at = NOW()
    ');

    $count = 0;
    foreach ($xml->TM as $row) {
        $data = [
            'obdobi' => rep_volani_xml_string($row, 'OBDOBI'),
            'mobil' => rep_volani_xml_string($row, 'MOBIL'),
            'produkt' => rep_volani_xml_string($row, 'PRODUKT'),
            'polozka' => rep_volani_xml_string($row, 'POLOZKA'),
            'datumcas' => rep_volani_xml_string($row, 'DATUMCAS'),
            'smer' => rep_volani_xml_string($row, 'SMER'),
            'cislo' => rep_volani_xml_string($row, 'CISLO'),
            'trvani' => rep_volani_decimal(rep_volani_xml_string($row, 'TRVANI')),
            'uctovano' => rep_volani_decimal(rep_volani_xml_string($row, 'UCTOVANO')),
            'objem' => rep_volani_decimal(rep_volani_xml_string($row, 'OBJEM')),
            'celkem_bez_dph' => rep_volani_decimal(rep_volani_xml_string($row, 'CELKEM_BEZ_DPH')),
            'celkem_s_dph' => rep_volani_decimal(rep_volani_xml_string($row, 'CELKEM_S_DPH')),
        ];

        if ($data['obdobi'] === '' || $data['mobil'] === '') {
            continue;
        }

        $data['row_hash'] = rep_volani_row_hash($data);
        $data['source_file'] = $sourceFile;
        $stmt->execute(array_combine(array_map(static fn (string $key): string => ':' . $key, array_keys($data)), array_values($data)));
        $count++;
    }

    return $count;
}

function rep_volani_periods(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT obdobi, COUNT(*) AS total, SUM(celkem) AS total_amount
        FROM volani_preuctovani
        WHERE valid = 1
        GROUP BY obdobi
        ORDER BY obdobi DESC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volani_counts(PDO $pdo): array
{
    return [
        'preuctovani' => (int)$pdo->query('SELECT COUNT(*) FROM volani_preuctovani')->fetchColumn(),
        'souhrn' => (int)$pdo->query('SELECT COUNT(*) FROM volani_souhrn')->fetchColumn(),
        'detail' => (int)$pdo->query('SELECT COUNT(*) FROM volani_detail')->fetchColumn(),
    ];
}

function rep_volani_rows(PDO $pdo, string $period = '', string $email = '', string $mobil = ''): array
{
    $sql = 'SELECT * FROM volani_preuctovani WHERE valid = 1';
    $params = [];
    if ($period !== '') {
        $sql .= ' AND obdobi = :period';
        $params[':period'] = $period;
    }
    if ($email !== '') {
        $sql .= ' AND email LIKE :email';
        $params[':email'] = '%' . $email . '%';
    }
    if ($mobil !== '') {
        $sql .= ' AND mobil LIKE :mobil';
        $params[':mobil'] = '%' . $mobil . '%';
    }

    $sql .= ' ORDER BY obdobi DESC, email, mobil';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_volani_public_url(string $unify, int $type = 1): string
{
    return '/volani/index.php?typ=' . (int)$type . '&unify=' . rawurlencode($unify);
}

function rep_volani_public_email_url(string $email): string
{
    return '/volani/index.php?typ=3&identify=' . rawurlencode($email);
}
