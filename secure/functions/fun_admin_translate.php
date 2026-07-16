<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate_map.php';

function admin_auto_translate_config_value(string $key, string $default = ''): string
{
    $config = function_exists('app_bootstrap_config') ? app_bootstrap_config() : [];

    return trim((string)($config[$key] ?? $default));
}

function admin_auto_translate_deepl_url(string $authKey): string
{
    $configured = admin_auto_translate_config_value('deepl_api_url');
    if ($configured !== '') {
        return $configured;
    }

    return str_ends_with($authKey, ':fx')
        ? 'https://api-free.deepl.com/v2/translate'
        : 'https://api.deepl.com/v2/translate';
}

function admin_auto_translate_deepl_post_fields(array $fields): string
{
    $pairs = [];
    foreach ($fields as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$item);
            }
            continue;
        }

        $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
    }

    return implode('&', $pairs);
}

function admin_auto_translate_deepl(array $texts, bool $html): array
{
    $authKey = admin_auto_translate_config_value('deepl_auth_key');
    if ($authKey === '' || !function_exists('curl_init')) {
        return [];
    }

    $indexes = [];
    $payloadTexts = [];
    foreach ($texts as $key => $text) {
        $text = trim((string)$text);
        if ($text === '') {
            continue;
        }

        $indexes[] = $key;
        $payloadTexts[] = $text;
    }

    if ($payloadTexts === []) {
        return [];
    }

    $postFields = [
        'source_lang' => 'CS',
        'target_lang' => admin_auto_translate_config_value('deepl_target_lang', 'EN'),
    ];
    foreach ($payloadTexts as $text) {
        $postFields['text'][] = $text;
    }
    if ($html) {
        $postFields['tag_handling'] = 'html';
    }

    $ch = curl_init(admin_auto_translate_deepl_url($authKey));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => admin_auto_translate_deepl_post_fields($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . $authKey,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($decoded) || !is_array($decoded['translations'] ?? null)) {
        return [];
    }

    $result = [];
    foreach ($decoded['translations'] as $i => $translation) {
        $key = $indexes[$i] ?? null;
        if ($key !== null) {
            $result[$key] = (string)($translation['text'] ?? '');
        }
    }

    return $result;
}

function admin_auto_translate_enabled(array $data, ?array $existing = null): int
{
    if (array_key_exists('skip_auto_translate_en', $data)) {
        return 0;
    }
    if (array_key_exists('skip_auto_translate_en', $_POST)) {
        return 0;
    }
    if (array_key_exists('auto_translate_en', $data)) {
        return (int)$data['auto_translate_en'] === 1 ? 1 : 0;
    }
    if (array_key_exists('auto_translate_en', $_POST)) {
        return (int)$_POST['auto_translate_en'] === 1 ? 1 : 0;
    }
    if (is_array($existing) && array_key_exists('auto_translate_en', $existing)) {
        return (int)$existing['auto_translate_en'] === 1 ? 1 : 0;
    }

    return 1;
}

function admin_auto_translate_payload(string $context, array $data, ?array $existing = null): array
{
    $map = admin_translate_field_map($context);
    if ($map === null) {
        return $data;
    }

    $autoTranslate = admin_auto_translate_enabled($data, $existing);
    $data['auto_translate_en'] = $autoTranslate;
    if ($autoTranslate !== 1) {
        return $data;
    }

    $plain = [];
    $html = [];
    foreach (($map['fields'] ?? []) as $key => $field) {
        $sourceColumn = (string)($field['cz'] ?? '');
        $targetColumn = (string)($field['en'] ?? '');
        if ($sourceColumn === '' || $targetColumn === '') {
            continue;
        }

        $source = (string)($data[$sourceColumn] ?? $existing[$sourceColumn] ?? '');
        if (trim(strip_tags($source)) === '') {
            $data[$targetColumn] = '';
            continue;
        }

        if ((string)($field['format'] ?? 'text') === 'html') {
            $html[$key] = $source;
        } else {
            $plain[$key] = $source;
        }
    }

    $translations = [];
    if ($plain !== []) {
        $translations += admin_auto_translate_deepl($plain, false);
    }
    if ($html !== []) {
        $translations += admin_auto_translate_deepl($html, true);
    }

    if ($translations === []) {
        return $data;
    }

    foreach (($map['fields'] ?? []) as $key => $field) {
        $targetColumn = (string)($field['en'] ?? '');
        if ($targetColumn !== '' && array_key_exists($key, $translations)) {
            $data[$targetColumn] = $translations[$key];
        }
    }

    return $data;
}

function admin_auto_translate_checkbox(?array $row = null, string $id = 'skip_auto_translate_en'): string
{
    $autoTranslate = $row === null || (int)($row['auto_translate_en'] ?? 1) === 1;
    $id = preg_match('~^[A-Za-z][A-Za-z0-9_-]{0,80}$~', $id) ? $id : 'skip_auto_translate_en';
    $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

    return '<div class="form-check form-switch">'
        . '<input class="form-check-input" type="checkbox" name="skip_auto_translate_en" id="' . $safeId . '" value="1" ' . ($autoTranslate ? '' : 'checked') . '>'
        . '<label class="form-check-label" for="' . $safeId . '">automaticky nepřekládat do EN</label>'
        . '<div class="form-text">Výchozí stav je automatický překlad CZ -> EN při uložení.</div>'
        . '</div>';
}

function admin_auto_translate_record(string $context, int $id, array $data, ?array $existing = null): void
{
    global $pdo;

    if (!($pdo instanceof PDO) || $id <= 0) {
        return;
    }

    $map = admin_translate_field_map($context);
    if ($map === null) {
        return;
    }

    $table = (string)($map['table'] ?? '');
    $primaryKey = (string)($map['primary_key'] ?? 'id');
    $manualFlag = (string)($map['manual_flag'] ?? 'auto_translate_en');
    if ($table === '' || $primaryKey === '' || $manualFlag === '') {
        return;
    }

    if ($existing === null) {
        $stmtExisting = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `' . $primaryKey . '` = :id LIMIT 1');
        $stmtExisting->execute([':id' => $id]);
        $existingRow = $stmtExisting->fetch(PDO::FETCH_ASSOC);
        $existing = is_array($existingRow) ? $existingRow : null;
    }

    $payload = admin_auto_translate_payload($context, $data, $existing);
    $sets = ['`' . $manualFlag . '` = :auto_translate_en'];
    $params = [
        ':auto_translate_en' => (int)($payload['auto_translate_en'] ?? 1),
        ':id' => $id,
    ];

    foreach (($map['fields'] ?? []) as $field) {
        $targetColumn = (string)($field['en'] ?? '');
        if ($targetColumn === '' || !array_key_exists($targetColumn, $payload)) {
            continue;
        }

        $placeholder = ':field_' . count($params);
        $sets[] = '`' . $targetColumn . '` = ' . $placeholder;
        $params[$placeholder] = (string)$payload[$targetColumn];
    }

    $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `' . $primaryKey . '` = :id';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $key === ':id' || $key === ':auto_translate_en' ? (int)$value : $value, $key === ':id' || $key === ':auto_translate_en' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
}
