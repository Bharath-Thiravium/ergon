<?php

class Database {
    public static function connect() {
        $config = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'u494785662_ergon',
            'username' => 'u494785662_ergon',
            'password' => 'ErgonFinance2024!',
            'charset' => 'utf8mb4'
        ];
        
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
}

return [
    'mysql' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'u494785662_ergon',
        'username' => 'u494785662_ergon',
        'password' => 'ErgonFinance2024!',
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