<?php
declare(strict_types=1);

function rep_brigadnici_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_brigadnici_type(string $value): string
{
    return strtolower($value) === 'mo' ? 'mo' : 'vo';
}

function rep_brigadnici_type_label(string $type): string
{
    return rep_brigadnici_type($type) === 'mo' ? 'MO' : 'VO';
}

function rep_brigadnici_format_datetime(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '';
    }

    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i');
    } catch (Throwable) {
        return $value;
    }
}

/** @param array<string, mixed> $row */
function rep_brigadnici_updated_cell(array $row): string
{
    $date = rep_brigadnici_format_datetime($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_brigadnici_e($user) . '</small>' : '';
    }

    return rep_brigadnici_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_brigadnici_e($user) . '</small>' : '');
}

/** @return array<int, int> */
function rep_brigadnici_parse_years(mixed $value): array
{
    $values = is_array($value) ? $value : [$value];
    $years = [];
    foreach ($values as $year) {
        $year = (int)$year;
        if ($year >= 2000 && $year <= 2100) {
            $years[$year] = $year;
        }
    }
    rsort($years, SORT_NUMERIC);
    return array_values($years);
}

/** @return array<int, array<string, mixed>> */
function rep_brigadnici_years(PDO $pdo, string $type): array
{
    $stmt = $pdo->prepare(
        'SELECT rok, COUNT(*) AS total, SUM(valid = 1) AS valid_count
         FROM rep_brigadnici_registrace
         WHERE typ = :typ
         GROUP BY rok
         ORDER BY rok DESC'
    );
    $stmt->execute([':typ' => rep_brigadnici_type($type)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_brigadnici_count(PDO $pdo, string $type, int $valid): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_brigadnici_registrace WHERE typ = :typ AND valid = :valid');
    $stmt->execute([
        ':typ' => rep_brigadnici_type($type),
        ':valid' => $valid === 1 ? 1 : 0,
    ]);
    return (int)$stmt->fetchColumn();
}

/** @param array<int, int> $years @return array<int, array<string, mixed>> */
function rep_brigadnici_rows(PDO $pdo, string $type, array $years = [], int $valid = 1): array
{
    $sql = 'SELECT b.*, p.nazev_cz AS pobocka_nazev, p.typ AS pobocka_typ, p.stredisko AS pobocka_stredisko
            FROM rep_brigadnici_registrace b
            LEFT JOIN pobocky p ON p.id = b.pobocka_ref_id
            WHERE b.typ = :typ AND b.valid = :valid';
    $params = [
        ':typ' => rep_brigadnici_type($type),
        ':valid' => $valid === 1 ? 1 : 0,
    ];

    if ($years !== []) {
        $placeholders = [];
        foreach (array_values($years) as $index => $year) {
            $key = ':year' . $index;
            $placeholders[] = $key;
            $params[$key] = $year;
        }
        $sql .= ' AND b.rok IN (' . implode(', ', $placeholders) . ')';
    }

    $sql .= ' ORDER BY b.rok DESC, b.reg_date DESC, b.id DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $paramType = in_array($key, [':typ'], true) ? PDO::PARAM_STR : PDO::PARAM_INT;
        $stmt->bindValue($key, $value, $paramType);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_brigadnici_set_valid(PDO $pdo, string $type, int $id, int $valid): void
{
    if ($id <= 0) {
        throw new RuntimeException('Neplatné ID registrace.');
    }
    $stmt = $pdo->prepare(
        'UPDATE rep_brigadnici_registrace
         SET valid = :valid, user_u = :user_u
         WHERE id = :id AND typ = :typ'
    );
    $stmt->execute([
        ':valid' => $valid === 1 ? 1 : 0,
        ':user_u' => admin_session_user(),
        ':id' => $id,
        ':typ' => rep_brigadnici_type($type),
    ]);
}

/** @param array<int, int> $years */
function rep_brigadnici_export_url(string $type, array $years = [], bool $none = false, int $valid = 1): string
{
    $valid = $valid === 1 ? 1 : 0;
    $type = rep_brigadnici_type($type);
    if ($none) {
        return '/secure/functions/ajax/rep_brigadnici_export.php?type=' . rawurlencode($type) . '&none=1&valid=' . $valid;
    }
    $params = ['type=' . rawurlencode($type), 'valid=' . $valid];
    foreach ($years as $year) {
        $params[] = 'years%5B%5D=' . rawurlencode((string)$year);
    }
    return '/secure/functions/ajax/rep_brigadnici_export.php?' . implode('&', $params);
}
