<?php
declare(strict_types=1);

$marketSectionConfig = [
    'route' => 'prodejny',
    'branch_type' => 'prodejna',
    'flyer_type' => 'qantoplus',
    'title_key' => 'prodejny.title',
    'router_title_key' => 'router.qantoplus.title',
    'router_text_code' => 'home.router.qantoplus.text',
    'intro_code' => 'qantoplus_intro',
    'text_code' => 'qantoplus_text',
    'router_number' => '03',
    'router_theme' => 'cream',
    'router_logo' => '/img/design/logo_qantoplus_router.png',
    'router_logo_alt_key' => 'router.qantoplus.title',
    'fallback_image' => '/img/design/logo_qantoplus_router.png',
    'finder_title_key' => 'prodejny.finder_title',
    'finder_text_key' => 'prodejny.finder_text',
    'empty_key' => 'prodejny.empty',
    'filter_empty_key' => 'prodejny.filter_empty',
    'map_empty_key' => 'prodejny.map_empty',
    'map_branch_key' => 'prodejny.map_branch',
    'detail_link_key' => 'prodejny.detail_link',
    'detail_not_found_key' => 'prodejny.detail_not_found',
];

include __DIR__ . '/markety.php';
