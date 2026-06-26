<?php
declare(strict_types=1);

global $pdo;

// --- vstupy ---
$id       = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$nazev_cz = trim((string)($_POST['nazev_cz'] ?? ''));
$poradi   = isset($_POST['poradi']) ? (int)$_POST['poradi'] : 1;
$valid    = isset($_POST['valid']) ? 1 : 0;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

// --- load record (jen když zobrazuju formulář) ---
$dev = null;

if ($add === 0) {
    if ($id <= 0) {
        echo '<div class="alert alert-danger mb-0">CHYBA: Neplatné ID.</div>';
        $add = -1;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users_skup WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dev) {
            echo '<div class="alert alert-danger mb-0">CHYBA: Záznam nebyl nalezen.</div>';
            $add = -1;
        } else {
            $nazev_cz = (string)($dev['nazev_cz'] ?? '');
            $poradi   = (int)($dev['poradi'] ?? 1);
            $valid    = (int)($dev['valid'] ?? 0);
        }
    }
}
?>

<?php if ($add === 0): ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-secondary">ID: <?= (int)$id ?></span>
            <?php if ($valid === 1): ?>
                <span class="badge text-bg-success">valid</span>
            <?php else: ?>
                <span class="badge text-bg-warning">nevalidní</span>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" class="needs-validation" novalidate>
        <div class="row g-3 align-items-end">

            <div class="col-12 col-md-5">
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

            <div class="col-12 col-md-2">
                <div class="form-check form-switch m-0">
                    <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            name="valid"
                            id="valid"
                            value="1"
                            <?= ($valid === 1) ? 'checked' : ''; ?>
                    >
                    <label class="form-check-label" for="valid">valid</label>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <input type="hidden" name="add" value="2">
                <button type="submit" class="btn btn-primary w-100">
                    Uložit skupinu uživatelů
                </button>
            </div>

            <?php if (is_array($dev)): ?>
                <div class="col-12">
                    <div class="small text-muted">
                        Založeno: <?= htmlspecialchars((string)format_datetime_www($dev['ts_i'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>;
                        Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>;
                        Upraveno: <?= htmlspecialchars((string)format_datetime_www($dev['ts_u'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>;
                        Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </form>

<?php elseif ($add === 2): ?>

    <?php
    // uloží funkce z fun_system.php
    users_skup_edit($id, $nazev_cz, $poradi, $valid);
    ?>

<?php endif; ?>