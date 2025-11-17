<?php
class DCreateMaintenanceTable {
    public function up() {
        $sql = "
        CREATE TABLE maintenance_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            tenant_id INT NOT NULL,
            reported_by INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high', 'emergency') DEFAULT 'medium',
            status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            category ENUM('plumbing', 'electrical', 'structural', 'appliance', 'other') DEFAULT 'other',
            assigned_to VARCHAR(100) NULL,
            estimated_cost DECIMAL(10,2) NULL,
            actual_cost DECIMAL(10,2) NULL,
            images JSON NULL,
            completion_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status_priority (status, priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }

    public function down() {
        return "DROP TABLE maintenance_requests;";
    }
}