<?php
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();


$result = true;
$msg = '';

$action              = $_POST['name'];
 
if(empty($_POST["account_id_0"])){
    $result = false; 
    $msg = 'يرجى إختيار  حساب مرتبات وأجور ';
}elseif(empty($_POST["account_id_1"])){
    $result = false;
    $msg = 'يرجى إختيار  حساب  مكافآت الموظفين ';  
}elseif(empty($_POST["account_id_2"])){
    $result = false;
    $msg = 'يرجى إختيار  حساب  تعويضات الموظفين ';  
}elseif(empty($_POST["account_id_3"])){
    $result = false;
    $msg = 'يرجى إختيار  حساب  سلف الموظفين ';  
}elseif(empty($_POST["account_id_4"])){
    $result = false;
    $msg = 'يرجى إختيار  حساب  خصومات الموظفين  ';  
}
elseif(empty($_POST["account_id_5"])){
    $result = false;
    $msg = 'يرجى إختيار  حساب مرتبات الموظفين المستحقة ';  
}

else{
$last_day_of_month = date("Y-m-t");

$account_0    = (!empty($_POST['account_id_0']) ?  trim(sanitizingData($_POST['account_id_0'])) : NULL); 
$account_1    = (!empty($_POST['account_id_1']) ?  trim(sanitizingData($_POST['account_id_1'])) : NULL); 	
$account_2  = (!empty($_POST['account_id_2'])     ?  trim(sanitizingData($_POST['account_id_2']))     : null); 
$account_3    = (!empty($_POST['account_id_3'])       ?  trim(sanitizingData($_POST['account_id_3']))       : NULL); 
$account_4  = (!empty($_POST['account_id_4'])     ?  trim(sanitizingData($_POST['account_id_4']))     : NULL); 
$account_5  = (!empty($_POST['account_id_5'])     ?  trim(sanitizingData($_POST['account_id_5']))     : NULL); 

$accountName_0    = (!empty($_POST['accountName_0']) ?  trim(sanitizingData($_POST['accountName_0'])) : NULL); 
$accountName_1    = (!empty($_POST['accountName_1']) ?  trim(sanitizingData($_POST['accountName_1'])) : NULL); 	
$accountName_2  = (!empty($_POST['accountName_2'])     ?  trim(sanitizingData($_POST['accountName_2']))     : null); 
$accountName_3    = (!empty($_POST['accountName_3'])       ?  trim(sanitizingData($_POST['accountName_3']))       : NULL); 
$accountName_4  = (!empty($_POST['accountName_4'])     ?  trim(sanitizingData($_POST['accountName_4']))     : NULL); 
$accountName_5  = (!empty($_POST['accountName_5'])     ?  trim(sanitizingData($_POST['accountName_5']))     : NULL); 

function createNew($connect,$account,$account_name,$user,$today_date,$now_date){
			$query = "INSERT INTO   setting_account_salary SET
                account_id     =:account_id,
                account_name     =:account_name,
                created_by        =:created_by,
                created_date   =:created_date,           
                last_update          =:last_update
            ";
			$stm = $connect->prepare($query);
			$stm->execute(
                    array(
                        'account_id'    => $account,
                        'account_name'    => $account_name,
                        'created_by'     => $user, 
                        'created_date'        => $today_date, 
                        'last_update'        => $now_date
                    )
            );
			$created_id = $connect->lastInsertId();
            if($created_id > 0){
                return $created_id;
            }
				 return false;
		}
        
        function update($connect,$account,$account_name,$user,$now_date,$id){
			$query = "UPDATE  setting_account_salary SET  
                account_id     =:account_id,
                account_name     =:account_name,
                created_by      =:created_by,
                last_update        =:last_update
				where Id   = :id
            ";
            
			$stm = $connect->prepare($query);
			$stm->execute(
                    array(
						'id'     => $id,
						'account_id'     => $account,
                        'account_name'     => $account_name,
                        'created_by'     => $user, 
                        'last_update'        => $now_date
                    )
            );

            if($stm->rowCount() > 0){
                return true;
             }
			
				 return false;

		}
        
        
		if($action=="add")
		{
            
		  $post_id_0=  createNew($connect_pdo,$account_0,$accountName_0,$user,$today_date,$now_date);
          $post_id_2=  createNew($connect_pdo,$account_1,$accountName_1,$user,$today_date,$now_date);
          $post_id_3=  createNew($connect_pdo,$account_2,$accountName_2,$user,$today_date,$now_date);
          $post_id_4=  createNew($connect_pdo,$account_3,$accountName_3,$user,$today_date,$now_date);
          $post_id_5=  createNew($connect_pdo,$account_4,$accountName_4,$user,$today_date,$now_date);
          $post_id_6=  createNew($connect_pdo,$account_5,$accountName_5,$user,$today_date,$now_date);
          $msg="تمت عملية الاضافة بنجاح";
          
		} 
		if($action=="edit")
		{
		  $id_reslut= update($connect_pdo,$account_0,$accountName_0,$user,$now_date,1);
          $id_reslut= update($connect_pdo,$account_1,$accountName_1,$user,$now_date,2);
          $id_reslut= update($connect_pdo,$account_2,$accountName_2,$user,$now_date,3);
          $id_reslut= update($connect_pdo,$account_3,$accountName_3,$user,$now_date,4);
          $id_reslut= update($connect_pdo,$account_4,$accountName_4,$user,$now_date,5);
          $id_reslut= update($connect_pdo,$account_5,$accountName_5,$user,$now_date,6);
          $msg="تمت عملية التعديل بنجاح";
		}       
          
	
}

$output = array(
	"result"    => $result,
	"msg"       => $msg
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));




?>