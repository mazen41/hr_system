<?php
/**
 * External Task Manager
 * Handles external tasks/missions with attendance linking and time tracking
 */

class ExternalTaskManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create external task
     */
    public function createTask($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO external_tasks 
            (user_id, task_type, title_ar, title_en, description,
             location_name, location_address, location_lat, location_lng,
             scheduled_date, start_time, end_time,
             requires_approval, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['task_type'],
            $data['title_ar'],
            $data['title_en'] ?? null,
            $data['description'] ?? null,
            $data['location_name'] ?? null,
            $data['location_address'] ?? null,
            $data['location_lat'] ?? null,
            $data['location_lng'] ?? null,
            $data['scheduled_date'],
            $data['start_time'],
            $data['end_time'],
            $data['requires_approval'] ?? 1,
            $data['created_by'] ?? null
        ]);
        
        $taskId = $this->pdo->lastInsertId();
        
        // Update employee presence if task is today
        if ($data['scheduled_date'] === date('Y-m-d')) {
            $this->updatePresenceForTask($data['user_id'], $taskId);
        }
        
        return [
            'success' => true,
            'task_id' => $taskId,
            'message' => 'تم إنشاء المهمة الخارجية بنجاح'
        ];
    }
    
    /**
     * Start task (check-in)
     */
    public function startTask($taskId, $lat = null, $lng = null) {
        $task = $this->getTask($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'المهمة غير موجودة'];
        }
        
        if ($task['status'] !== 'scheduled') {
            return ['success' => false, 'message' => 'لا يمكن بدء هذه المهمة'];
        }
        
        $actualStartTime = date('H:i:s');
        $scheduledStart = $task['start_time'];
        
        // Calculate late minutes
        $lateMinutes = 0;
        if (strtotime($actualStartTime) > strtotime($scheduledStart)) {
            $lateMinutes = (strtotime($actualStartTime) - strtotime($scheduledStart)) / 60;
        }
        
        // Create attendance record for external task
        $attendanceId = $this->createAttendanceRecord($task['user_id'], 1, $lat, $lng, 'external_task', $taskId);
        
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks 
            SET status = 'in_progress',
                actual_start_time = ?,
                late_minutes = ?,
                attendance_check_in_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$actualStartTime, $lateMinutes, $attendanceId, $taskId]);
        
        // Update presence
        $this->updatePresence($task['user_id'], 'external_task', $task['title_ar'], $taskId);
        
        return [
            'success' => true,
            'actual_start' => $actualStartTime,
            'late_minutes' => $lateMinutes,
            'message' => 'تم بدء المهمة بنجاح'
        ];
    }
    
    /**
     * End task (check-out)
     */
    public function endTask($taskId, $lat = null, $lng = null, $notes = null) {
        $task = $this->getTask($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'المهمة غير موجودة'];
        }
        
        if ($task['status'] !== 'in_progress') {
            return ['success' => false, 'message' => 'المهمة ليست قيد التنفيذ'];
        }
        
        $actualEndTime = date('H:i:s');
        $scheduledEnd = $task['end_time'];
        
        // Calculate early leave or overtime
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;
        
        if (strtotime($actualEndTime) < strtotime($scheduledEnd)) {
            $earlyLeaveMinutes = (strtotime($scheduledEnd) - strtotime($actualEndTime)) / 60;
        } elseif (strtotime($actualEndTime) > strtotime($scheduledEnd)) {
            $overtimeMinutes = (strtotime($actualEndTime) - strtotime($scheduledEnd)) / 60;
        }
        
        // Calculate actual hours
        $actualHours = (strtotime($actualEndTime) - strtotime($task['actual_start_time'])) / 3600;
        
        // Create attendance record for check-out
        $attendanceId = $this->createAttendanceRecord($task['user_id'], 2, $lat, $lng, 'external_task', $taskId);
        
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks 
            SET status = 'completed',
                actual_end_time = ?,
                actual_hours = ?,
                early_leave_minutes = ?,
                overtime_minutes = ?,
                attendance_check_out_id = ?,
                completion_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $actualEndTime, 
            $actualHours, 
            $earlyLeaveMinutes, 
            $overtimeMinutes, 
            $attendanceId,
            $notes,
            $taskId
        ]);
        
        // Clear presence status
        $this->clearPresenceTask($task['user_id']);
        
        return [
            'success' => true,
            'actual_end' => $actualEndTime,
            'actual_hours' => round($actualHours, 2),
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'message' => 'تم إنهاء المهمة بنجاح'
        ];
    }
    
    /**
     * Cancel task
     */
    public function cancelTask($taskId, $reason = null) {
        $task = $this->getTask($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'المهمة غير موجودة'];
        }
        
        if (!in_array($task['status'], ['scheduled', 'in_progress'])) {
            return ['success' => false, 'message' => 'لا يمكن إلغاء هذه المهمة'];
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks 
            SET status = 'cancelled', completion_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$reason, $taskId]);
        
        if ($task['status'] === 'in_progress') {
            $this->clearPresenceTask($task['user_id']);
        }
        
        return ['success' => true, 'message' => 'تم إلغاء المهمة'];
    }
    
    /**
     * Approve task
     */
    public function approveTask($taskId, $approvedBy) {
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks 
            SET approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$approvedBy, $taskId]);
        
        return ['success' => true, 'message' => 'تمت الموافقة على المهمة'];
    }
    
    /**
     * Get task by ID
     */
    public function getTask($id) {
        $stmt = $this->pdo->prepare("
            SELECT et.*, u.FirstName, u.LastName
            FROM external_tasks et
            JOIN tblusers u ON u.UserID = et.user_id
            WHERE et.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get employee tasks
     */
    public function getEmployeeTasks($userId, $filters = []) {
        $sql = "SELECT * FROM external_tasks WHERE user_id = ?";
        $params = [$userId];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND scheduled_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND scheduled_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['task_type'])) {
            $sql .= " AND task_type = ?";
            $params[] = $filters['task_type'];
        }
        
        $sql .= " ORDER BY scheduled_date DESC, start_time DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get today's tasks for an employee
     */
    public function getTodayTasks($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM external_tasks 
            WHERE user_id = ? AND scheduled_date = CURDATE()
            ORDER BY start_time
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all tasks for admin view
     */
    public function getAllTasks($filters = []) {
        $sql = "
            SELECT et.*, u.FirstName, u.LastName, s.Name as section_name
            FROM external_tasks et
            JOIN tblusers u ON u.UserID = et.user_id
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN tblsection s ON s.Id = r.SectionID
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND et.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND et.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['task_type'])) {
            $sql .= " AND et.task_type = ?";
            $params[] = $filters['task_type'];
        }
        
        if (!empty($filters['date'])) {
            $sql .= " AND et.scheduled_date = ?";
            $params[] = $filters['date'];
        }
        
        if (!empty($filters['section_id'])) {
            $sql .= " AND r.SectionID = ?";
            $params[] = $filters['section_id'];
        }
        
        $sql .= " ORDER BY et.scheduled_date DESC, et.start_time DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get task summary for a period
     */
    public function getTaskSummary($userId, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show,
                SUM(COALESCE(actual_hours, 0)) as total_hours,
                SUM(COALESCE(late_minutes, 0)) as total_late_minutes,
                SUM(COALESCE(overtime_minutes, 0)) as total_overtime_minutes
            FROM external_tasks
            WHERE user_id = ?
            AND scheduled_date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create attendance record for external task
     */
    private function createAttendanceRecord($userId, $type, $lat, $lng, $source, $sourceId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO attendancet (EmpID, Date, Time, Type, lat, lng, method, device_info)
            VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, 'gps', ?)
        ");
        $stmt->execute([$userId, $type, $lat, $lng, "external_task:$sourceId"]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update employee presence for task
     */
    private function updatePresence($userId, $status, $note, $taskId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_presence (user_id, manual_status, manual_status_note, current_task_id)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            manual_status = VALUES(manual_status),
            manual_status_note = VALUES(manual_status_note),
            current_task_id = VALUES(current_task_id),
            updated_at = NOW()
        ");
        $stmt->execute([$userId, $status, $note, $taskId]);
    }
    
    /**
     * Update presence when task is scheduled for today
     */
    private function updatePresenceForTask($userId, $taskId) {
        $task = $this->getTask($taskId);
        if ($task) {
            $this->updatePresence($userId, 'external_task', $task['title_ar'], $taskId);
        }
    }
    
    /**
     * Clear task from presence
     */
    private function clearPresenceTask($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE employee_presence 
            SET manual_status = NULL, manual_status_note = NULL, current_task_id = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Mark task as no-show
     */
    public function markNoShow($taskId) {
        $task = $this->getTask($taskId);
        if (!$task || $task['status'] !== 'scheduled') {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks SET status = 'no_show' WHERE id = ?
        ");
        return $stmt->execute([$taskId]);
    }
    
    /**
     * Auto-process overdue tasks (run via cron)
     */
    public function processOverdueTasks() {
        // Mark scheduled tasks from past dates as no-show
        $stmt = $this->pdo->prepare("
            UPDATE external_tasks 
            SET status = 'no_show'
            WHERE status = 'scheduled'
            AND scheduled_date < CURDATE()
        ");
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Get task types for dropdown
     */
    public function getTaskTypes() {
        return [
            'meeting' => ['ar' => 'اجتماع', 'en' => 'Meeting'],
            'visit' => ['ar' => 'زيارة', 'en' => 'Visit'],
            'training' => ['ar' => 'تدريب', 'en' => 'Training'],
            'workshop' => ['ar' => 'ورشة عمل', 'en' => 'Workshop'],
            'mission' => ['ar' => 'مهمة', 'en' => 'Mission'],
            'other' => ['ar' => 'أخرى', 'en' => 'Other']
        ];
    }
}
