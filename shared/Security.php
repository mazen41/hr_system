<?php
/**
 * Vision HR - Security Helper
 * CSRF Token management, CSP headers, password hashing utilities
 */

class Security
{
    /**
     * Generate or retrieve CSRF token for current session
     */
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session cookie settings for HTTPS
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
            self::regenerateCsrfToken();
        }

        // Regenerate token if older than 1 hour
        if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) {
            self::regenerateCsrfToken();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session cookie settings for HTTPS
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Output hidden CSRF input field for forms
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Middleware: check CSRF on POST/PUT/DELETE requests
     * Returns true if valid, false if invalid
     */
    public static function checkCsrf(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true; // GET/HEAD/OPTIONS don't need CSRF
        }

        $token = $_POST['_csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        return self::validateCsrfToken($token);
    }

    /**
     * Hash a password using bcrypt
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password against hash (supports legacy plain-text fallback)
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        // Try bcrypt first
        if (password_verify($password, $hash)) {
            return true;
        }
        // Legacy plain-text fallback (for migration period only)
        if ($password === $hash) {
            return true;
        }
        return false;
    }

    /**
     * Check if a password hash needs rehashing (e.g., plain text → bcrypt)
     */
    public static function needsRehash(string $hash): bool
    {
        return !password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])
            ? false
            : true;
    }

    /**
     * Send Content Security Policy headers
     */
    public static function sendCspHeaders(): void
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
        ]);

        header("Content-Security-Policy: $csp");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Generate a cryptographically secure random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    /**
     * Sanitize input - enhanced version
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}
