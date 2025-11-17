<?php
namespace App\Middleware;

use app\Utils\JWTHandler;
use app\Utils\Response;

class AuthMiddleware {
    public static function handle() {
        $token = JWTHandler::getTokenFromHeader();
        
        if (!$token) {
            Response::error('Authentication token required', 'UNAUTHORIZED', [], 401);
        }

        $decoded = JWTHandler::validateToken($token);
        
        if (!$decoded) {
            Response::error('Invalid or expired token', 'INVALID_TOKEN', [], 401);
        }

        // Store user data in request for later use
        $GLOBALS['user'] = $decoded;
        
        return $decoded;
    }

    public static function getUser() {
        return $GLOBALS['user'] ?? null;
    }
}