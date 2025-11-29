<?php
// inc/config.php
// Database configuration for Hostinger
// IMPORTANT: Edit these values with your Hostinger MySQL credentials

return [
    'db' => [
        'host' => 'localhost',          // Usually localhost on Hostinger
        'dbname' => 'your_db_name',     // Your MySQL database name
        'user' => 'your_db_user',       // Your MySQL username
        'pass' => 'your_db_pass',       // Your MySQL password
        'charset' => 'utf8mb4',
    ],
    // Maximum rows processed per upload (to avoid timeouts)
    'max_rows_per_upload' => 5000,
    // Max file size (5MB)
    'max_file_size' => 5 * 1024 * 1024,
];