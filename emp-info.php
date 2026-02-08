<?php
$appid  = 'HR';
// if(!isset($_GET['id'])){
// 	echo'<script> location.replace("employer-list"); </script>';
// 	die(); 
// }
// $screen = 'الموظفين';
// $page_title = 'إدارة الموظف';

$screen = 'إدارة الموارد البشرية';
$page_title ='معلومات الموظف';

// $page_perm=['إضافة موظف','تعديل موظف'];
 include_once('inc/header.php');
 
$employee = null;
$stopped_status = '';
$stopped_color = 'secondary';

if(isset($user)){
if(isset($_GET['id']))  
$id = $_GET['id'];
else
$id = $user; 
$parma = array( ':id'  => $id, );
	    $query = "	SELECT 	u.UserID, u.IsSystem,u.UserEmail , u.UserEmail,u.FirstName, u.SecondName, u.LastName, u.Photo, u.Phone, u.Note, u.IsDisabled , g.GroupName,
		u.FingerID,u.user_insurance
		,u.user_bank_name,u.user_account_bank,u.ohter_phone,u.HealthCondition,
		u.Sex,u.marital_status,u.user_address,u.Id_h,u.start_date_h,u.end_date_h,u.path_h,u.Id_license,u.start_date_license,u.end_date_license,
		u.path_license,u.Id_passport,u.start_date_passport,u.end_date_passport,u.path_passport,u.Id_health,u.start_date_health,
		u.end_date_health,u.path_health		
        FROM tblusers u
        
		 

		left join tblusergroups g ON g.GroupID = u.UserGroupID
		where u.UserID =:id
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

    $structure="SELECT t.id,t.UserID ,t.SectionID,t.GroupID,t.GradeID,t.jobtitleID,t.TypeID,t.shiftID,
    t.fingerID,t.Salary,t.Currency,t.state,t.come_name,t.new_s_date,t.new_e_date ,
    -- t.BranchID,b.branch_name,
    a.Name as section_name,j.Name as jobtitle_name,c.Name as group_name,f.Name as name_grade,
	h.Name as name_n , s.ShiftName

    from tblremewal t     
    
    --  left join branches b ON b.branch_id = t.BranchID
    	left join  tblsection a ON a.Id = t.SectionID
        left join  tbshift s ON s.ShiftID  = t.shiftID
        left join  tbljobtitle j ON j.Id = t.jobtitleID
		left join   tblgroup c ON c.Id = t.GroupID
		left join   tbljobgrade f ON f.Id = t.GradeID
		left join   tblemploymenttype h ON h.Id = t.TypeID
       
    
     where UserID =:id AND state is not null order by id Asc ";
	
    $stm_struct = $connect_pdo->prepare($structure);
	$stm_struct->execute($parma);
    if($stm_struct->rowCount() > 0)
    {
		$struct = $stm_struct->fetchAll();
    }

    $shift_ids = !empty($struct[0]['shiftID']) ? array_unique(explode(',', $struct[0]['shiftID'])) : [];
    $finger_ids = !empty($struct[0]['fingerID']) ? array_unique(explode(',', $struct[0]['fingerID'])) : [];
    $instant_ids = !empty($employee['user_insurance']) ? array_unique(explode(',', $employee['user_insurance'])) : [];
    $shift_names = [];
    $finger_names = [];
    $instant_names=[];

    if (!empty($instant_ids)) {
        $placeholders_in = implode(',', array_fill(0, count($instant_ids), '?'));
        
        $query_instant = "SELECT Name FROM tbinsurance WHERE Id IN ($placeholders_in)";
        $stmt_instant = $connect_pdo->prepare($query_instant);
        $stmt_instant->execute($instant_ids);
        
        $instant_names = $stmt_instant->fetchAll(PDO::FETCH_COLUMN);
        
    }

    if (!empty($shift_ids)) {
        $placeholders = implode(',', array_fill(0, count($shift_ids), '?'));
        
        $query_shift = "SELECT ShiftName FROM tbshift WHERE ShiftID IN ($placeholders)";
        $stmt_shift = $connect_pdo->prepare($query_shift);
        $stmt_shift->execute($shift_ids);
        
        $shift_names = $stmt_shift->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!empty($finger_ids)) {
        $placeholders__ = implode(',', array_fill(0, count($finger_ids), '?'));
        
        $query_finger = "SELECT FingerprintName FROM tbfingerprint WHERE FingerprintID  IN ($placeholders__)";
        $stmt_finger = $connect_pdo->prepare($query_finger);
        $stmt_finger->execute($finger_ids);
        
        $finger_names = $stmt_finger->fetchAll(PDO::FETCH_COLUMN);
    }
    


    $quary_cer="SELECT UserID ,Certifacte_name,Side,StartDate,FilePath from user_cer where UserID =:id";
	
    $stm_cer = $connect_pdo->prepare($quary_cer);
	$stm_cer->execute($parma);
    if($stm_cer->rowCount() > 0)
    {
		$certifcate = $stm_cer->fetchAll();
    }

    $quary_Expe="SELECT UserID ,TitleJob,side,StartDate,EndDate,FilePath from user_exper where UserID =:id";
	
    $stm_exp = $connect_pdo->prepare($quary_Expe);
	$stm_exp->execute($parma);
    if($stm_exp->rowCount() > 0)
    {
		$experince = $stm_exp->fetchAll();
    }

	
}

$user_shift = !empty($struct[0]['shiftID']) ? array_unique(explode(',', $struct[0]['shiftID'])) : [];
if(!isset($struct)) $struct = [];


?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
	
   
	<div class="content-header page-nav" style="background:#fff;">
      <div class="container-fluid ">
        <div class="row ">
         <div class="col-md-9 col-sm-7 col-xs-12">
         <span class="badge badge-<?= $stopped_color?>"> <?= $stopped_status?> </span>
            <span class="page-title"><?= $employee ? $employee['FirstName'].' '.$employee['LastName'] : 'غير موجود' ?> </span>
           
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
                                <li class="nav-item">
                                    <a class="nav-link" id="identity-info-tab" data-toggle="pill" href="#identity-info" role="tab" aria-controls="identity-info" aria-selected="false">
                                        <strong>معلومات الهوية</strong>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="certificates-tab" data-toggle="pill" href="#certificates" role="tab" aria-controls="certificates" aria-selected="false">
                                        <strong>الشهائد</strong>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="experiences-tab" data-toggle="pill" href="#experiences" role="tab" aria-controls="experiences" aria-selected="false">
                                        <strong>الخبرات</strong>
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
                            
                            <?php if (!empty($employee['user_insurance'])): ?>
                                <div class="col-md-8 col-12 mb-3">
                                    <div class="border-bottom pb-2">
                                    <p class="mb-0 text-muted small">شركات التامين</p>
                                <?php foreach ($instant_names as $ins_name): ?>
                                    
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($ins_name) ?></h6><br>
                                    
                                <?php endforeach; ?>
                                </div>
                                    </div>
                            <?php endif; ?>
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
                        <div class="card-header ">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-briefcase me-2"></i>السجل الوظيفي
                            </h3>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="120px">الفترة</th>
                                            <th>المسمى الوظيفي</th>
                                            <th>القسم</th>
                                            <th>الراتب</th>
                                            <th>الفترات</th>
                                            <th>اجهزة البصمة</th>
                                            <th>العملية</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- مثال لبيانات الترقي -->
                                        <?php if(!empty($struct)){
                                             foreach ($struct as $stru){  ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <small class="text-muted">من</small>
                                                    <span><?= $stru['new_s_date'] ?></span>
                                                    <small class="text-muted">إلى</small>
                                                    <span><?= $stru['new_e_date'] ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="mb-1 fw-bold"><?= !empty($stru['jobtitleID'])?$stru['jobtitle_name'] :'' ?></h6>       
                                                <small class="text-muted d-block">المجموعة: <?= !empty($stru['GroupID'])?$stru['group_name'] :'' ?></small>
                                                <small class="text-muted">الدرجة: <?= !empty($stru['GradeID'])?$stru['name_grade'] :'' ?></small>
                                            </td>
                                            <td>
                                                <span class="d-block"><?= !empty($stru['SectionID'])?$stru['section_name'] :'' ?></span>
                                                <small class="text-muted">النمط: <?= !empty($stru['TypeID'])?$stru['name_n'] :'' ?></small>    
                                            </td>   
                                            <td>
                                                <span class="fw-bold"><?= !empty($stru['Salary'])?$stru['Salary'] :'' ?></span>
                                                <span class="text-muted"><?= !empty($stru['Salary'])?$stru['Currency'] :'' ?></span>   
                                            </td>
                                            <td>
                                            <?php if (!empty($stru['shiftID'])): ?>
                                                <?php foreach ($shift_names as $shift_name): ?>
                                                    <small class="fw-bold"><?= htmlspecialchars($shift_name) ?></small><br>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                                
                                            </td>
                                            <td>   
                                            <?php if (!empty($stru['fingerID'])): ?>
                                                <?php foreach ($finger_names as $finger_name): ?>
                                                    <small class="fw-bold"><?= htmlspecialchars($finger_name) ?></small><br>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                                
                                            </td>

                                            <td>
                                                <span class="fw-bold"><?= !empty($stru['come_name'])?$stru['come_name'] :'' ?></span>
                                                 
                                            </td>
                                        </tr>
                                        
                                        <?php }  }?>
                                        
                                        <!-- ترقية سابقة -->
                                     
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تفاصيل الترقي عند النقر على عرض -->
            
                </div>
            </div>
        </div>
    </div>
                                
                                <!-- معلومات الهوية -->
                                <div class="tab-pane fade" id="identity-info" role="tabpanel" aria-labelledby="identity-info-tab">
                                    <div class="table-responsive p-0">
                                        <table id="inv-payments" class="table table-condensed table-hover  " width="100%">
                                            <thead>
                                             <tr>
                                              
                                              <th>اسم الهوية</th>
                                              <th>رقم الهوية</th>
                                              <th>تاريخ الاصدار</th>
                                              <th>تاريخ الانتها</th>
                                              <th>الاجراء</th>
                                             </tr>
                                             <?php   if(!empty($employee['Id_h']) || !empty($employee['start_date_h']) || !empty($employee['end_date_h']) || !empty($employee['path_h']) ):  ?>
                                             <tr><td>الهوية</td>
                                             <td><?php echo  !empty($employee['Id_h']) ? $employee['Id_h'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['start_date_h']) ?$employee['start_date_h'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['end_date_h']) ?$employee['end_date_h'] :'لايوجد' ?></td>
                                              <td><?php if (!empty($employee['path_h'])){  ?>
                                                    <a href="<?= $employee['path_h'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?> </td>
                                              <?php endif; ?>
                                                <?php   if(!empty($employee['Id_license']) || !empty($employee['start_date_license']) || !empty($employee['end_date_license']) || !empty($employee['path_license']) ):  ?>
                                                <tr><td>رخصة السواقة</td>
                                             <td><?php echo  !empty($employee['Id_license']) ?$employee['Id_license'] :'لايوجد' ?></td>
                                            <td><?php echo  !empty($employee['start_date_license']) ?$employee['start_date_license'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['end_date_license']) ?$employee['end_date_license'] :'لايوجد' ?></td>
                                             <td><?php if (!empty($employee['path_license'])){  ?>
                                                    <a href="<?= $employee['path_license'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?></td>
                               
                                                <?php endif; ?>
                                                <?php   if(!empty($employee['Id_passport']) || !empty($employee['start_date_passport']) || !empty($employee['end_date_passport']) || !empty($employee['path_passport']) ):  ?>
                                                <tr><td>جواز</td>
                                             <td><?php echo  !empty($employee['Id_passport']) ?$employee['Id_passport'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['start_date_passport']) ?$employee['start_date_passport'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['end_date_passport']) ?$employee['end_date_passport'] :'لايوجد' ?></td>
                                             <td><?php if (!empty($employee['path_passport'])){  ?>
                                                    <a href="<?= $employee['path_passport'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?></td>
                               
                                                <?php endif; ?>
                                                <?php   if(!empty($employee['Id_health']) || !empty($employee['start_date_health']) || !empty($employee['end_date_health']) || !empty($employee['path_health']) ):  ?>
                                               
                                                <tr><td>الشهادة الصحية</td>
                                             <td><?php echo  !empty($employee['Id_health']) ?$employee['Id_health'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['start_date_health']) ?$employee['start_date_health'] :'لايوجد' ?></td>
                                             <td><?php echo  !empty($employee['end_date_health']) ?$employee['end_date_health'] :'لايوجد' ?></td>
                                             <td><?php if (!empty($employee['path_health'])){  ?>
                                                    <a href="<?= $employee['path_health'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?></td>
                               
                                             <?php endif; ?>
                                            </thead>
                                           <tbody></tbody>
                                       </table>
                                    </div>
                                </div>
                                
                                <!-- الشهائد -->
                                <div class="tab-pane fade" id="certificates" role="tabpanel" aria-labelledby="certificates-tab">
                                    <div class="table-responsive p-0">
                                    <table id="inv-payments" class="table table-condensed table-hover  " width="100%">
                                            <thead>
                                                <tr> 
    <!-- certifcate -->
                                            <th>اسم الشهادة</th>
                                            <th>اسم الجهة</th>
                                            <th>تاريخ الاصدار</th>
                                            <th>الاجراء</th>
                                            </tr> 
                                            </thead>
                                            
                                            
                                            <?php if(!empty($certifcate)){
                                             foreach ($certifcate as $cert){  ?>
                                            <tr>
                                            <td><?= $cert['Certifacte_name'] ?></td>
                                            <td><?= $cert['Side'] ?></td>
                                            <td><?= $cert['StartDate'] ?></td>
                                            <td>
                                                <?php if (!empty($cert['FilePath'])){  ?>
                                                    <a href="<?= $cert['FilePath'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                            </tr>
                                            <?php }  }?>
                                          
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- الخبرات -->
                                <div class="tab-pane fade" id="experiences" role="tabpanel" aria-labelledby="experiences-tab">
                                    <div class="table-responsive p-0">
                                    <table id="inv-payments" class="table table-condensed table-hover  " width="100%">
                                            <thead>    
                                    <tr> 
    <!-- certifcate -->
                                            <th>المسمى الوظيفي</th>
                                            <th>اسم الجهة</th>
                                            <th>تاريخ البداء</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>الاجراء</th>
                                            </tr> 
                                            </thead>
                                            
                                            
                                            <?php if(!empty($experince)){
                                            foreach ($experince as $exper){  ?>
                                            <tr>
                                            <td><?= $exper['TitleJob'] ?></td>
                                            <td><?= $exper['side'] ?></td>
                                            <td><?= $exper['StartDate'] ?></td>
                                            <td><?= $exper['EndDate'] ?></td>
                                            <td>
                                                <?php if (!empty($cert['FilePath'])){  ?>
                                                    <a href="<?= $cert['FilePath'] ?>" download>
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                            </tr>
                                            <?php } } ?>
                                          
                                        </table>
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


function confirm_remove (id) {
	if(id !=''){
			$('#modal_title').text('تأكيد عملية الحذف');
				  $('#modal_default .modal-body').addClass('loader');
				  $('#modal_default .modal-dialog').removeClass('modal-lg');
				$('#modal_default').modal({show:true});
			  $('#modal_default .modal-body').load('./hr-app/fingerprint-remove?id='+id+'',function(){
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