<?php
declare(strict_types=1);

// users_add.php (PDO funkce ve fun_system.php)

$name = trim((string)($_POST['name'] ?? ''));
$login = trim((string)($_POST['login'] ?? ''));
$user_pass_admin = (string)($_POST['user_pass_admin'] ?? '');
$popis_cz = trim((string)($_POST['popis_cz'] ?? ''));
$popis_en = trim((string)($_POST['popis_en'] ?? ''));
$admin = isset($_POST['admin']) ? 1 : 0;
$aktivni_l = isset($_POST['add']) ? (isset($_POST['aktivni_l']) ? 1 : 0) : 1;
$prava = isset($_POST['prava']) ? (int)$_POST['prava'] : 1;
$skup_id = isset($_POST['skup_id']) ? (int)$_POST['skup_id'] : 2;
$email = trim((string)($_POST['email'] ?? ''));
$send_password_reset = isset($_POST['send_password_reset']) ? 1 : 0;
$add = isset($_POST['add']) ? (int)$_POST['add'] : 0;
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
                    <div class="form-text">Může zůstat prázdné při odeslání reset linku.</div>
                </div>

                <div class="col-12 col-lg-2">
                    <label for="skup_id" class="form-label">Skupina</label>
                    <select name="skup_id" id="skup_id" class="form-select">
                        <?php users_skup_option_form($skup_id); ?>
                    </select>
                </div>

                <div class="col-12 col-lg-2">
                    <label for="prava" class="form-label">Oprávnění administrace</label>
                    <input type="number" name="prava" id="prava" class="form-control" value="<?= (int)$prava ?>">
                </div>

                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <div class="d-flex flex-column gap-2 mb-2">
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

                <div class="col-12 col-lg-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="send_password_reset" id="send_password_reset" value="1" <?= ($send_password_reset === 1 ? 'checked' : '') ?>>
                        <label class="form-check-label" for="send_password_reset">odeslat link pro nastavení hesla</label>
                    </div>
                </div>

                <div class="col-12 col-lg-3 d-flex align-items-end">
                    <input type="hidden" name="add" value="1">
                    <button type="submit" class="btn btn-primary w-100">Vložit administrátora</button>
                </div>
            </div>
        </form>
    <?php elseif ($add === 1): ?>
        <?php users_add($name, $login, $user_pass_admin, $popis_cz, $popis_en, $admin, $aktivni_l, $prava, $skup_id, $email, $send_password_reset); ?>
    <?php endif; ?>
</div>
