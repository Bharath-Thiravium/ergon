<?php

return [
    'mysql' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['MYSQL_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'ergon',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],
    
    'postgres' => [
        'host' => $_ENV['POSTGRES_HOST'] ?? 'localhost',
        'port' => $_ENV['POSTGRES_PORT'] ?? 5432,
        'database' => $_ENV['POSTGRES_DATABASE'] ?? 'ergon_main',
        'username' => $_ENV['POSTGRES_USERNAME'] ?? 'postgres',
        'password' => $_ENV['POSTGRES_PASSWORD'] ?? '',
        'charset' => 'utf8',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ]
];