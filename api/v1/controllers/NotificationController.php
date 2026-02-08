<?php
/**
 * Vision HR - Notification Controller
 * List, read, and count notifications
 */

class NotificationController
{
    /**
     * GET /notifications
     * Get user notifications
     */
    public static function list(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $pagination = Validator::pagination();
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

        $where = "WHERE n.user_id = :uid";
        $params = [':uid' => $apiUser['id']];

        if ($unreadOnly) {
            $where .= " AND n.is_read = 0";
        }

        $stm = $connect_pdo->prepare(
            "SELECT n.id, n.title, n.body, n.type, n.reference_table, n.reference_id,
                    n.is_read, n.read_at, n.created_at
             FROM notifications n
             $where
             ORDER BY n.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $val) {
            $stm->bindValue($k, $val);
        }
        $stm->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stm->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stm->execute();
        $notifications = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Count total
        $stm2 = $connect_pdo->prepare("SELECT COUNT(*) as total FROM notifications n $where");
        foreach ($params as $k => $val) {
            $stm2->bindValue($k, $val);
        }
        $stm2->execute();
        $total = (int) $stm2->fetch(PDO::FETCH_ASSOC)['total'];

        $formatted = array_map(function ($n) {
            return [
                'id'              => (int) $n['id'],
                'title'           => $n['title'],
                'body'            => $n['body'],
                'type'            => $n['type'],
                'reference_table' => $n['reference_table'],
                'reference_id'    => $n['reference_id'] ? (int) $n['reference_id'] : null,
                'is_read'         => (bool) $n['is_read'],
                'read_at'         => $n['read_at'],
                'created_at'      => $n['created_at'],
            ];
        }, $notifications);

        Response::paginated($formatted, $total, $pagination['page'], $pagination['per_page']);
    }

    /**
     * PUT /notifications/:id/read
     * Mark a notification as read
     */
    public static function markRead(array $params): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $id = (int) ($params['id'] ?? 0);

        $stm = $connect_pdo->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE id = :id AND user_id = :uid AND is_read = 0"
        );
        $stm->execute([':id' => $id, ':uid' => $apiUser['id']]);

        if ($stm->rowCount() === 0) {
            // Either not found or already read
            $check = $connect_pdo->prepare("SELECT id FROM notifications WHERE id = :id AND user_id = :uid");
            $check->execute([':id' => $id, ':uid' => $apiUser['id']]);
            if (!$check->fetch()) {
                Response::notFound('الإشعار غير موجود');
            }
        }

        Response::success(null, 'تم تعليم الإشعار كمقروء');
    }

    /**
     * PUT /notifications/read-all
     * Mark all notifications as read
     */
    public static function markAllRead(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE user_id = :uid AND is_read = 0"
        );
        $stm->execute([':uid' => $apiUser['id']]);

        Response::success(['marked' => $stm->rowCount()], 'تم تعليم جميع الإشعارات كمقروءة');
    }

    /**
     * GET /notifications/unread-count
     * Get count of unread notifications
     */
    public static function unreadCount(): void
    {
        global $connect_pdo;
        $apiUser = authMiddleware();

        $stm = $connect_pdo->prepare(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stm->execute([':uid' => $apiUser['id']]);
        $count = (int) $stm->fetch(PDO::FETCH_ASSOC)['count'];

        Response::success(['unread_count' => $count]);
    }
}
