<?php
$page_title = 'المستندات والتقارير';
$page_perm = [];
$appid = 1;
include_once('inc/header.php');

// Get employee data - try multiple session keys
$userId = $_SESSION['UserID'] ?? $_SESSION['user']['UserID'] ?? $_SESSION['user_id'] ?? 0;
try {
    $stmt = $connect_pdo->prepare("
        SELECT u.UserID, u.FirstName, u.LastName, u.UserEmail, u.Phone,
               r.Salary, r.Currency, r.new_s_date, r.new_e_date,
               b.branch_name, j.Name as job_title,
               s.Name as section_name, g.Name as grade_name
        FROM tblusers u
        LEFT JOIN tblremewal r ON u.lastversion = r.Id
        LEFT JOIN branches b ON r.BranchID = b.branch_id
        LEFT JOIN tbljobtitle j ON j.Id = r.jobtitleID
        LEFT JOIN tblsection s ON s.Id = r.SectionID
        LEFT JOIN tbljobgrade g ON g.Id = r.GradeID
        WHERE u.UserID = ?
    ");
    $stmt->execute([$userId]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $emp = null;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12">
                <h1 class="m-0"><i class="fas fa-file-pdf"></i> المستندات والتقارير</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
            
            <!-- Document Categories -->
            <div class="row">
                <!-- Salary Documents -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> كشوف الرواتب</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">تحميل وطباعة كشوف الرواتب الشهرية</p>
                            <div class="form-group">
                                <label>اختر الشهر</label>
                                <select class="form-control" id="salaryMonth">
                                    <?php
                                    $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
                                    $currentMonth = date('n');
                                    $currentYear = date('Y');
                                    for ($i = 0; $i < 12; $i++) {
                                        $m = $currentMonth - $i;
                                        $y = $currentYear;
                                        if ($m <= 0) { $m += 12; $y--; }
                                        echo "<option value='{$y}-{$m}'>{$months[$m-1]} {$y}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button class="btn btn-primary btn-block" onclick="downloadSalarySlip()">
                                <i class="fas fa-download"></i> تحميل كشف الراتب
                            </button>
                            <button class="btn btn-outline-primary btn-block mt-2" onclick="printSalarySlip()">
                                <i class="fas fa-print"></i> طباعة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contract Documents -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-file-contract"></i> العقود</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">عرض وتحميل عقود العمل</p>
                            <div id="contractsList">
                                <div class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin"></i> جاري التحميل...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificates -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ess-form-card-enhanced h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-certificate"></i> الشهادات والخطابات</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">طلب شهادات وخطابات رسمية</p>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action" onclick="requestCertificate('experience')">
                                    <i class="fas fa-briefcase text-primary"></i> شهادة خبرة
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="requestCertificate('salary')">
                                    <i class="fas fa-money-check text-success"></i> تعريف بالراتب
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="requestCertificate('employment')">
                                    <i class="fas fa-id-card text-info"></i> تعريف بالعمل
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="requestCertificate('vacation')">
                                    <i class="fas fa-plane text-warning"></i> خطاب إجازة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card ess-form-card-enhanced">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> التقارير الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Date Range Filter -->
                                <div class="col-md-4 mb-3">
                                    <label>الفترة من</label>
                                    <input type="date" class="form-control" id="reportFromDate" value="<?= date('Y-01-01') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>إلى</label>
                                    <input type="date" class="form-control" id="reportToDate" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>نوع التقرير</label>
                                    <select class="form-control" id="reportType">
                                        <option value="attendance">تقرير الحضور</option>
                                        <option value="leaves">تقرير الإجازات</option>
                                        <option value="advances">تقرير السلف</option>
                                        <option value="salary">تقرير الرواتب</option>
                                        <option value="all">تقرير شامل</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary btn-lg" onclick="generateReport()">
                                    <i class="fas fa-file-pdf"></i> إنشاء التقرير
                                </button>
                                <button class="btn btn-success btn-lg mr-2" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> تصدير Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    </div>
</section>

<!-- Print Template (Hidden) -->
<div id="printTemplate" style="display:none;">
    <div id="printContent"></div>
</div>

<?php include_once('inc/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    loadContracts();
});

function loadContracts() {
    $.ajax({
        url: 'hr-app/index.php?action=ess-contracts-list',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var html = '';
            if (response.data && response.data.length > 0) {
                response.data.forEach(function(contract) {
                    html += '<div class="d-flex justify-content-between align-items-center border-bottom py-2">';
                    html += '<div><strong>' + contract.type + '</strong><br><small class="text-muted">' + contract.date + '</small></div>';
                    html += '<div>';
                    html += '<button class="btn btn-sm btn-outline-primary" onclick="viewContract(' + contract.id + ')"><i class="fas fa-eye"></i></button> ';
                    html += '<button class="btn btn-sm btn-primary" onclick="downloadContract(' + contract.id + ')"><i class="fas fa-download"></i></button>';
                    html += '</div></div>';
                });
            } else {
                html = '<div class="text-center text-muted py-3"><i class="fas fa-folder-open fa-2x mb-2"></i><br>لا توجد عقود</div>';
            }
            $('#contractsList').html(html);
        },
        error: function() {
            $('#contractsList').html('<div class="text-center text-danger">حدث خطأ في تحميل العقود</div>');
        }
    });
}

function downloadSalarySlip() {
    var month = $('#salaryMonth').val();
    window.location.href = 'hr-app/index.php?action=ess-salary-pdf&month=' + month;
}

function printSalarySlip() {
    var month = $('#salaryMonth').val();
    $.ajax({
        url: 'hr-app/index.php?action=ess-salary-print&month=' + month,
        type: 'GET',
        success: function(html) {
            var printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
            }, 500);
        }
    });
}

function requestCertificate(type) {
    Swal.fire({
        title: 'طلب شهادة',
        text: 'هل تريد طلب هذه الشهادة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، أطلب',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#0d21a5'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: 'hr-app/index.php?action=ess-request-certificate',
                type: 'POST',
                data: { type: type },
                dataType: 'json',
                success: function(response) {
                    if (response.result) {
                        Swal.fire('تم!', 'تم إرسال طلبك بنجاح', 'success');
                    } else {
                        Swal.fire('خطأ', response.msg || 'حدث خطأ', 'error');
                    }
                }
            });
        }
    });
}

function viewContract(id) {
    window.open('hr-app/index.php?action=ess-contract-view&id=' + id, '_blank');
}

function downloadContract(id) {
    window.location.href = 'hr-app/index.php?action=ess-contract-pdf&id=' + id;
}

function generateReport() {
    var fromDate = $('#reportFromDate').val();
    var toDate = $('#reportToDate').val();
    var type = $('#reportType').val();
    
    window.location.href = 'hr-app/index.php?action=ess-report-pdf&from=' + fromDate + '&to=' + toDate + '&type=' + type;
}

function exportToExcel() {
    var fromDate = $('#reportFromDate').val();
    var toDate = $('#reportToDate').val();
    var type = $('#reportType').val();
    
    window.location.href = 'hr-app/index.php?action=ess-report-excel&from=' + fromDate + '&to=' + toDate + '&type=' + type;
}
</script>
