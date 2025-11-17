<?php
class DatabaseSeeder {
    private $db;

    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $this->db = new PDO($dsn, $config['username'], $config['password']);
    }

    public function run() {
        $this->seedUsers();
        $this->seedProperties();
        $this->seedTenants();
        echo "Database seeded successfully!\n";
    }

    private function seedUsers() {
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);

        $users = [
            [
                'full_name' => 'System Administrator',
                'email' => 'admin@estatehub.com',
                'password_hash' => $passwordHash,
                'phone' => '+254700000000',
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true
            ],
            [
                'full_name' => 'John Tenant',
                'email' => 'tenant@example.com',
                'password_hash' => $passwordHash,
                'phone' => '+254711111111',
                'role' => 'tenant',
                'status' => 'active',
                'email_verified' => true
            ]
        ];

        foreach ($users as $user) {
            $stmt = $this->db->prepare("
                INSERT INTO users (full_name, email, password_hash, phone, role, status, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array_values($user));
        }
    }

    private function seedProperties() {
        $properties = [
            [
                'owner_id' => 1,
                'name' => 'Luxury Apartment in Westlands',
                'description' => 'Beautiful 3-bedroom apartment with modern amenities and great views.',
                'location' => 'Westlands, Nairobi',
                'city' => 'Nairobi',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'size' => 120,
                'price' => 45000.00,
                'status' => 'available',
                'amenities' => json_encode(['Swimming Pool', 'Gym', 'Parking', 'Security']),
                'images' => json_encode([])
            ]
        ];

        foreach ($properties as $property) {
            $stmt = $this->db->prepare("
                INSERT INTO properties (owner_id, name, description, location, city, bedrooms, bathrooms, size, price, status, amenities, images) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array_values($property));
        }
    }

    private function seedTenants() {
        // This would be populated after users and properties exist
    }
}

// Run seeder
$seeder = new DatabaseSeeder();
$seeder->run();