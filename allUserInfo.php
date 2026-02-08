<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
    $BranchID = $_POST['value'];
// section
$query = " SELECT c.Id, c.Name
FROM tblsection AS c
LEFT JOIN tblsection AS d ON c.Id = d.ParentID
WHERE c.ParentID IS NOT NULL
AND d.Id IS NULL and c.BranchID = :BranchID ";
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
// $jobtitle = " SELECT Id ,Name FROM tbljobtitle where BranchID = :BranchID";
$jobtitle = " SELECT c.Id, c.Name
FROM tbljobtitle AS c
LEFT JOIN tbljobtitle AS d ON c.Id = d.ParentID
WHERE c.ParentID IS NOT NULL
AND d.Id IS NULL and c.BranchID = :BranchID ";
// 
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


// user_related_to
$user_relate = "SELECT UserID ,FirstName,LastName FROM tblusers 
        where BranchID = :BranchID
        and isemp is  null
        and UserID Not in(SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)";
$stmt_user_relate = $connect_pdo->prepare($user_relate);
$stmt_user_relate->execute(['BranchID' => $BranchID]);
$results_user_relate = $stmt_user_relate->fetchAll();

$user_related_to_list = array();
foreach($results_user_relate as $row)
{
$user_relate_array = array();
$user_relate_array['data']=   array( 
'id' => $row['UserID'], 'name' => $row['FirstName'].' '.$row['LastName']); 
$user_related_to_list[] = $user_relate_array;
}



// userMAnager
$user_manger = "SELECT UserID ,FirstName,LastName FROM tblusers 
        where BranchID = :BranchID and isemp is not  null";
$stmt_user_manager = $connect_pdo->prepare($user_manger);
$stmt_user_manager->execute(['BranchID' => $BranchID]);
$results_user_manger = $stmt_user_manager->fetchAll();

$user_manager_list = array();
foreach($results_user_manger as $row)
{
$user_manager_array = array();
$user_manager_array['data']=   array( 
'id' => $row['UserID'], 'name' => $row['FirstName'].' '.$row['LastName']); 
$user_manager_list[] = $user_manager_array;
}
// 

//  groub
$groub = " SELECT Id ,Name FROM tblgroup where BranchID = :BranchID";
$stmt_group = $connect_pdo->prepare($groub);
$stmt_group->execute(['BranchID' => $BranchID]);
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
$JobGrade = " SELECT Id ,Name FROM tbljobgrade where BranchID = :BranchID";
$stmt_JobGrade = $connect_pdo->prepare($JobGrade);
$stmt_JobGrade->execute(['BranchID' => $BranchID]);
$results_groub = $stmt_JobGrade->fetchAll();

$JobGrade_list = array();
foreach($results_groub as $row)
{
$JobGrade_array = array();
$JobGrade_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$JobGrade_list[] = $JobGrade_array;
}

//  insurance
$insurance = " SELECT Id ,BranchID, Name FROM  tbinsurance  where BranchID = :BranchID and state=1";
$stmt_insurance = $connect_pdo->prepare($insurance);
$stmt_insurance->execute(['BranchID' => $BranchID]);
$results_insurance = $stmt_insurance->fetchAll();

$insurance_list = array();
foreach($results_insurance as $row)
{
$insurance_array = array();
$insurance_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$insurance_list[] = $insurance_array;
}

//  Shift
$Shift = " SELECT ShiftID ,ShiftName FROM  tbshift  where BranchID = :BranchID and ShiftState=0";
$stmt_Shift = $connect_pdo->prepare($Shift);
$stmt_Shift->execute(['BranchID' => $BranchID]);
$results_Shift = $stmt_Shift->fetchAll();

$Shift_list = array();
foreach($results_Shift as $row)
{
$Shift_array = array();
$Shift_array['data']=   array( 
'id' => $row['ShiftID'], 'name' => $row['ShiftName']); 
$Shift_list[] = $Shift_array;
}

//  tblemploymenttype
$tblemploymenttype = " SELECT Id ,Name FROM  tblemploymenttype where BranchID = :BranchID";
$stmt_tblemploymenttype = $connect_pdo->prepare($tblemploymenttype);
$stmt_tblemploymenttype->execute(['BranchID' => $BranchID]);
$results_tblemploymenttype = $stmt_tblemploymenttype->fetchAll();

$tblemploymenttype_list = array();
foreach($results_tblemploymenttype as $row)
{
$tblemploymenttype_array = array();
$tblemploymenttype_array['data']=   array( 
'id' => $row['Id'], 'name' => $row['Name']); 
$tblemploymenttype_list[] = $tblemploymenttype_array;
}
//  

//  fingerprint
$fingerprint = " SELECT FingerprintID ,FingerprintName FROM  tbfingerprint  where BranchID = :BranchID and FingerprintState=1";
$stmt_fingerprint = $connect_pdo->prepare($fingerprint);
$stmt_fingerprint->execute(['BranchID' => $BranchID]);
$results_fingerprint = $stmt_fingerprint->fetchAll();

$fingerprint_list = array();
foreach($results_fingerprint as $row)
{
$fingerprint_array = array();
$fingerprint_array['data']=   array( 
'id' => $row['FingerprintID'], 'name' => $row['FingerprintName']); 
$fingerprint_list[] = $fingerprint_array;
}
// $query1 = '';

// $statement = $connect_pdo->prepare($query);

// $statement->execute($parma);

// $number_filter_row = $statement->rowCount();

// $statement = $connect_pdo->prepare($query . $query1);

// $statement->execute($parma);

// $result = $statement->fetchAll();
// $data = array();


$output = array(
 "section"    => $section,
 "jobtitle"    => $jobtitle_list,
 "groub"    => $groub_list,
 "JobGrade"    => $JobGrade_list,
 "insurance"    => $insurance_list,
 "Shift"    => $Shift_list,
 "tblemploymenttype"    => $tblemploymenttype_list,
 "fingerprint"    => $fingerprint_list,
 "user_related_to" =>$user_related_to_list,
 "user_manager" =>$user_manager_list
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
