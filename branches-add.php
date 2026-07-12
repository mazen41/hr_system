<?php
$screen = 'الفروع';
$page_title = 'إدارة الفروع';
$only_main_branch = true;
include_once('inc/header.php');

$branch_apps = [];
$ids = []; // Array to hold identities safely

// FIX: Wrapped in try-catch so it doesn't crash if tblidentitytypes doesn't exist
try {
	$query_ids = "SELECT IDType,TypeName FROM tblidentitytypes WHERE AvailableFor  = 'any'";
	$stm_ids = $connect_pdo->prepare($query_ids);
	$stm_ids->execute();
	if ($stm_ids->rowCount() > 0) {
		$ids = $stm_ids->fetchAll();
	}
} catch (PDOException $e) {
	// Table doesn't exist, $ids remains empty
}

function getBranchData($connect, $id)
{
	$q = "SELECT branch_id,branch_ref,branch_name, isstopped, branch_style, branch_address, isdefault
    FROM branches WHERE branch_id =:id 
    -- AND isdefault is null
    limit 1 ";
	$stm = $connect->prepare($q);
	$stm->execute(['id' => $id]);
	if ($stm->rowCount() > 0) {
		$row = $stm->fetch();
		return $row;
	}
	return null;
}

function getBranchAddress($connect, $address_id)
{
	$q = "SELECT * 
    FROM tbladdress WHERE AddressID =:address_id AND AddressType = 'BRANCH' limit 1 ";
	$stm = $connect->prepare($q);
	$stm->execute(['address_id' => $address_id]);
	if ($stm->rowCount() > 0) {
		$row = $stm->fetch();
		return $row;
	}
	return null;
}

function branchApps($connect, $id)
{
	$data = [];
	// FIX: try-catch added in case tblbranchesapps doesn't exist either
	try {
		$q = "SELECT DISTINCT AppID FROM tblbranchesapps where BranchID = :id";
		$stm = $connect->prepare($q);
		$stm->execute(['id' => $id]);
		if ($stm->rowCount() > 0) {
			$rows = $stm->fetchAll();
			foreach ($rows as $row) {
				array_push($data, $row['AppID']);
			}
		}
	} catch (PDOException $e) {
	}
	return $data;
}

function allApps($connect)
{
	$data = [];
	try {
		$q = "SELECT AppID,AppName FROM apps where Disabled is null AND IsRrequred is null order by apps.Sort";
		$stm = $connect->prepare($q);
		$stm->execute();
		if ($stm->rowCount() > 0) {
			$rows = $stm->fetchAll();
			foreach ($rows as $row) {
				$data[$row['AppID']] = $row['AppName'];
			}
		}
	} catch (PDOException $e) {
	}
	return $data;
}

$all_apps = allApps($connect_pdo);

function ouApps($connect)
{
	try {
		$q = "SELECT DISTINCT
        tblbranchesapps.BranchID,tblbranchesapps.AppID, apps.AppName
        FROM tblbranchesapps 
        LEFT JOIN apps  ON apps.AppID = tblbranchesapps.AppID  
        LEFT JOIN branches  ON branches.branch_id = tblbranchesapps.BranchID
        WHERE branches.isdefault is not null
        AND apps.IsRrequred is null 
        AND apps.Disabled is null
        order by apps.Sort ";
		$stm = $connect->prepare($q);
		$stm->execute();
		if ($stm->rowCount() > 0) {
			$rows = $stm->fetchAll();
			return $rows;
		}
	} catch (PDOException $e) {
	}
	return null;
}
$ou_apps = ouApps($connect_pdo);

if (isset($_GET['id'])) {

	$id = (int) $_GET['id'];
	$branch_data = getBranchData($connect_pdo, $id);

	if (empty($branch_data)) {
		echo '<script> location.replace("branches-list"); </script>';
		die;
	} else {
		$id = $branch_data['branch_id'];
		$branch_ref = $branch_data['branch_ref'];
		$branch_name = $branch_data['branch_name'];
		$branch_style = $branch_data['branch_style'];
		$address = !empty($branch_data['branch_address']) ? getBranchAddress($connect_pdo, (int) $branch_data['branch_address']) : [];

		if (!empty($all_apps)) {
			$branch_apps = branchApps($connect_pdo, $id);
		}
	}

} else {
	function genrateBranchNo($connect)
	{
		$q = " SELECT max(branch_ref)+1 as branch_ref FROM branches";
		$stm = $connect->prepare($q);
		$stm->execute();
		if ($stm->rowCount() > 0) {
			$row = $stm->fetch();
			return $row['branch_ref'];
		}
		return 1;
	}
	$branch_ref = genrateBranchNo($connect_pdo);
}

?>

<style>
	.badge-warning {
		color: #fff;
	}

	.second-page-title {
		display: block;
		color: darkgray;
	}

	.second-page-title>.fa {
		color: #e5e5e5;
	}

	.readonly {
		background: transparent !important;
		border: none;
	}

	.no-brder-input tbody td,
	.no-brder-input tfoot td {
		padding: 0.1rem !important;
	}

	.no-brder-input .select2-selection--single,
	.no-brder-input input[type="text"] {
		border: none !important;
		box-shadow: inset 0 0px 0 #ddd;
		padding: 0.46875rem 0.1rem;
	}

	.no-brder-input td {
		vertical-align: middle;
	}

	#color-chooser li {
		cursor: pointer;
	}
</style>
<div class="content-header page-nav">
	<div class="container-fluid ">
		<div class="row ">
			<div class="col-md-7">
				<span class="page-title">إضافة فرع</span>
				<?= (!empty($entry_status) ? $entry_status : ''); ?>
				<?= (!empty($entry['FirstName']) ? '<span class="second-page-title">' . $related_branch . ' <i class="fa fa-user"></i> ' . $entry['FirstName'] . ' ' . $entry['LastName'] . ' <i class="fa fa-calendar-alt"></i> ' . $entry['CreatedDate'] . '  </span>' : ''); ?>
			</div>
			<div class="col-md-5 text-left">
				<button type="button" class="btn btn-default" onclick="history.back()" id="cancel-bt"><i
						class="fa fa-share"></i><span class="d-none d-sm-inline"> عودة</span></button>
				<button type="button" class="btn btn-success" id="save_data"><i class="fa fa-save"></i><span
						class="d-none d-sm-inline"> حفظ</span></button>
			</div>
		</div>
	</div>
</div>

<section class="content">
	<div class="container-fluid">
		<form class="form-horizontal" role="form" action="#" method="post" id="branch_fm">
			<input type="hidden" value="<?= !empty($id) ? $id : '' ?>" name="branch_id">
			<input type="hidden" value="" name="address_id">
			<div class="invoice p-2 mb-3">
				<div class="row">
					<div class="col-md-2">
						<div class="form-group">
							<label for="br_no" class="col-form-label required">رقم الفرع</label>
							<input type="number" value="<?= !empty($branch_ref) ? $branch_ref : '' ?>"
								class="form-control number-format " title="ادخل رقم مميز للفرع" id="br_no" name="br_no"
								placeholder="" autocomplete="off" style="direction:rtl" <?= !empty($branch_name) ? 'readonly' : '' ?> required>
						</div>
					</div>

					<div class="col-md-4">
						<div class="form-group">
							<label for="br_name" class="col-form-label required">اسم الفرع</label>
							<input type="text" value="<?= !empty($branch_name) ? $branch_name : '' ?>"
								class="form-control " title="إكتب اسم الفرع" id="br_name" name="br_name" placeholder=""
								autocomplete="off" required>
						</div>
					</div>

					<div class="col-md-12">
						<div class="form-group">
							<label for="br_style" class="col-form-label ">ستايل خاص :
								<ul class="fc-color-picker branch_style" id="color-chooser">
									<li data-color="#f06292"><span style="color:#f06292"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#009688"><span style="color:#009688"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#9c27b0"><span style="color:#9c27b0"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#00bcd4"><span style="color:#00bcd4"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#007bff"><span class="text-primary"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#ffc107"><span class="text-warning"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#28a745"><span class="text-success"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#dc3545"><span class="text-danger"><i
												class="fas fa-square"></i></span></li>
									<li data-color="#6c757d"><span class="text-muted"><i
												class="fas fa-square"></i></span></li>
									<li><span style="color:#247dbd"><i class="fas fa-square"></i></span></li>
								</ul>
							</label>
							<div style="margin-right: 5px; height: 25px;  width: 305px;background:<?= !empty($branch_style) ? $branch_style : '#247dbd' ?>"
								id="privew_style"></div>
							<input type="hidden" value="<?= !empty($branch_style) ? $branch_style : '' ?>" id="br_style"
								name="br_style">
						</div>
					</div>
					<?php if (!empty($branch_name) && empty($branch_data['isdefault'])) { ?>
						<div class="col-sm-12">
							<label class=" control-label" for="stopped">&nbsp;</label>
							<div class="col-sm-10">
								<label class="switch switch-danger switch-md ">
									<input type="checkbox" name="stopped" id="stopped" <?= !empty($branch_data['isstopped']) ? 'checked' : '' ?>>
									<span></span> إيقاف الفرع
								</label>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="card ">
						<div class="card-header" style="cursor: pointer">
							<h3 class="card-title">عنوان الفرع</h3>
							<div class="card-tools">
								<button type="button" class="btn btn-tool" data-card-widget="collapse"><i
										class="fas fa-minus"></i></button>
							</div>
						</div>

						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label required" for="street">عنوان الشارع</label>
										<input type="text" class="form-control req" data-toggle="tooltip"
											title="ادخل العنوان" id="street" name="street"
											value="<?= !empty($address['Street']) ? $address['Street'] : '' ?>" required>
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label required" for="block">الحي</label>
										<input type="text" class="form-control req" id="block" name="block"
											autocomplete="off" data-toggle="tooltip" title="ادخل اسم الحي"
											value="<?= !empty($address['Block']) ? $address['Block'] : '' ?>" required>
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label required" for="city">المحافظة / المدينة</label>
										<input type="text" class="form-control req" id="city" name="city"
											title="ادخل اسم المدينة / المحافظة" data-toggle="tooltip"
											value="<?= !empty($address['City']) ? $address['City'] : '' ?>" required>
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="building">رقم المبنى</label>
										<input type="text" class="form-control number-format" data-toggle="tooltip"
											title="ادخل رقم المبنى" id="building" name="building" autocomplete="off"
											value="<?= !empty($address['Building']) ? $address['Building'] : '' ?>">
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="phone">هاتف</label>
										<input type="text" class="form-control number-format" data-toggle="tooltip"
											title="ادخل رقم الهاتف" id="phone" name="phone" autocomplete="off"
											value="<?= !empty($address['Phone']) ? $address['Phone'] : '' ?>">
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="mobile">جوال</label>
										<input type="text" class="form-control number-format" data-toggle="tooltip"
											title="ادخل رقم الجوال" id="mobile" name="mobile" autocomplete="off"
											value="<?= !empty($address['Mobile']) ? $address['Mobile'] : '' ?>">
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="zip">الرمز البريدي</label>
										<input type="text" class="form-control number-format" id="postal_code"
											name="zip" autocomplete="off" data-toggle="zip" title="ادخل الرمز البريدي"
											value="<?= !empty($address['ZipCode']) ? $address['ZipCode'] : '' ?>">
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="email">بريد إلكتروني</label>
										<input type="text" class="form-control " id="email" name="email"
											autocomplete="off" data-toggle="zip" title="ادخل عنوان البريد الالكتروني"
											value="<?= !empty($address['Email']) ? $address['Email'] : '' ?>">
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="vat">الرقم الضريبي</label>
										<input type="text" class="form-control number-format" id="vat" name="vat"
											autocomplete="off" data-toggle="tooltip" title="ادخل الرقم الضريبي"
											value="<?= !empty($address['VatNumber']) ? $address['VatNumber'] : '' ?>">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="vat_g">رقم المجموعة الضريبية</label>
										<input type="text" class="form-control number-format" id="vat_g" name="vat_g"
											autocomplete="off" data-toggle="tooltip"
											title="إذا كان الفرع ضمن مجموعة ضريبية"
											value="<?= !empty($address['VatGNumber']) ? $address['VatGNumber'] : '' ?>">
									</div>
								</div>

								<!-- إحداثيات GPS -->
								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="latitude">خط العرض (Latitude)</label>
										<div class="input-group">
											<input type="text" class="form-control" id="latitude" name="latitude"
												autocomplete="off"
												value="<?= !empty($address['Latitude']) ? $address['Latitude'] : '' ?>">
											<span class="input-group-append">
												<button type="button" class="btn btn-info btn-flat" id="get_location"
													title="التحديد التلقائي باستخدام GPS"><i
														class="fas fa-crosshairs"></i></button>
											</span>
										</div>
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label class="col-form-label " for="longitude">خط الطول (Longitude)</label>
										<input type="text" class="form-control" id="longitude" name="longitude"
											autocomplete="off"
											value="<?= !empty($address['Longitude']) ? $address['Longitude'] : '' ?>">
									</div>
								</div>
								<!-- نهاية إحداثيات GPS -->

								<div class="col-md-8">
									<div class="form-group">
										<label class="col-form-label" for="idtype">هوية إضافية</label>
										<select id="idtype" name="idtype" class="form-control selectpicker"
											data-container="body" data-size="12" data-width="100%" data-toggle="tooltip"
											title="حدد نوع الهوية" <?= (!empty($site['IdentityDetail']) ? 'required' : '') ?>>
											<?php
											// FIX: Uses the $ids array safely even if the table doesn't exist
											echo '<option value="" ' . (empty($site['IdentityType']) ? 'selected' : '') . '>لايوجد (None)</option>';
											if (!empty($ids)) {
												foreach ($ids as $id) {
													echo '<option value="' . $id['IDType'] . '" data-subtext=" (' . $id['IDType'] . ') " ' . (!empty($address['IdentityType']) && $address['IdentityType'] == $id['IDType'] ? 'selected' : '') . '> ' . $id['TypeName'] . '</option>';
												}
											}
											?>
										</select>
									</div>
								</div>

								<div class="col-md-4" id="idnfield"
									style="display:<?= (!empty($address['IdentityDetail']) ? '' : 'none') ?>">
									<div class="form-group">
										<label class="col-form-label " for="idno">معرف الهوية</label>
										<input type="text" class="form-control" id="idno" name="idno" autocomplete="off"
											data-toggle="tooltip" title="ادخل رقم الهوية"
											value="<?= (!empty($address['IdentityDetail']) ? $address['IdentityDetail'] : '') ?>"
											<?= (!empty($address['IdentityType']) ? 'required' : '') ?>>
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>

				<div class="col-md-6" style="display:<?= !empty($branch_data['isdefault']) ? 'none' : '' ?>">
					<div class="invoice">
						<div class="card-header" style="padding-bottom: 0;">
							<h5 class="card-title">التطبيقات المتاحة للفرع</h5>
							<div class="card-tools ml-2">
								<label class="switch switch-info " style="margin-bottom: 0.3rem;">
									<label class="control-label " for="all_apps">كل التطبيقات</label>
									<input type="checkbox" name="all_apps" id="all_apps" title="" <?= !empty($all_apps) && count($all_apps) == count($branch_apps) ? 'checked' : '' ?>>
									<span></span>
								</label>
							</div>
						</div>

						<div class="card-body  " id="apps_container">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<?php
										if (!empty($all_apps)) {
											foreach ($all_apps as $k => $v) {
												echo '
								<div class="custom-control custom-checkbox">
								  <input class="custom-control-input" type="checkbox" name="apps[]" id="' . $k . '" value="' . $k . '" ' . (in_array($k, $branch_apps) ? 'checked' : '') . '>
								  <label for="' . $k . '" class="custom-control-label "> ' . $v . '</label>
								</div>';
											}
										}
										?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</form>
	</div>
</section>

<?php
include_once('inc/footer.php');
?>

<script>
	$(document).ready(function () {

		const urlParams = new URLSearchParams(window.location.search);
		const param_id = urlParams.get('id');

		$('#idtype').on('change', function (e) {
			var idtype = $(this).val();
			if (idtype == '') {
				$("#idnfield").hide();
				$("#idno").prop('required', false);
				$("#idno-error").hide();
			} else {
				$("#idnfield").show();
				$("#idno").prop('required', true);
				$("#idno-error").show();
			}
		});

		$(document).on('click', '#all_apps', function () {
			if ($(this).is(':checked')) {
				$('#apps_container input[name="apps[]"]').prop("checked", true);
			} else {
				$('#apps_container input[name="apps[]"]').prop("checked", false);
			}
		});

		// GPS Auto Detection
		$(document).on('click', '#get_location', function () {
			if (navigator.geolocation) {
				var btn = $(this);
				var originalHtml = btn.html();
				btn.html('<i class="fas fa-spinner fa-spin"></i>');
				btn.prop('disabled', true);

				navigator.geolocation.getCurrentPosition(function (position) {
					$('#latitude').val(position.coords.latitude);
					$('#longitude').val(position.coords.longitude);
					btn.html(originalHtml);
					btn.prop('disabled', false);
					toastr.success('تم تحديد الموقع بنجاح');
				}, function (error) {
					btn.html(originalHtml);
					btn.prop('disabled', false);
					var msg = "حدث خطأ أثناء تحديد الموقع.";
					if (error.code == 1) {
						msg = "يجب السماح للمتصفح بالوصول إلى موقعك (قم بتفعيل GPS).";
					} else if (error.code == 2) {
						msg = "معلومات الموقع غير متوفرة.";
					} else if (error.code == 3) {
						msg = "انتهى وقت الطلب.";
					}
					toastr.error(msg);
				}, {
					enableHighAccuracy: true,
					timeout: 10000,
					maximumAge: 0
				});
			} else {
				toastr.error("المتصفح الخاص بك لا يدعم تحديد الموقع (GPS).");
			}
		});

		$(document).on('click', '#save_data', function () {
			$('#branch_fm').trigger('submit');
		});

		$(document).on('click', '#color-chooser li', function () {
			var color = $(this).data('color');
			$('#br_style').val(color);
			if (!color) {
				color = '#247dbd';
			}
			$('#privew_style').css("background-color", color);
			$('.navbar-white').css("background", color);
		});

		$('#branch_fm').on('submit', function (e) {
			e.preventDefault();
			var form_data = $(this).serialize() + '&id=' + param_id;
			if ($(this).valid()) {
				$.ajax({
					type: 'POST',
					url: "branches-app/index.php?action=branches-add",
					data: form_data,
					dataType: "json",
					beforeSend: function () {
						$('#preloading').show();
					},
					success: function (data) {
						if (data.result) {
							toastr.success(data.msg);
							$('#preloading').hide();
							if (data.id > 0) {
								window.location.href = 'branches-list';
							}
						} else {
							toastr.error(data.msg);
							$('#preloading').hide();
						}
					}
				});
			}
		});

		$('#branch_fm').validate({
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

	});
</script>