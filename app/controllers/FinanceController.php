<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function getTableStructure() {
        header('Content-Type: application/json');
        
        try {
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
            
            if (!$conn) {
                throw new Exception('PostgreSQL connection failed');
            }
            
            // Focus on specific finance tables only
            $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
            $tableList = "'" . implode("','", $targetTables) . "'";
            
            $result = pg_query($conn, "
                SELECT 
                    t.table_name,
                    COUNT(c.column_name) as column_count,
                    COALESCE(s.n_tup_ins, 0) as estimated_rows
                FROM information_schema.tables t
                LEFT JOIN information_schema.columns c ON t.table_name = c.table_name
                LEFT JOIN pg_stat_user_tables s ON t.table_name = s.relname
                WHERE t.table_schema = 'public' 
                AND t.table_name IN ($tableList)
                GROUP BY t.table_name, s.n_tup_ins
                ORDER BY t.table_name
            ");
            
            $tables = [];
            while ($row = pg_fetch_assoc($result)) {
                $tableName = $row['table_name'];
                
                // Get column details
                $colResult = pg_query($conn, "
                    SELECT 
                        column_name,
                        data_type,
                        is_nullable,
                        column_default
                    FROM information_schema.columns 
                    WHERE table_name = '$tableName' 
                    AND table_schema = 'public'
                    ORDER BY ordinal_position
                ");
                
                $columns = [];
                while ($colRow = pg_fetch_assoc($colResult)) {
                    $columns[] = [
                        'name' => $colRow['column_name'],
                        'type' => $colRow['data_type'],
                        'nullable' => $colRow['is_nullable'] === 'YES',
                        'default' => $colRow['column_default']
                    ];
                }
                
                // Get actual row count
                $countResult = pg_query($conn, "SELECT COUNT(*) as actual_count FROM \"$tableName\"");
                $countRow = pg_fetch_assoc($countResult);
                
                $tables[] = [
                    'table_name' => $tableName,
                    'display_name' => str_replace('finance_', '', $tableName),
                    'column_count' => (int)$row['column_count'],
                    'estimated_rows' => (int)$row['estimated_rows'],
                    'actual_rows' => (int)$countRow['actual_count'],
                    'columns' => $columns
                ];
            }
            
            pg_close($conn);
            echo json_encode(['tables' => $tables]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function sync() {
        header('Content-Type: application/json');
        
        try {
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
            
            if (!$conn) {
                echo json_encode(['error' => 'PostgreSQL connection failed']);
                return;
            }
            
            $db = Database::connect();
            $this->createTables($db);
            
            $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
            $syncCount = 0;
            
            foreach ($targetTables as $tableName) {
                try {
                    $checkResult = pg_query($conn, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$tableName'");
                    $exists = pg_fetch_row($checkResult)[0] > 0;
                    
                    if ($exists) {
                        $dataResult = pg_query($conn, "SELECT * FROM \"$tableName\" LIMIT 1000");
                        $data = [];
                        
                        if ($dataResult) {
                            while ($dataRow = pg_fetch_assoc($dataResult)) {
                                $data[] = $dataRow;
                            }
                        }
                        
                        $this->storeTableData($db, $tableName, $data);
                        $syncCount++;
                    }
                } catch (Exception $e) {
                    // Continue with other tables
                }
            }
            
            pg_close($conn);
            echo json_encode(['tables' => $syncCount]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getFinanceStats() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            
            $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
            $tableList = "'" . implode("','", $targetTables) . "'";
            
            $stmt = $db->query("SELECT table_name, record_count FROM finance_tables WHERE table_name IN ($tableList) ORDER BY record_count DESC");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalRecords = array_sum(array_column($tables, 'record_count'));
            $totalTables = count($tables);
            
            echo json_encode([
                'tables' => $tables,
                'totalTables' => $totalTables,
                'totalRecords' => $totalRecords
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getChartData() {
        header('Content-Type: application/json');
        
        $type = $_GET['type'] ?? 'tables';
        
        try {
            $db = Database::connect();
            
            if ($type === 'tables') {
                $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
                $tableList = "'" . implode("','", $targetTables) . "'";
                
                $stmt = $db->query("SELECT table_name, record_count FROM finance_tables WHERE table_name IN ($tableList) AND record_count > 0 ORDER BY record_count DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'labels' => array_column($data, 'table_name'),
                    'data' => array_column($data, 'record_count'),
                    'title' => 'Finance Tables by Record Count'
                ]);
                
            } elseif ($type === 'invoices') {
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 100");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $amounts = [];
                $dates = [];
                
                foreach ($results as $row) {
                    $invoice = json_decode($row['data'], true);
                    if (isset($invoice['total_amount']) && isset($invoice['created_at'])) {
                        $amounts[] = (float)$invoice['total_amount'];
                        $dates[] = date('M Y', strtotime($invoice['created_at']));
                    }
                }
                
                echo json_encode([
                    'labels' => $dates,
                    'data' => $amounts,
                    'title' => 'Invoice Amounts Over Time'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getTables() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
            $tableList = "'" . implode("','", $targetTables) . "'";
            
            $stmt = $db->query("SELECT table_name, record_count, last_sync FROM finance_tables WHERE table_name IN ($tableList) ORDER BY record_count DESC");
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