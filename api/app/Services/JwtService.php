<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtService
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $ttl;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', config('app.key'));
        $this->ttl = (int) env('JWT_TTL', 3600);
    }

    public function generateToken($subject, string $type, array $additionalClaims = []): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->ttl;

        $payload = array_merge([
            'iss' => config('app.url'),
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $subject,
            'type' => $type,
        ], $additionalClaims);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function validateToken(string $token): bool
    {
        try {
            JWT::decode($token, new Key($this->secret, $this->algorithm));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPayload(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (Exception $e) {
            return null;
        }
    }
}
