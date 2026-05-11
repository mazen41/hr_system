<?php
$appid  = 'HR';
$page_perm=['اضافة ترقية'];
// $screen = 'الموظفين';
// $page_title = 'ترقية الموظفين';

$screen = 'إدارة الموارد البشرية';
if(!empty($_GET['type'])){
if($_GET['type']=='contract')
$page_title = 'إدارة تجديد العقود';
else
$page_title = 'إدارة الترقيات';

}
 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>

<style>
.modal-dialog .overlay{
	background-color: rgba(255, 255, 255, 0.7);
}
</style>
    <!-- Content Header (Page header) -->
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title"><?php echo $page_title ?></span>
          </div><!-- /.col -->
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
					<h3 class="card-title">ابحث عن الموظف المراد ترقية</h3>
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
							   <select class="form-control logindata  selectpicker "  data-live-search="true"    title="حدد فترات العمل " id="user_shift" name="user_shift[]"  multiple="multiple"  >
							
								</select>
                            </div>
				</div>

                <div class="col-md-4 filter-advance ">
                    <div class="form-group">
                    <label for="user_groub" class="col-form-label  logindata ">المجموعه الوظيفية</label>
                                   <select class="form-control logindata  selectpicker "  data-live-search="true"    title="حدد المجموعه  " id="user_groub" name="user_groub[]"  multiple="multiple"  >
                                
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
                       <th>نوع العملية</th>
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

<!-- DataTables loaded from CDN in footer.php -->



<script>
$(document).ready(function(){
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type');
    

    $(document).on('click', '.show-advance', function(){
	$('.filter-advance').toggle();
}); 

get_filter_info();
function get_filter_info()
{
    $.ajax({
                url: 'hr-app/index.php?action=allUserinfo_Search',
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


	fetch_users('no',type);
	
	function fetch_users(is_date_search,type, section=[],jobtitle=[],grade=[],shift=[],branchs=[], groub=[],name)
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
                      
					  { "bSortable": false },
					  { "bSortable": false }
					],
	
			
		 language: {
            url:'/dist/js/dataTables.arabic.json'
        }, 
		"ajax" : {
		url:"hr-app/index.php?action=contractRenewal-emp-list",
		 type:"POST",
		  data:{
            is_date_search:is_date_search,
            section: section,
            jobtitle: jobtitle,
            grade: grade,
            shift: shift,
            branchs: branchs,
            groub: groub,
            name:name,
            type:type
	

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
    fetch_users('yes',type,section,jobtitle,grade,shift,branchs,groub,name); // افحص مخرجات الداله
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








});
</script>