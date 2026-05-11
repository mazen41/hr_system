<?php
require_once __DIR__ . '/inc/config.php';

echo "=== Employee Count Audit ===\n\n";

// Dashboard count (active employees)
$dashboardCount = $connect_pdo->query("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0)")->fetchColumn();
echo "Dashboard Count (active, isemp=1, not disabled): $dashboardCount\n";

// List count (all with isemp=1)
$listCount = $connect_pdo->query("SELECT COUNT(*) FROM tblusers WHERE isemp = 1")->fetchColumn();
echo "Total with isemp=1: $listCount\n";

// Disabled employees
$disabledCount = $connect_pdo->query("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND IsDisabled = 1")->fetchColumn();
echo "Disabled employees: $disabledCount\n";

// List all employees
echo "\n=== All Employees ===\n";
$stmt = $connect_pdo->query("SELECT UserID, FirstName, LastName, isemp, IsDisabled FROM tblusers WHERE isemp = 1 ORDER BY UserID");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $status = $r['IsDisabled'] ? '[DISABLED]' : '[ACTIVE]';
    echo "ID: {$r['UserID']} | {$r['FirstName']} {$r['LastName']} $status\n";
}
