<?php
require_once __DIR__ . '/../../app/config/database.php';

$pg_host = '72.60.218.167';
$pg_port = 5432;
$pg_dbname = 'modernsap';
$pg_user = 'postgres';
$pg_pass = 'mango';
$table_name = $argv[1] ?? null;

if (!$table_name) {
    echo "Usage: php sync-postgres.php TABLE_NAME\n";
    exit(1);
}

try {
    $pg_conn = pg_connect("host=$pg_host port=$pg_port dbname=$pg_dbname user=$pg_user password=$pg_pass");
    if (!$pg_conn) throw new Exception('PostgreSQL connection failed');
    
    $col_result = pg_query($pg_conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table_name' ORDER BY ordinal_position");
    $columns = [];
    while ($col = pg_fetch_assoc($col_result)) {
        $columns[] = $col;
    }
    
    $mysql_db = Database::connect();
    
    $create_sql = "CREATE TABLE IF NOT EXISTS `pg_$table_name` (";
    $col_defs = [];
    foreach ($columns as $col) {
        $type = mapType($col['data_type']);
        $col_defs[] = "`{$col['column_name']}` $type";
    }
    $create_sql .= implode(", ", $col_defs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $mysql_db->exec($create_sql);
    echo "Table created: pg_$table_name\n";
    
    $data_result = pg_query($pg_conn, "SELECT * FROM $table_name LIMIT 1000");
    $count = 0;
    while ($row = pg_fetch_assoc($data_result)) {
        foreach ($row as $k => $v) {
            if ($v === 't') $row[$k] = 1;
            elseif ($v === 'f') $row[$k] = 0;
        }
        $col_names = array_keys($row);
        $escaped_cols = array_map(function($c) { return "`$c`"; }, $col_names);
        $placeholders = implode(',', array_fill(0, count($col_names), '?'));
        $insert_sql = "INSERT INTO `pg_$table_name` (" . implode(',', $escaped_cols) . ") VALUES ($placeholders)";
        $stmt = $mysql_db->prepare($insert_sql);
        $stmt->execute(array_values($row));
        $count++;
    }
    
    pg_close($pg_conn);
    echo "Synced $count rows from $table_name\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

function mapType($pg_type) {
    $map = ['character varying' => 'VARCHAR(255)', 'text' => 'TEXT', 'integer' => 'INT', 'bigint' => 'BIGINT', 'numeric' => 'DECIMAL(18,2)', 'boolean' => 'BOOLEAN', 'timestamp without time zone' => 'TIMESTAMP', 'date' => 'DATE', 'uuid' => 'VARCHAR(36)', 'json' => 'JSON'];
    return $map[$pg_type] ?? 'VARCHAR(255)';
}
?>
