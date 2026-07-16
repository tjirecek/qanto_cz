<?php
declare(strict_types=1);

function rep_akce_users_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_akce_users_zero_date(): string
{
    return '0000-00-00';
}

function rep_akce_users_today(): string
{
    return date('Y-m-d');
}

function rep_akce_users_normalize_email(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function rep_akce_users_email_is_valid(string $email): bool
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

function rep_akce_users_validate_email(string $email): string
{
    $email = rep_akce_users_normalize_email($email);
    if ($email === '' || !rep_akce_users_email_is_valid($email)) {
        throw new InvalidArgumentException('E-mail není platný.');
    }

    return $email;
}

function rep_akce_users_normalize_date(mixed $value, string $fallback = '0000-00-00'): string
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
    if ($date === '' || $date === '0000-00-00') {
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

function rep_akce_users_format_date(mixed $value): string
{
    $date = (string)$value;
    if ($date === '' || $date === '0000-00-00') {
        return '';
    }

    return function_exists('format_date_www') ? (string)format_date_www($date) : date('d.m.Y', strtotime($date));
}

function rep_akce_users_format_updated(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value;
}

/** @param array<string, mixed> $row */
function rep_akce_users_updated_cell(array $row): string
{
    $date = rep_akce_users_format_updated($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_akce_users_e($user) . '</small>' : '';
    }

    return rep_akce_users_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_akce_users_e($user) . '</small>' : '');
}

function rep_akce_users_bool(mixed $value, int $default = 0): int
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

function rep_akce_users_prepare_write(PDO $pdo): void
{
    $pdo->exec("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', ''), 'NO_ZERO_DATE', '')");
}

/** @return array<int, array<string, mixed>> */
function rep_akce_users_types(PDO $pdo): array
{
    $rows = [[
        'id' => 0,
        'legacy_id' => 0,
        'nazev_cz' => 'Všechny akce',
        'akce_users_count' => 0,
    ]];

    $stmt = $pdo->query(
        'SELECT t.id, t.legacy_id, t.nazev_cz, COUNT(u.id) AS akce_users_count
         FROM rep_akce_typ t
         LEFT JOIN rep_akce_users u ON u.akce_typ_id = t.id AND u.valid = 1
         WHERE t.valid = 1
         GROUP BY t.id, t.legacy_id, t.nazev_cz
         ORDER BY t.poradi ASC, t.nazev_cz ASC, t.id ASC'
    );

    return array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function rep_akce_users_type_label(?int $typeId, mixed $legacyAkceTyp = null, string $typeName = ''): string
{
    if ($typeId === null || $typeId <= 0) {
        return 'Všechny akce';
    }

    $typeName = trim($typeName);
    return $typeName !== '' ? $typeName : 'Typ #' . $typeId;
}

function rep_akce_users_type_id_from_legacy(PDO $pdo, int $legacyAkceTyp): ?int
{
    if ($legacyAkceTyp <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM rep_akce_typ WHERE legacy_id = :legacy_id LIMIT 1');
    $stmt->execute([':legacy_id' => $legacyAkceTyp]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int)$id : null;
}

function rep_akce_users_legacy_from_type_id(PDO $pdo, ?int $typeId): int
{
    if ($typeId === null || $typeId <= 0) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT legacy_id FROM rep_akce_typ WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $typeId]);
    $legacyId = $stmt->fetchColumn();

    return $legacyId !== false && $legacyId !== null ? (int)$legacyId : 0;
}

function rep_akce_users_payload_type_id(PDO $pdo, array $data): ?int
{
    $typeId = (int)($data['akce_typ_id'] ?? ($data['typ_id'] ?? -1));
    if ($typeId >= 0) {
        if ($typeId === 0) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_akce_typ WHERE id = :id AND valid = 1');
        $stmt->execute([':id' => $typeId]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException('Vybraný typ akčních nabídek neexistuje nebo není validní.');
        }
        return $typeId;
    }

    $legacyAkceTyp = (int)($data['akce_typ'] ?? 0);
    return rep_akce_users_type_id_from_legacy($pdo, $legacyAkceTyp);
}

function rep_akce_users_get(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM rep_akce_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function rep_akce_users_find_by_email(PDO $pdo, string $email, ?int $typeId = null, ?int $excludeId = null): ?array
{
    $sql = 'SELECT * FROM rep_akce_users WHERE LOWER(TRIM(email)) = :email';
    $params = [':email' => rep_akce_users_normalize_email($email)];
    if ($typeId === null || $typeId <= 0) {
        $sql .= ' AND akce_typ_id IS NULL';
    } else {
        $sql .= ' AND akce_typ_id = :akce_typ_id';
        $params[':akce_typ_id'] = $typeId;
    }
    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }
    $sql .= ' ORDER BY valid DESC, registered DESC, datum_od DESC, id ASC LIMIT 1';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function rep_akce_users_count(PDO $pdo, ?int $valid = null, ?int $typeFilter = null): int
{
    $where = [];
    $params = [];
    if ($valid !== null) {
        $where[] = 'valid = :valid';
        $params[':valid'] = $valid;
    }
    if ($typeFilter !== null) {
        if ($typeFilter <= 0) {
            $where[] = 'akce_typ_id IS NULL';
        } else {
            $where[] = 'akce_typ_id = :type_filter';
            $params[':type_filter'] = $typeFilter;
        }
    }

    $sql = 'SELECT COUNT(*) FROM rep_akce_users' . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '');
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

/** @return array<int, array<string, mixed>> */
function rep_akce_users_rows(PDO $pdo, int $limit = 500, ?int $valid = 1, ?int $typeFilter = null): array
{
    $limit = $limit <= 0 ? 999999 : $limit;
    $where = [];
    $params = [];
    if ($valid !== null) {
        $where[] = 'u.valid = :valid';
        $params[':valid'] = $valid;
    }
    if ($typeFilter !== null) {
        if ($typeFilter <= 0) {
            $where[] = 'u.akce_typ_id IS NULL';
        } else {
            $where[] = 'u.akce_typ_id = :type_filter';
            $params[':type_filter'] = $typeFilter;
        }
    }

    $sql = 'SELECT u.*, t.nazev_cz AS typ_nazev_cz
            FROM rep_akce_users u
            LEFT JOIN rep_akce_typ t ON t.id = u.akce_typ_id'
        . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY u.datum_od DESC, u.id DESC LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_akce_users_payload_from_array(PDO $pdo, array $data): array
{
    $registered = rep_akce_users_bool($data['registered'] ?? null, 1);
    $valid = rep_akce_users_bool($data['valid'] ?? null, 1);
    $datumOd = rep_akce_users_normalize_date($data['datum_od'] ?? '', rep_akce_users_today());
    $datumDo = rep_akce_users_normalize_date($data['datum_do'] ?? '', rep_akce_users_zero_date());
    $typeId = rep_akce_users_payload_type_id($pdo, $data);

    if ($registered === 1) {
        $datumDo = rep_akce_users_zero_date();
    } elseif ($datumDo === rep_akce_users_zero_date()) {
        $datumDo = rep_akce_users_today();
    }

    return [
        'akce_typ_id' => $typeId,
        'legacy_akce_typ' => rep_akce_users_legacy_from_type_id($pdo, $typeId),
        'name' => trim((string)($data['name'] ?? '')),
        'email' => rep_akce_users_validate_email((string)($data['email'] ?? '')),
        'datum_od' => $datumOd,
        'datum_do' => $datumDo,
        'registered' => $registered,
        'valid' => $valid,
    ];
}

function rep_akce_users_save(PDO $pdo, array $data, ?int $id = null, bool $upsertByEmail = false): int
{
    rep_akce_users_prepare_write($pdo);
    $payload = rep_akce_users_payload_from_array($pdo, $data);
    $user = function_exists('admin_session_user') ? admin_session_user() : 'system';

    if ($id === null && $upsertByEmail) {
        $existing = rep_akce_users_find_by_email($pdo, $payload['email'], $payload['akce_typ_id']);
        if (is_array($existing)) {
            $id = (int)$existing['id'];
        }
    }

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO rep_akce_users
            (akce_typ_id, legacy_akce_typ, name, email, datum_od, datum_do, registered, valid, user_i, user_u)
            VALUES (:akce_typ_id, :legacy_akce_typ, :name, :email, :datum_od, :datum_do, :registered, :valid, :user_i, :user_u)');
        $stmt->execute([
            ':akce_typ_id' => $payload['akce_typ_id'],
            ':legacy_akce_typ' => $payload['legacy_akce_typ'],
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

    $stmt = $pdo->prepare('UPDATE rep_akce_users
        SET akce_typ_id = :akce_typ_id,
            legacy_akce_typ = :legacy_akce_typ,
            name = :name,
            email = :email,
            datum_od = :datum_od,
            datum_do = :datum_do,
            registered = :registered,
            valid = :valid,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':akce_typ_id' => $payload['akce_typ_id'],
        ':legacy_akce_typ' => $payload['legacy_akce_typ'],
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

function rep_akce_users_delete(PDO $pdo, int $id): void
{
    rep_akce_users_prepare_write($pdo);
    $stmt = $pdo->prepare('UPDATE rep_akce_users
        SET registered = 0,
            valid = 0,
            datum_do = IF(datum_do = "0000-00-00", :datum_do, datum_do),
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_do' => rep_akce_users_today(),
        ':user_u' => function_exists('admin_session_user') ? admin_session_user() : 'system',
    ]);
}

function rep_akce_users_set_valid(PDO $pdo, int $id, int $valid): void
{
    rep_akce_users_prepare_write($pdo);
    $stmt = $pdo->prepare('UPDATE rep_akce_users
        SET valid = :valid,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':valid' => $valid === 1 ? 1 : 0,
        ':user_u' => function_exists('admin_session_user') ? admin_session_user() : 'system',
    ]);
}

function rep_akce_users_end(PDO $pdo, int $id): void
{
    rep_akce_users_prepare_write($pdo);
    $stmt = $pdo->prepare('UPDATE rep_akce_users
        SET registered = 0,
            datum_do = :datum_do,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_do' => rep_akce_users_today(),
        ':user_u' => function_exists('admin_session_user') ? admin_session_user() : 'system',
    ]);
}

function rep_akce_users_renew(PDO $pdo, int $id): void
{
    rep_akce_users_prepare_write($pdo);
    $stmt = $pdo->prepare('UPDATE rep_akce_users
        SET registered = 1,
            valid = 1,
            datum_od = :datum_od,
            datum_do = "0000-00-00",
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':datum_od' => rep_akce_users_today(),
        ':user_u' => function_exists('admin_session_user') ? admin_session_user() : 'system',
    ]);
}

function rep_akce_users_registered_badge(int $registered): string
{
    return $registered === 1
        ? '<span class="badge text-bg-success">aktivní odběr</span>'
        : '<span class="badge text-bg-secondary">ukončeno</span>';
}

function rep_akce_users_valid_badge(int $valid): string
{
    return $valid === 1
        ? '<span class="badge text-bg-success">valid</span>'
        : '<span class="badge text-bg-danger">smazáno</span>';
}

function rep_akce_users_require_spreadsheet(): void
{
    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer vendor/autoload.php není dostupný. Spusť composer install.');
    }

    require_once $autoload;
}

function rep_akce_users_import_xlsx(PDO $pdo, string $tmpFile): array
{
    rep_akce_users_require_spreadsheet();

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

    $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    $fields = ['akce_typ_id', 'akce_typ', 'typ_id', 'name', 'email', 'datum_od', 'datum_do', 'registered', 'valid'];

    for ($row = 2; $row <= $highestRow; $row++) {
        $data = [];
        foreach ($fields as $field) {
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
            $payloadTypeId = rep_akce_users_payload_type_id($pdo, $data);
            $existing = rep_akce_users_find_by_email($pdo, $email, $payloadTypeId);
            rep_akce_users_save($pdo, $data, $existing ? (int)$existing['id'] : null, false);
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
