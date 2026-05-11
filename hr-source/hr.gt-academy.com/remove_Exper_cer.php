 <?php
$result=true;
$action=$_POST["action"];
$id_Delete=(!empty($_POST['id_delete']) ?  trim(sanitizingData($_POST['id_delete'])) : NULL);


function DeleteExperince($connect,$id_Delete)
{
    $qq = "SELECT FilePath FROM user_exper WHERE id = :ID";
    $stm_ = $connect->prepare($qq);
    $stm_->bindParam(':ID', $id_Delete, PDO::PARAM_INT);
    $stm_->execute();
    $path = ($stm_->rowCount() > 0) ? $stm_->fetch() : null;
    unlink($path["FilePath"]);



    $q="DELETE FROM user_exper WHERE id=:ID ";
    $stm_ = $connect->prepare($q);
    $stm_->bindParam(':ID', $id_Delete, PDO::PARAM_INT);
    $stm_->execute();
    $result=true;
}

function DeleteCer($connect,$id_Delete)
{
    $qq = "SELECT FilePath FROM user_cer WHERE id = :ID";
    $stm_ = $connect->prepare($qq);
    $stm_->bindParam(':ID', $id_Delete, PDO::PARAM_INT);
    $stm_->execute();
    $path = ($stm_->rowCount() > 0) ? $stm_->fetch() : null;
    unlink($path["FilePath"]);



    $q="DELETE FROM  user_cer WHERE id=:ID ";
    $stm_ = $connect->prepare($q);
    $stm_->bindParam(':ID', $id_Delete, PDO::PARAM_INT);
    $stm_->execute();
    $result=true;
}



if($action=="remove")
{
DeleteExperince($connect_pdo,$id_Delete);
} 
if($action=="remove_cer")
{
DeleteCer($connect_pdo,$id_Delete);
} 



$output = array(
    "result"    => $result,
	"msg"       => "تم الحذف بنجاح"
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));

?>