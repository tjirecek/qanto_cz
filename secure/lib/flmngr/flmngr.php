<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../functions/bootstrap.php';
require_once __DIR__ . '/../../../config.php';

if (!admin_session_is_logged()) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/vendor/autoload.php';

use EdSDK\FlmngrServer\FlmngrServer;

FlmngrServer::flmngrRequest(
    array(
        'dirFiles' => dirname(__DIR__, 3) . '/media/library'
    )
);
