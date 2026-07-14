<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start(); // Start output buffering to catch any unwanted output
/**
 * HR App AJAX Handler â€” all HR module AJAX endpoints
 * With RBAC guards, audit logging, GPS/QR attendance
 */
// Secure session cookie settings for HTTPS - only if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/User.php';
require_once __DIR__ . '/../inc/AuditLog.php';
require_once __DIR__ . '/../inc/MailService.php';
require_once __DIR__ . '/../inc/functions.php';
// error_log("HR-APP SESSION STATE: " . print_r($_SESSION, true));

$User = new User($connect_pdo);
$User->loadFromSession();
$Audit = new AuditLog($connect_pdo);

$user = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? $_SESSION['user']['UserID'] ?? null;
$branch = $_SESSION['branch'] ?? $_SESSION['BranchID'] ?? null;
$action = $_GET['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');
$allowDebugResponse = !empty($_GET['debug_session']) && !empty($_SESSION['user']['is_system']);
$debug_data = $allowDebugResponse ? $_SESSION : [];

$result = true;
$msg = '';
$data = [];
$responseMeta = [];

// Helper: get allowed branch IDs as comma string
$allowedBranches = $User->branches ?? '1';

// Helper: generate QR code data URL (internal - scannable pattern)
function generateQRDataURL($text)
{
    return 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode((string) $text);
}

function validateCSRF(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Helper function to format data for select pickers
function formatForSelect($items, $idKey, $nameKey)
{
    $out = [];
    foreach ($items as $item) {
        $out[] = ['data' => ['id' => $item[$idKey], 'name' => ($item[$nameKey] ?? $item[$idKey])]];
    }
    return $out;
}

// Helper function to format user data (FirstName + LastName) for select pickers
function formatUsersForSelect($users)
{
    $out = [];
    foreach ($users as $user) {
        $fullName = trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''));
        if (empty($fullName)) { // Fallback if no name parts
            $fullName = 'User ID: ' . $user['UserID'];
        }
        $out[] = ['data' => ['id' => $user['UserID'], 'name' => $fullName]];
    }
    return $out;
}

function attendanceTableColumns(PDO $pdo): array
{
    static $columns = null;

    if ($columns !== null) {
        return $columns;
    }

    $columns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM attendancet")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['Field']] = true;
    }

    return $columns;
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . ':' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$safeTable}`");
    $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $cache[$key] = in_array($column, $columns, true);

    return $cache[$key];
}

function attendanceInsertRecord(PDO $pdo, array $values): int
{
    $available = attendanceTableColumns($pdo);
    $columns = [];
    $placeholders = [];
    $params = [];

    foreach ($values as $column => $value) {
        if (!isset($available[$column])) {
            continue;
        }

        $placeholder = ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
        $columns[] = "`{$column}`";
        $placeholders[] = $placeholder;
        $params[$placeholder] = $value;
    }

    if (!$columns) {
        throw new RuntimeException('تعذر مطابقة أعمدة جدول الحضور مع البيانات المطلوبة');
    }

    $sql = "INSERT INTO attendancet (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}

function dashboardParsePeriod(string $filter): array
{
    $filterYr = null;
    $filterMn = null;
    $filterYrOnly = false;

    if ($filter === 'this_month') {
        $filterYr = (int) date('Y');
        $filterMn = (int) date('n');
        $from = date('Y-m-01');
        $to = date('Y-m-t');
    } elseif ($filter === 'last_month') {
        $filterYr = (int) date('Y', strtotime('-1 month'));
        $filterMn = (int) date('n', strtotime('-1 month'));
        $from = date('Y-m-01', strtotime('-1 month'));
        $to = date('Y-m-t', strtotime('-1 month'));
    } elseif ($filter === 'this_year') {
        $filterYr = (int) date('Y');
        $filterYrOnly = true;
        $from = date('Y-01-01');
        $to = date('Y-12-31');
    } elseif (strpos($filter, 'month_') === 0) {
        $parts = explode('_', $filter);
        $filterYr = (int) ($parts[1] ?? date('Y'));
        $filterMn = (int) ($parts[2] ?? date('n'));
        $from = sprintf('%04d-%02d-01', $filterYr, $filterMn);
        $to = date('Y-m-t', strtotime($from));
    } else {
        $filterYr = (int) date('Y');
        $filterMn = (int) date('n');
        $from = date('Y-m-01');
        $to = date('Y-m-t');
    }

    return [
        'from' => $from,
        'to' => $to,
        'year' => $filterYr,
        'month' => $filterMn,
        'year_only' => $filterYrOnly,
        'year_month' => date('Y-m', strtotime($from)),
        'filter' => $filter,
    ];
}

function dashboardScopeConfig(User $User, $currentUserId, $currentBranchId = null): array
{
    if ($User->userIsEmployee()) {
        return [
            'employee_only' => true,
            'user_id' => (int) $currentUserId,
            'branch_ids' => [],
        ];
    }

    $branchIds = [];
    if (!$User->userIsAdmin()) {
        foreach (explode(',', (string) ($User->branches ?? '')) as $branchId) {
            $branchId = (int) trim($branchId);
            if ($branchId > 0) {
                $branchIds[] = $branchId;
            }
        }
    }

    if (empty($branchIds) && !$User->userIsAdmin() && !empty($currentBranchId)) {
        $branchIds[] = (int) $currentBranchId;
    }

    return [
        'employee_only' => false,
        'user_id' => null,
        'branch_ids' => array_values(array_unique($branchIds)),
    ];
}

function dashboardBuildEmployeeScopeWhere(array $scope, string $userAlias = 'u', string $contractAlias = 'r'): array
{
    $clauses = [
        "COALESCE($userAlias.isemp, 0) = 1",
        "COALESCE($userAlias.IsDisabled, 0) = 0",
    ];
    $params = [];

    if (!empty($scope['employee_only']) && !empty($scope['user_id'])) {
        $clauses[] = "$userAlias.UserID = :scope_user_id";
        $params[':scope_user_id'] = (int) $scope['user_id'];
    } elseif (!empty($scope['branch_ids'])) {
        $placeholders = [];
        foreach ($scope['branch_ids'] as $idx => $branchId) {
            $placeholder = ':scope_branch_' . $idx;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (int) $branchId;
        }
        $clauses[] = "$contractAlias.BranchID IN (" . implode(',', $placeholders) . ")";
    }

    return [
        'sql' => implode(' AND ', $clauses),
        'params' => $params,
    ];
}

function dashboardBuildTransactionScopeWhere(array $scope, string $userColumn = 'UserID', string $branchColumn = 'BranchID'): array
{
    $clauses = [];
    $params = [];

    if (!empty($scope['employee_only']) && !empty($scope['user_id'])) {
        $clauses[] = "$userColumn = :scope_user_id";
        $params[':scope_user_id'] = (int) $scope['user_id'];
    } elseif (!empty($scope['branch_ids'])) {
        $placeholders = [];
        foreach ($scope['branch_ids'] as $idx => $branchId) {
            $placeholder = ':scope_branch_' . $idx;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (int) $branchId;
        }
        $clauses[] = "$branchColumn IN (" . implode(',', $placeholders) . ")";
    }

    return [
        'sql' => $clauses ? implode(' AND ', $clauses) : '1=1',
        'params' => $params,
    ];
}

function dashboardFormatMinutes(int $totalMinutes): string
{
    $totalMinutes = max(0, $totalMinutes);
    $hours = intdiv($totalMinutes, 60);
    $minutes = $totalMinutes % 60;
    return sprintf('%02d:%02d', $hours, $minutes);
}

function dashboardSumWorkingMinutes(PDO $pdo, array $scope, string $from, string $to): int
{
    $scopeWhere = dashboardBuildEmployeeScopeWhere($scope, 'u', 'r');
    $where = $scopeWhere['sql'] . " AND a.Date BETWEEN :work_from AND :work_to";
    $params = $scopeWhere['params'] + [
        ':work_from' => $from,
        ':work_to' => $to,
    ];

    $sql = "SELECT a.EmpID, a.Date,
                   MIN(CASE WHEN a.Type = 1 THEN a.Time END) AS first_in,
                   MAX(CASE WHEN a.Type = 2 THEN a.Time END) AS last_out
            FROM attendancet a
            INNER JOIN tblusers u ON u.UserID = a.EmpID
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            WHERE $where
            GROUP BY a.EmpID, a.Date";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $minutes = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (empty($row['first_in']) || empty($row['last_out'])) {
            continue;
        }

        $firstInTs = strtotime($row['Date'] . ' ' . $row['first_in']);
        $lastOutTs = strtotime($row['Date'] . ' ' . $row['last_out']);

        if ($firstInTs === false || $lastOutTs === false) {
            continue;
        }

        if ($lastOutTs < $firstInTs) {
            $lastOutTs += 86400;
        }

        $diffMinutes = (int) round(($lastOutTs - $firstInTs) / 60);
        if ($diffMinutes > 0) {
            $minutes += $diffMinutes;
        }
    }

    return $minutes;
}

function dashboardResolveRemainSalary(PDO $pdo, array $scope, string $from, string $to): float
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'salary_before_this_month'")->fetchColumn();
        if (!$exists) {
            return 0.0;
        }

        $columns = $pdo->query("SHOW COLUMNS FROM salary_before_this_month")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('remain_salary', $columns, true)) {
            return 0.0;
        }

        $where = ['1=1'];
        $params = [];

        if (!empty($scope['employee_only']) && !empty($scope['user_id']) && in_array('UserID', $columns, true)) {
            $where[] = "UserID = :scope_user_id";
            $params[':scope_user_id'] = (int) $scope['user_id'];
        } elseif (!empty($scope['branch_ids']) && in_array('BranchID', $columns, true)) {
            $placeholders = [];
            foreach ($scope['branch_ids'] as $idx => $branchId) {
                $placeholder = ':scope_branch_' . $idx;
                $placeholders[] = $placeholder;
                $params[$placeholder] = (int) $branchId;
            }
            $where[] = "BranchID IN (" . implode(',', $placeholders) . ")";
        }

        foreach (['DueDate', 'date', 'CreatedDate', 'created_date'] as $dateColumn) {
            if (in_array($dateColumn, $columns, true)) {
                $where[] = "$dateColumn BETWEEN :remain_from AND :remain_to";
                $params[':remain_from'] = $from;
                $params[':remain_to'] = $to;
                break;
            }
        }

        $sql = "SELECT COALESCE(SUM(CAST(remain_salary AS DECIMAL(12,2))), 0) FROM salary_before_this_month WHERE " . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function getAdminNotificationTargets(PDO $pdo, int $requesterId, int $branchId = 0): array
{
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.UserID
        FROM tblusers u
        LEFT JOIN tblusergroups g ON g.GroupID = u.UserGroupID
        WHERE COALESCE(u.IsDisabled, 0) = 0
          AND u.UserID <> :requester_id
          AND COALESCE(u.isemp, 0) = 0
          AND (
                COALESCE(u.IsSystem, 0) = 1
                OR COALESCE(g.FullAccess, 0) = 1
                OR COALESCE(g.IsSystem, 0) = 1
                OR COALESCE(u.UserGroupID, 0) = 4
          )
          AND (
                :branch_any = 0
                OR COALESCE(u.IsSystem, 0) = 1
                OR COALESCE(g.FullAccess, 0) = 1
                OR COALESCE(g.IsSystem, 0) = 1
                OR COALESCE(u.BranchID, 0) = :branch_match
                OR FIND_IN_SET(CAST(:branch_allowed AS CHAR), REPLACE(COALESCE(u.AllowedBranches, ''), ' ', '')) > 0
          )
    ");
    $stmt->execute([
        ':requester_id' => $requesterId,
        ':branch_any' => $branchId,
        ':branch_match' => $branchId,
        ':branch_allowed' => $branchId,
    ]);

    return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

function notifyAdminsAboutRequest(PDO $pdo, int $requesterId, int $branchId, string $title, string $message, string $entityType, int $entityId): void
{
    $targetUserIds = getAdminNotificationTargets($pdo, $requesterId, $branchId);
    if (empty($targetUserIds)) {
        return;
    }

    require_once __DIR__ . '/../classes/NotificationService.php';
    $notifService = new NotificationService($pdo);
    $notifService->notifyMany($targetUserIds, $title, $message, 'info', $entityType, $entityId);
}

// Helper function for position markers
function isPositionMarker($row, $col)
{
    $size = 25;
    $markerSize = 7;

    // Top-left corner
    if ($row < $markerSize && $col < $markerSize) {
        return ($row === 0 || $row === $markerSize - 1 || $col === 0 || $col === $markerSize - 1) ||
            (($row === 2 || $row === 4) && ($col === 2 || $col === 4));
    }

    // Top-right corner
    if ($row < $markerSize && $col >= $size - $markerSize) {
        return ($row === 0 || $row === $markerSize - 1 || $col === $size - 1 || $col === $size - $markerSize) ||
            (($row === 2 || $row === 4) && ($col === $size - 3 || $col === $size - 5));
    }

    // Bottom-left corner
    if ($row >= $size - $markerSize && $col < $markerSize) {
        return ($row === $size - 1 || $row === $size - $markerSize || $col === 0 || $col === $markerSize - 1) ||
            (($row === $size - 3 || $row === $size - 5) && ($col === 2 || $col === 4));
    }

    // Bottom-right corner
    if ($row >= $size - $markerSize && $col >= $size - $markerSize) {
        return ($row === $size - 1 || $row === $size - $markerSize || $col === $size - 1 || $col === $size - $markerSize) ||
            (($row === $size - 3 || $row === $size - 5) && ($col === $size - 3 || $col === $size - 5));
    }

    return false;
}

// Helper: require login for all actions
if (!$user && !in_array($action, ['', 'qr-validate'])) {
    $unauthorizedPayload = ['result' => false, 'msg' => 'غير مصرح، يرجى تسجيل الدخول'];
    if ($allowDebugResponse) {
        $unauthorizedPayload['debug_session'] = $debug_data;
    }
    echo json_encode($unauthorizedPayload, JSON_UNESCAPED_UNICODE);
    exit;
}
// --- Function to handle 'report-all-emplyer' ---

$employee_status_map = [
    1 => 'استقالة',
    2 => 'فصل',
    3 => 'إنهاء عقد',
    4 => 'وفاة',
];

switch ($action) {

    // ============================================================
// ATTENDANCE CHECK-IN / CHECK-OUT (Hrdashboard â€” GPS method)
// ============================================================
    case 'Hrdashboard':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $today = date('Y-m-d');
            $time = date('H:i:s');
            $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
            $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;
            $method = 'gps';

            $check = $connect_pdo->prepare("SELECT Type FROM attendancet WHERE EmpID=:uid AND Date=:d ORDER BY ID DESC LIMIT 1");
            $check->execute([':uid' => $user, ':d' => $today]);
            $last = $check->fetch();
            $type = (!$last || $last['Type'] == 2) ? 1 : 2;
            $label = $type == 1 ? 'تم تسجيل الحضور بنجاح' : 'تم تسجيل الانصراف بنجاح';

            $attId = attendanceInsertRecord($connect_pdo, [
                'EmpID' => $user,
                'who_add' => $user,
                'Time' => $time,
                'Type' => $type,
                'Date' => $today,
                'lat' => $lat,
                'lng' => $lng,
                'method' => $method,
                'device_info' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);

            $Audit->logAttendance($attId, $user, $type, $method, $lat, $lng);
            $msg = $label . ' - ' . $time;
        } else {
            $result = false;
            $msg = 'غير مصرح';
        }
        break;

    // ============================================================
// ADMIN MAIL SETTINGS â€” save/test/send-test
// ============================================================
    case 'save-mail-settings':
        if (!($User->userIsAdmin() || $User->userIsEmployer())) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $type = strtolower(trim((string) ($_POST['type'] ?? '')));
        $mailService = new MailService($connect_pdo);
        if ($type === 'smtp') {
            $response = $mailService->saveSmtpSettings($_POST);
            $result = (bool) ($response['result'] ?? false);
            $msg = (string) ($response['msg'] ?? '');
        } elseif ($type === 'template') {
            $response = $mailService->saveTemplateSettings($_POST);
            $result = (bool) ($response['result'] ?? false);
            $msg = (string) ($response['msg'] ?? '');
        } else {
            $result = false;
            $msg = 'نوع الإعداد غير معروف';
        }
        break;
    /*
            $current['smtp'] = [
                'smtp_host'        => $_POST['smtp_host'] ?? '',
                'smtp_port'        => (int)($_POST['smtp_port'] ?? 587),
                'smtp_encryption'  => $_POST['smtp_encryption'] ?? 'tls',
                'smtp_username'    => $_POST['smtp_username'] ?? '',
                'smtp_password'    => !empty($_POST['smtp_password']) ? $_POST['smtp_password'] : ($current['smtp']['smtp_password'] ?? ''),
                'smtp_from_email'  => $_POST['smtp_from_email'] ?? '',
                'smtp_from_name'   => $_POST['smtp_from_name'] ?? ''
            ];
            @file_put_contents($storeFile, json_encode($current, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $msg = 'ØªÙ… Ø­ÙØ¸ Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª SMTP';
        } elseif ($type === 'template') {
            $current['template'] = [
                'reset_email_subject'  => $_POST['reset_email_subject'] ?? '',
                'reset_email_template' => $_POST['reset_email_template'] ?? ''
            ];
            @file_put_contents($storeFile, json_encode($current, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $msg = 'ØªÙ… Ø­ÙØ¸ Ø§Ù„Ù‚Ø§Ù„Ø¨ Ø¨Ù†Ø¬Ø§Ø­';
        } else {
            $result = false; $msg = 'Ù†ÙˆØ¹ ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ';
        }
        break;

    */
    case 'test-smtp-connection':
        if (!($User->userIsAdmin() || $User->userIsEmployer())) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $mailService = new MailService($connect_pdo);
        $response = $mailService->testConnection($_POST);
        $result = (bool) ($response['result'] ?? false);
        $msg = (string) ($response['msg'] ?? ''); /*
$msg = 'ØªÙ… Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ø­Ù‚ÙˆÙ„ Ø¨Ù†Ø¬Ø§Ø­ (ØªØ­Ù‚Ù‚ Ø£ÙˆÙ„ÙŠ)';
} else {
$result = false; $msg = 'Ø§Ù„Ø±Ø¬Ø§Ø¡ ØªØ¹Ø¨Ø¦Ø© Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø­Ù‚ÙˆÙ„ Ø¨Ø´ÙƒÙ„ ØµØ­ÙŠØ­';
*/
        break;

    case 'send-test-email':
        if (!($User->userIsAdmin() || $User->userIsEmployer())) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $mailService = new MailService($connect_pdo);
        $response = $mailService->sendTestEmail(trim((string) ($_POST['email'] ?? ''))); /*
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $result=false; $msg='Ø¨Ø±ÙŠØ¯ ØºÙŠØ± ØµØ§Ù„Ø­'; break; }
// Simulate send for now (no external SMTP requirement here)
$msg = 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„ØªØ¬Ø±ÙŠØ¨ÙŠ (Ù…Ø­Ø§ÙƒØ§Ø©)';
*/
        $result = (bool) ($response['result'] ?? false);
        $msg = (string) ($response['msg'] ?? '');
        break;

    // ============================================================
// EMPLOYEE LIST â€” DataTables server-side
// ============================================================
    case 'employer-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $name = $_POST['name'] ?? '';
        $section = $_POST['section'] ?? [];
        $jobtitle = $_POST['jobtitle'] ?? [];
        $grade = $_POST['grade'] ?? [];
        $shift = $_POST['shift'] ?? [];
        $groub = $_POST['groub'] ?? [];
        $branchs = $_POST['branchs'] ?? [];

        $where = "WHERE u.isemp IS NOT NULL";
        $params = [];
        $hasCertificatesTable = (bool) $connect_pdo->query("SHOW TABLES LIKE 'user_cer'")->fetchColumn();
        $hasExperiencesTable = (bool) $connect_pdo->query("SHOW TABLES LIKE 'user_exper'")->fetchColumn();

        if (!empty($name)) {
            $where .= " AND (u.FirstName LIKE :n OR u.LastName LIKE :n OR u.UserEmail LIKE :n)";
            $params[':n'] = "%$name%";
        }
        if (!empty($section) && is_array($section)) {
            $sIds = implode(',', array_map('intval', $section));
            if ($sIds)
                $where .= " AND k.SectionID IN ($sIds)";
        }
        if (!empty($jobtitle) && is_array($jobtitle)) {
            $jIds = implode(',', array_map('intval', $jobtitle));
            if ($jIds)
                $where .= " AND k.jobtitleID IN ($jIds)";
        }
        if (!empty($grade) && is_array($grade)) {
            $gIds = implode(',', array_map('intval', $grade));
            if ($gIds)
                $where .= " AND k.GradeID IN ($gIds)";
        }

        $totalQ = "SELECT COUNT(*) FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion=k.Id WHERE u.isemp IS NOT NULL";
        $totalS = $connect_pdo->prepare($totalQ);
        $totalS->execute();
        $recordsTotal = (int) $totalS->fetchColumn();

        $cntQ = "SELECT COUNT(*) FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion=k.Id $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        $certSelect = $hasCertificatesTable ? ", COALESCE(cert.cert_count, 0) AS cert_count, cert.first_cert_path" : ", 0 AS cert_count, NULL AS first_cert_path";
        $certJoin = $hasCertificatesTable ? " LEFT JOIN (SELECT UserID, COUNT(*) AS cert_count, MIN(FilePath) AS first_cert_path FROM user_cer WHERE FilePath IS NOT NULL AND FilePath != '' GROUP BY UserID) cert ON cert.UserID = u.UserID " : "";
        $expSelect = $hasExperiencesTable ? ", COALESCE(exp.exp_count, 0) AS exp_count" : ", 0 AS exp_count";
        $expJoin = $hasExperiencesTable ? " LEFT JOIN (SELECT UserID, COUNT(*) AS exp_count FROM user_exper GROUP BY UserID) exp ON exp.UserID = u.UserID " : "";

        // Fetch rows
        $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.UserEmail, u.Phone, u.IsDisabled, u.Photo,
                   k.Salary, k.Currency, b.branch_name, s.Name as section_name, j.Name as jobtitle_name
                   $certSelect
                   $expSelect
            FROM tblusers u
            LEFT JOIN tblremewal k ON u.lastversion=k.Id
            LEFT JOIN branches b ON b.branch_id=k.BranchID
            LEFT JOIN tblsection s ON s.Id=k.SectionID
            LEFT JOIN tbljobtitle j ON j.Id=k.jobtitleID
            $certJoin
            $expJoin
            $where ORDER BY u.UserID DESC LIMIT $start, $length";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll();

        // Format for DataTables â€” 7 columns matching the <th> in employer-list.php
        $formatted = [];
        foreach ($rows as $r) {
            $empName = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? ''));
            if ($empName === '') {
                $empName = (string) ($r['UserEmail'] ?? ('#' . $r['UserID']));
            }
            $photoPath = trim((string) ($r['Photo'] ?? ''));
            $photoUrl = 'dist/img/avatar-default.png';
            if ($photoPath !== '') {
                $photoUrl = preg_match('#^(https?:)?//#', $photoPath) ? $photoPath : ltrim($photoPath, '/');
            }
            $photo = '<img src="' . htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') . '" class="img-circle employee-list-photo" width="38" height="38" alt="صورة الموظف" onerror="this.onerror=null;this.src=&quot;dist/img/avatar-default.png&quot;;"> ';
            $status = empty($r['IsDisabled']) ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">موقوف</span>';
            $currency = trim((string) ($r['Currency'] ?? ''));
            if ($currency === '' || preg_match('/�|�/', $currency) || strtoupper($currency) === 'SAR') {
                $currency = 'ر.س';
            }
            $certCount = (int) ($r['cert_count'] ?? 0);
            $expCount = (int) ($r['exp_count'] ?? 0);
            
            // Generate certificate link - if certificates exist and have files, link to first file, otherwise link to emp-info page
            $certLink = '';
            if ($certCount > 0 && !empty($r['first_cert_path'])) {
                $certPath = preg_match('#^(https?:)?//#', $r['first_cert_path']) ? $r['first_cert_path'] : ltrim($r['first_cert_path'], '/');
                $certLink = '<a href="' . htmlspecialchars($certPath, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary mt-1">عرض الملف</a>';
            } else {
                $certLink = '<button type="button" class="btn btn-xs btn-outline-warning mt-1" disabled>لا يوجد ملف</button>';
            }
            
            $docsInfo = '
            <div class="d-flex flex-column">
                <small class="text-muted">الشهادات: ' . $certCount . '</small>
                <small class="text-muted">الخبرات: ' . $expCount . '</small>
                ' . $certLink . '
            </div>';
            $actions = '<a href="emp-info?id=' . $r['UserID'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a> ';
            $actions .= '<a href="employer-add?id=' . $r['UserID'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>';

            $formatted[] = [
                $photo . $empName,
                $r['section_name'] ?? '-',
                number_format((float) ($r['Salary'] ?? 0), 2) . ' ' . $currency,
                $r['branch_name'] ?? '-',
                $docsInfo,
                $status,
                $actions
            ];
        }

        if (ob_get_length())
            ob_clean();
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $total,
            'data' => $formatted
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'allUserInfo': // This is the new case to handle the frontend request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $branchId = (int) $_POST['value']; // 'value' is the BranchID sent from frontend

        if ($branchId <= 0) {
            $result = false;
            $msg = 'معرف الفرع غير صالح';
            break;
        }

        $parma_ = array(
            ':BranchID' => $branchId,
        );

        // --- Sections (Query 2 from employer-add.php) ---
        $query_sections = "SELECT c.Id, c.Name FROM tblsection AS c
                       LEFT JOIN tblsection AS d ON c.Id = d.ParentID
                       WHERE c.ParentID IS NOT NULL AND d.Id IS NULL AND c.BranchID = :BranchID ";
        $stm_sections = $connect_pdo->prepare($query_sections);
        $stm_sections->execute($parma_);
        $sections = $stm_sections->fetchAll(PDO::FETCH_ASSOC);

        // --- Groups (Query 3 from employer-add.php) ---
        $query_groups = "SELECT Id, Name FROM tblgroup where BranchID = :BranchID";
        $stm_groups = $connect_pdo->prepare($query_groups);
        $stm_groups->execute($parma_);
        $groups = $stm_groups->fetchAll(PDO::FETCH_ASSOC);

        // --- Job Grades (Query 4 from employer-add.php) ---
        $query_jobgrades = "SELECT Id, Name FROM tbljobgrade where BranchID = :BranchID";
        $stm_jobgrades = $connect_pdo->prepare($query_jobgrades);
        $stm_jobgrades->execute($parma_);
        $jobgrades = $stm_jobgrades->fetchAll(PDO::FETCH_ASSOC);

        // --- Insurance Companies (Query 5 from employer-add.php) ---
        $query_insurance = "SELECT Id , Name FROM tbinsurance WHERE BranchID = :BranchID AND state=1";
        $stm_insurance = $connect_pdo->prepare($query_insurance);
        $stm_insurance->execute($parma_);
        $insurance = $stm_insurance->fetchAll(PDO::FETCH_ASSOC);

        // --- Shifts (Query 6 from employer-add.php) ---
        $query_shifts = "SELECT ShiftID ,ShiftName FROM tbshift WHERE BranchID = :BranchID AND ShiftState=0";
        $stm_shifts = $connect_pdo->prepare($query_shifts);
        $stm_shifts->execute($parma_);
        $shifts = $stm_shifts->fetchAll(PDO::FETCH_ASSOC);

        // --- Employment Types (Query 7 from employer-add.php) ---
        $query_employment_types = "SELECT Id ,Name FROM tblemploymenttype WHERE BranchID = :BranchID";
        $stm_employment_types = $connect_pdo->prepare($query_employment_types);
        $stm_employment_types->execute($parma_);
        $employment_types = $stm_employment_types->fetchAll(PDO::FETCH_ASSOC);

        // --- Fingerprints (Query 8 from employer-add.php) ---
        $query_fingerprints = "SELECT FingerprintID ,FingerprintName FROM tbfingerprint WHERE BranchID = :BranchID AND FingerprintState=1";
        $stm_fingerprints = $connect_pdo->prepare($query_fingerprints);
        $stm_fingerprints->execute($parma_);
        $fingerprints = $stm_fingerprints->fetchAll(PDO::FETCH_ASSOC);

        // --- Job Titles (Query 9 from employer-add.php) ---
        $query_jobtitles = "SELECT Id ,Name FROM tbljobtitle WHERE BranchID = :BranchID";
        $stm_jobtitles = $connect_pdo->prepare($query_jobtitles);
        $stm_jobtitles->execute($parma_);
        $jobtitles = $stm_jobtitles->fetchAll(PDO::FETCH_ASSOC);

        // --- Related Users (Query 10 from employer-add.php) ---
        $query_user_relate = "SELECT UserID ,FirstName,LastName FROM tblusers
                          WHERE BranchID = :BranchID
                          AND isemp IS NULL
                          AND UserID NOT IN (SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)";
        $stm_user_relate = $connect_pdo->prepare($query_user_relate);
        $stm_user_relate->execute($parma_);
        $user_relate = $stm_user_relate->fetchAll(PDO::FETCH_ASSOC);

        // --- User Managers (Query 11 from employer-add.php) ---
        $query_user_manager = "SELECT UserID ,FirstName,LastName FROM tblusers
                           WHERE BranchID = :BranchID
                           AND isemp IS NOT NULL";
        $stm_user_manager = $connect_pdo->prepare($query_user_manager);
        $stm_user_manager->execute($parma_);
        $user_manager = $stm_user_manager->fetchAll(PDO::FETCH_ASSOC);

        // Format the collected data as expected by the frontend's populateSelect function
        $data = [
            'section' => formatForSelect($sections, 'Id', 'Name'),
            'jobtitle' => formatForSelect($jobtitles, 'Id', 'Name'),
            'JobGrade' => formatForSelect($jobgrades, 'Id', 'Name'),
            'Shift' => formatForSelect($shifts, 'ShiftID', 'ShiftName'),
            'fingerprint' => formatForSelect($fingerprints, 'FingerprintID', 'FingerprintName'),
            'insurance' => formatForSelect($insurance, 'Id', 'Name'),
            'groub' => formatForSelect($groups, 'Id', 'Name'),
            'tblemploymenttype' => formatForSelect($employment_types, 'Id', 'Name'),
            'user_related_to' => formatUsersForSelect($user_relate),
            'user_manager' => formatUsersForSelect($user_manager),
        ];

        $result = true;
        $msg = 'تم جلب بيانات الفرع بنجاح';
        break;

    case 'report-all-emplyer':
        handleReportAllEmployer($connect_pdo, $User, $employee_status_map);
        break; // <--- IMPORTANT: Don't forget 'break;' here!

        function handleReportAllEmployer($pdo, $User, $employee_status_map)
        {
            header('Content-Type: application/json');

            // DataTables parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

            // Custom filter parameters
            $emp_name = isset($_POST['name']) ? trim($_POST['name']) : '';
            // Status ID: For 'Ù…Ø³ØªÙ…Ø±' (ID 1 in HTML dropdown), we'll assume it maps to 0 or NULL in DB.
            // For other statuses (2-5), it will be the actual DB value.
            $status_filter_value = isset($_POST['status']) ? (int) $_POST['status'] : 0;
            if ($status_filter_value === 1) { // If "Ù…Ø³ØªÙ…Ø±" (Active) is selected
                $db_status_for_filter = null; // Assuming NULL in DB means active
            } else if ($status_filter_value > 1) { // For other specific statuses
                $db_status_for_filter = $status_filter_value - 1; // Adjusting to potentially map 2->1, 3->2 etc.
                // *** YOU MUST ADJUST THIS LOGIC BASED ON YOUR DB! ***
            } else { // No specific status selected, or invalid
                $db_status_for_filter = 0; // Means "any status" for now, or could map to active.
            }


            $sections = isset($_POST['section']) && is_array($_POST['section']) ? array_map('intval', $_POST['section']) : [];
            $job_titles = isset($_POST['jobtitle']) && is_array($_POST['jobtitle']) ? array_map('intval', $_POST['jobtitle']) : [];
            $grades = isset($_POST['grade']) && is_array($_POST['grade']) ? array_map('intval', $_POST['grade']) : [];
            $shifts = isset($_POST['shift']) && is_array($_POST['shift']) ? array_map('intval', $_POST['shift']) : [];
            $branches_filter = isset($_POST['branchs']) && is_array($_POST['branchs']) ? array_map('intval', $_POST['branchs']) : [];
            $groups = isset($_POST['groub']) && is_array($_POST['groub']) ? array_map('intval', $_POST['groub']) : [];

            // --- Retrieve branches allowed for the current user ---
            $allowed_user_branches_info = $User->allBranches($User->branches);
            $allowed_user_branch_ids = array_keys($allowed_user_branches_info);

            // Initial check: If user has no allowed branches, they can't see anything.
            if (empty($allowed_user_branch_ids)) {
                echo json_encode([
                    "draw" => $draw,
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => [],
                    "results_note" => [
                        "report_time" => date('Y-m-d H:i:s'),
                        "selected_period" => "جميع الفترات",
                        "filter_note" => "لا تملك صلاحية الوصول لأي فرع.",
                        "selected_branch" => ""
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }

            // Build the base query for SELECT and FROM clauses
            $select_cols = "
        CONCAT(u.FirstName, ' ', IFNULL(u.SecondName, ''), ' ', u.LastName) AS employee_name,
        b.branch_name,
        NULL AS job_title_name,     /* Missing link to tbljobtitle from tblusers */
        NULL AS section_name,       /* Missing link to tblsection from tblusers */
        u.start_date_h AS contract_start_date,
        u.end_date_h AS contract_end_date,
        NULL AS salary              /* Missing salary column/link */
    ";

            $from_tables = "
        FROM tblusers u
        JOIN branches b ON u.BranchID = b.branch_id
        /* JOINs for other tables will go here once you provide the linking columns/tables */
        LEFT JOIN tblgroup tg ON u.UserGroupID = tg.Id /* Assuming UserGroupID links to tblgroup */
    ";

            $where_clauses = [];
            $params = [];
            $filter_notes = [];

            // --- MANDATORY: Filter for employees only ---
            $where_clauses[] = "u.isemp = 1";

            // --- Apply user's branch permissions as the primary filter ---
            $placeholders_allowed_branches = implode(',', array_fill(0, count($allowed_user_branch_ids), '?'));
            $where_clauses[] = "u.BranchID IN ($placeholders_allowed_branches)";
            $params = array_merge($params, $allowed_user_branch_ids);


            // --- Apply custom filters from the form ---

            // Employee Name (using CONCAT for search)
            if (!empty($emp_name)) {
                $where_clauses[] = "CONCAT(u.FirstName, ' ', IFNULL(u.SecondName, ''), ' ', u.LastName) LIKE ?";
                $params[] = '%' . $emp_name . '%';
                $filter_notes[] = "اسم الموظف: " . htmlspecialchars($emp_name);
            }

            // Employee Status (using the ID from the HTML dropdown and mapping for DB)
            if ($status_filter_value > 0) { // If any status (including "Ù…Ø³ØªÙ…Ø±") is selected
                if ($status_filter_value === 1) { // "Ù…Ø³ØªÙ…Ø±" (Active)
                    $where_clauses[] = "u.resigned_or_dismissed IS NULL OR u.resigned_or_dismissed = 0"; // Assuming NULL or 0 means active
                    $filter_notes[] = "حالة الموظف: مستمر";
                } else if (isset($employee_status_map[$db_status_for_filter])) { // For other specific statuses (2-5 in HTML, mapped to DB values)
                    $where_clauses[] = "u.resigned_or_dismissed = ?";
                    $params[] = $db_status_for_filter;
                    $filter_notes[] = "حالة الموظف: " . $employee_status_map[$db_status_for_filter];
                }
            }

            // Sections (FILTER DISABLED until linking column is provided)
            if (!empty($sections)) {
                // $placeholders = implode(',', array_fill(0, count($sections), '?'));
                // $where_clauses[] = "u.SectionID IN ($placeholders)"; // This column does not exist in tblusers
                // $params = array_merge($params, $sections);
                $filter_notes[] = "القسم: (غير فعال، العمود غير موجود أو غير مرتبط بـ tblusers)";
            }

            // Job Titles (FILTER DISABLED until linking column is provided)
            if (!empty($job_titles)) {
                // $placeholders = implode(',', array_fill(0, count($job_titles), '?'));
                // $where_clauses[] = "u.JobTitleID IN ($placeholders)"; // This column does not exist in tblusers
                // $params = array_merge($params, $job_titles);
                $filter_notes[] = "المسمى الوظيفي: (غير فعال، العمود غير موجود أو غير مرتبط بـ tblusers)";
            }

            // Job Grades (FILTER DISABLED until linking column is provided)
            if (!empty($grades)) {
                // $placeholders = implode(',', array_fill(0, count($grades), '?'));
                // $where_clauses[] = "u.JobGradeID IN ($placeholders)"; // This column does not exist in tblusers
                // $params = array_merge($params, $grades);
                $filter_notes[] = "الدرجة الوظيفية: (غير فعال، العمود غير موجود أو غير مرتبط بـ tblusers)";
            }

            // Shifts (FILTER DISABLED until linking column is provided)
            if (!empty($shifts)) {
                // $placeholders = implode(',', array_fill(0, count($shifts), '?'));
                // $where_clauses[] = "u.ShiftID IN ($placeholders)"; // This column does not exist in tblusers
                // $params = array_merge($params, $shifts);
                $filter_notes[] = "فترات العمل: (غير فعال، العمود غير موجود أو غير مرتبط بـ tblusers)";
            }

            // Job Groups (assuming u.UserGroupID links to tblgroup.Id)
            if (!empty($groups)) {
                $placeholders = implode(',', array_fill(0, count($groups), '?'));
                $where_clauses[] = "u.UserGroupID IN ($placeholders)";
                $params = array_merge($params, $groups);
                $filter_notes[] = "المجموعة الوظيفية: " . implode(', ', getNamesByIds($pdo, 'tblgroup', 'Id', 'Name', $groups));
            }

            // Branches filter from the form (must be within user's allowed branches)
            if (!empty($branches_filter)) {
                $intersected_branches = array_intersect($branches_filter, $allowed_user_branch_ids);
                if (!empty($intersected_branches)) {
                    // Remove the general branch permission filter (first one added) and re-add specific branches
                    // This is safer to avoid issues with parameter count and placeholders
                    array_shift($where_clauses); // Remove the first 'e.BranchID IN (...)'
                    array_splice($params, 0, count($allowed_user_branch_ids)); // Remove its parameters

                    $placeholders_filter = implode(',', array_fill(0, count($intersected_branches), '?'));
                    $where_clauses[] = "u.BranchID IN ($placeholders_filter)";
                    $params = array_merge($params, $intersected_branches);
                    $filter_notes[] = "الفرع: " . implode(', ', getNamesByIds($pdo, 'branches', 'branch_id', 'branch_name', $intersected_branches));
                } else {
                    // If user selected branches they don't have access to, return empty result
                    echo json_encode([
                        "draw" => $draw,
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        "data" => [],
                        "results_note" => [
                            "report_time" => date('Y-m-d H:i:s'),
                            "selected_period" => "جميع الفترات",
                            "filter_note" => "الفروع المختارة لا تتوافق مع صلاحياتك.",
                            "selected_branch" => ""
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    exit();
                }
            } else {
                // If no specific branches selected in form filter, just state all allowed are included
                $selected_branches_names_for_note = getNamesByIds($pdo, 'branches', 'branch_id', 'branch_name', $allowed_user_branch_ids);
                $filter_notes[] = "الفرع: " . implode(', ', $selected_branches_names_for_note);
            }


            $where_sql = '';
            if (!empty($where_clauses)) {
                $where_sql = " WHERE " . implode(" AND ", $where_clauses);
            }

            // --- 1. Get total records without any filters (recordsTotal) ---
            // Count total employees visible to the user based on their branch permissions.
            // Ensure all base filters (isemp = 1, allowed branches) are applied.
            $total_records_base_where = ["u.isemp = 1"];
            $total_records_base_params = [];

            // Add branch permission filter to the base total count
            $total_records_base_where[] = "u.BranchID IN ($placeholders_allowed_branches)";
            $total_records_base_params = array_merge($total_records_base_params, $allowed_user_branch_ids);

            $total_records_query_base = "
        FROM tblusers u
        WHERE " . implode(" AND ", $total_records_base_where);

            $stmt = $pdo->prepare("SELECT COUNT(u.UserID) " . $total_records_query_base);
            $stmt->execute($total_records_base_params);
            $recordsTotal = $stmt->fetchColumn();


            // --- 2. Get total records *after* applying ALL filters (recordsFiltered) ---
            $filtered_records_query = "SELECT COUNT(u.UserID) " . $from_tables . $where_sql;
            $stmt = $pdo->prepare($filtered_records_query);
            $stmt->execute($params);
            $recordsFiltered = $stmt->fetchColumn();

            // Column mapping for ordering
            // IMPORTANT: Ensure these match the actual columns/aliases in your SELECT statement
            $columns = [
                'employee_name',           // 0: Ø§Ø³Ù… Ø§Ù„ÙˆØ¸Ù (aliased CONCAT)
                'b.branch_name',           // 1: Ø§Ù„ÙØ±Ø¹
                'job_title_name',          // 2: Ø§Ù„Ù…Ø³Ù…Ù‰ Ø§Ù„ÙˆØ¸ÙŠÙÙŠ (NULL placeholder)
                'section_name',            // 3: Ø§Ù„Ù‚Ø³Ù… (NULL placeholder)
                'u.start_date_h',          // 4: ØªØ§Ø±ÙŠØ® Ø¨Ø¯Ø§ÙŠØ© Ø§Ù„Ø¹Ù‚Ø¯
                'u.end_date_h',            // 5: ØªØ§Ø±ÙŠØ® Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ù„Ø¹Ù‚Ø¯
                'salary'                   // 6: Ø§Ù„Ø±Ø§ØªØ¨ (NULL placeholder)
            ];
            // DataTables sends a column index for ordering.
            $order_by = $columns[$order_column_index] . ' ' . $order_dir;
            // Special handling for the concatenated name column if DataTables can't order by alias
            if ($order_column_index === 0) {
                $order_by = "CONCAT(u.FirstName, ' ', IFNULL(u.SecondName, ''), ' ', u.LastName) " . $order_dir;
            }


            // --- 3. Get the data for the current page ---
            $data_query = "
        SELECT
            $select_cols
        " . $from_tables . $where_sql . "
        ORDER BY {$order_by}
        LIMIT ?, ?
    ";
            $stmt = $pdo->prepare($data_query);

            // Bind parameters for filters and LIMIT
            $final_params = array_merge($params, [$start, $length]);
            $stmt->execute($final_params);

            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [
                    $row['employee_name'],
                    $row['branch_name'],
                    $row['job_title_name'] ?? 'غير محدد',
                    $row['section_name'] ?? 'غير محدد',
                    $row['contract_start_date'] ?? 'غير محدد',
                    $row['contract_end_date'] ?? 'غير محدد',
                    number_format($row['salary'] ?? 0, 2)
                ];
            }

            $filter_note_text = !empty($filter_notes) ? "الفلاتر المطبقة: " . implode(" | ", $filter_notes) : "لا توجد فلاتر مطبقة.";

            $response = [
                "draw" => $draw,
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data,
                "results_note" => [
                    "report_time" => date('Y-m-d H:i:s'),
                    "selected_period" => "جميع الفترات", // This can be dynamic if you add date filters
                    "filter_note" => $filter_note_text,
                    "selected_branch" => "" // This will be populated client-side from the dropdown
                ]
            ];

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }
    // ============================================================
// FILTER DROPDOWNS (sections, jobtitles, grades, shifts, groups) - MODIFIED FOR REUSABILITY
// ============================================================
    case 'allUserinfo_Search':
        $branchId = isset($_POST['value']) ? (int) $_POST['value'] : 0;
        $cacheKey = perf_cache_key('alluserinfo-search', ['branch_id' => $branchId]);
        $cachedPayload = perf_cache_get($cacheKey);
        if (is_array($cachedPayload)) {
            echo json_encode($cachedPayload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Get branch-specific data if branch provided, otherwise get all
        $branchFilter = $branchId ? " WHERE BranchID = $branchId" : "";
        $branchFilterAnd = $branchId ? " AND BranchID = $branchId" : "";

        $sections = $connect_pdo->query("SELECT Id, Name FROM tblsection $branchFilter ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
        $jobtitles = $connect_pdo->query("SELECT Id, Name FROM tbljobtitle $branchFilter ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
        $grades = $connect_pdo->query("SELECT Id, Name FROM tbljobgrade $branchFilter ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
        $shifts = $connect_pdo->query("SELECT ShiftID as Id, ShiftName as Name FROM tbshift " . ($branchId ? "WHERE BranchID = $branchId AND ShiftState=0" : "WHERE ShiftState=0") . " ORDER BY ShiftName")->fetchAll(PDO::FETCH_ASSOC);
        $groups = $connect_pdo->query("SELECT Id, Name FROM tblgroup $branchFilter ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
        $fingerprints = $connect_pdo->query("SELECT FingerprintID as Id, FingerprintName as Name FROM tbfingerprint " . ($branchId ? "WHERE BranchID = $branchId AND FingerprintState=1" : "WHERE FingerprintState=1") . " ORDER BY FingerprintName")->fetchAll(PDO::FETCH_ASSOC);
        $insurance = $connect_pdo->query("SELECT Id, Name FROM tbinsurance " . ($branchId ? "WHERE BranchID = $branchId AND state=1" : "WHERE state=1") . " ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
        $employmentTypes = $connect_pdo->query("SELECT Id, Name FROM tblemploymenttype $branchFilter ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);

        // Get users for related_to and manager dropdowns
        $userRelatedTo = $connect_pdo->query("SELECT UserID as Id, CONCAT(FirstName, ' ', LastName) as Name FROM tblusers WHERE isemp IS NULL " . ($branchId ? "AND BranchID = $branchId" : "") . " AND UserID NOT IN (SELECT related_to FROM tblusers WHERE related_to IS NOT NULL) ORDER BY FirstName")->fetchAll(PDO::FETCH_ASSOC);
        $userManager = $connect_pdo->query("SELECT UserID as Id, CONCAT(FirstName, ' ', LastName) as Name FROM tblusers WHERE isemp IS NOT NULL " . ($branchId ? "AND BranchID = $branchId" : "") . " ORDER BY FirstName")->fetchAll(PDO::FETCH_ASSOC);

        $payload = [
            'section' => formatForSelect($sections, 'Id', 'Name'),
            'jobtitle' => formatForSelect($jobtitles, 'Id', 'Name'),
            'JobGrade' => formatForSelect($grades, 'Id', 'Name'),
            'Shift' => formatForSelect($shifts, 'Id', 'Name'),
            'groub_list' => formatForSelect($groups, 'Id', 'Name'),
            'fingerprint' => formatForSelect($fingerprints, 'Id', 'Name'),
            'insurance' => formatForSelect($insurance, 'Id', 'Name'),
            'tblemploymenttype' => formatForSelect($employmentTypes, 'Id', 'Name'),
            'user_related_to' => formatForSelect($userRelatedTo, 'Id', 'Name'),
            'user_manager' => formatForSelect($userManager, 'Id', 'Name'),
        ];
        perf_cache_set($cacheKey, $payload, 300);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;

    // ============================================================
// REVEAL ATTENDANCE â€” DataTables server-side
// ============================================================
    case 'reveal-attendance':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $states = $_POST['states'] ?? '';

        // Additional Filters from your Frontend
        $branches = $_POST['branchs'] ?? [];
        $sections = $_POST['section'] ?? [];
        $jobtitles = $_POST['jobtitle'] ?? [];
        $grades = $_POST['grade'] ?? [];
        $groups = $_POST['groub'] ?? [];

        $where = "WHERE 1=1";
        $params = [];

        // 1. Date Filter: Only apply if dateRange is provided
        if (!empty($dateRange)) {
            $parts = explode(' - ', $dateRange);
            if (count($parts) == 2) {
                $where .= " AND a.Date BETWEEN :df AND :dt";
                $params[':df'] = trim($parts[0]);
                $params[':dt'] = trim($parts[1]);
            }
        }
        // REMOVED: The "else { today }" block so it returns all records by default

        // 2. Branch Filter (Securely)
        if (!empty($branches) && is_array($branches)) {
            $clean_branches = array_map('intval', $branches);
            $where .= " AND u.BranchID IN (" . implode(',', $clean_branches) . ")";
        }

        // 3. Section Filter
        if (!empty($sections) && is_array($sections)) {
            $clean_sections = array_map('intval', $sections);
            $where .= " AND k.sectionID IN (" . implode(',', $clean_sections) . ")";
        }

        // 4. Job Title Filter
        if (!empty($jobtitles) && is_array($jobtitles)) {
            $clean_jobs = array_map('intval', $jobtitles);
            $where .= " AND k.jobtitleID IN (" . implode(',', $clean_jobs) . ")";
        }

        // --- COUNT TOTAL RECORDS ---
        $cntQ = "SELECT COUNT(DISTINCT a.EmpID, a.Date) FROM attendancet a 
             LEFT JOIN tblusers u ON u.UserID = a.EmpID
             LEFT JOIN tblremewal k ON u.lastversion = k.Id
             $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        // --- GET DATA ---
        $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.Photo,
                   k.shiftID, s.ShiftName, s.ShiftStartTime, s.ShiftEndTime,
                   GROUP_CONCAT(CONCAT(a.Type,'~',a.Time,'~',COALESCE(a.method,'manual')) ORDER BY a.Time ASC SEPARATOR '|') as punches,
                   MIN(CASE WHEN a.Type=1 THEN a.Time END) as first_in,
                   MAX(CASE WHEN a.Type=2 THEN a.Time END) as last_out,
                   a.Date
            FROM attendancet a
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
            $shiftEnd = $r['ShiftEndTime'] ?? '16:00:00';
            $firstIn = $r['first_in'] ?? '-';
            $lastOut = $r['last_out'] ?? '-';

            // Punch display logic
            $punchHtml = '';
            $methodIcons = [
                'gps' => '<i class="fas fa-map-marker-alt text-info ml-1" title="GPS"></i>',
                'qr' => '<i class="fas fa-qrcode text-primary ml-1" title="QR"></i>',
                'manual' => '<i class="fas fa-hand-paper text-warning ml-1" title="يدوي"></i>',
                'import' => '<i class="fas fa-file-import text-secondary ml-1" title="استيراد"></i>'
            ];
            if (!empty($r['punches'])) {
                foreach (explode('|', $r['punches']) as $p) {
                    $parts = explode('~', $p, 3);
                    $pType = $parts[0];
                    $pTime = $parts[1] ?? '';
                    $pMethod = $parts[2] ?? 'manual';
                    $icon = $pType == 1
                        ? '<span class="text-success">&#11044;</span>'
                        : '<span class="text-danger">&#11044;</span>';
                    $mIcon = $methodIcons[$pMethod] ?? '';
                    $punchHtml .= $icon . ' ' . substr($pTime, 0, 5) . '<small title="' . $pMethod . '">' . $mIcon . '</small> ';
                }
            }

            // Status calculations (In/Out)
            $checkinStatus = '-';
            if ($firstIn && $firstIn != '-') {
                if ($firstIn <= $shiftStart) {
                    $checkinStatus = '<span class="badge badge-success">في الوقت</span>';
                } else {
                    $diff = (strtotime($firstIn) - strtotime($shiftStart)) / 60;
                    $checkinStatus = '<span class="badge badge-warning">متأخر ' . round($diff) . ' د</span>';
                }
            }

            $checkoutStatus = '-';
            if ($lastOut && $lastOut != '-') {
                if ($lastOut >= $shiftEnd) {
                    $checkoutStatus = '<span class="badge badge-success">في الوقت</span>';
                } else {
                    $diff = (strtotime($shiftEnd) - strtotime($lastOut)) / 60;
                    $checkoutStatus = '<span class="badge badge-warning">مبكر ' . round($diff) . ' د</span>';
                }
            }

            // Work hours logic
            $scheduledH = round((strtotime($shiftEnd) - strtotime($shiftStart)) / 3600, 1);
            $actualH = ($firstIn && $firstIn != '-' && $lastOut && $lastOut != '-')
                ? round((strtotime($lastOut) - strtotime($firstIn)) / 3600, 1) : 0;

            $delayMin = ($firstIn && $firstIn != '-' && $firstIn > $shiftStart)
                ? round((strtotime($firstIn) - strtotime($shiftStart)) / 60) : 0;

            $earlyMin = ($lastOut && $lastOut != '-' && $lastOut < $shiftEnd)
                ? round((strtotime($shiftEnd) - strtotime($lastOut)) / 60) : 0;

            $formatted[] = [
                'attendance_date' => $r['Date'], // Changed key to match JS columns name
                'name' => $empName,
                'shift_name' => $shiftName,
                'shift_time' => substr($shiftStart, 0, 5) . ' - ' . substr($shiftEnd, 0, 5),
                'attendance_punches' => $punchHtml ?: '-',
                'checkin_status' => $checkinStatus,
                'checkout_status' => $checkoutStatus,
                'scheduled_hours' => $scheduledH . ' س',
                'delay_minutes' => $delayMin ? $delayMin . ' د' : '-',
                'early_departure_minutes' => $earlyMin ? $earlyMin . ' د' : '-',
                'actual_working_hours' => $actualH . ' س',
                'attendance_id' => $r['Date'], // For the view link
                'id' => $r['UserID'],          // For the view link
                'actions' => ''                // Handled by JS render
            ];
        }

        $totalUnfilteredStmt = $connect_pdo->query("SELECT COUNT(DISTINCT EmpID, Date) FROM attendancet");
        $recordsTotal = (int) $totalUnfilteredStmt->fetchColumn();

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $total,
            'data' => $formatted
        ], JSON_UNESCAPED_UNICODE);
        exit;
    // ============================================================
// DASHBOARD-EMP â€” employee financial overview
// ============================================================
    case 'reveal-attendance-view':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = max(1, (int) ($_POST['length'] ?? 10));
        $employeeId = (int) ($_POST['user_id'] ?? 0);
        $dateRange = trim((string) ($_POST['date_range'] ?? ''));

        if ($employeeId <= 0) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'total_credited_time' => '00:00'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $formatMinutes = static function (int $minutes): string {
            $minutes = max(0, $minutes);
            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;
            return sprintf('%02d:%02d', $hours, $mins);
        };

        $buildAttendanceRow = static function (array $row) use ($formatMinutes): array {
            $shiftStart = $row['ShiftStartTime'] ?? '08:00:00';
            $shiftEnd = $row['ShiftEndTime'] ?? '16:00:00';
            $attendanceDate = $row['Date'] ?? date('Y-m-d');
            $firstIn = $row['first_in'] ?? null;
            $lastOut = $row['last_out'] ?? null;

            $shiftStartTs = strtotime($attendanceDate . ' ' . $shiftStart);
            $shiftEndTs = strtotime($attendanceDate . ' ' . $shiftEnd);
            if ($shiftEndTs <= $shiftStartTs) {
                $shiftEndTs += 86400;
            }

            $firstInTs = $firstIn ? strtotime($attendanceDate . ' ' . $firstIn) : false;
            $lastOutTs = $lastOut ? strtotime($attendanceDate . ' ' . $lastOut) : false;

            if ($firstInTs !== false && $firstInTs < ($shiftStartTs - 43200)) {
                $firstInTs += 86400;
            }
            if ($lastOutTs !== false && $firstInTs !== false && $lastOutTs < $firstInTs) {
                $lastOutTs += 86400;
            }

            $methodIcons = [
                'gps' => '<i class="fas fa-map-marker-alt text-info ml-1"></i>',
                'qr' => '<i class="fas fa-qrcode text-primary ml-1"></i>',
                'manual' => '<i class="fas fa-hand-paper text-warning ml-1"></i>',
                'import' => '<i class="fas fa-file-import text-secondary ml-1"></i>'
            ];

            $punchHtml = '-';
            if (!empty($row['punches'])) {
                $partsHtml = [];
                foreach (explode('|', $row['punches']) as $punch) {
                    $parts = explode('~', $punch, 3);
                    $punchType = $parts[0] ?? '';
                    $punchTime = $parts[1] ?? '';
                    $punchMethod = $parts[2] ?? 'manual';
                    $typeBadge = $punchType == 1
                        ? '<span class="text-success"><i class="fas fa-sign-in-alt"></i></span>'
                        : '<span class="text-danger"><i class="fas fa-sign-out-alt"></i></span>';
                    $partsHtml[] = $typeBadge . ' ' . substr($punchTime, 0, 5) . ($methodIcons[$punchMethod] ?? '');
                }
                $punchHtml = implode(' ', $partsHtml);
            }

            $delayMinutes = 0;
            $earlyMinutes = 0;
            $actualMinutes = 0;

            if ($firstInTs !== false && $firstInTs > $shiftStartTs) {
                $delayMinutes = (int) round(($firstInTs - $shiftStartTs) / 60);
            }
            if ($lastOutTs !== false && $lastOutTs < $shiftEndTs) {
                $earlyMinutes = (int) round(($shiftEndTs - $lastOutTs) / 60);
            }
            if ($firstInTs !== false && $lastOutTs !== false && $lastOutTs >= $firstInTs) {
                $actualMinutes = (int) round(($lastOutTs - $firstInTs) / 60);
            }

            $scheduledMinutes = max(0, (int) round(($shiftEndTs - $shiftStartTs) / 60));

            $checkinStatus = '-';
            if ($firstInTs !== false) {
                $checkinStatus = $delayMinutes > 0
                    ? '<span class="badge badge-warning">متأخر ' . $delayMinutes . ' د</span>'
                    : '<span class="badge badge-success">في الوقت</span>';
            }

            $checkoutStatus = '-';
            if ($lastOutTs !== false) {
                $checkoutStatus = $earlyMinutes > 0
                    ? '<span class="badge badge-warning">مبكر ' . $earlyMinutes . ' د</span>'
                    : '<span class="badge badge-success">في الوقت</span>';
            }

            return [
                'row' => [
                    'updated' => $attendanceDate,
                    'ShiftID' => $row['shiftID'] ?? '-',
                    'attendance_punches' => $punchHtml,
                    'checkin_status' => $checkinStatus,
                    'checkout_status' => $checkoutStatus,
                    'scheduled_hours' => $formatMinutes($scheduledMinutes),
                    'delay_minutes' => $delayMinutes ? ($delayMinutes . ' د') : '-',
                    'early_departure_minutes' => $earlyMinutes ? ($earlyMinutes . ' د') : '-',
                    'actual_working_hours' => $formatMinutes($actualMinutes),
                    'credited_hours' => $formatMinutes($actualMinutes)
                ],
                'credited_minutes' => $actualMinutes
            ];
        };

        $where = "WHERE a.EmpID = :uid";
        $params = [':uid' => $employeeId];

        if ($dateRange !== '') {
            $parts = explode(' - ', $dateRange);
            if (count($parts) === 2) {
                $where .= " AND a.Date BETWEEN :df AND :dt";
                $params[':df'] = trim($parts[0]);
                $params[':dt'] = trim($parts[1]);
            }
        }

        try {
            $totalStmt = $connect_pdo->prepare("SELECT COUNT(DISTINCT a.Date) FROM attendancet a WHERE a.EmpID = :uid");
            $totalStmt->execute([':uid' => $employeeId]);
            $recordsTotal = (int) $totalStmt->fetchColumn();

            $filteredStmt = $connect_pdo->prepare("SELECT COUNT(DISTINCT a.Date) FROM attendancet a $where");
            $filteredStmt->execute($params);
            $recordsFiltered = (int) $filteredStmt->fetchColumn();

            $baseSql = "SELECT a.Date,
                           k.shiftID,
                           s.ShiftStartTime,
                           s.ShiftEndTime,
                           GROUP_CONCAT(CONCAT(a.Type,'~',a.Time,'~',COALESCE(a.method,'manual')) ORDER BY a.Time ASC SEPARATOR '|') AS punches,
                           MIN(CASE WHEN a.Type = 1 THEN a.Time END) AS first_in,
                           MAX(CASE WHEN a.Type = 2 THEN a.Time END) AS last_out
                    FROM attendancet a
                    LEFT JOIN tblusers u ON u.UserID = a.EmpID
                    LEFT JOIN tblremewal k ON u.lastversion = k.Id
                    LEFT JOIN tbshift s ON s.ShiftID = k.shiftID
                    $where
                    GROUP BY a.Date, k.shiftID, s.ShiftStartTime, s.ShiftEndTime";

            $totalTimeStmt = $connect_pdo->prepare($baseSql . " ORDER BY a.Date DESC");
            $totalTimeStmt->execute($params);
            $creditedMinutes = 0;
            foreach ($totalTimeStmt->fetchAll(PDO::FETCH_ASSOC) as $summaryRow) {
                $builtRow = $buildAttendanceRow($summaryRow);
                $creditedMinutes += $builtRow['credited_minutes'];
            }

            $pagedStmt = $connect_pdo->prepare($baseSql . " ORDER BY a.Date DESC LIMIT $start, $length");
            $pagedStmt->execute($params);
            $data = [];
            foreach ($pagedStmt->fetchAll(PDO::FETCH_ASSOC) as $summaryRow) {
                $data[] = $buildAttendanceRow($summaryRow)['row'];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
                'total_credited_time' => $formatMinutes($creditedMinutes)
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('reveal-attendance-view failed: ' . $e->getMessage());
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'total_credited_time' => '00:00',
                'error' => 'server-error'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    case 'dashboard-emp':
        try {
            $uid = (int) ($user ?: 0);
            if (!$uid) {
                echo json_encode(['result' => false, 'msg' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $filter = $_POST['filter_by'] ?? 'this_month';
            $dashboardCacheKey = perf_cache_key('dashboard-emp', [
                'uid' => $uid,
                'filter' => $filter,
                'branch' => (int) ($branch ?? 0),
                'role' => method_exists($User, 'getRole') ? $User->getRole() : 'guest',
            ]);
            $cachedDashboard = perf_cache_get($dashboardCacheKey);
            if (is_array($cachedDashboard)) {
                echo json_encode($cachedDashboard, JSON_UNESCAPED_UNICODE);
                exit;
            }
            $period = dashboardParsePeriod($filter);
            $scope = dashboardScopeConfig($User, $uid, $branch ?? null);
            $employeeScope = dashboardBuildEmployeeScopeWhere($scope, 'u', 'r');

            $salaryStmt = $connect_pdo->prepare("
        SELECT
            COALESCE(SUM(CAST(r.Salary AS DECIMAL(12,2))), 0) AS total_salary,
            MAX(NULLIF(r.Currency, '')) AS currency_code
        FROM tblusers u
        LEFT JOIN tblremewal r ON r.Id = u.lastversion
        WHERE {$employeeScope['sql']}
    ");
            $salaryStmt->execute($employeeScope['params']);
            $salaryRow = $salaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $contractSalary = (float) ($salaryRow['total_salary'] ?? 0);
            $salary = 0.0;
            $currencyCode = trim((string) ($salaryRow['currency_code'] ?? ''));
            $currency = $currencyCode !== '' ? $currencyCode : ($User->currency ?? 'ر.س');

            $payrollWhere = ["1=1"];
            $payrollParams = [];

            if (!empty($scope['employee_only']) && !empty($scope['user_id'])) {
                $payrollWhere[] = "es.UserID = :scope_user_id";
                $payrollParams[':scope_user_id'] = (int) $scope['user_id'];
            } elseif (!empty($scope['branch_ids'])) {
                $branchPlaceholders = [];
                foreach ($scope['branch_ids'] as $idx => $branchId) {
                    $placeholder = ':scope_branch_' . $idx;
                    $branchPlaceholders[] = $placeholder;
                    $payrollParams[$placeholder] = (int) $branchId;
                }
                $payrollWhere[] = "r.BranchID IN (" . implode(',', $branchPlaceholders) . ")";
            }

            if (!empty($period['year'])) {
                $payrollWhere[] = "sr.year = :payroll_year";
                $payrollParams[':payroll_year'] = (string) $period['year'];
            }

            if (!$period['year_only'] && !empty($period['month'])) {
                $payrollWhere[] = "es.month = :payroll_month";
                $payrollParams[':payroll_month'] = (int) $period['month'];
            }

            $payrollStmt = $connect_pdo->prepare("
        SELECT
            COUNT(*) AS rows_count,
            COALESCE(SUM(CAST(es.incentive AS DECIMAL(12,2))), 0) AS incentive_total,
            COALESCE(SUM(CAST(es.benefit AS DECIMAL(12,2))), 0) AS benefit_total,
            COALESCE(SUM(CAST(es.deductions AS DECIMAL(12,2))), 0) AS deduction_total,
            COALESCE(SUM(CAST(es.advances AS DECIMAL(12,2))), 0) AS advance_total,
            COALESCE(SUM(CAST(es.net_salary AS DECIMAL(12,2))), 0) AS net_total,
            COALESCE(SUM(CAST(es.end_salary AS DECIMAL(12,2))), 0) AS end_total
        FROM emp_salary es
        LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
        LEFT JOIN tblusers u ON u.UserID = es.UserID
        LEFT JOIN tblremewal r ON r.Id = u.lastversion
        WHERE " . implode(' AND ', $payrollWhere)
            );
            $payrollStmt->execute($payrollParams);
            $payrollRow = $payrollStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $payrollRowsCount = (int) ($payrollRow['rows_count'] ?? 0);
            $periodNetSalary = (float) ($payrollRow['net_total'] ?? 0);
            $benefits = (float) ($payrollRow['benefit_total'] ?? 0);
            $deductions = (float) ($payrollRow['deduction_total'] ?? 0);
            $incentives = (float) ($payrollRow['incentive_total'] ?? 0);
            $advanceOnSalary = (float) ($payrollRow['advance_total'] ?? 0);
            $netSalary = (float) ($payrollRow['end_total'] ?? 0);

            if ($netSalary <= 0) {
                $netSalary = $periodNetSalary;
            }

            if ($payrollRowsCount > 0) {
                // Use the payroll-period stored values directly to avoid reverse-derived
                // math drift on the dashboard cards.
                $salary = max(0, $periodNetSalary);
            }

            if ($payrollRowsCount === 0) {
                $benefitScope = dashboardBuildTransactionScopeWhere($scope, 'UserID', 'BranchID');
                $benefitStmt = $connect_pdo->prepare("
            SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))), 0)
            FROM tblbenefit
            WHERE {$benefitScope['sql']}
              AND COALESCE(Status, 0) = 1
              AND DueDate BETWEEN :benefit_from AND :benefit_to
        ");
                $benefitStmt->execute($benefitScope['params'] + [
                    ':benefit_from' => $period['from'],
                    ':benefit_to' => $period['to'],
                ]);
                $benefits = (float) $benefitStmt->fetchColumn();

                $deductionScope = dashboardBuildTransactionScopeWhere($scope, 'UserID', 'BranchID');
                $deductionStmt = $connect_pdo->prepare("
            SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))), 0)
            FROM tbldeductions
            WHERE {$deductionScope['sql']}
              AND COALESCE(Status, 0) = 1
              AND DueDate BETWEEN :deduction_from AND :deduction_to
        ");
                $deductionStmt->execute($deductionScope['params'] + [
                    ':deduction_from' => $period['from'],
                    ':deduction_to' => $period['to'],
                ]);
                $deductions = (float) $deductionStmt->fetchColumn();

                $incentiveScope = dashboardBuildTransactionScopeWhere($scope, 'UserID', 'BranchID');
                $incentiveStmt = $connect_pdo->prepare("
            SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))), 0)
            FROM tblincentives
            WHERE {$incentiveScope['sql']}
              AND COALESCE(Status, 0) = 1
              AND DueDate BETWEEN :incentive_from AND :incentive_to
        ");
                $incentiveStmt->execute($incentiveScope['params'] + [
                    ':incentive_from' => $period['from'],
                    ':incentive_to' => $period['to'],
                ]);
                $incentives = (float) $incentiveStmt->fetchColumn();

                // Keep cards aligned with the selected period only. If there is no salary
                // registration in the requested period, we should not fall back to the
                // current contract salary because that makes the filter appear broken.
                $salary = 0.0;
            }

            $advanceScope = dashboardBuildTransactionScopeWhere($scope, 'UserID', 'BranchID');
            $advanceStmt = $connect_pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN COALESCE(type, 1) = 1 THEN CAST(Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS on_salary_total,
            COALESCE(SUM(CASE WHEN COALESCE(type, 1) <> 1 THEN CAST(Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS outside_salary_total
        FROM tblempadvances
        WHERE {$advanceScope['sql']}
          AND COALESCE(Status, 0) = 1
          AND DueDate BETWEEN :advance_from AND :advance_to
    ");
            $advanceStmt->execute($advanceScope['params'] + [
                ':advance_from' => $period['from'],
                ':advance_to' => $period['to'],
            ]);
            $advanceRow = $advanceStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $advanceOnSalary = (float) ($advanceRow['on_salary_total'] ?? $advanceOnSalary);
            $advanceOutsideSalary = (float) ($advanceRow['outside_salary_total'] ?? 0);
            $remainSalary = dashboardResolveRemainSalary($connect_pdo, $scope, $period['from'], $period['to']);
            $workMinutes = dashboardSumWorkingMinutes($connect_pdo, $scope, $period['from'], $period['to']);

            if ($payrollRowsCount === 0 && $contractSalary > 0 && $period['filter'] === 'this_month') {
                $salary = $contractSalary;
            }

            if ($netSalary <= 0) {
                $netSalary = max(0, $salary + $benefits + $incentives - $deductions - $advanceOnSalary - $remainSalary);
            }

            $dashboardPayload = [
                'result' => true,
                'salary' => number_format($salary, 2),
                'remain_salary' => number_format($remainSalary, 2),
                'incentive' => number_format($incentives, 2),
                'benefit' => number_format($benefits, 2),
                'dections' => number_format($deductions, 2),
                'total_hour' => dashboardFormatMinutes($workMinutes),
                'end_salary' => number_format($netSalary, 2),
                'advance' => number_format($advanceOnSalary, 2),
                'advance_outside_salary' => number_format($advanceOutsideSalary, 2),
                'currency' => $currency,
            ];
            perf_cache_set($dashboardCacheKey, $dashboardPayload, 45);
            echo json_encode($dashboardPayload, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'result' => false,
                'error' => $e->getMessage(),
                'salary' => '0.00',
                'remain_salary' => '0.00',
                'incentive' => '0.00',
                'benefit' => '0.00',
                'dections' => '0.00',
                'total_hour' => '00:00',
                'end_salary' => '0.00',
                'advance' => '0.00',
                'advance_outside_salary' => '0.00',
                'currency' => 'ر.س',
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'dashboard-emp-legacy':
        try {
            $filter = $_POST['filter_by'] ?? 'this_month';
            $uid = $user ?: 0;
            if (!$uid) {
                echo json_encode(['result' => false, 'msg' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Get salary from latest contract
            $salQ = $connect_pdo->prepare("SELECT k.Salary, k.Currency FROM tblremewal k JOIN tblusers u ON u.lastversion=k.Id WHERE u.UserID=:uid LIMIT 1");
            $salQ->execute([':uid' => $uid]);
            $sal = $salQ->fetch();
            $salary = $sal ? (float) $sal['Salary'] : 0;
            $currency = $sal ? ($sal['Currency'] ?? 'ر.س') : 'ر.س';

            // Parse filter to year/month range
            $filterYr = null;
            $filterMn = null;
            $filterYrOnly = false;
            if ($filter == 'this_month') {
                $filterYr = (int) date('Y');
                $filterMn = (int) date('n');
                $from = date('Y-m-01');
                $to = date('Y-m-t');
            } elseif ($filter == 'last_month') {
                $filterYr = (int) date('Y', strtotime('-1 month'));
                $filterMn = (int) date('n', strtotime('-1 month'));
                $from = date('Y-m-01', strtotime('-1 month'));
                $to = date('Y-m-t', strtotime('-1 month'));
            } elseif ($filter == 'this_year') {
                $filterYr = (int) date('Y');
                $filterYrOnly = true;
                $from = date('Y-01-01');
                $to = date('Y-12-31');
            } elseif (strpos($filter, 'month_') === 0) {
                $parts = explode('_', $filter);
                $filterYr = (int) ($parts[1] ?? date('Y'));
                $filterMn = (int) ($parts[2] ?? date('n'));
                $from = sprintf('%04d-%02d-01', $filterYr, $filterMn);
                $to = date('Y-m-t', strtotime($from));
            } else {
                $filterYr = (int) date('Y');
                $filterYrOnly = true;
                $from = date('Y-01-01');
                $to = date('Y-12-31');
            }

            // Query emp_salary (joins salary_registration for year; emp_salary.month is month number)
            if ($filterYrOnly) {
                $esQ = $connect_pdo->prepare("
            SELECT SUM(es.incentive) as inc, SUM(es.benefit) as ben,
                   SUM(es.deductions) as ded, SUM(es.advances) as adv,
                   SUM(es.net_salary) as net, SUM(es.end_salary) as end_sal
            FROM emp_salary es
            LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
            WHERE es.UserID=:uid AND sr.year=:yr");
                $esQ->execute([':uid' => $uid, ':yr' => $filterYr]);
            } elseif ($filterMn) {
                $esQ = $connect_pdo->prepare("
            SELECT SUM(es.incentive) as inc, SUM(es.benefit) as ben,
                   SUM(es.deductions) as ded, SUM(es.advances) as adv,
                   SUM(es.net_salary) as net, SUM(es.end_salary) as end_sal
            FROM emp_salary es
            LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
            WHERE es.UserID=:uid AND sr.year=:yr AND es.month=:mn");
                $esQ->execute([':uid' => $uid, ':yr' => $filterYr, ':mn' => $filterMn]);
            } else {
                $esQ = $connect_pdo->prepare("
            SELECT SUM(es.incentive) as inc, SUM(es.benefit) as ben,
                   SUM(es.deductions) as ded, SUM(es.advances) as adv,
                   SUM(es.net_salary) as net, SUM(es.end_salary) as end_sal
            FROM emp_salary es WHERE es.UserID=:uid");
                $esQ->execute([':uid' => $uid]);
            }
            $esData = $esQ->fetch(PDO::FETCH_ASSOC);

            $benefits = (float) ($esData['ben'] ?? 0);
            $deductions = (float) ($esData['ded'] ?? 0);
            $incentives = (float) ($esData['inc'] ?? 0);
            $advOnSalary = (float) ($esData['adv'] ?? 0);
            $netFromTable = (float) ($esData['net'] ?? $esData['end_sal'] ?? 0);

            // Fallback to individual tables if emp_salary empty
            if ($benefits == 0 && $deductions == 0 && $incentives == 0 && $advOnSalary == 0) {
                $bQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblbenefit WHERE UserID=:uid AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
                $bQ->execute([':uid' => $uid, ':f' => $from, ':t' => $to]);
                $benefits = (float) $bQ->fetchColumn();

                $dQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tbldeductions WHERE UserID=:uid AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
                $dQ->execute([':uid' => $uid, ':f' => $from, ':t' => $to]);
                $deductions = (float) $dQ->fetchColumn();

                $iQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblincentives WHERE UserID=:uid AND Status IS NOT NULL AND DueDate BETWEEN :f AND :t");
                $iQ->execute([':uid' => $uid, ':f' => $from, ':t' => $to]);
                $incentives = (float) $iQ->fetchColumn();

                $aQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE UserID=:uid AND status=1 AND DueDate BETWEEN :f AND :t");
                $aQ->execute([':uid' => $uid, ':f' => $from, ':t' => $to]);
                $advOnSalary = (float) $aQ->fetchColumn();
            }

            // Working hours
            $hQ = $connect_pdo->prepare("SELECT COUNT(DISTINCT Date) FROM attendancet WHERE EmpID=:uid AND Date BETWEEN :f AND :t AND Type=1");
            $hQ->execute([':uid' => $uid, ':f' => $from, ':t' => $to]);
            $workDays = (int) $hQ->fetchColumn();
            $workHours = $workDays * 8;

            $netSalary = $netFromTable > 0 ? $netFromTable : max(0, $salary + $benefits + $incentives - $deductions - $advOnSalary);

            echo json_encode([
                'result' => true,
                'salary' => number_format($salary, 2),
                'remain_salary' => '0.00',
                'incentive' => number_format($incentives, 2),
                'benefit' => number_format($benefits, 2),
                'dections' => number_format($deductions, 2),
                'total_hour' => $workHours,
                'end_salary' => number_format($netSalary, 2),
                'advance' => number_format($advOnSalary, 2),
                'currency' => $currency,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['result' => false, 'error' => $e->getMessage(), 'salary' => '0.00', 'incentive' => '0.00', 'benefit' => '0.00', 'dections' => '0.00', 'total_hour' => 0, 'end_salary' => '0.00', 'advance' => '0.00', 'currency' => 'ر.س'], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// EMP-CHART â€” advances chart data
// ============================================================
    case 'emp-chart':
        try {
            $uid = (int) ($user ?: 0);
            if (!$uid) {
                echo json_encode(['result' => false, 'msg' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $filter = $_POST['filter_by'] ?? 'this_month';
            $chartCacheKey = perf_cache_key('dashboard-emp-chart', [
                'uid' => $uid,
                'filter' => $filter,
                'branch' => (int) ($branch ?? 0),
                'role' => method_exists($User, 'getRole') ? $User->getRole() : 'guest',
            ]);
            $cachedChart = perf_cache_get($chartCacheKey);
            if (is_array($cachedChart)) {
                echo json_encode($cachedChart, JSON_UNESCAPED_UNICODE);
                exit;
            }
            $period = dashboardParsePeriod($filter);
            $scope = dashboardScopeConfig($User, $uid, $branch ?? null);
            $labels = [];
            $onSalaryValues = [];
            $outsideSalaryValues = [];
            $approvedCount = 0;
            $scopeWhere = dashboardBuildTransactionScopeWhere($scope, 'a.UserID', 'a.BranchID');

            if ($filter === 'this_day') {
                $startDate = date('Y-m-d');
                $endDate = $startDate;
                $labels[] = date('Y-m-d');

                $stmt = $connect_pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) = 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS on_salary_total,
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) <> 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS outside_salary_total,
                COUNT(*) AS approved_count
            FROM tblempadvances a
            WHERE {$scopeWhere['sql']}
              AND COALESCE(a.Status, 0) = 1
              AND DATE(a.CreatedDate) BETWEEN :chart_from AND :chart_to
        ");
                $stmt->execute($scopeWhere['params'] + [
                    ':chart_from' => $startDate,
                    ':chart_to' => $endDate,
                ]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $onSalaryValues[] = (float) ($row['on_salary_total'] ?? 0);
                $outsideSalaryValues[] = (float) ($row['outside_salary_total'] ?? 0);
                $approvedCount = (int) ($row['approved_count'] ?? 0);
            } elseif (!empty($period['year_only'])) {
                $startDate = $period['from'];
                $endDate = $period['to'];
                $arabicMonths = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

                for ($month = 1; $month <= 12; $month++) {
                    $labels[] = $arabicMonths[$month];
                    $onSalaryValues[] = 0.0;
                    $outsideSalaryValues[] = 0.0;
                }

                $stmt = $connect_pdo->prepare("
            SELECT
                MONTH(a.CreatedDate) AS bucket_no,
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) = 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS on_salary_total,
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) <> 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS outside_salary_total,
                COUNT(*) AS approved_count
            FROM tblempadvances a
            WHERE {$scopeWhere['sql']}
              AND DATE(a.CreatedDate) BETWEEN :chart_from AND :chart_to
              AND COALESCE(a.Status, 0) = 1
            GROUP BY MONTH(a.CreatedDate)
            ORDER BY MONTH(a.CreatedDate)
        ");
                $stmt->execute($scopeWhere['params'] + [
                    ':chart_from' => $startDate,
                    ':chart_to' => $endDate,
                ]);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $monthIndex = max(0, ((int) $row['bucket_no']) - 1);
                    if (isset($onSalaryValues[$monthIndex])) {
                        $onSalaryValues[$monthIndex] = (float) ($row['on_salary_total'] ?? 0);
                        $outsideSalaryValues[$monthIndex] = (float) ($row['outside_salary_total'] ?? 0);
                        $approvedCount += (int) ($row['approved_count'] ?? 0);
                    }
                }
            } else {
                $startDate = $period['from'];
                $endDate = $period['to'];
                $daysInMonth = (int) date('t', strtotime($startDate));

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $labels[] = (string) $day;
                    $onSalaryValues[] = 0.0;
                    $outsideSalaryValues[] = 0.0;
                }

                $stmt = $connect_pdo->prepare("
            SELECT
                DAY(a.CreatedDate) AS bucket_no,
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) = 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS on_salary_total,
                COALESCE(SUM(CASE WHEN COALESCE(a.type, 1) <> 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS outside_salary_total,
                COUNT(*) AS approved_count
            FROM tblempadvances a
            WHERE {$scopeWhere['sql']}
              AND DATE(a.CreatedDate) BETWEEN :chart_from AND :chart_to
              AND COALESCE(a.Status, 0) = 1
            GROUP BY DAY(a.CreatedDate)
            ORDER BY DAY(a.CreatedDate)
        ");
                $stmt->execute($scopeWhere['params'] + [
                    ':chart_from' => $startDate,
                    ':chart_to' => $endDate,
                ]);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $dayIndex = max(0, ((int) $row['bucket_no']) - 1);
                    if (isset($onSalaryValues[$dayIndex])) {
                        $onSalaryValues[$dayIndex] = (float) ($row['on_salary_total'] ?? 0);
                        $outsideSalaryValues[$dayIndex] = (float) ($row['outside_salary_total'] ?? 0);
                        $approvedCount += (int) ($row['approved_count'] ?? 0);
                    }
                }
            }

            $summaryScope = dashboardBuildTransactionScopeWhere($scope, 'a.UserID', 'a.BranchID');
            $summaryStmt = $connect_pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN COALESCE(a.Status, 0) = 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS approved_total,
            COALESCE(SUM(CASE WHEN COALESCE(a.Status, 0) = 1 AND COALESCE(a.type, 1) = 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS approved_on_salary_total,
            COALESCE(SUM(CASE WHEN COALESCE(a.Status, 0) = 1 AND COALESCE(a.type, 1) <> 1 THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS approved_outside_salary_total,
            COALESCE(SUM(CASE WHEN COALESCE(a.Status, 0) = 0 OR a.Status IS NULL THEN CAST(a.Amount AS DECIMAL(12,2)) ELSE 0 END), 0) AS pending_total
        FROM tblempadvances a
        WHERE {$summaryScope['sql']}
          AND DATE(a.CreatedDate) BETWEEN :summary_from AND :summary_to
    ");
            $summaryStmt->execute($summaryScope['params'] + [
                ':summary_from' => $startDate,
                ':summary_to' => $endDate,
            ]);
            $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $chartPayload = [
                'result' => true,
                'xy' => $labels,
                'yv' => $onSalaryValues,
                'yvp' => $outsideSalaryValues,
                'style' => ['#16a34a', '#f59e0b'],
                'cots' => number_format($approvedCount, 0),
                'totals' => number_format((float) ($summaryRow['approved_on_salary_total'] ?? 0), 2),
                'advance_1' => number_format((float) ($summaryRow['approved_total'] ?? 0), 2),
                'advance_2' => number_format((float) ($summaryRow['approved_outside_salary_total'] ?? 0), 2),
                'label_prefix' => $filter === 'this_day' ? 'اليوم' : ($period['year_only'] ? 'شهرياً' : 'يومياً'),
            ];
            perf_cache_set($chartCacheKey, $chartPayload, 60);
            echo json_encode($chartPayload, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'result' => false,
                'error' => $e->getMessage(),
                'xy' => [],
                'yv' => [],
                'yvp' => [],
                'style' => [],
                'cots' => '0',
                'totals' => '0.00',
                'advance_1' => '0.00',
                'advance_2' => '0.00',
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'emp-chart-legacy':
        try {
            $uid = $user ?: 0;
            // Get last 6 months of salary data
            $months = [];
            $labels = [];
            $netValues = [];
            $salaryValues = [];
            $colors = [];

            $salQ2 = $connect_pdo->prepare("SELECT k.Salary FROM tblremewal k JOIN tblusers u ON u.lastversion=k.Id WHERE u.UserID=:uid LIMIT 1");
            $salQ2->execute([':uid' => $uid]);
            $baseSalary = (float) ($salQ2->fetchColumn() ?: 0);

            $arabicMonths = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

            for ($i = 5; $i >= 0; $i--) {
                $ts = strtotime("-$i months");
                $yr = (int) date('Y', $ts);
                $mn = (int) date('n', $ts);
                $labels[] = $arabicMonths[$mn] . ' ' . $yr;

                $esQ2 = $connect_pdo->prepare("
            SELECT SUM(es.net_salary) as net, SUM(es.end_salary) as end_sal
            FROM emp_salary es
            LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
            WHERE es.UserID=:uid AND sr.year=:yr AND es.month=:mn");
                $esQ2->execute([':uid' => $uid, ':yr' => $yr, ':mn' => $mn]);
                $row2 = $esQ2->fetch(PDO::FETCH_ASSOC);
                $net = (float) ($row2['net'] ?? $row2['end_sal'] ?? 0);
                $netValues[] = $net > 0 ? $net : 0;
                $salaryValues[] = $baseSalary;
                $colors[] = $net > 0 ? '#0d21a5' : '#e2e8f0';
            }

            // Advances summary
            $advQ = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE UserID=:uid AND status=1");
            $advQ->execute([':uid' => $uid]);
            $advPaid = (float) $advQ->fetchColumn();

            $advQ2 = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE UserID=:uid AND status IS NULL");
            $advQ2->execute([':uid' => $uid]);
            $advPending = (float) $advQ2->fetchColumn();

            echo json_encode([
                'result' => true,
                'xy' => $labels,
                'yv' => $netValues,
                'yvp' => $salaryValues,
                'style' => $colors,
                'cots' => number_format($baseSalary, 2),
                'totals' => number_format(array_sum($netValues), 2),
                'advance_1' => number_format($advPending, 2),
                'advance_2' => number_format($advPaid, 2),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['result' => true, 'xy' => [], 'yv' => [], 'yvp' => [], 'style' => [], 'cots' => '0.00', 'totals' => '0.00', 'advance_1' => '0.00', 'advance_2' => '0.00'], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// BENEFITS LIST â€” DataTables
// ============================================================
    case 'Benefits-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND b.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND b.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($stateFilter === '1') {
                $where .= " AND b.Status = 1";
            } elseif ($stateFilter === '2') {
                $where .= " AND (b.Status IS NULL OR b.Status != 1)";
            }

            $countSql = "SELECT COUNT(*) FROM tblbenefit b WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT b.*, br.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tblbenefit b
                LEFT JOIN branches br ON b.BranchID = br.branch_id
                LEFT JOIN tblusers u ON b.CreatedBy = u.UserID
                WHERE $where
                ORDER BY b.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['name'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'type' => $row['beneft_type'] ?? '',
                    'amount' => $row['Amount'] ?? '',
                    'status' => $row['Status'] ?? '',
                    'createddate' => $row['CreatedDate'] ?? '',
                    'createdby' => $row['CreatorName'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// JOB GRADE LIST â€” DataTables
// ============================================================
    case 'jobgrade-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND jg.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND jg.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tbljobgrade jg WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT jg.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tbljobgrade jg
                LEFT JOIN branches b ON jg.BranchID = b.branch_id
                LEFT JOIN tblusers u ON jg.CreatedBy = u.UserID
                WHERE $where
                ORDER BY jg.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'data' => [
                        'id' => $row['Id'],
                        'name' => $row['Name'] ?? '',
                        'branch' => $row['branch_name'] ?? '',
                        'user' => $row['CreatorName'] ?? '',
                        'updated' => $row['LastUpdateDate'] ?? $row['CreatedDate'] ?? ''
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// EMPLOYMENT TYPE LIST â€” DataTables
// ============================================================
    case 'empolyment-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND et.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND et.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            // Employment type table might not have a Status column, checking schema or adding if needed
            // Assuming it follows the pattern of other list tables

            $countSql = "SELECT COUNT(*) FROM tblemploymenttype et WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT et.Id, et.Name, et.BranchID, et.CreatedDate, b.branch_name
                FROM tblemploymenttype et
                LEFT JOIN branches b ON et.BranchID = b.branch_id
                WHERE $where
                ORDER BY et.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'data' => [
                        'id' => $row['Id'],
                        'name' => $row['Name'] ?? '',
                        'branch' => $row['branch_name'] ?? '',
                        'user' => 'System',
                        'updated' => $row['CreatedDate'] ?? ''
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// DEDUCTIONS LIST â€” DataTables
// ============================================================
    case 'deductions-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND d.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND d.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($stateFilter === '1') {
                $where .= " AND d.Status = 1";
            } elseif ($stateFilter === '2') {
                $where .= " AND (d.Status IS NULL OR d.Status != 1)";
            }

            $countSql = "SELECT COUNT(*) FROM tbldeductions d WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT d.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tbldeductions d
                LEFT JOIN branches b ON d.BranchID = b.branch_id
                LEFT JOIN tblusers u ON d.CreatedBy = u.UserID
                WHERE $where
                ORDER BY d.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['name'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'amount' => $row['Amount'] ?? '',
                    'status' => $row['Status'] ?? '',
                    'createddate' => $row['CreatedDate'] ?? '',
                    'createdby' => $row['CreatorName'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// INCENTIVE LIST â€” DataTables
// ============================================================
    case 'incentive-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND i.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND i.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($stateFilter === '1') {
                $where .= " AND i.Status = 1";
            } elseif ($stateFilter === '2') {
                $where .= " AND (i.Status IS NULL OR i.Status != 1)";
            }

            $countSql = "SELECT COUNT(*) FROM tblincentives i WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT i.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tblincentives i
                LEFT JOIN branches b ON i.BranchID = b.branch_id
                LEFT JOIN tblusers u ON i.CreatedBy = u.UserID
                WHERE $where
                ORDER BY i.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['name'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'amount' => $row['Amount'] ?? '',
                    'status' => $row['Status'] ?? '',
                    'createddate' => $row['CreatedDate'] ?? '',
                    'createdby' => $row['CreatorName'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// EMPLOYEE ADVANCES LIST â€” DataTables
// ============================================================
    case 'EmpAdvances-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND a.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND a.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($stateFilter === '1') {
                $where .= " AND a.status = 1";
            } elseif ($stateFilter === '2') {
                $where .= " AND (a.status IS NULL OR a.status != 1)";
            }

            $countSql = "SELECT COUNT(*) FROM tblempadvances a WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT a.*, b.branch_name, 
                CONCAT(u.FirstName, ' ', u.LastName) as EmployeeName,
                CONCAT(c.FirstName, ' ', c.LastName) as CreatorName
                FROM tblempadvances a
                LEFT JOIN branches b ON a.BranchID = b.branch_id
                LEFT JOIN tblusers u ON a.UserID = u.UserID
                LEFT JOIN tblusers c ON a.created_by = c.UserID
                WHERE $where
                ORDER BY a.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['EmployeeName'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'amount' => $row['Amount'] ?? '',
                    'status' => $row['status'] ?? null,
                    'draft' => $row['Draft'] ?? 0,
                    'createddate' => $row['CreatedDate'] ?? '',
                    'createdby' => $row['CreatorName'] ?? $row['EmployeeName'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// HOLIDAYS LIST â€” DataTables
// ============================================================
    case 'holidays-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND h.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND h.Start_date BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM holidays h WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT h.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM holidays h
                LEFT JOIN branches b ON h.BranchID = b.branch_id
                LEFT JOIN tblusers u ON h.CreatedBy = u.UserID
                WHERE $where
                ORDER BY h.Id DESC";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'data' => [
                        'id' => $row['Id'],
                        'name' => $row['Name'] ?? '',
                        'branch' => $row['branch_name'] ?? '',
                        'user' => $row['CreatorName'] ?? '',
                        'updated' => $row['LastUpdateDate'] ?? $row['CreatedDate'] ?? '',
                        'start_d' => $row['Start_date'] ?? '',
                        'end_d' => $row['End_date'] ?? ''
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// BRANCHES LIST â€” DataTables
// ============================================================
    case 'branches-list':
        $draw = (int) ($_POST['draw'] ?? 1);

        try {
            // FIXED JOIN: b.created_user links to u.UserID
            $sql = "SELECT b.*, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM branches b
                LEFT JOIN tblusers u ON b.created_user = u.UserID
                ORDER BY b.branch_id DESC";

            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = count($rows);

            $data = [];
            $no = 1;

            foreach ($rows as $row) {
                $data[] = [
                    'data' => [
                        'no' => $no++,
                        'id' => $row['branch_id'],
                        'name' => $row['branch_name'] ?? '',

                        // Mapped exactly to your table column names
                        'stopped' => $row['isstopped'] ?? 0,
                        'defaults' => $row['isdefault'] ?? 0,
                        'created' => $row['created_date'] ?? '',

                        // Added this just in case you want to show the creator name
                        'creator' => $row['CreatorName'] ?? ''
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// DISMISSAL LIST â€” DataTables (Corrected for Server-Side Processing)
// ============================================================
    case 'dismissal-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $statusFilter = $_POST['states'] ?? ''; // This is from the status filter dropdown

        // Get DataTables specific parameters for ordering and limits
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $orderColumnIndex = (int) ($_POST['order'][0]['column'] ?? 0);
        $orderDir = $_POST['order'][0]['dir'] ?? 'desc';

        // Map frontend column index to actual database column names for ordering
        $orderableColumns = [
            'u.FirstName',   // 0: Ø§Ø³Ù… Ø§Ù„Ù…ÙˆØ¸Ù
            'r.LastUpdateDate', // 1: Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ« (using LastUpdateDate as a proxy)
            'r.Status',      // 2: Ø­Ø§Ù„Ø© Ø§Ù„ÙØµÙ„
            'r.Draft',       // 3: Ù…Ø³ÙˆØ¯Ø© Ø§Ùˆ Ù„Ø§
            'cb.FirstName'   // 4: Ø§Ù†Ø´Ø¦ Ø¨ÙˆØ§Ø³Ø·Ø©
        ];
        $orderColumnName = $orderableColumns[$orderColumnIndex] ?? 'r.Id';

        if ($orderColumnName === 'u.FirstName') {
            $orderColumnName = 'CONCAT(u.FirstName, " ", u.LastName)';
        }
        if ($orderColumnName === 'cb.FirstName') {
            $orderColumnName = 'CONCAT(cb.FirstName, " ", cb.LastName)';
        }

        try {
            // *** CRUCIAL CHANGE: Filtering by type = 2 for dismissals ***
            $where = "r.type = 2";
            $params = [];

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND r.DueDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($statusFilter !== '') {
                $where .= " AND r.Status = ?";
                $params[] = $statusFilter;
            }

            // Total records without any filtering (for type 2)
            $totalRecordsSql = "SELECT COUNT(*) FROM tblresignation WHERE type = 2";
            $totalRecordsStmt = $connect_pdo->prepare($totalRecordsSql);
            $totalRecordsStmt->execute();
            $totalRecords = $totalRecordsStmt->fetchColumn();

            // Total records with filters applied (for recordsFiltered)
            $filteredRecordsSql = "SELECT COUNT(*) FROM tblresignation r WHERE $where";
            $filteredRecordsStmt = $connect_pdo->prepare($filteredRecordsSql);
            $filteredRecordsStmt->execute($params);
            $filteredRecords = $filteredRecordsStmt->fetchColumn();

            // The main query to fetch data for the current page
            $sql = "SELECT r.Id, r.DueDate, r.Reason, r.Status, r.Draft, r.LastUpdateDate,
                       CONCAT(u.FirstName, ' ', u.LastName) as employee,
                       CONCAT(cb.FirstName, ' ', cb.LastName) as created_by_name
                FROM tblresignation r
                LEFT JOIN tblusers u ON r.UserID = u.UserID
                LEFT JOIN tblusers cb ON r.created_by = cb.UserID
                WHERE $where";

            $sql .= " ORDER BY {$orderColumnName} {$orderDir}";
            $sql .= " LIMIT ?, ?";
            $limitParams = array_merge($params, [$start, $length]);

            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($limitParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['employee'] ?? '-', // Corresponds to aoColumns data.name
                    'updated' => $row['LastUpdateDate'] ?? '-', // Corresponds to aoColumns data.updated
                    'statedevice' => $row['Status'] ?? '0', // Corresponds to aoColumns data.statedevice
                    'draft' => $row['Draft'] ?? '0', // Corresponds to aoColumns data.draft
                    'draft___' => $row['Draft'] ?? '0', // For the action button logic
                    'status' => $row['Status'] ?? '0', // For the action button logic
                    'name_add' => $row['created_by_name'] ?? '-' // Corresponds to aoColumns data.name_add
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            error_log("Dismissal List DataTables Error: " . $e->getMessage());
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Database error: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// RESIGNATION LIST â€” DataTables
// ============================================================
    case 'resignation-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $statusFilter = $_POST['states'] ?? '';

        // Get DataTables specific parameters for ordering and limits
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $orderColumnIndex = (int) ($_POST['order'][0]['column'] ?? 0);
        $orderDir = $_POST['order'][0]['dir'] ?? 'desc';
        $columns = $_POST['columns'] ?? [];

        // Map frontend column index to actual database column names for ordering
        // IMPORTANT: Use actual table column names here, not aliases!
        $orderableColumns = [
            'u.FirstName',   // 0: Ø§Ø³Ù… Ø§Ù„Ù…ÙˆØ¸Ù (corresponds to employee alias)
            'r.DueDate',     // 1: ØªØ§Ø±ÙŠØ® Ø§Ù„Ø§Ø³ØªÙ‚Ø§Ù„Ø© (corresponds to due_date alias)
            'r.Reason',      // 2: Ø§Ù„Ø³Ø¨Ø¨ (corresponds to reason alias)
            'r.Status',      // 3: Ø§Ù„Ø­Ø§Ù„Ø© (corresponds to status alias)
            'cb.FirstName'   // 4: Ø£Ù†Ø´Ø¦ Ø¨ÙˆØ§Ø³Ø·Ø© (corresponds to created_by_name alias)
            // 5: Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª (not orderable)
        ];
        // Ensure the index is within bounds, otherwise default to a safe column
        $orderColumnName = $orderableColumns[$orderColumnIndex] ?? 'r.Id';

        // If ordering by employee name, consider using both First and Last names for better sorting
        if ($orderColumnName === 'u.FirstName') {
            $orderColumnName = 'CONCAT(u.FirstName, " ", u.LastName)'; // Order by full name
        }
        // If ordering by created_by name, consider using both First and Last names
        if ($orderColumnName === 'cb.FirstName') {
            $orderColumnName = 'CONCAT(cb.FirstName, " ", cb.LastName)'; // Order by full name
        }


        try {
            $where = "r.type = 1"; // Assuming type 1 is for resignations
            $params = [];

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND r.DueDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            // Add Status Filter
            if ($statusFilter !== '') { // Check if a specific status is selected
                $where .= " AND r.Status = ?";
                $params[] = $statusFilter;
            }

            // Total records without filtering
            $totalRecordsSql = "SELECT COUNT(*) FROM tblresignation r WHERE r.type = 1";
            $totalRecordsStmt = $connect_pdo->prepare($totalRecordsSql);
            $totalRecordsStmt->execute();
            $totalRecords = $totalRecordsStmt->fetchColumn();

            // Total records with filters (for recordsFiltered)
            $filteredRecordsSql = "SELECT COUNT(*) FROM tblresignation r 
                                LEFT JOIN tblusers u ON r.UserID = u.UserID
                                LEFT JOIN tblusers cb ON r.created_by = cb.UserID
                                WHERE $where";
            $filteredRecordsStmt = $connect_pdo->prepare($filteredRecordsSql);
            $filteredRecordsStmt->execute($params);
            $filteredRecords = $filteredRecordsStmt->fetchColumn();


            $sql = "SELECT r.Id, r.DueDate, r.Reason, r.Status, r.Draft, r.type, r.created_by,
                       CONCAT(u.FirstName, ' ', u.LastName) as employee,
                       CONCAT(cb.FirstName, ' ', cb.LastName) as created_by_name
                FROM tblresignation r
                LEFT JOIN tblusers u ON r.UserID = u.UserID
                LEFT JOIN tblusers cb ON r.created_by = cb.UserID
                WHERE $where";

            // Add ordering
            $sql .= " ORDER BY {$orderColumnName} {$orderDir}";

            // Add limit and offset for pagination
            $sql .= " LIMIT ?, ?";
            // Ensure params array has proper values before adding limit/offset
            $limitParams = array_merge($params, [$start, $length]);


            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($limitParams); // Use $limitParams here
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'employee' => $row['employee'] ?? '',
                    'due_date' => $row['DueDate'] ?? '',
                    'reason' => $row['Reason'] ?? '',
                    'status' => $row['Status'] ?? '0', // Ensure a default if null
                    'draft___' => $row['Draft'] ?? '0', // For the frontend logic (0 or 1)
                    'created_by_name' => $row['created_by_name'] ?? '-'
                    // You can add 'branch_name' here if you include it in the SELECT and JOIN
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $totalRecords, // Total records without filters
                'recordsFiltered' => $filteredRecords, // Total records with applied filters
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Resignation List DataTables Error: " . $e->getMessage());

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Database error: ' . $e->getMessage() // More descriptive error
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// CONTRACT RENEWAL LIST â€” DataTables
// ============================================================
    case 'contractRenewal-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $statesFilter = $_POST['states'] ?? []; // Corrected variable name from `states`

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND r.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($statesFilter) && is_array($statesFilter)) {
                // DataTables might send single value or array depending on select type
                $statesArray = is_array($statesFilter) ? $statesFilter : [$statesFilter];
                if (!empty($statesArray)) {
                    $placeholders = implode(',', array_fill(0, count($statesArray), '?'));
                    $where .= " AND r.Status IN ($placeholders)";
                    $params = array_merge($params, $statesArray);
                }
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    // Using new_s_date and new_e_date based on schema
                    $where .= " AND r.new_s_date BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }
            // Count total records
            $countSql = "SELECT COUNT(*) FROM tblremewal r WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Fetch paginated and filtered data
            $sql = "SELECT r.Id, r.new_s_date, r.new_e_date, r.state, r.CreatedBy, r.LastUpdateDate, -- Changed LastUpdate to LastUpdateDate
                       b.branch_name, 
                       CONCAT(u_emp.FirstName, ' ', u_emp.LastName) as employee_name,
                       CONCAT(u_creator.FirstName, ' ', u_creator.LastName) as creator_name
                FROM tblremewal r
                LEFT JOIN branches b ON r.BranchID = b.branch_id
                LEFT JOIN tblusers u_emp ON r.UserID = u_emp.UserID  /* Employee whose contract is being renewed */
                LEFT JOIN tblusers u_creator ON r.CreatedBy = u_creator.UserID /* Use CreatedBy for creator name */
                WHERE $where
                ORDER BY r.Id DESC"; // You might want to add pagination (LIMIT/OFFSET) here for true server-side processing

            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'employee_name' => $row['employee_name'] ?? 'غير معروف', // Use the alias from SQL
                    'branch_name' => $row['branch_name'] ?? 'غير معروف',     // Use the alias
                    'from_date' => $row['new_s_date'] ?? '',
                    'to_date' => $row['new_e_date'] ?? '',
                    'renewal_status' => $row['state'] ?? 0, // Use the alias, default to 0 for unknown
                    'creator_info' => ($row['creator_name'] ?? 'غير معروف') . '<br>' . ($row['LastUpdate'] ?? '') // Combined for display
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total, // For simple cases, filtered equals total. For actual filtering, it's different.
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Output detailed error for debugging, then remove for production
            error_log("Database error in contractRenewal-list: " . $e->getMessage());
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => "حدث خطأ في قاعدة البيانات: " . $e->getMessage() // More user-friendly error
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// PROMOTION LIST â€” DataTables
// ============================================================
// ============================================================
// PROMOTION LIST â€” DataTables
// ============================================================
    case 'promotion-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? []; // This filter will now be ignored/ineffective
        $statesFilter = $_POST['states'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            // --- REMOVE BRANCH FILTERING LOGIC HERE ---
            /* 
            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND p.BranchID IN ($placeholders)"; // REMOVE THIS LINE
                $params = array_merge($params, $branchFilter); // REMOVE THIS LINE
            }
            */
            // If you still want to filter by branch, you must find which ID in promotion_requests links to the branch. 
            // For now, we comment it out as BranchID is missing from the table.


            if (!empty($statesFilter) && is_array($statesFilter)) {
                $statesArray = is_array($statesFilter) ? $statesFilter : [$statesFilter];
                if (!empty($statesArray)) {
                    $placeholders = implode(',', array_fill(0, count($statesArray), '?'));
                    $where .= " AND p.status IN ($placeholders)"; // Status column name is 'status'
                    $params = array_merge($params, $statesArray);
                }
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND p.effective_date BETWEEN ? AND ?"; // Changed CreatedDate to effective_date
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM promotion_requests p WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // --- CORRECTED SQL: Removed Branch Join, used correct table/column names ---
            $sql = "SELECT p.*, 
                       CONCAT(u_emp.FirstName, ' ', u_emp.LastName) as employee_name,
                       CONCAT(u_creator.FirstName, ' ', u_creator.LastName) as creator_name
                FROM promotion_requests p
                LEFT JOIN tblusers u_emp ON p.user_id = u_emp.UserID /* Changed p.UserID to p.user_id */
                LEFT JOIN tblusers u_creator ON p.requested_by = u_creator.UserID /* Changed CreatedUser to requested_by */
                WHERE $where
                ORDER BY p.id DESC"; // Changed p.Id to p.id

            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['id'], // Use lowercase id
                    'employee_name' => $row['employee_name'] ?? 'غير معروف',
                    'branch_name' => 'N/A', // Branch info is not available in this table
                    'from_date' => $row['new_s_date'] ?? '', // Placeholder if you need this column elsewhere
                    'to_date' => $row['new_e_date'] ?? '',   // Placeholder if you need this column elsewhere
                    'renewal_status' => $row['status'] ?? 'draft', // Status column is 'status'
                    'creator_info' => ($row['creator_name'] ?? 'غير معروف') . '<br>' . ($row['updated_at'] ?? '')
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Output detailed error for debugging, then remove for production
            error_log("Database error in promotion-list: " . $e->getMessage());
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => "حدث خطأ في قاعدة البيانات: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    case 'get-promotion-details':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);

        $promotionId = (int) ($_GET['id'] ?? 0);
        if ($promotionId <= 0) {
            $result = false;
            $msg = 'معرف الترقية غير صالح';
            break;
        }

        $promotionDetails = $manager->getRequestById($promotionId);

        if ($promotionDetails) {
            $data = [
                'id' => $promotionDetails['id'],
                'user_id' => $promotionDetails['user_id'],
                'emp_name' => htmlspecialchars($promotionDetails['emp_first'] . ' ' . $promotionDetails['emp_last']),
                'emp_photo' => htmlspecialchars($promotionDetails['emp_photo'] ?: 'dist/img/avatar-default.png'),
                'requester_name' => htmlspecialchars($promotionDetails['requester_first'] . ' ' . $promotionDetails['requester_last']),
                'created_at' => $promotionDetails['created_at'],
                'status' => $promotionDetails['status'],
                'status_badge' => $promotionDetails['status_badge'],
                'effective_date' => $promotionDetails['effective_date'],
                'current_grade_id' => $promotionDetails['current_grade_id'],
                'current_grade_name' => htmlspecialchars($promotionDetails['current_grade_name']),
                'proposed_grade_id' => $promotionDetails['proposed_grade_id'],
                'proposed_grade_name' => htmlspecialchars($promotionDetails['proposed_grade_name']),
                'current_job_title_id' => $promotionDetails['current_job_title_id'],
                'current_job_title_name' => htmlspecialchars($promotionDetails['current_job_title_name']),
                'proposed_job_title_id' => $promotionDetails['proposed_job_title_id'],
                'proposed_job_title_name' => htmlspecialchars($promotionDetails['proposed_job_title_name']),
                'current_salary' => $promotionDetails['current_salary'],
                'current_currency' => $promotionDetails['current_currency'],
                'proposed_salary' => $promotionDetails['proposed_salary'],
                'justification' => htmlspecialchars($promotionDetails['justification']),
                'performance_notes' => htmlspecialchars($promotionDetails['performance_notes']),
                'has_violations' => (bool) $promotionDetails['has_violations'],
                'violation_summary_parsed' => $promotionDetails['violation_summary_parsed'], // Already parsed in manager
                'violation_override' => (bool) $promotionDetails['violation_override'],
                'override_reason' => htmlspecialchars($promotionDetails['override_reason']),
                'rejection_reason' => htmlspecialchars($promotionDetails['rejection_reason']),
                'section_name' => htmlspecialchars($promotionDetails['section_name']),
                'branch_name' => htmlspecialchars($promotionDetails['branch_name'])
            ];
            $result = true;
            $msg = 'تم جلب تفاصيل الترقية بنجاح';
        } else {
            $result = false;
            $msg = 'طلب الترقية غير موجود';
        }
        break;
    // ============================================================
// ISSUING SALARIES LIST â€” DataTables
// ============================================================

    case 'contractRenewal-emp-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $name = trim((string) ($_POST['name'] ?? ''));
        $branchs = $_POST['branchs'] ?? [];
        $section = $_POST['section'] ?? [];
        $jobtitle = $_POST['jobtitle'] ?? [];
        $grade = $_POST['grade'] ?? [];
        $type = $_POST['type'] ?? 'contract';
        $where = "WHERE u.isemp IS NOT NULL AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)";
        $params = [];
        if ($name !== '') { $where .= " AND (u.FirstName LIKE ? OR u.LastName LIKE ? OR CONCAT(u.FirstName,' ',u.LastName) LIKE ? OR u.UserID = ?)"; $params = array_merge($params, ["%$name%", "%$name%", "%$name%", (int)$name]); }
        foreach ([['k.BranchID',$branchs], ['k.SectionID',$section], ['k.jobtitleID',$jobtitle], ['k.GradeID',$grade]] as $filter) {
            [$col, $vals] = $filter; $vals = is_array($vals) ? array_values(array_filter(array_map('intval', $vals))) : [];
            if ($vals) { $where .= " AND $col IN (" . implode(',', array_fill(0, count($vals), '?')) . ")"; $params = array_merge($params, $vals); }
        }
        $countStmt = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion = k.Id $where");
        $countStmt->execute($params); $total = (int)$countStmt->fetchColumn();
        $sql = "SELECT u.UserID,u.FirstName,u.LastName,k.Id AS renewal_id,k.Salary,k.Currency,k.new_e_date,b.branch_name,s.Name AS section_name
                FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion = k.Id
                LEFT JOIN branches b ON b.branch_id=k.BranchID LEFT JOIN tblsection s ON s.Id=k.SectionID
                $where ORDER BY u.UserID DESC LIMIT $start,$length";
        $stmt = $connect_pdo->prepare($sql); $stmt->execute($params); $data=[];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $empName = trim(($row['FirstName'] ?? '').' '.($row['LastName'] ?? ''));
            $expired = !empty($row['new_e_date']) && strtotime($row['new_e_date']) < strtotime(date('Y-m-d'));
            $confirm = (!$expired && $type === 'contract') ? ' onclick="return confirm(\'This employee\\\'s current contract has not expired yet. Do you still want to renew it?\');"' : '';
            $url = 'contractRenewal-add?add=' . (int)($row['renewal_id'] ?: 0);
            $data[] = [$empName . '<br><small class="text-muted">#'.(int)$row['UserID'].'</small>', $row['section_name'] ?: '-', number_format((float)($row['Salary'] ?? 0),2).' '.(($row['Currency'] ?? 'SAR') === 'SAR' ? 'ر.س' : $row['Currency']), $row['branch_name'] ?: '-', ($type === 'contract' ? 'تجديد عقد' : 'ترقية'), '<a class="btn btn-sm btn-primary" href="'.$url.'"'.$confirm.'><i class="fas fa-sync-alt"></i> اختيار</a>'];
        }
        echo json_encode(['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data], JSON_UNESCAPED_UNICODE); exit;

    case 'Issuing-salaries':
        $draw = (int)($_POST['draw'] ?? 1); $start=(int)($_POST['start']??0); $length=(int)($_POST['length']??200);
        $dateRange = trim((string)($_POST['date_range'] ?? '')); $branchs = $_POST['branchs'] ?? [];
        $where = "WHERE u.isemp IS NOT NULL AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)"; $params=[];
        $branchVals = is_array($branchs) ? array_values(array_filter(array_map('intval',$branchs))) : [];
        if ($branchVals) { $where .= " AND k.BranchID IN (".implode(',',array_fill(0,count($branchVals),'?')).")"; $params=array_merge($params,$branchVals); }
        $countStmt=$connect_pdo->prepare("SELECT COUNT(*) FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion=k.Id $where"); $countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
        $sql="SELECT u.UserID,CONCAT(u.FirstName,' ',u.LastName) emp_name,b.branch_name,k.Salary,k.Currency FROM tblusers u LEFT JOIN tblremewal k ON u.lastversion=k.Id LEFT JOIN branches b ON b.branch_id=k.BranchID $where ORDER BY u.UserID DESC LIMIT $start,$length";
        $stmt=$connect_pdo->prepare($sql); $stmt->execute($params); $data=[]; $sum=0;
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){ $salary=(float)($row['Salary']??0); $sum+=$salary; $data[]=[(int)$row['UserID'],$row['emp_name']?:'-',$row['branch_name']?:'-',number_format($salary,2),'0.00','0.00','0.00','0.00','0.00','0.00','0','0','0',number_format($salary,2)]; }
        echo json_encode(['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'sum_salary'=>number_format($sum,2,'.',''),'net_salary'=>number_format($sum,2,'.',''),'sum_incentive'=>'0.00','sum_benefit'=>'0.00','sum_advance'=>'0.00','sum_dection'=>'0.00','currency'=>'SAR','results_note'=>['name'=>'','report_time'=>date('Y-m-d H:i'),'selected_period'=>$dateRange,'filter_note'=>'','selected_branch'=>$branchVals]], JSON_UNESCAPED_UNICODE); exit;

    case 'Issuing-salaries-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND s.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND s.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM salary_registration s WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT s.*, b.branch_name
                FROM salary_registration s
                LEFT JOIN branches b ON s.BranchID = b.branch_id
                WHERE $where
                ORDER BY s.Id DESC";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['Name'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'month' => $row['month'] ?? '',
                    'year' => $row['year'] ?? '',
                    'status' => $row['Status'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// LEAVE REQUEST LIST â€” DataTables
// ============================================================
    case 'leaveRequest-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0); // Added for DataTables server-side processing
        $length = (int) ($_POST['length'] ?? 10); // Added for DataTables server-side processing
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $statusFilter = $_POST['states'] ?? []; // Corrected to match JS data name

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND l.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND l.leave_start_date BETWEEN ? AND ?"; // Use leave_start_date for filtering
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if (!empty($statusFilter) && is_array($statusFilter)) { // Check for status filter
                $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
                $where .= " AND l.status IN ($placeholders)";
                $params = array_merge($params, $statusFilter);
            }


            $countSql = "SELECT COUNT(*) FROM tblleaverequest l WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT l.Id, l.leave_start_date, l.leave_end_date, l.leave_start_time, l.leave_end_time, l.day_leave, l.leave_unit,
                       l.status, l.Draft, l.CreatedDate, l.LastUpdateDate,
                       b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as EmployeeName,
                       lc.Name as LeaveTypeName,
                       CONCAT(u2.FirstName, ' ', u2.LastName) as CreatorName -- Added CreatorName fetch
                FROM tblleaverequest l
                LEFT JOIN branches b ON l.BranchID = b.branch_id
                LEFT JOIN tblusers u ON l.UserID = u.UserID
                LEFT JOIN leaveclassification lc ON l.leavetype = lc.Id
                LEFT JOIN tblusers u2 ON l.created_by = u2.UserID -- Join to get creator name
                WHERE $where
                ORDER BY l.Id DESC
                LIMIT $start, $length"; // Added LIMIT for server-side processing
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $statusText = '';
                if ((int) $row['status'] === 1) {
                    $statusText = '<span class="badge badge-success">معتمد</span>';
                } elseif ((int) $row['status'] === 2) {
                    $statusText = '<span class="badge badge-danger">مرفوض</span>';
                } else {
                    $statusText = '<span class="badge badge-warning">قيد المراجعة</span>';
                }

                $durationDisplay = '';
                if ($row['leave_unit'] === 'hour') {
                    $durationDisplay = "{$row['day_leave']} ساعة";
                } else {
                    $durationDisplay = "{$row['day_leave']} يوم";
                }

                $data[] = [
                    'id' => $row['Id'],
                    'employee_name' => htmlspecialchars($row['EmployeeName'] ?? ''),
                    'last_update' => htmlspecialchars($row['LastUpdateDate'] ?? $row['CreatedDate'] ?? '-'),
                    'duration_display' => $durationDisplay, // NEW FIELD for combined display
                    'status_text' => $statusText,
                    'is_draft' => ($row['Draft'] == 0) ? 1 : 0, // Sending 1 for draft, 0 for not draft for JS logic
                    'creator_name' => htmlspecialchars($row['CreatorName'] ?? 'غير محدد'),
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total, // For simplicity, assume filtered is same as total
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// LEAVE CLASSIFICATION LIST â€” DataTables
// ============================================================
    case 'leaveClassficate-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND lc.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND lc.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM leaveclassification lc WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT lc.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM leaveclassification lc
                LEFT JOIN branches b ON lc.BranchID = b.branch_id
                LEFT JOIN tblusers u ON lc.CreatedBy = u.UserID
                WHERE $where
                ORDER BY lc.Id DESC";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                // Map type values
                $typeText = '';
                switch ($row['type'] ?? '') {
                    case '1':
                        $typeText = 'اجازة مرضية';
                        break;
                    case '2':
                        $typeText = 'نصف فترة';
                        break;
                    case '3':
                        $typeText = 'يوم كامل';
                        break;
                    default:
                        $typeText = $row['type'] ?? '';
                }
                // Map state values
                $stateText = ($row['isaccept'] ?? 0) == 1 ? 'نشط' : 'موقف';

                $data[] = [
                    'data' => [
                        'id' => $row['Id'],
                        'name' => $row['Name'] ?? '',
                        'branch' => $row['branch_name'] ?? '',
                        'user' => $row['CreatorName'] ?? '',
                        'updated' => $row['LastUpdateDate'] ?? $row['CreatedDate'] ?? '',
                        'state' => $stateText,
                        'type' => $typeText
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// GENERIC LIST ENDPOINTS (DataTables for list pages)
// ============================================================
    case 'EmpAdvances-list-add':
    case 'EmpAdvances-list-admin':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);
        $dateRange = $_POST['date_range'] ?? '';
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) === 2) {
                    $where .= " AND DATE(a.CreatedDate) BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            if ($stateFilter === '1') {
                $where .= " AND a.status = 1";
            } elseif ($stateFilter === '2') {
                $where .= " AND a.status = 2";
            } elseif ($stateFilter === '0') {
                $where .= " AND a.status IS NULL";
            }

            $countSql = "SELECT COUNT(*) FROM tblempadvances a WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT a.Id, a.Amount, a.currency, a.Status, a.Draft, a.CreatedDate, a.LastUpdateDate,
                       CONCAT(u.FirstName, ' ', u.LastName) AS EmployeeName
                FROM tblempadvances a
                LEFT JOIN tblusers u ON a.UserID = u.UserID
                WHERE $where
                ORDER BY a.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $statusBadge = '<span class="badge badge-warning">معلق</span>';
                if ((string) ($row['Status'] ?? '') === '1') {
                    $statusBadge = '<span class="badge badge-success">معتمد</span>';
                } elseif ((string) ($row['Status'] ?? '') === '2') {
                    $statusBadge = '<span class="badge badge-danger">مرفوض</span>';
                }

                $data[] = [
                    'id' => (int) $row['Id'],
                    'name' => $row['EmployeeName'] ?? '',
                    'updated' => $row['LastUpdateDate'] ?: $row['CreatedDate'],
                    'amount' => ($row['Amount'] ?? '0') . ' ' . ($row['currency'] ?? 'SAR'),
                    'statedevice' => $statusBadge,
                    'status' => $row['Status'],
                    'draft' => (int) ($row['Draft'] ?? 0)
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// SECTION LIST â€” DataTables
// ============================================================
    case 'section-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            // Build WHERE clause
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND s.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND s.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM tblsection s WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Get data
            $sql = "SELECT s.Id, s.Name, s.BranchID, s.CreatedDate, s.CreatedBy,
                       b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tblsection s
                LEFT JOIN branches b ON s.BranchID = b.branch_id
                LEFT JOIN tblusers u ON s.CreatedBy = u.UserID
                WHERE $where
                ORDER BY s.Id DESC";

            // FIX: Only add the LIMIT clause if length is not -1
            if ($length != -1) {
                $sql .= " LIMIT $start, $length";
            }

            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => $row['Id'],
                    'name_' => $row['Name'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'createddate' => $row['CreatedDate'] ?? '',
                    'name' => $row['CreatorName'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage() // This will display the actual SQL error in the console if it happens again
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// JOB TITLE LIST â€” DataTables
// ============================================================
    case 'jobtitle-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND j.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND j.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tbljobtitle j WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT j.Id, j.Name, j.BranchID, j.CreatedDate, j.CreatedBy,
                       b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tbljobtitle j
                LEFT JOIN branches b ON j.BranchID = b.branch_id
                LEFT JOIN tblusers u ON j.CreatedBy = u.UserID
                WHERE $where
                ORDER BY j.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'data' => [
                        'id' => $row['Id'],
                        'name_' => $row['Name'] ?? '',
                        'branch' => $row['branch_name'] ?? '',
                        'createddate' => $row['CreatedDate'] ?? '',
                        'name' => $row['CreatorName'] ?? ''
                    ]
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// SHIFTS LIST â€” DataTables
// ============================================================
    case 'shift-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? ($_POST['filter_status'] ?? []);
        if ($length === -1) {
            $length = 100000;
        }

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter)) {
                $branchValues = is_array($branchFilter) ? $branchFilter : [$branchFilter];
                $branchValues = array_filter(array_map('intval', $branchValues));
                if (!empty($branchValues)) {
                    $placeholders = implode(',', array_fill(0, count($branchValues), '?'));
                    $where .= " AND s.BranchID IN ($placeholders)";
                    $params = array_merge($params, $branchValues);
                }
            }

            if (!empty($stateFilter)) {
                $stateValues = is_array($stateFilter) ? $stateFilter : [$stateFilter];
                $state = intval($stateValues[0]);
                if ($state === 0 || $state === 1) {
                    $where .= " AND s.ShiftState = ?";
                    $params[] = $state;
                }
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) === 2) {
                    $where .= " AND s.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tbshift s WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            if ($length === 0) {
                $length = 10;
            }
            $dataSql = "SELECT s.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName,
                           sched.first_start, sched.last_end, sched.first_time, sched.last_time
                    FROM tbshift s
                    LEFT JOIN branches b ON s.BranchID = b.branch_id
                    LEFT JOIN tblusers u ON s.CreatedBy = u.UserID
                    LEFT JOIN (
                        SELECT shift_id,
                               MIN(start_date) as first_start,
                               MAX(end_date) as last_end,
                               MIN(start_time) as first_time,
                               MAX(end_time) as last_time
                        FROM shifts_schedule
                        GROUP BY shift_id
                    ) sched ON sched.shift_id = s.ShiftID
                    WHERE $where
                    ORDER BY s.ShiftID DESC
                    LIMIT ?, ?";
            $queryParams = $params;
            $queryParams[] = $start;
            $queryParams[] = $length;
            $stmt = $connect_pdo->prepare($dataSql);
            $stmt->execute($queryParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $creatorName = trim($row['CreatorName'] ?? '');
                $createdDate = $row['CreatedDate'] ?? '';
                $updatedDate = $row['LastUpdatetim'] ?? '';

                $creatorParts = array_filter([$creatorName, $createdDate]);
                if ($updatedDate && $updatedDate !== $createdDate) {
                    $creatorParts[] = 'آخر تحديث: ' . substr($updatedDate, 0, 10);
                }
                $creatorHtml = '<small>' . nl2br(htmlspecialchars(implode("\n", $creatorParts))) . '</small>';

                $startTime = $row['first_time'] ?? $row['ShiftStartTime'] ?? '';
                $endTime = $row['last_time'] ?? $row['ShiftEndTime'] ?? '';
                $startLabel = $startTime ? substr($startTime, 0, 5) : '-';
                $endLabel = $endTime ? substr($endTime, 0, 5) : '-';

                $totalHours = '-';
                if ($startTime && $endTime) {
                    $diff = (strtotime($endTime) - strtotime($startTime)) / 3600;
                    if ($diff < 0) {
                        $diff += 24;
                    }
                    $totalHours = round($diff, 2) . ' س';
                }

                $stateBadge = ($row['ShiftState'] ?? 0) == 1
                    ? '<span class="badge badge-danger">موقوفة</span>'
                    : '<span class="badge badge-success">نشطة</span>';

                $dateRange = '-';
                $firstDate = $row['first_start'] ?? '';
                $lastDate = $row['last_end'] ?? '';
                if ($firstDate || $lastDate) {
                    if ($firstDate && $lastDate && $firstDate !== $lastDate) {
                        $dateRange = $firstDate . ' - ' . $lastDate;
                    } else {
                        $dateRange = $firstDate ?: $lastDate;
                    }
                }

                $data[] = [
                    'id' => $row['ShiftID'],
                    'name' => $row['ShiftName'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'date_range' => $dateRange,
                    'creator' => $creatorHtml,
                    'start_time' => $startLabel,
                    'end_time' => $endLabel,
                    'total_hours' => $totalHours,
                    'state_html' => $stateBadge
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// INSURANCE LIST â€” DataTables
// ============================================================
    case 'insurance-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['state'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND i.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($stateFilter)) {
                $where .= " AND i.Cstate = ?";
                $params[] = $stateFilter;
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND i.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tbinsurance i WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT i.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tbinsurance i
                LEFT JOIN branches b ON i.BranchID = b.branch_id
                LEFT JOIN tblusers u ON i.CreatedBy = u.UserID
                WHERE $where
                ORDER BY i.Id DESC";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $stateText = '';
                switch ($row['Cstate'] ?? '') {
                    case '1':
                        $stateText = 'نشط';
                        break;
                    case '2':
                        $stateText = 'موقف';
                        break;
                    default:
                        $stateText = '-';
                }

                $data[] = [
                    'id' => $row['Id'],
                    'name' => $row['Name'] ?? '',
                    'branch' => $row['BranchName'] ?? '',
                    'state' => $stateText,
                    'type' => $row['company_type'] ?? '',
                    'createdby' => $row['CreatorName'] ?? '',
                    'createddate' => $row['CreatedDate'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// FINGERPRINT LIST â€” DataTables (API Documentation Compliant)
// ============================================================
    case 'fingerprint-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];
        $stateFilter = $_POST['states'] ?? '';

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND fd.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($stateFilter)) {
                $where .= " AND fd.FingerprintState = ?";
                $params[] = intval($stateFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND fd.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tbfingerprint fd WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT fd.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tbfingerprint fd
                LEFT JOIN branches b ON fd.BranchID = b.branch_id
                LEFT JOIN tblusers u ON fd.CreatedBy = u.UserID
                WHERE $where
                ORDER BY fd.FingerprintID DESC";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $stateText = '';
                switch ((int) ($row['FingerprintState'] ?? 0)) {
                    case 1:
                        $stateText = 'شغال';
                        break;
                    case 2:
                        $stateText = 'موقف';
                        break;
                    case 3:
                        $stateText = 'جاري الصيانة';
                        break;
                    default:
                        $stateText = '-';
                }
                $createdDate = $row['CreatedDate'] ?? $row['lastUpdateDate'] ?? null;

                $data[] = [
                    'id' => $row['FingerprintID'],
                    'name' => $row['FingerprintName'] ?? '',
                    'branch' => $row['branch_name'] ?? '',
                    'state' => $stateText,
                    'createdby' => $row['CreatorName'] ?? '',
                    'createddate' => $createdDate ? date('Y-m-d', strtotime($createdDate)) : ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// GROUP LIST â€” DataTables
// ============================================================
    case 'groub-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $dateRange = $_POST['date_range'] ?? '';
        $branchFilter = $_POST['branchs'] ?? [];

        try {
            $where = "1=1";
            $params = [];

            if (!empty($branchFilter) && is_array($branchFilter)) {
                $placeholders = implode(',', array_fill(0, count($branchFilter), '?'));
                $where .= " AND g.BranchID IN ($placeholders)";
                $params = array_merge($params, $branchFilter);
            }

            if (!empty($dateRange)) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $where .= " AND g.CreatedDate BETWEEN ? AND ?";
                    $params[] = trim($dates[0]);
                    $params[] = trim($dates[1]);
                }
            }

            $countSql = "SELECT COUNT(*) FROM tblgroup g WHERE $where";
            $countStmt = $connect_pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $sql = "SELECT g.*, b.branch_name, CONCAT(u.FirstName, ' ', u.LastName) as CreatorName
                FROM tblgroup g
                LEFT JOIN branches b ON g.BranchID = b.branch_id
                LEFT JOIN tblusers u ON g.CreatedBy = u.UserID
                WHERE $where
                ORDER BY g.Id DESC
                LIMIT $start, $length";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    $row['Id'],
                    $row['Name'] ?? '',
                    $row['branch_name'] ?? '',
                    $row['CreatorName'] ?? '',
                    $row['LastUpdatetim'] ?? $row['CreatedDate'] ?? ''
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// BENEFITS â€” GET SINGLE BENEFIT
// ============================================================
    case 'get-benefit':
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'المعرف غير صحيح';
            break;
        }

        $stmt = $connect_pdo->prepare("
        SELECT b.*, br.branch_name 
        FROM tblbenefit b
        LEFT JOIN branches br ON br.branch_id = b.BranchID
        WHERE b.Id = ?
    ");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $result = false;
            $msg = 'التعويض غير موجود';
        }
        break;

    // ============================================================
// BENEFITS â€” ADD/UPDATE
// ============================================================
    case 'Benefits-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $benefitType = intval($_POST['beneft_type'] ?? 1);
        $amount = trim($_POST['amount'] ?? '0');
        $amountType = $_POST['AmountType'] ?? 'amount';
        $currency = $_POST['currency'] ?? 'SAR';
        $reason = trim($_POST['Reson'] ?? $_POST['reason'] ?? '');
        $forWhat = intval($_POST['for_what'] ?? 1);
        $employer = is_array($_POST['employer'] ?? null) ? implode(',', $_POST['employer']) : ($_POST['employer'] ?? '');
        $extinsion = is_array($_POST['extinsion'] ?? null) ? implode(',', $_POST['extinsion']) : ($_POST['extinsion'] ?? '');
        $dueDate = trim($_POST['Due_date'] ?? '');
        if ($benefitType === 1 && $dueDate === '') {
            $dueDate = date('Y-m-t');
        }
        if ($dueDate === '') {
            $result = false;
            $msg = 'تاريخ الاستحقاق مطلوب';
            break;
        }
        $isDraft = intval($_POST['isdraft'] ?? 0);
        $monthly = isset($_POST['monthly']) ? 1 : 0;
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم التعويض مطلوب';
            break;
        }
        if (empty($branchId)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                // Update existing
                $stmt = $connect_pdo->prepare("UPDATE tblbenefit SET 
                BranchID = ?, UserID = ?, name = ?, beneft_type = ?, AmountType = ?, Amount = ?, 
                Currency = ?, Reason = ?, for_what = ?, extionsion = ?, DueDate = ?, monthly = ?, Status = ?
                WHERE Id = ?");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $benefitType,
                    $amountType,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    $monthly,
                    ($isDraft ? null : 1),
                    $id
                ]);
                $data = ['id' => $id];
                $msg = 'تم تحديث التعويض بنجاح';
            } else {
                // Insert new
                $stmt = $connect_pdo->prepare("INSERT INTO tblbenefit 
                (BranchID, UserID, name, beneft_type, AmountType, Amount, Currency, Reason, for_what, extionsion, DueDate, monthly, Status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $benefitType,
                    $amountType,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    $monthly,
                    ($isDraft ? null : 1),
                    $userId
                ]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة التعويض بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            error_log('Benefits-add DB error: ' . $e->getMessage());
            $msg = 'حدث خطأ أثناء حفظ التعويض. يرجى التحقق من البيانات والمحاولة مرة أخرى';
        }
        break;

    case 'Benefits-conform':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tblbenefit SET Status = 1 WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم اعتماد التعويض بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'Benefits-remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tblbenefit WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف التعويض بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// DEDUCTIONS â€” ADD/UPDATE/REMOVE
// ============================================================
    case 'deductions-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $amount = trim($_POST['amount'] ?? '0');
        $currency = $_POST['currency'] ?? 'SAR';
        $reason = trim($_POST['Reson'] ?? $_POST['reason'] ?? '');
        $forWhat = intval($_POST['for_what'] ?? 1);
        $employer = is_array($_POST['employer'] ?? null) ? implode(',', $_POST['employer']) : ($_POST['employer'] ?? '');
        $extinsion = is_array($_POST['extinsion'] ?? null) ? implode(',', $_POST['extinsion']) : ($_POST['extinsion'] ?? '');
        $dueDate = trim($_POST['Due_date'] ?? '');
        if ($dueDate === '') {
            $result = false;
            $msg = 'تاريخ الاستحقاق مطلوب';
            break;
        }
        $dueDate = str_replace('T', ' ', $dueDate);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dueDate)) {
            $dueDate .= ':00';
        }
        // Convert date from DD/MM/YYYY to YYYY-MM-DD if needed
        if ($dueDate && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dueDate)) {
            $dateParts = explode('/', $dueDate);
            $dueDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
        $isDraft = intval($_POST['isdraft'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم الخصم مطلوب';
            break;
        }
        if (empty($branchId)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tbldeductions SET 
                BranchID = ?, UserID = ?, name = ?, Amount = ?, Currency = ?, Reason = ?, 
                for_what = ?, extionsion = ?, DueDate = ?, Status = ?
                WHERE Id = ?");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    ($isDraft ? null : 1),
                    $id
                ]);
                $data = ['id' => $id];
                $msg = 'تم تحديث الخصم بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tbldeductions 
                (BranchID, UserID, name, Amount, Currency, Reason, for_what, extionsion, DueDate, Status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    ($isDraft ? null : 1),
                    $userId
                ]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة الخصم بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            error_log('deductions-add DB error: ' . $e->getMessage());
            $msg = 'حدث خطأ أثناء حفظ الخصم. يرجى التحقق من البيانات والمحاولة مرة أخرى';
        }
        break;

    case 'deductions-conform':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tbldeductions SET Status = 1 WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم اعتماد الخصم بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'deductions-remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tbldeductions WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف الخصم بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// INCENTIVES â€” ADD/UPDATE/REMOVE
// ============================================================
    case 'incentive-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $incentiveType = $_POST['incentive_type'] ?? '1';
        $amount = trim($_POST['amount'] ?? '0');
        $amountType = $_POST['AmountType'] ?? 'amount';
        $currency = $_POST['currency'] ?? 'SAR';
        $reason = trim($_POST['Reson'] ?? $_POST['reason'] ?? '');
        $forWhat = intval($_POST['for_what'] ?? 1);
        $employer = is_array($_POST['employer'] ?? null) ? implode(',', $_POST['employer']) : ($_POST['employer'] ?? '');
        $extinsion = is_array($_POST['extinsion'] ?? null) ? implode(',', $_POST['extinsion']) : ($_POST['extinsion'] ?? '');
        $dueDate = trim($_POST['Due_date'] ?? '');
        if ($incentiveType == 1 && $dueDate === '') {
            $dueDate = date('Y-m-t');
        }
        if ($dueDate === '') {
            $result = false;
            $msg = 'تاريخ الاستحقاق مطلوب';
            break;
        }
        $isDraft = intval($_POST['isdraft'] ?? 0);
        $monthly = isset($_POST['monthly']) ? 1 : 0;
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم الحافز مطلوب';
            break;
        }
        if (empty($branchId)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblincentives SET 
                BranchID = ?, UserID = ?, name = ?, incentive_type = ?, AmountType = ?, Amount = ?, 
                Currency = ?, Reason = ?, for_what = ?, extionsion = ?, DueDate = ?, monthly = ?, Status = ?
                WHERE Id = ?");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $incentiveType,
                    $amountType,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    $monthly,
                    ($isDraft ? null : 1),
                    $id
                ]);
                $data = ['id' => $id];
                $msg = 'تم تحديث الحافز بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblincentives 
                (BranchID, UserID, name, incentive_type, AmountType, Amount, Currency, Reason, for_what, extionsion, DueDate, monthly, Status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $branchId,
                    $employer,
                    $name,
                    $incentiveType,
                    $amountType,
                    $amount,
                    $currency,
                    $reason,
                    $forWhat,
                    $extinsion,
                    $dueDate,
                    $monthly,
                    ($isDraft ? null : 1),
                    $userId
                ]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة الحافز بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    case 'incentive-conform':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tblincentives SET Status = 1 WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم اعتماد الحافز بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'incentive-remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tblincentives WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف الحافز بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// EMPLOYEE ADVANCES â€” ADD/UPDATE/REMOVE
// ============================================================
    case 'EmpAdvances-add':
    case 'EmpAdvances-add-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $amount = trim($_POST['amount'] ?? $_POST['Amount'] ?? '0');
        $currency = $_POST['currency'] ?? $_POST['Currency'] ?? 'SAR';
        $reason = trim($_POST['reason'] ?? $_POST['Reason'] ?? $_POST['Reson'] ?? '');
        $advanceType = intval($_POST['type'] ?? 1);
        $isDraft = intval($_POST['isdraft'] ?? 0);
        $dueDate = $_POST['DueDate'] ?? $_POST['due_date'] ?? $_POST['Due_date'] ?? null;
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET 
                UserID = ?, BranchID = ?, Amount = ?, Currency = ?, Reason = ?, DueDate = ?, type = ?, Draft = ?, status = ?
                WHERE Id = ?");
                $stmt->execute([$empId, $branchId, $amount, $currency, $reason, $dueDate, $advanceType, $isDraft, ($isDraft ? null : 0), $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث السلفة بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblempadvances 
                (UserID, BranchID, Amount, Currency, Reason, DueDate, type, Draft, status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$empId, $branchId, $amount, $currency, $reason, $dueDate, $advanceType, $isDraft, ($isDraft ? null : 0), $userId]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة السلفة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    case 'EmpAdvances-conform-admin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET status = 1, Draft = 0 WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم اعتماد السلفة بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'EmpAdvances-remove':
    case 'EmpAdvances-remove-add':
    case 'EmpAdvances-remove-admin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tblempadvances WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف السلفة بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'EmpAdvances-upload':
    case 'EmpAdvances-upload-add':
        // Handle file upload for advances
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        if (!empty($_FILES['file']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/advances/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            $fileName = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                try {
                    $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET path = ? WHERE Id = ?");
                    $stmt->execute(['uploads/advances/' . $fileName, $id]);
                    $msg = 'تم رفع الملف بنجاح';
                } catch (PDOException $e) {
                    $result = false;
                    $msg = 'خطأ في حفظ الملف';
                }
            } else {
                $result = false;
                $msg = 'فشل رفع الملف';
            }
        } else {
            $result = false;
            $msg = 'لم يتم اختيار ملف';
        }
        break;

    // ============================================================
// SECTIONS â€” ADD/UPDATE
// ============================================================
    case 'section-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? $_POST['Name'] ?? $_POST['sectiontname'] ?? '');
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $parentId = !empty($_POST['ParentID']) ? intval($_POST['ParentID']) : (!empty($_POST['select_section']) ? intval($_POST['select_section']) : null);

        // Set CreatedBy and CreatedDate
        $createdBy = $_SESSION['user']['id'] ?? $user;
        $createdDate = date('Y-m-d H:i:s'); // Current date and time

        if (empty($name)) {
            $result = false;
            $msg = 'اسم القسم مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                // Usually, we don't update "Created" data on an update operation. 
                $stmt = $connect_pdo->prepare("UPDATE tblsection SET Name = ?, BranchID = ?, ParentID = ? WHERE Id = ?");
                $stmt->execute([$name, $branchId, $parentId, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث القسم بنجاح';
            } else {
                // Insert with CreatedBy and CreatedDate
                $stmt = $connect_pdo->prepare("INSERT INTO tblsection (Name, BranchID, ParentID, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $branchId, $parentId, $createdBy, $createdDate]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة القسم بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// JOB TITLES — ADD/UPDATE
// ============================================================
    case 'jobtitle-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['jobtitlename'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $parentId = !empty($_POST['select_jobtitle']) ? intval($_POST['select_jobtitle']) : (!empty($_POST['ParentID']) ? intval($_POST['ParentID']) : null);

        // Set CreatedBy and CreatedDate
        $createdBy = $_SESSION['user_id'] ?? 0; // CHANGE THIS to your actual session variable
        $createdDate = date('Y-m-d H:i:s'); // Current date and time

        if (empty($name)) {
            $result = false;
            $msg = 'اسم المسمى الوظيفي مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                // Usually, we don't update "Created" data on an update operation.
                $stmt = $connect_pdo->prepare("UPDATE tbljobtitle SET Name = ?, BranchID = ?, ParentID = ? WHERE Id = ?");
                $stmt->execute([$name, $branchId, $parentId, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث المسمى الوظيفي بنجاح';
            } else {
                // Insert with CreatedBy and CreatedDate
                $stmt = $connect_pdo->prepare("INSERT INTO tbljobtitle (Name, BranchID, ParentID, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $branchId, $parentId, $createdBy, $createdDate]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة المسمى الوظيفي بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// JOB GRADES â€” ADD/UPDATE
// ============================================================
    case 'jobgrade-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['jobgradename'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $description = trim($_POST['note'] ?? '');

        if (empty($name)) {
            $result = false;
            $msg = 'اسم الدرجة الوظيفية مطلوب';
            break;
        }

        try {
            $userId = $_SESSION['user']['id'] ?? $user;
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tbljobgrade SET Name = ?, BranchID = ?, Description = ?, LastUpdateDate = CURRENT_TIMESTAMP WHERE Id = ?");
                $stmt->execute([$name, $branchId, $description, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث الدرجة الوظيفية بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tbljobgrade (Name, BranchID, Description, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$name, intval($bid), $description, $userId]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة الدرجة الوظيفية بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// GROUPS â€” ADD/UPDATE
// ============================================================
    case 'groub-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? $_POST['Name'] ?? $_POST['groubname'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $leaderGroup = intval($_POST['LeaderGroup'] ?? 0);
        $description = trim($_POST['note'] ?? $_POST['Description'] ?? '');

        if (empty($name)) {
            $result = false;
            $msg = 'اسم المجموعة مطلوب';
            break;
        }

        try {
            $userId = $_SESSION['user']['id'] ?? $user;
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblgroup SET Name = ?, BranchID = ?, LeaderGroup = ?, Description = ?, LastUpdateDate = CURRENT_TIMESTAMP WHERE Id = ?");
                $stmt->execute([$name, $branchId, $leaderGroup, $description, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث المجموعة بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tblgroup (Name, BranchID, LeaderGroup, Description, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$name, intval($bid), $leaderGroup, $description, $userId]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة المجموعة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// INSURANCE â€” ADD/UPDATE
// ============================================================
    case 'insurance-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['Insurancename'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $representative = trim($_POST['Cname'] ?? '');
        $type = intval($_POST['company_type'] ?? 1);
        $phone = trim($_POST['Cphone'] ?? '');
        $email = trim($_POST['CEmail'] ?? '');
        $address = trim($_POST['CAddress'] ?? '');
        $state = intval($_POST['Cstate'] ?? 1);
        $note = trim($_POST['note'] ?? '');

        // Set CreatedBy and CreatedDate
        $createdBy = $_SESSION['user']['id'] ?? $user;
        $createdDate = date('Y-m-d H:i:s'); // Current date and time

        if (empty($name)) {
            $result = false;
            $msg = 'اسم شركة التأمين مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tbinsurance SET 
                Name = ?, BranchID = ?, NameOfRepresentative = ?, type = ?, 
                Phone = ?, Email = ?, Address = ?, state = ?, Note = ? 
                WHERE Id = ?");
                $stmt->execute([$name, $branchId, $representative, $type, $phone, $email, $address, $state, $note, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث شركة التأمين بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    // Added CreatedBy and CreatedDate to the INSERT query
                    $stmt = $connect_pdo->prepare("INSERT INTO tbinsurance 
                    (Name, BranchID, NameOfRepresentative, type, Phone, Email, Address, state, Note, CreatedBy, CreatedDate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    // Passed the variables into the execute array
                    $stmt->execute([$name, intval($bid), $representative, $type, $phone, $email, $address, $state, $note, $createdBy, $createdDate]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة شركة التأمين بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;
    // ============================================================
// HOLIDAYS â€” ADD/UPDATE
// ============================================================
    case 'holidays-add':
    case 'hodidays-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['H_name'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $startDate = $_POST['form_date'] ?? $_POST['start_date'] ?? $_POST['StartDate'] ?? null;
        $endDate = $_POST['until_date'] ?? $_POST['end_date'] ?? $_POST['EndDate'] ?? null;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم العطلة مطلوب';
            break;
        }

        try {
            $userId = $_SESSION['user']['id'] ?? $user;
            $tableData = json_decode($_POST['tableData'] ?? '[]', true);
            
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE holidays SET Name = ?, BranchID = ?, Start_date = ?, End_date = ?, LastUpdateDate = CURRENT_TIMESTAMP WHERE Id = ?");
                $stmt->execute([$name, $branchId, $startDate, $endDate, $id]);
                
                // Get Holiday_ID from database
                $holidayStmt = $connect_pdo->prepare("SELECT Holiday_ID FROM holidays WHERE Id = ?");
                $holidayStmt->execute([$id]);
                $holidayRow = $holidayStmt->fetch();
                $holidayId = $holidayRow['Holiday_ID'] ?? 0;
                
                // Delete old holiday days and insert new ones
                if ($holidayId > 0) {
                    $connect_pdo->prepare("DELETE FROM holidays_day WHERE HolidayID = ?")->execute([$holidayId]);
                }
                
                // Insert holiday days
                if (!empty($tableData) && is_array($tableData)) {
                    $dayStmt = $connect_pdo->prepare("INSERT INTO holidays_day (HolidayID, Date, Description) VALUES (?, ?, ?)");
                    foreach ($tableData as $day) {
                        $dayStmt->execute([$holidayId, $day['date'], $day['description']]);
                    }
                }
                
                $data = ['id' => $id];
                $msg = 'تم تحديث العطلة بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    // Get next Holiday_ID
                    $maxIdStmt = $connect_pdo->prepare("SELECT MAX(Holiday_ID) as max_id FROM holidays");
                    $maxIdStmt->execute();
                    $maxIdRow = $maxIdStmt->fetch();
                    $nextHolidayId = ($maxIdRow['max_id'] ?? 0) + 1;
                    
                    $stmt = $connect_pdo->prepare("INSERT INTO holidays (Holiday_ID, Name, BranchID, Start_date, End_date, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nextHolidayId, $name, intval($bid), $startDate, $endDate, $userId]);
                    $lastId = $connect_pdo->lastInsertId();
                    $holidayId = $nextHolidayId;
                    
                    // Insert holiday days
                    if (!empty($tableData) && is_array($tableData)) {
                        $dayStmt = $connect_pdo->prepare("INSERT INTO holidays_day (HolidayID, Date, Description) VALUES (?, ?, ?)");
                        foreach ($tableData as $day) {
                            $dayStmt->execute([$holidayId, $day['date'], $day['description']]);
                        }
                    }
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة العطلة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            error_log('holidays-add DB error: ' . $e->getMessage());
            $msg = 'حدث خطأ أثناء حفظ العطلة. يرجى التحقق من البيانات والمحاولة مرة أخرى';
        }
        break;

    // ============================================================
// EMPLOYMENT TYPES â€” ADD/UPDATE
// ============================================================
    case 'empolyment-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['empolymentname'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);

        if (empty($name)) {
            $result = false;
            $msg = 'اسم نوع التوظيف مطلوب';
            break;
        }

        try {
            $userId = $_SESSION['user']['id'] ?? $user;
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblemploymenttype SET Name = ?, BranchID = ?, LastUpdateDate = CURRENT_TIMESTAMP WHERE Id = ?");
                $stmt->execute([$name, $branchId, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث نوع التوظيف بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tblemploymenttype (Name, BranchID, CreatedBy, CreatedDate) VALUES (?, ?, ?, CURDATE())");
                    $stmt->execute([$name, intval($bid), $userId]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة نوع التوظيف بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    case 'empolyment-remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tblemploymenttype WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف نوع التوظيف بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// LEAVE REQUESTS â€” ADD/UPDATE
// ============================================================
    case 'leaveRequest-add':
    case 'leaveRequest-add-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $leaveType = $_POST['leavetype'] ?? $_POST['leave_type'] ?? '';
        $startDate = $_POST['leave_start_date'] ?? $_POST['start_date'] ?? null;
        $endDate = $_POST['leave_end_date'] ?? $_POST['end_date'] ?? null;
        $dayLeave = $_POST['day_leave'] ?? $_POST['days'] ?? '';
        $description = trim($_POST['description'] ?? $_POST['reason'] ?? '');
        $isDraft = intval($_POST['isdraft'] ?? $_POST['Draft'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }
        if (empty($leaveType)) {
            $result = false;
            $msg = 'نوع الإجازة مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblleaverequest SET 
                UserID = ?, BranchID = ?, leavetype = ?, leave_start_date = ?, leave_end_date = ?, 
                day_leave = ?, description = ?, Draft = ?, status = ?
                WHERE Id = ?");
                $stmt->execute([
                    $empId,
                    $branchId,
                    $leaveType,
                    $startDate,
                    $endDate,
                    $dayLeave,
                    $description,
                    $isDraft,
                    ($isDraft ? -1 : 0),
                    $id
                ]);
                $data = ['id' => $id];
                $msg = 'تم تحديث طلب الإجازة بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblleaverequest 
                (UserID, BranchID, leavetype, leave_start_date, leave_end_date, day_leave, description, Draft, status, created_by, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $empId,
                    $branchId,
                    $leaveType,
                    $startDate,
                    $endDate,
                    $dayLeave,
                    $description,
                    $isDraft,
                    ($isDraft ? -1 : 0),
                    $userId
                ]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة طلب الإجازة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// LEAVE CLASSIFICATION â€” ADD/UPDATE
// ============================================================
    case 'leaveClassficate-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['leavename'] ?? $_POST['name'] ?? $_POST['Name'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $isaccept = intval($_POST['isaccept'] ?? 1);
        $requiresAttachment = intval($_POST['RequiresAttachment'] ?? 2);
        $type = intval($_POST['type'] ?? 1);
        $forWhat = intval($_POST['for_what'] ?? 0);
        $employer = is_array($_POST['employer'] ?? null) ? implode(',', $_POST['employer']) : ($_POST['employer'] ?? '');
        $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $amountType = $_POST['AmountType'] ?? 'amount';
        $note = trim($_POST['note'] ?? '');
        $stopped = isset($_POST['stopped']) ? 1 : 0;
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم تصنيف الإجازة مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE leaveclassification SET 
                Name = ?, BranchID = ?, Description = ?, isaccept = ?, type = ?, state = ?, 
                for_what = ?, chose = ?, AmountType = ?, Amount = ?, RequiresAttachment = ?, 
                LastUpdateDate = CURRENT_TIMESTAMP WHERE Id = ?");
                $stmt->execute([$name, $branchId, $note, $isaccept, $type, $stopped, $forWhat, $employer, $amountType, $amount, $requiresAttachment, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث تصنيف الإجازة بنجاح';
            } else {
                // Handle multiple branches
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;
                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO leaveclassification 
                    (Name, BranchID, Description, isaccept, type, state, for_what, chose, AmountType, Amount, RequiresAttachment, CreatedBy, CreatedDate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$name, intval($bid), $note, $isaccept, $type, $stopped, $forWhat, $employer, $amountType, $amount, $requiresAttachment, $userId]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تم إضافة تصنيف الإجازة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// CONTRACT RENEWAL â€” ADD/UPDATE/REMOVE
// ============================================================
    case 'contractRenewal-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $sectionId = intval($_POST['SectionID'] ?? $_POST['user_section'] ?? 0);
        $groupId = intval($_POST['GroupID'] ?? $_POST['user_group'] ?? $_POST['user_group_'] ?? 0);
        $gradeId = intval($_POST['GradeID'] ?? $_POST['user_grade'] ?? 0);
        $jobtitleId = intval($_POST['jobtitleID'] ?? $_POST['user_jobtitle'] ?? 0);
        $typeId = intval($_POST['TypeID'] ?? $_POST['user_type'] ?? 0);
        $shiftId = $_POST['shiftID'] ?? $_POST['user_shiftt'] ?? $_POST['user_shift'] ?? '';
        if (is_array($shiftId)) {
            $shiftId = implode(',', array_filter(array_map('strval', $shiftId), 'strlen'));
        }
        $fingerId = $_POST['fingerID'] ?? $_POST['user_fingerr'] ?? $_POST['user_finger'] ?? '';
        if (is_array($fingerId)) {
            $fingerId = implode(',', array_filter(array_map('strval', $fingerId), 'strlen'));
        }
        $salary = $_POST['Salary'] ?? $_POST['emp_salary'] ?? '';
        $currency = $_POST['Currency'] ?? $_POST['currency'] ?? 'SAR';
        $day = $_POST['day'] ?? '';
        $reason = trim($_POST['Reason'] ?? $_POST['Reson'] ?? '');
        $newStartDate = $_POST['new_s_date'] ?? $_POST['emp_contract_S'] ?? null;
        $newEndDate = $_POST['new_e_date'] ?? $_POST['emp_contract_F'] ?? null;
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblremewal SET 
                UserID = ?, BranchID = ?, SectionID = ?, GroupID = ?, GradeID = ?, jobtitleID = ?, 
                TypeID = ?, shiftID = ?, fingerID = ?, Salary = ?, Currency = ?, day = ?, Reason = ?, 
                new_s_date = ?, new_e_date = ?
                WHERE Id = ?");
                $stmt->execute([
                    $empId,
                    $branchId,
                    $sectionId,
                    $groupId,
                    $gradeId,
                    $jobtitleId,
                    $typeId,
                    $shiftId,
                    $fingerId,
                    $salary,
                    $currency,
                    $day,
                    $reason,
                    $newStartDate,
                    $newEndDate,
                    $id
                ]);
                $data = ['id' => $id];
                $msg = 'تم تحديث تجديد العقد بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblremewal 
                (UserID, BranchID, SectionID, GroupID, GradeID, jobtitleID, TypeID, shiftID, fingerID, 
                Salary, Currency, day, Reason, new_s_date, new_e_date, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([
                    $empId,
                    $branchId,
                    $sectionId,
                    $groupId,
                    $gradeId,
                    $jobtitleId,
                    $typeId,
                    $shiftId,
                    $fingerId,
                    $salary,
                    $currency,
                    $day,
                    $reason,
                    $newStartDate,
                    $newEndDate,
                    $userId
                ]);
                $newId = $connect_pdo->lastInsertId();
                $updateUser = $connect_pdo->prepare("UPDATE tblusers SET lastversion = ? WHERE UserID = ?");
                $updateUser->execute([$newId, $empId]);
                $data = ['id' => $newId];
                $msg = 'تم إضافة تجديد العقد بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    case 'contractRenewal-conform':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tblremewal SET state = 1, conform_date = CURDATE() WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم اعتماد تجديد العقد بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    case 'contractRenewal-remove':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("DELETE FROM tblremewal WHERE Id = ?");
            $stmt->execute([$id]);
            $msg = 'تم حذف تجديد العقد بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// SHIFTS â€” ADD/UPDATE
// ============================================================
    case 'shift-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['shiftname'] ?? $_POST['name'] ?? $_POST['ShiftName'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $branchId = is_array($branchIds) ? intval($branchIds[0] ?? 0) : intval($branchIds);
        $numFingerprints = intval($_POST['Footprint'] ?? $_POST['num_fingerprints'] ?? $_POST['NumFootprint'] ?? 2);
        $stopped = isset($_POST['stopped']) ? 1 : 0;
        $userId = $_SESSION['user']['id'] ?? $user;

        // Shift settings
        $allowedLateMinutes = intval($_POST['allowed_late_minutes'] ?? 15);
        $enableHalfDay = intval($_POST['enableHalfDay'] ?? 1);
        $halfDayMinutes = intval($_POST['half_day_minutes'] ?? 120);
        $absentMinutes = intval($_POST['absent_minutes'] ?? 180);
        $allowedEarlyLeave = intval($_POST['allowed_early_leave'] ?? 10);
        $enableEarlyHalfDay = intval($_POST['enableEarlyHalfDay'] ?? 1);
        $earlyHalfDayMinutes = intval($_POST['early_half_day_minutes'] ?? 90);
        $earlyAbsentMinutes = intval($_POST['early_absent_minutes'] ?? 120);
        $missingCheckoutAction = intval($_POST['missing_checkout_action'] ?? 1);
        $lateCheckoutPolicy = intval($_POST['late_checkout_policy'] ?? 1);

        // Schedule arrays
        $startDates = $_POST['start_date'] ?? [];
        $endDates = $_POST['end_date'] ?? [];
        $startTimes = $_POST['start_time'] ?? [];
        $endTimes = $_POST['end_time'] ?? [];

        if (empty($name)) {
            $result = false;
            $msg = 'اسم الوردية مطلوب';
            break;
        }

        try {
            $connect_pdo->beginTransaction();

            if ($id > 0) {
                // Update shift
                $stmt = $connect_pdo->prepare("UPDATE tbshift SET ShiftName = ?, BranchID = ?, NumFootprint = ?, ShiftState = ?, LastUpdateDate = CURRENT_TIMESTAMP WHERE ShiftID = ?");
                $stmt->execute([$name, $branchId, $numFingerprints, $stopped, $id]);
                $shiftId = $id;

                // Update or insert shift settings
                $checkStmt = $connect_pdo->prepare("SELECT id FROM shift_setting WHERE shift_id = ?");
                $checkStmt->execute([$id]);
                if ($checkStmt->rowCount() > 0) {
                    $stmt = $connect_pdo->prepare("UPDATE shift_setting SET 
                    allowed_late_minutes = ?, enable_half_day = ?, half_day_minutes = ?, absent_minutes = ?,
                    allowed_early_leave = ?, enable_early_half_day = ?, early_half_day_minutes = ?, early_absent_minutes = ?,
                    missing_checkout_action = ?, late_checkout_policy = ?
                    WHERE shift_id = ?");
                    $stmt->execute([
                        $allowedLateMinutes,
                        $enableHalfDay,
                        $halfDayMinutes,
                        $absentMinutes,
                        $allowedEarlyLeave,
                        $enableEarlyHalfDay,
                        $earlyHalfDayMinutes,
                        $earlyAbsentMinutes,
                        $missingCheckoutAction,
                        $lateCheckoutPolicy,
                        $id
                    ]);
                } else {
                    $stmt = $connect_pdo->prepare("INSERT INTO shift_setting 
                    (shift_id, allowed_late_minutes, enable_half_day, half_day_minutes, absent_minutes,
                    allowed_early_leave, enable_early_half_day, early_half_day_minutes, early_absent_minutes,
                    missing_checkout_action, late_checkout_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id,
                        $allowedLateMinutes,
                        $enableHalfDay,
                        $halfDayMinutes,
                        $absentMinutes,
                        $allowedEarlyLeave,
                        $enableEarlyHalfDay,
                        $earlyHalfDayMinutes,
                        $earlyAbsentMinutes,
                        $missingCheckoutAction,
                        $lateCheckoutPolicy
                    ]);
                }

                // Delete old schedules and insert new ones
                $connect_pdo->prepare("DELETE FROM shifts_schedule WHERE shift_id = ?")->execute([$id]);

                $msg = 'تم تحديث الوردية بنجاح';
            } else {
                // Handle multiple branches for new shift
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $shiftId = 0;
                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tbshift (ShiftName, BranchID, NumFootprint, ShiftState, ShiftStartTime, ShiftEndTime, TotalworkHour, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$name, intval($bid), $numFingerprints, $stopped, $startTimes[0] ?? '08:00', $endTimes[0] ?? '16:00', '08:00', $userId]);
                    $shiftId = $connect_pdo->lastInsertId();

                    // Insert shift settings
                    $stmt = $connect_pdo->prepare("INSERT INTO shift_setting 
                    (shift_id, allowed_late_minutes, enable_half_day, half_day_minutes, absent_minutes,
                    allowed_early_leave, enable_early_half_day, early_half_day_minutes, early_absent_minutes,
                    missing_checkout_action, late_checkout_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $shiftId,
                        $allowedLateMinutes,
                        $enableHalfDay,
                        $halfDayMinutes,
                        $absentMinutes,
                        $allowedEarlyLeave,
                        $enableEarlyHalfDay,
                        $earlyHalfDayMinutes,
                        $earlyAbsentMinutes,
                        $missingCheckoutAction,
                        $lateCheckoutPolicy
                    ]);
                }
                $msg = 'تم إضافة الوردية بنجاح';
            }

            // Insert schedules
            if (!empty($startDates) && is_array($startDates)) {
                $scheduleStmt = $connect_pdo->prepare("INSERT INTO shifts_schedule (shift_id, start_date, end_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                for ($i = 0; $i < count($startDates); $i++) {
                    if (!empty($startDates[$i]) && !empty($endDates[$i])) {
                        $scheduleStmt->execute([
                            $shiftId,
                            $startDates[$i],
                            $endDates[$i],
                            $startTimes[$i] ?? '08:00',
                            $endTimes[$i] ?? '16:00'
                        ]);
                    }
                }
            }

            $connect_pdo->commit();
            $data = ['id' => $shiftId];
        } catch (PDOException $e) {
            $connect_pdo->rollBack();
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// INCENTIVE INFO — Get data for dynamic field selection
// ============================================================
    case 'incentive-info':
        $value = intval($_POST['value'] ?? 0);
        $branchId = intval($_POST['BranchID'] ?? 0);

        try {
            $data = [];
            $sql = '';
            $params = [];

            switch ($value) {
                case 1: // Employees
                    $sql = "SELECT UserID as id, CONCAT(FirstName, ' ', LastName) as name FROM tblusers WHERE BranchID = ? AND isemp = 1";
                    $params = [$branchId];
                    break;
                case 2: // Groups
                    $sql = "SELECT Id as id, Name as name FROM tblgroup WHERE BranchID = ?";
                    $params = [$branchId];
                    break;
                case 3: // Job Grades
                    $sql = "SELECT Id as id, Name as name FROM tbljobgrade WHERE BranchID = ?";
                    $params = [$branchId];
                    break;
                case 4: // Sections
                    $sql = "SELECT Id as id, Name as name FROM tblsection WHERE BranchID = ?";
                    $params = [$branchId];
                    break;
                case 5: // Job Titles
                    $sql = "SELECT Id as id, Name as name FROM tbljobtitle WHERE BranchID = ?";
                    $params = [$branchId];
                    break;
                default:
                    $result = false;
                    $msg = 'قيمة غير صالحة';
                    break;
            }

            if (!empty($sql)) {
                $stmt = $connect_pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $data[] = ['data' => $row];
                }
                $result = true;
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// RESIGNATION — ADD/UPDATE
// ============================================================
    case 'resignation-add':
    case 'resignation-add-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $dueDate = $_POST['DueDate'] ?? $_POST['due_date'] ?? $_POST['Due_date'] ?? null;
        $reason = trim($_POST['Reason'] ?? $_POST['reason'] ?? $_POST['Reson'] ?? '');
        $resignType = intval($_POST['type'] ?? 1); // 1=resign, 2=dismiss
        $isDraft = intval($_POST['isdraft'] ?? $_POST['Draft'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblresignation SET 
                UserID = ?, BranchID = ?, DueDate = ?, Reason = ?, type = ?, Draft = ?, Status = ?
                WHERE Id = ?");
                $stmt->execute([$empId, $branchId, $dueDate, $reason, $resignType, $isDraft, ($isDraft ? null : 0), $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث طلب الاستقالة بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblresignation 
                (UserID, BranchID, DueDate, Reason, type, Draft, Status, created_by, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$empId, $branchId, $dueDate, $reason, $resignType, $isDraft, ($isDraft ? null : 0), $userId]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة طلب الاستقالة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// DISMISSAL â€” ADD/UPDATE
// ============================================================
    case 'dismissal-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $dueDate = $_POST['DueDate'] ?? $_POST['due_date'] ?? null;
        $reason = trim($_POST['Reason'] ?? $_POST['reason'] ?? '');
        $isDraft = intval($_POST['isdraft'] ?? $_POST['Draft'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblresignation SET 
                UserID = ?, BranchID = ?, DueDate = ?, Reason = ?, type = 2, Draft = ?, Status = ?
                WHERE Id = ?");
                $stmt->execute([$empId, $branchId, $dueDate, $reason, $isDraft, ($isDraft ? null : 0), $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث قرار الفصل بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblresignation 
                (UserID, BranchID, DueDate, Reason, type, Draft, Status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, 2, ?, ?, ?, CURDATE())");
                $stmt->execute([$empId, $branchId, $dueDate, $reason, $isDraft, ($isDraft ? null : 0), $userId]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة قرار الفصل بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    case 'dismissal-upload':
    case 'resignation-upload':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        if (!empty($_FILES['file']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/resignations/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            $fileName = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                try {
                    $stmt = $connect_pdo->prepare("UPDATE tblresignation SET path = ? WHERE Id = ?");
                    $stmt->execute(['uploads/resignations/' . $fileName, $id]);
                    $msg = 'تم رفع الملف بنجاح';
                } catch (PDOException $e) {
                    $result = false;
                    $msg = 'خطأ في حفظ الملف';
                }
            } else {
                $result = false;
                $msg = 'فشل رفع الملف';
            }
        } else {
            $result = false;
            $msg = 'لم يتم اختيار ملف';
        }
        break;

    // ============================================================
// FINGER FORGET â€” ADD
// ============================================================
    case 'finger-forget-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? $_POST['BranchID'] ?? 0);
        $forgetDate = $_POST['forget_date'] ?? $_POST['Date'] ?? date('Y-m-d');
        $forgetTime = $_POST['forget_time'] ?? $_POST['Time'] ?? null;
        $forgetType = intval($_POST['type'] ?? 1); // 1=enter, 2=out
        $reason = trim($_POST['reason'] ?? $_POST['Reason'] ?? '');
        $isDraft = intval($_POST['isdraft'] ?? $_POST['Draft'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE order_finger_add SET 
                UserID = ?, BranchID = ?, Date = ?, Time = ?, type = ?, Reason = ?, Draft = ?, status = ?
                WHERE Id = ?");
                $stmt->execute([$empId, $branchId, $forgetDate, $forgetTime, $forgetType, $reason, $isDraft, ($isDraft ? null : 0), $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث طلب البصمة المنسية بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO order_finger_add 
                (UserID, BranchID, Date, Time, type, Reason, Draft, status, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$empId, $branchId, $forgetDate, $forgetTime, $forgetType, $reason, $isDraft, ($isDraft ? null : 0), $userId]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة طلب البصمة المنسية بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// CHANGE MANAGER â€” UPDATE
// ============================================================
    case 'change-manager-emp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $managerId = intval($_POST['manager_id'] ?? $_POST['manager'] ?? 0);

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("UPDATE tblusers SET manager = ? WHERE UserID = ?");
            $stmt->execute([$managerId ?: null, $empId]);
            $msg = 'تم تغيير المدير المباشر بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// ATTENDANCE MANUAL ENTRY
// ============================================================
    case 'attendancet-emp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $empId = intval($_POST['emp_id'] ?? $_POST['EmpID'] ?? 0);
        $attDate = $_POST['Date'] ?? $_POST['att_date'] ?? date('Y-m-d');
        $attTime = $_POST['Time'] ?? $_POST['att_time'] ?? date('H:i:s');
        $attType = intval($_POST['Type'] ?? $_POST['type'] ?? 1); // 1=enter, 2=out
        $userId = $_SESSION['user']['id'] ?? $user;

        if (empty($empId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("INSERT INTO attendancet (EmpID, Date, Time, Type, who_add, method) VALUES (?, ?, ?, ?, ?, 'manual')");
            $stmt->execute([$empId, $attDate, $attTime, $attType, $userId]);
            $newId = $connect_pdo->lastInsertId();
            $data = ['id' => $newId];
            $msg = 'تم تسجيل الحضور بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// EMPLOYER/EMPLOYEE â€” ADD/UPDATE (Complex form with file uploads)
// ============================================================
    case 'employer-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['user_id'] ?? 0);
        $branchId = intval($_POST['emp_branch'] ?? $_POST['BranchID'] ?? $_POST['branchs_list'] ?? 0);
        $firstName = trim($_POST['emp_name'] ?? '');
        $secondName = trim($_POST['emp_name_2'] ?? $_POST['emp_name2'] ?? '');
        $lastName = trim($_POST['emp_name_3'] ?? $_POST['emp_lname'] ?? '');

        // تعديل الإيميل والهاتف: إذا كان فارغاً اجعله NULL
        $email = trim($_POST['emp_email'] ?? $_POST['email'] ?? '');
        $email = ($email === '') ? null : $email;
        $phone = trim($_POST['emp_phone'] ?? $_POST['mobile'] ?? '');
        $phone = ($phone === '') ? null : $phone;

        $otherPhone = trim($_POST['emp_phone_2'] ?? $_POST['emp_OtherPhone'] ?? '');
        $note = trim($_POST['emp_note'] ?? '');
        $password = $_POST['user_pass'] ?? '';
        $isDisabled = isset($_POST['desable_user']) ? 1 : 0;
        $userGroupId = intval($_POST['user_role'] ?? 0);

        // --- تحديد إذا كان موظف أم متقدم وحالة المتقدم ---
        $isEmp = intval($_POST['user_role_type'] ?? 1); // 1 = موظف, 2 = متقدم
        $applicantStatus = intval($_POST['applicant_status'] ?? 0); // 0=انتظار, 1=مقبول, 2=مرفوض

        $normalizeMultiSelect = static function ($value): ?string {
            if (is_array($value)) {
                $clean = array_values(array_filter(array_map('trim', array_map('strval', $value)), static function ($item) {
                    return $item !== '';
                }));
                return !empty($clean) ? implode(',', array_unique($clean)) : null;
            }
            if ($value === null)
                return null;
            $value = trim((string) $value);
            return $value !== '' ? $value : null;
        };
        $normalizeDate = static function ($value): ?string {
            if ($value === null) {
                return null;
            }
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        };

        // Contract/Renewal data
        $sectionId = intval($_POST['user_section'] ?? 0);
        $groupId = intval($_POST['user_group'] ?? $_POST['user_group_'] ?? 0);
        $gradeId = intval($_POST['user_grade'] ?? 0);
        $jobtitleId = intval($_POST['user_jobtitle'] ?? 0);
        $typeId = intval($_POST['emp_type'] ?? $_POST['user_type'] ?? 0);
        $shiftId = $normalizeMultiSelect($_POST['emp_shift'] ?? $_POST['user_shift'] ?? null);
        $fingerId = $normalizeMultiSelect($_POST['emp_finger'] ?? $_POST['user_finger'] ?? null);
        $salary = $_POST['emp_salary'] ?? '';
        $currency = $_POST['currency'] ?? 'SAR';
        $contractStart = $normalizeDate($_POST['emp_contract_S'] ?? null);
        $contractEnd = $normalizeDate($_POST['emp_contract_F'] ?? null);
        $insuranceId = $normalizeMultiSelect($_POST['emp_insurance'] ?? $_POST['user_insuance'] ?? null);
        $bankName = trim($_POST['back'] ?? '');
        $bankAccount = trim($_POST['account_number'] ?? '');
        $managerId = intval($_POST['emp_manager'] ?? $_POST['user_manager'] ?? 0);
        $relatedTo = intval($_POST['emp_related'] ?? $_POST['user_related_to'] ?? 0);

        // Personal data
        $sex = intval($_POST['emp_sex'] ?? $_POST['emp_Sex'] ?? 0);
        $maritalStatus = intval($_POST['emp_marital'] ?? $_POST['marital_status'] ?? 0);
        $address = trim($_POST['emp_address'] ?? '');
        $healthCondition = trim($_POST['emp_health'] ?? $_POST['state_human'] ?? '');

        // ID documents (Texts & Dates)
        $idH = trim($_POST['emp_Id'] ?? '');
        $idHStart = $normalizeDate($_POST['ID_Emp_date_S'] ?? null);
        $idHEnd = $normalizeDate($_POST['ID_Emp_date_F'] ?? null);
        $idLicense = trim($_POST['emp_IDD'] ?? '');
        $idLicenseStart = $normalizeDate($_POST['emp_IDD_Date_S'] ?? null);
        $idLicenseEnd = $normalizeDate($_POST['emp_IDD_Date_F'] ?? null);
        $idPassport = trim($_POST['emp_passport'] ?? $_POST['emp_passportID'] ?? '');
        $idPassportStart = $normalizeDate($_POST['emp_passport_Date_S'] ?? $_POST['emp_Passport_ID_Date_S'] ?? null);
        $idPassportEnd = $normalizeDate($_POST['emp_passport_Date_F'] ?? $_POST['emp_passport_Cer_ID_Date_F'] ?? null);
        $idHealth = trim($_POST['emp_health_id'] ?? $_POST['emp_CerID'] ?? '');
        $idHealthStart = $normalizeDate($_POST['emp_health_Date_S'] ?? $_POST['emp_Cer_ID_Date_S'] ?? null);
        $idHealthEnd = $normalizeDate($_POST['emp_health_Date_F'] ?? $_POST['emp_Cer_ID_Date_F'] ?? null);

        $createdBy = $_SESSION['user']['UserID'] ?? $user;

        if (empty($firstName)) {
            $result = false;
            $msg = 'اسم الموظف مطلوب';
            break;
        }
        if (empty($branchId)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = false;
            $msg = 'صيغة البريد الإلكتروني غير صحيحة';
            break;
        }
        $duplicateChecks = [
            ['value' => $email, 'column' => 'UserEmail', 'message' => 'البريد الإلكتروني مسجل بالفعل'],
            ['value' => $phone, 'column' => 'Phone', 'message' => 'رقم الهاتف مسجل بالفعل'],
            ['value' => $idH, 'column' => 'Id_h', 'message' => 'رقم الهوية مسجل بالفعل'],
        ];
        foreach ($duplicateChecks as $check) {
            if ($check['value'] === null || $check['value'] === '') {
                continue;
            }
            $dupStmt = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE {$check['column']} = ? AND UserID <> ? LIMIT 1");
            $dupStmt->execute([$check['value'], $id]);
            if ($dupStmt->fetchColumn()) {
                $result = false;
                $msg = $check['message'];
                break 2;
            }
        }

        // --- التحقق من نطاق الراتب الخاص بالقسم (فقط إذا كان موظفاً) ---
        $salaryClean = str_replace([',', ' '], '', $salary);
        $salaryFloat = floatval($salaryClean);

        if ($isEmp == 1 && $sectionId > 0 && $salaryFloat > 0) {
            try {
                require_once __DIR__ . '/../classes/EvaluationManager.php';
                $evalManager = new EvaluationManager($connect_pdo);
                $allRanges = $evalManager->getAllSalaryRanges();

                $matchedRange = null;
                $bestScore = -1;
                $currentTimestamp = strtotime(date('Y-m-d'));

                if (!empty($allRanges) && is_array($allRanges)) {
                    foreach ($allRanges as $range) {
                        if ($range['section_id'] == $sectionId) {
                            $rGradeId = intval($range['grade_id'] ?? 0);
                            $rJobTitleId = intval($range['job_title_id'] ?? 0);

                            $gradeMatch = ($rGradeId == 0 || $rGradeId == $gradeId);
                            $jobTitleMatch = ($rJobTitleId == 0 || $rJobTitleId == $jobtitleId);

                            if ($gradeMatch && $jobTitleMatch) {
                                $effDateStr = $range['effective_date'] ?? date('Y-m-d');
                                $effDate = strtotime($effDateStr);

                                if ($effDate <= $currentTimestamp) {
                                    $score = 0;
                                    if ($rGradeId > 0 && $rGradeId == $gradeId)
                                        $score += 10;
                                    if ($rJobTitleId > 0 && $rJobTitleId == $jobtitleId)
                                        $score += 10;
                                    $score += ($effDate / 10000000000);

                                    if ($score > $bestScore) {
                                        $bestScore = $score;
                                        $matchedRange = $range;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($matchedRange) {
                    $minSal = floatval($matchedRange['min_salary']);
                    $maxSal = floatval($matchedRange['max_salary']);

                    if ($salaryFloat < $minSal || $salaryFloat > $maxSal) {
                        $result = false;
                        $msg = "عذراً، الراتب المدخل (" . number_format($salaryFloat, 2) . ") خارج نطاق الراتب المسموح به لهذا القسم. (الحد الأدنى: " . number_format($minSal, 2) . " - الحد الأقصى: " . number_format($maxSal, 2) . ")";
                        break;
                    }
                }
            } catch (Exception $e) {
                $result = false;
                $msg = 'خطأ في التحقق من نطاق الراتب: ' . $e->getMessage();
                break;
            }
        }
        // ----------------------------------------------------------------------

        try {
            $connect_pdo->beginTransaction();

            if ($id > 0) {
                // Update existing employee/applicant
                $stmt = $connect_pdo->prepare("UPDATE tblusers SET 
                BranchID = ?, FirstName = ?, SecondName = ?, LastName = ?, UserEmail = ?, 
                Phone = ?, ohter_phone = ?, Note = ?, IsDisabled = ?, UserGroupID = ?, isemp = ?, applicant_status = ?,
                user_insurance = ?, user_bank_name = ?, user_account_bank = ?, manager = ?, related_to = ?,
                Sex = ?, marital_status = ?, user_address = ?, HealthCondition = ?,
                Id_h = ?, start_date_h = ?, end_date_h = ?,
                Id_license = ?, start_date_license = ?, end_date_license = ?,
                Id_passport = ?, start_date_passport = ?, end_date_passport = ?,
                Id_health = ?, start_date_health = ?, end_date_health = ?,
                UpdateBy = ?, LastUpdate = NOW()
                WHERE UserID = ?");
                $stmt->execute([
                    $branchId,
                    $firstName,
                    $secondName,
                    $lastName,
                    $email,
                    $phone,
                    $otherPhone,
                    $note,
                    $isDisabled,
                    $userGroupId ?: null,
                    $isEmp,
                    $applicantStatus,
                    $insuranceId,
                    $bankName,
                    $bankAccount,
                    $managerId ?: null,
                    $relatedTo ?: null,
                    $sex,
                    $maritalStatus,
                    $address,
                    $healthCondition,
                    $idH,
                    $idHStart,
                    $idHEnd,
                    $idLicense,
                    $idLicenseStart,
                    $idLicenseEnd,
                    $idPassport,
                    $idPassportStart,
                    $idPassportEnd,
                    $idHealth,
                    $idHealthStart,
                    $idHealthEnd,
                    $createdBy,
                    $id
                ]);
                $empId = $id;

                // Update password if provided
                if (!empty($password)) {
                    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $connect_pdo->prepare("UPDATE tblusers SET Password = ? WHERE UserID = ?");
                    $stmt->execute([$hashedPass, $empId]);
                }
            } else {
                // Insert new employee/applicant
                $hashedPass = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

                $stmt = $connect_pdo->prepare("INSERT INTO tblusers 
                (BranchID, FirstName, SecondName, LastName, UserEmail, Password, Phone, ohter_phone, Note, 
                IsDisabled, UserGroupID, isemp, applicant_status, user_insurance, user_bank_name, user_account_bank, manager, related_to,
                Sex, marital_status, user_address, HealthCondition,
                Id_h, start_date_h, end_date_h, Id_license, start_date_license, end_date_license,
                Id_passport, start_date_passport, end_date_passport, Id_health, start_date_health, end_date_health,
                CreatedDate, CreatedUser)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
                $stmt->execute([
                    $branchId,
                    $firstName,
                    $secondName,
                    $lastName,
                    $email,
                    $hashedPass,
                    $phone,
                    $otherPhone,
                    $note,
                    $isDisabled,
                    $userGroupId ?: null,
                    $isEmp,
                    $applicantStatus,
                    $insuranceId,
                    $bankName,
                    $bankAccount,
                    $managerId ?: null,
                    $relatedTo ?: null,
                    $sex,
                    $maritalStatus,
                    $address,
                    $healthCondition,
                    $idH,
                    $idHStart,
                    $idHEnd,
                    $idLicense,
                    $idLicenseStart,
                    $idLicenseEnd,
                    $idPassport,
                    $idPassportStart,
                    $idPassportEnd,
                    $idHealth,
                    $idHealthStart,
                    $idHealthEnd,
                    $createdBy
                ]);
                $empId = $connect_pdo->lastInsertId();
            }

            // Create/Update contract renewal record (فقط للموظفين)
            if ($isEmp == 1 && $empId > 0 && ($sectionId || $jobtitleId || $salary)) {
                $stmt = $connect_pdo->prepare("INSERT INTO tblremewal 
                (UserID, BranchID, SectionID, GroupID, GradeID, jobtitleID, TypeID, shiftID, fingerID, 
                Salary, Currency, new_s_date, new_e_date, state, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, CURDATE())");
                $stmt->execute([
                    $empId,
                    $branchId,
                    $sectionId ?: null,
                    $groupId ?: null,
                    $gradeId ?: null,
                    $jobtitleId ?: null,
                    $typeId ?: null,
                    $shiftId ?: null,
                    $fingerId ?: null,
                    $salary,
                    $currency,
                    $contractStart,
                    $contractEnd,
                    $createdBy
                ]);
                $renewalId = $connect_pdo->lastInsertId();

                $stmt = $connect_pdo->prepare("UPDATE tblusers SET lastversion = ? WHERE UserID = ?");
                $stmt->execute([$renewalId, $empId]);
            }

            // ==============================================================================
            // 🚀 رفع المرفقات والملفات وحفظ المسارات في قاعدة البيانات
            // ==============================================================================

            $fileUpdates = [];
            $fileParams = [];

            // دالة مساعدة لرفع الملفات
            function uploadEmployeeFile($inputName, $dirName, $prefix)
            {
                if (!empty($_FILES[$inputName]['name'])) {
                    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Invalid uploaded file.');
                    }
                    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
                    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        throw new RuntimeException('Invalid uploaded file.');
                    }
                    $uploadDir = __DIR__ . '/../uploads/' . $dirName . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid($prefix) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . $fileName)) {
                        return 'uploads/' . $dirName . '/' . $fileName;
                    }
                    throw new RuntimeException('Invalid uploaded file.');
                }
                return null;
            }

            // 1. الصورة الشخصية
            $photo = uploadEmployeeFile('user_photo', 'basics', 'img_');
            if ($photo) {
                $fileUpdates[] = "Photo = ?";
                $fileParams[] = $photo;
            }

            // 2. الهوية والفحص الطبي (التبويب المشترك)
            $path_h = uploadEmployeeFile('ID_file', 'basics', 'id_');
            if ($path_h) {
                $fileUpdates[] = "path_h = ?";
                $fileParams[] = $path_h;
            }

            $path_license = uploadEmployeeFile('IDD_file', 'basics', 'lic_');
            if ($path_license) {
                $fileUpdates[] = "path_license = ?";
                $fileParams[] = $path_license;
            }

            $path_passport = uploadEmployeeFile('Passport_ID_file', 'basics', 'pass_');
            if ($path_passport) {
                $fileUpdates[] = "path_passport = ?";
                $fileParams[] = $path_passport;
            }

            $path_health = uploadEmployeeFile('Cer_ID_file', 'basics', 'hlth_');
            if ($path_health) {
                $fileUpdates[] = "path_health = ?";
                $fileParams[] = $path_health;
            }

            // 3. مرفقات المتقدم الخاصة
            $path_residency = uploadEmployeeFile('file_residency', 'applicants', 'res_');
            if ($path_residency) {
                $fileUpdates[] = "path_residency = ?";
                $fileParams[] = $path_residency;
            }

            $path_qualifications = uploadEmployeeFile('file_qualifications', 'applicants', 'qual_');
            if ($path_qualifications) {
                $fileUpdates[] = "path_qualifications = ?";
                $fileParams[] = $path_qualifications;
            }

            $path_experience = uploadEmployeeFile('file_experience', 'applicants', 'exp_');
            if ($path_experience) {
                $fileUpdates[] = "path_experience = ?";
                $fileParams[] = $path_experience;
            }

            $path_service_cert = uploadEmployeeFile('file_service_cert', 'applicants', 'srv_');
            if ($path_service_cert) {
                $fileUpdates[] = "path_service_cert = ?";
                $fileParams[] = $path_service_cert;
            }

            $path_police_clearance = uploadEmployeeFile('file_police_clearance', 'applicants', 'pol_');
            if ($path_police_clearance) {
                $fileUpdates[] = "path_police_clearance = ?";
                $fileParams[] = $path_police_clearance;
            }

            // إذا تم رفع أي ملف، قم بتحديث قاعدة البيانات
            if (!empty($fileUpdates)) {
                $fileParams[] = $empId;
                $sql = "UPDATE tblusers SET " . implode(', ', $fileUpdates) . " WHERE UserID = ?";
                $stmt = $connect_pdo->prepare($sql);
                $stmt->execute($fileParams);
            }
            // ==============================================================================

            $connect_pdo->commit();
            $data = ['emp_id' => $empId];
            $msg = $id > 0 ? 'تم تحديث البيانات بنجاح' : 'تم إضافة السجل بنجاح';

        } catch (RuntimeException $e) {
            $connect_pdo->rollBack();
            error_log('employer-add validation error: ' . $e->getMessage());
            $result = false;
            $msg = $e->getMessage() === 'Invalid uploaded file.' ? 'الملف المرفوع غير صالح' : 'حدث خطأ أثناء حفظ البيانات. يرجى المحاولة مرة أخرى';
        } catch (PDOException $e) {
            $connect_pdo->rollBack();
            $result = false;
            
            error_log('employer-add DB error: ' . $e->getMessage());
            // Convert MySQL errors to user-friendly messages
            $errorMessage = $e->getMessage();
            
            // Check for duplicate entry errors
            if (strpos($errorMessage, 'Duplicate entry') !== false) {
                if (strpos($errorMessage, 'UserEmail') !== false || strpos($errorMessage, 'email') !== false) {
                    $msg = 'عذراً، هذا البريد الإلكتروني مسجل بالفعل في النظام';
                } elseif (strpos($errorMessage, 'Phone') !== false) {
                    $msg = 'عذراً، رقم الهاتف مسجل بالفعل في النظام';
                } else {
                    $msg = 'عذراً، هناك بيانات مكررة في النظام';
                }
            }
            // Check for foreign key constraint errors
            elseif (strpos($errorMessage, 'foreign key constraint') !== false) {
                $msg = 'عذراً، لا يمكن الحفظ بسبب ارتباط البيانات بسجلات أخرى';
            }
            // Check for data too long errors
            elseif (strpos($errorMessage, 'Data too long') !== false) {
                $msg = 'عذراً، بعض البيانات طويلة جداً';
            }
            // Generic database error
            else {
                $msg = 'حدث خطأ أثناء حفظ البيانات. يرجى المحاولة مرة أخرى';
            }
        }
        break;
    case 'applicants-list':
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $name = $_POST['name'] ?? '';
        $status = $_POST['status'] ?? '';

        // isemp = 2 means Applicant
        $where = "WHERE u.isemp = 2";
        $params = [];

        if (!empty($name)) {
            $where .= " AND (u.FirstName LIKE :n OR u.LastName LIKE :n OR u.UserEmail LIKE :n OR u.Phone LIKE :n)";
            $params[':n'] = "%$name%";
        }
        if ($status !== '') {
            $where .= " AND u.applicant_status = :st";
            $params[':st'] = (int) $status;
        }

        // Count total
        $totalQ = "SELECT COUNT(*) FROM tblusers u WHERE u.isemp = 2";
        $totalS = $connect_pdo->prepare($totalQ);
        $totalS->execute();
        $recordsTotal = (int) $totalS->fetchColumn();

        // Count filtered
        $cntQ = "SELECT COUNT(*) FROM tblusers u $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $recordsFiltered = (int) $cntS->fetchColumn();

        // Get data
        $sql = "SELECT u.UserID, u.FirstName, u.LastName, u.UserEmail, u.Phone, u.applicant_status,
                       u.path_residency, u.path_qualifications, u.path_experience, u.path_service_cert, u.path_police_clearance
                FROM tblusers u
                $where ORDER BY u.UserID DESC LIMIT $start, $length";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $empName = trim(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? ''));

            // Status Badge
            $st = (int) $r['applicant_status'];
            if ($st === 1)
                $statusBadge = '<span class="badge badge-success">مقبول (موظف)</span>';
            elseif ($st === 2)
                $statusBadge = '<span class="badge badge-danger">مرفوض</span>';
            else
                $statusBadge = '<span class="badge badge-warning">قيد الانتظار</span>';

            // Attachments (target="_blank" to open in new page)
            $attHtml = '<div class="d-flex justify-content-center flex-wrap" style="gap:5px;">';
            if (!empty($r['path_residency']))
                $attHtml .= '<a href="' . $r['path_residency'] . '" target="_blank" class="btn btn-xs btn-outline-primary" title="الإقامة"><i class="fas fa-id-card"></i> إقامة</a>';
            if (!empty($r['path_qualifications']))
                $attHtml .= '<a href="' . $r['path_qualifications'] . '" target="_blank" class="btn btn-xs btn-outline-info" title="المؤهلات"><i class="fas fa-graduation-cap"></i> مؤهل</a>';
            if (!empty($r['path_experience']))
                $attHtml .= '<a href="' . $r['path_experience'] . '" target="_blank" class="btn btn-xs btn-outline-success" title="الخبرات"><i class="fas fa-briefcase"></i> خبرات</a>';
            if (!empty($r['path_service_cert']))
                $attHtml .= '<a href="' . $r['path_service_cert'] . '" target="_blank" class="btn btn-xs btn-outline-secondary" title="شهادة خدمة"><i class="fas fa-certificate"></i> خدمة</a>';
            if (!empty($r['path_police_clearance']))
                $attHtml .= '<a href="' . $r['path_police_clearance'] . '" target="_blank" class="btn btn-xs btn-outline-dark" title="خلو سوابق"><i class="fas fa-shield-alt"></i> سوابق</a>';
            $attHtml .= '</div>';

            if ($attHtml === '<div class="d-flex justify-content-center flex-wrap" style="gap:5px;"></div>') {
                $attHtml = '<span class="text-muted"><i class="fas fa-exclamation-circle"></i> لا توجد مرفقات</span>';
            }

            // Actions
            $actions = '<a href="employer-add?id=' . $r['UserID'] . '" class="btn btn-sm btn-info m-1" title="عرض التفاصيل"><i class="fas fa-eye"></i></a>';

            // Show Approve/Reject only if Pending
            if ($st === 0) {
                // Approve redirects to employer-add and triggers the employee flow
                $actions .= '<a href="employer-add?id=' . $r['UserID'] . '&approve_flow=1" class="btn btn-sm btn-success m-1" title="قبول واستكمال التوظيف"><i class="fas fa-check"></i> قبول</a>';
                // Reject is an Ajax call
                $actions .= '<button type="button" class="btn btn-sm btn-danger reject-btn m-1" data-id="' . $r['UserID'] . '" title="رفض"><i class="fas fa-times"></i> رفض</button>';
            }

            $data[] = [
                $r['UserID'],
                '<strong>' . $empName . '</strong>',
                $r['Phone'] . '<br><small class="text-muted">' . $r['UserEmail'] . '</small>',
                $attHtml,
                $statusBadge,
                $actions
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ============================================================
    // APPLICANTS REJECT — AJAX Action
    // ============================================================
    case 'applicant-reject':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Update applicant_status to 2 (Rejected)
                $connect_pdo->prepare("UPDATE tblusers SET applicant_status = 2 WHERE UserID = ?")->execute([$id]);
                $result = true;
                $msg = 'تم رفض المتقدم بنجاح';
            } catch (PDOException $e) {
                $result = false;
                $msg = 'حدث خطأ في قاعدة البيانات';
            }
        } else {
            $result = false;
            $msg = 'معرف غير صالح';
        }
        break;
    // ============================================================
// USER CERTIFICATES â€” ADD/UPDATE
// ============================================================
    case 'user-certifacte':
    case 'UserCertifcate-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $userId = intval($_POST['user_id'] ?? $_POST['UserID'] ?? 0);
        $name = trim($_POST['name'] ?? $_POST['certificate_name'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $issueDate = $_POST['issue_date'] ?? null;
        $expiryDate = $_POST['expiry_date'] ?? null;
        $createdBy = $_SESSION['user']['UserID'] ?? $user;

        if (empty($userId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }
        if (empty($name)) {
            $result = false;
            $msg = 'اسم الشهادة مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblcertificates SET 
                UserID = ?, Name = ?, Institution = ?, IssueDate = ?, ExpiryDate = ?
                WHERE Id = ?");
                $stmt->execute([$userId, $name, $institution, $issueDate, $expiryDate, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث الشهادة بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblcertificates 
                (UserID, Name, Institution, IssueDate, ExpiryDate, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$userId, $name, $institution, $issueDate, $expiryDate, $createdBy]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة الشهادة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// USER EXPERIENCE â€” ADD/UPDATE
// ============================================================
    case 'user-experince':
    case 'UserExperince-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $userId = intval($_POST['user_id'] ?? $_POST['UserID'] ?? 0);
        $company = trim($_POST['company'] ?? $_POST['company_name'] ?? '');
        $position = trim($_POST['position'] ?? $_POST['job_title'] ?? '');
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $createdBy = $_SESSION['user']['UserID'] ?? $user;

        if (empty($userId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }
        if (empty($company)) {
            $result = false;
            $msg = 'اسم الشركة مطلوب';
            break;
        }

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tblexperience SET 
                UserID = ?, Company = ?, Position = ?, StartDate = ?, EndDate = ?, Description = ?
                WHERE Id = ?");
                $stmt->execute([$userId, $company, $position, $startDate, $endDate, $description, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث الخبرة بنجاح';
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO tblexperience 
                (UserID, Company, Position, StartDate, EndDate, Description, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$userId, $company, $position, $startDate, $endDate, $description, $createdBy]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة الخبرة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// REMOVE EXPERIENCE/CERTIFICATE
// ============================================================
    case 'remove_Exper_cer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';

        if ($id <= 0) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        try {
            if ($type === 'certificate') {
                $stmt = $connect_pdo->prepare("DELETE FROM tblcertificates WHERE Id = ?");
            } else {
                $stmt = $connect_pdo->prepare("DELETE FROM tblexperience WHERE Id = ?");
            }
            $stmt->execute([$id]);
            $msg = 'تم الحذف بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات';
        }
        break;

    // ============================================================
// SALARY ACCOUNT SETTINGS â€” SAVE
// ============================================================
    case 'save-setting-account-salary':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $branchId = intval($_POST['BranchID'] ?? $_POST['branchs_list'] ?? 0);
        $salaryAccount = $_POST['salary_account'] ?? '';
        $advanceAccount = $_POST['advance_account'] ?? '';
        $deductionAccount = $_POST['deduction_account'] ?? '';
        $incentiveAccount = $_POST['incentive_account'] ?? '';
        $benefitAccount = $_POST['benefit_account'] ?? '';

        try {
            // Check if settings exist
            $stmt = $connect_pdo->prepare("SELECT Id FROM setting_account_salary WHERE BranchID = ?");
            $stmt->execute([$branchId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $connect_pdo->prepare("UPDATE setting_account_salary SET 
                salary_account = ?, advance_account = ?, deduction_account = ?, 
                incentive_account = ?, benefit_account = ?
                WHERE BranchID = ?");
                $stmt->execute([$salaryAccount, $advanceAccount, $deductionAccount, $incentiveAccount, $benefitAccount, $branchId]);
            } else {
                $stmt = $connect_pdo->prepare("INSERT INTO setting_account_salary 
                (BranchID, salary_account, advance_account, deduction_account, incentive_account, benefit_account)
                VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branchId, $salaryAccount, $advanceAccount, $deductionAccount, $incentiveAccount, $benefitAccount]);
            }
            $msg = 'تم حفظ إعدادات الحسابات بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// PROMOTION â€” ADD/UPDATE (Employee promotion with new contract)
// ============================================================
    case 'promotion-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $addId = intval($_POST['add'] ?? 0);  // Original renewal ID for reference
        $editId = intval($_POST['edit'] ?? 0); // If editing existing promotion
        $userId = intval($_POST['UserID'] ?? 0);
        $branchId = intval($_POST['branchs_list'] ?? 0);
        $sectionId = intval($_POST['user_section'] ?? 0);
        $jobtitleId = intval($_POST['user_jobtitle'] ?? 0);
        $groupId = intval($_POST['user_group_'] ?? 0);
        $gradeId = intval($_POST['user_grade'] ?? 0);
        $typeId = intval($_POST['user_type'] ?? 0);
        $shiftIds = $_POST['user_shiftt'] ?? $_POST['user_shift'] ?? '';
        if (is_array($shiftIds)) {
            $shiftIds = implode(',', array_filter(array_map('strval', $shiftIds), 'strlen'));
        }
        $fingerIds = $_POST['user_fingerr'] ?? $_POST['user_finger'] ?? '';
        if (is_array($fingerIds)) {
            $fingerIds = implode(',', array_filter(array_map('strval', $fingerIds), 'strlen'));
        }
        $salary = $_POST['emp_salary'] ?? '';
        $currency = $_POST['currency'] ?? 'SAR';
        $contractStart = $_POST['emp_contract_S'] ?? null;
        $contractEnd = $_POST['emp_contract_F'] ?? null;
        $dayBeforeGo = $_POST['day_before_go'] ?? null;
        $reason = trim($_POST['Reson'] ?? '');
        $state = intval($_POST['state'] ?? 0);
        $createdBy = $_SESSION['user']['UserID'] ?? $user;

        if (empty($userId)) {
            $result = false;
            $msg = 'الموظف مطلوب';
            break;
        }
        if (empty($branchId)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }

        try {
            if ($editId > 0) {
                // Update existing promotion record
                $stmt = $connect_pdo->prepare("UPDATE tblremewal SET 
                BranchID = ?, SectionID = ?, GroupID = ?, GradeID = ?, jobtitleID = ?, TypeID = ?,
                shiftID = ?, fingerID = ?, Salary = ?, Currency = ?, new_s_date = ?, new_e_date = ?,
                day = ?, Reason = ?, state = ?
                WHERE Id = ?");
                $stmt->execute([
                    $branchId,
                    $sectionId ?: null,
                    $groupId ?: null,
                    $gradeId ?: null,
                    $jobtitleId ?: null,
                    $typeId ?: null,
                    $shiftIds ?: null,
                    $fingerIds ?: null,
                    $salary,
                    $currency,
                    $contractStart,
                    $contractEnd,
                    $dayBeforeGo,
                    $reason,
                    $state,
                    $editId
                ]);
                $data = ['id' => $editId, 'edit' => true];
                $msg = 'تم تحديث الترقية بنجاح';
            } else {
                // Create new promotion record (state=2 for promotion)
                $stmt = $connect_pdo->prepare("INSERT INTO tblremewal 
                (UserID, BranchID, SectionID, GroupID, GradeID, jobtitleID, TypeID, shiftID, fingerID, 
                Salary, Currency, new_s_date, new_e_date, day, Reason, state, CreatedBy, CreatedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2, ?, CURDATE())");
                $stmt->execute([
                    $userId,
                    $branchId,
                    $sectionId ?: null,
                    $groupId ?: null,
                    $gradeId ?: null,
                    $jobtitleId ?: null,
                    $typeId ?: null,
                    $shiftIds ?: null,
                    $fingerIds ?: null,
                    $salary,
                    $currency,
                    $contractStart,
                    $contractEnd,
                    $dayBeforeGo,
                    $reason,
                    $createdBy
                ]);
                $newId = $connect_pdo->lastInsertId();
                $data = ['id' => $newId];
                $msg = 'تم إضافة الترقية بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT DEVICE â€” ADD/UPDATE (API Documentation Compliant)
// ============================================================
    case 'fingerprint-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['devicetname'] ?? '');
        $branchIds = $_POST['branchs_list'] ?? [];
        $state = intval($_POST['decvicestate'] ?? 1);
        $vendor = trim($_POST['devicetype'] ?? '');
        $serialNumber = trim($_POST['deviceserialnumber'] ?? '');
        $ip = trim($_POST['ip'] ?? '');
        $port = intval(trim($_POST['port'] ?? '4370'));
        $createdBy = $_SESSION['user']['id'] ?? $user;

        if (empty($name)) {
            $result = false;
            $msg = 'اسم الجهاز مطلوب';
            break;
        }
        if (empty($branchIds)) {
            $result = false;
            $msg = 'الفرع مطلوب';
            break;
        }

        $state = $state > 0 ? $state : 1;
        $branchId = is_array($branchIds) ? intval($branchIds[0]) : intval($branchIds);

        try {
            if ($id > 0) {
                $stmt = $connect_pdo->prepare("UPDATE tbfingerprint SET 
                FingerprintName = ?, BranchID = ?, FingerprintType = ?, FingerprintSerailnumber = ?,
                ip = ?, port = ?, FingerprintState = ?, lastUpdateDate = NOW()
                WHERE FingerprintID = ?");
                $stmt->execute([$name, $branchId, $vendor, $serialNumber, $ip, $port, $state, $id]);
                $data = ['id' => $id];
                $msg = 'تم تحديث جهاز البصمة بنجاح';
            } else {
                $branches = is_array($branchIds) ? $branchIds : [$branchIds];
                $lastId = 0;

                foreach ($branches as $bid) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tbfingerprint
                    (FingerprintName, BranchID, FingerprintType, FingerprintSerailnumber, ip, port, FingerprintState, CreatedBy, CreatedDate, lastUpdateDate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, intval($bid), $vendor, $serialNumber, $ip, $port, $state, $createdBy]);
                    $lastId = $connect_pdo->lastInsertId();
                }
                $data = ['id' => $lastId];
                $msg = 'تمت إضافة جهاز البصمة بنجاح';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT DEVICE â€” TEST CONNECTION (API Documentation Compliant)
// ============================================================
    case 'fingerprint-test':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);

        if ($deviceId <= 0) {
            $result = false;
            $msg = 'معرف الجهاز مطلوب';
            break;
        }

        try {
            require_once __DIR__ . '/../shared/BiometricSync.php';

            $sync = new BiometricSync($connect_pdo);
            $data = $sync->testConnection($deviceId);

            if (!empty($data['connected'])) {
                $msg = 'تم الاتصال بجهاز البصمة بنجاح';
            } else {
                $result = false;
                $msg = $data['error'] ?? 'تعذر الاتصال بجهاز البصمة';
            }
        } catch (Exception $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء اختبار الاتصال: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT DEVICE â€” SYNC ATTENDANCE LOGS
// ============================================================
    case 'fingerprint-sync':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);
        $fromDate = $_POST['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $toDate = $_POST['to_date'] ?? date('Y-m-d');

        if ($deviceId <= 0) {
            $result = false;
            $msg = 'معرف الجهاز مطلوب';
            break;
        }

        try {
            require_once __DIR__ . '/../shared/BiometricSync.php';

            $sync = new BiometricSync($connect_pdo);
            $syncResult = $sync->syncDevice($deviceId);

            try {
                $logStmt = $connect_pdo->prepare(
                    "INSERT INTO biometric_sync_log
                    (device_id, sync_type, from_date, to_date, records_fetched, records_imported, status, error_message, created_at)
                 VALUES
                    (:device_id, 'attendance', :from_date, :to_date, :records_fetched, :records_imported, :status, :error_message, NOW())"
                );
                $logStmt->execute([
                    ':device_id' => $deviceId,
                    ':from_date' => $fromDate,
                    ':to_date' => $toDate,
                    ':records_fetched' => $syncResult['records_fetched'] ?? 0,
                    ':records_imported' => $syncResult['records_imported'] ?? 0,
                    ':status' => !empty($syncResult['success']) ? 'completed' : 'failed',
                    ':error_message' => $syncResult['error'] ?? null,
                ]);
                $syncResult['sync_id'] = (int) $connect_pdo->lastInsertId();
            } catch (Throwable $logException) {
                $syncResult['sync_id'] = 0;
            }

            $data = [
                'sync_id' => $syncResult['sync_id'] ?? 0,
                'device_id' => $deviceId,
                'device_name' => $syncResult['device_name'] ?? '',
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'records_fetched' => $syncResult['records_fetched'] ?? 0,
                'records_imported' => $syncResult['records_imported'] ?? 0,
                'status' => !empty($syncResult['success']) ? 'completed' : 'failed',
                'message' => !empty($syncResult['success'])
                    ? 'تمت مزامنة سجلات البصمة بنجاح'
                    : ($syncResult['error'] ?? 'تعذرت مزامنة سجلات البصمة'),
            ];

            if (!empty($syncResult['success'])) {
                $msg = 'تمت مزامنة سجلات البصمة بنجاح';
            } else {
                $result = false;
                $msg = $syncResult['error'] ?? 'تعذرت مزامنة سجلات البصمة';
            }
        } catch (Throwable $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء مزامنة جهاز البصمة: ' . $e->getMessage();
        }
        break;

    // ============================================================
    case 'fingerprint-info':
        $deviceId = intval($_GET['id'] ?? $_POST['device_id'] ?? 0);

        if ($deviceId <= 0) {
            $result = false;
            $msg = 'معرف الجهاز مطلوب';
            break;
        }

        try {
            $stmt = $connect_pdo->prepare("SELECT f.*, b.branch_name, u.FirstName, u.LastName
            FROM tbfingerprint f
            LEFT JOIN branches b ON f.BranchID = b.branch_id
            LEFT JOIN tblusers u ON f.CreatedBy = u.UserID
            WHERE f.FingerprintID = ?");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$device) {
                $result = false;
                $msg = 'الجهاز غير موجود';
                break;
            }

            $syncStmt = $connect_pdo->prepare("SELECT * FROM biometric_sync_log 
            WHERE device_id = ? ORDER BY created_at DESC LIMIT 10");
            $syncStmt->execute([$deviceId]);
            $syncHistory = $syncStmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'device' => $device,
                'sync_history' => $syncHistory
            ];
            $msg = 'تم جلب بيانات جهاز البصمة بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT DEVICE â€” SYNC ATTENDANCE LOGS (API Documentation Compliant)
// ============================================================
    case 'fingerprint-sync-logs':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $deviceId = intval($_POST['device_id'] ?? 0);
        $fromDate = $_POST['from'] ?? date('Y-m-d 00:00:00', strtotime('-7 days'));
        $toDate = $_POST['to'] ?? date('Y-m-d 23:59:59');

        if ($deviceId <= 0) {
            $result = false;
            $msg = 'معرف الجهاز مطلوب';
            break;
        }

        try {
            require_once __DIR__ . '/../shared/BiometricSync.php';

            $sync = new BiometricSync($connect_pdo);
            $syncResult = $sync->syncDevice($deviceId);

            $pulled = (int) ($syncResult['records_fetched'] ?? 0);
            $inserted = (int) ($syncResult['records_imported'] ?? 0);
            $duplicates = max(0, $pulled - $inserted);

            try {
                $logStmt = $connect_pdo->prepare(
                    "INSERT INTO biometric_sync_log
                    (device_id, sync_type, from_date, to_date, records_fetched, records_imported, status, error_message, created_at)
                 VALUES
                    (:device_id, 'attendance', :from_date, :to_date, :records_fetched, :records_imported, :status, :error_message, NOW())"
                );
                $logStmt->execute([
                    ':device_id' => $deviceId,
                    ':from_date' => $fromDate,
                    ':to_date' => $toDate,
                    ':records_fetched' => $pulled,
                    ':records_imported' => $inserted,
                    ':status' => !empty($syncResult['success']) ? 'completed' : 'failed',
                    ':error_message' => $syncResult['error'] ?? null,
                ]);
            } catch (Throwable $logException) {
            }

            $data = [
                'pulled' => $pulled,
                'inserted' => $inserted,
                'duplicates' => $duplicates,
                'from' => $fromDate,
                'to' => $toDate
            ];

            if (!empty($syncResult['success'])) {
                $msg = 'تمت مزامنة سجلات الحضور من جهاز البصمة بنجاح';
            } else {
                $result = false;
                $msg = $syncResult['error'] ?? 'تعذرت مزامنة سجلات الحضور من جهاز البصمة';
            }
        } catch (Throwable $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء مزامنة سجلات الحضور: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT DEVICE â€” GET ATTENDANCE LOGS (DataTables)
// ============================================================
    case 'fingerprint-attendance-logs':
        $draw = (int) ($_POST['draw'] ?? 1);
        $deviceId = intval($_POST['device_id'] ?? 0);

        if ($deviceId <= 0) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Count total logs for this device
            $countStmt = $connect_pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE device_id = ?");
            $countStmt->execute([$deviceId]);
            $total = $countStmt->fetchColumn();

            // Get attendance logs with employee names
            $sql = "SELECT al.*, 
                       CONCAT(u.FirstName, ' ', u.LastName) as employee_name
                FROM attendance_logs al
                LEFT JOIN tblusers u ON al.employee_id = u.UserID
                WHERE al.device_id = ?
                ORDER BY al.punch_time DESC
                LIMIT 100";
            $stmt = $connect_pdo->prepare($sql);
            $stmt->execute([$deviceId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'punch_time' => $row['punch_time'],
                    'employee_name' => $row['employee_name'] ?? ('موظف ' . $row['device_user_id']),
                    'punch_type' => $row['punch_type'],
                    'verify_mode' => $row['verify_mode'],
                    'status' => 'success'
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;

    // ============================================================
// IMPORT EMPLOYEE ATTENDANCE (Bulk import from Excel/CSV)
// ============================================================
    case 'import-emp-atten':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        // This endpoint handles bulk attendance import
        // Expected POST data: array of attendance records or file upload
        $records = $_POST['records'] ?? [];
        $branchId = intval($_POST['branch_id'] ?? $branch);
        $importedCount = 0;
        $createdBy = $_SESSION['user']['UserID'] ?? $user;

        try {
            if (!empty($records) && is_array($records)) {
                $stmt = $connect_pdo->prepare("INSERT INTO attendancet 
                (EmpID, Date, Time, Type, method, who_add) 
                VALUES (?, ?, ?, ?, 'import', ?)");

                foreach ($records as $record) {
                    $empId = intval($record['emp_id'] ?? 0);
                    $date = $record['date'] ?? date('Y-m-d');
                    $time = $record['time'] ?? date('H:i:s');
                    $type = intval($record['type'] ?? 1); // 1=check-in, 2=check-out

                    if ($empId > 0) {
                        $stmt->execute([$empId, $date, $time, $type, $createdBy]);
                        $importedCount++;
                    }
                }
                $data = ['imported' => $importedCount];
                $msg = "ØªÙ… Ø§Ø³ØªÙŠØ±Ø§Ø¯ $importedCount Ø³Ø¬Ù„ Ø­Ø¶ÙˆØ± Ø¨Ù†Ø¬Ø§Ø­";
            } else {
                $result = false;
                $msg = 'لا توجد بيانات للاستيراد';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// EMPLOYEE VALIDATE (Check employee data validity)
// ============================================================
    case 'empvalidate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $empId = intval($_POST['emp_id'] ?? $_POST['UserID'] ?? 0);
        $email = trim($_POST['email'] ?? $_POST['emp_email'] ?? '');
        $phone = trim($_POST['phone'] ?? $_POST['emp_phone'] ?? '');
        $fingerId = trim($_POST['finger_id'] ?? $_POST['emp_finger'] ?? '');
        $excludeId = intval($_POST['exclude_id'] ?? 0);

        $errors = [];

        try {
            // Check email uniqueness
            if (!empty($email)) {
                $stmt = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE UserEmail = ? AND UserID != ?");
                $stmt->execute([$email, $excludeId]);
                if ($stmt->fetch()) {
                    $errors['email'] = 'البريد الإلكتروني مستخدم بالفعل';
                }
            }

            // Check phone uniqueness
            if (!empty($phone)) {
                $stmt = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE Phone = ? AND UserID != ?");
                $stmt->execute([$phone, $excludeId]);
                if ($stmt->fetch()) {
                    $errors['phone'] = 'رقم الهاتف مستخدم بالفعل';
                }
            }

            // Check finger ID uniqueness (in tblremewal)
            if (!empty($fingerId)) {
                $stmt = $connect_pdo->prepare("SELECT r.Id FROM tblremewal r 
                JOIN tblusers u ON u.lastversion = r.Id 
                WHERE FIND_IN_SET(?, r.fingerID) AND u.UserID != ?");
                $stmt->execute([$fingerId, $excludeId]);
                if ($stmt->fetch()) {
                    $errors['finger_id'] = 'رقم البصمة مستخدم بالفعل';
                }
            }

            if (empty($errors)) {
                $data = ['valid' => true];
                $msg = 'البيانات صالحة';
            } else {
                $result = false;
                $data = ['valid' => false, 'errors' => $errors];
                $msg = 'توجد أخطاء في البيانات';
            }
        } catch (PDOException $e) {
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    // ============================================================
// ESS DOCUMENTS â€” contracts list
// ============================================================
    case 'ess-contracts-list':
        if (!$user) {
            echo json_encode(['result' => false, 'data' => []]);
            exit;
        }
        try {
            $stmt = $connect_pdo->prepare("SELECT r.Id, r.new_s_date, r.new_e_date, r.JobTitleID, jt.Name as job_title, r.BranchID, b.branch_name
            FROM tblremewal r
            LEFT JOIN tblusers u ON u.lastversion = r.Id
            LEFT JOIN tbljobtitle jt ON jt.Id = r.JobTitleID
            LEFT JOIN branches b ON b.branch_id = r.BranchID
            WHERE u.UserID = ?
            ORDER BY r.Id DESC");
            $stmt->execute([$user]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    'id' => $r['Id'],
                    'type' => 'عقد عمل - ' . ($r['job_title'] ?? ''),
                    'date' => ($r['new_s_date'] ?? '') . ' إلى ' . ($r['new_e_date'] ?? '')
                ];
            }
            echo json_encode(['result' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['result' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
        exit;

    // ============================================================
// ESS DOCUMENTS â€” contract PDF/print
// ============================================================
    case 'ess-contract-pdf':
    case 'ess-contract-print':
        if (!$user) {
            echo json_encode(['result' => false, 'msg' => 'غير مصرح']);
            exit;
        }
        $contractId = intval($_GET['id'] ?? 0);
        if ($contractId <= 0) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align:center;padding:40px;font-family:Arial;">معرف العقد غير صالح</div>';
            exit;
        }
        try {
            $stmt = $connect_pdo->prepare("
            SELECT r.*, 
                   CONCAT(u.FirstName, ' ', u.LastName) as empname,
                   u.Id_h as national_id, u.Phone as phone,
                   jt.Name as job_title,
                   s.Name as section_name,
                   b.branch_name,
                   et.Name as employment_type
            FROM tblremewal r
            JOIN tblusers u ON r.UserID = u.UserID
            LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
            LEFT JOIN tblsection s ON s.Id = r.SectionID
            LEFT JOIN branches b ON b.branch_id = r.BranchID
            LEFT JOIN tblemploymenttype et ON et.Id = r.TypeID
            WHERE r.Id = ? AND r.UserID = ?
            LIMIT 1");
            $stmt->execute([$contractId, $user]);
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contract) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<div style="text-align:center;padding:40px;font-family:Arial;">العقد غير موجود أو غير مصرح لك بعرضه</div>';
                exit;
            }

            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html dir="rtl"><head><meta charset="utf-8"><title>عقد عمل</title>
        <style>
            body{font-family:Arial,sans-serif;direction:rtl;padding:20px;max-width:800px;margin:0 auto;}
            .header{text-align:center;border-bottom:2px solid #333;padding-bottom:15px;margin-bottom:20px;}
            .header h1{margin:0;color:#0d21a5;}
            table{width:100%;border-collapse:collapse;margin:15px 0;}
            th,td{border:1px solid #ddd;padding:10px;text-align:right;}
            th{background:#f5f5f5;width:30%;}
            .section-title{background:#0d21a5;color:white;padding:8px;margin:20px 0 10px;font-weight:bold;}
            .footer{margin-top:30px;text-align:center;color:#666;font-size:12px;}
            @media print{.no-print{display:none;}}
        </style></head><body>
        <div class="header">
            <h1>عقد عمل</h1>
            <p>رقم العقد: ' . $contractId . '</p>
        </div>
        
        <div class="section-title">بيانات الموظف</div>
        <table>
            <tr><th>اسم الموظف</th><td>' . htmlspecialchars($contract['empname'] ?? '') . '</td></tr>
            <tr><th>رقم الهوية</th><td>' . htmlspecialchars($contract['national_id'] ?? '-') . '</td></tr>
            <tr><th>رقم الجوال</th><td>' . htmlspecialchars($contract['phone'] ?? '-') . '</td></tr>
        </table>
        
        <div class="section-title">بيانات العقد</div>
        <table>
            <tr><th>الفرع</th><td>' . htmlspecialchars($contract['branch_name'] ?? '-') . '</td></tr>
            <tr><th>القسم</th><td>' . htmlspecialchars($contract['section_name'] ?? '-') . '</td></tr>
            <tr><th>المسمى الوظيفي</th><td>' . htmlspecialchars($contract['job_title'] ?? '-') . '</td></tr>
            <tr><th>نوع التوظيف</th><td>' . htmlspecialchars($contract['employment_type'] ?? '-') . '</td></tr>
            <tr><th>تاريخ بداية العقد</th><td>' . htmlspecialchars($contract['new_s_date'] ?? $contract['CreatedDate'] ?? '-') . '</td></tr>
            <tr><th>تاريخ نهاية العقد</th><td>' . htmlspecialchars($contract['new_e_date'] ?? '-') . '</td></tr>
        </table>
        
        <div class="section-title">البيانات المالية</div>
        <table>
            <tr><th>الراتب الأساسي</th><td>' . number_format((float) ($contract['Salary'] ?? 0), 2) . ' ' . htmlspecialchars($contract['Currency'] ?? 'SAR') . '</td></tr>
        </table>
        
        <div class="footer">
            <p>تم إنشاء هذا المستند إلكترونياً من نظام Vision HR</p>
            <p>تاريخ الطباعة: ' . date('Y-m-d H:i') . '</p>
        </div>
        
        <script>window.onload=function(){window.print();}</script>
        </body></html>';
        } catch (Exception $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align:center;padding:40px;">خطأ: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        exit;

    // ============================================================
// ESS DOCUMENTS â€” salary PDF/print
// ============================================================
    case 'ess-salary-pdf':
    case 'ess-salary-print':
        if (!$user) {
            echo json_encode(['result' => false, 'msg' => 'غير مصرح']);
            exit;
        }
        $month = $_GET['month'] ?? $_POST['month'] ?? date('Y-n');
        [$yr, $mn] = explode('-', $month);
        try {
            $stmt = $connect_pdo->prepare("SELECT es.*, CONCAT(u.FirstName,' ',u.LastName) as empname, r.Salary, r.Currency
            FROM emp_salary es
            JOIN tblusers u ON u.UserID = es.UserID
            LEFT JOIN tblremewal r ON r.Id = u.lastversion
            LEFT JOIN salary_registration sr ON sr.Id = es.id_registration
            WHERE es.UserID = ? AND es.month = ? AND sr.year = ?
            LIMIT 1");
            $stmt->execute([$user, (int) $mn, (int) $yr]);
            $sal = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sal) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<div style="text-align:center;padding:40px;font-family:Arial;">لا يوجد كشف راتب لهذا الشهر</div>';
                exit;
            }
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html dir="rtl"><head><meta charset="utf-8"><title>كشف راتب</title>
        <style>body{font-family:Arial;direction:rtl;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f5f5f5;}</style></head><body>
        <h2 style="text-align:center">كشف راتب - ' . htmlspecialchars($sal['empname']) . '</h2>
        <p style="text-align:center">' . $mn . '/' . $yr . '</p>
        <table>
        <tr><th>الراتب الأساسي</th><td>' . number_format((float) $sal['Salary'], 2) . '</td></tr>
        <tr><th>المكافآت</th><td>' . number_format((float) ($sal['incentive'] ?? 0), 2) . '</td></tr>
        <tr><th>التعويضات</th><td>' . number_format((float) ($sal['benefit'] ?? 0), 2) . '</td></tr>
        <tr><th>الخصومات</th><td>' . number_format((float) ($sal['deductions'] ?? 0), 2) . '</td></tr>
        <tr><th>السلف</th><td>' . number_format((float) ($sal['advances'] ?? 0), 2) . '</td></tr>
        <tr><th>صافي الراتب</th><td><strong>' . number_format((float) ($sal['net_salary'] ?? $sal['end_salary'] ?? 0), 2) . '</strong></td></tr>
        </table>
        <script>window.onload=function(){window.print();}</script></body></html>';
        } catch (Exception $e) {
            echo '<div>خطأ: ' . $e->getMessage() . '</div>';
        }
        exit;

    // ============================================================
// ESS DOCUMENTS â€” request certificate
// ============================================================
    case 'ess-request-certificate':
        if (!$user) {
            echo json_encode(['result' => false, 'msg' => 'غير مصرح']);
            exit;
        }
        $type = $_POST['type'] ?? '';
        echo json_encode(['result' => true, 'msg' => 'تم إرسال طلبك بنجاح، سيتم التواصل معك قريباً'], JSON_UNESCAPED_UNICODE);
        exit;

    // ============================================================
// ESS DOCUMENTS â€” report PDF/Excel
// ============================================================
    case 'ess-report-pdf':
    case 'ess-report-excel':
        if (!$user) {
            echo 'غير مصرح';
            exit;
        }
        $from = $_GET['from'] ?? date('Y-01-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? 'attendance';
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html dir="rtl"><head><meta charset="utf-8"><title>تقرير</title>
    <style>body{font-family:Arial;direction:rtl;padding:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;} th{background:#0d21a5;color:#fff;}</style></head><body>';
        echo '<h2>تقرير ' . htmlspecialchars($type) . ' من ' . $from . ' إلى ' . $to . '</h2>';
        try {
            if ($type === 'attendance') {
                $stmt = $connect_pdo->prepare("SELECT Date, MIN(CASE WHEN Type=1 THEN Time END) as check_in, MAX(CASE WHEN Type=2 THEN Time END) as check_out FROM attendancet WHERE EmpID=? AND Date BETWEEN ? AND ? GROUP BY Date ORDER BY Date DESC");
                $stmt->execute([$user, $from, $to]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<table><tr><th>التاريخ</th><th>وقت الحضور</th><th>وقت الانصراف</th></tr>';
                foreach ($rows as $r) {
                    echo '<tr><td>' . $r['Date'] . '</td><td>' . ($r['check_in'] ?? '-') . '</td><td>' . ($r['check_out'] ?? '-') . '</td></tr>';
                }
                echo '</table>';
            } elseif ($type === 'leaves') {
                $stmt = $connect_pdo->prepare("SELECT leave_start_date, leave_end_date, day_leave, leavetype, description, status FROM tblleaverequest WHERE UserID=? AND leave_start_date BETWEEN ? AND ? ORDER BY leave_start_date DESC");
                $stmt->execute([$user, $from, $to]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<table><tr><th>من</th><th>إلى</th><th>أيام</th><th>النوع</th><th>الحالة</th></tr>';
                foreach ($rows as $r) {
                    $st = $r['status'] === null ? 'قيد المراجعة' : ($r['status'] == 1 ? 'مقبول' : 'مرفوض');
                    echo '<tr><td>' . $r['leave_start_date'] . '</td><td>' . $r['leave_end_date'] . '</td><td>' . $r['day_leave'] . '</td><td>' . $r['leavetype'] . '</td><td>' . $st . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<p>لا توجد بيانات للعرض</p>';
            }
        } catch (Exception $e) {
            echo '<p>خطأ: ' . $e->getMessage() . '</p>';
        }
        echo '<script>window.onload=function(){window.print();}</script></body></html>';
        exit;

    // ============================================================
// ESS â€” ATTENDANCE CHECK-IN/OUT (GPS method)
// ============================================================
    case 'ess-attendance':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $attSettings = $connect_pdo->query("SELECT setting_key, setting_value FROM attendance_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            if (($attSettings['gps_enabled'] ?? '1') !== '1') {
                $result = false;
                $msg = 'تسجيل الحضور عبر GPS غير مفعل حاليًا';
                break;
            }

            $today = date('Y-m-d');
            $time = date('H:i:s');
            $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
            $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float) $_POST['lng'] : null;
            $method = ($lat !== null && $lng !== null) ? 'gps' : 'manual';

            if (($attSettings['gps_required'] ?? '0') === '1' && ($lat === null || $lng === null)) {
                $result = false;
                $msg = 'يجب تفعيل الموقع الجغرافي لتسجيل الحضور';
                break;
            }

            if ($lat !== null && $lng !== null) {
                // جلب إحداثيات الفرع الخاص بالموظف
                $stmBranchGps = $connect_pdo->prepare("
                    SELECT a.Latitude, a.Longitude 
                    FROM tblusers u
                    LEFT JOIN tblremewal r ON r.Id = u.lastversion
                    LEFT JOIN branches b ON b.branch_id = r.BranchID
                    LEFT JOIN tbladdress a ON a.AddressID = b.branch_address
                    WHERE u.UserID = :uid LIMIT 1
                ");
                $stmBranchGps->execute([':uid' => $user]);
                $branchGps = $stmBranchGps->fetch(PDO::FETCH_ASSOC);

                $branchLat = (isset($branchGps['Latitude']) && $branchGps['Latitude'] !== '') ? (float) $branchGps['Latitude'] : null;
                $branchLng = (isset($branchGps['Longitude']) && $branchGps['Longitude'] !== '') ? (float) $branchGps['Longitude'] : null;

                // إذا كان للفرع إحداثيات نستخدمها، وإلا نستخدم إحداثيات الشركة الافتراضية
                $targetLat = $branchLat !== null ? $branchLat : (float) ($attSettings['office_lat'] ?? 0);
                $targetLng = $branchLng !== null ? $branchLng : (float) ($attSettings['office_lng'] ?? 0);
                $maxRadius = (int) ($attSettings['max_gps_radius_meters'] ?? 0);

                if ($targetLat && $targetLng && $maxRadius > 0) {
                    // حساب المسافة (مع تجنب أخطاء NAN إذا كانت المسافة صفرية)
                    $val = cos(deg2rad($targetLat)) * cos(deg2rad($lat)) *
                        cos(deg2rad($lng) - deg2rad($targetLng)) +
                        sin(deg2rad($targetLat)) * sin(deg2rad($lat));

                    // حصر القيمة بين -1 و 1 لتجنب مشاكل دالة acos
                    $val = max(-1, min(1, $val));
                    $distance = 6371000 * acos($val);

                    if ($distance > $maxRadius) {
                        $result = false;
                        $msg = 'أنت خارج نطاق موقع العمل المسموح للفرع. المسافة الحالية ' . round($distance) . ' متر، والحد الأقصى ' . $maxRadius . ' متر';
                        break;
                    }
                }
            }

            $check = $connect_pdo->prepare(
                "SELECT Type FROM attendancet WHERE EmpID = :uid AND Date = :d ORDER BY ID DESC LIMIT 1"
            );
            $check->execute([':uid' => $user, ':d' => $today]);
            $last = $check->fetch(PDO::FETCH_ASSOC);

            $type = (!$last || (int) $last['Type'] === 2) ? 1 : 2;
            $label = $type === 1 ? 'تم تسجيل الحضور بنجاح' : 'تم تسجيل الانصراف بنجاح';

            $attId = attendanceInsertRecord($connect_pdo, [
                'EmpID' => $user,
                'who_add' => $user,
                'Time' => $time,
                'Type' => $type,
                'Date' => $today,
                'lat' => $lat,
                'lng' => $lng,
                'method' => $method,
                'device_info' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);

            $Audit->logAttendance($attId, $user, $type, $method, $lat, $lng);
            $msg = $label . ' - ' . $time;
            $data = ['id' => $attId, 'type' => $type, 'method' => $method];
        } else {
            $result = false;
            $msg = 'غير مصرح';
        }
        break;

    // ============================================================
// ESS â€” ATTENDANCE CHECK-IN/OUT via QR Code
// ============================================================
    case 'ess-attendance-qr':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $attSettingsQr = $connect_pdo->query("SELECT setting_key, setting_value FROM attendance_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        if (($attSettingsQr['qr_enabled'] ?? '1') !== '1') {
            $result = false;
            $msg = 'تسجيل الحضور عبر QR غير مفعل حاليًا';
            break;
        }

        $qrToken = trim($_POST['qr_token'] ?? '');
        if ($qrToken === '') {
            $result = false;
            $msg = 'رمز QR مطلوب';
            break;
        }

        $now = date('Y-m-d H:i:s');
        $qrStmt = $connect_pdo->prepare(
            "SELECT id, branch_id, max_uses, used_count
         FROM attendance_qr_tokens
         WHERE token = :token
           AND valid_from <= :now
           AND valid_until >= :now2
           AND (used_count < max_uses)
         LIMIT 1"
        );
        $qrStmt->execute([':token' => $qrToken, ':now' => $now, ':now2' => $now]);
        $qrRow = $qrStmt->fetch(PDO::FETCH_ASSOC);

        if (!$qrRow) {
            $result = false;
            $msg = 'رمز QR غير صالح أو منتهي الصلاحية';
            break;
        }

        $today = date('Y-m-d');
        $time = date('H:i:s');

        $check = $connect_pdo->prepare("SELECT Type FROM attendancet WHERE EmpID = :uid AND Date = :d ORDER BY ID DESC LIMIT 1");
        $check->execute([':uid' => $user, ':d' => $today]);
        $last = $check->fetch(PDO::FETCH_ASSOC);

        $type = (!$last || (int) $last['Type'] === 2) ? 1 : 2;
        $label = $type === 1 ? 'تم تسجيل الحضور عبر QR بنجاح' : 'تم تسجيل الانصراف عبر QR بنجاح';

        $attId = attendanceInsertRecord($connect_pdo, [
            'EmpID' => $user,
            'who_add' => $user,
            'Time' => $time,
            'Type' => $type,
            'Date' => $today,
            'method' => 'qr',
            'qr_token' => $qrToken,
            'device_info' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        $connect_pdo->prepare("UPDATE attendance_qr_tokens SET used_count = used_count + 1 WHERE id = :id")
            ->execute([':id' => $qrRow['id']]);

        $Audit->logAttendance($attId, $user, $type, 'qr');
        $msg = $label . ' - ' . $time;
        $data = ['id' => $attId, 'type' => $type, 'method' => 'qr'];
        break;

    // ============================================================
// ADMIN â€” Generate QR Token for attendance
// ============================================================
    case 'generate-qr-token':
        if (!$User->userIsEmployer()) {
            echo json_encode(['result' => false, 'msg' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['result' => false, 'msg' => 'طريقة الطلب غير صحيحة'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $valid_minutes = (int) ($_POST['valid_minutes'] ?? 30);
            $max_uses = (int) ($_POST['max_uses'] ?? 50);
            $branch_id = (int) ($_POST['branch_id'] ?? 0);

            if ($branch_id <= 0) {
                echo json_encode(['result' => false, 'msg' => 'يرجى اختيار الفرع أولًا'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($valid_minutes <= 0) {
                $valid_minutes = 30;
            }

            if ($max_uses <= 0) {
                $max_uses = 50;
            }

            // Generate unique token
            $token = strtoupper(bin2hex(random_bytes(16)));

            // Calculate expiry
            $valid_from = date('Y-m-d H:i:s');
            $valid_until = date('Y-m-d H:i:s', strtotime("+$valid_minutes minutes"));

            // Insert token
            $stmt = $connect_pdo->prepare(
                "INSERT INTO attendance_qr_tokens (token, branch_id, generated_by, valid_from, valid_until, max_uses, used_count) 
             VALUES (:token, :branch_id, :generated_by, :valid_from, :valid_until, :max_uses, 0)"
            );
            $stmt->execute([
                ':token' => $token,
                ':branch_id' => $branch_id,
                ':generated_by' => $User->getId(),
                ':valid_from' => $valid_from,
                ':valid_until' => $valid_until,
                ':max_uses' => $max_uses
            ]);

            $result = true;
            $data = [
                'token' => $token,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'max_uses' => $max_uses
            ];
            $msg = 'تم إنشاء رمز QR بنجاح';

        } catch (Exception $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء إنشاء رمز QR: ' . $e->getMessage();
        }
        break;

    // ============================================================
// ESS â€” LEAVE REQUESTS LIST (DataTables)
// ============================================================
// ============================================================
// ESS â€” LEAVE REQUESTS LIST (DataTables)
// ============================================================
    case 'ess-leaves-list':
        if (!$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);

        $where = "WHERE lr.UserID = :uid";
        $params = [':uid' => $user];

        $statusFilter = $_POST['status_filter'] ?? '';
        if ($statusFilter !== '') {
            $where .= " AND lr.status = :sf";
            $params[':sf'] = (int) $statusFilter;
        }

        $cntQ = "SELECT COUNT(*) FROM tblleaverequest lr $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        $sql = "SELECT lr.Id, lr.leavetype, lr.leave_start_date, lr.leave_end_date, lr.leave_start_time, lr.leave_end_time, lr.day_leave,
                   lr.status, lr.Draft, lr.CreatedDate, lr.LastUpdateDate, lr.leave_unit,
                   lc.Name as LeaveTypeName
            FROM tblleaverequest lr
            LEFT JOIN leaveclassification lc ON lc.Id = lr.leavetype
            $where ORDER BY lr.Id DESC LIMIT $start, $length";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll();

        $formatted = [];
        foreach ($rows as $r) {
            $statusBadge = '';
            if ((int) $r['status'] === 1) {
                $statusBadge = '<span class="badge badge-success">معتمد</span>';
            } elseif ((int) $r['status'] === 2) {
                $statusBadge = '<span class="badge badge-danger">مرفوض</span>';
            } else {
                $statusBadge = '<span class="badge badge-warning">قيد المراجعة</span>';
            }

            $leaveDurationDisplay = '';
            $displayStartDate = $r['leave_start_date'] ?? '-';
            $displayEndDate = $r['leave_end_date'] ?? '-';

            if ($r['leave_unit'] === 'hour') {
                $leaveDurationDisplay = "{$r['day_leave']} ساعة"; // day_leave now stores hours
                if (!empty($r['leave_start_time'])) {
                    $displayStartDate .= ' <br> ' . date('H:i', strtotime($r['leave_start_time']));
                }
                if (!empty($r['leave_end_time'])) {
                    $displayEndDate .= ' <br> ' . date('H:i', strtotime($r['leave_end_time']));
                }
            } else {
                $leaveDurationDisplay = "{$r['day_leave']} يوم";
            }

            $formatted[] = [
                $r['LeaveTypeName'] ?? '-',
                $displayStartDate,
                $displayEndDate,
                $leaveDurationDisplay,
                $statusBadge,
                '<small>' . ($r['LastUpdateDate'] ?? $r['CreatedDate'] ?? '-') . '</small>'
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
// ESS â€” LEAVE REQUEST SUBMIT
// ============================================================
    case 'ess-leave-submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $leavetype = (int) ($_POST['leavetype'] ?? 0);
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $desc = $_POST['description'] ?? '';
        $isDraft = (int) ($_POST['isdraft'] ?? 0);
        $leaveUnit = $_POST['leave_unit'] ?? 'day'; // 'day' or 'hour'

        $startTime = null;
        $endTime = null;
        $dayLeave = 0; // Initialize day_leave

        if ($leaveUnit === 'hour') {
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';

            if (!$leavetype || !$startDate || !$startTime || !$endTime) {
                $result = false;
                $msg = 'يرجى تعبئة جميع الحقول المطلوبة لإجازة الساعة';
                break;
            }

            // For hourly, start and end date must be the same
            if ($startDate !== $endDate) {
                $result = false;
                $msg = 'لإجازة الساعة، يجب أن يكون تاريخ البداية والنهاية هو نفسه.';
                break;
            }

            // Calculate hours difference if times are valid
            $startDateTime = new DateTime($startDate . ' ' . $startTime);
            $endDateTime = new DateTime($endDate . ' ' . $endTime);

            // Handle cases where end time is on the next day (e.g., 22:00 to 02:00)
            // This assumes hourly leave can span midnight, adjust if not intended.
            if ($endDateTime < $startDateTime) {
                $endDateTime->modify('+1 day');
            }

            $interval = $startDateTime->diff($endDateTime);
            $dayLeave = $interval->h + ($interval->i / 60); // Total hours as decimal

            if ($dayLeave <= 0) {
                $result = false;
                $msg = 'عدد الساعات غير صالح. يرجى التحقق من أوقات البداية والنهاية.';
                break;
            }

        } else { // 'day' unit
            if (!$leavetype || !$startDate || !$endDate) {
                $result = false;
                $msg = 'يرجى تعبئة جميع الحقول المطلوبة لإجازة اليوم الكامل';
                break;
            }
            $dayLeave = max(1, (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        }

        // Handle file upload
        $filePath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = '../uploads/hrsys/leavefile/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = 'leavefile' . uniqid() . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $filePath = $uploadDir . $fileName;
            }
        }

        $status = null; // null = pending
        $draft = $isDraft ? 0 : 1; // 1 = submitted, 0 = draft

        $stmIns = $connect_pdo->prepare(
            "INSERT INTO tblleaverequest (UserID, BranchID, leavetype, path, leave_start_date, leave_end_date, leave_start_time, leave_end_time, day_leave, leave_unit, status, Draft, description, created_by, CreatedDate, LastUpdateDate)
         VALUES (:uid, :bid, :lt, :path, :sd, :ed, :st_time, :ed_time, :dl, :lu, :st, :dr, :desc, :uid2, NOW(), NOW())"
        );
        $stmIns->execute([
            ':uid' => $user,
            ':bid' => $branch ?? 1,
            ':lt' => $leavetype,
            ':path' => $filePath,
            ':sd' => $startDate,
            ':ed' => $endDate,
            ':st_time' => $startTime,
            ':ed_time' => $endTime,
            ':dl' => $dayLeave,
            ':lu' => $leaveUnit,
            ':st' => $status,
            ':dr' => $draft,
            ':desc' => $desc,
            ':uid2' => $user
        ]);
        $newId = (int) $connect_pdo->lastInsertId();
        $data = ['id' => $newId];

        // Start workflow approval process if not draft
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/WorkflowManager.php';
                $workflowManager = new WorkflowManager($connect_pdo);
                $workflowResult = $workflowManager->startWorkflow('leave_request', $newId, $user);
                if ($workflowResult['success']) {
                    $data['workflow_instance_id'] = $workflowResult['instance_id'];
                }
            } catch (Exception $e) {
                // Workflow optional - continue even if it fails
            }
        }

        $Audit->log(
            AuditLog::CREATE,
            AuditLog::ENTITY_LEAVE,
            $newId,
            'تقديم طلب إجازة جديد',
            null,
            ['leavetype' => $leavetype, 'start_date' => $startDate, 'end_date' => $endDate, 'start_time' => $startTime, 'end_time' => $endTime, 'duration' => $dayLeave, 'unit' => $leaveUnit]
        );

        // Send notification to employee
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/NotificationService.php';
                $notifService = new NotificationService($connect_pdo);
                $requesterName = trim((string) ($_SESSION['user']['name'] ?? 'الموظف'));
                $notificationMsg = ($leaveUnit === 'hour')
                    ? "تم تقديم طلب إجازة بالساعات من {$startTime} إلى {$endTime} ({$dayLeave} ساعات) بتاريخ {$startDate}"
                    : "تم تقديم طلب إجازة من {$startDate} إلى {$endDate} ({$dayLeave} أيام)";

                $notifService->notify(
                    $user,
                    'تم تقديم طلب الإجازة',
                    $notificationMsg,
                    'info',
                    'leave_request',
                    $newId
                );

                $adminMessage = ($leaveUnit === 'hour')
                    ? "{$requesterName} قدم طلب إجازة بالساعات بتاريخ {$startDate} من {$startTime} إلى {$endTime}"
                    : "{$requesterName} قدم طلب إجازة من {$startDate} إلى {$endDate}";

                notifyAdminsAboutRequest(
                    $connect_pdo,
                    (int) $user,
                    (int) ($branch ?? 0),
                    'طلب إجازة جديد بانتظار المراجعة',
                    $adminMessage,
                    'leave_request',
                    $newId
                );
            } catch (Exception $e) {
                // Notification optional
            }
        }

        $msg = 'تم تقديم طلب الإجازة بنجاح';
        break;

    // ============================================================
// ESS â€” ADVANCES LIST (DataTables)
// ============================================================
    case 'ess-advances-list':
        if (!$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);

        $where = "WHERE a.UserID = :uid";
        $params = [':uid' => $user];

        $cntQ = "SELECT COUNT(*) FROM tblempadvances a $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        $sql = "SELECT a.Id, a.Amount, a.currency, a.DueDate, a.Status, a.Draft, a.type, a.description, a.CreatedDate, a.LastUpdateDate
            FROM tblempadvances a
            $where ORDER BY a.Id DESC LIMIT $start, $length";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll();

        $formatted = [];
        foreach ($rows as $r) {
            $statusBadge = '';
            if ($r['Status'] == 1)
                $statusBadge = '<span class="badge badge-success">معتمد</span>';
            elseif ($r['Status'] == 2)
                $statusBadge = '<span class="badge badge-danger">مرفوض</span>';
            else
                $statusBadge = '<span class="badge badge-warning">قيد المراجعة</span>';

            $typeName = $r['type'] == 1 ? 'على الراتب' : 'خارج الراتب';

            $formatted[] = [
                number_format((float) $r['Amount'], 2) . ' ' . ($r['currency'] ?? 'SAR'),
                $typeName,
                $r['DueDate'] ?? '-',
                $statusBadge,
                '<small>' . ($r['LastUpdateDate'] ?? $r['CreatedDate'] ?? '-') . '</small>'
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
// ESS â€” ADVANCE REQUEST SUBMIT
// ============================================================
    case 'ess-advance-submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $amount = $_POST['amount'] ?? 0;
        $dueDate = $_POST['due_date'] ?? '';
        $type = (int) ($_POST['type'] ?? 1);
        $desc = $_POST['description'] ?? '';
        $isDraft = (int) ($_POST['isdraft'] ?? 0);

        if (!$amount || !$dueDate) {
            $result = false;
            $msg = 'يرجى تعبئة جميع الحقول المطلوبة';
            break;
        }

        $draft = $isDraft ? 0 : 1;

        $stmIns = $connect_pdo->prepare(
            "INSERT INTO tblempadvances (UserID, BranchID, Amount, currency, DueDate, Status, Draft, type, description, created_by, CreatedDate)
         VALUES (:uid, :bid, :amt, 'SAR', :dd, NULL, :dr, :tp, :desc, :uid2, NOW())"
        );
        $stmIns->execute([
            ':uid' => $user,
            ':bid' => $branch ?? 1,
            ':amt' => $amount,
            ':dd' => $dueDate,
            ':dr' => $draft,
            ':tp' => $type,
            ':desc' => $desc,
            ':uid2' => $user
        ]);
        $newId = (int) $connect_pdo->lastInsertId();
        $data = ['id' => $newId];

        // Start workflow approval process if not draft
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/WorkflowManager.php';
                $workflowManager = new WorkflowManager($connect_pdo);
                $workflowResult = $workflowManager->startWorkflow('advance_request', $newId, $user);
                if ($workflowResult['success']) {
                    $data['workflow_instance_id'] = $workflowResult['instance_id'];
                }
            } catch (Exception $e) {
                // Workflow optional - continue even if it fails
            }
        }

        $Audit->log(
            AuditLog::CREATE,
            AuditLog::ENTITY_ADVANCE,
            $newId,
            'تقديم طلب سلفة جديد',
            null,
            ['amount' => $amount, 'type' => $type, 'due_date' => $dueDate]
        );

        // Send notification to employee
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/NotificationService.php';
                $notifService = new NotificationService($connect_pdo);
                $requesterName = trim((string) ($_SESSION['user']['name'] ?? 'الموظف'));

                $notifService->notify(
                    $user,
                    'تم تقديم طلب السلفة',
                    "تم تقديم طلب سلفة بمبلغ {$amount} ريال - تاريخ الاستحقاق: {$dueDate}",
                    'info',
                    'advance_request',
                    $newId
                );

                notifyAdminsAboutRequest(
                    $connect_pdo,
                    (int) $user,
                    (int) ($branch ?? 0),
                    'طلب سلفة جديد بانتظار المراجعة',
                    "{$requesterName} قدم طلب سلفة بمبلغ {$amount} ريال وتاريخ استحقاق {$dueDate}",
                    'advance_request',
                    $newId
                );
            } catch (Exception $e) {
                // Notification optional
            }
        }

        $msg = 'تم تقديم طلب السلفة بنجاح';
        break;

    // ============================================================
// ESS â€” ORDERS LIST (DataTables)
// ============================================================
    case 'ess-orders-list':
        if (!$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);

        $where = "WHERE o.UserID = :uid";
        $params = [':uid' => $user];

        $cntQ = "SELECT COUNT(*) FROM emp_order o $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        $sql = "SELECT o.Id, o.title, o.description, o.Status, o.isread, o.CreatedDate, o.LastUpdateDate
            FROM emp_order o
            $where ORDER BY o.Id DESC LIMIT $start, $length";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll();

        $formatted = [];
        foreach ($rows as $r) {
            $statusBadge = '';
            if ($r['Status'] == 1)
                $statusBadge = '<span class="badge badge-success">معتمد</span>';
            elseif ($r['Status'] == 2)
                $statusBadge = '<span class="badge badge-danger">مرفوض</span>';
            else
                $statusBadge = '<span class="badge badge-warning">قيد المراجعة</span>';

            $readBadge = $r['isread'] ? '<span class="badge badge-info">مقروء</span>' : '<span class="badge badge-secondary">غير مقروء</span>';

            $formatted[] = [
                htmlspecialchars($r['title'] ?? '-'),
                $statusBadge,
                $readBadge,
                '<small>' . ($r['LastUpdateDate'] ?? $r['CreatedDate'] ?? '-') . '</small>'
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
// ESS â€” ORDER SUBMIT
// ============================================================
    case 'ess-order-submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $title = $_POST['title'] ?? '';
        $desc = $_POST['description'] ?? '';
        $isDraft = (int) ($_POST['isdraft'] ?? 0);

        if (!$title) {
            $result = false;
            $msg = 'يرجى إدخال الموضوع';
            break;
        }

        $draft = $isDraft ? 0 : 1;

        $stmIns = $connect_pdo->prepare(
            "INSERT INTO emp_order (UserID, BranchID, title, description, Status, Draft, created_by, CreatedDate)
         VALUES (:uid, :bid, :title, :desc, NULL, :dr, :uid2, NOW())"
        );
        $stmIns->execute([
            ':uid' => $user,
            ':bid' => $branch ?? 1,
            ':title' => $title,
            ':desc' => $desc,
            ':dr' => $draft,
            ':uid2' => $user
        ]);
        $newId = (int) $connect_pdo->lastInsertId();
        $data = ['id' => $newId];

        // Start workflow approval process if not draft
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/WorkflowManager.php';
                $workflowManager = new WorkflowManager($connect_pdo);
                $workflowResult = $workflowManager->startWorkflow('order', $newId, $user);
                if ($workflowResult['success']) {
                    $data['workflow_instance_id'] = $workflowResult['instance_id'];
                }
            } catch (Exception $e) {
                // Workflow optional - continue even if it fails
            }
        }

        $Audit->log(
            AuditLog::CREATE,
            AuditLog::ENTITY_ORDER,
            $newId,
            'تقديم طلب إداري جديد',
            null,
            ['title' => $title]
        );

        // Send notification to employee
        if (!$isDraft && $newId) {
            try {
                require_once __DIR__ . '/../classes/NotificationService.php';
                $notifService = new NotificationService($connect_pdo);
                $requesterName = trim((string) ($_SESSION['user']['name'] ?? 'الموظف'));

                $notifService->notify(
                    $user,
                    'تم تقديم الطلب الإداري',
                    "تم تقديم طلب إداري: {$title}",
                    'info',
                    'order',
                    $newId
                );

                notifyAdminsAboutRequest(
                    $connect_pdo,
                    (int) $user,
                    (int) ($branch ?? 0),
                    'طلب إداري جديد بانتظار المراجعة',
                    "{$requesterName} قدم طلباً إدارياً جديداً بعنوان {$title}",
                    'order',
                    $newId
                );
            } catch (Exception $e) {
                // Notification optional
            }
        }

        $msg = 'تم تقديم الطلب بنجاح';
        break;

    // ============================================================
// ESS â€” SALARY SLIPS LIST (matches real emp_salary schema)
// ============================================================
    case 'ess-salary-list':
        if (!$user) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $stm = $connect_pdo->prepare(
            "SELECT es.Id, es.month, es.incentive, es.benefit, es.advances,
                es.deductions, es.absent_salary, es.net_salary, es.end_salary,
                es.id_registration, es.created_date
         FROM emp_salary es
         WHERE es.UserID = :uid
         ORDER BY es.Id DESC"
        );
        $stm->execute([':uid' => $user]);
        $data = $stm->fetchAll(PDO::FETCH_ASSOC);
        break;
    case 'ess-forgot-punch-request':
        if (!$user) {
            echo json_encode(['result' => false, 'msg' => 'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $date = $_POST['correction_date'] ?? null;
        $time = $_POST['correction_time'] ?? null;
        $type = $_POST['correction_type'] ?? null;
        $reason = trim($_POST['reason'] ?? '');

        if (empty($date) || empty($time) || empty($type) || empty($reason)) {
            echo json_encode(['result' => false, 'msg' => 'يرجى تعبئة جميع الحقول المطلوبة'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $sql = "INSERT INTO attendance_correction_requests (emp_id, correction_date, correction_time, correction_type, reason)
                VALUES (:uid, :c_date, :c_time, :c_type, :reason)";

            $stm = $connect_pdo->prepare($sql);
            $stm->execute([
                ':uid' => $user,
                ':c_date' => $date,
                ':c_time' => $time,
                ':c_type' => $type,
                ':reason' => $reason
            ]);

            if ($stm->rowCount() > 0) {
                echo json_encode(['result' => true, 'msg' => 'تم إرسال طلبك بنجاح وهو الآن بانتظار المراجعة'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['result' => false, 'msg' => 'تعذر إرسال الطلب، يرجى المحاولة مرة أخرى'], JSON_UNESCAPED_UNICODE);
            }

        } catch (PDOException $e) {
            echo json_encode(['result' => false, 'msg' => 'حدث خطأ فني أثناء إرسال الطلب، يرجى التواصل مع الإدارة'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    // ============================================================
// ESS â€” ATTENDANCE HISTORY
// ============================================================
    case 'ess-attendance-history':
        if (!$user) {
            echo json_encode([
                'draw' => (int) ($_POST['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'غير مصرح لك بالوصول'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $month = $_POST['month'] ?? date('Y-m');

        $where = "WHERE a.EmpID = :uid";
        $params = [':uid' => $user];

        if (!empty($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start_date = $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));

            $where .= " AND a.Date >= :start_date AND a.Date <= :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }

        $cntQ = "SELECT COUNT(*) FROM attendancet a $where";
        $cntS = $connect_pdo->prepare($cntQ);
        $cntS->execute($params);
        $total = (int) $cntS->fetchColumn();

        $limit_clause = "";
        if ($length != -1) {
            $limit_clause = " LIMIT $start, $length";
        }

        $sql = "SELECT a.ID, a.Date, a.Time, a.Type, a.method, a.lat, a.lng
            FROM attendancet a
            $where ORDER BY a.Date DESC, a.Time DESC $limit_clause";

        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);
        $rows = $stm->fetchAll();

        $formatted = [];
        foreach ($rows as $r) {
            $typeBadge = $r['Type'] == 1
                ? '<span class="badge badge-success"><i class="fas fa-sign-in-alt"></i> حضور</span>'
                : '<span class="badge badge-danger"><i class="fas fa-sign-out-alt"></i> انصراف</span>';

            $methodLabels = ['gps' => 'GPS', 'qr' => 'QR', 'manual' => 'يدوي', 'import' => 'استيراد'];
            $m = $r['method'] ?? 'manual';

            $badgeClass = 'secondary';
            if ($m === 'gps')
                $badgeClass = 'info';
            elseif ($m === 'qr')
                $badgeClass = 'primary';
            elseif ($m === 'manual')
                $badgeClass = 'warning text-dark';

            $methodBadge = '<span class="badge badge-' . $badgeClass . '">'
                . ($methodLabels[$m] ?? $m) . '</span>';

            $formatted[] = [
                $r['Date'],
                $r['Time'],
                $typeBadge,
                $methodBadge
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $formatted
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'get-attendance-requests':
        // SECURITY CHECK: Ensure $connect_pdo and $User are accessible/valid if this file is included directly.
        // This code assumes you have access to $connect_pdo and $User->id (though $User->id isn't used here, it's safer to check).

        $sql = "SELECT acr.id, acr.request_date, acr.correction_date, acr.correction_time, acr.correction_type, acr.reason,
                   u.FirstName, u.LastName
            FROM attendance_correction_requests acr
            JOIN tblusers u ON acr.emp_id = u.UserID  
            WHERE acr.status = 'pending'
            ORDER BY acr.request_date ASC";

        $stm = $connect_pdo->prepare($sql);
        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Crucial: Ensure JSON is encoded correctly and the structure matches DataTables expectation
        echo json_encode(['data' => $rows]);
        exit; // <--- THIS MUST BE PRESENT

    // ACTION 2: To approve or reject a request
    case 'process-attendance-request':
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $adminNotes = trim($_POST['notes'] ?? 'تمت المعالجة بواسطة المسؤول');
        $adminId = method_exists($User, 'getId') ? (int) $User->getId() : (int) ($_SESSION['user_id'] ?? 0);

        if ($requestId <= 0 || !in_array($decision, ['approve', 'reject'], true) || $adminId <= 0) {
            echo json_encode(['result' => false, 'msg' => 'بيانات غير صالحة'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $connect_pdo->beginTransaction();

            $reqStm = $connect_pdo->prepare("SELECT * FROM attendance_correction_requests WHERE id = :id FOR UPDATE");
            $reqStm->execute([':id' => $requestId]);
            $request = $reqStm->fetch(PDO::FETCH_ASSOC);

            if (!$request || ($request['status'] ?? '') !== 'pending') {
                throw new Exception('لم يتم العثور على الطلب أو تمت معالجته بالفعل.');
            }

            $statusValue = $decision === 'approve' ? 'approved' : 'rejected';
            $updateStm = $connect_pdo->prepare("
            UPDATE attendance_correction_requests
            SET status = :status, reviewed_by = :admin_id, review_date = NOW(), reviewer_notes = :notes
            WHERE id = :request_id
        ");
            $updateStm->execute([
                ':status' => $statusValue,
                ':admin_id' => $adminId,
                ':notes' => $adminNotes,
                ':request_id' => $requestId
            ]);

            if ($decision === 'approve') {
                $attStm = $connect_pdo->prepare("
                INSERT INTO attendancet (EmpID, Date, Time, Type, who_add, method)
                VALUES (:emp_id, :att_date, :att_time, :att_type, :who_add, 'manual')
            ");
                $attStm->execute([
                    ':emp_id' => $request['emp_id'],
                    ':att_date' => $request['correction_date'],
                    ':att_time' => $request['correction_time'],
                    ':att_type' => $request['correction_type'],
                    ':who_add' => $adminId
                ]);
            }

            $connect_pdo->commit();

            if (!empty($request['emp_id'])) {
                try {
                    require_once __DIR__ . '/../classes/NotificationService.php';
                    $notifService = new NotificationService($connect_pdo);
                    $notifService->notify(
                        (int) $request['emp_id'],
                        $decision === 'approve' ? 'تم اعتماد طلب تعديل الحضور' : 'تم رفض طلب تعديل الحضور',
                        $decision === 'approve'
                        ? "ØªÙ… Ø§Ø¹ØªÙ…Ø§Ø¯ Ø·Ù„Ø¨ ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø­Ø¶ÙˆØ± Ø¨ØªØ§Ø±ÙŠØ® {$request['correction_date']} ÙÙŠ Ø§Ù„Ø³Ø§Ø¹Ø© {$request['correction_time']}"
                        : "ØªÙ… Ø±ÙØ¶ Ø·Ù„Ø¨ ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø­Ø¶ÙˆØ± Ø¨ØªØ§Ø±ÙŠØ® {$request['correction_date']}" . (!empty($adminNotes) ? " - Ø§Ù„Ù…Ù„Ø§Ø­Ø¸Ø§Øª: {$adminNotes}" : ''),
                        $decision === 'approve' ? 'success' : 'warning',
                        'attendance_correction',
                        $requestId
                    );
                } catch (Throwable $notificationError) {
                }
            }

            echo json_encode(['result' => true, 'msg' => 'تمت معالجة الطلب بنجاح'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            if ($connect_pdo->inTransaction()) {
                $connect_pdo->rollBack();
            }
            echo json_encode(['result' => false, 'msg' => 'حدث خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'process-attendance-request-legacy':
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $action = $_POST['decision'] ?? ''; // 'approve' or 'reject'
        $adminNotes = trim($_POST['notes'] ?? 'تمت المعالجة بواسطة المسؤول');
        $adminId = method_exists($User, 'getId') ? (int) $User->getId() : (int) ($_SESSION['user_id'] ?? 0);

        if ($requestId <= 0 || !in_array($action, ['approve', 'reject']) || $adminId <= 0) {
            echo json_encode(['result' => false, 'msg' => 'بيانات غير صالحة']);
            exit;
        }

        try {
            $connect_pdo->beginTransaction();

            // Step 1: Update the request status
            $sql = "UPDATE attendance_correction_requests
                SET status = :status, reviewed_by = :admin_id, review_date = NOW(), reviewer_notes = :notes
                WHERE id = :request_id AND status = 'pending'";

            $stm = $connect_pdo->prepare($sql);
            $stm->execute([
                ':status' => $action === 'approve' ? 'approved' : 'rejected',
                ':admin_id' => $adminId,
                ':notes' => $adminNotes,
                ':request_id' => $requestId
            ]);

            if ($stm->rowCount() === 0) {
                throw new Exception('لم يتم العثور على الطلب أو تمت معالجته بالفعل.');
            }

            // Step 2: If approved, insert the record into the main attendance table
            if ($action === 'approve') {
                // First, get the request details
                $reqStm = $connect_pdo->prepare("SELECT * FROM attendance_correction_requests WHERE id = :id");
                $reqStm->execute([':id' => $requestId]);
                $request = $reqStm->fetch(PDO::FETCH_ASSOC);

                // Now insert into the main attendance table
                $attSql = "INSERT INTO attendancet (EmpID, Date, Time, Type, who_add, method)
                       VALUES (:emp_id, :att_date, :att_time, :att_type, :who_add, 'manual')";
                $attStm = $connect_pdo->prepare($attSql);
                $attStm->execute([
                    ':emp_id' => $request['emp_id'],
                    ':att_date' => $request['correction_date'],
                    ':att_time' => $request['correction_time'],
                    ':att_type' => $request['correction_type'],
                    ':who_add' => $adminId
                ]);
            }

            // If all successful, commit the transaction
            $connect_pdo->commit();
            echo json_encode(['result' => true, 'msg' => 'تمت معالجة الطلب بنجاح']);

        } catch (Exception $e) {
            $connect_pdo->rollBack();
            echo json_encode(['result' => false, 'msg' => 'حدث خطأ: ' . $e->getMessage()]);
        }
        exit;
    // ============================================================
// AUDIT LOG â€” List (Admin only)
// ============================================================
    case 'audit-log-list':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $draw = (int) ($_POST['draw'] ?? 1);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 25);

        $filters = [];
        if (!empty($_POST['action_filter']))
            $filters['action'] = $_POST['action_filter'];
        if (!empty($_POST['entity_filter']))
            $filters['entity_type'] = $_POST['entity_filter'];
        if (!empty($_POST['user_filter']))
            $filters['user_id'] = (int) $_POST['user_filter'];
        if (!empty($_POST['date_from']))
            $filters['date_from'] = $_POST['date_from'];
        if (!empty($_POST['date_to']))
            $filters['date_to'] = $_POST['date_to'];
        if (!empty($_POST['search']['value']))
            $filters['search'] = $_POST['search']['value'];

        $logResult = $Audit->query($filters, $length, $start);

        // Arabic translations for actions
        $actionTranslations = [
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
            'attendance' => 'حضور',
            'import' => 'استيراد',
            'export' => 'تصدير',
            'permission' => 'صلاحية',
            'view' => 'عرض',
            'download' => 'تحميل',
            'upload' => 'رفع',
            'reset' => 'إعادة تعيين',
            'send' => 'إرسال',
            'cancel' => 'إلغاء'
        ];

        // Arabic translations for entity types
        $entityTranslations = [
            'session' => 'جلسة',
            'attendance' => 'حضور',
            'leave' => 'إجازة',
            'advance' => 'سلفة',
            'order' => 'طلب',
            'user' => 'مستخدم',
            'employee' => 'موظف',
            'benefit' => 'تعويض',
            'deduction' => 'خصم',
            'qr_token' => 'رمز QR',
            'setting' => 'إعداد',
            'salary' => 'راتب',
            'contract' => 'عقد',
            'promotion' => 'ترقية',
            'resignation' => 'استقالة',
            'dismissal' => 'فصل',
            'incentive' => 'حافز',
            'shift' => 'فترة',
            'section' => 'قسم',
            'jobtitle' => 'مسمى وظيفي',
            'jobgrade' => 'درجة وظيفية',
            'group' => 'مجموعة',
            'insurance' => 'تأمين',
            'holiday' => 'إجازة رسمية',
            'fingerprint' => 'بصمة',
            'report' => 'تقرير',
            'mail' => 'بريد',
            'notification' => 'إشعار',
            'promotion_requests' => 'طلبات الترقية',
            'leave_request' => 'طلب إجازة',
            'advance_request' => 'طلب سلفة'
        ];

        $formatted = [];
        foreach ($logResult['data'] as $row) {
            $actionBadges = [
                'login' => 'info',
                'logout' => 'secondary',
                'create' => 'success',
                'update' => 'primary',
                'delete' => 'danger',
                'approve' => 'success',
                'reject' => 'danger',
                'attendance' => 'warning',
                'import' => 'info',
                'export' => 'info',
                'permission' => 'dark'
            ];
            $badge = $actionBadges[$row['action']] ?? 'secondary';

            // Translate action and entity type to Arabic
            $actionAr = $actionTranslations[$row['action']] ?? $row['action'];
            $entityAr = $entityTranslations[$row['entity_type']] ?? $row['entity_type'];

            $formatted[] = [
                '<small>' . $row['created_at'] . '</small>',
                '<span class="badge badge-' . $badge . '">' . htmlspecialchars($actionAr) . '</span>',
                htmlspecialchars($row['user_name'] ?? '-'),
                htmlspecialchars($entityAr ?? '-'),
                htmlspecialchars($row['description'] ?? '-'),
                htmlspecialchars($row['ip_address'] ?? '-'),
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $logResult['total'],
            'recordsFiltered' => $logResult['total'],
            'data' => $formatted
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ============================================================
// AUDIT LOG â€” Detail (single entry)
// ============================================================
    case 'audit-log-detail':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $logId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$logId) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        $stmt = $connect_pdo->prepare("SELECT * FROM audit_log WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $logId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            $result = false;
            $msg = 'السجل غير موجود';
        }
        break;

    // ============================================================
// ADMIN â€” Attendance method stats (for dashboard)
// ============================================================
    case 'attendance-method-stats':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');

        $stmt = $connect_pdo->prepare(
            "SELECT COALESCE(method, 'manual') as method, COUNT(*) as cnt 
         FROM attendancet 
         WHERE Date BETWEEN :df AND :dt 
         GROUP BY method"
        );
        $stmt->execute([':df' => $dateFrom, ':dt' => $dateTo]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // ============================================================
// ADMIN â€” Get attendance settings
// ============================================================
    case 'attendance-settings-get':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $rows = $connect_pdo->query("SELECT setting_key, setting_value FROM attendance_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $data = $rows ?: [];
        break;

    // ============================================================
// ADMIN â€” Save attendance settings
// ============================================================
    case 'attendance-settings-save':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $allowedKeys = ['gps_enabled', 'qr_enabled', 'manual_enabled', 'fingerprint_enabled', 'gps_required', 'max_gps_radius_meters', 'office_lat', 'office_lng', 'fingerprint_device_ip', 'fingerprint_device_port', 'fingerprint_device_type'];
        $stmt = $connect_pdo->prepare("UPDATE attendance_settings SET setting_value = :v, updated_by = :uid WHERE setting_key = :k");
        $updated = 0;
        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $stmt->execute([':v' => $_POST[$key], ':uid' => $user, ':k' => $key]);
                $updated++;
            }
        }
        $Audit->log(AuditLog::UPDATE, 'attendance_settings', 0, "تحديث إعدادات الحضور ({$updated} إعداد)", null, $_POST);
        $msg = 'تم حفظ إعدادات الحضور بنجاح';
        break;

    // ============================================================
// ADMIN â€” Active QR tokens list
// ============================================================
    case 'active-qr-tokens':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $connect_pdo->prepare(
            "SELECT t.id, t.token, t.branch_id, t.valid_from, t.valid_until, t.max_uses, t.used_count,
                b.branch_name, u.FirstName, u.LastName
         FROM attendance_qr_tokens t
         LEFT JOIN branches b ON b.branch_id = t.branch_id
         LEFT JOIN tblusers u ON u.UserID = t.generated_by
         WHERE t.valid_until >= :now
         ORDER BY t.valid_from DESC LIMIT 20"
        );
        $stmt->execute([':now' => $now]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // ============================================================
// ADMIN â€” Revoke QR Token
// ============================================================
    case 'revoke-qr-token':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $token = $_POST['token'] ?? '';
        if (empty($token)) {
            $result = false;
            $msg = 'الرمز مطلوب';
            break;
        }

        // Delete the token
        $stmt = $connect_pdo->prepare("DELETE FROM attendance_qr_tokens WHERE token = :token");
        $deleted = $stmt->execute([':token' => $token]);

        if ($deleted) {
            $Audit->log(AuditLog::DELETE, AuditLog::ENTITY_QR_TOKEN, 0, "حذف رمز QR: {$token}", null, null);
            $result = true;
            $msg = 'تم حذف رمز QR بنجاح';
        } else {
            $result = false;
            $msg = 'تعذر حذف رمز QR';
        }
        break;

    // ============================================================
// FINGERPRINT â€” Test Connection
// ============================================================
    case 'fingerprint-test-connection':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $deviceIp = $_POST['device_ip'] ?? '';
        $devicePort = (int) ($_POST['device_port'] ?? 4370);
        $deviceType = $_POST['device_type'] ?? 'zkteco';

        if (empty($deviceIp)) {
            $result = false;
            $msg = 'عنوان IP للجهاز مطلوب';
            break;
        }

        try {
            $savedStmt = $connect_pdo->prepare(
                "SELECT FingerprintID, FingerprintName
             FROM tbfingerprint
             WHERE ip = :ip AND port = :port
             ORDER BY FingerprintID DESC
             LIMIT 1"
            );
            $savedStmt->execute([':ip' => $deviceIp, ':port' => $devicePort]);
            $savedDevice = $savedStmt->fetch(PDO::FETCH_ASSOC);

            if ($savedDevice) {
                require_once __DIR__ . '/../shared/BiometricSync.php';

                $sync = new BiometricSync($connect_pdo);
                $testResult = $sync->testConnection((int) $savedDevice['FingerprintID']);

                if (!empty($testResult['connected'])) {
                    $msg = 'تم الاتصال بجهاز البصمة بنجاح';
                    $data = [
                        'connected' => true,
                        'ip' => $deviceIp,
                        'port' => $devicePort,
                        'type' => $deviceType,
                        'serial' => $testResult['serial'] ?? 'غير متوفر',
                        'users' => $testResult['users'] ?? 'غير متوفر',
                        'records' => $testResult['records'] ?? 'غير متوفر',
                        'device_id' => (int) $savedDevice['FingerprintID'],
                        'device_name' => $savedDevice['FingerprintName'] ?? '',
                    ];
                } else {
                    $result = false;
                    $msg = $testResult['error'] ?? 'تعذر الاتصال بجهاز البصمة';
                }
            } else {
                $timeout = 3;
                $socket = @fsockopen($deviceIp, $devicePort, $errno, $errstr, $timeout);

                if ($socket) {
                    fclose($socket);
                    $msg = 'تم الوصول إلى جهاز البصمة عبر الشبكة بنجاح';
                    $data = [
                        'connected' => true,
                        'ip' => $deviceIp,
                        'port' => $devicePort,
                        'type' => $deviceType,
                        'serial' => 'غير متوفر',
                        'users' => 'غير متوفر',
                        'records' => 'غير متوفر',
                        'note' => 'تم التحقق من الاتصال الشبكي فقط. للمزامنة الفعلية، احفظ الجهاز أولًا في شاشة أجهزة البصمة.',
                    ];
                } else {
                    $result = false;
                    $msg = "فشل الاتصال بالجهاز: {$errstr} (رمز الخطأ: {$errno})";
                    $data = ['connected' => false, 'error' => $errstr, 'errno' => $errno];
                }
            }

            $Audit->log(AuditLog::UPDATE, 'fingerprint_device', 0, "اختبار اتصال جهاز البصمة {$deviceIp}:{$devicePort}", null, $_POST);
        } catch (Throwable $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء اختبار الاتصال: ' . $e->getMessage();
        }
        break;

    // ============================================================
// FINGERPRINT â€” Sync Data from Device
// ============================================================
    case 'fingerprint-sync-data':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $deviceIp = $_POST['device_ip'] ?? '';
        $devicePort = (int) ($_POST['device_port'] ?? 4370);
        $deviceType = $_POST['device_type'] ?? 'zkteco';

        if (empty($deviceIp)) {
            $result = false;
            $msg = 'عنوان IP للجهاز مطلوب';
            break;
        }

        try {
            $savedStmt = $connect_pdo->prepare(
                "SELECT FingerprintID, FingerprintName
             FROM tbfingerprint
             WHERE ip = :ip AND port = :port
             ORDER BY FingerprintID DESC
             LIMIT 1"
            );
            $savedStmt->execute([':ip' => $deviceIp, ':port' => $devicePort]);
            $savedDevice = $savedStmt->fetch(PDO::FETCH_ASSOC);

            if (!$savedDevice) {
                $result = false;
                $msg = 'يرجى حفظ جهاز البصمة أولًا داخل قائمة الأجهزة قبل تنفيذ المزامنة';
                break;
            }

            require_once __DIR__ . '/../shared/BiometricSync.php';

            $sync = new BiometricSync($connect_pdo);
            $syncResult = $sync->syncDevice((int) $savedDevice['FingerprintID']);

            $recordsFetched = (int) ($syncResult['records_fetched'] ?? 0);
            $recordsImported = (int) ($syncResult['records_imported'] ?? 0);

            $data = [
                'records_synced' => $recordsImported,
                'records_fetched' => $recordsFetched,
                'records_imported' => $recordsImported,
                'duplicates' => max(0, $recordsFetched - $recordsImported),
                'device_id' => (int) $savedDevice['FingerprintID'],
                'device_name' => $savedDevice['FingerprintName'] ?? '',
                'device_ip' => $deviceIp,
                'device_port' => $devicePort,
                'type' => $deviceType,
            ];

            if (!empty($syncResult['success'])) {
                $msg = 'تمت مزامنة بيانات جهاز البصمة بنجاح';
            } else {
                $result = false;
                $msg = $syncResult['error'] ?? 'تعذرت مزامنة بيانات جهاز البصمة';
            }

            $Audit->log(AuditLog::UPDATE, 'fingerprint_sync', 0, "مزامنة جهاز البصمة {$deviceIp}:{$devicePort}", null, $_POST);
        } catch (Throwable $e) {
            $result = false;
            $msg = 'حدث خطأ أثناء مزامنة جهاز البصمة: ' . $e->getMessage();
        }
        break;

    // ============================================================
// POLICY SYSTEM â€” Leave Policies
// ============================================================
    case 'get-leave-policy':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($connect_pdo);
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $data = $manager->getPolicyById($id);
        if (!$data) {
            $result = false;
            $msg = 'السياسة غير موجودة';
        }
        break;

    case 'save-leave-policy':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($connect_pdo);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;

        $_POST['created_by'] = $user;
        try {
            $savedId = $manager->savePolicy($_POST, $id);
        } catch (RuntimeException $e) {
            $result = false;
            $msg = $e->getMessage() === 'Only one leave policy can be active. Please disable the currently active policy before activating another.'
                ? 'يمكن تفعيل سياسة إجازات واحدة فقط. يرجى تعطيل السياسة النشطة حالياً قبل تفعيل سياسة أخرى.'
                : 'حدث خطأ أثناء حفظ السياسة';
            break;
        }

        if ($savedId) {
            $data = ['id' => $savedId];
            $msg = 'تم حفظ السياسة بنجاح';
            $Audit->log(AuditLog::UPDATE, 'leave_policies', $savedId, $id ? 'تعديل سياسة إجازات' : 'إضافة سياسة إجازات', null, $_POST);
        } else {
            $result = false;
            $msg = 'فشل حفظ السياسة';
        }
        break;

    case 'toggle-leave-policy':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['is_active'] ?? 0);

        if ($active === 1) {
            $stmt = $connect_pdo->prepare("SELECT id FROM leave_policies WHERE is_active = 1 AND id <> ? LIMIT 1");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn()) {
                $result = false;
                $msg = 'يمكن تفعيل سياسة إجازات واحدة فقط. يرجى تعطيل السياسة النشطة حالياً قبل تفعيل سياسة أخرى.';
                break;
            }
        }
        $stmt = $connect_pdo->prepare("UPDATE leave_policies SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        $msg = $active ? 'تم تفعيل السياسة' : 'تم تعطيل السياسة';
        break;

    case 'run-leave-accrual':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($connect_pdo);
        $processed = $manager->processAllEmployeesAccrual();
        $data = ['processed' => $processed];
        $msg = "ØªÙ… Ø§Ø­ØªØ³Ø§Ø¨ Ø§Ù„Ø§Ø³ØªØ­Ù‚Ø§Ù‚ Ù„Ù€ {$processed} Ù…ÙˆØ¸Ù";
        $Audit->log(AuditLog::UPDATE, 'leave_accrual', 0, "ØªØ´ØºÙŠÙ„ Ø§Ù„Ø§Ø³ØªØ­Ù‚Ø§Ù‚ Ø§Ù„Ø´Ù‡Ø±ÙŠ: {$processed} Ù…ÙˆØ¸Ù", null, null);
        break;

    case 'process-year-end':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($connect_pdo);
        $fromYear = (int) ($_POST['from_year'] ?? date('Y') - 1);
        $toYear = (int) ($_POST['to_year'] ?? date('Y'));

        $results = $manager->processYearEndCarryover($fromYear, $toYear);
        $data = $results;
        $msg = 'تمت معالجة نهاية السنة';
        $Audit->log(AuditLog::UPDATE, 'leave_carryover', 0, "Ù…Ø¹Ø§Ù„Ø¬Ø© Ù†Ù‡Ø§ÙŠØ© Ø§Ù„Ø³Ù†Ø©: {$fromYear} -> {$toYear}", null, $results);
        break;

    case 'get-employee-leave-balance':
        $empId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? $user);
        if ($empId != $user && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/LeavePolicyManager.php';
        $manager = new LeavePolicyManager($connect_pdo);
        $data = $manager->getBalanceSummary($empId);
        if (!$data) {
            $result = false;
            $msg = 'لا يوجد رصيد';
        }
        break;

    // ============================================================
// POLICY SYSTEM â€” Violations
// ============================================================
    case 'get-violation-types':
        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $data = $manager->getViolationTypes(!$User->userIsEmployer());
        break;

    case 'get-violation':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $data = $manager->getViolation($id);
        if (!$data) {
            $result = false;
            $msg = 'المخالفة غير موجودة';
        }
        break;

    case 'record-violation':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $_POST['reported_by'] = $user;
        $res = $manager->recordViolation($_POST);

        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
            $Audit->log(AuditLog::CREATE, 'employee_violations', $res['violation_id'], 'تسجيل مخالفة', null, $_POST);
        } else {
            $result = false;
            $msg = $res['message'];
        }
        break;

    case 'update-violation-status':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? null;

        $manager->updateViolationStatus($id, $status, $notes, $user);
        $msg = 'تم تحديث حالة المخالفة';
        $Audit->log(AuditLog::UPDATE, 'employee_violations', $id, "ØªØ­Ø¯ÙŠØ« Ø­Ø§Ù„Ø© Ø§Ù„Ù…Ø®Ø§Ù„ÙØ©: {$status}", null, $_POST);
        break;

    case 'get-employee-violations':
        $empId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? $user);
        if ($empId != $user && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $data = $manager->getEmployeeViolations($empId);
        break;

    case 'get-all-violations':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $data = $manager->getAllViolations($_GET);
        break;

    case 'save-violation-type':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ViolationManager.php';
        $manager = new ViolationManager($connect_pdo);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $_POST['created_by'] = $user;

        $savedId = $manager->saveViolationType($_POST, $id);
        $data = ['id' => $savedId];
        $msg = 'تم حفظ نوع المخالفة';
        break;

    // ============================================================
// POLICY SYSTEM â€” Promotions
// ============================================================
    case 'check-promotion-eligibility':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $empId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
        $data = $manager->checkEligibility($empId);
        break;

    case 'create-promotion-request':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $_POST['requested_by'] = $user;

        $res = $manager->createRequest($_POST);
        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
            $Audit->log(AuditLog::CREATE, 'promotion_requests', $res['request_id'], 'إنشاء طلب ترقية', null, $_POST);
        } else {
            $result = false;
            $msg = $res['message'];
        }
        break;

    case 'approve-promotion':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $id = (int) ($_POST['id'] ?? 0);
        $override = isset($_POST['override_violations']) && $_POST['override_violations'];
        $reason = $_POST['override_reason'] ?? null;

        $res = $manager->approveRequest($id, $user, $override, $reason);
        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
            $Audit->log(AuditLog::UPDATE, 'promotion_requests', $id, 'الموافقة على ترقية', null, $_POST);
        } else {
            $result = false;
            $msg = $res['message'];
            $data = $res;
        }
        break;

    case 'reject-promotion':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $id = (int) ($_POST['id'] ?? 0);
        $reason = $_POST['reason'] ?? '';

        $res = $manager->rejectRequest($id, $user, $reason);
        $msg = $res['message'];
        if (!$res['success'])
            $result = false;
        break;

    case 'get-promotion-requests':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $data = $manager->getRequests($_GET);
        break;

    case 'get-promotion-policies':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $data = $manager->getPolicies();
        break;

    case 'save-promotion-policy':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/PromotionManager.php';
        $manager = new PromotionManager($connect_pdo);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $_POST['created_by'] = $user;

        $savedId = $manager->savePolicy($_POST, $id);
        $data = ['id' => $savedId];
        $msg = 'تم حفظ سياسة الترقيات';
        break;

    // ============================================================
// POLICY SYSTEM â€” External Tasks
// ============================================================
    case 'create-external-task':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $_POST['created_by'] = $user;
        if (empty($_POST['user_id']))
            $_POST['user_id'] = $user;

        // Only employers can create tasks for others
        if ($_POST['user_id'] != $user && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $res = $manager->createTask($_POST);
        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
        } else {
            $result = false;
            $msg = $res['message'];
        }
        break;

    case 'start-external-task':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        $res = $manager->startTask($taskId, $lat, $lng);
        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
        } else {
            $result = false;
            $msg = $res['message'];
        }
        break;

    case 'end-external-task':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;
        $notes = $_POST['notes'] ?? null;

        $res = $manager->endTask($taskId, $lat, $lng, $notes);
        if ($res['success']) {
            $data = $res;
            $msg = $res['message'];
        } else {
            $result = false;
            $msg = $res['message'];
        }
        break;

    case 'get-my-tasks':
        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $data = $manager->getEmployeeTasks($user, $_GET);
        break;

    case 'get-today-tasks':
        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $data = $manager->getTodayTasks($user);
        break;

    case 'get-all-tasks':
        if (!$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/ExternalTaskManager.php';
        $manager = new ExternalTaskManager($connect_pdo);
        $data = $manager->getAllTasks($_GET);
        break;

    // ============================================================
// POLICY SYSTEM â€” Org Chart & Presence
// ============================================================
    case 'get-org-tree':
        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $data = $manager->getOrgTree(true, true);
        break;

    case 'get-presence-summary':
        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $sectionId = $_GET['section_id'] ?? null;
        $data = $manager->getPresenceSummary($sectionId);
        break;

    case 'get-employee-presence':
        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $empId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
        $data = $manager->getEmployeePresence($empId);
        if (!$data) {
            $result = false;
            $msg = 'الموظف غير موجود';
        }
        break;

    case 'update-presence':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $empId = (int) ($_POST['user_id'] ?? $user);

        // Only employers can update others' presence
        if ($empId != $user && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $status = $_POST['status'] ?? null;
        $note = $_POST['note'] ?? null;
        $until = $_POST['until'] ?? null;

        if (empty($status)) {
            $manager->clearManualPresence($empId);
        } else {
            $manager->updatePresence($empId, $status, $note, $until, $user);
        }
        $msg = 'تم تحديث الحالة';
        break;

    case 'save-org-node':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;

        if ($id) {
            $manager->updateNode($id, $_POST);
            $data = ['id' => $id];
        } else {
            $nodeId = $manager->createNode($_POST);
            $data = ['id' => $nodeId];
        }
        $msg = 'تم حفظ العنصر';
        break;

    case 'delete-org-node':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $id = (int) ($_POST['id'] ?? 0);

        $res = $manager->deleteNode($id);
        if (!$res['success'])
            $result = false;
        $msg = $res['message'];
        break;

    case 'get-org-node':
        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $id = (int) ($_GET['id'] ?? 0);
        $data = $manager->getNode($id);
        if (!$data) {
            $result = false;
            $msg = 'العنصر غير موجود';
        }
        break;

    case 'get-node-employees':
        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $nodeId = (int) ($_GET['node_id'] ?? 0);

        $node = $manager->getNode($nodeId);
        if ($node && $node['section_id']) {
            $data = $manager->getNodeEmployees($node['section_id'], true);
        } else {
            $data = [];
        }
        break;

    case 'move-org-node':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);

        $nodeId = (int) ($_POST['node_id'] ?? 0);
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        if (!$nodeId) {
            $result = false;
            $msg = 'معرف العقدة مطلوب';
            break;
        }

        // Prevent moving a node to itself or its children is handled by logic/db usually, 
        // but basic check:
        if ($nodeId === $parentId) {
            $result = false;
            $msg = 'لا يمكن نقل العقدة إلى نفسها';
            break;
        }

        $res = $manager->updateNode($nodeId, ['parent_id' => $parentId]);
        if ($res) {
            $msg = 'تم نقل العقدة بنجاح';
        } else {
            $result = false;
            $msg = 'فشل نقل العقدة';
        }
        break;

    case 'sync-org-from-sections':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/OrgChartManager.php';
        $manager = new OrgChartManager($connect_pdo);
        $created = $manager->syncFromSections();
        $data = ['created' => $created];
        $msg = "ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ {$created} Ø¹Ù†ØµØ± Ù…Ù† Ø§Ù„Ø£Ù‚Ø³Ø§Ù… Ø§Ù„Ù…ÙˆØ¬ÙˆØ¯Ø©";
        break;

    case 'get-pending-approvals':
        require_once __DIR__ . '/../classes/WorkflowManager.php';
        $manager = new WorkflowManager($connect_pdo);
        $data = $manager->getPendingApprovals($_SESSION['UserID']);
        break;

    case 'process-approval':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/WorkflowManager.php';
        $manager = new WorkflowManager($connect_pdo);

        $instanceId = intval($_POST['instance_id'] ?? 0);
        $action = $_POST['approval_action'] ?? '';
        $comment = $_POST['comment'] ?? '';

        if (!$instanceId || !in_array($action, ['approved', 'rejected'])) {
            $result = false;
            $msg = 'بيانات غير صحيحة';
            break;
        }

        $res = $manager->processApproval($instanceId, $_SESSION['UserID'], $action, $comment);
        $result = $res['success'];
        $msg = $res['message'];
        $data = $res;
        break;

    case 'get-workflow-history':
        require_once __DIR__ . '/../classes/WorkflowManager.php';
        $manager = new WorkflowManager($connect_pdo);

        $entityType = $_GET['entity_type'] ?? '';
        $entityId = intval($_GET['entity_id'] ?? 0);

        $data = $manager->getWorkflowHistory($entityType, $entityId);
        break;

    case 'get-workflow-status':
        require_once __DIR__ . '/../classes/WorkflowManager.php';
        $manager = new WorkflowManager($connect_pdo);

        $instanceId = intval($_GET['instance_id'] ?? 0);
        $data = $manager->getInstanceStatus($instanceId);
        break;

    case 'get-workflow-configs':
        // This is already present and used by the main admin-workflows.php for the list
        if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        $stmt = $connect_pdo->query("
        SELECT wc.*, 
               (SELECT COUNT(*) FROM workflow_steps WHERE workflow_id = wc.id) as steps_count
        FROM workflow_configs wc
        ORDER BY wc.entity_type
    ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'save-workflow-config': // This action is specifically for toggling is_active
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        // Ensure 'is_active' is explicitly checked to avoid issues with unchecked checkboxes not sending data
        $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        if ($id) {
            $stmt = $connect_pdo->prepare("UPDATE workflow_configs SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$isActive, $id]);
            $result = true;
            $msg = 'تم تحديث حالة سير العمل.';
        } else {
            $result = false;
            $msg = 'معرف سير العمل غير صالح.';
        }
        break;

    // --- New Workflow Configuration CRUD Actions ---

    case 'add-workflow':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $name_ar = trim($input['name_ar'] ?? '');
        $entity_type = trim($input['entity_type'] ?? '');
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 0;
        $steps = $input['steps'] ?? [];

        if (empty($name_ar) || empty($entity_type)) {
            $result = false;
            $msg = 'الرجاء توفير اسم سير العمل ونوع الكيان.';
            break;
        }
        // --- NEW CHECK FOR DUPLICATE entity_type ---
        $stmtCheck = $connect_pdo->prepare("SELECT COUNT(*) FROM workflow_configs WHERE entity_type = ?");
        $stmtCheck->execute([$entity_type]);
        if ($stmtCheck->fetchColumn() > 0) {
            $result = false;
            $msg = 'يوجد بالفعل سير عمل لهذا النوع من الكيانات. يرجى تعديل السير العمل الموجود بدلاً من إضافة واحد جديد.';
            break; // Stop execution here
        }
        // --- END NEW CHECK ---
        $connect_pdo->beginTransaction();
        try {
            // 1. Insert workflow config
            $stmt = $connect_pdo->prepare("INSERT INTO workflow_configs (name_ar, entity_type, is_active, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$name_ar, $entity_type, $is_active]);
            $workflow_id = $connect_pdo->lastInsertId();

            // 2. Insert workflow steps
            foreach ($steps as $step) {
                $step_name_ar = trim($step['name_ar'] ?? '');
                $step_order = intval($step['step_order'] ?? 0);
                $approver_type = trim($step['approver_type'] ?? '');
                $approver_id = isset($step['approver_id']) && $step['approver_id'] !== '' ? intval($step['approver_id']) : null; // specific user
                $approver_role = isset($step['approver_role']) && $step['approver_role'] !== '' ? intval($step['approver_role']) : null; // role ID

                if (empty($step_name_ar) || empty($approver_type) || $step_order <= 0) {
                    throw new Exception('بيانات خطوة سير العمل غير مكتملة أو غير صالحة.');
                }

                // Validate specific user/role requirements
                if ($approver_type === 'specific_user' && $approver_id === null) {
                    throw new Exception('الرجاء اختيار مستخدم محدد لخطوة الموافق.');
                }
                if ($approver_type === 'role' && $approver_role === null) {
                    throw new Exception('الرجاء اختيار دور محدد لخطوة الموافق.');
                }

                $stmt = $connect_pdo->prepare("INSERT INTO workflow_steps (workflow_id, name_ar, step_order, approver_type, approver_id, approver_role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$workflow_id, $step_name_ar, $step_order, $approver_type, $approver_id, $approver_role]);
            }

            $connect_pdo->commit();
            $result = true;
            $msg = 'تم إضافة سير العمل بنجاح.';

        } catch (Exception $e) {
            $connect_pdo->rollBack();
            $result = false;
            $msg = 'فشل إضافة سير العمل: ' . $e->getMessage();
        }
        break;

    case 'get-workflow-details':
        if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $workflow_id = intval($_GET['id'] ?? 0);

        if (!$workflow_id) {
            $result = false;
            $msg = 'معرف سير العمل غير صالح.';
            break;
        }

        try {
            // Get workflow config details
            $stmt = $connect_pdo->prepare("SELECT * FROM workflow_configs WHERE id = ?");
            $stmt->execute([$workflow_id]);
            $workflow_config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$workflow_config) {
                $result = false;
                $msg = 'لم يتم العثور على سير العمل.';
                break;
            }

            // Get associated steps
            $stmt = $connect_pdo->prepare("SELECT * FROM workflow_steps WHERE workflow_id = ? ORDER BY step_order");
            $stmt->execute([$workflow_id]);
            $workflow_config['steps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = true;
            $data = $workflow_config;

        } catch (Exception $e) {
            $result = false;
            $msg = 'فشل جلب تفاصيل سير العمل: ' . $e->getMessage();
        }
        break;

    case 'update-workflow':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $workflow_id = intval($input['workflow_id'] ?? 0);
        $name_ar = trim($input['name_ar'] ?? '');
        $entity_type = trim($input['entity_type'] ?? '');
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : 0;
        $steps = $input['steps'] ?? [];

        if (!$workflow_id || empty($name_ar) || empty($entity_type)) {
            $result = false;
            $msg = 'الرجاء توفير معرف سير العمل، الاسم، ونوع الكيان.';
            break;
        }

        $connect_pdo->beginTransaction();
        try {
            // 1. Update workflow config
            $stmt = $connect_pdo->prepare("UPDATE workflow_configs SET name_ar = ?, entity_type = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name_ar, $entity_type, $is_active, $workflow_id]);

            // 2. Delete existing steps for this workflow
            $stmt = $connect_pdo->prepare("DELETE FROM workflow_steps WHERE workflow_id = ?");
            $stmt->execute([$workflow_id]);

            // 3. Insert new/updated steps
            foreach ($steps as $step) {
                $step_name_ar = trim($step['name_ar'] ?? '');
                $step_order = intval($step['step_order'] ?? 0);
                $approver_type = trim($step['approver_type'] ?? '');
                $approver_id = isset($step['approver_id']) && $step['approver_id'] !== '' ? intval($step['approver_id']) : null; // specific user
                $approver_role = isset($step['approver_role']) && $step['approver_role'] !== '' ? intval($step['approver_role']) : null; // role ID

                if (empty($step_name_ar) || empty($approver_type) || $step_order <= 0) {
                    throw new Exception('بيانات خطوة سير العمل غير مكتملة أو غير صالحة.');
                }
                // Validate specific user/role requirements
                if ($approver_type === 'specific_user' && $approver_id === null) {
                    throw new Exception('الرجاء اختيار مستخدم محدد لخطوة الموافق.');
                }
                if ($approver_type === 'role' && $approver_role === null) {
                    throw new Exception('الرجاء اختيار دور محدد لخطوة الموافق.');
                }

                $stmt = $connect_pdo->prepare("INSERT INTO workflow_steps (workflow_id, name_ar, step_order, approver_type, approver_id, approver_role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$workflow_id, $step_name_ar, $step_order, $approver_type, $approver_id, $approver_role]);
            }

            $connect_pdo->commit();
            $result = true;
            $msg = 'تم تحديث سير العمل بنجاح.';

        } catch (Exception $e) {
            $connect_pdo->rollBack();
            $result = false;
            $msg = 'فشل تحديث سير العمل: ' . $e->getMessage();
        }
        break;

    // ============================================================
// EVALUATION MANAGEMENT
// ============================================================
    case 'get-evaluation-periods':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getActivePeriods();
        break;

    case 'create-evaluation-period':
        // Check user permissions and request method first
        if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        // Validate inputs (recommended: add more robust validation)
        $name_ar = trim($_POST['name_ar'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');

        if (empty($name_ar) || empty($start_date) || empty($end_date)) {
            $result = false;
            $msg = 'يرجى تعبئة جميع الحقول المطلوبة (الاسم، تاريخ البدء، تاريخ الانتهاء).';
            break;
        }

        try {
            $periodId = $manager->createPeriod([
                'name_ar' => $name_ar,
                'name_en' => trim($_POST['name_en'] ?? null),
                'period_type' => trim($_POST['period_type'] ?? 'annual'),
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

            if ($periodId) {
                $result = true; // <--- This was missing!
                $data = ['id' => $periodId];
                $msg = 'تم إنشاء فترة التقييم بنجاح.';
            } else {
                $result = false;
                $msg = 'فشل في إنشاء فترة التقييم.';
            }
        } catch (Exception $e) {
            $result = false;
            $msg = 'حدث خطأ: ' . $e->getMessage();
            // Log the actual error for debugging
            error_log("Error creating evaluation period: " . $e->getMessage());
        }
        break;

    case 'get-evaluation-criteria':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getCriteria();
        break;

    case 'save-evaluation-criteria':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $criteriaId = $manager->saveCriteria([
            'id' => $_POST['id'] ?? null,
            'name_ar' => $_POST['name_ar'] ?? '',
            'name_en' => $_POST['name_en'] ?? null,
            'category' => $_POST['category'] ?? 'performance',
            'weight' => floatval($_POST['weight'] ?? 1.0),
            'max_score' => intval($_POST['max_score'] ?? 5),
            'description' => $_POST['description'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        ]);

        $data = ['id' => $criteriaId];
        $msg = 'تم حفظ معيار التقييم';
        break;
    case 'acknowledgeEvaluation':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $evalId = intval($_POST['evaluation_id'] ?? 0);
        $employeeComment = $_POST['employee_comment'] ?? null;
        $result = $manager->acknowledgeEvaluation($evalId, $employeeComment);
        $msg = $result ? 'تم إقرار التقييم بنجاح' : 'فشل في إقرار التقييم';
        break;
    case 'create-evaluation':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        // Ensure $_SESSION['UserID'] is always set or provide a fallback
        $evaluatorId = $_SESSION['user_id'] ?? 0; // Changed 'UserID' to 'user_id'
        if ($evaluatorId === 0) {
            $result = false;
            $msg = 'معرف المقيم غير متوفر. يرجى تسجيل الدخول.';
            break;
        }

        try {
            $res = $manager->createEvaluation(
                intval($_POST['employee_id'] ?? 0),
                $evaluatorId, // Use the validated evaluatorId
                intval($_POST['period_id'] ?? 0)
            );

            $result = $res['success']; // <--- This is the key part to ensure success is passed
            $data = $res; // Pass the id back in data
            $msg = $res['message'] ?? ($res['success'] ? 'تم إنشاء التقييم بنجاح.' : 'فشل في إنشاء التقييم.');

        } catch (Exception $e) {
            $result = false;
            $msg = 'حدث خطأ غير متوقع: ' . $e->getMessage();
            error_log("Error in create-evaluation case: " . $e->getMessage());
        }
        break;

    case 'get-evaluation':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $evalId = intval($_GET['id'] ?? 0);
        $data = $manager->getEvaluation($evalId);
        break;

    case 'save-evaluation-scores':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $evalId = intval($_POST['evaluation_id'] ?? 0);
        $scores = json_decode($_POST['scores'] ?? '{}', true);
        $additionalData = [
            'strengths' => $_POST['strengths'] ?? null,
            'weaknesses' => $_POST['weaknesses'] ?? null,
            'recommendations' => $_POST['recommendations'] ?? null
        ];

        $manager->saveScores($evalId, $scores, $additionalData);
        $msg = 'تم حفظ الدرجات';
        break;

    case 'submit-evaluation':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $evalId = intval($_POST['evaluation_id'] ?? 0);
        $result = $manager->submitEvaluation($evalId);
        $msg = $result ? 'تم تقديم التقييم للمراجعة' : 'فشل في تقديم التقييم';
        break;

    case 'approve-evaluation':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $evalId = intval($_POST['evaluation_id'] ?? 0);
        $result = $manager->approveEvaluation($evalId, $_SESSION['UserID']);
        $msg = $result ? 'تم اعتماد التقييم' : 'فشل في اعتماد التقييم';
        break;

    case 'get-employee-evaluations':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $employeeId = intval($_GET['employee_id'] ?? $_SESSION['UserID']);
        $data = $manager->getEmployeeEvaluations($employeeId);
        break;

    case 'get-pending-evaluations':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getPendingEvaluations($_SESSION['UserID']);
        break;

    case 'get-probation-employees':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getEmployeesInProbation();
        break;

    case 'get-evaluation-stats':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $periodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : null;
        $data = $manager->getEvaluationStats($periodId);
        break;

    // ============================================================
// REWARDS MANAGEMENT
// ============================================================
    case 'create-reward':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $rewardId = $manager->createReward([
            'employee_id' => intval($_POST['employee_id'] ?? 0),
            'reward_type' => $_POST['reward_type'] ?? 'bonus',
            'title_ar' => $_POST['title_ar'] ?? '',
            'title_en' => $_POST['title_en'] ?? null,
            'description' => $_POST['description'] ?? null,
            'amount' => floatval($_POST['amount'] ?? 0),
            'currency' => $_POST['currency'] ?? 'SAR',
            'linked_evaluation_id' => $_POST['linked_evaluation_id'] ?? null,
            'awarded_by' => $_SESSION['user_id'],
            'awarded_date' => $_POST['awarded_date'] ?? date('Y-m-d')
        ]);

        $data = ['id' => $rewardId];
        $msg = 'تم إنشاء المكافأة';
        break;

    case 'approve-reward':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $rewardId = intval($_POST['reward_id'] ?? 0);
        $result = $manager->approveReward($rewardId);
        $msg = $result ? 'تم اعتماد المكافأة' : 'فشل في اعتماد المكافأة';
        break;

    case 'deliver-reward':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $rewardId = intval($_POST['reward_id'] ?? 0);
        $result = $manager->deliverReward($rewardId);
        $msg = $result ? 'تم تسليم المكافأة' : 'فشل في تسليم المكافأة';
        break;

    case 'get-employee-rewards':
        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $employeeId = intval($_GET['employee_id'] ?? $_SESSION['UserID']);
        $data = $manager->getEmployeeRewards($employeeId);
        break;

    case 'get-pending-rewards':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getPendingRewards();
        break;

    case 'get-reward-stats':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $data = $manager->getRewardStats($year);
        break;

    // ============================================================
// SALARY RANGES
// ============================================================
    case 'get-salary-ranges':
        if (!$User->userIsEmployer() && !$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);
        $data = $manager->getAllSalaryRanges();
        break;

    case 'save-salary-range':
        if (!$User->userIsAdmin()) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        require_once __DIR__ . '/../classes/EvaluationManager.php';
        $manager = new EvaluationManager($connect_pdo);

        try {
            // Convert empty strings to null for optional fields
            $gradeId = !empty($_POST['grade_id']) ? intval($_POST['grade_id']) : null;
            $jobTitleId = !empty($_POST['job_title_id']) ? intval($_POST['job_title_id']) : null;
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;

            $rangeId = $manager->saveSalaryRange([
                'id' => !empty($_POST['id']) ? intval($_POST['id']) : null,
                'section_id' => intval($_POST['section_id'] ?? 0),
                'grade_id' => $gradeId,
                'job_title_id' => $jobTitleId,
                'min_salary' => floatval($_POST['min_salary'] ?? 0),
                'max_salary' => floatval($_POST['max_salary'] ?? 0),
                'currency' => $_POST['currency'] ?? 'SAR',
                'effective_date' => $_POST['effective_date'] ?? date('Y-m-d'),
                'notes' => $notes,
                'created_by' => $_SESSION['user_id']
            ]);

            $data = ['id' => $rangeId];
            $msg = 'تم حفظ نطاق الراتب';
        } catch (Exception $e) {
            $result = false;
            $msg = 'خطأ: ' . $e->getMessage();
            error_log("save-salary-range error: " . $e->getMessage());
        }
        break;

    // ============================================================
// NOTIFICATIONS
// ============================================================
    case 'get-notifications':
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['UserID'] ?? null;
        if (!$userId) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/NotificationService.php';
        $service = new NotificationService($connect_pdo);

        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);
        $data = $service->getNotifications($userId, $limit, $offset);
        break;

    case 'get-unread-notifications':
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['UserID'] ?? null;
        if (!$userId) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/NotificationService.php';
        $service = new NotificationService($connect_pdo);
        $summary = $service->getUnreadSummary($userId, 5);
        $data = [
            'items' => $summary['items'] ?? [],
            'count' => (int) ($summary['count'] ?? 0),
        ];
        $responseMeta['count'] = $data['count'];
        break;

    case 'get-notification-count':
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['UserID'] ?? null;
        if (!$userId) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }
        require_once __DIR__ . '/../classes/NotificationService.php';
        $service = new NotificationService($connect_pdo);
        $summary = $service->getUnreadSummary($userId, 5);
        $data = ['count' => (int) ($summary['count'] ?? 0)];
        break;

    case 'mark-notification-read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['UserID'] ?? null;
        if (!$userId) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/NotificationService.php';
        $service = new NotificationService($connect_pdo);

        $notificationId = intval($_POST['notification_id'] ?? 0);
        $result = $service->markAsRead($notificationId, $userId);
        break;

    case 'mark-all-notifications-read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['UserID'] ?? null;
        if (!$userId) {
            $result = false;
            $msg = 'غير مصرح';
            break;
        }

        require_once __DIR__ . '/../classes/NotificationService.php';
        $service = new NotificationService($connect_pdo);
        $result = $service->markAllAsRead($userId);
        $msg = 'تم تحديد جميع الإشعارات كمقروءة';
        break;

    // ============================================================
// DIRECT APPROVE/REJECT (for items not in workflow system)
// ============================================================
    case 'direct-approve':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $type = $_POST['type'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if (!$type || !$id) {
            $result = false;
            $msg = 'البيانات غير صحيحة';
            break;
        }

        try {
            $notifyUserId = 0;
            $notifyTitle = '';
            $notifyBody = '';
            $notifyEntityType = $type;
            $canStoreRejectReasonOnOrders = tableHasColumn($connect_pdo, 'emp_order', 'reject_reason');
            $canStoreRejectReasonOnLeaves = tableHasColumn($connect_pdo, 'tblleaverequest', 'reject_reason');
            $canStoreRejectReasonOnAdvances = tableHasColumn($connect_pdo, 'tblempadvances', 'reject_reason');

            switch ($type) {
                case 'order':
                    $infoStmt = $connect_pdo->prepare("SELECT UserID, title FROM emp_order WHERE id = ?");
                    $infoStmt->execute([$id]);
                    $infoRow = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    if ($canStoreRejectReasonOnOrders) {
                        $stmt = $connect_pdo->prepare("UPDATE emp_order SET Status = ?, reject_reason = ? WHERE id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE emp_order SET Status = ? WHERE id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    $notifyUserId = (int) ($infoRow['UserID'] ?? 0);
                    $notifyTitle = $status == 1 ? 'تم اعتماد الطلب الإداري' : 'تم رفض الطلب الإداري';
                    $notifyBody = $status == 1
                        ? 'تم اعتماد الطلب الإداري: ' . ($infoRow['title'] ?? ('#' . $id))
                        : 'تم رفض الطلب الإداري: ' . ($infoRow['title'] ?? ('#' . $id)) . ($comment !== '' ? " - السبب: $comment" : '');
                    break;
                case 'leave':
                    $infoStmt = $connect_pdo->prepare("SELECT UserID, leave_start_date, leave_end_date FROM tblleaverequest WHERE id = ?");
                    $infoStmt->execute([$id]);
                    $infoRow = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    if ($canStoreRejectReasonOnLeaves) {
                        $stmt = $connect_pdo->prepare("UPDATE tblleaverequest SET status = ?, reject_reason = ? WHERE id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE tblleaverequest SET status = ? WHERE id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    $notifyUserId = (int) ($infoRow['UserID'] ?? 0);
                    $leaveRange = trim(($infoRow['leave_start_date'] ?? '') . ' - ' . ($infoRow['leave_end_date'] ?? ''));
                    $notifyTitle = $status == 1 ? 'تم اعتماد طلب الإجازة' : 'تم رفض طلب الإجازة';
                    $notifyBody = $status == 1
                        ? "تم اعتماد طلب الإجازة $leaveRange"
                        : "تم رفض طلب الإجازة $leaveRange" . ($comment !== '' ? " - السبب: $comment" : '');
                    break;
                case 'advance':
                    $infoStmt = $connect_pdo->prepare("SELECT UserID, Amount, currency FROM tblempadvances WHERE Id = ?");
                    $infoStmt->execute([$id]);
                    $infoRow = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    if ($canStoreRejectReasonOnAdvances) {
                        $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET status = ?, reject_reason = ? WHERE Id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET status = ? WHERE Id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    $notifyUserId = (int) ($infoRow['UserID'] ?? 0);
                    $amountText = number_format((float) ($infoRow['Amount'] ?? 0), 2) . ' ' . ($infoRow['currency'] ?? 'SAR');
                    $notifyTitle = $status == 1 ? 'تم اعتماد طلب السلفة' : 'تم رفض طلب السلفة';
                    $notifyBody = $status == 1
                        ? "تم اعتماد طلب السلفة بمبلغ $amountText"
                        : "تم رفض طلب السلفة بمبلغ $amountText" . ($comment !== '' ? " - السبب: $comment" : '');
                    break;
                default:
                    $result = false;
                    $msg = 'نوع غير معروف';
                    break 2;
            }

            if ($notifyUserId > 0) {
                try {
                    require_once __DIR__ . '/../classes/NotificationService.php';
                    $notifService = new NotificationService($connect_pdo);
                    $notifService->notify(
                        $notifyUserId,
                        $notifyTitle,
                        $notifyBody,
                        $status == 1 ? 'success' : 'warning',
                        $notifyEntityType,
                        $id
                    );
                } catch (Throwable $notificationError) {
                }
            }

            $msg = $status == 1 ? 'تمت الموافقة بنجاح' : 'تم الرفض بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'حدث خطأ في قاعدة البيانات';
        }
        break;

    case 'direct-approve-legacy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة الطلب غير صحيحة';
            break;
        }

        $type = $_POST['type'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $comment = $_POST['comment'] ?? '';

        if (!$type || !$id) {
            $result = false;
            $msg = 'البيانات غير صحيحة';
            break;
        }

        try {
            $canStoreRejectReasonOnOrders = tableHasColumn($connect_pdo, 'emp_order', 'reject_reason');
            $canStoreRejectReasonOnLeaves = tableHasColumn($connect_pdo, 'tblleaverequest', 'reject_reason');
            $canStoreRejectReasonOnAdvances = tableHasColumn($connect_pdo, 'tblempadvances', 'reject_reason');
            switch ($type) {
                case 'order':
                    if ($canStoreRejectReasonOnOrders) {
                        $stmt = $connect_pdo->prepare("UPDATE emp_order SET Status = ?, reject_reason = ? WHERE id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE emp_order SET Status = ? WHERE id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    break;
                case 'leave':
                    if ($canStoreRejectReasonOnLeaves) {
                        $stmt = $connect_pdo->prepare("UPDATE tblleaverequest SET status = ?, reject_reason = ? WHERE id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE tblleaverequest SET status = ? WHERE id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    break;
                case 'advance':
                    if ($canStoreRejectReasonOnAdvances) {
                        $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET status = ?, reject_reason = ? WHERE Id = ?");
                        $stmt->execute([$status, $comment, $id]);
                    } else {
                        $stmt = $connect_pdo->prepare("UPDATE tblempadvances SET status = ? WHERE Id = ?");
                        $stmt->execute([$status, $id]);
                    }
                    break;
                default:
                    $result = false;
                    $msg = 'نوع غير معروف';
                    break 2;
            }
            $msg = $status == 1 ? 'تمت الموافقة بنجاح' : 'تم الرفض بنجاح';
        } catch (PDOException $e) {
            $result = false;
            $msg = 'حدث خطأ في قاعدة البيانات';
        }
        break;

    case 'get-order-details':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            $result = false;
            $msg = 'معرف غير صالح';
            break;
        }

        $stmt = $connect_pdo->prepare("
        SELECT o.*, u.FirstName, u.LastName, b.branch_name
        FROM emp_order o
        LEFT JOIN tblusers u ON u.UserID = o.UserID
        LEFT JOIN branches b ON b.branch_id = o.BranchID
        WHERE o.id = ?
    ");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $data = [
                'الموضوع' => $order['title'],
                'التفاصيل' => $order['description'],
                'مقدم الطلب' => $order['FirstName'] . ' ' . $order['LastName'],
                'الفرع' => $order['branch_name'],
                'تاريخ الطلب' => $order['CreatedDate'],
                'الحالة' => $order['Status'] === null ? 'معلق' : ($order['Status'] == 1 ? 'موافق' : 'مرفوض')
            ];
        } else {
            $result = false;
            $msg = 'الطلب غير موجود';
        }
        break;

    // ============================================================
// REMOVE CONFIRMATION MODALS (return HTML, not JSON)
// ============================================================
    case 'Benefits-remove-modal':
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">معرف غير صالح</div>';
            exit;
        }
        $stmt = $connect_pdo->prepare("SELECT b.*, br.branch_name FROM tblbenefit b LEFT JOIN branches br ON b.BranchID = br.branch_id WHERE b.Id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo '<div class="alert alert-danger">التعويض غير موجود</div>';
            exit;
        }
        echo '<div class="alert alert-warning"><h5>تأكيد الحذف</h5><p>هل أنت متأكد من حذف التعويض: <strong>' . htmlspecialchars($row['name'] ?? '') . '</strong>؟</p><p class="text-muted">الفرع: ' . htmlspecialchars($row['branch_name'] ?? '-') . '</p><p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p></div>';
        echo '<form id="deleteForm"><input type="hidden" name="id" value="' . $id . '"><div class="text-center"><button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> حذف</button> <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> إلغاء</button></div></form>';
        echo '<script>$("#deleteForm").on("submit",function(e){e.preventDefault();$.ajax({url:"hr-app/index.php?action=Benefits-remove",type:"POST",data:$(this).serialize(),dataType:"json",success:function(r){if(r.result){$("#modal_default").modal("hide");toastr.success(r.msg);if($.fn.DataTable.isDataTable("#data_tb")){$("#data_tb").DataTable().ajax.reload();}}else{toastr.error(r.msg);}},error:function(){toastr.error("حدث خطأ أثناء الحذف");}});});</script>';
        exit;

    case 'deductions-remove-modal':
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">معرف غير صالح</div>';
            exit;
        }
        $stmt = $connect_pdo->prepare("SELECT d.*, b.branch_name FROM tbldeductions d LEFT JOIN branches b ON d.BranchID = b.branch_id WHERE d.Id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo '<div class="alert alert-danger">الخصم غير موجود</div>';
            exit;
        }
        echo '<div class="alert alert-warning"><h5>تأكيد الحذف</h5><p>هل أنت متأكد من حذف الخصم: <strong>' . htmlspecialchars($row['name'] ?? '') . '</strong>؟</p><p class="text-muted">الفرع: ' . htmlspecialchars($row['branch_name'] ?? '-') . '</p><p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p></div>';
        echo '<form id="deleteForm"><input type="hidden" name="id" value="' . $id . '"><div class="text-center"><button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> حذف</button> <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> إلغاء</button></div></form>';
        echo '<script>$("#deleteForm").on("submit",function(e){e.preventDefault();$.ajax({url:"hr-app/index.php?action=deductions-remove",type:"POST",data:$(this).serialize(),dataType:"json",success:function(r){if(r.result){$("#modal_default").modal("hide");toastr.success(r.msg);if($.fn.DataTable.isDataTable("#data_tb")){$("#data_tb").DataTable().ajax.reload();}}else{toastr.error(r.msg);}},error:function(){toastr.error("حدث خطأ أثناء الحذف");}});});</script>';
        exit;

    case 'incentive-remove-modal':
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">معرف غير صالح</div>';
            exit;
        }
        $stmt = $connect_pdo->prepare("SELECT i.*, b.branch_name FROM tblincentives i LEFT JOIN branches b ON i.BranchID = b.branch_id WHERE i.Id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo '<div class="alert alert-danger">المكافأة غير موجودة</div>';
            exit;
        }
        echo '<div class="alert alert-warning"><h5>تأكيد الحذف</h5><p>هل أنت متأكد من حذف المكافأة: <strong>' . htmlspecialchars($row['name'] ?? '') . '</strong>؟</p><p class="text-muted">الفرع: ' . htmlspecialchars($row['branch_name'] ?? '-') . '</p><p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p></div>';
        echo '<form id="deleteForm"><input type="hidden" name="id" value="' . $id . '"><div class="text-center"><button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> حذف</button> <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> إلغاء</button></div></form>';
        echo '<script>$("#deleteForm").on("submit",function(e){e.preventDefault();$.ajax({url:"hr-app/index.php?action=incentive-remove",type:"POST",data:$(this).serialize(),dataType:"json",success:function(r){if(r.result){$("#modal_default").modal("hide");toastr.success(r.msg);if($.fn.DataTable.isDataTable("#data_tb")){$("#data_tb").DataTable().ajax.reload();}}else{toastr.error(r.msg);}},error:function(){toastr.error("حدث خطأ أثناء الحذف");}});});</script>';
        exit;

    case 'EmpAdvances-remove-modal':
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">معرف غير صالح</div>';
            exit;
        }
        $stmt = $connect_pdo->prepare("SELECT a.*, b.branch_name, CONCAT(u.FirstName,' ',u.LastName) as EmpName FROM tblempadvances a LEFT JOIN branches b ON a.BranchID = b.branch_id LEFT JOIN tblusers u ON a.UserID = u.UserID WHERE a.Id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo '<div class="alert alert-danger">السلفة غير موجودة</div>';
            exit;
        }
        echo '<div class="alert alert-warning"><h5>تأكيد الحذف</h5><p>هل أنت متأكد من حذف سلفة الموظف: <strong>' . htmlspecialchars($row['EmpName'] ?? '') . '</strong>؟</p><p class="text-muted">المبلغ: ' . htmlspecialchars($row['Amount'] ?? '0') . '</p><p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p></div>';
        echo '<form id="deleteForm"><input type="hidden" name="id" value="' . $id . '"><div class="text-center"><button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> حذف</button> <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> إلغاء</button></div></form>';
        echo '<script>$("#deleteForm").on("submit",function(e){e.preventDefault();$.ajax({url:"hr-app/index.php?action=EmpAdvances-remove",type:"POST",data:$(this).serialize(),dataType:"json",success:function(r){if(r.result){$("#modal_default").modal("hide");toastr.success(r.msg);if($.fn.DataTable.isDataTable("#data_tb")){$("#data_tb").DataTable().ajax.reload();}}else{toastr.error(r.msg);}},error:function(){toastr.error("حدث خطأ أثناء الحذف");}});});</script>';
        exit;

    // ============================================================
// DEFAULT
// ============================================================
    default:
        $result = false;
        $msg = 'Action not found: ' . $action;
        break;
}

// Clean any previous output (whitespace, warnings, etc)
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
$payload = ['result' => $result, 'msg' => $msg, 'data' => $data];
$payload = array_merge($payload, $responseMeta);

if ($allowDebugResponse) {
    $payload['debug_session'] = $debug_data;
}

if (is_array($data) && array_key_exists('id', $data)) {
    $payload['id'] = $data['id'];
}
echo json_encode($payload, JSON_UNESCAPED_UNICODE);
exit;
