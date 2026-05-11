<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';

// =========================================================================
// 1. معالجة طلب AJAX لجلب بيانات الجدول (الرواتب)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'report-export-salarys') {
    ob_end_clean(); // مسح أي مخرجات سابقة لضمان إرسال JSON نقي
    header('Content-Type: application/json');

    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 200;

    // استلام الفلاتر
    $date_range = $_POST['date_range'] ?? '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $branchs = array_filter($_POST['branchs'] ?? []);
    $sections = array_filter($_POST['section'] ?? []);
    $jobtitles = array_filter($_POST['jobtitle'] ?? []);
    $shifts = array_filter($_POST['shift'] ?? []);
    $groups = array_filter($_POST['groub'] ?? []);

    // بناء الاستعلام الأساسي (بشكل آمن بدون جداول قد تكون غير موجودة)
    $query = "SELECT u.UserID, 
                     CONCAT_WS(' ', NULLIF(u.FirstName,''), NULLIF(u.SecondName,''), NULLIF(u.LastName,'')) AS full_name,
                     b.branch_name,
                     r.Salary
              FROM tblusers u
              LEFT JOIN branches b ON u.BranchID = b.branch_id
              LEFT JOIN tblremewal r ON u.lastversion = r.id
              WHERE u.isemp = 1 AND (u.resigned_or_dismissed IS NULL OR u.resigned_or_dismissed = 0) "; // الموظفين المستمرين فقط

    $params = [];

    // تطبيق فلتر الاسم
    if (!empty($name)) {
        $searchName = str_replace(' ', '%', $name);
        $query .= " AND CONCAT_WS(' ', NULLIF(u.FirstName,''), NULLIF(u.SecondName,''), NULLIF(u.LastName,'')) LIKE ? ";
        $params[] = "%$searchName%";
    }

    // دالة مساعدة للفلاتر المتعددة
    $addArrayFilter = function(&$q, &$p, $colName, $arr) {
        if (!empty($arr) && is_array($arr)) {
            $placeholders = implode(',', array_fill(0, count($arr), '?'));
            $q .= " AND $colName IN ($placeholders) ";
            foreach ($arr as $val) $p[] = $val;
        }
    };

    // تطبيق الفلاتر
    $addArrayFilter($query, $params, 'u.BranchID', $branchs);
    $addArrayFilter($query, $params, 'r.SectionID', $sections);
    $addArrayFilter($query, $params, 'r.jobtitleID', $jobtitles);
    $addArrayFilter($query, $params, 'r.shiftID', $shifts);
    $addArrayFilter($query, $params, 'r.GroupID', $groups);

    // حساب العدد الإجمالي
    $stmtCount = $connect_pdo->prepare($query);
    $stmtCount->execute($params);
    $recordsFiltered = $stmtCount->rowCount();

    // الترتيب والتقسيم
    $query .= " ORDER BY full_name ASC ";
    if ($length != -1) {
        $query .= " LIMIT $start, $length";
    }

    // جلب البيانات
    $stmt = $connect_pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تجهيز البيانات لـ DataTables (11 عمود حسب تصميمك)
    $data = [];
    foreach ($results as $row) {
        $base_salary = floatval($row['Salary'] ?? 0);
        
        // هنا يمكنك لاحقاً جلب السلف والخصومات من جداولها الخاصة، حالياً سنضعها 0
        $previous_debt = 0;
        $advances = 0;
        $deductions = 0;
        $compensations = 0;
        $bonuses = 0;
        
        // حساب الصافي
        $net_salary = $base_salary + $compensations + $bonuses - $deductions - $advances - $previous_debt;

        $data[] = [
            $row['full_name'] ?? 'بدون اسم',
            $row['branch_name'] ?? 'غير محدد',
            number_format($base_salary, 2),            // الراتب الأساسي
            number_format($previous_debt, 2),          // علية من الشهر السابق
            number_format($advances, 2),               // السلف
            number_format($deductions, 2),             // الخصومات
            number_format($compensations, 2),          // التعويضات
            number_format($bonuses, 2),                // المكافآت
            '0',                                       // عدد الساعات الافتراضي
            '0',                                       // عدد الساعات الفعلية
            number_format($net_salary, 2)              // صافي الراتب
        ];
    }

    // إرسال البيانات
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $recordsFiltered,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data,
        "results_note" => [
            "name" => !empty($name),
            "report_time" => date('Y-m-d H:i:s'),
            "selected_period" => $date_range,
            "selected_branch" => !empty($branchs),
            "filter_note" => "تقرير الرواتب" . (!empty($name) ? " - للموظف: $name" : "")
        ]
    ]);
    exit;
}
// =========================================================================

// =========================================================================
// 2. واجهة المستخدم HTML
// =========================================================================
$User = new User($connect_pdo);
$User->loadFromSession();

$screen = 'التقارير';
$report_name = 'رواتب الموظفين';
$page_title = $report_name;

include_once('inc/header.php');

$branch = $_SESSION['branch'] ?? $_SESSION['BranchID'] ?? null;
$all_list_branches = [$branch];
$allowed_branches = $User->allBranches($User->branches);
?>

<style> 
.table.dataTable{ margin-top: 0px !important; }
.table.dataTable td{ vertical-align: middle; }
.table thead th{ border-bottom: none !important; }
.filter-advance{ display:none }
.select2-container--default .select2-selection--multiple .select2-selection__choice { float: right !important; }
.select2-container--default .select2-selection--multiple .select2-selection__clear { float: left !important; }
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove { float: left !important; margin-right: 5px !important; margin-left: -2px !important; }
.bg-trans{ background-color: #f4f6f9; }
#entries_tb tr td:last-child { font-weight: bold; color: #28a745;} /* تلوين صافي الراتب بالأخضر */
</style>    

<div class="content-header page-nav" >
    <div class="container-fluid ">
        <div class="row ">
            <div class="col-7">
                <span class="page-title"><?=$report_name?></span>
            </div>
            <div class="col-5 text-left"></div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid d-print-none">
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
            <div class="alert alert-success alert-dismissible" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-check"></i>
                <?=$_SESSION['alert']?>
                <?php $_SESSION['alert'] ='';?>
            </div>
        <?php endif;?>

        <div class="row" id="filter-area">
            <div class="col-md-12">
                <div class="invoice mb-3" >
                    <div class="card-header bg-gry">
                        <h3 class="card-title">بحث</h3>
                    </div>
                    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">
                        <div class="card-body card-body pt-0 pb-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
                                        <input type="text" name="date_range" class="form-control input-date-range" placeholder="من - الى" id="date_range" autocomplete="off" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emp_name" class="col-form-label">اسم الموظف</label>
                                        <input type="text" class="form-control" id="emp_name" name="emp_name" placeholder="" >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="branchs_list" class="col-form-label logindata ">الفرع</label>
                                        <select class="selectpicker select_branch" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list" multiple="multiple" >
                                            <?php foreach($allowed_branches as $id => $name){ echo'<option value="'.$id.'" >'.$name.'</option>'; } ?>   
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_section" class="col-form-label logindata ">القسم</label>
                                        <select class="form-control logindata selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد باي قسم" id="user_section" name="user_section" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_jobtitle" class="col-form-label logindata ">المسمى الوظيفي</label>
                                        <select class="form-control logindata selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_grade" class="col-form-label logindata ">الدرجة الوظيفية</label>
                                        <select class="form-control logindata selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_shift" class="col-form-label logindata ">فترات العمل</label>
                                        <select class="form-control logindata selectpicker" data-live-search="true" title="حدد فترات العمل" id="user_shift" name="user_shift[]" multiple="multiple" data-size="5"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance ">
                                    <div class="form-group">
                                        <label for="user_groub" class="col-form-label logindata ">المجموعه الوظيفية</label>
                                        <select class="form-control logindata selectpicker" data-live-search="true" title="حدد المجموعه" id="user_groub" name="user_groub[]" multiple="multiple" data-size="5"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-1">
                            <div class="text-left">
                                <button type="button" class="btn show-advance float-right" data-dismiss=""><i class="fa fa-sliders-h"></i> <strong>خيارات اضافية</strong></button>
                                <button type="reset" class="btn btn-default reset-filter" data-dismiss=""> الغاء الفلترة</button>
                                <button type="submit" class="btn btn-info" name="" ><i class="fas fa-search"></i> بحث</button>
                            </div>
                        </div>
                    </form>
                    <div class="overlay" style="display:none" id="preloading"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
                </div> 
            </div>
        </div>

        <div class="container-fluid" id="result-containr" style="display:none">
            <div class="d-print-none text-left mb-2">
                <button type="button" class="btn btn-default print_repo" onclick="printData()"><i class="fas fa-print"></i> طباعة</button>
                <button type="button" class="btn btn-primary download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> PDF</button>
                <button type="button" class="btn btn-warning excel_data_table"><i class="fa fa-file-excel"></i> إكسل</button>
            </div>
            
            <style>
            @media print { 
                .table { width: 100% !important; }
                .table th { background-color: #f5f5f5 !important; } 
                section,div { background-color: #FFF !important; padding: 0px !important; margin: 0px !important; }
                a { color:#212529 !important; text-decoration: none !important; }
                .invoice .d-none{ display:block !important; }
            }
            </style>
                
            <div class="row">
                <div class="col-md-12 text-center mb-2" >
                    <div class="invoice p-3">
                        <h1 class="h5 d-none"><?=!empty($_SESSION['account']['title'])? $_SESSION['account']['title'] : $subdomain?></h1>
                        <h1 class="h5">رواتب الموظفين</h1>
                        <div id="selected_client" class="bold"></div>
                        <div id="selected_period" class="m-0" ></div>
                        <div id="selected_branch" class="m-0" ></div>
                        <p id="report_time"></p>
                        <div id="filter_note" class="m-0" ></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="entries_tb" class="table dataTable table-bordered table-hover nowrap dtr-inline collapsed display " width="100%" style="background: white;">
                            <thead>
                                <tr>
                                    <th style="text-align: center; vertical-align: middle;" >اسم الوظف</th>
                                    <th style="text-align: center; vertical-align: middle;" >الفرع</th>
                                    <th style="text-align: center; vertical-align: middle;" >الراتب الأساسي</th>
                                    <th style="text-align: center; vertical-align: middle;" >علية من السابق</th>
                                    <th style="text-align: center; vertical-align: middle;" >السلف</th>
                                    <th style="text-align: center; vertical-align: middle;" >الخصومات</th>
                                    <th style="text-align: center; vertical-align: middle;" >التعويضات</th>
                                    <th style="text-align: center; vertical-align: middle;" >المكافاّت</th>
                                    <th style="text-align: center; vertical-align: middle;" >ساعات العمل</th>
                                    <th style="text-align: center; vertical-align: middle;" >الساعات الفعلية</th>
                                    <th style="text-align: center; vertical-align: middle;" >صافي الراتب<br>(رس.)</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <br><br>
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
var branches = <?php echo json_encode($all_list_branches)?>;
var selected_branches = branches;

function printData(){
    var printContents = document.getElementById("result-containr").innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents; 
    window.location.reload();
}

$(document).ready(function(){
    function CreatePDFfromHTML(file_name) {
        var HTML_Width = $("#result-containr").width();
        var HTML_Height = $("#result-containr").height();
        var top_left_margin = 15;
        var PDF_Width = HTML_Width + (top_left_margin * 2);
        var PDF_Height = (PDF_Width * 1.5) + (top_left_margin * 2);
        var canvas_image_width = HTML_Width;
        var canvas_image_height = HTML_Height;
        var totalPDFPages = Math.ceil(HTML_Height / PDF_Height) - 1;

        html2canvas($("#result-containr .row")[0]).then(function (canvas) {
            var imgData = canvas.toDataURL("image/jpeg", 1.0);
            var pdf = new jsPDF('p', 'pt', [PDF_Width, PDF_Height]);
            pdf.addImage(imgData, 'JPG', top_left_margin, top_left_margin, canvas_image_width, canvas_image_height);
            for (var i = 1; i <= totalPDFPages; i++) { 
                pdf.addPage(PDF_Width, PDF_Height);
                pdf.addImage(imgData, 'JPG', top_left_margin, -(PDF_Height*i)+(top_left_margin*4),canvas_image_width,canvas_image_height);
            }
            pdf.save(file_name+".pdf");
        });
    } 
  
    $(document).on('click', '.download_pdf', function(){
        var file_name = $(this).val();
        CreatePDFfromHTML(file_name);
    });

    $(document).on('click', '.excel_data_table', function(){
        $('.buttons-excel').trigger('click');
    });

    $(document).on('click', '.reset-filter', function(){
        $('#filter-fm').each(function() {
            $("input").val('');
            $(".selectpicker").val('');
            $(".selectpicker").selectpicker("refresh");
        });
    });

    function entriesData(is_date_search, date_range, section, jobtitle, grade, shift, branchs, groub, name){
        var dataTable = $('#entries_tb').DataTable({
            "processing" : true,
            "serverSide" : true,
            "paging": true,
            "lengthChange": true,
            "pageLength": 200,
            "lengthMenu": [[200, 300, 400, 500,-1 ], [200, 300, 400, 500,'All']],
            "searching": false,
            "ordering": true,
            "order": [],
            "info": false,
            "autoWidth": false,
            "responsive": true,
            "pagingType": "numbers",
            "aoColumns":[null, null, null, null, null, null, null, null, null, null, null],  
            language: { url:'dist/js/dataTables.arabic.json' },
            "ajax" : {
                url:"?action=report-export-salarys", // تم تعديل المسار ليكون لنفس الصفحة
                type:"POST",
                data:{
                    is_date_search: is_date_search,
                    date_range: date_range,
                    section: section,
                    jobtitle: jobtitle,
                    grade: grade,
                    shift: shift,
                    branchs: branchs,
                    groub: groub,
                    name: name
                }
            },
            "dom": "<'row d-none'<'col-sm-12 col-md-4 'l><'col-sm-12 col-md-4'><'col-sm-12 col-md-4 text-left d-print-none'B>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'excelHtml5', footer: true, text: '<i class="fa fa-file-excel"></i>', titleAttr: 'إكسل' }
            ],    
            drawCallback: function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
                
                if(settings.json && settings.json.results_note){
                    if(settings.json.results_note.name){
                        $('#selected_client').html('<h5>الموظف '+$('#emp_name').val()+'</h5>');
                    } else {
                        $('#selected_client').html('');
                    }
                    $('#report_time').html('وقت إصدار التقرير : '+settings.json.results_note.report_time);
                    $('#selected_period').html('الفترة: ' + settings.json.results_note.selected_period);
                    $('#filter_note').html(settings.json.results_note.filter_note);
                    
                    if(settings.json.results_note.selected_branch){
                        $('#selected_branch').html($('.select_branch .filter-option-inner-inner').html());
                    } else {
                        $('#selected_branch').html('');
                    }
                }
                $('#preloading').hide();
                $('#result-containr').show();
            }
        });
    }

    function get_filter(input_name) {
        var filter = [];
        $('select[name="'+input_name+'"] option:selected').each(function() {
            filter.push($(this).val());
        });
        return filter;
    }
    
    function apply_filters(){
        var date_range = $('#date_range').val();
        var name = $('#emp_name').val();
        var branchs = get_filter('branchs_list');
        var section = get_filter('user_section');
        var jobtitle = get_filter('user_jobtitle');
        var grade = get_filter('user_grade');
        var shift = get_filter('user_shift');
        var groub = get_filter('user_groub');

        if (date_range) {
            var dates = date_range.split(' - ');
            if (dates.length === 2) {
                var startDate = new Date(dates[0]);
                var endDate = new Date(dates[1]);

                if (startDate <= endDate) {
                    var monthDiff = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth());
                    if (monthDiff >= 0) { // تم التعديل ليقبل الفترات بدلاً من التقييد بشهر واحد فقط
                        $('#preloading').show();
                        $('#result-containr').hide();
                        if ($.fn.dataTable.isDataTable('#entries_tb')) {
                            $('#entries_tb').DataTable().destroy();
                        }
                        entriesData('yes', date_range, section, jobtitle, grade, shift, branchs, groub, name);
                    }
                } else {
                    toastr.error('❌ تاريخ البداية أكبر من تاريخ النهاية.');
                }
            } else {
                toastr.error('❌ الصيغة غير صحيحة للتاريخ.');
            }
        } else {
            toastr.error('يرجى تحديد الفترة');
        }
    }

    $('#filter-fm').on('submit', function(e){  
        e.preventDefault();
        apply_filters();
    });

    $(document).on('click', '.show-advance', function(){
        $('.filter-advance').toggle();
    }); 

    get_filter_info();
    
    function get_filter_info() {
        $.ajax({
            url: 'hr-app/index.php?action=allUserinfo_Search',
            type: 'POST',
            data: { value: 1 },
            dataType: "json",
            success: function(response) { 
                populateSelect('#user_section', response.section);
                populateSelect('#user_jobtitle', response.jobtitle);
                populateSelect('#user_grade', response.JobGrade);
                populateSelect('#user_shift', response.Shift);
                populateSelect('#user_groub', response.groub_list);
            }
        });
    }

    function populateSelect(selectId, items) {
        var select = $(selectId);
        select.empty(); 
        if (items && items.length > 0) {        
            $.each(items, function(index, item) {
                select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
            });
        } 
        select.selectpicker('refresh');
    }
});
</script>