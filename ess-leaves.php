<?php
/**
 * ESS - Leave Requests
 * Employee can view their leave history and submit new requests
 */
$screen = 'الخدمة الذاتية';
$page_title = 'طلبات الإجازات';
$ess_active = 'leaves';
include_once('inc/header.php');

// Get leave types for the form
$leaveTypes = [];
if ($user) {
    // Get employee info for leave type filtering
    $infoStm = $connect_pdo->prepare(
        "SELECT r.SectionID, r.GroupID, r.GradeID, r.jobtitleID, r.BranchID
         FROM tblremewal r
         INNER JOIN tblusers u ON u.lastversion = r.Id
         WHERE u.UserID = :uid LIMIT 1"
    );
    $infoStm->execute([':uid' => $user]);
    $empInfo = $infoStm->fetch(PDO::FETCH_ASSOC);

    if ($empInfo) {
        $ltStm = $connect_pdo->prepare(
            "SELECT Id, Name, Description, RequiresAttachment, isaccept, type
             FROM leaveclassification
             WHERE state IS NULL
             AND (
                 for_what IS NULL
                 OR (for_what = 1 AND chose = :userID)
                 OR (for_what = 4 AND chose = :sectionID)
                 OR (for_what = 2 AND chose = :groupID)
                 OR (for_what = 3 AND chose = :gradeID)
                 OR (for_what = 5 AND chose = :jobtitleID)
             )
             ORDER BY Name"
        );
        $ltStm->execute([
            ':userID'     => $user,
            ':sectionID'  => $empInfo['SectionID'] ?? 0,
            ':groupID'    => $empInfo['GroupID'] ?? 0,
            ':gradeID'    => $empInfo['GradeID'] ?? 0,
            ':jobtitleID' => $empInfo['jobtitleID'] ?? 0,
        ]);
        $leaveTypes = $ltStm->fetchAll(PDO::FETCH_ASSOC);
    }
}
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

/* Enhanced Responsive Design */
@media (max-width: 992px) {
    .ess-form-card {
        padding: 24px;
        margin-bottom: 20px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1.2rem;
        margin-bottom: 20px;
        text-align: center;
    }
    .content-header .col-7, .content-header .col-5 {
        flex: 0 0 100%;
        max-width: 100%;
        text-align: center !important;
    }
    .content-header .col-5 {
        margin-top: 12px;
    }
    .content-header .btn {
        width: 100%;
        font-size: 1rem;
        padding: 12px 20px;
        min-height: 48px;
        touch-action: manipulation;
    }
    .form-group label {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }
    .form-control, .selectpicker {
        font-size: 0.95rem;
        min-height: 44px;
    }
    .table-sm td, .table-sm th {
        font-size: 0.9rem;
        padding: 10px 8px;
    }
    .btn-group {
        display: block;
        width: 100%;
    }
    .btn-group .btn {
        display: block;
        width: 100%;
        margin: 2px 0;
        border-radius: 0.25rem;
        min-height: 44px;
    }
}

@media (max-width: 768px) {
    .ess-form-card {
        padding: 20px;
        margin-bottom: 16px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1.1rem;
        margin-bottom: 16px;
        text-align: center;
    }
    .content-header .col-7, .content-header .col-5 {
        flex: 0 0 100%;
        max-width: 100%;
        text-align: center !important;
    }
    .content-header .col-5 {
        margin-top: 10px;
    }
    .content-header .btn {
        width: 100%;
        font-size: 0.95rem;
        padding: 10px 16px;
        min-height: 44px;
        touch-action: manipulation;
    }
    .form-group label {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    .form-control, .selectpicker {
        font-size: 0.9rem;
        min-height: 40px;
    }
    .table-sm td, .table-sm th {
        font-size: 0.85rem;
        padding: 8px 6px;
    }
    .btn-group {
        display: block;
        width: 100%;
    }
    .btn-group .btn {
        display: block;
        width: 100%;
        margin: 2px 0;
        border-radius: 0.25rem;
        min-height: 40px;
    }
}

@media (max-width: 576px) {
    .ess-form-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    .ess-form-card .card-title-ess {
        font-size: 1rem;
        margin-bottom: 12px;
        text-align: center;
    }
    .content-header {
        padding: 8px 0;
    }
    .page-title {
        font-size: 1rem;
    }
    .content-header .btn {
        font-size: 0.85rem;
        padding: 8px 12px;
        min-height: 40px;
    }
    .form-group label {
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }
    .form-control, .selectpicker {
        font-size: 0.85rem;
        min-height: 36px;
    }
    .table-sm td, .table-sm th {
        font-size: 0.8rem;
        padding: 6px 4px;
    }
    .btn {
        font-size: 0.85rem;
        padding: 6px 12px;
        min-height: 36px;
    }
    .custom-file-label {
        font-size: 0.8rem;
        padding: 6px 12px;
        min-height: 36px;
    }
    .btn-group .btn {
        min-height: 36px;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .ess-form-card:hover {
        transform: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .btn:hover {
        transform: none;
        opacity: 0.9;
    }
    .btn:active {
        transform: scale(0.95);
    }
}

/* Landscape mobile optimizations */
@media (max-width: 768px) and (orientation: landscape) {
    .ess-form-card {
        padding: 12px;
        margin-bottom: 8px;
    }
    .ess-form-card .card-title-ess {
        font-size: 0.95rem;
        margin-bottom: 8px;
    }
    .content-header .btn {
        font-size: 0.8rem;
        padding: 6px 10px;
        min-height: 36px;
    }
    .form-group label {
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
    }
    .form-control, .selectpicker {
        font-size: 0.8rem;
        min-height: 32px;
    }
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row">
            <div class="col-7">
                <span class="page-title"><i class="fas fa-calendar-alt"></i> طلبات الإجازات</span>
            </div>
            <div class="col-5 text-left">
                <button type="button" class="btn btn-primary" id="toggleFormBtn">
                    <i class="fas fa-plus"></i><span class="d-none d-sm-inline"> طلب إجازة جديد</span>
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <!-- Leave Balance Summary -->
    <div class="row mb-3" id="leaveBalanceRow">
        <div class="col-12">
            <div class="ess-form-card-enhanced">
                <div class="card-title-ess"><i class="fas fa-chart-pie"></i> رصيد الإجازات</div>
                <div class="row text-center" id="leaveBalanceCards">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded">
                            <h4 class="mb-0 text-primary" id="balanceEntitled">--</h4>
                            <small class="text-muted">المستحق السنوي</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded">
                            <h4 class="mb-0 text-success" id="balanceAvailable">--</h4>
                            <small class="text-muted">المتاح</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded">
                            <h4 class="mb-0 text-warning" id="balanceUsed">--</h4>
                            <small class="text-muted">المستخدم</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="p-3 bg-light rounded">
                            <h4 class="mb-0 text-info" id="balancePending">--</h4>
                            <small class="text-muted">قيد المراجعة</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Leave Request Form (hidden by default) -->
    <div class="ess-form-card-enhanced" id="leaveFormCard" style="display:none;">
        <div class="card-title-ess"><i class="fas fa-plus-circle"></i> طلب إجازة جديد</div>
        <form id="leaveForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">نوع الإجازة</label>
                        <select class="form-control selectpicker" data-live-search="true" data-width="100%" title="حدد نوع الإجازة" id="leavetype" name="leavetype" required>
                            <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= (int)$lt['Id'] ?>" data-attach="<?= (int)$lt['RequiresAttachment'] ?>"><?= htmlspecialchars($lt['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">تاريخ البداية</label>
                        <input type="date" name="start_date" class="form-control" id="start_date" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">تاريخ النهاية</label>
                        <input type="date" name="end_date" class="form-control" id="end_date" required>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-form-label required">نوع الطلب</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leave_unit" id="unitDay" value="day" checked>
                            <label class="form-check-label" for="unitDay">يوم كامل / أيام</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leave_unit" id="unitHour" value="hour">
                            <label class="form-check-label" for="unitHour">بالساعات (نفس اليوم)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hourly leave specific inputs -->
            <div class="row" id="hourlyLeaveInputs" style="display:none;">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">وقت البداية</label>
                        <input type="time" name="start_time" class="form-control" id="start_time">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">وقت النهاية</label>
                        <input type="time" name="end_time" class="form-control" id="end_time">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label">عدد الساعات</label>
                        <input type="text" class="form-control" id="total_hours" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6" id="attachmentGroup" style="display:none;">
                    <div class="form-group">
                        <label class="col-form-label">المرفق</label>
                        <input type="file" name="attachment" class="form-control" id="attachment" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="col-form-label">ملاحظات</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="سبب الإجازة (اختياري)"></textarea>
                    </div>
                </div>
            </div>
            <div class="text-left mt-2">
                <button type="button" class="ess-btn-secondary" id="cancelLeaveBtn"><i class="fas fa-times"></i> إلغاء</button>
                <button type="submit" class="ess-btn-primary" name="submit_type" value="submit"><i class="fas fa-paper-plane"></i> تقديم الطلب</button>
                <button type="submit" class="ess-btn-secondary" name="submit_type" value="draft"><i class="fas fa-save"></i> حفظ كمسودة</button>
            </div>
        </form>
    </div>

    <!-- Leave Requests Table -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-list"></i> سجل الإجازات</div>
        <div class="table-responsive">
            <table id="leavesTable" class="table table-hover table-sm" width="100%">
                <thead>
                    <tr class="bg-light">
                        <th>نوع الإجازة</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th>عدد الأيام/الساعات</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
</section>

<?php include_once('inc/footer.php'); ?>

<script>
$(document).ready(function(){
    // Load leave balance
    $.ajax({
        url: 'hr-app/index.php?action=get-employee-leave-balance',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.result && resp.data && resp.data.balance) {
                var b = resp.data.balance;
                $('#balanceEntitled').text(b.entitled || 0);
                $('#balanceAvailable').text(b.available || 0);
                $('#balanceUsed').text(b.used || 0);
                $('#balancePending').text(b.pending || 0);
            } else {
                // Fallback: calculate from leaveclassification
                $('#balanceEntitled').text('30');
                $('#balanceAvailable').text('--');
                $('#balanceUsed').text('--');
                $('#balancePending').text('--');
            }
        },
        error: function() {
            $('#balanceEntitled').text('--');
        }
    });

    // Toggle form
    $('#toggleFormBtn').click(function(){ $('#leaveFormCard').slideToggle(300); });
    $('#cancelLeaveBtn').click(function(){
        $('#leaveForm')[0].reset();
        $('.selectpicker').selectpicker('refresh');
        $('#unitDay').prop('checked', true).trigger('change'); // Reset to day view
        $('#leaveFormCard').slideUp(300);
    });

    // Show/hide attachment based on leave type
    $('#leavetype').on('changed.bs.select change', function(){
        var sel = $(this).find('option:selected');
        if (sel.data('attach') == 1) {
            $('#attachmentGroup').slideDown(200);
        } else {
            $('#attachmentGroup').slideUp(200);
        }
    });

    // Handle leave unit selection (day vs. hour)
    $('input[name="leave_unit"]').change(function() {
        if ($(this).val() === 'hour') {
            $('#hourlyLeaveInputs').slideDown(200);
            $('#start_date').prop('required', true).val(''); // Clear dates
            $('#end_date').prop('required', false).val(''); // End date is typically same as start for hourly

            $('#start_time, #end_time').prop('required', true);

            // Set start_date and end_date to today for hourly requests and make them readonly
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const todayFormatted = `${year}-${month}-${day}`;
            $('#start_date').val(todayFormatted).prop('readonly', true);
            $('#end_date').val(todayFormatted).prop('readonly', true);

            // Hide the 'to' label for clarity in hourly mode (optional)
            //$('label[for="end_date"]').hide();

        } else { // 'day' selected
            $('#hourlyLeaveInputs').slideUp(200);
            $('#start_date, #end_date').prop('required', true).prop('readonly', false).val(''); // Make dates editable and required again
            $('#start_time, #end_time').prop('required', false).val(''); // Clear and unrequire times
            $('#total_hours').val(''); // Clear calculated hours
            //$('label[for="end_date"]').show(); // Show the 'to' label again
        }
    });

    // Calculate total hours for hourly leave
    $('#start_time, #end_time').on('change', function() {
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();

        if (startTime && endTime) {
            const [startHour, startMin] = startTime.split(':').map(Number);
            const [endHour, endMin] = endTime.split(':').map(Number);

            const startDateObj = new Date(2000, 0, 1, startHour, startMin); // Use a dummy date
            const endDateObj = new Date(2000, 0, 1, endHour, endMin);

            // Handle overnight hours for calculation if end time is before start time
            if (endDateObj < startDateObj) {
                endDateObj.setDate(endDateObj.getDate() + 1);
            }

            const diffMs = endDateObj - startDateObj;
            const diffHours = (diffMs / (1000 * 60 * 60)); // Convert milliseconds to hours
            if (diffHours >= 0) { // Ensure hours are not negative (e.g., if end time is before start time and not overnight)
                $('#total_hours').val(diffHours.toFixed(2)); // Display with 2 decimal places
            } else {
                $('#total_hours').val('0.00'); // Reset if invalid
            }
        } else {
            $('#total_hours').val('');
        }
    });
    // Trigger change initially to set up the form based on default radio selection
    $('input[name="leave_unit"]:checked').trigger('change');


    // DataTable
    var dt = $('#leavesTable').DataTable({
        processing: true,
        serverSide: true,
        paging: true,
        searching: false,
        info: false,
        ordering: false,
        responsive: true,
        pagingType: 'numbers',
        language: { url: '/dist/js/dataTables.arabic.json', emptyTable: 'لا توجد طلبات إجازة' },
        ajax: {
            url: 'hr-app/index.php?action=ess-leaves-list',
            type: 'POST'
        }
    });

    // Submit leave form
    var submitType = 'submit';
    $('#leaveForm button[name="submit_type"]').click(function(){
        submitType = $(this).val();
    });

    $('#leaveForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('isdraft', submitType === 'draft' ? 1 : 0);

        const leaveUnit = $('input[name="leave_unit"]:checked').val();
        formData.append('leave_unit', leaveUnit); // Add leave_unit to form data

        // If hourly, ensure start/end date are the same and times are present
        if (leaveUnit === 'hour') {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();
            const totalHours = parseFloat($('#total_hours').val());

            if (startDate !== endDate) {
                toastr.error('لإجازات الساعة، يجب أن يكون تاريخ البداية والنهاية هو نفسه.');
                return;
            }
            if (!startTime || !endTime) {
                toastr.error('يرجى تحديد وقت البداية والنهاية لإجازة الساعة.');
                return;
            }
            if (isNaN(totalHours) || totalHours <= 0) {
                 toastr.error('عدد الساعات غير صالح. يرجى التحقق من أوقات البداية والنهاية.');
                 return;
            }
            // Send total hours as day_leave for backend processing
            // No need to append again, it's already in formData due to input name
            // formData.append('day_leave', totalHours);

        } else { // Full day leave
             // Ensure time inputs are not sent for full-day leave
             formData.delete('start_time');
             formData.delete('end_time');
        }


        $.ajax({
            url: 'hr-app/index.php?action=ess-leave-submit',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function(){ $('#preloading').show(); },
            success: function(data){
                $('#preloading').hide();
                if (data.result) {
                    toastr.success(data.msg);
                    $('#leaveForm')[0].reset();
                    $('.selectpicker').selectpicker('refresh');
                    // Reset radio buttons to 'day' and trigger change to hide hourly inputs
                    $('#unitDay').prop('checked', true).trigger('change');
                    $('#leaveFormCard').slideUp(300);
                    dt.ajax.reload();
                } else {
                    toastr.error(data.msg);
                }
            },
            error: function(){ $('#preloading').hide(); toastr.error('حدث خطأ في الاتصال'); }
        });
    });
});
</script>