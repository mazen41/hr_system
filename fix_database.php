<?php
// Database Fix Script for HR System
// This script adds missing columns to database tables

// Include config to get database credentials
require_once __DIR__ . '/inc/config.php';

echo "<h2>HR System Database Fix</h2>";
echo "<pre>";

try {
    $alterStatements = [
        // Add CreatedBy and CreatedDate to tblemploymenttype
        "ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER BranchID",
        "ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE tblemploymenttype ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
        
        // Add CreatedBy and CreatedDate to tblgroup
        "ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER Description",
        "ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE tblgroup ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
        
        // Add CreatedBy and CreatedDate to tbljobgrade
        "ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER Description",
        "ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE tbljobgrade ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
        
        // Add CreatedBy and CreatedDate to tblsection
        "ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER ParentID",
        "ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE tblsection ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
        
        // Add CreatedBy and CreatedDate to tbljobtitle
        "ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER ParentID",
        "ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE tbljobtitle ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
        
        // Add CreatedBy and CreatedDate to holidays
        "ALTER TABLE holidays ADD COLUMN IF NOT EXISTS CreatedBy int(11) NOT NULL DEFAULT 0 AFTER End_date",
        "ALTER TABLE holidays ADD COLUMN IF NOT EXISTS CreatedDate date NOT NULL DEFAULT CURRENT_DATE AFTER CreatedBy",
        "ALTER TABLE holidays ADD COLUMN IF NOT EXISTS LastUpdateDate timestamp NULL DEFAULT NULL AFTER CreatedDate",
    ];

    $successCount = 0;
    $errorCount = 0;

    foreach ($alterStatements as $sql) {
        try {
            $connect_pdo->exec($sql);
            echo "✓ SUCCESS: " . $sql . "\n";
            $successCount++;
        } catch (PDOException $e) {
            // Check if error is because column already exists (MySQL/MariaDB doesn't support IF NOT EXISTS in ALTER TABLE)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⊘ SKIPPED: Column already exists - " . $sql . "\n";
            } else {
                echo "✗ ERROR: " . $e->getMessage() . " - " . $sql . "\n";
                $errorCount++;
            }
        }
    }

    echo "\n";
    echo "========================================\n";
    echo "Database Fix Complete!\n";
    echo "Success: $successCount\n";
    echo "Errors: $errorCount\n";
    echo "========================================\n";
    echo "\n";
    echo "<a href='index.php'>Return to Home</a>";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}

echo "</pre>";
?>
