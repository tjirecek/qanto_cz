<?php
declare(strict_types=1);

function rep_akce_unsubscribe_secret(): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];

    return trim((string)($config['newsletter_unsubscribe_secret'] ?? ''));
}

function rep_akce_unsubscribe_token(int $userId, string $email): string
{
    $secret = rep_akce_unsubscribe_secret();
    if ($secret === '') {
        throw new RuntimeException('Chybí newsletter_unsubscribe_secret v INI konfiguraci.');
    }

    $normalizedEmail = mb_strtolower(trim($email), 'UTF-8');

    return hash_hmac('sha256', 'rep_akce|' . $userId . '|' . $normalizedEmail, $secret);
}

function rep_akce_unsubscribe_url(?array $recipient): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];
    $baseUrl = rtrim(trim((string)($config['newsletter_public_base_url'] ?? '')), '/');
    $url = $baseUrl . '/cz/odhlaseni-letaku';
    $separator = str_contains($url, '?') ? '&' : '?';

    $userId = (int)($recipient['id'] ?? 0);
    $email = trim((string)($recipient['email'] ?? ''));
    if ($recipient === null || $userId <= 0 || $email === '') {
        return $url . $separator . 'uid=preview&token=preview';
    }

    return $url . $separator . 'uid=' . $userId . '&token=' . rep_akce_unsubscribe_token($userId, $email);
}

function rep_akce_unsubscribe_recipient(PDO $pdo, int $userId, string $token): ?array
{
    if ($userId <= 0 || preg_match('~^[a-f0-9]{64}$~i', $token) !== 1) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, email, registered, valid FROM rep_akce_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($recipient)) {
        return null;
    }

    try {
        $expectedToken = rep_akce_unsubscribe_token((int)$recipient['id'], (string)$recipient['email']);
    } catch (Throwable) {
        return null;
    }

    return hash_equals($expectedToken, strtolower($token)) ? $recipient : null;
}

function rep_akce_unsubscribe_all_by_email(PDO $pdo, string $email): int
{
    $normalizedEmail = mb_strtolower(trim($email), 'UTF-8');
    if ($normalizedEmail === '') {
        return 0;
    }

    $stmt = $pdo->prepare(
        'UPDATE rep_akce_users
         SET registered = 0,
             datum_do = CURDATE(),
             user_u = :user_u
         WHERE LOWER(TRIM(email)) = :email
           AND registered = 1'
    );
    $stmt->execute([
        ':email' => $normalizedEmail,
        ':user_u' => 'newsletter-unsubscribe',
    ]);

    return $stmt->rowCount();
}

function rep_akce_unsubscribe_mask_email(string $email): string
{
    $email = trim($email);
    if (!str_contains($email, '@')) {
        return '';
    }

    [$local, $domain] = explode('@', $email, 2);
    $visible = mb_substr($local, 0, min(2, mb_strlen($local, 'UTF-8')), 'UTF-8');

    return $visible . str_repeat('*', max(3, mb_strlen($local, 'UTF-8') - mb_strlen($visible, 'UTF-8'))) . '@' . $domain;
}
