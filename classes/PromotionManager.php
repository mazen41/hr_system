<?php
/**
 * Promotion Manager
 * Handles promotion requests with violation checks and configurable blocking modes
 */

class PromotionManager {
    private $pdo;
    private $violationManager;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Ensure exceptions are thrown

        require_once __DIR__ . '/ViolationManager.php';
        $this->violationManager = new ViolationManager($pdo);
    }
    
    /**
     * Get all promotion policies
     */
    public function getPolicies($activeOnly = true) {
        try {
            $sql = "SELECT * FROM promotion_policies";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY policy_name_ar";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PromotionManager::getPolicies Error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    
    /**
     * Get policy by ID
     */
    public function getPolicy($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM promotion_policies WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PromotionManager::getPolicy Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single promotion request by ID with all details.
     * This method is used for the details modal.
     */
     public function getRequestById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pr.*,
                       u.FirstName AS emp_first, u.LastName AS emp_last, u.Photo AS emp_photo,
                       req.FirstName AS requester_first, req.LastName AS requester_last,
                       cur_jg.Name AS current_grade_name, cur_jt.Name AS current_job_title_name,
                       prop_jg.Name AS proposed_grade_name, prop_jt.Name AS proposed_job_title_name,
                       s.Name AS section_name, b.branch_name,
                       curr_contract.Salary AS current_salary, -- Removed Currency
                       curr_contract.GradeID AS current_grade_id, curr_contract.jobtitleID AS current_jobtitle_id
                FROM promotion_requests pr
                JOIN tblusers u ON u.UserID = pr.user_id
                LEFT JOIN tblusers req ON req.UserID = pr.requested_by
                LEFT JOIN tblremewal curr_contract ON curr_contract.UserID = u.UserID AND curr_contract.Id = u.lastversion
                LEFT JOIN tbljobgrade cur_jg ON cur_jg.Id = curr_contract.GradeID
                LEFT JOIN tbljobtitle cur_jt ON cur_jt.Id = curr_contract.jobtitleID
                LEFT JOIN tbljobgrade prop_jg ON prop_jg.Id = pr.proposed_grade_id
                LEFT JOIN tbljobtitle prop_jt ON prop_jt.Id = pr.proposed_job_title_id
                LEFT JOIN tblsection s ON s.Id = curr_contract.SectionID
                LEFT JOIN branches b ON b.branch_id = curr_contract.BranchID
                WHERE pr.id = ?
            ");
            $stmt->execute([$id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($request) {
                // Decode JSON fields if they exist
                $request['violation_summary_parsed'] = !empty($request['violation_summary']) ? json_decode($request['violation_summary'], true) : [];
                $request['current_salary'] = (float)($request['current_salary'] ?? 0); // Ensure float
                $request['proposed_salary'] = (float)($request['proposed_salary'] ?? 0); // Ensure float

                // Prepare status badge for display
                $statusBadges = [
                    'pending'        => '<span class="badge badge-warning">معلق</span>',
                    'manager_approved' => '<span class="badge badge-info">بانتظار HR</span>',
                    'hr_approved'    => '<span class="badge badge-primary">بانتظار التنفيذ</span>',
                    'approved'       => '<span class="badge badge-success">معتمد</span>',
                    'rejected'       => '<span class="badge badge-danger">مرفوض</span>',
                    'draft'          => '<span class="badge badge-secondary">مسودة</span>'
                ];
                $request['status_badge'] = $statusBadges[$request['status']] ?? '<span class="badge badge-light">' . htmlspecialchars($request['status'] ?? '') . '</span>';
            }

            return $request;

        } catch (PDOException $e) {
            error_log("PromotionManager::getRequestById Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get applicable promotion policy for an employee
     */
    public function getApplicablePolicy($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.GradeID, r.SectionID
                FROM tblusers u
                LEFT JOIN tblremewal r ON r.Id = u.lastversion
                WHERE u.UserID = ?
            ");
            $stmt->execute([$userId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $policies = $this->getPolicies(true);
            
            foreach ($policies as $policy) {
                if (($policy['applies_to_all'] ?? 0) == 1) { // Check for applies_to_all flag
                    return $policy;
                }
                
                $matches = true;
                
                // Ensure JSON fields are decoded and handle empty arrays
                $appliesToGrades = json_decode($policy['applies_to_grades'] ?? '[]', true);
                if (!empty($appliesToGrades) && !in_array($emp['GradeID'], $appliesToGrades)) {
                    $matches = false;
                }
                
                $appliesToDepartments = json_decode($policy['applies_to_departments'] ?? '[]', true);
                if (!empty($appliesToDepartments) && !in_array($emp['SectionID'], $appliesToDepartments)) {
                    $matches = false;
                }
                
                // Add similar checks for job titles and branches if policy has them
                // Example for Job Titles (assuming 'applies_to_job_titles' in policy and 'jobtitleID' in employee contract)
                // $appliesToJobTitles = json_decode($policy['applies_to_job_titles'] ?? '[]', true);
                // if (!empty($appliesToJobTitles) && !in_array($emp['jobtitleID'], $appliesToJobTitles)) {
                //     $matches = false;
                // }
                
                // Example for Branches (assuming 'applies_to_branches' in policy and 'BranchID' in employee contract)
                // $appliesToBranches = json_decode($policy['applies_to_branches'] ?? '[]', true);
                // if (!empty($appliesToBranches) && !in_array($emp['BranchID'], $appliesToBranches)) {
                //     $matches = false;
                // }

                if ($matches) return $policy;
            }
            
            return !empty($policies) ? $policies[0] : null; // Fallback to first policy if none specifically match
        } catch (PDOException $e) {
            error_log("PromotionManager::getApplicablePolicy Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check promotion eligibility with violation analysis
     */
    public function checkEligibility($userId) {
        $policy = $this->getApplicablePolicy($userId);
        if (!$policy) {
            return [
                'result' => false, // Added result to match AJAX response expectation
                'eligible' => false,
                'message' => 'لا توجد سياسة ترقيات مطبقة',
                'violations' => [],
                'can_override' => false
            ];
        }
        
        // Get employee info
        $stmt = $this->pdo->prepare("
            SELECT u.UserID, u.FirstName, u.LastName, u.CreatedDate,
                   r.GradeID, r.jobtitleID, r.Salary, -- Removed Currency
                   jg.Name as grade_name, jt.Name as job_title
            FROM tblusers u
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
            LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
            WHERE u.UserID = ?
        ");
        $stmt->execute([$userId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            return [
                'result' => false, // Added result
                'eligible' => false,
                'message' => 'الموظف غير موجود',
                'violations' => [],
                'can_override' => false
            ];
        }
        
        $issues = [];
        $violations = [];
        
        // Check minimum service months
        $serviceMonths = $this->getServiceMonths($userId);
        $minServiceMonths = (int)($policy['min_service_months'] ?? 0);
        if ($serviceMonths < $minServiceMonths) {
            $issues[] = [
                'type' => 'service',
                'message' => 'لم يكمل الحد الأدنى من الخدمة (' . $minServiceMonths . ' شهر)',
                'current' => $serviceMonths,
                'required' => $minServiceMonths
            ];
        }
        
        // Check violations
        $lookbackMonths = (int)($policy['violation_lookback_months'] ?? 12);
        // $violationSummary is not used directly for blocking, but for details/display
        $violationSummary = $this->violationManager->getEmployeeViolationSummary($userId, $lookbackMonths);
        
        // Check for *blocking* violations based on policy
        $blockingCheck = $this->violationManager->hasPromotionBlockingViolations($userId, $lookbackMonths);
        $blockingSeverities = json_decode($policy['blocking_violation_severities'] ?? '[]', true);
        
        $actualBlockingViolations = [];
        foreach (($blockingCheck['violations'] ?? []) as $v) { // Ensure $blockingCheck['violations'] is an array
            // Only consider violations that match the policy's blocking severities
            if (empty($blockingSeverities) || in_array($v['severity'], $blockingSeverities)) {
                $actualBlockingViolations[] = $v;
            }
        }
        
        if (!empty($actualBlockingViolations)) {
            $issues[] = [
                'type' => 'violations',
                'message' => 'يوجد مخالفات تمنع الترقية',
                'count' => count($actualBlockingViolations),
                'details' => $actualBlockingViolations
            ];
            $violations = $actualBlockingViolations; // Store filtered blocking violations
        }
        
        // Determine eligibility based on policy mode and actual issues
        $eligible = empty($issues); // If any issues, not initially eligible
        $handlingMode = $policy['violation_handling'];
        
        $result = [
            'result' => true, // Added result
            'eligible' => $eligible,
            'employee' => $employee,
            'policy' => $policy,
            'service_months' => $serviceMonths,
            'violation_summary' => $violationSummary, // All violations found
            'blocking_violations' => $violations, // Only those actually blocking based on policy
            'issues' => $issues, // All blocking issues identified
            'handling_mode' => $handlingMode,
            'can_override' => false,
            'requires_confirmation' => false,
            'message' => ''
        ];
        
        if (!$eligible) {
            $messagePrefix = $this->formatIssuesToMessage($issues); // Helper to make a combined message
            switch ($handlingMode) {
                case 'block':
                    $result['can_override'] = false; // Cannot override if policy is 'block'
                    $result['message'] = $messagePrefix . 'الترقية محظورة';
                    break;
                    
                case 'warn_allow':
                    // If the only issues are violations and policy allows warn_allow, then can override
                    $hasOnlyViolations = true;
                    foreach($issues as $issue) {
                        if ($issue['type'] !== 'violations') { // If there's any non-violation issue
                            $hasOnlyViolations = false;
                            break;
                        }
                    }
                    if ($hasOnlyViolations) {
                         $result['can_override'] = true;
                         $result['requires_confirmation'] = true;
                         $result['message'] = $messagePrefix . 'يمكن المتابعة مع التأكيد عند الموافقة (يوجد مخالفات)';
                    } else {
                        // Other issues (like service months) are not overridable by warn_allow policy
                        $result['can_override'] = false;
                        $result['message'] = $messagePrefix . 'الترقية محظورة';
                    }
                    break;
                    
                case 'notify_only':
                    $result['eligible'] = true; // Allow but notify, so set eligible to true here
                    $result['can_override'] = true; // Still allow override for extra flexibility, though not strictly "required" by policy
                    $result['requires_confirmation'] = false;
                    $result['message'] = $messagePrefix . 'تنبيه: يوجد مشكلات مسجلة للموظف (سيتم المتابعة)';
                    break;
            }
        } else {
            $result['message'] = 'الموظف مؤهل للترقية';
        }
        
        return $result;
    }

    private function formatIssuesToMessage($issues) {
        $messages = [];
        foreach ($issues as $issue) {
            $messages[] = $issue['message'];
        }
        if (!empty($messages)) {
            return implode('. ', $messages) . '. ';
        }
        return '';
    }
    
    /**
     * Get service months for an employee
     */
    private function getServiceMonths($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT CreatedDate FROM tblusers WHERE UserID = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['CreatedDate'])) {
                $createdDate = new DateTime($result['CreatedDate']);
                $now = new DateTime();
                $interval = $createdDate->diff($now);
                return $interval->y * 12 + $interval->m; // Total months
            }
            return 0;
        } catch (PDOException $e) {
            error_log("PromotionManager::getServiceMonths Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create promotion request
     */
    public function createRequest($data) {
        try {
            $userId = $data['user_id'];
            $requestedBy = $data['requested_by'];
            
            // Get employee's current contract details
            $stmt_emp_current = $this->pdo->prepare("
                SELECT r.GradeID, r.jobtitleID, r.Salary -- Removed Currency
                FROM tblusers u
                LEFT JOIN tblremewal r ON r.Id = u.lastversion
                WHERE u.UserID = ?
            ");
            $stmt_emp_current->execute([$userId]);
            $employee_current_contract = $stmt_emp_current->fetch(PDO::FETCH_ASSOC);

            if (!$employee_current_contract) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على معلومات العقد الحالي للموظف.',
                    'eligibility' => null // No eligibility check if no contract
                ];
            }

            // Check eligibility (important to get policy and violation info)
            $eligibility = $this->checkEligibility($userId);
            
            // If blocked and can't override, reject
            if (!$eligibility['eligible'] && !$eligibility['can_override']) {
                return [
                    'success' => false,
                    'message' => $eligibility['message'],
                    'eligibility' => $eligibility
                ];
            }
            
            // Insert request
            $stmt = $this->pdo->prepare("
                INSERT INTO promotion_requests 
                (user_id, requested_by, promotion_policy_id, 
                 current_grade_id, current_job_title_id, current_salary, -- Removed current_currency
                 proposed_grade_id, proposed_job_title_id, proposed_salary, effective_date,
                 justification, performance_notes,
                 has_violations, violation_count, violation_summary,
                 status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $hasViolations = !empty($eligibility['blocking_violations']);
            $violationCount = count($eligibility['blocking_violations']);
            
            $stmt->execute([
                $userId,
                $requestedBy,
                $eligibility['policy']['id'] ?? null,
                $employee_current_contract['GradeID'],
                $employee_current_contract['jobtitleID'],
                $employee_current_contract['Salary'],
                $data['proposed_grade_id'] ?? null,
                $data['proposed_job_title_id'] ?? null,
                $data['proposed_salary'] ?? null,
                $data['effective_date'] ?? null,
                $data['justification'] ?? null,
                $data['performance_notes'] ?? null,
                $hasViolations ? 1 : 0,
                $violationCount,
                $hasViolations ? json_encode($eligibility['blocking_violations']) : null,
                'pending'
            ]);
            
            $requestId = $this->pdo->lastInsertId();
            
            // Log audit (using AuditLog class, not internal logAudit)
            // Ensure you have an AuditLog instance or pass $pdo to it.
            // For now, removing the internal logAudit call
            
            return [
                'success' => true,
                'request_id' => $requestId,
                'has_violations' => $hasViolations,
                'requires_confirmation' => $eligibility['requires_confirmation'],
                'message' => 'تم إنشاء طلب الترقية بنجاح'
            ];
        } catch (PDOException $e) {
            error_log("PromotionManager::createRequest Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
        }
    }
    
    /**
     * Approve promotion with optional violation override
     */
    public function approveRequest($requestId, $approvedBy, $overrideViolations = false, $overrideReason = null) {
        try {
            $request = $this->getRequestById($requestId); // Use getRequestById
            if (!$request) {
                return ['success' => false, 'message' => 'طلب الترقية غير موجود'];
            }
            
            // Check if violations need override
            if (($request['has_violations'] ?? false) && !$overrideViolations) {
                $policy = $this->getPolicy($request['promotion_policy_id']);
                
                if (($policy['violation_handling'] ?? 'block') === 'block') { // Default to 'block' if policy not found
                    return [
                        'success' => false,
                        'message' => 'لا يمكن الموافقة على الترقية بسبب المخالفات المسجلة',
                        'violations' => json_decode($request['violation_summary'] ?? '[]', true) // Ensure default empty array
                    ];
                }
                
                if (($policy['violation_handling'] ?? 'warn_allow') === 'warn_allow') { // Default to 'warn_allow'
                    return [
                        'success' => false,
                        'requires_override' => true,
                        'message' => 'يجب تأكيد تجاوز المخالفات للموافقة على الترقية',
                        'violations' => json_decode($request['violation_summary'] ?? '[]', true)
                    ];
                }
            }
            
            // Determine approval level
            $newStatus = $this->determineNextStatus($request, $approvedBy);
            
            $updateFields = ['status = ?'];
            $updateParams = [$newStatus];
            
            // Track approval level
            if ($newStatus === 'manager_approved') {
                $updateFields[] = 'manager_approval = 1';
                $updateFields[] = 'manager_approved_by = ?';
                $updateFields[] = 'manager_approved_at = NOW()';
                $updateParams[] = $approvedBy;
            } elseif ($newStatus === 'hr_approved') {
                $updateFields[] = 'hr_approval = 1';
                $updateFields[] = 'hr_approved_by = ?';
                $updateFields[] = 'hr_approved_at = NOW()';
                $updateParams[] = $approvedBy;
            } elseif ($newStatus === 'approved') {
                $updateFields[] = 'final_approved_by = ?';
                $updateFields[] = 'final_approved_at = NOW()';
                $updateParams[] = $approvedBy;
            }
            
            // Handle violation override
            if ($overrideViolations && ($request['has_violations'] ?? false)) {
                $updateFields[] = 'violation_override = 1';
                $updateFields[] = 'override_reason = ?';
                $updateFields[] = 'override_by = ?';
                $updateParams[] = $overrideReason;
                $updateParams[] = $approvedBy;
            }
            
            $updateParams[] = $requestId;
            
            $sql = "UPDATE promotion_requests SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($updateParams);
            
            // If fully approved, apply the promotion
            if ($newStatus === 'approved') {
                $this->applyPromotion($request);
            }
            
            // Log audit (assuming AuditLog is handled by the calling script now)
            
            return [
                'success' => true,
                'new_status' => $newStatus,
                'message' => $newStatus === 'approved' ? 'تمت الموافقة النهائية على الترقية' : 'تمت الموافقة على هذه المرحلة'
            ];
        } catch (PDOException $e) {
            error_log("PromotionManager::approveRequest Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
        } catch (Exception $e) {
             error_log("PromotionManager::approveRequest General Error: " . $e->getMessage());
             return ['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()];
        }
    }
    
    /**
     * Determine next approval status
     */
    private function determineNextStatus($request, $approvedBy) {
        $policy = $this->getPolicy($request['promotion_policy_id']);
        
        if (!$policy) {
            // Default to immediate approval if no policy is linked (shouldn't happen with proper policy linking)
            return 'approved';
        }
        
        // Simple flow: pending -> manager_approved -> hr_approved -> approved
        switch ($request['status']) {
            case 'pending':
                if (($policy['requires_manager_approval'] ?? false)) { 
                    return 'manager_approved';
                }
                // Fall through if no manager approval required
            case 'manager_approved':
                if (($policy['requires_hr_approval'] ?? false)) {
                    return 'hr_approved';
                }
                // Fall through if no HR approval required
            case 'hr_approved':
                return 'approved';
            default:
                // If status is already approved, etc., keep it
                return $request['status'];
        }
    }
    
    /**
     * Apply promotion to employee record
     */
    private function applyPromotion($request) {
        // Start a transaction for applying promotion
        $this->pdo->beginTransaction();
        try {
            // Update employee's current contract/renewal record
            // Get the employee's current lastversion record
            $stmt_lastversion = $this->pdo->prepare("SELECT lastversion FROM tblusers WHERE UserID = ?");
            $stmt_lastversion->execute([$request['user_id']]);
            $currentRenewalId = $stmt_lastversion->fetchColumn();

            if (!$currentRenewalId) {
                throw new Exception("Could not find current contract for employee ID: " . $request['user_id']);
            }

            // Fetch the existing renewal record to copy fields that aren't changing
            $stmt_current_renewal = $this->pdo->prepare("SELECT * FROM tblremewal WHERE Id = ?");
            $stmt_current_renewal->execute([$currentRenewalId]);
            $currentRenewal = $stmt_current_renewal->fetch(PDO::FETCH_ASSOC);

            if (!$currentRenewal) {
                throw new Exception("Current contract record not found for ID: " . $currentRenewalId);
            }

            // INSERT a *new* tblremewal record with updated promotion details
            // Copy all fields from currentRenewal and override the promoted ones
            $newRenewalFields = [
                'UserID' => $request['user_id'],
                'BranchID' => $currentRenewal['BranchID'],
                'SectionID' => $currentRenewal['SectionID'],
                'GroupID' => $currentRenewal['GroupID'],
                'GradeID' => $request['proposed_grade_id'] ?? $currentRenewal['GradeID'], // Override grade
                'jobtitleID' => $request['proposed_job_title_id'] ?? $currentRenewal['jobtitleID'], // Override job title
                'TypeID' => $currentRenewal['TypeID'],
                'shiftID' => $currentRenewal['shiftID'],
                'fingerID' => $currentRenewal['fingerID'],
                'Salary' => $request['proposed_salary'] ?? $currentRenewal['Salary'], // Override salary
                // Removed 'Currency' field as per request
                'day' => $currentRenewal['day'], // Assuming 'day' is from original contract
                'Reason' => 'Promotion (Request ID: ' . $request['id'] . ')', // Add reason
                'state' => $currentRenewal['state'], // Keep current state
                'conform_date' => $currentRenewal['conform_date'],
                'come_id' => $currentRenewal['come_id'],
                'come_name' => $currentRenewal['come_name'],
                'new_s_date' => $request['effective_date'] ?? $currentRenewal['new_s_date'], // Effective date of promotion
                'new_e_date' => $currentRenewal['new_e_date'],
                'CreatedBy' => $request['final_approved_by'] ?? $request['requested_by'],
                'CreatedDate' => date('Y-m-d'), // New creation date
            ];

            // Filter out any empty/null values that might cause issues, especially for NOT NULL columns
            // You might need to adjust this based on exact tblremewal schema and defaults
            $filteredFields = array_filter($newRenewalFields, function($value) {
                return $value !== null && $value !== ''; // Exclude strictly null or empty string
            });

            $insertColumns = implode(', ', array_keys($filteredFields));
            $insertPlaceholders = ':' . implode(', :', array_keys($filteredFields));

            $stmt_insert_renewal = $this->pdo->prepare("
                INSERT INTO tblremewal ($insertColumns) VALUES ($insertPlaceholders)
            ");
            $stmt_insert_renewal->execute($filteredFields);
            $newRenewalId = $this->pdo->lastInsertId();

            // Update tblusers.lastversion to point to the new contract
            $stmt_update_user = $this->pdo->prepare("
                UPDATE tblusers SET lastversion = ? WHERE UserID = ?
            ");
            $stmt_update_user->execute([$newRenewalId, $request['user_id']]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error applying promotion for request ID {$request['id']}: " . $e->getMessage());
            throw $e; // Re-throw for reporting
        }
    }
    
    /**
     * Reject promotion request
     */
    public function rejectRequest($requestId, $rejectedBy, $reason) {
        try {
            $request = $this->getRequestById($requestId); // Use getRequestById
            if (!$request) {
                return ['success' => false, 'message' => 'طلب الترقية غير موجود'];
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE promotion_requests 
                SET status = 'rejected', rejection_reason = ?, rejected_by = ?, rejected_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $rejectedBy, $requestId]);
            
            // Log audit (assuming AuditLog is handled by the calling script now)
            
            return ['success' => true, 'message' => 'تم رفض طلب الترقية'];
        } catch (PDOException $e) {
            error_log("PromotionManager::rejectRequest Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all promotion requests (for the main list view)
     * Enhanced to fetch all necessary data for UI display.
     */
    public function getRequests($filters = []) {
        try {
            $sql = "
                SELECT pr.*, 
                       u.FirstName AS emp_first, u.LastName AS emp_last, u.Photo AS emp_photo,
                       req.FirstName AS requester_first, req.LastName AS requester_last,
                       cur_jg.Name as current_grade_name, pr.current_grade_id, -- Keep original grade ID
                       prop_jg.Name as proposed_grade_name, pr.proposed_grade_id, -- Keep proposed grade ID
                       cur_jt.Name as current_job_title_name, pr.current_job_title_id, -- Keep original job title ID
                       prop_jt.Name as proposed_job_title_name, pr.proposed_job_title_id, -- Keep proposed job title ID
                       pr.current_salary, pr.proposed_salary, pr.effective_date, pr.justification, pr.performance_notes, pr.rejection_reason, pr.override_reason, pr.violation_override, pr.violation_summary,
                       s.Name as section_name,
                       b.branch_name -- Added branch name
                FROM promotion_requests pr
                JOIN tblusers u ON u.UserID = pr.user_id
                LEFT JOIN tblusers req ON req.UserID = pr.requested_by -- Join for requester name
                LEFT JOIN tbljobgrade cur_jg ON cur_jg.Id = pr.current_grade_id -- Current grade name
                LEFT JOIN tbljobgrade prop_jg ON prop_jg.Id = pr.proposed_grade_id -- Proposed grade name
                LEFT JOIN tbljobtitle cur_jt ON cur_jt.Id = pr.current_job_title_id -- Current job title name
                LEFT JOIN tbljobtitle prop_jt ON prop_jt.Id = pr.proposed_job_title_id -- Proposed job title name
                LEFT JOIN tblremewal r_contract ON r_contract.UserID = u.UserID AND r_contract.Id = u.lastversion -- Join to get section/branch from user's current contract
                LEFT JOIN tblsection s ON s.Id = r_contract.SectionID
                LEFT JOIN branches b ON b.branch_id = r_contract.BranchID
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= " AND pr.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND pr.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['has_violations'])) {
                $sql .= " AND pr.has_violations = 1";
            }
            
            $sql .= " ORDER BY pr.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PromotionManager::getRequests Error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    
    /**
     * Save promotion policy
     */
    public function savePolicy($data, $id = null) {
        try {
            $fieldsToProcess = [
                'policy_name_ar', 'policy_name_en', 'violation_handling',
                'min_service_months', 'violation_lookback_months',
                'requires_hr_approval', 'requires_manager_approval', 'requires_ceo_approval',
                'applies_to_all', 'is_active'
            ];
            $jsonFields = [
                'blocking_violation_severities', 'blocking_violation_types',
                'applies_to_grades', 'applies_to_departments', 'applies_to_job_titles', 'applies_to_branches'
            ];

            $updateParts = [];
            $insertColumns = [];
            $insertPlaceholders = [];
            $params = [];
            
            // Process fields for UPDATE or INSERT
            foreach ($fieldsToProcess as $field) {
                if (isset($data[$field])) {
                    $updateParts[] = "$field = ?";
                    $insertColumns[] = $field;
                    $insertPlaceholders[] = '?';
                    $params[] = $data[$field];
                } else if ($id) {
                    // For update, if a checkbox is not present in POST, it means it was unchecked
                    if (in_array($field, ['requires_hr_approval', 'requires_manager_approval', 'requires_ceo_approval', 'applies_to_all', 'is_active'])) {
                        $updateParts[] = "$field = ?";
                        $params[] = 0;
                    }
                } else {
                    // For insert, provide default if not set (especially for NOT NULL columns)
                    if (in_array($field, ['requires_hr_approval', 'requires_manager_approval', 'requires_ceo_approval', 'applies_to_all', 'is_active'])) {
                         $insertColumns[] = $field;
                         $insertPlaceholders[] = '?';
                         $params[] = 0; // Default unchecked checkbox to 0
                    }
                }
            }

            // Process JSON fields
            foreach ($jsonFields as $field) {
                $updateParts[] = "$field = ?";
                $insertColumns[] = $field;
                $insertPlaceholders[] = '?';
                $params[] = (isset($data[$field]) && is_array($data[$field]) && !empty($data[$field])) ? json_encode($data[$field]) : null;
            }

            // Add created/updated timestamps
            if ($id) {
                $updateParts[] = 'updated_at = NOW()';
            } else {
                $insertColumns[] = 'created_at';
                $insertPlaceholders[] = 'NOW()';
                if (isset($data['created_by'])) {
                    $insertColumns[] = 'created_by';
                    $insertPlaceholders[] = '?';
                    $params[] = $data['created_by'];
                }
            }

            if ($id) {
                $sql = "UPDATE promotion_policies SET " . implode(', ', $updateParts) . " WHERE id = ?";
                $params[] = $id; // Add ID for WHERE clause
            } else {
                $sql = "INSERT INTO promotion_policies (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
            }

            error_log("Save Policy SQL: " . $sql);
            error_log("Save Policy Params: " . json_encode($params));

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $id ?: $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("PromotionManager::savePolicy Error: " . $e->getMessage());
            throw $e; // Re-throw for reporting
        }
    }
}