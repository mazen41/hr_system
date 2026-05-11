<?php
// $screen = 'إدارة الموارد البشرية';
// $page_title = 'اضافة موظف';

$screen = 'إدارة الموارد البشرية';
$page_title = 'اضافة موظف';

$appid  = 'HR';

$page_perm=['إضافة موظف','عرض موظف','تعديل موظف'];
 include_once('inc/header.php');

$over = 0;
$all_branches =  $User->allBranches();

$employee = [];
$section = [];
$groub = [];
$jobgrade = [];
$insurance = [];
$shift = [];
$employmenttyp = [];
$fingerprint = [];
$jobtitle = [];
$user_relate = [];
$user_manager = [];
$user_insurance = [];
$user_shift = [];
$user_finger_print_type = [];

// function ActiveBranches($connect,$branches_arry){
// 		$branches = [];
// 		$query = "select branch_id,branch_name from branches "; 
// 		if(!empty($branches_arry)){
// 		$query .= " where  branch_id in (" . $branches_arry . ") "; 
// 		}
// 		$query .= " ORDER BY branch_id  "; 
		
// 		$stm = $connect->prepare($query);
// 		$stm->execute();
// 		if($stm->rowCount() > 0){
			
// 			 return  $stm->fetchAll();
// 		}
		
// 		return $branches;
// }

function rollsList($connect){
		$roles = [];
		$query = "select GroupID,GroupName from tblusergroups where IsSystem is null "; 
		$stm = $connect->prepare($query);
		$stm->execute();
		if($stm->rowCount() > 0){
			
			 return  $stm->fetchAll();
		}
		
		return $roles;
}

function getEmployerBranchFormData($connect, $branchId){
	$branchData = [
		'section' => [],
		'groub' => [],
		'jobgrade' => [],
		'insurance' => [],
		'shift' => [],
		'employmenttyp' => [],
		'fingerprint' => [],
		'jobtitle' => [],
		'user_relate' => [],
		'user_manager' => [],
	];

	$branchId = (int) $branchId;
	if ($branchId <= 0) {
		return $branchData;
	}

	$params = [
		':BranchID' => $branchId,
	];

	$queries = [
		'section' => "SELECT c.Id, c.Name FROM tblsection AS c
			LEFT JOIN tblsection AS d ON c.Id = d.ParentID
			WHERE c.ParentID IS NOT NULL AND d.Id IS NULL AND c.BranchID = :BranchID",
		'groub' => "SELECT Id, Name FROM tblgroup WHERE BranchID = :BranchID",
		'jobgrade' => "SELECT Id, Name FROM tbljobgrade WHERE BranchID = :BranchID",
		'insurance' => "SELECT Id, Name FROM tbinsurance WHERE BranchID = :BranchID AND state = 1",
		'shift' => "SELECT ShiftID, ShiftName FROM tbshift WHERE BranchID = :BranchID AND ShiftState = 0",
		'employmenttyp' => "SELECT Id, Name FROM tblemploymenttype WHERE BranchID = :BranchID",
		'fingerprint' => "SELECT FingerprintID, FingerprintName FROM tbfingerprint WHERE BranchID = :BranchID AND FingerprintState = 1",
		'jobtitle' => "SELECT Id, Name FROM tbljobtitle WHERE BranchID = :BranchID",
		'user_relate' => "SELECT UserID, FirstName, LastName FROM tblusers
			WHERE BranchID = :BranchID
			AND isemp IS NULL
			AND UserID NOT IN (SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)",
		'user_manager' => "SELECT UserID, FirstName, LastName FROM tblusers
			WHERE BranchID = :BranchID
			AND isemp IS NOT NULL",
	];

	foreach ($queries as $key => $query) {
		$stmt = $connect->prepare($query);
		$stmt->execute($params);
		$branchData[$key] = $stmt->fetchAll();
	}

	return $branchData;
}

if(isset($_GET['id'])){
	$id = (int)$_GET['id'];
	
	$parma = array(
				':id'  => $id, 
				//':branch' => (isset($_GET['branch']) && !empty($_GET['branch']) ?  (int)$_GET['branch'] : $_SESSION['branch'] )
			);
	//$query = "SELECT * FROM tblsimpleentries WHERE SimpleEntryID = :id and BranchID = :branch LIMIT 1 ";
			
		$query = "	SELECT 	u.UserID,u.lastversion, u.IsSystem,u.AllowedBranches, u.UserGroupID, u.UserEmail,u.FirstName, u.SecondName, u.LastName, u.Photo, u.Phone, u.Note, u.IsDisabled ,b.branch_name, g.GroupName,w.related_to,w.FirstName as f_n,w.LastName as l_n,m.manager,m.FirstName as f_n_m,m.LastName as l_n_m,
		u.FingerID,k.SectionID,k.BranchID as actual_branch,k.GroupID,k.GradeID,u.user_insurance,k.shiftID,k.TypeID,k.fingerID,k.jobtitleID,
		k.Salary,k.Currency,k.new_s_date,k.new_e_date,u.user_bank_name,u.user_account_bank,u.ohter_phone,u.HealthCondition,
		u.Sex,u.marital_status,u.user_address,u.Id_h,u.start_date_h,u.end_date_h,u.path_h,u.Id_license,u.start_date_license,u.end_date_license,
		u.path_license,u.Id_passport,u.start_date_passport,u.end_date_passport,u.path_passport,u.Id_health,u.start_date_health,
		u.end_date_health,u.path_health,a.Name as section_name,j.Name as jobtitle_name,c.Name as group_name,f.Name as name_grade,
		h.Name as name_n
		FROM tblusers u
        LEFT JOIN tblusers m ON m.manager = u.UserID
        left join tblusers w ON w.related_to = u.UserID
        left join  tblremewal k ON u.lastversion=k.Id 
		left join branches b ON b.branch_id = k.BranchID

		left join  tblsection a ON a.Id = k.SectionID
        left join  tbljobtitle j ON j.Id = k.jobtitleID   
		left join   tblgroup c ON c.Id = k.GroupID
		left join   tbljobgrade f ON f.Id = k.GradeID
		left join   tblemploymenttype h ON h.Id = k.TypeID
		left join tblusergroups g ON g.GroupID = u.UserGroupID
		where u.UserID =:id  AND  (g.IsSystem is null OR u.UserGroupID is null) and k.state is not null
		";
	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);
	
	if($stm->rowCount() > 0){
		$employee = $stm->fetch();
      

		$parma_ = array(
			':BranchID'  => $employee['actual_branch'], 
		);
		// اظهار كل مايتعلق بالفرع
		$query2 = " SELECT c.Id, c.Name FROM tblsection AS c
		LEFT JOIN tblsection AS d ON c.Id = d.ParentID
		WHERE c.ParentID IS NOT NULL AND d.Id IS NULL and c.BranchID = :BranchID ";
		
		$query3 = " SELECT Id ,Name FROM tblgroup where BranchID = :BranchID";
		$query4 = " SELECT Id ,Name FROM tbljobgrade where BranchID = :BranchID";
		$query5 = " SELECT Id , Name FROM  tbinsurance  where BranchID = :BranchID and state=1";
		$query6 = " SELECT ShiftID ,ShiftName FROM  tbshift  where BranchID = :BranchID and ShiftState=0";
		$query7 = " SELECT Id ,Name FROM  tblemploymenttype where BranchID = :BranchID";
		$query8 = " SELECT FingerprintID ,FingerprintName FROM  tbfingerprint  where BranchID = :BranchID and FingerprintState=1";
		$query9 = " SELECT Id ,Name FROM tbljobtitle where BranchID = :BranchID";
        $query10 = " SELECT UserID ,FirstName,LastName FROM tblusers 
        where BranchID = :BranchID
        and isemp is  null
        and UserID Not in(SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)
        ";

        $query11 = " SELECT UserID ,FirstName,LastName FROM tblusers 
        where BranchID = :BranchID
        and isemp is NOT null";

		$stm_ = $connect_pdo->prepare($query2);
		$stm_->execute($parma_);
		if($stm_->rowCount() > 0){    
		$section = $stm_->fetchAll();
        }
		$stm_1 = $connect_pdo->prepare($query3);

		$stm_1->execute($parma_);
		if($stm_1->rowCount() > 0){   
		$groub = $stm_1->fetchAll();
        }
		$stm_2 = $connect_pdo->prepare($query4);
		$stm_2->execute($parma_);
		if($stm_2->rowCount() > 0){   
		$jobgrade = $stm_2->fetchAll();}

		$stm_3 = $connect_pdo->prepare($query5);
		$stm_3->execute($parma_);
		if($stm_3->rowCount() > 0){   
		$insurance = $stm_3->fetchAll();}

		$stm_4 = $connect_pdo->prepare($query6);
		$stm_4->execute($parma_);
		if($stm_4->rowCount() > 0){   
		$shift = $stm_4->fetchAll();}

		$stm_5 = $connect_pdo->prepare($query7);
		$stm_5->execute($parma_);
		if($stm_5->rowCount() > 0){   
		$employmenttyp = $stm_5->fetchAll();}

		$stm_6 = $connect_pdo->prepare($query8);
		$stm_6->execute($parma_);
		if($stm_6->rowCount() > 0){   
		$fingerprint = $stm_6->fetchAll();}

        $stm_7 = $connect_pdo->prepare($query9);
		$stm_7->execute($parma_);
		if($stm_7->rowCount() > 0){   
		$jobtitle = $stm_7->fetchAll();}

        $stm_8 = $connect_pdo->prepare($query10);
		$stm_8->execute($parma_);
		if($stm_8->rowCount() > 0){   
		$user_relate = $stm_8->fetchAll();}

        $stm_9 = $connect_pdo->prepare($query11);
		$stm_9->execute($parma_);
		if($stm_9->rowCount() > 0){   
		$user_manager = $stm_9->fetchAll();}


		$user_insurance = !empty($employee['user_insurance']) ? array_unique(explode ( ',', $employee['user_insurance'])) : [];
		$user_shift = !empty($employee['shiftID']) ? array_unique(explode ( ',', $employee['shiftID'])) : [];
		$user_finger_print_type = !empty($employee['fingerID']) ? array_unique(explode ( ',', $employee['fingerID'])) : [];
		
	
	}else{
		echo'<script> location.replace("users-list"); </script>';
		//die();
		
	}
	
}

// if(!empty($employee) && !empty($employee['BranchID'])){
// 		$Branches = ActiveBranches($connect_pdo,$employee['BranchID']);
// }else{
// 		//$user_branch= implode ( ',', $_SESSION['user']['extraBranchies']);
// 		//$Branches = ActiveBranches($connect_pdo,$user_branch);
// }

		$roles = rollsList($connect_pdo);

$defaultBranchId = '';
if (!empty($employee['actual_branch'])) {
    $defaultBranchId = (string) $employee['actual_branch'];
} elseif (!empty($_SESSION['branch']) && isset($all_branches[$_SESSION['branch']])) {
    $defaultBranchId = (string) $_SESSION['branch'];
} elseif (!empty($all_branches)) {
    $branchKeys = array_keys($all_branches);
    $defaultBranchId = (string) $branchKeys[0];
}

$defaultBranchName = '';
if (!empty($employee['branch_name'])) {
    $defaultBranchName = $employee['branch_name'];
} elseif ($defaultBranchId !== '' && isset($all_branches[$defaultBranchId])) {
    $defaultBranchName = $all_branches[$defaultBranchId];
}

if (!isset($_GET['id']) && $defaultBranchId !== '') {
	$branchFormData = getEmployerBranchFormData($connect_pdo, $defaultBranchId);
	$section = $branchFormData['section'];
	$groub = $branchFormData['groub'];
	$jobgrade = $branchFormData['jobgrade'];
	$insurance = $branchFormData['insurance'];
	$shift = $branchFormData['shift'];
	$employmenttyp = $branchFormData['employmenttyp'];
	$fingerprint = $branchFormData['fingerprint'];
	$jobtitle = $branchFormData['jobtitle'];
	$user_relate = $branchFormData['user_relate'];
	$user_manager = $branchFormData['user_manager'];
}


//$_SESSION['user']['extraBranchies']


?>

<script>
//var timezones  = [];
</script>
<style>	
label.error{
	color: red;
    font-size: 0.83rem;
}
 .bootstrap-select .dropdown-toggle:focus{
    outline: unset !important;
    outline: unset !important;
    outline-offset: unset !important;
 }
 .tabs {
    display: flex;
}

.tab-button {
    padding: 5px 10px;
    cursor: pointer;
    background-color:rgb(255, 255, 255);
    border: 1px solid #ccc;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.tab-button:hover {
    background-color: #ddd;
}

.tab-content {
    display: none;
    border-top: none;
    margin-top: -1px;
}

.tab-content.active {
    display: block;
}
#updateButton
{display: none;}
</style>	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
             <span class="page-title"><?=(empty($employee)? 'إضافة موظف' : 'تعديل موظف' )?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			
			<button type="button" class="btn btn-default" onclick="history.back()" id="cancel-bt"><i class="fa fa-times"></i><span class="d-none d-sm-inline"> عودة</span></button>
			
			<button type="button" class="btn btn-success"  id="save-data"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=(empty($employee)? 'حفظ' : 'تحديث' )?></span></button>
			
			
			
			
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
<!--  -->
    <div class="row">
        <div class="col-12">
            <!-- تبدأ قائمة التبويبات الخاصة بالأجهزة الصغيرة -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <!-- <button class="navbar-toggler" type="button" data-toggle="collapse" style="font-size:15px" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button> -->
                <!-- <div class="collapse navbar-collapse" id="navbarNav"> -->
                    <ul class="navbar-nav">
                        <li>
                            <button class="tab-button" onclick="openTab(event, 'tab1')">معلومات الموظف</button>
							<button class=" tab-button" onclick="openTab(event, 'tab2')">حساب الموظف</button>
							<button class="tab-button" onclick="openTab(event, 'tab3')">العقد</button>
							<button class="tab-button" onclick="openTab(event, 'tab4')">الهوية</button>
							<button class="tab-button" onclick="openTab(event, 'tab5')">اخرى</button>
                            <!-- <button class="tab-button" onclick="openTab(event, 'tab6')">مرفقات اخرى</button> -->
							<!-- <?php
							 if(!empty($id)){ ?>
							<a href="user-experince?id=<?php echo $_GET['id']; ?>"><button class="tab-button" onclick="openTab(event, 'tab6')">الخبرات</button></a>
							<a a href="user-certifacte?id=<?php echo $_GET['id']; ?>"><button class="tab-button" onclick="openTab(event, 'tab7')">الشهائد</button></a>
							<?php 
							 }
							 ?> -->
                        </li>

                    </ul>
                <!-- </div> -->
            </nav>
        </div>
    </div>

<!--  -->

		<!-- <div class="row"> -->
			
					<form class="form-horizontal" role="form" action="#" method="post" id="user_fm" enctype="multipart/form-data">
							<input type="hidden" name="user_id" id="user_id" value="<?=(!empty($employee['UserID'])? (int)$employee['UserID'] : '' )?>">
					
			
			    <div id="tab1" class="tab-content">
				<div class="col-md-12">
				<div class="card ">
					<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
						<h3 class="card-title">معلومات الموظف</h3>
						<div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse">
							<i class="fas fa-minus"></i>
						  </button>
						</div>
					</div>
					
					<div class="card-body" >
					
					<input type="hidden" value="" name="">

					  <div class="row">		  
						<div class="col-md-4">
						  <!-- text input -->
						  <div class="form-group">
							<label class="col-form-label required" for="emp_name">الأسم الأول</label>
							<input type="text" class="form-control " data-toggle="tooltip" title="ادخل الاسم" id="emp_name" name="emp_name" placeholder=""  value="<?=(!empty($employee['FirstName'])? $employee['FirstName'] : '' )?>" required="">
						  </div>
						</div>
						
						<div class="col-md-4">
						  <!-- text input -->
						  <div class="form-group">
							<label class="col-form-label " for="emp_name2">الأسم الأوسط</label>
							<input type="text" class="form-control " data-toggle="tooltip" title="" id="emp_name2" name="emp_name2" placeholder=""  value="<?=(!empty($employee['SecondName'])? $employee['SecondName'] : '' )?>" >
						  </div>
						</div>
						
						<div class="col-md-4">
						  <div class="form-group">
							<label class="col-form-label required" for="emp_lname">اللقب</label>
							<input type="text" class="form-control " id="emp_lname" name="emp_lname" autocomplete="off" data-toggle="tooltip" title="ادخل اللقب"  value="<?=(!empty($employee['LastName'])? $employee['LastName'] : '' )?>" required >
						  </div>
						</div>
					</div>
						
					<div class="row">	
						<div class="col-md-4">
						  <div class="form-group">
							<label class="col-form-label " for="mobile">جوال</label>
							<input type="text" class="form-control number-format" data-toggle="tooltip" title="ادخل رقم الجوال" id="mobile" name="mobile" placeholder="" autocomplete="off"  value="<?=(!empty($employee['Phone'])? $employee['Phone'] : '' )?>">
						  </div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
							  <label class="col-form-label " for="emp_fingerprint">البصمة</label>
							  <input type="text" class="form-control " id="emp_fingerprint" name="emp_fingerprint" autocomplete="off" data-toggle="tooltip" title="ادخل البصمة"  value="<?=(!empty($employee['FingerID'])? $employee['FingerID'] : '' )?>"  >
							</div>
						  </div>
						  <div class="col-md-4">
							<div class="form-group">
						  <label class="col-form-label logindata " for="email">البريد الالكتروني</label>
						  <input type="email" class="form-control ltr logindata" data-toggle="tooltip"   name="email" placeholder="" autocomplete="on"  value="<?=(!empty($employee['UserEmail'])? $employee['UserEmail'] : '' )?>"   title="ادخل عنوان البريد الالكتروني" <?=(empty($employee) || !empty($employee['UserGroupID'])? 'required' : '' )?>>
						  <p class="help-block user_account" style="display: <?=(empty($employee) || !empty($employee['UserGroupID'])? '' : 'none' )?>  ">يستخدم في تسجيل الدخول</p>
						</div>
						</div>

					</div>
					<div class="row">						
						<?php
							//if(!empty($_SESSION['user']['extraBranchies']) || $_SESSION['role'] == 'owner'){ ?>
								<div class="col-md-4">
									<div class="form-group">
										<label for="emp_branch" class="col-form-label required">الفرع الافتراضي</label>
											<select class=" form-control select_branch  "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد الفرع الافتراضي للموظف" id="emp_branch" name="emp_branch" required>
											<?php
											if($defaultBranchId !== '' && !isset($all_branches[$defaultBranchId]) && !empty($defaultBranchName)){
												echo'<option value="'.$defaultBranchId.'" selected>'.$defaultBranchName.'</option>';
											}
											
											foreach($all_branches as $id => $name){	
												$selected = ((string) $id === $defaultBranchId) ? 'selected' : '';
												echo'<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
											}
											?>
											  
											</select>
									</div>
								</div>
							
							<?php
							//$getbranch = 'group_branch';
							//} 
                            ?>
							<?php 
					   if(empty($employee)){
						?>
					   
						  <div class="col-md-6">
							  <div class="form-group">               
							<label class="col-form-label logindata user_pass " for="user_pass">كلمة المرور</label>
							<input type="text" class="form-control ltr logindata" data-toggle="tooltip"  id="user_pass" name="user_pass" placeholder=""  value="" title="إنشئ كلمة مرور للمستخدم، أو قم بتفعيل خيار ارسال كلمة المرور"  >
						  </div>
						  </div>
					 
					  <?php
					   }
                       ?>
							
					</div>
					<div class="row">
					<div class="col-md-12">
						<div class="form-group">
						  <label class="col-form-label " for="emp_note">ملاحظة</label>
						  <input type="text" class="form-control " data-toggle="tooltip" title=""name="emp_note" placeholder="" autocomplete="off"  value="<?=(!empty($employee['Note'])? $employee['Note'] : '' )?>">
						</div>
					  </div>
					</div>

					  
                      <div class="row">
                            <div class="col-md-6">
							<div class="form-group">
								<label class="switch switch-danger ml-auto  col-form-label " title="ايقاف المستخدم من الدخول للنظام">
									  <label class="control-label " for="desable_user"></label>
										 <input type="checkbox" name="desable_user" id="desable_user"  value="" <?=!empty($employee['IsDisabled'])? 'checked' : '' ?>  >
										 <span></span> إيقاف الموظف</i>
								  </label>
							</div>
                            </div>
                            </div>

					</div>
					
				</div>
			</div>
		</div>

			
			
		<div id="tab2" class="tab-content">
			<div class="col-md-12">
				<div class="card card-outline ">
					<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
						<h3 class="card-title">حساب الموظف في النظام</h3>
						<div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse">
							<i class="fas fa-minus"></i>
						  </button>
						</div>
					</div>
					<div class="card-body" >
					  <div class="row" >

					  </div>
					  
					   
					   
					   
					   <div class="row user_account">
                       <div class="col-md-4">
							<div class="form-group">
								<label for="user_jobtitle" class="col-form-label  logindata required">المسمى الوظيفي</label>
								<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle"  required>
								<?php
								if(!empty( $jobtitle)){
									
									if (isset($employee['jobtitleID'], $employee['jobtitle_name'])) {
										echo'<option value="'.$employee['jobtitleID'].'" selected>'.$employee['jobtitle_name'].'</option>';
									}
									foreach($jobtitle as $sec)
									{
										echo'<option value="'.$sec["Id"].'" >'.$sec["Name"].'</option>';
									}
									
								}  
								?>	
								</select>
							</div>
						 </div>
						 <!-- القسم -->
						 <div class="col-md-4">
							<div class="form-group">
								<label for="user_section" class="col-form-label  logindata required">القسم</label>
								<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد باي قسم" id="user_section" name="user_section"  required>
								<?php
								if(!empty( $section)){
									
									if (isset($employee['SectionID'], $employee['section_name'])) {
										echo'<option value="'.$employee['SectionID'].'" selected>'.$employee['section_name'].'</option>';
									}
									foreach($section as $sec)
									{
										echo'<option value="'.$sec["Id"].'" >'.$sec["Name"].'</option>';
									}
									
								}  
								?>	
								</select>
							</div>
						 </div>
						  <!--  -->
						<!-- المجموعة الوظيفيه -->
						<div class="col-md-4">
						<div class="form-group">
							<label for="user_group_" class="col-form-label  logindata">المجموعة الوظيفية</label>
							<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد المجموعة الوظيفية" id="user_group_" name="user_group_"  >
							<?php 
								if(!empty( $groub)){
		
									if (isset($employee['GroupID'], $employee['group_name'])) {
										echo'<option value="'.$employee['GroupID'].'" selected>'.$employee['group_name'].'</option>';
									}
									foreach($groub as $gro)
									{
										echo'<option value="'.$gro["Id"].'" >'.$gro["Name"].'</option>';
									}
									
								} 
							?>		
							</select>
						</div>
						</div>
					   </div>

					   <div class="row user_account">
						<div class="col-md-4">
						   <div class="form-group">
							   <label for="user_grade" class="col-form-label  logindata ">الدرجة الوظيفية</label>
								<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" >
								<?php 
								if(!empty( $jobgrade)){
		
									if (isset($employee['GradeID'], $employee['name_grade'])) {
										echo'<option value="'.$employee['GradeID'].'" selected>'.$employee['name_grade'].'</option>';
									}
									foreach($jobgrade as $job)
									{
										echo'<option value="'.$job["Id"].'" >'.$job["Name"].'</option>';
									}
									
								}
								?>   
								</select>
						   </div>
						</div>
						<!-- شركة التامين -->
						<div class="col-md-4">
						   <div class="form-group">
							   <label for="user_insuance" class="col-form-label  logindata ">شركة التامين</label>
								   <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد شركة التامين" id="user_insuance" name="user_insuance[]" multiple="multiple" multiple data-selected-text-format="count > 2" multiple data-actions-box="true" data-width="fit" style="display:none_">
								    
								   <?php
										if(!empty( $insurance)){
			
			
											foreach($insurance as $ins)
											{
												echo'<option value="'.$ins["Id"].'"  '.(!empty($user_insurance) && in_array($ins["Id"], $user_insurance) ? 'selected' : '').'  >'.$ins["Name"].'</option>';
												
												
											}
											
										} 
								?> 
								    
								</select>
						   </div>
						</div>
						 <!--  -->
					   <!-- فترات العمل-->
					   <div class="col-md-4">
					   <div class="form-group">
						   <label for="user_shift" class="col-form-label  logindata required">فترات العمل</label>
							   <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد فترات العمل " id="user_shift" name="user_shift[]"  required multiple="multiple" multiple data-selected-text-format="count > 2" multiple data-actions-box="true" data-width="fit" style="display:none_">
							   <?php
										if(!empty( $shift)){
											foreach($shift as $shi)
											{
												echo'<option value="'.$shi["ShiftID"].'"  '.(!empty($user_shift) && in_array($shi["ShiftID"], $user_shift) ? 'selected' : '').'  >'.$shi["ShiftName"].'</option>';	
											}
											
										} 
								?> 
							
								</select>
					   </div>
					   </div>
					  </div>

					  <div class="row user_account" >
						<div class="col-md-4">
						   <div class="form-group">
							   <label for="user_type" class="col-form-label  logindata">نمط العمل</label>
								<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد نمط العمل" id="user_type" name="user_type" >
								<?php 
								if(!empty( $employmenttyp)){

									if (isset($employee['TypeID'], $employee['name_n'])) {
										echo'<option value="'.$employee['TypeID'].'" selected>'.$employee['name_n'].'</option>';
									}
									foreach($employmenttyp as $typ)
									{
										echo'<option value="'.$typ["Id"].'" >'.$typ["Name"].'</option>';
									}
									
								}
								?> 
								</select>
						   </div>
						</div>

					   <div class="col-md-4">
					   <div class="form-group">
						   <label for="user_finger" class="col-form-label  logindata required">جهاز البصمة</label>
							   <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد صلاحية المستخدم" id="user_finger" name="user_finger[]"  required multiple="multiple" multiple data-selected-text-format="count > 2" multiple data-actions-box="true" data-width="fit" style="display:none_"> 
							   <?php
										if(!empty( $fingerprint)){
											foreach($fingerprint as $fin)
											{
												echo'<option value="'.$fin["FingerprintID"].'"  '.(!empty($user_finger_print_type) && in_array($fin["FingerprintID"], $user_finger_print_type) ? 'selected' : '').'  >'.$fin["FingerprintName"].'</option>';	
											}
											
										} 
								?>    
							</select>
					   </div>
					   </div>
					   <div class="col-md-4">
                        <div class="form-group">
                            <!-- user_jobtitle    jobtitle-->
                            <label for="user_related_to" class="col-form-label  logindata ">المستخدم المرتبط لة</label>
                            <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد اذا كان مرتبط ب مستخدم " id="user_related_to" name="user_related_to"  >
                            <?php
                            if(!empty( $employee['related_to'])){
                            echo '<option value="'.$employee['related_to'].'" selected>'.$employee['f_n'].' '.$employee['l_n'].'</option>';
                            }
                            if(!empty( $user_relate)){
                        
                                foreach($user_relate as $sec)
                                {
                                    echo'<option value="'.$sec["UserID"].'" >'.$sec["FirstName"].' '.$sec["LastName"].'</option>';
                                }
                                
                            }  
                            ?>	
                            </select>
                        </div>
                     </div>
					  </div>
						<!-- user_manager -->
                        <div class="row">
                        <div class="col-md-4">
                        <div class="form-group">
                            <label for="user_manager" class="col-form-label  logindata ">المدير بتاعه</label>
                            <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد اذا كان مرتبط ب مستخدم " id="user_manager" name="user_manager"  >
                            <?php
                            if(!empty( $employee['manager'])){
                            echo '<option value="'.$employee['manager'].'" selected>'.$employee['f_n_m'].' '.$employee['l_n_m'].'</option>';
                            }
                            if(!empty( $user_manager)){
                                
                                foreach($user_manager as $sec)
                                {
                                    echo'<option value="'.$sec["UserID"].'" >'.$sec["FirstName"].' '.$sec["LastName"].'</option>';
                                }
                                
                            }  
                            ?>	
                            </select>
                        </div>
                     </div>

                        </div>
                         
					   </div>
					
					
				</div>
		
			</div>
			</div>
			<!--  -->
			<div id="tab3" class="tab-content">
				<div class="col-md-12">
				<div class="card ">
					<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
						<h3 class="card-title">الموظف والعقد</h3>
						<div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse">
							<i class="fas fa-minus"></i>
						  </button>
						</div>
					</div>
					
					<div class="card-body" >
					
					<input type="hidden" value="" name="">

					  <div class="row">	
                        	  
						<div class="col-md-4">
						  <!-- text input -->
						  <div class="form-group">
							<label class="col-form-label required" for="emp_salary">الراتب</label>
							<input type="text" class="form-control " placeholder="0.00" data-toggle="tooltip" title="ادخل الراتب" id="emp_salary" name="emp_salary" placeholder=""  value="<?=(!empty($employee['Salary'])? $employee['Salary'] : '' )?>" required>
						  </div>
						</div>
						
						<div class="form-group col-md-4">
							<label class="col-form-label required" for="currency">العملة</label>
							<select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل العملة" id="currency" name="currency" required >
							<?php if(!empty($employee['Currency'])){ ?>
								<option value="<?= $employee['Currency'] ?>" selected><?= $employee['Currency'] ?></option>
								<option value="<?= $User->currency; ?>">عملة النظام</option>
							<?php } 
							else {
								?>
							<option value="<?= $User->currency; ?>" selected>عملة النظام</option>
								<?php
							} ?>
							
							 <!-- <option value="SAR" >الريال السعودي</option>
							 <option value="AED" >الدرهم الاماراتي</option>
							 <option value="USDT" >الدولار الامريكي</option>
							 <option value="QAR" >الريال القطري</option>
							 <option value="KWT" >الريال الكويتي</option>
							 <option value="BHD" >الريال البحريني</option> -->
								</select>
						  </div>
						
						<div class="col-md-4">
                            
						  <div class="form-group">
							<label class="col-form-label required" for="emp_contract_S">تاريخ بداية العقد</label>
							<input type="date" name="emp_contract_S" class="form-control "  placeholder="تاريخ بداية العقد" id="emp_contract_S" autocomplete="off" value="<?=(!empty($employee['new_s_date'])? $employee['new_s_date'] : '' )?>" required >
						  </div>
						</div>
					</div>
						
					<div class="row">	
						<div class="col-md-4">
						  <div class="form-group">
							<label class="col-form-label required " for="emp_contract_F">تاريخ انتهاء العقد</label>
							<input type="date" name="emp_contract_F" class="form-control "  placeholder="تاريخ انتهاء العقد" id="emp_contract_F" autocomplete="off" value="<?=(!empty($employee['new_e_date'])? $employee['new_e_date'] : '' )?>" required>
						  </div>
						</div>
						<div class="form-group col-md-4">
							<label class="col-form-label " for="back">اسم البنك</label>
							<input type="text" name="back" class="form-control"  placeholder="اسم البنك" id="back" autocomplete="off" value="<?=(!empty($employee['user_bank_name'])? $employee['user_bank_name'] : '' )?>">
						  </div>

						  <div class="col-md-4">
							<div class="form-group">
						  <label class="col-form-label logindata " for="account_number">رقم الحساب</label>
						  <input type="text" class="form-control ltr logindata" data-toggle="tooltip" id="account_number"   name="account_number" placeholder="" autocomplete="on"  value="<?=(!empty($employee['user_account_bank'])? $employee['user_account_bank'] : '' )?>"   title="رقم الحساب ابنكي" >
						</div>
						</div>

					</div>
					

					

					</div>
					
				</div>
			</div>
		</div>



		<!--  -->
		<div id="tab4" class="tab-content">
			<div class="col-md-12">
			<div class="card ">
				<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
					<h3 class="card-title">الموظف الهوية</h3>
					<div class="card-tools">
					  <button type="button" class="btn btn-tool" data-card-widget="collapse">
						<i class="fas fa-minus"></i>
					  </button>
					</div>
				</div>
				
				<div class="card-body" >
				
				<input type="hidden" value="" name="">

				  <div class="row">		  
					<div class="col-md-3">
					  <!-- text input -->
					  <div class="form-group">
						<label class="col-form-label " for="emp_Id">رقم الهوية</label>
						<input type="text" class="form-control " data-toggle="tooltip" title="ادخل رقم الهوية" id="emp_Id" name="emp_Id" placeholder=""  value="<?=(!empty($employee['Id_h'])? $employee['Id_h'] : '' )?>">
					  </div>
					</div>
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label" for="ID_Emp_date_S">تاريخ الاصدار</label>
						<input type="date" name="ID_Emp_date_S" class="form-control "  placeholder="تاريخ الاصدار" id="ID_Emp_date_S" autocomplete="off" value="<?=(!empty($employee['start_date_h'])? $employee['start_date_h'] : '' )?>" >
					  </div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label " for="ID_Emp_date_F">تاريخ الانتهاء </label>
						  <input type="date" name="ID_Emp_date_F" class="form-control"  placeholder="تاريخ الانتهاء" id="ID_Emp_date_F" autocomplete="off" value="<?=(!empty($employee['end_date_h'])? $employee['end_date_h'] : '' )?>">
						</div>
					  </div>
					  <div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label" for="ID_file">ارفقها</label>
						  <input type="file" name="ID_file" class="form-control"  placeholder="ارفق الهوية" id="ID_file" autocomplete="off">

						  <div class="file_control" style="display:<?=(!empty($employee['path_h']) ? 'block' :'none')?>;padding-right:7px;padding-bottom: 10px;">
                          <a href="<?=(!empty($employee['path_h']) ? $employee['path_h'] :'')?>" download>
                          <button type="button" value="<?=(!empty($employee['UserID']) ? $employee['UserID'].'.1' :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                                </a>
								
						  </div>


						</div>
					  </div>
				</div>

				<div class="row">		  
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_IDD">رقم رخصة السواقة</label>
						<input type="text" class="form-control " data-toggle="tooltip" title="ادخل رقم الهوية" id="emp_IDD" name="emp_IDD" placeholder=""  value="<?=(!empty($employee['Id_license'])? $employee['Id_license'] : '' )?>">
					  </div>
					</div>
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_IDD_Date_S">تاريخ الاصدار</label>
						<input type="date" name="emp_IDD_Date_S" class="form-control"  placeholder="تاريخ الاصدار" id="emp_IDD_Date_S" autocomplete="off" value="<?=(!empty($employee['start_date_license'])? $employee['start_date_license'] : '' )?>" >
					  </div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="emp_IDD_Date_F">تاريخ الانتهاء </label>
						  <input type="date" name="emp_IDD_Date_F" class="form-control"  placeholder="تاريخ الانتهاء" id="emp_IDD_Date_F" autocomplete="off" value="<?=(!empty($employee['end_date_license'])? $employee['end_date_license'] : '' )?>">
						</div>
					  </div>
					  <div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="IDD_file">ارفقها</label>
						  <input type="file" name="IDD_file" class="form-control"  placeholder="ارفق الهوية" id="IDD_file" autocomplete="off" value="">
						  <div class="file_control" style="display:<?=(!empty($employee['path_license']) ? 'block' :'none')?>;padding-right:7px;padding-bottom: 10px;">
						  <a href="<?=(!empty($employee['path_license']) ? $employee['path_license'] :'')?>" download>
                          <button type="button" value="<?=(!empty($employee['UserID']) ? $employee['UserID'].'.1' :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                                </a></div>
						</div>
					  </div>
				</div>

				<div class="row">		  
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_passportID">رقم جواز</label>
						<input type="text" class="form-control " data-toggle="tooltip" title="ادخل رقم الجواز" id="emp_passportID" name="emp_passportID" placeholder=""  value="<?=(!empty($employee['Id_passport'])? $employee['Id_passport'] : '' )?>">
					  </div>
					</div>
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_Passport_ID_Date_S">تاريخ الاصدار</label>
						<input type="date" name="emp_Passport_ID_Date_S" class="form-control "  placeholder="تاريخ الاصدار" id="emp_Passport_ID_Date_S" autocomplete="off" value="<?=(!empty($employee['start_date_passport'])? $employee['start_date_passport'] : '' )?>" >
					  </div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="emp_passport_ID_Date_F">تاريخ الانتهاء </label>
						  <input type="date" name="emp_passport_Cer_ID_Date_F" class="form-control"  placeholder="تاريخ الانتهاء" id="emp_passport_Cer_ID_Date_F" autocomplete="off" value="<?=(!empty($employee['end_date_passport'])? $employee['end_date_passport'] : '' )?>" >
						</div>
					  </div>
					  <div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="Passport_ID_file">ارفقها</label>
						  <input type="file" name="Passport_ID_file" class="form-control"  placeholder="ارفق الهوية" id="Passport_ID_file" autocomplete="off" value="">
						  <div class="file_control" style="display:<?=(!empty($employee['path_passport']) ? 'block' :'none')?>;padding-right:7px;padding-bottom: 10px;">
						  <a href="<?=(!empty($employee['path_passport']) ? $employee['path_passport'] :'')?>" download>
                          <button type="button" value="<?=(!empty($employee['UserID']) ? $employee['UserID'].'.1' :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                                </a></div>
						</div>
					  </div>
				</div>

				<div class="row">		  
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_CerID">رقم الشهادة الصحية</label>
						<input type="text" class="form-control " data-toggle="tooltip" title="ادخل رقم الشهادة الصحية" id="emp_CerID" name="emp_CerID" placeholder=""  value="<?=(!empty($employee['Id_health'])? $employee['Id_health'] : '' )?>">
					  </div>
					</div>
					<div class="col-md-3">
					  <div class="form-group">
						<label class="col-form-label " for="emp_Cer_ID_Date_S">تاريخ الاصدار</label>
						<input type="date" name="emp_Cer_ID_Date_S" class="form-control"  placeholder="تاريخ الاصدار" id="emp_Cer_ID_Date_S" autocomplete="off" value="<?=(!empty($employee['start_date_health'])? $employee['start_date_health'] : '' )?>" >
					  </div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="emp_Cer_ID_Date_F">تاريخ الانتهاء </label>
						  <input type="date" name="emp_Cer_ID_Date_F" class="form-control"  placeholder="تاريخ الانتهاء" id="emp_IDD_Date_F" autocomplete="off" value="<?=(!empty($employee['end_date_health'])? $employee['end_date_health'] : '' )?>" >
						</div>
					  </div>
					  <div class="col-md-3">
						<div class="form-group">
						  <label class="col-form-label  " for="Cer_ID_file">ارفقها</label>
						  <input type="file" name="Cer_ID_file" class="form-control"  placeholder="ارفق الهوية" id="Cer_ID_file" autocomplete="off" value="">
						  <div class="file_control" style="display:<?=(!empty($employee['path_health']) ? 'block' :'none')?>;padding-right:7px;padding-bottom: 10px;">
						  <a href="<?=(!empty($employee['path_health']) ? $employee['path_health'] :'')?>" download>
                          <button type="button" value="<?=(!empty($employee['UserID']) ? $employee['UserID'].'.1' :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
                                </a></div>
						</div>
					  </div>
				</div>
					
				

				

				</div>
				
			</div>
		</div>
	</div>
	<div id="tab5" class="tab-content">
		<div class="col-md-12">
		<div class="card ">
			<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
				<h3 class="card-title">تفاصيل اخرى عن الموظف</h3>
				<div class="card-tools">
				  <button type="button" class="btn btn-tool" data-card-widget="collapse">
					<i class="fas fa-minus"></i>
				  </button>
				</div>
			</div>
			
			<div class="card-body" >
			
			<input type="hidden" value="" name="">

			  <div class="row">		  
				<div class="col-md-6">
				  <!-- text input -->
				  <div class="form-group">
					<label class="col-form-label " for="emp_OtherPhone">هاتف اخر</label>
					<input type="text" class="form-control " data-toggle="tooltip" title="هل لديك رقم اخر" id="emp_OtherPhone" name="emp_OtherPhone" placeholder=""  value="<?=(!empty($employee['ohter_phone'])? $employee['ohter_phone'] : '' )?>" >
				  </div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
					  <label class="col-form-label " for="state_human">الحالة الصحية</label>
					  <input type="text" name="state_human" class="form-control"  placeholder="الحالة الصحية" id="state_human" autocomplete="off" value="<?=(!empty($employee['HealthCondition'])? $employee['HealthCondition'] : '' )?>">
					</div>
				  </div>
				<div class="col-md-4">
				  <div class="form-group">
					<label class="col-form-label " for="marital_status">الحالة الاجتماعية</label>
					<select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="الحالة الاجتماعية" id="marital_status" name="marital_status"  >
					<?php
					if(!empty($employee['marital_status']))
					{
					if($employee['marital_status']==1) $val="متزوج";
					if($employee['marital_status']==2) $val="اعزب";	
					if($employee['marital_status']==3) $val="ارمل";
					if($employee['marital_status']==4) $val="اخرى";
						?>
					<option value="<?= $employee['marital_status'] ?>" selected><?= $val?></option>
						<?php
					}	
					?>
                            <option value="1">متزوج</option>
                            <option value="2">اعزب</option>
							<option value="2">ارمل</option>
							<option value="2">اخرى</option>
                    </select>
				  </div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
					  <label class="col-form-label " for="emp_Sex">الجنس</label>
					  <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="الجنس" id="emp_Sex" name="emp_Sex"  >
					  <?php
					if(!empty($employee['Sex']))
					{
					if($employee['Sex']==1) $val_="ذكر";
					if($employee['Sex']==2) $val_="انثى";	
						?>
						
					<option value="<?= $employee['Sex'] ?>" selected><?= $val_?></option>
						<?php
					}	
					?>
					  			<option value="1">ذكر</option>
							  <option value="2">انثى</option>
					  </select>
					</div>
				  </div>
				  <div class="col-md-4">
					<div class="form-group">
					  <label class="col-form-label " for="emp_address">العنوان</label>
					  <input type="text" name="emp_address" class="form-control"  placeholder="العنوان" id="emp_address" autocomplete="off" value="<?=(!empty($employee['user_address'])? $employee['user_address'] : '' )?>">
					</div>
				  </div>
			</div>
				
			

			

			</div>
			
		</div>
	</div>
</div>
	<!--  -->
	<!--  -->

<!--  -->

			<!--  -->
		</form>
		</div>
				
			
		
		<!-- </div> -->
		
	</div>

			
    </section>





<?php
$GetBranch = true;
 include_once('inc/footer.php');
?>

<script>
var TheBranches  = [];

function refreshBranchSelectpicker(selector) {
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.selectpicker === 'function') {
        window.jQuery(selector).selectpicker('refresh');
    }
}

function populateBranchSelectOptions(selector, items) {
    var select = document.querySelector(selector);
    if (!select) {
        return;
    }

    select.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- اختر --';
    select.appendChild(placeholder);

    if (Array.isArray(items)) {
        items.forEach(function(item) {
            var optionValue = item && item.data ? item.data.id : item && item.Id;
            var optionLabel = item && item.data ? item.data.name : item && item.Name;

            if (optionValue === undefined || optionLabel === undefined) {
                return;
            }

            var option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionLabel;
            select.appendChild(option);
        });
    }

    refreshBranchSelectpicker(selector);
}

function applyBranchFormPayload(payload) {
    var data = payload || {};

    if (data.groub_list && !data.groub) {
        data.groub = data.groub_list;
    }

    populateBranchSelectOptions('#user_section', data.section || []);
    populateBranchSelectOptions('#user_jobtitle', data.jobtitle || []);
    populateBranchSelectOptions('#user_grade', data.JobGrade || []);
    populateBranchSelectOptions('#user_shift', data.Shift || []);
    populateBranchSelectOptions('#user_finger', data.fingerprint || []);
    populateBranchSelectOptions('#user_insuance', data.insurance || []);
    populateBranchSelectOptions('#user_group_', data.groub || data.groub_list || []);
    populateBranchSelectOptions('#user_type', data.tblemploymenttype || []);
    populateBranchSelectOptions('#user_related_to', data.user_related_to || []);
    populateBranchSelectOptions('#user_manager', data.user_manager || []);
}

function getActiveEmployerBranchValue() {
    var branchSelect = document.getElementById('emp_branch');
    if (!branchSelect) {
        return '';
    }

    if (!branchSelect.value && branchSelect.options.length > 0) {
        branchSelect.value = branchSelect.options[0].value;
        refreshBranchSelectpicker('#emp_branch');
    }

    return branchSelect.value || '';
}

function requestBranchFormPayload(selectedValue) {
    var branchValue = selectedValue || getActiveEmployerBranchValue();
    if (!branchValue) {
        return Promise.resolve(false);
    }

    if (typeof window.showPreloader === 'function') {
        window.showPreloader();
    }

    return fetch('hr-app/index.php?action=allUserinfo_Search', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({ value: branchValue }).toString()
    })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        })
        .then(function(response) {
            applyBranchFormPayload(response.data || response);
            return true;
        })
        .catch(function(error) {
            console.error('Branch dropdown load failed', error);
            if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error('Unable to load branch dropdown data.');
            }
            return false;
        })
        .finally(function() {
            if (typeof window.hidePreloader === 'function') {
                window.hidePreloader();
            }
        });
}

function shouldBootstrapBranchFormPayload() {
    var branchSelect = document.getElementById('emp_branch');
    var jobTitleSelect = document.getElementById('user_jobtitle');
    var sectionSelect = document.getElementById('user_section');

    return !!(branchSelect && jobTitleSelect && sectionSelect && jobTitleSelect.options.length === 0 && sectionSelect.options.length === 0);
}

function bootstrapBranchFormPayload() {
    if (shouldBootstrapBranchFormPayload()) {
        requestBranchFormPayload();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var branchSelect = document.getElementById('emp_branch');

    if (branchSelect && !branchSelect.dataset.payloadLoaderBound) {
        branchSelect.dataset.payloadLoaderBound = '1';
        branchSelect.addEventListener('change', function() {
            requestBranchFormPayload(this.value);
        });
    }

    setTimeout(bootstrapBranchFormPayload, 0);
    setTimeout(bootstrapBranchFormPayload, 500);
});

window.addEventListener('load', function() {
    setTimeout(bootstrapBranchFormPayload, 0);
});


$(document).ready(function(){
	
$(document).on('click', '#save-data', function(){
	$('#user_fm').trigger('submit');
});
//  تحميل ملفات المستخدم 
// $(document).on('click', '#user_fm #download_file', function(){

// var file_id = $(this).val();
// var parts = file_id.split('.'); 
// var id = 33;  
// var type = 1;
// window.location.href = './hr/download-file?id=' + id + '&type=' + type+'';
// });


// هنا عند اختيار الفرع يوجد يظهر كل ما يخص الفرع
function loadBranchFormData(selectedValue) {
    if (!selectedValue) {
        return;
    }

    $.ajax({
        url: 'hr-app/index.php?action=allUserinfo_Search',
        type: 'POST',
        data: { value: selectedValue },
		dataType:"json",
		beforeSend:function(){
			$('#preloading').show();
			}, 
        success: function(response) {
            var payload = response.data || response;

            if (payload.groub_list && !payload.groub) {
                payload.groub = payload.groub_list;
            }

            populateSelect('#user_section', payload.section || []);
            populateSelect('#user_jobtitle', payload.jobtitle || []);
            populateSelect('#user_grade', payload.JobGrade || []);
            populateSelect('#user_shift', payload.Shift || []);
            populateSelect('#user_finger', payload.fingerprint || []);
            populateSelect('#user_insuance', payload.insurance || []);
            populateSelect('#user_group_', payload.groub || payload.groub_list || []);
            populateSelect('#user_type', payload.tblemploymenttype || []);
            populateSelect('#user_related_to', payload.user_related_to || []);
            populateSelect('#user_manager', payload.user_manager || []);

            $('#preloading').hide();
        },
        error: function(xhr) {
            $('#preloading').hide();
            console.error('Branch dropdown load failed', xhr.responseText || xhr.statusText);
            toastr.error('حدث خطأ أثناء جلب البيانات');
        }
    });
}

$('#emp_branch').change(function() {
        if (this.dataset && this.dataset.payloadLoaderBound === '1') {
            return;
        }
        var selectedValue = $(this).val(); 
        loadBranchFormData(selectedValue);
        return;

        if (selectedValue) {
            $.ajax({
                url: 'hr-app/index.php?action=allUserinfo_Search',
                type: 'POST',
                data: { value: selectedValue },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
                success: function(response) { 
                    // Handle API response - map groub_list to groub for backwards compatibility
                    if (response.groub_list && !response.groub) {
                        response.groub = response.groub_list;
                    }
                    
                    populateSelect('#user_section', response.section);
                    populateSelect('#user_jobtitle', response.jobtitle);
                    populateSelect('#user_grade', response.JobGrade);
                    populateSelect('#user_shift', response.Shift);
                    populateSelect('#user_finger', response.fingerprint || []);
                    populateSelect('#user_insuance', response.insurance || []);
                    populateSelect('#user_group_', response.groub || response.groub_list || []);
                    populateSelect('#user_type', response.tblemploymenttype || []);
                    populateSelect('#user_related_to', response.user_related_to || []);
                    populateSelect('#user_manager', response.user_manager || []);
                    
                    $('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        } 
    });
var initialBranchValue = $('#emp_branch').val();
if (initialBranchValue && $('#user_jobtitle option').length === 0 && $('#user_section option').length === 0) {
    loadBranchFormData(initialBranchValue);
}
	function populateSelect(selectId, items) {
    var select = $(selectId);
    select.empty(); 
    // Add default empty option
    select.append('<option value="">-- اختر --</option>');
    
    if (items && items.length > 0) {		
        $.each(items, function(index, item) {
            // Handle both formats: item.data.id or item.Id
            var id = item.data ? item.data.id : item.Id;
            var name = item.data ? item.data.name : item.Name;
            if (id !== undefined && name !== undefined) {
                select.append('<option value="' + id + '">' + name + '</option>');
            }
        });
    } 
	select.selectpicker('refresh');
}



				



$('#user_fm').on('submit', function(e){  
	e.preventDefault();
	
	var form_data = new FormData(this);
	var branchValue = $('#emp_branch').val();
	if (branchValue) {
		form_data.set('emp_branch', branchValue);
		form_data.set('BranchID', branchValue);
		form_data.set('branchs_list', branchValue);
	}
	if($(this).valid()){
		
	//var chk = $('#store_stoped').is(':checked');  
	$.ajax({
		type: 'POST',
            url: "hr-app/index.php?action=employer-add",
            data: form_data,
            contentType: false, 
            processData: false, 
            dataType: "json", 
		beforeSend:function(){
			$('#preloading').show();
			
		}, 
		success:function(data){
			
				if(data.result){
					//toastr.success(data.msg);
					$("#user_id").val(data.emp_id);
					window.location.href = 'employer-list';					
				}else{
					toastr.error(data.msg);
                    $('#preloading').hide();
				}
				
				if(data.reload){
					window.location = document.URL ;
				}


					
			
			
			
			
			
		}
	});
	
	
	}
});

$("#rempic").on('click', function(e){ 

	var warning = confirm("هل انت متأكد من انك ترغب بحذف شعار الجهة ؟ سيتم الحذف مباشرة بعد التأكيد");
	if (warning) {
		$("#logoaction").val('removelogo');
		$('#ouLogoForm').trigger('submit');
		//$(".content-numpad button").not(".content-numpad span button").prop('disabled', true);
		
	}

});


  
 $('#create_account').change(function(){

    if($(this).is(':checked')){
        $(".user_account").show();
       $(".logindata").prop('required',true);
	   $("label.logindata").addClass('required');
    }
    else
    {
        $(".user_account").hide();
       $(".logindata").prop('required',false);
       $("label.logindata").removeClass('required');
    }    

});  


$('#send_pass').change(function(){

    if($(this).is(':checked')){
        $(".user_pass").show();
      // $("#user_pass").prop('required',true);
	   $("label.user_pass").addClass('required');
    }
    else
    {
        $(".user_pass").hide();
      // $("#user_pass").prop('required',false);
       $("label.user_pass").removeClass('required');
    }    

}); 



 

$('#user_fm').validate({
	errorElement: 'span',
	errorPlacement: function (error, element) {
	  error.addClass('invalid-feedback');
	  element.closest('div').append(error);
	},
	highlight: function (element, errorClass, validClass) {
	  $(element).addClass('is-invalid');
	},
	unhighlight: function (element, errorClass, validClass) {
	  $(element).removeClass('is-invalid');
	}
});
 
 
// هذا من اجل اظهار واخفاء التبيوات


// جعل التبويب الأول مفتوحًا بشكل افتراضي
$('.tab-button').first().click();
//  اظهار خبرات الموظف



// 










// الخبرات 

// 

// يسمح بالارقام فقط لا غير
$('#emp_salary').on('keypress', function(e) {
        var key = e.which || e.keyCode;
        if (key >= 48 && key <= 57) {
            return true;
        }    
        return false;
    });











});
 
function openTab(event, tabName) {
    $('.tab-content').removeClass('active');
    $('.tab-button').removeClass('active');
    $('#' + tabName).addClass('active');
    $(event.currentTarget).addClass('active');
}
 

</script>





