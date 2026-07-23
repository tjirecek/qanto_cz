<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
define('ROOT_DIR', $rootDir);
define('BASE_URL', '/');

require_once $rootDir . '/secure/functions/fun_rep_akce.php';

date_default_timezone_set('Europe/Prague');

$run = in_array('--run', $argv, true);
$limit = 0;
$offerId = 0;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int)substr($arg, 8));
    }
    if (str_starts_with($arg, '--offer=')) {
        $offerId = max(0, (int)substr($arg, 8));
    }
}

$config = parse_ini_file($rootDir . '/ini/config_local.ini', false, INI_SCANNER_TYPED);
if (!is_array($config)) {
    fwrite(STDERR, "Nelze nacist ini/config_local.ini\n");
    exit(1);
}

$host = (string)($config['host'] ?? '127.0.0.1');
$port = (int)($config['port'] ?? 3306);
$user = (string)($config['user'] ?? '');
$password = (string)($config['password'] ?? '');
$dbName = (string)($config['dbname'] ?? '');
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $password, $options);

$sql = 'SELECT s.*, a.nazev_cz
        FROM rep_akce_strany s
        INNER JOIN rep_akce a ON a.id = s.akce_id
        WHERE s.image_path LIKE :legacy_prefix';
$params = [':legacy_prefix' => '_files/akce_old/%'];
if ($offerId > 0) {
    $sql .= ' AND s.akce_id = :offer_id';
    $params[':offer_id'] = $offerId;
}
$sql .= ' ORDER BY s.akce_id ASC, s.poradi ASC, s.id ASC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll() ?: [];

if (!$run) {
    echo "DRY RUN - pro zapis pouzij --run.\n";
}

$checked = 0;
$copied = 0;
$missing = 0;
$errors = 0;
$affectedOffers = [];
$update = $pdo->prepare('UPDATE rep_akce_strany SET image_file = :image_file, image_path = :image_path, mime_type = :mime_type, width = :width, height = :height, filesize = :filesize, user_u = :user_u WHERE id = :id');

foreach ($pages as $page) {
    $checked++;
    $sourceRelative = ltrim((string)$page['image_path'], '/');
    $sourceAbsolute = $rootDir . '/' . $sourceRelative;
    if (!is_file($sourceAbsolute)) {
        $missing++;
        continue;
    }

    if (!$run) {
        $copied++;
        continue;
    }

    try {
        $info = @getimagesize($sourceAbsolute);
        if ($info === false) {
            throw new RuntimeException('nepodporovany obrazek');
        }
        $mime = strtolower((string)($info['mime'] ?? ''));
        $extension = rep_akce_image_extension($mime);
        if ($extension === null) {
            throw new RuntimeException('nepodporovany MIME ' . $mime);
        }
        $offerIdCurrent = (int)$page['akce_id'];
        $title = (string)($page['nazev_cz'] ?? 'akce');
        $relativeDir = rep_akce_ensure_pages_dir($offerIdCurrent, $title);
        $baseName = rep_akce_slug(pathinfo((string)$page['image_file'], PATHINFO_FILENAME), 'strana');
        $order = max(1, (int)$page['poradi']);
        $targetName = sprintf('%04d-%s.%s', $order, $baseName, $extension);
        $targetRelative = $relativeDir . '/' . $targetName;
        $targetAbsolute = $rootDir . '/' . $targetRelative;
        $suffix = 1;
        while (is_file($targetAbsolute)) {
            $targetName = sprintf('%04d-%s-%d.%s', $order, $baseName, $suffix, $extension);
            $targetRelative = $relativeDir . '/' . $targetName;
            $targetAbsolute = $rootDir . '/' . $targetRelative;
            $suffix++;
        }
        if (!copy($sourceAbsolute, $targetAbsolute)) {
            throw new RuntimeException('kopirovani selhalo');
        }
        $update->execute([
            ':image_file' => $targetName,
            ':image_path' => $targetRelative,
            ':mime_type' => $mime,
            ':width' => (int)($info[0] ?? 0),
            ':height' => (int)($info[1] ?? 0),
            ':filesize' => (int)filesize($targetAbsolute),
            ':user_u' => 'legacy-images-import',
            ':id' => (int)$page['id'],
        ]);
        $affectedOffers[$offerIdCurrent] = $offerIdCurrent;
        $copied++;
    } catch (Throwable $e) {
        $errors++;
        echo sprintf("ERR page #%d offer #%d: %s\n", (int)$page['id'], (int)$page['akce_id'], $e->getMessage());
    }
}

if ($run && $affectedOffers !== []) {
    foreach (array_keys($affectedOffers) as $id) {
        rep_akce_set_cover_from_first_page($pdo, (int)$id);
    }
}

echo "Souhrn:\n";
echo "- zkontrolovano: {$checked}\n";
echo "- pripraveno/zkopirovano: {$copied}\n";
echo "- chybi zdroj: {$missing}\n";
echo "- dotcene akce: " . count($affectedOffers) . "\n";
echo "- chyby: {$errors}\n";
