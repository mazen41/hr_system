 <?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
 $emp = $_POST['value'];
 $branch = $_POST['branch']; // مصفوفة من الـ IDs
 $for_whats = $_POST['for_whats'];
//  $table = $_POST['table'];
// إعداد placeholders ديناميكي     for_whats  
$placeholders = implode(',', array_fill(0, count($emp), '?'));

$query = " SELECT a.Id ,a.UserID ,b.branch_name,a.name,a.Status,a.LastUpdateDate,a.DueDate,
 a.Amount,a.Currency,u.FirstName , u.LastName,a.for_what
FROM  tbldeductions  AS a
LEFT JOIN branches AS b ON a.BranchID = b.branch_id
LEFT JOIN tblusers AS u ON a.UserID = u.UserID
where a.BranchID =$branch and a.UserID in($placeholders) and a.for_what=$for_whats
";


$stmt = $connect_pdo->prepare($query);
$stmt->execute($emp);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 
$data = array();
foreach($result as $row)
{
   
        $data[] = [
        'name' => $row['name'],
        'branch' => $row['branch_name'],
        'date' => $row['DueDate'],

        'money' => $row['Amount'] . ' ' . $row['Currency'],
        'username' => $row['FirstName'].' '.$row['LastName'],
        'check' => ($row['Status']==1)?'معتمد':'غير معتمد',
        'link' => '<a href="report-one-deductions?id='.$row['Id'].'" class="btn btn-info btn-sm show-details" data-id="'.$row['Id'].'" title="تفاصيل"><i class="fa fa-eye"></i></button>',
    ];

}
// 







$output = array(
 "section"    => $data,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
