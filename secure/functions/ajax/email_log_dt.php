<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';
require_once SEC_DIR . '/functions/mysql_connect.php';
require_once ROOT_DIR . '/functions/fun_email_log.php';

function email_log_dt_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function email_log_dt_badge(string $status): string
{
    $class = match ($status) {
        'sent' => 'success',
        'failed' => 'danger',
        'skipped' => 'warning',
        'queued' => 'secondary',
        default => 'light text-dark',
    };

    return '<span class="badge bg-' . email_log_dt_e($class) . '">' . email_log_dt_e($status) . '</span>';
}

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
    $pdo->exec('SET NAMES utf8mb4');
    email_log_prepare_table($pdo);

    $draw = (int)($_GET['draw'] ?? 0);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 25);
    if ($length <= 0) {
        $length = 25;
    }
    if ($length > 2000) {
        $length = 2000;
    }

    $statusFilter = strtolower(trim((string)($_GET['status'] ?? '')));
    if (!in_array($statusFilter, ['', 'queued', 'sent', 'failed', 'skipped'], true)) {
        $statusFilter = '';
    }

    $columns = [
        'id',
        'context',
        'template_code',
        'recipient_email',
        'subject',
        'status',
        'related_table',
        'queued_at',
        'sent_at',
        'provider',
    ];

    $orderColIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDirRaw = strtolower((string)($_GET['order'][0]['dir'] ?? 'desc'));
    $orderDir = ($orderDirRaw === 'asc') ? 'ASC' : 'DESC';
    $orderCol = $columns[$orderColIndex] ?? 'id';

    $whereParts = [];
    $params = [];

    if ($statusFilter !== '') {
        $whereParts[] = 'status = :status_filter';
        $params[':status_filter'] = $statusFilter;
    }

    $searchValue = trim((string)($_GET['search']['value'] ?? ''));
    if ($searchValue !== '') {
        $whereParts[] = "(
            CAST(id AS CHAR) LIKE :q OR
            public_id LIKE :q OR
            context LIKE :q OR
            template_code LIKE :q OR
            recipient_email LIKE :q OR
            recipient_name LIKE :q OR
            subject LIKE :q OR
            status LIKE :q OR
            provider LIKE :q OR
            provider_message_id LIKE :q OR
            error_message LIKE :q OR
            related_table LIKE :q OR
            CAST(related_id AS CHAR) LIKE :q OR
            CAST(queued_at AS CHAR) LIKE :q OR
            CAST(sent_at AS CHAR) LIKE :q OR
            CAST(failed_at AS CHAR) LIKE :q
        )";
        $params[':q'] = '%' . $searchValue . '%';
    }

    $columnMap = [
        0 => 'CAST(id AS CHAR)',
        1 => 'context',
        2 => 'template_code',
        3 => "CONCAT(IFNULL(recipient_email, ''), ' ', IFNULL(recipient_name, ''))",
        4 => 'subject',
        5 => 'status',
        6 => "CONCAT(IFNULL(related_table, ''), ' ', IFNULL(related_id, ''))",
        7 => 'CAST(queued_at AS CHAR)',
        8 => 'CAST(sent_at AS CHAR)',
        9 => "CONCAT(IFNULL(provider, ''), ' ', IFNULL(provider_message_id, ''), ' ', IFNULL(error_message, ''))",
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
    $recordsTotal = (int)$pdo->query('SELECT COUNT(*) FROM log_emails')->fetchColumn();

    if ($where === '') {
        $recordsFiltered = $recordsTotal;
    } else {
        $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM log_emails {$where}");
        foreach ($params as $key => $value) {
            $stmtCnt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtCnt->execute();
        $recordsFiltered = (int)$stmtCnt->fetchColumn();
    }

    $sql = "SELECT
            id,
            public_id,
            context,
            template_code,
            subject,
            recipient_email,
            recipient_name,
            related_table,
            related_id,
            status,
            provider,
            provider_message_id,
            error_message,
            queued_at,
            sent_at,
            failed_at
        FROM log_emails
        {$where}
        ORDER BY {$orderCol} {$orderDir}
        LIMIT :len OFFSET :start";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':len', $length, PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recipient = (string)($row['recipient_email'] ?? '');
        $recipientName = (string)($row['recipient_name'] ?? '');
        $recipientHtml = '<div class="fw-semibold email-log-text" title="' . email_log_dt_e($recipient) . '">' . email_log_dt_e($recipient) . '</div>';
        if ($recipientName !== '') {
            $recipientHtml .= '<div class="small text-muted email-log-text" title="' . email_log_dt_e($recipientName) . '">' . email_log_dt_e($recipientName) . '</div>';
        }

        $related = trim((string)($row['related_table'] ?? '') . ' #' . (string)($row['related_id'] ?? ''), ' #');
        $relatedHtml = $related !== '' ? '<span class="email-log-text" title="' . email_log_dt_e($related) . '">' . email_log_dt_e($related) . '</span>' : '<span class="text-muted">-</span>';

        $providerBits = array_filter([
            (string)($row['provider'] ?? ''),
            (string)($row['provider_message_id'] ?? ''),
        ], static fn(string $value): bool => $value !== '');
        $providerText = implode(' / ', $providerBits);
        $error = trim((string)($row['error_message'] ?? ''));
        $detail = trim("public_id: " . (string)($row['public_id'] ?? '') . "\n" .
            "provider: " . $providerText . "\n" .
            "error: " . $error);

        $providerHtml = $providerText !== ''
            ? '<div class="email-log-text" title="' . email_log_dt_e($providerText) . '">' . email_log_dt_e($providerText) . '</div>'
            : '<span class="text-muted">-</span>';
        if ($error !== '') {
            $providerHtml .= '<div><a href="#" class="small email-log-detail-btn" data-detail="' . email_log_dt_e($detail) . '">chyba/detail</a></div>';
        }

        $data[] = [
            (int)($row['id'] ?? 0),
            '<span class="email-log-text" title="' . email_log_dt_e($row['context'] ?? '') . '">' . email_log_dt_e($row['context'] ?? '') . '</span>',
            '<span class="email-log-text" title="' . email_log_dt_e($row['template_code'] ?? '') . '">' . email_log_dt_e($row['template_code'] ?? '-') . '</span>',
            $recipientHtml,
            '<span class="email-log-text" title="' . email_log_dt_e($row['subject'] ?? '') . '">' . email_log_dt_e($row['subject'] ?? '') . '</span>',
            email_log_dt_badge((string)($row['status'] ?? '')),
            $relatedHtml,
            email_log_dt_e($row['queued_at'] ?? ''),
            email_log_dt_e($row['sent_at'] ?? ''),
            $providerHtml,
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
