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
            "SELECT Id, Name, Description, type, max_days, RequiresAttachment, for_what, chose
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
            "SELECT Id, Name, max_days
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
                "SELECT COALESCE(SUM(Days), 0) as used_days
                 FROM tblleave
                 WHERE UserID = :uid
                   AND LeaveTypeID = :tid
                   AND Status = 1
                   AND YEAR(StartDate) = :year"
            );
            $stm2->execute([
                ':uid'  => $apiUser['id'],
                ':tid'  => $t['Id'],
                ':year' => $year,
            ]);
            $used = (int) $stm2->fetch(PDO::FETCH_ASSOC)['used_days'];

            // Count pending days
            $stm3 = $connect_pdo->prepare(
                "SELECT COALESCE(SUM(Days), 0) as pending_days
                 FROM tblleave
                 WHERE UserID = :uid
                   AND LeaveTypeID = :tid
                   AND Status = 0
                   AND YEAR(StartDate) = :year"
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
            "SELECT Id, Name, max_days, RequiresAttachment
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
                "SELECT COALESCE(SUM(Days), 0) as used
                 FROM tblleave
                 WHERE UserID = :uid AND LeaveTypeID = :tid AND Status IN (0, 1) AND YEAR(StartDate) = :year"
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
            "SELECT Id FROM tblleave
             WHERE UserID = :uid AND Status IN (0, 1)
               AND ((StartDate BETWEEN :s AND :e) OR (EndDate BETWEEN :s2 AND :e2)
                    OR (StartDate <= :s3 AND EndDate >= :e3))
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
            "INSERT INTO tblleave 
                (UserID, BranchID, LeaveTypeID, StartDate, EndDate, Days, Reason, Status, type, created_by, CreatedDate)
             VALUES 
                (:uid, :branch, :type_id, :start, :end, :days, :reason, 0, 1, :uid, NOW())"
        );
        $stm4->execute([
            ':uid'     => $apiUser['id'],
            ':branch'  => $apiUser['branch_id'],
            ':type_id' => $leaveTypeId,
            ':start'   => $startDate,
            ':end'     => $endDate,
            ':days'    => $days,
            ':reason'  => $reason,
        ]);

        $leaveId = (int) $connect_pdo->lastInsertId();

        $auditLog->logCreate($apiUser['id'], 'tblleave', $leaveId, [
            'leave_type' => $leaveType['Name'], 'start' => $startDate, 'end' => $endDate, 'days' => $days
        ]);

        // Create notification for manager
        if ($apiUser['manager_id']) {
            $connect_pdo->prepare(
                "INSERT INTO notifications (user_id, title, body, type, reference_table, reference_id, created_at)
                 VALUES (:uid, :title, :body, 'leave', 'tblleave', :ref_id, NOW())"
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
            "SELECT l.Id, l.LeaveTypeID, l.StartDate, l.EndDate, l.Days, l.Reason,
                    l.Status, l.attachment, l.CreatedDate, l.LastUpdateDate,
                    lc.Name as LeaveTypeName,
                    appr.FirstName as ApprovedByFirst, appr.LastName as ApprovedByLast
             FROM tblleave l
             LEFT JOIN leaveclassification lc ON lc.Id = l.LeaveTypeID
             LEFT JOIN tblusers appr ON appr.UserID = l.approved_by
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
        $stm2 = $connect_pdo->prepare("SELECT COUNT(*) as total FROM tblleave l $where");
        foreach ($params as $k => $val) {
            $stm2->bindValue($k, $val);
        }
        $stm2->execute();
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $statusMap = [0 => 'pending', 1 => 'approved', 2 => 'rejected'];

        $formatted = array_map(function ($r) use ($statusMap) {
            return [
                'id'              => (int) $r['Id'],
                'leave_type'      => $r['LeaveTypeName'],
                'leave_type_id'   => (int) $r['LeaveTypeID'],
                'start_date'      => $r['StartDate'],
                'end_date'        => $r['EndDate'],
                'days'            => (int) $r['Days'],
                'reason'          => $r['Reason'],
                'status'          => $statusMap[(int) ($r['Status'] ?? 0)] ?? 'pending',
                'status_code'     => (int) ($r['Status'] ?? 0),
                'attachment'      => $r['attachment'],
                'approved_by'     => $r['ApprovedByFirst']
                    ? trim($r['ApprovedByFirst'] . ' ' . ($r['ApprovedByLast'] ?? ''))
                    : null,
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
            "SELECT l.*, lc.Name as LeaveTypeName, lc.RequiresAttachment,
                    appr.FirstName as ApprovedByFirst, appr.LastName as ApprovedByLast
             FROM tblleave l
             LEFT JOIN leaveclassification lc ON lc.Id = l.LeaveTypeID
             LEFT JOIN tblusers appr ON appr.UserID = l.approved_by
             WHERE l.Id = :id LIMIT 1"
        );
        $stm->execute([':id' => $id]);
        $leave = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            Response::notFound('طلب الإجازة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $leave['UserID']);

        $statusMap = [0 => 'pending', 1 => 'approved', 2 => 'rejected'];

        Response::success([
            'id'                  => (int) $leave['Id'],
            'leave_type'          => $leave['LeaveTypeName'],
            'leave_type_id'       => (int) $leave['LeaveTypeID'],
            'start_date'          => $leave['StartDate'],
            'end_date'            => $leave['EndDate'],
            'days'                => (int) $leave['Days'],
            'reason'              => $leave['Reason'],
            'status'              => $statusMap[(int) ($leave['Status'] ?? 0)] ?? 'pending',
            'attachment'          => $leave['attachment'],
            'requires_attachment' => !empty($leave['RequiresAttachment']),
            'approved_by'         => $leave['ApprovedByFirst']
                ? trim($leave['ApprovedByFirst'] . ' ' . ($leave['ApprovedByLast'] ?? ''))
                : null,
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

        $stm = $connect_pdo->prepare("SELECT Id, UserID, Status FROM tblleave WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $leave = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            Response::notFound('طلب الإجازة غير موجود');
        }

        if ((int) $leave['UserID'] !== $apiUser['id']) {
            Response::forbidden('لا يمكنك إلغاء طلب إجازة لموظف آخر');
        }

        if ((int) $leave['Status'] !== 0) {
            Response::error('لا يمكن إلغاء طلب تمت معالجته', 409);
        }

        $stm2 = $connect_pdo->prepare("DELETE FROM tblleave WHERE Id = :id AND UserID = :uid AND Status = 0");
        $stm2->execute([':id' => $id, ':uid' => $apiUser['id']]);

        $auditLog->logDelete($apiUser['id'], 'tblleave', $id, $leave);

        Response::success(null, 'تم إلغاء طلب الإجازة بنجاح');
    }
}
