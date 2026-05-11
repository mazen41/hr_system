<?php
/**
 * Vision HR - Biometric Controller
 * Fingerprint device management and sync via API
 */

class BiometricController
{
    /**
     * GET /biometric/devices
     * List all fingerprint devices (admin only)
     */
    public static function devices(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف']);

        $stm = $connect_pdo->prepare(
            "SELECT f.FingerprintID, f.FingerprintName, f.FingerprintType,
                    f.FingerprintSerailnumber, f.ip, f.port, f.FingerprintState,
                    f.BranchID, b.branch_name
             FROM tbfingerprint f
             LEFT JOIN branches b ON b.branch_id = f.BranchID
             ORDER BY f.FingerprintID"
        );
        $stm->execute();
        $devices = $stm->fetchAll(PDO::FETCH_ASSOC);

        Response::success(array_map(function ($device) {
            return [
                'id'            => (int) $device['FingerprintID'],
                'name'          => $device['FingerprintName'],
                'type'          => $device['FingerprintType'],
                'serial_number' => $device['FingerprintSerailnumber'],
                'ip'            => $device['ip'],
                'port'          => $device['port'],
                'active'        => empty($device['FingerprintState']) || (int) $device['FingerprintState'] === 1,
                'branch_id'     => (int) $device['BranchID'],
                'branch_name'   => $device['branch_name'],
            ];
        }, $devices));
    }

    /**
     * POST /biometric/sync
     * Trigger sync for all devices (admin only)
     */
    public static function syncAll(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف']);

        require_once dirname(__DIR__, 2) . '/shared/BiometricSync.php';
        $sync = new BiometricSync($connect_pdo);
        $result = $sync->syncAll();

        foreach ($result['details'] as $detail) {
            try {
                $connect_pdo->prepare(
                    "INSERT INTO biometric_sync_log (device_id, records_fetched, records_imported, status, error_message, synced_at)
                     VALUES (:did, :fetched, :imported, :status, :error, NOW())"
                )->execute([
                    ':did'      => $detail['device_id'],
                    ':fetched'  => $detail['records_fetched'] ?? 0,
                    ':imported' => $detail['records_imported'] ?? 0,
                    ':status'   => !empty($detail['success']) ? 'success' : 'error',
                    ':error'    => $detail['error'] ?? null,
                ]);
            } catch (PDOException $e) {
                // biometric_sync_log may not exist in some installations
            }
        }

        $auditLog->log($apiUser['id'], 'biometric_sync_all', 'tbfingerprint', null, null, [
            'devices_synced' => $result['devices_synced'],
            'total_records'  => $result['total_records'],
        ]);

        Response::success($result, 'تمت مزامنة أجهزة البصمة');
    }

    /**
     * POST /biometric/sync/:id
     * Trigger sync for a specific device
     */
    public static function syncDevice(array $params): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف']);

        $deviceId = (int) ($params['id'] ?? 0);

        require_once dirname(__DIR__, 2) . '/shared/BiometricSync.php';
        $sync = new BiometricSync($connect_pdo);
        $result = $sync->syncDevice($deviceId);

        try {
            $connect_pdo->prepare(
                "INSERT INTO biometric_sync_log (device_id, records_fetched, records_imported, status, error_message, synced_at)
                 VALUES (:did, :fetched, :imported, :status, :error, NOW())"
            )->execute([
                ':did'      => $deviceId,
                ':fetched'  => $result['records_fetched'] ?? 0,
                ':imported' => $result['records_imported'] ?? 0,
                ':status'   => !empty($result['success']) ? 'success' : 'error',
                ':error'    => $result['error'] ?? null,
            ]);
        } catch (PDOException $e) {
            // biometric_sync_log may not exist in some installations
        }

        $auditLog->log($apiUser['id'], 'biometric_sync_device', 'tbfingerprint', $deviceId, null, $result);

        Response::success(
            $result,
            !empty($result['success']) ? 'تمت مزامنة الجهاز بنجاح' : 'تعذرت مزامنة الجهاز'
        );
    }

    /**
     * GET /biometric/test/:id
     * Test connection to a specific device
     */
    public static function testDevice(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف']);

        $deviceId = (int) ($params['id'] ?? 0);

        require_once dirname(__DIR__, 2) . '/shared/BiometricSync.php';
        $sync = new BiometricSync($connect_pdo);
        $result = $sync->testConnection($deviceId);

        Response::success($result, !empty($result['connected']) ? 'الجهاز متصل' : 'الجهاز غير متصل');
    }

    /**
     * GET /biometric/sync-log
     * Get sync history
     */
    public static function syncLog(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف']);

        $pagination = Validator::pagination();
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];

        $stm = $connect_pdo->prepare(
            "SELECT l.*, f.FingerprintName as device_name
             FROM biometric_sync_log l
             LEFT JOIN tbfingerprint f ON f.FingerprintID = l.device_id
             ORDER BY l.synced_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stm->execute();
        $logs = $stm->fetchAll(PDO::FETCH_ASSOC);

        $stm2 = $connect_pdo->prepare("SELECT COUNT(*) as cnt FROM biometric_sync_log");
        $stm2->execute();
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['cnt'];

        Response::paginated(array_map(function ($log) {
            return [
                'id'               => (int) $log['id'],
                'device_id'        => (int) $log['device_id'],
                'device_name'      => $log['device_name'],
                'records_fetched'  => (int) ($log['records_fetched'] ?? 0),
                'records_imported' => (int) ($log['records_imported'] ?? 0),
                'status'           => $log['status'],
                'error_message'    => $log['error_message'],
                'synced_at'        => $log['synced_at'],
            ];
        }, $logs), $total, $pagination['page'], $pagination['per_page']);
    }
}
