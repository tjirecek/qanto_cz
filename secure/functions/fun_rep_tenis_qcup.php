<?php
declare(strict_types=1);

function rep_tenis_qcup_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rep_tenis_qcup_format_updated(mixed $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return function_exists('format_datetime_www') ? (string)format_datetime_www($value) : $value;
}

/** @param array<string, mixed> $row */
function rep_tenis_qcup_updated_cell(array $row): string
{
    $date = rep_tenis_qcup_format_updated($row['ts_u'] ?? '');
    $user = trim((string)($row['user_u'] ?? ''));

    if ($date === '') {
        return $user !== '' ? '<small class="text-muted">' . rep_tenis_qcup_e($user) . '</small>' : '';
    }

    return rep_tenis_qcup_e($date) . ($user !== '' ? '<br><small class="text-muted">' . rep_tenis_qcup_e($user) . '</small>' : '');
}

/**
 * @return array<int, int>
 */
function rep_tenis_qcup_parse_years(mixed $value): array
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
function rep_tenis_qcup_years(PDO $pdo, int $valid = 1): array
{
    $stmt = $pdo->prepare(
        'SELECT rok, COUNT(*) AS total
         FROM rep_tenis_qcup_registrace
         WHERE valid = :valid
         GROUP BY rok
         ORDER BY rok DESC'
    );
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array<int, int> $years
 * @return array<int, array<string, mixed>>
 */
function rep_tenis_qcup_count(PDO $pdo, int $valid): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rep_tenis_qcup_registrace WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

/**
 * @param array<int, int> $years
 * @return array<int, array<string, mixed>>
 */
function rep_tenis_qcup_rows(PDO $pdo, array $years = [], int $valid = 1): array
{
    $sql = 'SELECT *
            FROM rep_tenis_qcup_registrace
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

function rep_tenis_qcup_set_valid(PDO $pdo, int $id, int $valid): void
{
    if ($id <= 0) {
        throw new RuntimeException('Neplatné ID registrace.');
    }

    $stmt = $pdo->prepare(
        'UPDATE rep_tenis_qcup_registrace
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
function rep_tenis_qcup_export_url(array $years = [], bool $none = false, int $valid = 1): string
{
    $valid = $valid === 1 ? 1 : 0;

    if ($none) {
        return '/secure/functions/ajax/rep_tenis_qcup_export.php?none=1&valid=' . $valid;
    }

    $params = ['valid=' . $valid];
    foreach ($years as $year) {
        $params[] = 'years%5B%5D=' . rawurlencode((string)$year);
    }

    return '/secure/functions/ajax/rep_tenis_qcup_export.php' . ($params !== [] ? '?' . implode('&', $params) : '');
}

/**
 * @param array<int, int> $years
 */
function rep_tenis_qcup_page_url(array $years = []): string
{
    $params = [
        'section' => '02',
        'page' => '07',
        'sec_page' => '01',
    ];

    $query = http_build_query($params, '', '&amp;');
    foreach ($years as $year) {
        $query .= '&amp;years%5B%5D=' . rawurlencode((string)$year);
    }

    return 'index.php?' . $query;
}
