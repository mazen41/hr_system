
<?php
$appid  = 'HR';
$page_perm=['اضافة قسم'];
// $screen = 'الاقسام';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة الاقاسم';
// }else{
// $page_title = 'إضافة قسم';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = ' الاعدادات';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);
$querySection = "SELECT *
FROM  tblsection where BranchID in ($allowed_branch) ";
$stSection = $connect_pdo->prepare($querySection);
$stSection->execute();
if($stSection->rowCount() > 0){
    $allowed_section = $stSection->fetchAll();
    if (!empty($allowed_section))
{                                 
    foreach($allowed_section as $row_){
        $section [$row_['Id']]= $row_['Name'];
    }
}
}


$form_title = 'إضافة قسم جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT Id ,Name,ParentID,BranchID
 	FROM tblsection 
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
		$form_title = 'تعديل قسم ' .$row['Name'].'';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("section-list"); </script>';
		die();
	}
	
	function GetlastID($connect, $id) {
		$sql = "SELECT SectionID FROM tblremewal WHERE SectionID = :id LIMIT 1";
		$stmt = $connect->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->rowCount() > 0) {
			return true;
		}
		return false;
	}

		function CheckSectionChild($connect, $id) {
		$sql = "SELECT ParentID FROM tblsection
		WHERE ParentID = :id LIMIT 1";
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
					<h4 class="card-title">تفاضيل القسم</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
 				</div>
                

					<div class="card-body p-3">
					  <div class="row">
                          
                          <div class="form-group col-md-12">
							<label class="col-form-label required"  for="sectiontname">اسم القسم</label>
							<input type="text"  value="<?=!empty($row['Name']) ? $row['Name'] : ''?>" class="form-control"  data-toggle="tooltip"  id="sectiontname" name="sectiontname"  autocomplete="off" required>
						 </div>
                        
                      </div>
                      
					 
					 <div class="row">
                             <div class="form-group col-md-6">
                                <label class="col-form-label required" for="branchs_list">الفرع</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل الفروع" id="branchs_list" name="branchs_list" required >
                                     <?php
                              if (isset($_GET['id']) && GetlastID($connect_pdo,$client_no)) {
                                  if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">هذا القسم تم ربطه بموظفين لايمكن تغير الفرع</label>
                                 
                                 <?php
                              } 
							  else if(isset($_GET['id']) && CheckSectionChild($connect_pdo,$client_no))
							  {
							if (!empty($row['BranchID'])) {
                                      echo "<option value='{$row['BranchID']}' selected>{$allowed_branches[$row['BranchID']]}</option>";
                                  }
                                  ?>
                                  </select>
                          <label class="col-form-label required" for="branchs_list">هذا القسم تم ربطه باقسام اخرى لايمكن تغير الفرع</label>
                                 
                                 <?php
							  }
							  else {
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
                                <label class="col-form-label" for="select_section">الاب</label>
                                <select class=" selectpicker select_section"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اضف الاب" id="select_section" name="select_section" >
                                    <?php
									if(!empty($row['ParentID'])) {
										echo "<option value='{$row['ParentID']}' selected>{$section[$row['ParentID']]}</option>";
									}
                                    // else
                                    // echo '<option value="Null" selected > الاب</option>';
                                    // // if($row['ParentID']!=Null)
                                    //     foreach($section as $id => $name){	
                                    //         echo'<option value="'.$id.'" >'.$name.'</option>';
                                    // }

                                    ?>
                                    </select>
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
	var form_data = $(this).serialize() + '&id=' + param_id;
	if($(this).valid()){
	$.ajax({
        url:"./hr-app/section-add",
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
                   window.location.href = 'section-view?id='+data.id+'';
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



	$('#branchs_list').change(function() {
        var selectedValue =$(this).val(); 
        if (selectedValue) {
            $.ajax({
                url: '/hr-app/info-of-section-and-job-title',
                type: 'POST',
                data: { value: selectedValue },
				dataType:"json",
				beforeSend:function(){
					$('#preloading').show();
					}, 
                success: function(response) { 
                    // user_manager
					populateSelect('#select_section', response.section);
					$('#preloading').hide();
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء جلب البيانات');
                }
            });
        } 
    });
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

});
 

</script>