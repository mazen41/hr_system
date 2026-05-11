<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/classes/NotificationService.php';

$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? $_SESSION['user']['UserID'] ?? null;
if (!$userId) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => false, 'msg' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

session_write_close();

ignore_user_abort(true);
set_time_limit(0);
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');

while (ob_get_level() > 0) {
    @ob_end_flush();
}

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

$service = new NotificationService($connect_pdo);
$lastSignature = '';
$startedAt = time();

echo "retry: 5000\n\n";
@ob_flush();
flush();

do {
    $summary = $service->getUnreadSummary((int)$userId, 5);
    $notifications = $summary['items'] ?? [];
    $count = (int)($summary['count'] ?? 0);

    $signaturePayload = [
        'count' => $count,
        'items' => array_map(static function (array $item): array {
            return [
                'id' => (int)($item['id'] ?? 0),
                'is_read' => (int)($item['is_read'] ?? 0),
                'created_at' => (string)($item['created_at'] ?? ''),
            ];
        }, $notifications),
    ];
    $signature = md5(json_encode($signaturePayload));

    if ($signature !== $lastSignature) {
        $payload = [
            'count' => $count,
            'notifications' => $notifications,
            'timestamp' => date('c'),
        ];

        echo "event: notifications\n";
        echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        $lastSignature = $signature;
    } else {
        echo "event: ping\n";
        echo 'data: {"ok":true}' . "\n\n";
    }

    @ob_flush();
    flush();

    if (connection_aborted()) {
        break;
    }

    sleep(5);
} while ((time() - $startedAt) < 55);
