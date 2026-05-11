<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("fingerprint-list"); </script>';
	die(); 
}
$appid  = 'HR';
$page_perm = ['عرض بصمة'];

$screen = 'إدارة الموارد البشرية';
$page_title = ' إعدادات البصمة';
include_once('inc/header.php');

// تحديد صيغة التاريخ الافتراضية لحل مشكلة $dateformat
$dateFormat = 'Y-m-d'; 
$timeFormat = 'h:i A';

if(isset($_GET['id'])){
	$get_id = (int)$_GET['id'];
    
    // الاستعلام الصحيح مع الجداول والأعمدة الصحيحة
    $query = " SELECT a.FingerprintID, b.branch_name, a.FingerprintName,
                a.FingerprintState, a.FingerprintType,
                a.lastUpdateDate, a.CreatedDate, 
                a.ip, a.FingerprintSerailnumber, a.port,
                u.FirstName, u.LastName
                FROM tbfingerprint AS a
                LEFT JOIN branches AS b ON a.BranchID = b.branch_id
                LEFT JOIN tblusers u ON a.CreatedBy = u.UserID
                WHERE a.FingerprintID = :id 
                LIMIT 1 ";
                
	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id' => $get_id));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
	
        // تحديد حالة الجهاز والألوان
        if($row['FingerprintState'] == 3){
            $stopped_status = 'جاري الصيانة';
            $stopped_color = 'warning';
            $status_icon = 'fa-tools';
        } elseif($row['FingerprintState'] == 1){
            $stopped_status = 'نشط / شغال';
            $stopped_color = 'success';
            $status_icon = 'fa-check-circle';
        } elseif($row['FingerprintState'] == 2){
            $stopped_status = 'معطل / موقف';
            $stopped_color = 'danger';
            $status_icon = 'fa-times-circle';
        } else {
            $stopped_status = 'غير محدد';
            $stopped_color = 'secondary';
            $status_icon = 'fa-question-circle';
        }
	} else {
		echo'<script> location.replace("fingerprint-list"); </script>';
		die();
	}
}
?>

<div class="content-header page-nav" style="background:#fff; border-bottom: 1px solid #dee2e6; margin-bottom: 20px;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8 col-sm-12">
                <h3 class="m-0 text-dark" style="display: inline-block; font-weight: bold;">
                    <i class="fas fa-fingerprint text-primary mr-2"></i> <?= htmlspecialchars($row['FingerprintName']) ?>
                </h3>
                <span class="badge badge-<?= $stopped_color ?> ml-2 px-3 py-2" style="font-size: 14px;">
                    <i class="fas <?= $status_icon ?>"></i> <?= $stopped_status ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
    
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])): ?>
            <div class="alert alert-success alert-dismissible shadow-sm" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-check"></i> <?= $_SESSION['alert'] ?>
                <?php unset($_SESSION['alert']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline shadow">
                    <div class="card-header p-3 d-print-none">
                        <div class="btn-group" style="direction:rtl; float: right;">
                            <a href="hr-setting" class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-cog"></i> الإعدادات
                            </a>
                            <a href="fingerprint-list" class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-list"></i> قائمة الأجهزة
                            </a>
                            
                            <?php if ($User->isAllowedPerm(['اضافة بصمة'], $appid)) { ?>
                                <a href="fingerprint-add" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-plus"></i> إضافة جهاز جديد
                                </a>
                            <?php } ?>
                            
                            <?php if ($User->isAllowedPerm(['تعديل بصمة'], $appid)) { ?>
                                <a href="fingerprint-add?id=<?=$row['FingerprintID']?>" class="btn btn-outline-info btn-sm">
                                    <i class="fa fa-edit"></i> تعديل
                                </a>
                            <?php } ?>
                            
                            <?php if ($User->isAllowedPerm(['حذف بصمة'], $appid)) { ?>
                                <button type="button" class="btn btn-outline-danger btn-sm remove_client" value="<?=$row['FingerprintID']?>">
                                    <i class="fas fa-trash-alt"></i> حذف
                                </button>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <ul class="nav nav-tabs p-2" id="custom-content-above-tab" role="tablist" style="background-color: #f8f9fa;">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab">
                                    <i class="fas fa-info-circle"></i> عن جهاز البصمة
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="attendance-logs-tab" data-toggle="pill" href="#attendance-logs" role="tab">
                                    <i class="fas fa-users"></i> سجلات الحضور
                                </a>
                            </li>			
                        </ul>
           
                        <div class="tab-content p-4" id="custom-content-above-tabContent">
                            <!-- Main Info Tab -->
                            <div class="tab-pane fade show active" id="main-info" role="tabpanel">
                                <div class="row">
                                    <!-- Network & Hardware Info -->
                                    <div class="col-lg-6 col-md-12 mb-4">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title m-0 text-primary font-weight-bold"><i class="fas fa-network-wired"></i> بيانات الجهاز والشبكة</h5>
                                            </div>
                                            <div class="card-body p-0">
                                                <ul class="list-group list-group-flush text-right">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-desktop text-muted ml-2"></i> اسم الجهاز</span>
                                                        <strong><?= !empty($row['FingerprintName']) ? $row['FingerprintName'] : '-' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-building text-muted ml-2"></i> الفرع المرتبط</span>
                                                        <strong><?= !empty($row['branch_name']) ? $row['branch_name'] : 'لم يتم التحديد' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-barcode text-muted ml-2"></i> الرقم التسلسلي</span>
                                                        <strong class="text-monospace"><?= !empty($row['FingerprintSerailnumber']) ? $row['FingerprintSerailnumber'] : '-' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-sitemap text-muted ml-2"></i> عنوان (IP)</span>
                                                        <strong class="text-monospace text-primary"><?= !empty($row['ip']) ? $row['ip'] : '-' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-plug text-muted ml-2"></i> منفذ (Port)</span>
                                                        <strong class="text-monospace"><?= !empty($row['port']) ? $row['port'] : '4370' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-industry text-muted ml-2"></i> نوع الجهاز (المصنع)</span>
                                                        <strong><?= !empty($row['FingerprintType']) ? $row['FingerprintType'] : '-' ?></strong>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- System & Logs Info -->
                                    <div class="col-lg-6 col-md-12 mb-4">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title m-0 text-info font-weight-bold"><i class="fas fa-history"></i> سجل النظام</h5>
                                            </div>
                                            <div class="card-body p-0">
                                                <ul class="list-group list-group-flush text-right">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-user-plus text-muted ml-2"></i> أضيف بواسطة</span>
                                                        <strong><?= !empty($row['FirstName']) ? $row['FirstName'].' '.$row['LastName'] : 'مدير النظام' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-calendar-alt text-muted ml-2"></i> تاريخ الإضافة</span>
                                                        <strong dir="ltr"><?= !empty($row['CreatedDate']) ? date($dateFormat . ' ' . $timeFormat, strtotime($row['CreatedDate'])) : '-' ?></strong>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span><i class="fas fa-edit text-muted ml-2"></i> آخر تعديل</span>
                                                        <strong dir="ltr"><?= !empty($row['lastUpdateDate']) ? date($dateFormat . ' ' . $timeFormat, strtotime($row['lastUpdateDate'])) : '-' ?></strong>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
		  
                            <!-- Attendance Logs Tab -->
                            <div class="tab-pane fade" id="attendance-logs" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-12 text-right">
                                        <button type="button" class="btn btn-success" id="sync-attendance-btn">
                                            <i class="fas fa-sync-alt"></i> مزامنة الحضور من الجهاز الآن
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table id="attendance-logs-table" class="table table-bordered table-hover table-striped w-100 text-center">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>تاريخ ووقت البصمة</th>
                                                        <th>اسم الموظف</th>
                                                        <th>نوع البصمة</th>
                                                        <th>طريقة التحقق</th>
                                                        <th>حالة السجل</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data loaded via Ajax -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
			  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_once('inc/footer.php'); ?>

<!-- DataTables Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

<script>
$(document).ready(function(){
	
    // ابقاء القائمة مفتوحة (تعديل حسب نظامك)
    $("#pur_nav").addClass('menu-open');
    
    const urlParams = new URLSearchParams(window.location.search);
    const param_id = urlParams.get('id');

    // إعداد جدول سجلات الحضور
    var attendanceTable = $('#attendance-logs-table').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        responsive: true,
        pageLength: 25,
        language: {
            url: '/dist/js/dataTables.arabic.json'
        },
        ajax: {
            url: 'hr-app/index.php',
            type: 'POST',
            data: {
                action: 'fingerprint-attendance-logs',
                device_id: param_id
            }
        },
        columns: [
            { data: 'punch_time', render: function(data) {
                return data ? '<span dir="ltr">' + new Date(data).toLocaleString('ar-SA') + '</span>' : '-';
            }},
            { data: 'employee_name' },
            { data: 'punch_type', render: function(data) {
                var typeMap = {'IN': 'دخول', 'OUT': 'خروج', 'UNKNOWN': 'غير معروف'};
                return typeMap[data] || data;
            }},
            { data: 'verify_mode', render: function(data) {
                var modeMap = {'FINGER': 'بصمة', 'CARD': 'بطاقة', 'PIN': 'رقم سري', 'FACE': 'وجه'};
                return modeMap[data] || data;
            }},
            { data: 'status', render: function(data) {
                return '<span class="badge badge-' + (data === 'success' ? 'success' : 'warning') + '">' + data + '</span>';
            }}
        ],
        buttons: [
            { extend: 'excel', text: '<i class="fas fa-file-excel text-success"></i> إكسيل', className: 'btn btn-light border' },
            { extend: 'pdf', text: '<i class="fas fa-file-pdf text-danger"></i> PDF', className: 'btn btn-light border' },
            { extend: 'print', text: '<i class="fas fa-print text-primary"></i> طباعة', className: 'btn btn-light border' }
        ]
    });

    // زر مزامنة الحضور
    $('#sync-attendance-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري المزامنة مع الجهاز...');
        
        $.ajax({
            url: 'hr-app/index.php',
            type: 'POST',
            data: {
                action: 'fingerprint-sync-logs',
                device_id: param_id,
                from: moment().subtract(7, 'days').format('YYYY-MM-DD 00:00:00'),
                to: moment().format('YYYY-MM-DD 23:59:59')
            },
            dataType: 'json',
            success: function(response) {
                if (response.result !== false) {
                    toastr.success('تمت مزامنة ' + response.data.pulled + ' سجل بنجاح');
                    attendanceTable.ajax.reload();
                } else {
                    toastr.error(response.msg || 'فشلت المزامنة');
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء الاتصال بالجهاز للمزامنة');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> مزامنة الحضور من الجهاز الآن');
            }
        });
    });

    // دالة تأكيد الحذف
    function confirm_remove(id) {
        if(id != '') {
            $('#modal_title').text('تأكيد عملية الحذف');
            $('#modal_default .modal-body').addClass('loader');
            $('#modal_default .modal-dialog').removeClass('modal-lg');
            $('#modal_default').modal({show:true});
            $('#modal_default .modal-body').load('./hr-app/fingerprint-remove?id='+id, function(){
                 // Loaded
            });
        }
    }
    
    $(document).on('click', '.remove_client', function(){
        var id = $(this).val();
        confirm_remove(id);
    }); 

});
</script>