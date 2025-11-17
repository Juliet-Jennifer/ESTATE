<?php
class ACreateUsersTable {
    public function up() {
        $sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            role ENUM('admin', 'tenant') NOT NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            avatar VARCHAR(255) NULL,
            email_verified BOOLEAN DEFAULT FALSE,
            email_verification_token VARCHAR(100) NULL,
            reset_token VARCHAR(100) NULL,
            reset_token_expiry DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }

    public function down() {
        return "DROP TABLE users;";
    }
}