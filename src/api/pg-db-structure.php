<?php
header('Content-Type: application/json');

$host = $_GET['host'] ?? 'localhost';
$port = $_GET['port'] ?? 5432;
$dbname = $_GET['dbname'] ?? 'sap_source';
$user = $_GET['user'] ?? 'postgres';
$pass = $_GET['pass'] ?? '';

if (!$pass) {
    echo json_encode(['error' => 'PostgreSQL password required: ?host=X&port=5432&dbname=X&user=X&pass=PASSWORD']);
    exit;
}

try {
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$pass");
    if (!$conn) {
        throw new Exception('Connection failed');
    }
    
    // Get all tables
    $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }
    
    $structure = [];
    foreach ($tables as $table) {
        // Get columns
        $col_result = pg_query($conn, "SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' ORDER BY ordinal_position");
        $columns = [];
        while ($col = pg_fetch_assoc($col_result)) {
            $columns[] = $col;
        }
        
        // Get row count
        $count_result = pg_query($conn, "SELECT COUNT(*) FROM $table");
        $count = pg_fetch_row($count_result)[0];
        
        $structure[$table] = ['columns' => $columns, 'row_count' => $count];
    }
    
    pg_close($conn);
    echo json_encode(['success' => true, 'database' => $dbname, 'tables' => $structure], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
