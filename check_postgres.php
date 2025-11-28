<?php
// PostgreSQL Extension Check for Hostinger
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PostgreSQL Extension Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>PostgreSQL Extension Check</h1>
    
    <?php
    echo "<h2>PHP Version: " . PHP_VERSION . "</h2>";
    
    // Check if PostgreSQL functions exist
    echo "<h3>Extension Status:</h3>";
    if (function_exists('pg_connect')) {
        echo "<div class='success'>✅ pg_connect function available</div>";
    } else {
        echo "<div class='error'>❌ pg_connect function not available</div>";
    }
    
    if (extension_loaded('pgsql')) {
        echo "<div class='success'>✅ pgsql extension loaded</div>";
    } else {
        echo "<div class='error'>❌ pgsql extension not loaded</div>";
    }
    
    if (extension_loaded('pdo_pgsql')) {
        echo "<div class='success'>✅ pdo_pgsql extension loaded</div>";
    } else {
        echo "<div class='error'>❌ pdo_pgsql extension not loaded</div>";
    }
    
    // Show loaded extensions
    echo "<h3>All Loaded Extensions:</h3>";
    $extensions = get_loaded_extensions();
    sort($extensions);
    echo "<pre>";
    foreach ($extensions as $ext) {
        if (strpos(strtolower($ext), 'pg') !== false || strpos(strtolower($ext), 'post') !== false) {
            echo "🔍 $ext\n";
        } else {
            echo "$ext\n";
        }
    }
    echo "</pre>";
    
    // Test connection if extension available
    if (function_exists('pg_connect')) {
        echo "<h3>Connection Test:</h3>";
        $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
        
        if ($conn) {
            echo "<div class='success'>✅ PostgreSQL connection successful</div>";
            
            // Test query
            $result = @pg_query($conn, "SELECT version()");
            if ($result) {
                $version = pg_fetch_row($result);
                echo "<div class='info'>📊 PostgreSQL Version: " . $version[0] . "</div>";
            }
            
            // Check tables
            $result = @pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('invoices', 'quotations', 'customers') ORDER BY table_name");
            if ($result) {
                echo "<h4>Available Finance Tables:</h4>";
                while ($row = pg_fetch_row($result)) {
                    echo "<div class='info'>📋 " . $row[0] . "</div>";
                }
            }
            
            pg_close($conn);
        } else {
            echo "<div class='error'>❌ PostgreSQL connection failed: " . pg_last_error() . "</div>";
        }
    }
    
    // Show PHP info for debugging
    echo "<h3>PHP Configuration:</h3>";
    echo "<div class='info'>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</div>";
    echo "<div class='info'>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
    ?>
    
    <p><a href="/ergon/finance">← Back to Finance Dashboard</a></p>
</body>
</html>