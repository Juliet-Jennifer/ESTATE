<?php
namespace App\Models;

use App\Utils\Database;
use PDO;
class Payment extends BaseModel {
    protected $table = 'payments';

    public function getAllPayments($limit = 10, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, t.user_id, u.full_name as tenant_name, prop.name as property_name 
                FROM payments p 
                JOIN tenants t ON p.tenant_id = t.id 
                JOIN users u ON t.user_id = u.id 
                JOIN properties prop ON p.property_id = prop.id 
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY p.payment_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAllPayments($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM payments p WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['end_date'];
        }

        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getPaymentsByTenant($tenantId, $limit = 10, $offset = 0) {
        $sql = "SELECT p.*, prop.name as property_name 
                FROM payments p 
                JOIN properties prop ON p.property_id = prop.id 
                WHERE p.tenant_id = ? 
                ORDER BY p.payment_date DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->execute($sql, [$tenantId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countPaymentsByTenant($tenantId) {
        $sql = "SELECT COUNT(*) as total FROM payments WHERE tenant_id = ?";
        $stmt = $this->execute($sql, [$tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getPaymentWithDetails($paymentId) {
        $sql = "SELECT p.*, t.user_id, u.full_name as tenant_name, u.email as tenant_email, 
                       prop.name as property_name, prop.location 
                FROM payments p 
                JOIN tenants t ON p.tenant_id = t.id 
                JOIN users u ON t.user_id = u.id 
                JOIN properties prop ON p.property_id = prop.id 
                WHERE p.id = ?";
        
        $stmt = $this->execute($sql, [$paymentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getOverduePayments() {
        $sql = "SELECT p.*, t.user_id, u.full_name as tenant_name, u.email, prop.name as property_name 
                FROM payments p 
                JOIN tenants t ON p.tenant_id = t.id 
                JOIN users u ON t.user_id = u.id 
                JOIN properties prop ON p.property_id = prop.id 
                WHERE p.status = 'pending' AND p.due_date < CURDATE()";
        
        $stmt = $this->execute($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}