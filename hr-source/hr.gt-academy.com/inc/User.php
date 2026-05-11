<?php
/**
 * User Class — Vision HR RBAC & Authentication
 * 
 * Roles hierarchy:
 *   ROLE_OWNER    → IsSystem=1 (GroupID 1) — full system access
 *   ROLE_ADMIN    → FullAccess=1 (GroupID 2) — full HR access
 *   ROLE_EMPLOYER → GroupID 4 (HR Manager)  — HR management access
 *   ROLE_EMPLOYEE → isemp=1                 — ESS self-service only
 *
 * Permissions are checked via tblpermission (AppID='HR', PermID hierarchy).
 * tblpermissionmenu links PermIDs to GroupIDs for granular access.
 */
class User
{
    private const SESSION_CACHE_TTL = 120;
    private $pdo;
    private $id;
    private $name;
    private $email;
    private $groupId = 0;
    private $isAdmin = false;
    private $isSystem = false;
    private $fullAccess = false;
    private $isemp = false;
    private $permCache = null;
    public $currency = 'ر.س';
    public $branches;

    // Role constants
    const ROLE_OWNER    = 'owner';
    const ROLE_ADMIN    = 'admin';
    const ROLE_EMPLOYER = 'employer';
    const ROLE_EMPLOYEE = 'employee';
    const ROLE_GUEST    = 'guest';

    // Group ID constants (match tblusergroups)
    const GROUP_OWNER    = 1;
    const GROUP_ADMIN    = 2;
    const GROUP_EMPLOYER = 4;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Authenticate user by email and password
     */
    public function login($email, $password)
    {
        $query = "SELECT UserID, UserEmail, Password, FirstName, SecondName, LastName, 
                         UserGroupID, IsDisabled, IsSystem, AllowedBranches, Photo, Phone, BranchID, isemp
                  FROM tblusers 
                  WHERE UserEmail = :email 
                  LIMIT 1";
        $stm = $this->pdo->prepare($query);
        $stm->execute([':email' => $email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!empty($user['IsDisabled'])) {
            $_SESSION['fatel_error'] = 'هذا الحساب موقف';
            return false;
        }

        // Check password (support both hashed and plain for dev)
        $validPassword = false;
        if (password_verify($password, $user['Password'] ?? '')) {
            $validPassword = true;
        } elseif ($password === ($user['Password'] ?? '')) {
            $validPassword = true;
        }

        if (!$validPassword) {
            return false;
        }

        // Hydrate internal state before building session
        $this->id        = $user['UserID'];
        $this->name      = $user['FirstName'] . ' ' . $user['LastName'];
        $this->email     = $user['UserEmail'];
        $this->groupId   = $user['UserGroupID'];
        $this->isSystem  = !empty($user['IsSystem']);
        $this->isemp     = !empty($user['isemp']);
        $this->branches  = $user['AllowedBranches'];

        // Resolve FullAccess from group
        $this->fullAccess = false;
        if ($this->groupId) {
            $gq = $this->pdo->prepare("SELECT FullAccess FROM tblusergroups WHERE GroupID = :gid LIMIT 1");
            $gq->execute([':gid' => $this->groupId]);
            $grp = $gq->fetch(PDO::FETCH_ASSOC);
            $this->fullAccess = !empty($grp['FullAccess']);
        }
        $this->isAdmin = $this->isSystem || $this->fullAccess;

        $_SESSION['user_id'] = $user['UserID'];
        $sessionSource = [
            'UserID' => $user['UserID'],
            'UserEmail' => $user['UserEmail'],
            'FirstName' => $user['FirstName'],
            'LastName' => $user['LastName'],
            'UserGroupID' => $user['UserGroupID'],
            'IsSystem' => $user['IsSystem'],
            'AllowedBranches' => $user['AllowedBranches'],
            'Photo' => $user['Photo'],
            'Phone' => $user['Phone'],
            'isemp' => $user['isemp'],
            'FullAccess' => $this->fullAccess ? 1 : 0,
            'BranchID' => $user['BranchID'] ?? null,
        ];
        $this->storeSessionUser($sessionSource);
        perf_cache_set(perf_cache_key('user-session', [(int)$user['UserID']]), $sessionSource, self::SESSION_CACHE_TTL);

        $this->loadBranch($user['UserID']);

        return true;
    }

    /**
     * Load user from session (called on every page load)
     */
    public function loadFromSession()
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        $cachedSessionUser = $_SESSION['user'] ?? null;
        $cacheUntil = (int)($_SESSION['user_cache_until'] ?? 0);
        if (is_array($cachedSessionUser) && $cacheUntil >= time()) {
            $this->hydrateFromSessionPayload($cachedSessionUser);
            return true;
        }

        $cacheKey = perf_cache_key('user-session', [(int)$_SESSION['user_id']]);
        $cachedUser = perf_cache_get($cacheKey);
        if (is_array($cachedUser) && (int)($cachedUser['UserID'] ?? 0) === (int)$_SESSION['user_id']) {
            $this->hydrateFromDbRow($cachedUser);
            $this->storeSessionUser($cachedUser);
            return true;
        }

        $query = "SELECT u.UserID, u.UserEmail, u.FirstName, u.LastName, u.UserGroupID, 
                         u.IsSystem, u.AllowedBranches, u.Photo, u.Phone, u.isemp, u.BranchID,
                         g.FullAccess
                  FROM tblusers u
                  LEFT JOIN tblusergroups g ON g.GroupID = u.UserGroupID
                  WHERE u.UserID = :id LIMIT 1";
        $stm = $this->pdo->prepare($query);
        $stm->execute([':id' => $_SESSION['user_id']]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        $this->hydrateFromDbRow($user);
        $this->storeSessionUser($user);
        perf_cache_set($cacheKey, $user, self::SESSION_CACHE_TTL);

        return true;
    }

    // ─── Role Checks ─────────────────────────────────────────

    /**
     * Get the user's resolved role string
     */
    public function getRole(): string
    {
        if ($this->isSystem)   return self::ROLE_OWNER;
        if ($this->fullAccess) return self::ROLE_ADMIN;
        if ($this->groupId == self::GROUP_EMPLOYER) return self::ROLE_EMPLOYER;
        if ($this->isemp)      return self::ROLE_EMPLOYEE;
        return self::ROLE_GUEST;
    }

    public function userIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function userIsEmployer(): bool
    {
        $role = $this->getRole();
        return in_array($role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_EMPLOYER]);
    }

    public function userIsEmployee(): bool
    {
        return $this->isemp && !$this->userIsAdmin() && $this->groupId != self::GROUP_EMPLOYER;
    }

    public function userIsOwner(): bool
    {
        return $this->isSystem;
    }

    // ─── Middleware-style Guards ──────────────────────────────

    /**
     * Require authentication — redirect to login if not logged in
     */
    public function requireAuth()
    {
        if (empty($this->id)) {
            header('Location: login-sys');
            exit;
        }
    }

    /**
     * Require a minimum role level. Redirects with error if insufficient.
     * Role hierarchy: owner > admin > employer > employee > guest
     */
    public function requireRole(string $minRole)
    {
        $this->requireAuth();
        $hierarchy = [
            self::ROLE_GUEST    => 0,
            self::ROLE_EMPLOYEE => 1,
            self::ROLE_EMPLOYER => 2,
            self::ROLE_ADMIN    => 3,
            self::ROLE_OWNER    => 4,
        ];
        $userLevel = $hierarchy[$this->getRole()] ?? 0;
        $requiredLevel = $hierarchy[$minRole] ?? 0;

        if ($userLevel < $requiredLevel) {
            $home = $this->resolveHomePage();
            $_SESSION['fatel_error'] = 'ليس لديك صلاحية للوصول لهذه الصفحة';
            echo '<script>location.replace("' . $home . '");</script>';
            exit;
        }
    }

    /**
     * Require admin or employer role (for HR management pages)
     */
    public function requireEmployer()
    {
        $this->requireRole(self::ROLE_EMPLOYER);
    }

    /**
     * Require admin role
     */
    public function requireAdmin()
    {
        $this->requireRole(self::ROLE_ADMIN);
    }

    // ─── Granular Permission Check ───────────────────────────

    /**
     * Check if user has specific permission(s) from tblpermission
     * 
     * @param string|array $perms  Permission name(s) or PermID(s)
     * @param string|null  $appid  App filter (default 'HR')
     * @return bool
     */
    public function isAllowedPerm($perms, $appid = 'HR'): bool
    {
        // Owner and FullAccess bypass all checks
        if ($this->isAdmin) {
            return true;
        }

        if (empty($this->groupId)) {
            return false;
        }

        // HR Manager (GroupID 4) gets all HR permissions
        if ($this->groupId == self::GROUP_EMPLOYER && $appid === 'HR') {
            return true;
        }

        // Load permission cache for this group
        if ($this->permCache === null) {
            $this->permCache = [];
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT p.PermID, p.PermName, p.AppID
                     FROM tblpermission p
                     INNER JOIN tblpermissionmenu pm ON pm.PermID = p.PermID
                     WHERE pm.MenuID = :gid AND (p.IsDisabled IS NULL OR p.IsDisabled = 0)"
                );
                $stmt->execute([':gid' => $this->groupId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->permCache[$row['PermID']] = $row;
                    $this->permCache['name:' . $row['AppID'] . ':' . $row['PermName']] = $row;
                }
            } catch (\PDOException $e) {
                error_log('Permission check failed: ' . $e->getMessage());
                return false;
            }
        }

        if (!is_array($perms)) {
            $perms = [$perms];
        }

        foreach ($perms as $perm) {
            // Check by PermID (numeric)
            if (is_numeric($perm) && isset($this->permCache[(int)$perm])) {
                return true;
            }
            // Check by name
            $key = 'name:' . ($appid ?? 'HR') . ':' . $perm;
            if (isset($this->permCache[$key])) {
                return true;
            }
        }

        return false;
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function resolveHomePage(): string
    {
        if ($this->isemp && !$this->isAdmin && $this->groupId != self::GROUP_EMPLOYER) {
            return 'ess-dashboard';
        }
        if ($this->isAdmin || $this->groupId == self::GROUP_EMPLOYER) {
            return 'employer-dashboard';
        }
        return 'Hrdashboard';
    }

    private function loadBranch($userId)
    {
        $query = "SELECT BranchID FROM tblremewal WHERE UserID = :id AND state IS NOT NULL ORDER BY Id DESC LIMIT 1";
        $stm = $this->pdo->prepare($query);
        $stm->execute([':id' => $userId]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $_SESSION['branch'] = $row['BranchID'];
        } else {
            $q = "SELECT branch_id FROM branches ORDER BY branch_id LIMIT 1";
            $s = $this->pdo->prepare($q);
            $s->execute();
            $b = $s->fetch(PDO::FETCH_ASSOC);
            $_SESSION['branch'] = $b ? $b['branch_id'] : 1;
        }
    }

    public function getId()    { return $this->id; }
    public function getName()  { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getGroupId() { return $this->groupId; }

    /**
     * Get all branches the user has access to
     */
    public function allBranches($allowedBranches = null)
    {
        $branches = [];
        $query = "SELECT branch_id, branch_name FROM branches WHERE isstopped IS NULL";

        if ($allowedBranches && !$this->isAdmin) {
            $ids = explode(',', $allowedBranches);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query .= " AND branch_id IN ($placeholders)";
            $stm = $this->pdo->prepare($query);
            $stm->execute($ids);
        } else {
            $stm = $this->pdo->prepare($query);
            $stm->execute();
        }

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $branches[$row['branch_id']] = $row['branch_name'];
        }

        return $branches;
    }

    private function hydrateFromDbRow(array $user): void
    {
        $this->id         = (int)($user['UserID'] ?? 0);
        $this->name       = trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''));
        $this->email      = $user['UserEmail'] ?? null;
        $this->groupId    = (int)($user['UserGroupID'] ?? 0);
        $this->isSystem   = !empty($user['IsSystem']);
        $this->fullAccess = !empty($user['FullAccess']);
        $this->isAdmin    = $this->isSystem || $this->fullAccess;
        $this->isemp      = !empty($user['isemp']);
        $this->branches   = $user['AllowedBranches'] ?? null;
    }

    private function hydrateFromSessionPayload(array $sessionUser): void
    {
        $this->id         = (int)($sessionUser['id'] ?? $sessionUser['UserID'] ?? 0);
        $this->name       = $sessionUser['name'] ?? trim(($sessionUser['first_name'] ?? '') . ' ' . ($sessionUser['last_name'] ?? ''));
        $this->email      = $sessionUser['email'] ?? null;
        $this->groupId    = (int)($sessionUser['group_id'] ?? 0);
        $this->isSystem   = !empty($sessionUser['is_system']);
        $this->fullAccess = !empty($sessionUser['full_access']);
        $this->isAdmin    = $this->isSystem || $this->fullAccess;
        $this->isemp      = !empty($sessionUser['isemp']);
        $this->branches   = $sessionUser['branches'] ?? null;
    }

    private function storeSessionUser(array $user): void
    {
        $_SESSION['user'] = [
            'id'          => $user['UserID'] ?? null,
            'email'       => $user['UserEmail'] ?? null,
            'name'        => trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? '')),
            'first_name'  => $user['FirstName'] ?? '',
            'last_name'   => $user['LastName'] ?? '',
            'group_id'    => $user['UserGroupID'] ?? 0,
            'photo'       => $user['Photo'] ?? null,
            'phone'       => $user['Phone'] ?? null,
            'home_page'   => $this->resolveHomePage(),
            'branch_id'   => $user['BranchID'] ?? ($_SESSION['branch'] ?? null),
            'branches'    => $user['AllowedBranches'] ?? null,
            'is_system'   => $user['IsSystem'] ?? 0,
            'isemp'       => $user['isemp'] ?? 0,
            'full_access' => !empty($user['FullAccess']) ? 1 : 0,
            'role'        => $this->getRole(),
        ];
        $_SESSION['user_cache_until'] = time() + self::SESSION_CACHE_TTL;
    }
}
