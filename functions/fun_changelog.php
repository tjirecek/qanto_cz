<?php
declare(strict_types=1);

function changelog_statuses(): array
{
    return [
        'zaevidovano' => ['label' => 'Zaevidováno', 'badge' => 'text-bg-secondary'],
        'naplanovano' => ['label' => 'Naplánováno', 'badge' => 'text-bg-info'],
        'probiha' => ['label' => 'Probíhá', 'badge' => 'text-bg-warning'],
        'nasazeno' => ['label' => 'Nasazeno', 'badge' => 'text-bg-success'],
    ];
}

function changelog_categories(): array
{
    return [
        'expedice' => 'Expedice',
        'oz' => 'OZ',
        'maloobchod' => 'Maloobchod',
        'centrala' => 'Centrála',
        'system' => 'Systém',
    ];
}

function changelog_table_exists(): bool
{
    global $pdo;

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'changelog'");
        return $stmt !== false && $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function changelog_current_user(): string
{
    if (function_exists('_qn_user')) {
        return (string)_qn_user();
    }

    return (string)($_SESSION['user_name'] ?? '');
}

function changelog_default(): array
{
    return [
        'id' => 0,
        'title' => '',
        'description' => '',
        'status' => 'zaevidovano',
        'category' => 'system',
        'priority' => 50,
        'recorded_on' => date('Y-m-d'),
        'planned_year' => '',
        'planned_month' => '',
        'done_on' => '',
        'active_l' => 1,
    ];
}

function changelog_from_request(array $src, ?array $base = null): array
{
    $data = $base ?? changelog_default();

    $data['title'] = trim((string)($src['title'] ?? $data['title']));
    $data['description'] = trim((string)($src['description'] ?? $data['description']));
    $data['status'] = trim((string)($src['status'] ?? $data['status']));
    $data['category'] = trim((string)($src['category'] ?? $data['category']));
    $data['priority'] = max(0, min(255, (int)($src['priority'] ?? $data['priority'])));
    $data['recorded_on'] = trim((string)($src['recorded_on'] ?? $data['recorded_on']));
    $data['planned_year'] = trim((string)($src['planned_year'] ?? $data['planned_year']));
    $data['planned_month'] = trim((string)($src['planned_month'] ?? $data['planned_month']));
    $data['done_on'] = trim((string)($src['done_on'] ?? $data['done_on']));
    $data['active_l'] = isset($src['active_l']) ? 1 : 0;

    return $data;
}

function changelog_normalize_date(?string $value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        return false;
    }

    return $value;
}

function changelog_validate(array $data): array
{
    $errors = [];
    $statuses = array_keys(changelog_statuses());
    $categories = array_keys(changelog_categories());

    if ($data['title'] === '') {
        $errors[] = 'Vyplň název změny.';
    }
    if (mb_strlen((string)$data['title']) > 180) {
        $errors[] = 'Název změny může mít maximálně 180 znaků.';
    }
    if (!in_array($data['status'], $statuses, true)) {
        $errors[] = 'Vyber platný stav.';
    }
    if (!in_array($data['category'], $categories, true)) {
        $errors[] = 'Vyber platnou kategorii.';
    }

    $recordedOn = changelog_normalize_date((string)$data['recorded_on']);
    if ($recordedOn === false) {
        $errors[] = 'Datum zaevidování není platné.';
    } else {
        $data['recorded_on'] = $recordedOn ?? date('Y-m-d');
    }

    $doneOn = changelog_normalize_date((string)$data['done_on']);
    if ($doneOn === false) {
        $errors[] = 'Datum hotovo není platné.';
    } else {
        $data['done_on'] = $doneOn ?? null;
    }

    $plannedYear = trim((string)$data['planned_year']);
    $plannedMonth = trim((string)$data['planned_month']);

    if ($plannedYear === '' && $plannedMonth === '') {
        $data['planned_year'] = null;
        $data['planned_month'] = null;
    } else {
        $year = (int)$plannedYear;
        $month = (int)$plannedMonth;
        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            $errors[] = 'Plánovaný termín musí obsahovat platný měsíc a rok.';
        } else {
            $data['planned_year'] = $year;
            $data['planned_month'] = $month;
        }
    }

    if ($data['status'] === 'nasazeno' && $data['done_on'] === null) {
        $data['done_on'] = date('Y-m-d');
    }

    return [$errors, $data];
}

function changelog_create(array $data): void
{
    global $pdo;

    $user = changelog_current_user();
    $stmt = $pdo->prepare(
        'INSERT INTO changelog (
            title, description, status, category, priority,
            recorded_on, planned_year, planned_month, done_on,
            active_l, created_by, updated_by
        ) VALUES (
            :title, :description, :status, :category, :priority,
            :recorded_on, :planned_year, :planned_month, :done_on,
            :active_l, :created_by, :updated_by
        )'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':description' => $data['description'] === '' ? null : $data['description'],
        ':status' => $data['status'],
        ':category' => $data['category'],
        ':priority' => (int)$data['priority'],
        ':recorded_on' => $data['recorded_on'],
        ':planned_year' => $data['planned_year'],
        ':planned_month' => $data['planned_month'],
        ':done_on' => $data['done_on'],
        ':active_l' => (int)$data['active_l'],
        ':created_by' => $user,
        ':updated_by' => $user,
    ]);
}

function changelog_update(int $id, array $data): void
{
    global $pdo;

    $user = changelog_current_user();
    $stmt = $pdo->prepare(
        'UPDATE changelog SET
            title = :title,
            description = :description,
            status = :status,
            category = :category,
            priority = :priority,
            recorded_on = :recorded_on,
            planned_year = :planned_year,
            planned_month = :planned_month,
            done_on = :done_on,
            active_l = :active_l,
            updated_by = :updated_by
         WHERE id = :id'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':description' => $data['description'] === '' ? null : $data['description'],
        ':status' => $data['status'],
        ':category' => $data['category'],
        ':priority' => (int)$data['priority'],
        ':recorded_on' => $data['recorded_on'],
        ':planned_year' => $data['planned_year'],
        ':planned_month' => $data['planned_month'],
        ':done_on' => $data['done_on'],
        ':active_l' => (int)$data['active_l'],
        ':updated_by' => $user,
        ':id' => $id,
    ]);
}

function changelog_archive(int $id): void
{
    global $pdo;

    $user = changelog_current_user();
    $stmt = $pdo->prepare('UPDATE changelog SET active_l = 0, updated_by = :updated_by WHERE id = :id');
    $stmt->execute([
        ':updated_by' => $user,
        ':id' => $id,
    ]);
}

function changelog_fetch(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM changelog WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function changelog_list(bool $includeInactive = false): array
{
    global $pdo;

    $where = $includeInactive ? '1=1' : 'active_l = 1';
    $stmt = $pdo->query(
        "SELECT *
         FROM changelog
         WHERE {$where}
         ORDER BY active_l DESC,
             status = 'nasazeno' ASC,
             FIELD(status, 'probiha', 'naplanovano', 'zaevidovano', 'nasazeno') ASC,
             priority ASC,
             recorded_on DESC,
             id DESC"
    );

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function changelog_dashboard_open(int $limit = 12): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM changelog
         WHERE active_l = 1
           AND status IN ('zaevidovano','naplanovano','probiha')
         ORDER BY FIELD(status, 'probiha', 'naplanovano', 'zaevidovano') ASC,
             priority ASC,
             recorded_on DESC,
             id DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function changelog_dashboard_done(int $limit = 12): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM changelog
         WHERE active_l = 1
           AND status = 'nasazeno'
         ORDER BY COALESCE(done_on, recorded_on) DESC, id DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function changelog_planned_text(array $row): string
{
    $year = (int)($row['planned_year'] ?? 0);
    $month = (int)($row['planned_month'] ?? 0);
    if ($year <= 0 || $month <= 0) {
        return 'bez termínu';
    }

    return str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . $year;
}

function changelog_status_label(string $status): string
{
    $statuses = changelog_statuses();
    return (string)($statuses[$status]['label'] ?? $status);
}

function changelog_status_badge(string $status): string
{
    $statuses = changelog_statuses();
    return (string)($statuses[$status]['badge'] ?? 'text-bg-secondary');
}

function changelog_category_label(string $category): string
{
    $categories = changelog_categories();
    return (string)($categories[$category] ?? $category);
}

function changelog_category_badge(string $category): string
{
    return match ($category) {
        'expedice' => 'text-bg-warning',
        'oz' => 'text-bg-success',
        'maloobchod' => 'text-bg-primary',
        'centrala' => 'text-bg-dark',
        'system' => 'text-bg-secondary',
        default => 'text-bg-light',
    };
}
