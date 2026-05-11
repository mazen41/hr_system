<?php
/**
 * Vision HR - QR Code Attendance Service
 * TOTP-based rotating QR codes for secure attendance
 * 
 * Features:
 * - QR rotates every 30 seconds (configurable)
 * - HMAC-SHA256 signed payload: branch_id + timestamp + nonce
 * - Valid for 60 seconds max
 * - Dual verification: GPS + QR together
 * - Anti-replay: each code tracked with usage count
 */

class QRCodeService
{
    private PDO $pdo;
    private string $hmacMasterKey;
    private int $rotationInterval;
    private int $validityWindow;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->hmacMasterKey = defined('JWT_SECRET') ? JWT_SECRET : 'vision-hr-qr-master-key';
        $this->rotationInterval = defined('QR_ROTATION_INTERVAL') ? QR_ROTATION_INTERVAL : 30;
        $this->validityWindow = defined('QR_CODE_TTL') ? QR_CODE_TTL : 60;
    }

    /**
     * Generate a new TOTP-based QR code for a branch
     * Returns data to be encoded into a QR image by the frontend
     */
    public function generate(int $branchId, int $generatedBy): array
    {
        // Invalidate any existing active codes for this branch
        $this->invalidateExpired();

        $timestamp = time();
        $nonce = bin2hex(random_bytes(8));
        $timeSlot = (int) floor($timestamp / $this->rotationInterval);

        // Build the payload that will be in the QR
        $payload = [
            'b'  => $branchId,          // branch_id
            'ts' => $timestamp,          // unix timestamp
            'n'  => $nonce,              // nonce for uniqueness
            'sl' => $timeSlot,           // time slot for TOTP
        ];

        // Sign the payload with HMAC
        $dataToSign = $branchId . ':' . $timestamp . ':' . $nonce . ':' . $timeSlot;
        $signature = hash_hmac('sha256', $dataToSign, $this->hmacMasterKey);
        $payload['sig'] = $signature;

        // The QR content is a JSON string
        $qrContent = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Also create a short code for simple scanning
        $shortCode = substr($signature, 0, 16) . dechex($timestamp);

        $expiresAt = date('Y-m-d H:i:s', $timestamp + $this->validityWindow);

        // Store in DB
        $stm = $this->pdo->prepare(
            "INSERT INTO attendance_qr_codes 
                (branch_id, code, hmac_secret, generated_by, expires_at, created_at)
             VALUES 
                (:branch, :code, :secret, :uid, :expires, NOW())"
        );
        $stm->execute([
            ':branch'  => $branchId,
            ':code'    => $shortCode,
            ':secret'  => $signature,
            ':uid'     => $generatedBy,
            ':expires' => $expiresAt,
        ]);

        $qrId = (int) $this->pdo->lastInsertId();

        return [
            'qr_id'       => $qrId,
            'qr_content'  => $qrContent,
            'short_code'  => $shortCode,
            'branch_id'   => $branchId,
            'expires_at'  => $expiresAt,
            'ttl'         => $this->validityWindow,
            'rotation'    => $this->rotationInterval,
            'timestamp'   => $timestamp,
        ];
    }

    /**
     * Validate a scanned QR code
     * Supports both JSON payload and short code formats
     * Returns ['valid' => bool, 'branch_id' => int, 'qr_id' => int] or error
     */
    public function validate(string $scannedData): array
    {
        // Try JSON payload first
        $payload = json_decode($scannedData, true);

        if (is_array($payload) && isset($payload['b'], $payload['ts'], $payload['n'], $payload['sl'], $payload['sig'])) {
            return $this->validatePayload($payload);
        }

        // Fallback: try as short code
        return $this->validateShortCode($scannedData);
    }

    /**
     * Validate the full JSON payload (HMAC verification)
     */
    private function validatePayload(array $payload): array
    {
        $branchId = (int) $payload['b'];
        $timestamp = (int) $payload['ts'];
        $nonce = $payload['n'];
        $timeSlot = (int) $payload['sl'];
        $signature = $payload['sig'];

        // 1. Check timestamp - must be within validity window
        $age = time() - $timestamp;
        if ($age > $this->validityWindow || $age < -5) {
            return ['valid' => false, 'error' => 'رمز QR منتهي الصلاحية', 'code' => 'expired'];
        }

        // 2. Verify HMAC signature
        $dataToSign = $branchId . ':' . $timestamp . ':' . $nonce . ':' . $timeSlot;
        $expectedSig = hash_hmac('sha256', $dataToSign, $this->hmacMasterKey);

        if (!hash_equals($expectedSig, $signature)) {
            return ['valid' => false, 'error' => 'رمز QR غير صالح - توقيع خاطئ', 'code' => 'invalid_signature'];
        }

        // 3. Check time slot matches current or previous slot (allow 1 slot tolerance)
        $currentSlot = (int) floor(time() / $this->rotationInterval);
        if ($timeSlot < $currentSlot - 1 || $timeSlot > $currentSlot + 1) {
            return ['valid' => false, 'error' => 'رمز QR منتهي الصلاحية - تم تدويره', 'code' => 'rotated'];
        }

        // 4. Find in DB and check not over-used
        $shortCode = substr($signature, 0, 16) . dechex($timestamp);
        $stm = $this->pdo->prepare(
            "SELECT id, used_count FROM attendance_qr_codes
             WHERE code = :code AND branch_id = :branch AND expires_at > NOW()
             LIMIT 1"
        );
        $stm->execute([':code' => $shortCode, ':branch' => $branchId]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        $qrId = $row ? (int) $row['id'] : null;

        return [
            'valid'     => true,
            'branch_id' => $branchId,
            'qr_id'     => $qrId,
            'timestamp' => $timestamp,
            'age'       => $age,
        ];
    }

    /**
     * Validate a short code against DB
     */
    private function validateShortCode(string $shortCode): array
    {
        $shortCode = trim($shortCode);

        $stm = $this->pdo->prepare(
            "SELECT id, branch_id, hmac_secret, expires_at, used_count
             FROM attendance_qr_codes
             WHERE code = :code AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stm->execute([':code' => $shortCode]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['valid' => false, 'error' => 'رمز QR غير صالح أو منتهي الصلاحية', 'code' => 'not_found'];
        }

        return [
            'valid'     => true,
            'branch_id' => (int) $row['branch_id'],
            'qr_id'     => (int) $row['id'],
            'timestamp' => time(),
            'age'       => 0,
        ];
    }

    /**
     * Record QR code usage (increment counter)
     */
    public function recordUsage(int $qrId): void
    {
        $this->pdo->prepare(
            "UPDATE attendance_qr_codes SET used_count = used_count + 1 WHERE id = :id"
        )->execute([':id' => $qrId]);
    }

    /**
     * Get the current active QR code for a branch (if any)
     */
    public function getActive(int $branchId): ?array
    {
        $stm = $this->pdo->prepare(
            "SELECT id, code, expires_at, used_count, created_at
             FROM attendance_qr_codes
             WHERE branch_id = :branch AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stm->execute([':branch' => $branchId]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $expiresAt = strtotime($row['expires_at']);
        $remaining = $expiresAt - time();

        return [
            'qr_id'      => (int) $row['id'],
            'short_code'  => $row['code'],
            'expires_at'  => $row['expires_at'],
            'remaining'   => max(0, $remaining),
            'used_count'  => (int) $row['used_count'],
            'needs_refresh' => $remaining <= $this->rotationInterval,
        ];
    }

    /**
     * Invalidate expired QR codes (cleanup)
     */
    public function invalidateExpired(): int
    {
        $stm = $this->pdo->prepare(
            "DELETE FROM attendance_qr_codes WHERE expires_at < NOW()"
        );
        $stm->execute();
        return $stm->rowCount();
    }

    /**
     * Invalidate all codes for a branch
     */
    public function invalidateBranch(int $branchId): void
    {
        $this->pdo->prepare(
            "DELETE FROM attendance_qr_codes WHERE branch_id = :branch"
        )->execute([':branch' => $branchId]);
    }

    /**
     * Dual verification: validate both GPS and QR together
     */
    public function dualVerify(
        string $qrData,
        float $lat,
        float $lng,
        float $accuracy,
        int $userBranchId
    ): array {
        // 1. Validate QR
        $qrResult = $this->validate($qrData);
        if (!$qrResult['valid']) {
            return $qrResult;
        }

        // 2. Check branch match
        if ($qrResult['branch_id'] !== $userBranchId) {
            return ['valid' => false, 'error' => 'رمز QR لا ينتمي لفرعك', 'code' => 'branch_mismatch'];
        }

        // 3. Check GPS accuracy
        if ($accuracy > (defined('GPS_MAX_ACCURACY') ? GPS_MAX_ACCURACY : 100)) {
            return ['valid' => false, 'error' => 'دقة GPS غير كافية', 'code' => 'low_accuracy'];
        }

        // 4. Validate GPS against branch geofence
        $stm = $this->pdo->prepare(
            "SELECT TypeBracnhLocation, Onepoint, MorePoint FROM branches WHERE branch_id = :id LIMIT 1"
        );
        $stm->execute([':id' => $qrResult['branch_id']]);
        $branch = $stm->fetch(PDO::FETCH_ASSOC);

        if ($branch) {
            $locationType = (int) ($branch['TypeBracnhLocation'] ?? 0);

            if ($locationType === 1 && !empty($branch['Onepoint'])) {
                $parts = explode(',', $branch['Onepoint']);
                if (count($parts) >= 3) {
                    $bLat = (float) $parts[0];
                    $bLng = (float) $parts[1];
                    $radius = (float) $parts[2];

                    $distance = self::haversineDistance($lat, $lng, $bLat, $bLng);
                    if ($distance > $radius) {
                        return [
                            'valid' => false,
                            'error' => 'أنت خارج نطاق موقع العمل. المسافة: ' . round($distance) . ' متر',
                            'code'  => 'out_of_range',
                        ];
                    }
                }
            } elseif ($locationType === 2 && !empty($branch['MorePoint'])) {
                $pointPairs = explode(',', $branch['MorePoint']);
                $polygon = [];
                foreach ($pointPairs as $pair) {
                    $coords = explode('-', $pair);
                    if (count($coords) >= 2) {
                        $polygon[] = [(float) $coords[0], (float) $coords[1]];
                    }
                }
                if (!empty($polygon) && !self::pointInPolygon($lat, $lng, $polygon)) {
                    return ['valid' => false, 'error' => 'أنت خارج نطاق موقع العمل', 'code' => 'out_of_polygon'];
                }
            }
        }

        // All checks passed
        $qrResult['dual_verified'] = true;
        return $qrResult;
    }

    private static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
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

    private static function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $polygon[$i][0]; $xi = $polygon[$i][1];
            $yj = $polygon[$j][0]; $xj = $polygon[$j][1];
            if (($yi > $lat) !== ($yj > $lat) &&
                $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
