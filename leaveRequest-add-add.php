   
<?php

$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);

$query = "SELECT u.UserID ,CONCAT(u.FirstName, ' ', u.LastName) as emp_name
FROM  tblusers  u
LEFT JOIN tblremewal t ON t.Id = u.lastversion 
LEFT JOIN (
    SELECT r1.*
    FROM tblresignation r1
    WHERE r1.Status IS NOT NULL
      AND r1.DueDate IS NOT NULL
      AND NOT EXISTS (
          SELECT 1 FROM tblresignation r2
          WHERE r2.UserID = r1.UserID
            AND r2.Status IS NOT NULL
            AND r2.DueDate IS NOT NULL
            AND r2.DueDate < r1.DueDate 
      )
) r ON r.UserID = u.UserID

WHERE  u.isemp is not null and u.BranchID in ($allowed_branch) 
and '$today_date'  BETWEEN t.new_s_date AND t.new_e_date 
and t.state is not null 
AND (r.DueDate IS NULL OR '$today_date' < r.DueDate)";

$st = $connect_pdo->prepare($query);
$st->execute(
   array());

if($st->rowCount() > 0){
   $results = $st->fetchAll(PDO::FETCH_ASSOC);

   foreach($results as $rows){
       $emp[] = array(
           'id' => $rows['UserID'],
           'name' => $rows['emp_name']
       );
}

}
//  
$form_title = 'إضافة طلب جديد';
$save_btn = 'حفظ ورفع';
$save_btn_2 = 'حفظ كمسودة';





if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT a.Id ,a.UserID,a.leavetype,a.path,a.leave_start_date,a.leave_end_date,a.status,a.description,
     f.FirstName as f_name, f.LastName as l_name
 	FROM   tblleaverequest a
    LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("leaveRequest-list-add"); </script>';
            die(); 
        }

		$client_id = $row['Id'];
		

        if(isset($_GET['copy'])){
          $save_btn = 'حفظ ورفع';
          $save_btn_2 = 'حفظ كمسودة';
         }
          else{
          $save_btn = 'حفظ التغييرات';
          $form_title = 'تعديل طلب الاجازة ';
          $save_btn_2 = null;
          }
        //   
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
function rollsList($connect, $user){
  $info = Getinfo($connect, $user);

  $branchID = null;
  $groupID = null;

  if ($info) {
      $BranchID = $info['BranchID'] ?? null;
      $SectionID = $info['SectionID'] ?? null;
      $groupID = $info['GroupID'] ?? null;
      $GradeID = $info['GradeID'] ?? null;
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
      AND (BranchID = :branchID OR :branchID IS NULL)
  ";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userID', $user, PDO::PARAM_INT);
  $stmt->bindParam(':SectionID', $SectionID, PDO::PARAM_INT);
  $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
  $stmt->bindParam(':GradeID', $GradeID, PDO::PARAM_INT);
  $stmt->bindParam(':jobtitleID', $jobtitleID, PDO::PARAM_INT);
  $stmt->bindParam(':branchID', $branchID, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// 
$roles = rollsList($connect_pdo,$row['UserID']);

        // 
	}
	else{
		echo'<script> location.replace("leaveRequest-list-add"); </script>';
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

                                <div class="form-group col-md-12">
                                <label class="col-form-label required" for="emp_list">اسم الموظف</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اسم الموظف" id="emp_list" name="emp_list"  required >
                                    <?php
								if(!empty($row['UserID'])){
                                    
                                    echo '<option  selected value="' . $row['UserID'] . '">' . $row['f_name'] .' '. $row['l_name'].'</option> ';
                                }
                                else{
                                foreach($emp as $roww) {
                                            echo '<option value="' . $roww['id'] . '">' . $roww['name'] . '</option>';
                                        }
                                    }
                                    ?>
                                    </select>
                              </div>
                                    
                            <!--  -->
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

                             <!--  -->
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
                            <input type="date" name="date_leave_start" class="form-control input-date"  placeholder="تاريخ بدابة الاجازة" id="date_leave_start" autocomplete="off" value="<?=(!empty($row['leave_start_date'])? $row['leave_start_date'] : '' )?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="col-form-label required" for="date_leave_end">تاريخ انتهاء الاجازة</label>
                            <input type="date" name="date_leave_end" class="form-control input-date"  placeholder="تاريخ انتهاء الاجازة" id="date_leave_end" autocomplete="off" value="<?=(!empty($row['leave_end_date'])? $row['leave_end_date'] : '' )?>" required>
                        </div>


                   </div>
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">وصف الاجازة</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['description']) ? $row['description'] : ''?></textarea>
                      </div>
                </div>

                <div id="detials"></div>
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

	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=leaveRequest-add-add",
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
                   window.location.href = 'leaveRequest-list-add';
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



    $('#emp_list').change(function() {
    var selectedValue = $(this).val(); 

    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: 'hr-app/index.php?action=emp-info-leaves',
            type: 'POST',
            data: { value: selectedValue },
            dataType: "json",
            beforeSend: function() {
                $('#preloading').show();
            },
            success: function(response) { 
                $('#preloading').hide();

                populateSelect('#leavetype', response.roles);
                if (response.section && response.section.length > 0) {
                    let html = `
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>عدد الايام</th>
                                    <th>حالة الاجازة</th>
                                    <th>مسودة او تم الرفع</th>
                                    <th> تاريخ بدء الاجازة</th>
                                    <th> تاريخ انتهاء الاجازة</th>
                                    <th>انشئ بواسطة</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    response.section.forEach(function(emp, index) {
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${emp.name ?? '-'}</td>
                                <td>${emp.day_leave ?? '-'}</td>
                                <td>${emp.statedevice ?? '-'}</td>
                                <td>${emp.draft ?? '-'}</td>
                                <td>${emp.startDate ?? '-'}</td>
                                <td>${emp.endDate ?? '-'}</td>
                                <td>${emp.name_add ?? '-'}</td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                        </div>
                    `;

                    $('#detials').html(html);
                } else {
                    $('#detials').html('<div class="alert alert-info">لا توجد بيانات متاحة لهذا الموظف.</div>');
                }
            },
            error: function() {
                $('#preloading').hide();
                toastr.error('حدث خطأ أثناء جلب البيانات');
            }
        });
    } else {
        $('#detials').html('');
    }
});


	function populateSelect(selectId, items) {
    var select = $(selectId);
    select.empty(); 
    if (items && items.length > 0) {		
        $.each(items, function(index, item) {
            select.append('<option data-info="' + item.RequiresAttachment + '" value="' + parseInt(item.Id) + '" isaccept="' + item.isaccept + '" >' + item.Name + '</option>');
            
        });
    } 
	select.selectpicker('refresh'); 
    }

}); 

</script>
