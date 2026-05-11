<?php
/**
 * Vision HR - Approval Controller
 * Manager approvals for leaves, advances, resignations, orders
 */

class ApprovalController
{
    private static array $typeConfig = [
        'leave' => [
            'table'      => 'tblleaverequest',
            'id_col'     => 'Id',
            'user_col'   => 'UserID',
            'status_col' => 'status',
            'label'      => 'إجازة',
        ],
        'advance' => [
            'table'      => 'tblempadvances',
            'id_col'     => 'Id',
            'user_col'   => 'UserID',
            'status_col' => 'Status',
            'label'      => 'سلفة',
        ],
        'resignation' => [
            'table'      => 'tblresignation',
            'id_col'     => 'Id',
            'user_col'   => 'UserID',
            'status_col' => 'Status',
            'label'      => 'استقالة',
        ],
        'order' => [
            'table'      => 'emp_order',
            'id_col'     => 'Id',
            'user_col'   => 'UserID',
            'status_col' => 'Status',
            'label'      => 'أمر',
        ],
        'finger_forget' => [
            'table'      => 'order_finger_add',
            'id_col'     => 'Id',
            'user_col'   => 'UserID',
            'status_col' => 'status',
            'label'      => 'نسيان بصمة',
        ],
    ];

    /**
     * GET /approvals/pending
     * Get all pending requests for the manager
     */
    public static function pending(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        // Must be manager or admin
        if (empty($apiUser['is_admin'])) {
            requireManager($apiUser);
        }

        $type = $_GET['type'] ?? null; // filter by type
        $results = [];

        // Get subordinate IDs
        $subordinateIds = self::getSubordinateIds($apiUser);

        if (empty($subordinateIds) && empty($apiUser['is_admin'])) {
            Response::success([]);
            return;
        }

        // Pending leaves
        if (!$type || $type === 'leave') {
            $results['leaves'] = self::getPendingByType('leave', $subordinateIds, $apiUser);
        }

        // Pending advances
        if (!$type || $type === 'advance') {
            $results['advances'] = self::getPendingByType('advance', $subordinateIds, $apiUser);
        }

        // Pending resignations
        if (!$type || $type === 'resignation') {
            $results['resignations'] = self::getPendingByType('resignation', $subordinateIds, $apiUser);
        }

        // Pending orders
        if (!$type || $type === 'order') {
            $results['orders'] = self::getPendingByType('order', $subordinateIds, $apiUser);
        }

        // Pending finger forget
        if (!$type || $type === 'finger_forget') {
            $results['finger_forget'] = self::getPendingByType('finger_forget', $subordinateIds, $apiUser);
        }

        // Count totals
        $totalPending = 0;
        foreach ($results as $items) {
            $totalPending += count($items);
        }

        Response::success([
            'total_pending' => $totalPending,
            'items'         => $results,
        ]);
    }

    /**
     * POST /approvals/:type/:id/approve
     * Approve a request
     */
    public static function approve(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $type = $params['type'] ?? '';
        $id = (int) ($params['id'] ?? 0);

        if (!isset(self::$typeConfig[$type])) {
            Response::notFound('نوع الطلب غير صالح');
        }

        $config = self::$typeConfig[$type];

        // Must be manager or admin
        if (empty($apiUser['is_admin'])) {
            requireManager($apiUser);
        }

        // Get the request
        $stm = $connect_pdo->prepare(
            "SELECT * FROM {$config['table']} WHERE {$config['id_col']} = :id LIMIT 1"
        );
        $stm->execute([':id' => $id]);
        $record = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            Response::notFound('الطلب غير موجود');
        }

        // Check authorization
        if (empty($apiUser['is_admin'])) {
            $subordinateIds = self::getSubordinateIds($apiUser);
            if (!in_array((int) $record[$config['user_col']], $subordinateIds)) {
                Response::forbidden('ليس لديك صلاحية الموافقة على هذا الطلب');
            }
        }

        // Check if already processed
        $currentStatus = $record[$config['status_col']];
        if ($currentStatus !== null && (int) $currentStatus !== 0) {
            Response::error('تم معالجة هذا الطلب مسبقاً', 409);
        }

        $body = getRequestBody();
        $notes = $body['notes'] ?? null;

        // Update status to approved (1)
        $updateFields = "{$config['status_col']} = 1, approved_by = :approver, LastUpdateDate = NOW()";
        
        // Some tables don't have approved_by or LastUpdateDate
        $stm2 = $connect_pdo->prepare(
            "UPDATE {$config['table']} SET {$config['status_col']} = 1 WHERE {$config['id_col']} = :id"
        );
        $stm2->execute([':id' => $id]);

        // Try to set approved_by (may not exist on all tables)
        try {
            $connect_pdo->prepare(
                "UPDATE {$config['table']} SET approved_by = :approver WHERE {$config['id_col']} = :id"
            )->execute([':approver' => $apiUser['id'], ':id' => $id]);
        } catch (PDOException $e) {
            // Column may not exist, ignore
        }

        // Try to set LastUpdateDate
        try {
            $connect_pdo->prepare(
                "UPDATE {$config['table']} SET LastUpdateDate = NOW() WHERE {$config['id_col']} = :id"
            )->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Column may not exist, ignore
        }

        $auditLog->logApproval($apiUser['id'], $config['table'], $id, 'approve');

        // Notify the employee
        $employeeId = (int) $record[$config['user_col']];
        $connect_pdo->prepare(
            "INSERT INTO notifications (user_id, title, body, type, entity_type, entity_id, created_at)
             VALUES (:uid, :title, :body, 'approval', :ref_table, :ref_id, NOW())"
        )->execute([
            ':uid'       => $employeeId,
            ':title'     => 'تمت الموافقة على طلبك',
            ':body'      => 'تمت الموافقة على طلب ' . $config['label'] . ' الخاص بك',
            ':ref_table' => $config['table'],
            ':ref_id'    => $id,
        ]);

        Response::success(['id' => $id, 'status' => 'approved'], 'تمت الموافقة بنجاح');
    }

    /**
     * POST /approvals/:type/:id/reject
     * Reject a request
     */
    public static function reject(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $type = $params['type'] ?? '';
        $id = (int) ($params['id'] ?? 0);

        if (!isset(self::$typeConfig[$type])) {
            Response::notFound('نوع الطلب غير صالح');
        }

        $config = self::$typeConfig[$type];

        if (empty($apiUser['is_admin'])) {
            requireManager($apiUser);
        }

        $stm = $connect_pdo->prepare(
            "SELECT * FROM {$config['table']} WHERE {$config['id_col']} = :id LIMIT 1"
        );
        $stm->execute([':id' => $id]);
        $record = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            Response::notFound('الطلب غير موجود');
        }

        if (empty($apiUser['is_admin'])) {
            $subordinateIds = self::getSubordinateIds($apiUser);
            if (!in_array((int) $record[$config['user_col']], $subordinateIds)) {
                Response::forbidden('ليس لديك صلاحية رفض هذا الطلب');
            }
        }

        $currentStatus = $record[$config['status_col']];
        if ($currentStatus !== null && (int) $currentStatus !== 0) {
            Response::error('تم معالجة هذا الطلب مسبقاً', 409);
        }

        // Update status to rejected (2)
        $stm2 = $connect_pdo->prepare(
            "UPDATE {$config['table']} SET {$config['status_col']} = 2 WHERE {$config['id_col']} = :id"
        );
        $stm2->execute([':id' => $id]);

        try {
            $connect_pdo->prepare(
                "UPDATE {$config['table']} SET approved_by = :approver WHERE {$config['id_col']} = :id"
            )->execute([':approver' => $apiUser['id'], ':id' => $id]);
        } catch (PDOException $e) {}

        try {
            $connect_pdo->prepare(
                "UPDATE {$config['table']} SET LastUpdateDate = NOW() WHERE {$config['id_col']} = :id"
            )->execute([':id' => $id]);
        } catch (PDOException $e) {}

        $auditLog->logApproval($apiUser['id'], $config['table'], $id, 'reject');

        // Notify employee
        $employeeId = (int) $record[$config['user_col']];
        $body = getRequestBody();
        $rejectReason = $body['reason'] ?? '';

        $connect_pdo->prepare(
            "INSERT INTO notifications (user_id, title, body, type, entity_type, entity_id, created_at)
             VALUES (:uid, :title, :body, 'rejection', :ref_table, :ref_id, NOW())"
        )->execute([
            ':uid'       => $employeeId,
            ':title'     => 'تم رفض طلبك',
            ':body'      => 'تم رفض طلب ' . $config['label'] . ' الخاص بك' . ($rejectReason ? '. السبب: ' . $rejectReason : ''),
            ':ref_table' => $config['table'],
            ':ref_id'    => $id,
        ]);

        Response::success(['id' => $id, 'status' => 'rejected'], 'تم الرفض');
    }

    // ---- Private helpers ----

    /**
     * Get IDs of subordinates for the current manager
     */
    private static function getSubordinateIds(array $apiUser): array
    {
        global $connect_pdo;

        if (!empty($apiUser['is_admin'])) {
            // Admin sees all
            $stm = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE IsDisabled IS NULL");
            $stm->execute();
        } else {
            $stm = $connect_pdo->prepare(
                "SELECT UserID FROM tblusers WHERE manager = :uid AND IsDisabled IS NULL"
            );
            $stm->execute([':uid' => $apiUser['id']]);
        }

        return array_column($stm->fetchAll(PDO::FETCH_ASSOC), 'UserID');
    }

    /**
     * Get pending items for a specific type
     */
    private static function getPendingByType(string $type, array $subordinateIds, array $apiUser): array
    {
        global $connect_pdo;

        $config = self::$typeConfig[$type];

        if (empty($subordinateIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($subordinateIds), '?'));

        $sql = "SELECT t.*, u.FirstName, u.LastName, u.Photo
                FROM {$config['table']} t
                JOIN tblusers u ON u.UserID = t.{$config['user_col']}
                WHERE t.{$config['user_col']} IN ($placeholders)
                  AND (t.{$config['status_col']} IS NULL OR t.{$config['status_col']} = 0)
                ORDER BY t.{$config['id_col']} DESC
                LIMIT 50";

        $stm = $connect_pdo->prepare($sql);
        $stm->execute(array_map('intval', $subordinateIds));
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) use ($config, $type) {
            $item = [
                'id'            => (int) $r[$config['id_col']],
                'type'          => $type,
                'type_label'    => $config['label'],
                'employee_id'   => (int) $r[$config['user_col']],
                'employee_name' => trim($r['FirstName'] . ' ' . ($r['LastName'] ?? '')),
                'employee_photo'=> $r['Photo'],
            ];

            // Add type-specific fields
            if ($type === 'leave') {
                $item['start_date'] = $r['leave_start_date'] ?? null;
                $item['end_date'] = $r['leave_end_date'] ?? null;
                $item['days'] = (int) ($r['day_leave'] ?? 0);
                $item['reason'] = $r['description'] ?? null;
            } elseif ($type === 'advance') {
                $item['amount'] = (float) ($r['Amount'] ?? 0);
                $item['due_date'] = $r['DueDate'] ?? null;
                $item['description'] = $r['description'] ?? null;
            } elseif ($type === 'resignation') {
                $item['due_date'] = $r['DueDate'] ?? null;
                $item['reason'] = $r['Reason'] ?? null;
            } elseif ($type === 'order') {
                $item['title'] = $r['title'] ?? null;
                $item['description'] = $r['description'] ?? null;
            } elseif ($type === 'finger_forget') {
                $item['date'] = $r['date'] ?? null;
                $item['reason'] = $r['description'] ?? null;
            }

            return $item;
        }, $rows);
    }
}
