<?php
require_once __DIR__ . '/../core/Controller.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function syncPostgres() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $tables = $this->fetchPostgresData();
            $this->storeInMySQL($tables);
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'tables' => count($tables)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getTables() {
        try {
            $db = Database::connect();
            $stmt = $db->query("SELECT table_name, record_count, last_sync FROM finance_tables ORDER BY last_sync DESC");
            $tables = $stmt->fetchAll();
            
            header('Content-Type: application/json');
            echo json_encode(['tables' => $tables]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getTableData() {
        $table = $_GET['table'] ?? '';
        $limit = (int)($_GET['limit'] ?? 100);
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM finance_data WHERE table_name = ? ORDER BY id DESC LIMIT ?");
            $stmt->execute([$table, $limit]);
            $data = $stmt->fetchAll();
            
            $columns = !empty($data) ? array_keys($data[0]) : [];
            
            header('Content-Type: application/json');
            echo json_encode(['data' => $data, 'columns' => $columns]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function fetchPostgresData() {
        $host = '72.60.218.167';
        $port = 5432;
        $dbname = 'modernsap';
        $user = 'postgres';
        $password = 'mango';
        
        $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
        
        if (!$conn) {
            throw new Exception('PostgreSQL connection failed');
        }
        
        // Get table list
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        $tables = [];
        
        while ($row = pg_fetch_assoc($result)) {
            $tableName = $row['table_name'];
            
            // Get table data
            $dataResult = pg_query($conn, "SELECT * FROM $tableName LIMIT 1000");
            $tableData = [];
            
            while ($dataRow = pg_fetch_assoc($dataResult)) {
                $tableData[] = $dataRow;
            }
            
            $tables[$tableName] = $tableData;
        }
        
        pg_close($conn);
        return $tables;
    }
    
    private function storeInMySQL($tables) {
        $db = Database::connect();
        
        // Create tables
        $db->exec("CREATE TABLE IF NOT EXISTS finance_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) UNIQUE,
            record_count INT,
            last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS finance_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(table_name)
        )");
        
        foreach ($tables as $tableName => $data) {
            // Clear existing data
            $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name = ?");
            $stmt->execute([$tableName]);
            
            // Insert new data
            foreach ($data as $row) {
                $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
                $stmt->execute([$tableName, json_encode($row)]);
            }
            
            // Update table info
            $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE record_count = ?, last_sync = NOW()");
            $stmt->execute([$tableName, count($data), count($data)]);
        }
    }
}