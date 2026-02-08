 <?php
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();


$result = true;
$msg = '';

$post_id             = isset($_POST["id"]) ? (int)$_POST["id"]: NULL;

$action=$_POST["action"];

$id_edite=(!empty($_POST['id_edite']) ?  trim(sanitizingData($_POST['id_edite'])) : NULL);
// $id_Delete=(!empty($_POST['id_delete']) ?  trim(sanitizingData($_POST['id_delete'])) : NULL);
$name=(!empty($_POST['emp_Job']) ?  trim(sanitizingData($_POST['emp_Job'])) : NULL);
$side=(!empty($_POST['emp_JobCom']) ?  trim(sanitizingData($_POST['emp_JobCom'])) : NULL);
$start_date=(!empty($_POST['emp_Job_start_date']) ?  trim(sanitizingData($_POST['emp_Job_start_date'])) : NULL);
$enddate=(!empty($_POST['emp_Job_end_date']) ?  trim(sanitizingData($_POST['emp_Job_end_date'])) : NULL);
$task=(!empty($_POST['JObDeteils']) ?  trim(sanitizingData($_POST['JObDeteils'])) : NULL);
$file_type_error=Null;
$file_size_error=Null;
$location=Null;
$file_temp=Null;

if (!empty($_FILES['emp_jobFile']) && !empty($_FILES['emp_jobFile']['name'])){
    $file_name = $_FILES['emp_jobFile']['name'];
    $file_temp = $_FILES['emp_jobFile']['tmp_name'];
    $file_size = $_FILES['emp_jobFile']['size'];
    $file_type = $_FILES['emp_jobFile']['type'];
    $upload_folder  = '../uploads/'.$subdomain.'/';
    $upload_too = 'ExperinceUser';
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
    $msg = 'يرجى إدخال تاريخ البداية';
}
elseif(empty($enddate))
{
    $result = false; 
    $msg = 'يرجى إدخال تاريخ النهاية';
}
elseif(empty($name))
{
    $result = false; 
    $msg = 'يرجى إدخال المسمى الوظيفي';
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
































function createNewExperince($connect,$ID,$name,$side,$start_date,$enddate,$location,$file_temp,$task,$user,$today_date,$now_date){
try {
$sql = "INSERT INTO user_exper (UserID, TitleJob, side, StartDate, EndDate, FilePath, JobTasks, CreatedBy, CreatedDate, LastUpdateDate)
    VALUES (:UserID, :TitleJob, :side, :StartDate, :EndDate, :FilePath, :JobTasks, :CreatedBy, :CreatedDate, :LastUpdateDate)";
$stmt = $connect->prepare($sql);
    $stmt->execute(
        [
        ':UserID' => $ID,
        ':TitleJob' => $name,
        ':side' => $side,
        ':StartDate' => $start_date,
        ':EndDate' => $enddate,
        ':FilePath' => $location,
        ':JobTasks' => $task,
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

function UpdateExperince($connect, $ID, $name, $side, $start_date, $enddate, $location, $file_temp, $task, $now_date){
    try {
        $qq = "SELECT FilePath FROM user_exper WHERE id = :ID";
        $stm_ = $connect->prepare($qq);
        $stm_->bindParam(':ID', $ID, PDO::PARAM_INT);
        $stm_->execute();
        $path = ($stm_->rowCount() > 0) ? $stm_->fetch() : null;
        if(!empty($location)) {
            unlink($path["FilePath"]);
            move_uploaded_file($file_temp, $location); 
        }

        // بناء الاستعلام
        $sql = "UPDATE user_exper SET 
                TitleJob = :TitleJob, 
                side = :side, 
                StartDate = :StartDate, 
                EndDate = :EndDate, 
                JobTasks = :JobTasks, 
                LastUpdateDate = :LastUpdateDate";
        if(!empty($location)) {
            $sql .= ", FilePath = :FilePath";
        }
        $sql .= " WHERE id = :ID";
        $stmt = $connect->prepare($sql);
        $params = [
            ':ID' => $ID,
            ':TitleJob' => $name,
            ':side' => $side,
            ':StartDate' => $start_date,
            ':EndDate' => $enddate,
            ':JobTasks' => $task,
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

// function DeleteExperince($connect,$id_Delete)
// {
//     $q="DELETE FROM user_exper WHERE id=:ID ";
//     $stm_ = $connect->prepare($q);
//     $stm_->bindParam(':ID', $id_Delete, PDO::PARAM_INT);
//     $stm_->execute();
//     $result=true;
// }






       
		if($action=="add" && $result)
		{
		  $post_id_check=  createNewExperince($connect_pdo,$post_id,$name,$side,$start_date,$enddate,$location,$file_temp,$task,$user,$today_date,$now_date);
          
		} 
		if($action=="edit" && $result)
		{
		  $post_id_check=  UpdateExperince($connect_pdo,$id_edite,$name,$side,$start_date,$enddate,$location,$file_temp,$task,$now_date);
		} 
        // if($action=="remove")
		// {
		//  DeleteExperince($connect_pdo,$id_Delete);
		// } 
            
         
	
    }
$output = array(
	"result"    => $result,
	"id"        => !empty($post_id_check) ? $post_id_check : '',
	"type"    => $action,
    "post_id"    => $post_id,
	"msg"       => $msg
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));




?>