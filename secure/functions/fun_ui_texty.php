<?php
declare(strict_types=1);

require_once __DIR__ . '/fun_admin_translate.php';

function ui_texty_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ui_texty_code_is_valid(string $code): bool
{
    return preg_match('~^[a-z0-9][a-z0-9_.-]{1,188}[a-z0-9]$~', $code) === 1;
}

function ui_texty_clean_value(string $value): string
{
    if (function_exists('plain_text')) {
        return plain_text($value);
    }

    $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
}

function ui_texty_preview(string $value, int $limit = 140): string
{
    $value = ui_texty_clean_value($value);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $limit
            ? mb_substr($value, 0, $limit, 'UTF-8') . '...'
            : $value;
    }

    return strlen($value) > $limit ? substr($value, 0, $limit) . '...' : $value;
}

function ui_texty_count(?int $valid = null): int
{
    global $pdo;

    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM ui_texty')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ui_texty WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function ui_texty_code_exists(string $code, int $ignoreId = 0): bool
{
    global $pdo;

    $sql = 'SELECT 1 FROM ui_texty WHERE code = :code';
    $params = [':code' => $code];
    if ($ignoreId > 0) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool)$stmt->fetchColumn();
}

function ui_texty_find(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM ui_texty WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function ui_texty_save_error(string $message): void
{
    echo '<div class="alert alert-warning mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>' . ui_texty_e($message) . '</div>';
}

function ui_texty_add(string $code, string $cz, string $en): bool
{
    global $pdo;

    $code = trim($code);
    $cz = ui_texty_clean_value($cz);
    $en = ui_texty_clean_value($en);

    if (!ui_texty_code_is_valid($code)) {
        ui_texty_save_error('Kód musí mít 3-190 znaků a může obsahovat malá písmena, čísla, tečku, pomlčku a podtržítko.');
        return false;
    }
    if ($cz === '') {
        ui_texty_save_error('CZ text je povinný.');
        return false;
    }
    if (ui_texty_code_exists($code)) {
        ui_texty_save_error('UI text s tímto kódem už existuje.');
        return false;
    }

    $user = admin_session_user();
    $stmt = $pdo->prepare('
        INSERT INTO ui_texty (code, cz, en, user_i, user_u)
        VALUES (:code, :cz, :en, :user_i, :user_u)
    ');
    $stmt->execute([
        ':code' => $code,
        ':cz' => $cz,
        ':en' => $en,
        ':user_i' => $user,
        ':user_u' => $user,
    ]);

    admin_auto_translate_record('ui_texty.record', (int)$pdo->lastInsertId(), array_merge($_POST, [
        'cz' => $cz,
        'en' => $en,
    ]));

    return true;
}

function ui_texty_edit(int $id, string $code, string $cz, string $en, int $valid): bool
{
    global $pdo;

    $existing = ui_texty_find($id);
    if ($existing === null) {
        ui_texty_save_error('Záznam nebyl nalezen.');
        return false;
    }

    $code = trim($code);
    $cz = ui_texty_clean_value($cz);
    $en = ui_texty_clean_value($en);

    if (!ui_texty_code_is_valid($code)) {
        ui_texty_save_error('Kód musí mít 3-190 znaků a může obsahovat malá písmena, čísla, tečku, pomlčku a podtržítko.');
        return false;
    }
    if ($cz === '') {
        ui_texty_save_error('CZ text je povinný.');
        return false;
    }
    if (ui_texty_code_exists($code, $id)) {
        ui_texty_save_error('UI text s tímto kódem už existuje.');
        return false;
    }

    $stmt = $pdo->prepare('
        UPDATE ui_texty
        SET code = :code,
            cz = :cz,
            en = :en,
            valid = :valid,
            user_u = :user_u
        WHERE id = :id
    ');
    $stmt->execute([
        ':code' => $code,
        ':cz' => $cz,
        ':en' => $en,
        ':valid' => $valid,
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);

    admin_auto_translate_record('ui_texty.record', $id, array_merge($_POST, [
        'cz' => $cz,
        'en' => $en,
    ]), $existing);

    return true;
}

function ui_texty_delete(int $id): bool
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE ui_texty SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':user_u' => admin_session_user(),
        ':id' => $id,
    ]);

    return $stmt->rowCount() > 0;
}

function ui_texty_list(int $limit, int $valid): array
{
    global $pdo;

    $sqlLimit = $limit === 0 ? 999999 : max(1, $limit);
    $stmt = $pdo->prepare('SELECT * FROM ui_texty WHERE valid = :valid ORDER BY code LIMIT :limit');
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqlLimit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ui_texty_catalog_from_lang_file(): array
{
    $file = ROOT_DIR . '/functions/lang.php';
    if (!is_file($file)) {
        return [];
    }

    $lang = 'cz';
    $ui_texts = [];
    include $file;

    $result = [];
    if (!is_array($ui_texts)) {
        return $result;
    }

    foreach ($ui_texts as $code => $values) {
        $code = trim((string)$code);
        if (!ui_texty_code_is_valid($code)) {
            continue;
        }

        if (is_array($values)) {
            $cz = ui_texty_clean_value((string)($values['cz'] ?? ''));
            $en = ui_texty_clean_value((string)($values['en'] ?? ''));
        } else {
            $cz = ui_texty_clean_value((string)$values);
            $en = '';
        }

        if ($cz !== '') {
            $result[$code] = ['code' => $code, 'cz' => $cz, 'en' => $en, 'source' => 'lang.php'];
        }
    }

    return $result;
}

function ui_texty_scan_php_files(): array
{
    $roots = [
        ROOT_DIR . '/index.php',
        ROOT_DIR . '/functions',
        ROOT_DIR . '/inc',
        ROOT_DIR . '/secure/functions',
        ROOT_DIR . '/secure/inc',
    ];
    $skipDirs = ['vendor', '.git', 'media', '_files', 'downloads', 'var', 'secure/sql'];
    $result = [];

    foreach ($roots as $root) {
        if (is_file($root)) {
            $files = [new SplFileInfo($root)];
        } elseif (is_dir($root)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                    static function (SplFileInfo $current) use ($skipDirs): bool {
                        $path = str_replace('\\', '/', $current->getPathname());
                        foreach ($skipDirs as $skipDir) {
                            if (str_contains($path, '/' . $skipDir . '/')) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            );
            $files = iterator_to_array($iterator, false);
        } else {
            continue;
        }

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (!is_string($content) || $content === '') {
                continue;
            }

            foreach ([
                '~ui_text\s*\(\s*\'([a-z0-9][a-z0-9_.-]{1,188}[a-z0-9])\'\s*,\s*\'((?:\\\\.|[^\'])*)\'~s',
                '~ui_text\s*\(\s*"([a-z0-9][a-z0-9_.-]{1,188}[a-z0-9])"\s*,\s*"((?:\\\\.|[^"])*)"~s',
            ] as $pattern) {
                $matched = preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
                if ($matched === false || $matched === 0) {
                    continue;
                }

                foreach ($matches as $match) {
                    $code = trim((string)($match[1] ?? ''));
                    $cz = ui_texty_clean_value(stripcslashes((string)($match[2] ?? '')));
                    if ($code !== '' && $cz !== '' && !isset($result[$code])) {
                        $result[$code] = ['code' => $code, 'cz' => $cz, 'en' => '', 'source' => 'code'];
                    }
                }
            }
        }
    }

    return $result;
}

function ui_texty_sync_from_sources(): array
{
    global $pdo;

    $catalog = ui_texty_scan_php_files();
    $catalog = ui_texty_catalog_from_lang_file() + $catalog;

    $inserted = 0;
    $skipped = 0;
    $user = admin_session_user();
    $stmt = $pdo->prepare('
        INSERT INTO ui_texty (code, cz, en, user_i, user_u)
        VALUES (:code, :cz, :en, :user_i, :user_u)
    ');

    foreach ($catalog as $row) {
        $code = (string)($row['code'] ?? '');
        if ($code === '' || ui_texty_code_exists($code)) {
            $skipped++;
            continue;
        }

        $stmt->execute([
            ':code' => $code,
            ':cz' => (string)($row['cz'] ?? ''),
            ':en' => (string)($row['en'] ?? ''),
            ':user_i' => $user,
            ':user_u' => $user,
        ]);
        $inserted++;
    }

    return [
        'inserted' => $inserted,
        'skipped' => $skipped,
        'found' => count($catalog),
    ];
}
