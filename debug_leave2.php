<?php
/**
 * Capture errors from leaveRequest-add.php execution
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Buffer all output
ob_start();

// Set up session
session_start();
$_SESSION['user'] = ['id' => 1, 'UserID' => 1, 'FirstName' => 'Admin', 'LastName' => 'Test'];

try {
    include __DIR__ . '/leaveRequest-add.php';
} catch (Throwable $e) {
    echo "\n\n=== FATAL ERROR CAUGHT ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

if (empty(trim($output))) {
    echo "=== OUTPUT IS EMPTY ===\n";
    echo "The page produced no output at all.\n";
} else {
    echo "=== PAGE OUTPUT (first 3000 chars) ===\n";
    echo substr($output, 0, 3000);
}
