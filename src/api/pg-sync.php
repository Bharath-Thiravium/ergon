<?php
require_once __DIR__ . '/../../app/config/database.php';

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
    
    $mysql_db = Database::connect();
    $synced_tables = [];
    
    foreach ($tables as $table) {
        $col_result = pg_query($pg_conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' ORDER BY ordinal_position");
        $columns = [];
        while ($col = pg_fetch_assoc($col_result)) {
            $columns[] = $col;
        }
        
        $create_sql = "CREATE TABLE IF NOT EXISTS `pg_$table` (";
        $col_defs = [];
        foreach ($columns as $col) {
            $type = mapPostgresToMySQL($col['data_type']);
            $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
            $col_defs[] = "`{$col['column_name']}` $type $nullable";
        }
        $create_sql .= implode(", ", $col_defs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $mysql_db->exec($create_sql);
        
        $data_result = pg_query($pg_conn, "SELECT * FROM $table LIMIT 10000");
        $rows = [];
        while ($row = pg_fetch_assoc($data_result)) {
            foreach ($row as $k => $v) {
                if ($v === 't') $row[$k] = 1;
                elseif ($v === 'f') $row[$k] = 0;
            }
            $rows[] = $row;
        }
        
        if (!empty($rows)) {
            $col_names = array_keys($rows[0]);
            $escaped_cols = array_map(function($c) { return "`$c`"; }, $col_names);
            $placeholders = implode(',', array_fill(0, count($col_names), '?'));
            $insert_sql = "INSERT INTO `pg_$table` (" . implode(',', $escaped_cols) . ") VALUES ($placeholders)";
            $stmt = $mysql_db->prepare($insert_sql);
            
            foreach ($rows as $row) {
                $stmt->execute(array_values($row));
            }
        }
        
        $synced_tables[$table] = ['rows' => count($rows), 'columns' => count($columns)];
    }
    
    pg_close($pg_conn);
    echo json_encode(['success' => true, 'synced_tables' => $synced_tables]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function mapPostgresToMySQL($pg_type) {
    $map = [
        'character varying' => 'VARCHAR(255)',
        'text' => 'TEXT',
        'integer' => 'INT',
        'bigint' => 'BIGINT',
        'numeric' => 'DECIMAL(18,2)',
        'boolean' => 'BOOLEAN',
        'timestamp without time zone' => 'TIMESTAMP',
        'date' => 'DATE',
        'uuid' => 'VARCHAR(36)',
        'json' => 'JSON'
    ];
    return $map[$pg_type] ?? 'VARCHAR(255)';
}
?>
