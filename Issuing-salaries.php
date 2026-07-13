<?php
$appid  = 'HR';
$page_perm = ['إصدار الرواتب'];

$screen = 'إدارة الموارد البشرية';
$page_title = 'إصدار الرواتب';
$report_name = "إصدار الرواتب";

//setcookie('lasturl', 'reports-account-statement', time() + (86400 * 30), "/"); // 86400 = 1 day
include_once('inc/header.php');
$all_list_branches = [$branch];
$allowed_branches = $User->allBranches($User->branches);

// 
$query = "SELECT * FROM setting_account_salary";
$st = $connect_pdo->prepare($query);
$st->execute();
if($st->rowCount() > 0){
    $rows = $st->fetchAll();
    $firstRow_0 = $rows[0];
    $firstRow_1 = $rows[1];
    $firstRow_2 = $rows[2];
    $firstRow_3 = $rows[3];
    $firstRow_4 = $rows[4];
    $firstRow_5 = $rows[5];
}
// 
?>

<!-- المكتبات -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style> 
    /* تحسينات عامة للواجهة */
    body { background-color: #f4f6f9; }
    .card { box-shadow: 0 0 10px rgba(0,0,0,0.05); border-radius: 8px; border: none; }
    .card-header { border-bottom: 1px solid rgba(0,0,0,0.05); }
    
    /* تحسين Select2 و Selectpicker للغة العربية */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice { float: right !important; background-color: #007bff; border-color: #006fe6; color: #fff;}
    .select2-container--default .select2-selection--multiple .select2-selection__clear { float: left !important; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { float: left !important; margin-right: 5px !important; margin-left: -2px !important; color: #fff;}
    
    /* تحسين الجدول */
    .table.dataTable { margin-top: 15px !important; border-collapse: collapse !important; }
    .table.dataTable td, .table.dataTable th { vertical-align: middle; text-align: center; }
    .table thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6 !important; font-weight: 600; }
    #entries_tb tr td:last-child { font-weight: bold; color: #28a745; }
    
    /* أيقونات المساعدة */
    .popover-icon { cursor: pointer; font-size: 1.1em; margin-right: 5px; }
    .popover { text-align: right; }
    
    /* الطباعة */
    .salary-date-range { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
    .salary-date-range .date-input-wrap { flex: 1 1 145px; min-width: 145px; direction: ltr; }
    .salary-date-range .input-group-text { background: #f8f9fc; color: #4e73df; border-color: #d9e2f3; }
    .salary-date-range input[type="date"] { min-height: 42px; border-color: #d9e2f3; border-radius: .35rem 0 0 .35rem; text-align: right; }
    .date-separator { color: #6c757d; font-weight: 700; white-space: nowrap; }
    @media (max-width: 576px) { .salary-date-range { display:block; } .date-separator { display:block; text-align:center; margin:.4rem 0; } }

    @media print { 
        .table { width: 100% !important; }
        .table th { background-color: #f5f5f5 !important; -webkit-print-color-adjust: exact; } 
        section, div { background-color: #FFF !important; padding: 0px !important; margin: 0px !important; }
        a { color:#212529 !important; text-decoration: none !important; }
        .invoice .d-none { display:block !important; }
        .d-print-none { display: none !important; }
    }
</style>    

<div class="content-header page-nav">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-money-check-alt text-primary mr-2"></i> <?= $page_title ?></h1>
            </div>
            <div class="col-sm-6 text-left" id="addbutton"> 
                <!-- يتم إضافة أزرار الصرف هنا عبر الجافاسكربت -->
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid d-print-none">
        
        <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])):?>
        <div class="alert alert-success alert-dismissible shadow-sm" id="result-alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fas fa-check"></i>
            <?= $_SESSION['alert'] ?>
            <?php $_SESSION['alert'] =''; ?>
        </div>
        <?php endif;?>
    
        <form role="form" action="#" method="post" id="filter-fm">
            <!-- كارت الفلاتر الأساسية -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h3 class="card-title text-primary font-weight-bold"><i class="fas fa-search"></i> خيارات البحث والفلترة</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label font-weight-bold" for="date_range">الفترة (من - إلى)</label>
                                <div class="salary-date-range" dir="rtl">
                                    <div class="input-group date-input-wrap">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar-alt"></i></span></div>
                                        <input type="date" class="form-control" id="date_from" aria-label="تاريخ البداية">
                                    </div>
                                    <span class="date-separator">إلى</span>
                                    <div class="input-group date-input-wrap">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar-check"></i></span></div>
                                        <input type="date" class="form-control" id="date_to" aria-label="تاريخ النهاية">
                                    </div>
                                    <input type="hidden" name="date_range" id="date_range" value="">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="branchs_list" class="control-label font-weight-bold">الفرع</label>
                                <select class="selectpicker select_branch form-control" data-live-search="true" data-size="5" title="الكل" id="branchs_list" name="branchs_list" multiple="multiple">
                                    <?php
                                    foreach($allowed_branches as $id => $name){ 
                                        echo'<option value="'.$id.'" >'.$name.'</option>';
                                    }
                                    ?>  
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label font-weight-bold" for="advance_payment_method">وسيلة الدفع</label>
                                <select class="form-control payment_method advance_pay_info" name="advance_payment_method" id="advance_payment_method"></select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label font-weight-bold" for="advance_payment_treasur">الخزينة</label>
                                <select class="form-control treasur advance_pay_info" title="أختر" id="advance_payment_treasur" name="advance_payment_treasur"></select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- كارت إعدادات الحسابات -->
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0 font-weight-bold"><i class="fas fa-cogs"></i> إعدادات حسابات الرواتب (دليل الحسابات)</h3>
                    <div>
                        <?php if(empty($rows)){ ?>
                            <button type="button" class="btn btn-light text-info btn-sm font-weight-bold shadow-sm" id="save_setting"><i class="fas fa-save"></i> حفظ الإعدادات</button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-light text-info btn-sm font-weight-bold shadow-sm" id="update_setting"><i class="fas fa-edit"></i> تعديل الإعدادات</button>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <div class="row">
                        <!-- الحساب 0 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب مصروفات الرواتب
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← المصروفات ← مصروفات إدارية وعمومية ← مصروفات إدارية ← <strong>مرتبات وأجور</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_0" name="account_0" id="account_0" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_0['account_id'])){ ?><option value="<?=$firstRow_0['account_id']?>" data_slev="<?=$firstRow_0['account_name']?>" selected><?=$firstRow_0['account_name']?></option><?php } ?>
                            </select>
                        </div>

                        <!-- الحساب 1 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب مكافآت الموظفين
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← المصروفات ← مصروفات إدارية وعمومية ← مصروفات إدارية ← <strong>مكافآت الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_1" name="account_1" id="account_1" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_1['account_id'])){ ?><option value="<?=$firstRow_1['account_id']?>" data_slev="<?=$firstRow_1['account_name']?>" selected><?=$firstRow_1['account_name']?></option><?php } ?>    
                            </select>
                        </div>

                        <!-- الحساب 2 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب تعويضات الموظفين
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← المصروفات ← مصروفات إدارية وعمومية ← مصروفات إدارية ← <strong>تعويضات الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_2" name="account_2" id="account_2" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_2['account_id'])){ ?><option value="<?=$firstRow_2['account_id']?>" data_slev="<?=$firstRow_2['account_name']?>" selected><?=$firstRow_2['account_name']?></option><?php } ?>      
                            </select>
                        </div>

                        <!-- الحساب 3 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب سلف الموظفين
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← الأصول ← الأصول المتداولة ← المدينون ← العمال والموظفين ← سلف العمال والموظفين ← <strong>سلف الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_3" name="account_3" id="account_3" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_3['account_id'])){ ?><option value="<?=$firstRow_3['account_id']?>" data_slev="<?=$firstRow_3['account_name']?>" selected><?=$firstRow_3['account_name']?></option><?php } ?>      
                            </select>
                        </div>

                        <!-- الحساب 4 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب خصومات الموظفين
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← الخصوم ← الخصوم المتداولة ← مصروفات مستحقة ← <strong>خصومات الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_4" name="account_4" id="account_4" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_4['account_id'])){ ?><option value="<?=$firstRow_4['account_id']?>" data_slev="<?=$firstRow_4['account_name']?>" selected><?=$firstRow_4['account_name']?></option><?php } ?>      
                            </select>
                        </div>

                        <!-- الحساب 5 -->
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">
                                حساب مرتبات مستحقة
                                <a tabindex="0" class="text-info popover-icon" role="button" data-toggle="popover" data-trigger="focus" data-html="true" title="توضيح المسار" data-content='<div style="text-align:right;">دليل الحسابات ← الخصوم ← الخصوم المتداولة ← مصروفات مستحقة ← <strong>مرتبات الموظفين المستحقة</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success btn-block mt-2"><i class="fas fa-plus"></i> إضافة حساب </a> </div>'><i class="fas fa-question-circle"></i></a>
                            </label>
                            <select class="form-control select2 select_account_5" name="account_5" id="account_5" data-placeholder="اسم أو رقم الحساب">
                                <?php if(!empty($firstRow_5['account_id'])){ ?><option value="<?=$firstRow_5['account_id']?>" data_slev="<?=$firstRow_5['account_name']?>" selected><?=$firstRow_5['account_name']?></option><?php } ?>      
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- أزرار البحث -->
                <div class="card-footer bg-white text-left">
                    <button type="submit" class="btn btn-primary shadow-sm px-4"><i class="fas fa-search"></i> استعراض الرواتب</button>
                    <button type="reset" class="btn btn-secondary shadow-sm reset-filter px-4"><i class="fas fa-undo"></i> الغاء الفلترة</button>
                </div>
            </div>
            
            <div class="overlay" style="display:none"><i class="fas fa-3x fa-sync-alt fa-spin text-primary"></i></div>
        </form>
    </div> 
            
    <!-- قسم عرض النتائج -->
    <div class="container-fluid" id="result-containr" style="display:none">
        
        <!-- أزرار التصدير -->
        <div class="d-print-none text-left mb-3">
            <button type="button" class="btn btn-dark shadow-sm print_repo" onclick="printData()"><i class="fas fa-print"></i> طباعة</button>
            <button type="button" class="btn btn-danger shadow-sm download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> تصدير PDF</button>
            <button type="button" class="btn btn-success shadow-sm excel_data_table"><i class="fa fa-file-excel"></i> تصدير إكسل</button>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- ترويسة التقرير للطباعة -->
                <div class="text-center mb-4 pb-3 border-bottom">
                    <h1 class="h4 font-weight-bold d-none"><?=!empty($_SESSION['account']['title'])? $_SESSION['account']['title'] : $subdomain?></h1>
                    <h2 class="h3 font-weight-bold text-primary">مسير رواتب الموظفين</h2>
                    <div id="selected_client" class="font-weight-bold mt-2"></div>
                    <div id="selected_period" class="text-muted mt-1"></div>
                    <div id="selected_branch" class="text-muted"></div>
                    <div id="report_time" class="small text-muted mt-2"></div>
                    <div id="filter_note" class="small text-muted"></div>
                </div>

                <div class="table-responsive">
                    <table id="entries_tb" class="table table-striped table-bordered table-hover nowrap dtr-inline w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>رقم الموظف</th>
                                <th>اسم الموظف</th>
                                <th>الفرع</th>
                                <th>الراتب الأساسي</th>
                                <th>مستحق من الشهر</th>
                                <th>السلف</th>
                                <th>خصومات نقدية</th>
                                <th>خصم الغياب</th>
                                <th>التعويضات</th>
                                <th>المكافآت</th>
                                <th>ساعات العمل</th>
                                <th>الساعات الكاملة</th>
                                <th>ساعات الغياب</th>
                                <th>صافي الراتب (رس)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    
                    <!-- حقول مخفية للحسابات -->
                    <input type="hidden" id="sum_salary" name="sum_salary" readonly>
                    <input type="hidden" id="net_salary" name="net_salary" readonly>
                    <input type="hidden" id="sum_incentive" name="sum_incentive" readonly>
                    <input type="hidden" id="sum_benefit" name="sum_benefit" readonly>
                    <input type="hidden" id="sum_advance" name="sum_advance" readonly>
                    <input type="hidden" id="sum_dection" name="sum_dection" readonly>
                    <input type="hidden" id="currency" name="currency" readonly>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_once('inc/footer.php'); ?>

<!-- إعدادات الجافاسكربت -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script>
var branches = <?php echo json_encode($all_list_branches)?>;
var selected_branches = branches;

function printData(){
    var printContents = document.getElementById("result-containr").innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents; 
    window.location.reload(); // Reload to restore JS events after print
}

$(document).ready(function(){

    // تفعيل الـ Popover
    $('[data-toggle="popover"]').popover({ html: true });

    function CreatePDFfromHTML(file_name) {
        var top_left_margin = 15;
        html2canvas($("#result-containr .card-body")[0], {scale: 2}).then(function (canvas) {
            var imgData = canvas.toDataURL("image/jpeg", 1.0);
            var pdf = new jsPDF('p', 'pt', 'a4');
            var pageWidth = pdf.internal.pageSize.getWidth();
            var imgWidth = pageWidth - (top_left_margin * 2);
            var imgHeight = (canvas.height * imgWidth) / canvas.width;
            var position = top_left_margin;
            pdf.addImage(imgData, 'JPG', top_left_margin, position, imgWidth, imgHeight);
            pdf.save(file_name + ".pdf");
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
            $("#branchs_list").val('');
            $(".selectpicker").selectpicker("refresh");
            $('#addbutton').html('');
        });
    });

    function entriesData(is_date_search, date_range='', branchs=[]){
        var dataTable = $('#entries_tb').DataTable({
            "processing" : true,
            "serverSide" : true,
            "paging": true,
            "lengthChange": true,
            "pageLength": 200,
            "lengthMenu": [[200, 300, 400, 500, -1], [200, 300, 400, 500, 'الكل']],
            "searching": false,
            "order" : [],
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": true,
            "pagingType": "numbers",
            "aoColumns": [null, null, null, null, null, null, null, null, null, null, null, null, null, null],  
            language: { url: '/dist/js/dataTables.arabic.json' },
            "ajax": {
                url: "hr-app/index.php?action=Issuing-salaries",
                type: "POST",
                data: { is_date_search: is_date_search, date_range: date_range, branchs: branchs }
            },
            "dom": "<'row d-none'<'col-sm-12 col-md-4 'l><'col-sm-12 col-md-4'><'col-sm-12 col-md-4 text-left d-print-none'B>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                { extend: 'copyHtml5', footer: true, text: '<i class="fa fa-copy "></i>', titleAttr: 'نسح' },
                { extend: 'excelHtml5', footer: true, text: '<i class="fa fa-file-excel"></i>', titleAttr: 'إكسل' },
                { extend: 'print', footer: true, text: '<i class="fa fa-print"></i>', titleAttr: 'طباعة' }
            ],    
            drawCallback: function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
                if(settings.json.results_note.name){
                    $('#selected_client').html('<h5>الموظف '+$('#select2-client-container').text()+'</h5>');
                } else {
                    $('#selected_client').html('');
                }
                $('#report_time').html('وقت إصدار التقرير : '+settings.json.results_note.report_time);
                $('#selected_period').html('الفترة: ' + settings.json.results_note.selected_period);
                $('#filter_note').html(settings.json.results_note.filter_note);
                $('#selected_branch').html('');
                if(settings.json.results_note.selected_branch.length > 0){
                    $('#selected_branch').html('الفرع: ' + $('.select_branch .filter-option-inner-inner').html());
                }
                if(settings.json.data.length > 0) {
                    $('#addbutton').html('<button type="button" class="btn btn-success shadow-sm" id="issuing_salaries"><i class="fas fa-money-bill-wave"></i> صرف الرواتب المحددة</button>'); 
                    $('#sum_salary').val(settings.json.sum_salary); 
                    $('#net_salary').val(settings.json.net_salary); 
                    $('#sum_incentive').val(settings.json.sum_incentive); 
                    $('#sum_benefit').val(settings.json.sum_benefit); 
                    $('#sum_advance').val(settings.json.sum_advance); 
                    $('#sum_dection').val(settings.json.sum_dection); 
                    $('#currency').val(settings.json.currency); 
                } else {
                    $('#addbutton').html('');
                    $('#sum_salary, #net_salary, #sum_incentive, #sum_benefit, #sum_advance, #sum_dection').val(''); 
                }
                $('.overlay').hide();
                $('#result-containr').fadeIn();
            }
        });
    }

    function get_filter(input_name){
        var filter = [];
        $('select[name="'+input_name+'"] option:selected').each(function() { filter.push($(this).val()); });
        return filter;
    }
    
    function apply_filters(){
        var from = $('#date_from').val();
        var to = $('#date_to').val();
        var date_range = (from && to) ? from + ' - ' + to : '';
        $('#date_range').val(date_range);
        var branchs = get_filter('branchs_list');

        if (date_range) {
            var dates = date_range.split(' - ');
            if (dates.length === 2) {
                var startDate = new Date(dates[0]);
                var endDate = new Date(dates[1]);

                if (startDate <= endDate) {
                    if (startDate.getFullYear() === endDate.getFullYear() && startDate.getMonth() === endDate.getMonth()) {
                        var isFirstDay = startDate.getDate() === 1;
                        var lastDay = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0).getDate();
                        var isLastDay = endDate.getDate() === lastDay;

                        if (isFirstDay && isLastDay) {
                            $('.overlay').show();
                            $('#result-containr').hide();
                            if ($.fn.DataTable.isDataTable('#entries_tb')) { $('#entries_tb').DataTable().destroy(); }
                            entriesData('yes', date_range, branchs);
                        } else {
                            toastr.error('❌ يجب أن تبدأ من أول يوم وتنتهي في آخر يوم من نفس الشهر.');
                        }
                    } else {
                        toastr.error('❌ يجب أن تكون الفترة لنفس الشهر.');
                    }
                } else {
                    toastr.error('❌ تاريخ البداية أكبر من تاريخ النهاية.');
                }
            } else {
                toastr.error('❌ الصيغة غير صحيحة.');
            }
        } else {
            toastr.error('❌ حدد فترة زمنية لشهر واحد.');
        }
    }

    $('#filter-fm').on('submit', function(e){  
        e.preventDefault();
        apply_filters();
    });

    <?php if(!empty($auto_search)){ echo'apply_filters();'; } ?>

    // صرف الرواتب
    $(document).on('click', '#issuing_salaries', function(e){
        e.preventDefault();
        var tableData = [];

        $('#entries_tb tbody tr').each(function () {
            var rowData = [];
            $(this).find('td').each(function () { rowData.push($(this).text().trim()); });
            tableData.push(rowData);
        });

        var dataToSend = {
            rows: tableData, date_range: $('#date_range').val(), branchs: get_filter('branchs_list'),
            treasur: $('#advance_payment_treasur').val(), payment_methods: $('#advance_payment_method').val(),
            net_salary: $('#net_salary').val(), sum_salary: $('#sum_salary').val(), sum_incentive: $('#sum_incentive').val(),
            sum_benefit: $('#sum_benefit').val(), sum_advance: $('#sum_advance').val(), sum_dection: $('#sum_dection').val(),
            currency: $('#currency').val(),
            account_id_0: $(".select_account_0").val(), account_id_1: $(".select_account_1").val(),
            account_id_2: $(".select_account_2").val(), account_id_3: $(".select_account_3").val(),
            account_id_4: $(".select_account_4").val(), account_id_5: $(".select_account_5").val()
        };

        $.ajax({
            url: "salary-disbursement.php",
            method: "POST", 
            data: dataToSend,
            dataType: "json", 
            beforeSend: function(){ $('.overlay').show(); }, 
            success: function(data){
                if(data.result){
                    toastr.success(data.msg);
                    window.location.href = 'Issuing-salaries-view';
                } else {
                    toastr.error(data.msg);
                }
                $('.overlay').hide();
            },
            error: function(){
                $('.overlay').hide();
                toastr.error('حدث خطأ أثناء معالجة الطلب');
            }
        });
    });

    // دوال جلب البيانات
    payment_methods();
    function payment_methods(){
        $.ajax({  
            method:"POST", url: './sheard/payment-methods', data:{active:'1'}, dataType:"json",
            success:function(data) {  
                var html ='';
                $.each(data.data, function(k, v) {
                    if(data.data[k].id != $('.payment_method').val()){
                        html +='<option value="'+data.data[k].id+'" data-treasur="'+data.data[k].treasur+'">'+data.data[k].name+'</option>';
                    }
                });
                $('.payment_method').append(html);
            } 
       });
    }

    $(document).on('change', '.payment_method', function(){
        var treasur = $(this).find(':selected').attr('data-treasur');
        if($(this).hasClass('advance_pay_info')){ $('#advance_payment_treasur').val(treasur); }
    });

    $(document).on('change', '#branchs_list, #date_from, #date_to', function(){ $('#addbutton').html(''); });

    treasurList();
    function treasurList(){
        $.ajax({  
            method:"POST", url: './sheard/treasurs-menu', data:{active:0, actions:['out']}, dataType:"json",
            success:function(data) {  
                var html ='';
                $.each(data.data, function(k, v) { html +='<option value="'+data.data[k].id+'" >'+data.data[k].name+'</option>'; });
                $('#advance_payment_treasur, #treasur').html(html);
                if(data.user_treasur > 0){ $('#treasur, #advance_payment_treasur').val(data.user_treasur); }
            } 
       });
    }

    // تهيئة Select2 للحسابات
    var deptorsdArry = [];
    [0, 1, 2, 3, 4, 5].forEach(accountsList);

    function accountsList(row_id){
        $(".select_account_"+row_id).select2({
            dir: "rtl", // إضافة لدعم اللغة العربية والترتيب
            ajax: {
                url: './sheard/accounts-menu', dataType: 'json', delay: 250,
                data: function (params) {
                    return { q: params.term, s_disabled: 'n', s_chosed: deptorsdArry, page: params.page };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return { results: data.accounts, pagination: { more: (params.page) > 0 } };
                },
                cache: true
            },
            allowClear: false,
            placeholder: 'بحث عن حساب...',
            minimumInputLength: 1,
            templateResult: formatRepo,
            templateSelection: formatRepoSelection
        }).on('select2:select', function (e) {
            $(this).find("option:selected").attr("data_slev", e.params.data.name);
        });
    }

    function formatRepo (repo) {
        if (repo.loading) return repo.text;
        return $("<div class='select2-result-repository clearfix'><div class='select2-result-repository__path small text-muted'></div><div class='select2-result-repository__title font-weight-bold'></div></div>")
            .find(".select2-result-repository__path").text(repo.path).end()
            .find(".select2-result-repository__title").text(repo.name).end();
    }
    function formatRepoSelection (repo) { return repo.name || repo.text; }

    // حفظ وإعدادات الحسابات
    $(document).on('click', '#save_setting', function(){ save_setting('add'); });
    $(document).on('click', '#update_setting', function(){ save_setting('edit'); });

    function save_setting(name){
        var dataToSend = { name: name };
        for(var i=0; i<=5; i++){
            var opt = $(".select_account_"+i).find("option:selected");
            dataToSend["account_id_"+i] = opt.val();
            dataToSend["accountName_"+i] = opt.attr("data_slev");
        }

        $.ajax({
            url: "hr-app/index.php?action=save-setting-account-salary",
            method: "POST", data: dataToSend, dataType: "json", 
            beforeSend: function(){ $('.overlay').show(); },  
            success: function(data){
                if(data.result){
                    toastr.success(data.msg);
                    setTimeout(()=> window.location.href = 'Issuing-salaries', 1000);
                } else {
                    toastr.error(data.msg);
                }
                $('.overlay').hide();
            }
        });
    }
});
</script>