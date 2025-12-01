<?php
header('Content-Type: application/json');

$pg_host = $_GET['host'] ?? 'localhost';
$pg_port = $_GET['port'] ?? 5432;
$pg_dbname = $_GET['dbname'] ?? 'sap_source';
$pg_user = $_GET['user'] ?? 'postgres';
$pg_pass = $_GET['pass'] ?? '';

try {
    $conn_string = "host=$pg_host port=$pg_port dbname=$pg_dbname user=$pg_user password=$pg_pass";
    $conn = @pg_connect($conn_string);
    
    if (!$conn) {
        throw new Exception('PostgreSQL connection failed. Provide credentials: ?host=X&port=5432&dbname=X&user=X&pass=X');
    }
    
    $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }
    
    $schema = [];
    foreach ($tables as $table) {
        $col_result = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' ORDER BY ordinal_position");
        $columns = [];
        while ($col_row = pg_fetch_assoc($col_result)) {
            $columns[] = ['name' => $col_row['column_name'], 'type' => $col_row['data_type']];
        }
        $schema[$table] = $columns;
    }
    
    $shipping_refs = [];
    foreach ($schema as $table => $columns) {
        foreach ($columns as $col) {
            if (stripos($col['name'], 'shipping') !== false || stripos($col['name'], 'address') !== false) {
                if (!isset($shipping_refs[$table])) $shipping_refs[$table] = [];
                $shipping_refs[$table][] = $col['name'];
            }
        }
    }
    
    pg_close($conn);
    
    echo json_encode(['success' => true, 'tables' => $schema, 'shipping_references' => $shipping_refs], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
