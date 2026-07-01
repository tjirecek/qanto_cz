<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';

function admin_translate_response(bool $ok, array $payload = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_translate_config_value(string $key, string $default = ''): string
{
    $config = app_bootstrap_config();

    return trim((string)($config[$key] ?? $default));
}

function admin_translate_deepl_url(string $authKey): string
{
    $configured = admin_translate_config_value('deepl_api_url');
    if ($configured !== '') {
        return $configured;
    }

    return str_ends_with($authKey, ':fx')
        ? 'https://api-free.deepl.com/v2/translate'
        : 'https://api.deepl.com/v2/translate';
}

function admin_translate_deepl_post_fields(array $fields): string
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

function admin_translate_deepl(array $texts, bool $html): array
{
    $authKey = admin_translate_config_value('deepl_auth_key');
    if ($authKey === '') {
        throw new RuntimeException('Neni nastaven deepl_auth_key v INI konfiguraci.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL neni dostupne.');
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
        return array_fill_keys(array_keys($texts), '');
    }

    $postFields = [
        'source_lang' => 'CS',
        'target_lang' => admin_translate_config_value('deepl_target_lang', 'EN'),
    ];
    foreach ($payloadTexts as $text) {
        $postFields['text'][] = $text;
    }
    if ($html) {
        $postFields['tag_handling'] = 'html';
    }

    $ch = curl_init(admin_translate_deepl_url($authKey));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => admin_translate_deepl_post_fields($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . $authKey,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        throw new RuntimeException('DeepL nevratil odpoved. ' . $curlError);
    }

    $decoded = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($decoded) ? (string)($decoded['message'] ?? '') : '';
        throw new RuntimeException('DeepL chyba ' . $status . ($message !== '' ? ': ' . $message : '.'));
    }

    if (!is_array($decoded) || !isset($decoded['translations']) || !is_array($decoded['translations'])) {
        throw new RuntimeException('DeepL vratil neocekavanou odpoved.');
    }

    $result = array_fill_keys(array_keys($texts), '');
    foreach ($decoded['translations'] as $i => $translation) {
        $key = $indexes[$i] ?? null;
        if ($key !== null) {
            $result[$key] = (string)($translation['text'] ?? '');
        }
    }

    return $result;
}

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        admin_translate_response(false, ['error' => 'Forbidden'], 403);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        admin_translate_response(false, ['error' => 'Method not allowed'], 405);
    }

    $itemsRaw = (string)($_POST['items'] ?? '');
    $items = json_decode($itemsRaw, true);
    if (!is_array($items)) {
        admin_translate_response(false, ['error' => 'Chybi data k prekladu.'], 400);
    }

    $plain = [];
    $html = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $key = trim((string)($item['key'] ?? ''));
        $text = (string)($item['text'] ?? '');
        $format = trim((string)($item['format'] ?? 'text'));
        if ($key === '' || trim(strip_tags($text)) === '') {
            continue;
        }

        if ($format === 'html') {
            $html[$key] = $text;
        } else {
            $plain[$key] = $text;
        }
    }

    $data = [];
    if ($plain !== []) {
        $data += admin_translate_deepl($plain, false);
    }
    if ($html !== []) {
        $data += admin_translate_deepl($html, true);
    }

    admin_translate_response(true, ['data' => $data]);
} catch (Throwable $e) {
    admin_translate_response(false, ['error' => $e->getMessage()], 500);
}
