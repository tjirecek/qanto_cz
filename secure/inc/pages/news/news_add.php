<?php
declare(strict_types=1);

require_once SEC_DIR . '/functions/fun_news.php';
require_once SEC_DIR . '/functions/fun_galerie.php';

$nazev_cz = (string)($_POST['nazev_cz'] ?? '');
$nazev_en = (string)($_POST['nazev_en'] ?? '');
$datum = (string)($_POST['datum'] ?? date('Y-m-d'));
$news_typ = (int)($_POST['news_typ'] ?? 0);
$galerie_id = (int)($_POST['galerie_id'] ?? 0);
$url_cz = (string)($_POST['url_cz'] ?? '');
$url_en = (string)($_POST['url_en'] ?? '');
$perex_cz = str_replace("\r\n", '', (string)($_POST['perex_cz'] ?? ''));
$perex_en = str_replace("\r\n", '', (string)($_POST['perex_en'] ?? ''));
$text_cz = str_replace("\r\n", '', (string)($_POST['text_cz'] ?? ''));
$text_en = str_replace("\r\n", '', (string)($_POST['text_en'] ?? ''));
$seo_title_cz = (string)($_POST['seo_title_cz'] ?? '');
$seo_title_en = (string)($_POST['seo_title_en'] ?? '');
$seo_description_cz = (string)($_POST['seo_description_cz'] ?? '');
$seo_description_en = (string)($_POST['seo_description_en'] ?? '');
$tagIds = array_map('intval', (array)($_POST['tag_ids'] ?? []));
$visible = $_SERVER['REQUEST_METHOD'] === 'POST' ? news_visible_from_post($_POST) : 2;
$checked = news_visible_checked($visible);
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
$tags = news_tags_all(1, 0);
$galleries = function_exists('galerie_all') ? galerie_all(null, 1, 0) : [];
?>

<div class="card-body">
    <?php if ($add === 0): ?>
        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="datum" class="form-label">Datum</label>
                    <input type="date" name="datum" id="datum" class="form-control" required value="<?= htmlspecialchars($datum, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-4">
                    <label for="news_typ" class="form-label">Typ novinky</label>
                    <select
                        name="news_typ"
                        id="news_typ"
                        class="form-select js-admin-single-picker"
                        data-picker-title="Vybrat typ novinky"
                        data-picker-description="Vyberte právě jeden typ, do kterého bude novinka zařazena."
                        data-picker-search-placeholder="Hledat podle názvu typu…"
                        data-picker-empty-label="Vyberte typ"
                        required
                    >
                        <option value="">Vyberte typ</option>
                        <?php news_typ_option_form((string)$news_typ); ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="userfile" class="form-label">Obrázek novinky</label>
                    <input type="file" name="userfile" id="userfile" class="form-control" accept="image/*">
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
                    <select
                        name="galerie_id"
                        id="galerie_id"
                        class="form-select js-admin-single-picker"
                        data-picker-title="Vybrat fotogalerii"
                        data-picker-description="Volitelně přiřaďte k novince jednu fotogalerii."
                        data-picker-search-placeholder="Hledat podle názvu galerie…"
                        data-picker-empty-label="Bez galerie"
                    >
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
                    <ul class="nav nav-tabs admin-lang-tabs" id="newsAddLangTabs" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" id="news-add-cz-tab" data-bs-toggle="tab" data-bs-target="#news-add-cz-pane" type="button" role="tab">CZ</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="news-add-en-tab" data-bs-toggle="tab" data-bs-target="#news-add-en-pane" type="button" role="tab">EN</button></li>
                    </ul>
                    <div class="tab-content admin-lang-tab-content border border-top-0 rounded-bottom p-3">
                        <div class="tab-pane fade show active" id="news-add-cz-pane" role="tabpanel" tabindex="0">
                            <div class="row g-3">
                                <div class="col-md-6"><label for="nazev_cz" class="form-label">Název novinky CZ</label><input type="text" name="nazev_cz" id="nazev_cz" class="form-control" required value="<?= htmlspecialchars($nazev_cz, ENT_QUOTES, 'UTF-8') ?>" data-translate-source="nazev" data-translate-format="text"></div>
                                <div class="col-md-6"><label for="url_cz" class="form-label">URL CZ</label><input type="text" name="url_cz" id="url_cz" class="form-control" value="<?= htmlspecialchars($url_cz, ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z data a názvu"></div>
                                <div class="col-12"><label for="perex_cz" class="form-label">Perex CZ</label><textarea name="perex_cz" id="perex_cz" class="form-control js-tinymce" rows="5" data-tinymce-height="240" data-translate-source="perex" data-translate-format="html"><?= $perex_cz ?></textarea></div>
                                <div class="col-12"><label for="text_cz" class="form-label">Text novinky CZ</label><textarea name="text_cz" id="text_cz" class="form-control js-tinymce" rows="12" data-translate-source="text" data-translate-format="html"><?= $text_cz ?></textarea></div>
                                <div class="col-md-6"><label for="seo_title_cz" class="form-label">SEO titulek CZ</label><input type="text" name="seo_title_cz" id="seo_title_cz" class="form-control" value="<?= htmlspecialchars($seo_title_cz, ENT_QUOTES, 'UTF-8') ?>" data-translate-source="seo_title" data-translate-format="text"></div>
                                <div class="col-md-6"><label for="seo_description_cz" class="form-label">SEO popis CZ</label><textarea name="seo_description_cz" id="seo_description_cz" class="form-control" rows="3" data-translate-source="seo_description" data-translate-format="text"><?= htmlspecialchars($seo_description_cz, ENT_QUOTES, 'UTF-8') ?></textarea></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="news-add-en-pane" role="tabpanel" tabindex="0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="text-muted small">EN pole lze předvyplnit překladem aktuálních CZ hodnot z tohoto formuláře.</div>
                                <div class="d-flex flex-wrap align-items-center gap-2"><button type="button" class="btn btn-sm btn-outline-primary" data-admin-translate="cs-en" data-translate-status-target=".news-translate-status"><i class="bi bi-translate me-1"></i> přeložit aktuální CZ</button><span class="small text-muted news-translate-status"></span></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label for="nazev_en" class="form-label">Název novinky EN</label><input type="text" name="nazev_en" id="nazev_en" class="form-control" value="<?= htmlspecialchars($nazev_en, ENT_QUOTES, 'UTF-8') ?>" data-translate-target="nazev"></div>
                                <div class="col-md-6"><label for="url_en" class="form-label">URL EN</label><input type="text" name="url_en" id="url_en" class="form-control" value="<?= htmlspecialchars($url_en, ENT_QUOTES, 'UTF-8') ?>" placeholder="automaticky z data a názvu EN"></div>
                                <div class="col-12"><label for="perex_en" class="form-label">Perex EN</label><textarea name="perex_en" id="perex_en" class="form-control js-tinymce" rows="5" data-tinymce-height="240" data-translate-target="perex"><?= $perex_en ?></textarea></div>
                                <div class="col-12"><label for="text_en" class="form-label">Text novinky EN</label><textarea name="text_en" id="text_en" class="form-control js-tinymce" rows="12" data-translate-target="text"><?= $text_en ?></textarea></div>
                                <div class="col-md-6"><label for="seo_title_en" class="form-label">SEO titulek EN</label><input type="text" name="seo_title_en" id="seo_title_en" class="form-control" value="<?= htmlspecialchars($seo_title_en, ENT_QUOTES, 'UTF-8') ?>" data-translate-target="seo_title"></div>
                                <div class="col-md-6"><label for="seo_description_en" class="form-label">SEO popis EN</label><textarea name="seo_description_en" id="seo_description_en" class="form-control" rows="3" data-translate-target="seo_description"><?= htmlspecialchars($seo_description_en, ENT_QUOTES, 'UTF-8') ?></textarea></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <?= admin_auto_translate_checkbox(null, 'news_add_auto_translate_en') ?>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit novinku</button>
                </div>
            </div>
        </form>
    <?php elseif ($add === 1): ?>
        <?php
        try {
            $soubor = (string)news_photo_add();
            $newsId = news_add($datum, $news_typ, $nazev_cz, $perex_cz, $text_cz, $galerie_id, $visible, $soubor, $url_cz, $seo_title_cz, $seo_description_cz, $tagIds, $nazev_en, $perex_en, $text_en, $url_en, $seo_title_en, $seo_description_en);
            if ($newsId <= 0) {
                throw new RuntimeException('Novinka nebyla vložena, databáze nevrátila ID nového záznamu.');
            }
        } catch (Throwable $e) {
            error_log('news_add page failed: ' . $e->getMessage());
            ?>
            <div class="alert alert-danger mb-3">
                <div class="fw-bold">Novinka nebyla vložena.</div>
                <div><?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a href="<?= htmlspecialchars(news_admin_url_with_show(1), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">Zpět na přidání novinky</a>
            <?php
        }
        ?>
    <?php endif; ?>
</div>
