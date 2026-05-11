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
        $branchId = (int) ($apiUser['branch_id'] ?? 0);

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
            $auditLog->log($apiUser['id'], 'antispoof_blocked', 'attendancet', null, null, [
                'reason'     => $spoofCheck['reason'],
                'risk_score' => $spoofCheck['risk_score'],
                'warnings'   => $spoofCheck['warnings'],
                'lat'        => $lat,
                'lng'        => $lng,
                'method'     => 'gps',
            ]);

            $msg = implode('. ', $spoofCheck['warnings']);
            Response::error('تم رفض تسجيل الحضور: ' . $msg, 403);
        }

        self::validateLocation($branchId, $lat, $lng);

        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT ID, Type FROM attendancet
             WHERE EmpID = :uid AND Date = :date
             ORDER BY ID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $lastRecord = $stm->fetch(PDO::FETCH_ASSOC);

        if ($lastRecord && (int) $lastRecord['Type'] === 1) {
            Response::error('أنت مسجل حضور بالفعل. يرجى تسجيل الانصراف أولًا', 409);
        }

        $attendanceId = self::insertAttendanceRecord([
            'EmpID'       => $apiUser['id'],
            'Date'        => $today,
            'Type'        => 1,
            'Time'        => date('H:i:s'),
            'who_add'     => $apiUser['id'],
            'lat'         => $lat,
            'lng'         => $lng,
            'method'      => 'gps',
            'device_info' => $deviceFingerprint,
        ]);

        $auditLog->logCreate($apiUser['id'], 'attendancet', $attendanceId, [
            'type'   => 'check-in',
            'method' => 'gps',
            'lat'    => $lat,
            'lng'    => $lng,
        ]);

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

        if ($accuracy !== null && $accuracy > GPS_MAX_ACCURACY) {
            Response::error('دقة GPS غير كافية', 422);
        }

        if (!empty($body['mock_location'])) {
            Response::error('تم اكتشاف موقع وهمي', 403);
        }

        self::validateLocation((int) ($apiUser['branch_id'] ?? 0), $lat, $lng);

        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT ID, Type FROM attendancet
             WHERE EmpID = :uid AND Date = :date
             ORDER BY ID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $lastRecord = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$lastRecord || (int) $lastRecord['Type'] !== 1) {
            Response::error('لم يتم تسجيل الحضور بعد. يرجى تسجيل الحضور أولًا', 409);
        }

        $attendanceId = self::insertAttendanceRecord([
            'EmpID'       => $apiUser['id'],
            'Date'        => $today,
            'Type'        => 2,
            'Time'        => date('H:i:s'),
            'who_add'     => $apiUser['id'],
            'lat'         => $lat,
            'lng'         => $lng,
            'method'      => 'gps',
            'device_info' => $deviceFingerprint,
        ]);

        $auditLog->logCreate($apiUser['id'], 'attendancet', $attendanceId, [
            'type'   => 'check-out',
            'method' => 'gps',
        ]);

        $stm2 = $connect_pdo->prepare(
            "SELECT Time FROM attendancet
             WHERE EmpID = :uid AND Date = :date AND Type = 1
             ORDER BY ID ASC LIMIT 1"
        );
        $stm2->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $checkInRow = $stm2->fetch(PDO::FETCH_ASSOC);

        $workingHours = null;
        if ($checkInRow) {
            $inTime = strtotime($today . ' ' . $checkInRow['Time']);
            $outTime = strtotime($today . ' ' . date('H:i:s'));
            $workingHours = round(($outTime - $inTime) / 3600, 2);
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
            "SELECT ID, Type, Time, method, lat, lng
             FROM attendancet
             WHERE EmpID = :uid AND Date = :date
             ORDER BY ID ASC"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $records = $stm->fetchAll(PDO::FETCH_ASSOC);

        $checkIn = null;
        $checkOut = null;
        $status = 'absent';

        foreach ($records as $record) {
            if ((int) $record['Type'] === 1 && !$checkIn) {
                $checkIn = $record['Time'];
                $status = 'checked_in';
            }
            if ((int) $record['Type'] === 2) {
                $checkOut = $record['Time'];
                $status = 'checked_out';
            }
        }

        $workingHours = null;
        if ($checkIn && $checkOut) {
            $diff = strtotime($today . ' ' . $checkOut) - strtotime($today . ' ' . $checkIn);
            $workingHours = round($diff / 3600, 2);
        }

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
            "SELECT ID, Date, Type, Time, method, lat, lng
             FROM attendancet
             WHERE EmpID = :uid AND Date BETWEEN :from AND :to
             ORDER BY Date DESC, ID ASC
             LIMIT :limit OFFSET :offset"
        );
        $stm->bindValue(':uid', $apiUser['id'], PDO::PARAM_INT);
        $stm->bindValue(':from', $dateFrom);
        $stm->bindValue(':to', $dateTo);
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $records = $stm->fetchAll(PDO::FETCH_ASSOC);

        $stm2 = $connect_pdo->prepare(
            "SELECT COUNT(*) as total FROM attendancet
             WHERE EmpID = :uid AND Date BETWEEN :from AND :to"
        );
        $stm2->execute([':uid' => $apiUser['id'], ':from' => $dateFrom, ':to' => $dateTo]);
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $grouped = [];
        foreach ($records as $record) {
            $date = $record['Date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date'      => $date,
                    'check_in'  => null,
                    'check_out' => null,
                    'records'   => [],
                ];
            }

            $entry = [
                'id'     => (int) $record['ID'],
                'type'   => (int) $record['Type'] === 1 ? 'check_in' : 'check_out',
                'time'   => $record['Time'],
                'method' => $record['method'] ?? 'manual',
            ];
            $grouped[$date]['records'][] = $entry;

            if ((int) $record['Type'] === 1 && !$grouped[$date]['check_in']) {
                $grouped[$date]['check_in'] = $record['Time'];
            }
            if ((int) $record['Type'] === 2) {
                $grouped[$date]['check_out'] = $record['Time'];
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
        global $connect_pdo, $auditLog, $qrService, $antiSpoof;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('qr_code', 'رمز QR')
          ->required('lat', 'خط العرض')
          ->required('lng', 'خط الطول')
          ->latitude('lat')
          ->longitude('lng');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $qrCode = (string) $body['qr_code'];
        $lat = (float) $body['lat'];
        $lng = (float) $body['lng'];
        $accuracy = isset($body['accuracy']) ? (float) $body['accuracy'] : null;
        $mockLocation = !empty($body['mock_location']) ? 1 : null;
        $deviceFingerprint = $body['device_fingerprint'] ?? null;
        $branchId = (int) ($apiUser['branch_id'] ?? 0);

        $qrResult = $qrService->validate($qrCode);
        if (!$qrResult['valid']) {
            Response::error($qrResult['error'], 422);
        }

        if ((int) $qrResult['branch_id'] !== $branchId) {
            Response::error('رمز QR لا ينتمي إلى فرعك', 403);
        }

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
            $auditLog->log($apiUser['id'], 'antispoof_blocked', 'attendancet', null, null, [
                'reason'     => $spoofCheck['reason'],
                'risk_score' => $spoofCheck['risk_score'],
                'warnings'   => $spoofCheck['warnings'],
                'lat'        => $lat,
                'lng'        => $lng,
                'method'     => 'qr',
            ]);

            $msg = implode('. ', $spoofCheck['warnings']);
            Response::error('تم رفض تسجيل الحضور عبر QR: ' . $msg, 403);
        }

        self::validateLocation($branchId, $lat, $lng);

        $today = date('Y-m-d');
        $stm = $connect_pdo->prepare(
            "SELECT Type FROM attendancet
             WHERE EmpID = :uid AND Date = :date
             ORDER BY ID DESC LIMIT 1"
        );
        $stm->execute([':uid' => $apiUser['id'], ':date' => $today]);
        $last = $stm->fetch(PDO::FETCH_ASSOC);

        $type = (!$last || (int) $last['Type'] === 2) ? 1 : 2;

        $attendanceId = self::insertAttendanceRecord([
            'EmpID'       => $apiUser['id'],
            'Date'        => $today,
            'Type'        => $type,
            'Time'        => date('H:i:s'),
            'who_add'     => $apiUser['id'],
            'lat'         => $lat,
            'lng'         => $lng,
            'method'      => 'qr',
            'qr_token'    => $qrCode,
            'device_info' => $deviceFingerprint,
        ]);

        $qrService->recordUsage((int) $qrResult['qr_id']);

        $typeLabel = $type === 1 ? 'الحضور' : 'الانصراف';
        $auditLog->logCreate($apiUser['id'], 'attendancet', $attendanceId, [
            'type'   => $type === 1 ? 'check-in' : 'check-out',
            'method' => 'qr',
        ]);

        Response::success([
            'attendance_id' => $attendanceId,
            'type'          => $type === 1 ? 'check_in' : 'check_out',
            'date'          => $today,
            'time'          => date('H:i:s'),
        ], "تم تسجيل {$typeLabel} عبر QR بنجاح");
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
            Response::success(['active' => false], 'لا يوجد رمز QR نشط حاليًا');
            return;
        }

        $active['active'] = true;
        Response::success($active);
    }

    // ---- Private helpers ----

    private static function attendanceTableColumns(): array
    {
        global $connect_pdo;
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = [];
        $stm = $connect_pdo->query("SHOW COLUMNS FROM attendancet");
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[$column['Field']] = true;
        }

        return $columns;
    }

    private static function insertAttendanceRecord(array $values): int
    {
        global $connect_pdo;

        $available = self::attendanceTableColumns();
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($values as $column => $value) {
            if (!isset($available[$column])) {
                continue;
            }

            $placeholder = ':' . $column;
            $columns[] = "`{$column}`";
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }

        if (!$columns) {
            throw new RuntimeException('تعذر مطابقة أعمدة جدول الحضور مع البيانات المطلوبة');
        }

        $sql = "INSERT INTO attendancet (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $stm = $connect_pdo->prepare($sql);
        $stm->execute($params);

        return (int) $connect_pdo->lastInsertId();
    }

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
            return;
        }

        $locationType = (int) ($branch['TypeBracnhLocation'] ?? 0);

        if ($locationType === 1 && !empty($branch['Onepoint'])) {
            $parts = explode(',', $branch['Onepoint']);
            if (count($parts) >= 3) {
                $bLat = (float) $parts[0];
                $bLng = (float) $parts[1];
                $radius = (float) $parts[2];
                $distance = self::haversineDistance($lat, $lng, $bLat, $bLng);

                if ($distance > $radius) {
                    Response::error('أنت خارج نطاق موقع العمل. المسافة الحالية: ' . round($distance) . ' متر', 403);
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
                Response::error('أنت خارج نطاق موقع العمل', 403);
            }
        }
    }

    /**
     * Calculate distance between two GPS points using Haversine formula
     */
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

        $dayOfWeek = (int) date('w', strtotime($date));
        $stm = $connect_pdo->prepare(
            "SELECT start_time, is_off FROM shifts_schedule
             WHERE shift_id = :sid AND day_of_week = :dow
             LIMIT 1"
        );
        $stm->execute([':sid' => $shiftId, ':dow' => $dayOfWeek]);
        $schedule = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$schedule || !empty($schedule['is_off'])) {
            return null;
        }

        $shiftStart = $schedule['start_time'] ?? null;
        if (!$shiftStart) {
            $stm2 = $connect_pdo->prepare("SELECT ShiftStartTime FROM tbshift WHERE ShiftID = :sid LIMIT 1");
            $stm2->execute([':sid' => $shiftId]);
            $shift = $stm2->fetch(PDO::FETCH_ASSOC);
            $shiftStart = $shift['ShiftStartTime'] ?? null;
        }

        if (!$shiftStart) {
            return null;
        }

        $stm3 = $connect_pdo->prepare("SELECT allowed_late_minutes FROM shift_setting WHERE shift_id = :sid LIMIT 1");
        $stm3->execute([':sid' => $shiftId]);
        $setting = $stm3->fetch(PDO::FETCH_ASSOC);
        $tolerance = (int) ($setting['allowed_late_minutes'] ?? 0);

        $now = strtotime(date('H:i:s'));
        $start = strtotime($shiftStart);
        $allowedTime = $start + ($tolerance * 60);

        if ($now > $allowedTime) {
            $lateMinutes = (int) round(($now - $start) / 60);
            return [
                'is_late'     => true,
                'minutes'     => $lateMinutes,
                'shift_start' => $shiftStart,
                'tolerance'   => $tolerance,
            ];
        }

        return [
            'is_late'     => false,
            'shift_start' => $shiftStart,
        ];
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
