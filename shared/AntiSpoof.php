<?php
/**
 * Vision HR - Anti-Spoofing Service
 * GPS spoofing prevention, device fingerprint tracking, IP range validation
 */

class AntiSpoof
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Full anti-spoofing check for attendance
     * Returns ['allowed' => bool, 'warnings' => [], 'risk_score' => int]
     */
    public function check(array $data): array
    {
        $warnings = [];
        $riskScore = 0;

        // 1. Mock location detection
        if (!empty($data['mock_location'])) {
            return [
                'allowed'    => false,
                'warnings'   => ['تم اكتشاف موقع وهمي (Mock Location)'],
                'risk_score' => 100,
                'reason'     => 'mock_location',
            ];
        }

        // 2. GPS accuracy check
        $accuracy = $data['accuracy'] ?? null;
        $maxAccuracy = defined('GPS_MAX_ACCURACY') ? GPS_MAX_ACCURACY : 100;
        if ($accuracy !== null && (float) $accuracy > $maxAccuracy) {
            $warnings[] = 'دقة GPS منخفضة: ' . round($accuracy) . 'm (الحد: ' . $maxAccuracy . 'm)';
            $riskScore += 40;
        }

        // 3. Device fingerprint consistency check
        $userId = $data['user_id'] ?? null;
        $deviceFp = $data['device_fingerprint'] ?? null;
        if ($userId && $deviceFp) {
            $fpCheck = $this->checkDeviceFingerprint($userId, $deviceFp);
            if ($fpCheck['is_new']) {
                $warnings[] = 'جهاز جديد لم يُستخدم من قبل';
                $riskScore += 10;
            }
            if ($fpCheck['device_count'] > 3) {
                $warnings[] = 'تم استخدام أكثر من 3 أجهزة مختلفة';
                $riskScore += 20;
            }
        }

        // 4. IP range check
        $ip = $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $branchId = $data['branch_id'] ?? null;
        if ($ip && $branchId) {
            $ipCheck = $this->checkIpRange($branchId, $ip);
            if (!$ipCheck['in_range'] && $ipCheck['has_ranges']) {
                $warnings[] = 'عنوان IP خارج نطاق الشركة';
                $riskScore += 15;
            }
        }

        // 5. Velocity check (impossible travel)
        if ($userId) {
            $velocityCheck = $this->checkVelocity($userId, (float) ($data['lat'] ?? 0), (float) ($data['lng'] ?? 0));
            if ($velocityCheck['suspicious']) {
                $warnings[] = 'انتقال مشبوه: ' . round($velocityCheck['distance']) . 'km في ' . round($velocityCheck['time_diff'] / 60) . ' دقيقة';
                $riskScore += 50;
            }
        }

        // 6. Time pattern anomaly (check-in at unusual hours)
        $hour = (int) date('H');
        if ($hour < 4 || $hour > 23) {
            $warnings[] = 'تسجيل حضور في وقت غير اعتيادي';
            $riskScore += 10;
        }

        // Decision
        $allowed = $riskScore < 70; // Block if risk >= 70

        return [
            'allowed'    => $allowed,
            'warnings'   => $warnings,
            'risk_score' => min(100, $riskScore),
            'reason'     => $allowed ? null : 'high_risk_score',
        ];
    }

    /**
     * Check device fingerprint against known devices
     */
    public function checkDeviceFingerprint(int $userId, string $fingerprint): array
    {
        // Check if this fingerprint is known for this user
        $stm = $this->pdo->prepare(
            "SELECT id, last_seen FROM device_fingerprints
             WHERE user_id = :uid AND fingerprint = :fp
             LIMIT 1"
        );
        $stm->execute([':uid' => $userId, ':fp' => $fingerprint]);
        $existing = $stm->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update last seen
            $this->pdo->prepare(
                "UPDATE device_fingerprints SET last_seen = NOW(), use_count = use_count + 1 WHERE id = :id"
            )->execute([':id' => $existing['id']]);

            $isNew = false;
        } else {
            // Register new device
            $this->pdo->prepare(
                "INSERT INTO device_fingerprints (user_id, fingerprint, device_info, ip_address, first_seen, last_seen, use_count)
                 VALUES (:uid, :fp, :info, :ip, NOW(), NOW(), 1)"
            )->execute([
                ':uid'  => $userId,
                ':fp'   => $fingerprint,
                ':info' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $isNew = true;
        }

        // Count total devices for this user (last 90 days)
        $stm2 = $this->pdo->prepare(
            "SELECT COUNT(*) as cnt FROM device_fingerprints
             WHERE user_id = :uid AND last_seen > DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        $stm2->execute([':uid' => $userId]);
        $deviceCount = (int) $stm2->fetch(PDO::FETCH_ASSOC)['cnt'];

        return [
            'is_new'       => $isNew,
            'device_count' => $deviceCount,
        ];
    }

    /**
     * Check if IP is within configured branch IP ranges
     */
    public function checkIpRange(int $branchId, string $ip): array
    {
        $stm = $this->pdo->prepare(
            "SELECT ip_range_start, ip_range_end, cidr
             FROM branch_ip_ranges
             WHERE branch_id = :branch AND is_active = 1"
        );
        $stm->execute([':branch' => $branchId]);
        $ranges = $stm->fetchAll(PDO::FETCH_ASSOC);

        if (empty($ranges)) {
            return ['in_range' => true, 'has_ranges' => false]; // No ranges configured
        }

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return ['in_range' => false, 'has_ranges' => true];
        }

        foreach ($ranges as $range) {
            // CIDR check
            if (!empty($range['cidr'])) {
                if ($this->ipInCidr($ip, $range['cidr'])) {
                    return ['in_range' => true, 'has_ranges' => true];
                }
            }

            // Range check
            if (!empty($range['ip_range_start']) && !empty($range['ip_range_end'])) {
                $startLong = ip2long($range['ip_range_start']);
                $endLong = ip2long($range['ip_range_end']);
                if ($startLong !== false && $endLong !== false) {
                    if ($ipLong >= $startLong && $ipLong <= $endLong) {
                        return ['in_range' => true, 'has_ranges' => true];
                    }
                }
            }
        }

        return ['in_range' => false, 'has_ranges' => true];
    }

    /**
     * Velocity check - detect impossible travel between two GPS points
     */
    public function checkVelocity(int $userId, float $lat, float $lng): array
    {
        // Get last attendance record with GPS
        $stm = $this->pdo->prepare(
            "SELECT lat, lng, Date, Time, CreatedDate
             FROM tblattendance
             WHERE EmpID = :uid AND lat IS NOT NULL AND lng IS NOT NULL
             ORDER BY AttendanceID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $userId]);
        $last = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$last || !$last['lat'] || !$last['lng']) {
            return ['suspicious' => false, 'distance' => 0, 'time_diff' => 0];
        }

        $lastLat = (float) $last['lat'];
        $lastLng = (float) $last['lng'];
        $lastTime = strtotime($last['Date'] . ' ' . $last['Time']);
        $now = time();
        $timeDiff = $now - $lastTime;

        if ($timeDiff <= 0 || $timeDiff > 86400) {
            return ['suspicious' => false, 'distance' => 0, 'time_diff' => $timeDiff];
        }

        // Calculate distance in km
        $distance = $this->haversineDistance($lat, $lng, $lastLat, $lastLng) / 1000;

        // Max reasonable speed: 200 km/h
        $maxSpeed = 200;
        $maxDistance = ($timeDiff / 3600) * $maxSpeed;

        $suspicious = $distance > $maxDistance && $distance > 5; // Ignore small distances

        return [
            'suspicious' => $suspicious,
            'distance'   => $distance,
            'time_diff'  => $timeDiff,
            'speed_kmh'  => $timeDiff > 0 ? round(($distance / $timeDiff) * 3600, 1) : 0,
        ];
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
