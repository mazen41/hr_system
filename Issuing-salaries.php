 <?php
 $appid  = 'HR';
$page_perm=['إصدار الرواتب'];

 $screen = 'إدارة الموارد البشرية';
$page_title = 'إصدار الرواتب';
$report_name="إصدار الرواتب";

//setcookie('lasturl', 'reports-account-statement', time() + (86400 * 30), "/"); // 86400 = 1 day
 include_once('inc/header.php');
$all_list_branches = [$branch];
$allowed_branches = $User->allBranches($User->branches);

// 
	$query = "SELECT * FROM  setting_account_salary";

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

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <script type="text/javascript" src="plugins/jspdf/jspdf.min.js"></script>
<script type="text/javascript" src="plugins/jspdf/html2canvas.js"></script>

<style>	
.table.dataTable{
	margin-top: 0px !important;
}
.table.dataTable  td{
	vertical-align: middle;
}
.table thead th{
	border-bottom: none !important; 
}
.filter-advance{
	display:none
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    float: right !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__clear {
    float: left !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    float: left !important;
    margin-right: 5px !important;
    margin-left: -2px !important;
}
.bg-trans{
    background-color: #f4f6f9;
}
//#entries_tb tr td:nth-child(3) { width: 80px; max-width: 80px; white-space: break-spaces;}
#entries_tb tr td:last-child { font-weight: bold;}
button.btn.btn-sm.btn-light {
    padding: 0px 10px;
    color: white;
    font-weight: bold;
    background-color: #448ce0;
}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title"><?=$page_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left" id="addbutton">	
			
			<!--<button type="button" class="btn btn-default "  id="entry-search-bt"><i class="fas fa-search"></i><span class="d-none d-sm-inline"> بحث</span></button>
			
			<button type="button" class="btn btn-default "  id="entry-search-bt"><i class="fas fa-print"></i><span class="d-none d-sm-inline"> طباعة</span></button>
			
			<button type="button" class="btn btn-default "  id="entry-search-bt"><i class="fas fa-external-link-alt"></i><span class="d-none d-sm-inline"> تصدير</span></button>-->
			
			
			
			
			
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
	

	
		
    <div class="row" id="filter-area" style="display:none_">
        <div class="col-md-12">
        <div class="invoice mb-3" id="filter-area" style="display:none_">
       <div class="card-header bg-gry">
                   <h3 class="card-title">بحث</h3>
               </div>
       <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">

           
               <div class="card-body card-body pt-0 pb-0">
               <div class="row">
               
                                <div class="col-md-3">
                                    <div class="form-group">
                  <label class="control-label" for="date_range">الفترة (من - الى)</label>
                  <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
                </div>
            </div>

               
                <div class="col-md-3">
               <div class="form-group">
               <label for="branchs_list" class="control-label">الفرع</label>

                              <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list"  multiple="multiple" >
                              <?php
                                   
                                       foreach($allowed_branches as $id => $name){	
                                           echo'<option value="'.$id.'" >'.$name.'</option>';
                                       }

                                   ?>	
                           </select>
               </div>
               </div>
                                    <div class="form-group col-md-3 advance_pay_info"  style="display:none_">
							<label class="control-label" for="advance_payment_method ">وسيلة الدفع</label>
							<select class="form-control payment_method advance_pay_info" name="advance_payment_method" id="advance_payment_method">
							 
							</select>
							
						</div>


                                   <div class="form-group col-md-3 advance_pay_info"  style="display:none_">
							<label class="control-label" for="advance_payment_treasur">الخزينة</label>
                            <select class="form-control treasur advance_pay_info  "  title="أختر" id="advance_payment_treasur" name="advance_payment_treasur"  >
                            </select>

							
						</div>




<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">حسابات الرواتب</h5>
        <div>
            <?php  if(empty($rows)){ ?>
            <button type="button" class="btn btn-primary btn-sm" id="save_setting">💾 حفظ الإعداد</button>
            <?php  } else { ?>
            <button type="button" class="btn btn-secondary btn-sm" id="update_setting">✏️ تعديل الإعداد</button>
            <?php  } ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row">

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب مصروفات الرواتب يفضل ان يكون حساب <span style="color:red">مرتبات وأجور</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> المصروفات ←<br> مصروفات إدارية وعمومية ←<br> مصروفات إدارية ←<br> <strong>مرتبات وأجور</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_0" name="account_0" id="account_0" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
    <?php if(!empty($firstRow_0['account_id'])){  ?>  <option value="<?=$firstRow_0['account_id']?>" data_slev="<?=$firstRow_0['account_name']?>" selected><?=$firstRow_0['account_name']?></option> <?php } ?>
    </select>
</div>

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب مكافئات الموظفين  يفضل ان يكون حساب <span style="color:red">مكافآت الموظفين</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> المصروفات ←<br> مصروفات إدارية وعمومية ←<br> مصروفات إدارية ←<br> <strong>مكافئات الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_1" name="account_1" id="account_1" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
<?php if(!empty($firstRow_1['account_id'])){  ?>  <option value="<?=$firstRow_1['account_id']?>" data_slev="<?=$firstRow_1['account_name']?>" selected><?=$firstRow_1['account_name']?></option> <?php } ?>    
</select>
</div>

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب تعويضات الموظفين	  يفضل ان يكون حساب <span style="color:red">تعويضات الموظفين</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> المصروفات ←<br> مصروفات إدارية وعمومية ←<br> مصروفات إدارية ←<br> <strong>تعويضات الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_2" name="account_2" id="account_2" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
<?php if(!empty($firstRow_2['account_id'])){  ?>  <option value="<?=$firstRow_2['account_id']?>" data_slev="<?=$firstRow_2['account_name']?>" selected><?=$firstRow_2['account_name']?></option> <?php } ?>      
</select>
</div>

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب سلف الموظفين يفضل ان يكون حساب <span style="color:red">سلف الموظفين</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> الأصول ←<br>الأصول المتداولة←<br> المدينون ←<br>العمال والموظفين ←<br>سلف العمال والموظفين ←<br> <strong>سلف الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_3" name="account_3" id="account_3" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
<?php if(!empty($firstRow_3['account_id'])){  ?>  <option value="<?=$firstRow_3['account_id']?>" data_slev="<?=$firstRow_3['account_name']?>" selected><?=$firstRow_3['account_name']?></option> <?php } ?>      
</select>
</div>

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب خصومات الموظفين يفضل ان يكون حساب <span style="color:red">خصومات الموظفين</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> الخصوم ←<br>الخصوم المتداولة←<br> مصروفات مستحقة ←<br>  <strong>خصومات الموظفين</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_4" name="account_4" id="account_4" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
<?php if(!empty($firstRow_4['account_id'])){  ?>  <option value="<?=$firstRow_4['account_id']?>" data_slev="<?=$firstRow_4['account_name']?>" selected><?=$firstRow_4['account_name']?></option> <?php } ?>      
</select>
</div>

<div class="form-group col-md-4 advance_pay_info" style="display:none_">
    <label class="control-label" for="advance_payment_treasur">اختيار حساب مرتبات الموظفين المستحقة  يفضل ان يكون حساب <span style="color:red">مرتبات الموظفين المستحقة</span>
        <button type="button" class="btn btn-sm btn-light" data-toggle="popover" data-html="true" title="هام" data-content='<div style="text-align:right;">مسار الحساب:<br> دليل الحسابات ←<br> الخصوم ←<br>الخصوم المتداولة←<br> مصروفات مستحقة ←<br>  <strong>مرتبات الموظفين المستحقة</strong><br><br> <a href="accountant-coa" target="_blank" class="btn btn-sm btn-success"> ➕ إضافة حساب </a> </div>'> ؟</button>
    </label>
    <select class="form-control select2 select_account_5" name="account_5" id="account_5" data-placeholder="اسم أو رقم الحساب" style="width:100%" >
<?php if(!empty($firstRow_5['account_id'])){  ?>  <option value="<?=$firstRow_5['account_id']?>" data_slev="<?=$firstRow_5['account_name']?>" selected><?=$firstRow_5['account_name']?></option> <?php } ?>      
</select>
</div>
     </div>
    </div>
</div>



               
                   
   
                  




  
               
               </div>
               

             
               
               <div class="p-1">
               
           <div class="text-left">
               <!-- <button type="button" class="btn  show-advance float-right " data-dismiss=""><i class="fa fa-sliders-h"></i> <strong>خيارات اضافية</strong></button> -->
             <button type="reset" class="btn btn-default reset-filter" data-dismiss=""> الغاء الفلترة</button>
             <button type="submit" class="btn btn-info" name="" ><i class="fas fa-search"></i> بحث</button>
           </div>
           
         </div>
         

             <!-- /.row -->
             </form>
             <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
           </div> 
       
        </div>
        </div>
		</div> 
			
			
         
		<div class="container-fluid" id="result-containr" style="display:none">
        <div class="d-print-none text-left mb-2">
                    <button type="button" class="btn btn-default print_repo" onclick="printData()"><i class="fas fa-print"></i> طباعة</button>
                    <button type="button" class="btn btn-primary  download_pdf" value="<?=$report_name?>"><i class="fa fa-file-pdf"></i> PDF</button>
                    <button type="button" class="btn btn-warning  excel_data_table"><i class="fa fa-file-excel"></i> إكسل</button>
                </div>
<style>
@media print { 
    .table  { 
       width: 100% !important; 
    }
     .table  th { 
        background-color: #f5f5f5 !important; 
    } 
     section,div  { 
        background-color: #FFF !important; 
        padding: 0px !important; 
         margin: 0px !important;
    }
    
   a{
       color:#212529 !important; 
       text-decoration: none !important;
   }
   .invoice .d-none{
       display:block !important;
   }
   
}

</style>
				
		<div class="row">

			<div class="col-md-12 text-center mb-2" >
            <div class="invoice p-3">
                <h1 class="h5 d-none"><?=!empty($_SESSION['account']['title'])? $_SESSION['account']['title'] : $subdomain?></h1>
                <h1 class="h5">رواتب الموظفين</h1>
                <div  id="selected_client" class="bold"></div>
                <div id="selected_period" class="m-0" ></div>
                <div id="selected_branch" class="m-0" ></div>
                <p  id="report_time"></p>
                <div id="filter_note" class="m-0" ></div>

            </div>
            </div>
            
			<div class="col-md-12">
				
					<div class="table-responsive">
					   <table id="entries_tb" class="table dataTable table-bordered table-hover nowrap dtr-inline collapsed   display " width="100%" style="background: white;">
						<thead>
                        <tr >
<th style="text-align: center; vertical-align: middle;"   >رقم الموظف</th>
<th style="text-align: center; vertical-align: middle;" >اسم الوظف</th>
<th style="text-align: center; vertical-align: middle;" >الفرع</th>
<th style="text-align: center; vertical-align: middle;" >الراتب</th>
 <th style="text-align: center; vertical-align: middle;" >علية من الشهر الاول</th>

<th style="text-align: center; vertical-align: middle;" >السلف</th>

<th style="text-align: center; vertical-align: middle;" >الخصومات النقدية<br></th>
<th style="text-align: center; vertical-align: middle;" >خصومات الغياب<br></th>
<th style="text-align: center; vertical-align: middle;" >التعويضات</th>
<th style="text-align: center; vertical-align: middle;" >المكافاّت</th>
<th style="text-align: center; vertical-align: middle;" >عدد الساعات<br>الفعليه</th>
<th style="text-align: center; vertical-align: middle;" >عدد الساعات<br>الكاملة</th>
<th style="text-align: center; vertical-align: middle;" >عدد ساعات<br>الغياب</th>
<th style="text-align: center; vertical-align: middle;" >صافي الراتب<br>(رس.)</th>


						 
						</thead>
						<tbody></tbody>
						
					  </table>
                      <input type="hidden" id="sum_salary" name="sum_salary" readonly>
                      <input type="hidden" id="net_salary" name="net_salary" readonly>
                      <input type="hidden" id="sum_incentive" name="sum_incentive" readonly>
                      <input type="hidden" id="sum_benefit" name="sum_benefit" readonly>
                      <input type="hidden" id="sum_advance" name="sum_advance" readonly>
                      <input type="hidden" id="sum_dection" name="sum_dection" readonly>
                      <input type="hidden" id="currency" name="currency" readonly>
					</div>
				<br>
				<br>
		</div>
		</div>
			

         
	</div>

			
    </section>





<?php
 include_once('inc/footer.php');
?>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>


<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<script src="plugins/select2_n/dist/js/select2.full.js"></script>
<script>
var branches = <?php echo json_encode($all_list_branches)?>;
var selected_branches = branches;
function printData(){
   // window.print();
     var printContents = document.getElementById("result-containr").innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents; 
}

$(document).ready(function(){

   $('[data-toggle="popover"]').popover({ html: true });
 
function CreatePDFfromHTML(file_name) {
    var top_left_margin = 15;

    html2canvas($("#result-containr .row")[0]).then(function (canvas) {
        var imgData = canvas.toDataURL("image/jpeg", 1.0);

        // A4 Landscape
        var pdf = new jsPDF('p', 'pt', 'a4');
        var pageWidth = pdf.internal.pageSize.getWidth();
        var pageHeight = pdf.internal.pageSize.getHeight();

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




function entriesData(is_date_search,date_range='',branchs=[]){
	//var account = 0;
	var groupColumn = 0;
  var dataTable = $('#entries_tb').DataTable({
	"processing" : true,
	"serverSide" : true,
	"paging": true,
	"lengthChange": true,
	"pageLength": 200,
    "lengthMenu": [
        [200, 300, 400, 500,-1 ],
        [200, 300, 400, 500,'All'],
    ],
    
	"searching": false,
	"order" : [],
	"ordering": true,
	"info": false,
	"autoWidth": false,
	"responsive": false,
	
	"pagingType": "numbers",
	"aoColumns":[
		
		null,
		null,
        null,
        null,
		null,
		null,
		null,
		null,
        null,
        null,
        null,
        null,
        null,
		null
		
	],  
//         "columnDefs": [
//         {
//             "targets": 0,       // أول عمود
//             "visible": false,   // إخفاءه
//             "searchable": false // إذا ما تبغى يكون قابل للبحث برضه
//         }
//     ]
// ,
	language: {
            url:'/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
	url:"./hr-app/Issuing-salaries",
	type:"POST",
	data:{
        is_date_search:is_date_search,
            date_range: date_range,
            branchs: branchs
	
}
   },
   "dom": "<'row d-none'<'col-sm-12 col-md-4 'l><'col-sm-12 col-md-4'><'col-sm-12 col-md-4 text-left d-print-none'B>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    
     buttons: [
			{
			
			   text: '<i class="fa fa-filter"></i>',
			   titleAttr: 'فلترة متقدمة',
                action: function ( e, dt, node, config ) {
                    show_filter();
                }
			},
            {
                extend: 'copyHtml5',
				footer: true,
				 text: '<i class="fa fa-copy "></i>',
				 titleAttr: 'نسح الصفحة ',
                exportOptions: {
                   // columns: [ 0, ':visible' ]
					 columns: [ ':visible' ]
					
                }
            },
            {
                extend: 'excelHtml5',
				footer: true,
				 text: '<i class="fa fa-file-excel"></i>',
				 titleAttr: 'تصدير الصفحة إلى إكسل',
                exportOptions: {
                    //columns: [ 0, ':bSortable' ]
					 columns: [ ':visible' ]
                }
            },
			/* {
                extend: 'colvis',
				footer: true,
				 text: '<i class="fa fa-bars"></i>',
				  titleAttr: 'الأعمدة',
            }, */
			
			
			{
                extend: 'print',
				footer: true,
				 text: '<i class="fa fa-print"></i>',
				  titleAttr: 'طباعة الصفحة',
                exportOptions: {
                    columns: [ ':visible' ]
                }
            },
			
			
			
           
        ],    
         drawCallback: function (settings) {
             var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
             if(settings.json.results_note.name){
           // $('#selected_client').html(settings.json.results_note.selected_client);
        //    <button type="button" class="btn btn-success"  id="issuing_salaries" style><i class="fas fa-save"></i><span class="d-none d-sm-inline"> صرف</span></button>
			
            $('#selected_client').html('<h5>الموظف '+$('#select2-client-container').text()+'</h5>');
             }else{
                  $('#selected_client').html('');
             }
            $('#report_time').html('وقت إصدار التقرير : '+settings.json.results_note.report_time);
            $('#selected_period').html(settings.json.results_note.selected_period);
             $('#filter_note').html(settings.json.results_note.filter_note);
             $('#selected_branch').html('');
            if(settings.json.results_note.selected_branch.length > 0){
                   $('#selected_branch').html($('.select_branch .filter-option-inner-inner').html());
                   $('#selected_branch').html($('.select_branch .filter-option-inner-inner').html());

            }
            if(settings.json.data.length>0)
            {
              $('#addbutton').html('<button type="button" class="btn btn-success"  id="issuing_salaries" style><i class="fas fa-save"></i><span class="d-none d-sm-inline"> صرف</span></button>'); 
              $('#sum_salary').val(settings.json.sum_salary); 
              $('#net_salary').val(settings.json.net_salary); 
              $('#sum_incentive').val(settings.json.sum_incentive); 
              $('#sum_benefit').val(settings.json.sum_benefit); 
              $('#sum_advance').val(settings.json.sum_advance); 
              $('#sum_dection').val(settings.json.sum_dection); 
              $('#currency').val(settings.json.currency); 
            }
            else
            {
              $('#addbutton').html('');
              $('#sum_salary').val(''); 
              $('#net_salary').val(''); 
              $('#sum_incentive').val(''); 
              $('#sum_benefit').val(''); 
              $('#sum_advance').val(''); 
              $('#sum_dection').val('');   
            }

           
            $('#preloading').hide();
            $('#result-containr').show();
        },  

        
    

  });
  
  
  /* $('#entries_tb tbody').on( 'click', '.view', function () {
		var data = $(this).closest('tr').children('td:first').text();
		window.location.href = "purchase-suppliers-view?id="+data;
    } );
	
	$('#entries_tb tbody').on( 'click', '.edit', function () {
		var data = $(this).closest('tr').children('td:first').text();
		window.location.href = "purchase-suppliers-add?id="+data;
    } ); */
};


function get_filter(input_name)
{
	var filter = [];
		$('select[name="'+input_name+'"] option:selected').each(function() {
		filter.push($(this).val());
	   });
	return filter;
}
	
	
	
function apply_filters(){

    var date_range = $('#date_range').val();
    var branchs = get_filter('branchs_list');
  
//   


if (date_range) {
    var dates = date_range.split(' - ');
    if (dates.length === 2) {
        var startDate = new Date(dates[0]);
        var endDate = new Date(dates[1]);

        // التأكد من أن تاريخ البداية أصغر أو يساوي النهاية
        if (startDate <= endDate) {

            // التحقق أنهما في نفس الشهر ونفس السنة
            if (
                startDate.getFullYear() === endDate.getFullYear() &&
                startDate.getMonth() === endDate.getMonth()
            ) {
                // التحقق أن البداية هي أول يوم في الشهر
                var isFirstDay = startDate.getDate() === 1;

                // التحقق أن النهاية هي آخر يوم في الشهر
                var lastDay = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0).getDate();
                var isLastDay = endDate.getDate() === lastDay;

                if (isFirstDay && isLastDay) {
                    // ✅ التاريخ صالح: شهر كامل
                    $('#preloading').show();
                    $('#result-containr').hide();
                    $('#entries_tb').DataTable().destroy();
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

// 
  

  
 
}

$('#filter-fm').on('submit', function(e){  
	e.preventDefault();
	apply_filters();
});
<?php
if(!empty($auto_search)){
echo'apply_filters()';
}
?>




// $(document).on('click', '.show-advance', function(){
// 	$('.filter-advance').toggle();
// }); 
// <input type="hidden" id="sum_salary" name="sum_salary" readonly>
                    //   <input type="hidden" id="sum_incentive" name="sum_incentive" readonly>
                    //   <input type="hidden" id="sum_benefit" name="sum_benefit" readonly>
                    //   <input type="hidden" id="sum_advance" name="sum_advance" readonly>
                    //   <input type="hidden" id="sum_dection" name="sum_dection" readonly>
// صرف الرواتب

$(document).on('click', '#issuing_salaries', function(e){
    e.preventDefault();
    var tableData = [];

$('#entries_tb tbody tr').each(function () {
    // alert("jj");

    var rowData = [];
    $(this).find('td').each(function () {
        rowData.push($(this).text().trim());
    });
    tableData.push(rowData);
});
var sum_salary = $('#sum_salary').val();
var net_salary = $('#net_salary').val();
var sum_incentive = $('#sum_incentive').val();
var sum_benefit = $('#sum_benefit').val();
var sum_advance = $('#sum_advance').val(); 
var sum_dection = $('#sum_dection').val();
var currency = $('#currency').val();
var date_range = $('#date_range').val();
var branchs = get_filter('branchs_list');
var treasur = $('#advance_payment_treasur').val();
var payment_methods = $('#advance_payment_method').val();

alert(date_range);
// 
var account_id_0 = $(".select_account_0").val();
var account_id_1 = $(".select_account_1").val();
var account_id_2 = $(".select_account_2").val();
var account_id_3 = $(".select_account_3").val();
var account_id_4 = $(".select_account_4").val();
var account_id_5 = $(".select_account_5").val();
// 

	$.ajax({
        url:"./hr-app/salary-disbursement",
        method:"POST", 
		data:{ rows: tableData , date_range:date_range ,branchs:branchs,treasur:treasur,payment_methods:payment_methods,
            net_salary:net_salary,sum_salary:sum_salary,sum_incentive:sum_incentive,sum_benefit:sum_benefit,sum_advance:sum_advance,sum_dection:sum_dection,currency:currency,
            account_id_0:account_id_0,account_id_1:account_id_1,account_id_2:account_id_2,
            account_id_3:account_id_3,account_id_4:account_id_4,account_id_5:account_id_5
         },
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
                   window.location.href = 'Issuing-salaries-view';
			}
            else
            {
				toastr.error(data.msg);
			}

			$('#preloading').hide();
					
					
			
		}
	});
	
	
});

// 





//   دوال البحث عن طرق الدفع
payment_methods();
function payment_methods(){
	$.ajax({  
		method:"POST",  
		url: './sheard/payment-methods',
		data:{active:'1'}, 
		dataType:"json",
		beforeSend:function()
		  {
			//$('#preloading').show();
		  },		
		success:function(data)  
		{  
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
    if($(this).hasClass('advance_pay_info')){
        $('#advance_payment_treasur').val(treasur);
    }else if($(this).hasClass('full_payment')){
         $('#treasur').val(treasur);
    }else{
        
    }

});

$(document).on('change', '#branchs_list', function(){
$('#addbutton').html('');  
});
$(document).on('change', '#date_range', function(){
$('#addbutton').html('');  
});
// 
treasurList();
function treasurList(){
	$.ajax({  
		method:"POST",  
		url: './sheard/treasurs-menu',
		data:{active:0, actions:['out']}, 
		dataType:"json",
		beforeSend:function()
		  {
			//$('#preloading').show();
		  },		
		success:function(data)  
		{  
         var treasurs = data.data;
         console.log(treasurs);
			var html ='';
			$.each(data.data, function(k, v) {
				html +='<option value="'+data.data[k].id+'" >'+data.data[k].name+'</option>';			
			});
				$('#advance_payment_treasur').html(html);
				$('#treasur').html(html);
                if(data.user_treasur > 0){
				$('#treasur').val(data.user_treasur);
				$('#advance_payment_treasur').val(data.user_treasur);
                }
		} 
		
   });
	
}

//   خيار تحديد الحسابات
accountsList(0);
accountsList(1);
accountsList(2);
accountsList(3);
accountsList(4);
accountsList(5);
var deptorsdArry = [];
function accountsList(row_id){

$(".select_account_"+row_id).select2({
	
	//$(".js-select2-items-ajax_"+row_id).focus();
  ajax: {
	url: './sheard/accounts-menu',
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term, // search term
        s_disabled: 'n', // disable disabled accounts
        //s_type: '0', // disable disabled accounts
        s_chosed: deptorsdArry,
        page: params.page
      };
    },
    processResults: function (data, params) {
      params.page = params.page || 1;

      return {
        results: data.accounts,
        pagination: {
         // more: (params.page * 10) < data.total_count
		   more: (params.page) > 0
        }
      };
    },
    cache: true
  },
  allowClear: false,
  placeholder: 'اسم أو رقم الحساب',
  minimumInputLength: 1,
  templateResult: formatRepo,
  templateSelection: formatRepoSelection


});
$('.select_account_' + row_id).on('select2:select', function (e) {
    var data = e.params.data;
    // احفظ data_slev في الـ option المحددة
    $(this).find("option:selected").attr("data_slev", data.name);
});
function formatRepo (repo) {
  if (repo.loading) {
    return repo.text;
  }
  

  var $container = $(
	 "<div class='select2-result-repository clearfix'>" +
     
        "<div class='select2-result-repository__path'></div>" +
        "<div class='select2-result-repository__title bold_sm'></div>" +
    
      "</div>" 
  );

  $container.find(".select2-result-repository__path").text(repo.path);
  $container.find(".select2-result-repository__title").text(repo.name);
   return $container;
}

function formatRepoSelection (repo) {
	return repo.name || repo.text;
}
}
//   حفظ اعدادات الحسابات
$(document).on('click', '#save_setting', function(e){
   save_setting('add'); 
});
$(document).on('click', '#update_setting', function(e){
   save_setting('edit'); 
});

function save_setting(name){
var selectedOption = $(".select_account_0").find("option:selected");
var account_id_0 = selectedOption.val();

var accountName_0 = selectedOption.attr("data_slev");

var selectedOption_1 = $(".select_account_1").find("option:selected");
var account_id_1 = selectedOption_1.val();
var accountName_1 = selectedOption_1.attr("data_slev");

var selectedOption_2 = $(".select_account_2").find("option:selected");
var account_id_2 = selectedOption_2.val();
var accountName_2 = selectedOption_2.attr("data_slev");

var selectedOption_3 = $(".select_account_3").find("option:selected");
var account_id_3 = selectedOption_3.val();
var accountName_3 = selectedOption_3.attr("data_slev");

var selectedOption_4 = $(".select_account_4").find("option:selected");
var account_id_4 = selectedOption_4.val();
var accountName_4 = selectedOption_4.attr("data_slev");

var selectedOption_5 = $(".select_account_5").find("option:selected");
var account_id_5 = selectedOption_5.val();
var accountName_5 = selectedOption_5.attr("data_slev");


	$.ajax({
        url:"./hr-app/save-setting-account-salary",
        method:"POST", 
		data:{ account_id_0: account_id_0 , account_id_1:account_id_1 ,account_id_2:account_id_2,account_id_3:account_id_3,account_id_4:account_id_4, account_id_5:account_id_5,name:name,
accountName_0:accountName_0,accountName_1:accountName_1,accountName_2:accountName_2,accountName_3:accountName_3,
accountName_4:accountName_4,accountName_5:accountName_5

        },
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		},  
		success:function(data){
			if(data.result)
            {
				toastr.success(data.msg);
                window.location.href = 'Issuing-salaries';
			}
            else
            {
				toastr.error(data.msg);
			}
			$('#preloading').hide();
		}
	});
}



});
 

</script>