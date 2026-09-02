<?php
declare(strict_types=1);

function news_users_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function news_users_open_end_date(): ?string
{
    return null;
}

function news_users_today(): string
{
    return date('Y-m-d');
}

function news_users_normalize_email(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function news_users_normalize_date(mixed $value, ?string $fallback = null): ?string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_numeric($value) && (float)$value > 0) {
        try {
            return PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
        } catch (Throwable) {
            return $fallback;
        }
    }

    $date = trim((string)$value);
    if ($date === '') {
        return $fallback;
    }

    foreach (['Y-m-d', 'd.m.Y', 'j.n.Y', 'd/m/Y', 'j/n/Y'] as $format) {
        $dt = DateTimeImmutable::createFromFormat('!' . $format, $date);
        if ($dt instanceof DateTimeImmutable) {
            return $dt->format('Y-m-d');
        }
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? $fallback : date('Y-m-d', $timestamp);
}

function news_users_format_date(mixed $value): string
{
    $date = (string)$value;
    if ($date === '') {
        return '';
    }

    return function_exists('format_date_www') ? (string)format_date_www($date) : date('d.m.Y', strtotime($date));
}

function news_users_bool(mixed $value, int $default = 0): int
{
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }

    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    if ($value === '') {
        return $default;
    }

    return in_array($value, ['1', 'ano', 'yes', 'true', 'aktivni', 'aktivní'], true) ? 1 : 0;
}

function news_users_validate_email(string $email): string
{
    $email = news_users_normalize_email($email);
    if ($email === '' || !news_users_email_is_valid($email)) {
        throw new InvalidArgumentException('E-mail není platný.');
    }

    return $email;
}

function news_users_email_is_valid(string $email): bool
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }

    if (!function_exists('idn_to_ascii') || !str_contains($email, '@')) {
        return false;
    }

    [$local, $domain] = explode('@', $email, 2);
    if ($local === '' || $domain === '') {
        return false;
    }

    $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    return is_string($asciiDomain) && filter_var($local . '@' . $asciiDomain, FILTER_VALIDATE_EMAIL) !== false;
}

function news_users_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM news_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function news_users_find_by_email(string $email, ?int $excludeId = null): ?array
{
    global $pdo;

    $sql = 'SELECT * FROM news_users WHERE LOWER(TRIM(email)) = :email';
    $params = [':email' => news_users_normalize_email($email)];
    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }
    $sql .= ' ORDER BY valid DESC, registered DESC, id ASC LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function news_users_count(?int $valid = null): int
{
    global $pdo;

    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM news_users')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news_users WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function news_users_rows(int $limit = 500, ?int $valid = 1): array
{
    global $pdo;

    $limit = $limit <= 0 ? 999999 : $limit;
    $params = [];
    $where = '';
    if ($valid !== null) {
        $where = 'WHERE valid = :valid';
        $params[':valid'] = $valid;
    }

    $stmt = $pdo->prepare("SELECT * FROM news_users {$where} ORDER BY datum_od DESC, id DESC LIMIT :limit");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function news_users_payload_from_array(array $data): array
{
    $registered = news_users_bool($data['registered'] ?? null, 1);
    $valid = news_users_bool($data['valid'] ?? null, 1);
    $datumOd = news_users_normalize_date($data['datum_od'] ?? '', news_users_today());
    $datumDo = news_users_normalize_date($data['datum_do'] ?? '', news_users_open_end_date());

    if ($registered === 1) {
        $datumDo = news_users_open_end_date();
    } elseif ($datumDo === null) {
        $datumDo = news_users_today();
    }

    return [
        'name' => trim((string)($data['name'] ?? '')),
        'email' => news_users_validate_email((string)($data['email'] ?? '')),
        'datum_od' => $datumOd,
        'datum_do' => $datumDo,
        'registered' => $registered,
        'valid' => $valid,
    ];
}

function news_users_save(array $data, ?int $id = null, bool $upsertByEmail = false): int
{
    global $pdo;

    $payload = news_users_payload_from_array($data);
    $user = admin_session_user();

    if ($id === null && $upsertByEmail) {
        $existing = news_users_find_by_email($payload['email']);
        if (is_array($existing)) {
            $id = (int)$existing['id'];
        }
    }

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO news_users
            (name, email, datum_od, datum_do, registered, valid, user_i, user_u)
            VALUES (:name, :email, :datum_od, :datum_do, :registered, :valid, :user_i, :user_u)');
        $stmt->execute([
            ':name' => $payload['name'],
            ':email' => $payload['email'],
            ':datum_od' => $payload['datum_od'],
            ':datum_do' => $payload['datum_do'],
            ':registered' => $payload['registered'],
            ':valid' => $payload['valid'],
            ':user_i' => $user,
            ':user_u' => $user,
        ]);

        return (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('UPDATE news_users
        SET name = :name,
            email = :email,
            datum_od = :datum_od,
            datum_do = :datum_do,
            registered = :registered,
            valid = :valid,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $payload['name'],
        ':email' => $payload['email'],
        ':datum_od' => $payload['datum_od'],
        ':datum_do' => $payload['datum_do'],
        ':registered' => $payload['registered'],
        ':valid' => $payload['valid'],
        ':user_u' => $user,
    ]);

    return $id;
}

function news_users_delete(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE news_users
        SET registered = 0,
            valid = 0,
            datum_do = COALESCE(datum_do, :datum_do),
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_do' => news_users_today(),
        ':user_u' => admin_session_user(),
    ]);
}

function news_users_end(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE news_users
        SET registered = 0,
            datum_do = :datum_do,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_do' => news_users_today(),
        ':user_u' => admin_session_user(),
    ]);
}

function news_users_renew(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE news_users
        SET registered = 1,
            valid = 1,
            datum_od = :datum_od,
            datum_do = NULL,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_od' => news_users_today(),
        ':user_u' => admin_session_user(),
    ]);
}

function news_users_registered_badge(int $registered): string
{
    return $registered === 1
        ? '<span class="badge text-bg-success">aktivní odběr</span>'
        : '<span class="badge text-bg-secondary">ukončeno</span>';
}

function news_users_valid_badge(int $valid): string
{
    return $valid === 1
        ? '<span class="badge text-bg-success">valid</span>'
        : '<span class="badge text-bg-danger">smazáno</span>';
}

function news_users_require_spreadsheet(): void
{
    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer vendor/autoload.php není dostupný. Spusť composer install.');
    }

    require_once $autoload;
}

function news_users_import_xlsx(string $tmpFile): array
{
    news_users_require_spreadsheet();

    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

    $headers = [];
    for ($col = 1; $col <= $highestColumn; $col++) {
        $header = trim(mb_strtolower((string)$sheet->getCell([$col, 1])->getValue(), 'UTF-8'));
        if ($header !== '') {
            $headers[$header] = $col;
        }
    }

    if (!isset($headers['email'])) {
        throw new RuntimeException('Importní XLSX musí obsahovat sloupec email.');
    }

    $result = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    for ($row = 2; $row <= $highestRow; $row++) {
        $data = [];
        foreach (['name', 'email', 'datum_od', 'datum_do', 'registered', 'valid'] as $field) {
            if (isset($headers[$field])) {
                $data[$field] = $sheet->getCell([$headers[$field], $row])->getValue();
            }
        }

        $email = trim((string)($data['email'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        if ($email === '' && $name === '') {
            $result['skipped']++;
            continue;
        }

        try {
            $existing = news_users_find_by_email($email);
            news_users_save($data, $existing ? (int)$existing['id'] : null, false);
            if ($existing) {
                $result['updated']++;
            } else {
                $result['inserted']++;
            }
        } catch (Throwable $e) {
            $result['errors'][] = 'Řádek ' . $row . ': ' . $e->getMessage();
        }
    }

    return $result;
}
