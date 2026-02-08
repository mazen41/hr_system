<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
// section


$allowed_branches = $User->allBranches($User->branches);
$branch_ids = array_keys($allowed_branches); // استخراج المفاتيح
$allowed_branch = implode(',', $branch_ids);
$query = " SELECT c.Id, c.Name
FROM tblsection AS c
LEFT JOIN tblsection AS d ON c.Id = d.ParentID
WHERE c.ParentID IS NOT NULL
AND d.Id IS NULL and c.BranchID in ($allowed_branch) ";
$stmt = $connect_pdo->prepare($query);
$stmt->execute();
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
$jobtitle = " SELECT Id ,Name FROM tbljobtitle  where BranchID in ($allowed_branch)";
$stmt_jobtitle = $connect_pdo->prepare($jobtitle);
$stmt_jobtitle->execute();
$results_jobtitle = $stmt_jobtitle->fetchAll();

$jobtitle_list = array();
foreach($results_jobtitle as $row)
{
$jobtitle_array = array();
$jobtitle_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$jobtitle_list[] = $jobtitle_array;
}



 

//  groub
$groub = " SELECT Id ,Name FROM tblgroup  where BranchID in ($allowed_branch)";
$stmt_group = $connect_pdo->prepare($groub);
$stmt_group->execute();
$results_groub = $stmt_group->fetchAll();

$groub_list = array();
foreach($results_groub as $row)
{
$groub_array = array();
$groub_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$groub_list[] = $groub_array;
}

//  JobGrade
$JobGrade = " SELECT Id ,Name FROM tbljobgrade where BranchID in ($allowed_branch)";
$stmt_JobGrade = $connect_pdo->prepare($JobGrade);
$stmt_JobGrade->execute();
$results_groub = $stmt_JobGrade->fetchAll();

$JobGrade_list = array();
foreach($results_groub as $row)
{
$JobGrade_array = array();
$JobGrade_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$JobGrade_list[] = $JobGrade_array;
}



//  Shift
$Shift = " SELECT ShiftID ,ShiftName FROM  tbshift  where ShiftState=0 and BranchID in ($allowed_branch)";
$stmt_Shift = $connect_pdo->prepare($Shift);
$stmt_Shift->execute();
$results_Shift = $stmt_Shift->fetchAll();

$Shift_list = array();
foreach($results_Shift as $row)
{
$Shift_array = array();
$Shift_array['data']=   array( 
'id' => $row['ShiftID'], 'name' => $row['ShiftName']); 
$Shift_list[] = $Shift_array;
}


//
//  branch  


$output = array(
 "section"    => $section,
 "jobtitle"    => $jobtitle_list,
 "groub_list"    => $groub_list,
 "JobGrade"    => $JobGrade_list,
 "Shift"    => $Shift_list,
 "branch"   =>$allowed_branches
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
