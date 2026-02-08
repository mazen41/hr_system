<?php
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();


$result = true;
$msg = '';

$post_id             = isset($_POST["id"]) ? (int)$_POST["id"]: NULL;
$action              = !empty($post_id) ? 'edit': 'add';

if(empty($_POST["Reson"])){
    $result = false; 
    $msg = 'يرجى إدخال سبب الاستقالة';
}elseif(empty($_POST["Due_date"])){
    $result = false;
    $msg = 'يرجى تحديد تاريخ الاستقالة';   
}

else{ 	
$Due_date  = (!empty($_POST['Due_date'])     ?  trim(sanitizingData($_POST['Due_date']))     : NULL); 
$Reson      = (!empty($_POST['Reson'])         ?  trim(sanitizingData($_POST['Reson']))         : NULL); 	



        function createNewResignation($connect,$user,$branch,$Due_date,$Reson,$today_date,$now_date){
			$query = "INSERT INTO    tblresignation SET
                UserID     =:UserID,
                BranchID     =:BranchID,
                DueDate            =:DueDate,
                Reason            =:Reason,
                type            =:type,
                created_by            =:created_by,
                CreatedDate   =:CreatedDate,           
                LastUpdateDate          =:LastUpdateDate
            ";
			$stm = $connect->prepare($query);
			$stm->execute(
                    array(
                        'UserID'    => $user,
                        'BranchID'    => $branch,
                        'DueDate'            => $Due_date, 
                        'Reason'            => $Reson,
                        'type'            => 1,
                        'created_by'            => $user,
                        'CreatedDate'   => $today_date,
                        'LastUpdateDate'          => $now_date
                  
                    )
            );
		
            
			$created_id = $connect->lastInsertId();
            if($created_id > 0){
                return $created_id;
            }
				 return false;
		}
        
        function updateResignation($connect,$user,$branch,$Due_date,$Reson,$today_date,$now_date,$post_id){
			$query = "UPDATE  tblresignation SET  
                UserID     =:UserID,
                BranchID     =:BranchID,
                DueDate            =:DueDate,
                Reason            =:Reason,
                CreatedDate   =:CreatedDate,           
                LastUpdateDate          =:LastUpdateDate
				where Id   = :id
            ";
            
			$stm = $connect->prepare($query);
			$stm->execute(
                    array(
						'id'     => $post_id,
                        'UserID'    => $user,
                        'BranchID'    => $branch,
                        'DueDate'            => $Due_date,  
                        'Reason'            => $Reson,
                        'CreatedDate'   => $today_date,
                        'LastUpdateDate'          => $now_date
                    )
            );

            if($stm->rowCount() > 0){
                return true;
             }
			
				 return false;

		}
        
        
		if($action=="add")
		{
		  $post_id=  createNewResignation($connect_pdo,$user,$branch,$Due_date,$Reson,$today_date,$now_date);
          
		} 
		if($action=="edit")
		{
		  $id_reslut= updateResignation($connect_pdo,$user,$branch,$Due_date,$Reson,$today_date,$now_date,$post_id);
		}       
          
	}

$output = array(
	"result"    => $result,
	"id"        => !empty($post_id) ? $post_id : '',
	"msg"       => $msg
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));




?>