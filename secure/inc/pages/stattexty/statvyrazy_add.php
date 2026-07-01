<?php
declare(strict_types=1);

global $pdo;

$code = trim((string)($_POST['code'] ?? ''));
$cz = str_replace("\r\n", '', (string)($_POST['cz'] ?? ''));
$en = str_replace("\r\n", '', (string)($_POST['en'] ?? ''));
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

if ($add === 1 && trim(strip_tags($cz)) === '' && trim(strip_tags($en)) === '') {
    $add = 0;
    echo '<div class="alert alert-warning mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Nevyplnil jsi CZ ani EN - výraz nebyl uložen.</div>';
}
?>

<div class="card-body">
    <?php if ($add === 0): ?>
        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="code" class="form-label">Kód výrazu</label>
                    <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" placeholder="např. home_gateway_order_text">
                </div>

                <div class="col-12">
                    <ul class="nav nav-tabs admin-lang-tabs" id="statvyrazLangTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="statvyraz-cz-tab" data-bs-toggle="tab" data-bs-target="#statvyraz-cz-pane" type="button" role="tab" aria-controls="statvyraz-cz-pane" aria-selected="true">CZ</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="statvyraz-en-tab" data-bs-toggle="tab" data-bs-target="#statvyraz-en-pane" type="button" role="tab" aria-controls="statvyraz-en-pane" aria-selected="false">EN</button>
                        </li>
                    </ul>

                    <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="statvyrazLangTabsContent">
                        <div class="tab-pane fade show active" id="statvyraz-cz-pane" role="tabpanel" aria-labelledby="statvyraz-cz-tab" tabindex="0">
                            <label for="cz" class="form-label">Statický výraz CZ</label>
                            <textarea name="cz" id="cz" class="form-control js-tinymce" rows="8" data-tinymce-height="260" data-translate-source="vyraz" data-translate-format="html"><?= $cz ?></textarea>
                        </div>

                        <div class="tab-pane fade" id="statvyraz-en-pane" role="tabpanel" aria-labelledby="statvyraz-en-tab" tabindex="0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="text-muted small">EN pole lze předvyplnit překladem aktuální CZ hodnoty z tohoto formuláře.</div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".statvyraz-translate-status">
                                        <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                    </button>
                                    <span class="small text-muted statvyraz-translate-status"></span>
                                </div>
                            </div>
                            <label for="en" class="form-label">Statický výraz EN</label>
                            <textarea name="en" id="en" class="form-control js-tinymce" rows="8" data-tinymce-height="260" data-translate-target="vyraz"><?= $en ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit statický výraz</button>
                </div>
            </div>
        </form>
    <?php elseif ($add === 1): ?>
        <?php statvyrazy_add($code, $cz, $en); ?>
    <?php endif; ?>
</div>
