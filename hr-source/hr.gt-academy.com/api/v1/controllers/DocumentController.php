<?php
/**
 * Vision HR - Document Controller
 * Salary slips, experience letters, salary definition letters
 */

class DocumentController
{
    /**
     * GET /documents/salary-slip/:month/:year
     * Generate salary slip data for a specific month
     */
    public static function salarySlip(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $month = (int) ($params['month'] ?? 0);
        $year = (int) ($params['year'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2000) {
            Response::validationError(['date' => 'الشهر أو السنة غير صالحة']);
        }

        // Get salary details
        $stm = $connect_pdo->prepare(
            "SELECT es.*, sr.month as sr_month, sr.year, sr.BranchID, sr.created_date as issued_date
             FROM emp_salary es
             JOIN salary_registration sr ON sr.Id = es.id_registration
             WHERE es.UserID = :uid AND es.month = :month AND sr.year = :year
             LIMIT 1"
        );
        $stm->execute([
            ':uid'   => $apiUser['id'],
            ':month' => $month,
            ':year'  => $year,
        ]);
        $slip = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$slip) {
            Response::notFound('كشف الراتب غير موجود لهذا الشهر');
        }

        // Get employee info
        $stm2 = $connect_pdo->prepare(
            "SELECT u.FirstName, u.SecondName, u.LastName, u.UserEmail, u.Phone,
                    u.Id_h, u.user_bank_name, u.user_account_bank,
                    r.Salary, r.Currency,
                    s.Name as SectionName, jt.Name as JobTitleName,
                    jg.Name as GradeName, b.branch_name
             FROM tblusers u
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
             LEFT JOIN branches b ON b.branch_id = r.BranchID
             WHERE u.UserID = :uid LIMIT 1"
        );
        $stm2->execute([':uid' => $apiUser['id']]);
        $emp = $stm2->fetch(PDO::FETCH_ASSOC);

        // Get individual benefits for this month
        $stm3 = $connect_pdo->prepare(
            "SELECT name, Amount FROM tblbenefit
             WHERE UserID = :uid AND Status = 1
               AND (monthly = 1 OR (MONTH(DueDate) = :month AND YEAR(DueDate) = :year))"
        );
        $stm3->execute([':uid' => $apiUser['id'], ':month' => $month, ':year' => $year]);
        $benefits = $stm3->fetchAll(PDO::FETCH_ASSOC);

        // Get individual deductions
        $stm4 = $connect_pdo->prepare(
            "SELECT name, Amount FROM tbldeductions
             WHERE UserID = :uid AND Status = 1
               AND (MONTH(DueDate) = :month AND YEAR(DueDate) = :year)"
        );
        $stm4->execute([':uid' => $apiUser['id'], ':month' => $month, ':year' => $year]);
        $deductions = $stm4->fetchAll(PDO::FETCH_ASSOC);

        // Get site info for header
        $stm5 = $connect_pdo->prepare("SELECT SiteTitle, SiteLogo FROM tblsite LIMIT 1");
        $stm5->execute();
        $site = $stm5->fetch(PDO::FETCH_ASSOC);

        $monthNames = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        Response::success([
            'company' => [
                'name' => $site['SiteTitle'] ?? 'Vision HR',
                'logo' => $site['SiteLogo'] ?? null,
            ],
            'employee' => [
                'name'           => trim(($emp['FirstName'] ?? '') . ' ' . ($emp['SecondName'] ?? '') . ' ' . ($emp['LastName'] ?? '')),
                'email'          => $emp['UserEmail'] ?? '',
                'phone'          => $emp['Phone'] ?? '',
                'national_id'    => $emp['Id_h'] ?? '',
                'job_title'      => $emp['JobTitleName'] ?? '',
                'section'        => $emp['SectionName'] ?? '',
                'grade'          => $emp['GradeName'] ?? '',
                'branch'         => $emp['branch_name'] ?? '',
                'bank_name'      => $emp['user_bank_name'] ?? '',
                'account_number' => $emp['user_account_bank'] ?? '',
            ],
            'period' => [
                'month'      => $month,
                'year'       => $year,
                'month_name' => $monthNames[$month] ?? '',
                'label'      => ($monthNames[$month] ?? '') . ' ' . $year,
            ],
            'earnings' => [
                'incentive'    => (float) ($slip['incentive'] ?? 0),
                'benefit'      => (float) ($slip['benefit'] ?? 0),
                'details'      => array_map(function ($b) {
                    return ['name' => $b['name'], 'amount' => (float) $b['Amount']];
                }, $benefits),
            ],
            'deductions_detail' => [
                'deductions'   => (float) ($slip['deductions'] ?? 0),
                'advances'     => (float) ($slip['advances'] ?? 0),
                'absent_salary'=> (float) ($slip['absent_salary'] ?? 0),
                'details'      => array_map(function ($d) {
                    return ['name' => $d['name'], 'amount' => (float) $d['Amount']];
                }, $deductions),
            ],
            'summary' => [
                'net_salary'       => (float) ($slip['net_salary'] ?? 0),
                'end_salary'       => (float) ($slip['end_salary'] ?? 0),
                'currency'         => $emp['Currency'] ?? 'SAR',
            ],
            'issued_date' => $slip['issued_date'],
        ]);
    }

    /**
     * GET /documents/experience-letter
     * Generate experience letter data
     */
    public static function experienceLetter(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        // Get employee info
        $stm = $connect_pdo->prepare(
            "SELECT u.FirstName, u.SecondName, u.LastName, u.Id_h, u.Sex,
                    u.CreatedDate as join_date,
                    r.Salary, r.Currency, r.new_s_date, r.new_e_date,
                    s.Name as SectionName, jt.Name as JobTitleName,
                    jg.Name as GradeName, b.branch_name,
                    et.Name as EmploymentType
             FROM tblusers u
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
             LEFT JOIN branches b ON b.branch_id = r.BranchID
             LEFT JOIN tblemploymenttype et ON et.Id = r.TypeID
             WHERE u.UserID = :uid LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $emp = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            Response::notFound('بيانات الموظف غير موجودة');
        }

        // Get site info
        $stm2 = $connect_pdo->prepare("SELECT SiteTitle, SiteLogo FROM tblsite LIMIT 1");
        $stm2->execute();
        $site = $stm2->fetch(PDO::FETCH_ASSOC);

        $fullName = trim(($emp['FirstName'] ?? '') . ' ' . ($emp['SecondName'] ?? '') . ' ' . ($emp['LastName'] ?? ''));
        $pronoun = ($emp['Sex'] === 'أنثى' || $emp['Sex'] === 'female') ? 'السيدة' : 'السيد';

        Response::success([
            'company' => [
                'name' => $site['SiteTitle'] ?? 'Vision HR',
                'logo' => $site['SiteLogo'] ?? null,
            ],
            'employee' => [
                'full_name'       => $fullName,
                'pronoun'         => $pronoun,
                'national_id'     => $emp['Id_h'],
                'job_title'       => $emp['JobTitleName'],
                'section'         => $emp['SectionName'],
                'grade'           => $emp['GradeName'],
                'branch'          => $emp['branch_name'],
                'employment_type' => $emp['EmploymentType'],
                'join_date'       => $emp['join_date'],
                'contract_start'  => $emp['new_s_date'],
                'contract_end'    => $emp['new_e_date'],
                'salary'          => (float) ($emp['Salary'] ?? 0),
                'currency'        => $emp['Currency'] ?? 'SAR',
            ],
            'letter' => [
                'date'      => date('Y-m-d'),
                'reference' => 'EXP-' . $apiUser['id'] . '-' . date('Ymd'),
                'type'      => 'experience_letter',
            ],
        ]);
    }

    /**
     * GET /documents/salary-definition
     * Generate salary definition letter data
     */
    public static function salaryDefinition(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT u.FirstName, u.SecondName, u.LastName, u.Id_h, u.Sex,
                    r.Salary, r.Currency, r.new_s_date,
                    jt.Name as JobTitleName, s.Name as SectionName,
                    b.branch_name
             FROM tblusers u
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN branches b ON b.branch_id = r.BranchID
             WHERE u.UserID = :uid LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $emp = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            Response::notFound('بيانات الموظف غير موجودة');
        }

        // Get total monthly compensation
        $stm2 = $connect_pdo->prepare(
            "SELECT COALESCE(SUM(Amount), 0) as total
             FROM tblbenefit WHERE UserID = :uid AND Status = 1 AND monthly = 1"
        );
        $stm2->execute([':uid' => $apiUser['id']]);
        $monthlyBenefits = (float) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $basicSalary = (float) ($emp['Salary'] ?? 0);
        $totalCompensation = $basicSalary + $monthlyBenefits;

        $stm3 = $connect_pdo->prepare("SELECT SiteTitle, SiteLogo FROM tblsite LIMIT 1");
        $stm3->execute();
        $site = $stm3->fetch(PDO::FETCH_ASSOC);

        $fullName = trim(($emp['FirstName'] ?? '') . ' ' . ($emp['SecondName'] ?? '') . ' ' . ($emp['LastName'] ?? ''));

        Response::success([
            'company' => [
                'name' => $site['SiteTitle'] ?? 'Vision HR',
                'logo' => $site['SiteLogo'] ?? null,
            ],
            'employee' => [
                'full_name'   => $fullName,
                'national_id' => $emp['Id_h'],
                'job_title'   => $emp['JobTitleName'],
                'section'     => $emp['SectionName'],
                'branch'      => $emp['branch_name'],
                'start_date'  => $emp['new_s_date'],
            ],
            'salary' => [
                'basic_salary'       => $basicSalary,
                'monthly_benefits'   => $monthlyBenefits,
                'total_compensation' => $totalCompensation,
                'currency'           => $emp['Currency'] ?? 'SAR',
            ],
            'letter' => [
                'date'      => date('Y-m-d'),
                'reference' => 'SAL-' . $apiUser['id'] . '-' . date('Ymd'),
                'type'      => 'salary_definition',
            ],
        ]);
    }
}
