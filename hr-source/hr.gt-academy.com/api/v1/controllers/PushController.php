<?php
/**
 * Vision HR - Push Notification Controller
 * FCM push subscription management
 */

class PushController
{
    /**
     * POST /push/subscribe
     * Register a device for push notifications
     */
    public static function subscribe(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $v = new Validator($body);
        $v->required('fcm_token', 'رمز FCM')
          ->required('device_type', 'نوع الجهاز');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $fcmToken = $body['fcm_token'];
        $deviceType = $body['device_type']; // android, ios, web
        $deviceName = $body['device_name'] ?? null;

        // Check if token already registered
        $stm = $connect_pdo->prepare(
            "SELECT id, user_id, is_active FROM push_subscriptions WHERE fcm_token = :token LIMIT 1"
        );
        $stm->execute([':token' => $fcmToken]);
        $existing = $stm->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ((int) $existing['user_id'] !== (int) $apiUser['id']) {
                Response::error('This device token is already registered to another account.', 409);
            }

            $connect_pdo->prepare(
                "UPDATE push_subscriptions 
                 SET device_type = :dtype, device_name = :dname, is_active = 1, updated_at = NOW()
                  WHERE id = :id"
            )->execute([
                ':dtype' => $deviceType,
                ':dname' => $deviceName,
                ':id'    => $existing['id'],
            ]);
            $subId = (int) $existing['id'];
        } else {
            $connect_pdo->prepare(
                "INSERT INTO push_subscriptions (user_id, fcm_token, device_type, device_name, is_active, created_at, updated_at)
                 VALUES (:uid, :token, :dtype, :dname, 1, NOW(), NOW())"
            )->execute([
                ':uid'   => $apiUser['id'],
                ':token' => $fcmToken,
                ':dtype' => $deviceType,
                ':dname' => $deviceName,
            ]);
            $subId = (int) $connect_pdo->lastInsertId();
        }

        $auditLog->log($apiUser['id'], 'push_subscribe', 'push_subscriptions', $subId);

        Response::success(['subscription_id' => $subId], 'تم تسجيل الجهاز للإشعارات بنجاح');
    }

    /**
     * POST /push/unsubscribe
     * Unregister a device from push notifications
     */
    public static function unsubscribe(): void
    {
        global $connect_pdo, $auditLog;
        $apiUser = authMiddleware();

        $body = getRequestBody();
        $fcmToken = $body['fcm_token'] ?? '';

        if (empty($fcmToken)) {
            Response::validationError(['fcm_token' => 'رمز FCM مطلوب']);
        }

        $stm = $connect_pdo->prepare(
            "UPDATE push_subscriptions SET is_active = 0, updated_at = NOW()
             WHERE fcm_token = :token AND user_id = :uid"
        );
        $stm->execute([':token' => $fcmToken, ':uid' => $apiUser['id']]);

        $auditLog->log($apiUser['id'], 'push_unsubscribe', 'push_subscriptions', null);

        Response::success(null, 'تم إلغاء تسجيل الجهاز من الإشعارات');
    }

    /**
     * GET /push/subscriptions
     * List active push subscriptions for current user
     */
    public static function listSubscriptions(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT id, device_type, device_name, is_active, created_at, updated_at
             FROM push_subscriptions
             WHERE user_id = :uid AND is_active = 1
             ORDER BY updated_at DESC"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $subs = $stm->fetchAll(PDO::FETCH_ASSOC);

        Response::success(array_map(function ($s) {
            return [
                'id'          => (int) $s['id'],
                'device_type' => $s['device_type'],
                'device_name' => $s['device_name'],
                'created_at'  => $s['created_at'],
                'updated_at'  => $s['updated_at'],
            ];
        }, $subs));
    }

    /**
     * POST /push/test
     * Send a test push notification to current user
     */
    public static function test(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $notifService = new NotificationService($connect_pdo);
        $notifService->create(
            $apiUser['id'],
            'إشعار تجريبي',
            'هذا إشعار تجريبي للتأكد من عمل الإشعارات',
            'info'
        );

        Response::success(null, 'تم إرسال الإشعار التجريبي');
    }
}
