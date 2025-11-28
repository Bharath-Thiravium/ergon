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
            $customerFilter = $_GET['customer'] ?? '';
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceCount = $stmt->fetchColumn();
            
            if ($invoiceCount == 0) {
                // Return empty stats if no data, but still provide the funnel structure
                echo json_encode([
                    'totalInvoiceAmount' => 0,
                    'invoiceReceived' => 0,
                    'pendingInvoiceAmount' => 0,
                    'pendingGSTAmount' => 0,
                    'pendingPOValue' => 0,
                    'claimableAmount' => 0,
                    'conversionFunnel' => $this->getConversionFunnel($db, $customerFilter),
                    'cashFlow' => [
                        'expectedInflow' => 0,
                        'poCommitments' => 0
                    ],
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
            $pendingGSTAmount = 0;
            $paidInvoiceCount = 0;
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $total = floatval($data['total_amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $totalTax = floatval($data['total_tax'] ?? 0);
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;

                if ($outstanding > 0) {
                    // Approximate GST on outstanding amount
                    if ($total > 0) {
                        $pendingGSTAmount += ($outstanding / $total) * $totalTax;
                    }
                } else {
                    $paidInvoiceCount++;
                }
            }

            // Calculate PO and Claimable amounts
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $pendingPOValue = 0;
            $claimableAmount = 0;

            foreach ($poResults as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['po_number'] ?? '';
                if ($prefix && !empty($prefix) && strpos($poNumber, $prefix) !== 0) {
                    continue;
                }
                $status = strtolower($data['status'] ?? '');
                if ($status === 'open' || $status === 'partially_billed') {
                    $pendingPOValue += floatval($data['total_amount'] ?? 0);
                }
                if ($status === 'billed' || $status === 'partially_billed') {
                     $claimableAmount += floatval($data['billed_amount'] ?? 0) - floatval($data['paid_amount'] ?? 0);
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
            echo json_encode([
                'totalInvoiceAmount' => 0,
                'invoiceReceived' => 0,
                'pendingInvoiceAmount' => 0,
                'pendingGSTAmount' => 0,
                'pendingPOValue' => 0,
                'claimableAmount' => 0,
                'conversionFunnel' => ['quotations' => 0, 'purchaseOrders' => 0, 'invoices' => 0, 'payments' => 0],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getConversionFunnel($db, $customerFilter = '') {
        $prefix = $this->getCompanyPrefix();
        $funnel = [
            'quotations' => 0, 'quotationValue' => 0,
            'purchaseOrders' => 0, 'poValue' => 0,
            'invoices' => 0, 'invoiceValue' => 0,
            'payments' => 0, 'paymentValue' => 0,
            'quotationToPO' => 0, 'poToInvoice' => 0, 'invoiceToPayment' => 0
        ];

        // Quotations
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if ($prefix && !empty($prefix) && strpos($data['quotation_number'] ?? '', $prefix) !== 0) continue;
            if ($customerFilter && ($data['customer_id'] ?? '') != $customerFilter) continue;
            $funnel['quotations']++;
            $funnel['quotationValue'] += floatval($data['total_amount'] ?? $data['amount'] ?? 0);
        }

        // Purchase Orders
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if ($prefix && !empty($prefix) && strpos($data['po_number'] ?? '', $prefix) !== 0) continue;
            if ($customerFilter && ($data['customer_id'] ?? '') != $customerFilter) continue;
            $funnel['purchaseOrders']++;
            $funnel['poValue'] += floatval($data['total_amount'] ?? 0);
        }

        // Invoices and Payments (can be derived from invoice data)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if ($prefix && !empty($prefix) && strpos($data['invoice_number'] ?? '', $prefix) !== 0) continue;
            if ($customerFilter && ($data['customer_id'] ?? '') != $customerFilter) continue;
            $funnel['invoices']++;
            $total = floatval($data['total_amount'] ?? 0);
            $outstanding = floatval($data['outstanding_amount'] ?? 0);
            $funnel['invoiceValue'] += $total;
            $funnel['paymentValue'] += ($total - $outstanding);
            if ($outstanding <= 0) {
                $funnel['payments']++; // Count fully paid invoices as "payments"
            }
        }

        // Calculate conversion rates
        if ($funnel['quotations'] > 0) $funnel['quotationToPO'] = round(($funnel['purchaseOrders'] / $funnel['quotations']) * 100);
        if ($funnel['purchaseOrders'] > 0) $funnel['poToInvoice'] = round(($funnel['invoices'] / $funnel['purchaseOrders']) * 100);
        if ($funnel['invoices'] > 0) $funnel['invoiceToPayment'] = round(($funnel['payments'] / $funnel['invoices']) * 100);

        return $funnel;
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
            $limit = intval($_GET['limit'] ?? 10);
            
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
            
            // Sort by amount descending and limit
            arsort($outstandingByCustomer);
            $outstandingByCustomer = array_slice($outstandingByCustomer, 0, $limit, true);
            
            echo json_encode([
                'labels' => array_keys($outstandingByCustomer),
                'data' => array_values($outstandingByCustomer),
                'total' => array_sum($outstandingByCustomer),
                'customerCount' => count($outstandingByCustomer)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => [], 'data' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function getAvailablePrefixes() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $prefixes = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                // Extract prefix (letters before numbers/special chars)
                if (preg_match('/^([A-Z]+)/', $invoiceNumber, $matches)) {
                    $prefix = $matches[1];
                    if (!in_array($prefix, $prefixes)) {
                        $prefixes[] = $prefix;
                    }
                }
            }
            
            sort($prefixes);
            echo json_encode(['prefixes' => $prefixes]);
            
        } catch (Exception $e) {
            echo json_encode(['prefixes' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function getCustomers() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            $customerMap = [];

            // Read from both finance_customer and finance_customers
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';

                if ($customerId) {
                    $displayName = $data['display_name'] ?? $data['name'] ?? 'Unknown Customer';
                    $gstin = $data['gstin'] ?? '';
                    
                    $label = $displayName;
                    if ($gstin) {
                        $label .= " (GSTIN: $gstin)";
                    }

                    $customerMap[$customerId] = [
                        'id' => $customerId,
                        'name' => $data['name'] ?? 'Unknown',
                        'display_name' => $displayName,
                        'display' => $label,
                        'gstin' => $gstin
                    ];
                }
            }
            
            // Get customers from invoices if not in customer tables
            $stmt = $db->prepare("SELECT DISTINCT JSON_EXTRACT(data, '$.customer_id') as customer_id, JSON_EXTRACT(data, '$.customer_name') as customer_name FROM finance_data WHERE table_name = 'finance_invoices' AND JSON_EXTRACT(data, '$.customer_id') IS NOT NULL");
            $stmt->execute();
            $invoiceCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($invoiceCustomers as $row) {
                $customerId = trim($row['customer_id'], '"');
                $customerName = trim($row['customer_name'] ?? '', '"');
                
                if ($customerId && !isset($customerMap[$customerId])) {
                    $customerMap[$customerId] = [
                        'id' => $customerId,
                        'name' => $customerName ?: 'Customer ' . $customerId,
                        'display_name' => $customerName ?: 'Customer ' . $customerId,
                        'display' => $customerName ?: 'Customer ' . $customerId,
                        'gstin' => ''
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
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' ORDER BY id DESC LIMIT 3");
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
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' ORDER BY id DESC LIMIT 2");
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
            
            // Get recent purchase orders
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' ORDER BY id DESC LIMIT 2");
            $stmt->execute();
            $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pos as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['po_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($poNumber, $prefix) !== 0) {
                    continue;
                }
                
                $activities[] = [
                    'type' => 'po',
                    'description' => "Purchase Order {$poNumber} created",
                    'amount' => floatval($data['total_amount'] ?? 0),
                    'date' => $data['po_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? 'open'
                ];
            }
            
            // Get recent payments
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_payments' ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($payments as $row) {
                $data = json_decode($row['data'], true);
                $paymentRef = $data['payment_reference'] ?? $data['reference'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($paymentRef, $prefix) !== 0) {
                    continue;
                }
                
                $activities[] = [
                    'type' => 'payment',
                    'description' => "Payment {$paymentRef} received",
                    'amount' => floatval($data['amount'] ?? $data['payment_amount'] ?? 0),
                    'date' => $data['payment_date'] ?? $data['date'] ?? date('Y-m-d'),
                    'status' => 'completed'
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
        
        switch ($type) {
            case 'quotations':
                $this->getQuotationChart();
                break;
            case 'purchase_orders':
                $this->getPurchaseOrderChart();
                break;
            case 'invoices':
                $this->getInvoiceChart();
                break;
            case 'payments':
                $this->getPaymentChart();
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['labels' => [], 'data' => [], 'error' => 'Invalid visualization type']);
        }
    }
    
    public function recentQuotations() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' ORDER BY id DESC LIMIT 10");
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
                    'customer_name' => $data['customer_name'] ?? $data['name'] ?? 'Unknown',
                    'total_amount' => floatval($data['amount'] ?? $data['total_amount'] ?? 0),
                    'valid_until' => $data['valid_until'] ?? $data['expiry_date'] ?? date('Y-m-d', strtotime('+30 days')),
                    'status' => $data['status'] ?? 'active'
                ];
            }
            
            echo json_encode(['quotations' => $quotations]);
            
        } catch (Exception $e) {
            echo json_encode(['quotations' => [], 'error' => $e->getMessage()]);
        }
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
    
    public function getPurchaseOrderChart() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                echo json_encode(['labels' => ['No Data'], 'data' => [0]]);
                return;
            }
            
            $monthlyData = [];
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['po_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($poNumber, $prefix) !== 0) {
                    continue;
                }
                
                $date = $data['created_date'] ?? $data['po_date'] ?? date('Y-m-d');
                $month = date('M Y', strtotime($date));
                $amount = floatval($data['total_amount'] ?? 0);
                
                $monthlyData[$month] = ($monthlyData[$month] ?? 0) + $amount;
            }
            
            if (empty($monthlyData)) {
                echo json_encode(['labels' => ['No Data'], 'data' => [0]]);
                return;
            }
            
            echo json_encode([
                'labels' => array_keys($monthlyData),
                'data' => array_values($monthlyData)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => ['Error'], 'data' => [0], 'error' => $e->getMessage()]);
        }
    }
    
    public function getInvoiceChart() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $paid = 0;
            $unpaid = 0;
            $overdue = 0;
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $total = floatval($data['total_amount'] ?? 0);
                $dueDate = $data['due_date'] ?? date('Y-m-d');
                $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));
                
                if ($outstanding <= 0) {
                    $paid += $total;
                } elseif ($daysOverdue > 0) {
                    $overdue += $outstanding;
                } else {
                    $unpaid += $outstanding;
                }
            }
            
            echo json_encode([
                'labels' => ['Paid', 'Unpaid', 'Overdue'],
                'data' => [$paid, $unpaid, $overdue]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => [], 'data' => [], 'error' => $e->getMessage()]);
        }
    }
    
    public function getPaymentChart() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_payments'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                echo json_encode(['labels' => ['No Data'], 'data' => [0]]);
                return;
            }
            
            $monthlyData = [];
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $paymentRef = $data['payment_reference'] ?? $data['reference'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($paymentRef, $prefix) !== 0) {
                    continue;
                }
                
                $date = $data['payment_date'] ?? $data['date'] ?? date('Y-m-d');
                $month = date('M Y', strtotime($date));
                $amount = floatval($data['amount'] ?? $data['payment_amount'] ?? 0);
                
                $monthlyData[$month] = ($monthlyData[$month] ?? 0) + $amount;
            }
            
            if (empty($monthlyData)) {
                echo json_encode(['labels' => ['No Data'], 'data' => [0]]);
                return;
            }
            
            echo json_encode([
                'labels' => array_keys($monthlyData),
                'data' => array_values($monthlyData)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['labels' => ['Error'], 'data' => [0], 'error' => $e->getMessage()]);
        }
    }
    
    // Route aliases for dashboard endpoints
    public function companyPrefix() {
        $this->updateCompanyPrefix();
    }
    
    public function availablePrefixes() {
        $this->getAvailablePrefixes();
    }
    
    public function customers() {
        $this->getCustomers();
    }
    
    public function dashboardStats() {
        $this->getDashboardStats();
    }
    
    public function outstandingInvoices() {
        $this->getOutstandingInvoices();
    }
    
    public function outstandingByCustomer() {
        $this->getOutstandingByCustomer();
    }
    
    public function agingBuckets() {
        $this->getAgingBuckets();
    }
    
    public function recentActivities() {
        $this->getRecentActivities();
    }
    
    private function getCompanyPrefix() {
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $prefix = $result ? strtoupper(trim($result['company_prefix'])) : '';
            
            return $prefix;
        } catch (Exception $e) {
            error_log("Error fetching company prefix: " . $e->getMessage());
            return '';
        }
    }
}
?>