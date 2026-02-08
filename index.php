<?php
/**
 * Vision HR - Main Router
 * Bootstraps core dependencies and routes to the correct page
 */
session_start();

// Core config and DB
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';
require_once __DIR__ . '/inc/functions.php';

// Initialize User
$User = new User($connect_pdo);

// Determine page
$page = 'login-sys'; // default
if (isset($_GET['page']) && !empty($_GET['page'])) {
    $page = basename($_GET['page']); // sanitize
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . SITE_URL . 'login-sys');
    exit;
}

// Global variables expected by all pages
$user = $_SESSION['user_id'] ?? null;
$branch = $_SESSION['branch'] ?? null;
$coreDir = __DIR__ . '/';

// Public pages (no login required)
$public_pages = ['login-sys'];

// Check authentication for protected pages
if (!in_array($page, $public_pages)) {
    if (empty($_SESSION['user_id'])) {
        echo '<script> location.replace("login-sys"); </script>';
        die();
    }
    $User->loadFromSession();
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
