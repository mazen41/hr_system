<?php
//$fix_foot = false;
$screen = 'استقالة الموظف ';
$page_title = 'إدارة الاستقالة ';
 include_once('inc/header.php'); // Ensure this path is correct
 $allowed_branches = $User->allBranches($User->branches); // Assuming $User is an instantiated class/object
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Your existing styles */
.table.dataTable{
  margin-top: 0px !important;
}
.table.dataTable  td, .table.dataTable  th {
  vertical-align: middle;
}

/* Custom styles for better UI */
.content-header.page-nav {
    background: #f8f9fa;
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
}
.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #343a40;
}
.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}
.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}
.invoice.mb-5 {
    border-radius: .5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    padding: 1.5rem;
    background-color: #fff;
}
.card-body.card-body.pt-0.pb-0 {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}
.form-group label {
    font-weight: 500;
    color: #343a40;
}
.btn-default {
    background-color: #e9ecef;
    border-color: #e9ecef;
    color: #343a40;
}
.btn-default:hover {
    background-color: #dae0e5;
    border-color: #dae0e5;
}
.card {
    border-radius: .5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}
.bg-gry { /* Assuming this means a light gray background for table header */
    background-color: #f2f2f2;
    color: #343a40;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5em 0.75em;
    border-radius: 0.25rem;
    margin: 0 0.25em;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: #fff !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e2e6ea !important;
    border-color: #e2e6ea !important;
    color: #343a40 !important;
}
/* Select2 specific styling for consistency */
.select2-container--default .select2-selection--single {
    height: calc(2.25rem + 2px);
    border: 1px solid #ced4da;
    border-radius: .25rem;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem + 2px);
    padding-right: .75rem; /* For RTL */
    padding-left: .75rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem + 2px);
    top: 0;
}
</style>

    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title">الاستقالة</span>
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
                                    <select class="form-control select2" data-width="100%"  title="أي" id="filter_status" name="filter_status">
                                        <option value="">كل الحالات</option>
                                        <option value="1">معتمد</option>
                                        <option value="2">غير معتمد</option>
                                        <option value="0">مسودة/معلق</option> <!-- Assuming 0 for draft/pending -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="text-left mt-3">
                            <button type="button" class="btn btn-default reset-filter" data-dismiss="">إلغاء الفلتر</button>
                            <button type="submit" class="btn btn-success" name="" ><i class="fas fa-eye"></i> بحث</button>
                        </div>
                    </div>
                </form>
                <div class="overlay" style="display:none" ><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>
            </div> 

            <div class="row">
                <div class="col-md-12">
          <div class="card">
                        <div class="table-responsive">
                            <table id="data_tb" class="table dataTable table-hover dtr-inline collapsed nowrap display table-sm" width="100%">
                                <thead>
                                    <tr class="bg-gry">
                                        <th>اسم الموظف</th>
                                        <th>تاريخ الاستقالة</th> <!-- Changed from 'آخر تحديث' to 'تاريخ الاستقالة' for better relevance -->
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                        <th>أنشئ بواسطة</th>
                                        <th>الإجراءات</th>
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
 include_once('inc/footer.php'); // Ensure this path is correct
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- You might need a date range picker library for .input-date-range -->
<!-- Example:daterangepicker -->
<!-- <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script> -->
<!-- <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" /> -->


<script>
$(document).ready(function(){

    // Initialize Select2 for status filter
    $('#filter_status').select2({
        placeholder: "اختر حالة...",
        allowClear: true
    });

    // Initialize Date Range Picker (if you have one)
    // Example for daterangepicker:
    // $('.input-date-range').daterangepicker({
    //     autoUpdateInput: false,
    //     locale: {
    //         cancelLabel: 'Clear',
    //         applyLabel: 'Apply',
    //         format: 'YYYY-MM-DD',
    //         direction: 'rtl' // For RTL language
    //     }
    // });
    // $('.input-date-range').on('apply.daterangepicker', function(ev, picker) {
    //     $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    // });
    // $('.input-date-range').on('cancel.daterangepicker', function(ev, picker) {
    //     $(this).val('');
    // });


    // Reset Filter Functionality
    $(document).on('click', '.reset-filter', function(){
        $('#filter-fm')[0].reset(); // Reset form fields
        $('.select2').val(null).trigger('change'); // Reset Select2 fields
        $('#data_tb').DataTable().destroy(); // Destroy current DataTable instance
        clients_data('no'); // Reload DataTable without filters
    });

    // Apply Filters Functionality
    function apply_filters() {
        var date_range = $('#date_range').val();
        var status_filter = $('#filter_status').val(); // Get value from Select2
        $('#data_tb').DataTable().destroy();
        clients_data('yes', date_range, status_filter); // Pass status_filter
    }

    $('#filter-fm').on('submit', function(e){  
        e.preventDefault();
        apply_filters();
    });

    $('#add-advances-bt').on('click', function(event){
       window.open("resignation-add-add","_self"); // Ensure this path is correct
    });

    // Initial load of DataTables
    clients_data('no');

    // DataTable Initialization Function
    function clients_data(is_date_search, date_range='', status_filter='') // Renamed states to status_filter for clarity
    {
        var dataTable = $('#data_tb').DataTable({
            "processing" : true,
            "serverSide" : true, 
            "paging": true,
            "lengthChange": true,
            "searching": false, // Keep false if you use custom filters
            "order" : [[1, 'desc']], // Order by DueDate (index 1) descending by default
            "ordering": true,
            "info": true, // Show info like "Showing 1 to X of Y entries"
            "autoWidth": false,
            "responsive": true,
            "pagingType": "full_numbers", // More complete pagination
            language: {
                url: '/dist/js/dataTables.arabic.json' // Ensure this path is correct
            },
            "ajax" : {
                url:"hr-app/index.php?action=resignation-list", // This URL points to your backend
                type:"POST",
                data:{
                    is_date_search:is_date_search,
                    date_range: date_range,
                    states: status_filter // Send the single status value
                },
                error: function (xhr, error, thrown) {
                    console.error("DataTables AJAX Error:", error, thrown);
                    toastr.error("حدث خطأ أثناء تحميل البيانات: " + thrown);
                }
            },
            aoColumns: [
                { data: 'employee' }, // Directly use 'employee' from backend
                { data: 'due_date' }, // Directly use 'due_date' from backend
                { data: 'reason' }, // Directly use 'reason' from backend
                {
                    data: 'status', // Use 'status' from backend
                    render: function(data, type, row) {
                        // Customize display based on status value (0, 1, 2)
                        if (data === '1') {
                            return '<span class="badge badge-success">معتمد</span>';
                        } else if (data === '2') {
                            return '<span class="badge badge-danger">غير معتمد</span>';
                        } else {
                            return '<span class="badge badge-warning">مسودة / معلق</span>';
                        }
                    }
                },
                { data: 'created_by_name', defaultContent: '-' }, // Assuming you will add created_by_name to backend output later
                { 
                    data: 'id', // Use 'id' for actions
                    orderable: false, // Actions column not sortable
                    render: function(data, type, row) {
                        let actionsHtml = `<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-left">`;

                        // View action
                        actionsHtml += `<a href="resignation-view?id=${row.id}" type="button" class="dropdown-item view-pos"><i class="fa fa-eye text-info"></i> عرض</a>`;

                        // Edit and Delete based on status. Assuming status 0 means editable (draft/pending)
                        if (row.status === '0') { // Check if status is 0 (draft/pending)
                            actionsHtml += `<a href="resignation-add-add?id=${row.id}" type="button" class="dropdown-item"><i class="fa fa-edit text-primary"></i> تعديل</a>`;
                            actionsHtml += `<button type="button" class="dropdown-item remove_client" value="${row.id}"><i class="fa fa-trash-alt text-danger"></i> حذف</button>`;
                        } else {
                            actionsHtml += `<span class="dropdown-item text-muted"><i class="fa fa-edit"></i> تم الرفع / لا يمكن التعديل</span>`;
                            actionsHtml += `<button type="button" class="dropdown-item remove_client" value="${row.id}"><i class="fa fa-trash-alt text-danger"></i> حذف</button>`;
                        }

                        actionsHtml += `</div></div>`;
                        return actionsHtml;
                    }
                }
            ],
             // Buttons for export (optional, adjust as needed)
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-left'B>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-success',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3, 4 ] // Exclude action column
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3, 4 ] // Exclude action column
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> طباعة',
                    className: 'btn btn-sm btn-info',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3, 4 ] // Exclude action column
                    }
                }
            ]
        });
    }
  
    // Function to confirm removal using SweetAlert2
    function confirm_remove (id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احذفه!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'hr-app/index.php?action=resignation-remove', // Assuming this is your delete endpoint
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.result) {
                            Swal.fire(
                                'تم الحذف!',
                                response.msg,
                                'success'
                            );
                            $('#data_tb').DataTable().ajax.reload(); // Reload DataTable
                        } else {
                            Swal.fire(
                                'خطأ!',
                                response.msg,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('حدث خطأ أثناء معالجة طلبك: ' + error);
                    }
                });
            }
        });
    }
    
    // Attach click listener for remove button
    $(document).on('click', '.remove_client', function(){
        var id = $(this).val();
        confirm_remove(id);
    });
});
</script>