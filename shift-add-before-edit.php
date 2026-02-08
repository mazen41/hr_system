
<?php
$appid  = 'HR';
$page_perm=['اضافة فترة'];
$screen = 'الفترات';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة الفترات';
// }else{
// $page_title = 'إضافة فتره';
// }

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 

$form_title = 'إضافة فتره جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
			
	$query = "SELECT ShiftID,BranchID,ShiftName, ShiftStartTime,ShiftEndTime,ShiftState,	NumFootprint,BranchID
 	FROM tbshift 
	WHERE  ShiftID  = :id 
	LIMIT 1 ";

	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $client_no
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		$client_id = $row['ShiftID'];
		$form_title = 'تعديل فتره ' .$row['ShiftName'].'';
        $save_btn = 'حفظ التغييرات';
		$parts = explode(":", $row['ShiftStartTime']);
		$hours = $parts[0];    
		$minutes = $parts[1]; 

		$parts_e = explode(":", $row['ShiftEndTime']);
		$hours_e = $parts_e[0];    
		$minutes_e = $parts_e[1]; 

// 

$query_2 = "SELECT * 	FROM shift_setting WHERE  shift_id  = :id LIMIT 1 ";
$st_2 = $connect_pdo->prepare($query_2);
$st_2->execute( array( ':id'  => $client_no));
if($st_2->rowCount() > 0){
  $row_2 = $st_2->fetch();
	}
}
	else{
		echo'<script> location.replace("shift-list"); </script>';
		die();
	}		


  // check this has employer or not
    function GetlastID($connect,$client_no) {
  $sql = "SELECT shiftID FROM tblremewal WHERE FIND_IN_SET(:id, shiftID) LIMIT 1";
  $stmt = $connect->prepare($sql);
  $stmt->bindValue(':id', $client_no, PDO::PARAM_STR);
  $stmt->execute();
  if ($stmt->rowCount() > 0) {
      return true;
  }
  return false;
}
}
?>
<style>
body {
      direction: rtl;
      background-color: #f8f9fa;
    }


    .dropdown-scroll {
      position: relative;
    }

    .dropdown-menu-custom {
      position: absolute;
      top: 100%;
      right: 0;
      left: 0;
      max-height: 150px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 10px;
      background-color: white;
      z-index: 1000;
      display: none;
    }

    .dropdown-menu-custom div {
      padding: 10px;
      cursor: pointer;
    }

    .dropdown-menu-custom div:hover {
      background-color: #f1f1f1;
    }

    .dropdown-toggle-custom {
      width: auto;
      padding: 5px;
      border: 1px solid #ced4da;
      border-radius: 2px;
      background-color: white;
      text-align: center;
      cursor: pointer;
    }
  </style>
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-shift"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
		 </div>
        </div>
      </div>
    </div>
   
	
	
		
	


    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="AddShift">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل الفتره</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-md-12">
							<label class="col-form-label required"  for="shiftname">اسم الفتره</label>
							<input type="text"  value="<?=!empty($row['ShiftName']) ? $row['ShiftName'] : ''?>" class="form-control"  data-toggle="tooltip"  id="shiftname" name="shiftname"  autocomplete="off" required>
						<!-- <label for="start-time">اختر الوقت:</label>
<input type="time" id="start-time" name="start-time" step="60"> -->
            </div>
                        
                      </div>
                      
					 
					 <div class="row">
                             <div class="form-group col-md-6">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list[]"<?php echo !isset($_GET['id']) ? "multiple='multiple'" : ''; ?> required >
                                  <?php
                              if (isset($_GET['id']) && GetlastID($connect_pdo,$client_no)) {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">هذه الفتره تم ربطها بموظفين لايمكن تغير الفرع</label>
                                 
                                 <?php
                              } else {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  foreach ($allowed_branches as $id => $name) {
                                      echo '<option value="' . $id . '">' . $name . '</option>';
                                  }
                                  ?>
</select>
                                  <?php
                              }
                              ?>


                                  
                                    
                              </div>
                              <div class="form-group col-md-6">
                                <label class="col-form-label required" for="Footprint">عدد بصمات الفتره</label>
                                  <select class=" selectpicker select_branch" data-container="body" data-size="5" data-width="100%" title="أدخل عدد البصمات" id="Footprint" name="Footprint" required>
                                  <option value="1" <?=!empty($row['NumFootprint']) && $row['NumFootprint']=='1' ? 'selected' : ''?>>بصمة واحد</option>
                                  <option value="2" <?=!empty($row['NumFootprint']) && $row['NumFootprint']=='2' ? 'selected' : ''?>>بصمتين</option>
                                  </select>
                              </div>
							
						</div>





						<div class="row">
							<div class="form-group col-md-6">
							<label class="col-form-label required" for="time_start">وقت بداية الدوام</label>
							<div class="row gx-2 mb-3">
							<div class="col">
							<label>الساعة</label>
							<div class="dropdown-scroll">
								<div id="hourToggle" class="dropdown-toggle-custom"><?=!empty($hours) ? $hours : '--'?></div>
								<div id="hourMenu" class="dropdown-menu-custom"></div>
							</div>
							</div>
							<div class="col">
							<label>الدقيقة</label>
							<div class="dropdown-scroll">
								<div id="minuteToggle" class="dropdown-toggle-custom"><?=!empty($minutes) ? $minutes : '--'?></div>
								<div id="minuteMenu" class="dropdown-menu-custom"></div>
							</div>
							</div>
						</div>
						<input type="hidden" value="<?=!empty($row['ShiftStartTime']) ? $row['ShiftStartTime'] : ''?>" id="time_start" class="form-control"  name="time_start" readonly>
		
					</div>


					<div class="form-group col-md-6">
							<label class="col-form-label required" for="time_start">وقت نهاية الدوام</label>
							<div class="row gx-2 mb-3">
							<div class="col">
							<label>الساعة</label>
							<div class="dropdown-scroll">
								<div id="hourToggle_e" class="dropdown-toggle-custom"><?=!empty($hours_e) ? $hours_e : '--'?></div>
								<div id="hourMenu_e" class="dropdown-menu-custom"></div>
							</div>
							</div>
							<div class="col">
							<label>الدقيقة</label>
							<div class="dropdown-scroll">
								<div id="minuteToggle_e" class="dropdown-toggle-custom"><?=!empty($minutes_e) ? $minutes_e : '--'?></div>
								<div id="minuteMenu_e" class="dropdown-menu-custom"></div>
							</div>
							</div>
						</div>
						<input type="hidden" value="<?=!empty($row['ShiftEndTime']) ? $row['ShiftEndTime'] : ''?>" id="time_end" class="form-control"  name="time_end" readonly>
                             
					</div>



					
						</div>






<!-- الاعدادت الخاصة باالفترة -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header"    style="background: rgb(250, 253, 255); cursor: pointer">
        <h4 class="card-title">اعدادات بداية الدوام</h4>
        <span style="float: left;"><div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
            <i class="fas fa-minus"></i>
            </button>
          </div></span>
      </div>

      <div class="card-body">

        <div class="row mb-3">
          <!-- الوقت المسموح به للتأخير -->
          <div class="form-group col-md-6">
            <label class="form-label required">التأخير المسموح به</label>
            <input type="number" value="<?=!empty($row_2['allowed_late_minutes']) ? $row_2['allowed_late_minutes'] : ''?>"  class="form-control" name="allowed_late_minutes" min="0" max="120" value="15">
          </div>

          <!-- هل ترغب بتفعيل نصف دوام؟ -->
          <div class="form-group col-md-6 ">
            <label class="form-label">اعدادت مابعد التاخير المسموح بة</label>
            <select class=" selectpicker enableHalfDay" data-container="body" data-size="5" data-width="100%"  id="enableHalfDay" name="enableHalfDay" required>
           <?php
            if(!empty($row_2['enable_half_day'])) {
										echo "<option value='{$row_2['enable_half_day']}' selected>".(($row_2['enable_half_day']==1)?'نعم، حساب ساعات العمل الفعلية فقط' :(($row_2['enable_half_day']==2)?'>لا، أي تأخير أكثر من المسموح = غياب':'نعم، أريد تحديد وقت نصف دوام وغياب'))."</option>";
									}
                  ?>
            <option value="1" <?=!empty($row['enable_early_half_day'])?'selected':'' ?>>نعم، حساب ساعات العمل الفعلية فقط</option>
              <option value="2" >لا، أي تأخير أكثر من المسموح = غياب</option>
              <option value="3">نعم، أريد تحديد وقت نصف دوام وغياب</option>
              
            </select>
          </div>
        </div>

        <!-- إعدادات نصف الدوام والغياب -->
        <div class="row" id="halfDaySettings" style="display: none;">
          <div class="form-group col-md-6">
            <label class="form-label"> بداية اعتبار نصف دوام<br><small>(بعد كم دقيقة من بداية الدوام)</small></label>
            <input type="number" value="<?=!empty($row_2['half_day_minutes']) ? $row_2['half_day_minutes'] : ''?>" class="form-control" name="half_day_minutes" min="0" max="480" value="120">
          </div>

          <div class="form-group col-md-6">
            <label class="form-label"> بداية اعتبار غياب<br><small>(بعد كم دقيقة من بداية الدوام)</small></label>
            <input type="number" value="<?=!empty($row_2['absent_minutes']) ? $row_2['absent_minutes'] : ''?>" class="form-control" name="absent_minutes" min="0" max="480" value="180">
          </div>
        </div>

      </div>
    </div>
  </div>
</div>




<div class="row" id="setting_add" style="display:none">
  <div class="col-12">
    <div class="card">
      <div class="card-header" style="background: rgb(250, 253, 255); cursor: pointer">
        <h4 class="card-title">إعدادات نهاية الدوام (الانصراف)</h4>
        <span style="float: left;">
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" style="margin: 0; padding: 2px 5px;">
              <i class="fas fa-minus"></i>
            </button>
          </div>
        </span>
      </div> 

      <div class="card-body">

        <div class="row mb-3">
          <!-- دقائق الانصراف المبكر المسموح بها -->
          <div class="form-group col-md-6">
            <label class="form-label ">  الانصراف المبكر المسموح به</label>
            <input type="number" value="<?=!empty($row_2['allowed_early_leave']) ? $row_2['allowed_early_leave'] : '1'?>" class="form-control" name="allowed_early_leave" min="0" max="120" value="10">
          </div>

          <!-- هل ترغب بتفعيل نصف دوام عند الانصراف المبكر؟ -->
          <div class="form-group col-md-6">
            <label class="form-label">اعدادات ما بعد الانصراف المسموح به</label>
            
            <select class=" selectpicker enableEarlyHalfDay" data-container="body" data-size="5" data-width="100%"  id="enableEarlyHalfDay" name="enableEarlyHalfDay" required>

            <?php
            if(!empty($row_2['enable_early_half_day'])) {
										echo "<option value='{$row_2['enable_early_half_day']}' selected>".(($row_2['enable_early_half_day']==1)?'نعم، حساب ساعات العمل الفعلية فقط' :(($row_2['enable_early_half_day']==2)?'>لا، أي تأخير أكثر من المسموح = غياب':'نعم، أريد تحديد وقت نصف دوام وغياب'))."</option>";
									}
                  ?>
            <option value="1" <?=!empty($row['enable_early_half_day'])?'selected':'' ?> >نعم، حساب ساعات العمل الفعلية فقط</option>
              <option value="2" >لا، أي انصراف مبكر أكثر من المسموح = غياب</option>
              <option value="3">نعم، أريد تحديد وقت نصف دوام وغياب</option>
              
            </select>
          </div>
        </div>

        <!-- إعدادات نصف الدوام والغياب عند الانصراف المبكر -->
        <div class="row" id="earlyHalfDaySettings" style="display: none;">
          <div class="form-group col-md-6">
            <label class="form-label"> بداية اعتبار نصف دوام<br><small>(قبل نهاية الدوام بـ كم دقيقة)</small></label>
            <input type="number" value="<?=!empty($row_2['early_half_day_minutes']) ? $row_2['early_half_day_minutes'] : ''?>" class="form-control" name="early_half_day_minutes" min="0" max="480" value="90">
          </div>

          <div class="form-group col-md-6">
            <label class="form-label"> بداية اعتبار غياب<br><small>(قبل نهاية الدوام بـ كم دقيقة)</small></label>
            <input type="number" value="<?=!empty($row_2['early_absent_minutes']) ? $row_2['early_absent_minutes'] : ''?>"  class="form-control" name="early_absent_minutes" min="0" max="480" value="120">
          </div>
        </div>

        <hr>

        <div class="row">
          <!-- في حال عدم تسجيل الانصراف -->
          <div class="form-group col-md-6">
            <label class="form-label"> في حال عدم تسجيل الانصراف </label>
            <select class="form-control" name="missing_checkout_action">
              <?php
            if(!empty($row['missing_checkout_action'])) {
										echo "<option value='{$row['missing_checkout_action']}' selected>".(($row_2['missing_checkout_action']==1)?'اعتماد نهاية الدوام تلقائيًا' :(($row_2['missing_checkout_action']==2)?'يُحسب نصف دوام':(($row_2['missing_checkout_action']==3)?'يُحسب غياب كامل':'فقط تنبيه بدون خصم')))."</option>";
									}
                  ?>
              <option value="1"  <?=!empty($row['missing_checkout_action'])?'selected':'' ?> >اعتماد نهاية الدوام تلقائيًا</option>
              <option value="2">يُحسب نصف دوام</option>
              <option value="3">يُحسب غياب كامل</option>
              <option value="4">فقط تنبيه بدون خصم</option>
            </select>
          </div>

          <!-- في حال تسجيل انصراف بعد الوقت الرسمي -->
          <div class="form-group col-md-6">
            <label class="form-label"> في حال تسجيل انصراف بعد نهاية الدوام</label>
            <select class="form-control" name="late_checkout_policy">
            <?php
            if(!empty($row['late_checkout_policy'])) {
										echo "<option value='{$row['late_checkout_policy']}' selected>".(($row_2['late_checkout_policy']==1)?'لا يُحسب شيء' :(($row_2['late_checkout_policy']==2)?'يُحسب كعمل إضافي':'فقط إشعار / تنبيه'))."</option>";
									}
                  ?>
              <option value="1" <?=!empty($row['late_checkout_policy'])?'selected':'' ?> >لا يُحسب شيء</option>
              <option value="2">يُحسب كعمل إضافي</option>
              <option value="3">فقط إشعار / تنبيه</option>
            </select>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>




 
						<!--  -->

                       <div class="row"> 
						   <div class="col-sm-10">
							  <label class="switch switch-danger switch-md">
								 <input type="checkbox" name="stopped" value="1" id="stopped" <?=!empty($row['ShiftState']) ? 'checked': ''?>>
								 <span></span>  ايقاف الفترة
							  </label>
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
 <script src="plugins/select2_n/dist/js/select2.full.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');

$(document).on('click', '#save-shift', function(){
	$('#AddShift').trigger('submit');
});




$('#AddShift').on('submit', function(e){  
    	e.preventDefault();
	var sup_id = $('#shift_id').val();
	var form_data = $(this).serialize() + '&id=' + param_id;
	if($(this).valid()){
	//var chk = $('#store_stoped').is(':checked');  
	$.ajax({
        url:"./hr-app/shift-add",
        method:"POST", 
		data:form_data,
		// data:new FormD?ata(this), 
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
				if(data.id > 0){  
                   window.location.href = 'shift-view?id='+data.id+'';
				}

			}
            else
            {
				toastr.error(data.msg);
			}
			
			
			
			

			$('#preloading').hide();
					
					
			
		}
	});
	
	}
	
});



	$('#AddShift').validate({
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












	// 
	$(function() {
  const $hourToggle = $('#hourToggle');
  const $hourMenu = $('#hourMenu');
  const $minuteToggle = $('#minuteToggle');
  const $minuteMenu = $('#minuteMenu');
  const $timeInput = $('#time_start');

  const $hourToggle_e = $('#hourToggle_e');
  const $hourMenu_e = $('#hourMenu_e');
  const $minuteToggle_e = $('#minuteToggle_e');
  const $minuteMenu_e = $('#minuteMenu_e');
  const $timeInput_e = $('#time_end');

  function generateOptions($container, $toggle, range, step = 1) {
    for(let i = 0; i < range; i += step) {
      const val = i.toString().padStart(2, '0');
      const $option = $('<div>').text(val);
      $option.on('click', function() {
        $toggle.text(val);
        $container.hide();
        updateTime();
      });
      $container.append($option);
    }
  }

  function updateTime() {
    const h1 = $hourToggle.text();
    const m1 = $minuteToggle.text();
    if (h1 !== '--' && m1 !== '--') {
      $timeInput.val(`${h1}:${m1}`);
    } else {
      $timeInput.val('');
    }

    const h2 = $hourToggle_e.text();
    const m2 = $minuteToggle_e.text();
    if (h2 !== '--' && m2 !== '--') {
      $timeInput_e.val(`${h2}:${m2}`);
    } else {
      $timeInput_e.val('');
    }
  }

  generateOptions($hourMenu, $hourToggle, 25);
  generateOptions($minuteMenu, $minuteToggle, 65, 5);
  generateOptions($hourMenu_e, $hourToggle_e, 25);
  generateOptions($minuteMenu_e, $minuteToggle_e, 65, 5);

  $hourToggle.on('click', function(e) {
    e.stopPropagation();
    $hourMenu.toggle();
    $minuteMenu.hide();
    $hourMenu_e.hide();
    $minuteMenu_e.hide();
  });

  $minuteToggle.on('click', function(e) {
    e.stopPropagation();
    $minuteMenu.toggle();
    $hourMenu.hide();
    $hourMenu_e.hide();
    $minuteMenu_e.hide();
  });

  $hourToggle_e.on('click', function(e) {
    e.stopPropagation();
    $hourMenu_e.toggle();
    $minuteMenu_e.hide();
    $hourMenu.hide();
    $minuteMenu.hide();
  });

  $minuteToggle_e.on('click', function(e) {
    e.stopPropagation();
    $minuteMenu_e.toggle();
    $hourMenu_e.hide();
    $hourMenu.hide();
    $minuteMenu.hide();
  });

  $(document).on('click', function() {
    $hourMenu.hide();
    $minuteMenu.hide();
    $hourMenu_e.hide();
    $minuteMenu_e.hide();
  });
});
















// الاعدادات
function enableHalfDay(el) {
  const value = $(el).val();
  if (value === '3') {
    $('#halfDaySettings').show();
  } else {
    $('#halfDaySettings').hide();
  }
}

function enableEarlyHalfDay(el) {
  const value = $(el).val();
  if (value === '3') {
    $('#earlyHalfDaySettings').show();
  } else {
    $('#earlyHalfDaySettings').hide();
  }
}

// عند التغيير
$('#enableHalfDay').on('change', function () {
  enableHalfDay(this);
});
$('#enableEarlyHalfDay').on('change', function () {
  enableEarlyHalfDay(this);
});

function toggleSettingAdd() {
    const value = $('#Footprint').val();
    if (value === '1') {
      $('#setting_add').hide();
    } else {
      $('#setting_add').show();
    }
  }

  // عند تغيير قيمة "عدد البصمات"
  $('#Footprint').on('change', toggleSettingAdd);

// عند تحميل الصفحة
toggleSettingAdd();
enableHalfDay($('#enableHalfDay'));
enableEarlyHalfDay($('#enableEarlyHalfDay'));


  


});
 

</script>