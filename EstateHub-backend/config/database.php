<?php
return [
    'driver'   => 'mysql',
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_NAME') ?: 'estatehub',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '12345678',
    'charset'  => 'utf8',
];
