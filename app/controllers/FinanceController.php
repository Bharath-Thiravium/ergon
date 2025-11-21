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
            // Try different connection methods
            $conn = $this->tryAllConnections();
            
            if (!$conn) {
                throw new Exception('PostgreSQL connection failed. Contact Hostinger to whitelist IP 72.60.218.167 or enable outbound connections.');
            }
            
            $db = Database::connect();
            $this->createTables($db);
            
            $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
            $syncCount = 0;
            
            while ($row = pg_fetch_assoc($result)) {
                $tableName = $row['table_name'];
                
                try {
                    $dataResult = pg_query($conn, "SELECT * FROM \"$tableName\" LIMIT 1000");
                    $data = [];
                    
                    if ($dataResult) {
                        while ($dataRow = pg_fetch_assoc($dataResult)) {
                            $data[] = $dataRow;
                        }
                    }
                    
                    // Store table even if empty
                    $this->storeTableData($db, $tableName, $data);
                    $syncCount++;
                    
                } catch (Exception $e) {
                    // Skip tables with errors but continue with others
                    error_log("Error syncing table $tableName: " . $e->getMessage());
                }
            }
            
            pg_close($conn);
            echo json_encode(['status' => 'success', 'tables' => $syncCount, 'method' => 'direct_postgres']);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function tryAllConnections() {
        $configs = [
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=30",
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=require connect_timeout=30",
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=prefer connect_timeout=30",
            "host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango application_name=hostinger_client",
        ];
        
        foreach ($configs as $config) {
            $conn = @pg_connect($config);
            if ($conn && pg_connection_status($conn) === PGSQL_CONNECTION_OK) {
                return $conn;
            }
        }
        
        return false;
    }
    
    public function testConnection() {
        header('Content-Type: application/json');
        
        // Test if port is accessible
        $socket = @fsockopen('72.60.218.167', 5432, $errno, $errstr, 10);
        if (!$socket) {
            echo json_encode(['error' => "Port blocked: $errstr ($errno)"]);
            return;
        }
        fclose($socket);
        
        // Test PostgreSQL connection
        $conn = $this->tryAllConnections();
        if ($conn) {
            $version = pg_version($conn);
            pg_close($conn);
            echo json_encode(['success' => true, 'version' => $version]);
        } else {
            echo json_encode(['error' => 'PostgreSQL connection failed']);
        }
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