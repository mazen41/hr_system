<?php
// Ensure this file is only accessed via POST requests
($_SERVER['REQUEST_METHOD'] == 'POST') ? "" : die('Invalid request method.');

// Assuming $connect_pdo, $user, $branch, $today_date, $now_date, and sanitizingData function are available from your included files or global scope.
// If not, you need to ensure they are defined or included here.
// Example:
// include_once('../../config/database.php'); // Adjust path as necessary for $connect_pdo
// include_once('../../inc/functions.php'); // Adjust path as necessary for sanitizingData
// session_start();
// $user = $_SESSION['user']['id'] ?? 1; // Default user ID if not set
// $branch = $_SESSION['user']['BranchID'] ?? 1; // Default branch ID if not set
// $today_date = date('Y-m-d');
// $now_date = date('Y-m-d H:i:s');


$result = true;
$msg = '';
$post_id = isset($_POST["id"]) ? (int)$_POST["id"] : NULL; // This `id` comes from the AJAX data
$action = !empty($post_id) ? 'edit' : 'add';

// Retrieve values directly from $_POST with their correct names from the frontend
$empId   = (!empty($_POST['UserID']) ? (int)sanitizingData($_POST['UserID']) : NULL);
$dueDate = (!empty($_POST['DueDate']) ? trim(sanitizingData($_POST['DueDate'])) : NULL);
$reason  = (!empty($_POST['Reason']) ? trim(sanitizingData($_POST['Reason'])) : NULL);

// Basic validation
if (empty($empId)) {
    $result = false;
    $msg = 'الرجاء اختيار الموظف';
} elseif (empty($reason)) {
    $result = false;
    $msg = 'يرجى إدخال سبب الاستقالة';
} elseif (empty($dueDate)) {
    $result = false;
    $msg = 'يرجى تحديد تاريخ الاستقالة';
} else {
    function createNewResignation($connect, $empId, $branch, $dueDate, $reason, $createdBy, $createdDate, $lastUpdateDate) {
        $query = "INSERT INTO tblresignation SET
            UserID = :UserID,
            BranchID = :BranchID,
            DueDate = :DueDate,
            Reason = :Reason,
            type = :type,
            created_by = :created_by,
            CreatedDate = :CreatedDate,
            LastUpdateDate = :LastUpdateDate,
            Status = :Status,           -- Assuming new records are draft by default
            Draft = :Draft              -- Draft status (1 for draft)
        ";
        $stm = $connect->prepare($query);
        $stm->execute(
            array(
                'UserID' => $empId,
                'BranchID' => $branch,
                'DueDate' => $dueDate,
                'Reason' => $reason,
                'type' => 1, // Assuming type 1 for resignation
                'created_by' => $createdBy,
                'CreatedDate' => $createdDate,
                'LastUpdateDate' => $lastUpdateDate,
                'Status' => 0, // 0 for pending/draft based on your system
                'Draft' => 1 // 1 for draft
            )
        );
        $created_id = $connect->lastInsertId();
        return ($created_id > 0) ? $created_id : false;
    }

    function updateResignation($connect, $empId, $branch, $dueDate, $reason, $createdBy, $createdDate, $lastUpdateDate, $post_id) {
        $query = "UPDATE tblresignation SET
            UserID = :UserID,
            BranchID = :BranchID,
            DueDate = :DueDate,
            Reason = :Reason,
            created_by = :created_by,           -- Might not want to update created_by on edit
            CreatedDate = :CreatedDate,         -- Might not want to update CreatedDate on edit
            LastUpdateDate = :LastUpdateDate    -- This should definitely be updated
            WHERE Id = :id
        ";
        $stm = $connect->prepare($query);
        $stm->execute(
            array(
                'id' => $post_id,
                'UserID' => $empId,
                'BranchID' => $branch,
                'DueDate' => $dueDate,
                'Reason' => $reason,
                'created_by' => $createdBy, // Re-setting or keeping original, depending on business logic
                'CreatedDate' => $createdDate, // Re-setting or keeping original
                'LastUpdateDate' => $lastUpdateDate
            )
        );
        return ($stm->rowCount() > 0) ? true : false;
    }

    if ($action == "add") {
        $post_id = createNewResignation($connect_pdo, $empId, $branch, $dueDate, $reason, $user, $today_date, $now_date);
        if ($post_id) {
            $msg = 'تم إضافة طلب الاستقالة بنجاح';
        } else {
            $result = false;
            $msg = 'فشل في إضافة طلب الاستقالة';
        }
    } elseif ($action == "edit") {
        $id_result = updateResignation($connect_pdo, $empId, $branch, $dueDate, $reason, $user, $today_date, $now_date, $post_id);
        if ($id_result) {
            $msg = 'تم تحديث طلب الاستقالة بنجاح';
        } else {
            $result = false;
            $msg = 'فشل في تحديث طلب الاستقالة (أو لم يتم تغيير البيانات)';
        }
    }
}

$output = array(
    "result"    => $result,
    "id"        => !empty($post_id) ? $post_id : '',
    "msg"       => $msg
);

echo(json_encode($output, JSON_UNESCAPED_UNICODE));
?>