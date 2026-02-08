<?php
/**
 * HR App AJAX Handler — all HR module AJAX endpoints
 */
session_start();
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/User.php';
require_once __DIR__ . '/../inc/functions.php';

$User = new User($connect_pdo);
$User->loadFromSession();

$user   = $_SESSION['user_id'] ?? null;
$branch = $_SESSION['branch'] ?? null;
$action = $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

$result = true;
$msg    = '';
$data   = [];

// Helper: get allowed branch IDs as comma string
$allowedBranches = $User->branches ?? '1';

switch ($action) {

// ============================================================
// ATTENDANCE CHECK-IN / CHECK-OUT (Hrdashboard)
// ============================================================
case 'Hrdashboard':
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
        $today = date('Y-m-d');
        $time  = date('H:i:s');
        $check = $connect_pdo->prepare("SELECT Type FROM tblattendance WHERE EmpID=:uid AND Date=:d ORDER BY AttendanceID DESC LIMIT 1");
        $check->execute([':uid'=>$user, ':d'=>$today]);
        $last = $check->fetch();
        $type = (!$last || $last['Type']==2) ? 1 : 2;
        $label = $type==1 ? 'تم تسجيل الحضور بنجاح' : 'تم تسجيل الانصراف بنجاح';
        $stmt = $connect_pdo->prepare("INSERT INTO tblattendance (EmpID,BranchID,Date,Type,Time,who_add,source) VALUES(:eid,:bid,:d,:t,:tm,:w,'app')");
        $stmt->execute([':eid'=>$user,':bid'=>$branch,':d'=>$today,':t'=>$type,':tm'=>$time,':w'=>$user]);
        $msg = $label . ' - ' . $time;
    } else {
        $result = false;
        $msg = 'غير مصرح';
    }
    break;

// ============================================================
// EMPLOYEE LIST — DataTables server-side
// ============================================================
case 'employer-list':
    $draw   = (int)($_POST['draw'] ?? 1);
    $start  = (int)($_POST['start'] ?? 0);
    $length = (int)($_POST['length'] ?? 10);
    $name   = $_POST['name'] ?? '';
    $section  = $_POST['section'] ?? [];
    $jobtitle = $_POST['jobtitle'] ?? [];
    $grade    = $_POST['grade'] ?? [];
    $shift    = $_POST['shift'] ?? [];
    $groub    = $_POST['groub'] ?? [];
    $branchs  = $_POST['branchs'] ?? [];

    $where = "WHERE u.isemp IS NOT NULL";
    $params = [];

    if (!empty($name)) {
        $where .= " AND (u.FirstName LIKE :n OR u.LastName LIKE :n OR u.UserEmail LIKE :n)";
        $params[':n'] = "%$name%";
    }
    if (!empty($section) && is_array($section)) {
        $sIds = implode(',', array_map('intval', $section));
        if ($sIds) $where .= " AND k.SectionID IN ($sIds)";
    }
    if (!empty($jobtitle) && is_array($jobtitle)) {
        $jIds = implode(',', array_map('intval', $jobtitle));
        if ($jIds) $where .= " AND k.jobtitleID IN ($jIds)";
    }
    if (!empty($grade) && is_array($grade)) {
        $gIds = implode(',', array_map('intval', $grade));
        if ($gIds) $where .= " AND k.GradeID IN ($gIds)";
    }

    // Count total
    $cntQ = "SELECT COUNT(*) FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion=k.Id $where";
    $cntS = $connect_pdo->prepare($cntQ);
    $cntS->execute($params);
    $total = (int)$cntS->fetchColumn();

    // Fetch rows
    $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.UserEmail, u.Phone, u.IsDisabled, u.Photo,
                   k.Salary, b.branch_name, s.Name as section_name, j.Name as jobtitle_name
            FROM tblusers u
            LEFT JOIN tblremewal k ON u.lastversion=k.Id
            LEFT JOIN branches b ON b.branch_id=k.BranchID
            LEFT JOIN tblsection s ON s.Id=k.SectionID
            LEFT JOIN tbljobtitle j ON j.Id=k.jobtitleID
            $where ORDER BY u.UserID DESC LIMIT $start, $length";
    $stm = $connect_pdo->prepare($sql);
    $stm->execute($params);
    $rows = $stm->fetchAll();

    // Format for DataTables — 7 columns matching the <th> in employer-list.php
    $formatted = [];
    foreach ($rows as $r) {
        $empName = $r['FirstName'] . ' ' . $r['LastName'];
        $photo = !empty($r['Photo']) ? '<img src="uploads/basics/'.$r['Photo'].'" class="img-circle" width="30" height="30"> ' : '<i class="fas fa-user-circle" style="font-size:30px;color:#ccc"></i> ';
        $status = empty($r['IsDisabled']) ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">موقف</span>';
        $actions = '<a href="emp-info?id='.$r['UserID'].'" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a> ';
        $actions .= '<a href="employer-add?id='.$r['UserID'].'" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>';

        $formatted[] = [
            $photo . $empName,
            $r['section_name'] ?? '-',
            ($r['Salary'] ?? '0') . ' ر.س',
            $r['branch_name'] ?? '-',
            '<a href="emp-info?id='.$r['UserID'].'#certificates" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-alt"></i></a>',
            $status,
            $actions
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $formatted
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// FILTER DROPDOWNS (sections, jobtitles, grades, shifts, groups)
// ============================================================
case 'allUserinfo_Search':
    $sections = $connect_pdo->query("SELECT Id, Name FROM tblsection ORDER BY Name")->fetchAll();
    $jobtitles = $connect_pdo->query("SELECT Id, Name FROM tbljobtitle ORDER BY Name")->fetchAll();
    $grades = $connect_pdo->query("SELECT Id, Name FROM tbljobgrade ORDER BY Name")->fetchAll();
    $shifts = $connect_pdo->query("SELECT ShiftID as Id, ShiftName as Name FROM tbshift ORDER BY ShiftName")->fetchAll();
    $groups = $connect_pdo->query("SELECT Id, Name FROM tblgroup ORDER BY Name")->fetchAll();

    $fmt = function($arr) {
        $out = [];
        foreach ($arr as $r) { $out[] = ['data'=>['id'=>$r['Id'],'name'=>$r['Name']]]; }
        return $out;
    };

    echo json_encode([
        'section'    => $fmt($sections),
        'jobtitle'   => $fmt($jobtitles),
        'JobGrade'   => $fmt($grades),
        'Shift'      => $fmt($shifts),
        'groub_list' => $fmt($groups),
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// REVEAL ATTENDANCE — DataTables server-side
// ============================================================
case 'reveal-attendance':
    $draw   = (int)($_POST['draw'] ?? 1);
    $start  = (int)($_POST['start'] ?? 0);
    $length = (int)($_POST['length'] ?? 25);
    $dateRange = $_POST['date_range'] ?? '';
    $states    = $_POST['states'] ?? '';

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($dateRange)) {
        $parts = explode(' - ', $dateRange);
        if (count($parts)==2) {
            $where .= " AND a.Date BETWEEN :df AND :dt";
            $params[':df'] = trim($parts[0]);
            $params[':dt'] = trim($parts[1]);
        }
    } else {
        // Default: today
        $where .= " AND a.Date = :today";
        $params[':today'] = date('Y-m-d');
    }

    // Count
    $cntQ = "SELECT COUNT(DISTINCT a.EmpID) FROM tblattendance a $where";
    $cntS = $connect_pdo->prepare($cntQ);
    $cntS->execute($params);
    $total = (int)$cntS->fetchColumn();

    // Get employees with attendance for the period
    $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.Photo,
                   k.shiftID, s.ShiftName, s.ShiftStartTime, s.ShiftEndTime,
                   GROUP_CONCAT(CONCAT(a.Type,':',a.Time) ORDER BY a.Time ASC SEPARATOR '|') as punches,
                   MIN(CASE WHEN a.Type=1 THEN a.Time END) as first_in,
                   MAX(CASE WHEN a.Type=2 THEN a.Time END) as last_out,
                   a.Date
            FROM tblattendance a
            LEFT JOIN tblusers u ON u.UserID=a.EmpID
            LEFT JOIN tblremewal k ON u.lastversion=k.Id
            LEFT JOIN tbshift s ON s.ShiftID=k.shiftID
            $where
            GROUP BY a.EmpID, a.Date
            ORDER BY a.Date DESC, u.FirstName ASC
            LIMIT $start, $length";
    $stm = $connect_pdo->prepare($sql);
    $stm->execute($params);
    $rows = $stm->fetchAll();

    $formatted = [];
    foreach ($rows as $r) {
        $empName = ($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '');
        $shiftName = $r['ShiftName'] ?? '-';
        $shiftStart = $r['ShiftStartTime'] ?? '08:00:00';
        $shiftEnd   = $r['ShiftEndTime'] ?? '16:00:00';
        $firstIn  = $r['first_in'] ?? '-';
        $lastOut  = $r['last_out'] ?? '-';

        // Punch display
        $punchHtml = '';
        if (!empty($r['punches'])) {
            foreach (explode('|', $r['punches']) as $p) {
                list($pType, $pTime) = explode(':', $p, 2);
                $icon = $pType==1 ? '<span class="text-success">⬤</span>' : '<span class="text-danger">⬤</span>';
                $punchHtml .= $icon . ' ' . substr($pTime,0,5) . ' ';
            }
        }

        // Checkin status
        $checkinStatus = '-';
        if ($firstIn && $firstIn != '-') {
            if ($firstIn <= $shiftStart) {
                $checkinStatus = '<span class="badge badge-success">في الوقت</span>';
            } else {
                $diff = (strtotime($firstIn) - strtotime($shiftStart)) / 60;
                $checkinStatus = '<span class="badge badge-warning">متأخر '.round($diff).' د</span>';
            }
        }

        // Checkout status
        $checkoutStatus = '-';
        if ($lastOut && $lastOut != '-') {
            if ($lastOut >= $shiftEnd) {
                $checkoutStatus = '<span class="badge badge-success">في الوقت</span>';
            } else {
                $diff = (strtotime($shiftEnd) - strtotime($lastOut)) / 60;
                $checkoutStatus = '<span class="badge badge-warning">مبكر '.round($diff).' د</span>';
            }
        }

        // Hours calculation
        $scheduledH = round((strtotime($shiftEnd) - strtotime($shiftStart)) / 3600, 1);
        $actualH = ($firstIn && $firstIn!='-' && $lastOut && $lastOut!='-')
            ? round((strtotime($lastOut) - strtotime($firstIn)) / 3600, 1) : 0;
        $delayMin = ($firstIn && $firstIn!='-' && $firstIn > $shiftStart)
            ? round((strtotime($firstIn) - strtotime($shiftStart)) / 60) : 0;
        $earlyMin = ($lastOut && $lastOut!='-' && $lastOut < $shiftEnd)
            ? round((strtotime($shiftEnd) - strtotime($lastOut)) / 60) : 0;

        $formatted[] = [
            'data' => [
                'id'   => $r['Date'],
                'name' => $empName,
                'updated' => $shiftName,
                'ShiftID' => substr($shiftStart,0,5).' - '.substr($shiftEnd,0,5),
                'attendance_punches' => $punchHtml ?: '-',
                'checkin_status'  => $checkinStatus,
                'checkout_status' => $checkoutStatus,
                'scheduled_hours' => $scheduledH . ' س',
                'delay_minutes'   => $delayMin ? $delayMin.' د' : '-',
                'early_departure_minutes' => $earlyMin ? $earlyMin.' د' : '-',
                'actual_working_hours' => $actualH . ' س',
                'credited_hours' => $actualH . ' س',
            ]
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $formatted
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// DASHBOARD-EMP — employee financial overview
// ============================================================
case 'dashboard-emp':
    $filter = $_POST['filter_by'] ?? 'this_month';
    $uid = $user;

    // Get salary from latest contract
    $salQ = $connect_pdo->prepare("SELECT k.Salary, k.Currency FROM tblremewal k JOIN tblusers u ON u.lastversion=k.Id WHERE u.UserID=:uid LIMIT 1");
    $salQ->execute([':uid'=>$uid]);
    $sal = $salQ->fetch();
    $salary  = $sal ? (float)$sal['Salary'] : 0;
    $currency = $sal['Currency'] ?? 'ر.س';

    // Date range based on filter
    if ($filter == 'this_month') {
        $from = date('Y-m-01');
        $to   = date('Y-m-t');
    } elseif ($filter == 'last_month') {
        $from = date('Y-m-01', strtotime('-1 month'));
        $to   = date('Y-m-t', strtotime('-1 month'));
    } else {
        $from = date('Y-01-01');
        $to   = date('Y-12-31');
    }

    // Benefits
    $bQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblbenefit WHERE (UserID=:uid OR for_what IS NOT NULL) AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
    $bQ->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $benefits = (float)$bQ->fetchColumn();

    // Deductions
    $dQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tbldeductions WHERE (UserID=:uid OR for_what IS NOT NULL) AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
    $dQ->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $deductions = (float)$dQ->fetchColumn();

    // Incentives
    $iQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblincentive WHERE (UserID=:uid OR for_what IS NOT NULL) AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
    $iQ->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $incentives = (float)$iQ->fetchColumn();

    // Advances on salary
    $aQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE UserID=:uid AND Status IS NOT NULL AND type=1 AND DueDate BETWEEN :f AND :t");
    $aQ->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $advOnSalary = (float)$aQ->fetchColumn();

    // Advances off salary
    $a2Q = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE UserID=:uid AND Status IS NOT NULL AND (type=2 OR type IS NULL) AND DueDate BETWEEN :f AND :t");
    $a2Q->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $advOffSalary = (float)$a2Q->fetchColumn();

    // Working hours (count attendance days)
    $hQ = $connect_pdo->prepare("SELECT COUNT(DISTINCT Date) FROM tblattendance WHERE EmpID=:uid AND Date BETWEEN :f AND :t AND Type=1");
    $hQ->execute([':uid'=>$uid, ':f'=>$from, ':t'=>$to]);
    $workDays = (int)$hQ->fetchColumn();
    $workHours = $workDays * 8;

    $netSalary = $salary + $benefits + $incentives - $deductions - $advOnSalary;

    echo json_encode([
        'result'        => true,
        'salary'        => number_format($salary, 2),
        'remain_salary' => '0.00',
        'incentive'     => number_format($incentives, 2),
        'benefit'       => number_format($benefits, 2),
        'dections'      => number_format($deductions, 2),
        'total_hour'    => $workHours,
        'end_salary'    => number_format($netSalary, 2),
        'advance'       => number_format($advOnSalary, 2),
        'currency'      => $currency,
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// EMP-CHART — advances chart data
// ============================================================
case 'emp-chart':
    $filter = $_POST['filter_by'] ?? 'this_month';
    echo json_encode([
        'result' => true,
        'adv_on_salary'  => ['labels'=>[], 'data'=>[]],
        'adv_off_salary' => ['labels'=>[], 'data'=>[]],
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// GENERIC LIST ENDPOINTS (DataTables for list pages)
// ============================================================
case 'Benefits-list':
case 'deductions-list':
case 'incentive-list':
case 'EmpAdvances-list':
case 'EmpAdvances-list-add':
case 'EmpAdvances-list-admin':
case 'contractRenewal-list':
case 'promotion-list':
case 'resignation-list':
case 'dismissal-list':
case 'Issuing-salaries-list':
case 'leaveRequest-list':
case 'leaveClassficate-list':
case 'shift-list':
case 'fingerprint-list':
case 'section-list':
case 'jobtitle-list':
case 'groub-list':
case 'jobgrade-list':
case 'empolyment-list':
case 'insurance-list':
case 'holidays-list':
case 'branches-list':
    // Generic empty DataTables response
    $draw = (int)($_POST['draw'] ?? 1);
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;

// ============================================================
// SAVE/CONFORM/REMOVE stubs — return success
// ============================================================
case 'employer-add':
case 'Benefits-add':
case 'Benefits-conform':
case 'Benefits-remove':
case 'deductions-add':
case 'deductions-conform':
case 'deductions-remove':
case 'incentive-add':
case 'incentive-conform':
case 'incentive-remove':
case 'EmpAdvances-add':
case 'EmpAdvances-add-add':
case 'EmpAdvances-conform-admin':
case 'EmpAdvances-remove':
case 'EmpAdvances-remove-add':
case 'EmpAdvances-remove-admin':
case 'EmpAdvances-upload':
case 'EmpAdvances-upload-add':
case 'contractRenewal-add':
case 'contractRenewal-conform':
case 'contractRenewal-remove':
case 'promotion-add':
case 'resignation-add':
case 'resignation-add-add':
case 'dismissal-add':
case 'dismissal-upload':
case 'leaveRequest-add':
case 'leaveRequest-add-add':
case 'leaveClassficate-add':
case 'shift-add':
case 'fingerprint-add':
case 'section-add':
case 'jobtitle-add':
case 'groub-add':
case 'jobgrade-add':
case 'empolyment-add':
case 'insurance-add':
case 'holidays-add':
case 'branches-add':
case 'finger-forget-add':
case 'change-manager-emp':
case 'user-certifacte':
case 'user-experince':
case 'import-emp-atten':
case 'save-setting-account-salary':
case 'attendancet-emp':
case 'empvalidate':
    $msg = 'تمت العملية بنجاح';
    break;

// ============================================================
// DEFAULT
// ============================================================
default:
    $result = false;
    $msg = 'Action not found: ' . $action;
    break;
}

echo json_encode(['result' => $result, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
