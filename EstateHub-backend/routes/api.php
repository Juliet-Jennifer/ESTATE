<?php
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\PropertiesController;
use App\Controllers\TenantsController;
use App\Controllers\PaymentsController;
use App\Controllers\MaintenanceController;
use App\Controllers\NotificationController;
use App\Controllers\ReportsController;
use App\Utils\Response;
use App\Middleware\CorsMiddleware;

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/api/v1';

// Remove base path from request URI
$path = substr($requestUri, strlen($basePath));

// Route mapping with correct controller names
$routes = [
    'POST' => [
        '/auth/register' => [AuthController::class, 'register'],
        '/auth/login' => [AuthController::class, 'login'],
        '/auth/logout' => [AuthController::class, 'logout'],
        '/auth/forgot-password' => [AuthController::class, 'forgotPassword'],
        '/auth/reset-password' => [AuthController::class, 'resetPassword'],
        '/properties' => [PropertiesController::class, 'store'],        // Fixed
        '/tenants' => [TenantsController::class, 'store'],              // Fixed
        '/payments' => [PaymentsController::class, 'store'],            // Fixed
        '/maintenance' => [MaintenanceController::class, 'store'],
        '/reports/export' => [ReportsController::class, 'export'],      // Fixed
        '/notifications/mark-all-read' => [NotificationController::class, 'markAllAsRead']
    ],
    'GET' => [
        '/properties' => [PropertiesController::class, 'index'],        // Fixed
        '/properties/(\d+)' => [PropertiesController::class, 'show'],   // Fixed
        '/tenants' => [TenantsController::class, 'index'],              // Fixed
        '/tenants/(\d+)' => [TenantsController::class, 'show'],         // Fixed
        '/tenants/current' => [TenantsController::class, 'getCurrentTenant'], // Fixed
        '/payments' => [PaymentsController::class, 'index'],            // Fixed
        '/payments/(\d+)' => [PaymentsController::class, 'show'],       // Fixed
        '/payments/(\d+)/receipt' => [PaymentsController::class, 'generateReceipt'], // Fixed
        '/maintenance' => [MaintenanceController::class, 'index'],
        '/maintenance/(\d+)' => [MaintenanceController::class, 'show'],
        '/reports/revenue' => [ReportsController::class, 'revenue'],    // Fixed
        '/reports/occupancy' => [ReportsController::class, 'occupancy'], // Fixed
        '/reports/maintenance' => [ReportsController::class, 'maintenance'], // Fixed
        '/users/me' => [UserController::class, 'getProfile'],
        '/notifications' => [NotificationController::class, 'index'],
        '/notifications/unread-count' => [NotificationController::class, 'unreadCount']
    ],
    'PUT' => [
        '/properties/(\d+)' => [PropertiesController::class, 'update'], // Fixed
        '/tenants/(\d+)' => [TenantsController::class, 'update'],       // Fixed
        '/maintenance/(\d+)' => [MaintenanceController::class, 'update'],
        '/users/me' => [UserController::class, 'updateProfile'],
        '/users/change-password' => [UserController::class, 'changePassword'],
        '/notifications/(\d+)/read' => [NotificationController::class, 'markAsRead']
    ],
    'DELETE' => [
        '/properties/(\d+)' => [PropertiesController::class, 'destroy'], // Fixed
        '/tenants/(\d+)' => [TenantsController::class, 'destroy'],       // Fixed
        '/maintenance/(\d+)' => [MaintenanceController::class, 'destroy']
    ]
];

// Find matching route
$matched = false;
foreach ($routes[$requestMethod] as $route => $handler) {
    $pattern = "#^{$route}$#";
    if (preg_match($pattern, $path, $matches)) {
        $matched = true;
        
        $controller = new $handler[0]();
        $method = $handler[1];
        
        // Pass URL parameters to method
        array_shift($matches);
        call_user_func_array([$controller, $method], $matches);
        break;
    }
}

// Handle 404
if (!$matched) {
    Response::error('Endpoint not found', 'NOT_FOUND', [], 404);
}