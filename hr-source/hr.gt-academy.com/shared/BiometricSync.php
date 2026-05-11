<?php
/**
 * Vision HR - Biometric Device Sync Service
 * Reads attendance data from fingerprint devices and writes to tblattendance
 * Supports ZKTeco and compatible devices via their HTTP/TCP API
 * 
 * Usage:
 *   $sync = new BiometricSync($connect_pdo);
 *   $sync->syncAll();           // Sync all active devices
 *   $sync->syncDevice($deviceId); // Sync a specific device
 */

class BiometricSync
{
    private PDO $pdo;
    private int $timeout;

    public function __construct(PDO $pdo, int $timeout = 10)
    {
        $this->pdo = $pdo;
        $this->timeout = $timeout;
    }

    /**
     * Sync all active fingerprint devices
     * Returns summary of sync results
     */
    public function syncAll(): array
    {
        $stm = $this->pdo->prepare(
            "SELECT FingerprintID, BranchID, FingerprintName, FingerprintType, 
                    FingerprintSerailnumber, ip, port
             FROM tbfingerprint
             WHERE FingerprintState IS NULL OR FingerprintState = 1"
        );
        $stm->execute();
        $devices = $stm->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($devices as $device) {
            $results[] = $this->syncDevice((int) $device['FingerprintID']);
        }

        return [
            'devices_total'  => count($devices),
            'devices_synced' => count(array_filter($results, fn($r) => $r['success'])),
            'total_records'  => array_sum(array_column($results, 'records_imported')),
            'details'        => $results,
            'synced_at'      => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Sync a specific fingerprint device
     */
    public function syncDevice(int $deviceId): array
    {
        $stm = $this->pdo->prepare(
            "SELECT FingerprintID, BranchID, FingerprintName, FingerprintType,
                    FingerprintSerailnumber, ip, port
             FROM tbfingerprint WHERE FingerprintID = :id LIMIT 1"
        );
        $stm->execute([':id' => $deviceId]);
        $device = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            return ['success' => false, 'device_id' => $deviceId, 'error' => 'الجهاز غير موجود', 'records_imported' => 0];
        }

        $ip = $device['ip'] ?? '';
        $port = $device['port'] ?? '80';

        if (empty($ip)) {
            return [
                'success'          => false,
                'device_id'        => $deviceId,
                'device_name'      => $device['FingerprintName'],
                'error'            => 'لا يوجد عنوان IP محفوظ للجهاز',
                'records_imported' => 0,
            ];
        }

        try {
            // Fetch attendance logs from device
            $logs = $this->fetchDeviceLogs($ip, $port, $device['FingerprintType'] ?? 'zkteco');

            if ($logs === false) {
                return [
                    'success'          => false,
                    'device_id'        => $deviceId,
                    'device_name'      => $device['FingerprintName'],
                    'error'            => 'تعذر الاتصال بجهاز البصمة',
                    'records_imported' => 0,
                ];
            }

            // Import logs into tblattendance
            $imported = $this->importLogs($logs, (int) $device['BranchID'], $deviceId);

            return [
                'success'          => true,
                'device_id'        => $deviceId,
                'device_name'      => $device['FingerprintName'],
                'records_fetched'  => count($logs),
                'records_imported' => $imported,
                'error'            => null,
            ];
        } catch (\Exception $e) {
            return [
                'success'          => false,
                'device_id'        => $deviceId,
                'device_name'      => $device['FingerprintName'],
                'error'            => $e->getMessage(),
                'records_imported' => 0,
            ];
        }
    }

    /**
     * Fetch attendance logs from a device
     * Returns array of ['finger_id' => string, 'datetime' => string, 'type' => int]
     */
    private function fetchDeviceLogs(string $ip, string $port, string $deviceType): array|false
    {
        $url = "http://{$ip}:{$port}";

        switch (strtolower($deviceType)) {
            case 'zkteco':
            case 'zk':
                return $this->fetchZKTeco($url);

            case 'hikvision':
                return $this->fetchHikvision($url);

            default:
                // Generic HTTP API - try ZKTeco format
                return $this->fetchZKTeco($url);
        }
    }

    /**
     * Fetch from ZKTeco device via SOAP/HTTP
     * ZKTeco devices typically expose attendance via /iclock/cdata
     */
    private function fetchZKTeco(string $baseUrl): array|false
    {
        // ZKTeco push protocol: device pushes to server
        // For pull: use /iclock/getrequest or direct SOAP
        $url = $baseUrl . '/iclock/cdata?cmd=getattlog&stn=false';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Content-Type: text/plain'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return false;
        }

        // Parse ZKTeco attendance log format
        // Format: PIN\tDatetime\tStatus\tVerify\tWorkcode\tReserved
        $logs = [];
        $lines = explode("\n", trim($response));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode("\t", $line);
            if (count($parts) < 3) continue;

            $fingerId = trim($parts[0]);
            $datetime = trim($parts[1]);
            $status = (int) trim($parts[2]); // 0=check-in, 1=check-out

            if (empty($fingerId) || empty($datetime)) continue;

            $logs[] = [
                'finger_id' => $fingerId,
                'datetime'  => $datetime,
                'type'      => $status === 0 ? 1 : 2, // 1=in, 2=out
            ];
        }

        return $logs;
    }

    /**
     * Fetch from Hikvision device
     */
    private function fetchHikvision(string $baseUrl): array|false
    {
        // Hikvision uses ISAPI
        $url = $baseUrl . '/ISAPI/AccessControl/AcsEvent?format=json';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_HTTPAUTH       => CURLAUTH_DIGEST,
            CURLOPT_USERPWD        => 'admin:admin123', // Should be configurable
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return false;
        }

        $data = json_decode($response, true);
        $logs = [];

        $events = $data['AcsEvent']['InfoList'] ?? [];
        foreach ($events as $event) {
            $logs[] = [
                'finger_id' => $event['employeeNoString'] ?? '',
                'datetime'  => $event['time'] ?? '',
                'type'      => 1, // Hikvision doesn't distinguish in/out by default
            ];
        }

        return $logs;
    }

    /**
     * Import device logs into tblattendance
     * Skips duplicates based on EmpID + Date + Time + source
     */
    private function importLogs(array $logs, int $branchId, int $deviceId): int
    {
        $imported = 0;

        // Build finger_id → UserID mapping
        $fingerMap = $this->getFingerIdMap();

        $insertStm = $this->pdo->prepare(
            "INSERT INTO attendancet (EmpID, BranchID, Date, Type, Time, method, who_add)
             VALUES (:emp, :branch, :date, :type, :time, 'import', NULL)"
        );

        $checkStm = $this->pdo->prepare(
            "SELECT ID FROM attendancet
             WHERE EmpID = :emp AND Date = :date AND Time = :time AND method = 'import'
             LIMIT 1"
        );

        foreach ($logs as $log) {
            $fingerId = $log['finger_id'];
            $userId = $fingerMap[$fingerId] ?? null;

            if (!$userId) {
                continue; // Unknown finger ID
            }

            // Parse datetime
            $dt = strtotime($log['datetime']);
            if (!$dt) continue;

            $date = date('Y-m-d', $dt);
            $time = date('H:i:s', $dt);

            // Check for duplicate
            $checkStm->execute([':emp' => $userId, ':date' => $date, ':time' => $time]);
            if ($checkStm->fetch()) {
                continue; // Already imported
            }

            // Insert
            $insertStm->execute([
                ':emp'    => $userId,
                ':branch' => $branchId,
                ':date'   => $date,
                ':type'   => $log['type'],
                ':time'   => $time,
            ]);

            $imported++;
        }

        // Log sync event
        if ($imported > 0) {
            try {
                $this->pdo->prepare(
                    "INSERT INTO audit_log (user_id, action, table_name, notes, ip_address, created_at)
                     VALUES (NULL, 'device_sync', 'attendancet', :notes, :ip, NOW())"
                )->execute([
                    ':notes' => "Device #$deviceId synced: $imported records imported",
                    ':ip'    => $_SERVER['REMOTE_ADDR'] ?? 'cron',
                ]);
            } catch (\PDOException $e) {
                // Audit log table may not exist yet
            }
        }

        return $imported;
    }

    /**
     * Build mapping of FingerID → UserID from tblusers and tblremewal
     */
    private function getFingerIdMap(): array
    {
        $map = [];

        // From tblusers.FingerID
        $stm = $this->pdo->prepare(
            "SELECT UserID, FingerID FROM tblusers WHERE FingerID IS NOT NULL AND FingerID != ''"
        );
        $stm->execute();
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['FingerID']] = (int) $row['UserID'];
        }

        // From tblremewal.fingerID (latest contract)
        $stm2 = $this->pdo->prepare(
            "SELECT r.UserID, r.fingerID
             FROM tblremewal r
             INNER JOIN (SELECT UserID, MAX(Id) as maxId FROM tblremewal WHERE state IS NOT NULL GROUP BY UserID) latest
                ON r.Id = latest.maxId
             WHERE r.fingerID IS NOT NULL AND r.fingerID != ''"
        );
        $stm2->execute();
        while ($row = $stm2->fetch(PDO::FETCH_ASSOC)) {
            $map[$row['fingerID']] = (int) $row['UserID'];
        }

        return $map;
    }

    /**
     * Test connection to a device
     */
    public function testConnection(int $deviceId): array
    {
        $stm = $this->pdo->prepare(
            "SELECT ip, port, FingerprintName FROM tbfingerprint WHERE FingerprintID = :id LIMIT 1"
        );
        $stm->execute([':id' => $deviceId]);
        $device = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$device || empty($device['ip'])) {
            return ['connected' => false, 'error' => 'الجهاز غير موجود أو لا يحتوي على عنوان IP'];
        }

        $ip = $device['ip'];
        $port = $device['port'] ?? '80';

        // Try TCP connection
        $fp = @fsockopen($ip, (int) $port, $errno, $errstr, 5);
        if ($fp) {
            fclose($fp);
            return [
                'connected'   => true,
                'device_name' => $device['FingerprintName'],
                'ip'          => $ip,
                'port'        => $port,
            ];
        }

        return [
            'connected'   => false,
            'device_name' => $device['FingerprintName'],
            'ip'          => $ip,
            'port'        => $port,
            'error'       => "$errstr (code: $errno)",
        ];
    }
}
