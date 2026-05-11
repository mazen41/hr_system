<?php
/**
 * Notification Service - Handles in-app notifications
 * Supports: In-app notifications
 */
class NotificationService {
    private $pdo;
    private const UNREAD_CACHE_TTL = 10;
    private const UNREAD_CACHE_LIMITS = [5, 10, 20, 50];
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send notification to a user
     */
    public function notify($userId, $title, $message, $type = 'info', $entityType = null, $entityId = null, $data = []) {
        // Create in-app notification
        return $this->createInAppNotification($userId, $title, $message, $type, $entityType, $entityId);
    }
    
    /**
     * Send notification to multiple users
     */
    public function notifyMany($userIds, $title, $message, $type = 'info', $entityType = null, $entityId = null, $data = []) {
        $results = [];
        foreach ($userIds as $userId) {
            $results[$userId] = $this->notify($userId, $title, $message, $type, $entityType, $entityId, $data);
        }
        return $results;
    }
    
    /**
     * Create in-app notification record
     */
    private function createInAppNotification($userId, $title, $message, $type, $entityType, $entityId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, body, type, entity_type, entity_id, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $entityType, $entityId]);
        $this->flushUnreadCache((int)$userId);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications($userId, $limit = 20) {
        $cacheKey = $this->getUnreadListCacheKey((int)$userId, (int)$limit);
        $cached = perf_cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        perf_cache_set($cacheKey, $items, self::UNREAD_CACHE_TTL);
        return $items;
    }

    public function getUnreadSummary($userId, $limit = 5): array
    {
        $userId = (int)$userId;
        $limit = max(1, (int)$limit);
        $summaryKey = $this->getUnreadSummaryCacheKey($userId, $limit);
        $cached = perf_cache_get($summaryKey);
        if (is_array($cached) && array_key_exists('count', $cached) && array_key_exists('items', $cached)) {
            return $cached;
        }

        $itemsStmt = $this->pdo->prepare("
            SELECT * FROM notifications
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $itemsStmt->bindValue(1, $userId, PDO::PARAM_INT);
        $itemsStmt->bindValue(2, $limit, PDO::PARAM_INT);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");
        $countStmt->execute([$userId]);
        $count = (int)$countStmt->fetchColumn();

        $summary = [
            'count' => $count,
            'items' => $items,
        ];

        perf_cache_set($summaryKey, $summary, self::UNREAD_CACHE_TTL);
        perf_cache_set($this->getUnreadListCacheKey($userId, $limit), $items, self::UNREAD_CACHE_TTL);
        perf_cache_set($this->getUnreadCountCacheKey($userId), $count, self::UNREAD_CACHE_TTL);

        return $summary;
    }
    
    /**
     * Get user's all notifications
     */
    public function getNotifications($userId, $limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $result = $stmt->execute([$notificationId, $userId]);
        if ($result) {
            $this->flushUnreadCache((int)$userId);
        }
        return $result;
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");
        $result = $stmt->execute([$userId]);
        if ($result) {
            $this->flushUnreadCache((int)$userId);
        }
        return $result;
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $cacheKey = $this->getUnreadCountCacheKey((int)$userId);
        $cached = perf_cache_get($cacheKey);
        if ($cached !== null) {
            return (int)$cached;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();
        perf_cache_set($cacheKey, $count, self::UNREAD_CACHE_TTL);
        return $count;
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications($daysOld = 90) {
        $stmt = $this->pdo->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$daysOld]);
    }
    
    /**
     * Notification types for different events
     */
    public function notifyLeaveRequest($requesterId, $leaveId, $action = 'submitted') {
        $actions = [
            'submitted' => ['title' => 'طلب إجازة جديد', 'type' => 'info'],
            'approved' => ['title' => 'تمت الموافقة على الإجازة', 'type' => 'success'],
            'rejected' => ['title' => 'تم رفض طلب الإجازة', 'type' => 'warning']
        ];
        
        $config = $actions[$action] ?? $actions['submitted'];
        
        // Get leave details
        $stmt = $this->pdo->prepare("
            SELECT lr.*, lc.Name as leave_type_name
            FROM tblleaverequest lr
            LEFT JOIN leaveclassification lc ON lc.id = lr.leavetype
            WHERE lr.id = ?
        ");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leave) {
            $message = "طلب إجازة {$leave['leave_type_name']} من {$leave['leave_start_date']} إلى {$leave['leave_end_date']}";
            $this->notify($requesterId, $config['title'], $message, $config['type'], 'leave_request', $leaveId);
        }
    }
    
    public function notifyViolation($employeeId, $violationId) {
        $stmt = $this->pdo->prepare("
            SELECT ev.*, vt.name_ar as violation_type
            FROM employee_violations ev
            JOIN violation_types vt ON vt.id = ev.violation_type_id
            WHERE ev.id = ?
        ");
        $stmt->execute([$violationId]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($violation) {
            $this->notify(
                $employeeId,
                'تسجيل مخالفة',
                "تم تسجيل مخالفة: {$violation['violation_type']}",
                'warning',
                'violation',
                $violationId
            );
        }
    }
    
    public function notifyPromotion($employeeId, $promotionId, $status) {
        $titles = [
            'pending' => 'طلب ترقية قيد المراجعة',
            'approved' => 'تمت الموافقة على ترقيتك',
            'rejected' => 'تم رفض طلب الترقية'
        ];
        
        $types = [
            'pending' => 'info',
            'approved' => 'success',
            'rejected' => 'warning'
        ];
        
        $this->notify(
            $employeeId,
            $titles[$status] ?? 'تحديث طلب الترقية',
            'تم تحديث حالة طلب الترقية الخاص بك',
            $types[$status] ?? 'info',
            'promotion',
            $promotionId
        );
    }

    private function flushUnreadCache(int $userId): void
    {
        perf_cache_delete($this->getUnreadCountCacheKey($userId));
        foreach (self::UNREAD_CACHE_LIMITS as $limit) {
            perf_cache_delete($this->getUnreadListCacheKey($userId, $limit));
            perf_cache_delete($this->getUnreadSummaryCacheKey($userId, $limit));
        }
    }

    private function getUnreadListCacheKey(int $userId, int $limit): string
    {
        return perf_cache_key('notifications-unread-list', [$userId, $limit]);
    }

    private function getUnreadSummaryCacheKey(int $userId, int $limit): string
    {
        return perf_cache_key('notifications-unread-summary', [$userId, $limit]);
    }

    private function getUnreadCountCacheKey(int $userId): string
    {
        return perf_cache_key('notifications-unread-count', [$userId]);
    }
}
