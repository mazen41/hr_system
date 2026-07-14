<?php
/**
 * Evaluation Manager - Handles employee performance evaluations and rewards
 */
class EvaluationManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // Ensure PDO throws exceptions on errors
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // ==========================================
    // EVALUATION PERIODS
    // ==========================================
    
    public function createPeriod(array $data) {
        $sql = "INSERT INTO evaluation_periods 
                (name_ar, name_en, period_type, start_date, end_date, is_active)
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $data['name_ar'],
            $data['name_en'] ?? null,
            $data['period_type'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 1 // Default to 1 if not provided
        ];

        error_log("EvaluationManager::createPeriod SQL: " . $sql);
        error_log("EvaluationManager::createPeriod Params: " . json_encode($params));

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $lastId = $this->pdo->lastInsertId();
            error_log("EvaluationManager::createPeriod successful, ID: " . $lastId);
            return $lastId;
        } catch (PDOException $e) {
            error_log("EvaluationManager::createPeriod Error: " . $e->getMessage());
            // Re-throw the exception so the calling code (hr-app/index.php) can catch it and return an error response
            throw $e; 
        }
    }
    
    public function getActivePeriods() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM evaluation_periods 
                WHERE is_active = 1 
                ORDER BY start_date DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getActivePeriods Error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    
    public function getPeriod($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM evaluation_periods WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getPeriod Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCurrentProbationPeriod() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM evaluation_periods 
                WHERE period_type = 'probation' AND is_active = 1
                AND CURDATE() BETWEEN start_date AND end_date
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getCurrentProbationPeriod Error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==========================================
    // EVALUATION CRITERIA
    // ==========================================
    
    public function getCriteria($activeOnly = true) {
        try {
            $sql = "SELECT * FROM evaluation_criteria";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY sort_order, category";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getCriteria Error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    
    public function getCriteriaByCategory() {
        $criteria = $this->getCriteria();
        $grouped = [];
        foreach ($criteria as $c) {
            $grouped[$c['category']][] = $c;
        }
        return $grouped;
    }
    
    public function saveCriteria(array $data) {
        try {
            if (isset($data['id']) && $data['id']) {
                $sql = "
                    UPDATE evaluation_criteria 
                    SET name_ar = ?, name_en = ?, category = ?, weight = ?, max_score = ?, description = ?, is_active = ?, sort_order = ?
                    WHERE id = ?
                ";
                $params = [
                    $data['name_ar'],
                    $data['name_en'] ?? null,
                    $data['category'],
                    $data['weight'] ?? 1.0,
                    $data['max_score'] ?? 5,
                    $data['description'] ?? null,
                    $data['is_active'] ?? 1,
                    $data['sort_order'] ?? 0,
                    $data['id']
                ];
                error_log("EvaluationManager::saveCriteria (UPDATE) SQL: " . $sql);
                error_log("EvaluationManager::saveCriteria (UPDATE) Params: " . json_encode($params));

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                error_log("EvaluationManager::saveCriteria (UPDATE) successful, ID: " . $data['id']);
                return $data['id'];
            } else {
                $sql = "
                    INSERT INTO evaluation_criteria 
                    (name_ar, name_en, category, weight, max_score, description, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $params = [
                    $data['name_ar'],
                    $data['name_en'] ?? null,
                    $data['category'],
                    $data['weight'] ?? 1.0,
                    $data['max_score'] ?? 5,
                    $data['description'] ?? null,
                    $data['is_active'] ?? 1,
                    $data['sort_order'] ?? 0
                ];
                error_log("EvaluationManager::saveCriteria (INSERT) SQL: " . $sql);
                error_log("EvaluationManager::saveCriteria (INSERT) Params: " . json_encode($params));

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $lastId = $this->pdo->lastInsertId();
                error_log("EvaluationManager::saveCriteria (INSERT) successful, ID: " . $lastId);
                return $lastId;
            }
        } catch (PDOException $e) {
            error_log("EvaluationManager::saveCriteria Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ==========================================
    // EMPLOYEE EVALUATIONS
    // ==========================================
    
    public function createEvaluation($employeeId, $evaluatorId, $periodId) {
        try {
            // Check if evaluation already exists
            $stmt = $this->pdo->prepare("
                SELECT id FROM employee_evaluations 
                WHERE employee_id = ? AND period_id = ?
            ");
            $stmt->execute([$employeeId, $periodId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                return ['success' => false, 'message' => 'يوجد تقييم مسبق لهذا الموظف في هذه الفترة', 'id' => $existing['id']];
            }
            
            $sql = "
                INSERT INTO employee_evaluations 
                (employee_id, evaluator_id, period_id, evaluation_date, status)
                VALUES (?, ?, ?, CURDATE(), 'draft')
            ";
            $params = [$employeeId, $evaluatorId, $periodId];

            error_log("EvaluationManager::createEvaluation SQL: " . $sql);
            error_log("EvaluationManager::createEvaluation Params: " . json_encode($params));

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $evalId = $this->pdo->lastInsertId();
            
            // Create empty scores for all active criteria
            $criteria = $this->getCriteria(); 
            if (!empty($criteria)) {
                $stmtScores = $this->pdo->prepare("
                    INSERT INTO evaluation_scores (evaluation_id, criteria_id, score)
                    VALUES (?, ?, 0)
                ");
                foreach ($criteria as $c) {
                    $stmtScores->execute([$evalId, $c['id']]);
                }
            }
            
            error_log("EvaluationManager::createEvaluation successful, ID: " . $evalId);
            return ['success' => true, 'id' => $evalId, 'message' => 'تم إنشاء التقييم بنجاح.'];

        } catch (PDOException $e) {
            error_log("EvaluationManager::createEvaluation Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'فشل في إنشاء التقييم: ' . $e->getMessage()];
        }
    }

  public function getEvaluation($id) {
    try {
        $stmt = $this->pdo->prepare("
            SELECT ee.*, 
                   ep.name_ar as period_name, ep.period_type, ep.start_date, ep.end_date, -- <<< ADDED THESE LINES
                   e.FirstName as emp_first, e.LastName as emp_last, e.Photo as emp_photo,
                   ev.FirstName as evaluator_first, ev.LastName as evaluator_last
            FROM employee_evaluations ee
            JOIN evaluation_periods ep ON ep.id = ee.period_id
            JOIN tblusers e ON e.UserID = ee.employee_id
            JOIN tblusers ev ON ev.UserID = ee.evaluator_id
            WHERE ee.id = ?
        ");
        $stmt->execute([$id]);
        $eval = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($eval) {
            // Get scores
            $stmt = $this->pdo->prepare("
                SELECT es.*, ec.name_ar, ec.name_en, ec.category, ec.weight, ec.max_score, ec.description -- Also ensure criteria description is selected
                FROM evaluation_scores es
                JOIN evaluation_criteria ec ON ec.id = es.criteria_id
                WHERE es.evaluation_id = ?
                ORDER BY ec.sort_order
            ");
            $stmt->execute([$id]);
            $eval['scores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $eval;
    } catch (PDOException $e) {
        error_log("EvaluationManager::getEvaluation Error: " . $e->getMessage());
        return false;
    }
}
    
    public function saveScores($evaluationId, $scores, $additionalData = []) {
        try {
            // Update scores
            $stmt = $this->pdo->prepare("
                UPDATE evaluation_scores 
                SET score = ?, comment = ?
                WHERE evaluation_id = ? AND criteria_id = ?
            ");
            
            foreach ($scores as $criteriaId => $scoreData) {
                $score = is_array($scoreData) ? $scoreData['score'] : $scoreData;
                $comment = is_array($scoreData) ? ($scoreData['comment'] ?? null) : null;
                $stmt->execute([$score, $comment, $evaluationId, $criteriaId]);
            }
            
            // Calculate total score
            $this->calculateTotalScore($evaluationId);
            
            // Update additional fields
            if (!empty($additionalData)) {
                $fields = [];
                $values = [];
                
                foreach (['strengths', 'weaknesses', 'recommendations'] as $field) {
                    if (isset($additionalData[$field])) {
                        $fields[] = "$field = ?";
                        $values[] = $additionalData[$field];
                    }
                }
                
                if (!empty($fields)) {
                    $values[] = $evaluationId;
                    $sql = "UPDATE employee_evaluations SET " . implode(', ', $fields) . " WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($values);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("EvaluationManager::saveScores Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function calculateTotalScore($evaluationId) {
        try {
            // Get weighted scores
            $stmt = $this->pdo->prepare("
                SELECT SUM(es.score * ec.weight) as weighted_sum,
                       SUM(ec.max_score * ec.weight) as max_weighted_sum
                FROM evaluation_scores es
                JOIN evaluation_criteria ec ON ec.id = es.criteria_id
                WHERE es.evaluation_id = ?
            ");
            $stmt->execute([$evaluationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalScore = $result['weighted_sum'] ?? 0;
            $maxScore = $result['max_weighted_sum'] ?? 1;
            $percentage = ($maxScore > 0) ? ($totalScore / $maxScore) * 100 : 0;
            
            // Determine grade
            $grade = $this->getGradeFromPercentage($percentage);
            
            // Update evaluation
            $stmt = $this->pdo->prepare("
                UPDATE employee_evaluations 
                SET total_score = ?, percentage = ?, grade = ?
                WHERE id = ?
            ");
            $stmt->execute([$totalScore, $percentage, $grade, $evaluationId]);
            
            return ['total_score' => $totalScore, 'percentage' => $percentage, 'grade' => $grade];
        } catch (PDOException $e) {
            error_log("EvaluationManager::calculateTotalScore Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getGradeFromPercentage($percentage) {
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 80) return 'very_good';
        if ($percentage >= 70) return 'good';
        if ($percentage >= 60) return 'acceptable';
        return 'poor';
    }
    
    public function submitEvaluation($evaluationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE employee_evaluations 
                SET status = 'submitted'
                WHERE id = ? AND status = 'draft'
            ");
            $stmt->execute([$evaluationId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("EvaluationManager::submitEvaluation Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function approveEvaluation($evaluationId, $approverId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE employee_evaluations 
                SET status = 'approved'
                WHERE id = ? AND status IN ('submitted', 'reviewed')
            ");
            $stmt->execute([$evaluationId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("EvaluationManager::approveEvaluation Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function acknowledgeEvaluation($evaluationId, $employeeComment = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE employee_evaluations 
                SET status = 'acknowledged', employee_comment = ?, acknowledged_at = NOW()
                WHERE id = ? AND status = 'approved'
            ");
            $stmt->execute([$employeeComment, $evaluationId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("EvaluationManager::acknowledgeEvaluation Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getEmployeeEvaluations($employeeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ee.*, ep.name_ar as period_name, ep.period_type,
                       ev.FirstName as evaluator_first, ev.LastName as evaluator_last
                FROM employee_evaluations ee
                JOIN evaluation_periods ep ON ep.id = ee.period_id
                JOIN tblusers ev ON ev.UserID = ee.evaluator_id
                WHERE ee.employee_id = ?
                ORDER BY ee.evaluation_date DESC
            ");
            $stmt->execute([$employeeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getEmployeeEvaluations Error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingEvaluations($evaluatorId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ee.*, ep.name_ar as period_name,
                       e.FirstName as emp_first, e.LastName as emp_last, e.Photo as emp_photo
                FROM employee_evaluations ee
                JOIN evaluation_periods ep ON ep.id = ee.period_id
                JOIN tblusers e ON e.UserID = ee.employee_id
                WHERE ee.evaluator_id = ? AND ee.status = 'draft'
                ORDER BY ee.created_at DESC
            ");
            $stmt->execute([$evaluatorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getPendingEvaluations Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ==========================================
    // PROBATION EVALUATIONS
    // ==========================================
    
    public function getEmployeesInProbation() {
        try {
            // Get employees whose contract start date is within probation period (typically 90 days)
            $stmt = $this->pdo->query("
                SELECT u.UserID, u.FirstName, u.LastName, u.Photo,
                       r.new_s_date as contract_start, r.new_e_date as contract_end,
                       DATEDIFF(CURDATE(), r.new_s_date) as days_employed,
                       jt.Name as job_title, s.Name as section_name
                FROM tblusers u
                JOIN tblremewal r ON r.Id = u.lastversion
                LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
                LEFT JOIN tblsection s ON s.Id = r.SectionID
                WHERE u.isemp = 1 
                AND DATEDIFF(CURDATE(), r.new_s_date) <= 90
                AND NOT EXISTS (
                    SELECT 1 FROM employee_evaluations ee
                    JOIN evaluation_periods ep ON ep.id = ee.period_id
                    WHERE ee.employee_id = u.UserID AND ep.period_type = 'probation'
                )
                ORDER BY r.new_s_date DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getEmployeesInProbation Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ==========================================
    // REWARDS
    // ==========================================
    
    public function createReward($data) {
        try {
            $sql = "
                INSERT INTO rewards 
                (employee_id, reward_type, title_ar, title_en, description, amount, currency, 
                 linked_evaluation_id, linked_task_id, awarded_by, awarded_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ";
            $params = [
                $data['employee_id'],
                $data['reward_type'],
                $data['title_ar'],
                $data['title_en'] ?? null,
                $data['description'] ?? null,
                $data['amount'] ?? null,
                $data['currency'] ?? 'SAR',
                $data['linked_evaluation_id'] ?? null,
                $data['linked_task_id'] ?? null,
                $data['awarded_by'],
                $data['awarded_date'] ?? date('Y-m-d')
            ];
            error_log("EvaluationManager::createReward SQL: " . $sql);
            error_log("EvaluationManager::createReward Params: " . json_encode($params));

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $lastId = $this->pdo->lastInsertId();
            error_log("EvaluationManager::createReward successful, ID: " . $lastId);
            return $lastId;
        } catch (PDOException $e) {
            error_log("EvaluationManager::createReward Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function approveReward($rewardId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE rewards SET status = 'approved' WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$rewardId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("EvaluationManager::approveReward Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deliverReward($rewardId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE rewards SET status = 'delivered' WHERE id = ? AND status = 'approved'
            ");
            $stmt->execute([$rewardId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("EvaluationManager::deliverReward Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getEmployeeRewards($employeeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, ab.FirstName as awarded_by_first, ab.LastName as awarded_by_last
                FROM rewards r
                JOIN tblusers ab ON ab.UserID = r.awarded_by
                WHERE r.employee_id = ?
                ORDER BY r.awarded_date DESC
            ");
            $stmt->execute([$employeeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getEmployeeRewards Error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingRewards() {
        try {
            $stmt = $this->pdo->query("
                SELECT r.*, 
                       e.FirstName as emp_first, e.LastName as emp_last, e.Photo as emp_photo,
                       ab.FirstName as awarded_by_first, ab.LastName as awarded_by_last
                FROM rewards r
                JOIN tblusers e ON e.UserID = r.employee_id
                JOIN tblusers ab ON ab.UserID = r.awarded_by
                ORDER BY r.created_at DESC
                LIMIT 50
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getPendingRewards Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ==========================================
    // SALARY RANGES
    // ==========================================
    
    public function getSalaryRange($sectionId, $gradeId = null, $jobTitleId = null) {
        try {
            $sql = "SELECT * FROM department_salary_ranges WHERE section_id = ?";
            $params = [$sectionId];
            
            if ($gradeId) {
                $sql .= " AND (grade_id = ? OR grade_id IS NULL)";
                $params[] = $gradeId;
            }
            if ($jobTitleId) {
                $sql .= " AND (job_title_id = ? OR job_title_id IS NULL)";
                $params[] = $jobTitleId;
            }
            
            $sql .= " ORDER BY grade_id DESC, job_title_id DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getSalaryRange Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function saveSalaryRange($data) {
        try {
            // Check if table exists, if not create it
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS department_salary_ranges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    section_id INT NOT NULL,
                    grade_id INT NULL,
                    job_title_id INT NULL,
                    min_salary DECIMAL(15,2) NOT NULL,
                    max_salary DECIMAL(15,2) NOT NULL,
                    currency VARCHAR(10) DEFAULT 'SAR',
                    effective_date DATE NOT NULL,
                    notes TEXT NULL,
                    created_by INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (section_id) REFERENCES tblsection(Id) ON DELETE CASCADE,
                    FOREIGN KEY (grade_id) REFERENCES tbljobgrade(Id) ON DELETE SET NULL,
                    FOREIGN KEY (job_title_id) REFERENCES tbljobtitle(Id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            ");

            if (isset($data['id']) && $data['id']) {
                $sql = "
                    UPDATE department_salary_ranges 
                    SET section_id = ?, grade_id = ?, job_title_id = ?, min_salary = ?, max_salary = ?, 
                        currency = ?, effective_date = ?, notes = ?
                    WHERE id = ?
                ";
                $params = [
                    $data['section_id'],
                    $data['grade_id'] ?? null,
                    $data['job_title_id'] ?? null,
                    $data['min_salary'],
                    $data['max_salary'],
                    $data['currency'] ?? 'SAR',
                    $data['effective_date'],
                    $data['notes'] ?? null,
                    $data['id']
                ];
                error_log("EvaluationManager::saveSalaryRange (UPDATE) SQL: " . $sql);
                error_log("EvaluationManager::saveSalaryRange (UPDATE) Params: " . json_encode($params));

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                error_log("EvaluationManager::saveSalaryRange (UPDATE) successful, ID: " . $data['id']);
                return $data['id'];
            } else {
                $sql = "
                    INSERT INTO department_salary_ranges 
                    (section_id, grade_id, job_title_id, min_salary, max_salary, currency, effective_date, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $params = [
                    $data['section_id'],
                    $data['grade_id'] ?? null,
                    $data['job_title_id'] ?? null,
                    $data['min_salary'],
                    $data['max_salary'],
                    $data['currency'] ?? 'SAR',
                    $data['effective_date'],
                    $data['notes'] ?? null,
                    $data['created_by'] ?? null
                ];
                error_log("EvaluationManager::saveSalaryRange (INSERT) SQL: " . $sql);
                error_log("EvaluationManager::saveSalaryRange (INSERT) Params: " . json_encode($params));

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $lastId = $this->pdo->lastInsertId();
                error_log("EvaluationManager::saveSalaryRange (INSERT) successful, ID: " . $lastId);
                return $lastId;
            }
        } catch (PDOException $e) {
            error_log("EvaluationManager::saveSalaryRange Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getAllSalaryRanges() {
        try {
            // Check if table exists, if not create it
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS department_salary_ranges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    section_id INT NOT NULL,
                    grade_id INT NULL,
                    job_title_id INT NULL,
                    min_salary DECIMAL(15,2) NOT NULL,
                    max_salary DECIMAL(15,2) NOT NULL,
                    currency VARCHAR(10) DEFAULT 'SAR',
                    effective_date DATE NOT NULL,
                    notes TEXT NULL,
                    created_by INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (section_id) REFERENCES tblsection(Id) ON DELETE CASCADE,
                    FOREIGN KEY (grade_id) REFERENCES tbljobgrade(Id) ON DELETE SET NULL,
                    FOREIGN KEY (job_title_id) REFERENCES tbljobtitle(Id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            ");

            $stmt = $this->pdo->query("
                SELECT dsr.*, s.Name as section_name, jg.Name as grade_name, jt.Name as job_title_name
                FROM department_salary_ranges dsr
                JOIN tblsection s ON s.Id = dsr.section_id
                LEFT JOIN tbljobgrade jg ON jg.Id = dsr.grade_id
                LEFT JOIN tbljobtitle jt ON jt.Id = dsr.job_title_id
                ORDER BY s.Name, jg.Name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getAllSalaryRanges Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ==========================================
    // STATISTICS
    // ==========================================
    
    public function getEvaluationStats($periodId = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END) as acknowledged,
                    AVG(percentage) as avg_percentage,
                    SUM(CASE WHEN grade = 'excellent' THEN 1 ELSE 0 END) as excellent_count,
                    SUM(CASE WHEN grade = 'very_good' THEN 1 ELSE 0 END) as very_good_count,
                    SUM(CASE WHEN grade = 'good' THEN 1 ELSE 0 END) as good_count,
                    SUM(CASE WHEN grade = 'acceptable' THEN 1 ELSE 0 END) as acceptable_count,
                    SUM(CASE WHEN grade = 'poor' THEN 1 ELSE 0 END) as poor_count
                FROM employee_evaluations
            ";
            
            if ($periodId) {
                $sql .= " WHERE period_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$periodId]);
            } else {
                $stmt = $this->pdo->query($sql);
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getEvaluationStats Error: " . $e->getMessage());
            return [ // Return default stats on error to prevent page breaking
                'total' => 0, 'draft' => 0, 'submitted' => 0, 'approved' => 0, 'acknowledged' => 0,
                'avg_percentage' => 0, 'excellent_count' => 0, 'very_good_count' => 0, 'good_count' => 0,
                'acceptable_count' => 0, 'poor_count' => 0
            ];
        }
    }
    
    public function getRewardStats($year = null) {
        try {
            $year = $year ?? date('Y');
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN reward_type = 'bonus' THEN amount ELSE 0 END) as total_bonus_amount,
                    COUNT(DISTINCT employee_id) as unique_employees
                FROM rewards
                WHERE YEAR(awarded_date) = ?
            ");
            $stmt->execute([$year]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EvaluationManager::getRewardStats Error: " . $e->getMessage());
            return [ // Return default stats on error
                'total' => 0, 'delivered' => 0, 'total_bonus_amount' => 0, 'unique_employees' => 0
            ];
        }
    }
}