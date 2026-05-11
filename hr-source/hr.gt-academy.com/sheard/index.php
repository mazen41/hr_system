<?php
/**
 * Shared AJAX Handler (stub)
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
    case 'net-profit':
        echo json_encode([
            'data' => [
                'label' => ['الإيرادات', 'المصروفات'],
                'values' => [0, 0],
                'colors' => ['#28a745', '#dc3545']
            ],
            'docs' => [
                'in' => ['total' => 0],
                'out' => ['total' => 0]
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['result' => false, 'msg' => 'Action not found'], JSON_UNESCAPED_UNICODE);
        break;
}
