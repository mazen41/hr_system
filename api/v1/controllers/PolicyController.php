<?php
/**
 * Policy Controller
 * API endpoints for leave policies, violations, promotions, and external tasks
 */

class PolicyController {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    // ==========================================
    // LEAVE BALANCE ENDPOINTS
    // ==========================================
    
    /**
     * GET /policy/leave-balance
     * Get current user's leave balance
     */
    public function getLeaveBalance() {
        require_once __DIR__ . '/../../../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($this->pdo);
        
        $balance = $manager->getBalanceSummary($this->userId);
        
        if (!$balance) {
            return Response::error('لا يوجد رصيد إجازات', 404);
        }
        
        return Response::success($balance);
    }
    
    /**
     * GET /policy/leave-policy
     * Get applicable leave policy for current user
     */
    public function getLeavePolicy() {
        require_once __DIR__ . '/../../../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($this->pdo);
        
        $policy = $manager->getApplicablePolicy($this->userId);
        
        if (!$policy) {
            return Response::error('لا توجد سياسة إجازات مطبقة', 404);
        }
        
        return Response::success([
            'id' => $policy['id'],
            'name' => $policy['policy_name_ar'],
            'annual_days' => (float)$policy['annual_days'],
            'monthly_accrual' => (float)$policy['monthly_accrual'],
            'allow_hourly_leave' => (bool)$policy['allow_hourly_leave'],
            'max_hours_per_day' => (float)$policy['max_hours_per_day'],
            'hours_per_day' => (float)$policy['hours_per_day'],
            'allow_carryover' => (bool)$policy['allow_carryover'],
            'max_carryover_days' => (float)$policy['max_carryover_days'],
            'advance_notice_days' => (int)$policy['advance_notice_days'],
            'max_consecutive_days' => (int)$policy['max_consecutive_days']
        ]);
    }
    
    /**
     * POST /policy/request-leave
     * Request leave (days or hours)
     */
    public function requestLeave() {
        require_once __DIR__ . '/../../../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($this->pdo);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['days']) && empty($data['hours'])) {
            return Response::error('يجب تحديد عدد الأيام أو الساعات', 400);
        }
        
        $result = $manager->requestLeave($this->userId, $data);
        
        if ($result['success']) {
            return Response::success($result, $result['message']);
        } else {
            return Response::error($result['message'], 400);
        }
    }
    
    // ==========================================
    // VIOLATION ENDPOINTS
    // ==========================================
    
    /**
     * GET /policy/my-violations
     * Get current user's violations
     */
    public function getMyViolations() {
        require_once __DIR__ . '/../../../classes/ViolationManager.php';
        $manager = new ViolationManager($this->pdo);
        
        $violations = $manager->getEmployeeViolations($this->userId);
        
        return Response::success([
            'violations' => array_map(function($v) {
                return [
                    'id' => $v['id'],
                    'type' => $v['violation_name'],
                    'category' => $v['category'],
                    'severity' => $v['severity'],
                    'date' => $v['violation_date'],
                    'status' => $v['status'],
                    'penalty_type' => $v['penalty_type'],
                    'penalty_value' => $v['penalty_value'],
                    'occurrence_number' => $v['occurrence_number'],
                    'blocks_promotion' => (bool)$v['blocks_promotion']
                ];
            }, $violations),
            'count' => count($violations)
        ]);
    }
    
    /**
     * GET /policy/violation-summary
     * Get violation summary for current user
     */
    public function getViolationSummary() {
        require_once __DIR__ . '/../../../classes/ViolationManager.php';
        $manager = new ViolationManager($this->pdo);
        
        $summary = $manager->getEmployeeViolationSummary($this->userId, 12);
        
        return Response::success([
            'total' => (int)($summary['total_violations'] ?? 0),
            'minor' => (int)($summary['minor_count'] ?? 0),
            'moderate' => (int)($summary['moderate_count'] ?? 0),
            'major' => (int)($summary['major_count'] ?? 0),
            'critical' => (int)($summary['critical_count'] ?? 0),
            'promotion_blocking' => (int)($summary['promotion_blocking_count'] ?? 0),
            'last_violation_date' => $summary['last_violation_date']
        ]);
    }
    
    // ==========================================
    // EXTERNAL TASK ENDPOINTS
    // ==========================================
    
    /**
     * GET /policy/my-tasks
     * Get current user's external tasks
     */
    public function getMyTasks() {
        require_once __DIR__ . '/../../../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($this->pdo);
        
        $filters = [
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        $tasks = $manager->getEmployeeTasks($this->userId, array_filter($filters));
        
        return Response::success([
            'tasks' => array_map(function($t) {
                return [
                    'id' => $t['id'],
                    'type' => $t['task_type'],
                    'title' => $t['title_ar'],
                    'location' => $t['location_name'],
                    'date' => $t['scheduled_date'],
                    'start_time' => $t['start_time'],
                    'end_time' => $t['end_time'],
                    'status' => $t['status'],
                    'actual_start' => $t['actual_start_time'],
                    'actual_end' => $t['actual_end_time'],
                    'actual_hours' => $t['actual_hours'],
                    'late_minutes' => $t['late_minutes']
                ];
            }, $tasks),
            'count' => count($tasks)
        ]);
    }
    
    /**
     * GET /policy/today-tasks
     * Get today's tasks for current user
     */
    public function getTodayTasks() {
        require_once __DIR__ . '/../../../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($this->pdo);
        
        $tasks = $manager->getTodayTasks($this->userId);
        
        return Response::success([
            'tasks' => array_map(function($t) {
                return [
                    'id' => $t['id'],
                    'type' => $t['task_type'],
                    'title' => $t['title_ar'],
                    'location' => $t['location_name'],
                    'address' => $t['location_address'],
                    'lat' => $t['location_lat'],
                    'lng' => $t['location_lng'],
                    'start_time' => $t['start_time'],
                    'end_time' => $t['end_time'],
                    'status' => $t['status'],
                    'requires_approval' => (bool)$t['requires_approval']
                ];
            }, $tasks),
            'count' => count($tasks)
        ]);
    }
    
    /**
     * POST /policy/start-task
     * Start an external task (check-in)
     */
    public function startTask() {
        require_once __DIR__ . '/../../../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($this->pdo);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['task_id'])) {
            return Response::error('معرف المهمة مطلوب', 400);
        }
        
        $result = $manager->startTask(
            $data['task_id'],
            $data['lat'] ?? null,
            $data['lng'] ?? null
        );
        
        if ($result['success']) {
            return Response::success($result, $result['message']);
        } else {
            return Response::error($result['message'], 400);
        }
    }
    
    /**
     * POST /policy/end-task
     * End an external task (check-out)
     */
    public function endTask() {
        require_once __DIR__ . '/../../../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($this->pdo);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['task_id'])) {
            return Response::error('معرف المهمة مطلوب', 400);
        }
        
        $result = $manager->endTask(
            $data['task_id'],
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            return Response::success($result, $result['message']);
        } else {
            return Response::error($result['message'], 400);
        }
    }
    
    /**
     * POST /policy/create-task
     * Create a new external task
     */
    public function createTask() {
        require_once __DIR__ . '/../../../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($this->pdo);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['task_type', 'title_ar', 'scheduled_date', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return Response::error("الحقل {$field} مطلوب", 400);
            }
        }
        
        $data['user_id'] = $this->userId;
        $data['created_by'] = $this->userId;
        
        $result = $manager->createTask($data);
        
        if ($result['success']) {
            return Response::success($result, $result['message']);
        } else {
            return Response::error($result['message'], 400);
        }
    }
    
    // ==========================================
    // PRESENCE ENDPOINTS
    // ==========================================
    
    /**
     * GET /policy/my-presence
     * Get current user's presence status
     */
    public function getMyPresence() {
        require_once __DIR__ . '/../../../classes/OrgChartManager.php';
        $manager = new OrgChartManager($this->pdo);
        
        $presence = $manager->getEmployeePresence($this->userId);
        
        if (!$presence) {
            return Response::success([
                'status' => 'off_duty',
                'auto_status' => 'off_duty',
                'manual_status' => null,
                'last_check_in' => null,
                'last_check_out' => null
            ]);
        }
        
        return Response::success([
            'status' => $presence['current_status'],
            'auto_status' => $presence['auto_status'],
            'manual_status' => $presence['manual_status'],
            'manual_status_note' => $presence['manual_status_note'],
            'last_check_in' => $presence['last_check_in'],
            'last_check_out' => $presence['last_check_out'],
            'current_task' => $presence['current_task_title']
        ]);
    }
    
    /**
     * POST /policy/update-presence
     * Update current user's presence status
     */
    public function updatePresence() {
        require_once __DIR__ . '/../../../classes/OrgChartManager.php';
        $manager = new OrgChartManager($this->pdo);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $status = $data['status'] ?? null;
        $note = $data['note'] ?? null;
        $until = $data['until'] ?? null;
        
        if (empty($status)) {
            $manager->clearManualPresence($this->userId);
        } else {
            $manager->updatePresence($this->userId, $status, $note, $until, $this->userId);
        }
        
        return Response::success(null, 'تم تحديث الحالة');
    }
    
    /**
     * GET /policy/presence-options
     * Get available presence status options
     */
    public function getPresenceOptions() {
        require_once __DIR__ . '/../../../classes/OrgChartManager.php';
        $manager = new OrgChartManager($this->pdo);
        
        return Response::success($manager->getPresenceStatusOptions());
    }
    
    // ==========================================
    // ORG CHART ENDPOINTS
    // ==========================================
    
    /**
     * GET /policy/org-chart
     * Get organizational chart data
     */
    public function getOrgChart() {
        require_once __DIR__ . '/../../../classes/OrgChartManager.php';
        $manager = new OrgChartManager($this->pdo);
        
        $tree = $manager->getOrgTree(true, true);
        
        return Response::success([
            'tree' => $tree,
            'summary' => $manager->getPresenceSummary()
        ]);
    }
    
    /**
     * GET /policy/department-employees
     * Get employees in a department with presence
     */
    public function getDepartmentEmployees() {
        require_once __DIR__ . '/../../../classes/OrgChartManager.php';
        $manager = new OrgChartManager($this->pdo);
        
        $sectionId = $_GET['section_id'] ?? null;
        
        if (!$sectionId) {
            return Response::error('معرف القسم مطلوب', 400);
        }
        
        $employees = $manager->getNodeEmployees($sectionId, true);
        
        return Response::success([
            'employees' => array_map(function($e) {
                return [
                    'id' => $e['UserID'],
                    'name' => trim($e['FirstName'] . ' ' . $e['LastName']),
                    'job_title' => $e['job_title'],
                    'photo' => $e['Photo'],
                    'status' => $e['current_status'] ?? 'off_duty',
                    'status_note' => $e['manual_status_note'],
                    'last_check_in' => $e['last_check_in']
                ];
            }, $employees),
            'count' => count($employees),
            'summary' => $manager->getPresenceSummary($sectionId)
        ]);
    }
    
    // ==========================================
    // PROMOTION ELIGIBILITY ENDPOINT
    // ==========================================
    
    /**
     * GET /policy/promotion-eligibility
     * Check if current user is eligible for promotion
     */
    public function checkPromotionEligibility() {
        require_once __DIR__ . '/../../../classes/PromotionManager.php';
        $manager = new PromotionManager($this->pdo);
        
        $eligibility = $manager->checkEligibility($this->userId);
        
        return Response::success([
            'eligible' => $eligibility['eligible'],
            'message' => $eligibility['message'],
            'service_months' => $eligibility['service_months'],
            'has_blocking_violations' => !empty($eligibility['blocking_violations']),
            'violation_count' => count($eligibility['blocking_violations'] ?? []),
            'handling_mode' => $eligibility['handling_mode']
        ]);
    }
}
