<?php
$appid  = 'HR';
$page_perm=['اضافة مجموعه','عرض مجموعه','تعديل مجموعه','حذف مجموعه'];

// $screen = 'المجموعات';
// $page_title = 'إدارة المجموعات';

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>


<style>	
.table.dataTable{
	margin-top: 0px !important;
}
.table.dataTable  td, .table.dataTable  th {
	vertical-align: middle;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .page-nav .row {
        flex-direction: column;
    }
    
    .page-nav .col-7,
    .page-nav .col-5 {
        width: 100% !important;
        text-align: center !important;
        margin-bottom: 10px;
    }
    
    .page-nav .btn {
        margin: 5px;
        min-width: 120px;
    }
    
    .card-body .row {
        flex-direction: column;
    }
    
    .card-body .col-md-3 {
        width: 100% !important;
        margin-bottom: 15px;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .dropdown-menu {
        position: fixed !important;
        left: 10px !important;
        right: 10px !important;
        width: auto !important;
        min-width: 200px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 5px;
    }
    
    .card {
        margin: 5px;
    }
    
    .table-responsive {
        border: none;
    }
    
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
        text-align: center;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_paginate {
        justify-content: center;
    }
}

/* Mobile-first table improvements */
.table td {
    word-wrap: break-word;
    white-space: normal;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Fix dropdown on mobile */
@media (max-width: 991px) {
    .dropdown-menu {
        transform: none !important;
        top: auto !important;
        left: 0 !important;
        right: 0 !important;
        margin: 10px;
    }
}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row d-flex justify-content-between align-items-center">
          <div class="col-12 col-md-7">
            <h1 class="page-title h4 mb-0">المجموعات</h1>
          </div><!-- /.col -->
          <div class="col-12 col-md-5 text-md-left text-center">	
          <?php if($User->isAllowedPerm(['اضافة مجموعه'],$appid)){ ?>
            <button type="button" class="btn btn-primary btn-sm mx-1 mb-2"  id="add-client-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة</span></button>	
            <button type="button" class="btn btn-primary btn-sm mx-1 mb-2"  id="add-client-bt-setting"><i class="fas fa-cog"></i><span class="d-none d-sm-inline"> قائمة الاعدادات</span></button>		
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

    <div class="invoice mb-3" id="filter-area" >
	
    <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">

        
            <div class="card-body card-body pt-3 pb-3">
            <div class="row align-items-end">
            
            
            <div class="col-12 col-md-3">
              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
            </div>

            
            
            
            <div class="col-12 col-md-3">
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

            <div class="col-12 col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-default btn-sm reset-filter" data-dismiss="">إلغاء الفلتر</button>
                        <button type="submit" class="btn btn-success btn-sm" name="" ><i class="fas fa-eye"></i> بحث</button>
                    </div>
                </div>
            </div>

            
            </div>
            </div>
            
        
        
      

          <!-- /.row -->
          </form>
          <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
    </div> 

	
		
				
		
				
				<div class="row">
                
                <div class="col-12">
					 <div class="card shadow-sm">
					<div class="table-responsive">
						<table id="data_tb" class="table table-hover table-striped" width="100%">
							 <thead>
                                 <tr class="bg-light">
                                  <th data-priority="1">اسم المجموعة</th>
                                  <th data-priority="2">الفرع</th>
                                  <th data-priority="3">منشئ بواسطة<br>آخر تحديث</th>
                                  <th data-priority="4" class="text-center">الاجراءات</th>

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
<!-- DataTables loaded from CDN in footer.php -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script>

$(document).ready(function(){
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
    var state = get_filter('filter_status');
    $('#data_tb').DataTable().destroy();
    clients_data('yes',date_range,branchs,state); // افحص مخرجات الداله
}
// هذه عندما نطبق الفلتر
$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});

$('#add-client-bt').on('click', function(event){
   window.open("groub-add","_self");
});

$('#add-client-bt-setting').on('click', function(event){
   window.open("hr-setting","_self");
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
    "order" : [[0, 'desc']],
    "ordering": true,
    "info": true,
    "autoWidth": false,
    "responsive": {
        details: {
            type: 'column',
            target: -1
        }
    },
    "pagingType": "full_numbers",
    "pageLength": 25,
    "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
    language: {
            url: '/dist/js/dataTables.arabic.json',
            search: "بحث:",
            lengthMenu: "عرض _MENU_ سجلات",
            info: "عرض السجل _START_ إلى _END_ من إجمالي _TOTAL_ سجل",
            paginate: {
                first: "الأول",
                last: "الأخير",
                next: "التالي",
                previous: "السابق"
            }
        },
   "ajax" : {
    url:"hr-app/index.php?action=groub-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            branchs: branchs,
            states: states
	}
	},
   
    columns: [
		  
			{
                data: 0,
                render: function(data, type, row) {
					return data || '';
                }
            },  
            {
                data: 1,
                className: 'text-nowrap',
                render: function(data, type, row) {
					return data || '';
                     
                }
            },
            {
                data: 2,
                render: function(data, type, row) {
					return '<small class="text-muted">'+(data || '')+'</small>';
                     
                }
            },
			
			  { 
			   data: 3,
			  "bSortable": false,
			  "className": "text-center",
			  render: function(data, type, row) {
				 
          
          var options='<div class="btn-group dropleft"> <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu" role="menu">';
                <?php if ($User->isAllowedPerm(['عرض مجموعه'], $appid)) { ?>
                    options += '<a href="groub-view?id=' + row[0] + '" class="dropdown-item"><i class="fa fa-eye"></i> عرض</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['تعديل مجموعه'], $appid)) { ?>
                    options += '<a href="groub-add?id=' + row[0] + '" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a>';
                <?php } ?>

                <?php if ($User->isAllowedPerm(['حذف مجموعه'], $appid)) { ?>
                    options += '<button type="button" class="dropdown-item remove_client" value="' + row[0] + '"><i class="fa fa-trash-alt"></i> حذف</button>';
                <?php } ?>

                options+='</div></div>'
                   return options;  

                }
				
			
			  }
			
			
			
		], 
   
    "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
    
    "<'row'<'col-sm-12'tr>>" +
    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    
	


  });

};
  
function confirm_remove (id) {
		if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/groub-remove?id='+id+'',function(){
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