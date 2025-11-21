<?php
// Simple PostgreSQL Bridge - Deploy this file on any server with PostgreSQL access
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    // PostgreSQL connection
    $conn = pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
    
    if (!$conn) {
        throw new Exception('PostgreSQL connection failed: ' . pg_last_error());
    }
    
    if ($action === 'tables') {
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name LIMIT 20");
        $tables = [];
        
        while ($row = pg_fetch_assoc($result)) {
            $tables[] = $row['table_name'];
        }
        
        echo json_encode(['success' => true, 'tables' => $tables]);
        
    } elseif ($action === 'data') {
        $table = $input['table'] ?? $_GET['table'] ?? '';
        $limit = $input['limit'] ?? $_GET['limit'] ?? 50;
        
        if (empty($table)) {
            throw new Exception('Table name required');
        }
        
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $result = pg_query($conn, "SELECT * FROM \"$table\" LIMIT $limit");
        $data = [];
        
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
    pg_close($conn);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>