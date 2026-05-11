<?php
/**
 * Workflow Manager - Handles approval workflows for HR actions
 * Supports: Leave requests, Promotions, Violations, Advances, Orders
 */
class WorkflowManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get workflow configuration for an entity type.
     */
    public function getWorkflowConfig($entityType)
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM workflow_configs
            WHERE entity_type = ? AND is_active = 1
        ");
        $stmt->execute([$entityType]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM workflow_steps
                WHERE workflow_id = ?
                ORDER BY step_order
            ");
            $stmt->execute([$config['id']]);
            $config['steps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $config;
    }

    /**
     * Start a workflow for an entity.
     */
    public function startWorkflow($entityType, $entityId, $requesterId, $data = [])
    {
        $config = $this->getWorkflowConfig($entityType);

        if (!$config || empty($config['steps'])) {
            return [
                'success' => true,
                'auto_approved' => true,
                'message' => 'No active workflow found, auto-approved.',
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_instances
                (workflow_id, entity_type, entity_id, requester_id, current_step, status, data, created_at)
            VALUES (?, ?, ?, ?, 1, 'pending', ?, NOW())
        ");
        $stmt->execute([
            $config['id'],
            $entityType,
            $entityId,
            $requesterId,
            json_encode($data),
        ]);
        $instanceId = (int) $this->pdo->lastInsertId();

        $firstStep = $config['steps'][0];
        $approvers = $this->getStepApprovers($firstStep, $requesterId);

        foreach ($approvers as $approverId) {
            $this->createApprovalTask($instanceId, 1, (int) $approverId);
        }

        $this->logWorkflowAction($instanceId, 'started', $requesterId, 'بدء سير العمل');

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'current_step' => 1,
            'approvers' => $approvers,
            'message' => 'Workflow started successfully.',
        ];
    }

    /**
     * Get approvers for a workflow step.
     */
    private function getStepApprovers($step, $requesterId)
    {
        $approvers = [];
        $requesterBranchId = $this->getUserBranchId($requesterId);

        switch ($step['approver_type']) {
            case 'direct_manager':
                $stmt = $this->pdo->prepare("SELECT manager FROM tblusers WHERE UserID = ?");
                $stmt->execute([$requesterId]);
                $manager = $stmt->fetchColumn();
                if (!empty($manager)) {
                    $approvers[] = (int) $manager;
                }
                break;

            case 'hr_manager':
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT u.UserID
                    FROM tblusers u
                    LEFT JOIN tblusergroups g ON g.GroupID = u.UserGroupID
                    WHERE COALESCE(u.IsDisabled, 0) = 0
                      AND u.UserID <> :requester_id
                      AND COALESCE(u.isemp, 0) = 0
                      AND (
                            COALESCE(u.IsSystem, 0) = 1
                            OR COALESCE(g.FullAccess, 0) = 1
                            OR COALESCE(g.IsSystem, 0) = 1
                            OR COALESCE(u.UserGroupID, 0) = 4
                      )
                      AND (
                            :branch_any = 0
                            OR COALESCE(u.IsSystem, 0) = 1
                            OR COALESCE(g.FullAccess, 0) = 1
                            OR COALESCE(g.IsSystem, 0) = 1
                            OR COALESCE(u.BranchID, 0) = :branch_match
                            OR FIND_IN_SET(CAST(:branch_allowed AS CHAR), REPLACE(COALESCE(u.AllowedBranches, ''), ' ', '')) > 0
                      )
                ");
                $stmt->execute([
                    ':requester_id' => $requesterId,
                    ':branch_any' => $requesterBranchId,
                    ':branch_match' => $requesterBranchId,
                    ':branch_allowed' => $requesterBranchId,
                ]);
                $approvers = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                break;

            case 'department_head':
                $stmt = $this->pdo->prepare("
                    SELECT os.manager_id
                    FROM tblusers u
                    JOIN tblremewal r ON r.Id = u.lastversion
                    JOIN org_structure os ON os.section_id = r.SectionID
                    WHERE u.UserID = ? AND os.manager_id IS NOT NULL
                ");
                $stmt->execute([$requesterId]);
                $head = $stmt->fetchColumn();
                if (!empty($head)) {
                    $approvers[] = (int) $head;
                }
                break;

            case 'specific_user':
                if (!empty($step['approver_id'])) {
                    $approvers[] = (int) $step['approver_id'];
                }
                break;

            case 'role':
                if (!empty($step['approver_role'])) {
                    $stmt = $this->pdo->prepare("
                        SELECT u.UserID
                        FROM tblusers u
                        WHERE u.UserGroupID = ? AND COALESCE(u.IsDisabled, 0) = 0
                    ");
                    $stmt->execute([$step['approver_role']]);
                    $approvers = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                }
                break;
        }

        return array_values(array_unique(array_filter($approvers)));
    }

    private function getUserBranchId($userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(r.BranchID, u.BranchID, 0)
            FROM tblusers u
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            WHERE u.UserID = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Create an approval task.
     */
    private function createApprovalTask($instanceId, $stepNumber, $approverId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_approvals
                (instance_id, step_number, approver_id, status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$instanceId, $stepNumber, $approverId]);

        $this->notifyApprover($instanceId, $approverId);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Process an approval action.
     */
    public function processApproval($instanceId, $approverId, $action, $comment = '')
    {
        $stmt = $this->pdo->prepare("
            SELECT wi.*, wc.entity_type, wc.name_ar as workflow_name
            FROM workflow_instances wi
            JOIN workflow_configs wc ON wc.id = wi.workflow_id
            WHERE wi.id = ?
        ");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$instance) {
            return ['success' => false, 'message' => 'سير العمل غير موجود'];
        }

        if ($instance['status'] !== 'pending') {
            return ['success' => false, 'message' => 'سير العمل منتهي بالفعل'];
        }

        $stmt = $this->pdo->prepare("
            SELECT id
            FROM workflow_approvals
            WHERE instance_id = ? AND step_number = ? AND approver_id = ? AND status = 'pending'
        ");
        $stmt->execute([$instanceId, $instance['current_step'], $approverId]);
        $approval = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$approval) {
            return ['success' => false, 'message' => 'ليس لديك صلاحية الموافقة على هذا الطلب'];
        }

        $stmt = $this->pdo->prepare("
            UPDATE workflow_approvals
            SET status = ?, comment = ?, actioned_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$action, $comment, $approval['id']]);

        $actionText = $action === 'approved' ? 'موافقة' : 'رفض';
        $this->logWorkflowAction($instanceId, $action, $approverId, $actionText . ($comment ? ": $comment" : ''));

        if ($action === 'rejected') {
            $this->updateInstanceStatus($instanceId, 'rejected');
            $this->onWorkflowComplete($instance, 'rejected');

            return ['success' => true, 'status' => 'rejected', 'message' => 'تم رفض الطلب'];
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as pending
            FROM workflow_approvals
            WHERE instance_id = ? AND step_number = ? AND status = 'pending'
        ");
        $stmt->execute([$instanceId, $instance['current_step']]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int) ($pending['pending'] ?? 0) > 0) {
            return ['success' => true, 'status' => 'pending', 'message' => 'تمت الموافقة، وفي انتظار موافقات أخرى'];
        }

        $config = $this->getWorkflowConfig($instance['entity_type']);
        if (!$config) {
            return ['success' => false, 'message' => 'فشل تحميل إعدادات سير العمل للخطوة التالية'];
        }

        $nextStepNumber = (int) $instance['current_step'] + 1;

        if ($nextStepNumber > count($config['steps'])) {
            $this->updateInstanceStatus($instanceId, 'approved');
            $this->onWorkflowComplete($instance, 'approved');

            return ['success' => true, 'status' => 'approved', 'message' => 'تمت الموافقة النهائية'];
        }

        $stmt = $this->pdo->prepare("UPDATE workflow_instances SET current_step = ? WHERE id = ?");
        $stmt->execute([$nextStepNumber, $instanceId]);

        $nextStep = $config['steps'][$nextStepNumber - 1];
        $approvers = $this->getStepApprovers($nextStep, $instance['requester_id']);

        foreach ($approvers as $nextApproverId) {
            $this->createApprovalTask($instanceId, $nextStepNumber, (int) $nextApproverId);
        }

        $this->logWorkflowAction($instanceId, 'step_advanced', $approverId, "الانتقال للخطوة $nextStepNumber");

        return [
            'success' => true,
            'status' => 'pending',
            'current_step' => $nextStepNumber,
            'message' => 'تمت الموافقة، وانتقل الطلب إلى الخطوة التالية',
        ];
    }

    /**
     * Update instance status.
     */
    private function updateInstanceStatus($instanceId, $status)
    {
        $stmt = $this->pdo->prepare("
            UPDATE workflow_instances
            SET status = ?, completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $instanceId]);
    }

    /**
     * Handle workflow completion.
     */
    private function onWorkflowComplete($instance, $status)
    {
        $entityType = $instance['entity_type'];
        $entityId = $instance['entity_id'];

        switch ($entityType) {
            case 'leave_request':
                $newStatus = $status === 'approved' ? 1 : 2;
                $stmt = $this->pdo->prepare("UPDATE tblleaverequest SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $entityId]);
                break;

            case 'advance_request':
                $newStatus = $status === 'approved' ? 1 : 2;
                $stmt = $this->pdo->prepare("UPDATE tblempadvances SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $entityId]);
                break;

            case 'promotion_request':
                $newStatus = $status === 'approved' ? 'approved' : 'rejected';
                $stmt = $this->pdo->prepare("UPDATE promotion_requests SET status = ?, decision_date = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $entityId]);
                break;

            case 'violation':
                $newStatus = $status === 'approved' ? 'confirmed' : 'dismissed';
                $stmt = $this->pdo->prepare("UPDATE employee_violations SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $entityId]);
                break;

            case 'order':
                $newStatus = $status === 'approved' ? 1 : 2;
                $stmt = $this->pdo->prepare("UPDATE emp_order SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $entityId]);
                break;
        }

        $this->notifyRequester($instance, $status);
    }

    /**
     * Log workflow action.
     */
    private function logWorkflowAction($instanceId, $action, $userId, $details = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_audit_log
                (instance_id, action, user_id, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$instanceId, $action, $userId, $details]);
    }

    /**
     * Notify approver about pending approval.
     */
    private function notifyApprover($instanceId, $approverId)
    {
        $stmt = $this->pdo->prepare("
            SELECT wi.*, wc.name_ar as workflow_name, u.FirstName, u.LastName
            FROM workflow_instances wi
            JOIN workflow_configs wc ON wc.id = wi.workflow_id
            JOIN tblusers u ON u.UserID = wi.requester_id
            WHERE wi.id = ?
        ");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($instance) {
            $title = 'طلب موافقة جديد';
            $message = "يوجد طلب {$instance['workflow_name']} من {$instance['FirstName']} {$instance['LastName']} بانتظار موافقتك";
            $this->createNotification($approverId, $title, $message, $instance['entity_type'], $instance['entity_id']);
        }
    }

    /**
     * Notify requester about workflow completion.
     */
    private function notifyRequester($instance, $status)
    {
        $statusText = $status === 'approved' ? 'تمت الموافقة على' : 'تم رفض';
        $title = $statusText . ' طلبك';

        $stmt = $this->pdo->prepare("SELECT name_ar FROM workflow_configs WHERE id = ?");
        $stmt->execute([$instance['workflow_id']]);
        $workflowName = $stmt->fetchColumn();

        $message = "$statusText طلب $workflowName الخاص بك";
        $this->createNotification($instance['requester_id'], $title, $message, $instance['entity_type'], $instance['entity_id']);
    }

    /**
     * Create an in-app notification using the centralized notification service.
     */
    private function createNotification($userId, $title, $message, $entityType, $entityId)
    {
        require_once __DIR__ . '/NotificationService.php';
        $service = new NotificationService($this->pdo);
        $service->notify((int) $userId, $title, $message, 'info', $entityType, (int) $entityId);
    }

    /**
     * Get pending approvals for a user.
     */
    public function getPendingApprovals($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                wa.id as approval_id,
                wa.instance_id,
                wa.step_number,
                wa.created_at as requested_at,
                wi.entity_type,
                wi.entity_id,
                wi.requester_id,
                wi.data,
                wc.name_ar as workflow_name,
                ws.name_ar as step_name,
                u.FirstName as requester_first,
                u.LastName as requester_last,
                u.Photo as requester_photo
            FROM workflow_approvals wa
            JOIN workflow_instances wi ON wi.id = wa.instance_id
            JOIN workflow_configs wc ON wc.id = wi.workflow_id
            JOIN workflow_steps ws ON ws.workflow_id = wc.id AND ws.step_order = wa.step_number
            JOIN tblusers u ON u.UserID = wi.requester_id
            WHERE wa.approver_id = ? AND wa.status = 'pending'
            ORDER BY wa.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get workflow history for an entity.
     */
    public function getWorkflowHistory($entityType, $entityId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                wal.action,
                wal.details,
                wal.created_at,
                u.FirstName,
                u.LastName,
                u.Photo
            FROM workflow_audit_log wal
            JOIN workflow_instances wi ON wi.id = wal.instance_id
            JOIN tblusers u ON u.UserID = wal.user_id
            WHERE wi.entity_type = ? AND wi.entity_id = ?
            ORDER BY wal.created_at ASC
        ");
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get workflow instance status.
     */
    public function getInstanceStatus($instanceId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                wi.*,
                wc.name_ar as workflow_name,
                (SELECT COUNT(*) FROM workflow_steps WHERE workflow_id = wc.id) as total_steps
            FROM workflow_instances wi
            JOIN workflow_configs wc ON wc.id = wi.workflow_id
            WHERE wi.id = ?
        ");
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($instance) {
            $stmt = $this->pdo->prepare("
                SELECT
                    wa.*,
                    ws.name_ar as step_name,
                    u.FirstName,
                    u.LastName
                FROM workflow_approvals wa
                JOIN workflow_steps ws ON ws.workflow_id = ? AND ws.step_order = wa.step_number
                JOIN tblusers u ON u.UserID = wa.approver_id
                WHERE wa.instance_id = ?
                ORDER BY wa.step_number, wa.created_at
            ");
            $stmt->execute([$instance['workflow_id'], $instanceId]);
            $instance['approvals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $instance;
    }
}
