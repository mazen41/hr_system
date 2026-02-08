<?php
// Vision HR - Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vision_hr');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_URL', '/HR/');
define('SITE_TITLE', 'Vision HR');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');

// Timezone
date_default_timezone_set('Asia/Riyadh');

// Force UTF-8 everywhere
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=utf-8');

// PDO Connection
try {
    $connect_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Global date variables used throughout the system
$today_date = date('Y-m-d');
$now_date = date('Y-m-d H:i:s');
