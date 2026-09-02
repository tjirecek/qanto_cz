<?php
declare(strict_types=1);

function frontend_banner_file_url(string $relativePath): string
{
    $relativePath = ltrim(trim($relativePath), '/');
    if ($relativePath === '') {
        return '';
    }
    $base = defined('BASE_URL') ? (string)BASE_URL : '/';
    return rtrim($base, '/') . '/' . $relativePath;
}

function frontend_banner_safe_text(mixed $value): string
{
    $text = function_exists('plain_text') ? plain_text((string)$value) : trim(strip_tags((string)$value));
    return trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
}

function frontend_akce_badge_class(mixed $value): string
{
    $class = trim((string)$value);
    if ($class === '') {
        return 'text-bg-light';
    }

    $classes = preg_split('~\s+~', $class) ?: [];
    $safeClasses = array_filter($classes, static function (string $item): bool {
        return (bool)preg_match('~^[a-zA-Z0-9_-]+$~', $item);
    });

    return $safeClasses === [] ? 'text-bg-light' : implode(' ', $safeClasses);
}

function frontend_banner_safe_url(mixed $value): string
{
    $url = trim((string)$value);
    if ($url === '' || preg_match('~^(javascript|data):~i', $url) === 1) {
        return '#';
    }
    return $url;
}

function frontend_banner_text(string $key): string
{
    return function_exists('ui_text') ? ui_text($key) : $key;
}

function frontend_akce_subscribe_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['akce_subscribe_token']) || !is_string($_SESSION['akce_subscribe_token'])) {
        $_SESSION['akce_subscribe_token'] = bin2hex(random_bytes(16));
    }

    return (string)$_SESSION['akce_subscribe_token'];
}

function frontend_akce_subscribe_label(string $newsletterGroup, string $fallback): string
{
    return match ($newsletterGroup) {
        'maloobchod' => frontend_banner_text('flyers.subscribe_group_maloobchod'),
        'velkoobchod' => frontend_banner_text('flyers.subscribe_group_velkoobchod'),
        default => $fallback,
    };
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_akce_subscription_types(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $nameColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';

    try {
        $stmt = $pdo->query(
            "SELECT id, legacy_id, code, nazev_cz, nazev_en, color, newsletter_group
             FROM rep_akce_typ
             WHERE valid = 1
               AND newsletter_group IN ('maloobchod', 'velkoobchod')
             ORDER BY FIELD(newsletter_group, 'maloobchod', 'velkoobchod'),
                      CASE
                          WHEN code = 'markety' THEN 0
                          WHEN code = 'velkoobchod' THEN 0
                          ELSE 1
                      END,
                      poradi ASC,
                      id ASC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }

    $types = [];
    $seenGroups = [];
    foreach ($rows as $row) {
        $newsletterGroup = trim((string)($row['newsletter_group'] ?? ''));
        if ($newsletterGroup === '' || isset($seenGroups[$newsletterGroup])) {
            continue;
        }
        $seenGroups[$newsletterGroup] = true;
        $code = trim((string)($row['code'] ?? ''));
        $fallback = frontend_banner_safe_text($row[$nameColumn] ?? '');
        if ($fallback === '') {
            $fallback = frontend_banner_safe_text($row['nazev_cz'] ?? '');
        }

        $types[] = [
            'id' => (int)$row['id'],
            'legacy_id' => $row['legacy_id'] !== null ? (int)$row['legacy_id'] : 0,
            'code' => $code,
            'newsletter_group' => $newsletterGroup,
            'label' => frontend_akce_subscribe_label($newsletterGroup, $fallback),
            'class' => frontend_akce_badge_class($row['color'] ?? ''),
        ];
    }

    return $types;
}

function frontend_akce_subscribe_normalize_email(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function frontend_akce_subscribe_email_valid(string $email): bool
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

/**
 * @param array<string, mixed> $data
 * @return array{ok: bool, message: string}
 */
function frontend_akce_subscribe_save(array $data): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return ['ok' => false, 'message' => frontend_banner_text('flyers.subscribe_error')];
    }

    if (function_exists('frontend_captcha_validate')) {
        $captcha = frontend_captcha_validate('akce_subscribe', $data);
        if (!empty($captcha['bot'])) {
            return ['ok' => true, 'message' => frontend_banner_text('flyers.subscribe_success')];
        }
        if (empty($captcha['ok'])) {
            return ['ok' => false, 'message' => (string)$captcha['message']];
        }
    }

    $sessionToken = session_status() === PHP_SESSION_ACTIVE ? (string)($_SESSION['akce_subscribe_token'] ?? '') : '';
    $postedToken = (string)($data['csrf_token'] ?? '');
    if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
        return ['ok' => false, 'message' => frontend_banner_text('flyers.subscribe_invalid')];
    }

    $email = frontend_akce_subscribe_normalize_email((string)($data['email'] ?? ''));
    if ($email === '' || !frontend_akce_subscribe_email_valid($email)) {
        return ['ok' => false, 'message' => frontend_banner_text('flyers.subscribe_email_error')];
    }

    $allowedTypes = [];
    foreach (frontend_akce_subscription_types('cz') as $type) {
        $allowedTypes[(int)$type['id']] = (int)$type['legacy_id'];
    }

    $rawTypeIds = $data['type_ids'] ?? [];
    if (!is_array($rawTypeIds)) {
        $rawTypeIds = [$rawTypeIds];
    }

    $typeIds = [];
    foreach ($rawTypeIds as $rawTypeId) {
        $typeId = (int)$rawTypeId;
        if (isset($allowedTypes[$typeId])) {
            $typeIds[$typeId] = $allowedTypes[$typeId];
        }
    }

    if ($typeIds === []) {
        return ['ok' => false, 'message' => frontend_banner_text('flyers.subscribe_type_error')];
    }

    try {
        $find = $pdo->prepare('SELECT id FROM rep_akce_users WHERE LOWER(TRIM(email)) = :email AND akce_typ_id = :type_id ORDER BY valid DESC, registered DESC, id ASC LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO rep_akce_users
            (akce_typ_id, legacy_akce_typ, name, email, datum_od, datum_do, registered, valid, user_i, user_u)
            VALUES (:type_id, :legacy_type_id, "", :email, CURDATE(), NULL, 1, 1, "frontend", "frontend")');
        $update = $pdo->prepare('UPDATE rep_akce_users
            SET registered = 1,
                valid = 1,
                datum_od = CURDATE(),
                datum_do = NULL,
                user_u = "frontend"
            WHERE id = :id');

        foreach ($typeIds as $typeId => $legacyTypeId) {
            $find->execute([':email' => $email, ':type_id' => $typeId]);
            $existingId = $find->fetchColumn();
            if ($existingId !== false) {
                $update->execute([':id' => (int)$existingId]);
                continue;
            }

            $insert->execute([
                ':type_id' => $typeId,
                ':legacy_type_id' => $legacyTypeId,
                ':email' => $email,
            ]);
        }
    } catch (Throwable) {
        return ['ok' => false, 'message' => frontend_banner_text('flyers.subscribe_error')];
    }

    $_SESSION['akce_subscribe_token'] = bin2hex(random_bytes(16));

    return ['ok' => true, 'message' => frontend_banner_text('flyers.subscribe_success')];
}

function frontend_banner_theme(mixed $value): string
{
    $value = (string)$value;
    $themes = [
        'brand-red' => true,
        'brand-dark' => true,
        'graphite' => true,
        'silver' => true,
        'wholesale-green' => true,
        'qantoplus-orange' => true,
        'ocean-blue' => true,
        'cream-paper' => true,
        'warm-sand' => true,
        'line-pattern' => true,
        'photo-zdenek' => true,
        'photo-vodicka' => true,
        'photo-mirek' => true,
        'photo-verka' => true,
        'photo-zaneta' => true,
        'photo-vodickova' => true,
        'photo-standa' => true,
        'photo-matej' => true,
    ];

    return isset($themes[$value]) ? $value : 'brand-red';
}

function frontend_akce_view_url(int $offerId, string $lang = 'cz'): string
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    return '/' . rawurlencode($lang) . '/akce?akce=' . $offerId;
}

function frontend_akce_file_exists(string $relativePath): bool
{
    $relativePath = ltrim(trim($relativePath), '/');
    return $relativePath !== '' && defined('ROOT_DIR') && is_file(ROOT_DIR . '/' . $relativePath);
}

function frontend_akce_image_url(string $relativePath): string
{
    return frontend_akce_file_exists($relativePath) ? frontend_banner_file_url($relativePath) : '';
}

function frontend_akce_page_thumbnail_path(string $imagePath): string
{
    return frontend_akce_page_variant_path($imagePath, 'thumbs');
}

function frontend_akce_page_variant_path(string $imagePath, string $variant): string
{
    $imagePath = ltrim(trim($imagePath), '/');
    $variant = strtolower(trim($variant));
    if ($imagePath === '' || !in_array($variant, ['small', 'medium', 'thumbs'], true)) {
        return '';
    }

    return dirname($imagePath) . '/' . $variant . '/' . pathinfo($imagePath, PATHINFO_FILENAME) . '.webp';
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_akce_auto_secondary_ads(string $lang = 'cz', int $limit = 12): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $typeColumn = $lang === 'en' ? 'typ_nazev_en' : 'typ_nazev_cz';

    try {
        $stmt = $pdo->query(
            'SELECT
                a.id,
                a.typ_id,
                a.nazev_cz,
                a.nazev_en,
                a.datum_od,
                a.datum_do,
                a.cover_image,
                a.legacy_cover_image,
                t.nazev_cz AS typ_nazev_cz,
                t.nazev_en AS typ_nazev_en,
                t.color AS typ_color,
                t.poradi AS typ_poradi,
                (
                    SELECT s.image_path
                    FROM rep_akce_strany s
                    WHERE s.akce_id = a.id AND s.valid = 1
                    ORDER BY s.poradi ASC, s.id ASC
                    LIMIT 1
                ) AS first_page
             FROM rep_akce a
             INNER JOIN rep_akce_typ t ON t.id = a.typ_id AND t.valid = 1
             WHERE a.valid = 1
               AND a.visible = 1
               AND a.is_primary = 1
               AND a.typ_id IS NOT NULL
               AND a.datum_do IS NOT NULL
               AND a.datum_do >= CURDATE()
             ORDER BY t.poradi ASC, t.id ASC, a.datum_do DESC, a.datum_od DESC, a.id DESC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }

    $items = [];
    $seenTypes = [];
    foreach ($rows as $row) {
        $typeId = (int)($row['typ_id'] ?? 0);
        if ($typeId <= 0 || isset($seenTypes[$typeId])) {
            continue;
        }

        $imagePath = trim((string)($row['first_page'] ?? ''));
        if ($imagePath === '') {
            $imagePath = trim((string)($row['cover_image'] ?? ''));
        }
        $imageUrl = frontend_akce_image_url($imagePath);
        if ($imageUrl === '') {
            continue;
        }

        $title = frontend_banner_safe_text($row[$titleColumn] ?? '');
        if ($title === '') {
            $title = frontend_banner_safe_text($row['nazev_cz'] ?? '');
        }
        if ($title === '') {
            continue;
        }

        $typeLabel = frontend_banner_safe_text($row[$typeColumn] ?? '');
        if ($typeLabel === '') {
            $typeLabel = frontend_banner_safe_text($row['typ_nazev_cz'] ?? '');
        }

        $seenTypes[$typeId] = true;
        $items[] = [
            'title' => $title,
            'link_text' => frontend_banner_text('akce.view_offer'),
            'href' => frontend_akce_view_url((int)$row['id'], $lang),
            'image' => $imageUrl,
            'image_mode' => 'cover',
            'text_color' => 'light',
            'valid_from' => (string)($row['datum_od'] ?? ''),
            'valid_to' => (string)($row['datum_do'] ?? ''),
            'position' => (int)($row['typ_poradi'] ?? 0),
            'theme' => 'custom',
            'source' => 'akce_auto',
            'type_id' => $typeId,
            'type_label' => $typeLabel,
            'type_class' => frontend_akce_badge_class($row['typ_color'] ?? ''),
            'offer_id' => (int)$row['id'],
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function frontend_akce_auto_secondary_count(): int
{
    return count(frontend_akce_auto_secondary_ads('cz', 50));
}

function frontend_akce_offer_detail(int $offerId, string $lang = 'cz'): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $offerId <= 0) {
        return null;
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $typeColumn = $lang === 'en' ? 'typ_nazev_en' : 'typ_nazev_cz';

    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, t.nazev_cz AS typ_nazev_cz, t.nazev_en AS typ_nazev_en, t.color AS typ_color
             FROM rep_akce a
             LEFT JOIN rep_akce_typ t ON t.id = a.typ_id
             WHERE a.id = :id AND a.valid = 1 AND a.visible = 1
             LIMIT 1'
        );
        $stmt->execute([':id' => $offerId]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($offer)) {
            return null;
        }

        $pagesStmt = $pdo->prepare(
            'SELECT image_path, poradi, width, height
             FROM rep_akce_strany
             WHERE akce_id = :akce_id AND valid = 1
             ORDER BY poradi ASC, id ASC'
        );
        $pagesStmt->execute([':akce_id' => $offerId]);
        $pageRows = $pagesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return null;
    }

    $pages = [];
    foreach ($pageRows as $index => $page) {
        $imagePath = (string)($page['image_path'] ?? '');
        $imageUrl = frontend_akce_image_url($imagePath);
        if ($imageUrl === '') {
            continue;
        }
        $thumbPath = frontend_akce_page_thumbnail_path($imagePath);
        $thumbUrl = frontend_akce_image_url($thumbPath);
        $sources = [];
        foreach (['small', 'medium'] as $variant) {
            $variantPath = frontend_akce_page_variant_path($imagePath, $variant);
            $variantUrl = frontend_akce_image_url($variantPath);
            if ($variantUrl === '') {
                continue;
            }
            $variantInfo = @getimagesize(ROOT_DIR . '/' . ltrim($variantPath, '/'));
            $sources[] = [
                'src' => $variantUrl,
                'width' => (int)($variantInfo[0] ?? 0),
            ];
        }
        $sources[] = [
            'src' => $imageUrl,
            'width' => (int)($page['width'] ?? 0),
        ];
        $pages[] = [
            'src' => $imageUrl,
            'thumb' => $thumbUrl !== '' ? $thumbUrl : $imageUrl,
            'label' => frontend_banner_text('akce.page') . ' ' . ((int)($page['poradi'] ?? 0) > 0 ? (int)$page['poradi'] : $index + 1),
            'width' => (int)($page['width'] ?? 0),
            'height' => (int)($page['height'] ?? 0),
            'sources' => $sources,
        ];
    }

    $typeLabel = frontend_banner_safe_text($offer[$typeColumn] ?? '');
    if ($typeLabel === '') {
        $typeLabel = frontend_banner_safe_text($offer['typ_nazev_cz'] ?? '');
    }

    $dateFrom = (string)($offer['datum_od'] ?? '');
    $dateTo = (string)($offer['datum_do'] ?? '');
    $status = frontend_akce_row_status($offer, date('Y-m-d'));
    $title = frontend_banner_safe_text($offer[$titleColumn] ?? '');
    if ($title === '') {
        $title = frontend_banner_safe_text($offer['nazev_cz'] ?? '');
    }
    $title = frontend_akce_display_title($title, $typeLabel, $dateTo, $offerId);

    return [
        'id' => $offerId,
        'title' => $title,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'date_from_label' => frontend_akce_date_label($dateFrom),
        'date_to_label' => frontend_akce_date_label($dateTo),
        'status' => $status,
        'status_label' => frontend_akce_status_label($status),
        'type_label' => $typeLabel,
        'type_class' => frontend_akce_badge_class($offer['typ_color'] ?? ''),
        'pdf' => frontend_akce_pdf_url((string)($offer['pdf_file'] ?? '')),
        'pages' => $pages,
    ];
}

function frontend_akce_pdf_url(string $relativePath): string
{
    return frontend_akce_file_exists($relativePath) ? frontend_banner_file_url($relativePath) : '';
}

function frontend_akce_date_label(string $date): string
{
    if ($date === '') {
        return '';
    }

    return function_exists('format_date_www') ? (string)format_date_www($date) : $date;
}

function frontend_akce_display_title(string $title, string $typeLabel, string $dateTo, int $offerId): string
{
    $title = frontend_banner_safe_text($title);
    if ($title !== '') {
        return $title;
    }

    $fallback = frontend_banner_safe_text($typeLabel);
    if ($fallback === '') {
        $fallback = frontend_banner_text('flyers.title');
    }

    $dateLabel = frontend_akce_date_label($dateTo);
    if ($dateLabel !== '') {
        return $fallback . ' ' . $dateLabel;
    }

    return $fallback . ' #' . $offerId;
}

function frontend_akce_validity_text(array $item): string
{
    $from = (string)($item['date_from_label'] ?? '');
    $to = (string)($item['date_to_label'] ?? '');

    if ($from !== '' && $to !== '') {
        return sprintf(frontend_banner_text('flyers.validity_from_to'), $from, $to);
    }
    if ($to !== '') {
        return sprintf(frontend_banner_text('flyers.validity_to'), $to);
    }
    if ($from !== '') {
        return sprintf(frontend_banner_text('flyers.validity_from'), $from);
    }

    return '';
}

function frontend_akce_status_label(string $status): string
{
    return match ($status) {
        'upcoming' => frontend_banner_text('flyers.status_upcoming'),
        'expired' => frontend_banner_text('flyers.status_expired'),
        default => frontend_banner_text('flyers.status_valid'),
    };
}

function frontend_akce_row_status(array $row, string $today): string
{
    $dateFrom = (string)($row['datum_od'] ?? '');
    $dateTo = (string)($row['datum_do'] ?? '');

    if ($dateTo !== '' && $dateTo < $today) {
        return 'expired';
    }
    if ($dateFrom !== '' && $dateFrom > $today) {
        return 'upcoming';
    }

    return 'valid';
}

function frontend_akce_item_from_row(array $row, string $lang, ?string $status = null): ?array
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $typeColumn = $lang === 'en' ? 'typ_nazev_en' : 'typ_nazev_cz';
    $today = date('Y-m-d');
    $status = $status ?: frontend_akce_row_status($row, $today);

    $imagePath = trim((string)($row['first_page'] ?? ''));
    if ($imagePath === '') {
        $imagePath = trim((string)($row['cover_image'] ?? ''));
    }
    $imageUrl = frontend_akce_image_url($imagePath);

    $typeLabel = frontend_banner_safe_text($row[$typeColumn] ?? '');
    if ($typeLabel === '') {
        $typeLabel = frontend_banner_safe_text($row['typ_nazev_cz'] ?? '');
    }
    if ($typeLabel === '') {
        $typeLabel = frontend_banner_text('flyers.category');
    }

    $dateFrom = (string)($row['datum_od'] ?? '');
    $dateTo = (string)($row['datum_do'] ?? '');
    $title = frontend_banner_safe_text($row[$titleColumn] ?? '');
    if ($title === '') {
        $title = frontend_banner_safe_text($row['nazev_cz'] ?? '');
    }
    $title = frontend_akce_display_title($title, $typeLabel, $dateTo, (int)$row['id']);

    return [
        'id' => (int)$row['id'],
        'title' => $title,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'date_from_label' => frontend_akce_date_label($dateFrom),
        'date_to_label' => frontend_akce_date_label($dateTo),
        'status' => $status,
        'status_label' => frontend_akce_status_label($status),
        'type_id' => (int)($row['typ_id'] ?? 0),
        'type_code' => trim((string)($row['typ_code'] ?? '')),
        'type_label' => $typeLabel,
        'type_class' => frontend_akce_badge_class($row['typ_color'] ?? ''),
        'type_position' => (int)($row['typ_poradi'] ?? 0),
        'image' => $imageUrl,
        'pdf' => frontend_akce_pdf_url((string)($row['pdf_file'] ?? '')),
        'href' => frontend_akce_view_url((int)$row['id'], $lang),
        'page_count' => (int)($row['page_count'] ?? 0),
        'year' => $dateTo !== '' ? (int)substr($dateTo, 0, 4) : 0,
    ];
}

function frontend_akce_build_type_panels(array $items, string $allLabelKey = 'flyers.all_categories'): array
{
    if ($items === []) {
        return [];
    }

    $allItems = $items;
    $types = [];
    foreach ($items as $item) {
        $typeId = (int)($item['type_id'] ?? 0);
        if ($typeId <= 0) {
            continue;
        }

        $typeCode = trim((string)($item['type_code'] ?? ''));
        $panelId = $typeCode !== '' ? $typeCode : (string)$typeId;
        if (!isset($types[$panelId])) {
            $types[$panelId] = [
                'id' => $panelId,
                'label' => (string)($item['type_label'] ?? ''),
                'class' => (string)($item['type_class'] ?? ''),
                'position' => (int)($item['type_position'] ?? 0),
                'items' => [],
            ];
        }
        $types[$panelId]['items'][] = $item;
    }

    $typePanels = array_values($types);
    usort($typePanels, static fn(array $a, array $b): int => ((int)$a['position'] <=> (int)$b['position']) ?: strcmp((string)$a['label'], (string)$b['label']));

    return array_merge([[
        'id' => 'all',
        'label' => frontend_banner_text($allLabelKey),
        'class' => 'home-flyers__tab--all',
        'items' => $allItems,
    ]], $typePanels);
}

function frontend_akce_page_rows(): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    try {
        $stmt = $pdo->query(
            'SELECT
                a.id,
                a.typ_id,
                a.nazev_cz,
                a.nazev_en,
                a.datum_od,
                a.datum_do,
                a.cover_image,
                a.pdf_file,
                t.code AS typ_code,
                t.nazev_cz AS typ_nazev_cz,
                t.nazev_en AS typ_nazev_en,
                t.color AS typ_color,
                t.poradi AS typ_poradi,
                (
                    SELECT s.image_path
                    FROM rep_akce_strany s
                    WHERE s.akce_id = a.id AND s.valid = 1
                    ORDER BY s.poradi ASC, s.id ASC
                    LIMIT 1
                ) AS first_page,
                (
                    SELECT COUNT(*)
                    FROM rep_akce_strany s
                    WHERE s.akce_id = a.id AND s.valid = 1
                ) AS page_count
             FROM rep_akce a
             INNER JOIN rep_akce_typ t ON t.id = a.typ_id AND t.valid = 1
             WHERE a.valid = 1
               AND a.visible = 1
               AND a.typ_id IS NOT NULL
               AND a.datum_do IS NOT NULL
               AND a.datum_do >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
             ORDER BY
                CASE
                    WHEN a.datum_do < CURDATE() THEN 2
                    WHEN a.datum_od > CURDATE() THEN 1
                    ELSE 0
                END ASC,
                CASE WHEN a.datum_do < CURDATE() THEN a.datum_do END DESC,
                CASE WHEN a.datum_do >= CURDATE() THEN a.datum_do END ASC,
                a.datum_od ASC,
                a.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}

function frontend_akce_page_overview(string $lang = 'cz'): array
{
    $today = date('Y-m-d');
    $current = [];
    $upcoming = [];
    $expired = [];

    foreach (frontend_akce_page_rows() as $row) {
        $status = frontend_akce_row_status($row, $today);
        $item = frontend_akce_item_from_row($row, $lang, $status);
        if ($item === null) {
            continue;
        }

        if ($status === 'upcoming') {
            $upcoming[] = $item;
            continue;
        }
        if ($status === 'expired') {
            $expired[] = $item;
            continue;
        }

        $current[] = $item;
    }

    return [
        'current' => $current,
        'upcoming' => $upcoming,
        'expired' => $expired,
        'current_panels' => frontend_akce_build_type_panels($current),
        'upcoming_panels' => frontend_akce_build_type_panels($upcoming),
        'expired_panels' => frontend_akce_build_type_panels($expired),
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_akce_home_flyer_categories(string $lang = 'cz', int $limitPerCategory = 5): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $typeColumn = $lang === 'en' ? 'typ_nazev_en' : 'typ_nazev_cz';
    $limitPerCategory = max(1, min(12, $limitPerCategory));

    try {
        $stmt = $pdo->query(
            'SELECT
                a.id,
                a.typ_id,
                a.nazev_cz,
                a.nazev_en,
                a.datum_od,
                a.datum_do,
                a.cover_image,
                a.pdf_file,
                t.nazev_cz AS typ_nazev_cz,
                t.nazev_en AS typ_nazev_en,
                t.color AS typ_color,
                t.poradi AS typ_poradi,
                (
                    SELECT s.image_path
                    FROM rep_akce_strany s
                    WHERE s.akce_id = a.id AND s.valid = 1
                    ORDER BY s.poradi ASC, s.id ASC
                    LIMIT 1
                ) AS first_page
             FROM rep_akce a
             INNER JOIN rep_akce_typ t ON t.id = a.typ_id AND t.valid = 1
             WHERE a.valid = 1
               AND a.visible = 1
               AND a.typ_id IS NOT NULL
               AND a.datum_do IS NOT NULL
               AND a.datum_do >= CURDATE()
             ORDER BY t.poradi ASC, t.id ASC, a.datum_do ASC, a.datum_od ASC, a.id DESC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }

    $today = date('Y-m-d');
    $categories = [];
    foreach ($rows as $row) {
        $typeId = (int)($row['typ_id'] ?? 0);
        if ($typeId <= 0) {
            continue;
        }

        $imagePath = trim((string)($row['first_page'] ?? ''));
        if ($imagePath === '') {
            $imagePath = trim((string)($row['cover_image'] ?? ''));
        }
        $imageUrl = frontend_akce_image_url($imagePath);
        if ($imageUrl === '') {
            continue;
        }

        $title = frontend_banner_safe_text($row[$titleColumn] ?? '');
        if ($title === '') {
            $title = frontend_banner_safe_text($row['nazev_cz'] ?? '');
        }
        if ($title === '') {
            continue;
        }

        $typeLabel = frontend_banner_safe_text($row[$typeColumn] ?? '');
        if ($typeLabel === '') {
            $typeLabel = frontend_banner_safe_text($row['typ_nazev_cz'] ?? '');
        }
        if ($typeLabel === '') {
            $typeLabel = frontend_banner_text('flyers.category');
        }

        if (!isset($categories[$typeId])) {
            $categories[$typeId] = [
                'id' => $typeId,
                'label' => $typeLabel,
                'class' => frontend_akce_badge_class($row['typ_color'] ?? ''),
                'position' => (int)($row['typ_poradi'] ?? 0),
                'items' => [],
            ];
        }
        if (count($categories[$typeId]['items']) >= $limitPerCategory) {
            continue;
        }

        $dateFrom = (string)($row['datum_od'] ?? '');
        $dateTo = (string)($row['datum_do'] ?? '');
        $status = $dateFrom !== '' && $dateFrom > $today ? 'upcoming' : 'valid';

        $categories[$typeId]['items'][] = [
            'id' => (int)$row['id'],
            'title' => $title,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_from_label' => frontend_akce_date_label($dateFrom),
            'date_to_label' => frontend_akce_date_label($dateTo),
            'status' => $status,
            'status_label' => frontend_banner_text($status === 'upcoming' ? 'flyers.status_upcoming' : 'flyers.status_valid'),
            'type_label' => $typeLabel,
            'type_class' => frontend_akce_badge_class($row['typ_color'] ?? ''),
            'image' => $imageUrl,
            'pdf' => frontend_akce_pdf_url((string)($row['pdf_file'] ?? '')),
            'href' => frontend_akce_view_url((int)$row['id'], $lang),
        ];
    }

    $categories = array_values(array_filter($categories, static fn(array $category): bool => ($category['items'] ?? []) !== []));
    usort($categories, static fn(array $a, array $b): int => ((int)$a['position'] <=> (int)$b['position']) ?: ((int)$a['id'] <=> (int)$b['id']));

    return $categories;
}

function frontend_banners(string $positionKey, string $lang = 'cz', int $limit = 10): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $positionKey = in_array($positionKey, ['main_carousel', 'secondary_links'], true) ? $positionKey : 'main_carousel';
    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'popis_en' : 'popis_cz';
    $linkColumn = $lang === 'en' ? 'link_text_en' : 'link_text_cz';

    try {
        $stmt = $pdo->prepare(
            'SELECT id, position_key, poradi, valid_to, image, url, popis_cz, popis_en, link_text_cz, link_text_en, text_color, background_theme
             FROM rep_bannery
             WHERE position_key = :position_key
               AND valid = 1
               AND visible = 1
               AND (valid_to IS NULL OR valid_to >= CURDATE())
             ORDER BY poradi ASC, id DESC
             LIMIT ' . max(1, min(50, $limit))
        );
        $stmt->execute([':position_key' => $positionKey]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }

    $items = [];
    foreach ($rows as $row) {
        $title = frontend_banner_safe_text($row[$titleColumn] ?? '');
        if ($title === '') {
            $title = frontend_banner_safe_text($row['popis_cz'] ?? '');
        }
        if ($title === '') {
            continue;
        }

        $linkText = frontend_banner_safe_text($row[$linkColumn] ?? '');
        if ($linkText === '') {
            $linkText = frontend_banner_safe_text($row['link_text_cz'] ?? '');
        }
        if ($linkText === '') {
            $linkText = ui_text('common.more');
        }

        $items[] = [
            'title' => $title,
            'link_text' => $linkText,
            'href' => frontend_banner_safe_url($row['url'] ?? ''),
            'image' => frontend_banner_file_url((string)($row['image'] ?? '')),
            'image_mode' => 'cover',
            'text_color' => (string)($row['text_color'] ?? '') === 'light' ? 'light' : 'dark',
            'valid_from' => '',
            'valid_to' => (string)($row['valid_to'] ?? ''),
            'position' => (int)($row['poradi'] ?? 0),
            'theme' => frontend_banner_theme($row['background_theme'] ?? 'brand-red'),
        ];
    }

    return $items;
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_detail_sidebar_ads(string $lang = 'cz', int $limit = 3): array
{
    $limit = max(1, min(8, $limit));
    $autoItems = frontend_akce_auto_secondary_ads($lang, $limit);
    $manualItems = frontend_banners('secondary_links', $lang, $limit);

    return array_slice(array_values(array_merge($autoItems, $manualItems)), 0, $limit);
}
