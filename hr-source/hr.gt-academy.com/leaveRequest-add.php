   
<?php

$screen = 'إدارة الموارد البشرية';
$page_title = 'طب اجازة ';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 
$form_title = 'إضافة طلب جديد';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';

function Getinfo($connect, $user)
{
    $query = "SELECT Id, SectionID, UserID, GroupID, GradeID, jobtitleID
              FROM tblremewal 
              WHERE state IS NOT NULL AND UserID = :user 
              ORDER BY Id DESC 
              LIMIT 1";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':user', $user, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// function rollsList($connect, $user){

//     $query_ = "SELECT BranchID FROM tblusers WHERE UserID = :user"; 
//     $stm_ = $connect->prepare($query_);
//     $stm_->bindParam(':user', $user, PDO::PARAM_INT);
//     $stm_->execute();
//     $branch = $stm_->fetch(PDO::FETCH_ASSOC);

//     $query = "SELECT Id ,BranchID, Name, Description, isaccept, type, state, RequiresAttachment 
//               ,for_what,chose
//               FROM leaveclassification 
//               WHERE state IS NULL AND BranchID = :branchID";
//     $stm = $connect->prepare($query);
//     $stm->bindParam(':branchID', $branch['BranchID'], PDO::PARAM_INT);
//     $stm->execute();
    
//     if ($stm->rowCount() > 0) {
//         return $stm->fetchAll(PDO::FETCH_ASSOC);
//     }
//     return [];
// }

function rollsList($connect, $user){
  // 1. الحصول على بيانات الموظف (GroupID, BranchID)
  $info = Getinfo($connect, $user);

  $branchID = null;
  $sectionID = null;
  $groupID = null;
  $gradeID = null;
  $jobtitleID = null;

  if ($info) {
      $branchID = $info['BranchID'] ?? null;
      $sectionID = $info['SectionID'] ?? null;
      $groupID = $info['GroupID'] ?? null;
      $gradeID = $info['GradeID'] ?? null;
      $jobtitleID = $info['jobtitleID'] ?? null;
  } 

  // 2. جلب الإجازات بناءً على الشروط الثلاثة
  $query = "
      SELECT Id, BranchID, Name, Description, isaccept, type, state, RequiresAttachment,
             for_what, chose
      FROM leaveclassification 
      WHERE state IS NULL
      AND (
          for_what IS NULL
          OR (for_what = 1 AND chose = :userID)
          OR (for_what = 4 AND chose = :SectionID)
          OR (for_what = 2 AND chose = :groupID)
          OR (for_what = 3 AND chose = :GradeID)
          OR (for_what = 5 AND chose = :jobtitleID)
      )
      AND (BranchID = :branchID OR :branchID2 IS NULL)
  ";

  $stmt = $connect->prepare($query);
  $stmt->bindValue(':userID', $user, PDO::PARAM_INT);
  $stmt->bindValue(':SectionID', $sectionID, PDO::PARAM_INT);
  $stmt->bindValue(':groupID', $groupID, PDO::PARAM_INT);
  $stmt->bindValue(':GradeID', $gradeID, PDO::PARAM_INT);
  $stmt->bindValue(':jobtitleID', $jobtitleID, PDO::PARAM_INT);
  $stmt->bindValue(':branchID', $branchID, PDO::PARAM_INT);
  $stmt->bindValue(':branchID2', $branchID, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// 
$roles = rollsList($connect_pdo,$user);

if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT Id ,UserID,leavetype,path,leave_start_date,leave_end_date,status,description
 	FROM   tblleaverequest 
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("leaveRequest-list"); </script>';
            die(); 
        }

		$client_id = $row['Id'];
		

        if(isset($_GET['copy'])){
          $save_btn = 'حفظ واعتماد';
          $save_btn_2 = 'حفظ كمسودة';
         }
          else{
          $save_btn = 'حفظ التغييرات';
          $form_title = 'تعديل طلب الاجازة ';
          $save_btn_2 = null;
          }
	}
	else{
		echo'<script> location.replace("leaveRequest-list"); </script>';
		die();
	}		
}
?>

<style>
  #ess
  {
    display:none;
  }
</style>
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-incentive"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
      <?php 
      if(!empty($save_btn_2))
      { 
        ?>
        <button type="button" class="btn btn-success"  id="save-draft"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn_2?></span></button>
     <?php
      }
     
      ?> 
    </div>
        </div>
      </div>
    </div>
   
	
	
		
	


    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="AddleaveRequesr" enctype="multipart/form-data">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل الطلب</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 

                        

                        <div class="row">
                            <!-- Employee Selection - REQUIRED -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="emp_id" class="col-form-label required">الموظف</label>
                                    <select class="form-control selectpicker" data-live-search="true" data-container="body" data-size="5" data-width="100%" title="اختر الموظف" id="emp_id" name="emp_id" required>
                                        <?php
                                        // Get employees for dropdown
                                        $empQuery = "SELECT u.UserID, u.FirstName, u.LastName FROM tblusers u WHERE u.isemp = 1 AND (u.IsDisabled IS NULL OR u.IsDisabled = 0) ORDER BY u.FirstName";
                                        $empStmt = $connect_pdo->query($empQuery);
                                        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($employees as $emp) {
                                            $selected = (!empty($row['UserID']) && $row['UserID'] == $emp['UserID']) ? 'selected' : '';
                                            echo '<option value="' . $emp['UserID'] . '" ' . $selected . '>' . htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="user_group" class="col-form-label  logindata required">نوع الاجازة</label>
                                        <select class="form-control logindata  selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%"  title="حدد نوع الاجازة" id="leavetype" name="leavetype"  required>
                                        <?php                              
                                        if(!empty($roles)){
                                            foreach($roles as $role)
                                            {
                                                echo'<option data-info="'.(int)$role['RequiresAttachment'].'"  value="'.(int)$role['Id'].'"  isaccept="'.(int)$role['isaccept'].'" '.(!empty($row['leavetype']) && (int)$role['Id'] == (int)$row['leavetype'] ? 'selected' : '').'>'.$role['Name'].'</option>';
                                            }
                                        }
                                        ?>
                                        
                                        
                                        </select>
                                </div>
                             </div>
                             <input type="hidden" name="isdraft" id="isdraft"/>
                             <!--  -->
                        <div class="form-group col-md-3" id="attachment" style="display:<?=(!empty($row['path']) ? '' :'none')?>">
                        <div class="form-group">
						  <label class="col-form-label  " for="attacmentt">ارفقها</label>
						  <input type="file" name="attacmentt" class="form-control"  placeholder="ارفق الطلب" id="attacmentt" autocomplete="off" value="" >
						</div>    
                        </div>
                        <?php
                        if(!empty($row['path']))
                        {
                          ?>
                          <div class="col-md-3">
                          <div class="form-group">
						  <div class="file_control" style="display:<?=(!empty($row['path']) ? '' :'none')?>;padding-right:7px;padding-bottom: 10px;">
						  <button type="button" value="<?=(!empty($row['path']) ? $row['path'].'.2' :'')?>" class="btn btn-xs btn-default" id="download_file"><i class="fa fa-download"></i> تنزيل المرفق</button>
							</div>
                        </div>
                        </div>
                          <?php
                        }
                        ?>
                        <!--  -->
                        <input type="hidden" name="state_leave" class="form-control"   id="state_leave" value="<?=(!empty($row['status']) ? $row['status'] :'')?>" >
                        <input type="hidden" name="attachment_leave" class="form-control"   id="attachment_leave" >
                       
                      </div>

                       <div class="row">
                        <div class="form-group col-md-6">
                            <label class="col-form-label required" for="date_leave_start">تاريخ بداية الاجازة</label>
                            <input type="text" name="date_leave_start" class="form-control input-date"  placeholder="تاريخ بدابة الاجازة" id="date_leave_start" autocomplete="off" value="<?=(!empty($row['leave_start_date'])? $row['leave_start_date'] : '' )?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="col-form-label required" for="date_leave_end">تاريخ انتهاء الاجازة</label>
                            <input type="text" name="date_leave_end" class="form-control input-date"  placeholder="تاريخ انتهاء الاجازة" id="date_leave_end" autocomplete="off" value="<?=(!empty($row['leave_end_date'])? $row['leave_end_date'] : '' )?>" required>
                        </div>


                   </div>
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">وصف الاجازة</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['description']) ? $row['description'] : ''?></textarea>
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
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


  const urlParams = new URLSearchParams(window.location.search);
let param_id = urlParams.get('id');
if (urlParams.has('copy')) {
    param_id = null;
}


$(document).on('click', '#save-incentive', function() {
  $('#isdraft').val(1);
    $('#AddleaveRequesr').trigger('submit');
    
});

$(document).on('click', '#save-draft', function() {
  $('#isdraft').val(null);
    $('#AddleaveRequesr').trigger('submit');
});



$('#AddleaveRequesr').on('submit', function(e){  
    e.preventDefault();

  var form_data = new FormData(this);
  form_data.append('id', param_id);
  
  // Map form fields to expected backend names
  form_data.append('leave_start_date', $('#date_leave_start').val());
  form_data.append('leave_end_date', $('#date_leave_end').val());
  form_data.append('description', $('#Reson').val());

	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=leaveRequest-add",
        method:"POST", 
		data:form_data,
    contentType: false,
    processData: false,
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
				toastr.success(data.msg);
				if(data.id > 0){  
                   window.location.href = 'leaveRequest-list';
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



	$('#AddleaveRequesr').validate({
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

$('#amount').on('keypress', function(e) {
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode < 48 || charCode > 57) {
        e.preventDefault();  
    }
});
// جلب بيانات الحديد وعرضها


// 
    // for get extinsion
    $('#leavetype').change(function() {
      const selectedOption = $(this).find('option:selected');
      if(selectedOption.data('info')==1)
    {
      $('#attachment').show();
    }
    else
    {
      $('#attachment').hide();
    }
    $("#state_leave").val(selectedOption.attr('isaccept'));
    $("#attachment_leave").val(selectedOption.data('info'))
    
    });


}); 

</script>
