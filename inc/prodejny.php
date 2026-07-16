<?php
declare(strict_types=1);

$marketSectionConfig = [
    'route' => 'prodejny',
    'branch_type' => 'prodejna',
    'flyer_type' => 'qantoplus',
    'title_key' => 'prodejny.title',
    'title_fallback' => 'Prodejny',
    'router_title_key' => 'router.qantoplus.title',
    'router_title_fallback' => 'prodejny Qanto+',
    'router_text_code' => 'home.router.qantoplus.text',
    'intro_code' => 'qantoplus_intro',
    'text_code' => 'qantoplus_text',
    'router_number' => '03',
    'router_theme' => 'cream',
    'router_logo' => '/img/design/logo_qantoplus_router.png',
    'router_logo_alt' => 'Qanto+',
    'fallback_image' => '/img/design/logo_qantoplus_router.png',
    'finder_title_key' => 'prodejny.finder_title',
    'finder_title_fallback' => 'Najděte prodejnu Qanto+ podle města',
    'finder_text_key' => 'prodejny.finder_text',
    'finder_text_fallback' => 'Zobrazují se všechny dostupné prodejny Qanto+.',
    'empty_key' => 'prodejny.empty',
    'empty_fallback' => 'Aktuálně nejsou dostupné žádné prodejny.',
    'filter_empty_key' => 'prodejny.filter_empty',
    'filter_empty_fallback' => 'Pro vybrané město nejsou dostupné žádné prodejny.',
    'map_empty_key' => 'prodejny.map_empty',
    'map_empty_fallback' => 'Pro mapu nejsou dostupné souřadnice prodejen.',
    'map_branch_key' => 'prodejny.map_branch',
    'map_branch_fallback' => 'Qanto+',
    'detail_link_key' => 'prodejny.detail_link',
    'detail_link_fallback' => 'Detail prodejny',
    'detail_not_found_key' => 'prodejny.detail_not_found',
    'detail_not_found_fallback' => 'Prodejna nebyla nalezena.',
];

include __DIR__ . '/markety.php';
