<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Middleware\CorsMiddleware;


// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Load constants
require_once __DIR__ . '/../config/constants.php';

// Error reporting
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// CORS handler
CorsMiddleware::handle();

// Route request
require_once __DIR__ . '/../routes/api.php';
