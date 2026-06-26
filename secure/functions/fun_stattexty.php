<?php
// Převedeno na PDO s prepared statements pro bezpečnost

function stattexty_code_is_valid(string $code): bool
{
    return preg_match('~^[a-z0-9][a-z0-9_.-]{1,118}[a-z0-9]$~', $code) === 1;
}

function stattexty_code_error(): void
{
    echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Kód textu musí mít 3-120 znaků a může obsahovat malá písmena, čísla, tečku, pomlčku a podtržítko.</span></a>';
}

function stattexty_code_exists(string $code, int $ignoreId = 0): bool
{
    global $pdo;

    $sql = 'SELECT 1 FROM stat_texty WHERE code = :code';
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

function stattexty_code_duplicate_error(): void
{
    echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický text s tímto kódem už existuje.</span></a>';
}

function stattexty_add($code, $nazev_cz, $text_cz, $galerie_id, $col): void
{
    global $pdo;
    $qn_user = admin_session_user();
    $code = trim((string)$code);
    if (!stattexty_code_is_valid($code)) {
        stattexty_code_error();
        return;
    }
    if (stattexty_code_exists($code)) {
        stattexty_code_duplicate_error();
        return;
    }

    $sql = 'INSERT INTO stat_texty (
        code, nazev_cz, nazev_en, text_cz, text_en, galerie_id, col, user_i, user_u
    ) VALUES (
        :code, :nazev_cz, :nazev_en, :text_cz, :text_en, :galerie_id, :col, :user_i, :user_u
    )';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $code,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => '',
            ':text_cz' => $text_cz,
            ':text_en' => '',
            ':galerie_id' => $galerie_id,
            ':col' => $col,
            ':user_i' => $qn_user,
            ':user_u' => $qn_user
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický text nebyl vložen</span></a>';
        echo $e->getMessage();
    }
}

function stattexty_vypis($limit, $valid): void
{
    global $pdo;
    $sqllimit = ($limit == 0) ? 999999 : $limit;

    $sql = 'SELECT * FROM stat_texty WHERE valid = :valid ORDER BY code LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $galerie_id = ($dev["galerie_id"] == 0) ? 'NE' : $dev["galerie_id"];
        echo '<tr>
            <td>'.$dev["id"].'</td>
            <td>'.htmlspecialchars($dev["nazev_cz"], ENT_QUOTES, 'UTF-8').'</td>
            <td>'.htmlspecialchars((string)($dev["code"] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
            <td>'.$dev["col"].'</td>
            <td>'.$galerie_id.'</td>
            <td class="text-center">
                <a class="btn btn-primary btn-circle btn-sm" href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;lang=en&amp;show=2">
                <i class="bi bi-pencil"></i></a></td>
            <td class="text-center">
                <a class="btn btn-success btn-circle btn-sm" href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
                <i class="bi bi-pencil"></i></td>
            <td class="text-center">
                <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=02&amp;sec_page=02&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
                <i class="bi bi-trash"></i></td>
        </tr>';
    }
}

function stattexty_delete($id): void
{
    global $pdo;
    $qn_user = admin_session_user();

    $sql = 'UPDATE stat_texty SET valid = 0, user_u = :user_u WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_u' => $qn_user, ':id' => $id]);
        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Statický text byl smazán</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický text nebyl smazán</span></a>';
        echo $e->getMessage();
    }
}

function stattexty_edit($id, $code, $nazev, $text, $galerie_id, $col, $lang, $valid): void
{
    global $pdo;
    $qn_user = admin_session_user();
    $code = trim((string)$code);
    if (!stattexty_code_is_valid($code)) {
        stattexty_code_error();
        return;
    }
    if (stattexty_code_exists($code, (int)$id)) {
        stattexty_code_duplicate_error();
        return;
    }

    if ($lang == "cz") {
        $sql = 'UPDATE stat_texty SET code = :code, nazev_cz = :nazev, text_cz = :text, galerie_id = :galerie_id, col = :col, valid = :valid, user_u = :user_u WHERE id = :id';
    } elseif ($lang == "en") {
        $sql = 'UPDATE stat_texty SET code = :code, nazev_en = :nazev, text_en = :text, galerie_id = :galerie_id, col = :col, valid = :valid, user_u = :user_u WHERE id = :id';
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $code,
            ':nazev' => $nazev,
            ':text' => $text,
            ':galerie_id' => $galerie_id,
            ':col' => $col,
            ':valid' => $valid,
            ':user_u' => $qn_user,
            ':id' => $id
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický text nebyl uložen</span></a>';
        echo $e->getMessage();
    }
}

function statvyrazy_code_is_valid(string $code): bool
{
    return preg_match('~^[a-z0-9][a-z0-9_.-]{1,118}[a-z0-9]$~', $code) === 1;
}

function statvyrazy_code_error(): void
{
    echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Kód výrazu musí mít 3-120 znaků a může obsahovat malá písmena, čísla, tečku, pomlčku a podtržítko.</span></a>';
}

function statvyrazy_code_exists(string $code, int $ignoreId = 0): bool
{
    global $pdo;

    $sql = 'SELECT 1 FROM stat_vyrazy WHERE code = :code';
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

function statvyrazy_code_duplicate_error(): void
{
    echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický výraz s tímto kódem už existuje.</span></a>';
}

function statvyrazy_preview(string $html, int $limit = 120): string
{
    $text = trim(preg_replace('~\s+~u', ' ', strip_tags($html)) ?? '');
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $limit
            ? mb_substr($text, 0, $limit, 'UTF-8') . '...'
            : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

function statvyrazy_add($code, $cz, $en): void
{
    global $pdo;
    $qn_user = admin_session_user();
    $code = trim((string)$code);

    if (!statvyrazy_code_is_valid($code)) {
        statvyrazy_code_error();
        return;
    }
    if (statvyrazy_code_exists($code)) {
        statvyrazy_code_duplicate_error();
        return;
    }

    $sql = 'INSERT INTO stat_vyrazy (code, cz, en, user_i, user_u) VALUES
        (:code, :cz, :en, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $code,
            ':cz' => $cz,
            ':en' => $en,
            ':user_i' => $qn_user,
            ':user_u' => $qn_user
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
               <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický výraz nebyl vložen</span></a>';
        echo $e->getMessage();
    }
}

function statvyrazy_vypis($limit, $valid): void
{
    global $pdo;
    $sqllimit = ($limit == 0) ? 999999 : $limit;

    $sql = 'SELECT * FROM stat_vyrazy WHERE valid = :valid ORDER BY code LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>
            <td>'.$dev["id"].'</td>
            <td>'.htmlspecialchars((string)($dev["code"] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
            <td>'.htmlspecialchars(statvyrazy_preview((string)($dev["cz"] ?? '')), ENT_QUOTES, 'UTF-8').'</td>
            <td>'.htmlspecialchars(statvyrazy_preview((string)($dev["en"] ?? '')), ENT_QUOTES, 'UTF-8').'</td>
            <td class="text-center">
                <a class="btn btn-success btn-circle btn-sm" href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
                <i class="bi bi-pencil"></i></a></td>
            <td class="text-center">
                <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=02&amp;sec_page=03&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
                <i class="bi bi-trash"></i></td>
        </tr>';
    }
}

function statvyrazy_delete($id): void
{
    global $pdo;
    $qn_user = admin_session_user();

    $sql = 'UPDATE stat_vyrazy SET valid = 0, user_u = :user_u WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_u' => $qn_user, ':id' => $id]);
        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Statický výraz byl smazán</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický výraz nebyl smazán</span></a>';
        echo $e->getMessage();
    }
}

function statvyrazy_edit($id, $code, $cz, $en, $valid): void
{
    global $pdo;
    $qn_user = admin_session_user();
    $code = trim((string)$code);

    if (!statvyrazy_code_is_valid($code)) {
        statvyrazy_code_error();
        return;
    }
    if (statvyrazy_code_exists($code, (int)$id)) {
        statvyrazy_code_duplicate_error();
        return;
    }

    $sql = 'UPDATE stat_vyrazy SET code = :code, cz = :cz, en = :en, valid = :valid, user_u = :user_u WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':code' => $code,
            ':cz' => $cz,
            ':en' => $en,
            ':valid' => $valid,
            ':user_u' => $qn_user,
            ':id' => $id
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Statický výraz nebyl uložen</span></a>';
        echo $e->getMessage();
    }
}

function stattexty_count($valid)
{
    global $pdo;
    $sql = 'SELECT COUNT(*) FROM stat_texty WHERE valid = :valid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':valid' => $valid]);
    return $stmt->fetchColumn();
}

function statvyrazy_count($valid)
{
    global $pdo;
    $sql = 'SELECT COUNT(*) FROM stat_vyrazy WHERE valid = :valid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':valid' => $valid]);
    return $stmt->fetchColumn();
}
