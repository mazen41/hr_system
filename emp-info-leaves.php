 <?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
$emp = $_POST['value']; // مصفوفة من الـ IDs

// إعداد placeholders ديناميكي
// $placeholders = implode(',', array_fill(0, count($emp), '?'));

$query = " SELECT a.Id ,a.UserID,a.leavetype,a.day_leave ,a.status,a.LastUpdateDate, a.Draft,a.created_by,a.leave_start_date,a.leave_end_date,
f.FirstName as f_name, f.LastName as l_name,ff.FirstName as ff_name, ff.LastName as ll_name
FROM    tblleaverequest AS a 
LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
LEFT JOIN tblusers AS ff ON ff.UserID  = a.created_by
where a.UserID=$emp
";

$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$section = [];
foreach ($results as $row) {
    $section[] = [
                            'id' => $row['Id'],
                            'status' => ($row['status']==null)?'0':'1',
                            'draft___' => ($row['Draft']==null)?'0':'1',
                            'draft' => ($row['Draft']==null)?'مسودة':'تم الرفع',
                            'day_leave' => $row['day_leave'], 
							'name' => $row['f_name'].' '. $row['l_name'],
							'updated' => $User->displayDate($row['LastUpdateDate'], true),
                            'statedevice' => ($row['status']==null)?'غير معتمد':'معتمد',
                            'name_add' => $row['ff_name'].' '. $row['ll_name'],
                            'startDate' => $row['leave_start_date'],
                            'endDate' => $row['leave_end_date'],
    ];
}

function Getinfo($connect, $user)
{
    $query = "SELECT Id, SectionID, UserID, GroupID, GradeID, jobtitleID
              FROM tblremewal 
              WHERE state IS NOT NULL AND UserID = :user 
              ORDER BY Id DESC 
              LIMIT 1";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':user', $user, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function rollsList($connect, $user){
  // 1. الحصول على بيانات الموظف (GroupID, BranchID)
  $info = Getinfo($connect, $user);

  $branchID = null;
  $groupID = null;

  if ($info) {
      $BranchID = $info['BranchID'] ?? null;
      $SectionID = $info['SectionID'] ?? null;
      $groupID = $info['GroupID'] ?? null;
      $GradeID = $info['GradeID'] ?? null;
      $jobtitleID = $info['jobtitleID'] ?? null;
  } 

  // 2. جلب الإجازات بناءً على الشروط الثلاثة
  $query = "
      SELECT Id, BranchID, Name, Description, isaccept, type, state, RequiresAttachment,
             for_what, chose
      FROM leaveclassification 
      WHERE state IS NULL
      AND (
          for_what IS NULL
          OR (for_what = 1 AND chose = :userID)
          OR (for_what = 4 AND chose = :SectionID)
          OR (for_what = 2 AND chose = :groupID)
          OR (for_what = 3 AND chose = :GradeID)
          OR (for_what = 5 AND chose = :jobtitleID)
      )
      AND (BranchID = :branchID OR :branchID IS NULL)
  ";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userID', $user, PDO::PARAM_INT);
  $stmt->bindParam(':SectionID', $SectionID, PDO::PARAM_INT);
  $stmt->bindParam(':groupID', $groupID, PDO::PARAM_INT);
  $stmt->bindParam(':GradeID', $GradeID, PDO::PARAM_INT);
  $stmt->bindParam(':jobtitleID', $jobtitleID, PDO::PARAM_INT);
  $stmt->bindParam(':branchID', $branchID, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// 
$roles = rollsList($connect_pdo,$emp);







$output = array(
 "section"    => $section,
 "roles"=>$roles,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
