<?php
/**
 * Leave Policy Manager
 * Handles all leave policy operations including accrual, balance tracking, and policy enforcement
 */

class LeavePolicyManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all active leave policies
     */
    public function getAllPolicies($activeOnly = true) {
        $sql = "SELECT * FROM leave_policies";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY policy_name_ar";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get policy by ID
     */
    public function getPolicyById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM leave_policies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get applicable policy for an employee
     */
    public function getApplicablePolicy($userId) {
        // Get employee details
        $stmt = $this->pdo->prepare("
            SELECT u.UserID, r.GradeID, r.SectionID, r.jobtitleID, r.BranchID
            FROM tblusers u
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            WHERE u.UserID = ?
        ");
        $stmt->execute([$userId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$emp) return null;
        
        // Find matching policy (most specific first)
        $policies = $this->getAllPolicies(true);
        
        foreach ($policies as $policy) {
            if ($policy['applies_to_all']) {
                return $policy;
            }
            
            // Check specific criteria
            $matches = true;
            
            if (!empty($policy['applies_to_grades'])) {
                $grades = json_decode($policy['applies_to_grades'], true);
                if (!in_array($emp['GradeID'], $grades)) {
                    $matches = false;
                }
            }
            
            if (!empty($policy['applies_to_departments'])) {
                $depts = json_decode($policy['applies_to_departments'], true);
                if (!in_array($emp['SectionID'], $depts)) {
                    $matches = false;
                }
            }
            
            if (!empty($policy['applies_to_job_titles'])) {
                $titles = json_decode($policy['applies_to_job_titles'], true);
                if (!in_array($emp['jobtitleID'], $titles)) {
                    $matches = false;
                }
            }
            
            if (!empty($policy['applies_to_branches'])) {
                $branches = json_decode($policy['applies_to_branches'], true);
                if (!in_array($emp['BranchID'], $branches)) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                return $policy;
            }
        }
        
        // Return first policy as default
        return !empty($policies) ? $policies[0] : null;
    }
    
    /**
     * Get or create employee leave balance for current fiscal year
     */
    public function getEmployeeBalance($userId, $policyId = null, $fiscalYear = null) {
        if (!$fiscalYear) {
            $fiscalYear = date('Y');
        }
        
        if (!$policyId) {
            $policy = $this->getApplicablePolicy($userId);
            $policyId = $policy ? $policy['id'] : null;
        }
        
        if (!$policyId) return null;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM employee_leave_balances 
            WHERE user_id = ? AND leave_policy_id = ? AND fiscal_year = ?
        ");
        $stmt->execute([$userId, $policyId, $fiscalYear]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$balance) {
            // Create new balance record
            $balance = $this->initializeBalance($userId, $policyId, $fiscalYear);
        }
        
        return $balance;
    }
    
    /**
     * Initialize leave balance for a new year
     */
    public function initializeBalance($userId, $policyId, $fiscalYear) {
        $policy = $this->getPolicyById($policyId);
        if (!$policy) return null;
        
        // Get carryover from previous year
        $carryover = 0;
        if ($policy['allow_carryover']) {
            $prevYear = $fiscalYear - 1;
            $stmt = $this->pdo->prepare("
                SELECT available_days FROM employee_leave_balances 
                WHERE user_id = ? AND leave_policy_id = ? AND fiscal_year = ?
            ");
            $stmt->execute([$userId, $policyId, $prevYear]);
            $prevBalance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prevBalance) {
                $carryover = min($prevBalance['available_days'], $policy['max_carryover_days']);
            }
        }
        
        // Insert new balance
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_leave_balances 
            (user_id, leave_policy_id, fiscal_year, entitled_days, accrued_days, carryover_days)
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([$userId, $policyId, $fiscalYear, $policy['annual_days'], $carryover]);
        
        return $this->getEmployeeBalance($userId, $policyId, $fiscalYear);
    }
    
    /**
     * Process monthly accrual for an employee
     */
    public function processMonthlyAccrual($userId, $month = null, $year = null) {
        if (!$month) $month = date('n');
        if (!$year) $year = date('Y');
        
        $policy = $this->getApplicablePolicy($userId);
        if (!$policy || $policy['accrual_method'] !== 'monthly') {
            return false;
        }
        
        // Check if already accrued for this month
        $stmt = $this->pdo->prepare("
            SELECT id FROM leave_accrual_log 
            WHERE user_id = ? AND leave_policy_id = ? AND accrual_month = ? AND accrual_year = ?
        ");
        $stmt->execute([$userId, $policy['id'], $month, $year]);
        if ($stmt->fetch()) {
            return false; // Already accrued
        }
        
        $balance = $this->getEmployeeBalance($userId, $policy['id'], $year);
        if (!$balance) return false;
        
        $accrualAmount = $policy['monthly_accrual'];
        $newAccrued = $balance['accrued_days'] + $accrualAmount;
        
        // Update balance
        $stmt = $this->pdo->prepare("
            UPDATE employee_leave_balances 
            SET accrued_days = ?, last_accrual_date = CURDATE()
            WHERE id = ?
        ");
        $stmt->execute([$newAccrued, $balance['id']]);
        
        // Log accrual
        $stmt = $this->pdo->prepare("
            INSERT INTO leave_accrual_log 
            (user_id, leave_policy_id, accrual_date, accrual_month, accrual_year, days_accrued, balance_after)
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $policy['id'], $month, $year, $accrualAmount, $newAccrued]);
        
        return true;
    }
    
    /**
     * Process monthly accrual for all employees
     */
    public function processAllEmployeesAccrual($month = null, $year = null) {
        $stmt = $this->pdo->query("SELECT UserID FROM tblusers WHERE isemp = 1");
        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $processed = 0;
        foreach ($employees as $userId) {
            if ($this->processMonthlyAccrual($userId, $month, $year)) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Request leave (days or hours)
     */
    public function requestLeave($userId, $data) {
        $policy = $this->getApplicablePolicy($userId);
        if (!$policy) {
            return ['success' => false, 'message' => 'لا توجد سياسة إجازات مطبقة'];
        }
        
        $balance = $this->getEmployeeBalance($userId, $policy['id']);
        if (!$balance) {
            return ['success' => false, 'message' => 'لا يوجد رصيد إجازات'];
        }
        
        $isHourly = isset($data['hours']) && $data['hours'] > 0;
        
        if ($isHourly) {
            if (!$policy['allow_hourly_leave']) {
                return ['success' => false, 'message' => 'الإجازة بالساعات غير مسموحة'];
            }
            
            $hours = floatval($data['hours']);
            if ($hours > $policy['max_hours_per_day']) {
                return ['success' => false, 'message' => 'تجاوز الحد الأقصى للساعات اليومية'];
            }
            
            // Convert hours to days for balance check
            $daysEquivalent = $hours / $policy['hours_per_day'];
            
            if ($daysEquivalent > $balance['available_days']) {
                return ['success' => false, 'message' => 'رصيد الإجازات غير كافٍ'];
            }
        } else {
            $days = floatval($data['days'] ?? 0);
            
            if ($days > $balance['available_days']) {
                return ['success' => false, 'message' => 'رصيد الإجازات غير كافٍ. المتاح: ' . $balance['available_days'] . ' يوم'];
            }
            
            if ($policy['max_consecutive_days'] && $days > $policy['max_consecutive_days']) {
                return ['success' => false, 'message' => 'تجاوز الحد الأقصى للأيام المتتالية'];
            }
        }
        
        // Update pending days
        $pendingDays = $isHourly ? ($data['hours'] / $policy['hours_per_day']) : $data['days'];
        
        $stmt = $this->pdo->prepare("
            UPDATE employee_leave_balances 
            SET pending_days = pending_days + ?
            WHERE id = ?
        ");
        $stmt->execute([$pendingDays, $balance['id']]);
        
        return [
            'success' => true, 
            'message' => 'تم تقديم طلب الإجازة بنجاح',
            'pending_days' => $pendingDays
        ];
    }
    
    /**
     * Approve leave request
     */
    public function approveLeave($userId, $days, $hours = 0) {
        $policy = $this->getApplicablePolicy($userId);
        $balance = $this->getEmployeeBalance($userId, $policy['id']);
        
        if (!$balance) return false;
        
        $totalDays = $days + ($hours / ($policy['hours_per_day'] ?? 8));
        
        $stmt = $this->pdo->prepare("
            UPDATE employee_leave_balances 
            SET used_days = used_days + ?,
                used_hours = used_hours + ?,
                pending_days = pending_days - ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$days, $hours, $totalDays, $balance['id']]);
    }
    
    /**
     * Reject leave request
     */
    public function rejectLeave($userId, $days, $hours = 0) {
        $policy = $this->getApplicablePolicy($userId);
        $balance = $this->getEmployeeBalance($userId, $policy['id']);
        
        if (!$balance) return false;
        
        $totalDays = $days + ($hours / ($policy['hours_per_day'] ?? 8));
        
        $stmt = $this->pdo->prepare("
            UPDATE employee_leave_balances 
            SET pending_days = pending_days - ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$totalDays, $balance['id']]);
    }
    
    /**
     * Get leave balance summary for display
     */
    public function getBalanceSummary($userId) {
        $policy = $this->getApplicablePolicy($userId);
        if (!$policy) return null;
        
        $balance = $this->getEmployeeBalance($userId, $policy['id']);
        if (!$balance) return null;
        
        return [
            'policy' => [
                'id' => $policy['id'],
                'name' => $policy['policy_name_ar'],
                'annual_days' => $policy['annual_days'],
                'monthly_accrual' => $policy['monthly_accrual'],
                'allow_hourly' => $policy['allow_hourly_leave'],
                'max_hours_per_day' => $policy['max_hours_per_day'],
                'hours_per_day' => $policy['hours_per_day']
            ],
            'balance' => [
                'fiscal_year' => $balance['fiscal_year'],
                'entitled' => $balance['entitled_days'],
                'accrued' => $balance['accrued_days'],
                'carryover' => $balance['carryover_days'],
                'used' => $balance['used_days'],
                'used_hours' => $balance['used_hours'],
                'pending' => $balance['pending_days'],
                'available' => $balance['available_days']
            ]
        ];
    }
    
    /**
     * Create or update leave policy
     */
    public function savePolicy($data, $id = null) {
        $fields = [
            'policy_name_ar', 'policy_name_en', 'leave_type_id', 'annual_days',
            'accrual_method', 'allow_carryover', 'max_carryover_days',
            'carryover_expiry_months', 'compensate_unused', 'force_leave_before_expiry',
            'allow_hourly_leave', 'max_hours_per_day', 'hours_per_day', 'min_hours_per_request',
            'min_service_months', 'probation_eligible', 'requires_approval',
            'approval_levels', 'advance_notice_days', 'max_consecutive_days',
            'applies_to_all', 'applies_to_grades', 'applies_to_departments',
            'applies_to_job_titles', 'applies_to_branches', 'is_active'
        ];
        
        if (!empty($data['is_active'])) {
            $activeStmt = $this->pdo->prepare("SELECT id FROM leave_policies WHERE is_active = 1" . ($id ? " AND id <> ?" : "") . " LIMIT 1");
            $activeStmt->execute($id ? [$id] : []);
            if ($activeStmt->fetchColumn()) {
                throw new RuntimeException('Only one leave policy can be active. Please disable the currently active policy before activating another.');
            }
        }

        $values = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = $field;
                // Handle JSON fields
                if (in_array($field, ['applies_to_grades', 'applies_to_departments', 'applies_to_job_titles', 'applies_to_branches'])) {
                    $params[] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
                } else {
                    $params[] = $data[$field];
                }
            }
        }
        
        if ($id) {
            // Update
            $setClause = implode(' = ?, ', $values) . ' = ?';
            $params[] = $id;
            $sql = "UPDATE leave_policies SET $setClause WHERE id = ?";
        } else {
            // Insert
            $placeholders = str_repeat('?, ', count($values) - 1) . '?';
            $sql = "INSERT INTO leave_policies (" . implode(', ', $values) . ") VALUES ($placeholders)";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $id ?: $this->pdo->lastInsertId();
    }
    
    /**
     * Process year-end carryover for all employees
     */
    public function processYearEndCarryover($fromYear, $toYear) {
        $stmt = $this->pdo->query("SELECT UserID FROM tblusers WHERE isemp = 1");
        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $results = [
            'processed' => 0,
            'carryover_total' => 0,
            'forfeited_total' => 0,
            'compensated_total' => 0
        ];
        
        foreach ($employees as $userId) {
            $policy = $this->getApplicablePolicy($userId);
            if (!$policy) continue;
            
            $oldBalance = $this->getEmployeeBalance($userId, $policy['id'], $fromYear);
            if (!$oldBalance) continue;
            
            $available = $oldBalance['available_days'];
            $carryover = 0;
            $forfeited = 0;
            $compensated = 0;
            
            if ($policy['allow_carryover'] && $available > 0) {
                $carryover = min($available, $policy['max_carryover_days']);
                $remaining = $available - $carryover;
                
                if ($policy['compensate_unused'] && $remaining > 0) {
                    $compensated = $remaining;
                } else {
                    $forfeited = $remaining;
                }
            } else {
                if ($policy['compensate_unused']) {
                    $compensated = $available;
                } else {
                    $forfeited = $available;
                }
            }
            
            // Update old year balance
            $stmt = $this->pdo->prepare("
                UPDATE employee_leave_balances 
                SET compensated_days = ?, forfeited_days = ?
                WHERE id = ?
            ");
            $stmt->execute([$compensated, $forfeited, $oldBalance['id']]);
            
            // Initialize new year with carryover
            $this->initializeBalance($userId, $policy['id'], $toYear);
            
            $results['processed']++;
            $results['carryover_total'] += $carryover;
            $results['forfeited_total'] += $forfeited;
            $results['compensated_total'] += $compensated;
        }
        
        return $results;
    }
}
