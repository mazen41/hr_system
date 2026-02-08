<?php
/**
 * Vision HR - RBAC (Role-Based Access Control) Middleware
 * Checks permissions against the existing tblusergroups/tblpermissions system
 */

/**
 * Check if the authenticated user has the required permission
 *
 * @param array        $apiUser  The authenticated user from authMiddleware()
 * @param string|array $perms    Permission name(s) to check
 * @param string       $appId    Application ID (default: 'HR')
 */
function rbacMiddleware(array $apiUser, $perms, string $appId = 'HR'): void
{
    // Admins bypass all permission checks
    if (!empty($apiUser['is_admin'])) {
        return;
    }

    global $connect_pdo;

    $groupId = $apiUser['group_id'] ?? 0;
    if (empty($groupId)) {
        Response::forbidden('لم يتم تعيين مجموعة صلاحيات لهذا المستخدم');
    }

    // Check if group has FullAccess
    $stm = $connect_pdo->prepare("SELECT FullAccess FROM tblusergroups WHERE GroupID = :gid LIMIT 1");
    $stm->execute([':gid' => $groupId]);
    $group = $stm->fetch(PDO::FETCH_ASSOC);

    if ($group && !empty($group['FullAccess'])) {
        return; // Full access group
    }

    // Check specific permissions
    if (!is_array($perms)) {
        $perms = [$perms];
    }

    foreach ($perms as $perm) {
        $stm = $connect_pdo->prepare(
            "SELECT PermID FROM tblpermissions 
             WHERE GroupID = :gid AND PermName = :perm AND AppID = :appid 
             LIMIT 1"
        );
        $stm->execute([':gid' => $groupId, ':perm' => $perm, ':appid' => $appId]);

        if ($stm->rowCount() > 0) {
            return; // Has at least one of the required permissions
        }
    }

    Response::forbidden('ليس لديك صلاحية لهذا الإجراء');
}

/**
 * Check if user is a manager (has subordinates)
 */
function requireManager(array $apiUser): void
{
    if (!empty($apiUser['is_admin'])) {
        return;
    }

    global $connect_pdo;

    $stm = $connect_pdo->prepare(
        "SELECT COUNT(*) as cnt FROM tblusers WHERE manager = :uid AND IsDisabled IS NULL"
    );
    $stm->execute([':uid' => $apiUser['id']]);
    $row = $stm->fetch(PDO::FETCH_ASSOC);

    if (empty($row['cnt'])) {
        Response::forbidden('هذا الإجراء متاح للمدراء فقط');
    }
}

/**
 * Check if user is admin
 */
function requireAdmin(array $apiUser): void
{
    if (empty($apiUser['is_admin'])) {
        Response::forbidden('هذا الإجراء متاح للمسؤولين فقط');
    }
}

/**
 * Check if user can access a specific branch
 */
function requireBranchAccess(array $apiUser, int $branchId): void
{
    if (!empty($apiUser['is_admin'])) {
        return;
    }

    $allowed = $apiUser['allowed_branches'] ?? '';
    if (empty($allowed)) {
        return; // No restriction
    }

    $allowedIds = array_map('intval', explode(',', $allowed));
    if (!in_array($branchId, $allowedIds)) {
        Response::forbidden('ليس لديك صلاحية الوصول لهذا الفرع');
    }
}

/**
 * Check if user owns the resource or is admin/manager
 */
function requireOwnerOrAdmin(array $apiUser, int $resourceUserId): void
{
    if (!empty($apiUser['is_admin'])) {
        return;
    }

    if ($apiUser['id'] === $resourceUserId) {
        return;
    }

    // Check if user is the manager of the resource owner
    global $connect_pdo;
    $stm = $connect_pdo->prepare(
        "SELECT manager FROM tblusers WHERE UserID = :uid LIMIT 1"
    );
    $stm->execute([':uid' => $resourceUserId]);
    $row = $stm->fetch(PDO::FETCH_ASSOC);

    if ($row && (int) $row['manager'] === $apiUser['id']) {
        return; // Is the manager
    }

    Response::forbidden('ليس لديك صلاحية الوصول لهذا المورد');
}
