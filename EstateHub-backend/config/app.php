<?php
return [
    'name' => $_ENV['APP_NAME'] ?? 'EstateHub',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone' => 'Africa/Nairobi',
    
    'providers' => [
        // Service providers would be listed here in a framework
    ],
    
    'upload' => [
        'max_size' => $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880, // 5MB
        'allowed_image_types' => explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'image/jpeg,image/png,image/webp'),
        'allowed_document_types' => explode(',', $_ENV['ALLOWED_DOCUMENT_TYPES'] ?? 'application/pdf'),
    ],
    
    'cors' => [
        'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000', $_ENV['APP_URL'] ?? 'http://localhost:8000'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
    ]
];