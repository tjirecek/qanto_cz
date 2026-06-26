<?php
declare(strict_types=1);

// menu_edit.php (BS5 + PDO)

global $pdo;

// ID z GET
$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// POST (při uložení)
$url_cz   = trim((string)($_POST['url_cz'] ?? ''));
$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$menu     = isset($_POST['menu']) ? (int)$_POST['menu'] : 0;
$valid    = isset($_POST['valid']) ? 1 : 0;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger mb-0">CHYBA: Neplatné ID (edit).</div>';
    return;
}

// načtení záznamu, pokud se má zobrazit formulář
$dev = [];
if ($add === 0) {
    $stmt = $pdo->prepare('SELECT * FROM users_menu WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $dev = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$dev) {
        echo '<div class="alert alert-warning mb-0">Záznam menu nebyl nalezen.</div>';
        return;
    }

    $url_cz   = (string)($dev['url_cz'] ?? '');
    $nazev_cz = (string)($dev['nazev_cz'] ?? '');
    $menu     = (int)($dev['menu'] ?? 0);
    $valid    = (int)($dev['valid'] ?? 0);
}
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

                <div class="col-12 col-md-2">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="valid" id="valid" value="1" <?php echo ($valid === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-success w-100">Uložit menu</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?php echo isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : ''; ?>;
                    Založil: <?php echo htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>;
                    Upraveno: <?php echo isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : ''; ?>;
                    Upravil: <?php echo htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </div>

            </div>
        </form>

    <?php elseif ($add === 2): ?>

        <?php menu_edit($id, $url_cz, $nazev_cz, $menu, $valid); ?>

    <?php endif; ?>
</div>