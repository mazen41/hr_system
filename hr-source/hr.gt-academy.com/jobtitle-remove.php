<?php

if($User->userIsAdmin()){

if(!empty($_GET['confirme']) && !empty($_POST['job_it']) ){
	

	$result = false;
	$reload = false;
	$msg ='خطاء..!';
	
	$id= (int) $_POST['job_it'];
	function GetlastID($connect, $id) {
	   $sql = "SELECT jobtitleID FROM tblremewal WHERE jobtitleID = :id LIMIT 1";
	   $stmt = $connect->prepare($sql);
	   $stmt->bindValue(':id', $id, PDO::PARAM_STR);
	   $stmt->execute();
	   if ($stmt->rowCount() > 0) {
		   return false;
	   }
	   return true;
   }

   function CheckSectionChild($connect, $id) {
	$sql = "SELECT ParentID FROM  tbljobtitle
	WHERE ParentID = :id LIMIT 1";
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
			$q2 = "delete  from tbljobtitle  WHERE Id  =:id LIMIT 1";
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
	if(CheckSectionChild($connect_pdo,$id)){
	
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
$msg = 'هذه المسمى عباره عن اب ولديه ابناء';
}
}
else
{
$msg = 'هذا المسمى  مرتبطه بمستخدمين ولايمكن حذفها';
}
	// $result = false;
	// $reload = false;
	// $msg ='خطاء..!';
	// $id= (int) $_POST['client_id'];
	
	
	// function cheack($connect,$id) {
	// 	$parma = ["id" => $id  ];
		
	// 	$query = "SELECT  id,AccountID from clients WHERE id =:id  LIMIT 1";
		
	// 	$stm = $connect->prepare($query);
	// 	$stm->execute($parma);
	// 	$rows = $stm->rowCount();
	// 	if($rows > 0)
	// 	{
	// 		$rows = $stm->fetch();
	// 		return $rows;
	// 	}
		
	// 	return false;
	// }
	
	//  function cheackIsThereAnyEntry($connect, $account_id) {
	// 	$parma = ["id" => (int)$account_id];
		
	// 	$query = "SELECT  EntryDetailsID from tblentriesdetails  WHERE AccountID =:id  order by EntryDetailsID DESC LIMIT 1";
		
	// 	$stm = $connect->prepare($query);
	// 	$stm->execute($parma);
	// 	if($stm->rowCount() > 0)
	// 	{
	// 		return false;
	// 	}else{
	// 		return true;
	// 	}
		
	// 	return false;
	// }
    
    // function cheackIsClientHadInv($connect, $id) {
	// 	$parma = ["id" => (int)$id];
		
	// 	$query = "SELECT  InvID from tblinvsh  WHERE ClientID =:id  order by InvID DESC LIMIT 1";
		
	// 	$stm = $connect->prepare($query);
	// 	$stm->execute($parma);
	// 	if($stm->rowCount() > 0)
	// 	{
	// 		return true;
	// 	}
		
	// 	return false;
	// }
	
	// function removeRow($connect, $id, $account_id) {
		
	// 	try {
	// 			$connect->beginTransaction();
				
	// 			$parma1 = ["account_id" => (int)$account_id ];
				
	// 			$q1 = "delete  from tblaccountguide  WHERE AccountID =:account_id  AND AccountType ='0' LIMIT 1"; 
	// 			$stm1 = $connect->prepare($q1);
	// 			$stm1->execute($parma1);
				

    //             $parma2 = ["id" => (int)$id ];
    //             $q2 = "delete  from clients  WHERE id =:id LIMIT 1";
    //             $stm2 = $connect->prepare($q2);
    //             $stm2->execute($parma2);
					
    //             if($stm2->rowCount() > 0){
                    
    //                 $connect->commit();
    //                 return true;
    //             }else{
    //                 $connect->rollBack();
    //                 return false;
    //             }
	// 		}catch (\PDOException $e) {
	// 			$connect->rollBack();
	// 			return false;
	// 		}
		
	// 	return false;
	// }
			
			
			
	// $row = cheack($connect_pdo,$id);
	
	// if(!empty($row)){
		
			
	// 		if(cheackIsClientHadInv($connect_pdo, $row['id'])){
    //              $msg = 'لايمكن خذف هذا العميل لارتباطة بفواتير بيع موجودة';
    //         }else{
    //             if(cheackIsThereAnyEntry($connect_pdo, $row['AccountID'])){
                    
    //                 if(removeRow($connect_pdo, $row['id'], $row['AccountID'])){
    //                     $result = true;
    //                     $msg = 'تم الحذف بنجاح';
    //                     $reload = true;
    //                     $_SESSION['alert'] = $msg;
    //                 }else{
    //                     $msg = 'لايمكن تنفيذ هذة العملية.. قد يكون هذا العميل قيد الاستخدام حالياً';
    //                 }
                
    //             }else{
    //                 $msg = 'لايمكن خذف هذا العميل لوجود حركات مالية متعلقة';
    //             }
    //         }
			
			
			
		
		
		
		
	// }
    // else{
	// 	$msg = 'هذا العميل لم يعد متوفرة';
	// }
		
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
	<p class="text-danger"><b>هل ترغب بالفعل بحذف المسمى الوظيفي</b>
	<input type="hidden" name="job_it" id="job_it" class="form-control" value="<?=$_GET['id']?>" >
	

	<hr>
	<button class="btn btn-danger" type="submit" >نعم</button>
	<button class="btn btn-default" type="button"  id="cancel_remove">لا</button>
</form>

</div>

<!--<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>-->

<script>

//$("#modal_default .close").hide
 //$('#modal_default .modal-body').removeClass('loader');
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
                url: 'hr-app/index.php?action=jobtitle-remove?confirme=1',
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
                        window.location.href = 'jobtitle-list';
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

