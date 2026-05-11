<?php
/**
 * ESS - Advance Requests
 * Employee can view their advance history and submit new requests
 */
$screen = 'الخدمة الذاتية';
$page_title = 'طلبات السلف';
$ess_active = 'advances';
include_once('inc/header.php');
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
                <span class="page-title"><i class="fas fa-hand-holding-usd"></i> طلبات السلف</span>
            </div>
            <div class="col-5 text-left">
                <button type="button" class="btn btn-primary" id="toggleFormBtn">
                    <i class="fas fa-plus"></i><span class="d-none d-sm-inline"> طلب سلفة جديد</span>
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <!-- New Advance Request Form -->
    <div class="ess-form-card-enhanced" id="advanceFormCard" style="display:none;">
        <div class="card-title-ess"><i class="fas fa-plus-circle"></i> طلب سلفة جديد</div>
        <form id="advanceForm">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">المبلغ (ر.س)</label>
                        <input type="number" name="amount" class="form-control" id="amount" placeholder="أدخل المبلغ" min="1" step="0.01" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" class="form-control" id="due_date" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-form-label required">نوع السلفة</label>
                        <select class="form-control" name="type" id="advance_type" required>
                            <option value="1">سلفة على الراتب</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-form-label">السبب</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="سبب طلب السلفة (اختياري)"></textarea>
                    </div>
                </div>
            </div>
            <div class="text-left mt-2">
                <button type="button" class="ess-btn-secondary" id="cancelAdvanceBtn"><i class="fas fa-times"></i> إلغاء</button>
                <button type="submit" class="ess-btn-primary" name="submit_type" value="submit"><i class="fas fa-paper-plane"></i> تقديم الطلب</button>
                <button type="submit" class="ess-btn-secondary" name="submit_type" value="draft"><i class="fas fa-save"></i> حفظ كمسودة</button>
            </div>
        </form>
    </div>

    <!-- Advances Table -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-list"></i> سجل السلف</div>
        <div class="table-responsive">
            <table id="advancesTable" class="table table-hover table-sm" width="100%">
                <thead>
                    <tr class="bg-light">
                        <th>المبلغ</th>
                        <th>النوع</th>
                        <th>تاريخ الاستحقاق</th>
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
    $('#toggleFormBtn').click(function(){ $('#advanceFormCard').slideToggle(300); });
    $('#cancelAdvanceBtn').click(function(){ $('#advanceFormCard').slideUp(300); });

    var dt = $('#advancesTable').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        paging: true,
        searching: false,
        info: false,
        ordering: false,
        pagingType: 'numbers',
        language: { url: '/dist/js/dataTables.arabic.json', emptyTable: 'لا توجد طلبات سلف' },
        ajax: {
            url: 'hr-app/index.php?action=ess-advances-list',
            type: 'POST'
        }
    });

    var submitType = 'submit';
    $('#advanceForm button[name="submit_type"]').click(function(){
        submitType = $(this).val();
    });

    $('#advanceForm').on('submit', function(e){
        e.preventDefault();
        var formData = $(this).serialize() + '&isdraft=' + (submitType === 'draft' ? 1 : 0);

        $.ajax({
            url: 'hr-app/index.php?action=ess-advance-submit',
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function(){ $('#preloading').show(); },
            success: function(data){
                $('#preloading').hide();
                if (data.result) {
                    toastr.success(data.msg);
                    $('#advanceForm')[0].reset();
                    $('#advanceFormCard').slideUp(300);
                    dt.ajax.reload();
                } else {
                    toastr.error(data.msg);
                }
            },
            error: function(){ $('#preloading').hide(); toastr.error('حدث خطأ في الاتصال'); }
        });
    });

    // Only allow numbers in amount
    $('#amount').on('keypress', function(e) {
        var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode !== 46 && (charCode < 48 || charCode > 57)) {
            e.preventDefault();
        }
    });
});
</script>
