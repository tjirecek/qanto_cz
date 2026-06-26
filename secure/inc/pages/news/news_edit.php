<?php
declare(strict_types=1);

include "functions/fun_galerie.php";
global $pdo;

// lang whitelist
$lang = isset($_GET['lang']) ? (string)$_GET['lang'] : 'cz';
$lang = in_array($lang, ['cz', 'en'], true) ? $lang : 'cz';

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// POST
$nazev      = (string)($_POST['nazev'] ?? '');
$url        = (string)($_POST['url'] ?? '');
$datum      = (string)($_POST['datum'] ?? '');
$news_typ   = (string)($_POST['news_typ'] ?? '');
$galerie_id = isset($_POST['galerie_id']) ? (int)$_POST['galerie_id'] : 0;
$visible    = isset($_POST['visible']) ? (int)$_POST['visible'] : 0;
$valid      = isset($_POST['valid']) ? 1 : 0;

$text = (string)($_POST['editor'] ?? '');
$text = str_replace("\r\n", '', $text);

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <?php
        // načti novinku
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $edit]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ($lang === 'cz') {
            $nazev = (string)($dev['nazev_cz'] ?? '');
            $url   = (string)($dev['url_cz'] ?? '');
            $text  = (string)($dev['text_cz'] ?? '');
        } else {
            $nazev = (string)($dev['nazev_en'] ?? '');
            $url   = (string)($dev['url_en'] ?? '');
            $text  = (string)($dev['text_en'] ?? '');
        }

        $datum      = (string)($dev['datum'] ?? '');
        $galerie_id = (int)($dev['galerie_id'] ?? 0);
        $visible    = (int)($dev['visible'] ?? 0);
        $news_typ   = (string)($dev['news_typ'] ?? '');
        $valid      = (int)($dev['valid'] ?? 0);
        ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row g-3">

                <div class="col-md-5">
                    <label for="nazev" class="form-label">Název novinky (<?= htmlspecialchars($lang, ENT_QUOTES) ?>)</label>
                    <input type="text" name="nazev" id="nazev" class="form-control"
                           value="<?= htmlspecialchars($nazev, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-2">
                    <label for="datum" class="form-label">Datum</label>
                    <input type="date" name="datum" id="datum" class="form-control"
                           value="<?= htmlspecialchars($datum, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-2">
                    <label for="news_typ" class="form-label">Typ novinky</label>
                    <select name="news_typ" id="news_typ" class="form-select">
                        <?php news_typ_option_form($news_typ); ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="galerie_id" class="form-label">ID galerie</label>
                    <input type="number" name="galerie_id" id="galerie_id" class="form-control"
                           value="<?= (int)$galerie_id ?>">
                </div>

                <div class="col-md-4">
                    <label for="url" class="form-label">URL (<?= htmlspecialchars($lang, ENT_QUOTES) ?>)</label>
                    <input type="text" name="url" id="url" class="form-control"
                           value="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-4">
                    <label for="userfile" class="form-label">Obrázek novinky</label>
                    <input type="file" name="userfile" id="userfile" class="form-control">
                </div>

                <div class="col-md-4">
                    <label for="visible" class="form-label">Zobrazit</label>
                    <select name="visible" id="visible" class="form-select">
                        <option value="1" <?= ($visible === 1 ? 'selected' : '') ?>>Ano</option>
                        <option value="2" <?= ($visible === 2 ? 'selected' : '') ?>>Ano, pouze CZ</option>
                        <option value="3" <?= ($visible === 3 ? 'selected' : '') ?>>Ano, pouze EN</option>
                        <option value="0" <?= ($visible === 0 ? 'selected' : '') ?>>Ne</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ($valid === 1 ? 'checked' : '') ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="editor">Text novinky</label>
                    <!-- TinyMCE = HTML, takže NEpoužívat htmlspecialchars -->
                    <textarea name="editor" id="editor" class="form-control js-tinymce" rows="12"><?= $text ?></textarea>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-primary w-100">Upravit novinku</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                    Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES) ?>;
                    Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                    Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES) ?>
                </div>

            </div>
        </form>

    <?php elseif ($add === 2): ?>

        <?php
        $news_maxid = (int)news_maxid();
        $soubor = news_photo_add($news_maxid);

        news_edit($edit, $datum, $news_typ, $nazev, $text, $galerie_id, $visible, $lang, $url, $valid, $soubor);
        ?>

    <?php endif; ?>
</div>