   
<?php

$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);

// 
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

 
$form_title = 'إضافة سلفة جديده';
$save_btn = 'حفظ ورفع';
$save_btn_2 = 'حفظ كمسودة';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT a.Id,a.UserID,a.Amount,a.currency,a.DueDate,a.Status,a.type,a.description,
    f.FirstName as f_name, f.LastName as l_name
 	FROM  tblempadvances a
    LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("EmpAdvances-list-add"); </script>';
            die(); 
        } 

		$client_id = $row['Id'];
    if(isset($_GET['copy'])){
      $save_btn = 'حفظ ورفع';
      $save_btn_2 = 'حفظ كمسودة';
     }
      else{
      $save_btn = 'حفظ التغييرات';
      $form_title = 'تعديل السلفه  ';
      $save_btn_2 = null;
      }
	}
	else{
		echo'<script> location.replace("EmpAdvances-list-add"); </script>';
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
	<form class="form-horizontal" role="form" action="" method="post" id="AddIncentive">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">تفاضيل السلفة</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 

                        
          <input type="hidden" name="isdraft" id="isdraft"/>
                        <div class="row">
                            <!--  -->
                        
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
                            <div class="form-group col-md-6">
                                <label class="col-form-label required" for="amount">المبلغ</label>
                                <input type="text" name="amount" class="form-control"  placeholder="المبلغ" id="amount" autocomplete="off" value="<?=(!empty($row['Amount'])? $row['Amount'] : '' )?>" required>
                            </div>
                            <div class="form-group col-md-6">
                               <label class="col-form-label required" for="currency">العملة</label>
                               <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل العملة" id="currency" name="currency" required >
                               <?php if(!empty($row['Currency'])){ ?>
                                  <option value="<?= $row['Currency'] ?>" selected><?= $row['Currency'] ?></option>
                                  <option value="<?= $User->currency; ?>">عملة النظام</option>
                                <?php } 
                                else {
                                  ?>
                                <option value="<?= $User->currency; ?>" selected>عملة النظام</option>
                                  <?php
                                } ?>
                                   </select>
                             </div>

                       </div>

                       <div class="row">
                        <div class="form-group col-md-6">
                            <label class="col-form-label required" for="Due_date">تاريخ الاستحقاق</label>
                            <input type="text" name="Due_date" class="form-control input-date"  placeholder="تاريخ الاستحقاق" id="Due_date" autocomplete="off" value="<?=(!empty($row['DueDate'])? $row['DueDate'] : '' )?>" required>
                            </div>
                        <div class="form-group col-md-6">
                           <label class="col-form-label required" for="type_advances">نوع السلفة</label>
                           <select class=" selectpicker "  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="أدخل العملة" id="type_advances" name="type_advances" required >
                           <?php if(!empty($row['type'])){ ?>
                              <option value="<?= $row['type'] ?>" selected><?= ($row['type']==1 )?'سلفة على الراتب':'سلفة خارج الراتب' ?></option>
                            <?php } ?>
                            <option value="1" >سلفة على الراتب</option>
                            <!-- <option value="2" >سلفة خارج الراتب</option> -->
                               </select>
                         </div>

                   </div>
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">السبب</label><br>
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
    $('#AddIncentive').trigger('submit');
    
});

$(document).on('click', '#save-draft', function() {
  $('#isdraft').val(null);
    $('#AddIncentive').trigger('submit');
});
 



$('#AddIncentive').on('submit', function(e){  
    e.preventDefault();
  form_data = $(this).serialize() + '&id='+ param_id ;

	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=EmpAdvances-add-add",
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
                   window.location.href = 'EmpAdvances-list-add';
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



	$('#AddIncentive').validate({
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



$('#emp_list').change(function() {
    var selectedValue = $(this).val(); 

    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: 'hr-app/index.php?action=emp-info-advances',
            type: 'POST',
            data: { value: selectedValue },
            dataType: "json",
            beforeSend: function() {
                $('#preloading').show();
            },
            success: function(response) { 
                $('#preloading').hide();

                if (response.section && response.section.length > 0) {
                    let html = `
                        <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الموظف</th>
                                    <th>الملبغ</th>
                                    <th>حالة السلفة</th>
                                    <th>نوع السلفة</th>
                                    <th>مسودة او تم الرفع</th>
                                    <th> تاريخ الاستحقاق</th>
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
                                <td>${emp.amount ?? '-'}</td>
                                <td>${emp.statedevice ?? '-'}</td>
                                <td>${emp.type ?? '-'}</td>
                                <td>${emp.draft ?? '-'}</td>
                                <td>${emp.DueDate ?? '-'}</td>
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

});
 

</script>
