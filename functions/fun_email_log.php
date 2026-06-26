<?php
declare(strict_types=1);

function email_log_schema_sql(): string
{
    return "CREATE TABLE IF NOT EXISTS log_emails (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        public_id CHAR(36) NOT NULL,
        context VARCHAR(60) NOT NULL,
        template_code VARCHAR(80) DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        recipient_email VARCHAR(190) NOT NULL,
        recipient_name VARCHAR(160) DEFAULT NULL,
        sender_email VARCHAR(190) DEFAULT NULL,
        sender_name VARCHAR(160) DEFAULT NULL,
        reply_to_email VARCHAR(190) DEFAULT NULL,
        related_table VARCHAR(80) DEFAULT NULL,
        related_id BIGINT UNSIGNED DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'queued',
        provider VARCHAR(60) DEFAULT NULL,
        provider_message_id VARCHAR(190) DEFAULT NULL,
        error_message TEXT DEFAULT NULL,
        payload_json MEDIUMTEXT DEFAULT NULL,
        body_text MEDIUMTEXT DEFAULT NULL,
        body_html MEDIUMTEXT DEFAULT NULL,
        queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME DEFAULT NULL,
        failed_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_log_emails_public_id (public_id),
        KEY idx_log_emails_recipient (recipient_email),
        KEY idx_log_emails_context (context, template_code),
        KEY idx_log_emails_related (related_table, related_id),
        KEY idx_log_emails_status (status, queued_at),
        KEY idx_log_emails_provider_message (provider, provider_message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

function email_log_prepare_table(PDO $pdo): void
{
    $pdo->exec(email_log_schema_sql());
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
