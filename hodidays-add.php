<?php
($_SERVER['REQUEST_METHOD'] == 'POST') ? "":die();


$result = true;
$msg = '';

$post_id             = isset($_POST["id"]) ? (int)$_POST["id"]: NULL;
$action              = !empty($post_id) ? 'edit': 'add';

if(empty($_POST["H_name"])){
    $result = false; 
    $msg = 'يرجى إدخال اسم العطلة ';
}elseif(empty($_POST["branchs_list"])){
    $result = false;
    $msg = 'يرجى تحديد الفرع';   
}
elseif(empty($_POST["form_date"])){
    $result = false;
    $msg = 'يرجى تحديد تاريخ البداية';   
}
elseif(empty($_POST["until_date"])){
    $result = false;
    $msg = 'يرجى تحديد تاريخ النهاية';   
}
else{
		
		
$name 	        = sanitizingData($_POST["H_name"]);
$branch 	        = $_POST["branchs_list"];
$form_date 	        = sanitizingData($_POST["form_date"]);
$until_date 	        = sanitizingData($_POST["until_date"]);

function GetlastID($connect)
{
    $sql = "SELECT Holiday_ID FROM holidays ORDER BY Holiday_ID DESC LIMIT 1";
$result = $connect->query($sql);

if ($result->rowCount() > 0) {
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $lastHolidayID = $row['Holiday_ID'];
    $newHolidayID = $lastHolidayID + 1;
} else {
    $newHolidayID = 1; 
}
return $newHolidayID;
}


$lastID=GetlastID($connect_pdo); 
function createNewholidays($connect, $branches, $lastID, $name, $form_date, $until_date, $user, $today_date, $now_date) {
    $tableData = json_decode($_POST['tableData'], true);

    if (!empty($tableData)) {
        try {
            // تحقق من التاريخ قبل الإدخال
            $checkSql = "SELECT COUNT(*) FROM holidays_day WHERE HolidayID = :holidayID AND Date = :date";
            $checkStmt = $connect->prepare($checkSql);

            $insertSql = "INSERT INTO holidays_day (HolidayID, Description, Date, CreatedDate, LastUpdateDate)
                          VALUES (:holidayID, :description, :date, :createdDate, :lastUpdateDate)";
            $insertStmt = $connect->prepare($insertSql);

            foreach ($tableData as $row) {
                $date = $row['date'];
                $description = $row['description'];

                // تحقق أولاً
                $checkStmt->execute([
                    ':holidayID' => $lastID,
                    ':date' => $date
                ]);
                $exists = $checkStmt->fetchColumn();

                if ($exists == 0) {
                    // إذا التاريخ غير موجود، أضفه
                    $insertStmt->execute([
                        ':holidayID' => $lastID,
                        ':description' => $description,
                        ':date' => $date,
                        ':createdDate' => $today_date,
                        ':lastUpdateDate' => $now_date
                    ]);
                }
            }

            $result = true;
            $msg = "تمت إضافة الأيام بنجاح (مع تجاهل المكررات).";
        } catch (PDOException $e) {
            $result = false;
            $msg = "حدث خطأ أثناء إدخال بيانات الأيام: " . $e->getMessage();
        }
    }

    // إضافة الفروع إلى جدول holidays
    foreach ($branches as $branch) {
        $query = "INSERT INTO holidays SET
            BranchID = :BranchID,
            Holiday_ID = :Holiday_ID,
            Name = :Name,
            Start_date = :Start_date,
            End_date = :End_date,
            CreatedBy = :CreatedBy,
            CreatedDate = :CreatedDate,
            LastUpdateDate = :LastUpdateDate";

        $stm = $connect->prepare($query);
        $stm->execute([
            'BranchID' => sanitizingData($branch),
            'Holiday_ID' => $lastID,
            'Name' => $name,
            'Start_date' => $form_date,
            'End_date' => $until_date,
            'CreatedBy' => $user,
            'CreatedDate' => $today_date,
            'LastUpdateDate' => $now_date
        ]);
    }

    $created_id = $connect->lastInsertId();
    if ($created_id > 0) {
        return $created_id;
    }
    return false;
}




        
    function updateholidays($connect, $branches, $lastID, $name, $form_date, $until_date, $user, $today_date, $now_date, $post_id) {
    // استرجاع Holiday_ID
    $sql = "SELECT Holiday_ID FROM holidays WHERE Id = :id";
    $stmt = $connect->prepare($sql);
    $stmt->execute([':id' => $post_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return false;
    }

    $lastHolidayID = $row['Holiday_ID'];

    // قراءة البيانات القادمة من الـ POST
    $tableData = json_decode($_POST['tableData'], true);

    if (!empty($tableData)) {
        try {
            // تجهيز الاستعلامات
            $checkSql = "SELECT COUNT(*) FROM holidays_day WHERE HolidayID = :holidayID AND Date = :date";
            $checkStmt = $connect->prepare($checkSql);

            $insertSql = "INSERT INTO holidays_day (HolidayID, Description, Date, CreatedDate, LastUpdateDate)
                          VALUES (:holidayID, :description, :date, :createdDate, :lastUpdateDate)";
            $insertStmt = $connect->prepare($insertSql);

            foreach ($tableData as $row) {
                $date = $row['date'];
                $description = $row['description'];

                // تحقق من أن التاريخ غير مكرر
                $checkStmt->execute([
                    ':holidayID' => $lastHolidayID,
                    ':date' => $date
                ]);
                $exists = $checkStmt->fetchColumn();

                if ($exists == 0) {
                    $insertStmt->execute([
                        ':holidayID' => $lastHolidayID,
                        ':description' => $description,
                        ':date' => $date,
                        ':createdDate' => $today_date,
                        ':lastUpdateDate' => $now_date
                    ]);
                }
            }

            $msg = "تم تحديث الأيام (مع تجاهل المكررات).";
        } catch (PDOException $e) {
            $msg = "حدث خطأ أثناء إدخال بيانات الأيام: " . $e->getMessage();
        }
    }

    // تحديث بيانات العطلة نفسها
    $query = "UPDATE holidays SET  
        BranchID = :BranchID,
        Name = :Name,
        Start_date = :Start_date,
        End_date = :End_date,
        CreatedBy = :CreatedBy,
        CreatedDate = :CreatedDate,           
        LastUpdateDate = :LastUpdateDate
        WHERE Id = :id";

    $stm = $connect->prepare($query);
    $stm->execute([
        ':id' => $post_id,
        ':BranchID' => $branches[0],
        ':Name' => $name,
        ':Start_date' => $form_date,
        ':End_date' => $until_date,
        ':CreatedBy' => $user,
        ':CreatedDate' => $today_date,
        ':LastUpdateDate' => $now_date
    ]);

    return $stm->rowCount() > 0;
}

        
		if($action=="add" && $result)
		{
		  $post_id=  createNewholidays($connect_pdo,$branch,$lastID,$name,$form_date,$until_date,$user,$today_date,$now_date);
          
		} 
		if($action=="edit" && $result)
		{
		  $id_reslut= updateholidays($connect_pdo,$branch,$lastID,$name,$form_date,$until_date,$user,$today_date,$now_date,$post_id);
		}       
          
	}

$output = array(
	"result"    => $result,
	"id"        => !empty($post_id) ? $post_id : '',
	
	"msg"       => $msg
);


echo(json_encode($output,JSON_UNESCAPED_UNICODE));




?>