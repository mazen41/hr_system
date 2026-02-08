<?php
$appid  = 'HR';
$page_perm=['الحضور والانصراف','عرض الحضور والانصراف','تحضير موظف','رفع ملف الاكسل'];

$screen = 'إدارة الموارد البشرية';
$page_title = 'كشف الحضور والانصراف';
 

 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<style>	
.table.dataTable{
	margin-top: 0px !important;
}
.table.dataTable  td, .table.dataTable  th {
	vertical-align: middle;
}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">الكشف</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
                  <?php if($User->isAllowedPerm(['تحضير موظف'],$appid)){ ?>
        <button type="button" class="btn btn-primary"  id="add-emp-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline">تحضير موظف</span></button>	
            <?php
         } 
         if($User->isAllowedPerm(['رفع ملف الاكسل'],$appid)){ 
         ?> 
			
            <button type="button" class="btn btn-primary"  id="add-emp-bt-excel"><i class="fas fa-plus"></i><span class="d-none d-sm-inline">رفع من ملف اكسل</span></button>			
          <?php
         }
         ?>
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
                  <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
                  <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
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

       <div class="col-md-4">
        <div class="form-group">
          <label for="filter_status" class="col-form-label">الحالة</label>
            <select class=" selectpicker show-tick" data-width="100%"  title="أي" id="filter_status" name="filter_status">
              <option value="1">حاضر </option>
              <option value="2">غير حاضر</option>
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
               

<!--                
               <div class="col-md-4 filter-advance">
       <div class="form-group">
               <label for="user_shift" class="col-form-label  logindata ">فترات العمل</label>
                <select class="form-control logindata  selectpicker "  data-live-search="true"    title="حدد فترات العمل " id="user_shift" name="user_shift[]"  multiple="multiple"  data-size="5" >
             
               </select>
                           </div>
       </div> -->

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
	
	

			
			
		
				
				<div class="row">
                
                <div class="col-md-12">
					 <div class="card">
					<div class="table-responsive">
						<table id="data_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm" width="100%">
							 <thead>
                                 <tr class="bg-gry">
                                 <th>رقم الموظف</th>
                                  <th>اسم الموظف</th>
                                  <th >تاريخ الحظور</th>
                                  <th>رقم الفتره</th>
                                  <th>الحضور والانصراف</th>
                                  <th >حاله الحضور</th>
                                  <th >حاله الانصراف</th>
                                  <!--  -->
                                  <th > ساعات العمل المطلوبة</th>
                                  <th >دقائق تاخير الحظور</th>
                                  <th >دقائق تاخير الانصراف</th>
                                   <!--  -->
                                  <th >عدد الساعات الفعلية</th>
                                  <!--  -->
                                  <th >اجمالي الساعات</th>
                                  <th >الحالة</th>
                                   <!--  -->
                                  <th>الاجراءات</th>

                                </tr>
                            </thead>
							<tbody></tbody>
						</table>
					</div>
					</div>
					</div>
              </div>
              <!-- /.row -->
			

         
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
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script>

$(document).ready(function(){


  $(document).on('click', '.show-advance', function(){
        
        $('.filter-advance').toggle();
      }); 



//هذه من اجل الغاء الفلتره

$(document).on('click', '.reset-filter', function(){
    $('#filter-fm').each(function() {
    $("input").val('');
    $(".selectpicker").val('');
    $("#branchs_list").val('');
    $("#user_section").val('');
    $("#user_jobtitle").val('');
    $("#user_grade").val('');
    // $("#user_shift").val('');
    $("#user_groub").val('');
    $("#emp_name").val('');
    $(".selectpicker").selectpicker("refresh");
});
});
// هذه من اجل جلب القيم تبع الفلتره وتحويلها لمصفوفه
function get_filter(input_name)
{
    var filter = [];
    $('select[name="'+input_name+'"] option:selected').each(function() {
    filter.push($(this).val());
    });
return filter;
}
// هذه تقوم بتطبيق قيمة الفلتر 
function apply_filters()
{
    var date_range = $('#date_range').val();
    var state = $('#filter_status').val();
    var name = $('#emp_name').val();
    var branchs = get_filter('branchs_list');
    var section = get_filter('user_section');
    var jobtitle = get_filter('user_jobtitle');
    var grade = get_filter('user_grade');
    // var shift = get_filter('user_shift');
    var groub = get_filter('user_groub');
    
    $('#data_tb').DataTable().destroy();
    clients_data('yes',date_range,state,section,jobtitle,grade,branchs,groub,name); // افحص مخرجات الداله


}
// هذه عندما نطبق الفلتر
$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});

$('#add-emp-bt').on('click', function(event){
   window.open("attendancet-emp","_self");
});

$('#add-emp-bt-excel').on('click', function(event){
   window.open("import-emp-atten","_self");
});

// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
function clients_data(is_date_search,date_range='',states, section=[],jobtitle=[],grade=[],branchs=[], groub=[])
 {
	//var account = 0;
  var dataTable = $('#data_tb').DataTable({
    "processing" : true,
    "serverSide" : true, 
    "paging": true,
    "lengthChange": true,
    "searching": false,
    "order" : [],
    "ordering": true,
    "info": false,
    "autoWidth": false,
    "responsive": true,
    "pagingType": "numbers",
	language: {
            url: '/dist/js/dataTables.arabic.json'
        },
   "ajax" : {
    url:"./hr-app/reveal-attendance",
    type:"POST",
    data:{ 
            is_date_search:is_date_search,
            date_range: date_range,
            states: states,
            section: section,
            jobtitle: jobtitle,
            grade: grade,
            branchs: branchs,
            groub: groub,
	}
	},
   
    aoColumns: [
		  
			{
                data: ['data'],
                render: function(data, type) {
					return data.id;
                }
            },  
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.name;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.updated;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.ShiftID;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.attendance_punches;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.checkin_status;
                     
                }
            },
            {

                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.checkout_status;
                     
                }
            },
            // 
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.scheduled_hours;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.delay_minutes;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.early_departure_minutes;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.actual_working_hours;
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.credited_hours;
                     
                }
            },
            // 

			
			  { 
			   data: ['data'],
			  "bSortable": false,
			  render: function(data, type) {
                      var options='<div class="btn-group"> <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu">';
                <?php if ($User->isAllowedPerm(['عرض الحضور والانصراف'], $appid)) { ?>
                    options += '<a href="reveal-attendance-view?id='+data.id+'" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a>';
                <?php } ?>

                options+='</div></div>'
                   return options;     
                }
				
			
			  }
			
			
		],
   
    "dom": "<'row'<'col-sm-12 col-md-7'><'col-sm-12 col-md-1'><'col-sm-12 col-md-4 text-left'>>" +
    
    "<'row'<'col-sm-12 mb-4'tr>>" +
    "<'row'<'col-sm-12 col-md-5'li><'col-sm-12 col-md-7'p>>", 
  
    
	
	
  });

};
  


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
					// populateSelect('#user_shift', response.Shift);
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