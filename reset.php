<?php
require_once 'inc/config.php'; // adjust if different

$email = 'demo@admin.com';
$newPassword = '123456';

// hash password (VERY IMPORTANT)
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $stmt = $connect_pdo->prepare("UPDATE tblusers SET Password = :password WHERE UserEmail = :email");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':email' => $email
    ]);

    if ($stmt->rowCount()) {
        echo "✅ Password updated successfully for $email";
    } else {
        echo "⚠️ User not found or already updated";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}