<?php
//session_start();
 ($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();   
 if(isset($_POST['value'])) 
$laeve = $_POST['value']; // مصفوفة من الـ IDs


$query = "SELECT ShiftID , ShiftName, ShiftStartTime, ShiftEndTime, NumFootprint
FROM tbshift 

WHERE ShiftID =$laeve";

$stmt = $connect_pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetch(PDO::FETCH_ASSOC);

$leaves = [];
    $leaves[] = [
        'ShiftID' => $results['ShiftID'],
        'shiftname' => $results['ShiftName'],
        'ShiftStartTime' => $results['ShiftStartTime'],
        'ShiftEndTime' => $results['ShiftEndTime'],
        'NumFootprint' => $results['NumFootprint']
    ];








$output = array(
 "leaves"    => $leaves,
);

echo json_encode($output,JSON_UNESCAPED_UNICODE);
//}
?>
