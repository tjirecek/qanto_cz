<?php
declare(strict_types=1);

$supportedLangs = ['cz', 'en'];
$defaultLang = 'cz';

$langRaw = strtolower(trim((string)($_GET['lang'] ?? '')));
if ($langRaw === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $parts = array_values(array_filter(explode('/', trim($path, '/'))));
    $langRaw = strtolower((string)($parts[0] ?? ''));
    if ($langRaw !== '' && in_array($langRaw, $supportedLangs, true)) {
        $_GET['lang'] = $langRaw;
    }
}

if ($langRaw === '') {
    header('Location: /' . $defaultLang, true, 302);
    exit;
}

$lang = in_array($langRaw, $supportedLangs, true) ? $langRaw : $defaultLang;
if ($langRaw !== $lang) {
    header('Location: /' . $lang, true, 302);
    exit;
}
