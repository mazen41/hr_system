<?php
/**
 * Fix salary data - find and correct the 846M SAR issue
 */
require_once __DIR__ . '/inc/config.php';

echo "=== Salary Data Audit ===\n\n";

// Find employees with abnormally high salaries
$stmt = $connect_pdo->query("
    SELECT u.UserID, u.FirstName, u.LastName, r.Salary, r.Id as RenewalId
    FROM tblusers u 
    LEFT JOIN tblremewal r ON u.lastversion = r.Id 
    WHERE u.isemp = 1 
    AND r.Salary IS NOT NULL 
    ORDER BY CAST(r.Salary AS DECIMAL(20,2)) DESC 
    LIMIT 20
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Top 20 Salaries:\n";
echo str_repeat('-', 80) . "\n";
foreach ($rows as $r) {
    $salary = number_format((float)$r['Salary'], 2);
    echo "ID: {$r['UserID']} | {$r['FirstName']} {$r['LastName']} | Salary: {$salary}\n";
    
    // Flag abnormal salaries (> 1 million)
    if ((float)$r['Salary'] > 1000000) {
        echo "  ^^^ ABNORMAL - This needs to be fixed!\n";
    }
}

// Calculate total
$totalStmt = $connect_pdo->query("
    SELECT SUM(CAST(r.Salary AS DECIMAL(20,2))) as total
    FROM tblusers u 
    LEFT JOIN tblremewal r ON u.lastversion = r.Id 
    WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
");
$total = $totalStmt->fetch(PDO::FETCH_ASSOC);
echo "\n\nTotal Monthly Salaries: " . number_format((float)$total['total'], 2) . " SAR\n";

// Find the problematic record
$problemStmt = $connect_pdo->query("
    SELECT u.UserID, u.FirstName, u.LastName, r.Salary, r.Id as RenewalId
    FROM tblusers u 
    LEFT JOIN tblremewal r ON u.lastversion = r.Id 
    WHERE u.isemp = 1 
    AND CAST(r.Salary AS DECIMAL(20,2)) > 1000000
");
$problems = $problemStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($problems) > 0) {
    echo "\n\n=== PROBLEMATIC RECORDS ===\n";
    foreach ($problems as $p) {
        echo "UserID: {$p['UserID']}, Name: {$p['FirstName']} {$p['LastName']}, Salary: {$p['Salary']}, RenewalId: {$p['RenewalId']}\n";
        
        // Fix: Set to reasonable salary (e.g., 5000)
        // Uncomment to apply fix:
        // $connect_pdo->prepare("UPDATE tblremewal SET Salary = 5000 WHERE Id = ?")->execute([$p['RenewalId']]);
        // echo "  -> Fixed to 5000 SAR\n";
    }
}
