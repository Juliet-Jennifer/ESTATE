<?php
namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTHandler {
    public static function generateToken($userId, $email, $role) {
        $payload = [
            'iss' => APP_URL,
            'aud' => APP_URL,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
            'sub' => $userId,
            'email' => $email,
            'role' => $role
        ];

        return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    }

    public static function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}