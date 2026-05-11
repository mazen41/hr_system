<?php
/**
 * Vision HR - Audit Log
 * Tracks all CRUD operations for compliance and security
 */

class AuditLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Log an action
     *
     * @param int|null    $userId    The user performing the action
     * @param string      $action    Action type: create, update, delete, login, logout, view, export, approve, reject
     * @param string      $tableName The affected table
     * @param int|null    $recordId  The affected record ID
     * @param array|null  $oldData   Previous data (for updates/deletes)
     * @param array|null  $newData   New data (for creates/updates)
     * @param string|null $notes     Additional notes
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType = '',
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $description = null
    ): bool {
        try {
            // Resolve user name
            $userName = null;
            if ($userId) {
                $stmU = $this->pdo->prepare("SELECT CONCAT(FirstName, ' ', IFNULL(LastName,'')) FROM tblusers WHERE UserID = :uid LIMIT 1");
                $stmU->execute([':uid' => $userId]);
                $userName = $stmU->fetchColumn() ?: null;
            }

            $stm = $this->pdo->prepare(
                "INSERT INTO audit_log 
                    (user_id, user_name, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, description, created_at)
                 VALUES 
                    (:user_id, :user_name, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, :description, NOW())"
            );

            $stm->execute([
                ':user_id'     => $userId,
                ':user_name'   => $userName,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':old_values'  => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                ':new_values'  => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                ':ip_address'  => self::getClientIp(),
                ':user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':description' => $description,
            ]);

            return true;
        } catch (PDOException $e) {
            error_log('AuditLog Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a login attempt
     */
    public function logLogin(?int $userId, bool $success, string $email = ''): bool
    {
        return $this->log(
            $userId,
            $success ? 'login' : 'login_failed',
            'tblusers',
            $userId,
            null,
            null,
            $success ? null : 'Failed login for: ' . $email
        );
    }

    /**
     * Log a logout
     */
    public function logLogout(int $userId): bool
    {
        return $this->log($userId, 'logout', 'tblusers', $userId);
    }

    /**
     * Log a create operation
     */
    public function logCreate(int $userId, string $entityType, int $entityId, array $data): bool
    {
        return $this->log($userId, 'create', $entityType, $entityId, null, $data);
    }

    /**
     * Log an update operation
     */
    public function logUpdate(int $userId, string $entityType, int $entityId, array $oldData, array $newData): bool
    {
        // Only log changed fields
        $changes = [];
        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key]) || $oldData[$key] !== $value) {
                $changes[$key] = $value;
            }
        }

        if (empty($changes)) {
            return true; // No actual changes
        }

        $oldFiltered = [];
        foreach ($changes as $key => $value) {
            $oldFiltered[$key] = $oldData[$key] ?? null;
        }

        return $this->log($userId, 'update', $entityType, $entityId, $oldFiltered, $changes);
    }

    /**
     * Log a delete operation
     */
    public function logDelete(int $userId, string $entityType, int $entityId, ?array $oldData = null): bool
    {
        return $this->log($userId, 'delete', $entityType, $entityId, $oldData);
    }

    /**
     * Log an approval action
     */
    public function logApproval(int $userId, string $entityType, int $entityId, string $action = 'approve'): bool
    {
        return $this->log($userId, $action, $entityType, $entityId);
    }

    /**
     * Query audit logs with filters
     */
    public function query(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'a.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'a.entity_type = :entity_type';
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $where[] = 'a.entity_id = :entity_id';
            $params[':entity_id'] = $filters['entity_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT a.*, u.FirstName, u.LastName, u.UserEmail
                FROM audit_log a
                LEFT JOIN tblusers u ON u.UserID = a.user_id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stm = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stm->bindValue($key, $value);
        }
        $stm->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stm->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

/**
 * Global helper function for quick audit logging
 */
function logAction(
    PDO $pdo,
    ?int $userId,
    string $action,
    string $entityType = '',
    ?int $entityId = null,
    ?array $oldData = null,
    ?array $newData = null,
    ?string $description = null
): bool {
    static $auditLog = null;
    if ($auditLog === null) {
        $auditLog = new AuditLog($pdo);
    }
    return $auditLog->log($userId, $action, $entityType, $entityId, $oldData, $newData, $description);
}
