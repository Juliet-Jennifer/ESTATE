<?php
namespace App\Models;

use App\Utils\Database;
use PDO;
class Tenant extends BaseModel {
    protected $table = 'tenants';

    public function getActiveTenants($propertyId = null) {
        $sql = "SELECT t.*, u.full_name, u.email, u.phone, p.name as property_name 
                FROM tenants t 
                JOIN users u ON t.user_id = u.id 
                JOIN properties p ON t.property_id = p.id 
                WHERE t.status = 'active'";
        
        $params = [];

        if ($propertyId) {
            $sql .= " AND t.property_id = ?";
            $params[] = $propertyId;
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantByUserId($userId) {
        $sql = "SELECT t.*, p.name as property_name, p.location, p.city 
                FROM tenants t 
                JOIN properties p ON t.property_id = p.id 
                WHERE t.user_id = ? AND t.status = 'active'";
        
        $stmt = $this->execute($sql, [$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function checkPropertyAvailability($propertyId) {
        $sql = "SELECT COUNT(*) as active_tenants 
                FROM tenants 
                WHERE property_id = ? AND status = 'active'";
        
        $stmt = $this->execute($sql, [$propertyId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['active_tenants'] == 0;
    }

    public function terminateLease($tenantId, $moveOutDate) {
        return $this->update($tenantId, [
            'status' => 'inactive',
            'move_out_date' => $moveOutDate
        ]);
    }

    public function getExpiringLeases($days = 30) {
        $sql = "SELECT t.*, u.full_name, u.email, p.name as property_name 
                FROM tenants t 
                JOIN users u ON t.user_id = u.id 
                JOIN properties p ON t.property_id = p.id 
                WHERE t.status = 'active' 
                AND DATEDIFF(t.lease_end_date, CURDATE()) <= ?";
        
        $stmt = $this->execute($sql, [$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // MISSING METHOD: Update tenant information
    public function update($id, $data) {
        $fields = [];
        $params = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    // MISSING METHOD: Soft delete (mark inactive)
    public function softDelete($id) {
        return $this->update($id, [
            'status' => 'inactive',
            'move_out_date' => date('Y-m-d')
        ]);
    }
}