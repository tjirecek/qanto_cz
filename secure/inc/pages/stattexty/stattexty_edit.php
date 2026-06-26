<?php
declare(strict_types=1);

include "functions/fun_galerie.php";
global $pdo;

// GET
$lang = isset($_GET['lang']) ? (string)$_GET['lang'] : 'cz';
$lang = in_array($lang, ['cz', 'en'], true) ? $lang : 'cz';

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// POST (defaulty – u add=0 se přepíšou z DB)
$nazev      = trim((string)($_POST['nazev'] ?? ''));
$code       = trim((string)($_POST['code'] ?? ''));
$galerie_id = isset($_POST['galerie_id']) ? (int)$_POST['galerie_id'] : 0;
$col        = isset($_POST['col']) ? (int)$_POST['col'] : 12;
$valid      = isset($_POST['valid']) ? 1 : 0;

$text = (string)($_POST['editor'] ?? ''); // TinyMCE posílá HTML
$text = str_replace("\r\n", '', $text);

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

// ochrana BS gridu
$col = max(1, min(12, $col));
?>

<div class="card-body">
    <?php
    if ($add === 0):

        // === PDO READ ===
        $stmt = $pdo->prepare('SELECT * FROM stat_texty WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $edit]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dev) {
            echo '<div class="alert alert-danger">Záznam nenalezen.</div>';
            return;
        }

        if ($lang === 'en') {
            $nazev = (string)($dev['nazev_en'] ?? '');
            $text  = (string)($dev['text_en'] ?? '');
        } else {
            $lang  = 'cz';
            $nazev = (string)($dev['nazev_cz'] ?? '');
            $text  = (string)($dev['text_cz'] ?? '');
        }

        $code       = (string)($dev['code'] ?? '');
        $galerie_id = (int)($dev['galerie_id'] ?? 0);
        $col        = max(1, min(12, (int)($dev['col'] ?? 12)));
        $valid      = (int)($dev['valid'] ?? 0);
        ?>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="row g-3">

                <div class="col-md-6">
                    <label for="nazev" class="form-label">
                        Název statického textu (<?= htmlspecialchars($lang, ENT_QUOTES) ?>)
                    </label>
                    <input type="text" name="nazev" id="nazev" class="form-control"
                           value="<?= htmlspecialchars($nazev, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-4">
                    <label for="code" class="form-label">Kód textu</label>
                    <input type="text" name="code" id="code" class="form-control"
                           value="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
                </div>

                <div class="col-md-2">
                    <label for="galerie_id" class="form-label">ID galerie</label>
                    <input type="number" name="galerie_id" id="galerie_id" class="form-control" value="<?= (int)$galerie_id ?>">
                </div>

                <div class="col-md-2">
                    <label for="col" class="form-label">Sloupců</label>
                    <input type="number" name="col" id="col" class="form-control" value="<?= (int)$col ?>">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ($valid === 1 ? 'checked' : '') ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-md-<?= (int)$col ?>">
                    <label for="editor" class="form-label">Text</label>
                    <!-- TinyMCE = HTML, takže tady NE escapovat přes htmlspecialchars -->
                    <textarea name="editor" id="editor" class="form-control js-tinymce" rows="12"><?= $text ?></textarea>
                </div>

                <div class="col-md-2">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-primary w-100">Upravit statický text</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                    Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES) ?>;
                    Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                    Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES) ?>
                </div>

            </div>
        </form>

    <?php
    elseif ($add === 2):
        // zápis řeší tvoje existující funkce
        stattexty_edit($edit, $code, $nazev, $text, $galerie_id, $col, $lang, $valid);
    endif;
    ?>
</div>
