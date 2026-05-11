<?php
/**
 * AuditLog — Professional audit trail for Vision HR
 * Logs all significant user actions with before/after snapshots.
 */
class AuditLog
{
    private $pdo;

    // Action constants
    const LOGIN        = 'login';
    const LOGOUT       = 'logout';
    const CREATE       = 'create';
    const UPDATE       = 'update';
    const DELETE       = 'delete';
    const APPROVE      = 'approve';
    const REJECT       = 'reject';
    const ATTENDANCE   = 'attendance';
    const IMPORT       = 'import';
    const EXPORT       = 'export';
    const VIEW         = 'view';
    const PERMISSION   = 'permission';

    // Entity constants
    const ENTITY_USER       = 'user';
    const ENTITY_LEAVE      = 'leave';
    const ENTITY_ADVANCE    = 'advance';
    const ENTITY_ORDER      = 'order';
    const ENTITY_ATTENDANCE = 'attendance';
    const ENTITY_BENEFIT    = 'benefit';
    const ENTITY_DEDUCTION  = 'deduction';
    const ENTITY_INCENTIVE  = 'incentive';
    const ENTITY_CONTRACT   = 'contract';
    const ENTITY_SALARY     = 'salary';
    const ENTITY_SETTING    = 'setting';
    const ENTITY_RESIGN     = 'resignation';
    const ENTITY_DISMISS    = 'dismissal';
    const ENTITY_QR_TOKEN   = 'qr_token';
    const ENTITY_SESSION    = 'session';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Log an action
     *
     * @param string      $action      One of the action constants
     * @param string|null $entityType  One of the entity constants
     * @param int|null    $entityId    ID of the affected record
     * @param string|null $description Human-readable description (Arabic)
     * @param array|null  $oldValues   Previous state (for updates)
     * @param array|null  $newValues   New state (for creates/updates)
     * @return int|false  The audit log ID or false on failure
     */
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ) {
        try {
            $userId   = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user']['name'] ?? null;
            $ip       = $this->getClientIp();
            $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_log 
                 (user_id, user_name, ip_address, user_agent, action, entity_type, entity_id, description, old_values, new_values)
                 VALUES (:uid, :uname, :ip, :ua, :action, :etype, :eid, :desc, :old, :new)"
            );

            $stmt->execute([
                ':uid'    => $userId,
                ':uname'  => $userName,
                ':ip'     => $ip,
                ':ua'     => $ua,
                ':action' => $action,
                ':etype'  => $entityType,
                ':eid'    => $entityId,
                ':desc'   => $description,
                ':old'    => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new'    => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log('AuditLog::log failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Shorthand: log a login event
     */
    public function logLogin(int $userId, string $email, bool $success = true)
    {
        $desc = $success
            ? "تسجيل دخول ناجح: {$email}"
            : "محاولة دخول فاشلة: {$email}";

        // Temporarily set session for the log
        $origUid = $_SESSION['user_id'] ?? null;
        if ($success) $_SESSION['user_id'] = $userId;

        $result = $this->log(
            self::LOGIN,
            self::ENTITY_SESSION,
            $userId,
            $desc,
            null,
            ['email' => $email, 'success' => $success]
        );

        if (!$success && $origUid === null) unset($_SESSION['user_id']);
        return $result;
    }

    /**
     * Shorthand: log attendance punch
     */
    public function logAttendance(int $attendanceId, int $empId, int $type, string $method, ?float $lat = null, ?float $lng = null)
    {
        $typeLabel = $type == 1 ? 'تسجيل حضور' : 'تسجيل انصراف';
        $methodLabel = ['gps' => 'GPS', 'qr' => 'QR', 'manual' => 'يدوي', 'import' => 'استيراد'][$method] ?? $method;

        return $this->log(
            self::ATTENDANCE,
            self::ENTITY_ATTENDANCE,
            $attendanceId,
            "{$typeLabel} عبر {$methodLabel}",
            null,
            ['emp_id' => $empId, 'type' => $type, 'method' => $method, 'lat' => $lat, 'lng' => $lng]
        );
    }

    /**
     * Shorthand: log approval/rejection
     */
    public function logApproval(string $entityType, int $entityId, bool $approved, ?string $note = null)
    {
        $action = $approved ? self::APPROVE : self::REJECT;
        $label  = $approved ? 'اعتماد' : 'رفض';
        return $this->log($action, $entityType, $entityId, "{$label} #{$entityId}" . ($note ? " - {$note}" : ''));
    }

    /**
     * Query audit logs with filters
     */
    public function query(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where  = "WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = :uid";
            $params[':uid'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where .= " AND action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where .= " AND entity_type = :etype";
            $params[':etype'] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $where .= " AND entity_id = :eid";
            $params[':eid'] = $filters['entity_id'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND created_at >= :dfrom";
            $params[':dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND created_at <= :dto";
            $params[':dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where .= " AND (description LIKE :search OR user_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Count
        $cntStmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_log $where");
        $cntStmt->execute($params);
        $total = (int)$cntStmt->fetchColumn();

        // Fetch
        $sql = "SELECT * FROM audit_log $where ORDER BY created_at DESC LIMIT $offset, $limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['total' => $total, 'data' => $rows];
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = explode(',', $_SERVER[$h])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }
}
