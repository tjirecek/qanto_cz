<?php
declare(strict_types=1);

$adminTranslateProjectMapFile = __DIR__ . '/fun_rep_admin_translate_map.php';
if (is_file($adminTranslateProjectMapFile)) {
    require_once $adminTranslateProjectMapFile;
}

/**
 * Explicit CZ -> EN translation maps.
 *
 * Do not infer translatable fields only from *_cz / *_en suffixes. Some paired
 * fields are technical identifiers (URLs, slugs) and must stay under agenda
 * control.
 *
 * Field format:
 * - text: plain text input/textarea
 * - html: TinyMCE or trusted HTML textarea, translated with DeepL tag handling
 */

function admin_translate_shared_field_maps(): array
{
    return [
        'news.record' => [
            'layer' => 'shared',
            'table' => 'news',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'perex' => ['cz' => 'perex_cz', 'en' => 'perex_en', 'format' => 'html', 'label' => 'Perex'],
                'text' => ['cz' => 'text_cz', 'en' => 'text_en', 'format' => 'html', 'label' => 'Text'],
                'seo_title' => ['cz' => 'seo_title_cz', 'en' => 'seo_title_en', 'format' => 'text', 'label' => 'SEO titulek'],
                'seo_description' => ['cz' => 'seo_description_cz', 'en' => 'seo_description_en', 'format' => 'text', 'label' => 'SEO popis'],
            ],
            'excluded_pairs' => [
                'url_cz/url_en' => 'URL se generuje a validuje samostatně, není prostý překlad.',
            ],
        ],
        'news.type' => [
            'layer' => 'shared',
            'table' => 'news_typ',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'text', 'label' => 'Popis'],
            ],
        ],
        'news.tag' => [
            'layer' => 'shared',
            'table' => 'news_tag',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
            ],
            'excluded_pairs' => [
                'slug_cz/slug_en' => 'Slug se generuje a validuje samostatně, není prostý překlad.',
            ],
        ],
        'stat_texty.record' => [
            'layer' => 'shared',
            'table' => 'stat_texty',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'text' => ['cz' => 'text_cz', 'en' => 'text_en', 'format' => 'html', 'label' => 'Text'],
            ],
        ],
        'stat_vyrazy.record' => [
            'layer' => 'shared',
            'table' => 'stat_vyrazy',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'vyraz' => ['cz' => 'cz', 'en' => 'en', 'format' => 'text', 'label' => 'Výraz'],
            ],
        ],
        'ui_texty.record' => [
            'layer' => 'shared',
            'table' => 'ui_texty',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'text' => ['cz' => 'cz', 'en' => 'en', 'format' => 'text', 'label' => 'UI text'],
            ],
        ],
        'galerie.type' => [
            'layer' => 'shared',
            'table' => 'galerie_typ',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'text', 'label' => 'Popis'],
            ],
        ],
        'galerie.record' => [
            'layer' => 'shared',
            'table' => 'galerie',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'html', 'label' => 'Popis'],
            ],
        ],
        'galerie.photo' => [
            'layer' => 'shared',
            'table' => 'galerie_photo',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
            ],
        ],
        'pobocky.record' => [
            'layer' => 'shared',
            'table' => 'pobocky',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'sluzby' => ['cz' => 'sluzby_cz', 'en' => 'sluzby_en', 'format' => 'html', 'label' => 'Služby'],
            ],
        ],
        'pobocky.opening_hours' => [
            'layer' => 'shared',
            'table' => 'pobocky_otevdoba',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'poznamka' => ['cz' => 'poznamka_cz', 'en' => 'poznamka_en', 'format' => 'text', 'label' => 'Poznámka'],
            ],
        ],
        'pobocky.opening_hours_exception' => [
            'layer' => 'shared',
            'table' => 'pobocky_otevdoba_vyjimky',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'poznamka' => ['cz' => 'poznamka_cz', 'en' => 'poznamka_en', 'format' => 'text', 'label' => 'Poznámka'],
            ],
        ],
        'obchodni_zastupci.record' => [
            'layer' => 'shared',
            'table' => 'obchodni_zastupci',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'html', 'label' => 'Popis'],
            ],
        ],
        'kontakty_lide.person' => [
            'layer' => 'shared',
            'table' => 'kontakty_lide',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'funkce' => ['cz' => 'funkce_cz', 'en' => 'funkce_en', 'format' => 'text', 'label' => 'Funkce'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'html', 'label' => 'Popis'],
            ],
        ],
        'kontakty_lide.group' => [
            'layer' => 'shared',
            'table' => 'kontakty_lide_skupiny',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
            ],
        ],
        'napiste_nam.category' => [
            'layer' => 'shared',
            'table' => 'napiste_nam_kategorie',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
            ],
        ],
    ];
}

function admin_translate_project_field_maps(): array
{
    if (function_exists('rep_admin_translate_field_maps')) {
        return rep_admin_translate_field_maps();
    }

    return [];
}

function admin_translate_field_maps(): array
{
    return admin_translate_shared_field_maps() + admin_translate_project_field_maps();
}

function admin_translate_field_map(string $context): ?array
{
    $maps = admin_translate_field_maps();

    return $maps[$context] ?? null;
}

function admin_translate_field_map_for_table(string $table): array
{
    return array_filter(
        admin_translate_field_maps(),
        static fn (array $map): bool => (string)($map['table'] ?? '') === $table
    );
}
