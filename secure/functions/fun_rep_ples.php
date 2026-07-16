<?php
declare(strict_types=1);

function rep_ples_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_ples_format_updated(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value;
}

/** @param array<string, mixed> $row */
function rep_ples_updated_cell(array $row): string
{
    $date = rep_ples_format_updated($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_ples_e($user) . '</small>' : '';
    }

    return rep_ples_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_ples_e($user) . '</small>' : '');
}

/**
 * @return array<int, int>
 */
function rep_ples_parse_years(mixed $value): array
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

/**
 * @return array<int, array<string, mixed>>
 */
function rep_ples_years(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT rok, COUNT(*) AS total, SUM(valid = 1) AS valid_count
         FROM rep_ples_registrace
         GROUP BY rok
         ORDER BY rok DESC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_ples_count(PDO $pdo, int $valid): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_ples_registrace WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

/**
 * @param array<int, int> $years
 * @return array<int, array<string, mixed>>
 */
function rep_ples_rows(PDO $pdo, array $years = [], int $valid = 1): array
{
    $sql = 'SELECT *
            FROM rep_ples_registrace
            WHERE valid = :valid';
    $params = [':valid' => $valid === 1 ? 1 : 0];

    if ($years !== []) {
        $placeholders = [];
        foreach (array_values($years) as $index => $year) {
            $key = ':year' . $index;
            $placeholders[] = $key;
            $params[$key] = $year;
        }
        $sql .= ' AND rok IN (' . implode(', ', $placeholders) . ')';
    }

    $sql .= ' ORDER BY rok DESC, datum DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rep_ples_set_valid(PDO $pdo, int $id, int $valid): void
{
    if ($id <= 0) {
        throw new RuntimeException('Neplatné ID registrace.');
    }

    $stmt = $pdo->prepare(
        'UPDATE rep_ples_registrace
         SET valid = :valid, user_u = :user_u
         WHERE id = :id'
    );
    $stmt->execute([
        ':valid' => $valid === 1 ? 1 : 0,
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

/**
 * @param array<int, int> $years
 */
function rep_ples_export_url(array $years = [], bool $none = false, int $valid = 1): string
{
    $valid = $valid === 1 ? 1 : 0;

    if ($none) {
        return '/secure/functions/ajax/rep_ples_export.php?none=1&valid=' . $valid;
    }

    $params = ['valid=' . $valid];
    foreach ($years as $year) {
        $params[] = 'years%5B%5D=' . rawurlencode((string)$year);
    }

    return '/secure/functions/ajax/rep_ples_export.php' . ($params !== [] ? '?' . implode('&', $params) : '');
}
