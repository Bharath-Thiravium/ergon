<?php
// Hostinger PHP 8.4 Optimizations

// 1. Enable OPcache optimizations (add to .htaccess or php.ini)
/*
opcache.enable=1
opcache.memory_consumption=196M
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=7963
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
*/

// 2. Session optimizations
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 1440);
ini_set('session.cookie_samesite', 'Lax'); // Better than 'None' for security

// 3. Memory and execution optimizations
ini_set('memory_limit', '512M'); // Use less than max 1536M
ini_set('max_execution_time', 60); // Reduce from 360 for web requests

// 4. Database connection pooling
class OptimizedDatabase {
    private static ?PDO $connection = null;
    
    public static function connect(): PDO {
        if (self::$connection === null) {
            self::$connection = new PDO(
                "mysql:host=localhost;dbname=u494785662_ergon;charset=utf8mb4",
                "u494785662_ergon",
                "@Admin@2025@",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_PERSISTENT => true, // Connection pooling
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                ]
            );
        }
        return self::$connection;
    }
}

// 5. Output compression
if (!ob_get_level()) {
    ob_start('ob_gzhandler');
}

// 6. Reduce error reporting for production
error_reporting(E_ERROR | E_WARNING | E_PARSE);

echo "Hostinger optimizations applied\n";
?>