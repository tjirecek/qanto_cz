<?php
declare(strict_types=1);

/**
 * fun_default.php (PDO verze)
 * - očekává globální $pdo (z mysql_connect.php)
 */

/**
 * Log přihlášení
 */
function log_users(string $login, int $web): bool
{
    global $pdo;

    $login = trim($login);
    if ($login === '') {
        return false;
    }

    $web = ($web === 1) ? 1 : 0;

    // IP (nejjednodušší varianta; pokud máš reverse proxy, dá se rozšířit o X-Forwarded-For)
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') {
        $ip = 'unknown';
    }

    // držím původní formát datum sloupce (d.m.Y-H.i.s)
    $datum = (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('d.m.Y-H.i.s');

    $stmt = $pdo->prepare(
        'INSERT INTO log_users (login, ip, datum, web)
         VALUES (:login, :ip, :datum, :web)'
    );

    return $stmt->execute([
        ':login' => $login,
        ':ip'    => $ip,
        ':datum' => $datum,
        ':web'   => $web,
    ]);
}

/**
 * Hodnota systémové proměnné (číselná / string)
 * - vrací string (stejně jako dřív), nebo null pokud nenalezeno
 */
function sp_hodnota(string $sp): ?string
{
    global $pdo;

    $sp = trim($sp);
    if ($sp === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT hodnota
         FROM settings
         WHERE name = :name AND valid = 1
         LIMIT 1'
    );
    $stmt->execute([':name' => $sp]);

    $val = $stmt->fetchColumn();
    return ($val === false) ? null : (string)$val;
}

/**
 * Textová hodnota systémové proměnné
 * - vrací string (stejně jako dřív), nebo null pokud nenalezeno
 */
function sp_hodnota_text(string $sp): ?string
{
    global $pdo;

    $sp = trim($sp);
    if ($sp === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT hodnota_text
         FROM settings
         WHERE name = :name AND valid = 1
         LIMIT 1'
    );
    $stmt->execute([':name' => $sp]);

    $val = $stmt->fetchColumn();
    return ($val === false) ? null : (string)$val;
}

/**
 * Statický text dle stabilního kódu a jazyka.
 * - $field: "text" nebo "nazev"
 * - pro EN vrací fallback do CZ, pokud EN varianta není vyplněná
 */
function stat_text(string $code, string $lang = 'cz', string $field = 'text'): ?string
{
    global $pdo;

    $code = trim($code);
    if ($code === '') {
        return null;
    }

    if (!($pdo instanceof PDO)) {
        return null;
    }

    $lang = ($lang === 'en') ? 'en' : 'cz';
    $field = ($field === 'nazev') ? 'nazev' : 'text';

    $column = $field . '_' . $lang;
    $fallbackColumn = $field . '_cz';

    $stmt = $pdo->prepare(
        "SELECT {$column} AS val, {$fallbackColumn} AS fallback_val
         FROM stat_texty
         WHERE code = :code AND valid = 1
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([':code' => $code]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $value = trim((string)($row['val'] ?? ''));
    if ($value !== '') {
        return $value;
    }

    $fallbackValue = trim((string)($row['fallback_val'] ?? ''));
    return ($fallbackValue === '') ? null : $fallbackValue;
}

/**
 * Statický výraz dle stabilního kódu a jazyka.
 * Výraz může obsahovat HTML z TinyMCE; výstup escapuj jen tam, kde ho chceš použít jako plain text.
 */
function stat_vyraz(string $code, string $lang = 'cz'): ?string
{
    global $pdo;

    $code = trim($code);
    if ($code === '') {
        return null;
    }

    if (!($pdo instanceof PDO)) {
        return null;
    }

    $column = ($lang === 'en') ? 'en' : 'cz';

    $stmt = $pdo->prepare(
        "SELECT {$column} AS val, cz AS fallback_val
         FROM stat_vyrazy
         WHERE code = :code AND valid = 1
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([':code' => $code]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $value = trim((string)($row['val'] ?? ''));
    if ($value !== '') {
        return $value;
    }

    $fallbackValue = trim((string)($row['fallback_val'] ?? ''));
    return ($fallbackValue === '') ? null : $fallbackValue;
}

/**
 * Právo skupiny na menu (true/false)
 * - původně vracelo počet řádků; teď vracím bool (je přehlednější)
 * - pokud chceš zachovat int, změň return na (int)$stmt->fetchColumn()
 */
function menu(int $skup_id, int $menu): bool
{
    global $pdo;

    if ($skup_id <= 0 || $menu <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT 1
         FROM menu_users_skup
         WHERE valid = 1 AND skup_id = :skup_id AND menu = :menu
         LIMIT 1'
    );
    $stmt->execute([
        ':skup_id' => $skup_id,
        ':menu'    => $menu,
    ]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Jméno uživatele dle loginu
 * - vrací string nebo null
 */
function user_name(string $user): ?string
{
    global $pdo;

    $user = trim($user);
    if ($user === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT name
         FROM users
         WHERE login = :login AND valid = 1
         LIMIT 1'
    );
    $stmt->execute([':login' => $user]);

    $val = $stmt->fetchColumn();
    return ($val === false) ? null : (string)$val;
}

/**
 * Formátování datumu z "d.m.Y" na "Y-m-d"
 * - když to nesedí, vrátí null
 */
function format_date_db(string $datum): ?string
{
    $datum = trim($datum);
    if ($datum === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('!d.m.Y', $datum);
    if (!$dt) {
        return null;
    }

    return $dt->format('Y-m-d');
}

/**
 * Aktuální datum (d.m.Y)
 */
function get_date(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('d.m.Y');
}

/**
 * Aktuální datum (Y-m-d)
 */
function get_date_ymd(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('Y-m-d');
}

/**
 * Formát datetime pro web "d.m.Y H:i:s"
 * - když to nepůjde parse, vrátí původní string
 */
function format_datetime_www(string $datetime): string
{
    $datetime = trim($datetime);
    if ($datetime === '') {
        return '';
    }

    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }

    return date('d.m.Y H:i:s', $ts);
}

/**
 * Formát "Y-m-d" => "d.m.Y"
 * - když to nesedí, vrátí původní string
 */
function format_date_www(string $datum): string
{
    $datum = trim($datum);
    if ($datum === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);
    if (!$dt) {
        return $datum;
    }

    return $dt->format('d.m.Y');
}

/**
 * Slugify (původní logika zachovaná, jen bez global $mysqli)
 */
function text_str(string $name): string
{
    $name_str = trim($name);
    if ($name_str === '') {
        return '';
    }

    $name_str = preg_replace('~[^\pL0-9_.]+~u', '-', $name_str);
    $name_str = trim((string)$name_str, '-');

    $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $name_str);
    if ($converted !== false) {
        $name_str = $converted;
    }

    $name_str = strtolower($name_str);
    $name_str = preg_replace('~[^-a-z0-9_.]+~', '', $name_str);

    return (string)$name_str;
}

/**
 * 0/1 => NE/ANO
 */
function anone(int $hodnota): string
{
    return ($hodnota === 0) ? 'NE' : 'ANO';
}
