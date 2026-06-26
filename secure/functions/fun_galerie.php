<?php
// PDO verze (původně 3.1.17 - mysqli syntaxe)

//funkce pro pridani typu galerie
function galerie_typ_add ($nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'INSERT INTO galerie_typ (poradi, nazev_cz, nazev_en, popis_cz, popis_en)
            VALUES (:poradi, :nazev_cz, :nazev_en, :popis_cz, :popis_en)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':popis_cz' => $popis_cz,
            ':popis_en' => $popis_en
        ]);

        echo '<span class="warning">Typ galerie byl úspěšně vytvořen</span><br />';
        unset ($_POST['add']);
    } catch (PDOException $e) {
        echo '<span class="warning">Typ galerie nebyl vytvořen</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro vypis typu galerie
function galerie_typ_vypis ()
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT id, nazev_cz, poradi FROM galerie_typ WHERE valid = 1 ORDER BY poradi';
    $stmt = $pdo->query($sql);

    while ($dev = $stmt->fetch(PDO::FETCH_NUM))
    {
        $id = $dev[0];
        $nazev_cz = stripslashes($dev[1]);
        $poradi = $dev[2];

        echo '<tr>' . "\n";
        echo '<td>'.$id.'</td>' . "\n";
        echo '<td>'.$nazev_cz.'</td>' . "\n";
        echo '<td>'.$poradi.'</td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=03&amp;edit='.$id.'"><img src="images/edit.gif" alt="Upravit" /></a></td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=03&amp;del='.$id.'"><img src="images/del.gif" alt="Smazat" /></a></td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '</table>';
}

//funkce pro editaci typu galerie
function galerie_typ_edit ($id, $nazev_cz, $nazev_en, $poradi, $popis_cz, $popis_en)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE galerie_typ SET
                poradi = :poradi,
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                popis_cz = :popis_cz,
                popis_en = :popis_en
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':popis_cz' => $popis_cz,
            ':popis_en' => $popis_en,
            ':id'       => (int)$id
        ]);

        echo '<span class="warning">Typ galerie byl úspěšně změněn</span><br />';
        unset ($_POST['add']);
    } catch (PDOException $e) {
        echo '<span class="warning">Typ galerie nebyl změněn</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro vymazani galerie
function galerie_typ_delete ($id)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");
    $sql = 'UPDATE galerie_typ SET valid = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        echo '<span class="warning">Typ galerie s ID = '.$id.' byl smazán.</span>';
    } catch (PDOException $e) {
        echo '<span class="warning">Typ galerie nebyl vymazán</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro pridani nove galerie a vytvoreni adresaru pro ukladani fotografii
function galerie_add ($nazev_cz, $nazev_en, $datum, $galerie_typ, $popis_cz, $popis_en)
{
    global $pdo;

    $datum = format_date_db($datum);

    $pdo->exec("SET NAMES utf8");

    // Předpoklad: galerie.id je AUTO_INCREMENT
    $sql = 'INSERT INTO galerie (nazev_cz, nazev_en, datum, galerie_typ, popis_cz, popis_en)
            VALUES (:nazev_cz, :nazev_en, :datum, :galerie_typ, :popis_cz, :popis_en)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nazev_cz'    => $nazev_cz,
            ':nazev_en'    => $nazev_en,
            ':datum'       => $datum,
            ':galerie_typ' => (int)$galerie_typ,
            ':popis_cz'    => $popis_cz,
            ':popis_en'    => $popis_en
        ]);

        $id = (int)$pdo->lastInsertId();

        echo '<span class="warning">Galerie byla úspěšně vytvořena</span><br />';
        unset ($_POST['add']);
    } catch (PDOException $e) {
        echo '<span class="warning">Galerie nebyla vytvořena</span><br />';
        echo $e->getMessage();
        return;
    }

    umask(0000);
    if(@mkdir('../_images/_galerie/'.$id.'-galerie', 0777)):
        echo '<span class="warning">Adresář "'.$id.'-galerie" pro ukládání fotek byl úspěšně vytvořen</span><br />';
    else:
        echo '<span class="warning">Adresář pro ukládání fotek nebyl vytvořen</span><br />';
    endif;

    umask(0000);
    if(@mkdir('../_images/_galerie/'.$id.'-galerie/small', 0777)):
        echo '<span class="warning">Adresář "'.$id.'-galerie/small" pro náhledy byl úspěšně vytvořen</span><br />';
    else:
        echo '<span class="warning">Adresář pro náhledy nebyl vytvořen</span><br />';
    endif;
}

//funkce pro editaci galerie
function galerie_edit ($id, $nazev_cz, $nazev_en, $datum, $galerie_typ, $popis_cz, $popis_en)
{
    global $pdo;

    $datum = format_date_db($datum);

    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE galerie SET
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                datum = :datum,
                galerie_typ = :galerie_typ,
                popis_cz = :popis_cz,
                popis_en = :popis_en
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nazev_cz'    => $nazev_cz,
            ':nazev_en'    => $nazev_en,
            ':datum'       => $datum,
            ':galerie_typ' => (int)$galerie_typ,
            ':popis_cz'    => $popis_cz,
            ':popis_en'    => $popis_en,
            ':id'          => (int)$id
        ]);

        echo '<span class="warning">Galerie byla úspěšně aktualizována</span>';
        unset ($_POST['add']);
    } catch (PDOException $e) {
        echo '<span class="warning">Galerie nebyla aktualizována</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro vypis galerie s filtrovanim
function galerie_vypis ($str, $galerie_typ)
{
    global $pdo;
    require_once('../_functions/pager.class.php');

    $pdo->exec("SET NAMES utf8");

    // POZOR: Pager pravděpodobně vykonává SQL interně (možná přes mysqli).
    // Pokud bude potřeba, přepíšeme pager na PDO.
    if ((int)$galerie_typ === 0):
        $sql = 'SELECT id, nazev_cz, datum, galerie_typ FROM galerie WHERE valid = 1 ORDER BY datum DESC, id DESC';
    else:
        $sql = 'SELECT id, nazev_cz, datum, galerie_typ FROM galerie WHERE galerie_typ = '.(int)$galerie_typ.' AND valid = 1 ORDER BY datum DESC, id DESC';
    endif;

    $pager = new Pager($sql, 'str');
    $pager->PageSize = sp_hodnota('admin_galerie_pocet');
    $pager->PagerAlign = "center";
    $pager->SeoPrefix = "index.php?section=01&amp;page=05&amp;sec_page=02";
    $pager->DataBind();

    while ($dev = $pager->GetOne())
    {
        $id = (int)$dev->id;
        $nazev_cz = stripslashes($dev->nazev_cz);
        $datum = format_date_www($dev->datum);
        $galerie_typ_id = (int)$dev->galerie_typ;

        // typ galerie
        $stmt1 = $pdo->prepare('SELECT nazev_cz FROM galerie_typ WHERE id = :id AND valid = 1');
        $stmt1->execute([':id' => $galerie_typ_id]);
        $galerie_typ_nazev = (string)$stmt1->fetchColumn();

        // počet fotek
        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM galerie_photo WHERE galerie_id = :gid');
        $stmt2->execute([':gid' => $id]);
        $dev2 = (int)$stmt2->fetchColumn();

        echo '<tr>' . "\n";
        echo '<td>'.$id.'</td>' . "\n";
        echo '<td>'.$datum.'</td>' . "\n";
        echo '<td>'.$nazev_cz.'</td>' . "\n";
        echo '<td>'.$galerie_typ_nazev.'</td>' . "\n";
        echo '<td>'.$dev2.'</td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=06&amp;view='.$id.'"><img src="images/view.gif" alt="Zobrazit" /></a></td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=04&amp;edit='.$id.'"><img src="images/edit.gif" alt="Upravit" /></a></td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=05&amp;add_foto='.$id.'"><img src="images/add.gif" alt="Přidat" /></a></td>' . "\n";
        echo '<td class="text-center"><a href="index.php?section=01&amp;page=05&amp;sec_page=02&amp;del='.$id.'"><img src="images/del.gif" alt="Smazat" /></a></td>' . "\n";
        echo '</tr>' . "\n";
    }
    echo '</table>';

    $firstLastMode = new FirstLastPagerMode();
    $pager->AddPagerMode($firstLastMode);
    $skipperMode = new SkipperPagerMode();
    $pager->AddPagerMode($skipperMode);
    $neighbourMode = new NeighbourPagerMode();
    $neighbourMode->NeighbourPagesCount = 3;
    $pager->AddPagerMode($neighbourMode);
    $pager->DrawPager();
}

//funkce pro znevalidneni galerie (neni smazana)
function galerie_delete ($id)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE galerie SET valid = 0 WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        echo '<span class="warning">Galerie s ID = '.$id.' byla smazána.</span>';
    } catch (PDOException $e) {
        echo '<span class="warning">Galerie nebyla vymazána</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro pridani fotografie ke galerii
function galerie_photo_add ($galerie_id, $nazev_cz, $nazev_en)
{
    global $pdo;

    $galerie_id = (int)$galerie_id;

    if(isset($_POST['soubor'])): $soubor = $_POST['soubor']; else: $soubor = ""; endif;

    $dir_original = '../_images/_galerie/'.$galerie_id.'-galerie/';
    $dir_small    = '../_images/_galerie/'.$galerie_id.'-galerie/small/';

    $pdo->exec("SET NAMES utf8");

    // zjisti poradi
    $stmt1 = $pdo->prepare('SELECT MAX(poradi) FROM galerie_photo WHERE galerie_id = :gid');
    $stmt1->execute([':gid' => $galerie_id]);
    $maxPoradi = $stmt1->fetchColumn();
    $poradi = ($maxPoradi === null) ? 1 : ((int)$maxPoradi + 1);

    if ($_FILES["soubor"]["error"] == UPLOAD_ERR_NO_FILE):
        $soubor = "není připojen";
        return "";
    endif;

    $soubor_str = text_str($_FILES['soubor']['name']);
    if(move_uploaded_file($_FILES["soubor"]["tmp_name"], $dir_original.$soubor_str )):
        copy($dir_original.$soubor_str, $dir_small.$soubor_str);
    else:
        echo "Nastala chyba, zkuste upload znova";
        return "";
    endif;

    $sql = 'INSERT INTO galerie_photo (galerie_id, nazev_cz, nazev_en, poradi, soubor)
            VALUES (:gid, :nazev_cz, :nazev_en, :poradi, :soubor)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':gid'      => $galerie_id,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':poradi'   => (int)$poradi,
            ':soubor'   => $soubor_str
        ]);

        echo '<span class="warning">Název a údaje byly uloženy</span><br />';
    } catch (PDOException $e) {
        echo '<span class="warning">Název a údaje nebyly uloženy</span><br />';
        echo $e->getMessage();
    }

    return $soubor_str;
}

//funkce pro vytvoreni fotografie
function create_thumbnail($file_in, $max_x = 0, $max_y = 0)
{
    list($width, $height) = getimagesize($file_in);
    if (!$width || !$height) {
        return array(0, 0);
    }
    if ($max_x && $width > $max_x) {
        $height = round($height * $max_x / $width);
        $width = $max_x;
    }
    if ($max_y && $height > $max_y) {
        $width = round($width * $max_y / $height);
        $height = $max_y;
    }
    return array($width, $height);
}

//funkce pro zmenseni fotografie a ulozeni
function image_resize($file_in, $width, $height)
{
    list($origwidth, $origheight) = getimagesize($file_in);

    $image_p = imagecreatetruecolor($width, $height);
    $image = imagecreatefromjpeg($file_in);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $origwidth, $origheight);

    if(imagejpeg($image_p, $file_in, sp_hodnota('galerie_image_quality'))):
        echo 'Obrázek '.$file_in.' vložen <br />';
    else:
        echo 'Obrázek '.$file_in.' nebyl vložen <br />';
    endif;
}

//funkce pro nahled fotogalerie s obrazky
function galerie_view ($galerie_id)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT id, nazev_cz, nazev_en, soubor, poradi
            FROM galerie_photo
            WHERE galerie_id = :gid
            ORDER BY poradi, id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':gid' => (int)$galerie_id]);

    while ($dev = $stmt->fetch(PDO::FETCH_NUM))
    {
        $id = $dev[0];
        $nazev_cz = stripslashes($dev[1]);
        $nazev_en = stripslashes($dev[2]);
        $soubor = $dev[3];
        $poradi = $dev[4];

        echo '<div class="img">';
        echo '<span class="popisek"><strong>'.$poradi.'</strong> - '.$soubor.'</span>' . "\n";
        echo '<img src="../_images/_galerie/'.$galerie_id.'-galerie/small/'.$soubor.'" alt="'.$nazev_cz.'" />' . "\n";
        echo "<br />\n";
        echo '<span class="popisek">'.$nazev_cz.'</span>' . "\n";
        echo '<span class="popisek">'.$nazev_en.'</span>' . "\n";
        echo '<a href="index.php?section=01&amp;page=05&amp;sec_page=06&amp;view='.$galerie_id.'&amp;photo_del='.$id.'">DELETE</a>' . "\n";
        echo '<a href="index.php?section=01&amp;page=05&amp;sec_page=06&amp;view='.$galerie_id.'&amp;photo_edit='.$id.'">EDIT</a>';
        echo '</div>';
    }
}

//funkce pro smazani fotografie
function galerie_photo_delete ($id_photo, $galerie_id)
{
    global $pdo;

    $id_photo = (int)$id_photo;
    $galerie_id = (int)$galerie_id;

    $pdo->exec("SET NAMES utf8");

    $stmt = $pdo->prepare('SELECT soubor FROM galerie_photo WHERE id = :id');
    $stmt->execute([':id' => $id_photo]);
    $soubor = (string)$stmt->fetchColumn();
    $soubor = stripslashes($soubor);

    $delete_photo = @unlink('../_images/_galerie/'.$galerie_id.'-galerie/'.$soubor);
    $delete_photo_small = @unlink('../_images/_galerie/'.$galerie_id.'-galerie/small/'.$soubor);

    if ($delete_photo):
        echo '<span class="warning">Originál obrázku smazán</span><br />';
    endif;
    if ($delete_photo_small):
        echo '<span class="warning">Thumbnail obrázku smazán</span><br />';
    endif;

    $sql = 'DELETE FROM galerie_photo WHERE id = :id';

    try {
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute([':id' => $id_photo]);
        echo '<span class="warning">Fotografie s ID = '.$id_photo.' byla smazána.</span>';
    } catch (PDOException $e) {
        echo '<span class="warning">Fotografie z DB nebyla vymazána</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro upravu popisku u fotografie
function galerie_photo_edit ($id, $nazev_cz, $nazev_en, $poradi)
{
    global $pdo;

    $pdo->exec("SET NAMES utf8");

    $sql = 'UPDATE galerie_photo
            SET nazev_cz = :nazev_cz, nazev_en = :nazev_en, poradi = :poradi
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':poradi'   => (int)$poradi,
            ':id'       => (int)$id
        ]);

        echo '<span class="warning">Popisek byl aktualizován</span><br />';
    } catch (PDOException $e) {
        echo '<span class="warning">Popisek nebyl aktualizován</span><br />';
        echo $e->getMessage();
    }
}

//funkce pro editaci fotografie (přepočet pořadí)
function galerie_photo_poradi_update ($galerie)
{
    global $pdo;

    $galerie = (int)$galerie;

    $pdo->exec("SET NAMES utf8");

    $sql = 'SELECT id, soubor FROM galerie_photo WHERE galerie_id = :gid ORDER BY soubor';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':gid' => $galerie]);

    $i = 0;
    while ($dev = $stmt->fetch(PDO::FETCH_NUM)) {
        $i++;
        $id = (int)$dev[0];

        $stmtU = $pdo->prepare('UPDATE galerie_photo SET poradi = :poradi WHERE id = :id');
        $stmtU->execute([':poradi' => $i, ':id' => $id]);
    }

    echo '<span class="warning">Pořadí bylo aktualizováno</span>';
}

//funkce pro odstraneni duplicit v galerii
function galerie_photo_duplicity_delete ($galerie)
{
    global $pdo;

    $galerie = (int)$galerie;

    $pdo->exec("SET NAMES utf8");

    // MySQL varianta mazání duplicit (gp1 join gp2) - přepsaná na exec s parametrem
    $sql = 'DELETE gp1
            FROM galerie_photo gp1, galerie_photo gp2
            WHERE gp1.id > gp2.id
              AND gp1.soubor = gp2.soubor
              AND gp1.galerie_id = :gid
              AND gp2.galerie_id = :gid';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':gid' => $galerie]);

        echo '<span class="warning">Duplicity byly úspěšně odstraněny.</span><br />';
    } catch (PDOException $e) {
        echo '<span class="warning">Duplicity nebyly odstraněny.</span><br />';
        echo $e->getMessage();
    }
}
?>