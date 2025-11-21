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
            // Use cURL to call external API that connects to PostgreSQL
            $apiUrl = 'https://api-bridge.example.com/postgres-data'; // You'll need to create this
            
            // Alternative: Manual data import via CSV/JSON upload
            $this->createSampleData();
            
            echo json_encode(['status' => 'success', 'tables' => 3, 'message' => 'Sample data created. Upload your PostgreSQL export files.']);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function uploadData() {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['dataFile'])) {
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }
        
        try {
            $file = $_FILES['dataFile'];
            $tableName = $_POST['tableName'] ?? 'imported_data';
            
            $data = [];
            if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
                $data = $this->parseCSV($file['tmp_name']);
            } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) === 'json') {
                $data = json_decode(file_get_contents($file['tmp_name']), true);
            }
            
            $this->storeData($tableName, $data);
            
            echo json_encode(['status' => 'success', 'records' => count($data)]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
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
    
    private function createSampleData() {
        $db = Database::connect();
        
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
        
        // Sample finance data
        $sampleData = [
            'sales' => [
                ['date' => '2025-01-01', 'amount' => 15000, 'customer' => 'ABC Corp'],
                ['date' => '2025-01-02', 'amount' => 8500, 'customer' => 'XYZ Ltd'],
            ],
            'expenses' => [
                ['date' => '2025-01-01', 'amount' => 2500, 'category' => 'Rent'],
                ['date' => '2025-01-02', 'amount' => 800, 'category' => 'Utilities'],
            ]
        ];
        
        foreach ($sampleData as $tableName => $data) {
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
    
    private function parseCSV($filePath) {
        $data = [];
        $headers = [];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    private function storeData($tableName, $data) {
        $db = Database::connect();
        
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