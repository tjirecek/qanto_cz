<?php
declare(strict_types=1);

// ukládání řeší users_skup_add() ve fun_system.php

$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$poradi   = isset($_POST['poradi']) ? (int)$_POST['poradi'] : 1;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<?php if ($add === 0): ?>

    <form method="post" class="needs-validation" novalidate>
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-4">
                <label for="nazev_cz" class="form-label">Název (cz)</label>
                <input
                        type="text"
                        name="nazev_cz"
                        id="nazev_cz"
                        class="form-control"
                        value="<?= htmlspecialchars($nazev_cz, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                >
            </div>

            <div class="col-12 col-md-2">
                <label for="poradi" class="form-label">Pořadí</label>
                <input
                        type="number"
                        name="poradi"
                        id="poradi"
                        class="form-control"
                        value="<?= (int)$poradi; ?>"
                        min="1"
                        step="1"
                        required
                >
            </div>

            <div class="col-12 col-md-3">
                <input type="hidden" name="add" value="1">
                <button type="submit" class="btn btn-primary w-100">
                    Vložit skupinu uživatelů
                </button>
            </div>

        </div>
    </form>

<?php elseif ($add === 1): ?>

    <?php users_skup_add($nazev_cz, $poradi); ?>

<?php endif; ?>