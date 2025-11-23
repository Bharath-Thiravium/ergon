<?php
echo "<h3>PostgreSQL Connection Test</h3>";

// Test 1: Check extension
if (function_exists('pg_connect')) {
    echo "✅ PostgreSQL extension enabled<br>";
    
    // Test 2: Direct connection
    $conn = pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango");
    
    if ($conn) {
        echo "✅ Direct PostgreSQL connection successful<br>";
        
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' LIMIT 3");
        echo "📋 Sample tables:<br>";
        
        while ($row = pg_fetch_assoc($result)) {
            echo "- " . $row['table_name'] . "<br>";
        }
        
        pg_close($conn);
    } else {
        echo "❌ Direct connection failed<br>";
    }
} else {
    echo "❌ PostgreSQL extension not found<br>";
}

// Test 3: Bridge API (if you set it up)
echo "<br><h4>Bridge API Test:</h4>";
$bridgeUrl = 'https://your-bridge-server.com/postgres_bridge.php'; // Update this

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $bridgeUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'tables']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Bridge API working<br>";
} else {
    echo "❌ Bridge API not accessible (update URL or deploy bridge)<br>";
}
?>