<?php
declare(strict_types=1);

$main_menu = '';
$sec_text  = '';

$norm2 = static function ($val, string $default): string {
    if ($val === null || $val === '') {
        return $default;
    }

    return sprintf('%02d', (int)$val);
};

$adminUserPrava = admin_session_prava();

$defaultRoute = [
    'section' => '03',
    'page' => '01',
    'sec_page' => '01',
];

$routes = [
    '01' => [
        '_menu' => 'mm_all',
        '01' => [
            '02' => 'pages/news/news_vypis',
            '03' => 'pages/news/news_typ',
            '05' => 'pages/news/news_users',
            '06' => 'pages/news/news_info_send',
        ],
        '02' => [
            '02' => 'pages/stattexty/stattexty_vypis',
            '03' => 'pages/stattexty/statvyrazy_vypis',
        ],
        '03' => [
            '01' => 'pages/kontakty/prodejny',
            '02' => 'pages/kontakty/markety',
            '03' => 'pages/kontakty/velkoobchody',
            '04' => 'pages/kontakty/obchodni_zastupci',
            '05' => 'pages/kontakty/oteviraci_doby',
        ],
    ],
    '02' => [
        '_menu' => 'mm_system',
        '01' => [
            '02' => 'settings/users_vypis',
            '03' => 'settings/users_skup',
            '05' => 'settings/users_log',
        ],
        '02' => [
            '01' => 'settings/system_add',
            '02' => 'settings/system_vypis',
            '03' => 'settings/menu_vypis',
            '04' => 'settings/menu_users_skup',
            '05' => 'settings/cron_log',
            '06' => 'settings/cron_vypis',
            '07' => 'settings/changelog',
            '08' => 'settings/migrations',
            '09' => 'settings/email_log',
        ],
    ],
    '03' => [
        '_menu' => 'mm_dashboard',
        '01' => [
            '01' => 'dashboard/dashboard_main',
        ],
    ],
];

$allowedRoles = [1, 2];
$currentDefault = $defaultRoute;

$section = (string)$currentDefault['section'];
$page = (string)$currentDefault['page'];
$sec_page = (string)$currentDefault['sec_page'];

if (in_array($adminUserPrava, $allowedRoles, true)) {
    $section  = $norm2($_GET['section']  ?? null, $section);
    $page     = $norm2($_GET['page']     ?? null, $page);
    $sec_page = $norm2($_GET['sec_page'] ?? null, $sec_page);
}

if (isset($routes[$section])) {
    $main_menu = (string)($routes[$section]['_menu'] ?? '');
    $sec_text  = (string)($routes[$section][$page][$sec_page] ?? '');
}

if ($sec_text === '') {
    $section = (string)$currentDefault['section'];
    $page = (string)$currentDefault['page'];
    $sec_page = (string)$currentDefault['sec_page'];

    $main_menu = (string)($routes[$section]['_menu'] ?? 'mm_dashboard');
    $sec_text = (string)($routes[$section][$page][$sec_page] ?? 'dashboard/dashboard_main');
}

$GLOBALS['section']   = $section;
$GLOBALS['page']      = $page;
$GLOBALS['sec_page']  = $sec_page;
$GLOBALS['main_menu'] = $main_menu;
$GLOBALS['sec_text']  = $sec_text;
