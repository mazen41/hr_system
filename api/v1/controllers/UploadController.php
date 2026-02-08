<?php
/**
 * Vision HR - Upload Controller
 * File upload for leave requests, advances, and other attachments
 */

class UploadController
{
    /**
     * POST /upload/leave/:id
     * Upload attachment for a leave request
     */
    public static function leaveAttachment(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        // Verify ownership
        $stm = $connect_pdo->prepare("SELECT Id, UserID, Status FROM tblleave WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $leave = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$leave) {
            Response::notFound('طلب الإجازة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $leave['UserID']);

        $result = self::handleUpload('leaves');
        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }

        // Update leave record
        $connect_pdo->prepare("UPDATE tblleave SET attachment = :file, LastUpdateDate = NOW() WHERE Id = :id")
            ->execute([':file' => $result['filename'], ':id' => $id]);

        $auditLog->log($apiUser['id'], 'upload', 'tblleave', $id, null, ['attachment' => $result['filename']]);

        Response::success([
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ], 'تم رفع المرفق بنجاح');
    }

    /**
     * POST /upload/advance/:id
     * Upload attachment for an advance request
     */
    public static function advanceAttachment(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare("SELECT Id, UserID FROM tblempadvances WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $advance = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$advance) {
            Response::notFound('طلب السلفة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $advance['UserID']);

        $result = self::handleUpload('advances');
        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }

        $connect_pdo->prepare("UPDATE tblempadvances SET attachment = :file, LastUpdateDate = NOW() WHERE Id = :id")
            ->execute([':file' => $result['filename'], ':id' => $id]);

        $auditLog->log($apiUser['id'], 'upload', 'tblempadvances', $id, null, ['attachment' => $result['filename']]);

        Response::success([
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ], 'تم رفع المرفق بنجاح');
    }

    /**
     * POST /upload/order/:id
     * Upload attachment for an employee order
     */
    public static function orderAttachment(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare("SELECT Id, UserID FROM tblorders WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $order = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            Response::notFound('الأمر غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $order['UserID']);

        $result = self::handleUpload('orders');
        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }

        $connect_pdo->prepare("UPDATE tblorders SET attachment = :file, LastUpdateDate = NOW() WHERE Id = :id")
            ->execute([':file' => $result['filename'], ':id' => $id]);

        $auditLog->log($apiUser['id'], 'upload', 'tblorders', $id, null, ['attachment' => $result['filename']]);

        Response::success([
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ], 'تم رفع المرفق بنجاح');
    }

    /**
     * POST /upload/resignation/:id
     * Upload attachment for a resignation
     */
    public static function resignationAttachment(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare("SELECT Id, UserID FROM tblresignation WHERE Id = :id LIMIT 1");
        $stm->execute([':id' => $id]);
        $resignation = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$resignation) {
            Response::notFound('طلب الاستقالة غير موجود');
        }

        requireOwnerOrAdmin($apiUser, (int) $resignation['UserID']);

        $result = self::handleUpload('resignations');
        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }

        $connect_pdo->prepare("UPDATE tblresignation SET attachment = :file, LastUpdateDate = NOW() WHERE Id = :id")
            ->execute([':file' => $result['filename'], ':id' => $id]);

        $auditLog->log($apiUser['id'], 'upload', 'tblresignation', $id, null, ['attachment' => $result['filename']]);

        Response::success([
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ], 'تم رفع المرفق بنجاح');
    }

    /**
     * POST /upload/profile-photo
     * Upload profile photo
     */
    public static function profilePhoto(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $result = self::handleUpload('photos', ['jpg', 'jpeg', 'png', 'webp']);
        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }

        $connect_pdo->prepare("UPDATE tblusers SET Photo = :file WHERE UserID = :uid")
            ->execute([':file' => $result['filename'], ':uid' => $apiUser['id']]);

        $auditLog->log($apiUser['id'], 'upload', 'tblusers', $apiUser['id'], null, ['photo' => $result['filename']]);

        Response::success([
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ], 'تم تحديث الصورة الشخصية بنجاح');
    }

    // ---- Private helpers ----

    /**
     * Handle file upload from $_FILES
     */
    private static function handleUpload(string $subfolder, ?array $allowedExt = null): array
    {
        if (empty($_FILES['file'])) {
            return ['error' => 'لم يتم اختيار ملف'];
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'حجم الملف يتجاوز الحد المسموح',
                UPLOAD_ERR_FORM_SIZE  => 'حجم الملف يتجاوز الحد المسموح',
                UPLOAD_ERR_PARTIAL    => 'لم يتم رفع الملف بالكامل',
                UPLOAD_ERR_NO_FILE    => 'لم يتم اختيار ملف',
                UPLOAD_ERR_NO_TMP_DIR => 'خطأ في الخادم',
                UPLOAD_ERR_CANT_WRITE => 'خطأ في الكتابة',
            ];
            return ['error' => $errors[$file['error']] ?? 'خطأ في رفع الملف'];
        }

        $maxSize = defined('API_MAX_UPLOAD_SIZE') ? API_MAX_UPLOAD_SIZE : 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['error' => 'حجم الملف كبير جداً. الحد الأقصى: ' . ($maxSize / 1024 / 1024) . 'MB'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $allowedExt ?? (defined('API_ALLOWED_EXTENSIONS') ? API_ALLOWED_EXTENSIONS : ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx']);

        if (!in_array($ext, $allowed)) {
            return ['error' => 'نوع الملف غير مسموح. الأنواع المسموحة: ' . implode(', ', $allowed)];
        }

        $uploadDir = dirname(__DIR__, 2) . '/uploads/' . $subfolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('vhr_') . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['error' => 'فشل في حفظ الملف'];
        }

        return [
            'filename' => $subfolder . '/' . $filename,
            'size'     => $file['size'],
            'ext'      => $ext,
        ];
    }
}
