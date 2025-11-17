<?php
namespace App\Controllers;

use app\Models\Notification;
use app\Middleware\AuthMiddleware;
use app\Utils\Response;

class NotificationController {
    private $notificationModel;

    public function __construct() {
        $this->notificationModel = new Notification();
    }

    public function index() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $queryParams = $_GET;
        $page = max(1, intval($queryParams['page'] ?? 1));
        $limit = min(50, max(1, intval($queryParams['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationModel->getByUser($user['sub'], $limit, $offset);
        $unreadCount = $this->notificationModel->countUnreadByUser($user['sub']);
        $total = $this->notificationModel->countAll(['user_id' => $user['sub']]);

        Response::success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function markAsRead($id) {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $affected = $this->notificationModel->markAsRead($id, $user['sub']);
        if ($affected === 0) {
            Response::error('Notification not found or already read', 'NOT_FOUND', [], 404);
        }

        Response::success([], 'Notification marked as read');
    }

    public function markAllAsRead() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $affected = $this->notificationModel->markAllAsRead($user['sub']);
        Response::success(['marked_count' => $affected], 'All notifications marked as read');
    }

    public function unreadCount() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $count = $this->notificationModel->countUnreadByUser($user['sub']);
        Response::success(['unread_count' => $count]);
    }
}