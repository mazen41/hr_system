

<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
$emp = $_POST['value']; // مصفوفة من الـ IDs
// إعداد placeholders ديناميكي
// $placeholders = implode(',', array_fill(0, count($emp), '?'));

 $query = 
"SELECT 
    u.UserID,u.isemp,u.UserEmail,u.IsDisabled,t.BranchID, CONCAT(u.FirstName, ' ', u.LastName) AS person_name, 
    b.branch_name,
    t.SectionID,t.Salary,t.jobtitleID,t.GradeID,t.shiftID,t.GroupID,t.new_s_date,t.new_e_date,
    s.Name,
    r.DueDate,r.type
FROM tblusers AS u
LEFT JOIN tblremewal t ON t.Id = u.lastversion
LEFT JOIN  tblsection s ON s.Id = t.SectionID
LEFT JOIN branches b ON b.branch_id = t.BranchID
LEFT JOIN (
    SELECT r1.*
    FROM tblresignation r1
    WHERE r1.Status IS NOT NULL
      AND r1.DueDate IS NOT NULL
      AND NOT EXISTS (
          SELECT 1 FROM tblresignation r2
          WHERE r2.UserID = r1.UserID
            AND r2.Status IS NOT NULL
            AND r2.DueDate IS NOT NULL
            AND r2.DueDate < r1.DueDate 
      )
) r ON r.UserID = u.UserID
WHERE t.state IS NOT NULL and u.manager =$emp
";


$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$section = [];

foreach ($results as $row) {
if($today_date > $row['new_e_date'])
$stat="انتهت فتره العقد";
else if(!empty($row['DueDate']) && $today_date > $row['DueDate'] && $row['type']==1)
$stat="مستقيل";
else if(!empty($row['DueDate']) && $today_date > $row['DueDate'] && $row['type']==2)
$stat="مفصول";
else if(!empty($row['IsDisabled']))
$stat="موقف";
else
  $stat="شغال";  


    $section[] = [
                            'person_name' => $row['person_name'],
                            'sectionname' => $row['Name'],
                            'salary' => $row['Salary'],
                            'branchname' => $row['branch_name'],
							'email' => $row['UserEmail'],
							'state' => $stat,
    ];
}








$output = array(
 "section"    => $section,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
