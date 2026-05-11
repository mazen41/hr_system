<?php
$screen = 'لوحة تحكم المدير';
$page_title = 'لوحة تحكم المدير';
include_once('inc/header.php');

// Only admin/employer can access
if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
    echo '<script>location.replace("ess-dashboard");</script>';
    die();
}

$currency = $User->currency ?? 'SAR';
$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');
$monthStart = "$currentYear-$currentMonth-01";
$monthEnd = date('Y-m-t');

// ── 1. Employee Stats ──
$stmTotal = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0) AND BranchID IN (SELECT branch_id FROM branches WHERE branch_id = :bid OR :bid2 = 0)");
$branchFilter = $branch ?: 0;
$stmTotal->execute([':bid' => $branchFilter, ':bid2' => $branchFilter]);
$totalEmployees = (int)$stmTotal->fetchColumn();

$stmNew = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0) AND CreatedDate >= :ms AND CreatedDate <= :me");
$stmNew->execute([':ms' => $monthStart, ':me' => $monthEnd . ' 23:59:59']);
$newEmployeesThisMonth = (int)$stmNew->fetchColumn();

$stmDisabled = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND IsDisabled = 1");
$stmDisabled->execute();
$disabledEmployees = (int)$stmDisabled->fetchColumn();

// ── 2. Attendance Today ──
$stmAttIn = $connect_pdo->prepare("SELECT COUNT(DISTINCT EmpID) FROM attendancet WHERE Date = :d AND Type = 1");
$stmAttIn->execute([':d' => $today]);
$presentToday = (int)$stmAttIn->fetchColumn();

$absentToday = max(0, $totalEmployees - $presentToday);

$stmLate = $connect_pdo->prepare("
    SELECT COUNT(DISTINCT a.EmpID) FROM attendancet a
    INNER JOIN tblusers u ON u.UserID = a.EmpID
    INNER JOIN tblremewal r ON r.Id = u.lastversion
    INNER JOIN tbshift s ON s.ShiftID = r.shiftID
    WHERE a.Date = :d AND a.Type = 1
    AND a.Time > s.ShiftStartTime
");
$stmLate->execute([':d' => $today]);
$lateToday = (int)$stmLate->fetchColumn();

// ── 3. Leave Stats ──
$stmPendingLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE status IS NULL AND Draft = 1");
$stmPendingLeaves->execute();
$pendingLeaves = (int)$stmPendingLeaves->fetchColumn();

$stmApprovedLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE status = 1 AND leave_start_date <= :d AND leave_end_date >= :d2");
$stmApprovedLeaves->execute([':d' => $today, ':d2' => $today]);
$onLeaveToday = (int)$stmApprovedLeaves->fetchColumn();

$stmMonthLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE CreatedDate >= :ms AND CreatedDate <= :me");
$stmMonthLeaves->execute([':ms' => $monthStart, ':me' => $monthEnd]);
$leavesThisMonth = (int)$stmMonthLeaves->fetchColumn();

// ── 4. Financial Stats ──
$stmPendingAdvances = $connect_pdo->prepare("SELECT COUNT(*) FROM tblempadvances WHERE Status IS NULL OR Status = 0");
$stmPendingAdvances->execute();
$pendingAdvances = (int)$stmPendingAdvances->fetchColumn();

$stmTotalAdvances = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE Status = 1 AND CreatedDate >= :ms AND CreatedDate <= :me");
$stmTotalAdvances->execute([':ms' => $monthStart, ':me' => $monthEnd . ' 23:59:59']);
$totalAdvancesMonth = (float)$stmTotalAdvances->fetchColumn();

$stmTotalBenefits = $connect_pdo->prepare("SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))),0) FROM tblbenefit WHERE Status = 1");
$stmTotalBenefits->execute();
$totalBenefits = (float)$stmTotalBenefits->fetchColumn();

$stmTotalDeductions = $connect_pdo->prepare("SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))),0) FROM tbldeductions WHERE Status = 1");
$stmTotalDeductions->execute();
$totalDeductions = (float)$stmTotalDeductions->fetchColumn();

// ── 5. Contract Expiry ──
$stmExpiring = $connect_pdo->prepare("
    SELECT COUNT(*) FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    WHERE r.new_e_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL 30 DAY)
    AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
");
$stmExpiring->execute([':today' => $today, ':today2' => $today]);
$expiringContracts = (int)$stmExpiring->fetchColumn();

// ── 6. Pending Resignations ──
$stmPendingResign = $connect_pdo->prepare("SELECT COUNT(*) FROM tblresignation WHERE Status IS NULL OR Status = 0");
$stmPendingResign->execute();
$pendingResignations = (int)$stmPendingResign->fetchColumn();

// ── 7. Pending Orders ──
$stmPendingOrders = $connect_pdo->prepare("SELECT COUNT(*) FROM emp_order WHERE Status IS NULL AND Draft = 1");
$stmPendingOrders->execute();
$pendingOrders = (int)$stmPendingOrders->fetchColumn();

// ── 8. Department Distribution ──
$stmDepts = $connect_pdo->prepare("
    SELECT s.Name, COUNT(u.UserID) as cnt
    FROM tblusers u
    INNER JOIN tblremewal r ON r.Id = u.lastversion
    INNER JOIN tblsection s ON s.Id = r.SectionID
    WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
    GROUP BY s.Id, s.Name
    ORDER BY cnt DESC
    LIMIT 10
");
$stmDepts->execute();
$deptDistribution = $stmDepts->fetchAll(PDO::FETCH_ASSOC);
$deptLabels = array_column($deptDistribution, 'Name');
$deptCounts = array_column($deptDistribution, 'cnt');

// ── 9. Monthly Attendance Trend (last 7 days) ──
$attendanceTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $stmDay = $connect_pdo->prepare("SELECT COUNT(DISTINCT EmpID) FROM attendancet WHERE Date = :d AND Type = 1");
    $stmDay->execute([':d' => $d]);
    $attendanceTrend[] = [
        'date' => $d,
        'day' => ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'][date('w', strtotime($d))],
        'count' => (int)$stmDay->fetchColumn()
    ];
}
$trendLabels = array_column($attendanceTrend, 'day');
$trendCounts = array_column($attendanceTrend, 'count');

// ── 10. Recent Leave Requests ──
$stmRecentLeaves = $connect_pdo->prepare("
    SELECT l.Id, l.leave_start_date as StartDate, l.leave_end_date as EndDate, l.day_leave as Days, l.status as Status, l.CreatedDate,
           u.FirstName, u.LastName,
           lc.Name as LeaveType
    FROM tblleaverequest l
    INNER JOIN tblusers u ON u.UserID = l.UserID
    LEFT JOIN leaveclassification lc ON lc.Id = l.leavetype
    ORDER BY l.Id DESC LIMIT 5
");
$stmRecentLeaves->execute();
$recentLeaves = $stmRecentLeaves->fetchAll(PDO::FETCH_ASSOC);

// ── 11. Recent Advances ──
$stmRecentAdv = $connect_pdo->prepare("
    SELECT a.Id, a.Amount, a.currency, a.Status, a.CreatedDate,
           u.FirstName, u.LastName
    FROM tblempadvances a
    INNER JOIN tblusers u ON u.UserID = a.UserID
    ORDER BY a.Id DESC LIMIT 5
");
$stmRecentAdv->execute();
$recentAdvances = $stmRecentAdv->fetchAll(PDO::FETCH_ASSOC);

// ── 12. Expiring Contracts List ──
$stmExpiringList = $connect_pdo->prepare("
    SELECT u.UserID, u.FirstName, u.LastName, u.Photo,
           r.new_e_date, r.Salary,
           jt.Name as JobTitle
    FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
    WHERE r.new_e_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL 60 DAY)
    AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
    ORDER BY r.new_e_date ASC
    LIMIT 5
");
$stmExpiringList->execute([':today' => $today, ':today2' => $today]);
$expiringList = $stmExpiringList->fetchAll(PDO::FETCH_ASSOC);

// ── 13. Document Expiry Alerts ──
$stmDocExpiry = $connect_pdo->prepare("
    SELECT UserID, FirstName, LastName,
           CASE
               WHEN end_date_h IS NOT NULL AND end_date_h BETWEEN :t1 AND DATE_ADD(:t2, INTERVAL 30 DAY) THEN 'هوية'
               WHEN end_date_license IS NOT NULL AND end_date_license BETWEEN :t3 AND DATE_ADD(:t4, INTERVAL 30 DAY) THEN 'رخصة'
               WHEN end_date_passport IS NOT NULL AND end_date_passport BETWEEN :t5 AND DATE_ADD(:t6, INTERVAL 30 DAY) THEN 'جواز'
               WHEN end_date_health IS NOT NULL AND end_date_health BETWEEN :t7 AND DATE_ADD(:t8, INTERVAL 30 DAY) THEN 'صحي'
           END as doc_type,
           LEAST(
               COALESCE(end_date_h, '2099-12-31'),
               COALESCE(end_date_license, '2099-12-31'),
               COALESCE(end_date_passport, '2099-12-31'),
               COALESCE(end_date_health, '2099-12-31')
           ) as earliest_expiry
    FROM tblusers
    WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0)
    AND (
        (end_date_h IS NOT NULL AND end_date_h BETWEEN :t9 AND DATE_ADD(:t10, INTERVAL 30 DAY))
        OR (end_date_license IS NOT NULL AND end_date_license BETWEEN :t11 AND DATE_ADD(:t12, INTERVAL 30 DAY))
        OR (end_date_passport IS NOT NULL AND end_date_passport BETWEEN :t13 AND DATE_ADD(:t14, INTERVAL 30 DAY))
        OR (end_date_health IS NOT NULL AND end_date_health BETWEEN :t15 AND DATE_ADD(:t16, INTERVAL 30 DAY))
    )
    ORDER BY earliest_expiry ASC
    LIMIT 5
");
$docParams = [];
for ($i = 1; $i <= 16; $i++) {
    $docParams[":t$i"] = $today;
}
$stmDocExpiry->execute($docParams);
$docExpiryAlerts = $stmDocExpiry->fetchAll(PDO::FETCH_ASSOC);

// ── 14. Salary Overview ──
$stmTotalSalary = $connect_pdo->prepare("
    SELECT COALESCE(SUM(r.Salary), 0) FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
");
$stmTotalSalary->execute();
$totalMonthlySalary = (float)$stmTotalSalary->fetchColumn();

// Attendance rate
$attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;

// Total pending approvals
$totalPending = $pendingLeaves + $pendingAdvances + $pendingResignations + $pendingOrders;
?>

<section class="content dash-page">
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1 text-brand-primary" style="font-weight:800;">لوحة تحكم المدير</h4>
            <p class="mb-0 text-muted" style="font-size:14px;">
                <i class="far fa-calendar-alt"></i>
                <?= ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'][date('w')] ?>
                ، <?= date('Y-m-d') ?>
            </p>
        </div>
        <?php if ($totalPending > 0): ?>
        <div>
            <span class="brand-badge brand-badge-warning" style="font-size:14px; padding:8px 16px;">
                <i class="fas fa-bell"></i>
                <?= $totalPending ?> طلب بانتظار الموافقة
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════ ROW 1: Main Stat Cards ═══════ -->
    <div class="row">
        <!-- Total Employees -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-brand-gradient">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= $totalEmployees ?></div>
                <div class="stat-label">إجمالي الموظفين</div>
                <?php if ($newEmployeesThisMonth > 0): ?>
                <div class="stat-change up"><i class="fas fa-arrow-up"></i> +<?= $newEmployeesThisMonth ?> هذا الشهر</div>
                <?php else: ?>
                <div class="stat-change neutral">لا يوجد موظفين جدد هذا الشهر</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Present Today -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-brand-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?= $presentToday ?></div>
                <div class="stat-label">حاضرون اليوم</div>
                <div class="stat-change <?= $attendanceRate >= 80 ? 'up' : ($attendanceRate >= 50 ? 'neutral' : 'down') ?>">
                    <i class="fas fa-chart-pie"></i> نسبة الحضور: <?= $attendanceRate ?>%
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-brand-warning">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-value"><?= $totalPending ?></div>
                <div class="stat-label">طلبات معلقة</div>
                <div class="stat-change neutral">
                    <i class="fas fa-info-circle"></i>
                    <?= $pendingLeaves ?> إجازة ·
                    <?= $pendingAdvances ?> سلفة ·
                    <?= $pendingResignations ?> استقالة
                </div>
            </div>
        </div>

        <!-- Monthly Salary -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-brand-purple">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value" style="font-size:24px;"><?= number_format($totalMonthlySalary, 0) ?></div>
                <div class="stat-label">إجمالي الرواتب الشهرية (<?= $currency ?>)</div>
                <div class="stat-change neutral">
                    <i class="fas fa-calculator"></i>
                    متوسط: <?= $totalEmployees > 0 ? number_format($totalMonthlySalary / $totalEmployees, 0) : 0 ?> <?= $currency ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ ROW 2: Secondary Stats ═══════ -->
    <div class="row">
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value text-danger" style="font-size:28px;"><?= $absentToday ?></div>
                <div class="stat-label">غائبون اليوم</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value text-warning" style="font-size:28px;"><?= $lateToday ?></div>
                <div class="stat-label">متأخرون اليوم</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value text-info" style="font-size:28px;"><?= $onLeaveToday ?></div>
                <div class="stat-label">في إجازة اليوم</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value text-purple" style="font-size:28px;"><?= $expiringContracts ?></div>
                <div class="stat-label">عقود تنتهي قريباً</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value" style="font-size:28px; color:#ec4899;"><?= $disabledEmployees ?></div>
                <div class="stat-label">موظفون موقوفون</div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-3">
            <div class="stat-card text-center" style="padding:18px;">
                <div class="stat-value" style="font-size:28px; color:#14b8a6;"><?= $leavesThisMonth ?></div>
                <div class="stat-label">إجازات هذا الشهر</div>
            </div>
        </div>
    </div>

    <!-- ═══════ ROW 3: Charts ═══════ -->
    <div class="row mb-4">
        <!-- Attendance Trend Chart -->
        <div class="col-lg-8 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-chart-line text-primary"></i> حضور آخر 7 أيام</h5>
                    <span style="font-size:13px; color:#6b7280;">من إجمالي <?= $totalEmployees ?> موظف</span>
                </div>
                <div class="dash-card-body">
                    <canvas id="attendanceChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Distribution -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-sitemap text-info"></i> توزيع الأقسام</h5>
                </div>
                <div class="dash-card-body">
                    <?php if (!empty($deptDistribution)): ?>
                    <canvas id="deptChart" height="200"></canvas>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-building fa-2x mb-2" style="opacity:0.3;"></i>
                        <p>لا توجد بيانات أقسام</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ ROW 4: Financial Overview ═══════ -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-wallet text-success"></i> ملخص مالي</h5>
                </div>
                <div class="dash-card-body">
                    <div class="mini-stat">
                        <div class="mini-stat-dot" style="background:#10b981;"></div>
                        <div class="mini-stat-label">إجمالي التعويضات</div>
                        <div class="mini-stat-value"><?= number_format($totalBenefits, 0) ?> <small><?= $currency ?></small></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-dot" style="background:#ef4444;"></div>
                        <div class="mini-stat-label">إجمالي الخصومات</div>
                        <div class="mini-stat-value"><?= number_format($totalDeductions, 0) ?> <small><?= $currency ?></small></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-dot" style="background:#f59e0b;"></div>
                        <div class="mini-stat-label">سلف هذا الشهر</div>
                        <div class="mini-stat-value"><?= number_format($totalAdvancesMonth, 0) ?> <small><?= $currency ?></small></div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-dot" style="background:#8b5cf6;"></div>
                        <div class="mini-stat-label">إجمالي الرواتب</div>
                        <div class="mini-stat-value"><?= number_format($totalMonthlySalary, 0) ?> <small><?= $currency ?></small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Leave Requests -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-calendar-alt text-warning"></i> آخر طلبات الإجازات</h5>
                    <a href="leaveRequest-list-admin" style="font-size:13px; color:#3b82f6; font-weight:600;">عرض الكل</a>
                </div>
                <div class="dash-card-body" style="padding:12px 16px;">
                    <?php if (!empty($recentLeaves)): ?>
                    <?php foreach ($recentLeaves as $lv): ?>
                    <div class="alert-item">
                        <div class="alert-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <?= mb_substr($lv['FirstName'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div class="alert-info">
                            <div class="alert-name"><?= htmlspecialchars($lv['FirstName'] . ' ' . $lv['LastName']) ?></div>
                            <div class="alert-detail"><?= htmlspecialchars($lv['LeaveType'] ?? 'إجازة') ?> · <?= $lv['Days'] ?? '-' ?> يوم</div>
                        </div>
                        <?php
                        $statusClass = 'status-pending';
                        $statusText = 'معلق';
                        if ($lv['Status'] == 1) { $statusClass = 'status-approved'; $statusText = 'مقبول'; }
                        elseif ($lv['Status'] == 2) { $statusClass = 'status-rejected'; $statusText = 'مرفوض'; }
                        ?>
                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted"><p>لا توجد طلبات إجازات</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Advances -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-hand-holding-usd text-danger"></i> آخر طلبات السلف</h5>
                    <a href="EmpAdvances-list-admin" style="font-size:13px; color:#3b82f6; font-weight:600;">عرض الكل</a>
                </div>
                <div class="dash-card-body" style="padding:12px 16px;">
                    <?php if (!empty($recentAdvances)): ?>
                    <?php foreach ($recentAdvances as $adv): ?>
                    <div class="alert-item">
                        <div class="alert-avatar" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <?= mb_substr($adv['FirstName'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div class="alert-info">
                            <div class="alert-name"><?= htmlspecialchars($adv['FirstName'] . ' ' . $adv['LastName']) ?></div>
                            <div class="alert-detail"><?= number_format($adv['Amount'], 0) ?> <?= $adv['currency'] ?? $currency ?></div>
                        </div>
                        <?php
                        $aStatusClass = 'status-pending';
                        $aStatusText = 'معلق';
                        if ($adv['Status'] == 1) { $aStatusClass = 'status-approved'; $aStatusText = 'مقبول'; }
                        elseif ($adv['Status'] == 2) { $aStatusClass = 'status-rejected'; $aStatusText = 'مرفوض'; }
                        ?>
                        <span class="status-badge <?= $aStatusClass ?>"><?= $aStatusText ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted"><p>لا توجد طلبات سلف</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════ ROW 5: Alerts & Quick Actions ═══════ -->
    <div class="row mb-4">
        <!-- Expiring Contracts -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-exclamation-triangle text-danger"></i> عقود تنتهي قريباً</h5>
                    <a href="contractRenewal-list" style="font-size:13px; color:#3b82f6; font-weight:600;">عرض الكل</a>
                </div>
                <div class="dash-card-body" style="padding:12px 16px;">
                    <?php if (!empty($expiringList)): ?>
                    <?php foreach ($expiringList as $exp): ?>
                    <?php
                        $daysLeft = (int)((strtotime($exp['new_e_date']) - strtotime($today)) / 86400);
                        $urgency = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info');
                    ?>
                    <div class="alert-item">
                        <div class="alert-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <?= mb_substr($exp['FirstName'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div class="alert-info">
                            <div class="alert-name"><?= htmlspecialchars($exp['FirstName'] . ' ' . $exp['LastName']) ?></div>
                            <div class="alert-detail"><?= htmlspecialchars($exp['JobTitle'] ?? '-') ?> · <?= $exp['new_e_date'] ?></div>
                        </div>
                        <span class="status-badge" style="background:<?= $urgency == 'danger' ? '#fee2e2' : ($urgency == 'warning' ? '#fef3c7' : '#dbeafe') ?>; color:<?= $urgency == 'danger' ? '#991b1b' : ($urgency == 'warning' ? '#92400e' : '#1e40af') ?>;">
                            <?= $daysLeft ?> يوم
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i>
                        <p>لا توجد عقود تنتهي قريباً</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Document Expiry Alerts -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-id-card text-warning"></i> وثائق تنتهي قريباً</h5>
                </div>
                <div class="dash-card-body" style="padding:12px 16px;">
                    <?php if (!empty($docExpiryAlerts)): ?>
                    <?php foreach ($docExpiryAlerts as $doc): ?>
                    <div class="alert-item">
                        <div class="alert-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-id-card" style="font-size:16px;"></i>
                        </div>
                        <div class="alert-info">
                            <div class="alert-name"><?= htmlspecialchars($doc['FirstName'] . ' ' . $doc['LastName']) ?></div>
                            <div class="alert-detail"><?= $doc['doc_type'] ?? 'وثيقة' ?> · <?= $doc['earliest_expiry'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2" style="color:#10b981;"></i>
                        <p>لا توجد وثائق تنتهي قريباً</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-3">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fas fa-bolt text-primary"></i> إجراءات سريعة</h5>
                </div>
                <div class="dash-card-body">
                    <div class="row">
                        <div class="col-4 mb-3">
                            <a href="employer-add" class="quick-action">
                                <i class="fas fa-user-plus"></i>
                                <span>إضافة موظف</span>
                            </a>
                        </div>
                        <div class="col-4 mb-3">
                            <a href="leaveRequest-list-admin" class="quick-action">
                                <i class="fas fa-calendar-check"></i>
                                <span>الإجازات</span>
                            </a>
                        </div>
                        <div class="col-4 mb-3">
                            <a href="reveal-attendance" class="quick-action">
                                <i class="fas fa-clipboard-list"></i>
                                <span>كشف الحضور</span>
                            </a>
                        </div>
                        <div class="col-4 mb-3">
                            <a href="Issuing-salaries" class="quick-action">
                                <i class="fas fa-money-check-alt"></i>
                                <span>الرواتب</span>
                            </a>
                        </div>
                        <div class="col-4 mb-3">
                            <a href="contractRenewal-add" class="quick-action">
                                <i class="fas fa-file-signature"></i>
                                <span>عقد جديد</span>
                            </a>
                        </div>
                        <div class="col-4 mb-3">
                            <a href="report-center" class="quick-action">
                                <i class="fas fa-chart-bar"></i>
                                <span>التقارير</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

<?php include_once('inc/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {

    // ── Attendance Trend Chart ──
    const attCtx = document.getElementById('attendanceChart');
    if (attCtx) {
        new Chart(attCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($trendLabels) ?>,
                datasets: [{
                    label: 'عدد الحاضرين',
                    data: <?= json_encode($trendCounts) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 50
                }, {
                    label: 'إجمالي الموظفين',
                    data: Array(7).fill(<?= $totalEmployees ?>),
                    type: 'line',
                    borderColor: 'rgba(239, 68, 68, 0.5)',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true, position: 'top', align: 'start', rtl: true,
                        labels: { font: { family: 'Tajawal', size: 13, weight: '600' }, usePointStyle: true, padding: 20 }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { family: 'Tajawal', size: 12 }, stepSize: 1 }
                    },
                    x: { grid: { display: false },
                        ticks: { font: { family: 'Tajawal', size: 13, weight: '600' } }
                    }
                }
            }
        });
    }

    // ── Department Distribution Chart ──
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx) {
        const deptColors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
        ];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [{
                    data: <?= json_encode($deptCounts) ?>,
                    backgroundColor: deptColors.slice(0, <?= count($deptLabels) ?>),
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', rtl: true,
                        labels: { font: { family: 'Tajawal', size: 12, weight: '600' }, usePointStyle: true, padding: 12 }
                    }
                }
            }
        });
    }

});
</script>
