<?php
/**
 * Vision HR - API Bootstrap
 * Loads core dependencies WITHOUT HTML output
 * This is the entry point for all API requests
 */

// Prevent any HTML output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering to catch any stray output
ob_start();

// Timezone
date_default_timezone_set('Asia/Riyadh');

// UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');

// JSON content type
header('Content-Type: application/json; charset=utf-8');

// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
require_once __DIR__ . '/config.php';

if (in_array($origin, API_CORS_ORIGINS)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} elseif (empty($origin)) {
    // Same-origin requests (no Origin header)
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Load core config and DB connection
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../inc/User.php';
require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/../../inc/MailService.php';

// Load shared modules
require_once __DIR__ . '/../../shared/Security.php';
require_once __DIR__ . '/../../shared/RateLimiter.php';
require_once __DIR__ . '/../../shared/AuditLog.php';
require_once __DIR__ . '/../../shared/QRCodeService.php';
require_once __DIR__ . '/../../shared/NotificationService.php';
require_once __DIR__ . '/../../shared/AntiSpoof.php';

// Load API helpers
require_once __DIR__ . '/helpers/JWTHelper.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';

// Load middleware
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/rbac.php';
require_once __DIR__ . '/middleware/audit.php';
require_once __DIR__ . '/middleware/ratelimit.php';

// Initialize core objects
$User = new User($connect_pdo);
$jwt = new JWTHelper(JWT_SECRET, JWT_ACCESS_TTL, JWT_REFRESH_TTL);
$auditLog = new AuditLog($connect_pdo);
$qrService = new QRCodeService($connect_pdo);
$notifService = new NotificationService($connect_pdo);
$antiSpoof = new AntiSpoof($connect_pdo);

// Global date variables
$today_date = date('Y-m-d');
$now_date = date('Y-m-d H:i:s');

// Discard any buffered output from includes
ob_end_clean();

// Get JSON request body (cached)
function getRequestBody(): array
{
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    }
    return $body;
}

// Get request method
function getMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

// Note: requireOwnerOrAdmin, requireManager, requireBranchAccess are defined in middleware/rbac.php
// Note: recordFailedLogin, clearLoginRateLimit are defined in middleware/ratelimit.php
