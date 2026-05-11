<?php
/**
 * Violation Manager
 * Handles employee violations, escalation rules, and penalty tracking
 */

class ViolationManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all violation types
     */
    public function getViolationTypes($activeOnly = true) {
        $sql = "SELECT * FROM violation_types";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY category, severity, name_ar";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get violation type by ID
     */
    public function getViolationType($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM violation_types WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get escalation rules for a violation type
     */
    public function getEscalationRules($violationTypeId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM violation_escalation_rules 
            WHERE violation_type_id = ? 
            ORDER BY occurrence_number
        ");
        $stmt->execute([$violationTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count previous occurrences of a violation type for an employee
     */
    public function getOccurrenceCount($userId, $violationTypeId, $lookbackMonths = 12) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM employee_violations 
            WHERE user_id = ? 
            AND violation_type_id = ? 
            AND status IN ('confirmed', 'closed')
            AND violation_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        ");
        $stmt->execute([$userId, $violationTypeId, $lookbackMonths]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Record a new violation
     */
    public function recordViolation($data) {
        $userId = $data['user_id'];
        $violationTypeId = $data['violation_type_id'];
        
        // Get violation type
        $violationType = $this->getViolationType($violationTypeId);
        if (!$violationType) {
            return ['success' => false, 'message' => 'نوع المخالفة غير موجود'];
        }
        
        // Calculate occurrence number
        $occurrenceCount = $this->getOccurrenceCount($userId, $violationTypeId);
        $occurrenceNumber = $occurrenceCount + 1;
        
        // Get applicable escalation rule
        $escalationRule = $this->getApplicableEscalation($violationTypeId, $occurrenceNumber);
        
        // Determine penalty
        $penaltyType = $escalationRule ? $escalationRule['penalty_type'] : $violationType['default_penalty_type'];
        $penaltyValue = $escalationRule ? $escalationRule['penalty_value'] : $violationType['default_penalty_value'];
        
        // Insert violation
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_violations 
            (user_id, violation_type_id, violation_date, reported_by, description, 
             evidence_path, penalty_type, penalty_value, occurrence_number, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'reported')
        ");
        
        $stmt->execute([
            $userId,
            $violationTypeId,
            $data['violation_date'] ?? date('Y-m-d'),
            $data['reported_by'] ?? null,
            $data['description'] ?? null,
            $data['evidence_path'] ?? null,
            $penaltyType,
            $penaltyValue,
            $occurrenceNumber
        ]);
        
        $violationId = $this->pdo->lastInsertId();
        
        // Log to audit
        $this->logAudit('employee_violations', $violationId, 'create', null, $data, $data['reported_by']);
        
        return [
            'success' => true,
            'violation_id' => $violationId,
            'occurrence_number' => $occurrenceNumber,
            'penalty_type' => $penaltyType,
            'penalty_value' => $penaltyValue,
            'escalation_applied' => $escalationRule !== null,
            'message' => 'تم تسجيل المخالفة بنجاح'
        ];
    }
    
    /**
     * Get applicable escalation rule based on occurrence
     */
    private function getApplicableEscalation($violationTypeId, $occurrenceNumber) {
        // Try exact match first
        $stmt = $this->pdo->prepare("
            SELECT * FROM violation_escalation_rules 
            WHERE violation_type_id = ? AND occurrence_number = ?
        ");
        $stmt->execute([$violationTypeId, $occurrenceNumber]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rule) return $rule;
        
        // Get highest applicable rule
        $stmt = $this->pdo->prepare("
            SELECT * FROM violation_escalation_rules 
            WHERE violation_type_id = ? AND occurrence_number <= ?
            ORDER BY occurrence_number DESC
            LIMIT 1
        ");
        $stmt->execute([$violationTypeId, $occurrenceNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update violation status
     */
    public function updateViolationStatus($violationId, $status, $notes = null, $resolvedBy = null) {
        $oldData = $this->getViolation($violationId);
        
        $stmt = $this->pdo->prepare("
            UPDATE employee_violations 
            SET status = ?, 
                resolution_notes = COALESCE(?, resolution_notes),
                resolved_by = COALESCE(?, resolved_by),
                resolved_at = CASE WHEN ? IN ('confirmed', 'dismissed', 'closed') THEN NOW() ELSE resolved_at END
            WHERE id = ?
        ");
        $stmt->execute([$status, $notes, $resolvedBy, $status, $violationId]);
        
        $this->logAudit('employee_violations', $violationId, 'update', $oldData, ['status' => $status], $resolvedBy);
        
        return true;
    }
    
    /**
     * Get violation by ID
     */
    public function getViolation($id) {
        $stmt = $this->pdo->prepare("
            SELECT ev.*, vt.name_ar as violation_name, vt.category, vt.severity,
                   u.FirstName, u.LastName,
                   r.FirstName as reporter_first, r.LastName as reporter_last
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            JOIN tblusers u ON u.UserID = ev.user_id
            LEFT JOIN tblusers r ON r.UserID = ev.reported_by
            WHERE ev.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get employee violations
     */
    public function getEmployeeViolations($userId, $status = null, $lookbackMonths = null) {
        $sql = "
            SELECT ev.*, vt.name_ar as violation_name, vt.category, vt.severity,
                   vt.blocks_promotion, vt.promotion_block_months
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            WHERE ev.user_id = ?
        ";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND ev.status = ?";
            $params[] = $status;
        }
        
        if ($lookbackMonths) {
            $sql .= " AND ev.violation_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
            $params[] = $lookbackMonths;
        }
        
        $sql .= " ORDER BY ev.violation_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get violation summary for an employee
     */
    public function getEmployeeViolationSummary($userId, $lookbackMonths = 12) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_violations,
                SUM(CASE WHEN vt.severity = 'minor' THEN 1 ELSE 0 END) as minor_count,
                SUM(CASE WHEN vt.severity = 'moderate' THEN 1 ELSE 0 END) as moderate_count,
                SUM(CASE WHEN vt.severity = 'major' THEN 1 ELSE 0 END) as major_count,
                SUM(CASE WHEN vt.severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN vt.blocks_promotion = 1 THEN 1 ELSE 0 END) as promotion_blocking_count,
                MAX(ev.violation_date) as last_violation_date
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            WHERE ev.user_id = ?
            AND ev.status IN ('confirmed', 'closed')
            AND ev.violation_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        ");
        $stmt->execute([$userId, $lookbackMonths]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if employee has promotion-blocking violations
     */
    public function hasPromotionBlockingViolations($userId, $lookbackMonths = 12) {
        $stmt = $this->pdo->prepare("
            SELECT ev.id, vt.name_ar, vt.severity, ev.violation_date, 
                   vt.promotion_block_months,
                   DATE_ADD(ev.violation_date, INTERVAL vt.promotion_block_months MONTH) as block_until
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            WHERE ev.user_id = ?
            AND ev.status IN ('confirmed', 'closed')
            AND vt.blocks_promotion = 1
            AND DATE_ADD(ev.violation_date, INTERVAL vt.promotion_block_months MONTH) > CURDATE()
        ");
        $stmt->execute([$userId]);
        $blockingViolations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'has_blocking' => count($blockingViolations) > 0,
            'violations' => $blockingViolations,
            'count' => count($blockingViolations)
        ];
    }
    
    /**
     * Save violation type
     */
    public function saveViolationType($data, $id = null) {
        $fields = [
            'code', 'name_ar', 'name_en', 'category', 'severity',
            'description_ar', 'description_en', 'default_penalty_type',
            'default_penalty_value', 'escalation_enabled', 'blocks_promotion',
            'promotion_block_months', 'affects_leave', 'leave_deduction_days', 'is_active'
        ];
        
        $values = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = $field;
                $params[] = $data[$field];
            }
        }
        
        if ($id) {
            $setClause = implode(' = ?, ', $values) . ' = ?';
            $params[] = $id;
            $sql = "UPDATE violation_types SET $setClause WHERE id = ?";
        } else {
            $placeholders = str_repeat('?, ', count($values) - 1) . '?';
            $sql = "INSERT INTO violation_types (" . implode(', ', $values) . ") VALUES ($placeholders)";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $id ?: $this->pdo->lastInsertId();
    }
    
    /**
     * Save escalation rule
     */
    public function saveEscalationRule($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO violation_escalation_rules 
            (violation_type_id, occurrence_number, penalty_type, penalty_value, 
             penalty_duration_days, blocks_promotion, promotion_block_months, notes_ar, notes_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            penalty_type = VALUES(penalty_type),
            penalty_value = VALUES(penalty_value),
            penalty_duration_days = VALUES(penalty_duration_days),
            blocks_promotion = VALUES(blocks_promotion),
            promotion_block_months = VALUES(promotion_block_months),
            notes_ar = VALUES(notes_ar),
            notes_en = VALUES(notes_en)
        ");
        
        return $stmt->execute([
            $data['violation_type_id'],
            $data['occurrence_number'],
            $data['penalty_type'],
            $data['penalty_value'] ?? 0,
            $data['penalty_duration_days'] ?? null,
            $data['blocks_promotion'] ?? 0,
            $data['promotion_block_months'] ?? 0,
            $data['notes_ar'] ?? null,
            $data['notes_en'] ?? null
        ]);
    }
    
    /**
     * Get all violations for admin view
     */
    public function getAllViolations($filters = []) {
        $sql = "
            SELECT ev.*, vt.name_ar as violation_name, vt.category, vt.severity,
                   u.FirstName, u.LastName, u.UserEmail,
                   s.Name as section_name
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            JOIN tblusers u ON u.UserID = ev.user_id
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN tblsection s ON s.Id = r.SectionID
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND ev.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND ev.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND vt.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['severity'])) {
            $sql .= " AND vt.severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND ev.violation_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND ev.violation_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY ev.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log audit entry
     */
    private function logAudit($table, $recordId, $action, $oldValues, $newValues, $changedBy) {
        $stmt = $this->pdo->prepare("
            INSERT INTO policy_audit_log 
            (table_name, record_id, action, old_values, new_values, changed_by, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $table,
            $recordId,
            $action,
            $oldValues ? json_encode($oldValues) : null,
            json_encode($newValues),
            $changedBy,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
