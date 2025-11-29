<?php
echo "🔧 Simple Database Test\n";
echo "======================\n\n";

// Test 1: Check PHP extensions
echo "📋 PHP Extensions:\n";
echo "- PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "\n";
echo "- PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n";
echo "- PostgreSQL: " . (extension_loaded('pgsql') ? '✅' : '❌') . "\n\n";

// Test 2: Try database connection
try {
    require_once __DIR__ . '/app/config/database.php';
    
    echo "🔌 Testing MySQL Connection...\n";
    $db = Database::connect();
    echo "✅ MySQL Connected Successfully\n\n";
    
    // Test 3: Check if tables exist
    echo "🗄️  Checking Tables:\n";
    
    $tables = ['finance_consolidated', 'dashboard_stats', 'funnel_stats'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch() ? '✅' : '❌';
        echo "- $table: $exists\n";
    }
    
    echo "\n🎯 Next Steps:\n";
    echo "1. Run: SOURCE database/finance_etl_tables.sql\n";
    echo "2. Visit: /ergon/finance\n";
    echo "3. Click 'Sync Data'\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Fix:\n";
    echo "1. Start MySQL/MariaDB\n";
    echo "2. Check database credentials\n";
    echo "3. Install PDO MySQL extension\n";
}

echo "\n======================\n";
?>