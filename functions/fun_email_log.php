<?php
declare(strict_types=1);

function email_log_prepare_table(PDO $pdo): void
{
    // Schema is managed exclusively by SQL migrations.
}

function email_log_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function email_log_config_value(array $config, array $keys): ?string
{
    foreach ($keys as $key) {
        $value = trim((string)($config[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return null;
}

function email_log_defaults_from_config(array $config): array
{
    $provider = email_log_config_value($config, ['mail_provider', 'smtp_provider', 'smtp_server']);
    $senderEmail = email_log_config_value($config, ['smtp_from', 'mail_from']);
    $senderName = email_log_config_value($config, ['smtp_from_name', 'mail_from_name']);

    return [
        'provider' => $provider,
        'sender_email' => $senderEmail,
        'sender_name' => $senderName,
    ];
}

function email_log_create(PDO $pdo, array $data): int
{
    if (!$pdo->inTransaction()) {
        email_log_prepare_table($pdo);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO log_emails (
            public_id, context, template_code, subject, recipient_email, recipient_name,
            sender_email, sender_name, reply_to_email, related_table, related_id,
            status, provider, payload_json, body_text, body_html
        ) VALUES (
            :public_id, :context, :template_code, :subject, :recipient_email, :recipient_name,
            :sender_email, :sender_name, :reply_to_email, :related_table, :related_id,
            :status, :provider, :payload_json, :body_text, :body_html
        )'
    );
    $stmt->execute([
        ':public_id' => (string)($data['public_id'] ?? email_log_uuid()),
        ':context' => (string)($data['context'] ?? 'system'),
        ':template_code' => $data['template_code'] ?? null,
        ':subject' => (string)($data['subject'] ?? ''),
        ':recipient_email' => (string)($data['recipient_email'] ?? ''),
        ':recipient_name' => $data['recipient_name'] ?? null,
        ':sender_email' => $data['sender_email'] ?? null,
        ':sender_name' => $data['sender_name'] ?? null,
        ':reply_to_email' => $data['reply_to_email'] ?? null,
        ':related_table' => $data['related_table'] ?? null,
        ':related_id' => isset($data['related_id']) ? (int)$data['related_id'] : null,
        ':status' => (string)($data['status'] ?? 'queued'),
        ':provider' => $data['provider'] ?? null,
        ':payload_json' => isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ($data['payload_json'] ?? null),
        ':body_text' => $data['body_text'] ?? null,
        ':body_html' => $data['body_html'] ?? null,
    ]);

    return (int)$pdo->lastInsertId();
}

function email_log_mark_sent(PDO $pdo, int $id, ?string $providerMessageId = null): void
{
    $stmt = $pdo->prepare(
        "UPDATE log_emails
         SET status = 'sent',
             provider_message_id = :provider_message_id,
             error_message = NULL,
             sent_at = NOW(),
             failed_at = NULL
         WHERE id = :id"
    );
    $stmt->execute([
        ':provider_message_id' => $providerMessageId,
        ':id' => $id,
    ]);
}

function email_log_mark_failed(PDO $pdo, int $id, string $errorMessage): void
{
    $stmt = $pdo->prepare(
        "UPDATE log_emails
         SET status = 'failed',
             error_message = :error_message,
             failed_at = NOW()
         WHERE id = :id"
    );
    $stmt->execute([
        ':error_message' => $errorMessage,
        ':id' => $id,
    ]);
}
