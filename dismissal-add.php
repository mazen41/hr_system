   
<?php
$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة استقالة الموظفين';
// }else{
// $page_title = 'إضافة';
// }
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);
// 
$query = "SELECT UserID ,CONCAT(FirstName, ' ', LastName) as emp_name
FROM  tblusers 
WHERE  isemp is not null and BranchID in ($allowed_branch)  ";

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
 
$form_title = 'إضافة فصل جديده';
$save_btn = 'حفظ';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT a.Id ,a.UserID,a.DueDate,a.Reason,
     f.FirstName as f_name, f.LastName as l_name
 	FROM   tblresignation a 
    LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
	WHERE  Id  = :id ";


	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("dismissal-list"); </script>';
            die(); 
        }

		$client_id = $row['Id'];
		$form_title = 'تعديل فصل الموظف ';
        $save_btn = 'حفظ التغييرات';
	}
	else{
		echo'<script> location.replace("dismissal-list"); </script>';
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
					<h4 class="card-title">تفاضيل الفصل</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 

                        


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
                        <div class="form-group col-md-12">
                            <label class="col-form-label required" for="Due_date">تاريخ الفصل</label>
                            <input type="text" name="Due_date" class="form-control input-date"  placeholder="تاريخ الفصل" id="Due_date" autocomplete="off" value="<?=(!empty($row['DueDate'])? $row['DueDate'] : '' )?>" required>
                            <input type="hidden" name="type" id="type" value="2">
                        </div>
                   </div>
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label "  for="Reson">السبب</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;"><?=!empty($row['Reason']) ? $row['Reason'] : ''?></textarea>
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
 <script src="plugins/select2_n/dist/js/select2.full.js"></script>


<script>
//, lng: 



//initMap(15.3387008,44.204032);
$(document).ready(function(){


const urlParams = new URLSearchParams(window.location.search);
const param_id = urlParams.get('id');

$(document).on('click', '#save-incentive', function(){
	$('#AddIncentive').trigger('submit');
});




$('#AddIncentive').on('submit', function(e){  
    e.preventDefault();
  form_data = $(this).serialize() + '&id='+ param_id ;

	if($(this).valid()){
	$.ajax({
        url:"./hr-app/resignation-add-add",
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
                   window.location.href = 'dismissal-list';
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


$('#emp_list').change(function() {
    var selectedValue = $(this).val(); 

    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: '/hr-app/emp-info-resignation',
            type: 'POST',
            data: { value: selectedValue,type:2 },
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
                                    
                                    <th>حالة الفصل</th>
                                    <th>مسودة او تم الرفع</th>
                                    <th>تاريخ الفصل </th>
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
                                
                                <td>${emp.statedevice ?? '-'}</td>
                                <td>${emp.draft ?? '-'}</td>
                                <td>${emp.date ?? '-'}</td>
                                
                                
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
// 

});
 

</script>