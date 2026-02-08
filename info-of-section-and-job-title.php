<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
    $BranchID = $_POST['value'];
// section
$query = " SELECT c.Id, c.Name
FROM tblsection AS c
WHERE c.BranchID = :BranchID ";
$stmt = $connect_pdo->prepare($query);
$stmt->execute(['BranchID' => $BranchID]);
$results = $stmt->fetchAll();
$section = array();
foreach($results as $row)
{
$section_array = array();
$section_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$section[] = $section_array;
}

// jobtitle
$jobtitle = " SELECT Id ,Name FROM tbljobtitle where BranchID = :BranchID";
$stmt_jobtitle = $connect_pdo->prepare($jobtitle);
$stmt_jobtitle->execute(['BranchID' => $BranchID]);
$results_jobtitle = $stmt_jobtitle->fetchAll();

$jobtitle_list = array();
foreach($results_jobtitle as $row)
{
$jobtitle_array = array();
$jobtitle_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$jobtitle_list[] = $jobtitle_array;
}


$output = array(
 "section"    => $section,
 "jobtitle"    => $jobtitle_list,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
