<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function analyzeAllTables() {
        ob_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="finance_tables_analysis_' . date('Y-m-d_H-i-s') . '.csv"');
        
        try {
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
            
            if (!$conn) {
                echo "Error,PostgreSQL connection failed\n";
                exit;
            }
            
            $targetTables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments', 'finance_customers'];
            
            // CSV Header
            echo "Table Name,Exists,Row Count,Column Count,Columns,Sample Data\n";
            
            foreach ($targetTables as $tableName) {
                // Check if table exists
                $checkResult = pg_query($conn, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$tableName'");
                $exists = pg_fetch_row($checkResult)[0] > 0;
                
                if ($exists) {
                    // Get row count
                    $countResult = pg_query($conn, "SELECT COUNT(*) FROM \"$tableName\"");
                    $rowCount = pg_fetch_row($countResult)[0];
                    
                    // Get all columns
                    $colResult = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$tableName' ORDER BY ordinal_position");
                    $columns = [];
                    while ($col = pg_fetch_assoc($colResult)) {
                        $columns[] = $col['column_name'] . '(' . $col['data_type'] . ')';
                    }
                    
                    // Get sample data
                    $sampleResult = pg_query($conn, "SELECT * FROM \"$tableName\" LIMIT 2");
                    $sampleData = [];
                    while ($sample = pg_fetch_assoc($sampleResult)) {
                        $sampleData[] = json_encode($sample);
                    }
                    
                    echo '"' . $tableName . '",YES,' . $rowCount . ',' . count($columns) . ',"' . implode('; ', $columns) . '","' . implode(' | ', $sampleData) . "\"\n";
                } else {
                    echo '"' . $tableName . '",NO,0,0,"",""\n';
                }
            }
            
            pg_close($conn);
            
        } catch (Exception $e) {
            echo "Error," . $e->getMessage() . "\n";
        }
        exit;
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
        ob_clean();
        header('Content-Type: application/json');
        
        try {
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=10");
            
            if (!$conn) {
                echo json_encode(['error' => 'PostgreSQL connection failed']);
                exit;
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
            ob_clean();
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
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
    
    public function getVisualizationData() {
        header('Content-Type: application/json');
        ob_clean();
        
        $type = $_GET['type'] ?? 'quotations';
        
        try {
            $db = Database::connect();
            
            switch ($type) {
                case 'quotations':
                    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $statusCount = ['draft' => 0, 'revised' => 0, 'converted' => 0];
                    foreach ($results as $row) {
                        $data = json_decode($row['data'], true);
                        $status = strtolower($data['status'] ?? 'draft');
                        if (isset($statusCount[$status])) {
                            $statusCount[$status]++;
                        }
                    }
                    
                    echo json_encode(['data' => array_values($statusCount)]);
                    break;
                    
                case 'purchase_orders':
                    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $monthlyData = [];
                    foreach ($results as $row) {
                        $data = json_decode($row['data'], true);
                        $month = date('M Y', strtotime($data['po_date'] ?? '2024-01-01'));
                        $amount = floatval($data['total_amount'] ?? 0);
                        
                        if (!isset($monthlyData[$month])) {
                            $monthlyData[$month] = 0;
                        }
                        $monthlyData[$month] += $amount;
                    }
                    
                    echo json_encode([
                        'labels' => array_keys($monthlyData),
                        'data' => array_values($monthlyData)
                    ]);
                    break;
                    
                case 'invoices':
                    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
                    $stmt->execute();
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $paid = 0;
                    $unpaid = 0;
                    $outstanding = 0;
                    
                    foreach ($results as $row) {
                        $data = json_decode($row['data'], true);
                        $status = strtolower($data['payment_status'] ?? 'unpaid');
                        $amount = floatval($data['total_amount'] ?? 0);
                        $outstandingAmount = floatval($data['outstanding_amount'] ?? 0);
                        
                        if ($status === 'paid') {
                            $paid += $amount;
                        } else {
                            $unpaid += $amount;
                            $outstanding += $outstandingAmount;
                        }
                    }
                    
                    echo json_encode([
                        'data' => [$paid, $unpaid, 0],
                        'outstanding' => $outstanding
                    ]);
                    break;
            }
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function exportData() {
        $type = $_GET['type'] ?? 'quotations';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="finance_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ?");
            $stmt->execute(['finance_' . $type]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                echo "No data available for $type\n";
                exit;
            }
            
            // Get headers from first record
            $firstRecord = json_decode($results[0]['data'], true);
            echo implode(',', array_keys($firstRecord)) . "\n";
            
            // Output data
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $values = array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, array_values($data));
                echo implode(',', $values) . "\n";
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        exit;
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