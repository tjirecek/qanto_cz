<?php
declare(strict_types=1);

/**
 * fun_system.php (PDO verze)
 * - nahrazuje původní mysqli verzi
 * - používá global $pdo
 * - ikony: Bootstrap Icons (bi ...)
 */

require_once ROOT_DIR . '/functions/fun_users_password_reset.php';

function _qn_user(): string
{
    return admin_session_user();
}

function _redirect_self(): void
{
    $url = $_SERVER['REQUEST_URI'] ?? 'index.php';

    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }

    // už se vykreslilo -> redirect přes klienta
    echo '<script>location.replace(' . json_encode($url) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url='
        . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
    exit;
}

function _msg_warning(string $text): void
{
    echo '<div class="alert alert-warning py-2 mb-2"><i class="bi bi-exclamation-triangle me-2"></i>' . htmlspecialchars($text) . '</div>';
}

function _msg_success(string $text): void
{
    echo '<div class="alert alert-success py-2 mb-2"><i class="bi bi-check2-circle me-2"></i>' . htmlspecialchars($text) . '</div>';
}

function _admin_flash_add(string $type, string $text): void
{
    $messages = admin_session_get('flash_messages', []);
    if (!is_array($messages)) {
        $messages = [];
    }

    $messages[] = [
        'type' => in_array($type, ['success', 'warning', 'danger', 'info'], true) ? $type : 'info',
        'text' => $text,
    ];
    admin_session_set('flash_messages', $messages);
}

function _admin_flash_messages_take(): array
{
    $messages = admin_session_get('flash_messages', []);
    admin_session_unset('flash_messages');

    return is_array($messages) ? $messages : [];
}

function _admin_flash_render(): void
{
    foreach (_admin_flash_messages_take() as $message) {
        if (!is_array($message)) {
            continue;
        }
        $type = (string)($message['type'] ?? 'info');
        $text = (string)($message['text'] ?? '');
        if ($text === '') {
            continue;
        }
        echo '<div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES) . ' py-2 mb-2">'
            . htmlspecialchars($text, ENT_QUOTES)
            . '</div>';
    }
}

/* ===========================
   MENU
   =========================== */

function menu_add(string $url_cz, string $nazev_cz, int $menu): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users_menu (url_cz, nazev_cz, menu, user_i, user_u)
             VALUES (:url_cz, :nazev_cz, :menu, :user_i, :user_u)'
        );
        $stmt->execute([
            ':url_cz'   => $url_cz,
            ':nazev_cz' => $nazev_cz,
            ':menu'     => $menu,
            ':user_i'   => $qn_user,
            ':user_u'   => $qn_user,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Menu nebylo vloženo');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function menu_edit(int $id, string $url_cz, string $nazev_cz, int $menu, int $valid): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'UPDATE users_menu SET
                url_cz = :url_cz,
                nazev_cz = :nazev_cz,
                menu = :menu,
                valid = :valid,
                user_u = :user_u
             WHERE id = :id'
        );
        $stmt->execute([
            ':url_cz'   => $url_cz,
            ':nazev_cz' => $nazev_cz,
            ':menu'     => $menu,
            ':valid'    => $valid,
            ':user_u'   => $qn_user,
            ':id'       => $id,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Menu nebylo uloženo');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function menu_vypis(int $limit, int $valid): void
{
    global $pdo;

    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare('SELECT * FROM users_menu WHERE valid = :valid ORDER BY menu LIMIT :lim');
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$dev['id'];

        echo '<tr>
                <td>' . (int)$dev['id'] . '</td>
                <td>' . (int)$dev['menu'] . '</td>
                <td>' . htmlspecialchars((string)$dev['nazev_cz']) . '</td>
                <td>' . htmlspecialchars((string)$dev['url_cz']) . '</td>
                <td class="text-center">
                    <a class="btn btn-success btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;edit=' . $id . '&amp;limit=' . $limit . '&amp;show=2" title="Upravit">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </td>
                <td class="text-center">
                    <a class="btn btn-danger btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=03&amp;del=' . $id . '&amp;limit=' . $limit . '" title="Smazat">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
              </tr>';
    }
}

function menu_delete(int $id): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare('UPDATE users_menu SET valid = 0, user_u = :user_u WHERE id = :id');
        $stmt->execute([':user_u' => $qn_user, ':id' => $id]);

        _msg_success('Menu bylo smazáno');
    } catch (Throwable $e) {
        _msg_warning('Menu nebylo smazáno');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

/* ===========================
   MENU x USERS_SKUP
   =========================== */

function menu_users_skup_vypis(int $skup_id, int $limit, int $valid): void
{
    global $pdo;
    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare('SELECT * FROM users_menu WHERE valid = :valid ORDER BY menu LIMIT :lim');
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();
    $stmtCheck = $pdo->prepare('SELECT 1 FROM users_skup_menu WHERE valid = 1 AND skup_id = :skup_id AND menu_id = :menu_id LIMIT 1');

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $menu_id = (int)$dev['id'];
        $menu = (int)$dev['menu'];
        $pridat = '';
        $smazat = '';

        if ($skup_id === 0) {
            // nic
        } else {
            $stmtCheck->execute([':skup_id' => $skup_id, ':menu_id' => $menu_id]);
            $exists = (bool)$stmtCheck->fetchColumn();

            if (!$exists) {
                $pridat = '<a class="btn btn-success btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=04&amp;add=' . $menu_id . '&amp;limit=' . $limit . '&amp;skup_id=' . $skup_id . '" title="Přidat oprávnění">
                            <i class="bi bi-plus-circle"></i></a>';
            } else {
                $smazat = '<a class="btn btn-danger btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=04&amp;del=' . $menu_id . '&amp;limit=' . $limit . '&amp;skup_id=' . $skup_id . '" title="Odebrat oprávnění">
                            <i class="bi bi-trash"></i></a>';
            }
        }

        echo '<tr>
                <td>' . (int)$dev['id'] . '</td>
                <td>' . (int)$dev['id'] . '</td>
                <td>' . htmlspecialchars((string)$dev['nazev_cz']) . '</td>
                <td>' . htmlspecialchars((string)$dev['url_cz']) . '</td>
                <td class="text-center">' . $pridat . '</td>
                <td class="text-center">' . $smazat . '</td>
              </tr>';
    }
}

function menu_users_skup_delete(int $menu_id, int $skup_id): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'UPDATE users_skup_menu SET valid = 0, user_u = :user_u WHERE skup_id = :skup_id AND menu_id = :menu_id'
        );
        $stmt->execute([
            ':user_u'  => $qn_user,
            ':skup_id' => $skup_id,
            ':menu_id'    => $menu_id,
        ]);

        _msg_success('Oprávnění bylo smazáno');
    } catch (Throwable $e) {
        _msg_warning('Oprávnění nebylo smazáno');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function menu_users_skup_add(int $menu_id, int $skup_id): void
{
    global $pdo;

    $qn_user = _qn_user();

    $stmt0 = $pdo->prepare(
        'SELECT 1 FROM users_skup_menu WHERE valid = 1 AND menu_id = :menu_id AND skup_id = :skup_id LIMIT 1'
    );
    $stmt0->execute([':menu_id' => $menu_id, ':skup_id' => $skup_id]);
    $exists = (bool)$stmt0->fetchColumn();

    if ($exists) {
        _msg_warning('Oprávnění už existuje');
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users_skup_menu (skup_id, menu_id, user_i, user_u) VALUES (:skup_id, :menu_id, :user_i, :user_u)'
        );
        $stmt->execute([
            ':skup_id' => $skup_id,
            ':menu_id' => $menu_id,
            ':user_i'  => $qn_user,
            ':user_u'  => $qn_user,
        ]);

        _msg_success('Oprávnění bylo vloženo');
    } catch (Throwable $e) {
        _msg_warning('Oprávnění nebylo vloženo');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

/* ===========================
   SETTINGS
   =========================== */

function settings_add(string $typ, string $name, string $popis_cz, float|int $hodnota, string $hodnota_text): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO settings (typ, name, popis_cz, hodnota, hodnota_text, user_i, user_u)
             VALUES (:typ, :name, :popis_cz, :hodnota, :hodnota_text, :user_i, :user_u)'
        );
        $stmt->execute([
            ':typ'          => $typ,
            ':name'         => $name,
            ':popis_cz'     => $popis_cz,
            ':hodnota'      => $hodnota,
            ':hodnota_text' => $hodnota_text,
            ':user_i'       => $qn_user,
            ':user_u'       => $qn_user,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Hodnota nebyla vložena');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function settings_edit(int $id, string $typ, string $name, string $popis_cz, float|int $hodnota, string $hodnota_text, int $valid): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'UPDATE settings SET
                typ = :typ,
                name = :name,
                popis_cz = :popis_cz,
                hodnota = :hodnota,
                hodnota_text = :hodnota_text,
                valid = :valid,
                user_u = :user_u
             WHERE id = :id'
        );
        $stmt->execute([
            ':typ'          => $typ,
            ':name'         => $name,
            ':popis_cz'     => $popis_cz,
            ':hodnota'      => $hodnota,
            ':hodnota_text' => $hodnota_text,
            ':valid'        => $valid,
            ':user_u'       => $qn_user,
            ':id'           => $id,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Hodnota nebyla uložena');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function settings_vypis(int $limit, int $valid): void
{
    global $pdo;

    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare(
        'SELECT id, typ, name, popis_cz, hodnota, hodnota_text
         FROM settings
         WHERE valid = :valid
         ORDER BY typ, name
         LIMIT :lim'
    );
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$dev['id'];
        $textPreview = trim(preg_replace('/\s+/', ' ', strip_tags((string)$dev['hodnota_text'])));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($textPreview, 'UTF-8') > 160) {
                $textPreview = mb_substr($textPreview, 0, 160, 'UTF-8') . '...';
            }
        } elseif (strlen($textPreview) > 160) {
            $textPreview = substr($textPreview, 0, 160) . '...';
        }

        echo '<tr>
            <td>' . $id . '</td>
            <td>' . htmlspecialchars((string)$dev['typ']) . '</td>
            <td>' . htmlspecialchars((string)$dev['name']) . '</td>
            <td>' . htmlspecialchars((string)$dev['popis_cz']) . '</td>
            <td>' . htmlspecialchars((string)$dev['hodnota']) . '</td>
            <td>' . htmlspecialchars($textPreview, ENT_QUOTES, 'UTF-8') . '</td>
            <td class="text-center">
                <a class="btn btn-success btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=02&amp;edit=' . $id . '&amp;limit=' . $limit . '&amp;show=2" title="Upravit">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-danger btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=02&amp;del=' . $id . '&amp;limit=' . $limit . '" title="Smazat">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>';
    }
}

function settings_delete(int $id): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare('UPDATE settings SET valid = 0, user_u = :user_u WHERE id = :id');
        $stmt->execute([':user_u' => $qn_user, ':id' => $id]);

        _msg_success('Systémová proměnná byla smazána');
    } catch (Throwable $e) {
        _msg_warning('Systémová proměnná nebyla smazána');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

/* ===========================
   USERS
   =========================== */

function users_add(
    string $name,
    string $login,
    string $password,
    string $popis_cz,
    string $popis_en,
    int $admin,
    int $aktivni_l,
    int $prava,
    int $skup_id,
    string $email,
    int $sendPasswordReset = 0
): void {
    global $pdo;

    $qn_user = _qn_user();
    if ($password === '') {
        $password = bin2hex(random_bytes(16));
    }
    users_password_prepare_column($pdo);
    $passwordHash = users_password_hash($password);
    $aktivni_l = $aktivni_l === 1 ? 1 : 0;
    $admin = $admin === 1 ? 1 : 0;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, login, password, popis_cz, popis_en, admin, aktivni_l, prava, skup_id, email, user_i, user_u)
             VALUES (:name, :login, :password, :popis_cz, :popis_en, :admin, :aktivni_l, :prava, :skup_id, :email, :user_i, :user_u)'
        );
        $stmt->execute([
            ':name'      => $name,
            ':login'     => $login,
            ':password'  => $passwordHash,
            ':popis_cz'  => $popis_cz,
            ':popis_en'  => $popis_en,
            ':admin'     => $admin,
            ':aktivni_l' => $aktivni_l,
            ':prava'     => $prava,
            ':skup_id'   => $skup_id,
            ':email'     => $email,
            ':user_i'    => $qn_user,
            ':user_u'    => $qn_user,
        ]);

        $userId = (int)$pdo->lastInsertId();
        _admin_flash_add('success', 'Uživatelský účet byl vložen.');

        if ($sendPasswordReset === 1) {
            $resetResult = users_password_reset_request_for_user($pdo, $userId, $qn_user);
            _admin_flash_add($resetResult['ok'] ? 'success' : 'warning', (string)$resetResult['message']);
        }

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Uživatel nebyl vložen');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function users_vypis(int $limit, int $valid): void
{
    global $pdo;

    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare(
        'SELECT u.*
         FROM users u
         WHERE u.valid = :valid
         ORDER BY u.id
         LIMIT :lim'
    );
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lastAdminLogins = [];
    $logins = [];

    foreach ($users as $user) {
        $login = trim((string)($user['login'] ?? ''));
        if ($login !== '') {
            $logins[$login] = $login;
        }
    }

    if ($logins !== []) {
        $placeholders = implode(',', array_fill(0, count($logins), '?'));
        $logStmt = $pdo->prepare(
            "SELECT ul.login, ul.datum
             FROM log_users ul
             INNER JOIN (
                 SELECT login, MAX(id) AS max_id
                 FROM log_users
                 WHERE web = 1
                   AND login IN ($placeholders)
                 GROUP BY login
             ) last_log ON last_log.max_id = ul.id"
        );
        $logStmt->execute(array_values($logins));

        while ($log = $logStmt->fetch(PDO::FETCH_ASSOC)) {
            $lastAdminLogins[(string)$log['login']] = (string)$log['datum'];
        }
    }

    foreach ($users as $dev) {
        $id = (int)$dev['id'];
        $adminText = ((int)$dev['admin'] === 0) ? 'NE' : 'ANO';
        $aktivniText = ((int)($dev['aktivni_l'] ?? 1) === 1) ? 'ANO' : 'NE';
        $skup_name = users_skup_name((int)$dev['skup_id']);
        $lastAdminLogin = (string)($lastAdminLogins[(string)($dev['login'] ?? '')] ?? '');
        $lastAdminLoginOrder = '0';
        if ($lastAdminLogin !== '') {
            $lastAdminLoginDt = DateTimeImmutable::createFromFormat('!d.m.Y-H.i.s', $lastAdminLogin);
            if ($lastAdminLoginDt instanceof DateTimeImmutable) {
                $lastAdminLogin = $lastAdminLoginDt->format('d.m.Y H:i:s');
                $lastAdminLoginOrder = $lastAdminLoginDt->format('YmdHis');
            }
        }

        echo '<tr>
            <td>' . $id . '</td>
            <td class="users-vypis-name-col">' . htmlspecialchars((string)$dev['name']) . '</td>
            <td>' . htmlspecialchars((string)$dev['login']) . '</td>
            <td>' . htmlspecialchars((string)$dev['email']) . '</td>
            <td class="text-center">
                <form method="post" class="d-inline">
                    <button type="submit" name="send_password_reset_user_id" value="' . $id . '" class="btn btn-warning btn-sm" title="Odeslat odkaz pro reset hesla">
                        <i class="bi bi-key"></i>
                    </button>
                </form>
            </td>
            <td class="text-center">
                <a class="btn btn-success btn-sm" href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;edit=' . $id . '&amp;limit=' . $limit . '&amp;show=2" title="Upravit">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-danger btn-sm" href="index.php?section=02&amp;page=01&amp;sec_page=02&amp;del=' . $id . '&amp;limit=' . $limit . '" title="Smazat">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
            <td>' . $aktivniText . '</td>
            <td>' . $adminText . '</td>
            <td>' . htmlspecialchars((string)$skup_name) . '</td>
            <td data-order="' . htmlspecialchars($lastAdminLoginOrder, ENT_QUOTES) . '">' . htmlspecialchars($lastAdminLogin, ENT_QUOTES) . '</td>
        </tr>';
    }
}

function users_password_reset_send(int $id): void
{
    global $pdo;

    $result = users_password_reset_request_for_user($pdo, $id, _qn_user());
    _admin_flash_add($result['ok'] ? 'success' : 'warning', (string)$result['message']);
}

function users_edit(
    int $id,
    string $name,
    string $login,
    string $password,
    string $popis_cz,
    string $popis_en,
    int $admin,
    int $aktivni_l,
    int $prava,
    int $skup_id,
    string $email,
    int $valid
): void {
    global $pdo;

    $qn_user = _qn_user();
    $aktivni_l = $aktivni_l === 1 ? 1 : 0;
    $admin = $admin === 1 ? 1 : 0;

    try {
        if ($password !== '') {
            users_password_prepare_column($pdo);
            $passwordHash = users_password_hash($password);
            $stmt = $pdo->prepare(
                'UPDATE users SET
                    name = :name,
                    login = :login,
                    password = :password,
                    popis_cz = :popis_cz,
                    popis_en = :popis_en,
                    admin = :admin,
                    aktivni_l = :aktivni_l,
                    prava = :prava,
                    skup_id = :skup_id,
                    email = :email,
                    valid = :valid,
                    user_u = :user_u
                 WHERE id = :id'
            );
            $params = [':password' => $passwordHash];
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET
                    name = :name,
                    login = :login,
                    popis_cz = :popis_cz,
                    popis_en = :popis_en,
                    admin = :admin,
                    aktivni_l = :aktivni_l,
                    prava = :prava,
                    skup_id = :skup_id,
                    email = :email,
                    valid = :valid,
                    user_u = :user_u
                 WHERE id = :id'
            );
            $params = [];
        }

        $stmt->execute($params + [
            ':name'      => $name,
            ':login'     => $login,
            ':popis_cz'  => $popis_cz,
            ':popis_en'  => $popis_en,
            ':admin'     => $admin,
            ':aktivni_l' => $aktivni_l,
            ':prava'     => $prava,
            ':skup_id'   => $skup_id,
            ':email'     => $email,
            ':valid'     => $valid,
            ':user_u'    => $qn_user,
            ':id'        => $id,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Uživatel nebyl uložen');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function users_delete(int $id): void
{
    global $pdo;

    try {
        $stmt = $pdo->prepare('UPDATE users SET valid = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);

        _msg_success('Uživatel byl smazán');
    } catch (Throwable $e) {
        _msg_warning('Uživatel nebyl smazán');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

/* ===========================
   USERS_SKUP
   =========================== */

function users_skup_add(string $nazev_cz, int $poradi): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users_skup (poradi, nazev_cz, user_i, user_u)
             VALUES (:poradi, :nazev_cz, :user_i, :user_u)'
        );
        $stmt->execute([
            ':poradi'   => $poradi,
            ':nazev_cz' => $nazev_cz,
            ':user_i'   => $qn_user,
            ':user_u'   => $qn_user,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Skupina uživatelů nebyla vložena');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function users_skup_vypis(int $limit, int $valid): void
{
    global $pdo;

    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare(
        'SELECT id, nazev_cz, poradi FROM users_skup WHERE valid = :valid ORDER BY poradi LIMIT :lim'
    );
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$dev['id'];

        echo '<tr>
            <td>' . $id . '</td>
            <td>' . htmlspecialchars((string)$dev['nazev_cz']) . '</td>
            <td>' . (int)$dev['poradi'] . '</td>
            <td class="text-center">
                <a class="btn btn-primary btn-sm" href="index.php?section=02&amp;page=02&amp;sec_page=04&amp;skup_id=' . $id . '" title="Nastavit oprávnění">
                    <i class="bi bi-arrow-right-circle"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-success btn-sm" href="index.php?section=02&amp;page=01&amp;sec_page=03&amp;edit=' . $id . '&amp;limit=' . $limit . '&amp;show=2" title="Upravit">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-danger btn-sm" href="index.php?section=02&amp;page=01&amp;sec_page=03&amp;del=' . $id . '&amp;limit=' . $limit . '" title="Smazat">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>';
    }
}

function users_skup_edit(int $id, string $nazev_cz, int $poradi, int $valid): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare(
            'UPDATE users_skup SET
                poradi = :poradi,
                nazev_cz = :nazev_cz,
                valid = :valid,
                user_u = :user_u
             WHERE id = :id'
        );
        $stmt->execute([
            ':poradi'   => $poradi,
            ':nazev_cz' => $nazev_cz,
            ':valid'    => $valid,
            ':user_u'   => $qn_user,
            ':id'       => $id,
        ]);

        unset($_POST['add']);
        _redirect_self();
    } catch (Throwable $e) {
        _msg_warning('Skupina uživatelů nebyla uložena');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function users_skup_delete(int $id): void
{
    global $pdo;

    $qn_user = _qn_user();

    try {
        $stmt = $pdo->prepare('UPDATE users_skup SET valid = 0, user_u = :user_u WHERE id = :id');
        $stmt->execute([':user_u' => $qn_user, ':id' => $id]);

        _msg_success('Skupina uživatelů byla smazána');
    } catch (Throwable $e) {
        _msg_warning('Skupina uživatelů nebyla smazána');
        echo '<pre class="text-danger small mb-0">' . htmlspecialchars($e->getMessage()) . '</pre>';
    }
}

function users_skup_name(int $id): ?string
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT nazev_cz FROM users_skup WHERE id = :id AND valid = 1 LIMIT 1');
    $stmt->execute([':id' => $id]);
    $name = $stmt->fetchColumn();

    return ($name !== false) ? (string)$name : null;
}

function users_skup_option_form(int $select): void
{
    global $pdo;

    $stmt = $pdo->query('SELECT id, nazev_cz FROM users_skup WHERE valid = 1 ORDER BY poradi');

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$dev['id'];
        $nazev_cz = (string)$dev['nazev_cz'];

        $sel = ($select === $id) ? ' selected="selected"' : '';
        echo '<option value="' . $id . '"' . $sel . '>' . $id . ' - ' . htmlspecialchars($nazev_cz) . '</option>' . "\n";
    }
}

/* ===========================
   USERS LOG
   =========================== */

function users_log_vypis(int $limit): void
{
    global $pdo;

    $sqllimit = ($limit === 0) ? 999999 : $limit;

    $stmt = $pdo->prepare('SELECT id, login, ip, datum, web FROM log_users ORDER BY id DESC LIMIT :lim');
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    $stmtSkup = $pdo->prepare(
        'SELECT us.nazev_cz
         FROM users_skup us
         JOIN users u ON u.skup_id = us.id
         WHERE u.login = :login AND u.valid = 1 AND us.valid = 1
         LIMIT 1'
    );

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $login = (string)$dev['login'];
        $web = ((int)$dev['web'] === 0) ? 'Hlavní' : 'Administrace';

        $stmtSkup->execute([':login' => $login]);
        $skupina = $stmtSkup->fetchColumn();
        if ($skupina === false || $skupina === null || $skupina === '') {
            $skupina = 'žádná';
        }

        echo '<tr>
            <td>' . (int)$dev['id'] . '</td>
            <td>' . htmlspecialchars($login) . '</td>
            <td>' . htmlspecialchars((string)$skupina) . '</td>
            <td>' . htmlspecialchars((string)$dev['ip']) . '</td>
            <td>' . htmlspecialchars((string)$dev['datum']) . '</td>
            <td>' . htmlspecialchars($web) . '</td>
        </tr>';
    }
}

/* ===========================
   COUNTS
   =========================== */

function settings_count(int $valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function menu_count(int $valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users_menu WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function menu_users_skup_count(int $valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users_skup_menu WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function users_count(int $valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function users_skup_count(int $valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users_skup WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);
    return (int)$stmt->fetchColumn();
}

function users_log_count(): int
{
    global $pdo;
    return (int)$pdo->query('SELECT COUNT(*) FROM log_users')->fetchColumn();
}
