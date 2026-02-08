<?php

// if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['resignation_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['resignation_id'];
     
     function get_user_id($connect, $id)
     {
         // استخدم استعلام محضّر لتجنّب الحقن SQL Injection
         $query_id = "SELECT UserID FROM tblresignation WHERE Id = :id";
         $stm_id = $connect->prepare($query_id);
         $stm_id->execute([':id' => $id]);
         $result = $stm_id->fetch(PDO::FETCH_ASSOC);
         $user_id=$result['UserID'];
         if(!empty($user_id))
         {
             $query_upateversion = " UPDATE   tblusers SET
                resigned_or_dismissed=1 where UserID =$user_id
                ";
                $res_3 = $connect->prepare($query_upateversion);
                $res_3->execute();
         }
     }
	
	function Confrom($connect, $id) {
		
		try {
				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = " UPDATE   tblresignation SET 
                Status =1   WHERE Id  =:id";
                $stm2 = $connect->prepare($q2);
                $stm2->execute($parma2);
					
                if($stm2->rowCount() > 0){
                    
                    $connect->commit();
                    return true;
                }else{
                    $connect->rollBack();
                    return false;
                }
			}catch (\PDOException $e) {
				$connect->rollBack();
                echo $e;
				return false;
			}
		
		return false;
	}
			
			
			
	
		
			
			
                
					
                    if(Confrom($connect_pdo, $id)){
                        get_user_id($connect_pdo, $id);
                        $result = true;

                        $msg = 'تم التاكيد بنجاح';
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                    }
                    else{
                        $msg = 'هناك خطاء اثناء التاكيد';
                    }
				
                
                
			
			
			
		
		
		
		

		
	
	
	 $output = array(
		"result"    =>$result,
		"reload"    => $reload,
		 "msg"    => $msg
		);
 

	echo(json_encode($output,JSON_UNESCAPED_UNICODE));
 
	die();
}



?>
<style>
.hide {
   display:none !important; 
} 
form > button{
	float: left;
	margin: 0px 5px;
}

</style>
<div  id="modal_container" class="hide">



<div id="modify_confirme"></div>



<form class="form-horizontal" role="form" action="#" method="post" id="conform_incentive">
	<p class="text-danger"><b>هل ترغب بالفعل بتاكيد الاستقالة</b>
	<input type="hidden" name="resignation_id" id="resignation_id" class="form-control" value="<?=$_GET['id']?>" >
	

	<hr>
	<button class="btn btn-danger" type="submit" >نعم</button>
	<button class="btn btn-default" type="button"  id="cancel_remove">لا</button>
</form>

</div>
<script>

$(document).ready(function(){


	
       $('#modal_default .modal-body').removeClass('loader');
        $('#modal_container').removeClass('hide');
	 //$('#modal_container').show();
	 


	
    $('#cancel_remove').on('click', function(e){
        $('#modal_default').modal('toggle');
        
    });



    $("#conform_incentive").on('submit', function(e){
        e.preventDefault();
        
        var form_data = $(this).serialize();
            $.ajax({
                url: './hr-app/resignation-conform-admin?confirme=1',
                type: 'POST',
                data:form_data,
                dataType:"json",
                beforeSend:function(){
                    $('#preloading').show();
                }, 
                success:function(data){
                    if(data.result){
                        toastr.success(data.msg);
                    }else{
                        toastr.error(data.msg);
                    }
                    if(data.reload){
                        window.location.href = 'resignation-view-admin?id='+$("#resignation_id").val()+'; ';
                    }
                    
                    $('#preloading').hide();
                    $('#modal_default').modal('toggle');
                }
        });
        
    }); 



});
</script>
<?php
// }else{
//     echo '<p class="text-danger"><b>لايوجد لديك الصلاحيات الكافية لتنفيذ هذا الإجراء</b>';
// }
?>

