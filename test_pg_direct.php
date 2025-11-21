<?php
echo "<h3>Direct PostgreSQL Test</h3>";

// Test port accessibility
$socket = @fsockopen('72.60.218.167', 5432, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Port 5432 accessible<br>";
    fclose($socket);
} else {
    echo "❌ Port blocked: $errstr ($errno)<br>";
}

// Test PostgreSQL connection
$configs = [
    "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable",
    "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=require",
];

foreach ($configs as $i => $config) {
    echo "<br>Test " . ($i+1) . ": ";
    $conn = @pg_connect($config);
    
    if ($conn) {
        echo "✅ Connected!<br>";
        
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' LIMIT 3");
        echo "Sample tables:<br>";
        
        while ($row = pg_fetch_assoc($result)) {
            echo "- " . $row['table_name'] . "<br>";
        }
        
        pg_close($conn);
        break;
    } else {
        echo "❌ Failed<br>";
    }
}
?>