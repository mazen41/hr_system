<?php

if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['groub_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['groub_id'];
	 function GetlastID($connect, $id) {
		$sql = "SELECT 	GroupID FROM tblremewal WHERE FIND_IN_SET(:id, GroupID) LIMIT 1";
		$stmt = $connect->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->rowCount() > 0) {
			return false;
		}
		return true;
	}
	
	
	
    

	
	function removeRow($connect, $id) {
		
		try {
				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = "delete  from  tblgroup  WHERE Id  =:id LIMIT 1";
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
				return false;
			}
		
		return false;
	}
			
			
			
	
		
			
			
                
						if(GetlastID($connect_pdo,$id)){
                    if(removeRow($connect_pdo, $id)){
                        $result = true;
                        $msg = 'تم الحذف بنجاح';
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                    }
                    else{
                        $msg = 'هناك خطاء اثناء الحذف';
                    }
				}
				else
				{
					$msg = 'هذه المجموعة مرتبطه بمستخدمين ولايمكن حذفها';
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



<form class="form-horizontal" role="form" action="#" method="post" id="remove_confirmed_fm">
	<p class="text-danger"><b>هل ترغب بالفعل بحذف المجموعه</b>
	<input type="hidden" name="groub_id" id="groub_id" class="form-control" value="<?=$_GET['id']?>" >
	

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



    $("#remove_confirmed_fm").on('submit', function(e){
        e.preventDefault();
        
        var form_data = $(this).serialize();
            $.ajax({
                url: 'hr-app/index.php?action=groub-remove?confirme=1',
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
                        window.location.href = 'groub-list';
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

