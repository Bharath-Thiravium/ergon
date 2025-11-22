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
    
    public function getDashboardStats() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();
            $customerFilter = $_GET['customer'] ?? '';
            
            // Get invoice data
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalInvoiceAmount = 0;
            $invoiceReceived = 0;
            $pendingInvoiceAmount = 0;
            $pendingGSTAmount = 0;
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $gstRate = floatval($data['gst_rate'] ?? 0.18);
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;
                $pendingGSTAmount += ($outstanding * $gstRate);
            }
            
            // Get PO data
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pendingPOValue = 0;
            $claimableAmount = 0;
            
            foreach ($poResults as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? '';
                
                if (!str_contains(strtoupper($poNumber), $prefix)) continue;
                
                $status = strtolower($data['status'] ?? 'pending');
                $amount = floatval($data['total_amount'] ?? 0);
                
                if ($status !== 'invoiced') {
                    $pendingPOValue += $amount;
                    $claimableAmount += floatval($data['claimable_amount'] ?? $amount);
                }
            }
            
            echo json_encode([
                'totalInvoiceAmount' => $totalInvoiceAmount,
                'invoiceReceived' => $invoiceReceived,
                'pendingInvoiceAmount' => $pendingInvoiceAmount,
                'pendingGSTAmount' => $pendingGSTAmount,
                'pendingPOValue' => $pendingPOValue,
                'claimableAmount' => $claimableAmount,
                'conversionFunnel' => $this->getConversionFunnel($db, $customerFilter),
                'cashFlow' => [
                    'expectedInflow' => $pendingInvoiceAmount,
                    'poCommitments' => $pendingPOValue
                ]
            ]);
            
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
                    echo json_encode($this->getQuotationsChart($db));
                    break;
                case 'purchase_orders':
                    echo json_encode($this->getPurchaseOrdersChart($db));
                    break;
                case 'invoices':
                    echo json_encode($this->getInvoicesChart($db));
                    break;
            }
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function getOutstandingInvoices() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $invoices = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                if ($outstanding > 0) {
                    $dueDate = $data['due_date'] ?? date('Y-m-d');
                    $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                    
                    $invoices[] = [
                        'invoice_number' => $invoiceNumber,
                        'customer_name' => $data['customer_name'] ?? 'Unknown',
                        'due_date' => $dueDate,
                        'outstanding_amount' => $outstanding,
                        'daysOverdue' => floor($daysOverdue),
                        'status' => $daysOverdue > 0 ? 'Overdue' : 'Pending'
                    ];
                }
            }
            
            echo json_encode(['invoices' => $invoices]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getRecentQuotations() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $quotations = [];
            $count = 0;
            foreach ($results as $row) {
                if ($count >= 5) break;
                
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? '';
                
                if (!str_contains(strtoupper($quotationNumber), $prefix)) continue;
                
                $quotations[] = [
                    'quotation_number' => $quotationNumber,
                    'customer_name' => $data['customer_name'] ?? 'Unknown',
                    'total_amount' => floatval($data['total_amount'] ?? 0),
                    'valid_until' => $data['valid_until'] ?? 'N/A'
                ];
                $count++;
            }
            
            echo json_encode(['quotations' => $quotations]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function exportTable() {
        $type = $_GET['type'] ?? 'outstanding';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="finance_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        try {
            if ($type === 'outstanding') {
                echo "Invoice Number,Customer Name,Due Date,Outstanding Amount,Days Overdue,Status\n";
                
                $db = Database::connect();
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $prefix = $this->getCompanyPrefix();
                foreach ($results as $row) {
                    $data = json_decode($row['data'], true);
                    $invoiceNumber = $data['invoice_number'] ?? '';
                    
                    if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                    
                    $outstanding = floatval($data['outstanding_amount'] ?? 0);
                    
                    if ($outstanding > 0) {
                        $dueDate = $data['due_date'] ?? date('Y-m-d');
                        $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                        
                        echo '"' . $invoiceNumber . '","' . 
                             ($data['customer_name'] ?? 'Unknown') . '","' . 
                             $dueDate . '","' . 
                             $outstanding . '","' . 
                             floor($daysOverdue) . '","' . 
                             ($daysOverdue > 0 ? 'Overdue' : 'Pending') . "\"\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        exit;
    }
    
    public function exportDashboard() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="finance_dashboard_' . date('Y-m-d') . '.csv"');
        
        try {
            $db = Database::connect();
            
            echo "Finance Dashboard Summary - " . date('Y-m-d H:i:s') . "\n\n";
            echo "KPI,Value\n";
            
            // Get dashboard stats
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalInvoiceAmount = 0;
            $invoiceReceived = 0;
            $pendingInvoiceAmount = 0;
            
            $prefix = $this->getCompanyPrefix();
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;
            }
            
            echo "Total Invoice Amount," . $totalInvoiceAmount . "\n";
            echo "Invoice Amount Received," . $invoiceReceived . "\n";
            echo "Pending Invoice Amount," . $pendingInvoiceAmount . "\n";
            echo "Collection Rate," . ($totalInvoiceAmount > 0 ? round(($invoiceReceived / $totalInvoiceAmount) * 100, 2) : 0) . "%\n";
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        exit;
    }
    
    public function updateCompanyPrefix() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prefix = strtoupper(trim($_POST['company_prefix'] ?? 'BKC'));
            
            try {
                $db = Database::connect();
                $this->createTables($db);
                
                $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count, company_prefix) VALUES ('settings', 0, ?) ON DUPLICATE KEY UPDATE company_prefix = ?");
                $stmt->execute([$prefix, $prefix]);
                
                echo json_encode(['success' => true, 'prefix' => $prefix]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['prefix' => $this->getCompanyPrefix()]);
        }
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
        
        // Add company_prefix column if it doesn't exist
        try {
            $db->exec("ALTER TABLE finance_tables ADD COLUMN company_prefix VARCHAR(10) DEFAULT 'BKC'");
        } catch (Exception $e) {
            // Column already exists
        }
        
        $db->exec("CREATE TABLE IF NOT EXISTS finance_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(table_name)
        )");
        

    }
    
    private function getQuotationsChart($db) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $prefix = $this->getCompanyPrefix();
        $statusCount = ['draft' => 0, 'revised' => 0, 'converted' => 0];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? '';
            
            if (!str_contains(strtoupper($quotationNumber), $prefix)) continue;
            
            $status = strtolower($data['status'] ?? 'draft');
            if (isset($statusCount[$status])) {
                $statusCount[$status]++;
            }
        }
        
        return [
            'data' => array_values($statusCount),
            'draft' => $statusCount['draft'],
            'revised' => $statusCount['revised']
        ];
    }
    
    private function getPurchaseOrdersChart($db) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $prefix = $this->getCompanyPrefix();
        $monthlyData = [];
        $largest = 0;
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? '';
            
            if (!str_contains(strtoupper($poNumber), $prefix)) continue;
            
            $month = date('M Y', strtotime($data['po_date'] ?? '2024-01-01'));
            $amount = floatval($data['total_amount'] ?? 0);
            
            if ($amount > $largest) $largest = $amount;
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month] += $amount;
        }
        
        return [
            'labels' => array_keys($monthlyData),
            'data' => array_values($monthlyData),
            'largest' => $largest
        ];
    }
    
    private function getInvoicesChart($db) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $prefix = $this->getCompanyPrefix();
        $paid = 0;
        $unpaid = 0;
        $overdueCount = 0;
        
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            
            if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
            
            $status = strtolower($data['payment_status'] ?? 'unpaid');
            $amount = floatval($data['total_amount'] ?? 0);
            $dueDate = $data['due_date'] ?? date('Y-m-d');
            $isOverdue = strtotime($dueDate) < time();
            
            if ($status === 'paid') {
                $paid += $amount;
            } else {
                $unpaid += $amount;
                if ($isOverdue) $overdueCount++;
            }
        }
        
        return [
            'data' => [$paid, $unpaid, 0],
            'overdueCount' => $overdueCount
        ];
    }
    
    public function getCustomers() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();
            
            $customers = [];
            
            // Get customers from quotations with GST details
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? '';
                if (str_contains(strtoupper($quotationNumber), $prefix)) {
                    $customerId = $data['customer_id'] ?? '';
                    $customerGstin = $data['customer_gstin'] ?? '';
                    
                    if ($customerId) {
                        $customerKey = $customerId;
                        if (!isset($customers[$customerKey])) {
                            $customers[$customerKey] = [
                                'id' => $customerId,
                                'gstin' => $customerGstin,
                                'display' => "Customer ID: {$customerId}" . ($customerGstin ? " (GST: {$customerGstin})" : '')
                            ];
                        }
                    }
                }
            }
            
            // Sort by customer ID
            ksort($customers);
            echo json_encode(['customers' => array_values($customers)]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function getConversionFunnel($db, $customerFilter = '') {
        $prefix = $this->getCompanyPrefix();
        
        // Count quotations
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotationCount = 0;
        $quotationValue = 0;
        foreach ($quotationResults as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? '';
            $customerId = $data['customer_id'] ?? '';
            if (str_contains(strtoupper($quotationNumber), $prefix) && 
                ($customerFilter === '' || $customerId === $customerFilter)) {
                $quotationCount++;
                $quotationValue += floatval($data['total_amount'] ?? 0);
            }
        }
        
        // Count POs
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $poCount = 0;
        $poValue = 0;
        foreach ($poResults as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? '';
            $customerId = $data['customer_id'] ?? '';
            if (str_contains(strtoupper($poNumber), $prefix) && 
                ($customerFilter === '' || $customerId === $customerFilter)) {
                $poCount++;
                $poValue += floatval($data['total_amount'] ?? 0);
            }
        }
        
        // Count invoices
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invoiceCount = 0;
        $invoiceValue = 0;
        $paymentValue = 0;
        foreach ($invoiceResults as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            $customerId = $data['customer_id'] ?? '';
            if (str_contains(strtoupper($invoiceNumber), $prefix) && 
                ($customerFilter === '' || $customerId === $customerFilter)) {
                $invoiceCount++;
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $invoiceValue += $total;
                $paymentValue += ($total - $outstanding);
            }
        }
        
        return [
            'quotations' => $quotationCount,
            'quotationValue' => $quotationValue,
            'purchaseOrders' => $poCount,
            'poValue' => $poValue,
            'quotationToPO' => $quotationCount > 0 ? round(($poCount / $quotationCount) * 100) : 0,
            'invoices' => $invoiceCount,
            'invoiceValue' => $invoiceValue,
            'poToInvoice' => $poCount > 0 ? round(($invoiceCount / $poCount) * 100) : 0,
            'payments' => $paymentValue > 0 ? 1 : 0,
            'paymentValue' => $paymentValue,
            'invoiceToPayment' => $invoiceValue > 0 ? round(($paymentValue / $invoiceValue) * 100) : 0
        ];
    }
    
    private function getCompanyPrefix() {
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            // Try to get existing prefix
            $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return strtoupper($result['company_prefix']);
            }
            
            // Create default settings record if not exists
            $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count, company_prefix) VALUES ('settings', 0, 'BKC')");
            $stmt->execute();
            
            return 'BKC';
        } catch (Exception $e) {
            return 'BKC';
        }
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