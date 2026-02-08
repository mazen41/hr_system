
<?php
$appid  = 'HR';
$page_perm=['اضافة بصمة'];
$screen = 'الاجهزة';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة اجهزة البصمة';
// }else{
// $page_title = 'إضافة جهاز';
// }

$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';

include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);



$form_title = 'إضافة اجهزة جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT FingerprintID ,BranchID,FingerprintName,FingerprintType,FingerprintState,FingerprintSerailnumber,ip,port
 	FROM tbfingerprint 
	WHERE  FingerprintID  = :id 
	LIMIT 1 ";

	$st = $connect_pdo->prepare($query);
	$st->execute(
		array(
			':id'  => $client_no
		)
	);
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
		$client_id = $row['FingerprintID'];
		$form_title = 'تعديل جهاز ' .$row['FingerprintName'].'';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("fingerprint-list"); </script>';
		die();
	}
	
	
	    function GetlastID($connect, $id) {
        $sql = "SELECT fingerID FROM tblremewal WHERE FIND_IN_SET(:id, fingerID) LIMIT 1";
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
					<h4 class="card-title">تفاضيل الجهاز</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-md-6">
							<label class="col-form-label required"  for="devicetname">اسم الجهاز</label>
							<input type="text"  value="<?=!empty($row['FingerprintName']) ? $row['FingerprintName'] : ''?>" class="form-control"  data-toggle="tooltip"  id="devicetname" name="devicetname"  autocomplete="off" required>
						 </div>
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
                          <label class="col-form-label required" for="branchs_list">هذا الجهاز تم ربطه بموظفين لايمكن تغير الفرع</label>
                                 
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
                             
                              <div class="form-group col-md-6">
                                <label class="col-form-label required" for="decvicestate">حالة الجهاز</label>
                                  <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="حالة الجهاز" id="decvicestate" name="decvicestate" required>
                                  <option value="1" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='1' ? 'selected' : ''?>>شغال</option>
                                  <option value="2" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='2' ? 'selected' : ''?>>موقف</option>
                                  <option value="3" <?=!empty($row['FingerprintState']) && $row['FingerprintState']=='3' ? 'selected' : ''?>>جاري الصيانة</option>
                                  </select>
                              </div>
							  <div class="form-group col-md-6">
							<label class="col-form-label "  for="devicetype">نوع الجهاز(اسم الشركة)</label>
							<input type="text"  value="<?=!empty($row['FingerprintType']) ? $row['FingerprintType'] : ''?>" class="form-control"  data-toggle="tooltip"  id="devicetype" name="devicetype"  autocomplete="off" >
						 </div>
						</div>
                        <div class="row">
                        
                         <div class="form-group col-md-4">
							<label class="col-form-label "  for="deviceserialnumber">الرقم التسلسلي حق الجهاز</label>
							<input type="text"  value="<?=!empty($row['FingerprintSerailnumber']) ? $row['FingerprintSerailnumber'] : ''?>" class="form-control"  data-toggle="tooltip"  id="deviceserialnumber" name="deviceserialnumber"  autocomplete="off" >
						 </div>
						 <div class="form-group col-md-4">
							<label class="col-form-label "  for="ip">(IP) الجهاز</label>
							<input type="text"  value="<?=!empty($row['ip']) ? $row['ip'] : ''?>" class="form-control"  data-toggle="tooltip"  id="ip" name="ip"  autocomplete="off" >
						 </div>
						 <div class="form-group col-md-4">
							<label class="col-form-label "  for="port">(port) البوابة</label>
							<input type="text"  value="<?=!empty($row['port']) ? $row['port'] : ''?>" class="form-control"  data-toggle="tooltip"  id="port" name="port"  autocomplete="off" >
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
        url:"./hr-app/fingerprint-add",
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
                   window.location.href = 'fingerprint-view?id='+data.id+'';
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

	$('#ip').on('input', function() {
    let value = $(this).val();
    
    // إزالة أي أحرف غير رقمية أو نقاط
    value = value.replace(/[^0-9.]/g, '');
    
    // تقسيم العنوان إلى أجزاء
    let parts = value.split('.');
    
    // تقييد كل جزء بـ 3 خانات كحد أقصى وتعديل القيم > 255
    parts = parts.map(part => {
        if (part.length > 3) part = part.substring(0, 3); // لا يزيد عن 3 خانات
        if (parseInt(part) > 255) part = "255"; // لا يتجاوز 255
        return part;
    });
    
    // منع أكثر من 4 أجزاء (مثل 192.168.1.1.1)
    if (parts.length > 4) parts = parts.slice(0, 4);
    
    // إعادة تجميع الأجزاء
    $(this).val(parts.join('.'));
});
$('#port').on('input', function() {
    let value = $(this).val();
    value = value.replace(/[^0-9]/g, '');
    $(this).val(value);
});
// 

});
 

</script>