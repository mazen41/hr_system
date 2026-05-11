<?php
/**
 * Sales App AJAX Handler (stub for dashboard compatibility)
 */
// Secure session cookie settings for HTTPS - only if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'invs-factors':
        // Weekly invoice factors for dashboard chart
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[] = ['the_date' => $date, 'total_cots' => rand(5, 30), 'total_prices' => rand(1000, 10000)];
        }
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
        break;

    case 'invoices-overview':
        echo json_encode([
            'invs_amount_net' => '0', 'invs_vat_net' => '0', 'invs_cots' => '0',
            'refund_cots_avg' => '0', 'refund_amount_net' => '0'
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'sal-chart':
        echo json_encode([
            'xy' => [], 'yv' => [], 'yvp' => [], 'style' => [],
            'cots' => 0, 'totals' => 0, 'totals_payed' => 0, 'totals_unpayed' => 0,
            'pup' => ['xy' => [], 'yv' => [], 'style' => []]
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['result' => false, 'msg' => 'Action not found'], JSON_UNESCAPED_UNICODE);
        break;
}
