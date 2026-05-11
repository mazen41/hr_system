 
<?php
$appid  = 'HR';
$page_perm=['اضافة شركة تامين'];

// $screen = 'شركات التامين';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة شركات التامين';
// }else{
// $page_title = 'إضافة شركة';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);


 
$form_title = 'إضافة شركة تامين جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT *
 	FROM tbinsurance 
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
		$form_title = 'تعديل شركة تامين ' .$row['Name'].'';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("insurance-list"); </script>';
		die();
	}
	
		 function GetlastID($connect, $id) {
		$sql = "SELECT user_insurance FROM tblusers WHERE user_insurance = :id LIMIT 1";
		$stmt = $connect->prepare($sql);
		if (strpos($id, ',') === false) {
			$id_param = "$id";
		} else {
			$id_param = "%,$id,%";
		}
		$stmt->bindValue(':id', $id_param, PDO::PARAM_STR);
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
					<h4 class="card-title">تفاضيل شركة التامين</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label required"  for="Insurancename">اسم شركة التأمين</label>
							<input type="text"  value="<?=!empty($row['Name']) ? $row['Name'] : ''?>" class="form-control"  data-toggle="tooltip"  id="Insurancename" name="Insurancename"  autocomplete="off" required>
						 </div>

                         <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="Cname">اسم ممثل الشركة</label>
							<input type="text"  value="<?=!empty($row['NameOfRepresentative']) ? $row['NameOfRepresentative'] : ''?>" class="form-control"  data-toggle="tooltip"  id="Cname" name="Cname"  autocomplete="off">
						 </div>
                        
                      </div>
                      
					 
					 <div class="row">
                             <div class="form-group col-12 col-md-6 col-lg-4">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list[]"<?php echo !isset($_GET['id']) ? "multiple='multiple'" : ''; ?> required >
                                     <?php
                              if (isset($_GET['id']) && GetlastID($connect_pdo,$client_no)) {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">شركة التامين تم ربطها بمستخدمين ولايمكن تغير الفرع</label>
                                 
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
                              <div class="form-group col-12 col-md-6 col-lg-4">
                                <label class="col-form-label required" for="company_type">نوع شركة التامين</label>
                                  <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="نوع التامين" id="company_type" name="company_type" required>
                                  <option value="1" <?=!empty($row['type']) && $row['type']=='1' ? 'selected' : ''?>>تأمين على الحياة</option>
                                  <option value="2" <?=!empty($row['type']) && $row['type']=='2' ? 'selected' : ''?>>تأمين صحي</option>
                                  <option value="3" <?=!empty($row['type']) && $row['type']=='3' ? 'selected' : ''?>>تأمين على السيارات</option>
                                  <option value="4" <?=!empty($row['type']) && $row['type']=='4' ? 'selected' : ''?>>تأمين ضد الحوادث</option>
                                  <option value="5" <?=!empty($row['type']) && $row['type']=='5' ? 'selected' : ''?>>تأمين الممتلكات</option>
                                  
                                  </select>
                              </div>
							
						</div>

                        <div class="row">
                        <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="Cphone">رقم الاتصال </label>
							<input type="text"  value="<?=!empty($row['Phone']) ? $row['Phone'] : ''?>" class="form-control"  data-toggle="tooltip"  id="Cphone" name="Cphone"  autocomplete="off" >
						 </div>
                         <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="CEmail">البريد الالكتروني</label>
							<input type="email"  value="<?=!empty($row['Email']) ? $row['Email'] : ''?>" class="form-control"  data-toggle="tooltip"  id="CEmail" name="CEmail"  autocomplete="off" >
						 </div>
                       </div>

                       <div class="row">
                       <div class="form-group col-12 col-md-6 col-lg-4">
							<label class="col-form-label "  for="CAddress">عنوان الشركة</label>
							<input type="text"  value="<?=!empty($row['Address']) ? $row['Address'] : ''?>" class="form-control"  data-toggle="tooltip"  id="CAddress" name="CAddress"  autocomplete="off" >
						 </div>
                         <div class="form-group col-12 col-md-6 col-lg-4">
                           <label class="col-form-label required" for="Cstate">حالة الشركة</label>
                             <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="حالة شركة التامين" id="Cstate" name="Cstate" required>
                             <option value="1" <?=!empty($row['state']) && $row['state']=='1' ? 'selected' : ''?>>نشطة</option>
                             <option value="2" <?=!empty($row['state']) && $row['state']=='2' ? 'selected' : ''?>>غير نشطة</option>
                             </select>
                         </div>
                       
                   </div>

                   <div class="row">
                    <div class="form-group col-12 col-lg-8">
                         <label class="col-form-label "  for="Note">ملاحظات</label><br>
                         <textarea name="note" id="note" class="form-control" rows="5" style="resize: vertical;"><?=!empty($row['Note']) ? $row['Note'] : ''?></textarea>
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

$(document).on('click', '#save-shift', function(){
	$('#AddShift').trigger('submit');
});




$('#AddShift').on('submit', function(e){  
    e.preventDefault();
	var form_data = $(this).serialize() + '&id=' + param_id;
	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=insurance-add",
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
                   window.location.href = 'insurance-view?id='+data.id+'';
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
    //  لادخال الارقام فقط
    $('#Cphone').on('keypress', function(e) {
        var charCode = e.which || e.keyCode;
        if ((charCode >= 48 && charCode <= 57) || charCode === 43) {
          return true; 
        } else {
          return false;
        }
    });


});
 

</script>
