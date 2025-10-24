<?php
// Hostinger-specific fixes
session_start();

// Force production environment for Hostinger
$_ENV['ENVIRONMENT'] = 'production';

// Check if we're on Hostinger
$isHostinger = strpos($_SERVER['DOCUMENT_ROOT'] ?? '', '/home/') === 0 || 
               strpos($_SERVER['DOCUMENT_ROOT'] ?? '', '/public_html/') !== false;

if ($isHostinger) {
    // Set Hostinger-specific configurations
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
    
    // Create necessary directories
    $dirs = [
        __DIR__ . '/logs',
        __DIR__ . '/storage',
        __DIR__ . '/storage/user_documents',
        __DIR__ . '/public/uploads'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    echo "✅ Hostinger environment configured<br>";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "Script Path: " . __FILE__ . "<br>";
    
    // Test database connection
    try {
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        echo "✅ Database connection successful<br>";
        
        // Test a simple query
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "✅ Users table accessible: " . $result['count'] . " users<br>";
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "Not running on Hostinger<br>";
}
?>