<?php
namespace App\Middleware;


use App\Utils\JWTHandler;
use App\Utils\Response;


class AuthMiddleware {
/**
* Validate the bearer token from request and return decoded payload (associative array).
* Will send an error response and exit on failure.
*
* @return array|null
*/
public static function handle() {
$token = JWTHandler::getTokenFromHeader();


if (!$token) {
Response::error('Authentication token required', 'UNAUTHORIZED', [], 401);
return null;
}


$decoded = JWTHandler::verifyToken($token);


if (!$decoded) {
Response::error('Invalid or expired token', 'INVALID_TOKEN', [], 401);
return null;
}


// Store user data in global for controllers
$GLOBALS['user'] = $decoded;


return $decoded;
}


/**
* Convenience wrapper used by controllers that expect to require auth and get the user.
* Returns decoded user payload or sends an error response and exits.
*
* @return array
*/
public static function requireAuth() {
$user = self::handle();
return $user;
}


public static function getUser() {
return $GLOBALS['user'] ?? null;
}


/**
* Forwarding helper to generate tokens via JWTHandler so controllers may call either
* AuthMiddleware::generateToken(...) or JWTHandler::generateToken(...)
*
* @param array $payload
* @return string
*/
public static function generateToken(array $payload) {
return JWTHandler::generateToken($payload);
}
}