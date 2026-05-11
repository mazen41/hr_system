   
<?php
// $screen = ' استقالة الموظفين';
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة استقالة الموظفين';
// }else{
// $page_title = 'إضافة';
// }
$screen = 'إدارة الموارد البشرية';
$page_title = 'اعدادات الموظفين';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);
$today_date=date('Y-m-d');
// 
$query = "SELECT UserID, CONCAT(FirstName, ' ', LastName) AS emp_name
FROM tblusers
WHERE isemp IS NOT NULL 
  AND UserID IN (
      SELECT DISTINCT manager 
      FROM tblusers 
      WHERE manager IS NOT NULL
  )
  AND BranchID IN ($allowed_branch)";


$query_new = "SELECT u.UserID ,CONCAT(u.FirstName, ' ', u.LastName) as emp_name
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
$st->execute();

$st_new = $connect_pdo->prepare($query_new);
$st_new->execute();

if($st->rowCount() > 0){
   $results = $st->fetchAll(PDO::FETCH_ASSOC);

   foreach($results as $rows){
       $old_manager[] = array(
           'id' => $rows['UserID'],
           'name' => $rows['emp_name']
       );
}

}
if($st_new->rowCount() > 0){
   $results_new = $st_new->fetchAll(PDO::FETCH_ASSOC);

   foreach($results_new as $rows_new){
       $new_manager[] = array(
           'id' => $rows_new['UserID'],
           'name' => $rows_new['emp_name']
       );
}

}
// 
 
$form_title = 'تغير المدير';
$save_btn = 'تغير';

 

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
					<h4 class="card-title">تفاضيل تغير المدير</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

					<div class="card-body p-3">
                      
					 

                        


                       <div class="row">
                         <!--  -->
                        
                                <div class="form-group col-md-6">
                                <label class="col-form-label required" for="old_manager">اسم المدير</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اسم الموظف" id="old_manager" name="old_manager"  required >
                                    <?php
                               if(!empty($old_manager))
                                foreach($old_manager as $roww) {
                                            echo '<option value="' . $roww['id'] . '">' . $roww['name'] . '</option>';
                                        }
                                    
                                    ?>
                                    </select>
                              </div>

                              <!-- المدير الجديد -->
                               <div class="form-group col-md-6">
                                <label class="col-form-label required" for="new_manager">اسم المدير الجديد</label>
                                <select class=" selectpicker select_branch"  data-live-search="true"  data-container="body" data-size="5" data-width="100%" title="اسم الموظف" id="new_manager" name="new_manager"  required >
                                    <?php
                               if(!empty($new_manager))
                                foreach($new_manager as $row) {
                                            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                        }
                                    
                                    ?>
                                    </select>
                              </div>
                                        
                              <!--  --> 
            

                
					</div>
                    <div id="detials"></div>
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
$(document).on('click', '#save-incentive', function(){
	$('#AddIncentive').trigger('submit');
});




$('#AddIncentive').on('submit', function(e){  
    e.preventDefault();
  form_data = $(this).serialize();

	if($(this).valid()){
	$.ajax({
        url:"hr-app/index.php?action=change-manager-emp",
        method:"POST", 
		data:form_data,
		dataType:"json", 
		beforeSend:function(){
			$('#preloading').show();
		}, 
		success:function(data){
			if(data.result){
                
				toastr.success(data.msg);
				 
                   window.location.href = 'change-manager-emp';
				

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


$('#old_manager').change(function() {
    var selectedValue = $(this).val(); 

    if (selectedValue && selectedValue.length > 0) {
        $.ajax({
            url: 'hr-app/index.php?action=emp-related-manager',
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
                                    
                                    <th>القسم</th>
                                    <th>الراتب</th>
                                    <th>الفرع</th>
                                    <th>الايميل</th>
                                    <th>الحاله</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    response.section.forEach(function(emp, index) {
                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${emp.person_name ?? '-'}</td>
                                
                                <td>${emp.sectionname ?? '-'}</td>
                                <td>${emp.salary ?? '-'}</td>
                                <td>${emp.branchname ?? '-'}</td>
                                
                                
                                <td>${emp.email ?? '-'}</td>
                                <td>${emp.state ?? '-'}</td>
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
                    $('#detials').html('<div class="alert alert-info">ليس مرتبط باي موظف</div>');
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
