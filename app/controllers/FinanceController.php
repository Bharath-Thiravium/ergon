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
            $db = Database::connect();
            $this->createTables($db);
            
            // Use cURL to fetch data via HTTP API since PostgreSQL extension not available
            $apiUrl = 'http://72.60.218.167:8080/api/finance';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer postgres_api_key'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                echo json_encode(['success' => false, 'error' => 'PostgreSQL API not available. Extension required.']);
                exit;
            }
            
            $apiData = json_decode($response, true);
            if (!$apiData) {
                echo json_encode(['success' => false, 'error' => 'Invalid API response']);
                exit;
            }
            
            $syncCount = 0;
            $financeTables = ['finance_invoices', 'finance_quotations', 'finance_customers'];
            
            foreach ($financeTables as $tableName) {
                if (isset($apiData[$tableName]) && !empty($apiData[$tableName])) {
                    $this->storeTableData($db, $tableName, $apiData[$tableName]);
                    $syncCount++;
                }
            }
            
            echo json_encode(['success' => true, 'tables' => $syncCount]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'PostgreSQL extension not available on Hostinger Basic plan']);
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
    

    
    private function getCompanyPrefix() {
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? strtoupper($result['company_prefix']) : '';
        } catch (Exception $e) {
            return '';
        }
    }
}
?>