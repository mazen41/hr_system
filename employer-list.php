<?php
$appid  = 'HR';
// $screen = 'إدارة الموارد البشرية';
// $page_title = 'اضافة موظف';

$screen = 'إدارة الموارد البشرية';
$page_title = 'اضافة موظف';

$page_perm=['إضافة موظف','عرض موظف','تعديل موظف'];
 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<style>
.modal-dialog .overlay{
	background-color: rgba(255, 255, 255, 0.7);
}
/* .filter-advance
{
    height: 200px;
    overflow: scroll;
} */
</style>
    <!-- Content Header (Page header) -->
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title">إدارة الموارد البشرية</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
            <?php
          if($User->isAllowedPerm(['إضافة موظف'],$appid)){ ?>
			<button type="button" class="btn btn-success"  id="add-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة موظف</span></button>
            <?php
         } 
         ?>  
        </div>
        </div>
      </div>
    </div>
    <!-- /.content-header -->

    <!-- Search  -->


    <!-- Main content -->
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
			
			
		<div class="invoice mb-3">
  
              <!-- /.card-header -->
               <table id="userstb" class="table table-striped nowrap display" width="100%">
                     <thead>
                      <tr>
                       <th>الموظف</th>
                       <th>القسم</th>
                       <th>الراتب</th>
                       <th>الفرع</th>
                       <th>الشهائد والخبرات</th>
                       <th>ايقاف  موظف </th>
                       <th>اجراءات</th>
                      </tr>
                     </thead>
                    <tbody></tbody>
                </table>	
			
			
			
			
	  </div>
	  </div>
	  </div>
	  </div>
    </section>
    

<?php
 include_once('inc/footer.php');
?>
<!-- DataTables  & Plugins -->

<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>



<script>
$(document).ready(function(){


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


	fetch_users('no');
	
	function fetch_users(is_date_search, section=[],jobtitle=[],grade=[],shift=[],branchs=[], groub=[],name)
	{
		var dataTable = $('#userstb').DataTable({
		"processing" : true,
	"serverSide" : true,
	"paging": true,
	"lengthChange": false,
	
	"searching": false,
	"order" : [],
	"ordering": false,
	"info": false,
	"autoWidth": false,
	"responsive": true,
	
	//"pagingType": "simple",
		//"order": [[ 2, "desc" ]],
		
		"aoColumns":[
					  //{ "bSortable": false },
					 // null,
					  null,
					  null,
					  null,
                      null,
                      null,
                    
                      
					  { "bSortable": false },
					  { "bSortable": false }
					],
	
			
		 language: {
            url:'/dist/js/dataTables.arabic.json'
        }, 
		"ajax" : {
		url:"/hr-app/employer-list",
		 type:"POST",
		  data:{
            is_date_search:is_date_search,
            section: section,
            jobtitle: jobtitle,
            grade: grade,
            shift: shift,
            branchs: branchs,
            groub: groub,
            name:name
	

          }
		}
		});
		 
	}
	


 
$('#add-bt').on('click', function(event){
   window.open("employer-add","_self");
});


// 
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
    var name = $('#emp_name').val();
    var branchs = get_filter('branchs_list');
    var section = get_filter('user_section');
    var jobtitle = get_filter('user_jobtitle');
    var grade = get_filter('user_grade');
    var shift = get_filter('user_shift');
    var groub = get_filter('user_groub');
    
    $('#userstb').DataTable().destroy();
    fetch_users('yes',section,jobtitle,grade,shift,branchs,groub,name); // افحص مخرجات الداله
}

$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});


//  
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
    $(".selectpicker").selectpicker("refresh");
});
});



 

// ايقاف موظف
function confirm_remove (id) {
		if(id !=''){
			$('#modal_title').text('تأكيد ايقاف الموظف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/stop_emp?id='+id+'',function(){
				 // $('#modal_default .modal-dialog').addClass('modal-md');
			});
		}
	}
    
    

$(document).on('click', '.stop_emp', function(){
	var id = $(this).val();
	confirm_remove(id);

});

$(document).on('click', '.change_pass', function(){
	var user = $(this).val();
	var user_name = $(this).data('user');
    
	var new_pass = prompt("تعديل كلمة مرور المستخدم '"+user_name+"' : ", "");
	  if (new_pass !=null) {
	$.ajax({
		type: 'POST',
		url:"./users-app/change-user-pass",
		data: {user:user,new_pass:new_pass},
		dataType:"json",
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			
			if(data.error > 0){
				toastr.error(data.msg);
			}else{
				toastr.success(data.msg);
			}
			$('#preloading').hide();
		}
	});	
}

});




});
</script>