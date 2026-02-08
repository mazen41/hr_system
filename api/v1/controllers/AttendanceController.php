<?php
/**
 * Vision HR - Attendance Controller
 * Check-in/out, GPS validation, QR code attendance, history
 */

class AttendanceController
{
    /**
     * POST /attendance/check-in
     * Record employee check-in with GPS validation
     */
    public static function checkIn(): void
    {
        global $connect_pdo, $auditLog, $antiSpoof;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('lat', 'خط العرض')
          ->required('lng', 'خط الطول')
          ->latitude('lat')
          ->longitude('lng');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $lat = (float) $body['lat'];
        $lng = (float) $body['lng'];
        $accuracy = isset($body['accuracy']) ? (float) $body['accuracy'] : null;
        $mockLocation = !empty($body['mock_location']) ? 1 : null;
        $deviceFingerprint = $body['device_fingerprint'] ?? null;
        $branchId = $apiUser['branch_id'];

        // Comprehensive anti-spoofing check
        $spoofCheck = $antiSpoof->check([
            'user_id'            => $apiUser['id'],
            'lat'                => $lat,
            'lng'                => $lng,
            'accuracy'           => $accuracy,
            'mock_location'      => $mockLocation,
            'device_fingerprint' => $deviceFingerprint,
            'branch_id'          => $branchId,
            'ip'                 => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        if (!$spoofCheck['allowed']) {
            $auditLog->log($apiUser['id'], 'antispoof_blocked', 'tblattendance', null, null, [
                'reason'     => $spoofCheck['reason'],
                'risk_score' => $spoofCheck['risk_score'],
                'warnings'   => $spoofCheck['warnings'],
                'lat' => $lat, 'lng' => $lng,
            ]);
            $msg = implode('. ', $spoofCheck['warnings']);
            Response::error('تم رفض تسجيل الحضور: ' . $msg, 403);
        }

        // Validate location against branch geofence
        self::validateLocation($branchId, $lat, $lng);

        // Check if already checked in today
        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT AttendanceID, Type FROM tblattendance
             WHERE EmpID = :uid AND Date = :date
             ORDER BY AttendanceID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $lastRecord = $stm->fetch(PDO::FETCH_ASSOC);

        if ($lastRecord && (int) $lastRecord['Type'] === 1) {
            Response::error('أنت مسجل حضور بالفعل. يرجى تسجيل الانصراف أولاً', 409);
        }

        // Insert attendance record
        $stm2 = $connect_pdo->prepare(
            "INSERT INTO tblattendance 
                (EmpID, BranchID, Date, Type, Time, who_add, source, lat, lng, device_fingerprint, gps_accuracy, mock_location, risk_score, CreatedDate)
             VALUES 
                (:uid, :branch, :date, 1, :time, :uid, 'app', :lat, :lng, :device, :accuracy, :mock, :risk, NOW())"
        );
        $stm2->execute([
            ':uid'      => $apiUser['id'],
            ':branch'   => $branchId,
            ':date'     => $today,
            ':time'     => date('H:i:s'),
            ':lat'      => $lat,
            ':lng'      => $lng,
            ':device'   => $deviceFingerprint,
            ':accuracy' => $accuracy,
            ':mock'     => $mockLocation,
            ':risk'     => $spoofCheck['risk_score'],
        ]);

        $attendanceId = (int) $connect_pdo->lastInsertId();

        $auditLog->logCreate($apiUser['id'], 'tblattendance', $attendanceId, [
            'type' => 'check-in', 'source' => 'app', 'lat' => $lat, 'lng' => $lng
        ]);

        // Check lateness
        $lateInfo = self::checkLateness($apiUser, $today);

        Response::success([
            'attendance_id' => $attendanceId,
            'type'          => 'check-in',
            'date'          => $today,
            'time'          => date('H:i:s'),
            'late'          => $lateInfo,
        ], 'تم تسجيل الحضور بنجاح');
    }

    /**
     * POST /attendance/check-out
     * Record employee check-out
     */
    public static function checkOut(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('lat', 'خط العرض')
          ->required('lng', 'خط الطول')
          ->latitude('lat')
          ->longitude('lng');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $lat = (float) $body['lat'];
        $lng = (float) $body['lng'];
        $accuracy = isset($body['accuracy']) ? (float) $body['accuracy'] : null;
        $deviceFingerprint = $body['device_fingerprint'] ?? null;

        // Anti-spoofing
        if ($accuracy !== null && $accuracy > GPS_MAX_ACCURACY) {
            Response::error('دقة GPS غير كافية', 422);
        }

        if (!empty($body['mock_location'])) {
            Response::error('تم اكتشاف موقع وهمي', 403);
        }

        // Validate location
        self::validateLocation($apiUser['branch_id'], $lat, $lng);

        // Check if checked in today
        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT AttendanceID, Type FROM tblattendance
             WHERE EmpID = :uid AND Date = :date
             ORDER BY AttendanceID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $lastRecord = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$lastRecord || (int) $lastRecord['Type'] !== 1) {
            Response::error('لم يتم تسجيل الحضور بعد. يرجى تسجيل الحضور أولاً', 409);
        }

        // Insert check-out record
        $stm2 = $connect_pdo->prepare(
            "INSERT INTO tblattendance 
                (EmpID, BranchID, Date, Type, Time, who_add, source, lat, lng, device_fingerprint, gps_accuracy, CreatedDate)
             VALUES 
                (:uid, :branch, :date, 2, :time, :uid, 'app', :lat, :lng, :device, :accuracy, NOW())"
        );
        $stm2->execute([
            ':uid'      => $apiUser['id'],
            ':branch'   => $apiUser['branch_id'],
            ':date'     => $today,
            ':time'     => date('H:i:s'),
            ':lat'      => $lat,
            ':lng'      => $lng,
            ':device'   => $deviceFingerprint,
            ':accuracy' => $accuracy,
        ]);

        $attendanceId = (int) $connect_pdo->lastInsertId();

        $auditLog->logCreate($apiUser['id'], 'tblattendance', $attendanceId, [
            'type' => 'check-out', 'source' => 'app'
        ]);

        // Calculate working hours
        $stm3 = $connect_pdo->prepare(
            "SELECT Time FROM tblattendance
             WHERE EmpID = :uid AND Date = :date AND Type = 1
             ORDER BY AttendanceID ASC LIMIT 1"
        );
        $stm3->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $checkInRow = $stm3->fetch(PDO::FETCH_ASSOC);

        $workingHours = null;
        if ($checkInRow) {
            $inTime = strtotime($today . ' ' . $checkInRow['Time']);
            $outTime = strtotime($today . ' ' . date('H:i:s'));
            $diff = $outTime - $inTime;
            $workingHours = round($diff / 3600, 2);
        }

        Response::success([
            'attendance_id' => $attendanceId,
            'type'          => 'check-out',
            'date'          => $today,
            'time'          => date('H:i:s'),
            'working_hours' => $workingHours,
        ], 'تم تسجيل الانصراف بنجاح');
    }

    /**
     * GET /attendance/today
     * Get today's attendance status
     */
    public static function today(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT AttendanceID, Type, Time, source, lat, lng
             FROM tblattendance
             WHERE EmpID = :uid AND Date = :date
             ORDER BY AttendanceID ASC"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $records = $stm->fetchAll(PDO::FETCH_ASSOC);

        $checkIn = null;
        $checkOut = null;
        $status = 'absent';

        foreach ($records as $r) {
            if ((int) $r['Type'] === 1 && !$checkIn) {
                $checkIn = $r['Time'];
                $status = 'checked_in';
            }
            if ((int) $r['Type'] === 2) {
                $checkOut = $r['Time'];
                $status = 'checked_out';
            }
        }

        $workingHours = null;
        if ($checkIn && $checkOut) {
            $diff = strtotime($today . ' ' . $checkOut) - strtotime($today . ' ' . $checkIn);
            $workingHours = round($diff / 3600, 2);
        }

        // Get shift info
        $shiftInfo = self::getShiftInfo($apiUser);

        Response::success([
            'date'          => $today,
            'status'        => $status,
            'check_in'      => $checkIn,
            'check_out'     => $checkOut,
            'working_hours' => $workingHours,
            'records_count' => count($records),
            'shift'         => $shiftInfo,
        ]);
    }

    /**
     * GET /attendance/history
     * Get attendance history with optional date range
     */
    public static function history(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $pagination = Validator::pagination();
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $stm = $connect_pdo->prepare(
            "SELECT AttendanceID, Date, Type, Time, source, lat, lng, CreatedDate
             FROM tblattendance
             WHERE EmpID = :uid AND Date BETWEEN :from AND :to
             ORDER BY Date DESC, AttendanceID ASC
             LIMIT :limit OFFSET :offset"
        );
        $stm->bindValue(':uid', $apiUser['id'], PDO::PARAM_INT);
        $stm->bindValue(':from', $dateFrom);
        $stm->bindValue(':to', $dateTo);
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $records = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $stm2 = $connect_pdo->prepare(
            "SELECT COUNT(*) as total FROM tblattendance
             WHERE EmpID = :uid AND Date BETWEEN :from AND :to"
        );
        $stm2->execute([':uid' => $apiUser['id'], ':from' => $dateFrom, ':to' => $dateTo]);
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        // Group by date
        $grouped = [];
        foreach ($records as $r) {
            $date = $r['Date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'check_in' => null, 'check_out' => null, 'records' => []];
            }
            $entry = [
                'id'     => (int) $r['AttendanceID'],
                'type'   => (int) $r['Type'] === 1 ? 'check_in' : 'check_out',
                'time'   => $r['Time'],
                'source' => $r['source'],
            ];
            $grouped[$date]['records'][] = $entry;

            if ((int) $r['Type'] === 1 && !$grouped[$date]['check_in']) {
                $grouped[$date]['check_in'] = $r['Time'];
            }
            if ((int) $r['Type'] === 2) {
                $grouped[$date]['check_out'] = $r['Time'];
            }
        }

        Response::paginated(array_values($grouped), $total, $pagination['page'], $pagination['per_page']);
    }

    /**
     * POST /attendance/qr-scan
     * Record attendance via QR code scan
     */
    public static function qrScan(): void
    {
        global $connect_pdo, $auditLog, $qrService;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('qr_code', 'رمز QR')
          ->required('lat', 'خط العرض')
          ->required('lng', 'خط الطول');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $qrCode = $body['qr_code'];
        $lat = (float) $body['lat'];
        $lng = (float) $body['lng'];
        $accuracy = isset($body['accuracy']) ? (float) $body['accuracy'] : null;

        // Validate QR via QRCodeService (HMAC + expiry + TOTP)
        $qrResult = $qrService->validate($qrCode);
        if (!$qrResult['valid']) {
            Response::error($qrResult['error'], 422);
        }

        // Validate branch match
        if ($qrResult['branch_id'] !== $apiUser['branch_id']) {
            Response::error('رمز QR لا ينتمي لفرعك', 403);
        }

        $qrId = $qrResult['qr_id'];

        // Determine check-in or check-out
        $today = date('Y-m-d');
        $stm2 = $connect_pdo->prepare(
            "SELECT Type FROM tblattendance
             WHERE EmpID = :uid AND Date = :date
             ORDER BY AttendanceID DESC LIMIT 1"
        );
        $stm2->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $last = $stm2->fetch(PDO::FETCH_ASSOC);

        $type = (!$last || (int) $last['Type'] === 2) ? 1 : 2;

        // Insert record
        $stm3 = $connect_pdo->prepare(
            "INSERT INTO tblattendance 
                (EmpID, BranchID, Date, Type, Time, who_add, source, lat, lng, qr_code_id, CreatedDate)
             VALUES 
                (:uid, :branch, :date, :type, :time, :uid, 'qr', :lat, :lng, :qr_id, NOW())"
        );
        $stm3->execute([
            ':uid'    => $apiUser['id'],
            ':branch' => $apiUser['branch_id'],
            ':date'   => $today,
            ':type'   => $type,
            ':time'   => date('H:i:s'),
            ':lat'    => $lat,
            ':lng'    => $lng,
            ':qr_id'  => $qrId,
        ]);

        // Record QR usage
        $qrService->recordUsage($qrId);

        $attendanceId = (int) $connect_pdo->lastInsertId();
        $typeLabel = $type === 1 ? 'حضور' : 'انصراف';

        $auditLog->logCreate($apiUser['id'], 'tblattendance', $attendanceId, [
            'type' => $type === 1 ? 'check-in' : 'check-out', 'source' => 'qr'
        ]);

        Response::success([
            'attendance_id' => $attendanceId,
            'type'          => $type === 1 ? 'check_in' : 'check_out',
            'date'          => $today,
            'time'          => date('H:i:s'),
        ], "تم تسجيل ال{$typeLabel} عبر QR بنجاح");
    }

    /**
     * GET /attendance/qr-generate
     * Generate TOTP-based rotating QR code for attendance (admin/manager only)
     */
    public static function qrGenerate(): void
    {
        global $qrService;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف', 'عرض الحضور والانصراف']);

        $branchId = (int) ($_GET['branch_id'] ?? $apiUser['branch_id']);
        requireBranchAccess($apiUser, $branchId);

        $result = $qrService->generate($branchId, $apiUser['id']);

        Response::success($result, 'تم توليد رمز QR بنجاح');
    }

    /**
     * GET /attendance/qr-active
     * Get the current active QR code for a branch (if any)
     */
    public static function qrActive(): void
    {
        global $qrService;
        $apiUser = authMiddleware();
        rbacMiddleware($apiUser, ['الحضور والانصراف', 'عرض الحضور والانصراف']);

        $branchId = (int) ($_GET['branch_id'] ?? $apiUser['branch_id']);
        requireBranchAccess($apiUser, $branchId);

        $active = $qrService->getActive($branchId);

        if (!$active) {
            Response::success(['active' => false], 'لا يوجد رمز QR نشط حالياً');
            return;
        }

        $active['active'] = true;
        Response::success($active);
    }

    // ---- Private helpers ----

    /**
     * Validate GPS location against branch geofence
     */
    private static function validateLocation(int $branchId, float $lat, float $lng): void
    {
        global $connect_pdo;

        $stm = $connect_pdo->prepare(
            "SELECT TypeBracnhLocation, Onepoint, MorePoint FROM branches WHERE branch_id = :id LIMIT 1"
        );
        $stm->execute([':id' => $branchId]);
        $branch = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$branch) {
            return; // No branch config, allow
        }

        $locationType = (int) ($branch['TypeBracnhLocation'] ?? 0);

        if ($locationType === 1 && !empty($branch['Onepoint'])) {
            // Single point + radius
            $parts = explode(',', $branch['Onepoint']);
            if (count($parts) >= 3) {
                $bLat = (float) $parts[0];
                $bLng = (float) $parts[1];
                $radius = (float) $parts[2]; // meters

                $distance = self::haversineDistance($lat, $lng, $bLat, $bLng);
                if ($distance > $radius) {
                    Response::error(
                        'أنت خارج نطاق موقع العمل. المسافة: ' . round($distance) . ' متر',
                        403
                    );
                }
            }
        } elseif ($locationType === 2 && !empty($branch['MorePoint'])) {
            // Polygon
            $pointPairs = explode(',', $branch['MorePoint']);
            $polygon = [];
            foreach ($pointPairs as $pair) {
                $coords = explode('-', $pair);
                if (count($coords) >= 2) {
                    $polygon[] = [(float) $coords[0], (float) $coords[1]];
                }
            }

            if (!empty($polygon) && !self::pointInPolygon($lat, $lng, $polygon)) {
                Response::error('أنت خارج نطاق موقع العمل', 403);
            }
        }
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     */
    private static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a point is inside a polygon (ray casting algorithm)
     */
    private static function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $polygon[$i][0];
            $xi = $polygon[$i][1];
            $yj = $polygon[$j][0];
            $xj = $polygon[$j][1];

            if (($yi > $lat) !== ($yj > $lat) &&
                $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Check if employee is late based on shift
     */
    private static function checkLateness(array $apiUser, string $date): ?array
    {
        global $connect_pdo;

        $shiftId = $apiUser['contract']['shiftID'] ?? null;
        if (!$shiftId) {
            return null;
        }

        $dayOfWeek = (int) date('w', strtotime($date)); // 0=Sunday

        // Check shift schedule for today
        $stm = $connect_pdo->prepare(
            "SELECT start_time, is_off FROM shifts_schedule
             WHERE shift_id = :sid AND day_of_week = :dow
             LIMIT 1"
        );
        $stm->execute([':sid' => $shiftId, ':dow' => $dayOfWeek]);
        $schedule = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$schedule || !empty($schedule['is_off'])) {
            return null; // Day off
        }

        $shiftStart = $schedule['start_time'] ?? null;
        if (!$shiftStart) {
            // Fallback to main shift times
            $stm2 = $connect_pdo->prepare("SELECT ShiftStartTime FROM tbshift WHERE ShiftID = :sid LIMIT 1");
            $stm2->execute([':sid' => $shiftId]);
            $shift = $stm2->fetch(PDO::FETCH_ASSOC);
            $shiftStart = $shift['ShiftStartTime'] ?? null;
        }

        if (!$shiftStart) {
            return null;
        }

        // Get tolerance
        $stm3 = $connect_pdo->prepare("SELECT late_tolerance FROM shift_setting WHERE shift_id = :sid LIMIT 1");
        $stm3->execute([':sid' => $shiftId]);
        $setting = $stm3->fetch(PDO::FETCH_ASSOC);
        $tolerance = (int) ($setting['late_tolerance'] ?? 0);

        $now = strtotime(date('H:i:s'));
        $start = strtotime($shiftStart);
        $allowedTime = $start + ($tolerance * 60);

        if ($now > $allowedTime) {
            $lateMinutes = (int) round(($now - $start) / 60);
            return [
                'is_late'      => true,
                'minutes'      => $lateMinutes,
                'shift_start'  => $shiftStart,
                'tolerance'    => $tolerance,
            ];
        }

        return ['is_late' => false, 'shift_start' => $shiftStart];
    }

    /**
     * Get shift info for the current user
     */
    private static function getShiftInfo(array $apiUser): ?array
    {
        global $connect_pdo;

        $shiftId = $apiUser['contract']['shiftID'] ?? null;
        if (!$shiftId) {
            return null;
        }

        $stm = $connect_pdo->prepare(
            "SELECT ShiftName, ShiftStartTime, ShiftEndTime FROM tbshift WHERE ShiftID = :sid LIMIT 1"
        );
        $stm->execute([':sid' => $shiftId]);
        $shift = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$shift) {
            return null;
        }

        return [
            'name'       => $shift['ShiftName'],
            'start_time' => $shift['ShiftStartTime'],
            'end_time'   => $shift['ShiftEndTime'],
        ];
    }
}
