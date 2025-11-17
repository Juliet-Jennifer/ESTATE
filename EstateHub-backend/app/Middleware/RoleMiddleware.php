<?php
namespace App\Middleware;

use App\Utils\Response;

class RoleMiddleware {
    public static function handle($allowedRoles) {
        $user = AuthMiddleware::getUser();
        
        if (!$user) {
            Response::error('Authentication required', 'UNAUTHORIZED', [], 401);
        }

        if (!in_array($user['role'], $allowedRoles)) {
            Response::error('Insufficient permissions', 'FORBIDDEN', [], 403);
        }

        return true;
    }

    public static function adminOnly() {
        return self::handle([ROLE_ADMIN]);
    }

    public static function tenantOnly() {
        return self::handle([ROLE_TENANT]);
    }

    public static function adminOrTenant() {
        return self::handle([ROLE_ADMIN, ROLE_TENANT]);
    }
}