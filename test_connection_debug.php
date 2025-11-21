<?php
echo "<h3>PostgreSQL Connection Debug</h3>";

// Test different connection methods
$configs = [
    "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango",
    "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable",
    "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango connect_timeout=10",
];

foreach ($configs as $i => $config) {
    echo "<br><strong>Test " . ($i+1) . ":</strong> ";
    
    $conn = @pg_connect($config);
    
    if ($conn) {
        echo "✅ Connected with config " . ($i+1) . "<br>";
        
        $result = pg_query($conn, "SELECT version()");
        if ($result) {
            $row = pg_fetch_assoc($result);
            echo "PostgreSQL Version: " . $row['version'] . "<br>";
        }
        
        pg_close($conn);
        break;
    } else {
        echo "❌ Failed<br>";
        echo "Error: " . pg_last_error() . "<br>";
    }
}

// Test if it's a firewall/network issue
echo "<br><h4>Network Test:</h4>";
$socket = @fsockopen('72.60.218.167', 5432, $errno, $errstr, 5);
if ($socket) {
    echo "✅ Port 5432 is accessible<br>";
    fclose($socket);
} else {
    echo "❌ Port 5432 blocked or server down<br>";
    echo "Error: $errstr ($errno)<br>";
}
?>