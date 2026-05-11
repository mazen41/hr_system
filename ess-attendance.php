<?php
/**
 * ESS - Attendance History
 * Employee can view their attendance records with month filter
 */
$screen = 'الخدمة الذاتية';
$page_title = 'سجل الحضور والانصراف';
$ess_active = 'attendance';
include_once('inc/header.php');

// Get today's attendance summary
$todayAttendance = [];
$monthSummary = ['present' => 0, 'absent' => 0, 'late' => 0];
if ($user) {
    $stm = $connect_pdo->prepare(
        "SELECT ID, Time, Type, Date FROM attendancet 
         WHERE EmpID = :uid AND Date = :today ORDER BY Time ASC"
    );
    $stm->execute([':uid' => $user, ':today' => date('Y-m-d')]);
    $todayAttendance = $stm->fetchAll(PDO::FETCH_ASSOC);

    // This month summary: count distinct dates with check-in
    $stm2 = $connect_pdo->prepare(
        "SELECT COUNT(DISTINCT Date) as days_present 
         FROM attendancet 
         WHERE EmpID = :uid AND Type = 1 
         AND DATE_FORMAT(Date, '%Y-%m') = :month"
    );
    $stm2->execute([':uid' => $user, ':month' => date('Y-m')]);
    $monthSummary['present'] = (int)$stm2->fetchColumn();

    // Working days this month (excluding Fridays and Saturdays for Saudi)
    $daysInMonth = (int)date('t');
    $workingDays = 0;
    for ($d = 1; $d <= min($daysInMonth, (int)date('j')); $d++) {
        $dayOfWeek = date('N', mktime(0, 0, 0, (int)date('m'), $d, (int)date('Y')));
        if ($dayOfWeek != 5 && $dayOfWeek != 6) { // Fri=5, Sat=6
            $workingDays++;
        }
    }
    $monthSummary['absent'] = max(0, $workingDays - $monthSummary['present']);
}

$checkInTime = null;
$checkOutTime = null;
$isCheckedIn = false;
foreach ($todayAttendance as $att) {
    if ($att['Type'] == 1 && !$checkInTime) $checkInTime = $att['Time'];
    if ($att['Type'] == 2) $checkOutTime = $att['Time'];
}
if ($checkInTime && !$checkOutTime) $isCheckedIn = true;
?>


<style>
.ess-form-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
}
.ess-form-card .card-title-ess {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.att-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.att-summary-item {
    text-align: center;
    padding: 16px;
    border-radius: 12px;
}
.att-summary-item .value {
    font-size: 1.8rem;
    font-weight: 800;
}
.att-summary-item .label {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
}
.today-status {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}
.today-status .status-item {
    background: #f8fafc;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.9rem;
}
.today-status .status-item i { margin-left: 6px; }
.clock-inline {
    font-family: 'Courier New', monospace;
    font-size: 1.6rem;
    font-weight: 700;
    color: #1e3a5f;
    direction: ltr;
    display: inline-block;
}
.check-btn-sm {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}
.check-btn-sm.in { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.check-btn-sm.out { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
.check-btn-sm:hover { opacity: 0.9; }
#qrSection {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}
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
}

/* Enhanced Responsive Design */
@media (max-width: 992px) {
    .ess-form-card {
        padding: 24px;
        margin-bottom: 20px;
    }
    .card-title-ess {
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    .clock-inline {
        font-size: 2.5rem;
    }
    .status-item {
        font-size: 0.9rem;
        margin-bottom: 12px;
    }
    .check-btn-sm {
        padding: 12px 16px;
        font-size: 1rem;
        margin: 6px 3px;
        min-height: 48px;
        touch-action: manipulation;
    }
    #qrSection {
        padding: 16px;
        margin-top: 16px;
    }
    #qrTokenInput {
        font-size: 0.95rem;
        min-height: 44px;
    }
    .att-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }
    .att-summary-item {
        padding: 20px;
        text-align: center;
    }
    .att-summary-item .value {
        font-size: 1.6rem;
        margin-bottom: 4px;
    }
    .att-summary-item .label {
        font-size: 0.85rem;
    }
    .table-sm td, .table-sm th {
        font-size: 0.9rem;
        padding: 10px 8px;
    }
    .month-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .month-filter .form-control {
        width: 100%;
        font-size: 0.95rem;
        min-height: 44px;
    }
}

@media (max-width: 768px) {
    .ess-form-card {
        padding: 20px;
        margin-bottom: 16px;
    }
    .card-title-ess {
        font-size: 1.1rem;
        margin-bottom: 16px;
        text-align: center;
    }
    .clock-inline {
        font-size: 2.2rem;
        text-align: center;
    }
    .status-item {
        font-size: 0.85rem;
        margin-bottom: 10px;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        text-align: center;
    }
    .status-item strong {
        display: block;
        margin-top: 2px;
    }
    .check-btn-sm {
        padding: 10px 14px;
        font-size: 0.95rem;
        margin: 4px 2px;
        min-height: 44px;
        width: calc(50% - 4px);
        touch-action: manipulation;
    }
    #qrSection {
        padding: 14px;
        margin-top: 14px;
        text-align: center;
    }
    #qrTokenInput {
        font-size: 0.9rem;
        min-height: 40px;
    }
    .att-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }
    .att-summary-item {
        padding: 16px;
    }
    .att-summary-item .value {
        font-size: 1.4rem;
        margin-bottom: 4px;
    }
    .att-summary-item .label {
        font-size: 0.8rem;
    }
    .table-sm td, .table-sm th {
        font-size: 0.85rem;
        padding: 8px 6px;
    }
    .month-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .month-filter .form-control {
        width: 100%;
        font-size: 0.9rem;
        min-height: 40px;
    }
    .attendance-timeline {
        margin-top: 16px;
    }
    .attendance-timeline li {
        font-size: 0.85rem;
        padding: 8px 0;
    }
    .attendance-timeline .att-dot {
        width: 10px;
        height: 10px;
    }
}

@media (max-width: 576px) {
    .ess-form-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .card-title-ess {
        font-size: 1rem;
        margin-bottom: 12px;
    }
    .clock-inline {
        font-size: 1.8rem;
    }
    .status-item {
        font-size: 0.8rem;
        margin-bottom: 8px;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }
    .status-item strong {
        display: block;
        margin-top: 2px;
    }
    .check-btn-sm {
        padding: 8px 12px;
        font-size: 0.85rem;
        margin: 2px 1px;
        min-height: 40px;
        width: calc(50% - 2px);
        touch-action: manipulation;
    }
    #qrSection {
        padding: 12px;
        margin-top: 12px;
    }
    #qrTokenInput {
        font-size: 0.85rem;
        min-height: 36px;
    }
    .att-summary-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 12px;
    }
    .att-summary-item {
        padding: 12px;
    }
    .att-summary-item .value {
        font-size: 1.2rem;
        margin-bottom: 2px;
    }
    .att-summary-item .label {
        font-size: 0.75rem;
    }
    .table-sm td, .table-sm th {
        font-size: 0.8rem;
        padding: 6px 4px;
    }
    .month-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .month-filter .form-control {
        width: 100%;
        font-size: 0.85rem;
        min-height: 36px;
    }
    .attendance-timeline li {
        font-size: 0.8rem;
        padding: 6px 0;
    }
    .attendance-timeline .att-dot {
        width: 8px;
        height: 8px;
    }
    #qrScannerModal .modal-dialog {
        max-width: 95vw;
        margin: 10px auto;
    }
    #qrScannerModal .modal-body {
        padding: 0 12px 14px;
    }
    #qrScannerVideo {
        max-height: 280px;
    }
    #qrScannerOverlay {
        inset: 15%;
    }
    .qr-overlay-label {
        font-size: 0.8rem;
        bottom: 10px;
    }
    #qrScannerModal .btn {
        padding: 8px 12px;
        font-size: 0.85rem;
        margin: 4px 2px;
        min-height: 36px;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .ess-form-card:hover {
        transform: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .att-summary-item:hover {
        transform: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .check-btn-sm:hover {
        transform: none;
        opacity: 0.9;
    }
    .check-btn-sm:active {
        transform: scale(0.95);
    }
}

/* Landscape mobile optimizations */
@media (max-width: 768px) and (orientation: landscape) {
    .ess-form-card {
        padding: 12px;
        margin-bottom: 8px;
    }
    .card-title-ess {
        font-size: 0.95rem;
        margin-bottom: 8px;
    }
    .clock-inline {
        font-size: 1.6rem;
    }
    .status-item {
        font-size: 0.75rem;
        margin-bottom: 6px;
    }
    .check-btn-sm {
        padding: 6px 10px;
        font-size: 0.8rem;
        min-height: 36px;
    }
    #qrSection {
        padding: 10px;
        margin-top: 10px;
    }
    .att-summary-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }
    .att-summary-item {
        padding: 8px;
    }
    .att-summary-item .value {
        font-size: 1rem;
    }
    .att-summary-item .label {
        font-size: 0.7rem;
    }
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <span class="page-title"><i class="fas fa-clock"></i> سجل الحضور والانصراف</span>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <!-- Today's Status -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-calendar-day"></i> حالة اليوم — <?= date('Y-m-d') ?></div>
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:16px">
            <div class="today-status">
                <div>
                    <span class="clock-inline" id="liveClock">--:--:--</span>
                </div>
                <div class="status-item">
                    <i class="fas fa-sign-in-alt text-success"></i>
                    حضور: <strong><?= $checkInTime ? htmlspecialchars($checkInTime) : 'لم يسجل' ?></strong>
                </div>
                <div class="status-item">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                    انصراف: <strong><?= $checkOutTime ? htmlspecialchars($checkOutTime) : 'لم يسجل' ?></strong>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if ($isCheckedIn): ?>
                    <button class="check-btn-sm out" id="attendanceBtn" onclick="recordAttendance()">
                        <i class="fas fa-sign-out-alt"></i> تسجيل انصراف (GPS)
                    </button>
                <?php else: ?>
                    <button class="check-btn-sm in" id="attendanceBtn" onclick="recordAttendance()">
                        <i class="fas fa-sign-in-alt"></i> تسجيل حضور (GPS)
                    </button>
                <?php endif; ?>
                <button class="check-btn-sm" id="qrBtn" onclick="toggleQrInput()" style="background:#6366f1;color:#fff;">
                    <i class="fas fa-qrcode"></i> QR
                </button>
                 <button type="button" class="check-btn-sm" data-toggle="modal" data-target="#forgotPunchModal" style="background:#f59e0b; color:#fff;">
        <i class="fas fa-exclamation-circle"></i> نسيت البصمة
    </button>
            </div>
        </div>
    </div>

    <!-- QR Code Input (hidden by default) -->
    <div id="qrSection" style="display:none;margin-top:16px;padding:16px;background:#f8fafc;border-radius:10px;">
        <div class="d-flex align-items-center gap-2 flex-wrap" style="gap:10px;">
            <div style="flex:1;min-width:200px;">
                <label class="small text-muted mb-1 d-block">أدخل رمز QR أو امسحه بالكاميرا</label>
                <input type="text" id="qrTokenInput" class="form-control" placeholder="الصق رمز QR هنا..." autofocus>
            </div>
            <button class="btn btn-primary" onclick="submitQrAttendance()" id="qrSubmitBtn" style="margin-top:20px;">
                <i class="fas fa-check"></i> تسجيل
            </button>
            <button class="btn btn-success" onclick="startQrScanner()" id="qrScanBtn" style="margin-top:20px;">
                <i class="fas fa-camera"></i> مسح بالكاميرا
            </button>
            <button class="btn btn-outline-secondary" onclick="toggleQrInput()" style="margin-top:20px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle"></i> اطلب من المسؤول عرض رمز QR ثم امسحه أو الصقه هنا</small>
    </div>

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
                    <p class="qr-modal-help">وجّه الكاميرا نحو رمز QR داخل الإطار، أو استخدم الإدخال اليدوي إذا تعذر فتح الكاميرا.</p>
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
    </div>

    <!-- Month Summary -->
    <div class="att-summary-grid">
        <div class="att-summary-item" style="background:#d1fae5">
            <div class="value" style="color:#059669"><?= $monthSummary['present'] ?></div>
            <div class="label">أيام الحضور (هذا الشهر)</div>
        </div>
        <div class="att-summary-item" style="background:#fee2e2">
            <div class="value" style="color:#dc2626"><?= $monthSummary['absent'] ?></div>
            <div class="label">أيام الغياب</div>
        </div>
        <div class="att-summary-item" style="background:#dbeafe">
            <div class="value" style="color:#2563eb"><?= count($todayAttendance) ?></div>
            <div class="label">بصمات اليوم</div>
        </div>
    </div>

    <!-- Attendance History Table -->
    <div class="ess-form-card-enhanced">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px">
            <div class="card-title-ess mb-0"><i class="fas fa-history"></i> سجل البصمات</div>
            <div>
                <input type="month" id="monthFilter" class="form-control" value="<?= date('Y-m') ?>" style="width:auto;max-width:100%;">
            </div>
        </div>
        <div class="table-responsive">
            <table id="attendanceTable" class="table table-hover table-sm" width="100%">
                <thead>
                    <tr class="bg-light">
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>النوع</th>
                        <th>الطريقة</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
</section>
<!-- Forgot Punch Request Modal -->
<div class="modal fade" id="forgotPunchModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle"></i> طلب تسجيل بصمة منسية</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="forgotPunchForm">
                    <div class="form-group">
                        <label for="correction_date">تاريخ البصمة</label>
                        <input type="date" class="form-control" id="correction_date" name="correction_date" required>
                    </div>
                    <div class="form-group">
                        <label for="correction_time">وقت البصمة</label>
                        <input type="time" class="form-control" id="correction_time" name="correction_time" required>
                    </div>
                    <div class="form-group">
                        <label for="correction_type">نوع البصمة</label>
                        <select class="form-control" id="correction_type" name="correction_type" required>
                            <option value="">-- اختر النوع --</option>
                            <option value="1">حضور</option>
                            <option value="2">انصراف</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reason">السبب</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="مثال: نسيت تسجيل الحضور عند وصولي للمكتب" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="submitForgotPunchBtn">إرسال الطلب</button>
            </div>
        </div>
    </div>
</div>
<?php include_once('inc/footer.php'); ?>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
}
setInterval(updateClock, 1000);
updateClock();

// Attendance record
function recordAttendance() {
    const btn = document.getElementById('attendanceBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري...';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) { submitAtt(pos.coords.latitude, pos.coords.longitude); },
            function() { submitAtt(null, null); },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        submitAtt(null, null);
    }
}

function submitAtt(lat, lng) {
    $.ajax({
        url: 'hr-app/index.php?action=ess-attendance',
        method: 'POST',
        data: { lat: lat, lng: lng },
        dataType: 'json',
        success: function(data) {
            if (data.result) {
                toastr.success(data.msg);
                toggleAttBtn(data.data ? data.data.type : null);
            } else {
                toastr.error(data.msg || 'خطأ');
                resetAttBtn();
            }
        },
        error: function() {
            toastr.error('خطأ في الاتصال');
            resetAttBtn();
        }
    });
}

function toggleAttBtn(lastType) {
    var btn = document.getElementById('attendanceBtn');
    btn.disabled = false;
    if (lastType == 1) {
        // Just checked in → next action is check-out
        btn.className = 'check-btn-sm out';
        btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف (GPS)';
    } else {
        // Just checked out → next action is check-in
        btn.className = 'check-btn-sm in';
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل حضور (GPS)';
    }
}

function resetAttBtn() {
    var btn = document.getElementById('attendanceBtn');
    btn.disabled = false;
    // Keep current state
    if (btn.classList.contains('out')) {
        btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> تسجيل انصراف (GPS)';
    } else {
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل حضور (GPS)';
    }
}

// QR Scanner Functions
var qrScannerStream = null;
var qrScanning = false;
var scannedQrCode = '';
var qrDetectionBusy = false;
var nativeBarcodeDetector = null;

function getArabicValidationMessage(fieldName) {
    var messages = {
        correction_date: 'يرجى اختيار تاريخ البصمة.',
        correction_time: 'يرجى اختيار وقت البصمة.',
        correction_type: 'يرجى اختيار نوع البصمة.',
        reason: 'يرجى كتابة سبب طلب التعديل.'
    };

    return messages[fieldName] || 'يرجى تعبئة هذا الحقل.';
}

function startQrScanner() {
    $('#qrScannerModal').modal({backdrop: 'static', keyboard: true, show: true});
    resetQrScanner();
    requestCameraPermission();
}

function requestCameraPermission() {
    if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        showQrScannerError('متصفحك لا يدعم فتح الكاميرا من داخل النظام. يمكنك إدخال رمز QR يدويًا.');
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
            showQrScannerError('تعذر الوصول إلى الكاميرا. يرجى السماح بصلاحية الكاميرا أو إدخال رمز QR يدويًا.');
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
        document.getElementById('qrTokenInput').value = scannedQrCode;
        $('#qrScannerModal').modal('hide');
        submitQrAttendance();
    }
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

    $('#forgotPunchForm').find('[required]').on('invalid', function() {
        this.setCustomValidity(getArabicValidationMessage(this.name));
    }).on('input change', function() {
        this.setCustomValidity('');
    });

$('#submitForgotPunchBtn').on('click', function() {
    var form = $('#forgotPunchForm');
    
    // Simple validation
    if (form[0].checkValidity() === false) {
        form[0].reportValidity();
        return;
    }

    var btn = $(this);
    var originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ...جاري الإرسال');

    var formData = form.serialize();

    $.ajax({
        url: 'hr-app/index.php?action=ess-forgot-punch-request',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.result) {
                toastr.success(response.msg);
                $('#forgotPunchModal').modal('hide');
                form[0].reset(); // Clear the form
            } else {
                toastr.error(response.msg || 'حدث خطأ ما');
            }
        },
        error: function() {
            toastr.error('فشل الاتصال بالخادم');
        },
        complete: function() {
            btn.prop('disabled', false).html(originalText);
        }
    });
});
});

// QR Attendance
function toggleQrInput() {
    var sec = document.getElementById('qrSection');
    sec.style.display = sec.style.display === 'none' ? 'block' : 'none';
    if (sec.style.display === 'block') {
        document.getElementById('qrTokenInput').focus();
    }
}

function submitQrAttendance() {
    var token = document.getElementById('qrTokenInput').value.trim();
    if (!token) { toastr.warning('يرجى إدخال رمز QR أو مسحه بالكاميرا'); return; }

    var btn = document.getElementById('qrSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري...';

    $.ajax({
        url: 'hr-app/index.php?action=ess-attendance-qr',
        method: 'POST',
        data: { qr_token: token },
        dataType: 'json',
        success: function(data) {
            if (data.result) {
                toastr.success(data.msg);
                toggleAttBtn(data.data ? data.data.type : null);
                document.getElementById('qrTokenInput').value = '';
                document.getElementById('qrSection').style.display = 'none';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> تسجيل';
            } else {
                toastr.error(data.msg || 'رمز QR غير صالح');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> تسجيل';
            }
        },
        error: function() {
            toastr.error('خطأ في الاتصال');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> تسجيل';
        }
    });
}

$(document).ready(function(){
    function loadTable(month) {
        if ($.fn.DataTable.isDataTable('#attendanceTable')) {
            $('#attendanceTable').DataTable().destroy();
        }
        $('#attendanceTable').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            searching: false,
            info: false,
            ordering: false,
            responsive: true,
            pagingType: 'numbers',
            language: { url: 'dist/js/dataTables.arabic.json', emptyTable: 'لا توجد بصمات' },
            ajax: {
                url: 'hr-app/index.php?action=ess-attendance-history',
                type: 'POST',
                data: { month: month }
            }
        });
    }

    loadTable($('#monthFilter').val());

    $('#monthFilter').on('change', function(){
        loadTable($(this).val());
    });
});
</script>
