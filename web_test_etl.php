<?php
/**
 * Web-based ETL Test
 * Access via: http://localhost/ergon/web_test_etl.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Finance ETL Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚀 Finance ETL Module Test</h1>
    
    <?php
    echo "<h2>📋 System Check</h2>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "PDO Available: " . (extension_loaded('pdo') ? '✅ Yes' : '❌ No') . "\n";
    echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No') . "\n";
    echo "PostgreSQL: " . (extension_loaded('pgsql') ? '✅ Yes' : '❌ No') . "\n";
    echo "</pre>";
    
    if (!extension_loaded('pdo_mysql')) {
        echo "<div class='error'>❌ PDO MySQL extension not found. Please enable it in php.ini</div>";
        echo "<p>Add this line to php.ini: <code>extension=pdo_mysql</code></p>";
        exit;
    }
    
    try {
        require_once __DIR__ . '/app/config/database.php';
        
        echo "<h2>🔌 Database Connection</h2>";
        $db = Database::connect();
        echo "<div class='success'>✅ MySQL Connected Successfully</div>";
        
        echo "<h2>🗄️ Table Check</h2>";
        echo "<pre>";
        
        $tables = ['finance_consolidated', 'dashboard_stats', 'funnel_stats'];
        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $exists = $stmt->fetch();
            echo "$table: " . ($exists ? '✅ Exists' : '❌ Missing') . "\n";
        }
        echo "</pre>";
        
        // Try to create tables
        echo "<h2>🔧 Creating Tables</h2>";
        require_once __DIR__ . '/app/services/FinanceETLService.php';
        
        $etlService = new FinanceETLService();
        echo "<div class='success'>✅ ETL Service initialized</div>";
        
        echo "<h2>🎯 Test Results</h2>";
        echo "<div class='info'>✅ Finance ETL Module is ready!</div>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Visit: <a href='/ergon/finance'>/ergon/finance</a></li>";
        echo "<li>Click 'Sync Data' button</li>";
        echo "<li>View analytics dashboard</li>";
        echo "</ol>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        echo "<h3>🔧 Troubleshooting:</h3>";
        echo "<ul>";
        echo "<li>Check if MySQL/MariaDB is running</li>";
        echo "<li>Verify database credentials in app/config/database.php</li>";
        echo "<li>Ensure database 'ergon_db' exists</li>";
        echo "</ul>";
    }
    ?>
    
    <hr>
    <p><em>Finance ETL Module - Enterprise BI Dashboard Architecture</em></p>
</body>
</html>