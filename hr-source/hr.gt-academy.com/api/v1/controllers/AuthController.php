<?php
/**
 * Vision HR - Auth Controller
 * Handles login, token refresh, logout, and current user info
 */

class AuthController
{
    /**
     * POST /auth/login
     * Authenticate user and return JWT tokens
     */
    public static function login(): void
    {
        global $connect_pdo, $jwt, $auditLog;

        $body = getRequestBody();
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = $body['password'] ?? '';

        // Rate limit check
        loginRateLimitMiddleware($email);

        // Validate input
        $v = new Validator($body);
        $v->required('email', 'البريد الإلكتروني')
          ->email('email', 'البريد الإلكتروني')
          ->required('password', 'كلمة المرور');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        // Find user
        $stm = $connect_pdo->prepare(
            "SELECT UserID, UserEmail, Password, FirstName, SecondName, LastName,
                    UserGroupID, IsDisabled, IsSystem, AllowedBranches, Photo, Phone, isemp
             FROM tblusers
             WHERE UserEmail = :email
             LIMIT 1"
        );
        $stm->execute([':email' => $email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            recordFailedLogin($email);
            $auditLog->logLogin(null, false, $email);
            Response::error('بيانات الدخول غير صحيحة', 401);
        }

        if (!empty($user['IsDisabled'])) {
            $auditLog->logLogin((int) $user['UserID'], false, $email);
            Response::error('هذا الحساب موقف', 403);
        }

        // Verify password (supports bcrypt + legacy plain text)
        $validPassword = Security::verifyPassword($password, $user['Password'] ?? '');

        if (!$validPassword) {
            recordFailedLogin($email);
            $auditLog->logLogin((int) $user['UserID'], false, $email);
            Response::error('بيانات الدخول غير صحيحة', 401);
        }

        // Rehash password if needed (migrate plain text → bcrypt)
        if (Security::needsRehash($user['Password'])) {
            $newHash = Security::hashPassword($password);
            $stm2 = $connect_pdo->prepare("UPDATE tblusers SET Password = :hash WHERE UserID = :id");
            $stm2->execute([':hash' => $newHash, ':id' => $user['UserID']]);
        }

        $userId = (int) $user['UserID'];

        // Generate tokens
        $accessToken = $jwt->generateAccessToken($userId, [
            'email'    => $user['UserEmail'],
            'name'     => $user['FirstName'] . ' ' . ($user['LastName'] ?? ''),
            'is_admin' => !empty($user['IsSystem']),
        ]);

        $refreshData = $jwt->generateRefreshToken($userId);

        // Store refresh token hash in DB
        $stm3 = $connect_pdo->prepare(
            "INSERT INTO jwt_refresh_tokens (user_id, token_hash, device_info, ip_address, expires_at)
             VALUES (:uid, :hash, :device, :ip, :expires)"
        );
        $stm3->execute([
            ':uid'     => $userId,
            ':hash'    => $refreshData['hash'],
            ':device'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ':expires' => $refreshData['expires_at'],
        ]);

        // Clear rate limit on success
        clearLoginRateLimit($email);

        // Log successful login
        $auditLog->logLogin($userId, true);

        // Get contract info for response
        $stm4 = $connect_pdo->prepare(
            "SELECT r.BranchID, r.Salary, r.Currency, r.new_s_date, r.new_e_date,
                    s.Name as SectionName, jt.Name as JobTitleName, sh.ShiftName
             FROM tblremewal r
             LEFT JOIN tblsection s ON s.Id = r.SectionID
             LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
             LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
             WHERE r.UserID = :uid AND r.state IS NOT NULL
             ORDER BY r.Id DESC LIMIT 1"
        );
        $stm4->execute([':uid' => $userId]);
        $contract = $stm4->fetch(PDO::FETCH_ASSOC);

        Response::success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshData['token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_TTL,
            'user' => [
                'id'         => $userId,
                'email'      => $user['UserEmail'],
                'name'       => trim($user['FirstName'] . ' ' . ($user['LastName'] ?? '')),
                'first_name' => $user['FirstName'],
                'last_name'  => $user['LastName'],
                'photo'      => $user['Photo'],
                'phone'      => $user['Phone'],
                'is_admin'   => !empty($user['IsSystem']),
                'branch_id'  => (int) ($contract['BranchID'] ?? 1),
                'job_title'  => $contract['JobTitleName'] ?? null,
                'section'    => $contract['SectionName'] ?? null,
                'shift'      => $contract['ShiftName'] ?? null,
            ],
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * POST /auth/refresh
     * Refresh access token using refresh token
     */
    public static function refresh(): void
    {
        global $connect_pdo, $jwt;

        $body = getRequestBody();
        $refreshToken = $body['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            Response::error('رمز التحديث مطلوب', 400);
        }

        // Validate refresh token
        $userId = $jwt->validateRefreshToken($refreshToken);
        if (!$userId) {
            Response::unauthorized('رمز التحديث غير صالح أو منتهي الصلاحية');
        }

        // Check if token hash exists and is not revoked
        $tokenHash = hash('sha256', $refreshToken);
        $stm = $connect_pdo->prepare(
            "SELECT id, user_id FROM jwt_refresh_tokens
             WHERE token_hash = :hash AND revoked = 0 AND expires_at > NOW()
             LIMIT 1"
        );
        $stm->execute([':hash' => $tokenHash]);
        $tokenRow = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            Response::unauthorized('رمز التحديث غير صالح أو تم إلغاؤه');
        }

        // Revoke old refresh token
        $stm2 = $connect_pdo->prepare("UPDATE jwt_refresh_tokens SET revoked = 1 WHERE id = :id");
        $stm2->execute([':id' => $tokenRow['id']]);

        // Check user still active
        $stm3 = $connect_pdo->prepare(
            "SELECT UserID, UserEmail, FirstName, LastName, IsDisabled, IsSystem
             FROM tblusers WHERE UserID = :uid LIMIT 1"
        );
        $stm3->execute([':uid' => $userId]);
        $user = $stm3->fetch(PDO::FETCH_ASSOC);

        if (!$user || !empty($user['IsDisabled'])) {
            Response::unauthorized('الحساب غير نشط');
        }

        // Generate new tokens
        $newAccessToken = $jwt->generateAccessToken($userId, [
            'email'    => $user['UserEmail'],
            'name'     => $user['FirstName'] . ' ' . ($user['LastName'] ?? ''),
            'is_admin' => !empty($user['IsSystem']),
        ]);

        $newRefreshData = $jwt->generateRefreshToken($userId);

        // Store new refresh token
        $stm4 = $connect_pdo->prepare(
            "INSERT INTO jwt_refresh_tokens (user_id, token_hash, device_info, ip_address, expires_at)
             VALUES (:uid, :hash, :device, :ip, :expires)"
        );
        $stm4->execute([
            ':uid'     => $userId,
            ':hash'    => $newRefreshData['hash'],
            ':device'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ':expires' => $newRefreshData['expires_at'],
        ]);

        Response::success([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshData['token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JWT_ACCESS_TTL,
        ], 'تم تحديث الرمز بنجاح');
    }

    /**
     * POST /auth/logout
     * Revoke refresh token
     */
    public static function logout(): void
    {
        global $connect_pdo, $auditLog;

        $apiUser = authMiddleware();

        $body = getRequestBody();
        $refreshToken = $body['refresh_token'] ?? '';

        if (!empty($refreshToken)) {
            $tokenHash = hash('sha256', $refreshToken);
            $stm = $connect_pdo->prepare(
                "UPDATE jwt_refresh_tokens SET revoked = 1 WHERE token_hash = :hash AND user_id = :uid"
            );
            $stm->execute([':hash' => $tokenHash, ':uid' => $apiUser['id']]);
        }

        // Optionally revoke all tokens for this user
        if (!empty($body['all_devices'])) {
            $stm2 = $connect_pdo->prepare(
                "UPDATE jwt_refresh_tokens SET revoked = 1 WHERE user_id = :uid AND revoked = 0"
            );
            $stm2->execute([':uid' => $apiUser['id']]);
        }

        $auditLog->logLogout($apiUser['id']);

        Response::success(null, 'تم تسجيل الخروج بنجاح');
    }

    /**
     * GET /auth/me
     * Get current authenticated user info
     */
    public static function me(): void
    {
        $apiUser = authMiddleware();

        // Remove sensitive fields
        unset($apiUser['finger_id']);

        Response::success($apiUser, '');
    }

    /**
     * POST /auth/forgot-password
     * Generate a password reset token and send a branded reset email
     */
    public static function forgotPassword(): void
    {
        global $connect_pdo, $auditLog;

        $body = getRequestBody();
        $email = trim($body['email'] ?? '');

        $v = new Validator($body);
        $v->required('email', 'البريد الإلكتروني')
          ->email('email', 'البريد الإلكتروني');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        // Rate limit
        rateLimitMiddleware('forgot_password', 3, 600);

        $mailService = new MailService($connect_pdo);
        if (!$mailService->isConfigured()) {
            Response::error('خدمة استعادة كلمة المرور غير مهيأة حالياً. يرجى التواصل مع الإدارة.', 503);
        }

        // Find user
        $stm = $connect_pdo->prepare(
            "SELECT UserID, UserEmail, FirstName
             FROM tblusers
             WHERE LOWER(TRIM(UserEmail)) = :email
               AND COALESCE(IsDisabled, 0) = 0
             LIMIT 1"
        );
        $stm->execute([':email' => $email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        // Always return success to prevent email enumeration
        if (!$user) {
            Response::success(null, 'إذا كان البريد الإلكتروني مسجلاً، سيتم إرسال رابط إعادة تعيين كلمة المرور');
            return;
        }

        // Invalidate old tokens
        $connect_pdo->prepare(
            "UPDATE password_resets SET used = 1 WHERE user_id = :uid AND used = 0"
        )->execute([':uid' => $user['UserID']]);

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $connect_pdo->prepare(
            "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :hash, :expires)"
        )->execute([
            ':uid'     => $user['UserID'],
            ':hash'    => $tokenHash,
            ':expires' => $expiresAt,
        ]);

        $auditLog->log((int) $user['UserID'], 'password_reset_requested', 'tblusers', (int) $user['UserID']);

        $responseData = [
            'expires_at' => $expiresAt,
        ];

        if (filter_var(getenv('API_DEBUG_RESET_TOKENS') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
            $responseData['reset_token'] = $token;
            $responseData['note'] = 'Debug mode only: the reset token is returned directly.';
        }

        $successMessage = 'إذا كان البريد الإلكتروني موجوداً، فسيتم إرسال رابط إعادة تعيين كلمة المرور.';
        $displayName = trim((string) ($user['FirstName'] ?? '')) !== ''
            ? trim((string) $user['FirstName'])
            : (string) $user['UserEmail'];

        if (function_exists('fastcgi_finish_request')) {
            self::sendForgotPasswordResponse($responseData, $successMessage);

            $mailResult = $mailService->sendPasswordResetEmail(
                (string) $user['UserEmail'],
                $displayName,
                $token,
                $expiresAt
            );

            if (!($mailResult['result'] ?? false)) {
                error_log('Forgot password email failed for user ' . (int) $user['UserID'] . ': ' . ($mailResult['msg'] ?? 'unknown error'));
                $connect_pdo->prepare(
                    "UPDATE password_resets SET used = 1 WHERE token_hash = :hash"
                )->execute([':hash' => $tokenHash]);
            }

            return;
        }

        $mailResult = $mailService->sendPasswordResetEmail(
            (string) $user['UserEmail'],
            $displayName,
            $token,
            $expiresAt
        );

        if (!($mailResult['result'] ?? false)) {
            error_log('Forgot password email failed for user ' . (int) $user['UserID'] . ': ' . ($mailResult['msg'] ?? 'unknown error'));
            Response::error('تعذر إرسال رسالة إعادة التعيين حالياً. يرجى المحاولة لاحقاً.', 500);
        }

        Response::success($responseData, $successMessage);
    }

    private static function sendForgotPasswordResponse(array $data, string $message): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
    }

    /**
     * POST /auth/reset-password
     * Reset password using a reset token
     */
    public static function resetPassword(): void
    {
        global $connect_pdo, $auditLog;

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('token', 'رمز إعادة التعيين')
          ->required('password', 'كلمة المرور الجديدة')
          ->minLength('password', 6, 'كلمة المرور الجديدة')
          ->required('password_confirmation', 'تأكيد كلمة المرور');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        if ($body['password'] !== $body['password_confirmation']) {
            Response::validationError(['password_confirmation' => 'كلمة المرور غير متطابقة']);
        }

        $token = $body['token'];
        $tokenHash = hash('sha256', $token);

        // Find valid token
        $stm = $connect_pdo->prepare(
            "SELECT id, user_id FROM password_resets
             WHERE token_hash = :hash AND used = 0 AND expires_at > NOW()
             LIMIT 1"
        );
        $stm->execute([':hash' => $tokenHash]);
        $reset = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            Response::error('رمز إعادة التعيين غير صالح أو منتهي الصلاحية', 422);
        }

        // Update password
        $newHash = Security::hashPassword($body['password']);
        $connect_pdo->prepare(
            "UPDATE tblusers SET Password = :hash WHERE UserID = :uid"
        )->execute([':hash' => $newHash, ':uid' => $reset['user_id']]);

        // Mark token as used
        $connect_pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")
            ->execute([':id' => $reset['id']]);

        // Revoke all refresh tokens (force re-login)
        $connect_pdo->prepare(
            "UPDATE jwt_refresh_tokens SET revoked = 1 WHERE user_id = :uid AND revoked = 0"
        )->execute([':uid' => $reset['user_id']]);

        $auditLog->log((int) $reset['user_id'], 'password_reset', 'tblusers', (int) $reset['user_id']);

        Response::success(null, 'تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول مجدداً');
    }

    /**
     * POST /auth/change-password
     * Change password for authenticated user
     */
    public static function changePassword(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('current_password', 'كلمة المرور الحالية')
          ->required('new_password', 'كلمة المرور الجديدة')
          ->minLength('new_password', 6, 'كلمة المرور الجديدة')
          ->required('new_password_confirmation', 'تأكيد كلمة المرور');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        if ($body['new_password'] !== $body['new_password_confirmation']) {
            Response::validationError(['new_password_confirmation' => 'كلمة المرور غير متطابقة']);
        }

        // Verify current password
        $stm = $connect_pdo->prepare("SELECT Password FROM tblusers WHERE UserID = :uid LIMIT 1");
        $stm->execute([':uid' => $apiUser['id']]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$user || !Security::verifyPassword($body['current_password'], $user['Password'])) {
            Response::error('كلمة المرور الحالية غير صحيحة', 422);
        }

        // Update password
        $newHash = Security::hashPassword($body['new_password']);
        $connect_pdo->prepare("UPDATE tblusers SET Password = :hash WHERE UserID = :uid")
            ->execute([':hash' => $newHash, ':uid' => $apiUser['id']]);

        $auditLog->log($apiUser['id'], 'password_changed', 'tblusers', $apiUser['id']);

        Response::success(null, 'تم تغيير كلمة المرور بنجاح');
    }
}
