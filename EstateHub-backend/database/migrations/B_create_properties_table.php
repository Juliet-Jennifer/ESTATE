<?php
class BCreatePropertiesTable {
    public function up() {
        $sql = "
        CREATE TABLE properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(200) NOT NULL,
            city VARCHAR(100) NOT NULL,
            bedrooms TINYINT NOT NULL,
            bathrooms TINYINT NOT NULL,
            size INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
            amenities JSON,
            images JSON,
            featured_image VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status_city (status, city),
            INDEX idx_price (price)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }

    public function down() {
        return "DROP TABLE properties;";
    }
}