<?php
/**
 * Vision HR - Dashboard Controller
 * Stats for employee home screen and manager dashboard
 */

class DashboardController
{
    /**
     * GET /dashboard/employee
     * Employee home screen stats
     */
    public static function employee(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();
        $uid = $apiUser['id'];
        $branchId = $apiUser['branch_id'];
        $today = date('Y-m-d');
        $year = date('Y');
        $month = date('m');

        // 1. Today's attendance status
        $stm = $connect_pdo->prepare(
            "SELECT Type, Time FROM tblattendance
             WHERE EmpID = :uid AND Date = :date ORDER BY AttendanceID ASC"
        );
        $stm->execute([':uid' => $uid, ':date' => $today]);
        $attRecords = $stm->fetchAll(PDO::FETCH_ASSOC);

        $checkIn = null;
        $checkOut = null;
        $attStatus = 'absent';
        foreach ($attRecords as $r) {
            if ((int) $r['Type'] === 1 && !$checkIn) { $checkIn = $r['Time']; $attStatus = 'checked_in'; }
            if ((int) $r['Type'] === 2) { $checkOut = $r['Time']; $attStatus = 'checked_out'; }
        }

        // 2. Leave balance summary
        $stm2 = $connect_pdo->prepare(
            "SELECT lc.Name, lc.max_days,
                    COALESCE(SUM(CASE WHEN l.Status = 1 THEN l.Days ELSE 0 END), 0) as used,
                    COALESCE(SUM(CASE WHEN l.Status = 0 THEN l.Days ELSE 0 END), 0) as pending
             FROM leaveclassification lc
             LEFT JOIN tblleave l ON l.LeaveTypeID = lc.Id AND l.UserID = :uid AND YEAR(l.StartDate) = :year
             WHERE (lc.BranchID = :branch OR lc.BranchID IS NULL) AND (lc.state IS NULL OR lc.state = 1)
             GROUP BY lc.Id, lc.Name, lc.max_days"
        );
        $stm2->execute([':uid' => $uid, ':branch' => $branchId, ':year' => $year]);
        $leaveBalances = $stm2->fetchAll(PDO::FETCH_ASSOC);

        $totalLeaveMax = 0;
        $totalLeaveUsed = 0;
        $leaveSummary = [];
        foreach ($leaveBalances as $lb) {
            $max = $lb['max_days'] ? (int) $lb['max_days'] : null;
            $used = (int) $lb['used'];
            $remaining = $max !== null ? max(0, $max - $used) : null;
            $totalLeaveMax += $max ?? 0;
            $totalLeaveUsed += $used;
            $leaveSummary[] = [
                'name'      => $lb['Name'],
                'max'       => $max,
                'used'      => $used,
                'pending'   => (int) $lb['pending'],
                'remaining' => $remaining,
            ];
        }

        // 3. Pending requests count
        $stm3 = $connect_pdo->prepare(
            "SELECT 
                (SELECT COUNT(*) FROM tblleave WHERE UserID = :uid1 AND Status = 0) as pending_leaves,
                (SELECT COUNT(*) FROM tblempadvances WHERE UserID = :uid2 AND Status IS NULL) as pending_advances,
                (SELECT COUNT(*) FROM tblorders WHERE UserID = :uid3 AND Status IS NULL) as pending_orders"
        );
        $stm3->execute([':uid1' => $uid, ':uid2' => $uid, ':uid3' => $uid]);
        $pending = $stm3->fetch(PDO::FETCH_ASSOC);

        // 4. Unread notifications
        $stm4 = $connect_pdo->prepare(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stm4->execute([':uid' => $uid]);
        $unread = (int) $stm4->fetch(PDO::FETCH_ASSOC)['cnt'];

        // 5. This month attendance summary
        $monthStart = date('Y-m-01');
        $stm5 = $connect_pdo->prepare(
            "SELECT COUNT(DISTINCT Date) as days_present
             FROM tblattendance
             WHERE EmpID = :uid AND Date BETWEEN :start AND :end AND Type = 1"
        );
        $stm5->execute([':uid' => $uid, ':start' => $monthStart, ':end' => $today]);
        $daysPresent = (int) $stm5->fetch(PDO::FETCH_ASSOC)['days_present'];

        // 6. Salary info
        $salary = $apiUser['contract']['Salary'] ?? 0;
        $currency = $apiUser['contract']['Currency'] ?? 'SAR';

        // 7. Contract expiry warning
        $contractEnd = $apiUser['contract']['new_e_date'] ?? null;
        $contractWarning = null;
        if ($contractEnd) {
            $daysLeft = (int) ((strtotime($contractEnd) - time()) / 86400);
            if ($daysLeft <= 30 && $daysLeft >= 0) {
                $contractWarning = ['days_left' => $daysLeft, 'end_date' => $contractEnd];
            }
        }

        Response::success([
            'attendance' => [
                'status'    => $attStatus,
                'check_in'  => $checkIn,
                'check_out' => $checkOut,
                'days_present_this_month' => $daysPresent,
            ],
            'leave_balance' => [
                'total_max'       => $totalLeaveMax,
                'total_used'      => $totalLeaveUsed,
                'total_remaining' => max(0, $totalLeaveMax - $totalLeaveUsed),
                'details'         => $leaveSummary,
            ],
            'pending_requests' => [
                'leaves'   => (int) $pending['pending_leaves'],
                'advances' => (int) $pending['pending_advances'],
                'orders'   => (int) $pending['pending_orders'],
                'total'    => (int) $pending['pending_leaves'] + (int) $pending['pending_advances'] + (int) $pending['pending_orders'],
            ],
            'unread_notifications' => $unread,
            'salary' => [
                'basic'    => (float) $salary,
                'currency' => $currency,
            ],
            'contract_warning' => $contractWarning,
        ]);
    }

    /**
     * GET /dashboard/manager
     * Manager dashboard stats
     */
    public static function manager(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        if (empty($apiUser['is_admin'])) {
            requireManager($apiUser);
        }

        $today = date('Y-m-d');
        $uid = $apiUser['id'];

        // Get subordinate IDs
        if (!empty($apiUser['is_admin'])) {
            $stm = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE IsDisabled IS NULL AND isemp = 1");
            $stm->execute();
        } else {
            $stm = $connect_pdo->prepare("SELECT UserID FROM tblusers WHERE manager = :uid AND IsDisabled IS NULL");
            $stm->execute([':uid' => $uid]);
        }
        $subIds = array_column($stm->fetchAll(PDO::FETCH_ASSOC), 'UserID');
        $totalEmployees = count($subIds);

        if (empty($subIds)) {
            Response::success([
                'total_employees' => 0,
                'attendance_today' => ['present' => 0, 'absent' => 0, 'late' => 0],
                'pending_approvals' => ['total' => 0],
            ]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($subIds), '?'));

        // 1. Today's attendance
        $stm2 = $connect_pdo->prepare(
            "SELECT COUNT(DISTINCT EmpID) as present
             FROM tblattendance
             WHERE EmpID IN ($placeholders) AND Date = ? AND Type = 1"
        );
        $params = array_map('intval', $subIds);
        $params[] = $today;
        $stm2->execute($params);
        $present = (int) $stm2->fetch(PDO::FETCH_ASSOC)['present'];
        $absent = $totalEmployees - $present;

        // 2. Late employees today
        $lateCount = 0;
        // We check each present employee against their shift
        $stm3 = $connect_pdo->prepare(
            "SELECT a.EmpID, MIN(a.Time) as first_in,
                    sh.ShiftStartTime, ss.late_tolerance
             FROM tblattendance a
             JOIN tblusers u ON u.UserID = a.EmpID
             LEFT JOIN tblremewal r ON r.Id = u.lastversion
             LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
             LEFT JOIN shift_setting ss ON ss.shift_id = r.shiftID
             WHERE a.EmpID IN ($placeholders) AND a.Date = ? AND a.Type = 1
             GROUP BY a.EmpID, sh.ShiftStartTime, ss.late_tolerance"
        );
        $params2 = array_map('intval', $subIds);
        $params2[] = $today;
        $stm3->execute($params2);
        while ($row = $stm3->fetch(PDO::FETCH_ASSOC)) {
            if ($row['ShiftStartTime'] && $row['first_in']) {
                $tolerance = (int) ($row['late_tolerance'] ?? 0);
                $shiftStart = strtotime($row['ShiftStartTime']);
                $actualIn = strtotime($row['first_in']);
                if ($actualIn > $shiftStart + ($tolerance * 60)) {
                    $lateCount++;
                }
            }
        }

        // 3. Pending approvals
        $stm4 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM tblleave WHERE UserID IN ($placeholders) AND Status = 0");
        $stm4->execute(array_map('intval', $subIds));
        $pendingLeaves = (int) $stm4->fetch(PDO::FETCH_ASSOC)['cnt'];

        $stm5 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM tblempadvances WHERE UserID IN ($placeholders) AND Status IS NULL");
        $stm5->execute(array_map('intval', $subIds));
        $pendingAdvances = (int) $stm5->fetch(PDO::FETCH_ASSOC)['cnt'];

        $stm6 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM tblresignation WHERE UserID IN ($placeholders) AND Status IS NULL");
        $stm6->execute(array_map('intval', $subIds));
        $pendingResignations = (int) $stm6->fetch(PDO::FETCH_ASSOC)['cnt'];

        $stm7 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM tblorders WHERE UserID IN ($placeholders) AND Status IS NULL");
        $stm7->execute(array_map('intval', $subIds));
        $pendingOrders = (int) $stm7->fetch(PDO::FETCH_ASSOC)['cnt'];

        $stm8 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM tblfinger_forget WHERE UserID IN ($placeholders) AND Status IS NULL");
        $stm8->execute(array_map('intval', $subIds));
        $pendingFingerForget = (int) $stm8->fetch(PDO::FETCH_ASSOC)['cnt'];

        $totalPending = $pendingLeaves + $pendingAdvances + $pendingResignations + $pendingOrders + $pendingFingerForget;

        // 4. Employees on leave today
        $stm9 = $connect_pdo->prepare(
            "SELECT COUNT(*) as cnt FROM tblleave
             WHERE UserID IN ($placeholders) AND Status = 1 AND ? BETWEEN StartDate AND EndDate"
        );
        $params9 = array_map('intval', $subIds);
        $params9[] = $today;
        $stm9->execute($params9);
        $onLeave = (int) $stm9->fetch(PDO::FETCH_ASSOC)['cnt'];

        // 5. Expiring contracts (next 30 days)
        $stm10 = $connect_pdo->prepare(
            "SELECT COUNT(*) as cnt FROM tblremewal r
             JOIN tblusers u ON u.lastversion = r.Id
             WHERE u.UserID IN ($placeholders) AND r.new_e_date BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)"
        );
        $params10 = array_map('intval', $subIds);
        $params10[] = $today;
        $params10[] = $today;
        $stm10->execute($params10);
        $expiringContracts = (int) $stm10->fetch(PDO::FETCH_ASSOC)['cnt'];

        Response::success([
            'total_employees' => $totalEmployees,
            'attendance_today' => [
                'present'  => $present,
                'absent'   => $absent,
                'late'     => $lateCount,
                'on_leave' => $onLeave,
            ],
            'pending_approvals' => [
                'leaves'        => $pendingLeaves,
                'advances'      => $pendingAdvances,
                'resignations'  => $pendingResignations,
                'orders'        => $pendingOrders,
                'finger_forget' => $pendingFingerForget,
                'total'         => $totalPending,
            ],
            'expiring_contracts' => $expiringContracts,
        ]);
    }
}
