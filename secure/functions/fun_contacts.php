<?php

function contacts_lide_cat_add($nazev_cz, $nazev_en, $poradi, $visible): void
{
    global $pdo;
    $qn_user = admin_session_user();

    $sql = 'INSERT INTO contacts_lide_category
                (poradi, nazev_cz, nazev_en, visible, user_i, user_u)
            VALUES
                (:poradi, :nazev_cz, :nazev_en, :visible, :user_i, :user_u)';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':visible'  => (int)$visible,
            ':user_i'   => $qn_user,
            ':user_u'   => $qn_user
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span>
                <span class="text">Kategorie kontaktů nebyla vložena</span>
              </a>';
        echo $e->getMessage();
    }
}

function contacts_lide_cat_vypis($limit, $valid): void
{
    global $pdo;
    $sqllimit = ($limit == 0) ? 999999 : (int)$limit;

    $sql = 'SELECT id, nazev_cz, poradi, visible
            FROM contacts_lide_category
            WHERE valid = :valid
            ORDER BY poradi
            LIMIT :lim';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valid', (int)$valid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $sqllimit, PDO::PARAM_INT);
    $stmt->execute();

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $visible_txt = ((int)$dev["visible"] === 1) ? "ANO" : "NE";

        echo '
        <tr>
            <td>'.$dev['id'].'</td>
            <td>'.htmlspecialchars($dev["nazev_cz"], ENT_QUOTES, 'UTF-8').'</td>
            <td>'.$dev["poradi"].'</td>
            <td>'.$visible_txt.'</td>
            <td class="text-center">
                <a class="btn btn-success btn-circle btn-sm"
                   href="index.php?section=01&amp;page=09&amp;sec_page=03&amp;edit='.$dev['id'].'&amp;limit='.$limit.'&amp;show=2">
                    <i class="bi bi-pencil"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-danger btn-circle btn-sm"
                   href="index.php?section=01&amp;page=09&amp;sec_page=03&amp;del='.$dev['id'].'&amp;limit='.$limit.'">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>';
    }
}

function contacts_lide_cat_edit($id, $nazev_cz, $nazev_en, $poradi, $visible, $valid): void
{
    global $pdo;
    $qn_user = admin_session_user();

    $sql = 'UPDATE contacts_lide_category SET
                poradi = :poradi,
                nazev_cz = :nazev_cz,
                nazev_en = :nazev_en,
                visible = :visible,
                valid = :valid,
                user_u = :user_u
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':poradi'   => (int)$poradi,
            ':nazev_cz' => $nazev_cz,
            ':nazev_en' => $nazev_en,
            ':visible'  => (int)$visible,
            ':valid'    => (int)$valid,
            ':user_u'   => $qn_user,
            ':id'       => (int)$id
        ]);

        unset($_POST['add']);
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]1";
        echo "<script type='text/javascript'>document.location.href='$url';</script>";
        echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $url . '">';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span>
                <span class="text">Kategorie kontaktů osob nebyla uložena</span>
              </a>';
        echo $e->getMessage();
    }
}

function contacts_lide_cat_delete($id): void
{
    global $pdo;
    $qn_user = admin_session_user();

    $sql = 'UPDATE contacts_lide_category
            SET valid = 0, user_u = :user_u
            WHERE id = :id';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_u' => $qn_user,
            ':id'     => (int)$id
        ]);

        echo '<a href="#" class="btn btn-success btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-check"></i></span>
                <span class="text">Kategorie kontaktů osob byla smazána</span>
              </a>';
    } catch (PDOException $e) {
        echo '<a href="#" class="btn btn-warning btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-exclamation-triangle"></i></span>
                <span class="text">Kategorie kontaktů osob nebyla smazána</span>
              </a>';
        echo $e->getMessage();
    }
}

function contacts_lide_cat_option_form($select): void
{
    global $pdo;

    $stmt = $pdo->query(
        'SELECT id, nazev_cz
         FROM contacts_lide_category
         WHERE valid = 1
         ORDER BY poradi'
    );

    while ($dev = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$dev['id'];
        $nazev_cz = $dev['nazev_cz'];
        $selected = ((int)$select === $id) ? ' selected="selected"' : '';
        echo '<option value="'.$id.'"'.$selected.'>'.$id.'&nbsp;-&nbsp;'.htmlspecialchars($nazev_cz, ENT_QUOTES, 'UTF-8').'</option>'."\n";
    }
}

function contacts_lide_cat_count($valid): int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM contacts_lide_category WHERE valid = :valid');
    $stmt->execute([':valid' => (int)$valid]);
    return (int)$stmt->fetchColumn();
}

