<?php
declare(strict_types=1);

include "functions/fun_galerie.php";

// lang (pokud chceš whitelist jako u news_add, klidně doplním)
$lang = isset($_GET['lang']) ? (string)$_GET['lang'] : 'cz';

// POST
$nazev_cz   = trim((string)($_POST['nazev_cz'] ?? ''));
$nazev_en   = trim((string)($_POST['nazev_en'] ?? '')); // zatím nepoužito
$code       = trim((string)($_POST['code'] ?? ''));
$galerie_id = isset($_POST['galerie_id']) ? (int)$_POST['galerie_id'] : 0;
$col        = isset($_POST['col']) ? (int)$_POST['col'] : 12;

$text_cz = (string)($_POST['editor'] ?? ''); // TinyMCE posílá HTML
$text_cz = str_replace("\r\n", '', $text_cz);

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nazev_cz" class="form-label">
                        Název statického textu (<?= htmlspecialchars($lang, ENT_QUOTES) ?>)
                    </label>
                    <input type="text" name="nazev_cz" id="nazev_cz" class="form-control"
                           value="<?= htmlspecialchars($nazev_cz, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-4">
                    <label for="code" class="form-label">Kód textu</label>
                    <input type="text" name="code" id="code" class="form-control"
                           value="<?= htmlspecialchars($code, ENT_QUOTES) ?>"
                           placeholder="např. privacy_policy">
                </div>

                <div class="col-md-2">
                    <label for="galerie_id" class="form-label">ID galerie</label>
                    <input type="number" name="galerie_id" id="galerie_id" class="form-control"
                           value="<?= (int)$galerie_id ?>">
                </div>

                <div class="col-md-2">
                    <label for="col" class="form-label">Sloupců</label>
                    <input type="number" name="col" id="col" class="form-control"
                           value="<?= (int)$col ?>">
                </div>

                <div class="col-12">
                    <label for="editor" class="form-label">Text</label>
                    <!-- TinyMCE = HTML, takže NE escapovat přes htmlspecialchars -->
                    <textarea name="editor" id="editor" class="form-control js-tinymce" rows="12"><?= $text_cz ?></textarea>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit statický text</button>
                </div>
            </div>
        </form>

    <?php elseif ($add === 1): ?>

        <?php stattexty_add($code, $nazev_cz, $text_cz, $galerie_id, $col); ?>

    <?php endif; ?>
</div>
