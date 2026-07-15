<?php
/**
 * Organizational Chart Manager
 * Handles hierarchical org structure with real-time employee presence
 */

class OrgChartManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get full organizational hierarchy as nested tree
     */
    public function getOrgTree($includeEmployees = true, $includePresence = true) {
        $statusSql = "
            COALESCE(
                CASE WHEN ep.manual_status IS NOT NULL AND (ep.manual_status_until IS NULL OR ep.manual_status_until > NOW()) 
                     THEN ep.manual_status 
                     ELSE NULL 
                END,
                ep.auto_status
            ) as manager_status
        ";

        // Get all org nodes
        $stmt = $this->pdo->query("
            SELECT os.*, 
                   m.FirstName as manager_first, m.LastName as manager_last, m.Photo as manager_photo,
                   $statusSql
            FROM org_structure os
            LEFT JOIN tblusers m ON m.UserID = os.manager_id
            LEFT JOIN employee_presence ep ON ep.user_id = os.manager_id
            WHERE os.is_active = 1
            ORDER BY os.level, os.sort_order, os.name_ar
        ");
        $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build tree
        $tree = $this->buildTree($nodes, null);
        
        // Add employees to each node if requested
        if ($includeEmployees) {
            $tree = $this->addEmployeesToTree($tree, $includePresence);
        }
        
        return $tree;
    }
    
    /**
     * Build tree recursively
     */
    private function buildTree($nodes, $parentId) {
        $tree = [];
        
        foreach ($nodes as $node) {
            if ($node['parent_id'] == $parentId) {
                $children = $this->buildTree($nodes, $node['id']);
                $node['children'] = $children;
                $node['employee_count'] = 0;
                $tree[] = $node;
            }
        }
        
        return $tree;
    }
    
    /**
     * Add employees to tree nodes
     */
    private function addEmployeesToTree($tree, $includePresence = true) {
        foreach ($tree as &$node) {
            // Get employees for this node (by section)
            if ($node['section_id']) {
                $node['employees'] = $this->getNodeEmployees($node['section_id'], $includePresence);
                $node['employee_count'] = count($node['employees']);
            }
            
            // Recursively process children
            if (!empty($node['children'])) {
                $node['children'] = $this->addEmployeesToTree($node['children'], $includePresence);
                
                // Sum up employee counts from children
                foreach ($node['children'] as $child) {
                    $node['employee_count'] += $child['employee_count'];
                }
            }
        }
        
        return $tree;
    }
    
    /**
     * Get employees for a section/node
     */
    public function getNodeEmployees($sectionId, $includePresence = true) {
        $statusSql = "
            COALESCE(
                CASE WHEN ep.manual_status IS NOT NULL AND (ep.manual_status_until IS NULL OR ep.manual_status_until > NOW()) 
                     THEN ep.manual_status 
                     ELSE NULL 
                END,
                ep.auto_status
            ) as current_status
        ";

        $sql = "
            SELECT u.UserID, u.FirstName, u.LastName, u.Photo, u.UserEmail,
                   jt.Name as job_title, jg.Name as grade
        ";
        
        if ($includePresence) {
            $sql .= ", $statusSql, ep.manual_status_note, ep.last_check_in, ep.last_check_out";
        }
        
        $sql .= "
            FROM tblusers u
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
            LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
        ";
        
        if ($includePresence) {
            $sql .= " LEFT JOIN employee_presence ep ON ep.user_id = u.UserID";
        }
        
        $sql .= "
            WHERE r.SectionID = ? AND u.isemp = 1
            ORDER BY u.FirstName, u.LastName
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get flat list of all departments/sections with employee counts
     */
    public function getDepartmentList() {
        $stmt = $this->pdo->query("
            SELECT s.Id, s.Name,
                   COUNT(DISTINCT u.UserID) as employee_count,
                   os.manager_id,
                   m.FirstName as manager_first, m.LastName as manager_last
            FROM tblsection s
            LEFT JOIN tblremewal r ON r.SectionID = s.Id
            LEFT JOIN tblusers u ON u.lastversion = r.Id AND u.isemp = 1
            LEFT JOIN org_structure os ON os.section_id = s.Id
            LEFT JOIN tblusers m ON m.UserID = os.manager_id
            GROUP BY s.Id, s.Name, os.manager_id, m.FirstName, m.LastName
            ORDER BY s.Name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get presence summary for org chart
     */
    public function getPresenceSummary($sectionId = null) {
        $statusSql = "
            COALESCE(
                CASE WHEN ep.manual_status IS NOT NULL AND (ep.manual_status_until IS NULL OR ep.manual_status_until > NOW()) 
                     THEN ep.manual_status 
                     ELSE NULL 
                END,
                ep.auto_status
            )
        ";

        $sql = "
            SELECT 
                COUNT(DISTINCT u.UserID) as total_employees,
                SUM(CASE WHEN $statusSql = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN $statusSql = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN $statusSql = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN $statusSql = 'on_leave' THEN 1 ELSE 0 END) as on_leave,
                SUM(CASE WHEN $statusSql = 'external_task' THEN 1 ELSE 0 END) as external_task,
                SUM(CASE WHEN $statusSql = 'in_meeting' THEN 1 ELSE 0 END) as in_meeting,
                SUM(CASE WHEN $statusSql IS NULL OR $statusSql = 'off_duty' THEN 1 ELSE 0 END) as off_duty
            FROM tblusers u
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN employee_presence ep ON ep.user_id = u.UserID
            WHERE u.isemp = 1
        ";
        $params = [];
        
        if ($sectionId) {
            $sql .= " AND r.SectionID = ?";
            $params[] = $sectionId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update employee presence status
     */
    public function updatePresence($userId, $status, $note = null, $until = null, $setBy = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_presence 
            (user_id, manual_status, manual_status_note, manual_status_until, manual_status_set_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            manual_status = VALUES(manual_status),
            manual_status_note = VALUES(manual_status_note),
            manual_status_until = VALUES(manual_status_until),
            manual_status_set_by = VALUES(manual_status_set_by),
            updated_at = NOW()
        ");
        
        return $stmt->execute([$userId, $status, $note, $until, $setBy]);
    }
    
    /**
     * Clear manual presence status
     */
    public function clearManualPresence($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE employee_presence 
            SET manual_status = NULL, manual_status_note = NULL, 
                manual_status_until = NULL, manual_status_set_by = NULL
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Update auto presence from attendance
     */
    public function updateAutoPresence($userId, $status, $checkInTime = null, $checkOutTime = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_presence 
            (user_id, auto_status, last_check_in, last_check_out)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            auto_status = VALUES(auto_status),
            last_check_in = COALESCE(VALUES(last_check_in), last_check_in),
            last_check_out = COALESCE(VALUES(last_check_out), last_check_out),
            updated_at = NOW()
        ");
        
        return $stmt->execute([$userId, $status, $checkInTime, $checkOutTime]);
    }
    
    /**
     * Bulk update presence for a meeting/event
     */
    public function setMeetingPresence($userIds, $meetingTitle, $endTime = null, $setBy = null) {
        $results = [];
        foreach ($userIds as $userId) {
            $results[$userId] = $this->updatePresence($userId, 'in_meeting', $meetingTitle, $endTime, $setBy);
        }
        return $results;
    }
    
    /**
     * Get employee presence details
     */
    public function getEmployeePresence($userId) {
        $statusSql = "
            COALESCE(
                CASE WHEN ep.manual_status IS NOT NULL AND (ep.manual_status_until IS NULL OR ep.manual_status_until > NOW()) 
                     THEN ep.manual_status 
                     ELSE NULL 
                END,
                ep.auto_status
            ) as current_status
        ";

        $stmt = $this->pdo->prepare("
            SELECT ep.*, $statusSql, u.FirstName, u.LastName, u.Photo,
                   et.title_ar as current_task_title, et.task_type, et.location_name
            FROM employee_presence ep
            JOIN tblusers u ON u.UserID = ep.user_id
            LEFT JOIN external_tasks et ON et.id = ep.current_task_id
            WHERE ep.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create org structure node
     */
    public function createNode($data) {
        // Calculate level and path
        $level = 0;
        $path = '';
        
        $parentId = !empty($data['parent_id']) ? $data['parent_id'] : null;
        
        if ($parentId) {
            $parent = $this->getNode($parentId);
            if ($parent) {
                $level = $parent['level'] + 1;
                $path = $parent['path'] ? $parent['path'] . '/' : '';
            }
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO org_structure 
            (parent_id, node_type, name_ar, name_en, code, description_ar, description_en,
             section_id, branch_id, manager_id, sort_order, level, path, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $parentId,
            $data['node_type'],
            $data['name_ar'],
            $data['name_en'] ?? null,
            $data['code'] ?? null,
            $data['description_ar'] ?? null,
            $data['description_en'] ?? null,
            $data['section_id'] ?? null,
            $data['branch_id'] ?? null,
            $data['manager_id'] ?? null,
            $data['sort_order'] ?? 0,
            $level,
            $path
        ]);
        
        $nodeId = $this->pdo->lastInsertId();
        
        // Update path with new ID
        $fullPath = $path . $nodeId;
        $stmt = $this->pdo->prepare("UPDATE org_structure SET path = ? WHERE id = ?");
        $stmt->execute([$fullPath, $nodeId]);
        
        return $nodeId;
    }
    
    /**
     * Update org structure node
     */
    public function updateNode($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = [
            'parent_id', 'node_type', 'name_ar', 'name_en', 'code',
            'description_ar', 'description_en', 'section_id', 'branch_id',
            'manager_id', 'sort_order', 'is_active'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field] === '' ? null : $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $id;
        $sql = "UPDATE org_structure SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        // If parent changed, recalculate level and path
        if (array_key_exists('parent_id', $data)) {
            $this->recalculatePaths($id);
        }
        
        return $result;
    }
    
    /**
     * Recalculate paths for a node and its children
     */
    private function recalculatePaths($nodeId) {
        $node = $this->getNode($nodeId);
        if (!$node) return;
        
        $level = 0;
        $path = '';
        
        if ($node['parent_id']) {
            $parent = $this->getNode($node['parent_id']);
            if ($parent) {
                $level = $parent['level'] + 1;
                $path = $parent['path'] ? $parent['path'] . '/' : '';
            }
        }
        
        $fullPath = $path . $nodeId;
        
        $stmt = $this->pdo->prepare("UPDATE org_structure SET level = ?, path = ? WHERE id = ?");
        $stmt->execute([$level, $fullPath, $nodeId]);
        
        // Recursively update children
        $stmt = $this->pdo->prepare("SELECT id FROM org_structure WHERE parent_id = ?");
        $stmt->execute([$nodeId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($children as $childId) {
            $this->recalculatePaths($childId);
        }
    }
    
    /**
     * Delete org structure node
     */
    public function deleteNode($id) {
        // Check for children
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM org_structure WHERE parent_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'لا يمكن حذف عقدة لها عناصر فرعية'];
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM org_structure WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => true, 'message' => 'تم حذف العنصر بنجاح'];
    }
    
    /**
     * Get single node
     */
    public function getNode($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM org_structure WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Move employee to different section
     */
    public function moveEmployee($userId, $newSectionId) {
        // Get current renewal
        $stmt = $this->pdo->prepare("SELECT lastversion FROM tblusers WHERE UserID = ?");
        $stmt->execute([$userId]);
        $renewalId = $stmt->fetchColumn();
        
        if (!$renewalId) {
            return ['success' => false, 'message' => 'لم يتم العثور على بيانات الموظف'];
        }
        
        $stmt = $this->pdo->prepare("UPDATE tblremewal SET SectionID = ? WHERE Id = ?");
        $stmt->execute([$newSectionId, $renewalId]);
        
        return ['success' => true, 'message' => 'تم نقل الموظف بنجاح'];
    }
    
    /**
     * Change employee's manager
     */
    public function changeManager($userId, $newManagerId) {
        $stmt = $this->pdo->prepare("UPDATE tblusers SET manager = ? WHERE UserID = ?");
        $stmt->execute([$newManagerId, $userId]);
        
        return ['success' => true, 'message' => 'تم تغيير المدير بنجاح'];
    }
    
    /**
     * Sync org structure from existing sections
     */
    public function syncFromSections() {
        // Get all sections not yet in org_structure (ensure uniqueness)
        $stmt = $this->pdo->query("
            SELECT s.* FROM tblsection s
            WHERE s.Id NOT IN (SELECT DISTINCT section_id FROM org_structure WHERE section_id IS NOT NULL)
            ORDER BY s.Name
        ");
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $created = 0;
        $syncedSections = [];
        
        foreach ($sections as $section) {
            // Double-check section doesn't exist before creating
            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM org_structure WHERE section_id = ?");
            $checkStmt->execute([$section['Id']]);
            
            if ($checkStmt->fetchColumn() == 0) {
                $nodeId = $this->createNode([
                    'node_type' => 'department',
                    'name_ar' => $section['Name'],
                    'name_en' => $section['Name_en'] ?? null,
                    'section_id' => $section['Id']
                ]);
                if ($nodeId) {
                    $created++;
                    $syncedSections[] = $section['Name'];
                }
            }
        }
        
        return ['created' => $created, 'sections' => $syncedSections];
    }
    
    /**
     * Get presence status options
     */
    public function getPresenceStatusOptions() {
        return [
            'auto' => [
                'present' => ['ar' => 'حاضر', 'en' => 'Present', 'color' => '#10b981'],
                'absent' => ['ar' => 'غائب', 'en' => 'Absent', 'color' => '#ef4444'],
                'late' => ['ar' => 'متأخر', 'en' => 'Late', 'color' => '#f59e0b'],
                'on_leave' => ['ar' => 'في إجازة', 'en' => 'On Leave', 'color' => '#8b5cf6'],
                'off_duty' => ['ar' => 'خارج الدوام', 'en' => 'Off Duty', 'color' => '#6b7280']
            ],
            'manual' => [
                'in_meeting' => ['ar' => 'في اجتماع', 'en' => 'In Meeting', 'color' => '#3b82f6'],
                'external_task' => ['ar' => 'مهمة خارجية', 'en' => 'External Task', 'color' => '#06b6d4'],
                'training' => ['ar' => 'تدريب', 'en' => 'Training', 'color' => '#8b5cf6'],
                'break' => ['ar' => 'استراحة', 'en' => 'Break', 'color' => '#f59e0b'],
                'busy' => ['ar' => 'مشغول', 'en' => 'Busy', 'color' => '#ef4444'],
                'available' => ['ar' => 'متاح', 'en' => 'Available', 'color' => '#10b981'],
                'away' => ['ar' => 'بعيد', 'en' => 'Away', 'color' => '#6b7280']
            ]
        ];
    }
}
