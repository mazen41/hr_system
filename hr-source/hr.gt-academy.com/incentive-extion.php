<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/User.php';
require_once __DIR__ . '/inc/AuditLog.php';
require_once __DIR__ . '/inc/functions.php';
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die();
}

$index = '';
$type = '';
if (isset($_POST['value'])) {
    $index = $_POST['value'];
}
if (isset($_POST["parent"])) {
    $type = $_POST["parent"];
}

$msg = '';
$result = false;
$data = array();
$data_ = array();

if (empty($_POST['BranchID'])) {
    $msg = "اختر الفرع اولا";
    $result = false;
} elseif (empty($_POST['value'])) {
    $msg = "يرجى اخيتار الفئة او المجموعه او الموظف على حسب اخيارك";
    $result = false;
} else {
    $result = true;
    $branches = $_POST['BranchID'];
    
    if (is_array($branches)) {
        $branches = implode(",", $branches);
    }
    if (is_array($index)) {
        $index = implode(",", $index);
    }

    if ($type == 2) {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name , t.GroupID
                  FROM tblusers u
                  LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                  WHERE u.BranchID IN ($branches) AND t.GroupID IN ($index)       
                  AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NULL 
                  AND '$today_date' BETWEEN t.new_s_date AND t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name , t.GroupID
                   FROM tblusers u
                   LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                   WHERE u.BranchID IN ($branches) AND t.GroupID IN ($index) 
                   AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NOT NULL 
                   OR '$today_date' NOT BETWEEN t.new_s_date AND t.new_e_date";
    } elseif ($type == 3) {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name,t.GradeID 
                  FROM tblusers u
                  LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                  WHERE u.BranchID IN ($branches) AND t.GradeID IN ($index)       
                  AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NULL 
                  AND '$today_date' BETWEEN t.new_s_date AND t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name,t.GradeID 
                   FROM tblusers u
                   LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                   WHERE u.BranchID IN ($branches) AND t.GradeID IN ($index) 
                   AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NOT NULL 
                   OR '$today_date' NOT BETWEEN t.new_s_date AND t.new_e_date";
    } elseif ($type == 4) {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.SectionID
                  FROM tblusers u
                  LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                  WHERE u.BranchID IN ($branches) AND t.SectionID IN ($index)        
                  AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NULL 
                  AND '$today_date' BETWEEN t.new_s_date AND t.new_e_date ";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.SectionID
                   FROM tblusers u
                   LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                   WHERE u.BranchID IN ($branches) AND t.SectionID IN ($index) 
                   AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NOT NULL 
                   OR '$today_date' NOT BETWEEN t.new_s_date AND t.new_e_date";
    } elseif ($type == 5) {
        $query = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.BranchID
                  FROM tblusers u
                  LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                  WHERE t.BranchID IN ($branches) AND t.jobtitleID IN ($index)         
                  AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NULL 
                  AND '$today_date' BETWEEN t.new_s_date AND t.new_e_date";

        $query_ = "SELECT u.UserID as ID,u.isemp, CONCAT(u.FirstName, ' ', u.LastName) as Name ,t.BranchID
                   FROM tblusers u
                   LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
                   WHERE t.BranchID IN ($branches) AND t.jobtitleID IN ($index)  
                   AND u.isemp IS NOT NULL AND resigned_or_dismissed IS NOT NULL 
                   OR '$today_date' NOT BETWEEN t.new_s_date AND t.new_e_date";
    }

    $stmt = $connect_pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll();

    $stmt_ = $connect_pdo->prepare($query_);
    $stmt_->execute();
    $results_ = $stmt_->fetchAll();

    foreach ($results as $row) {
        $data_array = array();
        $data_array['data'] = array('id' => $row['ID'], 'name' => $row['Name']);
        $data[] = $data_array;
    }

    foreach ($results_ as $row_) {
        $data_array_ = array();
        $data_array_['data'] = array('id' => $row_['ID'], 'name' => $row_['Name']);
        $data_[] = $data_array_;
    }
}

$output = array(
    "result" => $result,
    "data"   => $data,
    "data_"  => $data_,
    "msg"    => $msg
);

echo json_encode($output, JSON_UNESCAPED_UNICODE);
?>