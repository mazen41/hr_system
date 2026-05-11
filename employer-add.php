<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'اضافة موظف/متقدم';

$appid = 'HR';

$page_perm = ['إضافة موظف', 'عرض موظف', 'تعديل موظف'];
include_once('inc/header.php');

$over = 0;
$all_branches = $User->allBranches();

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

function rollsList($connect)
{
	$roles = [];
	$query = "select GroupID,GroupName from tblusergroups where IsSystem is null ";
	$stm = $connect->prepare($query);
	$stm->execute();
	if ($stm->rowCount() > 0) {
		return $stm->fetchAll();
	}
	return $roles;
}

function getEmployerBranchFormData($connect, $branchId)
{
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

	$params = [':BranchID' => $branchId];

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

if (isset($_GET['id'])) {
	$id = (int) $_GET['id'];

	$parma = array(':id' => $id);

	$query = "SELECT u.UserID,u.lastversion, u.IsSystem,u.AllowedBranches, u.UserGroupID, u.UserEmail,u.FirstName, u.SecondName, u.LastName, u.Photo, u.Phone, u.Note, u.IsDisabled ,b.branch_name, g.GroupName,w.related_to,w.FirstName as f_n,w.LastName as l_n,m.manager,m.FirstName as f_n_m,m.LastName as l_n_m,
		u.FingerID,k.SectionID,k.BranchID as actual_branch,k.GroupID,k.GradeID,u.user_insurance,k.shiftID,k.TypeID,k.fingerID,k.jobtitleID,
		k.Salary,k.Currency,k.new_s_date,k.new_e_date,u.user_bank_name,u.user_account_bank,u.ohter_phone,u.HealthCondition,
		u.Sex,u.marital_status,u.user_address,u.Id_h,u.start_date_h,u.end_date_h,u.path_h,u.Id_license,u.start_date_license,u.end_date_license,
		u.path_license,u.Id_passport,u.start_date_passport,u.end_date_passport,u.path_passport,u.Id_health,u.start_date_health,
		u.end_date_health,u.path_health,a.Name as section_name,j.Name as jobtitle_name,c.Name as group_name,f.Name as name_grade,
		h.Name as name_n, u.isemp, u.applicant_status, u.path_residency, u.path_qualifications, u.path_experience, u.path_service_cert, u.path_police_clearance
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
		where u.UserID =:id  AND  (g.IsSystem is null OR u.UserGroupID is null)
		";
	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);

	if ($stm->rowCount() > 0) {
		$employee = $stm->fetch();

		$actual_branch_for_query = !empty($employee['actual_branch']) ? $employee['actual_branch'] : (isset($_SESSION['branch']) ? $_SESSION['branch'] : 1);

		$parma_ = array(':BranchID' => $actual_branch_for_query);

		$query2 = " SELECT c.Id, c.Name FROM tblsection AS c LEFT JOIN tblsection AS d ON c.Id = d.ParentID WHERE c.ParentID IS NOT NULL AND d.Id IS NULL and c.BranchID = :BranchID ";
		$query3 = " SELECT Id ,Name FROM tblgroup where BranchID = :BranchID";
		$query4 = " SELECT Id ,Name FROM tbljobgrade where BranchID = :BranchID";
		$query5 = " SELECT Id , Name FROM  tbinsurance  where BranchID = :BranchID and state=1";
		$query6 = " SELECT ShiftID ,ShiftName FROM  tbshift  where BranchID = :BranchID and ShiftState=0";
		$query7 = " SELECT Id ,Name FROM  tblemploymenttype where BranchID = :BranchID";
		$query8 = " SELECT FingerprintID ,FingerprintName FROM  tbfingerprint  where BranchID = :BranchID and FingerprintState=1";
		$query9 = " SELECT Id ,Name FROM tbljobtitle where BranchID = :BranchID";
		$query10 = " SELECT UserID ,FirstName,LastName FROM tblusers where BranchID = :BranchID and isemp is null and UserID Not in(SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)";
		$query11 = " SELECT UserID ,FirstName,LastName FROM tblusers where BranchID = :BranchID and isemp is NOT null";

		$stm_ = $connect_pdo->prepare($query2);
		$stm_->execute($parma_);
		if ($stm_->rowCount() > 0) {
			$section = $stm_->fetchAll();
		}
		$stm_1 = $connect_pdo->prepare($query3);
		$stm_1->execute($parma_);
		if ($stm_1->rowCount() > 0) {
			$groub = $stm_1->fetchAll();
		}
		$stm_2 = $connect_pdo->prepare($query4);
		$stm_2->execute($parma_);
		if ($stm_2->rowCount() > 0) {
			$jobgrade = $stm_2->fetchAll();
		}
		$stm_3 = $connect_pdo->prepare($query5);
		$stm_3->execute($parma_);
		if ($stm_3->rowCount() > 0) {
			$insurance = $stm_3->fetchAll();
		}
		$stm_4 = $connect_pdo->prepare($query6);
		$stm_4->execute($parma_);
		if ($stm_4->rowCount() > 0) {
			$shift = $stm_4->fetchAll();
		}
		$stm_5 = $connect_pdo->prepare($query7);
		$stm_5->execute($parma_);
		if ($stm_5->rowCount() > 0) {
			$employmenttyp = $stm_5->fetchAll();
		}
		$stm_6 = $connect_pdo->prepare($query8);
		$stm_6->execute($parma_);
		if ($stm_6->rowCount() > 0) {
			$fingerprint = $stm_6->fetchAll();
		}
		$stm_7 = $connect_pdo->prepare($query9);
		$stm_7->execute($parma_);
		if ($stm_7->rowCount() > 0) {
			$jobtitle = $stm_7->fetchAll();
		}
		$stm_8 = $connect_pdo->prepare($query10);
		$stm_8->execute($parma_);
		if ($stm_8->rowCount() > 0) {
			$user_relate = $stm_8->fetchAll();
		}
		$stm_9 = $connect_pdo->prepare($query11);
		$stm_9->execute($parma_);
		if ($stm_9->rowCount() > 0) {
			$user_manager = $stm_9->fetchAll();
		}

		$user_insurance = !empty($employee['user_insurance']) ? array_unique(explode(',', $employee['user_insurance'])) : [];
		$user_shift = !empty($employee['shiftID']) ? array_unique(explode(',', $employee['shiftID'])) : [];
		$user_finger_print_type = !empty($employee['fingerID']) ? array_unique(explode(',', $employee['fingerID'])) : [];

	} else {
		echo '<script> location.replace("applicants-list"); </script>';
		exit;
	}
}

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
?>

<style>
	label.error {
		color: red;
		font-size: 0.83rem;
	}

	.bootstrap-select .dropdown-toggle:focus {
		outline: unset !important;
		outline-offset: unset !important;
	}

	.nav-tabs .nav-link {
		font-weight: bold;
		color: #495057;
		background-color: #f8f9fa;
		border: 1px solid #dee2e6;
		margin-left: 5px;
		border-radius: 5px 5px 0 0;
	}

	.nav-tabs .nav-link.active {
		color: #007bff;
		background-color: #fff;
		border-color: #dee2e6 #dee2e6 #fff;
	}

	#updateButton {
		display: none;
	}
</style>

<div class="content-header page-nav">
	<div class="container-fluid">
		<div class="row">
			<div class="col-7">
				<span class="page-title"><?= (empty($employee) ? 'إضافة سجل' : 'تعديل السجل') ?></span>
			</div>
			<div class="col-5 text-left">
				<button type="button" class="btn btn-default" onclick="history.back()" id="cancel-bt">
					<i class="fa fa-times"></i><span class="d-none d-sm-inline"> عودة</span>
				</button>
				<button type="button" class="btn btn-success" id="save-data">
					<i class="fas fa-save"></i><span class="d-none d-sm-inline">
						<?= (empty($employee) ? 'حفظ' : 'تحديث') ?></span>
				</button>
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
			<div class="col-12">
				<!-- ======= NAV TABS ======= -->
				<ul class="nav nav-tabs mb-3" id="employeeTabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1" role="tab"
							aria-controls="tab1" aria-selected="true">البيانات الأساسية</a>
					</li>
					<!-- nav items for employee tabs — hidden/shown by JS, NOT the panes -->
					<li class="nav-item employee-only">
						<a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2" role="tab" aria-controls="tab2"
							aria-selected="false">البيانات الوظيفية</a>
					</li>
					<li class="nav-item employee-only">
						<a class="nav-link" id="tab3-tab" data-toggle="tab" href="#tab3" role="tab" aria-controls="tab3"
							aria-selected="false">العقد</a>
					</li>
					<li class="nav-item applicant-only" style="display:none;">
						<a class="nav-link" id="tab-applicant-tab" data-toggle="tab" href="#tab-applicant" role="tab"
							aria-controls="tab-applicant" aria-selected="false">مرفقات المتقدم</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="tab4-tab" data-toggle="tab" href="#tab4" role="tab" aria-controls="tab4"
							aria-selected="false">الهوية والفحص الطبي</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="tab5-tab" data-toggle="tab" href="#tab5" role="tab" aria-controls="tab5"
							aria-selected="false">اخرى</a>
					</li>
				</ul>
			</div>
		</div>

		<form class="form-horizontal" role="form" action="#" method="post" id="user_fm" enctype="multipart/form-data">
			<input type="hidden" name="user_id" id="user_id"
				value="<?= (!empty($employee['UserID']) ? (int) $employee['UserID'] : '') ?>">

			<!-- ======= TAB CONTENT ======= -->
			<!-- NOTE: employee-only / applicant-only classes are REMOVED from tab-pane divs.
					   They are only on the nav-item elements above. Bootstrap owns pane visibility. -->
			<div class="tab-content" id="employeeTabsContent">

				<!-- TAB 1 — البيانات الأساسية -->
				<div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">البيانات الأساسية</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">

								<!-- نوع التسجيل والحالة -->
								<div class="row bg-light pt-3 pb-2 mb-3 rounded" style="border: 1px dashed #ccc;">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="user_role_type">نوع
												التسجيل</label>
											<select class="form-control" id="user_role_type" name="user_role_type">
												<option value="1" <?= (empty($employee) || $employee['isemp'] == 1) ? 'selected' : '' ?>>موظف (Employee)</option>
												<option value="2" <?= (!empty($employee) && $employee['isemp'] == 2) ? 'selected' : '' ?>>متقدم (Applicant)</option>
											</select>
										</div>
									</div>
									<div class="col-md-4 applicant-status-container" style="display:none;">
										<div class="form-group">
											<label class="col-form-label required" for="applicant_status">حالة
												المتقدم</label>
											<select class="form-control" id="applicant_status" name="applicant_status">
												<option value="0" <?= (!empty($employee) && $employee['applicant_status'] == 0) ? 'selected' : '' ?>>قيد الانتظار
													(Pending)</option>
												<option value="1" <?= (!empty($employee) && $employee['applicant_status'] == 1) ? 'selected' : '' ?>>مقبول
													(Approved)</option>
												<option value="2" <?= (!empty($employee) && $employee['applicant_status'] == 2) ? 'selected' : '' ?>>مرفوض
													(Rejected)</option>
											</select>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="emp_name">الأسم الأول</label>
											<input type="text" class="form-control" id="emp_name" name="emp_name"
												value="<?= (!empty($employee['FirstName']) ? $employee['FirstName'] : '') ?>"
												required>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="emp_name2">الأسم الأوسط</label>
											<input type="text" class="form-control" id="emp_name2" name="emp_name2"
												value="<?= (!empty($employee['SecondName']) ? $employee['SecondName'] : '') ?>">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="emp_lname">اللقب</label>
											<input type="text" class="form-control" id="emp_lname" name="emp_lname"
												value="<?= (!empty($employee['LastName']) ? $employee['LastName'] : '') ?>"
												required>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="mobile">جوال</label>
											<input type="text" class="form-control number-format" id="mobile"
												name="mobile"
												value="<?= (!empty($employee['Phone']) ? $employee['Phone'] : '') ?>">
										</div>
									</div>
									<div class="col-md-4 employee-only">
										<div class="form-group">
											<label class="col-form-label" for="emp_fingerprint">البصمة</label>
											<input type="text" class="form-control" id="emp_fingerprint"
												name="emp_fingerprint"
												value="<?= (!empty($employee['FingerID']) ? $employee['FingerID'] : '') ?>">
										</div>
									</div>
									<div class="col-md-4 employee-only">
										<div class="form-group">
											<label class="col-form-label logindata" for="email">البريد
												الالكتروني</label>
											<input type="email" class="form-control ltr logindata" name="email"
												value="<?= (!empty($employee['UserEmail']) ? $employee['UserEmail'] : '') ?>">
											<p class="help-block user_account"
												style="display: <?= (empty($employee) || !empty($employee['UserGroupID']) ? '' : 'none') ?>">
												يستخدم في تسجيل الدخول</p>
										</div>
									</div>
								</div>

								<div class="row employee-only">
									<div class="col-md-4">
										<div class="form-group">
											<label for="emp_branch" class="col-form-label required">الفرع
												الافتراضي</label>
											<select class="form-control select_branch" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد الفرع الافتراضي" id="emp_branch" name="emp_branch">
												<?php
												if ($defaultBranchId !== '' && !isset($all_branches[$defaultBranchId]) && !empty($defaultBranchName)) {
													echo '<option value="' . $defaultBranchId . '" selected>' . $defaultBranchName . '</option>';
												}
												foreach ($all_branches as $id => $name) {
													$selected = ((string) $id === $defaultBranchId) ? 'selected' : '';
													echo '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
												}
												?>
											</select>
										</div>
									</div>

									<?php if (empty($employee)) { ?>
										<div class="col-md-4">
											<div class="form-group">
												<label class="col-form-label logindata user_pass" for="user_pass">كلمة
													المرور</label>
												<input type="text" class="form-control ltr logindata" id="user_pass"
													name="user_pass" title="إنشئ كلمة مرور للمستخدم">
											</div>
										</div>
									<?php } ?>

									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="user_photo">صور شخصية</label>
											<input type="file" name="user_photo" id="user_photo" class="form-control">
											<?php if (!empty($employee['Photo'])): ?>
												<a href="<?= $employee['Photo'] ?>" download>تنزيل الصورة الحالية</a>
											<?php endif; ?>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="col-form-label" for="emp_note">ملاحظة</label>
											<input type="text" class="form-control" name="emp_note"
												value="<?= (!empty($employee['Note']) ? $employee['Note'] : '') ?>">
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label class="switch switch-danger ml-auto col-form-label"
												title="ايقاف المستخدم من الدخول للنظام">
												<label class="control-label" for="desable_user"></label>
												<input type="checkbox" name="desable_user" id="desable_user" value=""
													<?= !empty($employee['IsDisabled']) ? 'checked' : '' ?>>
												<span></span> إيقاف السجل/الدخول
											</label>
										</div>
									</div>
								</div>

							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab1 -->

				<!-- TAB 2 — البيانات الوظيفية -->
				<!-- FIX: removed "employee-only" from this div — Bootstrap controls pane visibility -->
				<div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
					<div class="col-md-12">
						<div class="card card-outline">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">البيانات الوظيفية (حساب الموظف)</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">
								<div class="row user_account">
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_jobtitle" class="col-form-label logindata required">المسمى
												الوظيفي</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle"
												required>
												<?php
												if (!empty($jobtitle)) {
													if (isset($employee['jobtitleID'], $employee['jobtitle_name'])) {
														echo '<option value="' . $employee['jobtitleID'] . '" selected>' . $employee['jobtitle_name'] . '</option>';
													}
													foreach ($jobtitle as $sec) {
														echo '<option value="' . $sec["Id"] . '">' . $sec["Name"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_section"
												class="col-form-label logindata required">القسم</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد باي قسم" id="user_section" name="user_section" required>
												<?php
												if (!empty($section)) {
													if (isset($employee['SectionID'], $employee['section_name'])) {
														echo '<option value="' . $employee['SectionID'] . '" selected>' . $employee['section_name'] . '</option>';
													}
													foreach ($section as $sec) {
														echo '<option value="' . $sec["Id"] . '">' . $sec["Name"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_group_" class="col-form-label logindata">المجموعة
												الوظيفية</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد المجموعة الوظيفية" id="user_group_" name="user_group_">
												<?php
												if (!empty($groub)) {
													if (isset($employee['GroupID'], $employee['group_name'])) {
														echo '<option value="' . $employee['GroupID'] . '" selected>' . $employee['group_name'] . '</option>';
													}
													foreach ($groub as $gro) {
														echo '<option value="' . $gro["Id"] . '">' . $gro["Name"] . '</option>';
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
											<label for="user_grade" class="col-form-label logindata">الدرجة
												الوظيفية</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade">
												<?php
												if (!empty($jobgrade)) {
													if (isset($employee['GradeID'], $employee['name_grade'])) {
														echo '<option value="' . $employee['GradeID'] . '" selected>' . $employee['name_grade'] . '</option>';
													}
													foreach ($jobgrade as $job) {
														echo '<option value="' . $job["Id"] . '">' . $job["Name"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_insuance" class="col-form-label logindata">شركة
												التامين</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد شركة التامين" id="user_insuance" name="user_insuance[]"
												multiple data-selected-text-format="count > 2" data-actions-box="true">
												<?php
												if (!empty($insurance)) {
													foreach ($insurance as $ins) {
														$sel = (!empty($user_insurance) && in_array($ins["Id"], $user_insurance)) ? 'selected' : '';
														echo '<option value="' . $ins["Id"] . '" ' . $sel . '>' . $ins["Name"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_shift" class="col-form-label logindata required">فترات
												العمل</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد فترات العمل" id="user_shift" name="user_shift[]" required
												multiple data-selected-text-format="count > 2" data-actions-box="true">
												<?php
												if (!empty($shift)) {
													foreach ($shift as $shi) {
														$sel = (!empty($user_shift) && in_array($shi["ShiftID"], $user_shift)) ? 'selected' : '';
														echo '<option value="' . $shi["ShiftID"] . '" ' . $sel . '>' . $shi["ShiftName"] . '</option>';
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
											<label for="user_type" class="col-form-label logindata">نمط العمل</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد نمط العمل" id="user_type" name="user_type">
												<?php
												if (!empty($employmenttyp)) {
													if (isset($employee['TypeID'], $employee['name_n'])) {
														echo '<option value="' . $employee['TypeID'] . '" selected>' . $employee['name_n'] . '</option>';
													}
													foreach ($employmenttyp as $typ) {
														echo '<option value="' . $typ["Id"] . '">' . $typ["Name"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_finger" class="col-form-label logindata required">جهاز
												البصمة</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد صلاحية المستخدم" id="user_finger" name="user_finger[]"
												required multiple data-selected-text-format="count > 2"
												data-actions-box="true">
												<?php
												if (!empty($fingerprint)) {
													foreach ($fingerprint as $fin) {
														$sel = (!empty($user_finger_print_type) && in_array($fin["FingerprintID"], $user_finger_print_type)) ? 'selected' : '';
														echo '<option value="' . $fin["FingerprintID"] . '" ' . $sel . '>' . $fin["FingerprintName"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_related_to" class="col-form-label logindata">المستخدم
												المرتبط له</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد اذا كان مرتبط ب مستخدم" id="user_related_to"
												name="user_related_to">
												<?php
												if (!empty($employee['related_to'])) {
													echo '<option value="' . $employee['related_to'] . '" selected>' . $employee['f_n'] . ' ' . $employee['l_n'] . '</option>';
												}
												if (!empty($user_relate)) {
													foreach ($user_relate as $sec) {
														echo '<option value="' . $sec["UserID"] . '">' . $sec["FirstName"] . ' ' . $sec["LastName"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label for="user_manager" class="col-form-label logindata">المدير
												بتاعه</label>
											<select class="form-control logindata selectpicker" data-live-search="true"
												data-container="body" data-size="5" data-width="100%"
												title="حدد اذا كان مرتبط ب مستخدم" id="user_manager"
												name="user_manager">
												<?php
												if (!empty($employee['manager'])) {
													echo '<option value="' . $employee['manager'] . '" selected>' . $employee['f_n_m'] . ' ' . $employee['l_n_m'] . '</option>';
												}
												if (!empty($user_manager)) {
													foreach ($user_manager as $sec) {
														echo '<option value="' . $sec["UserID"] . '">' . $sec["FirstName"] . ' ' . $sec["LastName"] . '</option>';
													}
												}
												?>
											</select>
										</div>
									</div>
								</div>
							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab2 -->

				<!-- TAB 3 — العقد -->
				<!-- FIX: removed "employee-only" from this div -->
				<div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">الموظف والعقد</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="emp_salary">الراتب</label>
											<input type="text" class="form-control" placeholder="0.00" id="emp_salary"
												name="emp_salary"
												value="<?= (!empty($employee['Salary']) ? $employee['Salary'] : '') ?>"
												required>
										</div>
									</div>
									<div class="form-group col-md-4">
										<label class="col-form-label required" for="currency">العملة</label>
										<select class="selectpicker" data-live-search="true" data-container="body"
											data-size="5" data-width="100%" title="أدخل العملة" id="currency"
											name="currency" required>
											<?php if (!empty($employee['Currency'])) { ?>
												<option value="<?= $employee['Currency'] ?>" selected>
													<?= $employee['Currency'] ?></option>
												<option value="<?= $User->currency ?>">عملة النظام</option>
											<?php } else { ?>
												<option value="<?= $User->currency ?>" selected>عملة النظام</option>
											<?php } ?>
										</select>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="emp_contract_S">تاريخ بداية
												العقد</label>
											<input type="date" name="emp_contract_S" class="form-control"
												id="emp_contract_S"
												value="<?= (!empty($employee['new_s_date']) ? $employee['new_s_date'] : '') ?>"
												required>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label required" for="emp_contract_F">تاريخ انتهاء
												العقد</label>
											<input type="date" name="emp_contract_F" class="form-control"
												id="emp_contract_F"
												value="<?= (!empty($employee['new_e_date']) ? $employee['new_e_date'] : '') ?>"
												required>
										</div>
									</div>
									<div class="form-group col-md-4">
										<label class="col-form-label" for="back">اسم البنك</label>
										<input type="text" name="back" class="form-control" id="back"
											value="<?= (!empty($employee['user_bank_name']) ? $employee['user_bank_name'] : '') ?>">
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label logindata" for="account_number">رقم
												الحساب</label>
											<input type="text" class="form-control ltr logindata" id="account_number"
												name="account_number"
												value="<?= (!empty($employee['user_account_bank']) ? $employee['user_account_bank'] : '') ?>">
										</div>
									</div>
								</div>
							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab3 -->

				<!-- TAB applicant — مرفقات المتقدم -->
				<!-- FIX: removed "applicant-only" from this div -->
				<div class="tab-pane fade" id="tab-applicant" role="tabpanel" aria-labelledby="tab-applicant-tab">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">مرفقات وبيانات المتقدم</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label">الإقامة ورخصة العمل (لغير الخليجيين)</label>
											<input type="file" name="file_residency" class="form-control">
											<?php if (!empty($employee['path_residency'])): ?>
												<div class="mt-2">
													<a href="<?= $employee['path_residency'] ?>" download
														class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق الحالي
													</a>
												</div>
											<?php endif; ?>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label">المؤهلات العلمية</label>
											<input type="file" name="file_qualifications" class="form-control">
											<?php if (!empty($employee['path_qualifications'])): ?>
												<div class="mt-2">
													<a href="<?= $employee['path_qualifications'] ?>" download
														class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق الحالي
													</a>
												</div>
											<?php endif; ?>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label">الخبرات العملية</label>
											<input type="file" name="file_experience" class="form-control">
											<?php if (!empty($employee['path_experience'])): ?>
												<div class="mt-2">
													<a href="<?= $employee['path_experience'] ?>" download
														class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق الحالي
													</a>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label">شهادة خدمة (إن وجدت)</label>
											<input type="file" name="file_service_cert" class="form-control">
											<?php if (!empty($employee['path_service_cert'])): ?>
												<div class="mt-2">
													<a href="<?= $employee['path_service_cert'] ?>" download
														class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق الحالي
													</a>
												</div>
											<?php endif; ?>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label">شهادة خلو سوابق</label>
											<input type="file" name="file_police_clearance" class="form-control">
											<?php if (!empty($employee['path_police_clearance'])): ?>
												<div class="mt-2">
													<a href="<?= $employee['path_police_clearance'] ?>" download
														class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق الحالي
													</a>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab-applicant -->

				<!-- TAB 4 — الهوية والفحص الطبي -->
				<div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="tab4-tab">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">الهوية والفحص الطبي (مرفقات مشتركة)</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">
								<!-- الهوية -->
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_Id">رقم الهوية</label>
											<input type="text" class="form-control" id="emp_Id" name="emp_Id"
												value="<?= (!empty($employee['Id_h']) ? $employee['Id_h'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="ID_Emp_date_S">تاريخ الاصدار</label>
											<input type="date" name="ID_Emp_date_S" class="form-control"
												id="ID_Emp_date_S"
												value="<?= (!empty($employee['start_date_h']) ? $employee['start_date_h'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="ID_Emp_date_F">تاريخ الانتهاء</label>
											<input type="date" name="ID_Emp_date_F" class="form-control"
												id="ID_Emp_date_F"
												value="<?= (!empty($employee['end_date_h']) ? $employee['end_date_h'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="ID_file">ارفقها (صورة الهوية)</label>
											<input type="file" name="ID_file" class="form-control" id="ID_file">
											<div class="file_control"
												style="display:<?= (!empty($employee['path_h']) ? 'block' : 'none') ?>;padding-top:5px;">
												<a href="<?= (!empty($employee['path_h']) ? $employee['path_h'] : '') ?>"
													download>
													<button type="button" class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق
													</button>
												</a>
											</div>
										</div>
									</div>
								</div>

								<!-- رخصة السواقة -->
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_IDD">رقم رخصة السواقة</label>
											<input type="text" class="form-control" id="emp_IDD" name="emp_IDD"
												value="<?= (!empty($employee['Id_license']) ? $employee['Id_license'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_IDD_Date_S">تاريخ الاصدار</label>
											<input type="date" name="emp_IDD_Date_S" class="form-control"
												id="emp_IDD_Date_S"
												value="<?= (!empty($employee['start_date_license']) ? $employee['start_date_license'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_IDD_Date_F">تاريخ الانتهاء</label>
											<input type="date" name="emp_IDD_Date_F" class="form-control"
												id="emp_IDD_Date_F"
												value="<?= (!empty($employee['end_date_license']) ? $employee['end_date_license'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="IDD_file">ارفقها</label>
											<input type="file" name="IDD_file" class="form-control" id="IDD_file">
											<div class="file_control"
												style="display:<?= (!empty($employee['path_license']) ? 'block' : 'none') ?>;padding-top:5px;">
												<a href="<?= (!empty($employee['path_license']) ? $employee['path_license'] : '') ?>"
													download>
													<button type="button" class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق
													</button>
												</a>
											</div>
										</div>
									</div>
								</div>

								<!-- جواز السفر -->
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_passportID">رقم جواز (صورة
												الجواز)</label>
											<input type="text" class="form-control" id="emp_passportID"
												name="emp_passportID"
												value="<?= (!empty($employee['Id_passport']) ? $employee['Id_passport'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_Passport_ID_Date_S">تاريخ
												الاصدار</label>
											<input type="date" name="emp_Passport_ID_Date_S" class="form-control"
												id="emp_Passport_ID_Date_S"
												value="<?= (!empty($employee['start_date_passport']) ? $employee['start_date_passport'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_passport_Cer_ID_Date_F">تاريخ
												الانتهاء</label>
											<input type="date" name="emp_passport_Cer_ID_Date_F" class="form-control"
												id="emp_passport_Cer_ID_Date_F"
												value="<?= (!empty($employee['end_date_passport']) ? $employee['end_date_passport'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="Passport_ID_file">ارفقها</label>
											<input type="file" name="Passport_ID_file" class="form-control"
												id="Passport_ID_file">
											<div class="file_control"
												style="display:<?= (!empty($employee['path_passport']) ? 'block' : 'none') ?>;padding-top:5px;">
												<a href="<?= (!empty($employee['path_passport']) ? $employee['path_passport'] : '') ?>"
													download>
													<button type="button" class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق
													</button>
												</a>
											</div>
										</div>
									</div>
								</div>

								<!-- الشهادة الصحية -->
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_CerID">رقم الشهادة الصحية (اللياقة
												الطبية)</label>
											<input type="text" class="form-control" id="emp_CerID" name="emp_CerID"
												value="<?= (!empty($employee['Id_health']) ? $employee['Id_health'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_Cer_ID_Date_S">تاريخ الاصدار</label>
											<input type="date" name="emp_Cer_ID_Date_S" class="form-control"
												id="emp_Cer_ID_Date_S"
												value="<?= (!empty($employee['start_date_health']) ? $employee['start_date_health'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="emp_Cer_ID_Date_F">تاريخ الانتهاء</label>
											<!-- FIX: was incorrectly reusing id="emp_IDD_Date_F" — corrected to emp_Cer_ID_Date_F -->
											<input type="date" name="emp_Cer_ID_Date_F" class="form-control"
												id="emp_Cer_ID_Date_F"
												value="<?= (!empty($employee['end_date_health']) ? $employee['end_date_health'] : '') ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label class="col-form-label" for="Cer_ID_file">ارفقها</label>
											<input type="file" name="Cer_ID_file" class="form-control" id="Cer_ID_file">
											<div class="file_control"
												style="display:<?= (!empty($employee['path_health']) ? 'block' : 'none') ?>;padding-top:5px;">
												<a href="<?= (!empty($employee['path_health']) ? $employee['path_health'] : '') ?>"
													download>
													<button type="button" class="btn btn-xs btn-default">
														<i class="fa fa-download"></i> تنزيل المرفق
													</button>
												</a>
											</div>
										</div>
									</div>
								</div>
							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab4 -->

				<!-- TAB 5 — أخرى -->
				<div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="tab5-tab">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header" data-card-widget="collapse" style="cursor: pointer">
								<h3 class="card-title">تفاصيل اخرى عن الموظف/المتقدم</h3>
								<div class="card-tools">
									<button type="button" class="btn btn-tool" data-card-widget="collapse">
										<i class="fas fa-minus"></i>
									</button>
								</div>
							</div>
							<div class="card-body">
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label class="col-form-label" for="emp_OtherPhone">هاتف اخر</label>
											<input type="text" class="form-control" id="emp_OtherPhone"
												name="emp_OtherPhone"
												value="<?= (!empty($employee['ohter_phone']) ? $employee['ohter_phone'] : '') ?>">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="col-form-label" for="state_human">الحالة الصحية (نصية)</label>
											<input type="text" name="state_human" class="form-control" id="state_human"
												value="<?= (!empty($employee['HealthCondition']) ? $employee['HealthCondition'] : '') ?>">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="marital_status">الحالة الاجتماعية</label>
											<select class="selectpicker" data-live-search="true" data-container="body"
												data-size="5" data-width="100%" title="الحالة الاجتماعية"
												id="marital_status" name="marital_status">
												<?php
												if (!empty($employee['marital_status'])) {
													$vals = [1 => 'متزوج', 2 => 'اعزب', 3 => 'ارمل', 4 => 'اخرى'];
													$v = $vals[$employee['marital_status']] ?? '';
													echo '<option value="' . $employee['marital_status'] . '" selected>' . $v . '</option>';
												}
												?>
												<option value="1">متزوج</option>
												<option value="2">اعزب</option>
												<option value="3">ارمل</option>
												<option value="4">اخرى</option>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="emp_Sex">الجنس</label>
											<select class="selectpicker" data-live-search="true" data-container="body"
												data-size="5" data-width="100%" title="الجنس" id="emp_Sex"
												name="emp_Sex">
												<?php
												if (!empty($employee['Sex'])) {
													$vals_ = [1 => 'ذكر', 2 => 'انثى'];
													$v_ = $vals_[$employee['Sex']] ?? '';
													echo '<option value="' . $employee['Sex'] . '" selected>' . $v_ . '</option>';
												}
												?>
												<option value="1">ذكر</option>
												<option value="2">انثى</option>
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-form-label" for="emp_address">العنوان</label>
											<input type="text" name="emp_address" class="form-control" id="emp_address"
												value="<?= (!empty($employee['user_address']) ? $employee['user_address'] : '') ?>">
										</div>
									</div>
								</div>
							</div><!-- /card-body -->
						</div><!-- /card -->
					</div>
				</div><!-- /tab5 -->

			</div><!-- /tab-content -->
		</form>

	</div>
</section>

<?php
$GetBranch = true;
include_once('inc/footer.php');
?>

<script>
	// --- Toggle between Employee and Applicant ---
	function toggleRoleFields() {
		var roleType = $('#user_role_type').val();
		if (roleType == '2') { // Applicant
			$('.employee-only').hide();
			$('.applicant-only').show();
			$('.applicant-status-container').show();

			$('#emp_branch, #email').prop('required', false);

			// If currently on an employee-only tab, go back to tab1
			var activeTab = $('#employeeTabs .nav-link.active').attr('href');
			if (activeTab === '#tab2' || activeTab === '#tab3') {
				$('#tab1-tab').tab('show');
			}
		} else { // Employee
			$('.employee-only').show();
			$('.applicant-only').hide();
			$('.applicant-status-container').hide();

			$('#emp_branch').prop('required', true);

			// If currently on the applicant tab, go back to tab1
			var activeTab = $('#employeeTabs .nav-link.active').attr('href');
			if (activeTab === '#tab-applicant') {
				$('#tab1-tab').tab('show');
			}
		}
	}

	$('#user_role_type').on('change', function () {
		toggleRoleFields();
	});

	$(document).ready(function () {
		toggleRoleFields();

		// Auto-switch to employee mode when approve_flow=1
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('approve_flow') === '1') {
			$('#user_role_type').val('1').trigger('change');
			$('#applicant_status').val('1');

			if (window.toastr) {
				toastr.info('أنت الآن تقوم بقبول المتقدم. يرجى استكمال بيانات (العقد، القسم، الفرع) لتحويله إلى موظف.');
			}

			setTimeout(function () {
				$('#tab2-tab').tab('show');
			}, 300);
		}
	});
	// ------------------------------------------------

	var TheBranches = [];

	function refreshBranchSelectpicker(selector) {
		if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.selectpicker === 'function') {
			window.jQuery(selector).selectpicker('refresh');
		}
	}

	function populateBranchSelectOptions(selector, items) {
		var select = document.querySelector(selector);
		if (!select) { return; }

		select.innerHTML = '';
		var placeholder = document.createElement('option');
		placeholder.value = '';
		placeholder.textContent = '-- اختر --';
		select.appendChild(placeholder);

		if (Array.isArray(items)) {
			items.forEach(function (item) {
				var optionValue = item && item.data ? item.data.id : item && item.Id;
				var optionLabel = item && item.data ? item.data.name : item && item.Name;
				if (optionValue === undefined || optionLabel === undefined) { return; }
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
		if (data.groub_list && !data.groub) { data.groub = data.groub_list; }
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
		if (!branchSelect) { return ''; }
		if (!branchSelect.value && branchSelect.options.length > 0) {
			branchSelect.value = branchSelect.options[0].value;
			refreshBranchSelectpicker('#emp_branch');
		}
		return branchSelect.value || '';
	}

	function requestBranchFormPayload(selectedValue) {
		var branchValue = selectedValue || getActiveEmployerBranchValue();
		if (!branchValue) { return Promise.resolve(false); }
		if (typeof window.showPreloader === 'function') { window.showPreloader(); }

		return fetch('hr-app/index.php?action=allUserinfo_Search', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: new URLSearchParams({ value: branchValue }).toString()
		})
			.then(function (response) {
				if (!response.ok) { throw new Error('HTTP ' + response.status); }
				return response.json();
			})
			.then(function (response) {
				applyBranchFormPayload(response.data || response);
				return true;
			})
			.catch(function (error) {
				console.error('Branch dropdown load failed', error);
				if (window.toastr && typeof window.toastr.error === 'function') {
					window.toastr.error('Unable to load branch dropdown data.');
				}
				return false;
			})
			.finally(function () {
				if (typeof window.hidePreloader === 'function') { window.hidePreloader(); }
			});
	}

	function shouldBootstrapBranchFormPayload() {
		var branchSelect = document.getElementById('emp_branch');
		var jobTitleSelect = document.getElementById('user_jobtitle');
		var sectionSelect = document.getElementById('user_section');
		return !!(branchSelect && jobTitleSelect && sectionSelect &&
			jobTitleSelect.options.length === 0 && sectionSelect.options.length === 0);
	}

	function bootstrapBranchFormPayload() {
		if (shouldBootstrapBranchFormPayload()) { requestBranchFormPayload(); }
	}

	document.addEventListener('DOMContentLoaded', function () {
		var branchSelect = document.getElementById('emp_branch');
		if (branchSelect && !branchSelect.dataset.payloadLoaderBound) {
			branchSelect.dataset.payloadLoaderBound = '1';
			branchSelect.addEventListener('change', function () { requestBranchFormPayload(this.value); });
		}
		setTimeout(bootstrapBranchFormPayload, 0);
		setTimeout(bootstrapBranchFormPayload, 500);
	});

	window.addEventListener('load', function () { setTimeout(bootstrapBranchFormPayload, 0); });

	$(document).ready(function () {

		$(document).on('click', '#save-data', function () {
			$('#user_fm').trigger('submit');
		});

		function loadBranchFormData(selectedValue) {
			if (!selectedValue) { return; }
			$.ajax({
				url: 'hr-app/index.php?action=allUserinfo_Search',
				type: 'POST',
				data: { value: selectedValue },
				dataType: "json",
				beforeSend: function () { $('#preloading').show(); },
				success: function (response) {
					var payload = response.data || response;
					if (payload.groub_list && !payload.groub) { payload.groub = payload.groub_list; }
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
				error: function (xhr) {
					$('#preloading').hide();
					console.error('Branch dropdown load failed', xhr.responseText || xhr.statusText);
					toastr.error('حدث خطأ أثناء جلب البيانات');
				}
			});
		}

		$('#emp_branch').change(function () {
			if (this.dataset && this.dataset.payloadLoaderBound === '1') { return; }
			loadBranchFormData($(this).val());
		});

		var initialBranchValue = $('#emp_branch').val();
		if (initialBranchValue && $('#user_jobtitle option').length === 0 && $('#user_section option').length === 0) {
			loadBranchFormData(initialBranchValue);
		}

		function populateSelect(selectId, items) {
			var select = $(selectId);
			select.empty();
			select.append('<option value="">-- اختر --</option>');
			if (items && items.length > 0) {
				$.each(items, function (index, item) {
					var id = item.data ? item.data.id : item.Id;
					var name = item.data ? item.data.name : item.Name;
					if (id !== undefined && name !== undefined) {
						select.append('<option value="' + id + '">' + name + '</option>');
					}
				});
			}
			select.selectpicker('refresh');
		}

		$('#user_fm').on('submit', function (e) {
			e.preventDefault();

			var form_data = new FormData(this);
			var branchValue = $('#emp_branch').val();
			if (branchValue) {
				form_data.set('emp_branch', branchValue);
				form_data.set('BranchID', branchValue);
				form_data.set('branchs_list', branchValue);
			}

			// Remove required from employee-only fields when in Applicant mode
			if ($('#user_role_type').val() == '2') {
				$('#user_jobtitle, #user_section, #emp_salary, #currency, #emp_contract_S, #emp_contract_F, #user_shift, #user_finger').prop('required', false);
			} else {
				$('#user_jobtitle, #user_section, #emp_salary, #currency, #emp_contract_S, #emp_contract_F, #user_shift, #user_finger').prop('required', true);
			}

			if ($(this).valid()) {
				$.ajax({
					type: 'POST',
					url: "hr-app/index.php?action=employer-add",
					data: form_data,
					contentType: false,
					processData: false,
					dataType: "json",
					beforeSend: function () { $('#preloading').show(); },
					success: function (data) {
						if (data.result) {
							$("#user_id").val(data.emp_id);
							if ($('#user_role_type').val() == '2') {
								window.location.href = 'applicants-list';
							} else {
								window.location.href = 'employer-list';
							}
						} else {
							toastr.error(data.msg);
							$('#preloading').hide();
						}
						if (data.reload) { window.location = document.URL; }
					}
				});
			}
		});

		$('#create_account').change(function () {
			if ($(this).is(':checked')) {
				$(".user_account").show();
				$(".logindata").prop('required', true);
				$("label.logindata").addClass('required');
			} else {
				$(".user_account").hide();
				$(".logindata").prop('required', false);
				$("label.logindata").removeClass('required');
			}
		});

		$('#send_pass').change(function () {
			if ($(this).is(':checked')) {
				$(".user_pass").show();
				$("label.user_pass").addClass('required');
			} else {
				$(".user_pass").hide();
				$("label.user_pass").removeClass('required');
			}
		});

		$('#user_fm').validate({
			errorElement: 'span',
			errorPlacement: function (error, element) {
				error.addClass('invalid-feedback');
				element.closest('div').append(error);
			},
			highlight: function (element) {
				$(element).addClass('is-invalid');
			},
			unhighlight: function (element) {
				$(element).removeClass('is-invalid');
			}
		});

		$('#emp_salary').on('keypress', function (e) {
			var key = e.which || e.keyCode;
			return (key >= 48 && key <= 57);
		});

	});
</script>