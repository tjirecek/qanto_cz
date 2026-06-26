<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once SEC_DIR . '/functions/fun_pobocky.php';

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    $userPrava = (int)admin_session_prava();
    if (!in_array($userPrava, [1, 2], true)) {
        http_response_code(403);
        echo json_encode([
            'draw' => (int)($_GET['draw'] ?? 0),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Forbidden',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    pobocky_prepare_tables($pdo);

    $type = pobocky_normalize_type((string)($_GET['type'] ?? 'prodejna'));
    $columns = [
        'id',
        'poradi',
        'stredisko',
        'galerie_id',
        'nazev_cz',
        'nazev_en',
        'mobil',
        'email',
        'adresa',
        'gps',
        'vedouci',
        'image',
        'sluzby_cz',
        'sluzby_en',
        'valid',
        'user_i',
        'user_u',
        'ts_i',
        'ts_u',
    ];

    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 25);
    if ($length <= 0) {
        $length = 25;
    }
    if ($length > 1000) {
        $length = 1000;
    }

    $orderColIndex = (int)($_GET['order'][0]['column'] ?? 1);
    $orderDirRaw = strtolower((string)($_GET['order'][0]['dir'] ?? 'asc'));
    $orderDir = ($orderDirRaw === 'desc') ? 'DESC' : 'ASC';
    $orderCol = 'poradi';
    if ($orderColIndex >= 0 && $orderColIndex < count($columns)) {
        $orderCol = $columns[$orderColIndex];
    }

    $params = [
        ':type' => $type,
    ];
    $whereParts = ['typ = :type'];

    $searchValue = trim((string)($_GET['search']['value'] ?? ''));
    if ($searchValue !== '') {
        $likes = [];
        foreach ($columns as $column) {
            $likes[] = "{$column} LIKE :q";
        }
        $whereParts[] = '(' . implode(' OR ', $likes) . ')';
        $params[':q'] = '%' . $searchValue . '%';
    }

    foreach ($columns as $i => $column) {
        $val = trim((string)($_GET['columns'][$i]['search']['value'] ?? ''));
        if ($val === '') {
            continue;
        }

        $param = ':c' . $i;
        $whereParts[] = "{$column} LIKE {$param}";
        $params[$param] = '%' . $val . '%';
    }

    $where = 'WHERE ' . implode(' AND ', $whereParts);

    $recordsTotalStmt = $pdo->prepare('SELECT COUNT(*) FROM pobocky WHERE typ = :type');
    $recordsTotalStmt->execute([':type' => $type]);
    $recordsTotal = (int)$recordsTotalStmt->fetchColumn();

    if (count($whereParts) === 1) {
        $recordsFiltered = $recordsTotal;
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pobocky {$where}");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $recordsFiltered = (int)$stmt->fetchColumn();
    }

    $sql = sprintf(
        'SELECT %s FROM pobocky %s ORDER BY %s %s, id DESC LIMIT :len OFFSET :start',
        implode(', ', $columns),
        $where,
        $orderCol,
        $orderDir
    );

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':len', $length, PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = array_map(
            static fn(string $column): mixed => $row[$column] ?? null,
            $columns
        );
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'draw' => (int)($_GET['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Chyba serveru: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
