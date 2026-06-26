<?php
declare(strict_types=1);

// system_add.php (BS5) – ukládání řeší settings_add() ve fun_system.php

$typ          = trim((string)($_POST['typ'] ?? 'main'));
$name         = trim((string)($_POST['name'] ?? ''));
$popis_cz     = trim((string)($_POST['popis_cz'] ?? ''));
$hodnota_raw  = trim((string)($_POST['hodnota'] ?? '0'));
$hodnota_text = trim((string)($_POST['hodnota_text'] ?? ''));
$add          = isset($_POST['add']) ? (int)$_POST['add'] : 0;

// whitelist typů
$typ = in_array($typ, ['admin', 'main'], true) ? $typ : 'main';

// tolerantní převod čísla (1,5 -> 1.5)
$hodnota_num = (float)str_replace(',', '.', $hodnota_raw);
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" autocomplete="off" class="needs-validation" novalidate>
            <div class="row g-3 align-items-end">

                <div class="col-12 col-xl-2">
                    <label for="typ" class="form-label">Typ</label>
                    <select name="typ" id="typ" class="form-select">
                        <option value="admin" <?= $typ === 'admin' ? 'selected' : '' ?>>admin</option>
                        <option value="main"  <?= $typ === 'main'  ? 'selected' : '' ?>>main</option>
                    </select>
                </div>

                <div class="col-12 col-xl-3">
                    <label for="name" class="form-label">Systémový název</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="col-12 col-xl-4">
                    <label for="popis_cz" class="form-label">Popis</label>
                    <input type="text" name="popis_cz" id="popis_cz" class="form-control" value="<?= htmlspecialchars($popis_cz, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="col-12 col-xl-1">
                    <label for="hodnota" class="form-label">Hodnota</label>
                    <input type="text" name="hodnota" id="hodnota" class="form-control" value="<?= htmlspecialchars($hodnota_raw, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12">
                    <label for="hodnota_text" class="form-label">Hodnota (text)</label>
                    <textarea name="hodnota_text" id="hodnota_text" class="form-control js-tinymce" rows="10" data-tinymce-height="360"><?= htmlspecialchars($hodnota_text, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="col-12 col-xl-2">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit systémovou proměnnou</button>
                </div>

            </div>
        </form>

    <?php else: ?>

        <?php settings_add($typ, $name, $popis_cz, $hodnota_num, $hodnota_text); ?>

    <?php endif; ?>
</div>
