<?php
/**
 * Vision HR - Audit Middleware
 * Automatically logs API actions
 */

/**
 * Log an API action to the audit log
 */
function auditMiddleware(
    array $apiUser,
    string $action,
    string $tableName = '',
    ?int $recordId = null,
    ?array $oldData = null,
    ?array $newData = null,
    ?string $notes = null
): void {
    global $auditLog;
    $auditLog->log(
        $apiUser['id'] ?? null,
        $action,
        $tableName,
        $recordId,
        $oldData,
        $newData,
        $notes
    );
}
