<?php

if(!empty($_GET['confirme']) && !empty($_POST['leaveRequest_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['leaveRequest_id'];

function Search_leaves($connect, $id)
{
    // أولاً: نستخرج تاريخ بداية ونهاية الإجازة بالـ id المحدد
    $query_dates = "SELECT leave_start_date, leave_end_date, UserID FROM tblleaverequest WHERE Id = ?";
    $stmt_dates = $connect->prepare($query_dates);
    $stmt_dates->execute([$id]);
    $leave = $stmt_dates->fetch(PDO::FETCH_ASSOC);

    // إذا لم توجد إجازة بهذا الرقم
    if (!$leave) {
        return false;
    }

    $start_date = $leave['leave_start_date'];
    $end_date   = $leave['leave_end_date'];
    $user_id    = $leave['UserID'];

    // البحث عن أي إجازة متداخلة لنفس المستخدم في نفس الفترة
    $query = "SELECT Id 
        FROM tblleaverequest
        WHERE  UserID = ?
        AND status IS NOT NULL and status !=2
        AND leave_start_date <= ?
        AND leave_end_date >= ?
    ";

    $stmt = $connect->prepare($query);
    $stmt->execute([ $user_id, $end_date, $start_date]);

    return $stmt->rowCount() > 0;
}



	
	function Confrom($connect, $id) {
		
		try {
				$connect->beginTransaction();
                $parma2 = ["id" => (int)$id ];
                $q2 = " UPDATE   tblleaverequest SET 
                status =2   WHERE Id  =:id";
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
			
			
			
	
		
			
			
                    if(!Search_leaves($connect_pdo, $id)){
					
                    if(Confrom($connect_pdo, $id)){
                        $result = true;
                        $msg = 'تم التاكيد بنجاح';
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                    }
                    else{
                        $msg = 'هناك خطاء اثناء التاكيد';
                    }
                }
                else
                {
                   $msg = ' يوجد لهذا الموظف اجازة بنفس  يوم من ايام الاجازة وتم اعتمادها'; 
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
	<p class="text-danger"><b>هل ترغب بالفعل بالغاء الاجازة</b>
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



    $("#conform_incentive").on('submit', function(e){
        e.preventDefault();
        
        var form_data = $(this).serialize();
            $.ajax({
                url: 'hr-app/index.php?action=leaveRequest-deni-admin?confirme=1',
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
                        window.location.href = 'leaveRequest-view-admin?id='+$("#leaveRequest_id").val()+'; ';
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

