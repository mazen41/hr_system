<?php
/**
 * ESS - Employee Self-Service Dashboard
 * Main hub for employees: attendance, quick stats, recent activity
 */
$screen = 'الخدمة الذاتية';
$page_title = 'لوحة الخدمة الذاتية';
$ess_active = 'dashboard';
include_once('inc/header.php');

// Get current employee data
$empData = null;
$contract = null;
$todayAttendance = [];
$pendingLeaves = 0;
$pendingAdvances = 0;
$pendingOrders = 0;
$thisMonthSalary = null;

if ($user) {
    // Employee profile + contract
    $stm = $connect_pdo->prepare(
        "SELECT u.UserID, u.FirstName, u.SecondName, u.LastName, u.UserEmail, u.Photo, u.Phone,
                u.isemp, u.manager, u.CreatedDate,
                r.Salary, r.Currency, r.SectionID, r.jobtitleID, r.GradeID, r.shiftID,
                r.new_s_date, r.new_e_date, r.BranchID,
                s.Name as SectionName, jt.Name as JobTitleName, jg.Name as GradeName,
                sh.ShiftName, sh.ShiftStartTime, sh.ShiftEndTime,
                b.branch_name as BranchName,
                mgr.FirstName as MgrFirst, mgr.LastName as MgrLast
         FROM tblusers u
         LEFT JOIN tblremewal r ON r.Id = u.lastversion
         LEFT JOIN tblsection s ON s.Id = r.SectionID
         LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
         LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
         LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
         LEFT JOIN branches b ON b.branch_id = r.BranchID
         LEFT JOIN tblusers mgr ON mgr.UserID = u.manager
         WHERE u.UserID = :uid LIMIT 1"
    );
    $stm->execute([':uid' => $user]);
    $empData = $stm->fetch(PDO::FETCH_ASSOC);

    // Today's attendance
    $stm2 = $connect_pdo->prepare(
        "SELECT ID, Time, Type, Date FROM attendancet 
         WHERE EmpID = :uid AND Date = :today 
         ORDER BY Time ASC"
    );
    $stm2->execute([':uid' => $user, ':today' => date('Y-m-d')]);
    $todayAttendance = $stm2->fetchAll(PDO::FETCH_ASSOC);

    // Pending leave requests count
    $stm3 = $connect_pdo->prepare("SELECT COUNT(*) FROM tblleaverequest WHERE UserID = :uid AND status IS NULL");
    $stm3->execute([':uid' => $user]);
    $pendingLeaves = (int) $stm3->fetchColumn();

    // Pending advance requests count
    $stm4 = $connect_pdo->prepare("SELECT COUNT(*) FROM tblempadvances WHERE UserID = :uid AND Status IS NULL");
    $stm4->execute([':uid' => $user]);
    $pendingAdvances = (int) $stm4->fetchColumn();

    // Pending orders count
    $stm5 = $connect_pdo->prepare("SELECT COUNT(*) FROM emp_order WHERE UserID = :uid AND Status IS NULL");
    $stm5->execute([':uid' => $user]);
    $pendingOrders = (int) $stm5->fetchColumn();

    // This month salary
    $stm6 = $connect_pdo->prepare(
        "SELECT es.* FROM emp_salary es 
         WHERE es.UserID = :uid 
         ORDER BY es.Id DESC LIMIT 1"
    );
    $stm6->execute([':uid' => $user]);
    $thisMonthSalary = $stm6->fetch(PDO::FETCH_ASSOC);

    // Get unread notifications
    $unreadNotifications = [];
    try {
        require_once 'classes/NotificationService.php';
        $notificationService = new NotificationService($connect_pdo);
        $unreadNotifications = $notificationService->getUnreadNotifications($user, 5);
    } catch (Exception $e) {
        // Notifications optional
    }
}

$empName = $empData ? trim($empData['FirstName'] . ' ' . ($empData['SecondName'] ?? '') . ' ' . ($empData['LastName'] ?? '')) : 'موظف';
$checkInTime = null;
$checkOutTime = null;
$isCheckedIn = false;

// Fetch attendance settings to determine which methods are enabled
$attendanceSettings = [];
$stmSettings = $connect_pdo->query("SELECT setting_key, setting_value FROM attendance_settings");
while ($row = $stmSettings->fetch(PDO::FETCH_ASSOC)) {
    $attendanceSettings[$row['setting_key']] = $row['setting_value'];
}
$gpsEnabled = ($attendanceSettings['gps_enabled'] ?? '1') === '1';
$qrEnabled = ($attendanceSettings['qr_enabled'] ?? '1') === '1';
$manualEnabled = ($attendanceSettings['manual_enabled'] ?? '0') === '1';
$fingerprintEnabled = ($attendanceSettings['fingerprint_enabled'] ?? '0') === '1';

foreach ($todayAttendance as $att) {
    if ($att['Type'] == 1 && !$checkInTime) {
        $checkInTime = $att['Time'];
    }
    if ($att['Type'] == 2) {
        $checkOutTime = $att['Time'];
    }
}
if ($checkInTime && !$checkOutTime) {
    $isCheckedIn = true;
}

// Contract days remaining
$contractDaysLeft = null;
if ($empData && !empty($empData['new_e_date'])) {
    $contractDaysLeft = (int) ((strtotime($empData['new_e_date']) - time()) / 86400);
}
?>

<section class="content dash-page">
<div class="container-fluid">

    <!-- Welcome Section -->
    <div class="ess-hero-enhanced">
        <div class="text-center">
            <div class="greeting">مرحباً، <?= htmlspecialchars($empName) ?> 👋</div>
            <div class="subtitle">
                <?= date('l، j F Y', strtotime(date('Y-m-d'))) ?>
            </div>
            <?php if ($empData): ?>
            <div class="job-info" style="display:flex;justify-content:center;flex-wrap:wrap;gap:10px;margin-top:15px;">
                <?php if (!empty($empData['JobTitleName'])): ?>
                    <span><i class="fas fa-briefcase"></i> <?= htmlspecialchars($empData['JobTitleName']) ?></span>
                <?php endif; ?>
                <?php if (!empty($empData['SectionName'])): ?>
                    <span><i class="fas fa-building"></i> <?= htmlspecialchars($empData['SectionName']) ?></span>
                <?php endif; ?>
                <?php if (!empty($empData['BranchName'])): ?>
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($empData['BranchName']) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Attendance + Quick Actions -->
        <div class="col-lg-4 mb-4">
            <!-- Attendance Card -->
            <div class="ess-form-card-enhanced mb-3">
                <div class="card-title-ess"><i class="fas fa-fingerprint"></i> تسجيل الحضور</div>
                <div class="clock-display" id="liveClock">--:--:--</div>
                <div class="date-display"><?= date('Y-m-d') ?></div>

                <!-- Attendance Methods - Dynamically shown based on settings -->
                <div class="attendance-methods">
                    <?php if ($gpsEnabled || $manualEnabled): ?>
                        <?php if ($isCheckedIn): ?>
                            <button class="ess-btn-secondary w-100 mb-2" id="attendanceBtn" onclick="recordAttendance()">
                                <i class="fas fa-sign-out-alt"></i> تسجيل انصراف <?= $gpsEnabled ? '<small>(GPS)</small>' : '' ?>
                            </button>
                        <?php else: ?>
                            <button class="ess-btn-primary w-100 mb-2" id="attendanceBtn" onclick="recordAttendance()">
                                <i class="fas fa-sign-in-alt"></i> تسجيل حضور <?= $gpsEnabled ? '<small>(GPS)</small>' : '' ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($qrEnabled): ?>
                        <button class="ess-btn-primary w-100 mb-2" onclick="startQrScanner()">
                            <i class="fas fa-qrcode"></i> مسح QR
                        </button>
                    <?php endif; ?>

                    <?php if ($fingerprintEnabled): ?>
                        <div class="fingerprint-status alert alert-info py-2 px-3 mb-2">
                            <i class="fas fa-fingerprint text-success"></i>
                            <span class="small">البصمة مفعلة - استخدم جهاز البصمة</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$gpsEnabled && !$qrEnabled && !$manualEnabled && !$fingerprintEnabled): ?>
                    <div class="alert alert-warning py-2 text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="small">لم يتم تفعيل أي طريقة لتسجيل الحضور</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($todayAttendance)): ?>
                <ul class="attendance-timeline">
                    <?php foreach ($todayAttendance as $att): ?>
                    <li>
                        <span class="att-dot <?= $att['Type'] == 1 ? 'in' : 'out' ?>"></span>
                        <strong><?= $att['Type'] == 1 ? 'حضور' : 'انصراف' ?></strong>
                        <span class="mr-auto" style="direction:ltr"><?= htmlspecialchars($att['Time']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center mt-3" style="font-size:0.85rem">لم يتم تسجيل حضور اليوم</p>
                <?php endif; ?>
            </div>

            <!-- Enhanced Quick Actions -->
            <div class="ess-section-title"><i class="fas fa-bolt"></i> إجراءات سريعة</div>
            <div class="ess-quick-actions-enhanced">
                <a href="ess-leaves" class="ess-quick-action-enhanced">
                    <i class="fas fa-calendar-plus"></i>
                    <span>طلب إجازة</span>
                </a>
                <a href="ess-advances" class="ess-quick-action-enhanced">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>طلب سلفة</span>
                </a>
                <a href="ess-orders" class="ess-quick-action-enhanced">
                    <i class="fas fa-file-signature"></i>
                    <span>طلب إداري</span>
                </a>
                <a href="ess-profile" class="ess-quick-action-enhanced">
                    <i class="fas fa-user-edit"></i>
                    <span>ملفي الشخصي</span>
                </a>
            </div>
        </div>

        <!-- Right Column: Stats + Info -->
        <div class="col-lg-8">
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-6 col-md-3 mb-3">
                    <a href="ess-leaves" class="ess-stat-card">
                        <div class="icon-wrap bg-brand-purple">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?= $pendingLeaves ?></div>
                        <div class="stat-label">إجازات معلقة</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="ess-advances" class="ess-stat-card">
                        <div class="icon-wrap bg-brand-warning">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value"><?= $pendingAdvances ?></div>
                        <div class="stat-label">سلف معلقة</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="ess-orders" class="ess-stat-card">
                        <div class="icon-wrap bg-brand-info">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?= $pendingOrders ?></div>
                        <div class="stat-label">طلبات معلقة</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="ess-salary" class="ess-stat-card">
                        <div class="icon-wrap bg-brand-success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-value"><?= $empData ? number_format((float)($empData['Salary'] ?? 0), 0) : '0' ?></div>
                        <div class="stat-label">الراتب الأساسي (ر.س)</div>
                    </a>
                </div>
            </div>

            <!-- Contract Info -->
            <?php if ($empData && !empty($empData['new_s_date'])): ?>
            <div class="ess-stat-card mb-4">
                <div class="ess-section-title"><i class="fas fa-file-contract"></i> معلومات العقد</div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">بداية العقد</small>
                        <div class="font-weight-bold"><?= $empData['new_s_date'] ?></div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">نهاية العقد</small>
                        <div class="font-weight-bold"><?= $empData['new_e_date'] ?></div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">الأيام المتبقية</small>
                        <div class="font-weight-bold">
                            <?php if ($contractDaysLeft !== null): ?>
                                <?php if ($contractDaysLeft > 30): ?>
                                    <span class="text-success"><?= $contractDaysLeft ?> يوم</span>
                                <?php elseif ($contractDaysLeft > 0): ?>
                                    <span class="text-warning"><?= $contractDaysLeft ?> يوم</span>
                                <?php else: ?>
                                    <span class="text-danger">منتهي</span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                if ($contractDaysLeft !== null && !empty($empData['new_s_date']) && !empty($empData['new_e_date'])) {
                    $totalDays = max(1, (strtotime($empData['new_e_date']) - strtotime($empData['new_s_date'])) / 86400);
                    $elapsed = max(0, (time() - strtotime($empData['new_s_date'])) / 86400);
                    $pct = min(100, max(0, ($elapsed / $totalDays) * 100));
                    $barColor = $pct > 90 ? '#ef4444' : ($pct > 75 ? '#f59e0b' : '#10b981');
                }
                ?>
                <?php if (isset($pct)): ?>
                <div class="contract-progress">
                    <div class="bar" style="width:<?= round($pct) ?>%;background:<?= $barColor ?>"></div>
                </div>
                <small class="text-muted"><?= round($pct) ?>% من مدة العقد</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Employee Info Summary -->
            <?php if ($empData): ?>
            <div class="ess-stat-card mb-4">
                <div class="ess-section-title"><i class="fas fa-id-badge"></i> ملخص البيانات</div>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem">
                            <tr><td class="text-muted" style="width:40%">الاسم</td><td class="font-weight-bold"><?= htmlspecialchars($empName) ?></td></tr>
                            <tr><td class="text-muted">البريد</td><td><?= htmlspecialchars($empData['UserEmail'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">الهاتف</td><td><?= htmlspecialchars($empData['Phone'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">المدير</td><td><?= $empData['MgrFirst'] ? htmlspecialchars(trim($empData['MgrFirst'] . ' ' . ($empData['MgrLast'] ?? ''))) : '-' ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem">
                            <tr><td class="text-muted" style="width:40%">المسمى</td><td class="font-weight-bold"><?= htmlspecialchars($empData['JobTitleName'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">القسم</td><td><?= htmlspecialchars($empData['SectionName'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">الدرجة</td><td><?= htmlspecialchars($empData['GradeName'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">الفترة</td><td><?= htmlspecialchars($empData['ShiftName'] ?? '-') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Notifications -->
            <?php if (!empty($unreadNotifications)): ?>
            <div class="ess-stat-card mb-4" style="border-right: 4px solid #0d21a5;">
                <div class="ess-section-title"><i class="fas fa-bell"></i> الإشعارات الجديدة</div>
                <?php foreach ($unreadNotifications as $notif): ?>
                <div class="d-flex justify-content-between align-items-start py-2 border-bottom notification-item" data-id="<?= $notif['id'] ?>">
                    <div>
                        <strong><?= htmlspecialchars($notif['title']) ?></strong>
                        <small class="text-muted d-block"><?= htmlspecialchars($notif['body']) ?></small>
                        <small class="text-muted"><?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mark-read-btn" onclick="markNotificationRead(<?= $notif['id'] ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
                <?php endforeach; ?>
                <div class="text-center mt-3">
                    <a href="#" onclick="markAllNotificationsRead()" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double"></i> تحديد الكل كمقروء
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Document Expiry Alerts -->
            <?php
            $docAlerts = [];
            if ($empData) {
                $stmDocs = $connect_pdo->prepare(
                    "SELECT Id_h, end_date_h, Id_license, end_date_license, 
                            Id_passport, end_date_passport, Id_health, end_date_health 
                     FROM tblusers WHERE UserID = :uid"
                );
                $stmDocs->execute([':uid' => $user]);
                $docs = $stmDocs->fetch(PDO::FETCH_ASSOC);
                if ($docs) {
                    $docTypes = [
                        ['الهوية الوطنية', $docs['Id_h'], $docs['end_date_h']],
                        ['رخصة القيادة', $docs['Id_license'], $docs['end_date_license']],
                        ['جواز السفر', $docs['Id_passport'], $docs['end_date_passport']],
                        ['التأمين الصحي', $docs['Id_health'], $docs['end_date_health']],
                    ];
                    foreach ($docTypes as [$label, $num, $endDate]) {
                        if (!empty($endDate)) {
                            $daysLeft = (int)((strtotime($endDate) - time()) / 86400);
                            if ($daysLeft <= 30) {
                                $docAlerts[] = ['label' => $label, 'end' => $endDate, 'days' => $daysLeft];
                            }
                        }
                    }
                }
            }
            ?>
            <?php if (!empty($docAlerts)): ?>
            <div class="ess-stat-card mb-4" style="border-right: 4px solid #ef4444;">
                <div class="ess-section-title" style="color:#ef4444"><i class="fas fa-exclamation-triangle"></i> تنبيهات الوثائق</div>
                <?php foreach ($docAlerts as $alert): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong><?= $alert['label'] ?></strong>
                        <small class="text-muted d-block">ينتهي: <?= $alert['end'] ?></small>
                    </div>
                    <span class="badge <?= $alert['days'] < 0 ? 'badge-danger' : 'badge-warning' ?>">
                        <?= $alert['days'] < 0 ? 'منتهي' : $alert['days'] . ' يوم متبقي' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>
</section>

<style>
#qrScannerModal .modal-dialog {
    max-width: 560px;
    margin: 1.75rem auto;
}
#qrScannerModal .modal-content {
    border: 0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
}
#qrScannerModal .modal-header,
#qrScannerModal .modal-footer {
    border: 0;
}
.qr-modal-body {
    padding: 0 1.5rem 1.5rem;
}
.qr-scanner-stage {
    position: relative;
    width: 100%;
    max-width: 360px;
    margin: 0 auto;
    border-radius: 18px;
    overflow: hidden;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
}
#qrScannerVideo {
    width: 100%;
    height: auto;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    background: #111827;
    display: block;
}
#qrScannerOverlay {
    position: absolute;
    inset: 14%;
    border: 2px solid rgba(255,255,255,0.95);
    border-radius: 18px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.04);
    pointer-events: none;
}
.qr-overlay-label {
    position: absolute;
    bottom: 14px;
    right: 16px;
    left: 16px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
    text-shadow: 0 1px 4px rgba(0,0,0,0.45);
}
.qr-modal-help {
    color: #64748b;
    font-size: 0.92rem;
    margin-bottom: 1rem;
}
.qr-result-actions .btn {
    min-width: 140px;
}
</style>

    <!-- QR Scanner Modal -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-camera"></i> مسح رمز QR بالكاميرا</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center qr-modal-body">
                    <p class="qr-modal-help">وجّه الكاميرا نحو رمز QR داخل الإطار، أو استخدم شاشة الحضور اليدوية إذا تعذر فتح الكاميرا.</p>
                    <div id="qrScannerContainer" class="qr-scanner-stage">
                        <video id="qrScannerVideo" autoplay playsinline muted></video>
                        <canvas id="qrScannerCanvas" style="display:none;"></canvas>
                        <div id="qrScannerOverlay"></div>
                        <div class="qr-overlay-label">ضع رمز QR داخل الإطار</div>
                    </div>
                    <div id="qrScannerResult" style="display:none;margin-top:20px;">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> تم مسح الرمز بنجاح
                        </div>
                        <div class="qr-result-actions d-flex justify-content-center flex-wrap" style="gap:10px;">
                            <button class="btn btn-primary" onclick="useScannedQr()">استخدام الرمز</button>
                            <button class="btn btn-outline-secondary" onclick="resetQrScanner()">مسح مرة أخرى</button>
                        </div>
                    </div>
                    <div id="qrScannerError" style="display:none;margin-top:20px;">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <span id="qrScannerErrorText"></span>
                        </div>
                        <button class="btn btn-outline-secondary" onclick="resetQrScanner()">إعادة المحاولة</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

<?php include_once('inc/footer.php'); ?>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('liveClock').textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);
updateClock();

// Record attendance
function recordAttendance() {
    const btn = document.getElementById('attendanceBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التسجيل...';

    // Get GPS location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                submitAttendance(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
            },
            function(err) {
                // Submit without GPS
                submitAttendance(null, null, null);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        submitAttendance(null, null, null);
    }
}

function submitAttendance(lat, lng, accuracy) {
    $.ajax({
        url: 'hr-app/index.php?action=ess-attendance',
        method: 'POST',
        data: { lat: lat, lng: lng, accuracy: accuracy },
        dataType: 'json',
        success: function(data) {
            if (data.result) {
                toastr.success(data.msg);
                toggleDashBtn(data.data ? data.data.type : null);
            } else {
                toastr.error(data.msg || 'حدث خطأ');
                resetBtn();
            }
        },
        error: function() {
            toastr.error('حدث خطأ في الاتصال');
            resetBtn();
        }
    });
}

function toggleDashBtn(lastType) {
    const btn = document.getElementById('attendanceBtn');
    btn.disabled = false;
    if (lastType == 1) {
        btn.className = 'check-btn check-out';
        btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف';
    } else {
        btn.className = 'check-btn check-in';
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل حضور';
    }
}

function resetBtn() {
    const btn = document.getElementById('attendanceBtn');
    btn.disabled = false;
    if (btn.classList.contains('check-out')) {
        btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف';
    } else {
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل حضور';
    }
}

// QR Scanner Functions
var qrScannerStream = null;
var qrScanning = false;
var scannedQrCode = '';
var qrDetectionBusy = false;
var nativeBarcodeDetector = null;

function startQrScanner() {
    $('#qrScannerModal').modal({backdrop: 'static', keyboard: true, show: true});
    resetQrScanner();
    requestCameraPermission();
}

function requestCameraPermission() {
    if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        showQrScannerError('متصفحك لا يدعم فتح الكاميرا من داخل النظام.');
        return;
    }

    var constraints = {
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1280 },
            height: { ideal: 1280 }
        }
    };

    navigator.mediaDevices.getUserMedia(constraints)
        .catch(function() {
            return navigator.mediaDevices.getUserMedia({ video: true });
        })
        .then(function(stream) {
            qrScannerStream = stream;
            var video = document.getElementById('qrScannerVideo');
            video.setAttribute('playsinline', 'true');
            video.muted = true;
            video.srcObject = stream;
            return video.play().then(function() {
                qrScanning = true;
                scanQrCode();
            });
        })
        .catch(function() {
            showQrScannerError('تعذر الوصول إلى الكاميرا. يرجى السماح بصلاحية الكاميرا أو إعادة المحاولة.');
        });
}

function detectQrCode(video, canvas, context) {
    if ('BarcodeDetector' in window) {
        if (!nativeBarcodeDetector) {
            nativeBarcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
        }

        return nativeBarcodeDetector.detect(video).then(function(codes) {
            if (codes && codes.length && codes[0].rawValue) {
                return codes[0].rawValue;
            }
            return null;
        });
    }

    if (typeof jsQR === 'undefined') {
        return Promise.resolve(null);
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
    var code = jsQR(imageData.data, imageData.width, imageData.height);

    return Promise.resolve(code ? code.data : null);
}

function scanQrCode() {
    if (!qrScanning || qrDetectionBusy) return;
    
    var video = document.getElementById('qrScannerVideo');
    var canvas = document.getElementById('qrScannerCanvas');
    var context = canvas.getContext('2d');
    
    if (video.readyState !== video.HAVE_ENOUGH_DATA) {
        requestAnimationFrame(scanQrCode);
        return;
    }

    qrDetectionBusy = true;
    detectQrCode(video, canvas, context)
        .then(function(codeValue) {
            if (codeValue) {
                scannedQrCode = codeValue;
                stopQrScanner();
                showQrScannerResult();
                return;
            }

            if (qrScanning) {
                requestAnimationFrame(scanQrCode);
            }
        })
        .catch(function() {
            if (qrScanning) {
                requestAnimationFrame(scanQrCode);
            }
        })
        .finally(function() {
            qrDetectionBusy = false;
        });
}

function stopQrScanner() {
    qrScanning = false;
    qrDetectionBusy = false;
    if (qrScannerStream) {
        qrScannerStream.getTracks().forEach(track => track.stop());
        qrScannerStream = null;
    }
    var video = document.getElementById('qrScannerVideo');
    video.srcObject = null;
}

function showQrScannerResult() {
    document.getElementById('qrScannerContainer').style.display = 'none';
    document.getElementById('qrScannerResult').style.display = 'block';
    document.getElementById('qrScannerError').style.display = 'none';
}

function showQrScannerError(message) {
    stopQrScanner();
    document.getElementById('qrScannerContainer').style.display = 'none';
    document.getElementById('qrScannerResult').style.display = 'none';
    document.getElementById('qrScannerError').style.display = 'block';
    document.getElementById('qrScannerErrorText').textContent = message;
}

function resetQrScanner() {
    stopQrScanner();
    document.getElementById('qrScannerContainer').style.display = 'block';
    document.getElementById('qrScannerResult').style.display = 'none';
    document.getElementById('qrScannerError').style.display = 'none';
    scannedQrCode = '';
}

function useScannedQr() {
    if (scannedQrCode) {
        $('#qrScannerModal').modal('hide');
        submitQrAttendance(scannedQrCode);
    }
}

function submitQrAttendance(token) {
    if (!token) {
        toastr.warning('تعذر قراءة رمز QR من الكاميرا. حاول مرة أخرى.');
        return;
    }

    $.ajax({
        url: 'hr-app/index.php?action=ess-attendance-qr',
        method: 'POST',
        data: { qr_token: token },
        dataType: 'json',
        success: function(data) {
            if (data.result) {
                toastr.success(data.msg);
                toggleDashBtn(data.data ? data.data.type : null);
            } else {
                toastr.error(data.msg || 'رمز QR غير صالح');
            }
        },
        error: function() {
            toastr.error('خطأ في الاتصال');
        }
    });
}

// Include jsQR library for QR scanning
function loadJsQR() {
    if ('BarcodeDetector' in window || typeof jsQR !== 'undefined') {
        return;
    }

    if (typeof jsQR === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
        script.async = true;
        document.head.appendChild(script);
    }
}

// Load jsQR library when page loads
$(document).ready(function() {
    loadJsQR();
    $('#qrScannerModal').on('hidden.bs.modal', function() {
        resetQrScanner();
    });
});

// ===== Debug helpers =====
(function(){
    const debug = (...args) => console.debug('[ESS-DASH]', ...args);

    function logHeroMetrics(context = 'initial') {
        const hero = document.querySelector('.ess-hero-enhanced');
        if (!hero) return;
        const rect = hero.getBoundingClientRect();
        debug(`Hero metrics (${context})`, {
            width: rect.width.toFixed(2),
            height: rect.height.toFixed(2),
            top: rect.top.toFixed(2),
            bottom: rect.bottom.toFixed(2),
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight
        });
    }

    function logDropdownMetrics(trigger) {
        const menu = trigger?.parentElement?.querySelector('.dropdown-menu.show');
        if (!menu) {
            debug('Dropdown opened but menu not yet visible');
            return;
        }
        const rect = menu.getBoundingClientRect();
        debug('Dropdown metrics', {
            triggerWidth: trigger.offsetWidth,
            triggerRect: trigger.getBoundingClientRect(),
            menuWidth: rect.width.toFixed(2),
            menuLeft: rect.left.toFixed(2),
            menuRight: rect.right.toFixed(2),
            viewportWidth: window.innerWidth
        });
    }

    window.addEventListener('resize', () => logHeroMetrics('resize'));
    window.addEventListener('orientationchange', () => logHeroMetrics('orientation change'));

    $(document).on('shown.bs.dropdown', '.nav-item.dropdown > a', function(){
        setTimeout(() => logDropdownMetrics(this), 0);
    });

    document.addEventListener('DOMContentLoaded', () => logHeroMetrics());
})();

// Notification functions
function markNotificationRead(id) {
    $.post('hr-app/index.php?action=mark-notification-read', { notification_id: id }, function(res) {
        if (res.result) {
            $('.notification-item[data-id="' + id + '"]').fadeOut();
        }
    });
}

function markAllNotificationsRead() {
    $.post('hr-app/index.php?action=mark-all-notifications-read', {}, function(res) {
        if (res.result) {
            $('.notification-item').fadeOut();
            toastr.success('تم تحديد جميع الإشعارات كمقروءة');
        }
    });
    return false;
}
</script>

<?php include_once('inc/footer.php'); ?>
