<?php
declare(strict_types=1);

// system_edit.php (BS5 + PDO) – kompaktnější formulář (inputy na 1 řádek v kódu)

global $pdo;

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$typ          = trim((string)($_POST['typ'] ?? ''));
$popis_cz     = trim((string)($_POST['popis_cz'] ?? ''));
$name         = trim((string)($_POST['name'] ?? ''));
$hodnota      = (string)($_POST['hodnota'] ?? '0');
$hodnota_text = trim((string)($_POST['hodnota_text'] ?? ''));
$valid        = isset($_POST['valid']) ? 1 : 0;

$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;

// default pro případ, že záznam neexistuje
$dev = [];
?>

<div class="card-body">
    <?php
    if ($add === 0):

        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM settings WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $dev = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        $typ          = (string)($dev['typ'] ?? $typ);
        $name         = (string)($dev['name'] ?? $name);
        $popis_cz     = (string)($dev['popis_cz'] ?? $popis_cz);
        $hodnota      = (string)($dev['hodnota'] ?? $hodnota);
        $hodnota_text = (string)($dev['hodnota_text'] ?? $hodnota_text);
        $valid        = (int)($dev['valid'] ?? $valid);
        ?>

        <form method="post" autocomplete="off" class="needs-validation" novalidate>
            <div class="row g-3 align-items-end">

                <div class="col-12 col-xl-2">
                    <label for="typ" class="form-label">Typ systémové hodnoty</label>
                    <select name="typ" id="typ" class="form-select">
                        <option value="admin" <?= $typ === 'admin' ? 'selected' : '' ?>>admin</option>
                        <option value="main"  <?= $typ === 'main'  ? 'selected' : '' ?>>main</option>
                        <?php if ($typ !== '' && !in_array($typ, ['admin', 'main'], true)): ?>
                            <option value="<?= htmlspecialchars($typ, ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($typ, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-12 col-xl-3">
                    <label for="name" class="form-label">Systémový název hodnoty</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12 col-xl-4">
                    <label for="popis_cz" class="form-label">Popis systémové hodnoty</label>
                    <input type="text" name="popis_cz" id="popis_cz" class="form-control" value="<?= htmlspecialchars($popis_cz, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12 col-xl-1">
                    <label for="hodnota" class="form-label">Hodnota</label>
                    <input type="text" name="hodnota" id="hodnota" class="form-control" value="<?= htmlspecialchars($hodnota, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12">
                    <label for="hodnota_text" class="form-label">Hodnota - text</label>
                    <textarea name="hodnota_text" id="hodnota_text" class="form-control js-tinymce" rows="10" data-tinymce-height="360"><?= htmlspecialchars($hodnota_text, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="col-12 col-xl-2 d-flex justify-content-xl-center">
                    <div class="form-check form-switch mt-4 mt-xl-0">
                        <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= $valid === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="valid">valid</label>
                    </div>
                </div>

                <div class="col-12 col-xl-2">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-success w-100">Uložit systémovou proměnnou</button>
                </div>

                <div class="col-12 small text-muted">
                    Založeno: <?= isset($dev['ts_i']) ? format_datetime_www($dev['ts_i']) : '' ?>;
                    Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;
                    Upraveno: <?= isset($dev['ts_u']) ? format_datetime_www($dev['ts_u']) : '' ?>;
                    Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>

            </div>
        </form>

    <?php
    elseif ($add === 2):
        // ukládání přes fun_system.php (PDO)
        settings_edit($id, $typ, $name, $popis_cz, (float)$hodnota, $hodnota_text, $valid);
    endif;
    ?>
</div>
