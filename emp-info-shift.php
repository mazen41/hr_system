<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
$emp = $_POST['value']; // مصفوفة من الـ IDs
$date= $_POST['date']; 
// إعداد placeholders ديناميكي
$placeholders = implode(',', array_fill(0, count($emp), '?'));

$query = "SELECT u.UserID, CONCAT(u.FirstName, ' ', u.LastName) as emp_name,
t.shiftID, s.ShiftName, s.NumFootprint,ss.start_time,ss.end_time
FROM tblusers AS u
LEFT JOIN tblremewal AS t ON u.lastversion = t.Id
LEFT JOIN tbshift AS s ON FIND_IN_SET(s.ShiftID, t.shiftID)
LEFT JOIN shifts_schedule AS ss ON ss.shift_id= s.shiftID
AND '$date' BETWEEN ss.start_date AND ss.end_date

WHERE u.UserID IN ($placeholders) order by u.UserID";

$stmt = $connect_pdo->prepare($query);
$stmt->execute($emp);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$section = [];
foreach ($results as $row) {
    $section[] = [
        'name' => $row['emp_name'],
        'shiftname' => $row['ShiftName'],
        'ShiftStartTime' => $row['start_time'],
        'ShiftEndTime' => $row['end_time'],
        'NumFootprint' => $row['NumFootprint']==1?'حضور فقط':'حضور وانصراف'
    ];
}








$output = array(
 "section"    => $section,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
