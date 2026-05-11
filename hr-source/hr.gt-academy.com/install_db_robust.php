<?php
require_once 'inc/config.php';

try {
    echo "Reading schema files...\n";
    $sql = file_get_contents('sql/policy_system_schema.sql');
    $sql .= "\n" . file_get_contents('sql/workflow_system_schema.sql');
    
    echo "Connecting to database...\n";
    // Enable multi-statement execution
    $connect_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    
    // Split by semicolon to execute statement by statement for better error tracking
    // This regex splits by ; but ignores ; inside quotes (basic implementation)
    // For this specific file, simple splitting might be safer if we assume standard formatting
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Found " . count($statements) . " statements.\n";
    
    foreach ($statements as $i => $statement) {
        if (empty($statement)) continue;
        
        try {
            $connect_pdo->exec($statement);
            echo "Executed statement " . ($i + 1) . "\n";
        } catch (PDOException $e) {
            // Ignore "Table already exists" errors or similar minor warnings if we want to proceed
            // But for now, let's report everything
            echo "Error in statement " . ($i + 1) . ": " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
    
    echo "Database schema import process completed.\n";
    
    // Verify tables
    $tables = [
        'company_policies', 'leave_policies', 'employee_leave_balances', 'leave_accrual_log',
        'violation_types', 'employee_violations', 'violation_escalation_rules', 'promotion_policies',
        'promotion_requests', 'external_tasks', 'employee_presence', 'org_structure',
        'fiscal_year_settings', 'policy_audit_log'
    ];
    
    echo "\nVerifying tables:\n";
    foreach ($tables as $table) {
        try {
            $stmt = $connect_pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "✅ Table '$table' exists\n";
        } catch (Exception $e) {
            echo "❌ Table '$table' MISSING: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
