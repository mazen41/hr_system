
<?php
// $screen = 'تسجيل البصمة';
// include_once('inc/header.php');
// $form_title = 'ادارة الموظف';
// $save_btn = 'حفظ';
    
// 	$query = "SELECT confrom FROM tblusers 
// 	WHERE  	UserID   = :id 
// 	LIMIT 1 ";

// 	$st = $connect_pdo->prepare($query);
// 	$st->execute(
// 		array(
// 			':id'  => $user
// 		)
// 	);
	
// 	if($st->rowCount() > 0){
//         $row = $st->fetch();
//         if(!empty($row ["confrom"])){
//         echo'<script> location.replace("Hrdashboard"); </script>';
// 		die();
//         }
//         else
//         {
?>

<!-- <style>
</style>
	
    <div class="content-header page-nav" >
      <div class="container-fluid ">
        <div class="row ">
          <div class="col-7">
            <span class="page-title"><?=$form_title?></span> -->
          <!-- </div>/.col -->
          <!-- <div class="col-5 text-left">	
			<button type="button" class="btn btn-success"  id="save-empsetting"><i class="fas fa-save"></i><span class="d-none d-sm-inline"> <?=$save_btn?></span></button>
		 </div>
        </div>
      </div>
    </div>
    -->
	
	
		
	

<!-- 
    <section class="content">
		<div class="container-fluid">
	<form class="form-horizontal" role="form" action="" method="post" id="Addsetting">	
    <input type="hidden" value=""  class="" id="shift_id" name="shift_id">			
    <div class="row">
			<div class="col-md-12">
				<div class="invoice card mb-3 shadow-none">
                <div class="card-header"    style="background: aliceblue; cursor: pointer">
					<h4 class="card-title">اعدادات الدخول</h4>
					<span style="float: left;"><div class="card-tools">
						  <button type="button" class="btn btn-tool" data-card-widget="collapse"  style="margin: 0;    padding: 2px 5;">
							<i class="fas fa-minus"></i>
						  </button>
						</div></span>
				</div>
                

                <div class="card-body p-3">
                    <div class="row">
                      <div class="form-group col-md-12">
                        <label class="col-form-label required" for="Password">كلمة المرور</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="Password" name="Password" autocomplete="off" required>
                          <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                              <i class="fas fa-eye"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-12">
                        <label class="col-form-label required" for="ConfPassword">تأكيد كلمة المرور</label>
                        <div class="input-group">
                          <input type="password" class="form-control" id="ConfPassword" name="ConfPassword" autocomplete="off" required>
                          <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                              <i class="fas fa-eye"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
				</div>
			</div>
			
			
        
            
			
		</div>
		</form>
      </div>
	  
    </section>
	

 -->



<?php
	// }	
// }	
//  include_once('inc/footer.php');

?>
 <!-- <script src="plugins/select2_n/dist/js/select2.full.js"></script> -->


<script>
// 
// $(document).ready(function(){



// // end of fingerprint
// $(document).on('click', '#save-empsetting', function(){
// 	$('#Addsetting').trigger('submit');
// });




// $('#Addsetting').on('submit', function(e){  
//     e.preventDefault();
// 	var form_data = $(this).serialize() ;
// 	if($(this).valid()){
// 	$.ajax({
//         url:"./hr-app/empvalidate",
//         method:"POST", 
// 		data:form_data,
// 		dataType:"json", 
// 		beforeSend:function(){
// 			$('#preloading').show();
// 		}, 
// 		success:function(data){
// 			if(data.result){
// 				toastr.success(data.msg);
//                 window.location.href = 'Hrdashboard';

// 			}
//             else
//             {
// 				toastr.error(data.msg);
// 			}
			
			
			
			

// 			$('#preloading').hide();
					
					
			
// 		}
// 	});
	
// 	}
	
// });



	// $('#Addsetting').validate({
	// 	errorElement: 'span',
	// 	errorPlacement: function (error, element) {
	// 	  error.addClass('invalid-feedback');
	// 	  element.closest('div').append(error);
	// 	},
	// 	highlight: function (element, errorClass, validClass) {
	// 	  $(element).addClass('is-invalid');
	// 	},
	// 	unhighlight: function (element, errorClass, validClass) {
	// 	  $(element).removeClass('is-invalid');
	// 	}
	// });

// 




// $('.toggle-password').click(function() {
//     const input = $(this).closest('.input-group').find('input');
//     const icon = $(this).find('i');
    
//     if (input.attr('type') === 'password') {
//       input.attr('type', 'text');
//       icon.removeClass('fa-eye').addClass('fa-eye-slash');
//     } else {
//       input.attr('type', 'password');
//       icon.removeClass('fa-eye-slash').addClass('fa-eye');
//     }
//   });


// });
 

</script>