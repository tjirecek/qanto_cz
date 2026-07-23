<?php
declare(strict_types=1);

require_once ROOT_DIR . '/secure/functions/fun_rep_volani.php';

function frontend_volani_invoice_by_unify(PDO $pdo, string $unify): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM volani_preuctovani WHERE unify = :unify AND valid = 1 LIMIT 1');
    $stmt->execute([':unify' => trim($unify)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function frontend_volani_periods_by_email(PDO $pdo, string $email): array
{
    $stmt = $pdo->prepare('
        SELECT obdobi, COUNT(*) AS total, SUM(celkem) AS total_amount
        FROM volani_preuctovani
        WHERE email = :email AND valid = 1
        GROUP BY obdobi
        ORDER BY obdobi DESC
    ');
    $stmt->execute([':email' => trim($email)]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function frontend_volani_invoices_by_email(PDO $pdo, string $email, string $period = ''): array
{
    $sql = 'SELECT * FROM volani_preuctovani WHERE email = :email AND valid = 1';
    $params = [':email' => trim($email)];
    if ($period !== '') {
        $sql .= ' AND obdobi = :obdobi';
        $params[':obdobi'] = $period;
    }

    $sql .= ' ORDER BY obdobi DESC, jmeno, mobil';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function frontend_volani_latest_mobile_period(PDO $pdo, string $table, string $mobil): string
{
    if (!in_array($table, ['volani_souhrn', 'volani_detail'], true)) {
        return '';
    }

    $stmt = $pdo->prepare("
        SELECT obdobi
        FROM {$table}
        WHERE mobil = :mobil
        GROUP BY obdobi
        ORDER BY CAST(obdobi AS DECIMAL(14,4)) DESC, obdobi DESC
        LIMIT 1
    ");
    $stmt->execute([':mobil' => $mobil]);

    return (string)($stmt->fetchColumn() ?: '');
}

function frontend_volani_summary(PDO $pdo, array $invoice): array
{
    $stmt = $pdo->prepare('SELECT * FROM volani_souhrn WHERE obdobi = :obdobi AND mobil = :mobil ORDER BY produkt, polozka, sluzba, id');
    $stmt->execute([
        ':obdobi' => (string)$invoice['obdobi'],
        ':mobil' => (string)$invoice['mobil'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($rows !== []) {
        return $rows;
    }

    $latestPeriod = frontend_volani_latest_mobile_period($pdo, 'volani_souhrn', (string)$invoice['mobil']);
    if ($latestPeriod === '' || $latestPeriod === (string)$invoice['obdobi']) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT * FROM volani_souhrn WHERE obdobi = :obdobi AND mobil = :mobil ORDER BY produkt, polozka, sluzba, id');
    $stmt->execute([
        ':obdobi' => $latestPeriod,
        ':mobil' => (string)$invoice['mobil'],
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function frontend_volani_detail(PDO $pdo, array $invoice): array
{
    $stmt = $pdo->prepare('SELECT * FROM volani_detail WHERE obdobi = :obdobi AND mobil = :mobil ORDER BY datumcas, produkt, polozka, id');
    $stmt->execute([
        ':obdobi' => (string)$invoice['obdobi'],
        ':mobil' => (string)$invoice['mobil'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($rows !== []) {
        return $rows;
    }

    $latestPeriod = frontend_volani_latest_mobile_period($pdo, 'volani_detail', (string)$invoice['mobil']);
    if ($latestPeriod === '' || $latestPeriod === (string)$invoice['obdobi']) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT * FROM volani_detail WHERE obdobi = :obdobi AND mobil = :mobil ORDER BY datumcas, produkt, polozka, id');
    $stmt->execute([
        ':obdobi' => $latestPeriod,
        ':mobil' => (string)$invoice['mobil'],
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function frontend_volani_pdf_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function frontend_volani_pdf_money(mixed $value): string
{
    return number_format((float)$value, 2, ',', ' ') . ' Kč';
}

function frontend_volani_pdf_number(mixed $value): string
{
    return rep_volani_number($value);
}

function frontend_volani_pdf_file_part(mixed $value): string
{
    $value = trim((string)$value);
    $value = preg_replace('~[^a-z0-9._-]+~i', '-', $value) ?? '';
    $value = trim($value, '-._');

    return $value !== '' ? $value : 'export';
}

function frontend_volani_pdf_html(array $invoice, array $rows, int $type): string
{
    $isDetail = $type === 2;
    $title = $isDetail ? 'Podrobný výpis volání' : 'Souhrnné vyúčtování volání';
    $totalWithoutVat = 0.0;
    $totalWithVat = 0.0;

    foreach ($rows as $row) {
        $totalWithoutVat += (float)($row['celkem_bez_dph'] ?? 0);
        $totalWithVat += (float)($row['celkem_s_dph'] ?? 0);
    }

    if ($totalWithoutVat === 0.0) {
        $totalWithoutVat = (float)($invoice['zakladcelkem'] ?? 0);
    }
    if ($totalWithVat === 0.0) {
        $totalWithVat = (float)($invoice['celkem'] ?? 0);
    }

    $tableRows = '';
    if ($isDetail) {
        foreach ($rows as $row) {
            $tableRows .= '<tr>'
                . '<td>' . frontend_volani_pdf_e(rep_volani_period_label((string)($row['obdobi'] ?? ''))) . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['produkt'] ?? '') . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['polozka'] ?? '') . '</td>'
                . '<td>' . frontend_volani_pdf_e(rep_volani_datetime_label((string)($row['datumcas'] ?? ''))) . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['smer'] ?? '') . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['cislo'] ?? '') . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_number($row['trvani'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($row['celkem_bez_dph'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($row['celkem_s_dph'] ?? 0)) . '</td>'
                . '</tr>';
        }
        $tableHead = '<tr>'
            . '<th>Období</th>'
            . '<th>Produktová řada</th>'
            . '<th>Položka</th>'
            . '<th>Datum a čas</th>'
            . '<th>Směr</th>'
            . '<th>Volané číslo</th>'
            . '<th class="num">Trvání</th>'
            . '<th class="num">Bez DPH</th>'
            . '<th class="num">S DPH</th>'
            . '</tr>';
        $totalColspan = 7;
    } else {
        foreach ($rows as $row) {
            $tableRows .= '<tr>'
                . '<td>' . frontend_volani_pdf_e(rep_volani_period_label((string)($row['obdobi'] ?? ''))) . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['produkt'] ?? '') . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['polozka'] ?? '') . '</td>'
                . '<td>' . frontend_volani_pdf_e($row['sluzba'] ?? '') . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_number($row['pocet'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_number($row['trvani'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_number($row['objem'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($row['celkem_bez_dph'] ?? 0)) . '</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_number($row['dph'] ?? 0)) . ' %</td>'
                . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($row['celkem_s_dph'] ?? 0)) . '</td>'
                . '</tr>';
        }
        $tableHead = '<tr>'
            . '<th>Období</th>'
            . '<th>Produktová řada</th>'
            . '<th>Položka</th>'
            . '<th>Služba</th>'
            . '<th class="num">Počet</th>'
            . '<th class="num">Trvání</th>'
            . '<th class="num">Objem</th>'
            . '<th class="num">Bez DPH</th>'
            . '<th class="num">DPH</th>'
            . '<th class="num">S DPH</th>'
            . '</tr>';
        $totalColspan = 7;
    }

    if ($tableRows === '') {
        $tableRows = '<tr><td colspan="' . ($isDetail ? 9 : 10) . '">Nejsou dostupná žádná data.</td></tr>';
    }

    return '<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<style>
@page { margin: 24px 22px; }
body { font-family: DejaVu Sans, sans-serif; color: #17212f; font-size: 9px; }
h1 { margin: 0 0 8px 0; font-size: 18px; }
.meta { margin: 0 0 14px 0; color: #4b5563; font-size: 10px; }
.meta span { display: inline-block; margin-right: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #d1d5db; padding: 4px 5px; vertical-align: top; }
th { background: #eef2f7; font-weight: 700; text-align: left; }
tr:nth-child(even) td { background: #f8fafc; }
.num { text-align: right; white-space: nowrap; }
.total td { background: #17212f; color: #fff; font-weight: 700; }
</style>
</head>
<body>
<h1>' . frontend_volani_pdf_e($title) . '</h1>
<div class="meta">
    <span>Jméno: ' . frontend_volani_pdf_e($invoice['jmeno'] ?? '') . '</span>
    <span>Telefon: ' . frontend_volani_pdf_e($invoice['mobil'] ?? '') . '</span>
    <span>Období: ' . frontend_volani_pdf_e(rep_volani_period_label((string)($invoice['obdobi'] ?? ''))) . '</span>
</div>
<table>
<thead>' . $tableHead . '</thead>
<tbody>'
    . $tableRows
    . '<tr class="total"><td colspan="' . $totalColspan . '" class="num">Celkem</td><td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($totalWithoutVat)) . '</td>'
    . ($isDetail ? '' : '<td></td>')
    . '<td class="num">' . frontend_volani_pdf_e(frontend_volani_pdf_money($totalWithVat)) . '</td></tr>'
    . '</tbody>
</table>
</body>
</html>';
}

function frontend_volani_stream_pdf(PDO $pdo, string $unify, int $type): void
{
    if (!in_array($type, [1, 2], true)) {
        http_response_code(400);
        echo 'PDF export je dostupný pouze pro souhrn a detail.';
        exit;
    }

    $invoice = frontend_volani_invoice_by_unify($pdo, $unify);
    if ($invoice === null) {
        http_response_code(404);
        echo 'Vyúčtování nebylo nalezeno.';
        exit;
    }

    $rows = $type === 2
        ? frontend_volani_detail($pdo, $invoice)
        : frontend_volani_summary($pdo, $invoice);

    $autoload = ROOT_DIR . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        http_response_code(500);
        echo 'Composer vendor/autoload.php není dostupný.';
        exit;
    }
    require_once $autoload;

    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml(frontend_volani_pdf_html($invoice, $rows, $type), 'UTF-8');
    $dompdf->render();

    $filename = 'volani-' . ($type === 2 ? 'detail' : 'souhrn')
        . '-' . frontend_volani_pdf_file_part($invoice['obdobi'] ?? '')
        . '-' . frontend_volani_pdf_file_part($invoice['mobil'] ?? '')
        . '.pdf';

    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}
