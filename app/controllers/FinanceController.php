<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function sync() {
        header('Content-Type: application/json');
        
        try {
            if (!function_exists('pg_connect')) {
                echo json_encode(['success' => false, 'error' => 'PostgreSQL extension not available']);
                exit;
            }
            
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=30");
            
            if (!$conn) {
                echo json_encode(['success' => false, 'error' => 'PostgreSQL connection failed']);
                exit;
            }
            
            $db = Database::connect();
            $this->createTables($db);
            
            $financeTables = [
                'finance_quotations' => 'quotations',
                'finance_invoices' => 'invoices', 
                'finance_purchase_orders' => 'purchase_orders',
                'finance_customers' => 'customers',
                'finance_payments' => 'payments'
            ];
            
            $syncCount = 0;
            
            foreach ($financeTables as $localTable => $pgTable) {
                try {
                    $checkResult = pg_query($conn, "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '$pgTable'");
                    if (!$checkResult || pg_fetch_row($checkResult)[0] == 0) {
                        continue;
                    }
                    
                    $dataResult = pg_query($conn, "SELECT * FROM \"$pgTable\" LIMIT 100");
                    $data = [];
                    
                    if ($dataResult) {
                        while ($dataRow = pg_fetch_assoc($dataResult)) {
                            $data[] = $dataRow;
                        }
                    }
                    
                    if (!empty($data)) {
                        $this->storeTableData($db, $localTable, $data);
                        $syncCount++;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            pg_close($conn);
            echo json_encode(['success' => true, 'tables' => $syncCount]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function getDashboardStats() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceCount = $stmt->fetchColumn();
            
            if ($invoiceCount == 0) {
                echo json_encode([
                    'totalInvoiceAmount' => 0,
                    'invoiceReceived' => 0,
                    'pendingInvoiceAmount' => 0,
                    'conversionFunnel' => ['quotations' => 0],
                    'message' => 'No finance data available. Please sync data first.'
                ]);
                return;
            }
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalInvoiceAmount = 0;
            $invoiceReceived = 0;
            $pendingInvoiceAmount = 0;
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $quotationCount = $stmt->fetchColumn();
            
            echo json_encode([
                'totalInvoiceAmount' => $totalInvoiceAmount,
                'invoiceReceived' => $invoiceReceived,
                'pendingInvoiceAmount' => $pendingInvoiceAmount,
                'conversionFunnel' => ['quotations' => $quotationCount]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'totalInvoiceAmount' => 0,
                'invoiceReceived' => 0,
                'pendingInvoiceAmount' => 0,
                'conversionFunnel' => ['quotations' => 0],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getOutstandingInvoices() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                echo json_encode(['invoices' => [], 'message' => 'No invoice data available']);
                return;
            }
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $invoices = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                if ($outstanding > 0) {
                    $dueDate = $data['due_date'] ?? date('Y-m-d');
                    $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                    
                    $invoices[] = [
                        'invoice_number' => $data['invoice_number'] ?? 'N/A',
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
            echo json_encode(['invoices' => [], 'error' => 'Failed to load outstanding invoices']);
        }
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
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['prefix' => $this->getCompanyPrefix()]);
        }
    }
    
    public function importData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'populate_demo') {
            header('Content-Type: application/json');
            try {
                $this->createDemoData();
                echo json_encode(['success' => true, 'message' => 'Demo data populated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            return;
        }
        
        $this->view('finance/import');
    }
    
    private function createTables($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS finance_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) UNIQUE,
            record_count INT,
            last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            company_prefix VARCHAR(10) DEFAULT 'BKC'
        )");
        
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
    
    private function createDemoData() {
        $db = Database::connect();
        $this->createTables($db);
        
        $customers = [];
        for ($i = 1; $i <= 10; $i++) {
            $customers[] = [
                'id' => $i,
                'name' => 'Customer ' . $i,
                'display_name' => 'Customer ' . $i,
                'gstin' => '29ABCDE' . str_pad($i, 4, '0', STR_PAD_LEFT) . 'F1Z5'
            ];
        }
        
        $invoices = [];
        for ($i = 1; $i <= 25; $i++) {
            $customerId = rand(1, 10);
            $totalAmount = rand(25000, 200000);
            $outstanding = rand(0, 1) ? rand(0, $totalAmount) : 0;
            
            $invoices[] = [
                'invoice_number' => 'BKC-INV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customerId,
                'customer_name' => 'Customer ' . $customerId,
                'total_amount' => $totalAmount,
                'outstanding_amount' => $outstanding,
                'due_date' => date('Y-m-d', strtotime('-' . rand(0, 60) . ' days')),
                'payment_status' => $outstanding > 0 ? 'unpaid' : 'paid',
                'gst_rate' => 0.18
            ];
        }
        
        $quotations = [];
        for ($i = 1; $i <= 15; $i++) {
            $customerId = rand(1, 10);
            $quotations[] = [
                'quotation_number' => 'BKC-Q-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customerId,
                'customer_name' => 'Customer ' . $customerId,
                'total_amount' => rand(30000, 250000),
                'status' => ['draft', 'revised', 'converted'][rand(0, 2)],
                'valid_until' => date('Y-m-d', strtotime('+' . rand(15, 45) . ' days'))
            ];
        }
        
        $demoData = [
            'finance_customers' => $customers,
            'finance_invoices' => $invoices,
            'finance_quotations' => $quotations
        ];
        
        foreach ($demoData as $tableName => $records) {
            $this->storeTableData($db, $tableName, $records);
        }
    }
    
    private function getCompanyPrefix() {
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return strtoupper($result['company_prefix']);
            }
            
            $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count, company_prefix) VALUES ('settings', 0, 'BKC')");
            $stmt->execute();
            
            return 'BKC';
        } catch (Exception $e) {
            return 'BKC';
        }
    }
}
?>