<?php
// Application Constants
define('APP_NAME', $_ENV['APP_NAME'] ?? 'EstateHub');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');

// Database Constants
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
define('DB_NAME', $_ENV['DB_NAME'] ?? 'estatehub');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// File Upload Constants
define('MAX_UPLOAD_SIZE', $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'image/jpeg,image/png,image/webp'));
define('ALLOWED_DOCUMENT_TYPES', explode(',', $_ENV['ALLOWED_DOCUMENT_TYPES'] ?? 'application/pdf'));

// Path Constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads/');
define('LOG_PATH', STORAGE_PATH . '/logs/');
define('CACHE_PATH', STORAGE_PATH . '/cache/');

// JWT Constants
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production');
define('JWT_EXPIRY', $_ENV['JWT_EXPIRY'] ?? 86400); // 24 hours
define('JWT_ALGORITHM', 'HS256');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TENANT', 'tenant');

// Property Status
define('PROPERTY_AVAILABLE', 'available');
define('PROPERTY_OCCUPIED', 'occupied');
define('PROPERTY_MAINTENANCE', 'maintenance');

// Payment Status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_OVERDUE', 'overdue');
define('PAYMENT_CANCELLED', 'cancelled');

// Payment Methods
define('PAYMENT_MPESA', 'mpesa');
define('PAYMENT_BANK_TRANSFER', 'bank_transfer');
define('PAYMENT_CASH', 'cash');
define('PAYMENT_CHEQUE', 'cheque');

// Payment Types
define('PAYMENT_TYPE_RENT', 'rent');
define('PAYMENT_TYPE_DEPOSIT', 'deposit');
define('PAYMENT_TYPE_MAINTENANCE', 'maintenance');
define('PAYMENT_TYPE_PENALTY', 'penalty');

// Maintenance Priority
define('MAINTENANCE_LOW', 'low');
define('MAINTENANCE_MEDIUM', 'medium');
define('MAINTENANCE_HIGH', 'high');
define('MAINTENANCE_EMERGENCY', 'emergency');

// Maintenance Status
define('MAINTENANCE_PENDING', 'pending');
define('MAINTENANCE_IN_PROGRESS', 'in_progress');
define('MAINTENANCE_COMPLETED', 'completed');
define('MAINTENANCE_CANCELLED', 'cancelled');

// Maintenance Categories
define('MAINTENANCE_PLUMBING', 'plumbing');
define('MAINTENANCE_ELECTRICAL', 'electrical');
define('MAINTENANCE_STRUCTURAL', 'structural');
define('MAINTENANCE_APPLIANCE', 'appliance');
define('MAINTENANCE_OTHER', 'other');

// Notification Types
define('NOTIFICATION_INFO', 'info');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_ERROR', 'error');

// Notification Categories
define('NOTIFICATION_PAYMENT', 'payment');
define('NOTIFICATION_MAINTENANCE', 'maintenance');
define('NOTIFICATION_LEASE', 'lease');
define('NOTIFICATION_SYSTEM', 'system');

// User Status
define('USER_ACTIVE', 'active');
define('USER_INACTIVE', 'inactive');
define('USER_SUSPENDED', 'suspended');

// Deposit Status
define('DEPOSIT_UNPAID', 'unpaid');
define('DEPOSIT_PAID', 'paid');
define('DEPOSIT_REFUNDED', 'refunded');

// Response Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_VALIDATION_ERROR', 422);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_SERVER_ERROR', 500);

// Error Codes
define('ERROR_VALIDATION', 'VALIDATION_ERROR');
define('ERROR_AUTHENTICATION', 'AUTHENTICATION_ERROR');
define('ERROR_AUTHORIZATION', 'AUTHORIZATION_ERROR');
define('ERROR_NOT_FOUND', 'NOT_FOUND_ERROR');
define('ERROR_SERVER', 'SERVER_ERROR');
define('ERROR_DATABASE', 'DATABASE_ERROR');
define('ERROR_FILE_UPLOAD', 'FILE_UPLOAD_ERROR');