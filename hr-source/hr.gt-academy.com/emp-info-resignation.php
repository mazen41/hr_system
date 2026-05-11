<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
$emp = $_POST['value']; // مصفوفة من الـ IDs
$type= $_POST['type'];
// إعداد placeholders ديناميكي
// $placeholders = implode(',', array_fill(0, count($emp), '?'));

$query = " SELECT a.Id  ,a.UserID ,a.Status,a.LastUpdateDate, a.Draft,a.created_by,a.DueDate,
f.FirstName as f_name, f.LastName as l_name, ff.FirstName as ff_name, ff.LastName as ll_name
FROM   tblresignation AS a 
LEFT JOIN tblusers AS f ON f.UserID  = a.UserID
LEFT JOIN tblusers AS ff ON ff.UserID  = a.created_by
where a.UserID=$emp and a.type=$type
";


$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$section = [];
foreach ($results as $row) {
    $section[] = [
                            'id' => $row['Id'],
                            'status' => ($row['Status']==null)?'0':'1',
                            'draft___' => ($row['Draft']==null)?'0':'1',
                            'draft' => ($row['Draft']==null)?'مسودة':'تم الرفع',
							'name' => $row['f_name'].' '. $row['l_name'],
							'updated' => $User->displayDate($row['LastUpdateDate'], true),
                            'statedevice' => ($row['Status']==null)?'غير معتمد':'معتمد',
                            'name_add' => $row['ff_name'].' '. $row['ll_name'],
                            'date' => $row['DueDate'],
    ];
}








$output = array(
 "section"    => $section,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
