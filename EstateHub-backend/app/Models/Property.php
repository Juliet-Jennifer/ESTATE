<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class Property {
    private $db;
    private $table = 'properties';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get available properties with filters
     */
    public function getAvailableProperties($filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['city'])) {
            $sql .= " AND city = :city";
            $params[':city'] = $filters['city'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['bedrooms'])) {
            $sql .= " AND bedrooms >= :bedrooms";
            $params[':bedrooms'] = $filters['bedrooms'];
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        
        // Whitelist sort columns to prevent SQL injection
        $allowedSortColumns = ['created_at', 'price', 'name', 'bedrooms', 'size'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count available properties with filters
     */
    public function countAvailableProperties($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['city'])) {
            $sql .= " AND city = :city";
            $params[':city'] = $filters['city'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['bedrooms'])) {
            $sql .= " AND bedrooms >= :bedrooms";
            $params[':bedrooms'] = $filters['bedrooms'];
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find property by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new property
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (owner_id, name, description, location, city, bedrooms, bathrooms, size, price, amenities, images, featured_image, status, created_at, updated_at) 
                VALUES 
                (:owner_id, :name, :description, :location, :city, :bedrooms, :bathrooms, :size, :price, :amenities, :images, :featured_image, :status, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':owner_id', $data['owner_id']);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':location', $data['location']);
        $stmt->bindValue(':city', $data['city']);
        $stmt->bindValue(':bedrooms', $data['bedrooms'], PDO::PARAM_INT);
        $stmt->bindValue(':bathrooms', $data['bathrooms'], PDO::PARAM_INT);
        $stmt->bindValue(':size', $data['size'], PDO::PARAM_INT);
        $stmt->bindValue(':price', $data['price']);
        $stmt->bindValue(':amenities', $data['amenities']);
        $stmt->bindValue(':images', $data['images']);
        $stmt->bindValue(':featured_image', $data['featured_image']);
        $stmt->bindValue(':status', $data['status']);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Update property
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Delete property
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Get properties by owner
     */
    public function getByOwner($ownerId) {
        $sql = "SELECT * FROM {$this->table} WHERE owner_id = :owner_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':owner_id', $ownerId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}