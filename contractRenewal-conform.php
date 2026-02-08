<?php

if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['contractid']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['contractid'];
	

     function get_user_id($connect, $id)
     {
         // استخدم استعلام محضّر لتجنّب الحقن SQL Injection
         $query_id = "SELECT UserID FROM tblremewal WHERE Id = :id";
         $stm_id = $connect->prepare($query_id);
         $stm_id->execute([':id' => $id]);
         $result = $stm_id->fetch(PDO::FETCH_ASSOC);
         $user_id=$result['UserID'];
         if(!empty($user_id))
         {
             $query_upateversion = " UPDATE   tblusers SET
                lastversion=$id where UserID =$user_id
                ";
                $res_3 = $connect->prepare($query_upateversion);
                $res_3->execute();
         }
     }
	function Confrom($connect, $id, $today_date) {
    try {
        $connect->beginTransaction();

        $params = [
            ":id" => (int)$id,
            ":conform_date" => $today_date
        ];

        $q = "UPDATE tblremewal 
              SET state = 1, conform_date = :conform_date 
              WHERE Id = :id";

        $stmt = $connect->prepare($q);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $connect->commit();
            return true;
        } else {
            $connect->rollBack();
            return false;
        }
    } catch (\PDOException $e) {
        $connect->rollBack();
        error_log("PDO Error in Confrom: " . $e->getMessage());
        return false;
    }
}

			
			
			
	
		
			
			
                
					
                    if(Confrom($connect_pdo, $id,$today_date)){
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
	<p class="text-danger"><b>هل ترغب بالفعل بتاكيد تجديد البيانات</b>
	<input type="hidden" name="contractid" id="contractid" class="form-control" value="<?=$_GET['id']?>" >
	

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
                url: './hr-app/contractRenewal-conform?confirme=1',
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
                        window.location.href = 'contractRenewal-view?id='+$("#contractid").val()+'; ';
                    }
                    
                    $('#preloading').hide();
                    $('#modal_default').modal('toggle');
                }
        });
        
    }); 



});
</script>
<?php
}else{
    echo '<p class="text-danger"><b>لايوجد لديك الصلاحيات الكافية لتنفيذ هذا الإجراء</b>';
}
?>

