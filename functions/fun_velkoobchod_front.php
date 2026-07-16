<?php
declare(strict_types=1);

function frontend_velkoobchod_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_velkoobchod_json(mixed $value): string
{
    $json = json_encode(
        $value,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    );

    return is_string($json) ? $json : '[]';
}

function frontend_velkoobchod_normalize_status(string $status): string
{
    return in_array($status, ['served', 'excluded', 'review', 'not_served'], true) ? $status : 'not_served';
}

function frontend_velkoobchod_safe_html(mixed $value): string
{
    $html = trim((string)$value);
    if ($html === '') {
        return '';
    }

    $html = preg_replace('~<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $html) ?? '';
    $html = strip_tags($html, '<strong><b><em><i><br><p><ul><ol><li>');
    $html = preg_replace('~<\s*(/?)\s*(strong|b|em|i|br|p|ul|ol|li)\b[^>]*>~i', '<$1$2>', $html) ?? '';

    return trim($html);
}

function frontend_velkoobchod_html_text(string $html): string
{
    return function_exists('plain_text') ? plain_text($html) : trim(strip_tags($html));
}

/** @return array<int, array<string, mixed>> */
function frontend_velkoobchod_branches(string $lang = 'cz'): array
{
    return function_exists('frontend_markety_list') ? frontend_markety_list($lang, 'velkoobchod') : [];
}

/** @param array<int, array<string, mixed>> $branches */
function frontend_velkoobchod_branch_points(array $branches): array
{
    $points = [];
    foreach ($branches as $branch) {
        if (!is_numeric($branch['lat'] ?? null) || !is_numeric($branch['lon'] ?? null)) {
            continue;
        }

        $points[] = [
            'id' => (int)$branch['id'],
            'title' => (string)$branch['name'],
            'city' => (string)$branch['city'],
            'address' => (string)$branch['address'],
            'lat' => (float)$branch['lat'],
            'lon' => (float)$branch['lon'],
        ];
    }

    return $points;
}

function frontend_velkoobchod_representative_contact(array $row): array
{
    $directName = trim((string)($row['oz_jmeno'] ?? ''));
    $directEmail = trim((string)($row['oz_email'] ?? ''));
    $directPhone = trim((string)($row['oz_mobil'] ?? ''));
    if ($directName !== '' || $directEmail !== '' || $directPhone !== '') {
        return [
            'source' => 'city',
            'name' => $directName,
            'email' => $directEmail,
            'phone' => $directPhone,
        ];
    }

    $districtName = trim((string)($row['okres_oz_jmeno'] ?? ''));
    $districtEmail = trim((string)($row['okres_oz_email'] ?? ''));
    $districtPhone = trim((string)($row['okres_oz_mobil'] ?? ''));
    if ($districtName !== '' || $districtEmail !== '' || $districtPhone !== '') {
        return [
            'source' => 'district',
            'name' => $districtName,
            'email' => $districtEmail,
            'phone' => $districtPhone,
        ];
    }

    return [
        'source' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
    ];
}

function frontend_velkoobchod_map_areas(): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return ['type' => 'FeatureCollection', 'features' => []];
    }

    $stmt = $pdo->query(
        'SELECT
            o.id AS obec_id, o.nazev, o.okres, o.kraj, o.psc_list, o.geojson,
            z.id AS zavoz_id, z.status, z.prodej,
            oz.jmeno AS oz_jmeno, oz.email AS oz_email, oz.mobil AS oz_mobil,
            okres_oz.jmeno AS okres_oz_jmeno, okres_oz.email AS okres_oz_email, okres_oz.mobil AS okres_oz_mobil
         FROM rep_zavoz_obce z
         INNER JOIN rep_cr_obce o ON o.id = z.obec_id
         LEFT JOIN obchodni_zastupci oz ON oz.id = z.obchodni_zastupce_id AND oz.valid = 1
         LEFT JOIN rep_cr_okresy ok ON ok.id = o.okres_id AND ok.valid = 1
         LEFT JOIN obchodni_zastupci okres_oz ON okres_oz.id = ok.obchodni_zastupce_id AND okres_oz.valid = 1
         WHERE z.valid = 1
           AND z.status = "served"
           AND o.valid = 1
           AND o.geojson IS NOT NULL
           AND o.geojson <> ""
         ORDER BY z.prodej DESC, o.nazev ASC'
    );

    $features = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $geometry = json_decode((string)($row['geojson'] ?? ''), true);
        if (!is_array($geometry) || !isset($geometry['type'], $geometry['coordinates'])) {
            continue;
        }

        $features[] = [
            'type' => 'Feature',
            'properties' => [
                'id' => (int)($row['obec_id'] ?? 0),
                'zavozId' => (int)($row['zavoz_id'] ?? 0),
                'name' => (string)($row['nazev'] ?? ''),
                'okres' => (string)($row['okres'] ?? ''),
                'kraj' => (string)($row['kraj'] ?? ''),
                'psc' => (string)($row['psc_list'] ?? ''),
                'prodej' => (float)($row['prodej'] ?? 0),
                'contact' => frontend_velkoobchod_representative_contact($row),
            ],
            'geometry' => $geometry,
        ];
    }

    return [
        'type' => 'FeatureCollection',
        'features' => $features,
    ];
}

/** @return array<int, array<string, mixed>> */
function frontend_velkoobchod_availability_places(): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $stmt = $pdo->query(
        'SELECT
            o.id, o.nazev, o.okres, o.kraj, o.psc_list,
            COALESCE(z.status, "not_served") AS status,
            z.prodej,
            oz.jmeno AS oz_jmeno, oz.email AS oz_email, oz.mobil AS oz_mobil,
            okres_oz.jmeno AS okres_oz_jmeno, okres_oz.email AS okres_oz_email, okres_oz.mobil AS okres_oz_mobil
         FROM rep_cr_obce o
         LEFT JOIN rep_zavoz_obce z ON z.obec_id = o.id AND z.valid = 1
         LEFT JOIN obchodni_zastupci oz ON oz.id = z.obchodni_zastupce_id AND oz.valid = 1
         LEFT JOIN rep_cr_okresy ok ON ok.id = o.okres_id AND ok.valid = 1
         LEFT JOIN obchodni_zastupci okres_oz ON okres_oz.id = ok.obchodni_zastupce_id AND okres_oz.valid = 1
         WHERE o.valid = 1
         ORDER BY o.nazev ASC, o.okres ASC, o.id ASC'
    );

    $places = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $status = frontend_velkoobchod_normalize_status((string)($row['status'] ?? 'not_served'));
        $contact = in_array($status, ['served', 'review'], true)
            ? frontend_velkoobchod_representative_contact($row)
            : ['source' => '', 'name' => '', 'email' => '', 'phone' => ''];

        $places[] = [
            'id' => (int)$row['id'],
            'name' => (string)($row['nazev'] ?? ''),
            'district' => (string)($row['okres'] ?? ''),
            'region' => (string)($row['kraj'] ?? ''),
            'psc' => (string)($row['psc_list'] ?? ''),
            'status' => $status,
            'contact' => $contact,
        ];
    }

    return $places;
}

/** @return array<int, array<string, mixed>> */
function frontend_velkoobchod_representatives(string $lang = 'cz'): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $descriptionColumn = $lang === 'en' ? 'oz.popis_en' : 'oz.popis_cz';
    $branchNameColumn = $lang === 'en' ? 'p.nazev_en' : 'p.nazev_cz';

    $stmt = $pdo->query(
        "SELECT
            oz.id, oz.pobocka_id, oz.jmeno, oz.mobil, oz.email, oz.web, oz.image,
            {$descriptionColumn} AS description_label,
            oz.popis_cz,
            p.id AS branch_id,
            {$branchNameColumn} AS branch_label,
            p.nazev_cz AS branch_label_cz
         FROM obchodni_zastupci oz
         LEFT JOIN pobocky p ON p.id = oz.pobocka_id AND p.valid = 1
         WHERE oz.valid = 1
         ORDER BY p.poradi ASC, p.nazev_cz ASC, oz.poradi ASC, oz.jmeno ASC, oz.id ASC"
    );

    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $name = trim((string)($row['jmeno'] ?? ''));
        if ($name === '') {
            continue;
        }

        $branchLabel = trim((string)($row['branch_label'] ?? ''));
        if ($branchLabel === '') {
            $branchLabel = trim((string)($row['branch_label_cz'] ?? ''));
        }

        $description = frontend_velkoobchod_safe_html($row['description_label'] ?? '');
        if (frontend_velkoobchod_html_text($description) === '') {
            $description = frontend_velkoobchod_safe_html($row['popis_cz'] ?? '');
        }

        $items[] = [
            'id' => (int)$row['id'],
            'branch_id' => (int)($row['branch_id'] ?? 0),
            'branch_label' => $branchLabel,
            'name' => $name,
            'email' => trim((string)($row['email'] ?? '')),
            'phone' => trim((string)($row['mobil'] ?? '')),
            'web' => trim((string)($row['web'] ?? '')),
            'image' => function_exists('frontend_markety_file_url') ? frontend_markety_file_url($row['image'] ?? '') : '',
            'description' => $description,
        ];
    }

    return $items;
}
