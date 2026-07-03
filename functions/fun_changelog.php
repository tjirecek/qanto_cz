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
