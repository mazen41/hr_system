<?php
require_once __DIR__ . '/inc/config.php';

// Fix the abnormal salary (846M -> 5000)
$stmt = $connect_pdo->prepare("UPDATE tblremewal SET Salary = 5000 WHERE Id = 13");
$stmt->execute();

echo "Fixed! Rows affected: " . $stmt->rowCount() . "\n";

// Verify
$check = $connect_pdo->query("SELECT SUM(CAST(r.Salary AS DECIMAL(20,2))) as total FROM tblusers u LEFT JOIN tblremewal r ON u.lastversion = r.Id WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)");
$total = $check->fetch(PDO::FETCH_ASSOC);
echo "New Total Monthly Salaries: " . number_format((float)$total['total'], 2) . " SAR\n";
