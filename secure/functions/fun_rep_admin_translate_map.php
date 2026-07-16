<?php
declare(strict_types=1);

function rep_admin_translate_field_maps(): array
{
    return [
        'rep_volna_mista.job' => [
            'layer' => 'project',
            'table' => 'rep_volna_mista',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'html', 'label' => 'Popis'],
            ],
        ],
        'rep_volna_mista.type' => [
            'layer' => 'project',
            'table' => 'rep_volna_mista_typ',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'text', 'label' => 'Popis / adresa'],
            ],
        ],
        'rep_akce.offer' => [
            'layer' => 'project',
            'table' => 'rep_akce',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
                'text' => ['cz' => 'text_cz', 'en' => 'text_en', 'format' => 'html', 'label' => 'Text'],
            ],
        ],
        'rep_akce.type' => [
            'layer' => 'project',
            'table' => 'rep_akce_typ',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'nazev' => ['cz' => 'nazev_cz', 'en' => 'nazev_en', 'format' => 'text', 'label' => 'Název'],
            ],
        ],
        'rep_bannery.banner' => [
            'layer' => 'project',
            'table' => 'rep_bannery',
            'primary_key' => 'id',
            'manual_flag' => 'auto_translate_en',
            'fields' => [
                'popis' => ['cz' => 'popis_cz', 'en' => 'popis_en', 'format' => 'text', 'label' => 'Popis'],
                'link_text' => ['cz' => 'link_text_cz', 'en' => 'link_text_en', 'format' => 'text', 'label' => 'Text odkazu'],
            ],
        ],
    ];
}
