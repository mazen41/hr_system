<?php
// $screen = 'الاعدادات';
// $page_title = 'إستيراد الموظفين';

$screen = 'إدارة الموارد البشرية';
$page_title = 'كشف الحضور والانصراف';

$sub_title = 'إستيراد الموظفين';

include_once('inc/header.php');
?>
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
	<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

        
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-8">
            <span class="page-title"><?=$sub_title?></span>
          </div><!-- /.col -->
        </div>
      </div>
    </div>
    
    
     <section class="content">
      <div class="container-fluid">
      
      <div class="row">
		
            <div class="col-md-12 text-center">
              <h5>تحظير الموظفين من ملف اكسل</h5> 
                <br>
            <div>
        <div>
        
		<div class="row">
        
		<div class="col-md-6">
        <div class="invoice">
        <div class="box-body text-center m-2 pb-3">
        <h6>أولاً :  تجهيز البيانات</h6>
        
        <a href="emp_attendancet.xlsx" type="button" class="mt-2 col-4 btn btn-default" ><i class="fa fa-download"></i><br> تنزيل نموذج اكسل لبيانات الموظف </a>
        </div>
        </div>
        </div>
        
		<div class="col-md-6">
            <div class="card">
             
              <!-- /.card-header -->
              <div class="box-body text-center m-2">
						<form class="form-horizontal" method="post" enctype="multipart/form-data" id="import_items_fm" action="#">
						
					
						
						<div class="form-group">
                          <h6>ثانياً : استيراد البيانات</h6>
							<!--<label for="import_file" class=" control-label">حدد ملف الأكسل</label>-->
							<div class="col-sm-12">
								<input type="file" name="excel" id="import_file" class="form-control" />
							</div>
						</div>
						<div class="form-group">
							
                          <button type="submit" name="import_submit" id="import_submit"  class="btn btn-danger" ><i class="fa fa-upload"></i> إستيراد الملف</button>
                           
                           <!--<input type="submit" name="import_submit" id="import_submit" class="btn btn-danger " value="إستيراد الملف" />-->
						 </div>
						   </form>
						   
						  <!-- <div class="col-md-12 text-center" id="item_submit_result"></div>-->
					  <!-- /.box-body -->
				</div>
			  <div class="overlay" style="display:none" id="import_holdon"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
		
		
		 
		
		
		
		</div>
        <div class="row" style="">
         <div class="col-md-12 text-center" id="item_submit_result"></div>
        </div>
        
        
        <div class="row" style="">
            <div class="col-md-12 mb-5">
                <div class="table-responsive">
					  <table class="table table-bordered " id="report_tb" width="100%" style="display:none; background: white;">
						  <thead>
							<tr class="bg-gry">
							  <th width="5%">رقم الخطاء</th>
							  <th width="30%">رقم الموظف</th>
							  <th width="10%">التاريخ</th>
							  <th width="10%">الوقت</th>
							  <th width="30%">تفاصيل الخطاء</th>
							  <th width="25%">رقم الصف في ملف الإكسل</th>
							</tr>
						  </thead>
						  <tbody></tbody>
                      </table>
            </div>
		</div>
		</div>
      </div><!-- /.container-fluid -->
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


<script>
var errors_report = [];
$(document).ready(function(){
	
	
var Toast = Swal.mixin({
	toast: true,
	position: 'top-end',
	showConfirmButton: false,
	timer: 5000
});	
	
	
	
$('#import_items_fm').on('submit', function(e){  
	   e.preventDefault();
	   if($(this).valid()){
		   $.ajax({  
                url:"settings-app/import-emp_attent",                  
                //url:"settings-app/import-pharma",                  
				method:"POST",  
				data:new FormData(this), 
				dataType:"json",	
				contentType:false,  
				processData:false, 
				beforeSend:function(){ 
					$('#import_holdon').show();
						//import_holdon
				  $('#item_submit_result').html("<h5>جاري معالجة الملف يرجى الانتظار لحين اكتمال المعالجة<br>قد تستغرق عملية المعالجة عدة دقائق</h5>لاتقم بأي إجراء إلى أن تنتهي عملية الإستيراد");  
				}, 			
				success:function(data)  
				{  
					 
                    if(data.result){
                        toastr.success(data.mainmsg);
                    }else{
                        toastr.error(data.mainmsg);
                    }
                    
                    //if(data.msg !=''){
					 $('#item_submit_result').html(''+data.msg+'');
                   // }
                   
                        errors_report = data.errors_list;

					$('#import_holdon').hide();
					$('#import_items_fm')[0].reset();
					
				}  
		   });

		}
  });
	  
	  
	  
	  
	  
$('#import_items_fm').validate({
    rules: {
      excel: {
        required: true
      },
    },
    messages: {
      excel: {
        required: "يرجى تحديد الملف الذي سيتم استيرادة"
      },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    }
});
	
	
function showErrors(errors_report){
   // console.log(errors_report);
  
    var dataTable = $('#report_tb').DataTable({
	data: errors_report,
    "processing" : true,
    "paging": false,
	"lengthChange": true,
	"searching": true,
	"order" : [],
	"ordering": true,
	"info": false,
	"autoWidth": true,
	"responsive": true,
            columns: [
                 null,
                null,
                null,
                null,
                null,
                null 
                  
            ],
   "dom": '<"row col-md-12"lfB>rt<"col-md-12"ip>',
    /* "dom": "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'><'col-sm-12 col-md-4 text-left'B>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>", */
    buttons: [

            {
                extend: 'excelHtml5',
				footer: true,
				 text: '<i class="fa fa-file-excel "></i> تصدير إلى إكسل',
				 titleAttr: 'تصدير الصفحة إلى إكسل',
                exportOptions: {
                    //columns: [ 0, ':bSortable' ]
					 columns: [ ':visible' ]
                }
            },

			{
                extend: 'print',
				footer: true,
				 text: '<i class="fa fa-print"></i> طباعة',
				  titleAttr: 'طباعة الصفحة',
                exportOptions: {
                    columns: [ ':visible' ]
                }
            },
			
			
			
           
        ],
drawCallback:function(e){
	$('#report_tb').show();
     
}
	
	
  });
  
  
}	
	
$(document).on('click', '#report_btn', function(){
     if(errors_report.length === 0){
         alert('لايوجد تقرير بالاخطاء');
     }else{
        $('#report_tb').DataTable().destroy();
         showErrors(errors_report);
        //$('#preloading').show();
        
     }

});


});
</script>