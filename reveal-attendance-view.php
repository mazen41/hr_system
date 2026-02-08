<?php
if(!isset($_GET['id'])){
	echo'<script> location.replace("employer-list"); </script>';
	die(); 
}
$appid  = 'HR';
$page_perm=['عرض الحضور والانصراف'];

// $screen = 'الموظفين';
// $page_title = 'سجل الموظف';

$screen = 'إدارة الموارد البشرية';
$page_title = 'كشف الحضور والانصراف';

 include_once('inc/header.php');
 
if(isset($_GET['id'])){
$id = (int)$_GET['id'];
$parma = array( ':id'  => $id, );
	    $query = "	SELECT 	u.UserID, u.IsSystem,u.UserEmail , u.UserEmail,u.FirstName, u.SecondName, u.LastName, u.Photo, u.Phone, u.Note, u.IsDisabled , g.GroupName,
		u.FingerID,u.user_insurance
		,u.user_bank_name,u.user_account_bank,u.ohter_phone,u.HealthCondition,
		u.Sex,u.marital_status,u.user_address,u.Id_h,u.start_date_h,u.end_date_h,u.path_h,u.Id_license,u.start_date_license,u.end_date_license,
		u.path_license,u.Id_passport,u.start_date_passport,u.end_date_passport,u.path_passport,u.Id_health,u.start_date_health,
		u.end_date_health,u.path_health		
        FROM tblusers u
        
		 

		left join tblusergroups g ON g.GroupID = u.UserGroupID
		where u.UserID =:id  AND  (g.IsSystem is null OR u.UserGroupID is null)
		";
	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);
    if($stm->rowCount() > 0)
    {
		$employee = $stm->fetch();
        if(!empty($employee['IsDisabled'])){
            $stopped_status = 'موقف';
            $stopped_color = 'danger';
        }
        else{
            $stopped_status = 'نشط';
            
            $stopped_color = 'success';
            }
    }	
}
?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
         <span class="badge badge-<?= $stopped_color?>"> <?= $stopped_status?> </span>
            <span class="page-title"><?= $employee['FirstName'].' '.$employee['LastName'] ?> </span>
           
          </div>
          <style>
          .current_balance{ text-align: right;margin:0;border-left:none;display:none} 
.bsuccess{ border-right:0.3rem solid green; display:block;}
.bdanger{ border-right:0.3rem solid red; display:block;}

.card {
    border-radius: 0.5rem;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

/* تفاصيل الترقي */
.info-item {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

/* تجاوبية */
@media (max-width: 768px) {
    .table-responsive {
        border: 0;
    }
    
    .table thead {
        display: none;
    }
    
    .table tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    
    .table td {
        display: block;
        text-align: right;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table td:before {
        content: attr(data-label);
        float: left;
        font-weight: bold;
        color: #6c757d;
    }
    
    .table td:last-child {
        border-bottom: 0;
    }
}

          </style>

        </div>
      </div>
    </div>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if(isset($_SESSION['alert']) && !empty($_SESSION['alert'])): ?>
            <div class="alert alert-success alert-dismissible" id="result-alert">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fas fa-check"></i>
                <?=$_SESSION['alert']?>
                <?php $_SESSION['alert'] ='';?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body p-2">
                            <ul class="nav nav-tabs d-print-none" id="employee-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="main-info-tab" data-toggle="pill" href="#main-info" role="tab" aria-controls="main-info" aria-selected="true">
                                        <strong>معلومات الموظف</strong>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="payment-info-tab" data-toggle="pill" href="#payment-info" role="tab" aria-controls="payment-info" aria-selected="false">
                                        <strong>حساب الموظف</strong>
                                    </a>
                                </li>



                            </ul>
                            
                            <div class="tab-content p-2" id="employee-tabs-content" style="border: 1px solid #dddfe3; border-top: none;">
                                <!-- معلومات الموظف -->






                                
                               

<div class="tab-pane fade show active" id="main-info" role="tabpanel" aria-labelledby="main-info-tab">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-light">
                    <div class="card-header" style="border-color:gray;">
                        <h3 class="card-title mb-0">المعلومات الأساسية للموظف</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <!-- الصف الأول  -->
                            <?php   if(!empty($employee['FirstName'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الاسم الأول</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['FirstName']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['SecondName'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الاسم الأوسط</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['SecondName']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['LastName'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">اللقب</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['LastName']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['Phone'])):  ?>
                            <!-- الصف الثاني -->
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">جوال</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['Phone']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['UserEmail'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">البريد الإلكتروني</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['UserEmail']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['user_bank_name'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">البنك</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['user_bank_name']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>


                            <!-- الصف الثالث -->
                            <?php   if(!empty($employee['user_account_bank'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الحساب البنكي</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['user_account_bank']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php   if(!empty($employee['ohter_phone'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">هاتف آخر</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['ohter_phone']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php   if(!empty($employee['HealthCondition '])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الحالة الصحية</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['HealthCondition']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- الصف الرابع -->
                            <?php   if(!empty($employee['marital_status'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الحالة الاجتماعية</p>
                                    <h6 class="mb-0 fw-bold"><?=($employee['marital_status']==1?'متزوج' :($employee['marital_status']==2?'اعزب' :($employee['marital_status']==3?'ارمل' :'اخرى')))?></h6>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php   if(!empty($employee['Sex'])):  ?>
                            <div class="col-md-4 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">الجنس</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['Sex']==1?'ذكر':'انثى'?></h6>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php   if(!empty($employee['user_address'])):  ?>
                            <div class="col-md-8 col-12 mb-3">
                                <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">العنوان</p>
                                    <h6 class="mb-0 fw-bold"><?=$employee['user_address']?></h6>
                                </div>
                            </div>
                            <?php endif; ?>

                           
                            <!--  -->
                            
                            
                            <!--  -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                                















    <!-- حساب الموظف -->
    <div class="tab-pane fade" id="payment-info" role="tabpanel" aria-labelledby="payment-info-tab">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">

                        <!-- Search -->
                        <div class="row" id="filter-area" style="display:none_">
                            <div class="col-md-12">
                                <div class="invoice mb-3" id="filter-area" style="display:none_">
                           <form class="form-horizontal" role="form" action="#" method="post" id="filter-fm">
                        
                             
                               <div class="card-body card-body pt-0 pb-0">
                                       <div class="row" style="align-items: flex-end;">
                                       
                                        <div class="col-md-8">
                                          <label class="col-form-label" for="date_range">الفترة (من - الى)</label>
                                          <input type="text" name="date_range" class="form-control input-date-range"  placeholder="من - الى" id="date_range" autocomplete="off" value="">
                                        </div>
                        
                                        <div class="col-md-4">
                                        <button type="reset" class="btn btn-default reset-filter" data-dismiss=""> الغاء الفلترة</button>
                                            <button type="submit" class="btn btn-info" name="" ><i class="fas fa-search"></i> بحث</button>
                                            
                                          </div>
                        
                                       </div>
                                       
                                       </div>
                                       
                
                             
                        
                                     <!-- /.row -->
                               </form>
                             </div> 
                           
                                </div>
                                </div>

                        <!-- EndSearch -->
                        <div class="card-header ">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-briefcase me-2"></i>السجل الوظيفي
                            </h3>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                            <table id="data_tb" class="table dataTable table-hover  dtr-inline collapsed  nowrap display table-sm" width="100%">
                                    <thead class="bg-light">
                                        <tr>
                                            
                                            <th >تاريخ الحضور</th>
                                            <th>رقم الفتره</th>
                                            <th>الحضور والانصراف</th>
                                            <th >حضور</th>
                                            <th >انصراف</th>
                                            <th > ساعات الفتره</th>
                                            <th >دقائق تاخير الحضور</th>
                                            <th >دقائق تاخير الانصراف</th>
                                            <th >عدد الساعات الفعلية</th>
                                            <th >اجمالي الساعات</th>
                                        </tr>
                                    </thead>
                                     <tfoot>
  <tr class="bold">
    <td colspan="9" style="text-align: right">اجمالي الساعات</td>
    <td id="credited_total">00:00</td>
  </tr>
</tfoot>

                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تفاصيل الترقي عند النقر على عرض -->
            
                </div>
            </div>
        </div>
    </div>
                      
                               
                                
                          
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <style>

    </style>





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
	//data-widget
	
$("#purظ_nav").addClass('menu-open');
// $("#top_pur_menu").addClass('active');
// $("#pur_clients_menu").addClass('active');
    
const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');


$(document).on('click', '.reset-filter', function(){
    $('#date_range').val('');
});

function apply_filters()
{
    var date_range = $('#date_range').val();
    
    $('#data_tb').DataTable().destroy();
    clients_data('yes',param_id,date_range); // افحص مخرجات الداله
}

$('#filter-fm').on('submit', function(e){  
    // alert("HH");
    e.preventDefault();
    apply_filters();
});


clients_data('no',param_id);

function clients_data(is_date_search,user_id,date_range='')
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
    url:"./hr-app/reveal-attendance-view",
    type:"POST",
    data:{
            is_date_search:is_date_search,
            date_range: date_range,
            user_id:user_id,
	}
	},
   
    aoColumns: [
		  
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
			
			
		],
   
    "dom": "<'row'<'col-sm-12 col-md-7'><'col-sm-12 col-md-1'><'col-sm-12 col-md-4 text-left'>>" +
    
    "<'row'<'col-sm-12 mb-4'tr>>" +
    "<'row'<'col-sm-12 col-md-5'li><'col-sm-12 col-md-7'p>>", 
  
"footerCallback": function (row, data, start, end, display) {
    var api = this.api();
    var json = api.ajax.json(); // الطريقة المضمونة

    // تحقق من أن json موجود
    if (json && json.total_credited_time) {
        $(api.column(9).footer()).html(json.total_credited_time);
    } else {
        $(api.column(9).footer()).html('00:00');
    }
}

    

	
	
  });

};


});


</script>