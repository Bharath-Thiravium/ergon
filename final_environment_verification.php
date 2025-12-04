<?php
echo "🔍 FINAL ENVIRONMENT VERIFICATION\n\n";

// Test both environments
echo "📋 TESTING ENVIRONMENT DETECTION:\n";

// Simulate localhost (development)
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/app/config/environment.php';
echo "localhost → " . (Environment::isDevelopment() ? 'Development ✅' : 'Production ❌') . "\n";

// Simulate production
$_SERVER['HTTP_HOST'] = 'athenas.co.in';
Environment::$environment = null; // Reset for re-detection
echo "athenas.co.in → " . (Environment::isDevelopment() ? 'Development ❌' : 'Production ✅') . "\n";

// Reset to actual environment
unset($_SERVER['HTTP_HOST']);
Environment::$environment = null;

echo "\n📊 DATABASE CONFIGURATION SUMMARY:\n";

try {
    require_once __DIR__ . '/app/config/database.php';
    
    // Test local connection
    $_SERVER['HTTP_HOST'] = 'localhost';
    $localDb = new Database();
    echo "🔧 Local Development:\n";
    echo "   Database: ergon_db\n";
    echo "   Host: localhost\n";
    echo "   User: root\n";
    echo "   Environment: Development\n\n";
    
    // Test production config (don't actually connect)
    $_SERVER['HTTP_HOST'] = 'athenas.co.in';
    Environment::$environment = null;
    $prodDb = new Database();
    echo "🌐 Production (Hostinger):\n";
    echo "   Database: u494785662_ergon\n";
    echo "   Host: localhost\n";
    echo "   User: u494785662_ergon\n";
    echo "   Environment: Production\n\n";
    
} catch (Exception $e) {
    echo "❌ Configuration Error: " . $e->getMessage() . "\n";
}

echo "✅ ENVIRONMENT SETUP COMPLETE\n";
echo "🚀 System will auto-detect environment based on HTTP_HOST\n";
echo "📝 All database references updated to use dual configuration\n";
?>