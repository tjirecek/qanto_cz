<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_news.php';
require_once SEC_DIR . '/functions/fun_galerie.php';

global $pdo;

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

$tags = news_tags_all(1, 0);
$galleries = function_exists('galerie_all') ? galerie_all(null, 1, 0) : [];

if ($add === 2) {
    $datum = (string)($_POST['datum'] ?? date('Y-m-d'));
    $news_typ = (int)($_POST['news_typ'] ?? 0);
    $galerie_id = (int)($_POST['galerie_id'] ?? 0);
    $visible = news_visible_from_post($_POST);
    $valid = isset($_POST['valid']) ? 1 : 0;
    $tagIds = array_map('intval', (array)($_POST['tag_ids'] ?? []));
    $data = [
        'nazev_cz' => trim((string)($_POST['nazev_cz'] ?? '')),
        'nazev_en' => trim((string)($_POST['nazev_en'] ?? '')),
        'url_cz' => trim((string)($_POST['url_cz'] ?? '')),
        'url_en' => trim((string)($_POST['url_en'] ?? '')),
        'perex_cz' => str_replace("\r\n", '', (string)($_POST['perex_cz'] ?? '')),
        'perex_en' => str_replace("\r\n", '', (string)($_POST['perex_en'] ?? '')),
        'text_cz' => str_replace("\r\n", '', (string)($_POST['text_cz'] ?? '')),
        'text_en' => str_replace("\r\n", '', (string)($_POST['text_en'] ?? '')),
        'seo_title_cz' => trim((string)($_POST['seo_title_cz'] ?? '')),
        'seo_title_en' => trim((string)($_POST['seo_title_en'] ?? '')),
        'seo_description_cz' => trim((string)($_POST['seo_description_cz'] ?? '')),
        'seo_description_en' => trim((string)($_POST['seo_description_en'] ?? '')),
    ];

    $existingNewsIcoStmt = $pdo->prepare('SELECT news_ico FROM news WHERE id = :id LIMIT 1');
    $existingNewsIcoStmt->execute([':id' => $edit]);
    $existingNewsIco = (string)$existingNewsIcoStmt->fetchColumn();

    if (!empty($_POST['delete_image'])) {
        news_ico_delete($edit, false);
        $soubor = '';
    } else {
        $soubor = (string)news_photo_add();
        if ($soubor !== '' && $existingNewsIco !== '' && $soubor !== $existingNewsIco) {
            news_ico_delete($edit, false);
        }
    }
    news_edit_multilang($edit, $datum, $news_typ, $data, $galerie_id, $visible, $valid, $soubor, $tagIds);
    echo '<div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>Novinka byla uložena.</div>';
}

$stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $edit]);
$dev = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dev) {
    echo '<div class="alert alert-danger">Záznam nenalezen.</div>';
    return;
}

$datum = (string)($dev['datum'] ?? date('Y-m-d'));
$galerie_id = (int)($dev['galerie_id'] ?? 0);
$visible = (int)($dev['visible'] ?? 0);
$news_typ = (int)($dev['news_typ'] ?? 0);
$valid = (int)($dev['valid'] ?? 0);
$tagIds = news_tag_ids_for_news($edit);
$checked = news_visible_checked($visible);
?>

<div class="card-body">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-2">
                <label for="datum" class="form-label">Datum</label>
                <input type="date" name="datum" id="datum" class="form-control" required value="<?= htmlspecialchars($datum, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-4">
                <label for="news_typ" class="form-label">Typ novinky</label>
                <select name="news_typ" id="news_typ" class="form-select" required>
                    <option value="">Vyberte typ</option>
                    <?php news_typ_option_form((string)$news_typ); ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="userfile" class="form-label">Obrázek novinky</label>
                <input type="file" name="userfile" id="userfile" class="form-control" accept="image/*">
                <?php if ((string)($dev['news_ico'] ?? '') !== ''): ?>
                    <div class="form-text">Aktuálně: <?= htmlspecialchars((string)$dev['news_ico'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image" value="1">
                        <label class="form-check-label" for="delete_image">smazat aktuální obrázek</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <label class="form-label d-block">Zobrazit</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="visible_cz" id="visible_cz" value="1" <?= $checked['cz'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="visible_cz">CZ</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="visible_en" id="visible_en" value="1" <?= $checked['en'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="visible_en">EN</label>
                </div>
            </div>

            <div class="col-md-6">
                <label for="galerie_id" class="form-label">Přiřazená fotogalerie</label>
                <select name="galerie_id" id="galerie_id" class="form-select">
                    <option value="0">Bez galerie</option>
                    <?php foreach ($galleries as $gallery): ?>
                        <option value="<?= (int)$gallery['id'] ?>" <?= (int)$gallery['id'] === $galerie_id ? 'selected' : '' ?>>
                            #<?= (int)$gallery['id'] ?> - <?= htmlspecialchars((string)$gallery['nazev_cz'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label d-block">Štítky</label>
                <?php if ($tags === []): ?>
                    <div class="text-muted small">Nejsou založené žádné štítky novinek.</div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($tags as $tag): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tag_ids[]" id="news_tag_<?= (int)$tag['id'] ?>" value="<?= (int)$tag['id'] ?>" <?= in_array((int)$tag['id'], $tagIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="news_tag_<?= (int)$tag['id'] ?>"><?= htmlspecialchars((string)$tag['nazev_cz'], ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <ul class="nav nav-tabs admin-lang-tabs" id="newsLangTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="news-cz-tab" data-bs-toggle="tab" data-bs-target="#news-cz-pane" type="button" role="tab" aria-controls="news-cz-pane" aria-selected="true">CZ</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="news-en-tab" data-bs-toggle="tab" data-bs-target="#news-en-pane" type="button" role="tab" aria-controls="news-en-pane" aria-selected="false">EN</button>
                    </li>
                </ul>

                <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3" id="newsLangTabsContent">
                    <div class="tab-pane fade show active" id="news-cz-pane" role="tabpanel" aria-labelledby="news-cz-tab" tabindex="0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nazev_cz" class="form-label">Název novinky CZ</label>
                                <input type="text" name="nazev_cz" id="nazev_cz" class="form-control" required value="<?= htmlspecialchars((string)($dev['nazev_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-translate-source="nazev" data-translate-format="text">
                            </div>
                            <div class="col-md-6">
                                <label for="url_cz" class="form-label">URL CZ</label>
                                <input type="text" name="url_cz" id="url_cz" class="form-control" value="<?= htmlspecialchars((string)($dev['url_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z data a názvu">
                            </div>
                            <div class="col-12">
                                <label for="perex_cz" class="form-label">Perex CZ</label>
                                <textarea name="perex_cz" id="perex_cz" class="form-control js-tinymce" rows="5" data-tinymce-height="240" data-translate-source="perex" data-translate-format="html"><?= (string)($dev['perex_cz'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label for="text_cz" class="form-label">Text novinky CZ</label>
                                <textarea name="text_cz" id="text_cz" class="form-control js-tinymce" rows="12" data-translate-source="text" data-translate-format="html"><?= (string)($dev['text_cz'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="seo_title_cz" class="form-label">SEO titulek CZ</label>
                                <input type="text" name="seo_title_cz" id="seo_title_cz" class="form-control" value="<?= htmlspecialchars((string)($dev['seo_title_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="prázdné = název novinky" data-translate-source="seo_title" data-translate-format="text">
                            </div>
                            <div class="col-md-6">
                                <label for="seo_description_cz" class="form-label">SEO popis CZ</label>
                                <textarea name="seo_description_cz" id="seo_description_cz" class="form-control" rows="3" placeholder="prázdné = očištěný perex" data-translate-source="seo_description" data-translate-format="text"><?= htmlspecialchars((string)($dev['seo_description_cz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="news-en-pane" role="tabpanel" aria-labelledby="news-en-tab" tabindex="0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="text-muted small">EN pole lze předvyplnit překladem aktuálních CZ hodnot z tohoto formuláře.</div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".news-translate-status">
                                    <i class="bi bi-translate me-1"></i> přeložit aktuální CZ
                                </button>
                                <span class="small text-muted news-translate-status"></span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nazev_en" class="form-label">Název novinky EN</label>
                                <input type="text" name="nazev_en" id="nazev_en" class="form-control" value="<?= htmlspecialchars((string)($dev['nazev_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-translate-target="nazev">
                            </div>
                            <div class="col-md-6">
                                <label for="url_en" class="form-label">URL EN</label>
                                <input type="text" name="url_en" id="url_en" class="form-control" value="<?= htmlspecialchars((string)($dev['url_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z data a názvu EN">
                            </div>
                            <div class="col-12">
                                <label for="perex_en" class="form-label">Perex EN</label>
                                <textarea name="perex_en" id="perex_en" class="form-control js-tinymce" rows="5" data-tinymce-height="240" data-translate-target="perex"><?= (string)($dev['perex_en'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label for="text_en" class="form-label">Text novinky EN</label>
                                <textarea name="text_en" id="text_en" class="form-control js-tinymce" rows="12" data-translate-target="text"><?= (string)($dev['text_en'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="seo_title_en" class="form-label">SEO titulek EN</label>
                                <input type="text" name="seo_title_en" id="seo_title_en" class="form-control" value="<?= htmlspecialchars((string)($dev['seo_title_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="prázdné = název novinky EN" data-translate-target="seo_title">
                            </div>
                            <div class="col-md-6">
                                <label for="seo_description_en" class="form-label">SEO popis EN</label>
                                <textarea name="seo_description_en" id="seo_description_en" class="form-control" rows="3" placeholder="prázdné = očištěný perex EN" data-translate-target="seo_description"><?= htmlspecialchars((string)($dev['seo_description_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <?= admin_auto_translate_checkbox($dev ?? null, 'news_auto_translate_en') ?>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= $valid === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="valid">valid</label>
                </div>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <input type="hidden" name="add" value="2">
                <button type="submit" class="btn btn-primary w-100">Uložit novinku</button>
            </div>

            <div class="col-12 small text-muted">
                Založeno: <?= isset($dev['ts_i']) ? htmlspecialchars((string)format_datetime_www((string)$dev['ts_i']), ENT_QUOTES, 'UTF-8') : '' ?>;
                Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                Upraveno: <?= isset($dev['ts_u']) ? htmlspecialchars((string)format_datetime_www((string)$dev['ts_u']), ENT_QUOTES, 'UTF-8') : '' ?>;
                Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </form>
</div>
