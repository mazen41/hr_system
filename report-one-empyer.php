<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Start output buffering

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';

// =========================================================================
// 1. معالجة طلب AJAX لجلب تفاصيل الموظف المحددة (تم نقله إلى هنا)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'report-one-empyer') {
    ob_end_clean(); // مسح أي مخرجات سابقة لضمان إرسال JSON نقي
    header('Content-Type: application/json');
    
    $empId = intval($_POST['id'] ?? 0);
    
    if ($empId <= 0) {
        echo json_encode(['check' => false, 'msg' => 'الرجاء اختيار موظف بشكل صحيح']);
        exit;
    }

    try {
        // أ. جلب بيانات الموظف الأساسية
        $stmt = $connect_pdo->prepare("SELECT * FROM tblusers WHERE UserID = ?");
        $stmt->execute([$empId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            echo json_encode(['check' => false, 'msg' => 'لم يتم العثور على بيانات هذا الموظف']);
            exit;
        }

        // دالة مساعدة لتحويل الأرقام المفصولة بفاصلة إلى أسماء (مثل 1,2 -> تأمين بوبا, تأمين التعاونية)
        $getNamesFromIds = function($table, $idsStr) use ($connect_pdo) {
            if(empty($idsStr)) return [];
            $ids = array_filter(explode(',', $idsStr));
            if(empty($ids)) return [];
            $qs = str_repeat('?,', count($ids) - 1) . '?';
            try {
                $stmt = $connect_pdo->prepare("SELECT name FROM $table WHERE id IN ($qs)");
                $stmt->execute($ids);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch(Exception $e) { return $ids; } // في حال عدم وجود الجدول يرجع الأرقام
        };

        $instant_names = $getNamesFromIds('tblinsurance', $employee['user_insurance'] ?? '');

        // ب. جلب السجل الوظيفي والمالي من جدول العقود (tblremewal)
        $stmt = $connect_pdo->prepare("
            SELECT r.*, 
                   s.name AS section_name, 
                   jt.name AS jobtitle_name,
                   g.name AS name_grade,
                   gp.name AS group_name,
                   t.name AS name_n
            FROM tblremewal r
            LEFT JOIN tblsection s ON r.SectionID = s.id
            LEFT JOIN tbljobtitle jt ON r.jobtitleID = jt.id
            LEFT JOIN tbljobgrade g ON r.GradeID = g.id
            LEFT JOIN tblgroup gp ON r.GroupID = gp.id
            LEFT JOIN tblemploymenttype t ON r.TypeID = t.id
            WHERE r.UserID = ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$empId]);
        $struct = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $shift_names = [];
        $finger_names = [];
        if (!empty($struct)) {
            $latestContract = $struct[0]; // آخر عقد
            $shift_names = $getNamesFromIds('tblshift', $latestContract['shiftID'] ?? '');
            $finger_names = $getNamesFromIds('tblfingerprint', $latestContract['fingerID'] ?? '');
        }

        // ج. جلب الشهادات والخبرات (مع استخدام try-catch لتفادي الأخطاء إذا كانت الجداول غير موجودة)
        $certifcate = [];
        try {
            $stmt = $connect_pdo->prepare("SELECT * FROM tblcertificates WHERE UserID = ?");
            $stmt->execute([$empId]);
            $certifcate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) { /* تجاهل الخطأ إذا كان الجدول غير موجود */ }

        $experince = [];
        try {
            $stmt = $connect_pdo->prepare("SELECT * FROM tblexperience WHERE UserID = ?");
            $stmt->execute([$empId]);
            $experince = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) { /* تجاهل الخطأ إذا كان الجدول غير موجود */ }

        // إرسال البيانات كـ JSON لصفحة الـ Frontend
        echo json_encode([
            'check' => true,
            'employee' => $employee,
            'struct' => $struct,
            'instant_names' => $instant_names,
            'shift_names' => $shift_names,
            'finger_names' => $finger_names,
            'certifcate' => $certifcate,
            'experince' => $experince
        ]);

    } catch (Exception $e) {
        echo json_encode(['check' => false, 'msg' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}
// =========================================================================


// =========================================================================
// 2. تحميل الصفحة العادية
// =========================================================================
$User = new User($connect_pdo);
$User->loadFromSession();

$appid  = 'HR';
$screen = 'الموظفين';
$page_title = 'تفاصيل الموظفين';
$report_name = 'تفاصيل الموظف';
include_once('inc/header.php');

$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches);
$allowed_branch = implode(',', $branch_ids);

// تبسيط استعلام قائمة الموظفين واستخدام CONCAT_WS لتجنب مشكلة الـ NULL
$query_ = "SELECT u.UserID, CONCAT_WS(' ', NULLIF(u.FirstName,''), NULLIF(u.LastName,'')) as Name 
           FROM tblusers u 
           WHERE u.BranchID IN ($allowed_branch) AND u.isemp = 1 
           ORDER BY Name ASC";
$stmt = $connect_pdo->prepare($query_);
$stmt->execute();
$results = $stmt->fetchAll();
?>

<style>
.modal-dialog .overlay { background-color: rgba(255, 255, 255, 0.7); }
@page { size: A4; margin: 15mm 10mm; }
body { background-color: #f8f9fa; color: #333; }
.report-container { background-color: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); min-height: 100vh; }
.official-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.official-table th { background-color: #f8f9fa; padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold; }
.official-table td { padding: 10px; border: 1px solid #ddd; text-align: right; }
.section-title { font-size: 18px; font-weight: bold; color: #2c3e50; margin: 25px 0 15px 0; padding-right: 10px; border-right: 4px solid #3498db; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 14px; }
.status-active { background-color: #28a745; color: white; }
.status-inactive { background-color: #dc3545; color: white; }
.no-print { display: block; }

@media print {
    body { background-color: white; direction: rtl; }
    .no-print { display: none !important; }
    .report-container { box-shadow: none; }
    .table th { background-color: #f5f5f5 !important; }
    section, div { background-color: #FFF !important; padding: 0px !important; margin: 0px !important; }
    a { color:#212529 !important; text-decoration: none !important; }
    .invoice .d-none { display:block !important; }
}
</style>

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row">
            <div class="col-7">
                <span class="page-title">معلومات الموظف</span>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
            <div class="alert alert-success alert-dismissible" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-check"></i>
                <?=$_SESSION['alert']?>
                <?php $_SESSION['alert'] ='';?>
            </div>
        <?php endif;?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="invoice mb-3" id="filter-area">
                    <div class="card-header bg-gry">
                        <h3 class="card-title">بحث</h3>
                    </div>
                    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">
                        <div class="card-body card-body pt-0 pb-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employer" class="col-form-label logindata">الموظف</label>
                                        <select class="selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="أختر" id="employer" name="employer" required>
                                            <?php
                                            if(!empty($results)) {  
                                                foreach ($results as $ins) { 
                                                    echo '<option value="'.$ins["UserID"].'">'.$ins["Name"].'</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-1">
                            <div class="text-left">
                                <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> بحث</button>
                            </div>
                        </div>
                    </form>
                    <div class="overlay" style="display:none" id="preloading"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
                </div> 
            </div>
        </div>

        <div class="container-fluid" id="result-containr" style="display:none">
            <div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-print-none text-left mb-2">
                                <button type="button" class="btn btn-default print_repo" onclick="printData()"><i class="fas fa-print"></i> طباعة</button>
                                <button type="button" class="btn btn-primary download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> PDF</button>
                            </div>
                            <div class="card shadow-sm border-light" style="overflow:scroll" id="datauser">
                                
                                <h3 class="section-title">البيانات الشخصية</h3>
                                <table class="official-table">
                                    <tr><th width="25%">الاسم الكامل</th><td id="fullName"></td></tr>
                                    <tr><th>البريد الإلكتروني</th><td id="userEmail"></td></tr>
                                    <tr><th>رقم الجوال</th><td id="phone"></td></tr>
                                    <tr><th>هاتف آخر</th><td id="otherPhone"></td></tr>
                                    <tr><th>العنوان</th><td id="address"></td></tr>
                                    <tr><th>الجنس</th><td id="gender"></td></tr>
                                    <tr><th>الحالة الاجتماعية</th><td id="maritalStatus"></td></tr>
                                    <tr><th>الحالة الصحية</th><td id="employmenthealth"></td></tr>
                                </table>

                                <h3 class="section-title">المعلومات المالية</h3>
                                <table class="official-table">
                                    <tr><th width="25%">اسم البنك</th><td id="bankName"></td></tr>
                                    <tr><th>رقم الحساب البنكي</th><td id="bankAccount"></td></tr>
                                    <tr><th>شركات التأمين</th><td id="insuranceCompanies"></td></tr>
                                </table>

                                <h3 class="section-title">السجل الوظيفي</h3>
                                <table class="official-table" id="employmentHistory">
                                    <thead>
                                        <tr>
                                            <th>الفترة</th>
                                            <th>الوظيفة</th>
                                            <th>الدرجة الوظيفية</th>
                                            <th>القسم</th>
                                            <th>نمط العمل</th>
                                            <th>المجموعة</th>
                                            <th>فتره العمل</th>
                                            <th>جهاز البصمة</th>
                                            <th>الراتب</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>

                                <h3 class="section-title">المستندات الرسمية</h3>
                                <table class="official-table" id="officialDocuments">
                                    <thead>
                                        <tr>
                                            <th width="25%">نوع المستند</th>
                                            <th width="15%">رقم المستند</th>
                                            <th width="20%">تاريخ الإصدار</th>
                                            <th width="20%">تاريخ الانتهاء</th>
                                            <th width="20%">الحاله</th>
                                            <th width="20%">مرفق</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>

                                <div class="section-container">
                                    <h3 class="section-title">الشهادات</h3>
                                    <table class="official-table" id="certificates">
                                        <thead>
                                            <tr>
                                                <th width="30%">اسم الشهادة</th>
                                                <th width="25%">الجهة المانحة</th>
                                                <th width="20%">تاريخ الإصدار</th>
                                                <th width="25%">ملاحظات</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="exper-container">
                                    <h3 class="section-title">الخبرات العملية</h3>
                                    <table class="official-table" id="experiences">
                                        <thead>
                                            <tr>
                                                <th width="25%">المسمى الوظيفي</th>
                                                <th width="25%">الجهة</th>
                                                <th width="20%">تاريخ البدء</th>
                                                <th width="20%">تاريخ الانتهاء</th>
                                                <th width="10%">المدة</th>
                                                <th width="10%">الملف</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function printData() {
    try {
        const printWindow = window.open('', '_blank');
        const printContents = document.getElementById("datauser").innerHTML;
        
        const styles = `
            <style>
                @media print {
                    body { font-family: Arial, sans-serif; line-height: 1.5; color: #000; direction: rtl; text-align: right; font-size: 14pt; }
                    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    table, th, td { border: 1px solid #000 !important; }
                    th { background-color: #f2f2f2 !important; text-align: right; padding: 8px; font-weight: bold; }
                    td { padding: 8px; text-align: right; }
                    .section-title { font-weight: bold; font-size: 18px; margin: 20px 0 10px 0; border-bottom: 2px solid #000; padding-bottom: 5px; }
                    .no-print { display: none !important; }
                }
                @page { size: auto; margin: 10mm; }
            </style>
        `;
        
        printWindow.document.write(`
            <html dir="rtl">
                <head>
                    <title>تقرير الموظف</title>
                    ${styles}
                </head>
                <body>
                    ${printContents}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                                window.close();
                            }, 500);
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    } catch (error) {
        console.error('حدث خطأ أثناء الطباعة:', error);
        alert('حدث خطأ أثناء تحضير الطباعة. الرجاء المحاولة مرة أخرى.');
    }
}

$(document).ready(function(){

    function CreatePDFfromHTML(filename) {
        const element = document.getElementById("result-containr");
        html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
            const imgData = canvas.toDataURL('image/png', 1.0);
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210; 
            const pageHeight = 295; 
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            pdf.save(`${filename}.pdf`);
        });
    }

    $(document).on('click', '.download_pdf', function(){
        var file_name = $(this).val();
        CreatePDFfromHTML(file_name);
    });

    // إرسال الطلب لنفس الصفحة
    function get_filter_info(empId) {
        $.ajax({
            url: '?action=report-one-empyer', // المسار اصبح لنفس الصفحة
            type: 'POST',
            data: { id: empId },
            dataType: "json",
            beforeSend: function(){
                $('#preloading').show();
            }, 
            success: function(response) { 
                $('#preloading').hide();
                if(response.check) {
                    populateReport(response);
                    $('#result-containr').show();
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function() {
                $('#preloading').hide();
                toastr.error('حدث خطأ أثناء الاتصال بالخادم');
            }
        });
    }

    $('#filter-fm').on('submit', function(e){  
        e.preventDefault();
        var name = $('#employer').val();
        get_filter_info(name);
    });

    function populateReport(data) {
        const employee = data.employee;
        document.getElementById('fullName').textContent = `${employee.FirstName || ''} ${employee.SecondName || ''} ${employee.LastName || ''}`.trim();
        document.getElementById('userEmail').textContent = employee.UserEmail || 'غير متوفر';
        document.getElementById('phone').textContent = employee.Phone || 'غير متوفر'; 
        document.getElementById('otherPhone').textContent = employee.ohter_phone || 'غير متوفر';
        document.getElementById('address').textContent = employee.user_address || 'غير متوفر';
        document.getElementById('gender').textContent = employee.Sex == 1 ? 'ذكر' : (employee.Sex == 2 ? 'انثى' : 'غير محدد'); 
        document.getElementById('maritalStatus').textContent = getMaritalStatus(employee.marital_status);
        document.getElementById('employmenthealth').textContent = employee.HealthCondition || 'غير متوفر';
        
        document.getElementById('bankName').textContent = employee.user_bank_name || 'غير محدد';
        document.getElementById('bankAccount').textContent = employee.user_account_bank || 'غير محدد';
        document.getElementById('insuranceCompanies').textContent = data.instant_names.join(', ') || 'غير محدد';
        
        const employmentTable = document.getElementById('employmentHistory').getElementsByTagName('tbody')[0];
        employmentTable.innerHTML = '';
        
        if (data.struct && data.struct.length > 0) {
            data.struct.forEach(job => {
                const row = employmentTable.insertRow();
                row.innerHTML = `
                    <td>${job.new_s_date || ''} م إلى ${job.new_e_date || 'غير محدد'}</td>
                    <td>${job.jobtitle_name || 'غير محدد'}</td>
                    <td>${job.name_grade || 'غير محدد'}</td>
                    <td>${job.section_name || 'غير محدد'}</td>
                    <td>${job.name_n || 'غير محدد'}</td>
                    <td>${job.group_name || 'غير محدد'}</td>
                    <td>${data.shift_names.join(', ') || 'غير محدد'}</td>
                    <td>${data.finger_names.join(', ') || 'غير محدد'}</td>
                    <td>${job.Salary || 0} ${job.Currency || 'رس'}</td>
                `;
            });
        } else {
            employmentTable.innerHTML = '<tr><td colspan="9" class="text-center">لا توجد سجلات وظيفية</td></tr>';
        }
        
        const docsTable = document.getElementById('officialDocuments').getElementsByTagName('tbody')[0];
        docsTable.innerHTML = '';
        
        if (employee.Id_h) { docsTable.innerHTML += buildDocRow('الهوية الوطنية', employee.Id_h, employee.start_date_h, employee.end_date_h, employee.path_h); }
        if (employee.Id_license) { docsTable.innerHTML += buildDocRow('رخصة القيادة', employee.Id_license, employee.start_date_license, employee.end_date_license, employee.path_license); }
        if (employee.Id_passport) { docsTable.innerHTML += buildDocRow('جواز سفر', employee.Id_passport, employee.start_date_passport, employee.end_date_passport, employee.path_passport); }
        if (employee.Id_health) { docsTable.innerHTML += buildDocRow('الشهادة الصحية', employee.Id_health, employee.start_date_health, employee.end_date_health, employee.path_health); }
        
        const certTable = document.getElementById('certificates').getElementsByTagName('tbody')[0];
        certTable.innerHTML = '';
        if (data.certifcate && data.certifcate.length > 0) {
            data.certifcate.forEach(cert => {
                const row = certTable.insertRow();
                row.innerHTML = `
                    <td>${cert.Certifacte_name || ''}</td>
                    <td>${cert.Side || ''}</td>
                    <td>${cert.StartDate || ''}</td>
                    <td>${cert.FilePath ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            });
            document.getElementById('certificates').closest('.section-container').style.display = 'block';
        } else {
            document.getElementById('certificates').closest('.section-container').style.display = 'none';
        }
        
        const expTable = document.getElementById('experiences').getElementsByTagName('tbody')[0];
        expTable.innerHTML = '';
        if (data.experince && data.experince.length > 0) {
            data.experince.forEach(exp => {
                const row = expTable.insertRow();
                row.innerHTML = `
                    <td>${exp.TitleJob || ''}</td>
                    <td>${exp.side || ''}</td>
                    <td>${exp.StartDate || ''}</td>
                    <td>${exp.EndDate || 'حتى الآن'}</td>
                    <td>${calculateDuration(exp.StartDate, exp.EndDate)}</td>
                    <td>${exp.FilePath ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                `;
            });
            document.getElementById('experiences').closest('.exper-container').style.display = 'block';
        } else {
            document.getElementById('experiences').closest('.exper-container').style.display = 'none';
        }
    }
    
    function buildDocRow(name, number, start, end, path) {
        return `<tr>
                    <td>${name}</td>
                    <td>${number || 'غير مسجل'}</td>
                    <td>${start || 'غير محدد'}</td>
                    <td>${end || 'غير محدد'}</td>
                    <td>${isDocumentValid(end)}</td>
                    <td>${path ? '<i class="fas fa-check text-success"></i> مرفق' : 'غير مرفق'}</td>
                </tr>`;
    }

    function getMaritalStatus(status) {
        const statuses = { 1: 'متزوج', 2: 'أعزب', 3: 'أرمل', 4: 'مطلق' };
        return statuses[status] || 'غير محدد';
    }
    
    function isDocumentValid(endDate) {
        if (!endDate || endDate === '0000-00-00') return 'غير محدد';
        const today = new Date();
        const expiryDate = new Date(endDate);
        return expiryDate > today ? '<span class="text-success">ساري</span>' : '<span class="text-danger">منتهي</span>';
    }
    
    function calculateDuration(startDate, endDate) {
        if (!startDate || startDate === '0000-00-00') return 'غير محدد';
        const start = new Date(startDate);
        const end = (endDate && endDate !== '0000-00-00') ? new Date(endDate) : new Date();
        
        const years = end.getFullYear() - start.getFullYear();
        const months = end.getMonth() - start.getMonth();
        let duration = '';
        if (years > 0) duration += years + ' سنة ';
        if (months > 0) duration += months + ' شهر';
        return duration || 'أقل من شهر';
    }
});
</script>