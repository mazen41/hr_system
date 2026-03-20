<?php
/**
 * Vision HR - Modern Employer Dashboard
 * Built with Tailwind CSS + Alpine.js
 * Responsive, component-based, Vue-like UX
 */
$screen = 'لوحة تحكم المدير';
$page_title = 'لوحة تحكم المدير';
include_once('inc/header.php');

// Components
include_once('components/stat-card.php');
include_once('components/card.php');
include_once('components/badge.php');

// Authorization
if (!$User->userIsAdmin() && !$User->userIsEmployer()) {
    header('Location: ess-dashboard');
    exit;
}

$currency = $User->currency ?? 'ر.س';
$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');
$monthStart = "$currentYear-$currentMonth-01";
$monthEnd = date('Y-m-t');

// ── Data Queries (same as original) ──
$branchFilter = $branch ?: 0;

// Total Employees
$stmTotal = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0) AND BranchID IN (SELECT branch_id FROM branches WHERE branch_id = :bid OR :bid2 = 0)");
$stmTotal->execute([':bid' => $branchFilter, ':bid2' => $branchFilter]);
$totalEmployees = (int)$stmTotal->fetchColumn();

// New this month
$stmNew = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND (IsDisabled IS NULL OR IsDisabled = 0) AND CreatedDate >= :ms AND CreatedDate <= :me");
$stmNew->execute([':ms' => $monthStart, ':me' => $monthEnd . ' 23:59:59']);
$newEmployeesThisMonth = (int)$stmNew->fetchColumn();

// Disabled
$stmDisabled = $connect_pdo->prepare("SELECT COUNT(*) FROM tblusers WHERE isemp = 1 AND IsDisabled = 1");
$stmDisabled->execute();
$disabledEmployees = (int)$stmDisabled->fetchColumn();

// Attendance Today
$stmAttIn = $connect_pdo->prepare("SELECT COUNT(DISTINCT EmpID) FROM attendancet WHERE Date = :d AND Type = 1");
$stmAttIn->execute([':d' => $today]);
$presentToday = (int)$stmAttIn->fetchColumn();
$absentToday = max(0, $totalEmployees - $presentToday);

// Late Today
$stmLate = $connect_pdo->prepare("
    SELECT COUNT(DISTINCT a.EmpID) FROM attendancet a
    INNER JOIN tblusers u ON u.UserID = a.EmpID
    INNER JOIN tblremewal r ON r.Id = u.lastversion
    INNER JOIN tbshift s ON s.ShiftID = r.shiftID
    WHERE a.Date = :d AND a.Type = 1 AND a.Time > s.ShiftStartTime
");
$stmLate->execute([':d' => $today]);
$lateToday = (int)$stmLate->fetchColumn();

// Pending Leaves
$stmPendingLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE status IS NULL AND Draft = 1");
$stmPendingLeaves->execute();
$pendingLeaves = (int)$stmPendingLeaves->fetchColumn();

// On Leave Today
$stmApprovedLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE status = 1 AND leave_start_date <= :d AND leave_end_date >= :d2");
$stmApprovedLeaves->execute([':d' => $today, ':d2' => $today]);
$onLeaveToday = (int)$stmApprovedLeaves->fetchColumn();

// Leaves this month
$stmMonthLeaves = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE CreatedDate >= :ms AND CreatedDate <= :me");
$stmMonthLeaves->execute([':ms' => $monthStart, ':me' => $monthEnd]);
$leavesThisMonth = (int)$stmMonthLeaves->fetchColumn();

// Pending Advances
$stmPendingAdvances = $connect_pdo->prepare("SELECT COUNT(*) FROM tblempadvances WHERE Status IS NULL OR Status = 0");
$stmPendingAdvances->execute();
$pendingAdvances = (int)$stmPendingAdvances->fetchColumn();

// Total Advances this month
$stmTotalAdvances = $connect_pdo->prepare("SELECT COALESCE(SUM(Amount),0) FROM tblempadvances WHERE Status = 1 AND CreatedDate >= :ms AND CreatedDate <= :me");
$stmTotalAdvances->execute([':ms' => $monthStart, ':me' => $monthEnd . ' 23:59:59']);
$totalAdvancesMonth = (float)$stmTotalAdvances->fetchColumn();

// Total Benefits
$stmTotalBenefits = $connect_pdo->prepare("SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))),0) FROM tblbenefit WHERE Status = 1");
$stmTotalBenefits->execute();
$totalBenefits = (float)$stmTotalBenefits->fetchColumn();

// Total Deductions
$stmTotalDeductions = $connect_pdo->prepare("SELECT COALESCE(SUM(CAST(Amount AS DECIMAL(12,2))),0) FROM tbldeductions WHERE Status = 1");
$stmTotalDeductions->execute();
$totalDeductions = (float)$stmTotalDeductions->fetchColumn();

// Expiring Contracts
$stmExpiring = $connect_pdo->prepare("
    SELECT COUNT(*) FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    WHERE r.new_e_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL 30 DAY)
    AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
");
$stmExpiring->execute([':today' => $today, ':today2' => $today]);
$expiringContracts = (int)$stmExpiring->fetchColumn();

// Pending Resignations
$stmPendingResign = $connect_pdo->prepare("SELECT COUNT(*) FROM tblresignation WHERE Status IS NULL OR Status = 0");
$stmPendingResign->execute();
$pendingResignations = (int)$stmPendingResign->fetchColumn();

// Pending Orders
$stmPendingOrders = $connect_pdo->prepare("SELECT COUNT(*) FROM emp_order WHERE Status IS NULL AND Draft = 1");
$stmPendingOrders->execute();
$pendingOrders = (int)$stmPendingOrders->fetchColumn();

// Total Monthly Salary
$stmTotalSalary = $connect_pdo->prepare("
    SELECT COALESCE(SUM(r.Salary), 0) FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
");
$stmTotalSalary->execute();
$totalMonthlySalary = (float)$stmTotalSalary->fetchColumn();

// Department Distribution
$stmDepts = $connect_pdo->prepare("
    SELECT s.Name, COUNT(u.UserID) as cnt
    FROM tblusers u
    INNER JOIN tblremewal r ON r.Id = u.lastversion
    INNER JOIN tblsection s ON s.Id = r.SectionID
    WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
    GROUP BY s.Id, s.Name ORDER BY cnt DESC LIMIT 6
");
$stmDepts->execute();
$deptDistribution = $stmDepts->fetchAll(PDO::FETCH_ASSOC);
$deptLabels = array_column($deptDistribution, 'Name');
$deptCounts = array_column($deptDistribution, 'cnt');

// Attendance Trend (last 7 days)
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

// Recent Leave Requests
$stmRecentLeaves = $connect_pdo->prepare("
    SELECT l.Id, l.leave_start_date as StartDate, l.leave_end_date as EndDate, l.day_leave as Days, l.status as Status, l.CreatedDate,
           u.FirstName, u.LastName, lc.Name as LeaveType
    FROM tblleaverequest l
    INNER JOIN tblusers u ON u.UserID = l.UserID
    LEFT JOIN leaveclassification lc ON lc.Id = l.leavetype
    ORDER BY l.Id DESC LIMIT 5
");
$stmRecentLeaves->execute();
$recentLeaves = $stmRecentLeaves->fetchAll(PDO::FETCH_ASSOC);

// Recent Advances
$stmRecentAdv = $connect_pdo->prepare("
    SELECT a.Id, a.Amount, a.currency, a.Status, a.CreatedDate, u.FirstName, u.LastName
    FROM tblempadvances a
    INNER JOIN tblusers u ON u.UserID = a.UserID
    ORDER BY a.Id DESC LIMIT 5
");
$stmRecentAdv->execute();
$recentAdvances = $stmRecentAdv->fetchAll(PDO::FETCH_ASSOC);

// Expiring Contracts List
$stmExpiringList = $connect_pdo->prepare("
    SELECT u.UserID, u.FirstName, u.LastName, u.Photo, r.new_e_date, r.Salary, jt.Name as JobTitle
    FROM tblremewal r
    INNER JOIN tblusers u ON u.lastversion = r.Id
    LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
    WHERE r.new_e_date BETWEEN :today AND DATE_ADD(:today2, INTERVAL 60 DAY)
    AND (u.IsDisabled IS NULL OR u.IsDisabled = 0)
    ORDER BY r.new_e_date ASC LIMIT 5
");
$stmExpiringList->execute([':today' => $today, ':today2' => $today]);
$expiringList = $stmExpiringList->fetchAll(PDO::FETCH_ASSOC);

// Calculated values
$attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0;
$totalPending = $pendingLeaves + $pendingAdvances + $pendingResignations + $pendingOrders;

// Arabic day name
$dayNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$todayName = $dayNames[date('w')];
?>

<!-- Load Design System CSS -->
<link rel="stylesheet" href="dist/css/tailwind-design-system.css?v=<?= time() ?>">

<section class="content" x-data="{ showStats: true }">
<div class="container-fluid py-4 px-3 lg:px-6">

    <!-- ═══════ HEADER ═══════ -->
    <div class="vhr-dashboard-header vhr-fade-in">
        <div>
            <h1 class="vhr-dashboard-title">لوحة تحكم المدير</h1>
            <div class="vhr-dashboard-date">
                <i class="far fa-calendar-alt"></i>
                <span><?= $todayName ?>، <?= date('Y-m-d') ?></span>
            </div>
        </div>
        <?php if ($totalPending > 0): ?>
        <a href="leaveRequest-list-admin" class="vhr-badge-solid-warning vhr-text-sm" style="padding: 0.5rem 1rem; text-decoration: none;">
            <i class="fas fa-bell"></i>
            <?= $totalPending ?> طلب بانتظار الموافقة
        </a>
        <?php endif; ?>
    </div>

    <!-- ═══════ MAIN STATS GRID ═══════ -->
    <div class="vhr-grid vhr-grid-4 vhr-mb-6">
        <?php renderStatCard([
            'value' => $totalEmployees,
            'label' => 'إجمالي الموظفين',
            'icon' => 'fas fa-users',
            'color' => 'brand',
            'trend' => $newEmployeesThisMonth > 0 ? "+$newEmployeesThisMonth هذا الشهر" : null,
            'trend_dir' => 'up',
            'link' => 'employer-list'
        ]); ?>
        
        <?php renderStatCard([
            'value' => $presentToday,
            'label' => 'حاضرون اليوم',
            'icon' => 'fas fa-user-check',
            'color' => 'success',
            'trend' => "نسبة الحضور: {$attendanceRate}%",
            'trend_dir' => $attendanceRate >= 70 ? 'up' : 'down',
            'link' => 'reveal-attendance'
        ]); ?>
        
        <?php renderStatCard([
            'value' => $totalPending,
            'label' => 'طلبات معلقة',
            'icon' => 'fas fa-clipboard-check',
            'color' => 'warning',
            'link' => 'leaveRequest-list-admin'
        ]); ?>
        
        <?php renderStatCard([
            'value' => number_format($totalMonthlySalary, 0),
            'label' => "إجمالي الرواتب الشهرية ($currency)",
            'icon' => 'fas fa-money-bill-wave',
            'color' => 'info',
            'link' => 'Issuing-salaries'
        ]); ?>
    </div>

    <!-- ═══════ SECONDARY STATS ═══════ -->
    <div class="vhr-grid" style="grid-template-columns: repeat(6, 1fr);" class="vhr-mb-6">
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value vhr-text-danger" style="font-size: 1.75rem;"><?= $absentToday ?></div>
            <div class="vhr-stat-label">غائبون اليوم</div>
        </div>
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value vhr-text-warning" style="font-size: 1.75rem;"><?= $lateToday ?></div>
            <div class="vhr-stat-label">متأخرون اليوم</div>
        </div>
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value" style="font-size: 1.75rem; color: #06b6d4;"><?= $onLeaveToday ?></div>
            <div class="vhr-stat-label">في إجازة اليوم</div>
        </div>
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value" style="font-size: 1.75rem; color: #8b5cf6;"><?= $expiringContracts ?></div>
            <div class="vhr-stat-label">عقود تنتهي قريباً</div>
        </div>
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value" style="font-size: 1.75rem; color: #ec4899;"><?= $disabledEmployees ?></div>
            <div class="vhr-stat-label">موظفون موقوفون</div>
        </div>
        <div class="vhr-stat-card vhr-fade-in" style="flex-direction: column; text-align: center; padding: 1rem;">
            <div class="vhr-stat-value" style="font-size: 1.75rem; color: #14b8a6;"><?= $leavesThisMonth ?></div>
            <div class="vhr-stat-label">إجازات هذا الشهر</div>
        </div>
    </div>

    <!-- ═══════ CHARTS ROW ═══════ -->
    <div class="vhr-grid vhr-grid-3 vhr-mb-6" style="grid-template-columns: 2fr 1fr;">
        <!-- Attendance Chart -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-chart-line vhr-text-brand" style="margin-left: 0.5rem;"></i> حضور آخر 7 أيام</span>
                <span class="vhr-text-sm vhr-text-gray-500">من إجمالي <?= $totalEmployees ?> موظف</span>
            </div>
            <div class="vhr-card-body">
                <canvas id="attendanceChart" height="120"></canvas>
            </div>
        </div>

        <!-- Department Distribution -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-sitemap" style="margin-left: 0.5rem; color: #06b6d4;"></i> توزيع الأقسام</span>
            </div>
            <div class="vhr-card-body">
                <?php if (!empty($deptDistribution)): ?>
                <canvas id="deptChart" height="200"></canvas>
                <?php else: ?>
                <div class="vhr-empty-state">
                    <i class="fas fa-building vhr-empty-state-icon"></i>
                    <div class="vhr-empty-state-text">لا توجد بيانات أقسام</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════ FINANCIAL + RECENT REQUESTS ═══════ -->
    <div class="vhr-grid vhr-grid-3 vhr-mb-6">
        <!-- Financial Summary -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-wallet vhr-text-success" style="margin-left: 0.5rem;"></i> ملخص مالي</span>
            </div>
            <div class="vhr-card-body">
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-mb-4" style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--vhr-success);"></div>
                    <div class="vhr-flex-col" style="flex: 1;">
                        <span class="vhr-text-sm vhr-text-gray-500">إجمالي التعويضات</span>
                        <span class="vhr-font-bold"><?= number_format($totalBenefits, 0) ?> <small class="vhr-text-gray-400"><?= $currency ?></small></span>
                    </div>
                </div>
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-mb-4" style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--vhr-danger);"></div>
                    <div class="vhr-flex-col" style="flex: 1;">
                        <span class="vhr-text-sm vhr-text-gray-500">إجمالي الخصومات</span>
                        <span class="vhr-font-bold"><?= number_format($totalDeductions, 0) ?> <small class="vhr-text-gray-400"><?= $currency ?></small></span>
                    </div>
                </div>
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-mb-4" style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--vhr-warning);"></div>
                    <div class="vhr-flex-col" style="flex: 1;">
                        <span class="vhr-text-sm vhr-text-gray-500">سلف هذا الشهر</span>
                        <span class="vhr-font-bold"><?= number_format($totalAdvancesMonth, 0) ?> <small class="vhr-text-gray-400"><?= $currency ?></small></span>
                    </div>
                </div>
                <div class="vhr-flex vhr-items-center vhr-gap-3">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #8b5cf6;"></div>
                    <div class="vhr-flex-col" style="flex: 1;">
                        <span class="vhr-text-sm vhr-text-gray-500">إجمالي الرواتب</span>
                        <span class="vhr-font-bold"><?= number_format($totalMonthlySalary, 0) ?> <small class="vhr-text-gray-400"><?= $currency ?></small></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Leaves -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-calendar-alt vhr-text-warning" style="margin-left: 0.5rem;"></i> آخر طلبات الإجازات</span>
                <a href="leaveRequest-list-admin" class="vhr-text-sm vhr-text-brand vhr-font-semibold" style="text-decoration: none;">عرض الكل</a>
            </div>
            <div class="vhr-card-body" style="padding: 0.75rem;">
                <?php if (!empty($recentLeaves)): ?>
                <?php foreach ($recentLeaves as $lv): ?>
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-p-3" style="border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?= mb_substr($lv['FirstName'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="vhr-font-semibold vhr-truncate"><?= htmlspecialchars($lv['FirstName'] . ' ' . $lv['LastName']) ?></div>
                        <div class="vhr-text-sm vhr-text-gray-500"><?= htmlspecialchars($lv['LeaveType'] ?? 'إجازة') ?> · <?= $lv['Days'] ?? '-' ?> يوم</div>
                    </div>
                    <?= statusBadge($lv['Status']) ?>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="vhr-empty-state" style="padding: 2rem;">
                    <div class="vhr-text-gray-400">لا توجد طلبات إجازات</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Advances -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-hand-holding-usd vhr-text-danger" style="margin-left: 0.5rem;"></i> آخر طلبات السلف</span>
                <a href="EmpAdvances-list-admin" class="vhr-text-sm vhr-text-brand vhr-font-semibold" style="text-decoration: none;">عرض الكل</a>
            </div>
            <div class="vhr-card-body" style="padding: 0.75rem;">
                <?php if (!empty($recentAdvances)): ?>
                <?php foreach ($recentAdvances as $adv): ?>
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-p-3" style="border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?= mb_substr($adv['FirstName'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="vhr-font-semibold vhr-truncate"><?= htmlspecialchars($adv['FirstName'] . ' ' . $adv['LastName']) ?></div>
                        <div class="vhr-text-sm vhr-text-gray-500"><?= number_format($adv['Amount'], 0) ?> <?= $adv['currency'] ?? $currency ?></div>
                    </div>
                    <?= statusBadge($adv['Status']) ?>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="vhr-empty-state" style="padding: 2rem;">
                    <div class="vhr-text-gray-400">لا توجد طلبات سلف</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════ ALERTS + QUICK ACTIONS ═══════ -->
    <div class="vhr-grid vhr-grid-3 vhr-mb-6">
        <!-- Expiring Contracts -->
        <div class="vhr-card vhr-fade-in">
            <div class="vhr-card-header">
                <span><i class="fas fa-exclamation-triangle vhr-text-danger" style="margin-left: 0.5rem;"></i> عقود تنتهي قريباً</span>
                <a href="contractRenewal-list" class="vhr-text-sm vhr-text-brand vhr-font-semibold" style="text-decoration: none;">عرض الكل</a>
            </div>
            <div class="vhr-card-body" style="padding: 0.75rem;">
                <?php if (!empty($expiringList)): ?>
                <?php foreach ($expiringList as $exp): 
                    $daysLeft = (int)((strtotime($exp['new_e_date']) - strtotime($today)) / 86400);
                    $urgencyColor = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info');
                ?>
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-p-3" style="border-bottom: 1px solid var(--vhr-gray-100);">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?= mb_substr($exp['FirstName'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="vhr-font-semibold vhr-truncate"><?= htmlspecialchars($exp['FirstName'] . ' ' . $exp['LastName']) ?></div>
                        <div class="vhr-text-sm vhr-text-gray-500"><?= htmlspecialchars($exp['JobTitle'] ?? '-') ?> · <?= $exp['new_e_date'] ?></div>
                    </div>
                    <?= badge("$daysLeft يوم", $urgencyColor) ?>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="vhr-empty-state" style="padding: 2rem;">
                    <i class="fas fa-check-circle vhr-text-success" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <div class="vhr-text-gray-500">لا توجد عقود تنتهي قريباً</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="vhr-card vhr-fade-in" style="grid-column: span 2;">
            <div class="vhr-card-header">
                <span><i class="fas fa-bolt vhr-text-brand" style="margin-left: 0.5rem;"></i> إجراءات سريعة</span>
            </div>
            <div class="vhr-card-body">
                <div class="vhr-grid" style="grid-template-columns: repeat(6, 1fr); gap: 1rem;">
                    <a href="employer-add" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, var(--vhr-brand), var(--vhr-brand-light)); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">إضافة موظف</span>
                    </a>
                    <a href="leaveRequest-list-admin" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">الإجازات</span>
                    </a>
                    <a href="reveal-attendance" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #059669); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">كشف الحضور</span>
                    </a>
                    <a href="Issuing-salaries" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">الرواتب</span>
                    </a>
                    <a href="contractRenewal-add" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">عقد جديد</span>
                    </a>
                    <a href="report-center" class="vhr-flex vhr-flex-col vhr-items-center vhr-gap-2 vhr-p-4 vhr-rounded-xl vhr-bg-gray-50 vhr-transition vhr-hover-lift" style="text-decoration: none; color: inherit;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #ec4899, #db2777); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="vhr-text-sm vhr-font-semibold">التقارير</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Trend Chart
    const attCtx = document.getElementById('attendanceChart');
    if (attCtx && typeof Chart !== 'undefined') {
        new Chart(attCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($trendLabels) ?>,
                datasets: [{
                    label: 'عدد الحاضرين',
                    data: <?= json_encode($trendCounts) ?>,
                    backgroundColor: 'rgba(13, 33, 165, 0.8)',
                    borderColor: 'rgba(13, 33, 165, 1)',
                    borderWidth: 0,
                    borderRadius: 8,
                    maxBarThickness: 40
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
                        labels: { font: { family: 'Cairo', size: 12, weight: '600' }, usePointStyle: true, padding: 16 }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'Cairo', size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Cairo', size: 12, weight: '600' } } }
                }
            }
        });
    }

    // Department Distribution Chart
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx && typeof Chart !== 'undefined') {
        const colors = ['#0d21a5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [{
                    data: <?= json_encode($deptCounts) ?>,
                    backgroundColor: colors.slice(0, <?= count($deptLabels) ?>),
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', rtl: true,
                        labels: { font: { family: 'Cairo', size: 11, weight: '600' }, usePointStyle: true, padding: 10 }
                    }
                }
            }
        });
    }
});
</script>

<style>
/* Responsive grid fixes for secondary stats */
@media (max-width: 1199.98px) {
    .vhr-grid[style*="repeat(6"] {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}
@media (max-width: 767.98px) {
    .vhr-grid[style*="repeat(6"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .vhr-grid[style*="2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .vhr-card[style*="grid-column: span 2"] {
        grid-column: span 1 !important;
    }
}
@media (max-width: 575.98px) {
    .vhr-dashboard-header {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
    }
}
</style>
