<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';

function news_translate_response(bool $ok, array $payload = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function news_translate_config_value(string $key, string $default = ''): string
{
    $config = app_bootstrap_config();

    return trim((string)($config[$key] ?? $default));
}

function news_translate_deepl_url(string $authKey): string
{
    $configured = news_translate_config_value('deepl_api_url');
    if ($configured !== '') {
        return $configured;
    }

    return str_ends_with($authKey, ':fx')
        ? 'https://api-free.deepl.com/v2/translate'
        : 'https://api.deepl.com/v2/translate';
}

function news_translate_deepl(array $texts, bool $html): array
{
    $authKey = news_translate_config_value('deepl_auth_key');
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
        'target_lang' => news_translate_config_value('deepl_target_lang', 'EN'),
    ];
    foreach ($payloadTexts as $text) {
        $postFields['text'][] = $text;
    }
    if ($html) {
        $postFields['tag_handling'] = 'html';
    }

    $ch = curl_init(news_translate_deepl_url($authKey));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => news_translate_deepl_post_fields($postFields),
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

function news_translate_deepl_post_fields(array $fields): string
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

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    if (!admin_session_is_logged() || !in_array((int)admin_session_prava(), [1, 2], true)) {
        news_translate_response(false, ['error' => 'Forbidden'], 403);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        news_translate_response(false, ['error' => 'Method not allowed'], 405);
    }

    $newsId = (int)($_POST['news_id'] ?? 0);
    if ($newsId <= 0) {
        news_translate_response(false, ['error' => 'Chybi ID novinky.'], 400);
    }

    $stmt = $pdo->prepare('SELECT nazev_cz, perex_cz, text_cz, seo_title_cz, seo_description_cz FROM news WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $newsId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        news_translate_response(false, ['error' => 'Novinka nebyla nalezena.'], 404);
    }

    $plain = news_translate_deepl([
        'nazev' => (string)($row['nazev_cz'] ?? ''),
        'seo_title' => (string)($row['seo_title_cz'] ?? ''),
        'seo_description' => (string)($row['seo_description_cz'] ?? ''),
    ], false);
    $html = news_translate_deepl([
        'perex' => (string)($row['perex_cz'] ?? ''),
        'text' => (string)($row['text_cz'] ?? ''),
    ], true);

    news_translate_response(true, [
        'data' => [
            'nazev' => $plain['nazev'] ?? '',
            'perex' => $html['perex'] ?? '',
            'text' => $html['text'] ?? '',
            'seo_title' => $plain['seo_title'] ?? '',
            'seo_description' => $plain['seo_description'] ?? '',
        ],
    ]);
} catch (Throwable $e) {
    news_translate_response(false, ['error' => $e->getMessage()], 500);
}
