<?php
//$fix_foot = false;
// $screen = 'طلبات الاجازة  ';
// $page_title = 'إدارة الطلبات ';
$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة الاجازات';
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
.table-responsive {
    overflow-x: auto;
    overflow-y: visible;
}
#data_tb_wrapper,
#data_tb_wrapper .row,
#data_tb_wrapper .col-sm-12,
#data_tb_wrapper .dataTables_scroll,
#data_tb_wrapper .dataTables_scrollBody,
#data_tb tbody td {
    overflow: visible !important;
}
.action-dropdown-menu {
    min-width: 170px;
    text-align: right;
    direction: rtl;
    z-index: 999999;
}
</style>    
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">الطلبات</span>
          </div><!-- /.col -->
          <div class="col-5 text-left"> 
            <button type="button" class="btn btn-primary"  id="add-leaverequest-bt"><i class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة</span></button>          
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
                                  <th>عدد الأيام/الساعات</th> <!-- MODIFIED HEADER -->
                                  <th >حالة الاجازة</th>
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

$('#add-leaverequest-bt').on('click', function(event){
   window.open("leaveRequest-add","_self");
});


// هذه في حاله عندم وجود اي فلتره يتم اظهار المحتوى
clients_data('no');
// هذه الداله تستقبل متغيرات وتقوم بعرض المحتوى على datatable
 function clients_data(is_date_search, date_range='', states=[])
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
    url:"hr-app/index.php?action=leaveRequest-list",
    type:"POST",
    data:{
          is_date_search:is_date_search,
            date_range: date_range,
            states: states
    }
    },
   
    aoColumns: [
          
            {
                data: 'employee_name', // Corrected key based on backend logic
                render: function(data, type, row) {
                    return data;
                }
            },  
            {
                data: 'last_update', // Corrected key
                render: function(data, type, row) {
                    return '<small>'+(data || '')+'</small>';
                     
                }
            },
            {
                data: 'duration_display', // NEW KEY for combined days/hours
                render: function(data, type, row) {
                    return data || '0';
                     
                }
            },
            {
                data: 'status_text', // Corrected key
                className: 'col_states',
                render: function(data, type, row) {
                    return data;
                     
                }
            },
            {
                data: 'is_draft', // Corrected key
                className: 'col_states',
                render: function(data, type, row) {
                    return data == 1 ? 'نعم' : 'لا'; // Assuming 1=Draft
                     
                }
            },
               {
                data: 'creator_name', // Corrected key
                className: 'col_states',
                render: function(data, type, row) {
                    return data || 'غير محدد';
                     
                }
            },
            
              { 
               data: 'id',
              "bSortable": false,
              render: function(data, type, row) {
                // Use the actual draft status from the row object (assuming it's 'is_draft')
                const isDraft = row.is_draft; 
                
                 if( isDraft == 0) // Not a draft (submitted/processed)
                   return '<div class="btn-group"> <button type="button" class="btn btn-default" data-toggle="dropdown" data-boundary="viewport"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right action-dropdown-menu" role="menu"><a href="leaveRequest-view?id='+data+'" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a><a type="button" href="leaveRequest-add?id='+data+'" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a><button type="button" class="dropdown-item remove_client" value="'+data+'"><i class="fa fa-trash-alt"></i> حذف</button></div></div>';    
                    else
                    // Is a draft, maybe disable edit button or show 'تم الرفع' logic
                    return '<div class="btn-group"> <button type="button" class="btn btn-default" data-toggle="dropdown" data-boundary="viewport"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right action-dropdown-menu" role="menu"><a href="leaveRequest-view?id='+data+'" type="button" class="dropdown-item view-pos about_account"><i class="fa fa-eye"></i> عرض</a><a type="button" href="leaveRequest-add?id='+data+'" class="dropdown-item"><i class="fa fa-edit"></i> تعديل</a><button type="button" class="dropdown-item remove_client" value="'+data+'"><i class="fa fa-trash-alt"></i> حذف</button></div></div>';  
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
              $('#modal_default .modal-body').load('./hr-app/leaveRequest-remove?id='+id+'',function(){
            });
        }
    }
    
    

$(document).on('click', '.remove_client', function(){
    var id = $(this).val();
    confirm_remove(id);

});

(function() {
    function restoreActionMenus() {
        $('body > .dropdown-menu[data-action-menu]').each(function() {
            var $menu = $(this);
            var $parent = $menu.data('menu-parent');
            if ($parent && $parent.length) {
                $menu.css({
                    position: '',
                    top: '',
                    left: '',
                    right: '',
                    display: '',
                    zIndex: '',
                    minWidth: ''
                }).removeAttr('data-action-menu')
                  .removeData('menu-parent')
                  .detach()
                  .appendTo($parent);
            }
        });
    }

    $(document).on('shown.bs.dropdown', '#data_tb .btn-group', function() {
        restoreActionMenus();

        var $group = $(this);
        var $toggle = $group.children('[data-toggle="dropdown"]');
        var $menu = $group.children('.dropdown-menu');
        if (!$toggle.length || !$menu.length) {
            return;
        }

        var rect = $toggle[0].getBoundingClientRect();
        var minWidth = Math.max($menu.outerWidth() || 170, 170);
        var left = rect.right - minWidth;

        if (left < 10) {
            left = 10;
        }

        if ((left + minWidth) > (window.innerWidth - 10)) {
            left = window.innerWidth - minWidth - 10;
        }

        $menu.attr('data-action-menu', '1')
            .data('menu-parent', $group)
            .detach()
            .appendTo('body')
            .css({
                position: 'fixed',
                top: (rect.bottom + 4) + 'px',
                left: left + 'px',
                right: 'auto',
                display: 'block',
                zIndex: '999999',
                minWidth: minWidth + 'px'
            });
    });

    $(document).on('hide.bs.dropdown', '#data_tb .btn-group', function() {
        restoreActionMenus();
    });

    $(document).on('click touchstart', function(e) {
        if (!$(e.target).closest('#data_tb .btn-group').length &&
            !$(e.target).closest('body > .dropdown-menu[data-action-menu]').length) {
            restoreActionMenus();
        }
    });
})();
 
  
});
 

</script>
