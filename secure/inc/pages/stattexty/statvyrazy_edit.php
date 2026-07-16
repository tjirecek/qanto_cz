<?php
declare(strict_types=1);

global $pdo;

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$code = trim((string)($_POST['code'] ?? ''));
$cz = str_replace("\r\n", '', (string)($_POST['cz'] ?? ''));
$en = str_replace("\r\n", '', (string)($_POST['en'] ?? ''));
$valid = isset($_POST['valid']) ? 1 : 0;
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>
        <?php
        $stmt = $pdo->prepare('SELECT * FROM stat_vyrazy WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $edit]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dev) {
            echo '<div class="alert alert-danger">Záznam nenalezen.</div>';
            return;
        }

        $code = (string)($dev['code'] ?? '');
        $cz = (string)($dev['cz'] ?? '');
        $en = (string)($dev['en'] ?? '');
        $valid = (int)($dev['valid'] ?? 0);
        ?>

        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="code" class="form-label">Kód výrazu</label>
                    <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
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
                            <textarea name="cz" id="cz" class="form-control" rows="4" data-translate-source="vyraz" data-translate-format="text"><?= htmlspecialchars($cz, ENT_QUOTES, 'UTF-8') ?></textarea>
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
                            <textarea name="en" id="en" class="form-control" rows="4" data-translate-target="vyraz"><?= htmlspecialchars($en, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <?= admin_auto_translate_checkbox($dev ?? null, 'statvyraz_auto_translate_en') ?>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ($valid === 1 ? 'checked' : '') ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-primary w-100">Uložit statický výraz</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                    Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                    Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                    Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </form>
    <?php elseif ($add === 2): ?>
        <?php statvyrazy_edit($edit, $code, $cz, $en, $valid); ?>
    <?php endif; ?>
</div>
