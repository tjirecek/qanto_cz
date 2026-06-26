<?php
declare(strict_types=1);

// menu_add.php (BS5) – ukládání řeší menu_add() ve fun_system.php

$url_cz   = trim((string)($_POST['url_cz'] ?? ''));
$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$menu     = isset($_POST['menu']) ? (int)$_POST['menu'] : 0;

$add = isset($_POST['add']) ? 1 : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" autocomplete="off" class="needs-validation" novalidate>
            <div class="row g-3 align-items-end">

                <div class="col-12 col-md-2">
                    <label for="menu" class="form-label">Číslo menu</label>
                    <input type="number" name="menu" id="menu" class="form-control"
                           value="<?php echo (int)$menu; ?>" min="0" step="1" required>
                </div>

                <div class="col-12 col-md-5">
                    <label for="url_cz" class="form-label">URL (cz)</label>
                    <input type="text" name="url_cz" id="url_cz" class="form-control"
                           value="<?php echo htmlspecialchars($url_cz, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="col-12 col-md-5">
                    <label for="nazev_cz" class="form-label">Název (cz)</label>
                    <input type="text" name="nazev_cz" id="nazev_cz" class="form-control"
                           value="<?php echo htmlspecialchars($nazev_cz, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="col-12 col-md-3">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        Vložit menu
                    </button>
                </div>

            </div>
        </form>

    <?php else: ?>

        <?php menu_add($url_cz, $nazev_cz, $menu); ?>

    <?php endif; ?>
</div>