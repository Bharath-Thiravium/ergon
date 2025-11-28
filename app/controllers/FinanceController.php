<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function sync() {
        ob_clean();
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $pgHost = '72.60.218.167';
            $pgPort = '5432';
            $pgDb = 'modernsap';
            $pgUser = 'postgres';
            $pgPass = 'mango';
            
            $pgConn = @pg_connect("host=$pgHost port=$pgPort dbname=$pgDb user=$pgUser password=$pgPass");
            
            if (!$pgConn) {
                echo json_encode(['success' => false, 'error' => 'PostgreSQL connection failed']);
                exit;
            }
            
            $syncCount = 0;
            $financeTables = ['finance_invoices', 'finance_quotations', 'finance_customers', 'finance_customer', 'finance_payments', 'finance_purchase_orders'];
            
            foreach ($financeTables as $tableName) {
                $result = @pg_query($pgConn, "SELECT * FROM $tableName");
                if ($result && pg_num_rows($result) > 0) {
                    $data = pg_fetch_all($result);
                    $this->storeTableData($db, $tableName, $data);
                    $syncCount++;
                }
            }
            
            @pg_close($pgConn);
            echo json_encode(['success' => true, 'tables' => $syncCount]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'PostgreSQL connection failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function getDashboardStats() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
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
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;
            }
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $quotationCount = 0;
            foreach ($quotationResults as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                $quotationCount++;
            }
            
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
            
            $prefix = $this->getCompanyPrefix();
            
            // Build customer lookup map
            $customerMap = [];
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';
                if ($customerId) {
                    $customerMap[$customerId] = $data['display_name'] ?? $data['name'] ?? 'Unknown';
                }
            }
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $invoices = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? 'N/A';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                if ($outstanding > 0) {
                    $customerId = $data['customer_id'] ?? '';
                    $customerName = $customerMap[$customerId] ?? 'Unknown';
                    
                    $dueDate = $data['due_date'] ?? date('Y-m-d');
                    $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                    
                    $invoices[] = [
                        'invoice_number' => $invoiceNumber,
                        'customer_name' => $customerName,
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
    
    public function getQuotations() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $quotations = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? 'N/A';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                
                $quotations[] = [
                    'quotation_number' => $quotationNumber,
                    'customer_name' => $data['name'] ?? $data['display_name'] ?? $data['customer_name'] ?? 'Unknown',
                    'amount' => floatval($data['amount'] ?? $data['total_amount'] ?? 0),
                    'status' => $data['status'] ?? 'pending',
                    'created_date' => $data['created_date'] ?? $data['date'] ?? date('Y-m-d')
                ];
            }
            
            echo json_encode(['quotations' => $quotations]);
            
        } catch (Exception $e) {
            echo json_encode(['quotations' => [], 'error' => 'Failed to load quotations']);
        }
    }
    
    public function getOutstandingByCustomer() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            $customerFilter = $_GET['customer'] ?? '';
            
            // Build customer lookup map
            $customerMap = [];
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';
                if ($customerId) {
                    $customerMap[$customerId] = $data['display_name'] ?? $data['name'] ?? 'Unknown';
                }
            }
            
            // Aggregate outstanding amounts
            $outstandingByCustomer = [];
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                if ($outstanding > 0) {
                    $customerId = $data['customer_id'] ?? '';
                    $customerName = $customerMap[$customerId] ?? 'Unknown Customer';
                    
                    if ($customerFilter && $customerName !== $customerFilter) {
                        continue;
                    }
                    
                    $outstandingByCustomer[$customerName] = ($outstandingByCustomer[$customerName] ?? 0) + $outstanding;
                }
            }
            
            echo json_encode(['success' => true, 'data' => $outstandingByCustomer]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to load outstanding data', 'details' => $e->getMessage()]);
        }
    }
    
    public function getCustomers() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            $customers = [];
            $customerMap = [];

            // Step 1 & 2: Read from both finance_customer and finance_customers
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';

                if ($customerId) {
                    // Prefer display_name, then name, then fallback
                    $displayName = $data['display_name'] ?? $data['name'] ?? 'Unknown Customer';
                    $gstin = $data['gstin'] ?? '';
                    
                    // Step 3: Build a readable label
                    $label = $displayName;
                    if ($gstin) {
                        $label .= " (gstin: $gstin)";
                    }

                    // Use existing entry to enrich data, but don't overwrite with less info
                    $existing = $customerMap[$customerId] ?? [];
                    
                    $customerMap[$customerId] = [
                        'id' => $customerId,
                        'customer_code' => $data['customer_code'] ?? $existing['customer_code'] ?? '',
                        'name' => $data['name'] ?? $existing['name'] ?? 'Unknown',
                        'display_name' => $displayName,
                        'label' => $label,
                        'email' => $data['email'] ?? $existing['email'] ?? '',
                        'phone' => $data['phone'] ?? $existing['phone'] ?? '',
                        'gstin' => $gstin ?: ($existing['gstin'] ?? '')
                    ];
                }
            }
            
            // Step 4: Aggregate customers from quotations to find linked/missing customers
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quotationResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['customer_id'] ?? '';
                
                // If the customer from a quotation is not already in our map, create a fallback entry
                if ($customerId && !isset($customerMap[$customerId])) {
                    $customerName = $data['customer_name'] ?? 'Customer ' . $customerId;
                    $customerGstin = $data['customer_gstin'] ?? '';
                    $label = $customerName . ($customerGstin ? " (gstin: $customerGstin)" : '');

                    $customerMap[$customerId] = [
                        'id' => $customerId,
                        'customer_code' => '',
                        'name' => $customerName,
                        'display_name' => $customerName,
                        'label' => $label,
                        'email' => '',
                        'phone' => '',
                        'gstin' => $customerGstin
                    ];
                }
            }
            
            $customers = array_values($customerMap);
            echo json_encode(['customers' => $customers]);
            
        } catch (Exception $e) {
            echo json_encode(['customers' => [], 'error' => 'Failed to load customers']);
        }
    }
    
    public function getQuotationChart() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $chartData = ['labels' => [], 'data' => []];
            $monthlyData = [];
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                
                $date = $data['created_date'] ?? $data['date'] ?? date('Y-m-d');
                $month = date('M Y', strtotime($date));
                $amount = floatval($data['amount'] ?? $data['total_amount'] ?? 0);
                
                $monthlyData[$month] = ($monthlyData[$month] ?? 0) + $amount;
            }
            
            $chartData['labels'] = array_keys($monthlyData);
            $chartData['data'] = array_values($monthlyData);
            
            echo json_encode($chartData);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => [], 'data' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function getRecentActivities() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            $activities = [];
            
            // Get recent invoices
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' ORDER BY id DESC LIMIT 5");
            $stmt->execute();
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($invoices as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $activities[] = [
                    'type' => 'invoice',
                    'description' => "Invoice {$invoiceNumber} created",
                    'amount' => floatval($data['total_amount'] ?? 0),
                    'date' => $data['invoice_date'] ?? date('Y-m-d'),
                    'status' => $data['payment_status'] ?? 'pending'
                ];
            }
            
            // Get recent quotations
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' ORDER BY id DESC LIMIT 3");
            $stmt->execute();
            $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quotations as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                
                $activities[] = [
                    'type' => 'quotation',
                    'description' => "Quotation {$quotationNumber} created",
                    'amount' => floatval($data['amount'] ?? $data['total_amount'] ?? 0),
                    'date' => $data['created_date'] ?? $data['date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? 'pending'
                ];
            }
            
            // Sort by date
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            echo json_encode(['activities' => array_slice($activities, 0, 8)]);
            
        } catch (Exception $e) {
            echo json_encode(['activities' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function visualization() {
        $type = $_GET['type'] ?? '';
        
        if ($type === 'quotations') {
            $this->getQuotationChart();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['labels' => [], 'data' => [], 'error' => 'Invalid visualization type']);
        }
    }
    
    public function recentQuotations() {
        $this->getRecentActivities();
    }
    
    public function getAgingBuckets() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $buckets = [
                '0-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0
            ];
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                if ($outstanding > 0) {
                    $dueDate = $data['due_date'] ?? date('Y-m-d');
                    $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                    
                    if ($daysOverdue <= 30) {
                        $buckets['0-30'] += $outstanding;
                    } elseif ($daysOverdue <= 60) {
                        $buckets['31-60'] += $outstanding;
                    } elseif ($daysOverdue <= 90) {
                        $buckets['61-90'] += $outstanding;
                    } else {
                        $buckets['90+'] += $outstanding;
                    }
                }
            }
            
            echo json_encode([
                'labels' => array_keys($buckets),
                'data' => array_values($buckets)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => [], 'data' => [], 'error' => $e->getMessage()]);
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