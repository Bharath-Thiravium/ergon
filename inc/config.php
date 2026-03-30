<?php
echo "CONFIG LOADED"; die();
// inc/config.php
// Database configuration for Hostinger
// IMPORTANT: Edit these values with your Hostinger MySQL credentials

return [
    'db' => [
        'host' => '127.0.0.1',
        'dbname' => 'ergon_db',
        'user' => 'root',
        'pass' => 'Saran', // empty if no password
        'charset' => 'utf8mb4',
    ],
    'max_rows_per_upload' => 5000,
    'max_file_size' => 5 * 1024 * 1024,
];
