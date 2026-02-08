 <?php

// if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['order_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['order_id'];
	
	function Confrom($connect, $id) {
		
		try {
				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = " UPDATE  order_finger_add SET 
                status =1   WHERE Id  =:id";
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
    
    
    function add_to_attention($connect, $id,$user)
    {
        $query = "SELECT a.Id,a.date,a.UserID,a.time   
        FROM   order_finger_add AS a
        WHERE a.Id = :id";
    
    $st = $connect->prepare($query);
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $row = $st->fetch();

        $query_add="INSERT INTO   attendancet SET
        EmpID     =:EmpID,
        who_add      =:who_add,
        Time        =:Time,
        Type            =:Type,
        Date            =:Date
    ";
        $stm = $connect->prepare($query_add);
    $stm->execute(
            array(
                'EmpID'    => sanitizingData($row['UserID']),
                'who_add'     => $user, 
                'Type'        => null, 
                'Date'            => $row['date'], 
                'Time'            => $row['time']
            )
    );
        $created_id = $connect->lastInsertId();
    if($created_id > 0){
        return true;
    }
    else
    {
        return false;
        
    }

    }
    else
    {
        return false;
    }
    }
			
			
	
		
			
			
                
					
                    if(Confrom($connect_pdo, $id)){
                        $result = true;
                        $msg = 'تم التاكيد بنجاح';
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                        if(add_to_attention($connect_pdo, $id,$user))
                        {
                             $result = false;
                        }
                        else
                        {
                            $result = false;
                             $msg = 'لم يتم التاكيد';
                        }
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
	<p class="text-danger"><b>هل ترغب بالفعل بتاكيد الطلب</b>
	<input type="hidden" name="order_id" id="order_id" class="form-control" value="<?=$_GET['id']?>" >
	

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
                url: './hr-app/finger-forget-conform-admin?confirme=1',
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
                        window.location.href = 'finger-forget-view-admin?id='+$("#order_id").val()+'; ';
                    }
                    
                    $('#preloading').hide();
                    $('#modal_default').modal('toggle');
                }
        });
        
    }); 



});
</script>
<?php

?>

