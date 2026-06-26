<?php
declare(strict_types=1);

require_once ROOT_DIR . '/functions/fun_email_log.php';
require_once ROOT_DIR . '/functions/fun_mailer.php';

function users_password_prepare_column(PDO $pdo): void
{
    static $prepared = false;
    if ($prepared) {
        return;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'");
    $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    $type = strtolower((string)($column['Type'] ?? ''));

    if (!preg_match('~^varchar\((\d+)\)$~', $type, $matches) || (int)$matches[1] < 255) {
        $pdo->exec("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL DEFAULT ''");
    }

    $prepared = true;
}

function users_password_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function users_password_is_legacy_sha1(string $hash): bool
{
    return preg_match('~^[a-f0-9]{40}$~i', trim($hash)) === 1;
}

function users_password_verify(string $password, string $storedHash): bool
{
    $storedHash = trim($storedHash);
    if ($storedHash === '') {
        return false;
    }

    if (users_password_is_legacy_sha1($storedHash)) {
        return hash_equals(strtolower($storedHash), sha1($password));
    }

    return password_verify($password, $storedHash);
}

function users_password_needs_rehash(string $storedHash): bool
{
    $storedHash = trim($storedHash);
    if ($storedHash === '') {
        return false;
    }

    return users_password_is_legacy_sha1($storedHash)
        || password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

function users_password_reset_config(): array
{
    global $config;

    return is_array($config ?? null) ? $config : [];
}

function users_password_reset_config_value(array $keys, ?string $default = null): ?string
{
    $config = users_password_reset_config();
    foreach ($keys as $key) {
        $value = trim((string)($config[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return $default;
}

function users_password_reset_admin_name(): string
{
    return users_password_reset_config_value(
        ['admin_app_name', 'password_reset_app_name', 'site_name', 'project_name'],
        'Administrace'
    ) ?? 'Administrace';
}

function users_password_reset_admin_base_url(): string
{
    $configured = users_password_reset_config_value(['admin_base_url', 'password_reset_base_url', 'secure_base_url']);
    if ($configured !== null) {
        return rtrim($configured, '/');
    }

    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '/secure';
    }

    return ($isHttps ? 'https' : 'http') . '://' . $host . '/secure';
}

function users_password_reset_url(string $token): string
{
    return users_password_reset_admin_base_url() . '/?users_password_reset=' . rawurlencode($token);
}

function users_password_reset_token_hash(string $token): string
{
    return hash('sha256', $token);
}

function users_password_reset_create(PDO $pdo, int $userId, string $createdBy, int $ttlHours = 48): array
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = users_password_reset_token_hash($token);
    $ttlHours = max(1, $ttlHours);
    $expiresAt = date('Y-m-d H:i:s', time() + ($ttlHours * 3600));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE users_password_resets
             SET valid = 0, user_u = :user_u
             WHERE user_id = :user_id
               AND used_at IS NULL
               AND valid = 1'
        );
        $stmt->execute([
            ':user_u' => $createdBy,
            ':user_id' => $userId,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO users_password_resets
                (user_id, token_hash, expires_at, user_i, user_u)
             VALUES
                (:user_id, :token_hash, :expires_at, :user_i, :user_u)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':user_i' => $createdBy,
            ':user_u' => $createdBy,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'token' => $token,
        'url' => users_password_reset_url($token),
        'expires_at' => $expiresAt,
    ];
}

function users_password_reset_send_email(PDO $pdo, int $userId, string $email, string $name, string $login, string $url): array
{
    global $config;

    $email = trim($email);
    if ($email === '') {
        return ['ok' => false, 'message' => 'Uživatel nemá vyplněný e-mail.'];
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return ['ok' => false, 'message' => 'E-mail uživatele není platný.'];
    }

    $displayName = trim($name) !== '' ? trim($name) : $login;
    $adminName = users_password_reset_admin_name();
    $subject = $adminName . ' - nastavení hesla';
    $bodyText =
        "Dobrý den,\n\n"
        . "pro uživatele {$displayName} byl vytvořen odkaz pro nastavení hesla do administrace {$adminName}.\n\n"
        . "{$url}\n\n"
        . "Odkaz je jednorázový a platí 48 hodin.\n";
    $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeAdminName = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');
    $bodyHtml =
        '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2937;">'
        . '<p>Dobrý den,</p>'
        . '<p>pro uživatele <strong>' . $safeName . '</strong> byl vytvořen odkaz pro nastavení hesla do administrace ' . $safeAdminName . '.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:10px 14px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:4px;">Nastavit heslo</a></p>'
        . '<p>Odkaz je jednorázový a platí 48 hodin.</p>'
        . '<p style="color:#6b7280;">Pokud tlačítko nefunguje, otevřete tento odkaz:<br>' . $safeUrl . '</p>'
        . '</div>';

    $emailDefaults = email_log_defaults_from_config(is_array($config ?? null) ? $config : []);
    $emailLogId = email_log_create($pdo, [
        'context' => 'admin',
        'template_code' => 'users_password_reset',
        'subject' => $subject,
        'recipient_email' => $email,
        'recipient_name' => $displayName,
        'sender_email' => $emailDefaults['sender_email'],
        'sender_name' => $emailDefaults['sender_name'],
        'related_table' => 'users',
        'related_id' => $userId,
        'status' => 'queued',
        'provider' => $emailDefaults['provider'],
        'payload' => [
            'login' => $login,
            'password_reset_url' => $url,
        ],
        'body_text' => $bodyText,
        'body_html' => $bodyHtml,
    ]);

    try {
        $providerMessageId = mailer_send_smtp(is_array($config ?? null) ? $config : [], [
            'recipient_email' => $email,
            'recipient_name' => $displayName,
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ]);
        email_log_mark_sent($pdo, $emailLogId, $providerMessageId);
    } catch (Throwable $e) {
        email_log_mark_failed($pdo, $emailLogId, $e->getMessage());
        return ['ok' => false, 'message' => 'E-mail se nepodařilo odeslat: ' . $e->getMessage(), 'email_log_id' => $emailLogId];
    }

    return ['ok' => true, 'message' => 'Reset hesla byl odeslán na e-mail ' . $email . '.', 'email_log_id' => $emailLogId];
}

function users_password_reset_request_for_user(PDO $pdo, int $userId, string $createdBy): array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, login, email, valid, admin, aktivni_l
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['ok' => false, 'message' => 'Uživatel neexistuje.'];
    }
    if ((int)($user['valid'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'Uživatel není validní.'];
    }
    if ((int)($user['aktivni_l'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'Uživatel není aktivní.'];
    }
    if ((int)($user['admin'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'Uživatel nemá povolený přístup do administrace.'];
    }

    $email = trim((string)($user['email'] ?? ''));
    if ($email === '') {
        return ['ok' => false, 'message' => 'Uživatel nemá vyplněný e-mail.'];
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return ['ok' => false, 'message' => 'E-mail uživatele není platný.'];
    }

    try {
        $reset = users_password_reset_create($pdo, (int)$user['id'], $createdBy);
        $mail = users_password_reset_send_email(
            $pdo,
            (int)$user['id'],
            $email,
            (string)($user['name'] ?? ''),
            (string)($user['login'] ?? ''),
            (string)$reset['url']
        );
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Reset hesla se nepodařilo vytvořit: ' . $e->getMessage()];
    }

    $mail['url'] = (string)$reset['url'];
    return $mail;
}

function users_password_reset_request_for_identifier(PDO $pdo, string $identifier, string $createdBy): array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return ['ok' => false, 'message' => 'Vyplňte login nebo e-mail.'];
    }

    $stmt = $pdo->prepare(
        'SELECT id, login, email
         FROM users
         WHERE valid = 1
           AND aktivni_l = 1
           AND admin = 1
           AND (login = :identifier_login OR email = :identifier_email)
         ORDER BY CASE WHEN login = :identifier_order THEN 0 ELSE 1 END, id
         LIMIT 2'
    );
    $stmt->execute([
        ':identifier_login' => $identifier,
        ':identifier_email' => $identifier,
        ':identifier_order' => $identifier,
    ]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users === []) {
        return ['ok' => false, 'message' => 'Aktivní admin uživatel s tímto loginem nebo e-mailem nebyl nalezen.'];
    }
    if (count($users) > 1 && filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false) {
        return ['ok' => false, 'message' => 'E-mail je přiřazen více uživatelům. Zadejte prosím konkrétní login.'];
    }

    return users_password_reset_request_for_user($pdo, (int)$users[0]['id'], $createdBy);
}

function users_password_reset_find(PDO $pdo, string $token): ?array
{
    $token = trim($token);
    if (!preg_match('~^[a-f0-9]{64}$~i', $token)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT
            r.id,
            r.user_id,
            r.expires_at,
            u.login,
            u.name,
            u.email
         FROM users_password_resets r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.token_hash = :token_hash
           AND r.used_at IS NULL
           AND r.valid = 1
           AND r.expires_at >= NOW()
           AND u.valid = 1
           AND u.aktivni_l = 1
           AND u.admin = 1
         LIMIT 1'
    );
    $stmt->execute([':token_hash' => users_password_reset_token_hash($token)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function users_password_reset_save_password(PDO $pdo, string $token, string $password, string $passwordConfirm, string $ip = ''): array
{
    if ($password === '' || $passwordConfirm === '') {
        return ['ok' => false, 'message' => 'Vyplňte nové heslo i potvrzení hesla.'];
    }
    if ($password !== $passwordConfirm) {
        return ['ok' => false, 'message' => 'Hesla se neshodují.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'message' => 'Heslo musí mít alespoň 8 znaků.'];
    }

    $reset = users_password_reset_find($pdo, $token);
    if (!$reset) {
        return ['ok' => false, 'message' => 'Odkaz pro nastavení hesla je neplatný nebo již vypršel.'];
    }

    users_password_prepare_column($pdo);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET password = :password,
                 user_u = :user_u
             WHERE id = :user_id
               AND valid = 1
               AND aktivni_l = 1
               AND admin = 1'
        );
        $stmt->execute([
            ':password' => users_password_hash($password),
            ':user_u' => 'users_password_reset',
            ':user_id' => (int)$reset['user_id'],
        ]);

        $stmt = $pdo->prepare(
            'UPDATE users_password_resets
             SET used_at = NOW(),
                 used_ip = :used_ip,
                 user_u = :user_u
             WHERE id = :id
               AND used_at IS NULL
               AND valid = 1'
        );
        $stmt->execute([
            ':used_ip' => $ip,
            ':user_u' => 'users_password_reset',
            ':id' => (int)$reset['id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Token již byl použit.');
        }

        $stmt = $pdo->prepare(
            'UPDATE users_password_resets
             SET valid = 0,
                 user_u = :user_u
             WHERE user_id = :user_id
               AND used_at IS NULL
               AND valid = 1'
        );
        $stmt->execute([
            ':user_u' => 'users_password_reset',
            ':user_id' => (int)$reset['user_id'],
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'message' => 'Heslo se nepodařilo uložit: ' . $e->getMessage()];
    }

    return ['ok' => true, 'message' => 'Heslo bylo nastaveno. Nyní se můžete přihlásit do administrace.'];
}
