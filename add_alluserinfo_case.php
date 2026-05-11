<?php
/**
 * Add missing allUserInfo case to hr-app/index.php
 */

$file = __DIR__ . '/hr-app/index.php';
$content = file_get_contents($file);

// The allUserInfo case code to insert
$allUserInfoCase = '
// ============================================================
// ALL USER INFO (Branch-based dropdowns for employee form)
// ============================================================
case 'allUserInfo':
    header(\'Content-Type: application/json; charset=utf-8\');
    
    $BranchID = isset($_POST[\'value\']) ? (int)$_POST[\'value\'] : 0;
    
    if ($BranchID <= 0) {
        echo json_encode([\'error\' => \'Branch ID is required\'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // section
    $query = "SELECT c.Id, c.Name FROM tblsection AS c
              LEFT JOIN tblsection AS d ON c.Id = d.ParentID
              WHERE c.ParentID IS NOT NULL AND d.Id IS NULL AND c.BranchID = :BranchID";
    $stmt = $connect_pdo->prepare($query);
    $stmt->execute([\'BranchID\' => $BranchID]);
    $section = [];
    foreach($stmt->fetchAll() as $row) {
        $section[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // jobtitle
    $jobtitle = "SELECT c.Id, c.Name FROM tbljobtitle AS c
                 LEFT JOIN tbljobtitle AS d ON c.Id = d.ParentID
                 WHERE c.ParentID IS NOT NULL AND d.Id IS NULL AND c.BranchID = :BranchID";
    $stmt_jobtitle = $connect_pdo->prepare($jobtitle);
    $stmt_jobtitle->execute([\'BranchID\' => $BranchID]);
    $jobtitle_list = [];
    foreach($stmt_jobtitle->fetchAll() as $row) {
        $jobtitle_list[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // user_related_to
    $user_relate = "SELECT UserID, FirstName, LastName FROM tblusers 
                    WHERE BranchID = :BranchID AND isemp IS NULL
                    AND UserID NOT IN (SELECT related_to FROM tblusers WHERE related_to IS NOT NULL)";
    $stmt_user_relate = $connect_pdo->prepare($user_relate);
    $stmt_user_relate->execute([\'BranchID\' => $BranchID]);
    $user_related_to_list = [];
    foreach($stmt_user_relate->fetchAll() as $row) {
        $user_related_to_list[] = [\'data\' => [\'id\' => $row[\'UserID\'], \'name\' => $row[\'FirstName\'].\' \'.$row[\'LastName\']]];
    }
    
    // userManager
    $user_manger = "SELECT UserID, FirstName, LastName FROM tblusers 
                    WHERE BranchID = :BranchID AND isemp IS NOT NULL";
    $stmt_user_manager = $connect_pdo->prepare($user_manger);
    $stmt_user_manager->execute([\'BranchID\' => $BranchID]);
    $user_manager_list = [];
    foreach($stmt_user_manager->fetchAll() as $row) {
        $user_manager_list[] = [\'data\' => [\'id\' => $row[\'UserID\'], \'name\' => $row[\'FirstName\'].\' \'.$row[\'LastName\']]];
    }
    
    // groub
    $groub = "SELECT Id, Name FROM tblgroup WHERE BranchID = :BranchID";
    $stmt_group = $connect_pdo->prepare($groub);
    $stmt_group->execute([\'BranchID\' => $BranchID]);
    $groub_list = [];
    foreach($stmt_group->fetchAll() as $row) {
        $groub_list[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // JobGrade
    $JobGrade = "SELECT Id, Name FROM tbljobgrade WHERE BranchID = :BranchID";
    $stmt_JobGrade = $connect_pdo->prepare($JobGrade);
    $stmt_JobGrade->execute([\'BranchID\' => $BranchID]);
    $JobGrade_list = [];
    foreach($stmt_JobGrade->fetchAll() as $row) {
        $JobGrade_list[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // insurance
    $insurance = "SELECT Id, BranchID, Name FROM tbinsurance WHERE BranchID = :BranchID AND state=1";
    $stmt_insurance = $connect_pdo->prepare($insurance);
    $stmt_insurance->execute([\'BranchID\' => $BranchID]);
    $insurance_list = [];
    foreach($stmt_insurance->fetchAll() as $row) {
        $insurance_list[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // Shift
    $Shift = "SELECT ShiftID, ShiftName FROM tbshift WHERE BranchID = :BranchID AND ShiftState=0";
    $stmt_Shift = $connect_pdo->prepare($Shift);
    $stmt_Shift->execute([\'BranchID\' => $BranchID]);
    $Shift_list = [];
    foreach($stmt_Shift->fetchAll() as $row) {
        $Shift_list[] = [\'data\' => [\'id\' => $row[\'ShiftID\'], \'name\' => $row[\'ShiftName\']]];
    }
    
    // tblemploymenttype
    $tblemploymenttype = "SELECT Id, Name FROM tblemploymenttype WHERE BranchID = :BranchID";
    $stmt_tblemploymenttype = $connect_pdo->prepare($tblemploymenttype);
    $stmt_tblemploymenttype->execute([\'BranchID\' => $BranchID]);
    $tblemploymenttype_list = [];
    foreach($stmt_tblemploymenttype->fetchAll() as $row) {
        $tblemploymenttype_list[] = [\'data\' => [\'id\' => $row[\'Id\'], \'name\' => $row[\'Name\']]];
    }
    
    // fingerprint
    $fingerprint = "SELECT FingerprintID, FingerprintName FROM tbfingerprint WHERE BranchID = :BranchID AND FingerprintState=1";
    $stmt_fingerprint = $connect_pdo->prepare($fingerprint);
    $stmt_fingerprint->execute([\'BranchID\' => $BranchID]);
    $fingerprint_list = [];
    foreach($stmt_fingerprint->fetchAll() as $row) {
        $fingerprint_list[] = [\'data\' => [\'id\' => $row[\'FingerprintID\'], \'name\' => $row[\'FingerprintName\']]];
    }
    
    $output = [
        "section" => $section,
        "jobtitle" => $jobtitle_list,
        "groub" => $groub_list,
        "JobGrade" => $JobGrade_list,
        "insurance" => $insurance_list,
        "Shift" => $Shift_list,
        "tblemploymenttype" => $tblemploymenttype_list,
        "fingerprint" => $fingerprint_list,
        "user_related_to" => $user_related_to_list,
        "user_manager" => $user_manager_list
    ];
    
    echo json_encode($output, JSON_UNESCAPED_UNICODE);
    exit;

';

// Find the position after allUserinfo_Search case and before reveal-attendance
$search = "case 'reveal-attendance':";
$pos = strpos($content, $search);

if ($pos !== false) {
    // Insert before reveal-attendance
    $newContent = substr($content, 0, $pos) . $allUserInfoCase . substr($content, $pos);
    file_put_contents($file, $newContent);
    echo "allUserInfo case added successfully!\n";
} else {
    echo "Could not find insertion point\n";
}
