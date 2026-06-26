<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once __DIR__ . '/../../inc/cron/cron_log_helper.php';

try {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO pripojeni neni dostupne.');
    }

    $userPrava = (int)admin_session_prava();
    if ($userPrava !== 1) {
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

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    cronLogEnsureTable($pdo);

    $draw   = (int)($_GET['draw'] ?? 0);
    $start  = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 25);
    if ($length <= 0) {
        $length = 25;
    }
    if ($length > 2000) {
        $length = 2000;
    }

    $resultFilter = strtoupper(trim((string)($_GET['result'] ?? '')));
    if (!in_array($resultFilter, ['', 'SUCCESS', 'ERROR'], true)) {
        $resultFilter = '';
    }

    $columns = [
        'id',
        'cron_name',
        'script_path',
        'source_name',
        'processed_count',
        'result',
        'started_at',
        'finished_at',
        'duration_ms',
        'message',
    ];

    $orderColIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDirRaw   = strtolower((string)($_GET['order'][0]['dir'] ?? 'desc'));
    $orderDir      = ($orderDirRaw === 'asc') ? 'ASC' : 'DESC';

    $orderCol = $columns[0];
    if ($orderColIndex >= 0 && $orderColIndex < count($columns)) {
        $orderCol = $columns[$orderColIndex];
    }

    $whereParts = [];
    $params = [];

    if ($resultFilter !== '') {
        $whereParts[] = 'result = :result_filter';
        $params[':result_filter'] = $resultFilter;
    }

    $searchValue = trim((string)($_GET['search']['value'] ?? ''));
    if ($searchValue !== '') {
        $whereParts[] = "(
            CAST(id AS CHAR) LIKE :q OR
            cron_name LIKE :q OR
            script_path LIKE :q OR
            source_name LIKE :q OR
            source_path LIKE :q OR
            CAST(processed_count AS CHAR) LIKE :q OR
            result LIKE :q OR
            CAST(started_at AS CHAR) LIKE :q OR
            CAST(finished_at AS CHAR) LIKE :q OR
            CAST(duration_ms AS CHAR) LIKE :q OR
            message LIKE :q
        )";
        $params[':q'] = '%' . $searchValue . '%';
    }

    $columnMap = [
        0 => 'CAST(id AS CHAR)',
        1 => 'cron_name',
        2 => 'script_path',
        3 => 'CONCAT(IFNULL(source_name, \'\'), \' \', IFNULL(source_path, \'\'))',
        4 => 'CAST(processed_count AS CHAR)',
        5 => 'result',
        6 => 'CAST(started_at AS CHAR)',
        7 => 'CAST(finished_at AS CHAR)',
        8 => 'CAST(duration_ms AS CHAR)',
        9 => 'message',
    ];

    foreach ($columnMap as $i => $expr) {
        $val = trim((string)($_GET['columns'][$i]['search']['value'] ?? ''));
        if ($val === '') {
            continue;
        }

        $p = ':c' . $i;
        $whereParts[] = $expr . ' LIKE ' . $p;
        $params[$p] = '%' . $val . '%';
    }

    $where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

    $recordsTotal = (int)$pdo->query("SELECT COUNT(*) FROM log_cron")->fetchColumn();

    if ($where === '') {
        $recordsFiltered = $recordsTotal;
    } else {
        $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM log_cron {$where}");
        foreach ($params as $k => $v) {
            $stmtCnt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmtCnt->execute();
        $recordsFiltered = (int)$stmtCnt->fetchColumn();
    }

    $sql = "
        SELECT
            id,
            cron_name,
            script_path,
            source_name,
            source_path,
            processed_count,
            result,
            message,
            started_at,
            finished_at,
            duration_ms
        FROM log_cron
        {$where}
        ORDER BY {$orderCol} {$orderDir}
        LIMIT :len OFFSET :start
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':len', $length, PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rowResult = (string)($r['result'] ?? '');
        $badgeClass = 'secondary';
        if ($rowResult === 'SUCCESS') {
            $badgeClass = 'success';
        } elseif ($rowResult === 'ERROR') {
            $badgeClass = 'danger';
        }

        $cronName = (string)($r['cron_name'] ?? '');
        $scriptPath = (string)($r['script_path'] ?? '');
        $sourceName = (string)($r['source_name'] ?? '');
        $sourcePath = (string)($r['source_path'] ?? '');
        $sourceTitle = trim($sourceName . ($sourcePath !== '' ? "\n" . $sourcePath : ''));

        $sourceHtml = '';
        if ((string)($r['source_name'] ?? '') !== '') {
            $sourceHtml .= '<div class="cron-log-text" title="' . htmlspecialchars($sourceTitle, ENT_QUOTES) . '"><strong>' . htmlspecialchars($sourceName) . '</strong></div>';
        }
        if ((string)($r['source_path'] ?? '') !== '') {
            $sourceHtml .= '<div class="small text-muted cron-log-text" title="' . htmlspecialchars($sourceTitle, ENT_QUOTES) . '">' . htmlspecialchars($sourcePath) . '</div>';
        }

        $message = trim((string)($r['message'] ?? ''));
        $messageHtml = '<span class="text-muted">-</span>';
        if ($message !== '') {
            $messageEsc = htmlspecialchars($message);
            $messageAttr = htmlspecialchars($message, ENT_QUOTES);
            $messageHtml = ''
                . '<div class="cron-log-text" title="' . $messageAttr . '">' . $messageEsc . '</div>'
                . '<a href="#" class="small cron-log-message-btn" data-message="' . $messageAttr . '">zobrazit</a>';
        }

        $data[] = [
            (int)($r['id'] ?? 0),
            '<div class="fw-semibold cron-log-text" title="' . htmlspecialchars($cronName, ENT_QUOTES) . '">' . htmlspecialchars($cronName) . '</div>',
            '<div class="small text-muted cron-log-text" title="' . htmlspecialchars($scriptPath, ENT_QUOTES) . '">' . htmlspecialchars($scriptPath) . '</div>',
            $sourceHtml !== '' ? $sourceHtml : '<span class="text-muted">-</span>',
            (int)($r['processed_count'] ?? 0),
            '<span class="badge bg-' . $badgeClass . '">' . htmlspecialchars($rowResult) . '</span>',
            htmlspecialchars((string)($r['started_at'] ?? '')),
            htmlspecialchars((string)($r['finished_at'] ?? '')),
            (int)($r['duration_ms'] ?? 0) . ' ms',
            $messageHtml,
        ];
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
