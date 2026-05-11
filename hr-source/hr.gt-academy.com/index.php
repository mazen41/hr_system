<?php
/**
 * Vision HR - Main Router
 * Bootstraps core dependencies and routes to the correct page
 */

// Secure session cookie settings for HTTPS - only if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Core config and DB (robust path handling)
$__inc1 = __DIR__ . '/inc/';
$__inc2 = __DIR__ . '/../inc/';

// Prefer exact file paths under current dir, then fallback to parent
$__cfg1 = $__inc1 . 'config.php';
$__cfg2 = $__inc2 . 'config.php';

if (file_exists($__cfg1)) {
    require_once $__cfg1;
    require_once $__inc1 . 'User.php';
    require_once $__inc1 . 'AuditLog.php';
    require_once $__inc1 . 'functions.php';
} elseif (file_exists($__cfg2)) {
    require_once $__cfg2;
    require_once $__inc2 . 'User.php';
    require_once $__inc2 . 'AuditLog.php';
    require_once $__inc2 . 'functions.php';
} else {
    throw new Exception('Inc files not found. Tried: ' . $__cfg1 . ' and ' . $__cfg2);
}

// Initialize User & Audit
$User  = new User($connect_pdo);
$Audit = new AuditLog($connect_pdo);

// Determine page
$page = 'login-sys'; // default

// 1. Check $_GET['page']
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $page = basename($_GET['page']); // sanitize
}
// 2. Check URL path (for SEO URLs like /employer-dashboard)
else {
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = trim($request_uri, '/');
    if (!empty($path)) {
        // Special handling for hr-app AJAX endpoints
        if (strpos($path, 'hr-app/') === 0) {
            $endpoint = substr($path, 8); // Remove 'hr-app/' prefix
            // Allow only safe alphanumeric+dash+underscore endpoint names
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $endpoint)) {
                $filePath = __DIR__ . '/hr-app/' . $endpoint . '.php';
                if (file_exists($filePath)) {
                    include $filePath;
                    exit;
                }
            }
        }
        // Only allow alphanumeric and dashes/underscores to prevent directory traversal
        elseif (preg_match('/^[a-zA-Z0-9_-]+$/', $path)) {
             // Check if corresponding PHP file exists
             if (file_exists(__DIR__ . '/' . $path . '.php')) {
                 $page = $path;
             }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $Audit->log(AuditLog::LOGOUT, AuditLog::ENTITY_SESSION, $_SESSION['user_id'] ?? null, 'تسجيل خروج');
    session_destroy();
    header('Location: ' . SITE_URL . 'login-sys');
    exit;
}

// Global variables expected by all pages
$user = $_SESSION['user_id'] ?? null;
$branch = $_SESSION['branch'] ?? null;
$coreDir = __DIR__ . '/';

// Public pages (no login required)
$public_pages = [
    'login-sys',
    'forget-paswd',
    'reset-password',
];

// Check authentication for protected pages
if (!in_array($page, $public_pages)) {
    if (empty($_SESSION['user_id'])) {
        echo '<script> location.replace("login-sys"); </script>';
        die();
    }
    $User->loadFromSession();
} 
// Redirect logged-in users away from login page
else if ($page === 'login-sys' && !empty($_SESSION['user_id'])) {
    $User->loadFromSession();
    $home = $_SESSION['user']['home_page'] ?? 'employer-dashboard';
    header("Location: " . SITE_URL . $home);
    exit;
}

// Check if the page file exists
$page_file = __DIR__ . '/' . $page . '.php';
if (!file_exists($page_file)) {
    http_response_code(404);
    echo '<h1>404 - الصفحة غير موجودة</h1>';
    echo '<p>الصفحة المطلوبة <strong>' . htmlspecialchars($page) . '</strong> غير موجودة.</p>';
    echo '<a href="login-sys">العودة لتسجيل الدخول</a>';
    die();
}

// Include the page
include $page_file;
