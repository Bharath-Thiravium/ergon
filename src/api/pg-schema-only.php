<?php
header('Content-Type: application/json');

$pg_host = '72.60.218.167';
$pg_port = 5432;
$pg_dbname = 'modernsap';
$pg_user = 'postgres';
$pg_pass = 'mango';

try {
    $pg_conn = pg_connect("host=$pg_host port=$pg_port dbname=$pg_dbname user=$pg_user password=$pg_pass");
    if (!$pg_conn) {
        throw new Exception('PostgreSQL connection failed');
    }
    
    $result = pg_query($pg_conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }
    
    $schema = [];
    foreach ($tables as $table) {
        $col_result = pg_query($pg_conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' ORDER BY ordinal_position");
        $columns = [];
        while ($col = pg_fetch_assoc($col_result)) {
            $columns[] = $col;
        }
        $schema[$table] = $columns;
    }
    
    pg_close($pg_conn);
    echo json_encode(['success' => true, 'tables' => $schema]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
