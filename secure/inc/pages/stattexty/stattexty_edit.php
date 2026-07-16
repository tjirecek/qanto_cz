<?php
declare(strict_types=1);

include "functions/fun_galerie.php";
global $pdo;

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

if ($add === 2) {
    $code = trim((string)($_POST['code'] ?? ''));
    $galerie_id = isset($_POST['galerie_id']) ? (int)$_POST['galerie_id'] : 0;
    $col = max(1, min(12, (int)($_POST['col'] ?? 12)));
    $valid = isset($_POST['valid']) ? 1 : 0;
    $nazevCz = trim((string)($_POST['nazev_cz'] ?? ''));
    $nazevEn = trim((string)($_POST['nazev_en'] ?? ''));
    $textCz = str_replace("\r\n", '', (string)($_POST['text_cz'] ?? ''));
    $textEn = str_replace("\r\n", '', (string)($_POST['text_en'] ?? ''));

    stattexty_edit_multilang($edit, $code, $nazevCz, $nazevEn, $textCz, $textEn, $galerie_id, $col, $valid);
    echo '<div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>Statický text byl uložen.</div>';
}

$stmt = $pdo->prepare('SELECT * FROM stat_texty WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $edit]);
$dev = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dev) {
    echo '<div class="alert alert-danger">Záznam nenalezen.</div>';
    return;
}

$code = (string)($dev['code'] ?? '');
$galerie_id = (int)($dev['galerie_id'] ?? 0);
$col = max(1, min(12, (int)($dev['col'] ?? 12)));
$valid = (int)($dev['valid'] ?? 0);
?>

<div class="card-body">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="code" class="form-label">Kód textu</label>
                <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-3">
                <label for="galerie_id" class="form-label">ID galerie</label>
                <input type="number" name="galerie_id" id="galerie_id" class="form-control" value="<?= (int)$galerie_id ?>">
            </div>

            <div class="col-md-3">
                <label for="col" class="form-label">Sloupců</label>
                <input type="number" name="col" id="col" class="form-control" value="<?= (int)$col ?>">
            </div>

            <div class="col-12">
                <ul class="nav nav-tabs admin-lang-tabs" id="stattextLangTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="stattext-cz-tab" data-bs-toggle="tab" data-bs-target="#stattext-cz-pane" type="button" role="tab" aria-controls="stattext-cz-pane" aria-selected="true">CZ</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stattext-en-tab" data-bs-toggle="tab" data-bs-target="#stattext-en-pane" type="button" role="tab" aria-controls="stattext-en-pane" aria-selected="false">EN</button>
                    </li>
                </ul>

                <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="stattextLangTabsContent">
                    <div class="tab-pane fade show active" id="stattext-cz-pane" role="tabpanel" aria-labelledby="stattext-cz-tab" tabindex="0">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="nazev_cz" class="form-label">Název statického textu CZ</label>
                                <input type="text" name="nazev_cz" id="nazev_cz" class="form-control" value="<?= htmlspecialchars((string)($dev['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-translate-source="nazev" data-translate-format="text">
                            </div>
                            <div class="col-md-<?= (int)$col ?>">
                                <label for="text_cz" class="form-label">Text CZ</label>
                                <textarea name="text_cz" id="text_cz" class="form-control js-tinymce" rows="12" data-translate-source="text" data-translate-format="html"><?= (string)($dev['text_cz'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="stattext-en-pane" role="tabpanel" aria-labelledby="stattext-en-tab" tabindex="0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="text-muted small">EN pole lze předvyplnit překladem aktuálních CZ hodnot z tohoto formuláře.</div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".stattexty-translate-status">
                                    <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                </button>
                                <span class="small text-muted stattexty-translate-status"></span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="nazev_en" class="form-label">Název statického textu EN</label>
                                <input type="text" name="nazev_en" id="nazev_en" class="form-control" value="<?= htmlspecialchars((string)($dev['nazev_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-translate-target="nazev">
                            </div>
                            <div class="col-md-<?= (int)$col ?>">
                                <label for="text_en" class="form-label">Text EN</label>
                                <textarea name="text_en" id="text_en" class="form-control js-tinymce" rows="12" data-translate-target="text"><?= (string)($dev['text_en'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <?= admin_auto_translate_checkbox($dev ?? null, 'stattext_auto_translate_en') ?>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ($valid === 1 ? 'checked' : '') ?>>
                    <label class="form-check-label" for="valid">valid</label>
                </div>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <input type="hidden" name="add" value="2">
                <button type="submit" class="btn btn-primary w-100">Uložit statický text</button>
            </div>

            <div class="col-12 small text-muted">
                Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </form>
</div>
