<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function napiste_nam_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function napiste_nam_email_list(mixed $value): array
{
    $parts = preg_split('/[,;\n\r]+/', trim((string)$value)) ?: [];
    $emails = [];
    foreach ($parts as $email) {
        $email = trim((string)$email);
        if ($email === '') {
            continue;
        }

        $key = mb_strtolower($email, 'UTF-8');
        if (!isset($emails[$key])) {
            $emails[$key] = $email;
        }
    }

    return array_values($emails);
}

function napiste_nam_email_list_value(mixed $value): string
{
    return implode(', ', napiste_nam_email_list($value));
}

function napiste_nam_validate_email_list(mixed $value, string $label, bool $required): string
{
    $emails = napiste_nam_email_list($value);
    if ($required && $emails === []) {
        throw new RuntimeException($label . ' je povinný.');
    }

    foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($label . ' obsahuje neplatný e-mail: ' . $email);
        }
    }

    return implode(', ', $emails);
}

function napiste_nam_category_count(?int $valid = null): int
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return 0;
    }

    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM napiste_nam_kategorie')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM napiste_nam_kategorie WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

function napiste_nam_category_next_order(): int
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return 1;
    }

    return (int)$pdo->query('SELECT COALESCE(MAX(poradi), 0) + 1 FROM napiste_nam_kategorie')->fetchColumn();
}

function napiste_nam_category_get(int $id): ?array
{
    global $pdo;

    if (!($pdo instanceof PDO) || $id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM napiste_nam_kategorie WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function napiste_nam_categories_all(?int $valid = 1, int $limit = 500): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $sql = 'SELECT * FROM napiste_nam_kategorie';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' ORDER BY poradi ASC, nazev_cz ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function napiste_nam_category_save(array $data, ?int $id = null): int
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO připojení není dostupné.');
    }

    $nazevCz = trim((string)($data['nazev_cz'] ?? ''));
    if ($nazevCz === '') {
        throw new RuntimeException('Název CZ je povinný.');
    }

    $row = [
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => $nazevCz,
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':email_to' => napiste_nam_validate_email_list($data['email_to'] ?? '', 'E-mail příjemce', true),
        ':email_copy' => napiste_nam_validate_email_list($data['email_copy'] ?? '', 'E-mail kopie', false),
        ':type' => max(0, (int)($data['type'] ?? 1)),
        ':visible' => isset($data['visible']) ? 1 : 0,
        ':valid' => isset($data['valid']) ? 1 : 0,
        ':user_u' => admin_session_user(),
    ];

    if ($id !== null && $id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE napiste_nam_kategorie
             SET poradi = :poradi,
                 nazev_cz = :nazev_cz,
                 nazev_en = :nazev_en,
                 email_to = :email_to,
                 email_copy = :email_copy,
                 type = :type,
                 visible = :visible,
                 valid = :valid,
                 user_u = :user_u
             WHERE id = :id'
        );
        $stmt->execute($row + [':id' => $id]);
        admin_auto_translate_record('napiste_nam.category', $id, [
            'nazev_cz' => $row[':nazev_cz'],
            'nazev_en' => $row[':nazev_en'],
        ] + $data);
        return $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO napiste_nam_kategorie
            (poradi, nazev_cz, nazev_en, email_to, email_copy, type, visible, valid, user_i, user_u)
         VALUES
            (:poradi, :nazev_cz, :nazev_en, :email_to, :email_copy, :type, :visible, :valid, :user_i, :user_u)'
    );
    $stmt->execute($row + [':user_i' => admin_session_user()]);

    $newId = (int)$pdo->lastInsertId();
    admin_auto_translate_record('napiste_nam.category', $newId, [
        'nazev_cz' => $row[':nazev_cz'],
        'nazev_en' => $row[':nazev_en'],
    ] + $data);

    return $newId;
}

function napiste_nam_category_delete(int $id): void
{
    global $pdo;

    if (!($pdo instanceof PDO) || $id <= 0) {
        return;
    }

    $stmt = $pdo->prepare('UPDATE napiste_nam_kategorie SET valid = 0, visible = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}

function napiste_nam_message_count(?int $valid = 1): int
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return 0;
    }

    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM napiste_nam_zpravy')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM napiste_nam_zpravy WHERE valid = :valid');
    $stmt->execute([':valid' => $valid === 1 ? 1 : 0]);
    return (int)$stmt->fetchColumn();
}

function napiste_nam_messages_all(?int $valid = 1, int $limit = 500): array
{
    global $pdo;

    if (!($pdo instanceof PDO)) {
        return [];
    }

    $sql = 'SELECT z.*, k.nazev_cz AS kategorie_nazev
            FROM napiste_nam_zpravy z
            LEFT JOIN napiste_nam_kategorie k ON k.id = z.kategorie_id';
    $params = [];
    if ($valid !== null) {
        $sql .= ' WHERE z.valid = :valid';
        $params[':valid'] = $valid === 1 ? 1 : 0;
    }
    $sql .= ' ORDER BY z.datum DESC, z.id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function napiste_nam_message_delete(int $id): void
{
    global $pdo;

    if (!($pdo instanceof PDO) || $id <= 0) {
        return;
    }

    $stmt = $pdo->prepare('UPDATE napiste_nam_zpravy SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);
}
