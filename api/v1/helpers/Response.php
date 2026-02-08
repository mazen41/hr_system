<?php
/**
 * Vision HR - API Response Helper
 * Standardized JSON responses for all API endpoints
 */

class Response
{
    /**
     * Send a success response
     */
    public static function success($data = null, string $message = '', int $statusCode = 200): void
    {
        self::send($statusCode, [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Send a created response (201)
     */
    public static function created($data = null, string $message = 'تم الإنشاء بنجاح'): void
    {
        self::success($data, $message, 201);
    }

    /**
     * Send an error response
     */
    public static function error(string $message, int $statusCode = 400, $errors = null): void
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        self::send($statusCode, $body);
    }

    /**
     * 401 Unauthorized
     */
    public static function unauthorized(string $message = 'غير مصرح - يرجى تسجيل الدخول'): void
    {
        self::error($message, 401);
    }

    /**
     * 403 Forbidden
     */
    public static function forbidden(string $message = 'ليس لديك صلاحية للوصول'): void
    {
        self::error($message, 403);
    }

    /**
     * 404 Not Found
     */
    public static function notFound(string $message = 'العنصر غير موجود'): void
    {
        self::error($message, 404);
    }

    /**
     * 405 Method Not Allowed
     */
    public static function methodNotAllowed(string $message = 'طريقة الطلب غير مسموحة'): void
    {
        self::error($message, 405);
    }

    /**
     * 422 Validation Error
     */
    public static function validationError(array $errors, string $message = 'بيانات غير صالحة'): void
    {
        self::error($message, 422, $errors);
    }

    /**
     * 429 Too Many Requests
     */
    public static function tooManyRequests(int $retryAfter = 60, string $message = 'عدد المحاولات تجاوز الحد المسموح'): void
    {
        header("Retry-After: $retryAfter");
        self::error($message, 429);
    }

    /**
     * 500 Internal Server Error
     */
    public static function serverError(string $message = 'خطأ في الخادم'): void
    {
        self::error($message, 500);
    }

    /**
     * Paginated response
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = ''): void
    {
        self::send(200, [
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => [
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'total_pages'  => (int) ceil($total / max($perPage, 1)),
                'has_more'     => ($page * $perPage) < $total,
            ],
        ]);
    }

    /**
     * Send raw JSON response
     */
    private static function send(int $statusCode, array $body): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
