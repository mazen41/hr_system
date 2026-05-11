<?php
$screen = 'سجل المراجعة';
$page_title = 'سجل المراجعة';
include_once('inc/header.php');

// Only admin/employer can access
if (!$User->userIsEmployer()) {
    echo '<script>location.replace("ess-dashboard");</script>';
    die();
}
?>

<style>
.audit-filters { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.audit-filters .form-group { margin-bottom: 10px; }
.audit-filters label { font-weight: 600; font-size: 13px; color: #6b7280; }
.audit-detail-modal .modal-body pre { background: #f8f9fa; border-radius: 8px; padding: 12px; font-size: 13px; direction: ltr; text-align: left; max-height: 300px; overflow: auto; }
.badge-action { font-size: 12px; padding: 4px 10px; }
</style>

<section class="content" style="padding-top:20px;">
    <div class="container-fluid">

        <!-- Filters -->
        <div class="audit-filters">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>الإجراء</label>
                        <select id="filterAction" class="form-control form-control-sm">
                            <option value="">الكل</option>
                            <option value="login">تسجيل دخول</option>
                            <option value="logout">تسجيل خروج</option>
                            <option value="create">إنشاء</option>
                            <option value="update">تعديل</option>
                            <option value="delete">حذف</option>
                            <option value="approve">اعتماد</option>
                            <option value="reject">رفض</option>
                            <option value="attendance">حضور</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>النوع</label>
                        <select id="filterEntity" class="form-control form-control-sm">
                            <option value="">الكل</option>
                            <option value="session">جلسة</option>
                            <option value="attendance">حضور</option>
                            <option value="leave">إجازة</option>
                            <option value="advance">سلفة</option>
                            <option value="order">طلب</option>
                            <option value="user">مستخدم</option>
                            <option value="benefit">تعويض</option>
                            <option value="deduction">خصم</option>
                            <option value="qr_token">رمز QR</option>
                            <option value="setting">إعداد</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>من تاريخ</label>
                        <input type="date" id="filterDateFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>إلى تاريخ</label>
                        <input type="date" id="filterDateTo" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button id="btnFilter" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i> بحث</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button id="btnReset" class="btn btn-outline-secondary btn-sm btn-block"><i class="fas fa-redo"></i> إعادة تعيين</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <table id="auditTable" class="table table-hover table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الإجراء</th>
                            <th>المستخدم</th>
                            <th>النوع</th>
                            <th>الوصف</th>
                            <th>IP</th>
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
$(document).ready(function() {
    var table = $('#auditTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: 'hr-app/index.php?action=audit-log-list',
            type: 'POST',
            data: function(d) {
                d.action_filter = $('#filterAction').val();
                d.entity_filter = $('#filterEntity').val();
                d.date_from     = $('#filterDateFrom').val();
                d.date_to       = $('#filterDateTo').val();
            }
        },
        columns: [
            { width: '140px' },
            { width: '90px' },
            { width: '120px' },
            { width: '80px' },
            null,
            { width: '100px' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
            processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
        },
        dom: '<"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>'
    });

    $('#btnFilter').on('click', function() { table.ajax.reload(); });
    $('#btnReset').on('click', function() {
        $('#filterAction, #filterEntity').val('');
        $('#filterDateFrom').val('<?= date('Y-m-01') ?>');
        $('#filterDateTo').val('<?= date('Y-m-d') ?>');
        table.ajax.reload();
    });
});
</script>
