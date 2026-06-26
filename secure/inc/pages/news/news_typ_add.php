<?php
declare(strict_types=1);

// news_typ_add.php (BS5 + bez mysqli)
// DB logika je ve fun_news.php -> news_typ_add(...)

$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$nazev_en = trim((string)($_POST['nazev_en'] ?? ''));
$popis_cz = trim((string)($_POST['popis_cz'] ?? ''));
$popis_en = trim((string)($_POST['popis_en'] ?? ''));
$poradi   = isset($_POST['poradi']) ? (int)$_POST['poradi'] : 1;
$color    = trim((string)($_POST['color'] ?? ''));

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php if ($add === 0): ?>

        <form method="post" class="needs-validation" novalidate>
            <div class="row g-3">

                <div class="col-md-4">
                    <label for="nazev_cz" class="form-label">Název (cz)</label>
                    <input type="text" name="nazev_cz" id="nazev_cz"
                           class="form-control"
                           value="<?php echo htmlspecialchars($nazev_cz, ENT_QUOTES); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="popis_cz" class="form-label">Popis (cz)</label>
                    <input type="text" name="popis_cz" id="popis_cz"
                           class="form-control"
                           value="<?php echo htmlspecialchars($popis_cz, ENT_QUOTES); ?>">
                </div>

                <div class="col-md-2">
                    <label for="poradi" class="form-label">Pořadí</label>
                    <input type="number" name="poradi" id="poradi"
                           class="form-control"
                           value="<?php echo (int)$poradi; ?>" min="0">
                </div>

                <div class="col-md-2">
                    <label for="color" class="form-label">Color</label>
                    <input type="text" name="color" id="color"
                           class="form-control"
                           value="<?php echo htmlspecialchars($color, ENT_QUOTES); ?>"
                           placeholder="#ee4c50 nebo text">
                </div>

                <?php if (en_on() == 1): ?>
                    <div class="col-md-4">
                        <label for="nazev_en" class="form-label">Název (en)</label>
                        <input type="text" name="nazev_en" id="nazev_en"
                               class="form-control"
                               value="<?php echo htmlspecialchars($nazev_en, ENT_QUOTES); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="popis_en" class="form-label">Popis (en)</label>
                        <input type="text" name="popis_en" id="popis_en"
                               class="form-control"
                               value="<?php echo htmlspecialchars($popis_en, ENT_QUOTES); ?>">
                    </div>
                <?php endif; ?>

                <div class="col-md-4 d-flex align-items-end">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        Vložit typ novinek
                    </button>
                </div>

            </div>
        </form>

    <?php else: ?>

        <?php
        // ukládání (PDO implementace musí být ve fun_news.php)
        news_typ_add($nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en, $color);
        ?>

    <?php endif; ?>
</div>