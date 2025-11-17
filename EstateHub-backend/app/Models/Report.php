<?php
namespace App\Models;

use App\Utils\Database;  // Fixed: Changed from app\Utils to App\Utils
use PDO;

class Report {  // Removed extends BaseModel since we don't know if it exists
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Execute a query with parameters
     */
    private function execute($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Generate revenue report
     */
    public function generateRevenueReport($startDate, $endDate, $propertyId = null) {
        $sql = "SELECT 
                    p.name as property_name,
                    p.city,
                    COUNT(pm.id) as total_payments,
                    SUM(pm.amount) as total_revenue,
                    AVG(pm.amount) as average_payment,
                    MIN(pm.amount) as min_payment,
                    MAX(pm.amount) as max_payment
                FROM payments pm
                JOIN properties p ON pm.property_id = p.id
                WHERE pm.payment_date BETWEEN ? AND ?
                    AND pm.status = 'completed'";  // Changed from 'paid' to 'completed'
        
        $params = [$startDate, $endDate];
        
        if ($propertyId) {
            $sql .= " AND pm.property_id = ?";
            $params[] = $propertyId;
        }
        
        $sql .= " GROUP BY p.id, p.name, p.city
                  ORDER BY total_revenue DESC";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get revenue report (for ReportsController compatibility)
     */
    public function getRevenueReport($startDate, $endDate, $groupBy = 'month') {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        $sql = "SELECT 
                    DATE_FORMAT(payment_date, ?) as period,
                    payment_type,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount
                FROM payments
                WHERE payment_date BETWEEN ? AND ?
                    AND status = 'completed'
                GROUP BY period, payment_type
                ORDER BY period DESC, payment_type";

        $stmt = $this->execute($sql, [$dateFormat, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get revenue summary
     */
    public function getRevenueSummary($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_revenue,
                    AVG(amount) as average_transaction,
                    SUM(CASE WHEN payment_type = 'rent' THEN amount ELSE 0 END) as rent_revenue,
                    SUM(CASE WHEN payment_type = 'deposit' THEN amount ELSE 0 END) as deposit_revenue,
                    SUM(CASE WHEN payment_type = 'maintenance' THEN amount ELSE 0 END) as maintenance_revenue,
                    SUM(CASE WHEN payment_type = 'utility' THEN amount ELSE 0 END) as utility_revenue,
                    SUM(CASE WHEN payment_type = 'other' THEN amount ELSE 0 END) as other_revenue
                FROM payments
                WHERE payment_date BETWEEN ? AND ?
                    AND status = 'completed'";

        $stmt = $this->execute($sql, [$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate occupancy report
     */
    public function generateOccupancyReport() {
        $sql = "SELECT 
                    p.id,
                    p.name as property_name,
                    p.city,
                    p.status,
                    p.bedrooms,
                    COUNT(t.id) as total_tenants,
                    SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) as active_tenants,
                    CASE 
                        WHEN COUNT(t.id) > 0 
                        THEN ROUND((SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 2)
                        ELSE 0
                    END as occupancy_rate
                FROM properties p
                LEFT JOIN tenants t ON p.id = t.property_id
                GROUP BY p.id, p.name, p.city, p.status, p.bedrooms
                ORDER BY occupancy_rate DESC";

        $stmt = $this->execute($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get occupancy report (for ReportsController)
     */
    public function getOccupancyReport($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    p.id as property_id,
                    p.name as property_name,
                    p.city,
                    p.status as property_status,
                    COUNT(DISTINCT t.id) as tenant_count,
                    SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) as active_tenants,
                    MAX(t.lease_end) as latest_lease_end,
                    p.price as monthly_rent
                FROM properties p
                LEFT JOIN tenants t ON p.id = t.property_id";

        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " WHERE t.lease_start <= ? AND t.lease_end >= ?";
            $params = [$endDate, $startDate];
        }

        $sql .= " GROUP BY p.id, p.name, p.city, p.status, p.price
                  ORDER BY p.city, p.name";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get occupancy summary
     */
    public function getOccupancySummary() {
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_properties,
                    SUM(CASE WHEN p.status = 'occupied' THEN 1 ELSE 0 END) as occupied_properties,
                    SUM(CASE WHEN p.status = 'available' THEN 1 ELSE 0 END) as available_properties,
                    SUM(CASE WHEN p.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_properties,
                    COUNT(DISTINCT CASE WHEN t.status = 'active' THEN t.id END) as active_tenants,
                    CASE 
                        WHEN COUNT(DISTINCT p.id) > 0 
                        THEN ROUND(
                            (SUM(CASE WHEN p.status = 'occupied' THEN 1 ELSE 0 END) / COUNT(DISTINCT p.id)) * 100, 
                            2
                        )
                        ELSE 0
                    END as occupancy_rate
                FROM properties p
                LEFT JOIN tenants t ON p.id = t.property_id";

        $stmt = $this->execute($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate maintenance report
     */
    public function generateMaintenanceReport($startDate, $endDate) {
        $sql = "SELECT 
                    p.name as property_name,
                    p.city,
                    COUNT(mr.id) as total_requests,
                    SUM(CASE WHEN mr.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                    SUM(CASE WHEN mr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN mr.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                    SUM(mr.cost) as total_cost,
                    AVG(mr.cost) as average_cost
                FROM maintenance_requests mr
                JOIN properties p ON mr.property_id = p.id
                WHERE mr.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.name, p.city
                ORDER BY total_requests DESC";

        $stmt = $this->execute($sql, [$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance report (for ReportsController)
     */
    public function getMaintenanceReport($startDate, $endDate, $status = null) {
        $sql = "SELECT 
                    m.id,
                    m.title,
                    m.description,
                    m.priority,
                    m.status,
                    m.category,
                    m.cost,
                    m.created_at,
                    m.completed_at,
                    p.name as property_name,
                    p.city,
                    DATEDIFF(
                        COALESCE(m.completed_at, NOW()), 
                        m.created_at
                    ) as days_to_resolve
                FROM maintenance_requests m
                INNER JOIN properties p ON m.property_id = p.id
                WHERE m.created_at BETWEEN ? AND ?";

        $params = [$startDate, $endDate];

        if ($status) {
            $sql .= " AND m.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY m.created_at DESC";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance summary
     */
    public function getMaintenanceSummary($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_requests,
                    SUM(cost) as total_cost,
                    AVG(cost) as average_cost,
                    AVG(
                        DATEDIFF(
                            COALESCE(completed_at, NOW()), 
                            created_at
                        )
                    ) as avg_resolution_days
                FROM maintenance_requests
                WHERE created_at BETWEEN ? AND ?";

        $stmt = $this->execute($sql, [$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate payment analysis
     */
    public function generatePaymentAnalysis($months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                FROM payments
                WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month DESC";

        $stmt = $this->execute($sql, [$months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM properties WHERE status = 'available') as available_properties,
                    (SELECT COUNT(*) FROM properties WHERE status = 'occupied') as occupied_properties,
                    (SELECT COUNT(*) FROM tenants WHERE status = 'active') as active_tenants,
                    (SELECT COUNT(*) FROM maintenance_requests WHERE status = 'pending') as pending_maintenance,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) as monthly_revenue,
                    (SELECT COUNT(*) FROM payments WHERE status = 'pending' AND payment_date < CURDATE()) as overdue_payments";

        $stmt = $this->execute($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get property performance report
     */
    public function getPropertyPerformance($startDate, $endDate) {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.city,
                    p.price as monthly_rent,
                    COUNT(DISTINCT pay.id) as payment_count,
                    COALESCE(SUM(pay.amount), 0) as total_revenue,
                    COUNT(DISTINCT m.id) as maintenance_count,
                    COALESCE(SUM(m.cost), 0) as maintenance_cost,
                    (COALESCE(SUM(pay.amount), 0) - COALESCE(SUM(m.cost), 0)) as net_income
                FROM properties p
                LEFT JOIN payments pay ON p.id = pay.property_id 
                    AND pay.payment_date BETWEEN ? AND ?
                    AND pay.status = 'completed'
                LEFT JOIN maintenance_requests m ON p.id = m.property_id 
                    AND m.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.name, p.city, p.price
                ORDER BY net_income DESC";

        $stmt = $this->execute($sql, [$startDate, $endDate, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get tenant payment history
     */
    public function getTenantPaymentHistory($tenantId, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    pay.*,
                    p.name as property_name,
                    u.full_name as tenant_name
                FROM payments pay
                INNER JOIN tenants t ON pay.tenant_id = t.id
                INNER JOIN properties p ON pay.property_id = p.id
                INNER JOIN users u ON t.user_id = u.id
                WHERE pay.tenant_id = ?";

        $params = [$tenantId];

        if ($startDate && $endDate) {
            $sql .= " AND pay.payment_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY pay.payment_date DESC";

        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}