  
<?php

// $screen
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة سلف الموظفين';
// }else{
// $page_title = 'إضافة سلفة';
// }
//  = ' سلف الموظفين';
$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة السلف ';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 
$form_title = 'إضافة سلفة جديده';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT Id,UserID,Amount,currency,DueDate,Status,type,description
 	FROM  tblempadvances 
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("EmpAdvances-list"); </script>';
            die(); 
        } 

		$client_id = $row['Id'];
    if(isset($_GET['copy'])){
      $save_btn = 'حفظ واعتماد';
      $save_btn_2 = 'حفظ كمسودة';
     }
      else{
      $save_btn = 'حفظ التغييرات';
      $form_title = 'تعديل السلفه  ';
      $save_btn_2 = null;
      }
	}
	else{
		echo'<script> location.replace("EmpAdvances-list"); </script>';
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
                                <!-- <option value="SAR" >الريال السعودي</option>
                                <option value="AED" >الدرهم الاماراتي</option>
                                <option value="USDT" >الدولار الامريكي</option>
                                <option value="QAR" >الريال القطري</option>
                                <option value="KWT" >الريال الكويتي</option>
                                <option value="BHD" >الريال البحريني</option> -->
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
        url:"./hr-app/EmpAdvances-add",
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
                   window.location.href = 'EmpAdvances-list';
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



});
 

</script>