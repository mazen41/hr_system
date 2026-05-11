 
<?php
$appid  = 'HR';
$page_perm=['اضافة مجموعه'];

// $screen = 'المجموعات';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة المجموعات';
// }else{
// $page_title = 'إضافة مجموعه';
// }
 
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
// 
// $querygroub = "SELECT UserID,FirstName,SecondName
// FROM   tblusers ";
// $stgroub = $connect_pdo->prepare($querygroub);
// $stgroub->execute();
// if($stgroub->rowCount() > 0){
//     $allowed_groub = $stgroub->fetchAll();
//     if (!empty($allowed_groub))
// {                                 
//     foreach($allowed_groub as $row_){
//         $groub [$row_['UserID']]= $row_['FirstName'].' '.$row_['FirstName'];
//     }
// }
// }
//  

 
$form_title = 'إضافةمجموعة جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT *
 	FROM  tblgroup 
	WHERE  Id  = :id 
	LIMIT 1 ";

	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $client_no
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		$client_id = $row['Id'];
		$form_title = 'تعديل مجموعه  ' .$row['Name'].'';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("groub-list"); </script>';
		die();
	}
	
		 function GetlastID($connect, $id) {
		$sql = "SELECT 	GroupID FROM tblremewal WHERE FIND_IN_SET(:id, GroupID) LIMIT 1";
		$stmt = $connect->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->rowCount() > 0) {
			return true;
		}
		return false;
	}
}
?>


	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span>
          </div><!-- /.col -->
          <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-groub"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
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
					<h4 class="card-title">تفاضيل المجموعة</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-12 col-md-6">
							<label class="col-form-label required"  for="groubname">اسم المجموعة</label>
							<input type="text"  value="<?=!empty($row['Name']) ? $row['Name'] : ''?>" class="form-control"  data-toggle="tooltip"  id="groubname" name="groubname"  autocomplete="off" required>
						 </div>
						 <div class="form-group col-12 col-md-6">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list[]" <?php echo !isset($_GET['id']) ? "multiple='multiple'" : ''; ?> required >
                                     <?php
                              if (isset($_GET['id']) && GetlastID($connect_pdo,$client_no)) {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">هذه المجموعه تم ربطها بموظفين لايمكن تغير الفرع</label>
                                 
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
                        
                      </div>
                    



                   <div class="row">
                    <div class="form-group col-12">
                         <label class="col-form-label "  for="Note">ملاحظات</label><br>
                         <textarea name="note" id="note" class="form-control" rows="5" style="resize: vertical;"><?=!empty($row['Description']) ? $row['Description'] : ''?></textarea>
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
const param_id = urlParams.get('id');

$(document).on('click', '#save-groub', function(){
	$('#AddShift').trigger('submit');
});




$('#AddShift').on('submit', function(e){  
    e.preventDefault();
	var form_data = $(this).serialize() + '&id=' + param_id;
	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=groub-add",
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
                   window.location.href = 'groub-view?id='+data.id+'';
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


});
 

</script>
