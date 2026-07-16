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

function changelog_category_badge_options(): array
{
    return [
        'text-bg-secondary' => 'Šedá',
        'text-bg-primary' => 'Modrá',
        'text-bg-success' => 'Zelená',
        'text-bg-danger' => 'Červená',
        'text-bg-warning' => 'Žlutá',
        'text-bg-info' => 'Tyrkysová',
        'text-bg-light' => 'Světlá',
        'text-bg-dark' => 'Tmavá',
    ];
}

function changelog_category_fallback_rows(): array
{
    return [
        ['id' => 0, 'code' => 'expedice', 'name' => 'Expedice', 'badge_class' => 'text-bg-warning', 'sort_order' => 10, 'active_l' => 1],
        ['id' => 0, 'code' => 'oz', 'name' => 'OZ', 'badge_class' => 'text-bg-success', 'sort_order' => 20, 'active_l' => 1],
        ['id' => 0, 'code' => 'maloobchod', 'name' => 'Maloobchod', 'badge_class' => 'text-bg-primary', 'sort_order' => 30, 'active_l' => 1],
        ['id' => 0, 'code' => 'centrala', 'name' => 'Centrála', 'badge_class' => 'text-bg-dark', 'sort_order' => 40, 'active_l' => 1],
        ['id' => 0, 'code' => 'system', 'name' => 'Systém', 'badge_class' => 'text-bg-secondary', 'sort_order' => 50, 'active_l' => 1],
    ];
}

function changelog_table_exists(): bool
{
    return changelog_db_table_exists('changelog');
}

function changelog_category_table_exists(): bool
{
    return changelog_db_table_exists('changelog_cat');
}

function changelog_db_table_exists(string $table): bool
{
    global $pdo;

    $cacheKey = strtolower($table);
    static $cache = [];
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table'
        );
        $stmt->execute([':table' => $table]);
        $cache[$cacheKey] = (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function changelog_db_column_exists(string $table, string $column): bool
{
    global $pdo;

    $cacheKey = strtolower($table . '.' . $column);
    static $cache = [];
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);
        $cache[$cacheKey] = (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function changelog_current_user(): string
{
    if (function_exists('_qn_user')) {
        return (string)_qn_user();
    }

    return (string)($_SESSION['user_name'] ?? '');
}

function changelog_safe_badge_class(string $badgeClass): string
{
    $badgeClass = trim($badgeClass);
    if (isset(changelog_category_badge_options()[$badgeClass])) {
        return $badgeClass;
    }

    return 'text-bg-secondary';
}

function changelog_category_rows(bool $activeOnly = true): array
{
    global $pdo;

    if (!changelog_category_table_exists()) {
        return array_values(array_filter(
            changelog_category_fallback_rows(),
            static fn (array $row): bool => !$activeOnly || (int)$row['active_l'] === 1
        ));
    }

    try {
        $where = $activeOnly ? 'WHERE active_l = 1' : '';
        $stmt = $pdo->query(
            "SELECT id, code, name, badge_class, sort_order, active_l
             FROM changelog_cat
             {$where}
             ORDER BY active_l DESC, sort_order ASC, name ASC, id ASC"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($rows !== []) {
            foreach ($rows as &$row) {
                $row['badge_class'] = changelog_safe_badge_class((string)($row['badge_class'] ?? ''));
            }
            unset($row);
            return $rows;
        }
    } catch (Throwable $e) {
        return changelog_category_fallback_rows();
    }

    return changelog_category_fallback_rows();
}

function changelog_categories(): array
{
    $categories = [];
    foreach (changelog_category_rows(true) as $row) {
        $code = (string)($row['code'] ?? '');
        if ($code !== '') {
            $categories[$code] = (string)($row['name'] ?? $code);
        }
    }

    return $categories;
}

function changelog_category_meta(string $category): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (changelog_category_rows(false) as $row) {
            $code = (string)($row['code'] ?? '');
            if ($code !== '') {
                $cache[$code] = [
                    'label' => (string)($row['name'] ?? $code),
                    'badge' => changelog_safe_badge_class((string)($row['badge_class'] ?? '')),
                ];
            }
        }
    }

    return $cache[$category] ?? [
        'label' => $category,
        'badge' => 'text-bg-secondary',
    ];
}

function changelog_default_category_code(): string
{
    $categories = changelog_categories();
    if (isset($categories['system'])) {
        return 'system';
    }

    $first = array_key_first($categories);
    return is_string($first) && $first !== '' ? $first : 'system';
}

function changelog_category_default(): array
{
    return [
        'id' => 0,
        'code' => '',
        'name' => '',
        'badge_class' => 'text-bg-secondary',
        'sort_order' => 50,
        'active_l' => 1,
    ];
}

function changelog_category_from_request(array $src, ?array $base = null): array
{
    $data = $base ?? changelog_category_default();

    $data['code'] = strtolower(trim((string)($src['code'] ?? $data['code'])));
    $data['name'] = trim((string)($src['name'] ?? $data['name']));
    $data['badge_class'] = trim((string)($src['badge_class'] ?? $data['badge_class']));
    $data['sort_order'] = max(0, min(999, (int)($src['sort_order'] ?? $data['sort_order'])));
    $data['active_l'] = isset($src['active_l']) ? 1 : 0;

    return $data;
}

function changelog_category_code_exists(string $code, int $exceptId = 0): bool
{
    global $pdo;

    if (!changelog_category_table_exists()) {
        return array_key_exists($code, changelog_categories());
    }

    $sql = 'SELECT id FROM changelog_cat WHERE code = :code';
    $params = [':code' => $code];
    if ($exceptId > 0) {
        $sql .= ' AND id <> :id';
        $params[':id'] = $exceptId;
    }
    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() !== false;
}

function changelog_category_validate(array $data, int $id = 0): array
{
    $errors = [];

    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', (string)$data['code'])) {
        $errors[] = 'Kód kategorie smí obsahovat jen malá písmena bez diakritiky, čísla, pomlčku a podtržítko.';
    } elseif (changelog_category_code_exists((string)$data['code'], $id)) {
        $errors[] = 'Kategorie s tímto kódem už existuje.';
    }

    if ((string)$data['name'] === '') {
        $errors[] = 'Vyplň název kategorie.';
    }
    if (mb_strlen((string)$data['name']) > 120) {
        $errors[] = 'Název kategorie může mít maximálně 120 znaků.';
    }
    if (!isset(changelog_category_badge_options()[(string)$data['badge_class']])) {
        $errors[] = 'Vyber platnou barvu kategorie.';
    }

    return [$errors, $data];
}

function changelog_category_fetch(int $id): ?array
{
    global $pdo;

    if ($id <= 0 || !changelog_category_table_exists()) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM changelog_cat WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function changelog_category_create(array $data): void
{
    global $pdo;

    if (!changelog_category_table_exists()) {
        throw new RuntimeException('Tabulka changelog_cat zatím neexistuje.');
    }

    $user = changelog_current_user();
    $stmt = $pdo->prepare(
        'INSERT INTO changelog_cat (code, name, badge_class, sort_order, active_l, created_by, updated_by)
         VALUES (:code, :name, :badge_class, :sort_order, :active_l, :created_by, :updated_by)'
    );
    $stmt->execute([
        ':code' => $data['code'],
        ':name' => $data['name'],
        ':badge_class' => $data['badge_class'],
        ':sort_order' => (int)$data['sort_order'],
        ':active_l' => (int)$data['active_l'],
        ':created_by' => $user,
        ':updated_by' => $user,
    ]);
}

function changelog_category_update(int $id, array $data): void
{
    global $pdo;

    if (!changelog_category_table_exists()) {
        throw new RuntimeException('Tabulka changelog_cat zatím neexistuje.');
    }

    $stmt = $pdo->prepare(
        'UPDATE changelog_cat SET
            code = :code,
            name = :name,
            badge_class = :badge_class,
            sort_order = :sort_order,
            active_l = :active_l,
            updated_by = :updated_by
         WHERE id = :id'
    );
    $stmt->execute([
        ':code' => $data['code'],
        ':name' => $data['name'],
        ':badge_class' => $data['badge_class'],
        ':sort_order' => (int)$data['sort_order'],
        ':active_l' => (int)$data['active_l'],
        ':updated_by' => changelog_current_user(),
        ':id' => $id,
    ]);
}

function changelog_category_archive(int $id): void
{
    global $pdo;

    if (!changelog_category_table_exists()) {
        throw new RuntimeException('Tabulka changelog_cat zatím neexistuje.');
    }

    $stmt = $pdo->prepare('UPDATE changelog_cat SET active_l = 0, updated_by = :updated_by WHERE id = :id');
    $stmt->execute([
        ':updated_by' => changelog_current_user(),
        ':id' => $id,
    ]);
}

function changelog_news_link_available(): bool
{
    return changelog_table_exists() && changelog_db_column_exists('changelog', 'news_id');
}

function changelog_news_table_available(): bool
{
    return changelog_db_table_exists('news');
}

function changelog_news_exists(int $newsId): bool
{
    global $pdo;

    if ($newsId <= 0 || !changelog_news_table_available()) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM news WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $newsId]);
        return $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function changelog_news_select_part(string $column, string $alias): string
{
    if (changelog_db_column_exists('news', $column)) {
        return 'n.' . $column . ' AS ' . $alias;
    }

    return 'NULL AS ' . $alias;
}

function changelog_select_sql(string $where, string $orderAndLimit = ''): string
{
    $select = 'c.*';
    $join = '';

    if (changelog_news_link_available() && changelog_news_table_available()) {
        $select .= ', n.id AS changelog_news_found_id';
        $select .= ', ' . changelog_news_select_part('nazev_cz', 'changelog_news_title');
        $select .= ', ' . changelog_news_select_part('perex_cz', 'changelog_news_perex');
        $select .= ', ' . changelog_news_select_part('text_cz', 'changelog_news_text');
        $select .= ', ' . changelog_news_select_part('url_cz', 'changelog_news_url');
        $select .= ', ' . changelog_news_select_part('datum', 'changelog_news_date');
        $select .= ', ' . changelog_news_select_part('visible', 'changelog_news_visible');
        $select .= ', ' . changelog_news_select_part('valid', 'changelog_news_valid');
        $join = ' LEFT JOIN news n ON n.id = c.news_id';
    }

    return 'SELECT ' . $select . ' FROM changelog c' . $join . ' WHERE ' . $where . ' ' . $orderAndLimit;
}

function changelog_default(): array
{
    return [
        'id' => 0,
        'title' => '',
        'description' => '',
        'status' => 'zaevidovano',
        'category' => changelog_default_category_code(),
        'news_id' => '',
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
    $data['news_id'] = trim((string)($src['news_id'] ?? $data['news_id']));
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

    $newsIdRaw = trim((string)($data['news_id'] ?? ''));
    if ($newsIdRaw === '') {
        $data['news_id'] = null;
    } elseif (!ctype_digit($newsIdRaw) || (int)$newsIdRaw <= 0) {
        $errors[] = 'ID novinky musí být kladné celé číslo.';
        $data['news_id'] = null;
    } elseif (!changelog_news_link_available()) {
        $errors[] = 'Vazba na novinku není dostupná. Nejdříve spusť migraci pro sloupec changelog.news_id.';
        $data['news_id'] = (int)$newsIdRaw;
    } elseif (!changelog_news_exists((int)$newsIdRaw)) {
        $errors[] = 'Novinka s tímto ID neexistuje.';
        $data['news_id'] = (int)$newsIdRaw;
    } else {
        $data['news_id'] = (int)$newsIdRaw;
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
    $hasNewsId = changelog_news_link_available();
    $stmt = $pdo->prepare(
        'INSERT INTO changelog (
            title, description, status, category, priority,
            ' . ($hasNewsId ? 'news_id, ' : '') . 'recorded_on, planned_year, planned_month, done_on,
            active_l, created_by, updated_by
        ) VALUES (
            :title, :description, :status, :category, :priority,
            ' . ($hasNewsId ? ':news_id, ' : '') . ':recorded_on, :planned_year, :planned_month, :done_on,
            :active_l, :created_by, :updated_by
        )'
    );
    $params = [
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
    ];
    if ($hasNewsId) {
        $params[':news_id'] = $data['news_id'] === '' ? null : $data['news_id'];
    }

    $stmt->execute($params);
}

function changelog_update(int $id, array $data): void
{
    global $pdo;

    $user = changelog_current_user();
    $hasNewsId = changelog_news_link_available();
    $stmt = $pdo->prepare(
        'UPDATE changelog SET
            title = :title,
            description = :description,
            status = :status,
            category = :category,
            priority = :priority,
            ' . ($hasNewsId ? 'news_id = :news_id,' : '') . '
            recorded_on = :recorded_on,
            planned_year = :planned_year,
            planned_month = :planned_month,
            done_on = :done_on,
            active_l = :active_l,
            updated_by = :updated_by
         WHERE id = :id'
    );
    $params = [
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
    ];
    if ($hasNewsId) {
        $params[':news_id'] = $data['news_id'] === '' ? null : $data['news_id'];
    }

    $stmt->execute($params);
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

    $stmt = $pdo->prepare(changelog_select_sql('c.id = :id', 'LIMIT 1'));
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function changelog_fetch_active(int $id): ?array
{
    global $pdo;

    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(changelog_select_sql('c.id = :id AND c.active_l = 1', 'LIMIT 1'));
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function changelog_fetch_for_news(int $newsId, bool $activeOnly = true): ?array
{
    global $pdo;

    if ($newsId <= 0 || !changelog_news_link_available()) {
        return null;
    }

    $where = 'c.news_id = :news_id';
    if ($activeOnly) {
        $where .= ' AND c.active_l = 1';
    }

    $stmt = $pdo->prepare(
        changelog_select_sql(
            $where,
            "ORDER BY c.status = 'nasazeno' DESC,
             COALESCE(c.done_on, c.recorded_on) DESC,
             c.id DESC
             LIMIT 1"
        )
    );
    $stmt->execute([':news_id' => $newsId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function changelog_list(bool $includeInactive = false): array
{
    global $pdo;

    $where = $includeInactive ? '1=1' : 'c.active_l = 1';
    $stmt = $pdo->query(
        changelog_select_sql(
            $where,
            "ORDER BY c.active_l DESC,
             c.status = 'nasazeno' ASC,
             FIELD(c.status, 'probiha', 'naplanovano', 'zaevidovano', 'nasazeno') ASC,
             c.priority ASC,
             c.recorded_on DESC,
             c.id DESC"
        )
    );

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function changelog_dashboard_open(int $limit = 12): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        changelog_select_sql(
            "c.active_l = 1
           AND c.status IN ('zaevidovano','naplanovano','probiha')",
            "ORDER BY FIELD(c.status, 'probiha', 'naplanovano', 'zaevidovano') ASC,
             c.priority ASC,
             c.recorded_on DESC,
             c.id DESC
         LIMIT :limit"
        )
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function changelog_dashboard_done(int $limit = 12): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        changelog_select_sql(
            "c.active_l = 1
           AND c.status = 'nasazeno'",
            'ORDER BY COALESCE(c.done_on, c.recorded_on) DESC, c.id DESC
         LIMIT :limit'
        )
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function changelog_news_admin_url(int $newsId): string
{
    return 'index.php?section=01&amp;page=01&amp;sec_page=02&amp;edit=' . $newsId . '&amp;show=2';
}

function changelog_admin_detail_url(int $id): string
{
    return 'index.php?section=09&amp;page=02&amp;sec_page=07&amp;show=4&amp;detail=' . $id;
}

function changelog_admin_list_url(): string
{
    return 'index.php?section=09&amp;page=02&amp;sec_page=07';
}

function changelog_frontend_detail_url(int $id, string $lang = 'cz'): string
{
    $lang = in_array($lang, ['cz', 'en', 'sk'], true) ? $lang : 'cz';
    return '/' . $lang . '/changelog/' . $id;
}

function changelog_news_preview_text(array $row, int $limit = 180): string
{
    $text = trim(strip_tags((string)($row['changelog_news_perex'] ?? '')));
    if ($text === '') {
        $text = trim(strip_tags((string)($row['changelog_news_text'] ?? '')));
    }

    if ($text === '') {
        return '';
    }

    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...';
}

function changelog_linked_news_body_html(array $row): string
{
    return trim((string)($row['changelog_news_text'] ?? ''));
}

function changelog_linked_news_perex_html(array $row): string
{
    return trim((string)($row['changelog_news_perex'] ?? ''));
}

function changelog_linked_news_body_text(array $row): string
{
    return changelog_html_to_plain_text(changelog_linked_news_body_html($row));
}

function changelog_linked_news_perex_text(array $row): string
{
    return changelog_html_to_plain_text(changelog_linked_news_perex_html($row));
}

function changelog_html_to_plain_text(string $html): string
{
    $text = trim($html);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $text) ?? $text;
    $text = preg_replace('~</\s*(p|div|li|h[1-6]|tr)\s*>~i', "\n", $text) ?? $text;
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("~[ \t]+\n~", "\n", $text) ?? $text;
    $text = preg_replace("~\n{3,}~", "\n\n", $text) ?? $text;

    return trim($text);
}

function changelog_has_linked_news(array $row): bool
{
    return (int)($row['news_id'] ?? 0) > 0 && (int)($row['changelog_news_found_id'] ?? 0) > 0;
}

function changelog_linked_news_title(array $row): string
{
    $title = trim((string)($row['changelog_news_title'] ?? ''));
    if ($title !== '') {
        return $title;
    }

    $newsId = (int)($row['news_id'] ?? 0);
    return $newsId > 0 ? 'Novinka #' . $newsId : '';
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
    return (string)changelog_category_meta($category)['label'];
}

function changelog_category_badge(string $category): string
{
    return (string)changelog_category_meta($category)['badge'];
}

function changelog_config(): array
{
    return function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
}

function changelog_config_string(string $key, string $default = ''): string
{
    $config = changelog_config();
    $value = trim((string)($config[$key] ?? ''));

    return $value !== '' ? $value : $default;
}

function changelog_request_base_url(): string
{
    $configured = changelog_config_string('changelog_public_base_url');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host !== '') {
        $https = function_exists('app_is_https') ? app_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        return ($https ? 'https://' : 'http://') . $host;
    }

    $newsletterBase = changelog_config_string('newsletter_public_base_url');
    return $newsletterBase !== '' ? rtrim($newsletterBase, '/') : '';
}

function changelog_absolute_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || preg_match('~^(https?://|mailto:|tel:|#|data:)~i', $url) === 1) {
        return $url;
    }

    $baseUrl = changelog_request_base_url();
    if ($baseUrl === '') {
        return $url;
    }

    return $baseUrl . '/' . ltrim($url, '/');
}

function changelog_absolute_html_urls(string $html): string
{
    $html = preg_replace_callback('~\b(src|href)=(["\'])(/[^"\']*)\2~i', static function (array $match): string {
        return $match[1] . '=' . $match[2] . changelog_absolute_url($match[3]) . $match[2];
    }, $html) ?? $html;

    return preg_replace_callback('~\b(src|href)=(["\'])(?!https?://|mailto:|tel:|#|data:)([^"\']+)\2~i', static function (array $match): string {
        return $match[1] . '=' . $match[2] . changelog_absolute_url($match[3]) . $match[2];
    }, $html) ?? $html;
}

function changelog_prepare_email_content_html(string $html): string
{
    $html = changelog_absolute_html_urls($html);

    return preg_replace_callback('~<img\b([^>]*)>~i', static function (array $match): string {
        $attributes = $match[1];
        $responsiveStyle = 'max-width:100%;height:auto;border:0;display:block;margin:18px auto;';

        if (preg_match('~\sstyle=(["\'])(.*?)\1~i', $attributes, $styleMatch) === 1) {
            $style = rtrim(trim($styleMatch[2]), ';');
            $style .= ($style !== '' ? ';' : '') . $responsiveStyle;
            $attributes = preg_replace('~\sstyle=(["\'])(.*?)\1~i', ' style="' . $style . '"', $attributes, 1) ?? $attributes;
        } else {
            $attributes .= ' style="' . $responsiveStyle . '"';
        }

        return '<img' . $attributes . '>';
    }, $html) ?? $html;
}

function changelog_brand_name(): string
{
    return changelog_config_string('newsletter_brand_name', 'Qanto');
}

function changelog_logo_url(): string
{
    return changelog_absolute_url(changelog_config_string('newsletter_logo_url', '/img/design/logo_admin_login.png'));
}

function changelog_logo_file_path(): string
{
    $configured = changelog_config_string('newsletter_logo_url', '/img/design/logo_admin_login.png');
    $path = parse_url($configured, PHP_URL_PATH);
    $candidates = [];

    if (is_string($path) && trim($path) !== '') {
        $candidates[] = $path;
    }
    if ($configured !== '') {
        $candidates[] = $configured;
    }
    $candidates[] = '/img/design/logo_admin_login.png';

    foreach (array_unique($candidates) as $candidate) {
        $candidate = trim(str_replace('\\', '/', (string)$candidate));
        if ($candidate === '' || preg_match('~^(https?://|data:)~i', $candidate) === 1) {
            continue;
        }

        $filePath = $candidate[0] === '/'
            ? ROOT_DIR . $candidate
            : ROOT_DIR . '/' . ltrim($candidate, '/');
        $realPath = realpath($filePath);
        if (is_string($realPath) && is_file($realPath)) {
            return $realPath;
        }
    }

    return '';
}

function changelog_image_mime_type(string $filePath): string
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $types = [
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    return $types[$extension] ?? 'application/octet-stream';
}

function changelog_logo_embedded_image(string $cid): array
{
    $filePath = changelog_logo_file_path();
    if ($filePath === '') {
        return [];
    }

    return [
        'path' => $filePath,
        'cid' => $cid,
        'name' => basename($filePath),
        'type' => changelog_image_mime_type($filePath),
    ];
}

function changelog_logo_data_uri(): string
{
    $filePath = changelog_logo_file_path();
    if ($filePath === '') {
        return '';
    }

    $data = @file_get_contents($filePath);
    if (!is_string($data) || $data === '') {
        return '';
    }

    return 'data:' . changelog_image_mime_type($filePath) . ';base64,' . base64_encode($data);
}

function changelog_logo_preview_src(): string
{
    $dataUri = changelog_logo_data_uri();

    return $dataUri !== '' ? $dataUri : changelog_logo_url();
}

function changelog_accent_color(): string
{
    $color = changelog_config_string('newsletter_accent_color', '#e30613');

    return preg_match('~^#[0-9a-f]{6}$~i', $color) === 1 ? $color : '#e30613';
}

function changelog_email_subject(array $row): string
{
    $title = trim((string)($row['title'] ?? ''));

    return 'ChangeLog' . ($title !== '' ? ' :: ' . $title : '');
}

function changelog_email_body_html(array $row, ?string $logoSrc = null): string
{
    $title = trim((string)($row['title'] ?? ''));
    $description = trim((string)($row['description'] ?? ''));
    $category = (string)($row['category'] ?? '');
    $doneOn = trim((string)($row['done_on'] ?? ''));
    $brandName = changelog_brand_name();
    $logoUrl = trim((string)$logoSrc) !== '' ? trim((string)$logoSrc) : changelog_logo_url();
    $accentColor = changelog_accent_color();
    $changeUrl = changelog_absolute_url(changelog_frontend_detail_url((int)($row['id'] ?? 0), 'cz'));
    $descriptionHtml = $description !== ''
        ? nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'))
        : '<span style="color:#64748b;">Popis změny není vyplněný.</span>';

    $newsHtml = '';
    if (changelog_has_linked_news($row)) {
        $newsTitle = changelog_linked_news_title($row);
        $newsPerex = changelog_prepare_email_content_html(changelog_linked_news_perex_html($row));
        $newsBody = changelog_prepare_email_content_html(changelog_linked_news_body_html($row));
        $newsContent = trim($newsPerex . $newsBody);
        if ($newsContent === '') {
            $newsContent = '<p style="margin:0;color:#64748b;">Perex ani tělo navázané novinky nejsou vyplněné.</p>';
        }

        $newsHtml = '
          <tr>
            <td style="padding:0 38px 38px 38px;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;">
                <tr>
                  <td style="padding:22px 24px;font-size:16px;line-height:1.68;color:#26323f;">
                    <div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:8px;">Navázaná novinka</div>
                    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;color:#17212f;">' . htmlspecialchars($newsTitle, ENT_QUOTES, 'UTF-8') . '</h2>
                    ' . $newsContent . '
                  </td>
                </tr>
              </table>
            </td>
          </tr>';
    }

    $doneText = $doneOn !== '' ? format_date_www($doneOn) : '';
    $year = date('Y');

    return '<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title !== '' ? $title : 'ChangeLog', ENT_QUOTES, 'UTF-8') . '</title>
</head>
<body style="margin:0;padding:0;background:#eef1f4;color:#26323f;font-family:Arial,Helvetica,sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;color:#eef1f4;font-size:1px;line-height:1px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>
  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef1f4;margin:0;padding:28px 0;">
    <tr>
      <td align="center">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="720" style="width:720px;max-width:100%;background:#ffffff;border-collapse:collapse;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);">
          <tr>
            <td style="padding:28px 34px 22px 34px;background:#ffffff;border-top:8px solid ' . htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8') . ';">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" width="214" alt="' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:214px;max-width:70%;height:auto;border:0;">
                  </td>
                  <td align="right" style="vertical-align:middle;color:#6b7280;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                    ChangeLog
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#17212f;color:#ffffff;padding:34px 38px 36px 38px;">
              <div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">Nasazená změna</div>
              <h1 style="margin:10px 0 0 0;font-size:32px;line-height:1.22;font-weight:800;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:30px 38px 14px 38px;font-size:15px;line-height:1.55;color:#475569;">
              <span style="display:inline-block;background:#e2e8f0;border-radius:999px;padding:5px 11px;margin:0 8px 8px 0;">' . htmlspecialchars(changelog_category_label($category), ENT_QUOTES, 'UTF-8') . '</span>
              <span style="display:inline-block;background:#dcfce7;color:#166534;border-radius:999px;padding:5px 11px;margin:0 8px 8px 0;">Nasazeno' . ($doneText !== '' ? ' ' . htmlspecialchars($doneText, ENT_QUOTES, 'UTF-8') : '') . '</span>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 38px 34px 38px;font-size:16px;line-height:1.68;color:#26323f;">
              ' . $descriptionHtml . '
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0 0 0;"><tr><td bgcolor="' . htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8') . '" style="border-radius:999px;"><a href="' . htmlspecialchars($changeUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Zobrazit detail změny</a></td></tr></table>
            </td>
          </tr>
          ' . $newsHtml . '
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:24px 38px;font-size:13px;line-height:1.55;color:#64748b;">
              <p style="margin:0 0 8px 0;">Tento e-mail dostáváte jako uživatel vybrané skupiny aplikace ' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . '.</p>
              <p style="margin:0;">&copy; ' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . ' :: Astur &amp; Qanto s.r.o. ' . $year . '</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function changelog_email_body_text(array $row): string
{
    $parts = [];
    $parts[] = 'ChangeLog: ' . trim((string)($row['title'] ?? ''));
    $parts[] = 'Kategorie: ' . changelog_category_label((string)($row['category'] ?? ''));
    $parts[] = 'Stav: ' . changelog_status_label((string)($row['status'] ?? ''));
    if (trim((string)($row['done_on'] ?? '')) !== '') {
        $parts[] = 'Nasazeno: ' . format_date_www((string)$row['done_on']);
    }
    $parts[] = '';
    $parts[] = trim((string)($row['description'] ?? ''));

    if (changelog_has_linked_news($row)) {
        $parts[] = '';
        $parts[] = 'Navázaná novinka: ' . changelog_linked_news_title($row);
        $newsText = trim(changelog_linked_news_perex_text($row) . "\n\n" . changelog_linked_news_body_text($row));
        if ($newsText !== '') {
            $parts[] = $newsText;
        }
    }

    $parts[] = '';
    $parts[] = 'Detail změny: ' . changelog_absolute_url(changelog_frontend_detail_url((int)($row['id'] ?? 0), 'cz'));

    return trim(implode("\n", $parts));
}

function changelog_email_group_sources(): array
{
    $sources = [];

    if (
        changelog_db_table_exists('users')
        && changelog_db_table_exists('users_skup')
        && changelog_db_column_exists('users', 'email')
        && changelog_db_column_exists('users', 'skup_id')
    ) {
        $sources['users'] = [
            'label' => 'Administrace',
            'users_table' => 'users',
            'groups_table' => 'users_skup',
        ];
    }

    if (
        changelog_db_table_exists('rep_users')
        && changelog_db_table_exists('rep_users_skup')
        && changelog_db_column_exists('rep_users', 'email')
        && changelog_db_column_exists('rep_users', 'skup_id')
    ) {
        $sources['rep_users'] = [
            'label' => 'Projekt',
            'users_table' => 'rep_users',
            'groups_table' => 'rep_users_skup',
        ];
    }

    return $sources;
}

function changelog_email_group_key(string $source, int $groupId): string
{
    return $source . ':' . $groupId;
}

function changelog_email_group_options(): array
{
    global $pdo;

    $options = [];
    foreach (changelog_email_group_sources() as $source => $meta) {
        $groupsTable = $meta['groups_table'];
        $usersTable = $meta['users_table'];
        $groupValidWhere = changelog_db_column_exists($groupsTable, 'valid') ? 'g.valid = 1' : '1=1';
        $userWhere = ["u.email IS NOT NULL", "TRIM(u.email) <> ''"];
        if (changelog_db_column_exists($usersTable, 'valid')) {
            $userWhere[] = 'u.valid = 1';
        }
        if (changelog_db_column_exists($usersTable, 'aktivni_l')) {
            $userWhere[] = 'u.aktivni_l = 1';
        }
        $userWhereSql = implode(' AND ', $userWhere);

        $stmt = $pdo->query(
            "SELECT
                g.id,
                g.nazev_cz AS name,
                COUNT(u.id) AS recipient_count
             FROM {$groupsTable} g
             LEFT JOIN {$usersTable} u
                    ON u.skup_id = g.id
                   AND {$userWhereSql}
             WHERE {$groupValidWhere}
             GROUP BY g.id, g.nazev_cz
             ORDER BY g.nazev_cz ASC, g.id ASC"
        );

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            $groupId = (int)($row['id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $options[] = [
                'key' => changelog_email_group_key($source, $groupId),
                'source' => $source,
                'source_label' => (string)$meta['label'],
                'id' => $groupId,
                'name' => (string)($row['name'] ?? ''),
                'recipient_count' => (int)($row['recipient_count'] ?? 0),
            ];
        }
    }

    return $options;
}

function changelog_email_group_options_by_key(): array
{
    $options = [];
    foreach (changelog_email_group_options() as $option) {
        $options[(string)$option['key']] = $option;
    }

    return $options;
}

function changelog_email_selected_groups(array $values): array
{
    $available = changelog_email_group_options_by_key();
    $selected = [];

    foreach ($values as $value) {
        $key = trim((string)$value);
        if (!isset($available[$key]) || isset($selected[$key])) {
            continue;
        }
        $selected[$key] = $key;
    }

    return array_values($selected);
}

function changelog_email_recipients(array $groupKeys): array
{
    global $pdo;

    $availableGroups = changelog_email_group_options_by_key();
    $sources = changelog_email_group_sources();
    $bySource = [];
    foreach ($groupKeys as $groupKey) {
        $option = $availableGroups[$groupKey] ?? null;
        if (!is_array($option)) {
            continue;
        }
        $bySource[(string)$option['source']][] = (int)$option['id'];
    }

    $recipients = [];
    foreach ($bySource as $source => $groupIds) {
        $meta = $sources[$source] ?? null;
        if (!is_array($meta)) {
            continue;
        }

        $usersTable = $meta['users_table'];
        $groupsTable = $meta['groups_table'];
        $userWhere = ["u.email IS NOT NULL", "TRIM(u.email) <> ''"];
        if (changelog_db_column_exists($usersTable, 'valid')) {
            $userWhere[] = 'u.valid = 1';
        }
        if (changelog_db_column_exists($usersTable, 'aktivni_l')) {
            $userWhere[] = 'u.aktivni_l = 1';
        }
        $placeholders = [];
        $params = [];
        foreach (array_values(array_unique(array_map('intval', $groupIds))) as $index => $groupId) {
            if ($groupId <= 0) {
                continue;
            }
            $placeholder = ':g' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $groupId;
        }
        if ($placeholders === []) {
            continue;
        }

        $nameSelect = changelog_db_column_exists($usersTable, 'name') ? 'u.name' : "''";
        $stmt = $pdo->prepare(
            "SELECT
                u.id,
                {$nameSelect} AS name,
                u.email,
                u.skup_id,
                g.nazev_cz AS group_name
             FROM {$usersTable} u
             INNER JOIN {$groupsTable} g ON g.id = u.skup_id
             WHERE u.skup_id IN (" . implode(',', $placeholders) . ")
               AND " . implode(' AND ', $userWhere) . "
             ORDER BY g.poradi ASC, g.nazev_cz ASC, u.id ASC"
        );
        $stmt->execute($params);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $email = trim((string)($row['email'] ?? ''));
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            $emailKey = mb_strtolower($email, 'UTF-8');
            $groupKey = changelog_email_group_key($source, (int)($row['skup_id'] ?? 0));
            if (!isset($recipients[$emailKey])) {
                $recipients[$emailKey] = [
                    'email' => $email,
                    'name' => trim((string)($row['name'] ?? '')),
                    'sources' => [],
                    'groups' => [],
                ];
            }

            $recipients[$emailKey]['sources'][$source] = (string)$meta['label'];
            $recipients[$emailKey]['groups'][$groupKey] = [
                'source' => $source,
                'source_label' => (string)$meta['label'],
                'name' => (string)($row['group_name'] ?? ''),
            ];
        }
    }

    return array_values($recipients);
}

function changelog_email_send(int $changeId, array $groupKeys): array
{
    global $pdo;

    $row = changelog_fetch($changeId);
    if (!is_array($row)) {
        throw new RuntimeException('Změna nebyla nalezena.');
    }
    if ((string)($row['status'] ?? '') !== 'nasazeno') {
        throw new RuntimeException('E-mailem lze odeslat pouze změnu ve stavu Nasazeno.');
    }

    $groupKeys = changelog_email_selected_groups($groupKeys);
    if ($groupKeys === []) {
        throw new RuntimeException('Vyber alespoň jednu skupinu příjemců.');
    }

    $recipients = changelog_email_recipients($groupKeys);
    if ($recipients === []) {
        throw new RuntimeException('Ve vybraných skupinách není žádný aktivní uživatel s platným e-mailem.');
    }

    require_once ROOT_DIR . '/functions/fun_mailer.php';

    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }

    $config = changelog_config();
    $subject = changelog_email_subject($row);
    $logoCid = 'changelog-logo-' . $changeId;
    $embeddedImages = [];
    $embeddedLogo = changelog_logo_embedded_image($logoCid);
    if ($embeddedLogo !== []) {
        $embeddedImages[] = $embeddedLogo;
    }
    $bodyHtml = changelog_email_body_html($row, $embeddedImages !== [] ? 'cid:' . $logoCid : null);
    $bodyText = changelog_email_body_text($row);
    $result = [
        'total' => 0,
        'sent' => 0,
        'failed' => 0,
        'errors' => [],
        'recipients' => $recipients,
    ];

    foreach ($recipients as $recipient) {
        $result['total']++;
        try {
            $message = [
                'recipient_email' => (string)$recipient['email'],
                'recipient_name' => (string)($recipient['name'] ?? ''),
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
            ];
            if ($embeddedImages !== []) {
                $message['embedded_images'] = $embeddedImages;
            }

            mailer_send_smtp_logged($pdo, $config, $message, [
                'context' => 'changelog',
                'template_code' => 'changelog_release',
                'related_table' => 'changelog',
                'related_id' => $changeId,
                'payload' => [
                    'group_keys' => $groupKeys,
                    'recipient_groups' => array_values((array)($recipient['groups'] ?? [])),
                ],
            ]);
            $result['sent']++;
        } catch (Throwable $e) {
            $result['failed']++;
            $result['errors'][] = (string)$recipient['email'] . ': ' . $e->getMessage();
        }
    }

    return $result;
}
