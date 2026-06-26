<?php
declare(strict_types=1);

include "functions/fun_galerie.php";
global $pdo;

// lang whitelist
$lang = isset($_GET['lang']) ? (string)$_GET['lang'] : 'cz';
$lang = in_array($lang, ['cz', 'en'], true) ? $lang : 'cz';

// POST
$nazev_cz   = (string)($_POST['nazev_cz'] ?? '');
$nazev_en   = (string)($_POST['nazev_en'] ?? '');
$datum      = (string)($_POST['datum'] ?? '');
$news_typ   = (string)($_POST['news_typ'] ?? '');
$galerie_id = isset($_POST['galerie_id']) ? (int)$_POST['galerie_id'] : 0;
$visible    = isset($_POST['visible']) ? (int)$_POST['visible'] : 0;

$text_cz = (string)($_POST['editor'] ?? ''); // TinyMCE posílá HTML
$text_cz = str_replace("\r\n", '', $text_cz);

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row g-3">

                <div class="col-md-5">
                    <label for="nazev_cz" class="form-label">Název novinky (<?= htmlspecialchars($lang, ENT_QUOTES) ?>)</label>
                    <input type="text" name="nazev_cz" id="nazev_cz" class="form-control"
                           value="<?= htmlspecialchars($nazev_cz, ENT_QUOTES) ?>">
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

                <div class="col-md-3">
                    <label for="userfile" class="form-label">Obrázek novinky</label>
                    <input type="file" name="userfile" id="userfile" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="visible" class="form-label">Zobrazit</label>
                    <select name="visible" id="visible" class="form-select">
                        <option value="1" <?= ($visible === 1 ? 'selected' : '') ?>>Ano</option>
                        <option value="2" <?= ($visible === 2 ? 'selected' : '') ?>>Ano, pouze CZ</option>
                        <option value="3" <?= ($visible === 3 ? 'selected' : '') ?>>Ano, pouze EN</option>
                        <option value="0" <?= ($visible === 0 ? 'selected' : '') ?>>Ne</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="editor" class="form-label">Text novinky</label>
                    <!-- TinyMCE = HTML, takže tady NE escapovat přes htmlspecialchars, jinak uvidíš tagy -->
                    <textarea name="editor" id="editor" class="form-control js-tinymce" rows="12"><?= $text_cz ?></textarea>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit novinku</button>
                </div>

            </div>
        </form>

    <?php elseif ($add === 1): ?>

        <?php
        $news_maxid = (int)news_maxid();
        $soubor = news_photo_add($news_maxid);
        news_add($datum, $news_typ, $nazev_cz, $text_cz, $galerie_id, $visible, $soubor);
        ?>

    <?php endif; ?>
</div>