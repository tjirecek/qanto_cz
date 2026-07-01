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
function news_typ_delete($id): void
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'UPDATE news_typ SET valid = 0, user_u = :user_u WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => (int)$id,
        ':user_u' => admin_session_user(),
    ]);
}

function news_typ_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM news_typ WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function news_typ_all(int $valid = 1, int $limit = 0): array
{
    global $pdo;

    $sql = 'SELECT * FROM news_typ WHERE valid = :valid ORDER BY poradi ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function news_typ_save(array $data, ?int $id = null): int
{
    global $pdo;

    $user = admin_session_user();
    $payload = [
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => trim((string)($data['nazev_cz'] ?? '')),
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':popis_cz' => trim((string)($data['popis_cz'] ?? '')),
        ':popis_en' => trim((string)($data['popis_en'] ?? '')),
        ':color' => trim((string)($data['color'] ?? '')),
        ':user_u' => $user,
    ];

    if ($payload[':nazev_cz'] === '') {
        throw new InvalidArgumentException('Název CZ je povinný.');
    }

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO news_typ
            (poradi, nazev_cz, nazev_en, popis_cz, popis_en, color, user_i, user_u)
            VALUES (:poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en, :color, :user_i, :user_u)');
        $payload[':user_i'] = $user;
        $stmt->execute($payload);

        return (int)$pdo->lastInsertId();
    }

    $payload[':id'] = $id;
    $payload[':valid'] = isset($data['valid']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE news_typ
        SET poradi = :poradi,
            nazev_cz = :nazev_cz,
            nazev_en = :nazev_en,
            popis_cz = :popis_cz,
            popis_en = :popis_en,
            color = :color,
            valid = :valid,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute($payload);

    return $id;
}

function news_typ_next_order(): int
{
    global $pdo;

    return (int)$pdo->query('SELECT COALESCE(MAX(poradi), 0) + 1 FROM news_typ')->fetchColumn();
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

function news_visible_from_post(array $data): int
{
    $cz = isset($data['visible_cz']);
    $en = isset($data['visible_en']);

    if ($cz && $en) {
        return 1;
    }

    if ($cz) {
        return 2;
    }

    if ($en) {
        return 3;
    }

    return 0;
}

function news_visible_checked(int $visible): array
{
    return [
        'cz' => in_array($visible, [1, 2], true),
        'en' => in_array($visible, [1, 3], true),
    ];
}

function news_url_generate(string $title, string $date): string
{
    $date = preg_match('~^\d{4}-\d{2}-\d{2}$~', $date) ? $date : date('Y-m-d');
    $slug = trim((string)text_str($title), '-');
    if ($slug === '') {
        $slug = 'novinka';
    }

    return $date . '-' . $slug;
}

function news_url_unique(string $url, string $lang = 'cz', ?int $ignoreId = null): string
{
    global $pdo;

    $column = $lang === 'en' ? 'url_en' : 'url_cz';
    $base = trim((string)text_str($url), '-');
    if ($base === '') {
        $base = 'novinka';
    }

    $candidate = $base;
    $suffix = 2;
    do {
        $sql = "SELECT id FROM news WHERE {$column} = :url";
        $params = [':url' => $candidate];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = (bool)$stmt->fetchColumn();

        if ($exists) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }
    } while ($exists);

    return $candidate;
}

//funkce pro pridani nove novinky
function news_add (
    string $datum,
    int $news_typ,
    string $nazev_cz,
    string $perex_cz,
    string $text_cz,
    int $galerie_id,
    int $visible,
    string $soubor,
    string $url_cz = '',
    string $seo_title_cz = '',
    string $seo_description_cz = '',
    array $tagIds = [],
    string $nazev_en = '',
    string $perex_en = '',
    string $text_en = '',
    string $url_en = '',
    string $seo_title_en = '',
    string $seo_description_en = ''
): int
{
    global $pdo;

    $url_cz = news_url_unique($url_cz !== '' ? $url_cz : news_url_generate($nazev_cz, $datum), 'cz');
    $url_en = trim($url_en) !== ''
        ? news_url_unique($url_en, 'en')
        : (trim($nazev_en) !== '' ? news_url_unique(news_url_generate($nazev_en, $datum), 'en') : '');
    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");

    $sql = 'INSERT INTO news
                (datum, url_cz, url_en, news_typ, nazev_cz, nazev_en, perex_cz, perex_en, text_cz, text_en,
                 seo_title_cz, seo_title_en, seo_description_cz, seo_description_en, galerie_id, visible, news_ico, user_i, user_u)
            VALUES
                (:datum, :url_cz, :url_en, :news_typ, :nazev_cz, :nazev_en, :perex_cz, :perex_en, :text_cz, :text_en,
                 :seo_title_cz, :seo_title_en, :seo_description_cz, :seo_description_en, :galerie_id, :visible, :news_ico, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':datum'      => $datum,
            ':url_cz'     => $url_cz,
            ':url_en'     => $url_en,
            ':news_typ'   => $news_typ,
            ':nazev_cz'   => $nazev_cz,
            ':nazev_en'   => $nazev_en,
            ':perex_cz'   => $perex_cz,
            ':perex_en'   => $perex_en,
            ':text_cz'    => $text_cz,
            ':text_en'    => $text_en,
            ':seo_title_cz' => $seo_title_cz,
            ':seo_title_en' => $seo_title_en,
            ':seo_description_cz' => $seo_description_cz,
            ':seo_description_en' => $seo_description_en,
            ':galerie_id' => (int)$galerie_id,
            ':visible'    => (int)$visible,
            ':news_ico'   => $soubor,
            ':user_i'     => $qn_user,
            ':user_u'     => $qn_user,
        ]);
        $newsId = (int)$pdo->lastInsertId();
        news_tags_save_for_news($newsId, $tagIds);
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Novinka nebyla vložena</span></a>';
        echo $e->getMessage();
        return 0;
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

    return $newsId;
}

function news_edit_multilang(
    int $id,
    string $datum,
    int $news_typ,
    array $data,
    int $galerie_id,
    int $visible,
    int $valid,
    string $soubor,
    array $tagIds = []
): void {
    global $pdo;

    $qn_user = admin_session_user();
    $nazevCz = trim((string)($data['nazev_cz'] ?? ''));
    $nazevEn = trim((string)($data['nazev_en'] ?? ''));
    $urlCz = trim((string)($data['url_cz'] ?? ''));
    $urlEn = trim((string)($data['url_en'] ?? ''));

    $urlCz = news_url_unique($urlCz !== '' ? $urlCz : news_url_generate($nazevCz, $datum), 'cz', $id);
    $urlEn = $urlEn !== ''
        ? news_url_unique($urlEn, 'en', $id)
        : ($nazevEn !== '' ? news_url_unique(news_url_generate($nazevEn, $datum), 'en', $id) : '');

    $sql = 'UPDATE news SET
                url_cz = :url_cz,
                url_en = :url_en,
                datum = :datum,
                news_typ = :news_typ,
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                perex_cz = :perex_cz,
                perex_en = :perex_en,
                text_cz = :text_cz,
                text_en = :text_en,
                seo_title_cz = :seo_title_cz,
                seo_title_en = :seo_title_en,
                seo_description_cz = :seo_description_cz,
                seo_description_en = :seo_description_en,
                galerie_id = :galerie_id,
                visible = :visible,
                valid = :valid,
                ' . ($soubor !== '' ? 'news_ico = :news_ico,' : '') . '
                user_u = :user_u
            WHERE id = :id';

    try {
        $params = [
            ':url_cz' => $urlCz,
            ':url_en' => $urlEn,
            ':datum' => $datum,
            ':news_typ' => $news_typ,
            ':nazev_cz' => $nazevCz,
            ':nazev_en' => $nazevEn,
            ':perex_cz' => (string)($data['perex_cz'] ?? ''),
            ':perex_en' => (string)($data['perex_en'] ?? ''),
            ':text_cz' => (string)($data['text_cz'] ?? ''),
            ':text_en' => (string)($data['text_en'] ?? ''),
            ':seo_title_cz' => trim((string)($data['seo_title_cz'] ?? '')),
            ':seo_title_en' => trim((string)($data['seo_title_en'] ?? '')),
            ':seo_description_cz' => trim((string)($data['seo_description_cz'] ?? '')),
            ':seo_description_en' => trim((string)($data['seo_description_en'] ?? '')),
            ':galerie_id' => $galerie_id,
            ':visible' => $visible,
            ':valid' => $valid,
            ':user_u' => $qn_user,
            ':id' => $id,
        ];
        if ($soubor !== '') {
            $params[':news_ico'] = $soubor;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        news_tags_save_for_news($id, $tagIds);
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span><span class="text">Novinka nebyla uložena</span></a>';
        echo $e->getMessage();
    }
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
function news_edit (
    int $id,
    string $datum,
    int $news_typ,
    string $nazev,
    string $perex,
    string $text,
    int $galerie_id,
    int $visible,
    string $lang,
    string $url,
    int $valid,
    string $soubor,
    string $seo_title = '',
    string $seo_description = '',
    array $tagIds = []
): void
{
    global $pdo;

    $qn_user = admin_session_user();
    $pdo->exec("SET NAMES utf8");
    $url = news_url_unique($url !== '' ? $url : news_url_generate($nazev, $datum), $lang, $id);

    if($lang == "cz"):
        $sql = 'UPDATE news SET
                    url_cz = :url,
                    datum = :datum,
                    news_typ = :news_typ,
                    nazev_cz = :nazev,
                    perex_cz = :perex,
                    text_cz = :text,
                    seo_title_cz = :seo_title,
                    seo_description_cz = :seo_description,
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
                    perex_en = :perex,
                    text_en = :text,
                    seo_title_en = :seo_title,
                    seo_description_en = :seo_description,
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
            ':perex'      => $perex,
            ':text'       => $text,
            ':seo_title'  => $seo_title,
            ':seo_description' => $seo_description,
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
        news_tags_save_for_news($id, $tagIds);
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
                   n.info_send, n.valid, n.ts_u, n.user_u, nt.nazev_cz as typ,
                   GROUP_CONCAT(CONCAT(t.nazev_cz, "::", t.color) ORDER BY t.poradi ASC, t.nazev_cz ASC SEPARATOR "||") AS tag_names
            FROM news n
            LEFT JOIN news_typ nt ON nt.id = n.news_typ
            LEFT JOIN news_tag_rel tr ON tr.news_id = n.id
            LEFT JOIN news_tag t ON t.id = tr.tag_id AND t.valid = 1
            WHERE n.valid = :valid
            GROUP BY n.id, n.url_cz, n.nazev_cz, n.datum, n.news_ico, n.news_typ, n.galerie_id, n.visible,
                     n.info_send, n.valid, n.ts_u, n.user_u, nt.nazev_cz
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

        $galerie_id = ((int)$dev["galerie_id"] === 0) ? 'NE' : (string)$dev["galerie_id"];
        $visible = match ((int)$dev["visible"]) {
            1 => 'CZ/EN',
            2 => 'CZ',
            3 => 'EN',
            default => 'NE',
        };

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/cz/index/news/".$dev["url_cz"];
        $info_send = ($dev["info_send"]== '0000-00-00' || $dev["info_send"] === null) ? "NE" : format_date_www($dev["info_send"]);
        $tagNamesRaw = trim((string)($dev['tag_names'] ?? ''));
        $tagItems = $tagNamesRaw === '' ? [] : explode('||', $tagNamesRaw);
        $tagsHtml = $tagItems === []
            ? '<span class="text-muted small">bez štítku</span>'
            : implode(' ', array_map(static function ($item): string {
                [$tag, $class] = array_pad(explode('::', (string)$item, 2), 2, '');
                return '<span class="badge ' . htmlspecialchars(news_tag_badge_class($class), ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8')
                    . '</span>';
            }, $tagItems));

        echo '
        <tr>
            <td>'.$dev["id"].'</td>
            <td>'.htmlspecialchars((string)($dev["typ"] ?? ''), ENT_QUOTES, 'UTF-8').'</td>
            <td>'.htmlspecialchars((string)$dev["nazev_cz"], ENT_QUOTES, 'UTF-8').'</td>
            <td>'.$tagsHtml.'</td>
            <td>'.format_date_www($dev["datum"]).'</td>
            <td>'.$news_ico.'</td>
            <td>'.$galerie_id.'</td>
            <td><span class="badge text-bg-primary">'.$visible.'</span></td>
            <td>'.$info_send.'</td>
            <td class="text-center">
                '.(((int)$dev['valid'] === 1) ? '<span class="badge text-bg-success">ANO</span>' : '<span class="badge text-bg-secondary">NE</span>').'
            </td>
            <td>'.format_datetime_www((string)$dev["ts_u"]).'<br><small class="text-muted">'.htmlspecialchars((string)$dev["user_u"], ENT_QUOTES, 'UTF-8').'</small></td>
            <td class="text-center">
                <a class="btn btn-primary btn-circle btn-sm" href="'.$url.'" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i></i></a></td>
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

function news_tag_get(int $id): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM news_tag WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function news_tags_all(int $valid = 1, int $limit = 0): array
{
    global $pdo;

    $sql = 'SELECT * FROM news_tag WHERE valid = :valid ORDER BY poradi ASC, nazev_cz ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT :limit';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', $valid, PDO::PARAM_INT);
    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function news_tag_count(?int $valid = null): int
{
    global $pdo;

    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM news_tag')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news_tag WHERE valid = :valid');
    $stmt->execute([':valid' => $valid]);

    return (int)$stmt->fetchColumn();
}

function news_tag_next_order(): int
{
    global $pdo;

    return (int)$pdo->query('SELECT COALESCE(MAX(poradi), 0) + 1 FROM news_tag')->fetchColumn();
}

function news_tag_save(array $data, ?int $id = null): int
{
    global $pdo;

    $user = admin_session_user();
    $nazevCz = trim((string)($data['nazev_cz'] ?? ''));
    if ($nazevCz === '') {
        throw new InvalidArgumentException('Název CZ je povinný.');
    }

    $slugCz = trim((string)($data['slug_cz'] ?? ''));
    if ($slugCz === '') {
        $slugCz = (string)text_str($nazevCz);
    }
    $slugEn = trim((string)($data['slug_en'] ?? ''));
    if ($slugEn === '' && trim((string)($data['nazev_en'] ?? '')) !== '') {
        $slugEn = (string)text_str((string)$data['nazev_en']);
    }

    $payload = [
        ':poradi' => (int)($data['poradi'] ?? 0),
        ':nazev_cz' => $nazevCz,
        ':nazev_en' => trim((string)($data['nazev_en'] ?? '')),
        ':slug_cz' => $slugCz,
        ':slug_en' => $slugEn,
        ':color' => trim((string)($data['color'] ?? '')),
        ':user_u' => $user,
    ];

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO news_tag
            (poradi, nazev_cz, nazev_en, slug_cz, slug_en, color, user_i, user_u)
            VALUES (:poradi, :nazev_cz, :nazev_en, :slug_cz, :slug_en, :color, :user_i, :user_u)');
        $payload[':user_i'] = $user;
        $stmt->execute($payload);

        return (int)$pdo->lastInsertId();
    }

    $payload[':id'] = $id;
    $payload[':valid'] = isset($data['valid']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE news_tag
        SET poradi = :poradi,
            nazev_cz = :nazev_cz,
            nazev_en = :nazev_en,
            slug_cz = :slug_cz,
            slug_en = :slug_en,
            color = :color,
            valid = :valid,
            user_u = :user_u
        WHERE id = :id');
    $stmt->execute($payload);

    return $id;
}

function news_tag_delete(int $id): void
{
    global $pdo;

    $stmt = $pdo->prepare('UPDATE news_tag SET valid = 0, user_u = :user_u WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':user_u' => admin_session_user(),
    ]);
}

function news_tag_ids_for_news(int $newsId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT tag_id FROM news_tag_rel WHERE news_id = :news_id ORDER BY tag_id ASC');
    $stmt->execute([':news_id' => $newsId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

function news_tag_names_for_news(int $newsId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT t.nazev_cz
        FROM news_tag_rel r
        JOIN news_tag t ON t.id = r.tag_id
        WHERE r.news_id = :news_id AND t.valid = 1
        ORDER BY t.poradi ASC, t.nazev_cz ASC');
    $stmt->execute([':news_id' => $newsId]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function news_tag_badge_class(string $class): string
{
    $class = trim($class);
    if ($class === '') {
        return 'text-bg-light border';
    }

    $classes = preg_split('~\s+~', $class) ?: [];
    $safeClasses = array_filter($classes, static function (string $item): bool {
        return (bool)preg_match('~^[a-zA-Z0-9_-]+$~', $item);
    });

    return $safeClasses === [] ? 'text-bg-light border' : implode(' ', $safeClasses);
}

function news_tags_save_for_news(int $newsId, array $tagIds): void
{
    global $pdo;

    $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), static fn (int $id): bool => $id > 0)));

    $ownTransaction = !$pdo->inTransaction();
    if ($ownTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $delete = $pdo->prepare('DELETE FROM news_tag_rel WHERE news_id = :news_id');
        $delete->execute([':news_id' => $newsId]);

        if ($tagIds !== []) {
            $insert = $pdo->prepare('INSERT INTO news_tag_rel (news_id, tag_id, user_i) VALUES (:news_id, :tag_id, :user_i)');
            foreach ($tagIds as $tagId) {
                $insert->execute([
                    ':news_id' => $newsId,
                    ':tag_id' => $tagId,
                    ':user_i' => admin_session_user(),
                ]);
            }
        }

        if ($ownTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function news_typ_count (?int $valid = null): int
{
    global $pdo;
    if ($valid === null) {
        return (int)$pdo->query('SELECT COUNT(*) FROM news_typ')->fetchColumn();
    }

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
