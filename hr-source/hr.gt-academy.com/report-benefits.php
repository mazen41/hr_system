<?php
$screen = 'التقارير';
 $report_name = 'تعويضات الموظفين';
$page_title = $report_name;
//setcookie('lasturl', 'reports-account-statement', time() + (86400 * 30), "/"); // 86400 = 1 day
 include_once('inc/header.php');
$all_list_branches = [$branch];
$allowed_branches = $User->allBranches($User->branches);
?>


  

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
               
               <div class="col-md-3">
               <div class="form-group">
               
                   <label for="incentive_name" class="col-form-label">اسم التعويض</label>
                   <input type="text" class="form-control" id="incentive_name"  name="incentive_name" placeholder="" >
                   </select>
               </div>
               </div>
               
                <div class="col-md-3">
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

               <div class="col-md-3">
               <div class="form-group">
               <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
               <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
               </div>
               </div>

               <div class="col-md-3">
              <div class="form-group">
                <label for="filter_status" class="col-form-label">الحالة</label>
                  <select class=" selectpicker show-tick" data-width="100%"  title="أي" id="filter_status" name="filter_status">
                    <option value="1">معتمد</option>
                    <option value="2">غير معتمد</option>
                  </select>
              </div>
                  </div>

               </div>
               
               </div>
               

             
               
               <div class="p-1">
               
           <div class="text-left">
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
#entries_tb tbody tr.selected .show-details {
    display: inline-block !important;
}

</style>
				
		<div class="row">

			<div class="col-md-12 text-center mb-2" >
            <div class="invoice p-3">
                <h1 class="h5 d-none"><?=!empty($_SESSION['account']['title'])? $_SESSION['account']['title'] : $subdomain?></h1>
                <h1 class="h5">بيانات المكافئات</h1>
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
<th style="text-align: center; vertical-align: middle;" >اسم التعويض</th>
<th style="text-align: center; vertical-align: middle;" >الفرع</th>
<th style="text-align: center; vertical-align: middle;" >نوع التعويض</th>
<th style="text-align: center; vertical-align: middle;" >دورية</th>
<th style="text-align: center; vertical-align: middle;" >المبلغ</th>
<th style="text-align: center; vertical-align: middle;" >منشاءه بواسطة</th>
<th style="text-align: center; vertical-align: middle;" >حاله التعويض</th>
<th class="show-details"></th> 


						 
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
<!-- DataTables loaded from CDN in footer.php -->


<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
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
    $("#incentive_name").val('');
    $("#date_range").val('');
    $("#filter_status").val('');
    $("#emp_name").val('');
		$(".selectpicker").selectpicker("refresh");
        entriesData('no');
	});
});

entriesData('no');
function entriesData(is_date_search,name,branchs=[],date_range='',filter_status=''){
	//var account = 0;
	var groupColumn = 0;
    // $('#entries_tb').DataTable().destroy();
  var dataTable = $('#entries_tb').DataTable({
	"processing" : true,
	"serverSide" : true,
	"paging": true,
	"lengthChange": true,
	"pageLength": 10,
    "lengthMenu": [
        [10, 20, 30],
        [10, 20, 30],
    ],
	"searching": false,
	"order" : [],
	"ordering": false,
	"info": false,
	"autoWidth": false,
	"responsive": true,
	
	"pagingType": "numbers",
    "aoColumns": [
    null, null, null, null, null, null, null,
    { "orderable": false } // زر الإجراء
], 
	language: {
            url:'/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
	url:"hr-app/index.php?action=report-benefits",
	type:"POST",
	data:{
        is_date_search:is_date_search,
        name: name,
            branchs: branchs,
            date_range: date_range,
            filter_status:filter_status
	
}
   },
   "dom": "<'row'<'col-sm-12 col-md-4'><'col-sm-12 col-md-8 text-left'>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'il><'col-sm-12 col-md-7'p>>",
    
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
            $('#selected_client').html('<h5>تعويض '+$('#select2-client-container').text()+'</h5>');
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
  var name = $('#incentive_name').val();
    var branchs = get_filter('branchs_list');
    var date_range = $('#date_range').val()
    var filter_status = $('#filter_status').val();

//   $('#entries_tb').DataTable().destroy();
  entriesData('yes',name,branchs,date_range,filter_status);
  
 
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



// $('#entries_tb tbody').on('click', 'tr', function () {
//     var table = $('#entries_tb').DataTable();

//     if ($(this).hasClass('selected')) {
//         $(this).removeClass('selected');
//     } else {
//         table.$('tr.selected').removeClass('selected');
//         $(this).addClass('selected');
//     }
// });


// $('#entries_tb tbody').on('click', '.show-details', function (e) {
//     e.stopPropagation(); // حتى لا يؤثر على تحديد الصف

//     var table = $('#entries_tb').DataTable();
//     var rowData = table.row($(this).closest('tr')).data();

//     // مثال عرض التفاصيل
//     alert("تفاصيل المكافأة:\n" + rowData.join(" | "));
// });




});
 

</script>
