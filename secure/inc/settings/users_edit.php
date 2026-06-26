<?php
declare(strict_types=1);

global $pdo;

$id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$name = trim((string)($_POST['name'] ?? ''));
$login = trim((string)($_POST['login'] ?? ''));
$user_pass_admin = (string)($_POST['user_pass_admin'] ?? '');
$popis_cz = trim((string)($_POST['popis_cz'] ?? ''));
$popis_en = trim((string)($_POST['popis_en'] ?? ''));
$prava = isset($_POST['prava']) ? (int)$_POST['prava'] : 1;
$skup_id = isset($_POST['skup_id']) ? (int)$_POST['skup_id'] : 2;
$email = trim((string)($_POST['email'] ?? ''));
$admin = isset($_POST['admin']) ? 1 : 0;
$aktivni_l = isset($_POST['aktivni_l']) ? 1 : 0;
$valid = isset($_POST['valid']) ? 1 : 0;
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
$dev = null;

if ($add === 0) {
    if ($id <= 0) {
        echo '<div class="alert alert-danger mb-0">CHYBA: chybí parametr <strong>edit</strong>.</div>';
        $add = -1;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dev) {
            echo '<div class="alert alert-danger mb-0">CHYBA: uživatel s ID ' . (int)$id . ' neexistuje.</div>';
            $add = -1;
        } else {
            $name = (string)($dev['name'] ?? '');
            $login = (string)($dev['login'] ?? '');
            $popis_cz = (string)($dev['popis_cz'] ?? '');
            $popis_en = (string)($dev['popis_en'] ?? '');
            $prava = (int)($dev['prava'] ?? 1);
            $admin = (int)($dev['admin'] ?? 0);
            $aktivni_l = (int)($dev['aktivni_l'] ?? 1);
            $skup_id = (int)($dev['skup_id'] ?? 2);
            $email = (string)($dev['email'] ?? '');
            $valid = (int)($dev['valid'] ?? 0);
        }
    }
}

if ($add === 2 && $id <= 0) {
    echo '<div class="alert alert-danger mb-0">CHYBA: chybí parametr <strong>edit</strong> pro uložení.</div>';
    $add = -1;
}
?>

<div class="card-body">
    <?php if ($add === 0): ?>
        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-12 col-lg-3">
                    <label for="name" class="form-label">Příjmení, jméno</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name, ENT_QUOTES) ?>">
                </div>

                <div class="col-12 col-lg-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>">
                </div>

                <div class="col-12 col-lg-2">
                    <label for="login" class="form-label">Login</label>
                    <input type="text" name="login" id="login" class="form-control" value="<?= htmlspecialchars($login, ENT_QUOTES) ?>">
                </div>

                <div class="col-12 col-lg-2">
                    <label for="user_pass_admin" class="form-label">Heslo</label>
                    <input type="password" name="user_pass_admin" id="user_pass_admin" class="form-control" value="" autocomplete="new-password">
                    <div class="form-text">Nech prázdné, pokud nechceš měnit heslo.</div>
                </div>

                <div class="col-12 col-lg-2">
                    <label for="skup_id" class="form-label">Skupina</label>
                    <select name="skup_id" id="skup_id" class="form-select">
                        <?php users_skup_option_form($skup_id); ?>
                    </select>
                </div>

                <div class="col-12 col-lg-3">
                    <label for="popis_cz" class="form-label">Popis (cz)</label>
                    <input type="text" name="popis_cz" id="popis_cz" class="form-control" value="<?= htmlspecialchars($popis_cz, ENT_QUOTES) ?>">
                </div>

                <?php if (en_on() == 1): ?>
                    <div class="col-12 col-lg-3">
                        <label for="popis_en" class="form-label">Popis (en)</label>
                        <input type="text" name="popis_en" id="popis_en" class="form-control" value="<?= htmlspecialchars($popis_en, ENT_QUOTES) ?>">
                    </div>
                <?php endif; ?>

                <div class="col-12 col-lg-2">
                    <label for="prava" class="form-label">Oprávnění administrace</label>
                    <input type="number" name="prava" id="prava" class="form-control" value="<?= (int)$prava ?>">
                </div>

                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <div class="d-flex flex-column gap-2 mb-2">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="valid" id="valid" value="1" <?= ($valid === 1 ? 'checked' : '') ?>>
                            <label class="form-check-label" for="valid">valid</label>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="aktivni_l" id="aktivni_l" value="1" <?= ($aktivni_l === 1 ? 'checked' : '') ?>>
                            <label class="form-check-label" for="aktivni_l">aktivní</label>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="admin" id="admin" value="1" <?= ($admin === 1 ? 'checked' : '') ?>>
                            <label class="form-check-label" for="admin">admin</label>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <input type="hidden" name="add" value="2">
                    <button type="submit" class="btn btn-primary w-100">Uložit administrátora</button>
                </div>

                <?php if (is_array($dev)): ?>
                    <div class="col-12">
                        <div class="small text-muted">
                            Založeno: <?= htmlspecialchars((string)format_datetime_www($dev['ts_i'] ?? ''), ENT_QUOTES) ?>;
                            Založil: <?= htmlspecialchars((string)($dev['user_i'] ?? ''), ENT_QUOTES) ?>;
                            Upraveno: <?= htmlspecialchars((string)format_datetime_www($dev['ts_u'] ?? ''), ENT_QUOTES) ?>;
                            Upravil: <?= htmlspecialchars((string)($dev['user_u'] ?? ''), ENT_QUOTES) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php elseif ($add === 2): ?>
        <?php users_edit($id, $name, $login, $user_pass_admin, $popis_cz, $popis_en, $admin, $aktivni_l, $prava, $skup_id, $email, $valid); ?>
    <?php endif; ?>
</div>
