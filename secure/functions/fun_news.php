<?php
// PDO verze

function news_ico_dir(bool $small = false): string
{
    return ROOT_DIR . '/media/news_ico' . ($small ? '/small' : '');
}

function news_ico_path(string $filename, bool $small = false): string
{
    return news_ico_dir($small) . '/' . basename($filename);
}

function news_ico_ensure_dirs(): void
{
    foreach ([news_ico_dir(), news_ico_dir(true)] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Nelze vytvorit adresar pro ikony novinek: ' . $dir);
        }
    }
}

//funkce pro pridani typu novinky
function news_typ_add ($nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en, $color): void
{
    global $pdo;

    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    $sql = 'INSERT INTO news_typ (poradi, nazev_cz, nazev_en, popis_cz, popis_en, color, user_i, user_u)
            VALUES (:poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :color, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':popis_cz' => $popis_cz,
            ':popis_en' => $popis_en,
            ':color'    => $color,
            ':user_i'   => $qn_user,
            ':user_u'   => $qn_user,
        ]);

        unset ($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Typ novinky nebyl vložen</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro vypis typu novinky
function news_typ_vypis ($limit, $valid): void
{
    global $pdo;

    $sqllimit = ($limit == 0) ? 999999 : (int)$limit;
    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT * FROM news_typ WHERE valid = :valid ORDER BY poradi LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', (int)$valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        echo '<tr>
                <td>'.$dev["id"].'</td>
                <td>'.stripslashes($dev["nazev_cz"]).'</td>
                <td>'.$dev["poradi"].'</td>
                <td>'.$dev["color"].'</td>
                <td class="text-center">
                    <a class="btn btn-success btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=03&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
                    <i class="bi bi-pencil"></i></a></td>
                <td class="text-center">
                    <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=03&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
                    <i class="bi bi-trash"></i></a></td>
            </tr>';
    }
}

//funkce pro editaci typu novinky
function news_typ_edit ($id, $nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en, $color, $valid): void
{
    global $pdo;

    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE news_typ SET
                poradi = :poradi,
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                popis_cz = :popis_cz,
                popis_en = :popis_en,
                color = :color,
                valid = :valid,
                user_u = :user_u
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':popis_cz' => $popis_cz,
            ':popis_en' => $popis_en,
            ':color'    => $color,
            ':valid'    => (int)$valid,
            ':user_u'   => $qn_user,
            ':id'       => (int)$id
        ]);

        unset ($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Typ novinek nebyl uložen</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro vymazani typu novinky
function news_typ_delete ($id): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'UPDATE news_typ SET valid = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Typ novinky byl smazán</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Typ novinky nebyl smazán</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro vypis typu novinek do formulare
function news_typ_option_form ($select): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'SELECT id, nazev_cz FROM news_typ WHERE valid = 1 ORDER BY poradi';
    $stmt = $pdo->query($sql);

    while ($dev = $stmt->fetch(PDO::FETCH_NUM))
    {
        $id = $dev[0];
        $nazev_cz = stripslashes($dev[1]);
        $selected = ((string)$select === (string)$id) ? ' selected="selected"' : '';
        echo '<option value="'.$id.'"'.$selected.'>'.$id.'&nbsp;-&nbsp;'.$nazev_cz.'</option>' . "\n";
    }
}

//funkce pro pridani nove novinky
function news_add ($datum, $news_typ, $nazev_cz, $text_cz, $galerie_id, $visible, $soubor): void
{
    global $pdo;

    $url_cz = text_str($nazev_cz).'-'.$datum;
    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    $sql = 'INSERT INTO news (datum, url_cz, news_typ, nazev_cz, text_cz, galerie_id, visible, news_ico, user_i, user_u)
            VALUES (:datum, :url_cz, :news_typ, :nazev_cz, :text_cz, :galerie_id, :visible, :news_ico, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':datum'      => $datum,
            ':url_cz'     => $url_cz,
            ':news_typ'   => (int)$news_typ,
            ':nazev_cz'   => $nazev_cz,
            ':text_cz'    => $text_cz,
            ':galerie_id' => (int)$galerie_id,
            ':visible'    => (int)$visible,
            ':news_ico'   => $soubor,
            ':user_i'     => $qn_user,
            ':user_u'     => $qn_user,
        ]);
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Novinka nebyla vložena</span></a>';
        echo $e->getMessage();
        return;
    }

    if($soubor <> ""):
        $file_orig = news_ico_path((string)$soubor);
        $file_small = news_ico_path((string)$soubor, true);

        list($width, $height) = create_thumbnail($file_orig, sp_hodnota('pic_news_orig_width'), sp_hodnota('pic_news_orig_height'));
        if ($width && $height):
            image_resize($file_orig, $width, $height);
        endif;

        list($width, $height) = create_thumbnail($file_small, sp_hodnota('pic_news_small_width'), sp_hodnota('pic_news_small_height'));
        if ($width && $height):
            image_resize($file_small, $width, $height);
        endif;
    else:
        echo 'Soubor nebyl připojen, bude použit defaultní.<br />';
    endif;

    unset ($_POST['add']);
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
    echo "<script type='text/javascript'>document.location.href='$url';</script>";
    echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
}

//funkce pro zjisteni max id v novinkach
function news_maxid (): int
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'SELECT MAX(id) FROM news WHERE valid = 1';
    return (int)$pdo->query($sql)->fetchColumn();
}

//funkce pro pridani fotografie k novince
function news_photo_add (): array|string|null
{
    if (!isset($_FILES['userfile']) || ($_FILES['userfile']['error'] ?? UPLOAD_ERR_NO_FILE) == UPLOAD_ERR_NO_FILE) {
        return "";
    }

    news_ico_ensure_dirs();
    $soubor_str = text_str($_FILES['userfile']['name']);
    $fileOriginal = news_ico_path($soubor_str);
    $fileSmall = news_ico_path($soubor_str, true);

    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $fileOriginal)) {
        copy($fileOriginal, $fileSmall);
    } else {
        echo "Nastala chyba, zkuste upload znova";
        return "";
    }

    return $soubor_str;
}

//funkce pro editaci novinky
function news_edit ($id, $datum, $news_typ, $nazev, $text, $galerie_id, $visible, $lang, $url, $valid, $soubor): void
{
    global $pdo;

    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    if($lang == "cz"):
        $sql = 'UPDATE news SET
                    url_cz = :url,
                    datum = :datum,
                    news_typ = :news_typ,
                    nazev_cz = :nazev,
                    text_cz = :text,
                    galerie_id = :galerie_id,
                    visible = :visible,
                    valid = :valid,
                    ' . ($soubor !== '' ? 'news_ico = :news_ico,' : '') . '
                    user_u = :user_u
                WHERE id = :id';
    elseif($lang == "en"):
        // opravená chyba z původního kódu (chyběla čárka mezi visible a valid)
        $sql = 'UPDATE news SET
                    url_en = :url,
                    datum = :datum,
                    news_typ = :news_typ,
                    nazev_en = :nazev,
                    text_en = :text,
                    galerie_id = :galerie_id,
                    visible = :visible,
                    valid = :valid,
                    ' . ($soubor !== '' ? 'news_ico = :news_ico,' : '') . '
                    user_u = :user_u
                WHERE id = :id';
    else:
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Typ novinek nebyl uložen</span></a>';
        echo 'Neznámý jazyk.';
        return;
    endif;

    try {
        $params = [
            ':url'        => $url,
            ':datum'      => $datum,
            ':news_typ'   => (int)$news_typ,
            ':nazev'      => $nazev,
            ':text'       => $text,
            ':galerie_id' => (int)$galerie_id,
            ':visible'    => (int)$visible,
            ':valid'      => (int)$valid,
            ':user_u'     => $qn_user,
            ':id'         => (int)$id
        ];
        if ($soubor !== '') {
            $params[':news_ico'] = $soubor;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Typ novinek nebyl uložen</span></a>';
        echo $e->getMessage();
        return;
    }

    if($soubor <> ""):
        $file_orig = news_ico_path((string)$soubor);
        $file_small = news_ico_path((string)$soubor, true);

        list($width, $height) = create_thumbnail($file_orig, sp_hodnota('pic_news_orig_width'), sp_hodnota('pic_news_orig_height'));
        if ($width && $height):
            image_resize($file_orig, $width, $height);
        endif;

        list($width, $height) = create_thumbnail($file_small, sp_hodnota('pic_news_small_width'), sp_hodnota('pic_news_small_height'));
        if ($width && $height):
            image_resize($file_small, $width, $height);
        endif;
    else:
        echo 'Soubor nebyl připojen, bude použit defaultní.<br />';
    endif;

    unset ($_POST['add']);
    $urlr = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
    echo "<script type='text/javascript'>document.location.href='$urlr';</script>";
    echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $urlr . '">';
}

//funkce pro vypis novinek se strankovanim
function news_vypis ($limit, $valid): void
{
    global $pdo;

    $sqllimit = ($limit == 0) ? 999999 : (int)$limit;
    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT n.id, n.url_cz, n.nazev_cz, n.datum, n.news_ico, n.news_typ, n.galerie_id, n.visible,
                   nt.nazev_cz as typ, n.info_send
            FROM news n
            JOIN news_typ nt ON nt.id = n.news_typ
            WHERE n.valid = :valid
            ORDER BY n.datum DESC, n.id DESC
            LIMIT :limit';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', (int)$valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        if($dev["news_ico"] == ""):
            $news_ico = 'NE';
            $news_ico_odkaz = '';
        else:
            $news_ico = 'ANO';
            $news_ico_odkaz = '<a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;icon='.$dev['id'].'&amp;limit='.$limit.'">
                <i class="fas fa-icons"></i></a>';
        endif;

        $galerie_id = ((int)$dev["galerie_id"] === 0) ? 'NE' : $dev["galerie_id"];

        if($dev["visible"] == 0):       $visible = 'NE';
        elseif($dev["visible"] == 1):   $visible = "CZ/EN";
        elseif($dev["visible"] == 2):   $visible = "CZ";
        elseif($dev["visible"] == 3):   $visible = "EN";
        endif;

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/cz/index/news/".$dev["url_cz"];
        $info_send = ($dev["info_send"]== '0000-00-00' || $dev["info_send"] === null) ? "NE" : format_date_www($dev["info_send"]);

        echo '
        <tr>
            <td>'.$dev["id"].'</td>
            <td>'.stripslashes($dev["typ"]).'</td>
            <td>'.stripslashes($dev["nazev_cz"]).'</td>
            <td>'.format_date_www($dev["datum"]).'</td>
            <td>'.$news_ico.'</td>
            <td>'.$galerie_id.'</td>
            <td>'.$visible.'</td>
            <td>'.$info_send.'</td>
            <td class="text-center">
                <a class="btn btn-primary btn-circle btn-sm" href="'.$url.'" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i></i></a></td>
            <td class="text-center">
                <a class="btn btn-primary btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;lang=en&amp;show=2">
                <i class="bi bi-pencil"></i></a></td>
            <td class="text-center">
                <a class="btn btn-success btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
                <i class="bi bi-pencil"></i></a></td>
            <td class="text-center">
                <a class="btn btn-warning btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=06&amp;send='.$dev['id'].'&amp;limit='.$limit.'">
                <i class="bi bi-share"></i></a></td>
            <td class="text-center">
                '.$news_ico_odkaz.'
                </td>
            <td class="text-center">
                <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=02&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
                <i class="bi bi-trash"></i></a></td>
        </tr>';
    }
}

//funkce pro smazani novinky
function news_delete ($id): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'UPDATE news SET valid = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Novinka byla smazána</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Novinka nebyla smazána</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro smazani fotografie
function news_ico_delete ($ico_del): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $stmt = $pdo->prepare('SELECT news_ico FROM news WHERE id = :id');
    $stmt->execute([':id' => (int)$ico_del]);
    $soubor = (string)$stmt->fetchColumn();
    $soubor = stripslashes($soubor);

    if ($soubor !== '') {
        @unlink(news_ico_path($soubor));
        @unlink(news_ico_path($soubor, true));
    }

    try {
        $stmt2 = $pdo->prepare("UPDATE news SET news_ico = '' WHERE id = :id");
        $stmt2->execute([':id' => (int)$ico_del]);
        echo '<span class="warning">Novinka byla upravena</span>';
    } catch (PDOException $e) {
        echo '<span class="warning">Novinka nebyla upraveno</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro pridani uzivatele novinek
function news_users_add ($name, $email): void
{
    global $pdo;

    $datum_od = format_date_db(get_date());
    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    $sql = 'INSERT INTO news_users (name, email, datum_od, registered, user_i, user_u)
            VALUES (:name, :email, :datum_od, 1, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':datum_od' => $datum_od,
            ':user_i'   => $qn_user,
            ':user_u'   => $qn_user
        ]);

        unset ($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Uživatel novinky nebyl vložen</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro vymazani uzivatele prihlaseneho k odberu novinek
function news_users_delete ($id): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'UPDATE news_users SET registered = 0, valid = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Uživatel byl smazán</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Uživatel nebyl smazán</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro ukonceni odberu uzivatele prihlaseneho k odberu novinek
function news_users_end ($id): void
{
    global $pdo;

    $datum_do = format_date_db(get_date());
    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE news_users SET datum_do = :datum_do, registered = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':datum_do' => $datum_do,
            ':id'       => (int)$id
        ]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Uživatel byl ukončen</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Uživatel nebyl ukončen</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro ukonceni odberu uzivatele prihlaseneho k odberu novinek
function news_users_renew ($id): void
{
    global $pdo;

    $datum_od = format_date_db(get_date());
    $pdo->exec("SET NAMES utf8");

    $sql = "UPDATE news_users
        SET datum_od = :datum_od, registered = 1, datum_do = '0000-00-00', valid = 1
        WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':datum_od' => $datum_od,
            ':id'       => (int)$id
        ]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-check"></i></span><span class="text">Uživatel byl obnoven</span></a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Uživatel nebyl obnoven</span></a>';
        echo $e->getMessage();
    }
}

//funkce pro vypis uzivatelu prihlasenych k odberu
function news_users_vypis ($limit, $valid): void
{
    global $pdo;

    $sqllimit = ($limit == 0) ? 999999 : (int)$limit;
    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT * FROM news_users WHERE valid = :valid ORDER BY datum_od DESC LIMIT :limit';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', (int)$valid, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        $registered = ((int)$dev["registered"] === 1) ? "ANO" : "NE";

        echo '<tr>' . "\n";
        echo '<td>'.$dev["id"].'</td>' . "\n";
        echo '<td>'.stripslashes($dev["name"]).'</td>' . "\n";
        echo '<td>'.$dev["email"].'</td>' . "\n";
        echo '<td>'.format_date_www($dev["datum_od"]).'</td>' . "\n";
        echo '<td>'.format_date_www($dev["datum_do"]).'</td>' . "\n";
        echo '<td>'.$registered.'</td>' . "\n";
        echo '<td class="text-center">
            <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;end='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
            <i class="bi bi-pencil"></i></td>' . "\n";
        echo '<td class="text-center">
            <a class="btn btn-success btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;renew='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
            <i class="bi bi-pencil"></i></td>' . "\n";
        echo '<td class="text-center">
            <a class="btn btn-danger btn-circle btn-sm" href="index.php?section=01&amp;page=01&amp;sec_page=05&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
            <i class="bi bi-trash"></i></td>';
        echo '</tr>' . "\n";
    }
}

//funkce pro zkopirovani CZ do EN
function news_copytoen ($id): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $stmt = $pdo->prepare('SELECT nazev_cz, perex_cz, text_cz FROM news WHERE id = :id');
    $stmt->execute([':id' => (int)$id]);
    $dev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dev) {
        echo '<span class="warning">Novinka nebyla nalezena</span><br />';
        return;
    }

    $sql = 'UPDATE news SET nazev_en = :nazev_en, perex_en = :perex_en, text_en = :text_en WHERE id = :id';

    try {
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute([
            ':nazev_en' => $dev["nazev_cz"],
            ':perex_en' => $dev["perex_cz"],
            ':text_en'  => $dev["text_cz"],
            ':id'       => (int)$id
        ]);

        echo '<span class="warning">Novinka byla úspěšně zkopírována z CZ do EN</span><br />';
        unset ($_POST['add']);
    } catch (PDOException $e) {
        echo '<span class="warning">Novinka nebyla zkopírována z CZ do EN</span><br />';
        echo $e->getMessage();
    }
}

function news_typ_count ($valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news_typ WHERE valid = :valid');
    $stmt->execute([':valid' => (int)$valid]);
    return (int)$stmt->fetchColumn();
}

function news_count ($valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE valid = :valid');
    $stmt->execute([':valid' => (int)$valid]);
    return (int)$stmt->fetchColumn();
}

function news_users_count ($valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news_users WHERE valid = :valid');
    $stmt->execute([':valid' => (int)$valid]);
    return (int)$stmt->fetchColumn();
}
