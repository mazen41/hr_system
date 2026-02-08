<?php
/**
 * Vision HR - JWT Authentication Middleware
 * Validates Bearer token and loads user into global scope
 */

/**
 * Authenticate the request via JWT
 * Sets global $apiUser with user data
 * Sends 401 response if authentication fails
 */
function authMiddleware(): array
{
    global $connect_pdo, $jwt, $User;

    $token = JWTHelper::extractBearerToken();

    if (!$token) {
        Response::unauthorized('رمز المصادقة مفقود - يرجى تسجيل الدخول');
    }

    $userId = $jwt->validateAccessToken($token);

    if (!$userId) {
        Response::unauthorized('رمز المصادقة غير صالح أو منتهي الصلاحية');
    }

    // Load user from DB
    $stm = $connect_pdo->prepare(
        "SELECT u.UserID, u.UserEmail, u.FirstName, u.SecondName, u.LastName, 
                u.UserGroupID, u.IsSystem, u.IsDisabled, u.AllowedBranches, 
                u.Photo, u.Phone, u.BranchID, u.FingerID, u.lastversion, u.isemp, u.manager,
                g.FullAccess, g.GroupName
         FROM tblusers u
         LEFT JOIN tblusergroups g ON g.GroupID = u.UserGroupID
         WHERE u.UserID = :id 
         LIMIT 1"
    );
    $stm->execute([':id' => $userId]);
    $user = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        Response::unauthorized('المستخدم غير موجود');
    }

    if (!empty($user['IsDisabled'])) {
        Response::unauthorized('هذا الحساب موقف');
    }

    // Get current contract info
    $stm2 = $connect_pdo->prepare(
        "SELECT r.Id, r.SectionID, r.BranchID, r.GroupID, r.GradeID, r.shiftID, 
                r.TypeID, r.jobtitleID, r.Salary, r.Currency, r.new_s_date, r.new_e_date,
                s.Name as SectionName, jt.Name as JobTitleName, jg.Name as GradeName,
                grp.Name as GroupName2, sh.ShiftName
         FROM tblremewal r
         LEFT JOIN tblsection s ON s.Id = r.SectionID
         LEFT JOIN tbljobtitle jt ON jt.Id = r.jobtitleID
         LEFT JOIN tbljobgrade jg ON jg.Id = r.GradeID
         LEFT JOIN tblgroup grp ON grp.Id = r.GroupID
         LEFT JOIN tbshift sh ON sh.ShiftID = r.shiftID
         WHERE r.UserID = :uid AND r.state IS NOT NULL
         ORDER BY r.Id DESC LIMIT 1"
    );
    $stm2->execute([':uid' => $userId]);
    $contract = $stm2->fetch(PDO::FETCH_ASSOC);

    // Build API user object
    $apiUser = [
        'id'               => (int) $user['UserID'],
        'email'            => $user['UserEmail'],
        'name'             => trim($user['FirstName'] . ' ' . ($user['SecondName'] ?? '') . ' ' . ($user['LastName'] ?? '')),
        'first_name'       => $user['FirstName'],
        'last_name'        => $user['LastName'],
        'photo'            => $user['Photo'],
        'phone'            => $user['Phone'],
        'group_id'         => (int) $user['UserGroupID'],
        'group_name'       => $user['GroupName'],
        'is_admin'         => !empty($user['IsSystem']) || !empty($user['FullAccess']),
        'is_employee'      => !empty($user['isemp']),
        'manager_id'       => $user['manager'] ? (int) $user['manager'] : null,
        'branch_id'        => (int) ($contract['BranchID'] ?? $user['BranchID'] ?? 1),
        'allowed_branches' => $user['AllowedBranches'],
        'finger_id'        => $user['FingerID'],
        'contract'         => $contract ?: null,
    ];

    return $apiUser;
}

/**
 * Optional auth - returns user data if token present, null otherwise
 */
function optionalAuth(): ?array
{
    $token = JWTHelper::extractBearerToken();
    if (!$token) {
        return null;
    }

    try {
        return authMiddleware();
    } catch (\Exception $e) {
        return null;
    }
}
