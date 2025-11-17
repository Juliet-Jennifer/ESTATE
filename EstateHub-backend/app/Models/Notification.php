<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class Notification extends BaseModel {
    protected $table = 'notifications';

    public function getUnreadByUser($userId, $limit = 10) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->execute($sql, [$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE {$this->table} SET is_read = TRUE WHERE id = ? AND user_id = ?";
        $stmt = $this->execute($sql, [$notificationId, $userId]);
        return $stmt->rowCount();
    }

    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->execute($sql, [$userId]);
        return $stmt->rowCount();
    }

    public function createNotification($userId, $title, $message, $type = 'info', $category = 'system', $actionUrl = null) {
        return $this->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => $category,
            'action_url' => $actionUrl,
            'is_read' => false
        ]);
    }

    public function getByUser($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->execute($sql, [$userId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countUnreadByUser($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->execute($sql, [$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }
}