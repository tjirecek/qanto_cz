<?php
declare(strict_types=1);
// secure/admin_login.php

global $pdo;

require_once ROOT_DIR . '/functions/fun_users_password_reset.php';

$login_error = '';
$base = defined('BASE_URL') ? (string)BASE_URL : '/';
$reset_mode = isset($_GET['reset_hesla']) && (int)$_GET['reset_hesla'] === 1;
$usersPasswordResetToken = trim((string)($_POST['users_password_reset_token'] ?? $_GET['users_password_reset'] ?? ''));

/**
 * Helper: redirect na stejnou URL (bez POST resubmission)
 */
function admin_redirect_self_303(): void
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/secure/';
    header('Location: ' . $uri, true, 303);
    exit;
}

/**
 * LOGOUT administrace.
 * URL: /secure/?logout=1
 */
if (isset($_GET['logout']) && (int)$_GET['logout'] === 1) {
    admin_session_clear();

    header('Location: ' . $base . 'secure/', true, 303);
    exit;
}

/**
 * RESET PASSWORD REQUEST
 */
if (isset($_POST['users_password_reset_identifier'])) {
    $identifier = trim((string)$_POST['users_password_reset_identifier']);
    $result = users_password_reset_request_for_identifier($pdo, $identifier, 'users_password_reset_request');
    $alertType = $result['ok'] ? 'success' : 'danger';
    $login_error = '<div class="alert alert-' . $alertType . ' py-2 mb-3">' . htmlspecialchars((string)$result['message'], ENT_QUOTES) . '</div>';
    $reset_mode = !$result['ok'];
}

/**
 * USERS PASSWORD RESET FORM
 */
if ($usersPasswordResetToken !== '') {
    $logo = $base . 'img/design/logo_admin_login.png';
    $loginUrl = $base . 'secure/';
    $message = '';
    $messageType = 'info';
    $done = false;
    $reset = null;

    if (isset($_POST['users_password_reset_token'])) {
        $result = users_password_reset_save_password(
            $pdo,
            $usersPasswordResetToken,
            (string)($_POST['password'] ?? ''),
            (string)($_POST['password_confirm'] ?? ''),
            (string)($_SERVER['REMOTE_ADDR'] ?? '')
        );
        $message = (string)$result['message'];
        $messageType = $result['ok'] ? 'success' : 'danger';
        $done = (bool)$result['ok'];
    }

    if (!$done) {
        $reset = users_password_reset_find($pdo, $usersPasswordResetToken);
        if (!$reset && $message === '') {
            $message = 'Odkaz pro nastavení hesla je neplatný nebo již vypršel.';
            $messageType = 'danger';
        }
    }

    $displayName = '';
    if (is_array($reset)) {
        $displayName = trim((string)($reset['name'] ?? ''));
        if ($displayName === '') {
            $displayName = (string)($reset['login'] ?? '');
        }
    }

    $messageHtml = $message !== ''
        ? '<div class="alert alert-' . htmlspecialchars($messageType, ENT_QUOTES) . ' py-2 mb-3">' . htmlspecialchars($message, ENT_QUOTES) . '</div>'
        : '';
    $displayNameHtml = $displayName !== ''
        ? '<p class="small text-muted mb-3">Uživatel: <strong>' . htmlspecialchars($displayName, ENT_QUOTES) . '</strong></p>'
        : '';
    $tokenHtml = htmlspecialchars($usersPasswordResetToken, ENT_QUOTES);

    if ($done) {
        $formHtml = <<<HTML
        <a href="{$loginUrl}" class="btn btn-primary btn-lg w-100">
          <i class="bi bi-box-arrow-in-right me-1"></i> Pokračovat na přihlášení
        </a>
HTML;
    } elseif (is_array($reset)) {
        $formHtml = <<<HTML
        {$displayNameHtml}
        <form method="post" autocomplete="off">
          <input type="hidden" name="users_password_reset_token" value="{$tokenHtml}">

          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Nové heslo" required minlength="8" autocomplete="new-password">
          </div>

          <div class="mb-4 input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password_confirm" class="form-control" placeholder="Potvrzení hesla" required minlength="8" autocomplete="new-password">
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-check2-circle me-1"></i> Nastavit heslo
          </button>
        </form>
HTML;
    } else {
        $formHtml = <<<HTML
        <a href="{$loginUrl}" class="btn btn-outline-secondary w-100">
          Zpět na přihlášení
        </a>
HTML;
    }

    echo <<<HTML
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
  <div class="col-12 col-sm-10 col-md-6 col-lg-4">
    <div class="card shadow-lg border-0 rounded-4">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <img src="{$logo}" class="img-fluid mb-3" style="max-height:80px" alt="Administrace">
          <h5 class="fw-semibold text-secondary mb-0">Nastavení hesla administrace</h5>
        </div>

        {$messageHtml}
        {$formHtml}
      </div>
      <div class="card-footer text-center small text-muted">
        created by <strong>tm</strong>
      </div>
    </div>
  </div>
</div>
HTML;

    exit;
}

/**
 * LOGIN
 */
if (isset($_POST['admin_login'], $_POST['admin_pass'])) {

    $login = trim((string)$_POST['admin_login']);
    $pass  = (string)$_POST['admin_pass'];

    if ($login === '' || $pass === '') {
        $login_error = '<div class="alert alert-danger py-2 mb-3">Vyplň login i heslo.</div>';
    } else {

        $stmt = $pdo->prepare(
            "SELECT
                id, login, name, prava, admin, skup_id, password
             FROM users
             WHERE (login = :login OR email = :email)
               AND valid = 1
               AND aktivni_l = 1
             LIMIT 1"
        );
        $stmt->execute([
            ':login' => $login,
            ':email' => $login,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && users_password_verify($pass, (string)($user['password'] ?? ''))) {

            $isAdmin = ((int)($user['admin'] ?? 0) === 1);
            if (!$isAdmin) {
                $login_error = '<div class="alert alert-danger py-2 mb-3">Nemáš přístup do administrace.</div>';
            } else {
                session_regenerate_id(true);

                admin_session_set('logged', 1);
                admin_session_set('user', (string)$user['login']);
                admin_session_set('user_name', (string)($user['name'] ?? $user['login']));
                admin_session_set('user_prava', (string)($user['prava'] ?? '0'));
                admin_session_set('is_admin', 1);
                admin_session_set('user_skup', (int)($user['skup_id'] ?? 0));

                if (function_exists('log_users')) {
                    log_users((string)$user['login'], 1);
                }

                if (users_password_needs_rehash((string)($user['password'] ?? ''))) {
                    users_password_prepare_column($pdo);
                    $stmtHash = $pdo->prepare(
                        'UPDATE users
                         SET password = :password,
                             user_u = :user_u
                         WHERE id = :id'
                    );
                    $stmtHash->execute([
                        ':password' => users_password_hash($pass),
                        ':user_u' => 'password_rehash',
                        ':id' => (int)$user['id'],
                    ]);
                }

                admin_redirect_self_303();
            }

        } else {
            $login_error = '<div class="alert alert-danger py-2 mb-3">Neplatné přihlašovací údaje.</div>';
        }
    }
}

/**
 * NEPŘIHLÁŠENÝ nebo NEADMIN → login karta
 */
$logged = (int)admin_session_get('logged', 0) === 1;
$isAdmin = (int)admin_session_get('is_admin', 0) === 1;

if (!$logged || !$isAdmin) {

    $logo = $base . 'img/design/logo_admin_login.png';
    $loginUrl = $base . 'secure/';
    $resetUrl = $base . 'secure/?reset_hesla=1';

    if ($reset_mode) {
        $authFormHtml = <<<HTML
        <form method="post" autocomplete="off">
          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="users_password_reset_identifier" class="form-control" placeholder="Login nebo e-mail" required autofocus>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-key me-1"></i> Odeslat odkaz pro nastavení hesla
          </button>

          <div class="text-center mt-3">
            <a href="{$loginUrl}" class="small">Zpět na přihlášení</a>
          </div>
        </form>
HTML;
    } else {
        $authFormHtml = <<<HTML
        <form method="post" autocomplete="off">
          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="admin_login" class="form-control" placeholder="Login nebo e-mail" required autofocus>
          </div>

          <div class="mb-4 input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="admin_pass" class="form-control" placeholder="Heslo" required>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> Přihlásit
          </button>

          <div class="text-center mt-3">
            <a href="{$resetUrl}" class="small">Zapomenuté heslo</a>
          </div>
        </form>
HTML;
    }

    echo <<<HTML
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
  <div class="col-12 col-sm-10 col-md-6 col-lg-4">
    <div class="card shadow-lg border-0 rounded-4">
      <div class="card-body p-4">

        <div class="text-center mb-4">
          <img src="{$logo}" class="img-fluid mb-3" style="max-height:80px" alt="Administrace">
          <h5 class="fw-semibold text-secondary">Administrace</h5>
        </div>

        {$login_error}

        {$authFormHtml}

      </div>
      <div class="card-footer text-center small text-muted">
        created by <strong>tm</strong>
      </div>
    </div>
  </div>
</div>
HTML;

    exit;
}
