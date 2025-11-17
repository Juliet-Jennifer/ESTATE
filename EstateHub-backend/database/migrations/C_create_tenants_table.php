<?php
class CCreateTenantsTable {
    public function up() {
        $sql = "
        CREATE TABLE tenants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_id INT NOT NULL,
            lease_start_date DATE NOT NULL,
            lease_end_date DATE NOT NULL,
            monthly_rent DECIMAL(10,2) NOT NULL,
            deposit_amount DECIMAL(10,2) NOT NULL,
            deposit_status ENUM('paid', 'unpaid', 'refunded') DEFAULT 'unpaid',
            emergency_contact_name VARCHAR(100) NOT NULL,
            emergency_contact_phone VARCHAR(20) NOT NULL,
            move_in_date DATE NULL,
            move_out_date DATE NULL,
            status ENUM('active', 'inactive', 'evicted') DEFAULT 'active',
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_property_id (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }

    public function down() {
        return "DROP TABLE tenants;";
    }
}