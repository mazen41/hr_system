<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'قائمة المتقدمين للوظائف';

$appid = 'HR';
$page_perm = ['عرض المتقدمين']; // عدل الصلاحية حسب نظامك
include_once('inc/header.php');
?>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"> المتقدمين للوظائف</h1>
            </div>
            <div class="col-sm-6 text-left">
                <a href="employer-add" class="btn btn-success"><i class="fas fa-plus"></i> إضافة متقدم / موظف</a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">تصفية وبحث</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>الاسم أو البريد</label>
                            <input type="text" id="filter_name" class="form-control" placeholder="بحث بالاسم أو البريد الإلكتروني...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>حالة الطلب</label>
                            <select id="filter_status" class="form-control selectpicker">
                                <option value="">الكل</option>
                                <option value="0">قيد الانتظار</option>
                                <option value="1">مقبول</option>
                                <option value="2">مرفوض</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" id="btn-filter" class="btn btn-primary btn-block mb-3"><i class="fas fa-search"></i> بحث</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table id="applicants_tb" class="table table-bordered table-striped table-hover text-center w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>الرقم</th>
                            <th>اسم المتقدم</th>
                            <th>التواصل</th>
                            <th>المرفقات</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
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
    // إعداد DataTables
    var table = $('#applicants_tb').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "hr-app/index.php?action=applicants-list",
            "type": "POST",
            "data": function(d) {
                d.name = $('#filter_name').val();
                d.status = $('#filter_status').val();
            }
        },
        "order": [[0, "desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
        }
    });

    $('#btn-filter').click(function() {
        table.ajax.reload();
    });

    // رفض المتقدم
    $(document).on('click', '.reject-btn', function() {
        var applicantId = $(this).data('id');
        
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "هل تريد حقاً رفض هذا المتقدم؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، أرفض',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'hr-app/index.php?action=applicant-reject',
                    type: 'POST',
                    data: { id: applicantId },
                    dataType: 'json',
                    beforeSend: function() { $('#preloading').show(); },
                    success: function(response) {
                        $('#preloading').hide();
                        if (response.result) {
                            toastr.success(response.msg);
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function() {
                        $('#preloading').hide();
                        toastr.error('حدث خطأ أثناء الاتصال بالخادم.');
                    }
                });
            }
        });
    });
});
</script>