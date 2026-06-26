<?php
declare(strict_types=1);

// news_typ_edit.php (BS5 + bez mysqli)
// Načítání řádku je přes PDO, ukládání volá funkci news_typ_edit(...) ve fun_news.php (tak jak máš)

global $pdo;

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$nazev_en = trim((string)($_POST['nazev_en'] ?? ''));
$popis_cz = trim((string)($_POST['popis_cz'] ?? ''));
$popis_en = trim((string)($_POST['popis_en'] ?? ''));
$poradi   = isset($_POST['poradi']) ? (int)$_POST['poradi'] : 1;
$color    = trim((string)($_POST['color'] ?? ''));
$valid    = isset($_POST['valid']) ? 1 : 0;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
?>

<div class="card-body">
    <?php
    if ($add === 0):

        if ($id <= 0) {
            echo '<div class="alert alert-danger mb-0">Chybí parametr edit (ID).</div>';
        } else {

            // Načtení aktuálních hodnot přes PDO
            $stmt = $pdo->prepare('SELECT * FROM news_typ WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $dev = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $nazev_cz = (string)($dev['nazev_cz'] ?? '');
            $nazev_en = (string)($dev['nazev_en'] ?? '');
            $popis_cz = (string)($dev['popis_cz'] ?? '');
            $popis_en = (string)($dev['popis_en'] ?? '');
            $poradi   = (int)($dev['poradi'] ?? 1);
            $color    = (string)($dev['color'] ?? '');
            $valid    = (int)($dev['valid'] ?? 0);
            ?>

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

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1"
                                    <?php echo ($valid === 1 ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="valid">valid</label>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" name="add" value="2">
                        <button type="submit" class="btn btn-primary w-100">Uložit typ novinek</button>
                    </div>

                    <div class="col-12 small text-muted">
                        Založeno: <?php echo isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : ''; ?>;
                        Založil: <?php echo htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES); ?>;
                        Upraveno: <?php echo isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : ''; ?>;
                        Upravil: <?php echo htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES); ?>
                    </div>

                </div>
            </form>

            <?php
        }

    elseif ($add === 2):

        // Ukládání řeší fun_news.php (PDO)
        news_typ_edit($id, $nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en, $color, $valid);

    endif;
    ?>
</div>