<?php
/**
 * Vision HR - Rate Limiter
 * File-based rate limiting for login and API endpoints
 * Uses temp files to avoid requiring additional DB tables or Redis
 */

class RateLimiter
{
    private string $storageDir;
    private int $maxAttempts;
    private int $decaySeconds;

    /**
     * @param int $maxAttempts  Maximum attempts allowed within the window
     * @param int $decaySeconds Time window in seconds
     * @param string|null $storageDir Directory for rate limit files
     */
    public function __construct(int $maxAttempts = 5, int $decaySeconds = 300, ?string $storageDir = null)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/vision_hr_ratelimit';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0700, true);
        }
    }

    /**
     * Check if the given key has exceeded the rate limit
     */
    public function tooManyAttempts(string $key): bool
    {
        $attempts = $this->getAttempts($key);
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Record a hit/attempt for the given key
     */
    public function hit(string $key): int
    {
        $file = $this->getFilePath($key);
        $data = $this->loadData($file);

        // Clean expired entries
        $cutoff = time() - $this->decaySeconds;
        $data = array_filter($data, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        // Add new attempt
        $data[] = time();

        $this->saveData($file, $data);

        return count($data);
    }

    /**
     * Get current number of attempts for the key
     */
    public function getAttempts(string $key): int
    {
        $file = $this->getFilePath($key);
        $data = $this->loadData($file);

        // Clean expired entries
        $cutoff = time() - $this->decaySeconds;
        $data = array_filter($data, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        return count($data);
    }

    /**
     * Get remaining attempts
     */
    public function remainingAttempts(string $key): int
    {
        return max(0, $this->maxAttempts - $this->getAttempts($key));
    }

    /**
     * Get seconds until the rate limit resets
     */
    public function retryAfter(string $key): int
    {
        $file = $this->getFilePath($key);
        $data = $this->loadData($file);

        if (empty($data)) {
            return 0;
        }

        $oldest = min($data);
        $retryAfter = ($oldest + $this->decaySeconds) - time();

        return max(0, $retryAfter);
    }

    /**
     * Clear all attempts for a key
     */
    public function clear(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Clean up expired rate limit files (call periodically)
     */
    public function cleanup(): void
    {
        $files = glob($this->storageDir . '/rl_*.json');
        $cutoff = time() - $this->decaySeconds;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Generate a rate limit key from IP + optional identifier
     */
    public static function keyFromRequest(string $prefix = 'login'): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return $prefix . ':' . $ip;
    }

    /**
     * Generate a rate limit key from IP + email (for login)
     */
    public static function keyForLogin(string $email): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return 'login:' . $ip . ':' . md5(strtolower(trim($email)));
    }

    private function getFilePath(string $key): string
    {
        return $this->storageDir . '/rl_' . md5($key) . '.json';
    }

    private function loadData(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function saveData(string $file, array $data): void
    {
        // Re-index array
        $data = array_values($data);
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
