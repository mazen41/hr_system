<?php

if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['holidays_id']) ){
	
	 $result = false;
	 $reload = false;
	 $msg ='خطاء..!';
	 $id= (int) $_POST['holidays_id'];
     function GetlastID($connect,$id)
     {
        $sql = "SELECT Holiday_ID FROM holidays where Id=$id";
        $resultt = $connect->query($sql);
        
        if ($resultt->rowCount() > 0) {
            $row = $resultt->fetch(PDO::FETCH_ASSOC);
            $HolidayIDDay = $row['Holiday_ID'];
        }
    return $HolidayIDDay;
     }	
$h_d_ID=GetlastID($connect_pdo,$id);
    

	
	function removeRow($connect, $id, $h_d_ID) {
		
		try {
				$connect->beginTransaction();
				
				$parma1 = ["HolidayID" => (int)$h_d_ID ];
				
				$q1 = "delete  from holidays_day  WHERE HolidayID =:HolidayID"; 
				$stm1 = $connect->prepare($q1);
				$stm1->execute($parma1);
				

                $parma2 = ["id" => (int)$id ];
                $q2 = "delete  from holidays  WHERE id =:id LIMIT 1";
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
			
			
			
	
		
			
			
                
                    
                    if(removeRow($connect_pdo, $id,$h_d_ID)){
                        $result = true;
                        $msg = 'تم الحذف بنجاح';
                        $reload = true;
                        $_SESSION['alert'] = $msg;
                    }
                    else{
                        $msg = 'لايمكن تنفيذ هذة العملية.. قد يكون هذا العميل قيد الاستخدام حالياً';
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
	<p class="text-danger"><b>هل ترغب بالفعل بحذف الإجازة</b>
	<input type="hidden" name="holidays_id" id="holidays_id" class="form-control" value="<?=$_GET['id']?>" >
	

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
                url: './hr-app/holidays-remove?confirme=1',
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
                        window.location.href = 'holidays-list';
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

