<?php

$result = false;
$reload = false;
$check=null;
// if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['leaveRequest_id']) ){
	
	 
	 
	 $msg ='خطاء..!';
	 $id= (int) $_POST['leaveRequest_id'];

     function GetlastID($connect, $id) {
        $sql = "SELECT Draft FROM tblleaverequest WHERE Id = :id LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(); 
    
        if (!empty($row['Draft'])) {
                return 4; 
            }
            else
            return 3;
        } 
    }
    
	
	
	
    

	
	function removeRow($connect, $id) {
		
		try {
                     $qq = "SELECT path FROM tblleaverequest WHERE id = :ID";
$stm_ = $connect->prepare($qq);
$stm_->bindParam(':ID', $id, PDO::PARAM_INT);
$stm_->execute();

$path = $stm_->fetch(PDO::FETCH_ASSOC);

if ($path && !empty($path['path'])) {
    if (file_exists($path['path'])) {
        unlink($path['path']);
    }
}

				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = "delete  from   tblleaverequest  WHERE Id  =:id ";
                $stm2 = $connect->prepare($q2);
                $stm2->execute($parma2);
					
                if($stm2->rowCount() > 0){
                    
                    $connect->commit();
                    return true;
                }
                else{
                    $connect->rollBack();
                    return false;
                }
			}
            catch (\PDOException $e) 
            {
				$connect->rollBack();
				return false;
			}
		
		return false;
	}
			
			
			
	
		
    $check=GetlastID($connect_pdo,$id);		
			
                
						if($check==3){
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
				else if($check==4)
				{
					$msg = 'تم الرفع ولا يمكن الحذف';
				}
                
                
			
			
			
		
	// }
// }
//     else{
//     $msg= 'لايوجد لديك الصلاحيات الكافية لتنفيذ هذا الإجراء';
// }	
		
		

		
	
	
	 $output = array(
		"result"    =>$result,
		"reload"    => $reload,
		 "msg"    => $msg,
         "check"=>$check
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
	<p class="text-danger"><b>هل ترغب بالفعل بحذف الاجازة</b>
	<input type="hidden" name="leaveRequest_id" id="leaveRequest_id" class="form-control" value="<?=$_GET['id']?>" >
	

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
                url: 'hr-app/index.php?action=leaveRequest-remove?confirme=1',
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
                        window.location.href = 'leaveRequest-list';
                    }
                    
                    $('#preloading').hide();
                    $('#modal_default').modal('toggle');
                }
        });
        
    }); 



});
</script>


