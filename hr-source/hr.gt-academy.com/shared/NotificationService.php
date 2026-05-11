<?php
/**
 * Vision HR - Notification Service
 * Centralized notification creation for all event types
 * Supports in-app notifications + FCM push notifications
 */

class NotificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create an in-app notification
     */
    public function create(
        int $userId,
        string $title,
        string $body,
        string $type = 'info',
        ?string $referenceTable = null,
        ?int $referenceId = null
    ): int {
        $stm = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, title, body, type, entity_type, entity_id, is_read, created_at)
             VALUES (:uid, :title, :body, :type, :entity_type, :entity_id, 0, NOW())"
        );
        $stm->execute([
            ':uid'         => $userId,
            ':title'       => $title,
            ':body'        => $body,
            ':type'        => $type,
            ':entity_type' => $referenceTable,
            ':entity_id'   => $referenceId,
        ]);

        $notifId = (int) $this->pdo->lastInsertId();

        // Try to send push notification
        $this->sendPush($userId, $title, $body, $type, $referenceId);

        return $notifId;
    }

    /**
     * Notify multiple users
     */
    public function createBulk(
        array $userIds,
        string $title,
        string $body,
        string $type = 'info',
        ?string $referenceTable = null,
        ?int $referenceId = null
    ): int {
        $count = 0;
        foreach ($userIds as $uid) {
            $this->create((int) $uid, $title, $body, $type, $referenceTable, $referenceId);
            $count++;
        }
        return $count;
    }

    // ---- Event-specific notification helpers ----

    /**
     * Notify manager about a new leave request
     */
    public function notifyLeaveRequest(int $managerId, string $employeeName, string $leaveType, string $startDate, string $endDate, int $leaveId): int
    {
        return $this->create(
            $managerId,
            'طلب إجازة جديد',
            "$employeeName طلب إجازة $leaveType من $startDate إلى $endDate",
            'leave',
            'tblleave',
            $leaveId
        );
    }

    /**
     * Notify employee about leave approval
     */
    public function notifyLeaveApproved(int $employeeId, string $leaveType, int $leaveId): int
    {
        return $this->create(
            $employeeId,
            'تمت الموافقة على إجازتك',
            "تمت الموافقة على طلب إجازة $leaveType الخاص بك",
            'approval',
            'tblleave',
            $leaveId
        );
    }

    /**
     * Notify employee about leave rejection
     */
    public function notifyLeaveRejected(int $employeeId, string $leaveType, int $leaveId, string $reason = ''): int
    {
        $body = "تم رفض طلب إجازة $leaveType الخاص بك";
        if ($reason) {
            $body .= ". السبب: $reason";
        }
        return $this->create($employeeId, 'تم رفض طلب الإجازة', $body, 'rejection', 'tblleave', $leaveId);
    }

    /**
     * Notify manager about a new advance request
     */
    public function notifyAdvanceRequest(int $managerId, string $employeeName, float $amount, string $currency, int $advanceId): int
    {
        $formatted = number_format($amount, 2);
        return $this->create(
            $managerId,
            'طلب سلفة جديد',
            "$employeeName طلب سلفة بمبلغ $formatted $currency",
            'advance',
            'tblempadvances',
            $advanceId
        );
    }

    /**
     * Notify employee about advance approval
     */
    public function notifyAdvanceApproved(int $employeeId, float $amount, int $advanceId): int
    {
        $formatted = number_format($amount, 2);
        return $this->create(
            $employeeId,
            'تمت الموافقة على السلفة',
            "تمت الموافقة على طلب السلفة بمبلغ $formatted",
            'approval',
            'tblempadvances',
            $advanceId
        );
    }

    /**
     * Notify employee about advance rejection
     */
    public function notifyAdvanceRejected(int $employeeId, int $advanceId, string $reason = ''): int
    {
        $body = 'تم رفض طلب السلفة الخاص بك';
        if ($reason) {
            $body .= ". السبب: $reason";
        }
        return $this->create($employeeId, 'تم رفض طلب السلفة', $body, 'rejection', 'tblempadvances', $advanceId);
    }

    /**
     * Notify manager about a new resignation
     */
    public function notifyResignation(int $managerId, string $employeeName, int $resignationId): int
    {
        return $this->create(
            $managerId,
            'طلب استقالة جديد',
            "$employeeName تقدم بطلب استقالة",
            'warning',
            'tblresignation',
            $resignationId
        );
    }

    /**
     * Notify about contract expiry (30 days before)
     */
    public function notifyContractExpiry(int $employeeId, int $managerId, string $employeeName, string $expiryDate, int $contractId): void
    {
        $this->create(
            $employeeId,
            'تنبيه انتهاء العقد',
            "عقدك ينتهي بتاريخ $expiryDate. يرجى مراجعة الإدارة",
            'warning',
            'tblremewal',
            $contractId
        );

        if ($managerId) {
            $this->create(
                $managerId,
                'تنبيه انتهاء عقد موظف',
                "عقد الموظف $employeeName ينتهي بتاريخ $expiryDate",
                'warning',
                'tblremewal',
                $contractId
            );
        }
    }

    /**
     * Notify about document expiry (national ID, passport, license, health)
     */
    public function notifyDocumentExpiry(int $employeeId, string $documentType, string $expiryDate): int
    {
        $docNames = [
            'national_id' => 'الهوية الوطنية',
            'passport'    => 'جواز السفر',
            'license'     => 'رخصة القيادة',
            'health'      => 'التأمين الصحي',
        ];
        $docName = $docNames[$documentType] ?? $documentType;

        return $this->create(
            $employeeId,
            "تنبيه انتهاء $docName",
            "صلاحية $docName تنتهي بتاريخ $expiryDate. يرجى التجديد",
            'warning',
            'tblusers',
            $employeeId
        );
    }

    /**
     * Notify about salary issuance
     */
    public function notifySalaryIssued(int $employeeId, string $month, string $year, float $netSalary): int
    {
        $formatted = number_format($netSalary, 2);
        return $this->create(
            $employeeId,
            'تم صرف الراتب',
            "تم صرف راتب شهر $month/$year بمبلغ $formatted ر.س",
            'success',
            'salary_details',
            null
        );
    }

    /**
     * Notify about attendance anomaly (late, absent, early leave)
     */
    public function notifyAttendanceAnomaly(int $employeeId, string $anomalyType, string $date, ?int $minutes = null): int
    {
        $messages = [
            'late'        => 'تأخر في الحضور' . ($minutes ? " بـ $minutes دقيقة" : ''),
            'absent'      => 'غياب بدون إذن',
            'early_leave' => 'انصراف مبكر' . ($minutes ? " بـ $minutes دقيقة" : ''),
        ];
        $msg = $messages[$anomalyType] ?? $anomalyType;

        return $this->create(
            $employeeId,
            'تنبيه حضور',
            "$msg بتاريخ $date",
            'attendance',
            'attendancet',
            null
        );
    }

    // ---- Push Notification via FCM ----

    /**
     * Send push notification to user's registered devices
     */
    private function sendPush(int $userId, string $title, string $body, string $type = 'info', ?int $referenceId = null): void
    {
        // Get active push subscriptions
        $stm = $this->pdo->prepare(
            "SELECT fcm_token, device_type FROM push_subscriptions
             WHERE user_id = :uid AND is_active = 1 AND fcm_token IS NOT NULL"
        );
        $stm->execute([':uid' => $userId]);
        $subscriptions = $stm->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            return;
        }

        // FCM server key should be configured
        $fcmKey = defined('FCM_SERVER_KEY') ? FCM_SERVER_KEY : '';
        if (empty($fcmKey)) {
            return; // FCM not configured
        }

        $tokens = array_column($subscriptions, 'fcm_token');

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'icon'  => '/HR/ess/icons/icon-192.png',
                'badge' => '/HR/ess/icons/badge-72.png',
                'click_action' => '/HR/ess/',
            ],
            'data' => [
                'type'         => $type,
                'reference_id' => $referenceId,
                'timestamp'    => time(),
            ],
        ];

        // Send via FCM HTTP v1
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: key=' . $fcmKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Handle invalid tokens
        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['results']) && is_array($result['results'])) {
                foreach ($result['results'] as $i => $res) {
                    if (isset($res['error']) && in_array($res['error'], ['NotRegistered', 'InvalidRegistration'])) {
                        // Deactivate invalid token
                        if (isset($tokens[$i])) {
                            $this->pdo->prepare(
                                "UPDATE push_subscriptions SET is_active = 0 WHERE fcm_token = :token"
                            )->execute([':token' => $tokens[$i]]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        $stm = $this->pdo->prepare(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stm->execute([':uid' => $userId]);
        return (int) $stm->fetch(PDO::FETCH_ASSOC)['cnt'];
    }
}
