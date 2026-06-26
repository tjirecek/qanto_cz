<?php
declare(strict_types=1);

if (is_file(SEC_DIR . '/functions/fun_rep_cron.php')) {
    require_once SEC_DIR . '/functions/fun_rep_cron.php';
}

if (!function_exists('app_cron_http_base_url')) {
    function app_cron_http_base_url(): ?string
    {
        return null;
    }
}

if (!function_exists('app_cron_jobs')) {
    function app_cron_jobs(): array
    {
        return [];
    }
}

$baseHttpUrl = app_cron_http_base_url();
$jobs = app_cron_jobs();
$hostingJobs = array_values(array_filter($jobs, static fn(array $job): bool => ($job['role'] ?? '') === 'hosting'));
$manualJobs = array_values(array_filter($jobs, static fn(array $job): bool => ($job['role'] ?? '') === 'manual_child'));
$legacyJobs = array_values(array_filter($jobs, static fn(array $job): bool => ($job['role'] ?? '') === 'legacy'));

$roleLabels = [
    'hosting' => ['Provozní hosting', 'text-bg-success'],
    'manual_child' => ['Interní / manuální', 'text-bg-secondary'],
    'legacy' => ['Legacy', 'text-bg-dark'],
];
$typeBadges = [
    'Export' => 'text-bg-warning',
    'Import' => 'text-bg-info',
    'Email' => 'text-bg-primary',
];

$sections = [
    [
        'title' => 'Provozní crony na hostingu',
        'description' => 'Endpointy, které mají být nastavené v administraci hostingu.',
        'jobs' => $hostingJobs,
        'badge' => 'text-bg-success',
    ],
    [
        'title' => 'Interní / manuální child crony',
        'description' => 'Skripty volané wrapperem nebo určené pro diagnostiku. Nenastavovat samostatně na hosting bez konkrétního důvodu.',
        'jobs' => $manualJobs,
        'badge' => 'text-bg-secondary',
    ],
    [
        'title' => 'Legacy duplicity',
        'description' => 'Historické varianty. Nenastavovat na hosting; před smazáním ověřit logy a produkční odkazy.',
        'jobs' => $legacyJobs,
        'badge' => 'text-bg-dark',
    ],
];
?>

<style>
    .cron-list-table {
        min-width: 1120px;
        table-layout: fixed;
    }
    .cron-list-table th:nth-child(1) { width: 230px; }
    .cron-list-table th:nth-child(2) { width: 360px; }
    .cron-list-table th:nth-child(3) { width: 170px; }
    .cron-list-table th:nth-child(4) { width: auto; }
    .cron-path {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: .78rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
        word-break: break-word;
        user-select: all;
    }
    .cron-address-label {
        min-width: 42px;
    }
</style>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Cron</h1>

    <div class="d-flex flex-wrap gap-2">
        <span class="btn btn-sm btn-light shadow-sm">
            provozní endpointy: <?= count($hostingJobs) ?>
        </span>
        <span class="btn btn-sm btn-light shadow-sm">
            interní/manuální: <?= count($manualJobs) ?>
        </span>
        <span class="btn btn-sm btn-light shadow-sm">
            legacy: <?= count($legacyJobs) ?>
        </span>
    </div>
</div>

<div class="alert alert-info mb-4">
    Přehled rozlišuje provozní endpointy nastavené na hostingu, interní child/manuální skripty a legacy duplicity.
    URL a CLI adresy jsou zalomené pro snazší kontrolu a lze je označit kliknutím.
</div>

<?php foreach ($sections as $section): ?>
    <?php $sectionJobs = $section['jobs']; ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h6 class="m-0 fw-bold text-primary"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                <div class="small text-muted mt-1"><?= htmlspecialchars($section['description'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <span class="badge <?= htmlspecialchars($section['badge'], ENT_QUOTES, 'UTF-8') ?>">
                <?= count($sectionJobs) ?> souborů
            </span>
        </div>

        <div class="card-body p-0">
            <?php if ($sectionJobs === []): ?>
                <div class="p-3 text-muted">V této sekci nejsou žádné crony.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered table-sm align-middle mb-0 cron-list-table">
                        <thead class="table-dark align-middle">
                        <tr>
                            <th>Cron</th>
                            <th>Co dělá</th>
                            <th>Spuštění</th>
                            <th>Adresy</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sectionJobs as $job): ?>
                            <?php
                            $relativePath = '/' . ltrim((string)$job['relative_path'], '/');
                            $httpUrl = $baseHttpUrl !== null ? $baseHttpUrl . $relativePath : $relativePath;
                            $cliPath = ROOT_DIR . '/' . ltrim((string)$job['relative_path'], '/');
                            $schedule = trim((string)($job['recommended_schedule'] ?? ''));
                            $cronExpr = trim((string)($job['recommended_cron'] ?? ''));
                            $type = (string)($job['type'] ?? '');
                            $role = (string)($job['role'] ?? '');
                            [$roleLabel, $roleBadge] = $roleLabels[$role] ?? ['Nezařazeno', 'text-bg-light'];
                            $typeBadge = $typeBadges[$type] ?? 'text-bg-secondary';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold mb-2">
                                        <?= htmlspecialchars((string)$job['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge <?= $roleBadge ?>"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($type !== ''): ?>
                                            <span class="badge <?= $typeBadge ?>"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars((string)$job['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($schedule !== ''): ?>
                                        <div class="fw-semibold"><?= htmlspecialchars($schedule, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if ($cronExpr !== ''): ?>
                                        <div class="cron-path text-muted mt-1"><?= htmlspecialchars($cronExpr, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge text-bg-light cron-address-label">URL</span>
                                        <div class="cron-path flex-grow-1"><?= htmlspecialchars($httpUrl, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="badge text-bg-light cron-address-label">CLI</span>
                                        <div class="cron-path flex-grow-1"><?= htmlspecialchars('php ' . $cliPath, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
