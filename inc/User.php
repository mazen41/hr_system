<?php
/**
 * User Class - Minimal core implementation for Vision HR
 * Provides authentication, authorization, and user management
 */
class User
{
    private $pdo;
    private $id;
    private $name;
    private $email;
    private $role;
    private $groupId;
    private $isAdmin;
    public $currency = 'ر.س';
    public $branches;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Authenticate user by email and password
     */
    public function login($email, $password)
    {
        $query = "SELECT UserID, UserEmail, UserPassword, FirstName, SecondName, LastName, 
                         UserGroupID, IsDisabled, IsSystem, AllowedBranches, Photo, Phone, home_page
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
        if (password_verify($password, $user['UserPassword'] ?? '')) {
            $validPassword = true;
        } elseif ($password === ($user['UserPassword'] ?? '')) {
            $validPassword = true; // Plain text fallback for dev
        }

        if (!$validPassword) {
            return false;
        }

        // Set session
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['user'] = [
            'id' => $user['UserID'],
            'email' => $user['UserEmail'],
            'name' => $user['FirstName'] . ' ' . $user['LastName'],
            'first_name' => $user['FirstName'],
            'last_name' => $user['LastName'],
            'group_id' => $user['UserGroupID'],
            'photo' => $user['Photo'],
            'phone' => $user['Phone'],
            'home_page' => $user['home_page'] ?? 'Hrdashboard',
            'branches' => $user['AllowedBranches'],
            'is_system' => $user['IsSystem'],
        ];

        // Get branch
        $this->loadBranch($user['UserID']);

        $this->id = $user['UserID'];
        $this->name = $user['FirstName'] . ' ' . $user['LastName'];
        $this->email = $user['UserEmail'];
        $this->groupId = $user['UserGroupID'];
        $this->isAdmin = !empty($user['IsSystem']);
        $this->branches = $user['AllowedBranches'];

        return true;
    }

    /**
     * Load user from session
     */
    public function loadFromSession()
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        $query = "SELECT u.UserID, u.UserEmail, u.FirstName, u.LastName, u.UserGroupID, 
                         u.IsSystem, u.AllowedBranches, u.Photo, u.Phone,
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

        $this->id = $user['UserID'];
        $this->name = $user['FirstName'] . ' ' . $user['LastName'];
        $this->email = $user['UserEmail'];
        $this->groupId = $user['UserGroupID'];
        $this->isAdmin = !empty($user['IsSystem']) || !empty($user['FullAccess']);
        $this->branches = $user['AllowedBranches'];

        // Always refresh session user data from DB (fixes encoding issues)
        $_SESSION['user'] = [
            'id' => $user['UserID'],
            'email' => $user['UserEmail'],
            'name' => $user['FirstName'] . ' ' . $user['LastName'],
            'first_name' => $user['FirstName'],
            'last_name' => $user['LastName'],
            'group_id' => $user['UserGroupID'],
            'photo' => $user['Photo'],
            'phone' => $user['Phone'],
            'branches' => $user['AllowedBranches'],
            'is_system' => $user['IsSystem'],
        ];

        return true;
    }

    private function loadBranch($userId)
    {
        // Get the user's branch from tblremewal (latest contract)
        $query = "SELECT BranchID FROM tblremewal WHERE UserID = :id AND state IS NOT NULL ORDER BY Id DESC LIMIT 1";
        $stm = $this->pdo->prepare($query);
        $stm->execute([':id' => $userId]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $_SESSION['branch'] = $row['BranchID'];
        } else {
            // Fallback: get first branch
            $q = "SELECT branch_id FROM branches ORDER BY branch_id LIMIT 1";
            $s = $this->pdo->prepare($q);
            $s->execute();
            $b = $s->fetch(PDO::FETCH_ASSOC);
            $_SESSION['branch'] = $b ? $b['branch_id'] : 1;
        }
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }

    /**
     * Check if user is admin
     */
    public function userIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Check if user has specific permission
     */
    public function isAllowedPerm($perms, $appid = null)
    {
        // Admin has all permissions
        if ($this->isAdmin) {
            return true;
        }

        if (empty($this->groupId)) {
            return false;
        }

        // Check group permissions
        $query = "SELECT FullAccess FROM tblusergroups WHERE GroupID = :gid LIMIT 1";
        $stm = $this->pdo->prepare($query);
        $stm->execute([':gid' => $this->groupId]);
        $group = $stm->fetch(PDO::FETCH_ASSOC);

        if ($group && !empty($group['FullAccess'])) {
            return true;
        }

        // Check specific permissions from tblpermissions
        if (!is_array($perms)) {
            $perms = [$perms];
        }

        foreach ($perms as $perm) {
            $query = "SELECT PermID FROM tblpermissions 
                      WHERE GroupID = :gid AND PermName = :perm";
            if ($appid) {
                $query .= " AND AppID = :appid";
            }
            $query .= " LIMIT 1";

            $stm = $this->pdo->prepare($query);
            $params = [':gid' => $this->groupId, ':perm' => $perm];
            if ($appid) {
                $params[':appid'] = $appid;
            }
            $stm->execute($params);

            if ($stm->rowCount() > 0) {
                return true;
            }
        }

        return false;
    }

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
}
