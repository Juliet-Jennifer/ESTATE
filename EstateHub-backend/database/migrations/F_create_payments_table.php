<?php
class FCreatePaymentsTable {
    public function up() {
        $sql = "
        CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            property_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_type ENUM('rent', 'deposit', 'maintenance', 'penalty') NOT NULL,
            payment_method ENUM('mpesa', 'bank_transfer', 'cash', 'cheque') NOT NULL,
            transaction_reference VARCHAR(100) UNIQUE NOT NULL,
            payment_date DATE NOT NULL,
            due_date DATE NOT NULL,
            status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
            receipt_number VARCHAR(50) UNIQUE NULL,
            notes TEXT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status_date (status, payment_date),
            INDEX idx_tenant_id (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }

    public function down() {
        return "DROP TABLE payments;";
    }
}