<?php
/**
 * Vision HR - Advance Controller
 * Employee salary advances (loans) - request and list
 */

class AdvanceController
{
    /**
     * POST /advances/request
     * Submit a new advance request
     */
    public static function createRequest(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('amount', 'المبلغ')
          ->numeric('amount', 'المبلغ')
          ->min('amount', 1, 'المبلغ')
          ->required('due_date', 'تاريخ الاستحقاق')
          ->date('due_date');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $amount = (float) $body['amount'];
        $dueDate = $body['due_date'];
        $description = sanitizingData($body['description'] ?? '');
        $type = isset($body['type']) ? (int) $body['type'] : 1;

        // Check for existing pending advance
        $stm = $connect_pdo->prepare(
            "SELECT Id FROM tblempadvances
             WHERE UserID = :uid AND Status IS NULL
             LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        if ($stm->fetch()) {
            Response::error('لديك طلب سلفة معلق بالفعل. يرجى انتظار الرد عليه', 409);
        }

        // Insert advance request
        $stm2 = $connect_pdo->prepare(
            "INSERT INTO tblempadvances 
                (UserID, BranchID, Amount, currency, DueDate, Status, type, description, created_by, CreatedDate)
             VALUES 
                (:uid, :branch, :amount, 'SAR', :due, NULL, :type, :desc, :uid, NOW())"
        );
        $stm2->execute([
            ':uid'    => $apiUser['id'],
            ':branch' => $apiUser['branch_id'],
            ':amount' => $amount,
            ':due'    => $dueDate,
            ':type'   => $type,
            ':desc'   => $description,
        ]);

        $advanceId = (int) $connect_pdo->lastInsertId();

        $auditLog->logCreate($apiUser['id'], 'tblempadvances', $advanceId, [
            'amount' => $amount, 'due_date' => $dueDate
        ]);

        // Notify manager
        if ($apiUser['manager_id']) {
            $connect_pdo->prepare(
                "INSERT INTO notifications (user_id, title, body, type, reference_table, reference_id, created_at)
                 VALUES (:uid, :title, :body, 'advance', 'tblempadvances', :ref_id, NOW())"
            )->execute([
                ':uid'    => $apiUser['manager_id'],
                ':title'  => 'طلب سلفة جديد',
                ':body'   => $apiUser['name'] . ' طلب سلفة بمبلغ ' . number_format($amount, 2) . ' ر.س',
                ':ref_id' => $advanceId,
            ]);
        }

        Response::created([
            'id'          => $advanceId,
            'amount'      => $amount,
            'due_date'    => $dueDate,
            'description' => $description,
            'status'      => 'pending',
        ], 'تم تقديم طلب السلفة بنجاح');
    }

    /**
     * GET /advances/requests
     * Get current employee's advance requests
     */
    public static function listRequests(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $pagination = Validator::pagination();

        $stm = $connect_pdo->prepare(
            "SELECT a.Id, a.Amount, a.currency, a.DueDate, a.Status, a.type,
                    a.description, a.attachment, a.CreatedDate, a.LastUpdateDate,
                    appr.FirstName as ApprovedByFirst, appr.LastName as ApprovedByLast
             FROM tblempadvances a
             LEFT JOIN tblusers appr ON appr.UserID = a.approved_by
             WHERE a.UserID = :uid
             ORDER BY a.CreatedDate DESC
             LIMIT :limit OFFSET :offset"
        );
        $stm->bindValue(':uid', $apiUser['id'], PDO::PARAM_INT);
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $advances = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $stm2 = $connect_pdo->prepare("SELECT COUNT(*) as total FROM tblempadvances WHERE UserID = :uid");
        $stm2->execute([':uid' => $apiUser['id']]);
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $statusMap = [null => 'pending', 0 => 'pending', 1 => 'approved', 2 => 'rejected'];

        $formatted = array_map(function ($a) use ($statusMap) {
            $st = $a['Status'];
            return [
                'id'           => (int) $a['Id'],
                'amount'       => (float) $a['Amount'],
                'currency'     => $a['currency'] ?? 'SAR',
                'due_date'     => $a['DueDate'],
                'status'       => $statusMap[$st] ?? 'pending',
                'type'         => (int) ($a['type'] ?? 1),
                'description'  => $a['description'],
                'attachment'   => $a['attachment'],
                'approved_by'  => $a['ApprovedByFirst']
                    ? trim($a['ApprovedByFirst'] . ' ' . ($a['ApprovedByLast'] ?? ''))
                    : null,
                'created_date' => $a['CreatedDate'],
                'updated_date' => $a['LastUpdateDate'],
            ];
        }, $advances);

        Response::paginated($formatted, $total, $pagination['page'], $pagination['per_page']);
    }

    /**
     * GET /advances/requests/:id
     * Get advance request details
     */
    public static function getRequest(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare(
            "SELECT a.*, appr.FirstName as ApprovedByFirst, appr.LastName as ApprovedByLast
             FROM tblempadvances a
             LEFT JOIN tblusers appr ON appr.UserID = a.approved_by
             WHERE a.Id = :id LIMIT 1"
        );
        $stm->execute([':id' => $id]);
        $advance = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$advance) {
            Response::notFound('طلب السلفة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $advance['UserID']);

        $statusMap = [null => 'pending', 0 => 'pending', 1 => 'approved', 2 => 'rejected'];

        Response::success([
            'id'           => (int) $advance['Id'],
            'amount'       => (float) $advance['Amount'],
            'currency'     => $advance['currency'] ?? 'SAR',
            'due_date'     => $advance['DueDate'],
            'status'       => $statusMap[$advance['Status']] ?? 'pending',
            'type'         => (int) ($advance['type'] ?? 1),
            'description'  => $advance['description'],
            'attachment'   => $advance['attachment'],
            'approved_by'  => $advance['ApprovedByFirst']
                ? trim($advance['ApprovedByFirst'] . ' ' . ($advance['ApprovedByLast'] ?? ''))
                : null,
            'created_date' => $advance['CreatedDate'],
            'updated_date' => $advance['LastUpdateDate'],
        ]);
    }
}
