<?php
declare(strict_types=1);

global $word, $lang;

$section = trim((string)($_GET['section'] ?? ''));
if ($section === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $parts = array_values(array_filter(explode('/', trim($path, '/'))));
    $langFromPath = (string)($parts[0] ?? '');
    $section = in_array($langFromPath, ['cz', 'en'], true) ? (string)($parts[1] ?? 'main') : (string)($parts[0] ?? 'main');
}

$routes = [
    'main' => [
        'page' => 'main',
        'title_key' => 'page.main.title',
        'menu' => 100,
        'description_key' => 'page.main.description',
    ],
    'akce' => [
        'page' => 'akce',
        'title_key' => 'page.akce.title',
        'menu' => 200,
        'description_key' => 'page.akce.description',
    ],
    'news' => [
        'page' => 'news',
        'title_key' => 'page.news.title',
        'menu' => 300,
        'description_key' => 'page.news.description',
    ],
    'markety' => [
        'page' => 'markety',
        'title_key' => 'page.markety.title',
        'menu' => 210,
        'description_key' => 'page.markety.description',
    ],
    'velkoobchod' => [
        'page' => 'velkoobchod',
        'title_key' => 'page.velkoobchod.title',
        'menu' => 220,
        'description_key' => 'page.velkoobchod.description',
    ],
    'prodejny' => [
        'page' => 'prodejny',
        'title_key' => 'page.prodejny.title',
        'menu' => 230,
        'description_key' => 'page.prodejny.description',
    ],
    'kariera' => [
        'page' => 'kariera',
        'title_key' => 'page.kariera.title',
        'menu' => 400,
        'description_key' => 'page.kariera.description',
    ],
    'brigada' => [
        'page' => 'brigada',
        'title_key' => 'page.brigada.title',
        'menu' => 405,
        'description_key' => 'page.brigada.description',
        'current_section' => 'kariera',
    ],
    'brigada-mo' => [
        'page' => 'brigada',
        'title_key' => 'page.brigada.title',
        'menu' => 405,
        'description_key' => 'page.brigada.description',
        'current_section' => 'kariera',
    ],
    'brigada-vo' => [
        'page' => 'brigada',
        'title_key' => 'page.brigada.title',
        'menu' => 405,
        'description_key' => 'page.brigada.description',
        'current_section' => 'kariera',
    ],
    'ples' => [
        'page' => 'placeholder',
        'title_key' => 'page.ples.title',
        'menu' => 0,
        'description_key' => 'page.ples.description',
    ],
    'tenisqcup' => [
        'page' => 'placeholder',
        'title_key' => 'page.tenisqcup.title',
        'menu' => 0,
        'description_key' => 'page.tenisqcup.description',
    ],
    'inventury' => [
        'page' => 'placeholder',
        'title_key' => 'page.inventury.title',
        'menu' => 0,
        'description_key' => 'page.inventury.description',
    ],
    'nase-znacky' => [
        'page' => 'placeholder',
        'title_key' => 'page.nase_znacky.title',
        'menu' => 450,
        'description_key' => 'page.nase_znacky.description',
    ],
    'o-nas' => [
        'page' => 'static_text',
        'title_key' => 'page.o_nas.title',
        'menu' => 500,
        'description_key' => 'page.o_nas.description',
        'static_text_code' => 'o_nas',
    ],
    'historie' => [
        'page' => 'static_text',
        'title_key' => 'page.historie.title',
        'menu' => 510,
        'description_key' => 'page.historie.description',
        'static_text_code' => 'history',
    ],
    'media' => [
        'page' => 'static_text',
        'title_key' => 'page.media.title',
        'menu' => 515,
        'description_key' => 'page.media.description',
        'static_text_code' => 'media',
    ],
    'podporujeme' => [
        'page' => 'static_text',
        'title_key' => 'page.podporujeme.title',
        'menu' => 520,
        'description_key' => 'page.podporujeme.description',
        'static_text_code' => 'podporujeme',
    ],
    'kontakt' => [
        'page' => 'kontakty',
        'title_key' => 'page.kontakt.title',
        'menu' => 600,
        'description_key' => 'page.kontakt.description',
        'current_section' => 'kontakty',
    ],
    'kontakty' => [
        'page' => 'kontakty',
        'title_key' => 'page.kontakt.title',
        'menu' => 600,
        'description_key' => 'page.kontakt.description',
        'current_section' => 'kontakty',
    ],
    'napiste-nam' => [
        'page' => 'placeholder',
        'title_key' => 'page.napiste_nam.title',
        'menu' => 610,
        'description_key' => 'page.napiste_nam.description',
    ],
    'volani' => [
        'page' => 'volani',
        'title_key' => 'page.volani.title',
        'menu' => 0,
        'description_key' => 'page.volani.description',
    ],
    'obchodni-podminky' => [
        'page' => 'static_text',
        'title_key' => 'page.obchodni_podminky.title',
        'menu' => 0,
        'description_key' => 'page.obchodni_podminky.description',
        'static_text_code' => 'obchodni_podminky',
    ],
    'elektronicka-vymena-dat' => [
        'page' => 'static_text',
        'title_key' => 'page.elektronicka_vymena_dat.title',
        'menu' => 0,
        'description_key' => 'page.elektronicka_vymena_dat.description',
        'static_text_code' => 'elektronicka_vymena_dat',
    ],
    'zakaznicke-karty' => [
        'page' => 'static_text',
        'title_key' => 'page.zakaznicke_karty.title',
        'menu' => 0,
        'description_key' => 'page.zakaznicke_karty.description',
        'static_text_code' => 'zakaznicke_karty',
    ],
    'osobni-udaje' => [
        'page' => 'static_text',
        'title_key' => 'page.privacy.title',
        'menu' => 0,
        'description_key' => 'page.privacy.description',
        'static_text_code' => 'osobni_udaje',
    ],
    'cookies' => [
        'page' => 'static_text',
        'title_key' => 'page.cookies.title',
        'menu' => 0,
        'description_key' => 'page.cookies.description',
        'static_text_code' => 'cookies',
    ],
];

if (!isset($routes[$section])) {
    http_response_code(404);
    $section = 'main';
}

$route = $routes[$section];
$detailSlug = isset($parts[2]) ? rawurldecode((string)$parts[2]) : '';
if ($detailSlug !== '' && in_array($section, ['markety', 'prodejny'], true) && function_exists('frontend_markety_detail_by_slug')) {
    $detailType = $section === 'prodejny' ? 'prodejna' : 'market';
    if (frontend_markety_detail_by_slug($detailSlug, (string)($lang ?? 'cz'), $detailType) === null) {
        http_response_code(404);
    }
}
$page = (string)$route['page'];
$pagetitle = ui_text((string)$route['title_key'], 'Qanto');
$menu = (int)$route['menu'];
$metaDescription = ui_text((string)$route['description_key'], (string)($word[3] ?? ''));
$currentSection = (string)($route['current_section'] ?? $section);
$staticTextCode = (string)($route['static_text_code'] ?? '');
