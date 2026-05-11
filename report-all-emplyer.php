<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Start output buffering to catch any unwanted output early

// Define the missing function directly here or in inc/functions.php
function handleReportAllEmployer($pdo, $User, $employee_status_map) {
    // 1. Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 200;

    // 2. Get Search Filters
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $status = $_POST['status'] ?? '';
    
    // Arrays for advanced filtering
    $branchs = array_filter($_POST['branchs'] ?? []);
    $groups = array_filter($_POST['groub'] ?? []);
    $sections = array_filter($_POST['section'] ?? []); 
    $jobtitles = array_filter($_POST['jobtitle'] ?? []);
    $grades = array_filter($_POST['grade'] ?? []);
    $shifts = array_filter($_POST['shift'] ?? []);

    // 3. Build the Base Query with the correct JOINs to tblremewal (Contracts)
    // NOTE: Replace s.name and jt.name with the actual column names from your tblsection and tbljobtitle if they differ.
    $query = "SELECT u.UserID, 
                     CONCAT_WS(' ', NULLIF(u.FirstName,''), NULLIF(u.SecondName,''), NULLIF(u.LastName,'')) as full_employee_name,
                     u.resigned_or_dismissed, u.UserGroupID,
                     b.branch_name,
                     r.Salary, r.new_s_date, r.new_e_date,
                     s.name AS section_name, 
                     jt.name AS job_title_name
              FROM tblusers u
              LEFT JOIN branches b ON u.BranchID = b.branch_id
              LEFT JOIN tblremewal r ON u.lastversion = r.id -- Link to active contract
              LEFT JOIN tblsection s ON r.SectionID = s.id   -- Link to Section
              LEFT JOIN tbljobtitle jt ON r.jobtitleID = jt.id -- Link to Job Title
              WHERE u.isemp = 1 "; 

    $params = [];

    // 4. Apply Filters dynamically
    if (!empty($name)) {
        $searchName = str_replace(' ', '%', $name);
        $query .= " AND CONCAT_WS(' ', NULLIF(u.FirstName,''), NULLIF(u.SecondName,''), NULLIF(u.LastName,'')) LIKE ? ";
        $params[] = "%$searchName%";
    }

    if (!empty($status)) {
        if ($status == '1') {
            $query .= " AND (u.resigned_or_dismissed IS NULL OR u.resigned_or_dismissed = 0) ";
        } else {
            $db_status_value = null;
            switch ($status) {
                case '2': $db_status_value = 1; break; // موقف
                case '3': $db_status_value = 2; break; // مفصول
                case '4': $db_status_value = 3; break; // مستقيل
                case '5': $db_status_value = 4; break; // انتهاء عقد
            }
            if ($db_status_value !== null) {
                $query .= " AND u.resigned_or_dismissed = ? ";
                $params[] = $db_status_value;
            }
        }
    }

    // Helper to add array filters using IN clause
    $addArrayFilter = function(&$q, &$p, $colName, $arr) {
        if (!empty($arr) && is_array($arr)) {
            $placeholders = implode(',', array_fill(0, count($arr), '?'));
            $q .= " AND $colName IN ($placeholders) ";
            foreach ($arr as $val) $p[] = $val;
        }
    };

    // Apply multiple selection filters
    $addArrayFilter($query, $params, 'u.BranchID', $branchs);
    $addArrayFilter($query, $params, 'u.UserGroupID', $groups);
    $addArrayFilter($query, $params, 'r.SectionID', $sections);
    $addArrayFilter($query, $params, 'r.jobtitleID', $jobtitles);
    $addArrayFilter($query, $params, 'r.GradeID', $grades);
    $addArrayFilter($query, $params, 'r.shiftID', $shifts);

    // 5. Get Total Records (Filtered)
    $stmtCount = $pdo->prepare($query);
    $stmtCount->execute($params);
    $recordsFiltered = $stmtCount->rowCount();

    // 6. Apply Pagination
    $query .= " ORDER BY full_employee_name ASC";
    if ($length != -1) {
        $query .= " LIMIT $start, $length";
    }

    // 7. Fetch Data
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Format Data for DataTables Columns
    $data = [];
    foreach ($results as $row) {
        // Clean up empty dates (0000-00-00)
        $start_date = (!empty($row['new_s_date']) && $row['new_s_date'] !== '0000-00-00') ? $row['new_s_date'] : 'غير محدد';
        $end_date = (!empty($row['new_e_date']) && $row['new_e_date'] !== '0000-00-00') ? $row['new_e_date'] : 'غير محدد';

        $data[] = [
            $row['full_employee_name'] ?? 'بدون اسم',                  // Column 1: Employee Name
            $row['branch_name'] ?? 'غير محدد',                         // Column 2: Branch
            $row['job_title_name'] ?? 'غير محدد',                      // Column 3: Job Title
            $row['section_name'] ?? 'غير محدد',                        // Column 4: Section
            $start_date,                                               // Column 5: Contract Start
            $end_date,                                                 // Column 6: Contract End
            number_format((float)($row['Salary'] ?? 0), 2)             // Column 7: Salary 
        ];
    }

    // 9. Generate notes for the frontend
    $filter_note_text = "تقرير بجميع الموظفين"; 
    if (!empty($name)) { $filter_note_text .= " - بحث باسم: $name"; }
    if (!empty($status)) {
        $status_name = $employee_status_map[$status] ?? 'غير محدد';
        $filter_note_text .= " - حالة الموظف: $status_name";
    }

    $results_note = [
        'name' => $name ? true : false,
        'report_time' => date('Y-m-d H:i:s'),
        'selected_period' => '', 
        'selected_branch' => !empty($branchs),
        'filter_note' => $filter_note_text
    ];

    // 10. Output JSON Response
    ob_end_clean(); 
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $recordsFiltered, 
        "recordsFiltered" => $recordsFiltered,
        "data" => $data,
        "results_note" => $results_note
    ]);
    exit; 
}


// Ensure session is started for User class
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httplike', '1'); 
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';
require_once __DIR__ . '/inc/AuditLog.php'; 
require_once __DIR__ . '/inc/functions.php'; 

$User = new User($connect_pdo);
$User->loadFromSession();

$screen = 'التقارير';
$report_name = 'بيانات الموظفين';
$page_title = $report_name;

$branch = $_SESSION['branch'] ?? $_SESSION['BranchID'] ?? null; 
$all_list_branches = [$branch]; 
$allowed_branches = $User->allBranches($User->branches);

$employee_status_map = [
    '1' => 'مستمر',    
    '2' => 'موقف',
    '3' => 'مفصول',
    '4' => 'مستقيل',
    '5' => 'انتهاء عقد'
];

// --- AJAX REQUEST HANDLING ---
if (isset($_GET['action']) && $_GET['action'] == 'report-all-emplyer') {
    handleReportAllEmployer($connect_pdo, $User, $employee_status_map);
}

// --- NORMAL PAGE RENDERING STARTS HERE ---
include_once('inc/header.php');
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
#entries_tb tr td:last-child { font-weight: bold;}
</style>

<div class="content-header page-nav" >
    <div class="container-fluid ">
        <div class="row ">
            <div class="col-7">
                <span class="page-title"><?=$report_name?></span>
            </div>
            <div class="col-5 text-left">
            </div>
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
                <div class="invoice mb-3">
                    <div class="card-header bg-gry">
                        <h3 class="card-title">بحث</h3>
                    </div>
                    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">
                        <div class="card-body card-body pt-0 pb-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emp_name" class="col-form-label">اسم الموظف</label>
                                        <input type="text" class="form-control" id="emp_name" name="emp_name" placeholder="" >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="user_status" class="col-form-label logindata ">حالة الموظف</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد حالة الموظف" id="user_status" name="user_status">
                                            <option value="1">مستمر</option>
                                            <option value="2">موقف</option>
                                            <option value="3">مفصول</option>
                                            <option value="4">مستقيل</option>
                                            <option value="5">انتهاء عقد</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="branchs_list" class="col-form-label logindata ">الفرع</label>
                                        <select class="selectpicker select_branch" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list" multiple="multiple">
                                            <?php foreach($allowed_branches as $id => $name){ echo'<option value="'.$id.'" >'.$name.'</option>'; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_section" class="col-form-label logindata ">القسم</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد باي قسم" id="user_section" name="user_section" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_jobtitle" class="col-form-label logindata ">المسمى الوظيفي</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_grade" class="col-form-label logindata ">الدرجة الوظيفية</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" data-container="body" data-size="5" data-width="100%" title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" multiple="multiple"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance">
                                    <div class="form-group">
                                        <label for="user_shift" class="col-form-label logindata ">فترات العمل</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" title="حدد فترات العمل " id="user_shift" name="user_shift[]" multiple="multiple" data-size="5"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 filter-advance ">
                                    <div class="form-group">
                                        <label for="user_groub" class="col-form-label logindata ">المجموعه الوظيفية</label>
                                        <select class="form-control logindata selectpicker " data-live-search="true" title="حدد المجموعه " id="user_groub" name="user_groub[]" multiple="multiple" data-size="5"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-1">
                            <div class="text-left">
                                <button type="button" class="btn show-advance float-right " data-dismiss=""><i class="fa fa-sliders-h"></i> <strong>خيارات اضافية</strong></button>
                                <button type="reset" class="btn btn-default reset-filter" data-dismiss=""> الغاء الفلترة</button>
                                <button type="submit" class="btn btn-info" name="" ><i class="fas fa-search"></i> بحث</button>
                            </div>
                        </div>
                    </form>
                    <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
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
                a{ color:#212529 !important; text-decoration: none !important; }
                .invoice .d-none{ display:block !important; }
            }
            </style>

            <div class="row">
                <div class="col-md-12 text-center mb-2" >
                    <div class="invoice p-3">
                        <h1 class="h5 d-none"><?=!empty($_SESSION['account']['title'])? $_SESSION['account']['title'] : $subdomain?></h1>
                        <h1 class="h5">بيانات الموظفين</h1>
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
                                    <th style="text-align: center; vertical-align: middle;">اسم الوظف</th>
                                    <th style="text-align: center; vertical-align: middle;">الفرع</th>
                                    <th style="text-align: center; vertical-align: middle;">المسمى الوظيفي</th>
                                    <th style="text-align: center; vertical-align: middle;">القسم</th>
                                    <th style="text-align: center; vertical-align: middle;">تاريخ بداية العقد</th>
                                    <th style="text-align: center; vertical-align: middle;">تاريخ انتهاء العقد</th>
                                    <th style="text-align: center; vertical-align: middle;">الراتب<br>(رس.)</th>
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
    window.location.reload(); // Reload to restore event listeners after print hack
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

    function entriesData(is_date_search, status, section, jobtitle, grade, shift, branchs, groub, name){
        var dataTable = $('#entries_tb').DataTable({
            "processing" : true,
            "serverSide" : true,
            "paging": true,
            "lengthChange": true,
            "pageLength": 200,
            "lengthMenu": [
                [200, 300, 400, 500, -1 ],
                [200, 300, 400, 500, 'All'],
            ],
            "searching": false,
            "ordering": true,
            "order": [],
            "info": false,
            "autoWidth": false,
            "responsive": true,
            "pagingType": "numbers",
            "aoColumns":[ null, null, null, null, null, null, null ],
            language: { url:'dist/js/dataTables.arabic.json' },
            "ajax" : {
                url:"?action=report-all-emplyer",
                type:"POST",
                data:{
                    is_date_search:is_date_search,
                    status:status,
                    section: section, 
                    jobtitle: jobtitle, 
                    grade: grade, 
                    shift: shift, 
                    branchs: branchs,
                    groub: groub,
                    name:name
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
                    $('#selected_period').html(settings.json.results_note.selected_period);
                    $('#filter_note').html(settings.json.results_note.filter_note);
                    $('#selected_branch').html('');
                    if(settings.json.results_note.selected_branch){
                        $('#selected_branch').html($('.select_branch .filter-option-inner-inner').html());
                    }
                }
                $('#preloading').hide();
                $('#result-containr').show();
            },
        });
    };

    function get_filter(input_name) {
        var filter = [];
        $('select[name="'+input_name+'"] option:selected').each(function() {
            filter.push($(this).val());
        });
        return filter;
    }

    function apply_filters(){
        $('#preloading').show();
        $('#result-containr').hide();
        var name = $('#emp_name').val();
        var branchs = get_filter('branchs_list');
        var section = get_filter('user_section'); // الآن يعمل
        var jobtitle = get_filter('user_jobtitle'); // الآن يعمل
        var grade = get_filter('user_grade'); // الآن يعمل
        var shift = get_filter('user_shift'); // الآن يعمل
        var groub = get_filter('user_groub');
        var status = $('#user_status').val();

        if ($.fn.dataTable.isDataTable('#entries_tb')) {
            $('#entries_tb').DataTable().destroy();
        }
        entriesData('yes', status, section, jobtitle, grade, shift, branchs, groub, name); 
    }

    $('#filter-fm').on('submit', function(e){
        e.preventDefault();
        apply_filters();
    });

    <?php if(!empty($auto_search)){ echo'apply_filters();'; } ?>

    $(document).on('click', '.show-advance', function(){
        $('.filter-advance').toggle();
    });

    get_filter_info();
    
    function get_filter_info() {
        $.ajax({
            url: 'hr-app/index.php?action=allUserinfo_Search', 
            type: 'POST',
            data: { value: 1 },
            dataType:"json",
            beforeSend:function(){ $('#preloading').show(); },
            success: function(response) {
                populateSelect('#user_section', response.section);
                populateSelect('#user_jobtitle', response.jobtitle);
                populateSelect('#user_grade', response.JobGrade); 
                populateSelect('#user_shift', response.Shift); 
                populateSelect('#user_groub', response.groub_list);
                $('#preloading').hide();
            },
            error: function(xhr, status, error) {
                console.error("Error fetching filter info:", error);
                toastr.error('حدث خطأ أثناء جلب البيانات الأساسية للفلترة');
                $('#preloading').hide();
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