<?php
declare(strict_types=1);

global $page;

$projectAdminModules = [
    '01' => 'Volná místa',
    '02' => 'Akční nabídky',
    '03' => 'Bannery',
    '04' => 'Brigádníci',
    '05' => 'Ples',
    '06' => 'Volání',
    '07' => 'TenisQcup',
    '08' => 'Inventury',
];

$title = $projectAdminModules[(string)($page ?? '')] ?? 'Project modul qanto.cz';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
</div>

<div class="alert alert-info mb-0">
    Project modul noveho webu qanto.cz je zatim pripraveny v menu. Implementace a datovy model budou doplneny podle navrhu a migrace z `xqanto_cz_old`.
</div>
