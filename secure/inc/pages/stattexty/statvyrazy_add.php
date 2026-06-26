<?php
declare(strict_types=1);

global $pdo;

$code = trim((string)($_POST['code'] ?? ''));
$cz = (string)($_POST['cz'] ?? '');
$en = (string)($_POST['en'] ?? '');
$cz = str_replace("\r\n", '', $cz);
$en = str_replace("\r\n", '', $en);

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

if ($add === 1 && trim(strip_tags($cz)) === '' && trim(strip_tags($en)) === '') {
    $add = 0;
    echo '<div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Nevyplnil jsi CZ ani EN - výraz nebyl uložen.
          </div>';
}
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" autocomplete="off">
            <div class="row g-3">

                <div class="col-md-6">
                    <label for="code" class="form-label">Kód výrazu</label>
                    <input type="text" name="code" id="code" class="form-control"
                           value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="např. home_gateway_order_text">
                </div>

                <div class="col-12">
                    <label for="cz" class="form-label">Statický výraz (cz)</label>
                    <textarea name="cz" id="cz" class="form-control js-tinymce" rows="8" data-tinymce-height="260"><?= $cz ?></textarea>
                </div>

                <div class="col-12">
                    <label for="en" class="form-label">Statický výraz (en)</label>
                    <textarea name="en" id="en" class="form-control js-tinymce" rows="8" data-tinymce-height="260"><?= $en ?></textarea>
                </div>

                <div class="col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        Vložit statický výraz
                    </button>
                </div>

            </div>
        </form>

    <?php elseif ($add === 1): ?>

        <?php statvyrazy_add($code, $cz, $en); ?>

    <?php endif; ?>
</div>
