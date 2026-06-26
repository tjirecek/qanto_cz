<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

global $pdo;

$send = isset($_GET['send']) ? (int)$_GET['send'] : 0;
if ($send <= 0) {
    exit('Chybí parametr send.');
}

// --- načti novinku ---
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $send]);
$dev = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dev) {
    exit('Novinka nebyla nalezena.');
}

$year = date("Y");
$predmet = 'Qanto novinky :: ' . (string)($dev["nazev_cz"] ?? '');

// Pozn.: text v DB máš typicky uložený jako HTML
$textCz = (string)($dev["text_cz"] ?? '');

$body1 = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Qanto :: newsletter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body style="margin: 0; padding: 0;">
<table align="center" border="0" cellpadding="0" cellspacing="0" width="700">
  <tr>
    <td align="center" bgcolor="#ffffff" style="padding: 0 0 0 0;">
      <a href="https://' . $_SERVER["SERVER_NAME"] . '/secure/" style="font-family: Calibri, sans-serif; font-size: 12px;">Zobrazte si novinku v administraci</a>
      <div style="width:700px;display:block;background:#1f2937;color:#ffffff;padding:24px 32px;box-sizing:border-box;font:700 28px Calibri,sans-serif;">
        Qanto novinky
      </div>
    </td>
  </tr>
  <tr>
    <td bgcolor="#ffffff" style="padding: 0px 20px 0px 20px; color: #4c4c4c; font-family: Calibri, sans-serif; font-size: 12px;"><br />
      ' . $textCz . '
    </td>
  </tr>
  <tr>
    <td bgcolor="#ee4c50" style="padding: 0px 0px 20px 30px;">
      <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
          <td style="color: #ffffff; font-family: Calibri, sans-serif; font-size: 14px;">
            &reg; Qanto :: Astur & Qanto s.r.o. ' . $year . '<br/>
          </td>
          <td align="right"></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
';

// --- PHPMailer include ---
require_once "_scripts/phpmailer/src/Exception.php";
require_once "_scripts/phpmailer/src/PHPMailer.php";
require_once "_scripts/phpmailer/src/SMTP.php";

// --- spočti počet odběratelů ---
$stmtCnt = $pdo->query("SELECT COUNT(*) FROM news_users WHERE valid = 1 AND registered = 1");
$totalEmails = (int)$stmtCnt->fetchColumn();

$limitEmail = 5; // kolik BCC do jedné dávky
$emailsSend = 0;
$pocetDavek = (int)ceil($totalEmails / $limitEmail);

$zprava = 'Newsletter byl úspěšně odeslán';

for ($i = 0; $i < $pocetDavek; $i++) {
    $offset = $i * $limitEmail;

    // načti dávku emailů
    $stmtBatch = $pdo->prepare("
        SELECT email
        FROM news_users
        WHERE valid = 1 AND registered = 1
        ORDER BY id
        LIMIT :lim OFFSET :off
    ");
    $stmtBatch->bindValue(':lim', $limitEmail, PDO::PARAM_INT);
    $stmtBatch->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmtBatch->execute();

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = "smtpx.stable.cz";
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // TODO: přesuň do configu/env (ne do kódu)
    $mail->Username = "qanto@qanto.cz";
    $mail->Password = "a81OoxK7vyi16oK";

    $mail->From = "no-reply@qanto.cz";
    $mail->FromName = "Qanto";

    // BCC
    while ($row = $stmtBatch->fetch(PDO::FETCH_ASSOC)) {
        $addresses = explode(';', (string)($row['email'] ?? ''));
        foreach ($addresses as $address) {
            $address = trim($address);
            if ($address === '') {
                continue;
            }
            if (!PHPMailer::validateAddress($address)) {
                echo '<span class="none">Chybná adresa: ' . htmlspecialchars($address, ENT_QUOTES) . '.</span><br />';
                continue;
            }
            $mail->addBCC($address);
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $predmet;
    $mail->Body = $body1;
    $mail->AltBody = "";
    $mail->CharSet = "utf-8";
    $mail->addCustomHeader('X-CampaignID', '8lqzOS0AOmJypu1L5M7f');

    if (!$mail->send()) {
        echo '<span class="warning">Informace nebyla odeslána.</span><br />';
        echo 'Error: ' . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES);
    } else {
        $emailsSend++;
    }
}

// --- update info_send ---
$stmtUpd = $pdo->prepare("UPDATE news SET info_send = :dt WHERE id = :id");
$stmtUpd->execute([
    ':dt' => format_date_db(get_date()),
    ':id' => $send,
]);

echo '<span class="warning">Počet e-mailů v kopii = ' . (int)$limitEmail .
    '<br />' . htmlspecialchars($zprava, ENT_QUOTES) .
    ' na tento počet dávek: ' . (int)$emailsSend .
    '<br />Počet dávek, na který mělo být odesláno: ' . (int)$pocetDavek . '.</span><br />';
