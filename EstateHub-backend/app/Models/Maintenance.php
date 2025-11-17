<?php
namespace App\Models;

use App\Utils\Database;
use PDO;
class Maintenance extends BaseModel {
    protected $table = 'maintenance_requests';

    public function getAllRequests($limit = 10, $offset = 0, $filters = []) {
        $sql = "SELECT mr.*, p.name as property_name, u.full_name as reported_by_name, 
                       t.user_id as tenant_user_id, ut.full_name as tenant_name 
                FROM maintenance_requests mr 
                JOIN properties p ON mr.property_id = p.id 
                JOIN users u ON mr.reported_by = u.id 
                LEFT JOIN tenants t ON mr.tenant_id = t.id 
                LEFT JOIN users ut ON t.user_id = ut.id 
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND mr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND mr.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['property_id'])) {
            $sql .= " AND mr.property_id = ?";
            $params[] = $filters['property_id'];
        }

        $sql .= " ORDER BY mr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAllRequests($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }

        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getRequestsByTenant($tenantId, $limit = 10, $offset = 0) {
        $sql = "SELECT mr.*, p.name as property_name 
                FROM maintenance_requests mr 
                JOIN properties p ON mr.property_id = p.id 
                WHERE mr.tenant_id = ? 
                ORDER BY mr.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->execute($sql, [$tenantId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countRequestsByTenant($tenantId) {
        $sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE tenant_id = ?";
        $stmt = $this->execute($sql, [$tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getRequestsByStatus($status) {
        $sql = "SELECT mr.*, p.name as property_name, u.full_name as reported_by_name 
                FROM maintenance_requests mr 
                JOIN properties p ON mr.property_id = p.id 
                JOIN users u ON mr.reported_by = u.id 
                WHERE mr.status = ? 
                ORDER BY mr.priority DESC, mr.created_at ASC";
        
        $stmt = $this->execute($sql, [$status]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}