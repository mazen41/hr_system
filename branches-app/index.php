<?php
// Secure session cookie settings for HTTPS - only if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
header('Content-Type: application/json; charset=utf-8');

$result = true;
$msg = '';
$data = [];

$action = $_GET['action'] ?? '';
$user = $_SESSION['user']['UserID'] ?? null;

// Handle URL rewriting - extract action from REQUEST_URI if not in GET
if (empty($action)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/branches-app\/([a-zA-Z0-9_-]+)/', $uri, $matches)) {
        $action = $matches[1];
    }
}

switch ($action) {
    case 'branches-add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $result = false;
            $msg = 'طريقة غير صحيحة';
            break;
        }

        $id = intval($_POST['branch_id'] ?? $_POST['id'] ?? 0);
        $branchRef = intval($_POST['br_no'] ?? 0);
        $branchName = trim($_POST['br_name'] ?? '');
        $branchStyle = trim($_POST['br_style'] ?? '');
        $isStopped = isset($_POST['stopped']) ? 1 : null;
        $apps = $_POST['apps'] ?? [];

        // Address fields
        $street = trim($_POST['street'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $zip = trim($_POST['zip'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $vat = trim($_POST['vat'] ?? '');
        $vatG = trim($_POST['vat_g'] ?? '');
        $idType = trim($_POST['idtype'] ?? '');
        $idNo = trim($_POST['idno'] ?? '');

        // GPS Data
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $lat = $latitude !== '' ? $latitude : null;
        $lng = $longitude !== '' ? $longitude : null;

        $createdBy = $user;

        if (empty($branchName)) {
            $result = false;
            $msg = 'اسم الفرع مطلوب';
            break;
        }
        if (empty($branchRef)) {
            $result = false;
            $msg = 'رقم الفرع مطلوب';
            break;
        }

        try {
            $connect_pdo->beginTransaction();

            if ($id > 0) {
                // Update existing branch
                $stmt = $connect_pdo->prepare("UPDATE branches SET 
                    branch_ref = ?, branch_name = ?, branch_style = ?, isstopped = ?
                    WHERE branch_id = ?");
                $stmt->execute([$branchRef, $branchName, $branchStyle ?: null, $isStopped, $id]);
                $branchId = $id;

                // Update address if exists
                $stmtAddr = $connect_pdo->prepare("SELECT branch_address FROM branches WHERE branch_id = ?");
                $stmtAddr->execute([$id]);
                $addressId = $stmtAddr->fetchColumn();

                if ($addressId) {
                    $stmt = $connect_pdo->prepare("UPDATE tbladdress SET 
                        Street = ?, Block = ?, City = ?, Building = ?, Phone = ?, Mobile = ?,
                        ZipCode = ?, Email = ?, VatNumber = ?, VatGNumber = ?, IdentityType = ?, IdentityDetail = ?,
                        Latitude = ?, Longitude = ?
                        WHERE AddressID = ?");
                    $stmt->execute([
                        $street,
                        $block,
                        $city,
                        $building,
                        $phone,
                        $mobile,
                        $zip,
                        $email,
                        $vat,
                        $vatG,
                        $idType ?: null,
                        $idNo,
                        $lat,
                        $lng,
                        $addressId
                    ]);
                } else {
                    // Create new address
                    $stmt = $connect_pdo->prepare("INSERT INTO tbladdress 
                        (AddressType, Street, Block, City, Building, Phone, Mobile, ZipCode, Email, VatNumber, VatGNumber, IdentityType, IdentityDetail, Latitude, Longitude)
                        VALUES ('BRANCH', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$street, $block, $city, $building, $phone, $mobile, $zip, $email, $vat, $vatG, $idType ?: null, $idNo, $lat, $lng]);
                    $addressId = $connect_pdo->lastInsertId();

                    // Link address to branch
                    $stmt = $connect_pdo->prepare("UPDATE branches SET branch_address = ? WHERE branch_id = ?");
                    $stmt->execute([$addressId, $id]);
                }

                $msg = 'تم تحديث الفرع بنجاح';
            } else {
                // Check if branch_ref already exists
                $stmt = $connect_pdo->prepare("SELECT branch_id FROM branches WHERE branch_ref = ?");
                $stmt->execute([$branchRef]);
                if ($stmt->fetch()) {
                    $result = false;
                    $msg = 'رقم الفرع موجود بالفعل';
                    $connect_pdo->rollBack();
                    break;
                }

                // Create address first
                $stmt = $connect_pdo->prepare("INSERT INTO tbladdress 
                    (AddressType, Street, Block, City, Building, Phone, Mobile, ZipCode, Email, VatNumber, VatGNumber, IdentityType, IdentityDetail, Latitude, Longitude)
                    VALUES ('BRANCH', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$street, $block, $city, $building, $phone, $mobile, $zip, $email, $vat, $vatG, $idType ?: null, $idNo, $lat, $lng]);
                $addressId = $connect_pdo->lastInsertId();

                // Create branch
                $stmt = $connect_pdo->prepare("INSERT INTO branches 
                    (branch_ref, branch_name, branch_style, branch_address, created_user, created_date)
                    VALUES (?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$branchRef, $branchName, $branchStyle ?: null, $addressId, $createdBy]);
                $branchId = $connect_pdo->lastInsertId();

                $data = ['id' => $branchId];
                $msg = 'تم إضافة الفرع بنجاح';
            }

            // Update branch apps
            if ($branchId > 0) {
                // Delete existing apps
                $stmt = $connect_pdo->prepare("DELETE FROM tblbranchesapps WHERE BranchID = ?");
                $stmt->execute([$branchId]);

                // Insert new apps
                if (!empty($apps) && is_array($apps)) {
                    $stmt = $connect_pdo->prepare("INSERT INTO tblbranchesapps (BranchID, AppID) VALUES (?, ?)");
                    foreach ($apps as $appId) {
                        $stmt->execute([$branchId, $appId]);
                    }
                }

                // Always add required apps (HR, etc.)
                $reqApps = $connect_pdo->query("SELECT AppID FROM apps WHERE IsRrequred = 1")->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($reqApps)) {
                    $stmt = $connect_pdo->prepare("INSERT IGNORE INTO tblbranchesapps (BranchID, AppID) VALUES (?, ?)");
                    foreach ($reqApps as $appId) {
                        $stmt->execute([$branchId, $appId]);
                    }
                }
            }

            $connect_pdo->commit();
            $data['id'] = $branchId;

        } catch (PDOException $e) {
            $connect_pdo->rollBack();
            $result = false;
            $msg = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
        break;

    default:
        $result = false;
        $msg = 'Action not found: ' . $action;
        break;
}

echo json_encode(['result' => $result, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);