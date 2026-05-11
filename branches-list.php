<?php
$screen = 'الفروع';
$page_title = 'إدارة الفروع';
$only_main_branch = true;
include_once('inc/header.php');

?>


<style>
    .table.dataTable {
        margin-top: 0px !important;
    }

    .table.dataTable td {
        vertical-align: middle;
    }

    .table thead th {
        border-bottom: none !important;
    }

    .filter-advance {
        display: none
    }
</style>
<div class="content-header page-nav">
    <div class="container-fluid ">
        <div class="row ">
            <div class="col-7">
                <span class="page-title">إدارة الفروع</span>
            </div><!-- /.col -->
            <div class="col-5 text-left">

                <a href="branches-add" type="button" class="btn btn-success" id="add-bt"><i
                        class="fas fa-plus"></i><span class="d-none d-sm-inline"> إضافة فرع</span></a>




            </div>
        </div>
    </div>
</div>




<section class="content">


    <div class="container-fluid">
        <?php if (isset($_SESSION['alert']) && !empty($_SESSION['alert'])): ?>
            <div class="alert alert-success alert-dismissible" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-check"></i>
                <?= $_SESSION['alert'] ?>
                <?php $_SESSION['alert'] = ''; ?>
            </div>
        <?php endif; ?>

        <div class="row">

            <div class="col-md-12">
                <div class="card" id="suppliers-containr">



                    <div class="table-responsive">
                        <table id="table_data"
                            class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm"
                            width="100%">
                            <thead>
                                <tr class="bg-gry">
                                    <th width="70%">الفرع</th>
                                    <th class="text-left" width="20%">تاريخ الانشاء</th>
                                    <th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>

                        </table>
                    </div>

                    <div class="overlay" style="display:none"><i class="fas fa-3x fa-sync-alt fa-spin"></i>
                        <div class="text-bold pt-2">جاري التحميل ...</div>
                    </div>
                </div>
            </div>
            <!-- /.col -->
        </div>



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

<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>-->

<script>

    $(document).ready(function () {


        getTableData();
        function getTableData() {
            //var account = 0;
            var dataTable = $('#table_data').DataTable({
                "processing": true,
                "serverSide": true,
                "paging": true,
                "lengthChange": false,

                "searching": false,
                "order": [],
                "ordering": false,
                "info": false,
                "autoWidth": false,
                "responsive": true,

                //"pagingType": "simple",
                language: {
                    url: '/dist/js/dataTables.arabic.json'
                },
                "ajax": {
                    //url:"./financial-app/incomes-tb",
                    url: "./hr-app/index.php?action=branches-list",
                    type: "POST",
                    data: {}
                },

                aoColumns: [

                    {
                        data: ['data'],
                        "bSortable": false,
                        render: function (data, type) {
                            let status = '';
                            let defaults = '';
                            if (data.stopped >= 1) {
                                status = ' <span class="right badge badge-danger">موقف</span> ';
                            };
                            if (data.defaults > 0) {
                                return '<div class="">' + data.no + ' - <b>' + data.name + '</b> ' + status + '</div> ';
                            } else {
                                return '<div class="pointer" onClick="window.location.href =\'branches-add?id=' + data.id + '\';">' + data.no + ' - <b>' + data.name + '</b> ' + status + '</div> ';
                            }

                        }
                    },


                    {
                        data: ['data'],
                        "bSortable": false,
                        render: function (data, type) {
                            if (data.defaults == 1) {
                                return '<div class="right badge badge-primary">المركز الرئيسي</div>';
                            } else {
                                return '<div class="text-left ltr"><small class="text-muted">' + data.created + '</small></div>';
                            }
                        }
                    },




                    {
                        data: ['data'],
                        "bSortable": false,
                        render: function (data, type) {
                            let statues = '';
                            if (data.stopped >= 1) {
                                statues = '<button type="button" class="dropdown-item close-trash-alt change_status" data-statues="active" value="' + data.id + '"><i class="fas fa-user-check"></i> إعادة التفعيل</button>';
                            } else {
                                if (data.defaults != 1) {
                                    statues = '<button type="button" class="dropdown-item close-trash-alt change_status" data-statues="stop" value="' + data.id + '"><i class="fas fa-ban"></i> إيقاف الفرع</button>';
                                }
                            }


                            return '<div class="text-left pl-3"><div class="btn-group"> <button type="button" class="btn btn-default " data-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-left" role="menu"><a href="branches-add?id=' + data.id + '" type="button" class="dropdown-item close-trash-alt"><i class="fa fa-edit"></i> تعديل</a>' + statues + '</div></div></div>';

                        }



                    }


                ],



                drawCallback: function (settings) {
                    if (settings.json.display_names) {
                        $('.tdbranch').addClass('badge badge-light');
                        //$('.tdbranch').show();
                    }
                }

            });


        }

    });


</script>