<?php
/**
 * Vision HR - JWT Helper
 * Pure PHP JWT implementation (no external dependencies)
 * Supports HS256 algorithm
 */

class JWTHelper
{
    private string $secret;
    private int $accessTtl;   // Access token TTL in seconds
    private int $refreshTtl;  // Refresh token TTL in seconds

    /**
     * @param string $secret     HMAC secret key
     * @param int    $accessTtl  Access token lifetime (default 15 minutes)
     * @param int    $refreshTtl Refresh token lifetime (default 7 days)
     */
    public function __construct(string $secret, int $accessTtl = 900, int $refreshTtl = 604800)
    {
        $this->secret = $secret;
        $this->accessTtl = $accessTtl;
        $this->refreshTtl = $refreshTtl;
    }

    /**
     * Generate an access token
     */
    public function generateAccessToken(int $userId, array $claims = []): string
    {
        $payload = array_merge($claims, [
            'sub'  => $userId,
            'type' => 'access',
            'iat'  => time(),
            'exp'  => time() + $this->accessTtl,
            'jti'  => bin2hex(random_bytes(16)),
        ]);

        return $this->encode($payload);
    }

    /**
     * Generate a refresh token (opaque + JWT pair)
     * Returns ['token' => string, 'hash' => string, 'expires_at' => string]
     */
    public function generateRefreshToken(int $userId): array
    {
        $payload = [
            'sub'  => $userId,
            'type' => 'refresh',
            'iat'  => time(),
            'exp'  => time() + $this->refreshTtl,
            'jti'  => bin2hex(random_bytes(16)),
        ];

        $token = $this->encode($payload);

        return [
            'token'      => $token,
            'hash'       => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
        ];
    }

    /**
     * Decode and validate a JWT token
     * Returns the payload array or null on failure
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "$headerB64.$payloadB64", $this->secret, true)
        );

        if (!hash_equals($expectedSig, $signatureB64)) {
            return null;
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Validate an access token and return user ID
     * Returns user ID or null
     */
    public function validateAccessToken(string $token): ?int
    {
        $payload = $this->decode($token);
        if (!$payload) {
            return null;
        }

        if (($payload['type'] ?? '') !== 'access') {
            return null;
        }

        return (int) ($payload['sub'] ?? 0) ?: null;
    }

    /**
     * Validate a refresh token and return user ID
     */
    public function validateRefreshToken(string $token): ?int
    {
        $payload = $this->decode($token);
        if (!$payload) {
            return null;
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        return (int) ($payload['sub'] ?? 0) ?: null;
    }

    /**
     * Extract the Bearer token from Authorization header
     */
    public static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (empty($header)) {
            // Apache may not pass Authorization header; try apache_request_headers
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get remaining TTL of a token in seconds
     */
    public function getRemainingTtl(string $token): int
    {
        $payload = $this->decode($token);
        if (!$payload || !isset($payload['exp'])) {
            return 0;
        }
        return max(0, $payload['exp'] - time());
    }

    // ---- Internal encoding methods ----

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", $this->secret, true)
        );

        return "$header.$payloadEncoded.$signature";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
