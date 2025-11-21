<?php
// PostgreSQL Bridge API - Deploy this on a server with PostgreSQL support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        $conn = pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango");
        
        if (!$conn) {
            throw new Exception('PostgreSQL connection failed');
        }
        
        if ($action === 'tables') {
            $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            $tables = [];
            while ($row = pg_fetch_assoc($result)) {
                $tables[] = $row['table_name'];
            }
            echo json_encode(['tables' => $tables]);
            
        } elseif ($action === 'data') {
            $table = $input['table'] ?? '';
            $limit = $input['limit'] ?? 100;
            
            $result = pg_query($conn, "SELECT * FROM $table LIMIT $limit");
            $data = [];
            while ($row = pg_fetch_assoc($result)) {
                $data[] = $row;
            }
            echo json_encode(['data' => $data]);
        }
        
        pg_close($conn);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>