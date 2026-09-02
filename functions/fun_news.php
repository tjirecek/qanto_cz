<?php
declare(strict_types=1);

/**
 * fun_news.php (PDO)
 * - bezpečné dotazy (prepared statements)
 * - sjednocení CZ/EN sloupců bez duplicit
 */

function news_typ_field(string $lang, string $fieldBase): string
{
    // povol jen cz/en
    $lang = ($lang === 'en') ? 'en' : 'cz';

    return match ($fieldBase) {
        'nazev' => ($lang === 'en') ? 'nazev_en' : 'nazev_cz',
        'popis' => ($lang === 'en') ? 'popis_en' : 'popis_cz',
        default => 'nazev_cz',
    };
}

function news_typ_name(string $lang, int $id): string
{
    global $pdo;

    $col = news_typ_field($lang, 'nazev');
    $stmt = $pdo->prepare("SELECT {$col} AS val FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['val'] ?? '');
}

function news_typ_popis(string $lang, int $id): string
{
    global $pdo;

    $col = news_typ_field($lang, 'popis');
    $stmt = $pdo->prepare("SELECT {$col} AS val FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['val'] ?? '');
}

function news_typ_color(int $id): string
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT color FROM news_typ WHERE id = :id AND valid = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['color'] ?? '');
}

function frontend_news_safe_text(mixed $value, int $limit = 0): string
{
    $text = function_exists('plain_text') ? plain_text((string)$value) : trim(strip_tags((string)$value));
    $text = trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
    if ($limit > 0 && function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
        return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
    }
    if ($limit > 0 && !function_exists('mb_strlen') && strlen($text) > $limit) {
        return rtrim(substr($text, 0, $limit - 1)) . '…';
    }

    return $text;
}

function frontend_news_tag_class(string $class): string
{
    $class = trim($class);
    if ($class === '') {
        return 'text-bg-light border';
    }

    $classes = preg_split('~\s+~', $class) ?: [];
    $safeClasses = array_filter($classes, static function (string $item): bool {
        return (bool)preg_match('~^[a-zA-Z0-9_-]+$~', $item);
    });

    return $safeClasses === [] ? 'text-bg-light border' : implode(' ', $safeClasses);
}

function frontend_news_gallery_media_url(int $galleryId, string $file, bool $thumb = false): string
{
    $file = trim($file);
    if ($galleryId <= 0 || $file === '' || str_contains($file, '..') || str_contains($file, '/')) {
        return '';
    }

    $relative = 'media/galerie/' . $galleryId . '-galerie/' . ($thumb ? 'small/' : '') . $file;
    if (!defined('ROOT_DIR') || !is_file(ROOT_DIR . '/' . $relative)) {
        return '';
    }

    return '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
}

/**
 * @return array{title: string, description: string, photos: array<int, array{id: int, title: string, image: string, thumb: string}>}|null
 */
function frontend_news_gallery(int $galleryId, string $lang = 'cz'): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $galleryId <= 0) {
        return null;
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $descriptionColumn = $lang === 'en' ? 'popis_en' : 'popis_cz';
    $stmt = $pdo->prepare(
        "SELECT id, {$titleColumn} AS title, nazev_cz AS fallback_title,
                {$descriptionColumn} AS description, popis_cz AS fallback_description
         FROM galerie
         WHERE id = :gallery_id AND valid = 1
         LIMIT 1"
    );
    $stmt->execute([':gallery_id' => $galleryId]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($gallery)) {
        return null;
    }

    $photoTitleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $stmt = $pdo->prepare(
        "SELECT id, {$photoTitleColumn} AS title, nazev_cz AS fallback_title, soubor
         FROM galerie_photo
         WHERE galerie_id = :gallery_id AND valid = 1
         ORDER BY poradi ASC, id ASC"
    );
    $stmt->bindValue(':gallery_id', $galleryId, PDO::PARAM_INT);
    $stmt->execute();

    $photos = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $file = trim((string)($row['soubor'] ?? ''));
        $image = frontend_news_gallery_media_url($galleryId, $file);
        if ($image === '') {
            continue;
        }

        $thumb = frontend_news_gallery_media_url($galleryId, $file, true);
        $photoTitle = frontend_news_safe_text($row['title'] ?? '');
        if ($photoTitle === '') {
            $photoTitle = frontend_news_safe_text($row['fallback_title'] ?? '');
        }
        $photos[] = [
            'id' => (int)$row['id'],
            'title' => $photoTitle,
            'image' => $image,
            'thumb' => $thumb !== '' ? $thumb : $image,
        ];
    }

    if ($photos === []) {
        return null;
    }

    $title = frontend_news_safe_text($gallery['title'] ?? '');
    if ($title === '') {
        $title = frontend_news_safe_text($gallery['fallback_title'] ?? '');
    }
    $description = frontend_news_safe_text($gallery['description'] ?? '');
    if ($description === '') {
        $description = frontend_news_safe_text($gallery['fallback_description'] ?? '');
    }

    return [
        'title' => $title,
        'description' => $description,
        'photos' => $photos,
    ];
}

/**
 * @return array<int, string>
 */
function frontend_news_default_images(): array
{
    static $images = null;
    if (is_array($images)) {
        return $images;
    }

    $images = [];
    $baseDir = defined('ROOT_DIR') ? ROOT_DIR . '/img/design/news-default' : '';
    foreach (glob($baseDir . '/*.webp') ?: [] as $path) {
        $images[] = '/img/design/news-default/' . basename($path);
    }
    sort($images);

    return $images;
}

function frontend_news_default_image(int $newsId): string
{
    $images = frontend_news_default_images();
    if ($images === []) {
        return '';
    }

    $index = abs((int)crc32((string)$newsId)) % count($images);
    return $images[$index];
}

function frontend_news_local_image_url(string $src): string
{
    $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($src === '' || preg_match('~^(data|javascript):~i', $src) === 1) {
        return '';
    }
    if (str_starts_with($src, '//')) {
        return 'https:' . $src;
    }
    if (preg_match('~^https?://~i', $src) === 1) {
        return $src;
    }

    $path = (string)(parse_url($src, PHP_URL_PATH) ?: $src);
    $path = '/' . ltrim($path, '/');
    if (str_contains($path, '/../') || str_contains($path, '/..\\')) {
        return '';
    }
    if (preg_match('~^/(media|img|assets)/~', $path) !== 1) {
        return '';
    }

    $decodedPath = rawurldecode($path);
    if (!defined('ROOT_DIR') || !is_file(ROOT_DIR . $decodedPath)) {
        return '';
    }

    $encodedPath = implode('/', array_map(static fn(string $part): string => rawurlencode($part), explode('/', ltrim($decodedPath, '/'))));
    return '/' . $encodedPath;
}

function frontend_news_first_body_image_url(array $row): string
{
    foreach (['text_value', 'fallback_text', 'perex', 'fallback_perex'] as $field) {
        $html = (string)($row[$field] ?? '');
        if ($html === '' || stripos($html, '<img') === false) {
            continue;
        }
        if (preg_match('~<img\b[^>]*\bsrc\s*=\s*(?:"([^"]+)"|\'([^\']+)\'|([^\s>]+))~i', $html, $match) !== 1) {
            continue;
        }
        $src = (string)($match[1] ?: ($match[2] ?: ($match[3] ?? '')));
        $url = frontend_news_local_image_url($src);
        if ($url !== '') {
            return $url;
        }
    }

    return '';
}

function frontend_news_icon_image_url(array $row): string
{
    $filename = basename(trim((string)($row['news_ico'] ?? '')));
    if ($filename !== '') {
        $candidates = [
            'media/news_ico/small/' . $filename,
            'media/news_ico/' . $filename,
            'img/_news-ico/small/' . $filename,
            'img/_news-ico/' . $filename,
        ];
        foreach ($candidates as $candidate) {
            if (defined('ROOT_DIR') && is_file(ROOT_DIR . '/' . $candidate)) {
                return '/' . $candidate;
            }
        }
    }

    return '';
}

function frontend_news_image_url(array $row): string
{
    $iconImage = frontend_news_icon_image_url($row);
    if ($iconImage !== '') {
        return $iconImage;
    }

    $bodyImage = frontend_news_first_body_image_url($row);
    if ($bodyImage !== '') {
        return $bodyImage;
    }

    return frontend_news_default_image((int)($row['id'] ?? 0));
}

function frontend_news_detail_image_url(array $row): string
{
    $iconImage = frontend_news_icon_image_url($row);
    if ($iconImage !== '') {
        return $iconImage;
    }

    // Detail nesmi tahat prvni obrazek z tela clanku, aby se neduplikoval obsah.
    if (frontend_news_first_body_image_url($row) !== '') {
        return '';
    }

    return frontend_news_default_image((int)($row['id'] ?? 0));
}

function frontend_news_url(array $row, string $lang = 'cz'): string
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    $slug = trim((string)($row['url_' . $lang] ?? ''));
    if ($slug === '') {
        $slug = trim((string)($row['url_cz'] ?? ''));
    }
    if ($slug === '') {
        return '/' . rawurlencode($lang) . '/news?id=' . (int)($row['id'] ?? 0);
    }

    return '/' . rawurlencode($lang) . '/news/' . rawurlencode($slug);
}

function frontend_news_visible_sql(string $lang): string
{
    return $lang === 'en' ? 'n.visible IN (1,3)' : 'n.visible IN (1,2)';
}

function frontend_news_tag_slug_column(string $lang): string
{
    return $lang === 'en' ? "COALESCE(NULLIF(t.slug_en, ''), t.slug_cz)" : 't.slug_cz';
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_news_rows(string $lang = 'cz', int $limit = 4, ?string $slug = null, int $offset = 0, ?string $tagSlug = null): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $titleColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $perexColumn = $lang === 'en' ? 'perex_en' : 'perex_cz';
    $textColumn = $lang === 'en' ? 'text_en' : 'text_cz';
    $urlColumn = $lang === 'en' ? 'url_en' : 'url_cz';
    $tagColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $visibleSql = frontend_news_visible_sql($lang);
    $tagSlugColumn = frontend_news_tag_slug_column($lang);

    $where = "n.valid = 1 AND {$visibleSql}";
    $params = [];
    if ($slug !== null && $slug !== '') {
        $where .= " AND (n.{$urlColumn} = :slug_lang OR n.url_cz = :slug_cz)";
        $params[':slug_lang'] = $slug;
        $params[':slug_cz'] = $slug;
        $limit = 1;
    } else {
        $where .= " AND (n.datum IS NULL OR n.datum <= CURDATE())";
        $tagSlug = trim((string)$tagSlug);
        if ($tagSlug !== '') {
            $where .= " AND EXISTS (
                SELECT 1
                FROM news_tag_rel fr
                INNER JOIN news_tag t ON t.id = fr.tag_id AND t.valid = 1
                WHERE fr.news_id = n.id AND {$tagSlugColumn} = :tag_slug
            )";
            $params[':tag_slug'] = $tagSlug;
        }
    }

    $sql = "SELECT
                n.id,
                n.datum,
                n.news_ico,
                n.galerie_id,
                n.url_cz,
                n.url_en,
                n.{$titleColumn} AS title,
                n.nazev_cz AS fallback_title,
                n.{$perexColumn} AS perex,
                n.perex_cz AS fallback_perex,
                n.{$textColumn} AS text_value,
                n.text_cz AS fallback_text,
                (
                    SELECT GROUP_CONCAT(CONCAT(COALESCE(NULLIF(t.{$tagColumn}, ''), t.nazev_cz), '::', COALESCE(t.color, ''), '::', {$tagSlugColumn}) ORDER BY t.poradi ASC, t.nazev_cz ASC SEPARATOR '||')
                    FROM news_tag_rel r
                    INNER JOIN news_tag t ON t.id = r.tag_id AND t.valid = 1
                    WHERE r.news_id = n.id
                ) AS tags
            FROM news n
            WHERE {$where}
            ORDER BY n.datum DESC, n.id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $title = frontend_news_safe_text($row['title'] ?? '');
        if ($title === '') {
            $title = frontend_news_safe_text($row['fallback_title'] ?? '');
        }

        $perexFull = frontend_news_safe_text($row['perex'] ?? '');
        if ($perexFull === '') {
            $perexFull = frontend_news_safe_text($row['fallback_perex'] ?? '');
        }

        $perex = frontend_news_safe_text($row['perex'] ?? '', 155);
        if ($perex === '') {
            $perex = frontend_news_safe_text($row['fallback_perex'] ?? '', 155);
        }
        if ($perex === '') {
            $perex = frontend_news_safe_text($row['text_value'] ?? '', 155);
        }
        if ($perex === '') {
            $perex = frontend_news_safe_text($row['fallback_text'] ?? '', 155);
        }
        $content = trim((string)($row['text_value'] ?? ''));
        if ($content === '') {
            $content = trim((string)($row['fallback_text'] ?? ''));
        }
        $content = function_exists('editor_html') ? editor_html($content) : $content;

        $tags = [];
        foreach (explode('||', (string)($row['tags'] ?? '')) as $tagRaw) {
            $tagRaw = trim($tagRaw);
            if ($tagRaw === '') {
                continue;
            }
            [$tagName, $tagColor, $tagSlugValue] = array_pad(explode('::', $tagRaw, 3), 3, '');
            $tagName = frontend_news_safe_text($tagName);
            if ($tagName === '') {
                continue;
            }
            $tags[] = [
                'name' => $tagName,
                'class' => frontend_news_tag_class((string)$tagColor),
                'slug' => (string)$tagSlugValue,
            ];
        }

        $items[] = [
            'id' => (int)$row['id'],
            'date' => function_exists('format_date_www') ? format_date_www((string)($row['datum'] ?? '')) : (string)($row['datum'] ?? ''),
            'title' => $title,
            'perex' => $perex,
            'perex_full' => $perexFull,
            'content' => $content,
            'gallery_id' => (int)($row['galerie_id'] ?? 0),
            'href' => frontend_news_url($row, $lang),
            'icon_image' => frontend_news_icon_image_url($row),
            'detail_image' => frontend_news_detail_image_url($row),
            'image' => frontend_news_image_url($row),
            'tags' => $tags,
        ];
    }

    return $items;
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_news_latest(string $lang = 'cz', int $limit = 4): array
{
    return frontend_news_rows($lang, $limit);
}

function frontend_news_detail_row(string $lang, string $slug): ?array
{
    $rows = frontend_news_rows($lang, 1, $slug);
    return $rows[0] ?? null;
}

function frontend_news_count(string $lang = 'cz', ?string $tagSlug = null): int
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return 0;
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $visibleSql = frontend_news_visible_sql($lang);
    $tagSlugColumn = frontend_news_tag_slug_column($lang);
    $where = "n.valid = 1 AND {$visibleSql} AND (n.datum IS NULL OR n.datum <= CURDATE())";
    $params = [];

    $tagSlug = trim((string)$tagSlug);
    if ($tagSlug !== '') {
        $where .= " AND EXISTS (
            SELECT 1
            FROM news_tag_rel fr
            INNER JOIN news_tag t ON t.id = fr.tag_id AND t.valid = 1
            WHERE fr.news_id = n.id AND {$tagSlugColumn} = :tag_slug
        )";
        $params[':tag_slug'] = $tagSlug;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM news n WHERE {$where}");
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

/**
 * @return array<int, array<string, mixed>>
 */
function frontend_news_tags(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $lang = $lang === 'en' ? 'en' : 'cz';
    $tagColumn = $lang === 'en' ? 'nazev_en' : 'nazev_cz';
    $slugColumn = frontend_news_tag_slug_column($lang);
    $visibleSql = frontend_news_visible_sql($lang);

    $sql = "SELECT
                t.id,
                COALESCE(NULLIF(t.{$tagColumn}, ''), t.nazev_cz) AS label,
                {$slugColumn} AS slug,
                t.color,
                COUNT(DISTINCT n.id) AS news_count
            FROM news_tag t
            INNER JOIN news_tag_rel r ON r.tag_id = t.id
            INNER JOIN news n ON n.id = r.news_id
                AND n.valid = 1
                AND {$visibleSql}
                AND (n.datum IS NULL OR n.datum <= CURDATE())
            WHERE t.valid = 1
            GROUP BY t.id, label, slug, t.color, t.poradi
            HAVING news_count > 0
            ORDER BY t.poradi ASC, t.nazev_cz ASC";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $tags = [];
    foreach ($rows as $row) {
        $slug = trim((string)($row['slug'] ?? ''));
        $label = frontend_news_safe_text($row['label'] ?? '');
        if ($slug === '' || $label === '') {
            continue;
        }

        $tags[] = [
            'id' => (int)($row['id'] ?? 0),
            'label' => $label,
            'slug' => $slug,
            'class' => frontend_news_tag_class((string)($row['color'] ?? '')),
            'count' => (int)($row['news_count'] ?? 0),
        ];
    }

    return $tags;
}

function frontend_news_subscribe_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $token = $_SESSION['news_subscribe_csrf_token'] ?? '';
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(24));
        $_SESSION['news_subscribe_csrf_token'] = $token;
    }

    return $token;
}

/**
 * @return array{ok: bool, message: string}
 */
function frontend_news_subscribe_save(array $data): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return ['ok' => false, 'message' => ui_text('news.subscribe_error')];
    }

    if (function_exists('frontend_captcha_validate')) {
        $captcha = frontend_captcha_validate('news_subscribe', $data);
        if (!empty($captcha['bot'])) {
            return ['ok' => true, 'message' => ui_text('news.subscribe_success')];
        }
        if (empty($captcha['ok'])) {
            return ['ok' => false, 'message' => (string)$captcha['message']];
        }
    }

    $sessionToken = (string)($_SESSION['news_subscribe_csrf_token'] ?? '');
    $formToken = (string)($data['csrf_token'] ?? '');
    if ($sessionToken === '' || $formToken === '' || !hash_equals($sessionToken, $formToken)) {
        return ['ok' => false, 'message' => ui_text('news.subscribe_invalid')];
    }

    $email = trim(mb_strtolower((string)($data['email'] ?? ''), 'UTF-8'));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return ['ok' => false, 'message' => ui_text('news.subscribe_email_error')];
    }

    try {
        $existingStmt = $pdo->prepare('SELECT id FROM news_users WHERE LOWER(TRIM(email)) = :email ORDER BY id ASC LIMIT 1');
        $existingStmt->execute([':email' => $email]);
        $existingId = (int)($existingStmt->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $stmt = $pdo->prepare(
                "UPDATE news_users
                 SET email = :email,
                     datum_od = CURDATE(),
                     datum_do = NULL,
                     registered = 1,
                     valid = 1,
                     user_u = 'frontend_news_subscribe'
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $existingId,
                ':email' => $email,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO news_users (name, email, datum_od, datum_do, registered, valid, user_i, user_u)
                 VALUES ('', :email, CURDATE(), NULL, 1, 1, 'frontend_news_subscribe', 'frontend_news_subscribe')"
            );
            $stmt->execute([':email' => $email]);
        }

        $_SESSION['news_subscribe_csrf_token'] = bin2hex(random_bytes(24));

        return ['ok' => true, 'message' => ui_text('news.subscribe_success')];
    } catch (Throwable) {
        return ['ok' => false, 'message' => ui_text('news.subscribe_error')];
    }
}

/**
 * Výpis novinek (karty)
 * $category = 0 => všechny
 */
function news_vypis(int $category, string $lang): void
{
    global $pdo;

    if ($category === 0) {
        $sql = "SELECT *
                FROM news
                WHERE valid = 1
                  AND visible IN (1,2,3)
                ORDER BY datum DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "SELECT *
                FROM news
                WHERE news_typ = :category
                  AND valid = 1
                  AND visible IN (1,2,3)
                ORDER BY datum DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category' => $category]);
    }

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $typId = (int)($dev['news_typ'] ?? 0);

        $news_typ_popis = news_typ_popis($lang, $typId);
        $news_typ_color = news_typ_color($typId);

        $datum = format_date_www((string)($dev['datum'] ?? ''));

        // zatím bere CZ text jako dřív (můžeš později přepnout na EN variantu)
        $textCz = (string)($dev['text_cz'] ?? '');
        $perex  = mb_substr(strip_tags($textCz), 0, 130, 'UTF-8') . '…';

        $bg_color = ($news_typ_color !== '') ? 'bg-' . preg_replace('~[^a-z0-9_-]~i', '', $news_typ_color) : '';

        $urlCz   = (string)($dev['url_cz'] ?? '');
        $nazevCz = (string)($dev['nazev_cz'] ?? '');
        $tsu     = (string)($dev['ts_u'] ?? '');

        echo '
        <div class="col mb-4">
            <a class="underlineHover text-dark" href="/cz/news/' . htmlspecialchars($urlCz, ENT_QUOTES, 'UTF-8') . '">
            <div class="card h-100">
                <div class="card-header ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . '">
                    <small class="text-light fw-semibold">' . htmlspecialchars($news_typ_popis, ENT_QUOTES, 'UTF-8') . '</small>
                </div>
                <div class="card-body pb-0">
                    <h5 class="card-title text-dark">' . htmlspecialchars($nazevCz, ENT_QUOTES, 'UTF-8') . '</h5>
                    <p class="card-text"><small>' . htmlspecialchars($perex, ENT_QUOTES, 'UTF-8') . '</small></p>
                </div>
                <div class="card-footer bg-dark">
                    <small class="text-light">Aktualizováno: ' . htmlspecialchars(format_datetime_www($tsu), ENT_QUOTES, 'UTF-8') . '</small>
                </div>
            </div>
            </a>
        </div>';
    }
}

/**
 * Detail novinky
 * $news_url = url_cz
 */
function news_detail(string $lang, string $news_url): void
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM news WHERE url_cz = :url LIMIT 1");
    $stmt->execute([':url' => $news_url]);
    $dev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dev) {
        echo '<div class="alert alert-warning">Novinka nebyla nalezena.</div>';
        return;
    }

    $typId = (int)($dev['news_typ'] ?? 0);

    $news_typ_popis = news_typ_popis($lang, $typId);
    $news_typ_color = news_typ_color($typId);
    $bg_color = ($news_typ_color !== '') ? 'bg-' . preg_replace('~[^a-z0-9_-]~i', '', $news_typ_color) : '';

    $nazev = (string)($dev['nazev_cz'] ?? '');
    $text  = (string)($dev['text_cz'] ?? '');
    $tsu   = (string)($dev['ts_u'] ?? '');
    $userU = (string)($dev['user_u'] ?? '');

    $catLink = '/cz/news?category=' . (int)$typId;

    echo '
        <div class="card">
            <div class="card-header ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . ' fw-bold">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <a href="' . htmlspecialchars($catLink, ENT_QUOTES, 'UTF-8') . '" class="btn btn-danger btn-sm m-0 px-4 py-1">'
        . htmlspecialchars($news_typ_popis, ENT_QUOTES, 'UTF-8') .
        '</a>
                    </div>
                    <div class="col text-end">
                        <a href="/cz/news" class="btn btn-primary btn-sm m-0 px-4 py-1">&lt; zpět na výpis novinek</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h2 class="card-title">' . htmlspecialchars($nazev, ENT_QUOTES, 'UTF-8') . '</h2>
                <div class="card-text text-start">' . $text . '</div>
            </div>
            <div class="card-footer text-light bg-dark">
                Aktualizováno ' . htmlspecialchars(format_datetime_www($tsu), ENT_QUOTES, 'UTF-8') .
        ' uživatelem ' . htmlspecialchars(user_name($userU), ENT_QUOTES, 'UTF-8') . '
            </div>
        </div>';
}
