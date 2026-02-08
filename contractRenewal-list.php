<?php
$appid  = 'HR';
$page_perm=['اضافة عقد','عرض عقد','تعديل عقد','حذف عقد'];

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة تجديد العقود';
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
            <span class="page-title"><?=$screen?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
                                  <?php if($User->isAllowedPerm(['اضافة عقد'],$appid)){ ?>
<button type="button" class="btn btn-primary"  id="add-contract-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> تجديد عقد موظف</span></button>			
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

    <div class="invoice mb-5" id="filter-area" >
	
    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">

        
            <div class="card-body card-body pt-0 pb-0">
            <div class="row">
            
            
            <div class="col-md-3">
              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
            </div>

            
            
            <div class="col-md-3">
          <div class="form-group">
              <label for="branchs_list" class="col-form-label">الفرع</label>
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
                <label for="filter_status" class="col-form-label">الحالة</label>
                  <select class=" selectpicker show-tick" data-width="100%"  title="أي" id="filter_status" name="filter_status">
                    <option value="1">معتمد</option>
                    <option value="2">غير معتمد</option>
                  </select>
              </div>
                  </div>

            

            
            </div>
            <div class="text-left">
              <button type="button" class="btn btn-default reset-filter" data-dismiss="">إلغاء الفلتر</button>
              <button type="submit" class="btn btn-success" name="" ><i class="fas fa-eye"></i> بحث</button>
            </div>
            </div>
            
            
        
        
      

          <!-- /.row -->
          </form>
          <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
    </div> 

	
	

			
			
		
				
				<div class="row">
                
                <div class="col-md-12">
					 <div class="card">
					<div class="table-responsive">
						<table id="data_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm" width="100%">
							 <thead>
                                 <tr class="bg-gry">
                                  <th>اسم الموظف</th>
                                  <th >الفرع</th>
                                  <th>منشئ بواسطة<br>آخر تحديث</th>
                                  <th >حالة التجديد</th>
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


//
$('#add-contract-bt').on('click', function(event){
   window.open("contractRenewal-emp-list?type=contract", "_self");
});


//هذه من اجل الغاء الفلتره
$(document).on('click', '.reset-filter', function(){
    $('#filter-fm').each(function() {
    $("input").val('');
    $(".selectpicker").val('');
    $("#branchs_list").val('');
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
    var branchs = get_filter('branchs_list');
    var state = $('#filter_status').val();
    $('#data_tb').DataTable().destroy();
    clients_data('yes',date_range,branchs,state); // افحص مخرجات الداله
}
// هذه عندما نطبق الفلتر
$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});



// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
 function clients_data(is_date_search, date_range='',branchs=[], states=[])
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
    url:"./hr-app/contractRenewal-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            branchs: branchs,
            states: states
	}
	},
   
    aoColumns: [
		  
			{
                data: ['data'],
                render: function(data, type) {
					return data.name;
                }
            },  
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.branch;
                     
                }
            },
            {
                data: ['data'],
                render: function(data, type) {
					return '<small>'+data.user+'<br>'+data.updated+'</small>';
                     
                }
            },
            {
                data: ['data'],
                className: 'col_states',
                render: function(data, type) {
					return data.statedevice;
                     
                }
            },
			
			  { 
			   data: ['data'],
			  "bSortable": false,
			  render: function(data, type) {
				 

          
                    var options='<div class="btn-group"> <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu">';
                <?php if ($User->isAllowedPerm(['عرض عقد'], $appid)) { ?>
                    options += '<a href="contractRenewal-view?id=' + data.id + '" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['تعديل عقد'], $appid)) { ?>
                    options += '<a href="contractRenewal-add?edit=' + data.id + '" type="button" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['حذف عقد'], $appid)) { ?>
                    options += '<button type="button" class="dropdown-item remove_client" value="' + data.id + '"><i class="fa fa-trash-alt"></i> حذف</button>';
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
  
function confirm_remove (id) {
		if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/contractRenewal-remove?id='+id+'',function(){
				 // $('#modal_default .modal-dialog').addClass('modal-md');
			});
		}
	}
    
    

$(document).on('click', '.remove_client', function(){
	var id = $(this).val();
	confirm_remove(id);

});
 
  
});
 

</script>