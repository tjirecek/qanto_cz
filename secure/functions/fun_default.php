<?php
use Random\RandomException;

// Převedeno na PDO - bezpečnější a modernější

// funkce pro logování přihlášení
/** @noinspection PhpUnhandledExceptionInspection */
function log_users(string $login, int $web): void
{
    global $pdo;

    $ip = $_SERVER["REMOTE_ADDR"] ?? '';
    $datum = (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))
        ->format('d.m.Y-H.i.s');

    $sql = 'INSERT INTO log_users (login, ip, datum, web)
            VALUES (:login, :ip, :datum, :web)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':login' => $login,
        ':ip'    => $ip,
        ':datum' => $datum,
        ':web'   => $web
    ]);
}

// funkce pro zjištění podpory EN v administraci
function en_on(): int
{
    // používáme jednotnou funkci sp_hodnota()
    return (int)(sp_hodnota('admin_en') ?? 0);
}

function get_date(): string
{
    return date("d.m.Y");
}

function get_date_file(): string
{
    return date("d-m-Y");
}

function format_date_db(string $datum): ?string
{
    // očekává dd.mm.yyyy
    if (preg_match('~^([0-9]{1,2})\\.([0-9]{1,2})\\.([0-9]{4})$~', $datum, $match)) {
        return sprintf("%d-%02d-%02d", (int)$match[3], (int)$match[2], (int)$match[1]);
    }
    return null; // když to nesedí
}

function format_date_www(string $datum): string
{
    // očekává yyyy-mm-dd
    return preg_replace('~^([0-9]{4})-0?([0-9]{1,2})-0?([0-9]{1,2}).*$~', '$3.$2.$1', $datum);
}

function format_datetime_www(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    return date('d.m.Y H:i:s', $ts);
}

function format_datetime_db(string $datetime): string
{
    $ts = strtotime(str_replace('.', '-', $datetime));
    if ($ts === false) {
        return $datetime;
    }
    return date("Y-m-d H:i:s", $ts);
}

// funkce pro přepis českých znaků do URL-safe tvaru
function text_str(string $name): string
{
    $convertTable = [
        'á' => 'a', 'Á' => 'A', 'ä' => 'a', 'Ä' => 'A', 'č' => 'c',
        'Č' => 'C', 'ď' => 'd', 'Ď' => 'D', 'é' => 'e', 'É' => 'E',
        'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'í' => 'i',
        'Í' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ľ' => 'l', 'Ľ' => 'L',
        'ĺ' => 'l', 'Ĺ' => 'L', 'ň' => 'n', 'Ň' => 'N', 'ń' => 'n',
        'Ń' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ö' => 'o', 'Ö' => 'O',
        'ř' => 'r', 'Ř' => 'R', 'ŕ' => 'r', 'Ŕ' => 'R', 'š' => 's',
        'Š' => 'S', 'ś' => 's', 'Ś' => 'S', 'ť' => 't', 'Ť' => 'T',
        'ú' => 'u', 'Ú' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ü' => 'u',
        'Ü' => 'U', 'ý' => 'y', 'Ý' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y',
        'ž' => 'z', 'Ž' => 'Z', 'ź' => 'z', 'Ź' => 'Z',
    ];

    $name_str = strtr($name, $convertTable);
    $name_str = preg_replace('~[^\pL0-9_.]+~u', '-', $name_str);
    $name_str = trim((string)$name_str, "-");

    $conv = iconv("utf-8", "us-ascii//TRANSLIT", $name_str);
    if ($conv !== false) {
        $name_str = $conv;
    }

    $name_str = strtolower($name_str);
    $name_str = preg_replace('~[^-a-z0-9_.]+~', '', $name_str);

    return (string)$name_str;
}

// hodnota systémové proměnné (číslo)
function sp_hodnota(string $sp): ?string
{
    global $pdo;

    $sql = 'SELECT hodnota
            FROM settings
            WHERE name = :name AND valid = 1
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $sp]);

    $val = $stmt->fetchColumn();
    return ($val === false) ? null : (string)$val;
}

// hodnota systémové proměnné (text)
function sp_hodnota_text(string $sp): ?string
{
    global $pdo;

    $sql = 'SELECT hodnota_text
            FROM settings
            WHERE name = :name AND valid = 1
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $sp]);

    $val = $stmt->fetchColumn();
    return ($val === false) ? null : (string)$val;
}

// max šířka/orig, výška/orig, šířka thumb, výška thumb
// (ponechávám funkce kvůli kompatibilitě se starým kódem, ale tahám to přes sp_hodnota)
function galerie_orig_width(): int
{
    return (int)(sp_hodnota('galerie_orig_width') ?? 0);
}

function galerie_orig_height(): int
{
    return (int)(sp_hodnota('galerie_orig_height') ?? 0);
}

function galerie_thumb_width(): int
{
    return (int)(sp_hodnota('galerie_thumb_width') ?? 0);
}

function galerie_thumb_height(): int
{
    return (int)(sp_hodnota('galerie_thumb_height') ?? 0);
}

// generace hesla

function generace_hesla(int $length): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = mb_strlen($chars);

    $result = '';
    for ($i = 0; $i < $length; $i++) {
        try {
            $index = random_int(0, $count - 1);
        } catch (RandomException) {
            // fallback – aplikace nikdy nespadne
            $index = mt_rand(0, $count - 1);
        }
        $result .= mb_substr($chars, $index, 1);
    }

    return $result;
}

// přehled 0/1 na NE/ANO
function anone(int $hodnota): string
{
    return ($hodnota === 0) ? "NE" : "ANO";
}
