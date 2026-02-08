<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
    $type = $_POST['value'];
    $msg='';
    $result=false;
    $data = array();
if(empty($_POST['BranchID']))
{
    $msg="اختر الفرع اولا";
    $result=false;
}
else{
    $result=true;
    $branches = $_POST['BranchID'];

    if($type==1)
    {
        
        $query = "SELECT u.UserID as ID, CONCAT(u.FirstName, ' ', u.LastName) as Name FROM tblusers u 
        JOIN tblremewal t ON u.lastversion = t.Id
        WHERE u.BranchID IN ($branches) and u.isemp is not null and u.resigned_or_dismissed is null and '$today_date' between t.new_s_date and t.new_e_date";
    }
    if($type==2)
    {
        $query="SELECT 	Id as ID ,Name FROM  tblgroup WHERE BranchID IN ($branches)";
    }
    if($type==3)
    {
        $query="SELECT 	Id as ID ,Name FROM  tbljobgrade WHERE BranchID IN ($branches)";
    }
    if($type==4)
    {
        $query=" SELECT c.Id As ID, c.Name as Name
FROM tblsection AS c
LEFT JOIN tblsection AS d ON c.Id = d.ParentID
WHERE c.ParentID IS NOT NULL
AND d.Id IS NULL and c.BranchID IN ($branches) ";
    }

    if($type==5)
    {
        $query=" SELECT c.Id As ID, c.Name as Name
FROM  tbljobtitle AS c
LEFT JOIN  tbljobtitle AS d ON c.Id = d.ParentID
WHERE c.ParentID IS NOT NULL
AND d.Id IS NULL and c.BranchID IN ($branches) ";
    }
// // section

$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll();

foreach($results as $row)
{
$data_array = array();
$data_array['data']=   array( 
'id' => $row['ID'], 'name' => $row['Name']); 
$data[] = $data_array;
}



}
$output = array(
"result"=>$result,
"data"    => $data,
"msg"=>$msg

);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
