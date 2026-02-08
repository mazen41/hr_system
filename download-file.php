<?php
if($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['id'])){
	$id= $_GET['id'];
    $type=$_GET['type'];
 function getFilePath($connect, $id,$type ){
        $sql = "select path_h,path_license,path_passport,path_health FROM `tblusers` WHERE UserID  = :id limit 1";
        $stm = $connect->prepare($sql);
        $stm->execute(
            array(
                ':id'     => $id
            )
        );
        if($stm->rowCount() > 0){
            $row = $stm->fetch();
           if ($type==1) return $row['path_h'];
           if ($type==2) return $row['path_license'];
           if ($type==3) return $row['path_passport'];
           if ($type==4) return $row['path_health'];
           
        }
        return null;
    }
	
 $file = getFilePath($connect_pdo, $id,$type ); 
 
	if(file_exists($file)) { 
		header('Content-Description: File Transfer');  
		header('Content-Type: application/octet-stream');  
		header('Content-Disposition: attachment; filename="' . basename($file) . '"');  
		header('Expires: 0');  
		header('Cache-Control: must-revalidate');  
		header('Pragma: public');  
		header('Content-Length: ' . filesize($file));  
		readfile($file);
		exit;
	}else{
		include $coreDir . "404.html";
	}
} 
  
?>

