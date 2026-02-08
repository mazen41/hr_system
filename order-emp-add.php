  
<?php

// $screen
// if(isset($_GET['id'])){
// 	$page_title = 'إدارة سلف الموظفين';
// }else{
// $page_title = 'إضافة سلفة';
// }
//  = ' سلف الموظفين';
$screen = 'إدارة الموارد البشرية';
$page_title = 'إدارة الطلبات ';
 
include_once('inc/header.php');
$allowed_branches = $User->allBranches($User->branches);

 
$form_title = 'إضافة طلب جديده';
$save_btn = 'حفظ واعتماد';
$save_btn_2 = 'حفظ كمسودة';

 
if(isset($_GET['id'])){
	$client_no = (int)$_GET['id'];
    
	$query = "SELECT Id,UserID,Status,description,title,isread
 	FROM  emp_order 
	WHERE  Id  = :id ";

	$st = $connect_pdo->prepare($query);
	$st->execute(array(':id'  => $client_no));
	
	if($st->rowCount() > 0){
		$row = $st->fetch();
        if(!empty($row["Status"]))
        {
            echo'<script> location.replace("order-emp-list"); </script>';
            die(); 
        } 

		$client_id = $row['Id'];
    if(isset($_GET['copy'])){
      $save_btn = 'حفظ واعتماد';
      $save_btn_2 = 'حفظ كمسودة';
     }
      else{
      $save_btn = 'حفظ التغييرات';
      $form_title = 'تعديل الطلب  ';
      $save_btn_2 = null;
      }
	}
	else{
		echo'<script> location.replace("order-emp-list"); </script>';
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
			<button type="button" class="btn btn-success"  id="save-order"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
		
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
	<form class="form-horizontal" role="form" action="" method="post" id="order">	
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
                      
					 

                        
          <input type="hidden" name="isdraft" id="isdraft"/>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="col-form-label required" for="title">الموضوع</label>
                                <input type="text" name="title" class="form-control"  placeholder="الموضوع" id="title" autocomplete="off" value="<?=(!empty($row['title'])? $row['title'] : '' )?>" required>
                         </div>
                           </div> 

                       
                



                   <div class="row">
                    <div class="form-group col-md-12">
                         <label class="col-form-label required"  for="Reson">الوصف</label><br>
                         <textarea name="Reson" id="Reson" rows="10" style="resize: none; width: -webkit-fill-available;" required><?=!empty($row['description']) ? $row['description'] : ''?></textarea>
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


$(document).on('click', '#save-order', function() {
  $('#isdraft').val(1);
    $('#order').trigger('submit');
    
});

$(document).on('click', '#save-draft', function() {
  $('#isdraft').val(null);
    $('#order').trigger('submit');
});
 



$('#order').on('submit', function(e){  
    e.preventDefault();
  form_data = $(this).serialize() + '&id='+ param_id ;

	if($(this).valid()){
	$.ajax({
        url:"./hr-app/order-emp-add",
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
                   window.location.href = 'order-emp-list';
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



	$('#order').validate({
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