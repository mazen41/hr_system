<?php
/**
 * ESS - Administrative Orders/Requests
 * Employee can view their orders and submit new ones
 */
$screen = 'الخدمة الذاتية';
$page_title = 'الطلبات الإدارية';
$ess_active = 'orders';
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
                <span class="page-title"><i class="fas fa-file-signature"></i> الطلبات الإدارية</span>
            </div>
            <div class="col-5 text-left">
                <button type="button" class="btn btn-primary" id="toggleFormBtn">
                    <i class="fas fa-plus"></i><span class="d-none d-sm-inline"> طلب جديد</span>
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    <!-- New Order Form -->
    <div class="ess-form-card-enhanced" id="orderFormCard" style="display:none;">
        <div class="card-title-ess"><i class="fas fa-plus-circle"></i> طلب إداري جديد</div>
        <form id="orderForm">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-form-label required">الموضوع</label>
                        <input type="text" name="title" class="form-control" id="order_title" placeholder="موضوع الطلب" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-form-label required">الوصف</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="تفاصيل الطلب" required></textarea>
                    </div>
                </div>
            </div>
            <div class="text-left mt-2">
                <button type="button" class="ess-btn-secondary" id="cancelOrderBtn"><i class="fas fa-times"></i> إلغاء</button>
                <button type="submit" class="ess-btn-primary" name="submit_type" value="submit"><i class="fas fa-paper-plane"></i> تقديم الطلب</button>
                <button type="submit" class="ess-btn-secondary" name="submit_type" value="draft"><i class="fas fa-save"></i> حفظ كمسودة</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="ess-form-card-enhanced">
        <div class="card-title-ess"><i class="fas fa-list"></i> سجل الطلبات</div>
        <div class="table-responsive">
            <table id="ordersTable" class="table table-hover table-sm" width="100%">
                <thead>
                    <tr class="bg-light">
                        <th>الموضوع</th>
                        <th>الحالة</th>
                        <th>القراءة</th>
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
    $('#toggleFormBtn').click(function(){ $('#orderFormCard').slideToggle(300); });
    $('#cancelOrderBtn').click(function(){ $('#orderFormCard').slideUp(300); });

    var dt = $('#ordersTable').DataTable({
        processing: true,
        serverSide: true,
        paging: true,
        searching: false,
        info: false,
        ordering: false,
        responsive: true,
        pagingType: 'numbers',
        language: { url: '/dist/js/dataTables.arabic.json', emptyTable: 'لا توجد طلبات' },
        ajax: {
            url: 'hr-app/index.php?action=ess-orders-list',
            type: 'POST'
        }
    });

    var submitType = 'submit';
    $('#orderForm button[name="submit_type"]').click(function(){
        submitType = $(this).val();
    });

    $('#orderForm').on('submit', function(e){
        e.preventDefault();
        var formData = $(this).serialize() + '&isdraft=' + (submitType === 'draft' ? 1 : 0);

        $.ajax({
            url: 'hr-app/index.php?action=ess-order-submit',
            method: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function(){ $('#preloading').show(); },
            success: function(data){
                $('#preloading').hide();
                if (data.result) {
                    toastr.success(data.msg);
                    $('#orderForm')[0].reset();
                    $('#orderFormCard').slideUp(300);
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
