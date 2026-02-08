  
<?php
$appid  = 'HR';
$page_perm=['اضافة ترقية'];
// $screen = 'تجديد العقود';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة العقود';
// }else{
// $page_title = 'إضافة عقد';
// }

$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة الترقيات';
  
include_once('inc/header.php');
$all_branches =  $User->allBranches();

 
$form_title = ' تجديد';
isset($_GET['add'])?$save_btn = 'حفظ':$save_btn = 'تحديث';

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

if(isset($_GET['add']) || isset($_GET['edit'])){

	$id =   !empty($_GET['add'])?(int)$_GET['add']:(int)$_GET['edit'];
	
	$parma = array(':id'  => $id, );
			
    $query = "	SELECT 	k.Id,u.UserID,u.FirstName, u.LastName,b.branch_name,
    k.SectionID,k.BranchID,k.GroupID,k.GradeID,k.shiftID,k.TypeID,k.fingerID,k.jobtitleID,k.state,
    k.Salary,k.Currency,k.new_s_date,k.new_e_date,
    a.Name as section_name,j.Name as jobtitle_name,c.Name as group_name,f.Name as name_grade,
    h.Name as name_n,k.day,k.Reason
    FROM  tblremewal k

    left join  tblusers u ON u.UserID = k.UserID
    left join branches b ON b.branch_id = k.BranchID

    left join  tblsection a ON a.Id = k.SectionID
    left join  tbljobtitle j ON j.Id = k.jobtitleID   
    left join   tblgroup c ON c.Id = k.GroupID
    left join   tbljobgrade f ON f.Id = k.GradeID
    left join   tblemploymenttype h ON h.Id = k.TypeID
    	where k.Id =:id  
    ";
    
	$stm = $connect_pdo->prepare($query);
	$stm->execute($parma);
	
	if($stm->rowCount() > 0){
        $employee = $stm->fetch();
      

		$parma_ = array(
			':BranchID'  => $employee['BranchID'], 
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



		$user_insurance = !empty($employee['user_insurance']) ? array_unique(explode ( ',', $employee['user_insurance'])) : [];
		$user_shift = !empty($employee['shiftID']) ? array_unique(explode ( ',', $employee['shiftID'])) : [];
		$user_finger_print_type = !empty($employee['fingerID']) ? array_unique(explode ( ',', $employee['fingerID'])) : [];
		
	
	}
    else{
		echo'<script> location.replace("promotion-list"); </script>';	
	}
	$roles = rollsList($connect_pdo);
		
}
else{
        echo'<script> location.replace("promotion-list"); </script>';	
}
?>


	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-data"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
		 </div>
        </div>
      </div>
    </div>
   
	
	
		
	


    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="user_fm">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل ترقية الموظف <?= $employee['FirstName'].' '.$employee['LastName']  ?></h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 
					 <div class="row">
                             <div class="form-group col-md-4">
                                <label class="col-form-label required" for="branchs_list">الفرع الجديد</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list"  required >
                                    <?php
									if(!empty($employee['BranchID'])){
                                        echo'<option value="'.$employee['BranchID'].'" selected>'.$employee['branch_name'].'</option>';
                                    }
                                        foreach($all_branches as $id => $name){	
                                            echo'<option value="'.$id.'" >'.$name.'</option>';
                                        }

                                        

                                    ?>
                                    </select>
                              </div>

                              <div class="col-md-4">
                                <div class="form-group">
                                <label for="user_jobtitle" class="col-form-label  logindata required">المسمى الوظيفي</label>
								<select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد المسمى الوظيفي" id="user_jobtitle" name="user_jobtitle"  required>
								<?php
								if(!empty( $jobtitle)){
									
									echo'<option value="'.$employee['jobtitleID'].'" selected>'.$employee['jobtitle_name'].'</option>';
									foreach($jobtitle as $sec)
									{
										echo'<option value="'.$sec["Id"].'" >'.$sec["Name"].'</option>';
									}
									
								}  
								?>	
								</select>
                                </div>
                             </div>
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label for="user_section" class="col-form-label  logindata required">الادارة الجديد</label>
                                    <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد باي قسم" id="user_section" name="user_section"  required>
								<?php
								if(!empty( $section)){
									
									echo'<option value="'.$employee['SectionID'].'" selected>'.$employee['section_name'].'</option>';
									foreach($section as $sec)
									{
										echo'<option value="'.$sec["Id"].'" >'.$sec["Name"].'</option>';
									}
									
								}  
								?>	
								</select>
                                </div>
                             </div>
                              
						</div>

                        <div class="row" style="display: none;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="user_group_" class="col-form-label  logindata ">المجموعة الوظيفية الجديد</label>
                                    <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد المجموعة الوظيفية" id="user_group_" name="user_group_"  >
							<?php 
								if(!empty( $groub)){
		
									echo'<option value="'.$employee['GroupID'].'" selected>'.$employee['group_name'].'</option>';
									foreach($groub as $gro)
									{
										echo'<option value="'.$gro["Id"].'" >'.$gro["Name"].'</option>';
									}
									
								} 
							?>		
                            </select>
                                </div>
                                </div>
                                

                       
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="user_grade" class="col-form-label  logindata ">الدرجة الوظيفية الجديد</label>
                                        <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد الدرجة الوظيفية" id="user_grade" name="user_grade" >
								<?php 
								if(!empty( $jobgrade)){
		
									echo'<option value="'.$employee['GradeID'].'" selected>'.$employee['name_grade'].'</option>';
									foreach($jobgrade as $job)
									{
										echo'<option value="'.$job["Id"].'" >'.$job["Name"].'</option>';
									}
									
								}
								?>   
								</select>
                                    </div>
                                 </div>
                                 
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="user_type" class="col-form-label  logindata  ">نمط العمل الجديد</label>
                                        <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد نمط العمل" id="user_type" name="user_type" >
								<?php 
								if(!empty( $employmenttyp)){

									echo'<option value="'.$employee['TypeID'].'" selected>'.$employee['name_n'].'</option>';
									foreach($employmenttyp as $typ)
									{
										echo'<option value="'.$typ["Id"].'" >'.$typ["Name"].'</option>';
									}
									
								}
								?> 
								</select>
                                    </div>
                                 </div>


                   

                       </div>

                       <div class="row" style="display: none;">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="user_finger" class="col-form-label  logindata required">جهاز البصمة</label>
                                    <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد صلاحية المستخدم" id="user_finger" name="user_finger"  required multiple="multiple" multiple data-selected-text-format="count > 2" multiple data-actions-box="true" data-width="fit" style="display:none_"> 
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
                                 <label for="user_shift" class="col-form-label  logindata required">فترات العمل</label>
                                     <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد فترات العمل " id="user_shift" name="user_shift"  required multiple="multiple" multiple data-selected-text-format="count > 2" multiple data-actions-box="true" data-width="fit" style="display:none_">
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
                       <div class="row">
                        <div class="col-md-6">
                            <!-- text input -->
                            <div class="form-group">
                              <label class="col-form-label required" for="emp_salary">الراتب الجديد</label>
                              <input type="text" class="form-control " placeholder="0.00" data-toggle="tooltip" title="ادخل الراتب" id="emp_salary" name="emp_salary" placeholder=""  value="<?=(!empty($employee['Salary'])? $employee['Salary'] : '' )?>" required>
                            </div>
                          </div>
                          <div class="form-group col-md-6">
							<label class="col-form-label required" for="currency">العملة </label>
							<select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل العملة" id="currency" name="currency" required >
							<?php if(!empty($employee['Currency'])){ ?>
								<!-- <option value="<?= $employee['Currency'] ?>" selected><?= $employee['Currency'] ?></option> -->
								<option value="<?= $User->currency; ?>" selected>عملة النظام</option>
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
                   </div>
                   <input type="hidden" name="UserID" id="UserID" value="<?= $employee['UserID']  ?>">
                   <div class="row">
                    <div class="col-md-4">
                        <!-- <div class="form-group"> -->
                          <!-- <label class="col-form-label required" for="emp_contract_S">تاريخ بداية العقد الجديد</label> -->
                          <input type="hidden" name="emp_contract_S" class="form-control input-date"  placeholder="تاريخ بداية العقد" id="emp_contract_S" autocomplete="off" value="<?=(!empty($employee['new_s_date'])? $employee['new_s_date'] : '' )?>" required >
                        <!-- </div> -->
                      </div>
                      <div class="col-md-4">
                        <!-- <div class="form-group"> -->
                          <!-- <label class="col-form-label required " for="emp_contract_F">تاريخ انتهاء العقد الجديد</label> -->
                          <input type="hidden" name="emp_contract_F" class="form-control input-date"  placeholder="تاريخ انتهاء العقد" id="emp_contract_F" autocomplete="off" value="<?=(!empty($employee['new_e_date'])? $employee['new_e_date'] : '' )?>" required>
                        <!-- </div> -->
                      </div>
                      <div class="col-md-4">
                        <!-- <div class="form-group"> -->
                          <!-- <label class="col-form-label  " for="day_before_go">الايام  قبل المغادرة</label> -->
                          <input type="hidden" name="day_before_go" class="form-control "  placeholder="الايام قبل المغادرة" id="day_before_go" autocomplete="off" value="<?=(!empty($employee['day'])? $employee['day'] : '' )?>" >
                          <input type="hidden" name="state" id="state" value="<?= isset($_GET['edit']) && !empty($employee['state']) ? $employee['state'] : '' ?>">

                        <!-- </div> -->
                      </div>
                        
                      </div>


                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">السبب</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($employee['Reason']) ? $employee['Reason'] : ''?></textarea>
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
const param_id = urlParams.get('add');
const param_idd = urlParams.get('edit');

$(document).on('click', '#save-data', function(){
	$('#user_fm').trigger('submit');
});





$('#user_fm').on('submit', function(e){  
    e.preventDefault();
    
    var user_shiftt = get_filter('user_shift'); 
    var user_fingerr=get_filter('user_finger'); 
//    var form_data = $(this).serialize() + '&add=' + param_id+ '&edit=' + param_idd+ '&user_shiftt=' + user_shiftt+ '&user_fingerr=' + user_fingerr;
        form_data = $(this).serialize() + '&add=' + param_id + '&edit=' + param_idd+ '&user_shiftt=' + user_shiftt+ '&user_fingerr=' + user_fingerr;
	if($(this).valid()){
	$.ajax({
        url:"./hr-app/promotion-add",
        method:"POST", 
		data:form_data,
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
				if(data.id > 0){  
                   window.location.href = 'promotion-list';
				}
                if(data.edit){  
                   window.location.href = 'promotion-list';
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

function get_filter(input_name)
{
	var filter = [];
		$('select[name="'+input_name+'"] option:selected').each(function() {
		filter.push($(this).val());
	   });
	return filter;
}


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







    $('#branchs_list').change(function() {
        get_info( $(this).val()); 
    });
function get_info(branch) {
        // var selectedValue = $(this).val(); 
        var selectedValue =branch; 

        if (selectedValue) {
            $.ajax({
                url: '/hr-app/allUserInfo',
                type: 'POST',
                data: { value: selectedValue },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
                success: function(response) { 
                    // user_manager
					populateSelect('#user_section', response.section);
                    populateSelect('#user_jobtitle', response.jobtitle);
					populateSelect('#user_grade', response.JobGrade);
					populateSelect('#user_shift', response.Shift);
					populateSelect('#user_finger', response.fingerprint);
					populateSelect('#user_insuance', response.insurance);
					populateSelect('#user_group_', response.groub);
					populateSelect('#user_type', response.tblemploymenttype);
					
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        } 
    }
	function populateSelect(selectId, items) {
    var select = $(selectId);
    select.empty(); 
    if (items && items.length > 0) {		
        $.each(items, function(index, item) {
            select.append('<option value="' + item.data.id + '">' + item.data.name + '</option>');
        });
    } 
	select.selectpicker('refresh'); // تحديث SelectPicker
}




    $('#emp_salary').on('keypress', function(e) {
        var key = e.which || e.keyCode;
        if (key >= 48 && key <= 57) {
            return true;
        }    
        return false;
    });
    $('#day_before_go').on('keypress', function(e) {
        var key = e.which || e.keyCode;
        if (key >= 48 && key <= 57) {
            return true;
        }    
        return false;
    });

    
// جلب بيانات الحديد وعرضها











});
 

</script>