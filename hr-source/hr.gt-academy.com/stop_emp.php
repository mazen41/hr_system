<?php
function GetlastID($connect, $id) {
    $sql = "SELECT IsDisabled, UserID FROM tblusers WHERE UserID = :id LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

if($User->userIsAdmin()){

      $stat_value = null;

    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stat = GetlastID($connect_pdo, $id);
        if ($stat) {
            $stat_value = !empty($stat['IsDisabled']) ? 'تفعيل' : 'ايقاف';
        }
    }

if(!empty($_GET['confirme']) && !empty($_POST['shift_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['shift_id'];





$stat=GetlastID($connect_pdo,$id);
if ($stat) 
    $stat_value = !empty($stat['IsDisabled']) ? 'تفعيل' : 'ايقاف';

	
    

	
function update_disable_or_action($connect, $id, $stat_value) {
    try {
        $connect->beginTransaction();

        $params = ["id" => (int)$id];

        if ($stat_value == 'تفعيل') {
            $sql = "UPDATE tblusers SET IsDisabled = NULL WHERE UserID = :id LIMIT 1";
        } else {
            $sql = "UPDATE tblusers SET IsDisabled = 1 WHERE UserID = :id LIMIT 1";
        }

        $stmt = $connect->prepare($sql);
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
        return false;
    }
}

			
			
			
	
		
			
			
                
						
                    if(update_disable_or_action($connect_pdo, $id,$stat_value)){
                        $result = true;
                        $msg = "تم $stat_value بنجاح";
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                    }
                    else{
                        $msg = 'هناك خطاء اثناء التعديل';
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



<form class="form-horizontal" role="form" action="#" method="post" id="stop_emp">
	<p class="text-danger"><b>هل ترغب بالفعل ب <?php echo $stat_value ?> الموظف</b>
	<input type="hidden" name="shift_id" id="shift_id" class="form-control" value="<?=$_GET['id']?>" >
	

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



    $("#stop_emp").on('submit', function(e){
        e.preventDefault();
        
        var form_data = $(this).serialize();
            $.ajax({
                url: 'stop_emp.php?confirme=1',
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
                        window.location.href = 'employer-list';
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

