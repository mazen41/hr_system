<?php
session_start();
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'change-user-pass':
        $userId = $_POST['user'] ?? null;
        $newPass = $_POST['new_pass'] ?? null;
        if ($userId && $newPass) {
            $stmt = $connect_pdo->prepare("UPDATE tblusers SET UserPassword = :p WHERE UserID = :id");
            $stmt->execute([':p' => $newPass, ':id' => $userId]);
            echo json_encode(['result' => true, 'msg' => 'تم تغيير كلمة المرور بنجاح'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['result' => false, 'msg' => 'بيانات غير صحيحة'], JSON_UNESCAPED_UNICODE);
        }
        break;
    default:
        echo json_encode(['result' => false, 'msg' => 'Action not found'], JSON_UNESCAPED_UNICODE);
        break;
}
