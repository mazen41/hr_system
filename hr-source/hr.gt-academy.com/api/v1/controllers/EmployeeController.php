<?php
/**
 * Vision HR - Employee Controller
 * Employee Self-Service: profile, documents, salary slips, certificates, experience
 */

class EmployeeController
{
    /**
     * GET /employee/profile
     * Get current employee's full profile
     */
    public static function profile(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT u.UserID, u.UserEmail, u.FirstName, u.SecondName, u.LastName,
                    u.Photo, u.Phone, u.ohter_phone, u.Sex, u.marital_status,
                    u.user_address, u.user_insurance, u.user_bank_name, u.user_account_bank,
                    u.HealthCondition, u.Note,
                    u.Id_h, u.start_date_h, u.end_date_h,
                    u.Id_license, u.start_date_license, u.end_date_license,
                    u.Id_passport, u.start_date_passport, u.end_date_passport,
                    u.Id_health, u.start_date_health, u.end_date_health,
                    u.FingerID, u.CreatedDate,
                    r.SectionID, r.BranchID, r.GroupID, r.GradeID, r.shiftID,
                    r.TypeID, r.jobtitleID, r.Salary, r.Currency,
                    r.new_s_date, r.new_e_date,
                    s.Name as SectionName,
                    jt.Name as JobTitleName,
                    jg.Name as GradeName,
                    grp.Name as GroupName,
                    sh.ShiftName,
                    et.Name as EmploymentTypeName,
                    b.branch_name as BranchName,
                    ins.Name as InsuranceName,
                    mgr.FirstName as ManagerFirstName, mgr.LastName as ManagerLastName
             FROM tblusers u
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
             LEFT JOIN tblgroup grp ON grp.Id = r.GroupID
             LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
             LEFT JOIN tblemploymenttype et ON et.Id = r.TypeID
             LEFT JOIN branches b ON b.branch_id = r.BranchID
             LEFT JOIN tbinsurance ins ON ins.Id = u.user_insurance
             LEFT JOIN tblusers mgr ON mgr.UserID = u.manager
             WHERE u.UserID = :uid
             LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $emp = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            Response::notFound('بيانات الموظف غير موجودة');
        }

        Response::success([
            'personal' => [
                'id'               => (int) $emp['UserID'],
                'email'            => $emp['UserEmail'],
                'first_name'       => $emp['FirstName'],
                'second_name'      => $emp['SecondName'],
                'last_name'        => $emp['LastName'],
                'full_name'        => trim($emp['FirstName'] . ' ' . ($emp['SecondName'] ?? '') . ' ' . ($emp['LastName'] ?? '')),
                'photo'            => $emp['Photo'],
                'phone'            => $emp['Phone'],
                'other_phone'      => $emp['ohter_phone'],
                'sex'              => $emp['Sex'],
                'marital_status'   => $emp['marital_status'],
                'address'          => $emp['user_address'],
                'health_condition' => $emp['HealthCondition'],
                'note'             => $emp['Note'],
                'joined_date'      => $emp['CreatedDate'],
            ],
            'employment' => [
                'branch'          => $emp['BranchName'],
                'branch_id'       => (int) ($emp['BranchID'] ?? 0),
                'section'         => $emp['SectionName'],
                'job_title'       => $emp['JobTitleName'],
                'grade'           => $emp['GradeName'],
                'group'           => $emp['GroupName'],
                'shift'           => $emp['ShiftName'],
                'employment_type' => $emp['EmploymentTypeName'],
                'salary'          => (float) ($emp['Salary'] ?? 0),
                'currency'        => $emp['Currency'] ?? 'SAR',
                'contract_start'  => $emp['new_s_date'],
                'contract_end'    => $emp['new_e_date'],
                'finger_id'       => $emp['FingerID'],
                'manager'         => $emp['ManagerFirstName']
                    ? trim($emp['ManagerFirstName'] . ' ' . ($emp['ManagerLastName'] ?? ''))
                    : null,
            ],
            'documents' => [
                'national_id' => [
                    'number'     => $emp['Id_h'],
                    'start_date' => $emp['start_date_h'],
                    'end_date'   => $emp['end_date_h'],
                ],
                'license' => [
                    'number'     => $emp['Id_license'],
                    'start_date' => $emp['start_date_license'],
                    'end_date'   => $emp['end_date_license'],
                ],
                'passport' => [
                    'number'     => $emp['Id_passport'],
                    'start_date' => $emp['start_date_passport'],
                    'end_date'   => $emp['end_date_passport'],
                ],
                'health_insurance' => [
                    'number'     => $emp['Id_health'],
                    'start_date' => $emp['start_date_health'],
                    'end_date'   => $emp['end_date_health'],
                ],
            ],
            'banking' => [
                'insurance_company' => $emp['InsuranceName'],
                'bank_name'         => $emp['user_bank_name'],
                'account_number'    => $emp['user_account_bank'],
            ],
        ]);
    }

    /**
     * PUT /employee/profile
     * Update limited employee profile fields
     */
    public static function updateProfile(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();

        // Only allow updating these fields
        $allowedFields = ['Phone', 'ohter_phone', 'user_address', 'marital_status', 'HealthCondition'];
        $updates = [];
        $params = [':uid' => $apiUser['id']];

        foreach ($allowedFields as $field) {
            $inputKey = $field;
            // Map API field names to DB column names
            $fieldMap = [
                'phone'            => 'Phone',
                'other_phone'      => 'ohter_phone',
                'address'          => 'user_address',
                'marital_status'   => 'marital_status',
                'health_condition' => 'HealthCondition',
            ];

            foreach ($fieldMap as $apiKey => $dbCol) {
                if (isset($body[$apiKey])) {
                    $updates[] = "$dbCol = :$dbCol";
                    $params[":$dbCol"] = sanitizingData($body[$apiKey]);
                }
            }
        }

        if (empty($updates)) {
            Response::error('لا توجد بيانات للتحديث', 400);
        }

        // Get old data for audit
        $stm = $connect_pdo->prepare("SELECT Phone, ohter_phone, user_address, marital_status, HealthCondition FROM tblusers WHERE UserID = :uid");
        $stm->execute([':uid' => $apiUser['id']]);
        $oldData = $stm->fetch(PDO::FETCH_ASSOC);

        // Remove duplicates from updates
        $updates = array_unique($updates);

        $sql = "UPDATE tblusers SET " . implode(', ', $updates) . " WHERE UserID = :uid";
        $stm2 = $connect_pdo->prepare($sql);
        $stm2->execute($params);

        // Audit log
        $auditLog->logUpdate($apiUser['id'], 'tblusers', $apiUser['id'], $oldData ?: [], $body);

        Response::success(null, 'تم تحديث البيانات بنجاح');
    }

    /**
     * GET /employee/salary-slips
     * Get employee salary slips history
     */
    public static function salarySlips(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $pagination = Validator::pagination();

        $stm = $connect_pdo->prepare(
            "SELECT es.Id, es.id_registration, es.incentive, es.benefit,
                    es.deductions, es.advances, es.absent_salary, es.net_salary, es.end_salary,
                    es.month, sr.year, sr.BranchID, sr.created_date as issued_date
             FROM emp_salary es
             JOIN salary_registration sr ON sr.Id = es.id_registration
             WHERE es.UserID = :uid
             ORDER BY sr.year DESC, es.month DESC
             LIMIT :limit OFFSET :offset"
        );
        $stm->bindValue(':uid', $apiUser['id'], PDO::PARAM_INT);
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $slips = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $stm2 = $connect_pdo->prepare(
            "SELECT COUNT(*) as total FROM emp_salary WHERE UserID = :uid"
        );
        $stm2->execute([':uid' => $apiUser['id']]);
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        // Format
        $formatted = array_map(function ($slip) {
            return [
                'id'            => (int) $slip['Id'],
                'month'         => (int) $slip['month'],
                'year'          => (int) $slip['year'],
                'incentive'     => (float) $slip['incentive'],
                'benefit'       => (float) $slip['benefit'],
                'deductions'    => (float) $slip['deductions'],
                'advances'      => (float) $slip['advances'],
                'absent_salary' => (float) $slip['absent_salary'],
                'net_salary'    => (float) $slip['net_salary'],
                'end_salary'    => (float) $slip['end_salary'],
                'issued_date'   => $slip['issued_date'],
            ];
        }, $slips);

        Response::paginated($formatted, $total, $pagination['page'], $pagination['per_page']);
    }

    /**
     * GET /employee/salary-slips/:id
     * Get a specific salary slip by ID
     */
    public static function salarySlipById(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare(
            "SELECT es.Id, es.id_registration, es.UserID, es.incentive, es.benefit,
                    es.deductions, es.advances, es.absent_salary, es.net_salary, es.end_salary,
                    es.month,
                    sr.year, sr.BranchID, sr.created_date as issued_date,
                    u.FirstName, u.SecondName, u.LastName, u.UserEmail,
                    jt.Name as JobTitleName, b.branch_name
             FROM emp_salary es
             JOIN salary_registration sr ON sr.Id = es.id_registration
             JOIN tblusers u ON u.UserID = es.UserID
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN branches b ON b.branch_id = sr.BranchID
             WHERE es.Id = :id AND es.UserID = :uid
             LIMIT 1"
        );
        $stm->execute([':id' => $id, ':uid' => $apiUser['id']]);
        $slip = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$slip) {
            Response::notFound('كشف الراتب غير موجود');
        }

        Response::success([
            'id'             => (int) $slip['Id'],
            'month'          => (int) $slip['month'],
            'year'           => (int) $slip['year'],
            'employee'       => trim($slip['FirstName'] . ' ' . ($slip['SecondName'] ?? '') . ' ' . ($slip['LastName'] ?? '')),
            'job_title'      => $slip['JobTitleName'],
            'branch'         => $slip['branch_name'],
            'earnings'       => [
                'incentive'  => (float) $slip['incentive'],
                'benefit'    => (float) $slip['benefit'],
            ],
            'deductions'     => [
                'deductions'    => (float) $slip['deductions'],
                'advances'      => (float) $slip['advances'],
                'absent_salary' => (float) $slip['absent_salary'],
            ],
            'net_salary'     => (float) $slip['net_salary'],
            'end_salary'     => (float) $slip['end_salary'],
            'issued_date'    => $slip['issued_date'],
        ]);
    }

    /**
     * GET /employee/documents
     * Get employee document expiry summary (IDs, passport, license, health)
     */
    public static function documents(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT Id_h, start_date_h, end_date_h,
                    Id_license, start_date_license, end_date_license,
                    Id_passport, start_date_passport, end_date_passport,
                    Id_health, start_date_health, end_date_health
             FROM tblusers WHERE UserID = :uid LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::notFound('بيانات الموظف غير موجودة');
        }

        $today = time();
        $docs = [];
        $docTypes = [
            'national_id'      => ['Id_h', 'start_date_h', 'end_date_h', 'الهوية الوطنية'],
            'driving_license'  => ['Id_license', 'start_date_license', 'end_date_license', 'رخصة القيادة'],
            'passport'         => ['Id_passport', 'start_date_passport', 'end_date_passport', 'جواز السفر'],
            'health_insurance' => ['Id_health', 'start_date_health', 'end_date_health', 'التأمين الصحي'],
        ];

        foreach ($docTypes as $key => [$numCol, $startCol, $endCol, $label]) {
            $endDate = $user[$endCol] ?? null;
            $status = 'valid';
            $daysLeft = null;

            if ($endDate) {
                $daysLeft = (int) ((strtotime($endDate) - $today) / 86400);
                if ($daysLeft < 0) {
                    $status = 'expired';
                } elseif ($daysLeft <= 30) {
                    $status = 'expiring_soon';
                }
            } elseif (empty($user[$numCol])) {
                $status = 'missing';
            }

            $docs[] = [
                'type'       => $key,
                'label'      => $label,
                'number'     => $user[$numCol],
                'start_date' => $user[$startCol],
                'end_date'   => $endDate,
                'status'     => $status,
                'days_left'  => $daysLeft,
            ];
        }

        Response::success($docs);
    }

    /**
     * GET /employee/contracts
     * Get employee contract renewal history
     */
    public static function contracts(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT r.Id, r.new_s_date, r.new_e_date, r.Salary, r.Currency,
                    r.SectionID, r.jobtitleID, r.GradeID, r.TypeID, r.shiftID,
                    jt.Name as JobTitleName, jg.Name as GradeName,
                    s.Name as SectionName, et.Name as EmploymentTypeName,
                    sh.ShiftName, r.CreatedDate
             FROM tblremewal r
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN tblemploymenttype et ON et.Id = r.TypeID
             LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
             WHERE r.UserID = :uid
             ORDER BY r.new_s_date DESC"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $contracts = $stm->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($c) {
            return [
                'id'              => (int) $c['Id'],
                'start_date'      => $c['new_s_date'],
                'end_date'        => $c['new_e_date'],
                'salary'          => (float) ($c['Salary'] ?? 0),
                'currency'        => $c['Currency'] ?? 'SAR',
                'job_title'       => $c['JobTitleName'],
                'grade'           => $c['GradeName'],
                'section'         => $c['SectionName'],
                'employment_type' => $c['EmploymentTypeName'],
                'shift'           => $c['ShiftName'],
                'created_date'    => $c['CreatedDate'],
            ];
        }, $contracts);

        Response::success($formatted);
    }

    /**
     * GET /employee/certificates
     * Get employee certificates
     */
    public static function certificates(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT Id, Name, Side, CertDate, attachment, CreatedDate
             FROM tblcertificates
             WHERE UserID = :uid
             ORDER BY CertDate DESC"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $certs = $stm->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($c) {
            return [
                'id'           => (int) $c['Id'],
                'name'         => $c['Name'],
                'institution'  => $c['Side'],
                'date'         => $c['CertDate'],
                'attachment'   => $c['attachment'],
                'created_date' => $c['CreatedDate'],
            ];
        }, $certs);

        Response::success($formatted);
    }

    /**
     * GET /employee/experience
     * Get employee work experience
     */
    public static function experience(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT Id, JobTitle, Company, StartDate, EndDate, Tasks, attachment, CreatedDate
             FROM tblexperience
             WHERE UserID = :uid
             ORDER BY StartDate DESC"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $exps = $stm->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($e) {
            return [
                'id'           => (int) $e['Id'],
                'job_title'    => $e['JobTitle'],
                'company'      => $e['Company'],
                'start_date'   => $e['StartDate'],
                'end_date'     => $e['EndDate'],
                'tasks'        => $e['Tasks'],
                'attachment'   => $e['attachment'],
                'created_date' => $e['CreatedDate'],
            ];
        }, $exps);

        Response::success($formatted);
    }
}
