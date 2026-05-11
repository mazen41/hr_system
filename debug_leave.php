<?php
/**
 * Debug script for leaveRequest-add.php
 * This will help identify why the page is blank
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== Debug Output ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "\n";

// Try to include the file and catch any errors
try {
    ob_start();
    
    // Set up minimal session if needed
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Mock user data for testing
    if (!isset($_SESSION['user'])) {
        $_SESSION['user'] = ['id' => 1, 'UserID' => 1];
    }
    
    echo "Session initialized...\n";
    
    // Try to include config
    if (file_exists(__DIR__ . '/inc/config.php')) {
        require_once __DIR__ . '/inc/config.php';
        echo "Config loaded successfully\n";
        echo "Database connection: " . (isset($connect_pdo) ? "YES" : "NO") . "\n";
    } else {
        echo "Config file NOT found\n";
    }
    
    // Check for errors in output buffer
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Output buffer content:\n$output\n";
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
