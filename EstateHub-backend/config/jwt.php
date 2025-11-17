<?php
return [
    'secret' => $_ENV['JWT_SECRET'] ?? 'your-default-secret-key-change-in-production',
    'algorithm' => 'HS256',
    'expiration' => $_ENV['JWT_EXPIRY'] ?? 86400, // 24 hours in seconds
    
    'leeway' => 60, // 1 minute leeway for clock skew
    
    'blacklist_enabled' => true,
    'blacklist_grace_period' => 30, // 30 seconds grace period
    
    'persistent_claims' => ['sub', 'email', 'role'],
];