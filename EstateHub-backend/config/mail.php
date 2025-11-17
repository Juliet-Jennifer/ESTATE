<?php
return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
    'port' => $_ENV['MAIL_PORT'] ?? 2525,
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@estatehub.com',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'EstateHub'
    ],
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
];