<?php
//$fix_foot = false;
// $screen = 'سلفة الموظف ';
// $page_title = 'إدارة السلف ';

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة السلف ';

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
.advance-action-menu{
    min-width: 220px;
}
.card,
.table-responsive,
#data_tb_wrapper,
#data_tb_wrapper .row,
#data_tb_wrapper .col-sm-12{
    overflow: visible !important;
}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">السلف</span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-primary"  id="add-advances-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة</span></button>			
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
            
            
            <div class="col-md-6">
              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
            </div>

            <div class="col-md-6">
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
                                  <th>آخر تحديث</th>
                                  <th>الملبغ</th>
                                  <th >حالة السلفة</th>
                                  <th >مسودة او لا</th>
                                  <th >انشئ بواسطة</th>
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>

$(document).ready(function(){
//هذه من اجل الغاء الفلتره
$(document).on('click', '.reset-filter', function(){
    $('#filter-fm').each(function() {
    $("input").val('');
    $(".selectpicker").val('');
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
    $('#data_tb').DataTable().destroy();
    clients_data('yes',date_range,state); // افحص مخرجات الداله
}
// هذه عندما نطبق الفلتر
$('#filter-fm').on('submit', function(e){  
    e.preventDefault();
    apply_filters();
});

$('#add-advances-bt').on('click', function(event){
   window.open("EmpAdvances-add","_self");
});


// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
var advancesTable = null;
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
 function clients_data(is_date_search, date_range='', states=[])
 { 
	//var account = 0;
  advancesTable = $('#data_tb').DataTable({
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
    url:"hr-app/index.php?action=EmpAdvances-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            states: states
	}
	},
   
    aoColumns: [
		  
			{
                data: 'name',
                render: function(data, type, row) {
					return data || '';
                }
            },  
            {
                data: 'createddate',
                render: function(data, type, row) {
					return '<small>'+(data || '')+'</small>';
                }
            },
            {
                data: 'amount',
                render: function(data, type, row) {
					return data || '';
                }
            },
            {
                data: 'status',
                className: 'col_states',
                render: function(data, type, row) {
					if(data == 1) return '<span class="badge badge-success">معتمد</span>';
					if(data == 2) return '<span class="badge badge-danger">مرفوض</span>';
					return '<span class="badge badge-warning">معلق</span>';
                }
            },
            {
                data: 'draft',
                className: 'col_states',
                render: function(data, type, row) {
					return data == 1 ? '<span class="badge badge-info">مرفوع</span>' : '<span class="badge badge-secondary">مسودة</span>';
                }
            },
            {
                data: 'createdby',
                className: 'col_states',
                render: function(data, type, row) {
					return data || '';
                }
            },
			
			  { 
			   data: 'id',
			  "bSortable": false,
			  render: function(data, type, row) {
                 var html = '<div class="btn-group">';
                 html += '<button type="button" class="btn btn-default" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-h"></i></button>';
                 html += '<div class="dropdown-menu dropdown-menu-left advance-action-menu" role="menu">';
                 html += '<a href="EmpAdvances-view?id='+data+'" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a>';
                 if((row.status == null || row.status === '' || Number(row.status) === 0) && Number(row.draft) === 1){
                    html += '<a type="button" href="EmpAdvances-add?id='+data+'" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a>';
                    html += '<button type="button" class="dropdown-item approve-advance" value="'+data+'"><i class="fa fa-check text-success"></i> اعتماد</button>';
                    html += '<button type="button" class="dropdown-item reject-advance" value="'+data+'"><i class="fa fa-times text-danger"></i> رفض</button>';
                 }
                 html += '<button type="button" class="dropdown-item remove_client" value="'+data+'"><i class="fa fa-trash-alt"></i> حذف</button>';
                 html += '</div></div>';
                 return html;
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
			  $('#modal_default .modal-body').load('hr-app/index.php?action=EmpAdvances-remove-modal&id='+id,function(){
			});
		}
	}

function reloadAdvancesTable() {
    if ($.fn.DataTable.isDataTable('#data_tb')) {
        $('#data_tb').DataTable().ajax.reload(null, false);
    }
}

function processAdvanceDecision(id, status, comment) {
    $.ajax({
        url: 'hr-app/index.php?action=direct-approve',
        type: 'POST',
        dataType: 'json',
        data: {
            type: 'advance',
            id: id,
            status: status,
            comment: comment || ''
        },
        success: function(res) {
            if (res && res.result) {
                toastr.success(status === 1 ? 'تم اعتماد السلفة بنجاح' : 'تم رفض السلفة بنجاح');
                reloadAdvancesTable();
            } else {
                toastr.error((res && res.msg) ? res.msg : 'تعذر تنفيذ العملية');
            }
        },
        error: function() {
            toastr.error('حدث خطأ أثناء تنفيذ العملية');
        }
    });
}
    
    

$(document).on('click', '.remove_client', function(){
	var id = $(this).val();
	confirm_remove(id);

});

$(document).on('click', '.approve-advance', function(){
    var id = $(this).val();
    Swal.fire({
        title: 'اعتماد السلفة',
        text: 'هل تريد اعتماد طلب السلفة هذا؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'اعتماد',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#28a745'
    }).then(function(result){
        if (result.isConfirmed) {
            processAdvanceDecision(id, 1, '');
        }
    });
});

$(document).on('click', '.reject-advance', function(){
    var id = $(this).val();
    Swal.fire({
        title: 'رفض السلفة',
        input: 'textarea',
        inputLabel: 'سبب الرفض',
        inputPlaceholder: 'اكتب سبب الرفض هنا...',
        inputAttributes: {
            'aria-label': 'سبب الرفض'
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'رفض',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545'
    }).then(function(result){
        if (result.isConfirmed) {
            processAdvanceDecision(id, 2, result.value || '');
        }
    });
});

$(document).on('shown.bs.dropdown', '#data_tb .btn-group', function(){
    var $group = $(this);
    var $menu = $group.find('.dropdown-menu');
    if (!$menu.length) {
        return;
    }

    var rect = $group[0].getBoundingClientRect();
    $('body > .dropdown-menu[data-advance-menu="1"]').remove();

    $menu.attr('data-advance-menu', '1')
        .data('origin-group', $group)
        .detach()
        .appendTo('body')
        .css({
            position: 'fixed',
            top: (rect.bottom + 4) + 'px',
            left: Math.max(12, rect.left) + 'px',
            right: 'auto',
            display: 'block',
            zIndex: 999999,
            minWidth: '220px'
        })
        .addClass('show');
});

function restoreAdvanceMenus() {
    $('body > .dropdown-menu[data-advance-menu="1"]').each(function(){
        var $menu = $(this);
        var $group = $menu.data('origin-group');
        if ($group && $group.length) {
            $menu.removeAttr('style')
                .removeAttr('data-advance-menu')
                .removeClass('show')
                .detach()
                .appendTo($group);
        }
    });
}

$(document).on('hide.bs.dropdown', '#data_tb .btn-group', function(){
    restoreAdvanceMenus();
});

$(document).on('click touchstart', function(e){
    if (!$(e.target).closest('#data_tb .btn-group').length &&
        !$(e.target).closest('body > .dropdown-menu[data-advance-menu="1"]').length) {
        restoreAdvanceMenus();
    }
});
 
  
});
 

</script>
