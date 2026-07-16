<?php
declare(strict_types=1);

function rep_zavoz_page_url(array $params = []): string
{
    global $sec_page;

    return 'index.php?' . http_build_query(array_merge([
        'section' => '02',
        'page' => '09',
        'sec_page' => (string)($sec_page ?? '01'),
    ], $params));
}

function rep_zavoz_redirect(array $params = []): void
{
    $url = rep_zavoz_page_url($params);
    $jsUrl = json_encode($url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<script type='text/javascript'>document.location.href={$jsUrl};</script>";
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '">';
}

function rep_zavoz_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rep_zavoz_statuses(): array
{
    return [
        'served' => ['label' => 'Obsluhujeme', 'badge' => 'success'],
        'excluded' => ['label' => 'Vyloučeno', 'badge' => 'danger'],
        'review' => ['label' => 'Ke kontrole', 'badge' => 'warning'],
    ];
}

function rep_zavoz_ui_statuses(): array
{
    return [
        'served' => ['label' => 'Obsluhujeme', 'badge' => 'success', 'color' => '#198754'],
        'not_served' => ['label' => 'Neobsluhujeme', 'badge' => 'secondary', 'color' => '#6c757d'],
        'excluded' => ['label' => 'Vyloučeno', 'badge' => 'danger', 'color' => '#dc3545'],
        'review' => ['label' => 'Ke kontrole', 'badge' => 'warning', 'color' => '#f0ad4e'],
    ];
}

function rep_zavoz_normalize_status(string $status): string
{
    return array_key_exists($status, rep_zavoz_statuses()) ? $status : 'review';
}

function rep_zavoz_normalize_ui_status(string $status): string
{
    return array_key_exists($status, rep_zavoz_ui_statuses()) ? $status : 'review';
}

function rep_zavoz_status_badge(string $status): string
{
    $statuses = rep_zavoz_ui_statuses();
    $normalized = rep_zavoz_normalize_ui_status($status);
    $config = $statuses[$normalized];

    return '<span class="badge text-bg-' . rep_zavoz_e((string)$config['badge']) . '">' . rep_zavoz_e((string)$config['label']) . '</span>';
}

function rep_zavoz_normalize_psc(mixed $value): string
{
    $psc = preg_replace('~\D+~', '', trim((string)$value));
    return strlen((string)$psc) === 5 ? (string)$psc : '';
}

function rep_zavoz_parse_money(mixed $value): float
{
    if (is_int($value) || is_float($value)) {
        return round((float)$value, 2);
    }

    $raw = trim((string)$value);
    if ($raw === '') {
        return 0.0;
    }

    $raw = str_replace(["\xc2\xa0", ' '], '', $raw);
    $raw = preg_replace('~[^0-9,.\-]~u', '', (string)$raw);
    if ($raw === '' || $raw === '-' || $raw === ',' || $raw === '.') {
        return 0.0;
    }

    if (str_contains($raw, ',')) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    }

    return round((float)$raw, 2);
}

function rep_zavoz_header_key(mixed $value): string
{
    $value = mb_strtolower(trim((string)$value), 'UTF-8');
    $value = strtr($value, [
        'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i',
        'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u',
        'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
    ]);
    return preg_replace('~[^a-z0-9]+~', '_', $value) ?: '';
}

function rep_zavoz_require_spreadsheet(): void
{
    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer vendor/autoload.php není dostupný. Spusť composer install.');
    }

    require_once $autoload;
}

function rep_zavoz_find_obce_by_psc(PDO $pdo, string $psc): array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.nazev, o.okres, o.kraj
         FROM rep_cr_obce_psc p
         INNER JOIN rep_cr_obce o ON o.id = p.obec_id
         WHERE p.psc = :psc AND p.valid = 1 AND o.valid = 1
         ORDER BY o.nazev ASC, o.id ASC'
    );
    $stmt->execute([':psc' => $psc]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rep_zavoz_set_status(PDO $pdo, int $id, string $status): void
{
    $stmt = $pdo->prepare(
        'UPDATE rep_zavoz_obce
         SET status = :status, user_u = :user_u
         WHERE id = :id'
    );
    $stmt->execute([
        ':status' => rep_zavoz_normalize_status($status),
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

function rep_zavoz_set_obec_status(PDO $pdo, int $obecId, string $status): void
{
    $status = rep_zavoz_normalize_ui_status($status);
    $user = admin_session_user();

    $existsStmt = $pdo->prepare('SELECT id FROM rep_cr_obce WHERE id = :id AND valid = 1 LIMIT 1');
    $existsStmt->execute([':id' => $obecId]);
    if ((int)($existsStmt->fetchColumn() ?: 0) <= 0) {
        throw new RuntimeException('Obec nebyla nalezena v číselníku.');
    }

    if ($status === 'not_served') {
        $stmt = $pdo->prepare(
            'UPDATE rep_zavoz_obce
             SET valid = 0, user_u = :user_u
             WHERE obec_id = :obec_id'
        );
        $stmt->execute([
            ':user_u' => $user,
            ':obec_id' => $obecId,
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO rep_zavoz_obce
            (obec_id, status, prodej, source, valid, user_i, user_u)
         VALUES
            (:obec_id, :status, 0.00, "manual_admin", 1, :user_i, :user_u)
         ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            valid = 1,
            source = IF(source IS NULL OR source = "", "manual_admin", source),
            user_u = VALUES(user_u)'
    );
    $stmt->execute([
        ':obec_id' => $obecId,
        ':status' => rep_zavoz_normalize_status($status),
        ':user_i' => $user,
        ':user_u' => $user,
    ]);
}

function rep_zavoz_save(PDO $pdo, int $id, array $data): void
{
    $ozId = (int)($data['obchodni_zastupce_id'] ?? 0);
    $stmt = $pdo->prepare(
        'UPDATE rep_zavoz_obce
         SET status = :status,
             obchodni_zastupce_id = :obchodni_zastupce_id,
             note_internal = :note_internal,
             user_u = :user_u
         WHERE id = :id'
    );
    $stmt->execute([
        ':status' => rep_zavoz_normalize_status((string)($data['status'] ?? 'review')),
        ':obchodni_zastupce_id' => $ozId > 0 ? $ozId : null,
        ':note_internal' => trim((string)($data['note_internal'] ?? '')) !== '' ? trim((string)$data['note_internal']) : null,
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

function rep_zavoz_save_by_obec(PDO $pdo, int $obecId, array $data): void
{
    $status = rep_zavoz_normalize_ui_status((string)($data['status'] ?? 'review'));
    rep_zavoz_set_obec_status($pdo, $obecId, $status);

    if ($status === 'not_served') {
        return;
    }

    $ozId = (int)($data['obchodni_zastupce_id'] ?? 0);
    $stmt = $pdo->prepare(
        'UPDATE rep_zavoz_obce
         SET obchodni_zastupce_id = :obchodni_zastupce_id,
             note_internal = :note_internal,
             user_u = :user_u
         WHERE obec_id = :obec_id AND valid = 1'
    );
    $stmt->execute([
        ':obchodni_zastupce_id' => $ozId > 0 ? $ozId : null,
        ':note_internal' => trim((string)($data['note_internal'] ?? '')) !== '' ? trim((string)$data['note_internal']) : null,
        ':user_u' => admin_session_user(),
        ':obec_id' => $obecId,
    ]);
}

function rep_zavoz_save_okres(PDO $pdo, int $okresId, array $data): void
{
    $ozId = (int)($data['obchodni_zastupce_id'] ?? 0);
    $stmt = $pdo->prepare(
        'UPDATE rep_cr_okresy
         SET obchodni_zastupce_id = :obchodni_zastupce_id,
             note_internal = :note_internal,
             user_u = :user_u
         WHERE id = :id AND valid = 1'
    );
    $stmt->execute([
        ':obchodni_zastupce_id' => $ozId > 0 ? $ozId : null,
        ':note_internal' => trim((string)($data['note_internal'] ?? '')) !== '' ? trim((string)$data['note_internal']) : null,
        ':user_u' => admin_session_user(),
        ':id' => $okresId,
    ]);
}

function rep_zavoz_fetch_one(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT z.*, o.nazev, o.okres, o.kraj, o.psc_list
         FROM rep_zavoz_obce z
         INNER JOIN rep_cr_obce o ON o.id = z.obec_id
         WHERE z.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function rep_zavoz_fetch_oz_options(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, jmeno, email
         FROM obchodni_zastupci
         WHERE valid = 1
         ORDER BY poradi ASC, jmeno ASC, id ASC'
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rep_zavoz_rows(PDO $pdo, int $limit = 1000): array
{
    $stmt = $pdo->prepare(
        'SELECT
            z.id, z.obec_id, z.status, z.prodej, z.imported_psc_list, z.obchodni_zastupce_id,
            z.note_internal, z.last_imported_at, z.ts_u, z.user_u,
            o.nazev, o.okres, o.kraj, o.orp, o.psc_list, o.lat, o.lng,
            oz.jmeno AS oz_jmeno, oz.email AS oz_email
         FROM rep_zavoz_obce z
         INNER JOIN rep_cr_obce o ON o.id = z.obec_id
         LEFT JOIN obchodni_zastupci oz ON oz.id = z.obchodni_zastupce_id
         WHERE z.valid = 1
         ORDER BY FIELD(z.status, "review", "served", "excluded"), z.prodej DESC, o.nazev ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rep_zavoz_all_obce_rows(PDO $pdo, int $limit = 7000): array
{
    $stmt = $pdo->prepare(
        'SELECT
            o.id AS obec_id, o.nazev, o.okres_id, o.okres, o.kraj, o.orp, o.psc_list, o.lat, o.lng,
            z.id AS id,
            COALESCE(z.status, "not_served") AS status,
            COALESCE(z.prodej, 0) AS prodej,
            z.imported_psc_list, z.obchodni_zastupce_id, z.note_internal, z.last_imported_at, z.ts_u, z.user_u,
            oz.jmeno AS oz_jmeno, oz.email AS oz_email,
            ok.obchodni_zastupce_id AS okres_obchodni_zastupce_id,
            okres_oz.jmeno AS okres_oz_jmeno,
            okres_oz.email AS okres_oz_email
         FROM rep_cr_obce o
         LEFT JOIN rep_zavoz_obce z ON z.obec_id = o.id AND z.valid = 1
         LEFT JOIN obchodni_zastupci oz ON oz.id = z.obchodni_zastupce_id
         LEFT JOIN rep_cr_okresy ok ON ok.id = o.okres_id AND ok.valid = 1
         LEFT JOIN obchodni_zastupci okres_oz ON okres_oz.id = ok.obchodni_zastupce_id
         WHERE o.valid = 1
         ORDER BY FIELD(COALESCE(z.status, "not_served"), "review", "served", "excluded", "not_served"), z.prodej DESC, o.nazev ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rep_zavoz_counts(PDO $pdo): array
{
    $counts = [
        'obce' => 0,
        'psc' => 0,
        'zavoz' => 0,
        'served' => 0,
        'not_served' => 0,
        'excluded' => 0,
        'review' => 0,
        'prodej' => 0.0,
        'okresy' => 0,
    ];

    $counts['obce'] = (int)$pdo->query('SELECT COUNT(*) FROM rep_cr_obce WHERE valid = 1')->fetchColumn();
    $counts['psc'] = (int)$pdo->query('SELECT COUNT(*) FROM rep_cr_obce_psc WHERE valid = 1')->fetchColumn();
    $counts['okresy'] = (int)$pdo->query('SELECT COUNT(*) FROM rep_cr_okresy WHERE valid = 1')->fetchColumn();

    $stmt = $pdo->query(
        'SELECT status, COUNT(*) AS cnt, COALESCE(SUM(prodej), 0) AS prodej
         FROM rep_zavoz_obce
         WHERE valid = 1
         GROUP BY status'
    );
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $status = rep_zavoz_normalize_status((string)($row['status'] ?? ''));
        $count = (int)($row['cnt'] ?? 0);
        $counts[$status] += $count;
        $counts['zavoz'] += $count;
        $counts['prodej'] += (float)($row['prodej'] ?? 0);
    }
    $counts['not_served'] = max(0, $counts['obce'] - $counts['zavoz']);

    return $counts;
}

function rep_zavoz_okres_rows(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            ok.id, ok.nazev, ok.kraj, ok.obchodni_zastupce_id, ok.note_internal, ok.ts_u, ok.user_u,
            oz.jmeno AS oz_jmeno, oz.email AS oz_email,
            COUNT(o.id) AS obce_count,
            SUM(CASE WHEN z.id IS NOT NULL AND z.status = "served" THEN 1 ELSE 0 END) AS served_count,
            SUM(CASE WHEN z.id IS NOT NULL AND z.status = "excluded" THEN 1 ELSE 0 END) AS excluded_count,
            SUM(CASE WHEN z.id IS NOT NULL AND z.status = "review" THEN 1 ELSE 0 END) AS review_count,
            SUM(CASE WHEN z.id IS NULL THEN 1 ELSE 0 END) AS not_served_count,
            COALESCE(SUM(CASE WHEN z.id IS NOT NULL THEN z.prodej ELSE 0 END), 0) AS prodej
         FROM rep_cr_okresy ok
         LEFT JOIN rep_cr_obce o ON o.okres_id = ok.id AND o.valid = 1
         LEFT JOIN rep_zavoz_obce z ON z.obec_id = o.id AND z.valid = 1
         LEFT JOIN obchodni_zastupci oz ON oz.id = ok.obchodni_zastupce_id
         WHERE ok.valid = 1
         GROUP BY ok.id, ok.nazev, ok.kraj, ok.obchodni_zastupce_id, ok.note_internal, ok.ts_u, ok.user_u, oz.jmeno, oz.email
         ORDER BY ok.kraj ASC, ok.nazev ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rep_zavoz_map_points(array $rows): array
{
    $points = [];
    foreach ($rows as $row) {
        $lat = $row['lat'] ?? null;
        $lng = $row['lng'] ?? null;
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            continue;
        }

        $points[] = [
            'id' => (int)($row['obec_id'] ?? 0),
            'zavozId' => (int)($row['id'] ?? 0),
            'name' => (string)($row['nazev'] ?? ''),
            'okres' => (string)($row['okres'] ?? ''),
            'kraj' => (string)($row['kraj'] ?? ''),
            'psc' => (string)($row['psc_list'] ?? ''),
            'lat' => (float)$lat,
            'lng' => (float)$lng,
            'prodej' => (float)($row['prodej'] ?? 0),
            'status' => rep_zavoz_normalize_ui_status((string)($row['status'] ?? 'not_served')),
        ];
    }

    return $points;
}

function rep_zavoz_map_areas(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            o.id AS obec_id, o.nazev, o.okres, o.kraj, o.psc_list, o.geojson,
            z.id AS id, z.status, z.prodej
         FROM rep_zavoz_obce z
         INNER JOIN rep_cr_obce o ON o.id = z.obec_id
         WHERE z.valid = 1
           AND o.valid = 1
           AND o.geojson IS NOT NULL
           AND o.geojson <> ""
         ORDER BY FIELD(z.status, "review", "served", "excluded"), z.prodej DESC, o.nazev ASC'
    );

    $features = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $geometry = json_decode((string)($row['geojson'] ?? ''), true);
        if (!is_array($geometry) || !isset($geometry['type'], $geometry['coordinates'])) {
            continue;
        }

        $features[] = [
            'type' => 'Feature',
            'properties' => [
                'id' => (int)($row['obec_id'] ?? 0),
                'zavozId' => (int)($row['id'] ?? 0),
                'name' => (string)($row['nazev'] ?? ''),
                'okres' => (string)($row['okres'] ?? ''),
                'kraj' => (string)($row['kraj'] ?? ''),
                'psc' => (string)($row['psc_list'] ?? ''),
                'prodej' => (float)($row['prodej'] ?? 0),
                'status' => rep_zavoz_normalize_ui_status((string)($row['status'] ?? 'review')),
            ],
            'geometry' => $geometry,
        ];
    }

    return [
        'type' => 'FeatureCollection',
        'features' => $features,
    ];
}

function rep_zavoz_import_xlsx(PDO $pdo, string $tmpFile, string $filename): array
{
    rep_zavoz_require_spreadsheet();

    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

    $headers = [];
    for ($col = 1; $col <= $highestColumn; $col++) {
        $key = rep_zavoz_header_key($sheet->getCell([$col, 1])->getValue());
        if ($key !== '') {
            $headers[$key] = $col;
        }
    }

    if (!isset($headers['psc'], $headers['prodej'])) {
        throw new RuntimeException('Importní XLSX musí obsahovat sloupce PSC a PRODEJ.');
    }

    $user = admin_session_user();
    $result = [
        'import_id' => 0,
        'rows_total' => 0,
        'rows_matched' => 0,
        'rows_unmatched' => 0,
        'rows_ambiguous' => 0,
        'rows_skipped' => 0,
        'obce_inserted' => 0,
        'obce_updated' => 0,
        'prodej_total' => 0.0,
        'errors' => [],
    ];

    $pdo->beginTransaction();
    try {
        $importStmt = $pdo->prepare(
            'INSERT INTO rep_zavoz_import (filename, status, user_i, user_u)
             VALUES (:filename, "processing", :user_i, :user_u)'
        );
        $importStmt->execute([
            ':filename' => $filename,
            ':user_i' => $user,
            ':user_u' => $user,
        ]);
        $importId = (int)$pdo->lastInsertId();
        $result['import_id'] = $importId;

        $rowStmt = $pdo->prepare(
            'INSERT INTO rep_zavoz_import_radky
                (import_id, row_no, psc, prodej, obec_id, status, message, raw_psc, raw_prodej)
             VALUES
                (:import_id, :row_no, :psc, :prodej, :obec_id, :status, :message, :raw_psc, :raw_prodej)'
        );

        $aggregates = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $rawPsc = $sheet->getCell([$headers['psc'], $row])->getCalculatedValue();
            $rawProdej = $sheet->getCell([$headers['prodej'], $row])->getCalculatedValue();

            if (trim((string)$rawPsc) === '' && trim((string)$rawProdej) === '') {
                $result['rows_skipped']++;
                continue;
            }

            $result['rows_total']++;
            $psc = rep_zavoz_normalize_psc($rawPsc);
            $prodej = rep_zavoz_parse_money($rawProdej);
            $status = 'matched';
            $message = '';
            $obecId = null;

            if ($psc === '') {
                $status = 'unmatched';
                $message = 'Neplatné PSČ.';
                $result['rows_unmatched']++;
            } else {
                $obce = rep_zavoz_find_obce_by_psc($pdo, $psc);
                if ($obce === []) {
                    $status = 'unmatched';
                    $message = 'PSČ není v číselníku obcí.';
                    $result['rows_unmatched']++;
                } elseif (count($obce) > 1) {
                    $status = 'matched_multiple';
                    $message = 'PSČ odpovídá více obcím, prodej byl rozdělen rovnoměrně.';
                    $result['rows_ambiguous']++;
                    $result['rows_matched']++;

                    $share = round($prodej / count($obce), 2);
                    foreach ($obce as $obec) {
                        $matchedObecId = (int)$obec['id'];
                        if (!isset($aggregates[$matchedObecId])) {
                            $aggregates[$matchedObecId] = [
                                'prodej' => 0.0,
                                'psc' => [],
                            ];
                        }
                        $aggregates[$matchedObecId]['prodej'] += $share;
                        $aggregates[$matchedObecId]['psc'][$psc] = true;
                    }

                    $obecId = (int)$obce[0]['id'];
                } else {
                    $obecId = (int)$obce[0]['id'];
                    $result['rows_matched']++;
                    if (!isset($aggregates[$obecId])) {
                        $aggregates[$obecId] = [
                            'prodej' => 0.0,
                            'psc' => [],
                        ];
                    }
                    $aggregates[$obecId]['prodej'] += $prodej;
                    $aggregates[$obecId]['psc'][$psc] = true;
                }
            }

            $rowStmt->execute([
                ':import_id' => $importId,
                ':row_no' => $row,
                ':psc' => $psc !== '' ? $psc : null,
                ':prodej' => $prodej,
                ':obec_id' => $obecId,
                ':status' => $status,
                ':message' => $message !== '' ? $message : null,
                ':raw_psc' => trim((string)$rawPsc),
                ':raw_prodej' => trim((string)$rawProdej),
            ]);
        }

        $selectExisting = $pdo->prepare('SELECT id FROM rep_zavoz_obce WHERE obec_id = :obec_id LIMIT 1');
        $insertZavoz = $pdo->prepare(
            'INSERT INTO rep_zavoz_obce
                (obec_id, status, prodej, imported_psc_list, source, last_import_id, last_imported_at, user_i, user_u)
             VALUES
                (:obec_id, "served", :prodej, :imported_psc_list, "xlsx_psc_prodej", :last_import_id, NOW(), :user_i, :user_u)'
        );
        $updateZavoz = $pdo->prepare(
            'UPDATE rep_zavoz_obce
             SET prodej = :prodej,
                 imported_psc_list = :imported_psc_list,
                 source = "xlsx_psc_prodej",
                 last_import_id = :last_import_id,
                 last_imported_at = NOW(),
                 user_u = :user_u
             WHERE id = :id'
        );

        foreach ($aggregates as $obecId => $aggregate) {
            $pscList = implode(', ', array_keys((array)$aggregate['psc']));
            $prodej = round((float)$aggregate['prodej'], 2);
            $result['prodej_total'] += $prodej;

            $selectExisting->execute([':obec_id' => $obecId]);
            $existingId = (int)($selectExisting->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $updateZavoz->execute([
                    ':prodej' => $prodej,
                    ':imported_psc_list' => $pscList,
                    ':last_import_id' => $importId,
                    ':user_u' => $user,
                    ':id' => $existingId,
                ]);
                $result['obce_updated']++;
            } else {
                $insertZavoz->execute([
                    ':obec_id' => $obecId,
                    ':prodej' => $prodej,
                    ':imported_psc_list' => $pscList,
                    ':last_import_id' => $importId,
                    ':user_i' => $user,
                    ':user_u' => $user,
                ]);
                $result['obce_inserted']++;
            }
        }

        $updateImport = $pdo->prepare(
            'UPDATE rep_zavoz_import
             SET status = "done",
                 rows_total = :rows_total,
                 rows_matched = :rows_matched,
                 rows_unmatched = :rows_unmatched,
                 rows_ambiguous = :rows_ambiguous,
                 rows_skipped = :rows_skipped,
                 obce_inserted = :obce_inserted,
                 obce_updated = :obce_updated,
                 prodej_total = :prodej_total,
                 user_u = :user_u
             WHERE id = :id'
        );
        $updateImport->execute([
            ':rows_total' => $result['rows_total'],
            ':rows_matched' => $result['rows_matched'],
            ':rows_unmatched' => $result['rows_unmatched'],
            ':rows_ambiguous' => $result['rows_ambiguous'],
            ':rows_skipped' => $result['rows_skipped'],
            ':obce_inserted' => $result['obce_inserted'],
            ':obce_updated' => $result['obce_updated'],
            ':prodej_total' => $result['prodej_total'],
            ':user_u' => $user,
            ':id' => $importId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $result;
}
