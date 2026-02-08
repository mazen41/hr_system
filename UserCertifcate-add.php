<?php
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();


$result = true;
$msg = '';

$post_id             = isset($_POST["id"]) ? (int)$_POST["id"]: NULL;

$action=$_POST["action"];

$id_edite=(!empty($_POST['id_edite']) ?  trim(sanitizingData($_POST['id_edite'])) : NULL);
// $id_Delete=(!empty($_POST['id_delete']) ?  trim(sanitizingData($_POST['id_delete'])) : NULL);
$name=(!empty($_POST['emp_C']) ?  trim(sanitizingData($_POST['emp_C'])) : NULL);
$side=(!empty($_POST['emp_CerCom']) ?  trim(sanitizingData($_POST['emp_CerCom'])) : NULL);
$start_date=(!empty($_POST['emp_Cer_date']) ?  trim(sanitizingData($_POST['emp_Cer_date'])) : NULL);
$file_type_error=Null;
$file_size_error=Null;
$location=Null;
$file_temp=Null;
   


if (!empty($_FILES['emp_CerFile']) && !empty($_FILES['emp_CerFile']['name'])){
    $file_name = $_FILES['emp_CerFile']['name'];
    $file_temp = $_FILES['emp_CerFile']['tmp_name'];
    $file_size = $_FILES['emp_CerFile']['size'];
    $file_type = $_FILES['emp_CerFile']['type'];
    $upload_folder  = '../uploads/'.$subdomain.'/';
    $upload_too = 'CertifacteUser';
    $upload_app = $upload_folder.$upload_too;
    if (!file_exists($upload_folder)) {
        mkdir($upload_folder, 0755, true);
        touch($upload_folder.'/index.html');
    }
    if (!file_exists($upload_app)) {
        mkdir($upload_app, 0777, true);
        touch($upload_app.'/index.html');
    }
    $image_exe = explode('.' , $file_name);
    $image_exe = strtolower(end($image_exe));
    $file_type_error = !in_array($image_exe,['gif', 'png', 'jpg', 'jpeg', 'pdf' ]) ? true : null;
    $file_size_error = $file_size > 5242880 ? true : null;
    $new_name = uniqid($upload_too,false) .time(). '.' . $image_exe;
    $location ="$upload_app/$new_name";
    
}
if(empty($start_date))
{
    $result = false; 
    $msg = 'يرجى إدخال تاريخ الشهادة';
}
elseif(empty($name))
{
    $result = false; 
    $msg = 'يرجى إدخال اسم الشهادة ';
}
elseif($file_type_error)
{
    $result = false; 
    $msg =  'يسمح فقط ب png,jpg,pdf,jpeg,gif';
}
elseif($file_size_error)
{
    $result = false; 
    $msg =  'جحم الملف كبير جدا';
}
else
{
































function createNewCertifcate($connect,$ID,$name,$side,$start_date,$location,$file_temp,$user,$today_date,$now_date){
try {
$sql = "INSERT INTO user_cer (UserID, Certifacte_name, Side, StartDate, FilePath, CreatedBy, CreatedDate, LastUpdateDate)
    VALUES (:UserID, :Certifacte_name, :Side, :StartDate, :FilePath, :CreatedBy, :CreatedDate, :LastUpdateDate)";
$stmt = $connect->prepare($sql);
    $stmt->execute(
        [
        ':UserID' => $ID,
        ':Certifacte_name' => $name,
        ':Side' => $side,
        ':StartDate' => $start_date,
        ':FilePath' => $location,
        ':CreatedBy' => $user,
        ':CreatedDate' => $today_date,
        ':LastUpdateDate' => $now_date
    ]
);
    (!empty($location) ) ? move_uploaded_file($file_temp, $location):'';
$result = true;
$msg = "تمت إضافة البيانات للايام بنجاح!";
return 1;


} catch (PDOException $e) {
$result = false;
$msg = "حدث خطأ أثناء : " . $e->getMessage();
return 0;
}


}

function UpdateCertifcate($connect, $ID, $name, $side, $start_date, $location, $file_temp, $now_date){
    try {
        $qq = "SELECT FilePath FROM user_cer WHERE id = :ID";
        $stm_ = $connect->prepare($qq);
        $stm_->bindParam(':ID', $ID, PDO::PARAM_INT);
        $stm_->execute();
        $path = ($stm_->rowCount() > 0) ? $stm_->fetch() : null;
        if(!empty($location)) {
            unlink($path["FilePath"]);
            move_uploaded_file($file_temp, $location); 
        }

        // بناء الاستعلام
        $sql = "UPDATE user_cer SET 
                Certifacte_name = :Certifacte_name, 
                Side = :Side, 
                StartDate = :StartDate,  
                LastUpdateDate = :LastUpdateDate";
        if(!empty($location)) {
            $sql .= ", FilePath = :FilePath";
        }
        $sql .= " WHERE id = :ID";
        $stmt = $connect->prepare($sql);
        $params = [
            ':ID' => $ID,
            ':Certifacte_name' => $name,
            ':Side' => $side,
            ':StartDate' => $start_date,
            ':LastUpdateDate' => $now_date
        ];
        if(!empty($location)) {
            $params[':FilePath'] = $location;
        }

        $stmt->execute($params);
        $result = true;
        $msg = "تمت تعديل البيانات بنجاح!";
        return 1;

    } catch (PDOException $e) {
        $result = false;
        $msg = "حدث خطأ أثناء التعديل: " . $e->getMessage();
        return 0;
    }
}       
		if($action=="add" && $result)
		{
		  $post_id_check=  createNewCertifcate($connect_pdo,$post_id,$name,$side,$start_date,$location,$file_temp,$user,$today_date,$now_date);
          
		} 
		if($action=="edit" && $result)
		{
		  $post_id_check=  UpdateCertifcate($connect_pdo,$id_edite,$name, $side, $start_date, $location, $file_temp, $now_date);
		} 	
    }

$output = array(
	"result"    => $result,
	"id"        => !empty($post_id_check) ? $post_id_check : '',
	"type"    => $action,
    "post_id"    => $post_id,
	"msg"       => $msg,
    "loc"=>$location
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));




?>