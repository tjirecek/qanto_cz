<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_pobocky.php';

global $pdo;

$branches = [];
$allBranches = [];
$hoursByBranch = [];
$messages = [];
$counts = [
    'total' => 0,
    'with_hours' => 0,
    'prodejna' => 0,
    'market' => 0,
    'velkoobchod' => 0,
];
$requestedType = pobocky_normalize_type((string)($_GET['typ'] ?? ''), '');
$selectedType = in_array($requestedType, array_keys(pobocky_type_definitions()), true) ? $requestedType : '';
$baseUrl = 'index.php?section=01&page=04&sec_page=05';

$shortDayLabels = [
    1 => 'Po',
    2 => 'Út',
    3 => 'St',
    4 => 'Čt',
    5 => 'Pá',
    6 => 'So',
    7 => 'Ne',
];

$formatDayRange = static function (int $startDay, int $endDay) use ($shortDayLabels): string {
    $startLabel = (string)($shortDayLabels[$startDay] ?? $startDay);
    $endLabel = (string)($shortDayLabels[$endDay] ?? $endDay);

    return $startDay === $endDay ? $startLabel : $startLabel . '-' . $endLabel;
};

$summarizeWeek = static function (array $weekRows) use ($formatDayRange): string {
    $segments = [];
    $currentLabel = null;
    $currentStart = 1;
    $currentEnd = 1;

    for ($day = 1; $day <= 7; $day++) {
        $row = $weekRows[$day] ?? pobocky_otevdoba_default_row($day);
        $label = pobocky_otevdoba_time_range_label($row);
        $note = trim((string)($row['poznamka_cz'] ?? ''));

        if ($label === '') {
            $label = 'nezadáno';
        }

        if ($note !== '') {
            $label .= ' (' . $note . ')';
        }

        if ($currentLabel === null) {
            $currentLabel = $label;
            $currentStart = $day;
            $currentEnd = $day;
            continue;
        }

        if ($label === $currentLabel) {
            $currentEnd = $day;
            continue;
        }

        $segments[] = $formatDayRange($currentStart, $currentEnd) . ': ' . $currentLabel;
        $currentLabel = $label;
        $currentStart = $day;
        $currentEnd = $day;
    }

    if ($currentLabel !== null) {
        $segments[] = $formatDayRange($currentStart, $currentEnd) . ': ' . $currentLabel;
    }

    return implode('; ', $segments);
};

try {
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    $stmt = $pdo->query(
        'SELECT
            p.id, p.typ, p.poradi, p.stredisko, p.nazev_cz, p.email, p.adresa, p.user_u, p.ts_u,
            COALESCE(h.hours_count, 0) AS hours_count,
            h.hours_ts_u,
            COALESCE(e.upcoming_exceptions, 0) AS upcoming_exceptions
         FROM pobocky p
         LEFT JOIN (
            SELECT pobocka_id, COUNT(*) AS hours_count, MAX(ts_u) AS hours_ts_u
            FROM pobocky_otevdoba
            WHERE valid = 1
            GROUP BY pobocka_id
         ) h ON h.pobocka_id = p.id
         LEFT JOIN (
            SELECT pobocka_id, COUNT(*) AS upcoming_exceptions
            FROM pobocky_otevdoba_vyjimky
            WHERE valid = 1 AND datum >= CURDATE()
            GROUP BY pobocka_id
         ) e ON e.pobocka_id = p.id
         WHERE p.valid = 1
         ORDER BY FIELD(p.typ, "prodejna", "market", "velkoobchod"), p.poradi ASC, p.nazev_cz ASC, p.id DESC'
    );
    $allBranches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hoursStmt = $pdo->query(
        'SELECT pobocka_id, den, zavreno, od1, do1, od2, do2, poznamka_cz, poznamka_en, sync_lock, valid
         FROM pobocky_otevdoba
         WHERE valid = 1
         ORDER BY pobocka_id ASC, den ASC'
    );

    foreach ($hoursStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $pobockaId = (int)($row['pobocka_id'] ?? 0);
        $day = (int)($row['den'] ?? 0);
        if ($pobockaId <= 0 || $day < 1 || $day > 7) {
            continue;
        }

        $hoursByBranch[$pobockaId][$day] = [
            'den' => $day,
            'zavreno' => (int)($row['zavreno'] ?? 0),
            'od1' => pobocky_time_to_input((string)($row['od1'] ?? '')),
            'do1' => pobocky_time_to_input((string)($row['do1'] ?? '')),
            'od2' => pobocky_time_to_input((string)($row['od2'] ?? '')),
            'do2' => pobocky_time_to_input((string)($row['do2'] ?? '')),
            'poznamka_cz' => (string)($row['poznamka_cz'] ?? ''),
            'poznamka_en' => (string)($row['poznamka_en'] ?? ''),
            'sync_lock' => (int)($row['sync_lock'] ?? 0),
            'valid' => (int)($row['valid'] ?? 1),
        ];
    }

    foreach ($allBranches as $branch) {
        $type = pobocky_normalize_type((string)($branch['typ'] ?? ''), '');
        if (isset($counts[$type])) {
            $counts[$type]++;
        }
        if ((int)($branch['hours_count'] ?? 0) > 0) {
            $counts['with_hours']++;
        }
    }

    $counts['total'] = count($allBranches);
    $branches = $selectedType === ''
        ? $allBranches
        : array_values(array_filter(
            $allBranches,
            static fn (array $branch): bool => pobocky_normalize_type((string)($branch['typ'] ?? ''), '') === $selectedType
        ));
} catch (Throwable $e) {
    $messages[] = [
        'type' => 'danger',
        'text' => 'Nepodarilo se nacist oteviraci doby: ' . $e->getMessage(),
    ];
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Kontakty: Otevírací doby</h1>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES) ?>" class="btn btn-sm <?= $selectedType === '' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">
            všechny: <?= number_format((int)$counts['total'], 0, ',', ' ') ?>
        </a>
        <a href="<?= htmlspecialchars($baseUrl . '&typ=prodejna', ENT_QUOTES) ?>" class="btn btn-sm <?= $selectedType === 'prodejna' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">
            prodejny: <?= number_format((int)$counts['prodejna'], 0, ',', ' ') ?>
        </a>
        <a href="<?= htmlspecialchars($baseUrl . '&typ=market', ENT_QUOTES) ?>" class="btn btn-sm <?= $selectedType === 'market' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">
            markety: <?= number_format((int)$counts['market'], 0, ',', ' ') ?>
        </a>
        <a href="<?= htmlspecialchars($baseUrl . '&typ=velkoobchod', ENT_QUOTES) ?>" class="btn btn-sm <?= $selectedType === 'velkoobchod' ? 'btn-primary' : 'btn-outline-primary' ?> shadow-sm">
            velkoobchody: <?= number_format((int)$counts['velkoobchod'], 0, ',', ' ') ?>
        </a>
        <span class="btn btn-sm btn-light shadow-sm disabled" aria-disabled="true">
            s otevírací dobou: <?= number_format((int)$counts['with_hours'], 0, ',', ' ') ?>
        </span>
    </div>
</div>

<?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= htmlspecialchars($message['type'], ENT_QUOTES) ?> mb-3">
        <?= htmlspecialchars($message['text'], ENT_QUOTES) ?>
    </div>
<?php endforeach; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="m-0 fw-bold text-primary d-sm-inline">Přehled poboček</h6>
            <span class="d-none d-sm-inline-block ms-2">načteno <?= number_format(count($branches), 0, ',', ' ') ?> záznamů</span>
            <span class="d-none d-sm-inline-block ms-2 text-muted">otevírací doby se editují v detailu pobočky</span>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table
                class="table table-striped table-hover table-bordered table-sm align-middle w-100 js-datatable"
                id="DataTablePobockyOteviraciDoby"
                data-state-key="pobocky-oteviraci-doby-v1"
                data-column-filters="1"
                data-column-filter-placement="header"
                data-order='[[ 0, "asc" ], [ 1, "asc" ], [ 2, "asc" ]]'
                data-page-length='100'
            >
                <thead class="table-dark align-middle">
                <tr>
                    <th class="select-filter">Typ</th>
                    <th class="no-filter">Pořadí</th>
                    <th class="text-filter dt-autocomplete">Pobočka</th>
                    <th class="text-filter">Adresa</th>
                    <th class="text-filter">Otevírací doba</th>
                    <th class="no-filter">Výjimky</th>
                    <th class="no-filter">Upraveno</th>
                    <th class="no-sort no-filter">Akce</th>
                </tr>
                </thead>

                <tfoot class="table-light">
                <tr>
                    <th>Typ</th>
                    <th>Pořadí</th>
                    <th>Pobočka</th>
                    <th>Adresa</th>
                    <th>Otevírací doba</th>
                    <th>Výjimky</th>
                    <th>Upraveno</th>
                    <th>Akce</th>
                </tr>
                </tfoot>

                <tbody>
                <?php if ($branches === []): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Nejsou dostupné žádné aktivní pobočky.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($branches as $branch): ?>
                        <?php
                        $branchId = (int)($branch['id'] ?? 0);
                        $type = pobocky_normalize_type((string)($branch['typ'] ?? ''), 'prodejna');
                        $editUrl = pobocky_page_url($type, [
                            'show' => 2,
                            'edit' => $branchId,
                            'limit' => 100,
                            'valid' => 1,
                        ]) . '#otevdoba-standard';
                        $week = $hoursByBranch[$branchId] ?? [];
                        $summary = $summarizeWeek($week);
                        $updatedAt = (string)($branch['hours_ts_u'] ?? '');
                        if ($updatedAt === '') {
                            $updatedAt = (string)($branch['ts_u'] ?? '');
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars(pobocky_type_single_label($type), ENT_QUOTES) ?></td>
                            <td><?= (int)($branch['poradi'] ?? 0) ?></td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars((string)($branch['nazev_cz'] ?? ''), ENT_QUOTES) ?></span>
                                <?php if (trim((string)($branch['stredisko'] ?? '')) !== ''): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars((string)$branch['stredisko'], ENT_QUOTES) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($branch['adresa'] ?? ''), ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars($summary, ENT_QUOTES) ?></td>
                            <td class="text-center">
                                <?php if ((int)($branch['upcoming_exceptions'] ?? 0) > 0): ?>
                                    <span class="badge text-bg-warning"><?= number_format((int)$branch['upcoming_exceptions'], 0, ',', ' ') ?> budoucí</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars((string)format_datetime_www($updatedAt), ENT_QUOTES) ?>
                                <br><small class="text-muted"><?= htmlspecialchars((string)($branch['user_u'] ?? ''), ENT_QUOTES) ?></small>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-success btn-circle btn-sm" href="<?= htmlspecialchars($editUrl, ENT_QUOTES) ?>" title="Upravit otevírací dobu">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
