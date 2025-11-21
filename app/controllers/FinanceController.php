<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function syncPostgres() {
        header('Content-Type: application/json');
        
        try {
            // Try multiple connection methods
            $conn = $this->connectPostgres();
            
            if ($conn) {
                $this->syncDirect($conn);
            } else {
                // Use local bridge as fallback
                $this->syncViaLocalBridge();
            }
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function connectPostgres() {
        $configs = [
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable",
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango connect_timeout=10",
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=require",
        ];
        
        foreach ($configs as $config) {
            $conn = @pg_connect($config);
            if ($conn) {
                return $conn;
            }
        }
        
        return false;
    }
    
    private function syncDirect($conn) {
        $db = Database::connect();
        $this->createTables($db);
        
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' LIMIT 10");
        $syncCount = 0;
        
        while ($row = pg_fetch_assoc($result)) {
            $tableName = $row['table_name'];
            
            $dataResult = pg_query($conn, "SELECT * FROM \"$tableName\" LIMIT 100");
            $data = [];
            
            while ($dataRow = pg_fetch_assoc($dataResult)) {
                $data[] = $dataRow;
            }
            
            $this->storeTableData($db, $tableName, $data);
            $syncCount++;
        }
        
        pg_close($conn);
        echo json_encode(['status' => 'success', 'tables' => $syncCount, 'method' => 'direct']);
    }
    
    private function syncViaLocalBridge() {
        // Use the local bridge file
        $bridgeUrl = 'https://athenas.co.in/ergon/simple_bridge.php';
        
        $tables = $this->callBridge($bridgeUrl . '?action=tables');
        
        if (!$tables['success']) {
            throw new Exception('Bridge error: ' . $tables['error']);
        }
        
        $db = Database::connect();
        $this->createTables($db);
        
        $syncCount = 0;
        foreach (array_slice($tables['tables'], 0, 5) as $tableName) {
            $tableData = $this->callBridge($bridgeUrl . '?action=data&table=' . urlencode($tableName) . '&limit=50');
            
            if ($tableData['success']) {
                $this->storeTableData($db, $tableName, $tableData['data']);
                $syncCount++;
            }
        }
        
        echo json_encode(['status' => 'success', 'tables' => $syncCount, 'method' => 'local_bridge']);
    }
    
    public function getTables() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $stmt = $db->query("SELECT table_name, record_count, last_sync FROM finance_tables ORDER BY last_sync DESC");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['tables' => $tables]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getTableData() {
        header('Content-Type: application/json');
        
        $table = $_GET['table'] ?? '';
        $limit = (int)($_GET['limit'] ?? 50);
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ? LIMIT ?");
            $stmt->execute([$table, $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [];
            $columns = [];
            
            foreach ($results as $row) {
                $decoded = json_decode($row['data'], true);
                if ($decoded) {
                    $data[] = $decoded;
                    if (empty($columns)) {
                        $columns = array_keys($decoded);
                    }
                }
            }
            
            echo json_encode(['data' => $data, 'columns' => $columns]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function callBridge($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Bridge connection failed');
        }
        
        return json_decode($response, true);
    }
    
    private function createTables($db) {
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
    }
    
    private function storeTableData($db, $tableName, $data) {
        $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name = ?");
        $stmt->execute([$tableName]);
        
        foreach ($data as $row) {
            $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
            $stmt->execute([$tableName, json_encode($row)]);
        }
        
        $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE record_count = ?, last_sync = NOW()");
        $stmt->execute([$tableName, count($data), count($data)]);
    }
}