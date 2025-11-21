<?php
// Test PostgreSQL connection
if (function_exists('pg_connect')) {
    echo "✅ PostgreSQL extension enabled<br>";
    
    $conn = pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango");
    
    if ($conn) {
        echo "✅ PostgreSQL connection successful<br>";
        
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' LIMIT 5");
        echo "📋 Available tables:<br>";
        
        while ($row = pg_fetch_assoc($result)) {
            echo "- " . $row['table_name'] . "<br>";
        }
        
        pg_close($conn);
    } else {
        echo "❌ PostgreSQL connection failed<br>";
    }
} else {
    echo "❌ PostgreSQL extension not found<br>";
}

// Test MySQL
try {
    require_once 'app/config/database.php';
    $db = Database::connect();
    echo "✅ MySQL connection successful<br>";
} catch (Exception $e) {
    echo "❌ MySQL error: " . $e->getMessage() . "<br>";
}
?>