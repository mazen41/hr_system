<?php
/**
 * Vision HR - Rate Limiting Middleware
 * Protects API endpoints from abuse
 */

/**
 * Apply rate limiting to the current request
 *
 * @param string $prefix   Rate limit key prefix
 * @param int    $maxAttempts Maximum attempts
 * @param int    $window     Time window in seconds
 */
function rateLimitMiddleware(string $prefix = 'api', int $maxAttempts = 0, int $window = 0): void
{
    if ($maxAttempts === 0) {
        $maxAttempts = defined('API_RATE_LIMIT') ? API_RATE_LIMIT : 60;
    }
    if ($window === 0) {
        $window = defined('API_RATE_WINDOW') ? API_RATE_WINDOW : 60;
    }

    $limiter = new RateLimiter($maxAttempts, $window);
    $key = RateLimiter::keyFromRequest($prefix);

    if ($limiter->tooManyAttempts($key)) {
        $retryAfter = $limiter->retryAfter($key);
        header("X-RateLimit-Limit: $maxAttempts");
        header("X-RateLimit-Remaining: 0");
        header("X-RateLimit-Reset: $retryAfter");
        Response::tooManyRequests($retryAfter);
    }

    $attempts = $limiter->hit($key);
    $remaining = max(0, $maxAttempts - $attempts);

    header("X-RateLimit-Limit: $maxAttempts");
    header("X-RateLimit-Remaining: $remaining");
}

/**
 * Apply login-specific rate limiting
 */
function loginRateLimitMiddleware(string $email = ''): void
{
    $maxAttempts = defined('LOGIN_RATE_LIMIT') ? LOGIN_RATE_LIMIT : 5;
    $window = defined('LOGIN_RATE_WINDOW') ? LOGIN_RATE_WINDOW : 300;

    $limiter = new RateLimiter($maxAttempts, $window);

    // Rate limit by IP
    $ipKey = RateLimiter::keyFromRequest('login_ip');
    if ($limiter->tooManyAttempts($ipKey)) {
        $retryAfter = $limiter->retryAfter($ipKey);
        Response::tooManyRequests($retryAfter, 'تم تجاوز عدد محاولات تسجيل الدخول. حاول بعد ' . ceil($retryAfter / 60) . ' دقائق');
    }

    // Rate limit by IP + email
    if (!empty($email)) {
        $emailKey = RateLimiter::keyForLogin($email);
        if ($limiter->tooManyAttempts($emailKey)) {
            $retryAfter = $limiter->retryAfter($emailKey);
            Response::tooManyRequests($retryAfter, 'تم تجاوز عدد محاولات تسجيل الدخول لهذا الحساب. حاول بعد ' . ceil($retryAfter / 60) . ' دقائق');
        }
    }
}

/**
 * Record a failed login attempt
 */
function recordFailedLogin(string $email): void
{
    $maxAttempts = defined('LOGIN_RATE_LIMIT') ? LOGIN_RATE_LIMIT : 5;
    $window = defined('LOGIN_RATE_WINDOW') ? LOGIN_RATE_WINDOW : 300;

    $limiter = new RateLimiter($maxAttempts, $window);
    $limiter->hit(RateLimiter::keyFromRequest('login_ip'));
    $limiter->hit(RateLimiter::keyForLogin($email));
}

/**
 * Clear login rate limit on successful login
 */
function clearLoginRateLimit(string $email): void
{
    $maxAttempts = defined('LOGIN_RATE_LIMIT') ? LOGIN_RATE_LIMIT : 5;
    $window = defined('LOGIN_RATE_WINDOW') ? LOGIN_RATE_WINDOW : 300;

    $limiter = new RateLimiter($maxAttempts, $window);
    $limiter->clear(RateLimiter::keyFromRequest('login_ip'));
    $limiter->clear(RateLimiter::keyForLogin($email));
}
