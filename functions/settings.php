<?php
declare(strict_types=1);

/**
 * settings.php (OSTRÁ VERZE)
 * Řeší volbu jazyka přes GET ?lang=cz|en
 * Pokud lang chybí, přesměruje na /cz/index nebo /en/index (default cz).
 */

$supportedLangs = ['cz', 'en'];
$defaultLang    = 'cz';

if (!isset($_GET['lang']) || $_GET['lang'] === '') {
    // tady můžeš mít do budoucna autodetekci; teď držíme původní chování
    $lang = $defaultLang;

    header('Location: /' . $lang . '', true, 302);
    exit;
}

$langRaw = strtolower(trim((string)$_GET['lang']));

// whitelist – žádné escapování, žádné SQL
$lang = in_array($langRaw, $supportedLangs, true) ? $langRaw : $defaultLang;

// pokud přišel nepodporovaný lang, můžeš taky přesměrovat na default
if ($langRaw !== $lang) {
    header('Location: /' . $lang . '', true, 302);
    exit;
}