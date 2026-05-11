<?php
/**
 * Capture full output from leaveRequest-add.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();
$_SESSION['user'] = ['id' => 1, 'UserID' => 1, 'FirstName' => 'Admin', 'LastName' => 'Test'];

// Suppress warnings for cleaner output
error_reporting(E_ERROR | E_PARSE);

include __DIR__ . '/leaveRequest-add.php';

$output = ob_get_clean();

// Check for specific patterns
if (strpos($output, '<form') !== false) {
    echo "✓ Form tag found\n";
} else {
    echo "✗ Form tag NOT found\n";
}

if (strpos($output, 'emp_id') !== false) {
    echo "✓ Employee dropdown (emp_id) found\n";
} else {
    echo "✗ Employee dropdown NOT found\n";
}

if (strpos($output, '</html>') !== false) {
    echo "✓ Complete HTML document\n";
} else {
    echo "✗ Document is INCOMPLETE\n";
}

echo "\nTotal output length: " . strlen($output) . " chars\n";

// Show the end of the output
echo "\n=== LAST 500 CHARACTERS ===\n";
echo substr($output, -500);
