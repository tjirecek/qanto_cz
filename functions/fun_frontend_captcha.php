<?php
declare(strict_types=1);

function frontend_captcha_text(string $key, string $fallback): string
{
    return function_exists('ui_text') ? ui_text($key, $fallback) : $fallback;
}

function frontend_captcha_session_key(string $formKey): string
{
    $safeKey = preg_replace('~[^a-z0-9_-]+~i', '_', $formKey) ?: 'default';

    return 'frontend_captcha_' . $safeKey;
}

/**
 * @return array{token: string, question: string}
 */
function frontend_captcha_challenge(string $formKey): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return ['token' => '', 'question' => ''];
    }

    $sessionKey = frontend_captcha_session_key($formKey);
    $challenge = $_SESSION[$sessionKey] ?? null;
    $createdAt = is_array($challenge) ? (int)($challenge['created_at'] ?? 0) : 0;
    if (!is_array($challenge)
        || empty($challenge['token'])
        || empty($challenge['answer'])
        || empty($challenge['question'])
        || $createdAt <= 0
        || (time() - $createdAt) > 1800
    ) {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        $challenge = [
            'token' => bin2hex(random_bytes(16)),
            'answer' => (string)($a + $b),
            'question' => sprintf(frontend_captcha_text('form.captcha_question_sum', 'Kolik je %d + %d?'), $a, $b),
            'created_at' => time(),
        ];
        $_SESSION[$sessionKey] = $challenge;
    }

    return [
        'token' => (string)$challenge['token'],
        'question' => (string)$challenge['question'],
    ];
}

function frontend_captcha_reset(string $formKey): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION[frontend_captcha_session_key($formKey)]);
    }
}

/**
 * @param array<string, mixed> $data
 * @return array{ok: bool, bot: bool, message: string}
 */
function frontend_captcha_validate(string $formKey, array $data): array
{
    if (trim((string)($data['website'] ?? '')) !== '') {
        return ['ok' => false, 'bot' => true, 'message' => ''];
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [
            'ok' => false,
            'bot' => false,
            'message' => frontend_captcha_text('form.captcha_invalid', 'Ověření proti robotům nebylo správné. Zkuste to prosím znovu.'),
        ];
    }

    $sessionKey = frontend_captcha_session_key($formKey);
    $challenge = $_SESSION[$sessionKey] ?? null;
    $postedToken = trim((string)($data['captcha_token'] ?? ''));
    $postedAnswer = preg_replace('~\s+~', '', trim((string)($data['captcha_answer'] ?? ''))) ?? '';

    if (!is_array($challenge)
        || $postedToken === ''
        || $postedAnswer === ''
        || !hash_equals((string)($challenge['token'] ?? ''), $postedToken)
    ) {
        return [
            'ok' => false,
            'bot' => false,
            'message' => frontend_captcha_text('form.captcha_invalid', 'Ověření proti robotům nebylo správné. Zkuste to prosím znovu.'),
        ];
    }

    $createdAt = (int)($challenge['created_at'] ?? 0);
    $age = time() - $createdAt;
    if ($createdAt <= 0 || $age < 1 || $age > 1800) {
        frontend_captcha_reset($formKey);

        return [
            'ok' => false,
            'bot' => false,
            'message' => frontend_captcha_text('form.captcha_invalid', 'Ověření proti robotům nebylo správné. Zkuste to prosím znovu.'),
        ];
    }

    if (!hash_equals((string)($challenge['answer'] ?? ''), $postedAnswer)) {
        return [
            'ok' => false,
            'bot' => false,
            'message' => frontend_captcha_text('form.captcha_invalid', 'Ověření proti robotům nebylo správné. Zkuste to prosím znovu.'),
        ];
    }

    frontend_captcha_reset($formKey);

    return ['ok' => true, 'bot' => false, 'message' => ''];
}

function frontend_captcha_render(string $formKey, string $idPrefix = 'captcha'): void
{
    $challenge = frontend_captcha_challenge($formKey);
    $safePrefix = preg_replace('~[^a-z0-9_-]+~i', '-', $idPrefix) ?: 'captcha';
    $answerId = $safePrefix . '-captcha-answer';
    ?>
    <div class="frontend-captcha__trap" aria-hidden="true">
        <label for="<?= htmlspecialchars($safePrefix . '-website', ENT_QUOTES, 'UTF-8') ?>">Website</label>
        <input type="text" name="website" id="<?= htmlspecialchars($safePrefix . '-website', ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" autocomplete="off">
    </div>
    <div class="frontend-captcha">
        <input type="hidden" name="captcha_token" value="<?= htmlspecialchars($challenge['token'], ENT_QUOTES, 'UTF-8') ?>">
        <label class="frontend-captcha__label" for="<?= htmlspecialchars($answerId, ENT_QUOTES, 'UTF-8') ?>">
            <span><?= htmlspecialchars(frontend_captcha_text('form.captcha_label', 'Ověření'), ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= htmlspecialchars($challenge['question'], ENT_QUOTES, 'UTF-8') ?></strong>
        </label>
        <input
            type="text"
            name="captcha_answer"
            id="<?= htmlspecialchars($answerId, ENT_QUOTES, 'UTF-8') ?>"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="off"
            placeholder="<?= htmlspecialchars(frontend_captcha_text('form.captcha_answer', 'Výsledek'), ENT_QUOTES, 'UTF-8') ?>"
            required
        >
        <small><?= htmlspecialchars(frontend_captcha_text('form.captcha_help', 'Pro ochranu proti robotům napište výsledek.'), ENT_QUOTES, 'UTF-8') ?></small>
    </div>
    <?php
}
