<?php
$screen = 'التقارير';
 $report_name = 'بيانات الموظفين';
$page_title = $report_name;
//setcookie('lasturl', 'reports-account-statement', time() + (86400 * 30), "/"); // 86400 = 1 day
 include_once('inc/header.php');
$all_list_branches = [$branch];
$allowed_branches = $User->allBranches($User->branches);
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
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title"><?=$report_name?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			
			
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
               
               <div class="col-md-4">
               <div class="form-group">
               
                   <label for="emp_name" class="col-form-label">اسم الموظف</label>
                   <input type="text" class="form-control" id="emp_name"  name="emp_name" placeholder="" >
                   </select>
               </div>
               </div>

               <div class="col-md-4">
               <div class="form-group">
               <label for="user_status" class="col-form-label  logindata ">حالة الموظف</label>
                               <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد حالة الموظف" id="user_status" name="user_status"  >
                                <option value="1" >مستمر</option>
                                <option value="2" >موقف</option>
                                <option value="3" >مفصول</option>
                                <option value="4" >مستقيل</option>
                                <option value="5" >انتهاء عقد</option>
                                </select>
                           </div>
               </div>
               
                <div class="col-md-4">
               <div class="form-group">
               <label for="branchs_list" class="col-form-label  logindata ">الفرع</label>

                              <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أي" id="branchs_list" name="branchs_list"  multiple="multiple" >
                              <?php
                                   
                                       foreach($allowed_branches as $id => $name){	
                                           echo'<option value="'.$id.'" >'.$name.'</option>';
                                       }

                                   ?>	
                           </select>
               </div>
               </div>

               <div class="col-md-4 filter-advance">
               <div class="form-group">
               <label for="user_section" class="col-form-label  logindata ">القسم</label>
                               <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد باي قسم" id="user_section" name="user_section"  multiple="multiple" >
                                </select>
                           </div>
               </div>

               
                   
   
                  

               
               <div class="col-md-4 filter-advance">
               <div class="form-group">
               <label for="user_jobtitle" class="col-form-label  logindata ">المسمى الوظيفي</label>
               <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle" multiple="multiple"  >
                               </select>
               </div>
               </div>
               
               <div class="col-md-4 filter-advance">
               <div class="form-group">
               <label for="user_grade" class="col-form-label  logindata ">الدرجة الوظيفية</label>
                               <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" multiple="multiple"  >  
                               </select></div>
               </div>
               

               
               <div class="col-md-4 filter-advance">
               <div class="form-group">
               <label for="user_shift" class="col-form-label  logindata ">فترات العمل</label>
                              <select class="form-control logindata  selectpicker "  data-live-search="true"    title="حدد فترات العمل " id="user_shift" name="user_shift[]"  multiple="multiple"  data-size="5" >
                           
                               </select>
                           </div>
               </div>

               <div class="col-md-4 filter-advance ">
                   <div class="form-group">
                   <label for="user_groub" class="col-form-label  logindata ">المجموعه الوظيفية</label>
                                  <select class="form-control logindata  selectpicker "  data-live-search="true"    title="حدد المجموعه  " id="user_groub" name="user_groub[]"  multiple="multiple"  data-size="5">
                               
                                   </select>
                               </div>
                   </div>
               </div>
               
               </div>
               

             
               
               <div class="p-1">
               
           <div class="text-left">
               <button type="button" class="btn  show-advance float-right " data-dismiss=""><i class="fa fa-sliders-h"></i> <strong>خيارات اضافية</strong></button>
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
                <h1 class="h5">بيانات الموظفين</h1>
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
<th style="text-align: center; vertical-align: middle;" >اسم الوظف</th>
<th style="text-align: center; vertical-align: middle;" >الفرع</th>
<th style="text-align: center; vertical-align: middle;" >المسمى الوظيفي</th>
<th style="text-align: center; vertical-align: middle;" >القسم</th>
<th style="text-align: center; vertical-align: middle;" >تاريخ بداية العقد</th>
<th style="text-align: center; vertical-align: middle;" >تاريخ انتهاء العقد</th>
<th style="text-align: center; vertical-align: middle;" >الراتب<br>(رس.)</th>


						 
						</thead>
						<tbody></tbody>
						
					  </table>
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
        //$("#the_invoice").hide();
    });
} 
  
 $(document).on('click', '.download_pdf', function(){
    var file_name = $(this).val();
    CreatePDFfromHTML(file_name);
});
$(document).on('click', '.excel_data_table', function(){
    $('.buttons-excel').trigger('click');
});
/*  
$(document).on('click', '.print_repo', function(){
    $('.buttons-print').trigger('click');
}); */
 

$(document).on('click', '.reset-filter', function(){
		$('#filter-fm').each(function() {
            $("input").val('');
    $(".selectpicker").val('');
    $("#branchs_list").val('');
    $("#user_section").val('');
    $("#user_jobtitle").val('');
    $("#user_grade").val('');
    $("#user_shift").val('');
    $("#user_groub").val(''); 
    $("#emp_name").val('');
    $("#user_status").val('');
		$(".selectpicker").selectpicker("refresh");
	});
});




/* $(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
}); */




// clients();
// function clients(){
// $("#client").select2({
	
//   ajax: {
// 	url: '/sheard/client-search',
//     dataType: 'json',
//     delay: 250,
//     data: function (params) {
//       return {
//         q: params.term, 
//        // s_disabled: 'y', 
//        //  branches: selected_branches,
//         page: params.page
//       };
//     },

//     processResults: function (data, params) {
//       // parse the results into the format expected by Select2
//       // since we are using custom formatting functions we do not need to
//       // alter the remote JSON data, except to indicate that infinite
//       // scrolling can be used
//       params.page = params.page || 1;
//       return {
//         results: data.items,
//         pagination: {
//           more: (params.page * 10) < data.total_count
//          // more: (params.page) < 3
//         }
//       };
//     },
//     cache: true
//   },
//   allowClear: true,
//   placeholder: 'أي',
//   minimumInputLength: 1,
//   templateResult: formatRepo,
//   templateSelection: formatRepoSelection
// });

// function formatRepo (repo) {
//   if (repo.loading) {
//     return repo.text;
//   }

//   var $container = $(
//     "<div class='select2-result-repository clearfix rtl'>" +
     
//       "<div class='select2-result-repository__meta' >" +
//         "<div class='select2-result-repository__title'></div>" +
//         "</div>" +
//       "</div>" 
//   );

//   $container.find(".select2-result-repository__title").text(repo.name);
//   return $container;
// }

// function formatRepoSelection (repo) {
//   return repo.full_name || repo.text;
// }
// }



//  $(document).on('change', '#branchs_list', function(){
//      if($('select[name="branchs_list"] option:selected').length > 0){
         
//         selected_branches = get_filter('branchs_list');
//      }else{
//          selected_branches = branches;
//      }
// });





function entriesData(is_date_search, status,section=[],jobtitle=[],grade=[],shift=[],branchs=[], groub=[],name){
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
		null
		
	],  
	language: {
            url:'/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
	url:"./hr-app/report-all-emplyer",
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
    $('#preloading').show();
  $('#result-containr').hide();
    var name = $('#emp_name').val();
    var branchs = get_filter('branchs_list');
    var section = get_filter('user_section');
    var jobtitle = get_filter('user_jobtitle');
    var grade = get_filter('user_grade');
    var shift = get_filter('user_shift');
    var groub = get_filter('user_groub');
    var status = $('#user_status').val();
    
  
  $('#entries_tb').DataTable().destroy();
  entriesData('yes',status,section,jobtitle,grade,shift,branchs,groub,name);
  
 
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




$(document).on('click', '.show-advance', function(){
	$('.filter-advance').toggle();
}); 

get_filter_info();
function get_filter_info()
{
    $.ajax({
                url: '/hr-app/allUserinfo_Search',
                type: 'POST',
                data: { value: 1 },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
                success: function(response) { 
                    // user_manager
					populateSelect('#user_section', response.section);
                    populateSelect('#user_jobtitle', response.jobtitle);
					populateSelect('#user_grade', response.JobGrade);
					populateSelect('#user_shift', response.Shift);
                    // populateSelect('#branchs_list', response.branch);
					populateSelect('#user_groub', response.groub_list);
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
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
	select.selectpicker('refresh'); // تحديث SelectPicker
}


});
 

</script>