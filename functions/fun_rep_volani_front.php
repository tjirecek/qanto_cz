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
