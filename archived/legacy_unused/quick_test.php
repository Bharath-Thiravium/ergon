<?php
// Quick PostgreSQL test
$conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");

if ($conn) {
    echo "✅ PostgreSQL Connected<br>";
    
    $result = pg_query($conn, "SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = 'public'");
    $row = pg_fetch_assoc($result);
    echo "Total tables: " . $row['total'] . "<br>";
    
    pg_close($conn);
} else {
    echo "❌ PostgreSQL Connection Failed<br>";
    echo "Error: " . pg_last_error() . "<br>";
}
?>