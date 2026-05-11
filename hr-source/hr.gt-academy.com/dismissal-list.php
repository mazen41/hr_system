<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
 include_once('inc/header.php');
 $allowed_branches = $User->allBranches($User->branches);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style> 
/* Basic styles for a cleaner look */
.table.dataTable { margin-top: 0px !important; }
.table.dataTable td, .table.dataTable th { vertical-align: middle; }
.content-header.page-nav { background: #f8f9fa; padding: 15px 0; border-bottom: 1px solid #dee2e6; margin-bottom: 20px; }
.page-title { font-size: 1.75rem; font-weight: 600; color: #343a40; }
.invoice.mb-5 { border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; padding: 1.5rem; background-color: #fff; }
.card { border-radius: .5rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; }
.bg-gry { background-color: #f2f2f2; color: #343a40; }
.select2-container--default .select2-selection--single { height: calc(2.25rem + 2px); border: 1px solid #ced4da; border-radius: .25rem; }
</style>  

    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">فصل الموظفين</span>
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
                    <div class="card-body pt-0 pb-0">
                        <div class="row">
                            <div class="col-md-6">
                              <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
                              <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label for="filter_status" class="col-form-label">الحالة</label>
                                  <select class="form-control select2" data-width="100%" id="filter_status" name="filter_status">
                                    <option value="">كل الحالات</option>
                                    <option value="1">معتمد</option>
                                    <option value="2">غير معتمد</option>
                                    <option value="0">مسودة/معلق</option>
                                  </select>
                              </div>
                            </div>
                        </div>
                        <div class="text-left mt-3">
                            <button type="button" class="btn btn-default reset-filter">إلغاء الفلتر</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-eye"></i> بحث</button>
                        </div>
                    </div>
                </form>
            </div> 

            <div class="row">
                <div class="col-md-12">
           <div class="card">
                        <div class="table-responsive">
                            <table id="data_tb" class="table dataTable table-hover dtr-inline nowrap display table-sm" width="100%">
                                <thead>
                                    <tr class="bg-gry">
                                      <th>اسم الموظف</th>
                                      <th>آخر تحديث</th>
                                      <th>حالة الفصل</th>
                                      <th>مسودة او لا</th>
                                      <th>انشئ بواسطة</th>
                                      <th>الاجراءات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
          </div>
                </div>
            </div>
        </div>
    </section>

<?php
 include_once('inc/footer.php');
?>
<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Include Date Range Picker if needed -->

<script>
$(document).ready(function(){
    
    $('#filter_status').select2({
        placeholder: "اختر حالة...",
        allowClear: true
    });
    
    // Reset Filter
    $(document).on('click', '.reset-filter', function(){
        $('#filter-fm')[0].reset();
        $('.select2').val(null).trigger('change');
        $('#data_tb').DataTable().destroy();
        clients_data('no');
    });

    // Apply Filter
    $('#filter-fm').on('submit', function(e){  
        e.preventDefault();
        var date_range = $('#date_range').val();
        var state = $('#filter_status').val();
        $('#data_tb').DataTable().destroy();
        clients_data('yes', date_range, state);
    });

    // Add Button
    $('#add-advances-bt').on('click', function(event){
       window.open("dismissal-add","_self"); // Make sure this is the correct URL for adding a dismissal
    });


    // Initial DataTables Load
    clients_data('no');
    
    function clients_data(is_date_search, date_range='', states='')
    {
        var dataTable = $('#data_tb').DataTable({
            "processing" : true,
            "serverSide" : true, 
            "paging": true,
            "lengthChange": true,
            "searching": false,
            "order" : [[1, 'desc']], // Default order by 'last update' descending
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pagingType": "full_numbers",
            language: {
                url: '/dist/js/dataTables.arabic.json' // Ensure path is correct
            },
            "ajax" : {
                url:"hr-app/index.php?action=dismissal-list",
                type:"POST",
                data:{
                    is_date_search:is_date_search,
                    date_range: date_range,
                    states: states
                }
            },
            "columns": [ // Use "columns" instead of "aoColumns" for modern DataTables
                { "data": "name" },
                { "data": "updated", render: function(data) { return `<small>${data}</small>`; } },
                { "data": "statedevice", render: function(data) {
                    if (data === '1') return '<span class="badge badge-success">معتمد</span>';
                    if (data === '2') return '<span class="badge badge-danger">غير معتمد</span>';
                    return '<span class="badge badge-warning">مسودة/معلق</span>';
                }},
                { "data": "draft", render: function(data) {
                    return data === '1' ? '<span class="badge badge-info">نعم</span>' : '<span class="badge badge-secondary">لا</span>';
                }},
                { "data": "name_add" },
                { 
                    "data": "id",
                    "orderable": false,
                    "render": function(data, type, row) { // 'row' contains the full data object for the row
                        let actions = `<div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm" data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button>
                                        <div class="dropdown-menu dropdown-menu-left" role="menu">
                                            <a href="resignation-view-add?id=${data}" class="dropdown-item"><i class="fa fa-eye text-info"></i> عرض</a>`;
                        
                        if(row.status == 0) { // Check if status is pending/draft
                           actions += `<a href="dismissal-add?id=${data}" class="dropdown-item"><i class="fa fa-edit text-primary"></i> تعديل</a>
                                       <button type="button" class="dropdown-item remove_client" value="${data}"><i class="fa fa-trash-alt text-danger"></i> حذف</button>`;
                        } else {
                           actions += `<span class="dropdown-item text-muted"><i class="fa fa-edit"></i> تم الرفع</span>
                                       <button type="button" class="dropdown-item remove_client" value="${data}"><i class="fa fa-trash-alt text-danger"></i> حذف</button>`;
                        }
                        
                        actions += `</div></div>`;
                        return actions;
                    }
                }
            ],
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    }
    
    // Delete confirmation
    $(document).on('click', '.remove_client', function(){
        var id = $(this).val();
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم حذف سجل الفصل هذا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // Assuming you have a delete endpoint
                $.post('hr-app/index.php?action=dismissal-remove', { id: id }, function(response) {
                    if(response.result){
                        Swal.fire('تم الحذف!', 'تم حذف السجل بنجاح.', 'success');
                        $('#data_tb').DataTable().ajax.reload();
                    } else {
                        Swal.fire('خطأ!', 'فشل حذف السجل.', 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>