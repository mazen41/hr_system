 <?php

// if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['resignation_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['resignation_id'];

     function GetlastID($connect, $id) {
        $sql = "SELECT Status, DueDate,Draft FROM tblresignation WHERE Id = :id LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(); 
            $dueDate = strtotime($row['DueDate']);
            $currentDate = strtotime(date('Y-m-d'));
    
            if ($dueDate < $currentDate) {
                return 1;
            }
            elseif (!empty($row['Status'])) {
                return 2; 
            }
            elseif (!empty($row['Draft'])) {
                return 4; 
            }
            else
            return 3;
        } 
    }
    
	
	
	
    

	
	function removeRow($connect, $id) {
		
		try {
				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = "delete  from   tblresignation  WHERE Id  =:id ";
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
				else if($check==1)
				{
					$msg = 'تاريخ الاضافة اكبر من تاريخ اليوم';
				}
                else if($check==2)
				{
					$msg = ' تم الاعتماد ولايمكن حذفها';
				}
                else if($check==4)
				{
					$msg = 'تم رفعها وليس لديك الصلاحية لحذفها';
				}
                
                
			
			
			
		
		
		
		

		
	
	
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
	<p class="text-danger"><b>هل انت متاكد</b>
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



    $("#remove_confirmed_fm").on('submit', function(e){
        e.preventDefault();
        
        var form_data = $(this).serialize();
            $.ajax({
                url: './hr-app/resignation-remove-add?confirme=1',
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
                        window.location.href = 'resignation-list-add';
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

