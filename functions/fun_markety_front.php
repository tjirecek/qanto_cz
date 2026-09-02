<?php
declare(strict_types=1);

function frontend_markety_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_markety_localized(array $row, string $base, string $lang): string
{
    $lang = $lang === 'en' ? 'en' : 'cz';
    $value = trim((string)($row[$base . '_' . $lang] ?? ''));
    if ($value !== '') {
        return $value;
    }

    return trim((string)($row[$base . '_cz'] ?? ''));
}

function frontend_markety_plain(mixed $value, int $limit = 180): string
{
    $text = trim(preg_replace('~\s+~u', ' ', strip_tags((string)$value)) ?? '');
    if ($text === '' || mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
}

function frontend_markety_slug(string $value, int $id = 0): string
{
    if (function_exists('text_str')) {
        $slug = trim((string)text_str($value), '-');
    } else {
        $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        $slug = strtolower(preg_replace('~[^a-z0-9]+~i', '-', $ascii !== false ? $ascii : $value) ?? '');
    }
    $slug = strtolower(preg_replace('~[^a-z0-9]+~i', '-', $slug) ?? '');
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'pobocka';
    }

    return $id > 0 ? $id . '-' . $slug : $slug;
}

function frontend_markety_slug_base(string $value): string
{
    return frontend_markety_slug($value);
}

function frontend_markety_branch_slug(array $branch): string
{
    $slug = trim((string)($branch['slug'] ?? ''));
    if ($slug !== '') {
        return $slug;
    }

    return frontend_markety_slug_base((string)($branch['name'] ?? ''));
}

function frontend_markety_detail_url(array $branch, string $lang = 'cz', string $route = 'markety'): string
{
    $route = trim($route, '/');
    if (!preg_match('~^[a-z0-9_-]+$~i', $route)) {
        $route = 'markety';
    }

    return '/' . rawurlencode($lang) . '/' . rawurlencode($route) . '/' . rawurlencode(frontend_markety_branch_slug($branch));
}

function frontend_markety_file_url(mixed $path): string
{
    $path = trim((string)$path);
    if ($path === '' || str_contains($path, '..')) {
        return '';
    }

    $path = ltrim($path, '/');
    if (!is_file(ROOT_DIR . '/' . $path)) {
        return '';
    }

    return '/' . implode('/', array_map('rawurlencode', explode('/', $path)));
}

function frontend_markety_media_url(int $galleryId, string $file, bool $thumb = false): string
{
    $file = trim($file);
    if ($galleryId <= 0 || $file === '' || str_contains($file, '..') || str_contains($file, '/')) {
        return '';
    }

    $relative = 'media/galerie/' . $galleryId . '-galerie/' . ($thumb ? 'small/' : '') . $file;
    if (!is_file(ROOT_DIR . '/' . $relative)) {
        return '';
    }

    return '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
}

function frontend_markety_city(string $address, string $fallbackTitle = ''): string
{
    $address = trim($address);
    if ($address !== '') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn(string $part): bool => $part !== ''));
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $candidate = preg_replace('/\b\d{3}\s?\d{2}\b/u', '', $parts[$i]);
            $candidate = preg_replace('/\s+\d+[A-Za-z]?([\/-]\d+)?$/u', '', (string)$candidate);
            $candidate = trim((string)$candidate, " \t\n\r\0\x0B,");
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    $fallbackTitle = preg_replace('/^(Qanto\+?|Market|Supermarket)\s+/u', '', trim($fallbackTitle));
    $fallbackTitle = preg_replace('/\s*[-,].+$/u', '', (string)$fallbackTitle);

    return trim((string)$fallbackTitle);
}

function frontend_markety_parse_gps(string $gps): ?array
{
    $gps = trim($gps);
    if ($gps === '') {
        return null;
    }

    $parts = preg_split('~\s*,\s*~', $gps);
    if (!is_array($parts) || count($parts) < 2) {
        return null;
    }

    $lat = frontend_markety_parse_gps_part((string)$parts[0]);
    $lon = frontend_markety_parse_gps_part((string)$parts[1]);
    if ($lat === null || $lon === null) {
        return null;
    }

    if ($lat < 40 || $lat > 60 || $lon < 8 || $lon > 25) {
        return null;
    }

    return ['lat' => $lat, 'lon' => $lon];
}

function frontend_markety_parse_gps_part(string $value): ?float
{
    $value = strtoupper(trim($value));
    if ($value === '') {
        return null;
    }

    $sign = (str_contains($value, 'S') || str_contains($value, 'W')) ? -1.0 : 1.0;
    if (!preg_match('~-?\d+(?:[\.,]\d+)?~', $value, $match)) {
        return null;
    }

    return (float)str_replace(',', '.', $match[0]) * $sign;
}

function frontend_markety_time(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    return substr($value, 0, 5);
}

function frontend_markety_day_label(int $day, string $lang = 'cz'): string
{
    $key = match ($day) {
        1 => 'common.day_monday',
        2 => 'common.day_tuesday',
        3 => 'common.day_wednesday',
        4 => 'common.day_thursday',
        5 => 'common.day_friday',
        6 => 'common.day_saturday',
        7 => 'common.day_sunday',
        default => '',
    };

    return $key !== '' ? ui_text($key) : (string)$day;
}

/** @param array<string, mixed> $row */
function frontend_markety_opening_interval(array $row): string
{
    if ((int)($row['zavreno'] ?? 0) === 1) {
        return '';
    }

    $parts = [];
    $from1 = frontend_markety_time($row['od1'] ?? null);
    $to1 = frontend_markety_time($row['do1'] ?? null);
    $from2 = frontend_markety_time($row['od2'] ?? null);
    $to2 = frontend_markety_time($row['do2'] ?? null);
    if ($from1 !== '' && $to1 !== '') {
        $parts[] = $from1 . '-' . $to1;
    }
    if ($from2 !== '' && $to2 !== '') {
        $parts[] = $from2 . '-' . $to2;
    }

    return implode(', ', $parts);
}

function frontend_markety_opening_is_open(array $row, ?DateTimeImmutable $now = null): bool
{
    if ((int)($row['zavreno'] ?? 0) === 1) {
        return false;
    }

    $now ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $current = $now->format('H:i');
    foreach ([['od1', 'do1'], ['od2', 'do2']] as $pair) {
        $from = frontend_markety_time($row[$pair[0]] ?? null);
        $to = frontend_markety_time($row[$pair[1]] ?? null);
        if ($from !== '' && $to !== '' && $current >= $from && $current < $to) {
            return true;
        }
    }

    return false;
}

/** @return array<int, array{from: string, to: string}> */
function frontend_markety_opening_intervals(array $row): array
{
    if ((int)($row['zavreno'] ?? 0) === 1) {
        return [];
    }

    $intervals = [];
    foreach ([['od1', 'do1'], ['od2', 'do2']] as $pair) {
        $from = frontend_markety_time($row[$pair[0]] ?? null);
        $to = frontend_markety_time($row[$pair[1]] ?? null);
        if ($from !== '' && $to !== '') {
            $intervals[] = ['from' => $from, 'to' => $to];
        }
    }

    return $intervals;
}

/** @return array{from: string, to: string}|null */
function frontend_markety_current_opening_interval(array $row, ?DateTimeImmutable $now = null): ?array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $current = $now->format('H:i');
    foreach (frontend_markety_opening_intervals($row) as $interval) {
        if ($current >= $interval['from'] && $current < $interval['to']) {
            return $interval;
        }
    }

    return null;
}

/** @return array{from: string, to: string}|null */
function frontend_markety_next_opening_interval_today(array $row, ?DateTimeImmutable $now = null): ?array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $current = $now->format('H:i');
    foreach (frontend_markety_opening_intervals($row) as $interval) {
        if ($current < $interval['from']) {
            return $interval;
        }
    }

    return null;
}

/** @return array{from: string, to: string}|null */
function frontend_markety_first_opening_interval(array $row): ?array
{
    return frontend_markety_opening_intervals($row)[0] ?? null;
}

function frontend_markety_opening_note(array $row, string $lang = 'cz'): string
{
    return frontend_markety_plain($row['poznamka_' . ($lang === 'en' ? 'en' : 'cz')] ?? ($row['poznamka_cz'] ?? ''), 100);
}

function frontend_markety_opening_time_label(string $time): string
{
    return preg_replace('~^0(?=\d:)~', '', $time) ?? $time;
}

/** @param array{from: string, to: string} $interval */
function frontend_markety_opening_from_to_label(string $key, array $interval): string
{
    return sprintf(
        ui_text($key),
        frontend_markety_opening_time_label($interval['from']),
        frontend_markety_opening_time_label($interval['to'])
    );
}

function frontend_markety_opening_week(array $openingRows, string $lang = 'cz', ?array $todayException = null, ?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $today = (int)$now->format('N');
    $week = [];
    for ($day = 1; $day <= 7; $day++) {
        $row = $openingRows[$day] ?? [];
        $isToday = $day === $today;
        $isException = false;
        if ($isToday && is_array($todayException)) {
            $row = $todayException;
            $isException = true;
        }

        $interval = is_array($row) ? frontend_markety_opening_interval($row) : '';
        $note = is_array($row) ? frontend_markety_opening_note($row, $lang) : '';
        $isOpen = $isToday && is_array($row) && $interval !== '' && frontend_markety_opening_is_open($row, $now);
        $week[] = [
            'day' => $day,
            'label' => frontend_markety_day_label($day, $lang),
            'time' => $interval !== '' ? $interval : ui_text('markety.closed'),
            'note' => $note,
            'closed' => $interval === '',
            'is_today' => $isToday,
            'is_open' => $isOpen,
            'is_exception' => $isException,
        ];
    }

    return $week;
}

function frontend_markety_today_opening(array $openingRows, string $lang = 'cz', ?array $todayException = null, ?DateTimeImmutable $now = null, ?array $tomorrowException = null): array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $today = (int)$now->format('N');
    $tomorrow = $today === 7 ? 1 : $today + 1;
    $row = is_array($todayException) ? $todayException : ($openingRows[$today] ?? null);
    if (!is_array($row)) {
        return [
            'is_open' => false,
            'label' => ui_text('markety.opening_unknown'),
            'time' => '',
            'note' => '',
            'is_exception' => is_array($todayException),
        ];
    }

    $interval = frontend_markety_opening_interval($row);
    $tomorrowRow = is_array($tomorrowException) ? $tomorrowException : ($openingRows[$tomorrow] ?? null);
    $tomorrowFirstInterval = is_array($tomorrowRow) ? frontend_markety_first_opening_interval($tomorrowRow) : null;
    if ($interval === '') {
        return [
            'is_open' => false,
            'label' => $tomorrowFirstInterval !== null
                ? frontend_markety_opening_from_to_label('markety.closed_tomorrow_from_to', $tomorrowFirstInterval)
                : ui_text('markety.closed_today'),
            'time' => ui_text('markety.closed'),
            'note' => frontend_markety_opening_note($row, $lang),
            'is_exception' => is_array($todayException),
        ];
    }

    $currentInterval = frontend_markety_current_opening_interval($row, $now);
    $nextIntervalToday = frontend_markety_next_opening_interval_today($row, $now);
    $isOpen = $currentInterval !== null;

    if ($isOpen) {
        $label = frontend_markety_opening_from_to_label('markety.open_from_to', $currentInterval);
    } elseif ($nextIntervalToday !== null) {
        $label = frontend_markety_opening_from_to_label('markety.closed_today_from_to', $nextIntervalToday);
    } elseif ($tomorrowFirstInterval !== null) {
        $label = frontend_markety_opening_from_to_label('markety.closed_tomorrow_from_to', $tomorrowFirstInterval);
    } else {
        $label = ui_text('markety.closed_now');
    }

    return [
        'is_open' => $isOpen,
        'label' => $label,
        'time' => $interval,
        'note' => frontend_markety_opening_note($row, $lang),
        'is_exception' => is_array($todayException),
    ];
}

/** @return array<int, array<int, array<string, mixed>>> */
function frontend_markety_opening_rows(array $branchIds): array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $branchIds === []) {
        return [];
    }

    $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds), static fn(int $id): bool => $id > 0)));
    if ($branchIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT pobocka_id, den, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en
         FROM pobocky_otevdoba
         WHERE valid = 1 AND pobocka_id IN (' . $placeholders . ')
         ORDER BY den ASC'
    );
    foreach ($branchIds as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $rows[(int)$row['pobocka_id']][(int)$row['den']] = $row;
    }

    return $rows;
}

/** @return array<int, array<string, mixed>> */
function frontend_markety_opening_exceptions(array $branchIds, ?string $date = null): array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $branchIds === []) {
        return [];
    }

    $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds), static fn(int $id): bool => $id > 0)));
    if ($branchIds === []) {
        return [];
    }

    $date = trim((string)($date ?? (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('Y-m-d')));
    if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $date)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT pobocka_id, datum, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en
         FROM pobocky_otevdoba_vyjimky
         WHERE valid = 1 AND datum = ? AND pobocka_id IN (' . $placeholders . ')
         ORDER BY id DESC'
    );
    $stmt->bindValue(1, $date);
    foreach ($branchIds as $index => $id) {
        $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
    }
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $branchId = (int)$row['pobocka_id'];
        if (!isset($rows[$branchId])) {
            $rows[$branchId] = $row;
        }
    }

    return $rows;
}

/** @return array<int, array<string, mixed>> */
function frontend_markety_list(string $lang = 'cz', string $branchType = 'market'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $branchType = preg_replace('~[^a-z0-9_-]+~i', '', $branchType) ?: 'market';
    $stmt = $pdo->prepare(
        "SELECT id, slug, stredisko, nazev_cz, nazev_en, adresa, gps, image, mobil, email, vedouci
         FROM pobocky
         WHERE typ = :typ AND valid = 1
         ORDER BY nazev_cz ASC, id ASC"
    );
    $stmt->execute([':typ' => $branchType]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $branchIds = array_map(static fn(array $row): int => (int)$row['id'], $rows);
    $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $tomorrow = $today->modify('+1 day');
    $openingRows = frontend_markety_opening_rows($branchIds);
    $openingExceptions = frontend_markety_opening_exceptions($branchIds, $today->format('Y-m-d'));
    $tomorrowOpeningExceptions = frontend_markety_opening_exceptions($branchIds, $tomorrow->format('Y-m-d'));

    $branches = [];
    foreach ($rows as $row) {
        $name = frontend_markety_localized($row, 'nazev', $lang);
        if ($name === '') {
            continue;
        }

        $gps = frontend_markety_parse_gps((string)($row['gps'] ?? ''));
        $address = frontend_markety_plain($row['adresa'] ?? '', 160);
        $city = frontend_markety_city($address, $name);
        $todayOpening = frontend_markety_today_opening(
            $openingRows[(int)$row['id']] ?? [],
            $lang,
            $openingExceptions[(int)$row['id']] ?? null,
            $today,
            $tomorrowOpeningExceptions[(int)$row['id']] ?? null
        );

        $branches[] = [
            'id' => (int)$row['id'],
            'stredisko' => (int)($row['stredisko'] ?? 0),
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'image' => frontend_markety_file_url($row['image'] ?? ''),
            'phone' => trim((string)($row['mobil'] ?? '')),
            'email' => trim((string)($row['email'] ?? '')),
            'manager' => trim((string)($row['vedouci'] ?? '')),
            'slug' => trim((string)($row['slug'] ?? '')) !== '' ? trim((string)$row['slug']) : frontend_markety_slug_base($name),
            'opening_label' => (string)$todayOpening['label'],
            'is_open' => (bool)$todayOpening['is_open'],
            'lat' => $gps['lat'] ?? null,
            'lon' => $gps['lon'] ?? null,
        ];
    }

    return $branches;
}

function frontend_markety_services(mixed $html): array
{
    $html = trim((string)$html);
    if ($html === '') {
        return [];
    }

    $items = [];
    if (preg_match_all('~<li[^>]*>(.*?)</li>~isu', $html, $matches)) {
        foreach ($matches[1] as $match) {
            $text = frontend_markety_plain($match, 120);
            if ($text !== '') {
                $items[] = $text;
            }
        }
    }

    if ($items !== []) {
        return array_values(array_unique($items));
    }

    $plain = frontend_markety_plain($html, 600);
    if ($plain === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('~[\r\n]+~', $plain) ?: [])));
}

function frontend_markety_gallery_photos(int $galleryId, string $lang = 'cz', int $limit = 24): array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $galleryId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT id, poradi, nazev_cz, nazev_en, soubor
         FROM galerie_photo
         WHERE galerie_id = :gallery_id AND valid = 1
         ORDER BY poradi ASC, id ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':gallery_id', $galleryId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(120, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    $photos = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $file = trim((string)($row['soubor'] ?? ''));
        $original = frontend_markety_media_url($galleryId, $file, false);
        if ($original === '') {
            continue;
        }

        $thumb = frontend_markety_media_url($galleryId, $file, true);
        $title = frontend_markety_localized($row, 'nazev', $lang);
        $photos[] = [
            'id' => (int)$row['id'],
            'title' => $title,
            'image' => $original,
            'thumb' => $thumb !== '' ? $thumb : $original,
        ];
    }

    return $photos;
}

function frontend_markety_detail_by_slug(string $slug, string $lang = 'cz', string $branchType = 'market'): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $slug === '') {
        return null;
    }

    $branchId = 0;
    if (preg_match('~^(\d+)-~', $slug, $match)) {
        $branchId = (int)$match[1];
    }

    $branchType = preg_replace('~[^a-z0-9_-]+~i', '', $branchType) ?: 'market';
    $sql = "SELECT id, slug, typ, stredisko, galerie_id, nazev_cz, nazev_en, mobil, email, adresa, gps, vedouci, image, sluzby_cz, sluzby_en
            FROM pobocky
            WHERE typ = :typ AND valid = 1";
    $params = [':typ' => $branchType];
    if ($branchId > 0) {
        $sql .= ' AND id = :id';
        $params[':id'] = $branchId;
    }
    $sql .= ' ORDER BY nazev_cz ASC, id ASC';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $key === ':id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($branchId <= 0) {
        $rows = array_values(array_filter($rows, static function (array $row) use ($slug, $lang): bool {
            $rowSlug = trim((string)($row['slug'] ?? ''));
            if ($rowSlug !== '' && $rowSlug === $slug) {
                return true;
            }

            $name = frontend_markety_localized($row, 'nazev', $lang);
            return frontend_markety_slug($name, (int)$row['id']) === $slug || frontend_markety_slug_base($name) === $slug;
        }));
    }

    $row = $rows[0] ?? null;
    if (!is_array($row)) {
        return null;
    }

    $id = (int)$row['id'];
    $name = frontend_markety_localized($row, 'nazev', $lang);
    $gps = frontend_markety_parse_gps((string)($row['gps'] ?? ''));
    $address = frontend_markety_plain($row['adresa'] ?? '', 220);
    $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
    $tomorrow = $today->modify('+1 day');
    $openingRows = frontend_markety_opening_rows([$id]);
    $openingExceptions = frontend_markety_opening_exceptions([$id], $today->format('Y-m-d'));
    $tomorrowOpeningExceptions = frontend_markety_opening_exceptions([$id], $tomorrow->format('Y-m-d'));
    $todayException = $openingExceptions[$id] ?? null;
    $todayOpening = frontend_markety_today_opening(
        $openingRows[$id] ?? [],
        $lang,
        $todayException,
        $today,
        $tomorrowOpeningExceptions[$id] ?? null
    );

    return [
        'id' => $id,
        'stredisko' => (int)($row['stredisko'] ?? 0),
        'gallery_id' => (int)($row['galerie_id'] ?? 0),
        'name' => $name,
        'slug' => trim((string)($row['slug'] ?? '')) !== '' ? trim((string)$row['slug']) : frontend_markety_slug_base($name),
        'address' => $address,
        'city' => frontend_markety_city($address, $name),
        'image' => frontend_markety_file_url($row['image'] ?? ''),
        'phone' => trim((string)($row['mobil'] ?? '')),
        'email' => trim((string)($row['email'] ?? '')),
        'manager' => trim((string)($row['vedouci'] ?? '')),
        'services' => frontend_markety_services($row['sluzby_' . ($lang === 'en' ? 'en' : 'cz')] ?? ($row['sluzby_cz'] ?? '')),
        'opening_week' => frontend_markety_opening_week($openingRows[$id] ?? [], $lang, $todayException, $today),
        'opening_label' => (string)$todayOpening['label'],
        'opening_time_today' => (string)($todayOpening['time'] ?? ''),
        'opening_note_today' => (string)($todayOpening['note'] ?? ''),
        'opening_has_exception_today' => (bool)($todayOpening['is_exception'] ?? false),
        'is_open' => (bool)$todayOpening['is_open'],
        'lat' => $gps['lat'] ?? null,
        'lon' => $gps['lon'] ?? null,
    ];
}

function frontend_markety_jobs(int $stredisko, string $lang = 'cz', int $limit = 8): array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $stredisko <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT m.id, m.pocet, m.nazev_cz, m.nazev_en, t.nazev_cz AS typ_nazev_cz, t.nazev_en AS typ_nazev_en
         FROM rep_volna_mista m
         INNER JOIN rep_volna_mista_typ t ON t.id = m.typ_id
         WHERE m.valid = 1 AND m.visible = 1
           AND t.valid = 1 AND t.visible = 1
           AND t.stredisko_kod = :stredisko
         ORDER BY m.nazev_cz ASC, m.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':stredisko', $stredisko, PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(30, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    $jobs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $jobs[] = [
            'id' => (int)$row['id'],
            'title' => frontend_markety_localized($row, 'nazev', $lang),
            'location' => frontend_markety_localized([
                'nazev_cz' => $row['typ_nazev_cz'] ?? '',
                'nazev_en' => $row['typ_nazev_en'] ?? '',
            ], 'nazev', $lang),
            'count' => (int)$row['pocet'],
            'url' => '/' . rawurlencode($lang) . '/kariera?pozice=' . (int)$row['id'],
        ];
    }

    return $jobs;
}

function frontend_markety_flyers(string $lang = 'cz', int $limit = 3, string $typeCode = 'markety'): array
{
    global $pdo;

    if (!($pdo instanceof PDO) || !function_exists('frontend_akce_item_from_row')) {
        return [];
    }

    $typeCode = preg_replace('~[^a-z0-9_-]+~i', '', $typeCode) ?: 'markety';

    try {
        $stmt = $pdo->prepare(
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
               AND t.code = :type_code
               AND a.datum_do IS NOT NULL
               AND a.datum_do >= CURDATE()
               AND (a.datum_od IS NULL OR a.datum_od <= CURDATE())
             ORDER BY a.datum_do ASC, a.datum_od ASC, a.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':type_code', $typeCode, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(12, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }

    $items = [];
    foreach ($rows as $row) {
        $item = frontend_akce_item_from_row($row, $lang, 'valid');
        if ($item !== null) {
            if (function_exists('frontend_akce_offer_detail')) {
                $detail = frontend_akce_offer_detail((int)$item['id'], $lang);
                if (is_array($detail)) {
                    $item['pages'] = (array)($detail['pages'] ?? []);
                    $item['pdf'] = (string)($detail['pdf'] ?? $item['pdf']);
                }
            }
            $items[] = $item;
        }
    }

    return $items;
}

/** @param array<int, array<string, mixed>> $branches */
function frontend_markety_map_points(array $branches): array
{
    $points = [];
    foreach ($branches as $branch) {
        if (!is_numeric($branch['lat'] ?? null) || !is_numeric($branch['lon'] ?? null)) {
            continue;
        }

        $points[] = [
            'id' => (int)$branch['id'],
            'title' => (string)$branch['name'],
            'city' => (string)$branch['city'],
            'address' => (string)$branch['address'],
            'opening' => (string)$branch['opening_label'],
            'lat' => (float)$branch['lat'],
            'lon' => (float)$branch['lon'],
        ];
    }

    return $points;
}
