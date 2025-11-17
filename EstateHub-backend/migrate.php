<?php
require_once __DIR__ . '/vendor/autoload.php';

use app\Utils\Database;
use app\Utils\Logger;

function runMigrations() {
    $db = Database::getConnection();
    
    // Get all migration files
    $migrationFiles = glob(__DIR__ . '/database/migrations/*.php');
    sort($migrationFiles);

    // Create migrations table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Get already run migrations
    $stmt = $db->query("SELECT migration FROM migrations");
    $runMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Determine batch number
    $lastBatch = $db->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
    $batch = $lastBatch ? $lastBatch + 1 : 1;

    foreach ($migrationFiles as $file) {
        $migrationName = basename($file, '.php');

        if (!in_array($migrationName, $runMigrations)) {
            echo "Running migration: {$migrationName}\n";

            require_once $file;

            // Convert filename to class name (e.g., A_create_users_table -> ACreateUsersTable)
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $migrationName)));
            if (!class_exists($className)) {
                echo "✗ Migration class {$className} not found in file {$file}\n";
                Logger::error("Migration class {$className} not found", ['file' => $file]);
                exit(1);
            }

            $migration = new $className();

            try {
                // Run migration without transaction (MySQL DDL is non-transactional)
                $sql = $migration->up();
                $db->exec($sql);

                // Record migration as completed
                $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migrationName, $batch]);

                echo "✓ {$migrationName} completed successfully\n";
                Logger::info("Migration completed: {$migrationName}");
            } catch (Exception $e) {
                echo "✗ {$migrationName} failed: " . $e->getMessage() . "\n";
                Logger::error("Migration failed: {$migrationName}", ['error' => $e->getMessage()]);
                exit(1);
            }
        }
    }

    echo "All migrations completed successfully!\n";
}

// Run migrations
runMigrations();
