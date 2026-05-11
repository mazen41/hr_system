<?php
/**
 * Vision HR - Leave Controller
 * Leave types, balance, requests, cancellation
 */

class LeaveController
{
    /**
     * GET /leaves/types
     * Get available leave types for the current employee
     */
    public static function types(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $branchId = $apiUser['branch_id'];

        $stm = $connect_pdo->prepare(
            "SELECT Id, Name, Description, type, Amount as max_days, RequiresAttachment, for_what, chose
             FROM leaveclassification
             WHERE (BranchID = :branch OR BranchID IS NULL)
               AND (state IS NULL OR state = 1)
             ORDER BY Name"
        );
        $stm->execute([':branch' => $branchId]);
        $types = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Filter by employee eligibility (for_what)
        $eligible = [];
        foreach ($types as $t) {
            $forWhat = (int) ($t['for_what'] ?? 0);
            $chose = $t['chose'] ?? null;

            $isEligible = true;
            if ($forWhat === 1 && $chose) {
                // Specific user
                $isEligible = ((int) $chose === $apiUser['id']);
            } elseif ($forWhat === 2 && $chose && !empty($apiUser['contract'])) {
                // Specific group
                $isEligible = ((int) $chose === (int) ($apiUser['contract']['GroupID'] ?? 0));
            } elseif ($forWhat === 3 && $chose && !empty($apiUser['contract'])) {
                // Specific grade
                $isEligible = ((int) $chose === (int) ($apiUser['contract']['GradeID'] ?? 0));
            } elseif ($forWhat === 4 && $chose && !empty($apiUser['contract'])) {
                // Specific section
                $isEligible = ((int) $chose === (int) ($apiUser['contract']['SectionID'] ?? 0));
            } elseif ($forWhat === 5 && $chose && !empty($apiUser['contract'])) {
                // Specific job title
                $isEligible = ((int) $chose === (int) ($apiUser['contract']['jobtitleID'] ?? 0));
            }

            if ($isEligible) {
                $eligible[] = [
                    'id'                  => (int) $t['Id'],
                    'name'                => $t['Name'],
                    'description'         => $t['Description'],
                    'max_days'            => $t['max_days'] ? (int) $t['max_days'] : null,
                    'requires_attachment' => !empty($t['RequiresAttachment']),
                    'type'                => (int) ($t['type'] ?? 0),
                ];
            }
        }

        Response::success($eligible);
    }

    /**
     * GET /leaves/balance
     * Get leave balance for the current employee
     */
    public static function balance(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $year = $_GET['year'] ?? date('Y');

        // Get all leave types
        $stm = $connect_pdo->prepare(
            "SELECT Id, Name, Amount as max_days
             FROM leaveclassification
             WHERE (BranchID = :branch OR BranchID IS NULL)
               AND (state IS NULL OR state = 1)
             ORDER BY Name"
        );
        $stm->execute([':branch' => $apiUser['branch_id']]);
        $types = $stm->fetchAll(PDO::FETCH_ASSOC);

        $balances = [];
        foreach ($types as $t) {
            // Count used days this year
            $stm2 = $connect_pdo->prepare(
                "SELECT COALESCE(SUM(day_leave), 0) as used_days
                 FROM tblleaverequest
                 WHERE UserID = :uid
                   AND leavetype = :tid
                   AND status = 1
                   AND YEAR(leave_start_date) = :year"
            );
            $stm2->execute([
                ':uid'  => $apiUser['id'],
                ':tid'  => $t['Id'],
                ':year' => $year,
            ]);
            $used = (int) $stm2->fetch(PDO::FETCH_ASSOC)['used_days'];

            // Count pending days
            $stm3 = $connect_pdo->prepare(
                "SELECT COALESCE(SUM(day_leave), 0) as pending_days
                 FROM tblleaverequest
                 WHERE UserID = :uid
                   AND leavetype = :tid
                   AND status IS NULL
                   AND YEAR(leave_start_date) = :year"
            );
            $stm3->execute([
                ':uid'  => $apiUser['id'],
                ':tid'  => $t['Id'],
                ':year' => $year,
            ]);
            $pending = (int) $stm3->fetch(PDO::FETCH_ASSOC)['pending_days'];

            $maxDays = $t['max_days'] ? (int) $t['max_days'] : null;
            $remaining = $maxDays !== null ? max(0, $maxDays - $used) : null;

            $balances[] = [
                'leave_type_id'   => (int) $t['Id'],
                'leave_type_name' => $t['Name'],
                'max_days'        => $maxDays,
                'used_days'       => $used,
                'pending_days'    => $pending,
                'remaining_days'  => $remaining,
                'year'            => (int) $year,
            ];
        }

        Response::success($balances);
    }

    /**
     * POST /leaves/request
     * Submit a new leave request
     */
    public static function createRequest(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('leave_type_id', 'نوع الإجازة')
          ->integer('leave_type_id')
          ->required('start_date', 'تاريخ البداية')
          ->date('start_date')
          ->required('end_date', 'تاريخ النهاية')
          ->date('end_date');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $leaveTypeId = (int) $body['leave_type_id'];
        $startDate = $body['start_date'];
        $endDate = $body['end_date'];
        $reason = sanitizingData($body['reason'] ?? '');

        // Validate dates
        if (strtotime($endDate) < strtotime($startDate)) {
            Response::validationError(['end_date' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية']);
        }

        // Calculate days
        $days = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

        // Validate leave type exists
        $stm = $connect_pdo->prepare(
            "SELECT Id, Name, Amount as max_days, RequiresAttachment
             FROM leaveclassification WHERE Id = :id LIMIT 1"
        );
        $stm->execute([':id' => $leaveTypeId]);
        $leaveType = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leaveType) {
            Response::notFound('نوع الإجازة غير موجود');
        }

        // Check balance
        if ($leaveType['max_days']) {
            $stm2 = $connect_pdo->prepare(
                "SELECT COALESCE(SUM(day_leave), 0) as used
                 FROM tblleaverequest
                 WHERE UserID = :uid AND leavetype = :tid AND (status IS NULL OR status = 1) AND YEAR(leave_start_date) = :year"
            );
            $stm2->execute([
                ':uid'  => $apiUser['id'],
                ':tid'  => $leaveTypeId,
                ':year' => date('Y'),
            ]);
            $used = (int) $stm2->fetch(PDO::FETCH_ASSOC)['used'];

            if (($used + $days) > (int) $leaveType['max_days']) {
                $remaining = max(0, (int) $leaveType['max_days'] - $used);
                Response::error("رصيد الإجازات غير كافٍ. المتبقي: $remaining يوم", 422);
            }
        }

        // Check for overlapping leave requests
        $stm3 = $connect_pdo->prepare(
            "SELECT Id FROM tblleaverequest
             WHERE UserID = :uid AND (status IS NULL OR status = 1)
               AND ((leave_start_date BETWEEN :s AND :e) OR (leave_end_date BETWEEN :s2 AND :e2)
                    OR (leave_start_date <= :s3 AND leave_end_date >= :e3))
             LIMIT 1"
        );
        $stm3->execute([
            ':uid' => $apiUser['id'],
            ':s' => $startDate, ':e' => $endDate,
            ':s2' => $startDate, ':e2' => $endDate,
            ':s3' => $startDate, ':e3' => $endDate,
        ]);
        if ($stm3->fetch()) {
            Response::error('يوجد طلب إجازة متداخل مع هذه الفترة', 409);
        }

        // Insert leave request
        $stm4 = $connect_pdo->prepare(
            "INSERT INTO tblleaverequest 
                (UserID, BranchID, leavetype, leave_start_date, leave_end_date, day_leave, description, status, Draft, created_by, CreatedDate, LastUpdateDate)
             VALUES 
                (:uid, :branch, :type_id, :start, :end, :days, :reason, NULL, 1, :uid2, NOW(), NOW())"
        );
        $stm4->execute([
            ':uid'     => $apiUser['id'],
            ':branch'  => $apiUser['branch_id'],
            ':type_id' => $leaveTypeId,
            ':start'   => $startDate,
            ':end'     => $endDate,
            ':days'    => $days,
            ':reason'  => $reason,
            ':uid2'    => $apiUser['id'],
        ]);

        $leaveId = (int) $connect_pdo->lastInsertId();

        $auditLog->logCreate($apiUser['id'], 'tblleaverequest', $leaveId, [
            'leave_type' => $leaveType['Name'], 'start' => $startDate, 'end' => $endDate, 'days' => $days
        ]);

        // Create notification for manager
        if ($apiUser['manager_id']) {
            $connect_pdo->prepare(
                "INSERT INTO notifications (user_id, title, body, type, entity_type, entity_id, created_at)
                 VALUES (:uid, :title, :body, 'leave', 'tblleaverequest', :ref_id, NOW())"
            )->execute([
                ':uid'    => $apiUser['manager_id'],
                ':title'  => 'طلب إجازة جديد',
                ':body'   => $apiUser['name'] . ' طلب إجازة ' . $leaveType['Name'] . ' من ' . $startDate . ' إلى ' . $endDate,
                ':ref_id' => $leaveId,
            ]);
        }

        Response::created([
            'id'         => $leaveId,
            'leave_type' => $leaveType['Name'],
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'days'       => $days,
            'status'     => 'pending',
        ], 'تم تقديم طلب الإجازة بنجاح');
    }

    /**
     * GET /leaves/requests
     * Get current employee's leave requests
     */
    public static function listRequests(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $pagination = Validator::pagination();
        $status = $_GET['status'] ?? null; // 0=pending, 1=approved, 2=rejected

        $where = "WHERE l.UserID = :uid";
        $params = [':uid' => $apiUser['id']];

        if ($status !== null && $status !== '') {
            $where .= " AND l.Status = :status";
            $params[':status'] = (int) $status;
        }

        $stm = $connect_pdo->prepare(
            "SELECT l.Id, l.leavetype, l.leave_start_date, l.leave_end_date, l.day_leave, l.description,
                    l.status, l.path, l.CreatedDate, l.LastUpdateDate,
                    lc.Name as LeaveTypeName
             FROM tblleaverequest l
             LEFT JOIN leaveclassification lc ON lc.Id = l.leavetype
             $where
             ORDER BY l.CreatedDate DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $val) {
            $stm->bindValue($k, $val);
        }
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $requests = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $stm2 = $connect_pdo->prepare("SELECT COUNT(*) as total FROM tblleaverequest l $where");
        foreach ($params as $k => $val) {
            $stm2->bindValue($k, $val);
        }
        $stm2->execute();
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $formatted = array_map(function ($r) {
            $st = $r['status'];
            $statusLabel = $st === null ? 'pending' : ($st == 1 ? 'approved' : ($st == 2 ? 'rejected' : 'pending'));
            return [
                'id'              => (int) $r['Id'],
                'leave_type'      => $r['LeaveTypeName'],
                'leave_type_id'   => (int) $r['leavetype'],
                'start_date'      => $r['leave_start_date'],
                'end_date'        => $r['leave_end_date'],
                'days'            => (int) $r['day_leave'],
                'reason'          => $r['description'],
                'status'          => $statusLabel,
                'status_code'     => (int) ($r['status'] ?? 0),
                'attachment'      => $r['path'],
                'created_date'    => $r['CreatedDate'],
                'updated_date'    => $r['LastUpdateDate'],
            ];
        }, $requests);

        Response::paginated($formatted, $total, $pagination['page'], $pagination['per_page']);
    }

    /**
     * GET /leaves/requests/:id
     * Get leave request details
     */
    public static function getRequest(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare(
            "SELECT l.*, lc.Name as LeaveTypeName, lc.RequiresAttachment
             FROM tblleaverequest l
             LEFT JOIN leaveclassification lc ON lc.Id = l.leavetype
             WHERE l.Id = :id LIMIT 1"
        );
        $stm->execute([':id' => $id]);
        $leave = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            Response::notFound('طلب الإجازة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $leave['UserID']);

        $st = $leave['status'];
        $statusLabel = $st === null ? 'pending' : ($st == 1 ? 'approved' : ($st == 2 ? 'rejected' : 'pending'));

        Response::success([
            'id'                  => (int) $leave['Id'],
            'leave_type'          => $leave['LeaveTypeName'],
            'leave_type_id'       => (int) $leave['leavetype'],
            'start_date'          => $leave['leave_start_date'],
            'end_date'            => $leave['leave_end_date'],
            'days'                => (int) $leave['day_leave'],
            'reason'              => $leave['description'],
            'status'              => $statusLabel,
            'attachment'          => $leave['path'],
            'requires_attachment' => !empty($leave['RequiresAttachment']),
            'created_date'        => $leave['CreatedDate'],
            'updated_date'        => $leave['LastUpdateDate'],
        ]);
    }

    /**
     * DELETE /leaves/requests/:id
     * Cancel a pending leave request
     */
    public static function cancelRequest(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare("SELECT Id, UserID, status FROM tblleaverequest WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $leave = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            Response::notFound('طلب الإجازة غير موجود');
        }

        if ((int) $leave['UserID'] !== $apiUser['id']) {
            Response::forbidden('لا يمكنك إلغاء طلب إجازة لموظف آخر');
        }

        if ($leave['status'] !== null) {
            Response::error('لا يمكن إلغاء طلب تمت معالجته', 409);
        }

        $stm2 = $connect_pdo->prepare("DELETE FROM tblleaverequest WHERE Id = :id AND UserID = :uid AND status IS NULL");
        $stm2->execute([':id' => $id, ':uid' => $apiUser['id']]);

        $auditLog->logDelete($apiUser['id'], 'tblleaverequest', $id, $leave);

        Response::success(null, 'تم إلغاء طلب الإجازة بنجاح');
    }
}
