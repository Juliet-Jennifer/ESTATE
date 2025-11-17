<?php
namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private static $secret;
    private static $algo = 'HS256';
    private static $expiry = 3600; // 1 hour

    private static function init() {
        self::$secret = $_ENV['JWT_SECRET'] ?? 'supersecretkey';
    }

    public static function generateToken(array $payload) {
        self::init();

        $issuedAt = time();
        $exp = $issuedAt + self::$expiry;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $exp
        ]);

        return JWT::encode($tokenPayload, self::$secret, self::$algo);
    }

    public static function verifyToken(string $token) {
        self::init();

        try {
            return (array) JWT::decode($token, new Key(self::$secret, self::$algo));
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getUserFromBearer(string $authHeader) {
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return self::verifyToken($matches[1]);
        }
        return null;
    }

    public static function getTokenFromHeader(): ?string
    {
        $headers = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
