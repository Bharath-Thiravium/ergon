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
                    error_log("Synced $tableName: " . count($data) . " records");
                } else {
                    error_log("No data found for table: $tableName");
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
                $invoiceNumber = $data['invoice_number'] ?? $data['number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $total = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? $data['balance'] ?? $data['due_amount'] ?? 0);
                $totalTax = floatval($data['total_tax'] ?? $data['tax_amount'] ?? $data['gst_amount'] ?? 0);
                
                // If no explicit tax field, calculate 18% GST
                if ($totalTax == 0 && $total > 0) {
                    $totalTax = $total * 0.18; // Assume 18% GST
                }
                
                $totalInvoiceAmount += $total;
                $invoiceReceived += ($total - $outstanding);
                $pendingInvoiceAmount += $outstanding;

                if ($outstanding > 0) {
                    // Calculate GST on outstanding amount
                    if ($total > 0) {
                        $pendingGSTAmount += ($outstanding / $total) * $totalTax;
                    } else {
                        $pendingGSTAmount += $outstanding * 0.18; // Fallback 18% GST
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
            $claimablePOCount = 0;
            $totalPOCount = 0;
            $openPOCount = 0;

            foreach ($poResults as $row) {
                $data = json_decode($row['data'], true);
                // Check multiple possible field names for PO number
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? '';
                if ($prefix && !empty($prefix) && !empty($poNumber) && stripos($poNumber, $prefix) === false) {
                    continue;
                }
                $totalPOCount++;
                
                // Check multiple possible field names for status
                $status = strtolower($data['status'] ?? $data['po_status'] ?? $data['order_status'] ?? 'open');
                // Check multiple possible field names for amount
                $totalAmount = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? $data['order_amount'] ?? 0);
                
                if (in_array($status, ['open', 'partially_billed', 'pending', 'approved', 'active', 'confirmed'])) {
                    $pendingPOValue += $totalAmount;
                    $openPOCount++;
                }
                if (in_array($status, ['billed', 'partially_billed', 'completed', 'delivered', 'invoiced'])) {
                    $billedAmount = floatval($data['billed_amount'] ?? $data['invoiced_amount'] ?? $data['delivered_amount'] ?? $totalAmount);
                    $paidAmount = floatval($data['paid_amount'] ?? $data['payment_amount'] ?? $data['received_amount'] ?? 0);
                    $claimable = $billedAmount - $paidAmount;
                    if ($claimable > 0) {
                        $claimableAmount += $claimable;
                        $claimablePOCount++;
                    }
                }
            }
            
            $claimRate = $totalPOCount > 0 ? round(($claimablePOCount / $totalPOCount) * 100) : 0;
            
            echo json_encode([
                'totalInvoiceAmount' => $totalInvoiceAmount,
                'invoiceReceived' => $invoiceReceived,
                'pendingInvoiceAmount' => $pendingInvoiceAmount,
                'pendingGSTAmount' => $pendingGSTAmount,
                'pendingPOValue' => $pendingPOValue,
                'claimableAmount' => $claimableAmount,
                'claimablePOCount' => $claimablePOCount,
                'openPOCount' => $openPOCount,
                'totalPOCount' => $totalPOCount,
                'claimRate' => $claimRate,
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

        // Quotations - check multiple number fields
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? $data['number'] ?? '';
            if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) continue;
            if ($customerFilter && ($data['customer_id'] ?? '') != $customerFilter) continue;
            $funnel['quotations']++;
            $funnel['quotationValue'] += floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
        }

        // Purchase Orders - check multiple number fields
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? '';
            if ($prefix && !empty($prefix) && !empty($poNumber) && stripos($poNumber, $prefix) === false) continue;
            if ($customerFilter && ($data['customer_id'] ?? $data['supplier_id'] ?? '') != $customerFilter) continue;
            $funnel['purchaseOrders']++;
            $funnel['poValue'] += floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? $data['order_amount'] ?? 0);
        }

        // Invoices and Payments
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? $data['number'] ?? '';
            if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) continue;
            if ($customerFilter && ($data['customer_id'] ?? '') != $customerFilter) continue;
            $funnel['invoices']++;
            $total = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? 0);
            $outstanding = floatval($data['outstanding_amount'] ?? $data['balance'] ?? 0);
            $funnel['invoiceValue'] += $total;
            $funnel['paymentValue'] += ($total - $outstanding);
            if ($outstanding <= 0) {
                $funnel['payments']++;
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
            
            // Build customer lookup map with prefix filtering
            $customerMap = [];
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';
                $customerCode = $data['customer_code'] ?? '';
                
                // Filter by customer_code prefix
                if ($prefix && !empty($prefix) && !empty($customerCode) && stripos($customerCode, $prefix) === false) {
                    continue;
                }
                
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
            
            // Build customer lookup map with prefix filtering
            $customerMap = [];
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';
                $customerCode = $data['customer_code'] ?? '';
                
                // Filter by customer_code prefix
                if ($prefix && !empty($prefix) && !empty($customerCode) && stripos($customerCode, $prefix) === false) {
                    continue;
                }
                
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
            $prefixCustomers = [];

            // Get customers from invoices that match the prefix
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $customerId = $data['customer_id'] ?? '';
                if ($customerId) {
                    $prefixCustomers[$customerId] = true;
                }
            }
            
            // Get customers from quotations that match the prefix
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quotationResults as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                
                $customerId = $data['customer_id'] ?? '';
                if ($customerId) {
                    $prefixCustomers[$customerId] = true;
                }
            }

            // Read customer details from customer tables
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customer', 'finance_customers')");
            $stmt->execute();
            $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($customerResults as $row) {
                $data = json_decode($row['data'], true);
                $customerId = $data['id'] ?? '';
                $customerCode = $data['customer_code'] ?? '';
                
                // Filter by customer_code prefix
                if ($prefix && !empty($prefix) && !empty($customerCode) && stripos($customerCode, $prefix) === false) {
                    continue;
                }

                if ($customerId && (!$prefix || empty($prefix) || isset($prefixCustomers[$customerId]))) {
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
                        'gstin' => $gstin,
                        'customer_code' => $customerCode
                    ];
                }
            }
            
            // Add customers from invoices/quotations if not in customer tables
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($invoiceNumber, $prefix) !== 0) {
                    continue;
                }
                
                $customerId = $data['customer_id'] ?? '';
                $customerName = $data['customer_name'] ?? '';
                
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
            
            $statusData = ['Draft' => 0, 'Revised' => 0, 'Converted' => 0];
            $totalCount = 0;
            $totalAmount = 0;
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? $data['number'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($quotationNumber, $prefix) !== 0) {
                    continue;
                }
                
                $amount = floatval($data['amount'] ?? $data['total_amount'] ?? $data['value'] ?? 0);
                $status = strtolower($data['status'] ?? 'draft');
                
                if ($amount > 0) {
                    $totalCount++;
                    $totalAmount += $amount;
                    
                    if (in_array($status, ['converted', 'accepted', 'won'])) {
                        $statusData['Converted'] += $amount;
                    } elseif (in_array($status, ['revised', 'updated'])) {
                        $statusData['Revised'] += $amount;
                    } else {
                        $statusData['Draft'] += $amount;
                    }
                }
            }
            
            echo json_encode([
                'labels' => array_keys($statusData),
                'data' => array_values($statusData),
                'total' => $totalAmount,
                'count' => $totalCount
            ]);
            
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
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? '';
                
                if ($prefix && !empty($prefix) && !empty($poNumber) && stripos($poNumber, $prefix) === false) {
                    continue;
                }
                
                $activities[] = [
                    'type' => 'po',
                    'description' => "Purchase Order {$poNumber} created",
                    'amount' => floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? 0),
                    'date' => $data['po_date'] ?? $data['created_date'] ?? $data['date'] ?? $data['order_date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? $data['po_status'] ?? 'open'
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
            $totalCount = 0;
            $totalAmount = 0;
            
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                // Check multiple possible field names for PO number
                $poNumber = $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? $data['po_id'] ?? $data['id'] ?? '';
                
                if ($prefix && !empty($prefix) && strpos($poNumber, $prefix) !== 0) {
                    continue;
                }
                
                // Check multiple possible field names for date
                $date = $data['created_date'] ?? $data['po_date'] ?? $data['date'] ?? $data['order_date'] ?? $data['created_at'] ?? date('Y-m-d');
                $month = date('M Y', strtotime($date));
                // Check multiple possible field names for amount
                $amount = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? $data['order_amount'] ?? $data['total'] ?? 0);
                
                if ($amount > 0) {
                    $monthlyData[$month] = ($monthlyData[$month] ?? 0) + $amount;
                    $totalCount++;
                    $totalAmount += $amount;
                }
            }
            
            if (empty($monthlyData)) {
                echo json_encode(['labels' => ['No Data'], 'data' => [0], 'total' => 0, 'count' => 0]);
                return;
            }
            
            echo json_encode([
                'labels' => array_keys($monthlyData),
                'data' => array_values($monthlyData),
                'total' => $totalAmount,
                'count' => $totalCount
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
    
    public function debugPo() {
        $this->debugPurchaseOrders();
    }
    
    public function downloadTables() {
        $this->downloadPgTables();
    }
    
    public function getAllPurchaseOrders() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pos = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $pos[] = [
                    'internal_po_number' => $data['internal_po_number'] ?? 'N/A',
                    'po_number' => $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? 'N/A',
                    'amount' => floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? 0),
                    'status' => $data['status'] ?? $data['po_status'] ?? 'unknown',
                    'raw_data' => $data
                ];
            }
            
            echo json_encode([
                'total_count' => count($pos),
                'purchase_orders' => $pos
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function downloadPgTables() {
        try {
            $pgHost = '72.60.218.167';
            $pgPort = '5432';
            $pgDb = 'modernsap';
            $pgUser = 'postgres';
            $pgPass = 'mango';
            
            $pgConn = @pg_connect("host=$pgHost port=$pgPort dbname=$pgDb user=$pgUser password=$pgPass");
            
            if (!$pgConn) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'PostgreSQL connection failed']);
                exit;
            }
            
            $tables = ['finance_invoices', 'finance_quotations', 'finance_customers', 'finance_customer', 'finance_payments', 'finance_purchase_orders'];
            
            // Create ZIP file
            $zipFile = tempnam(sys_get_temp_dir(), 'finance_tables_') . '.zip';
            $zip = new ZipArchive();
            
            if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Cannot create ZIP file']);
                exit;
            }
            
            foreach ($tables as $tableName) {
                $result = @pg_query($pgConn, "SELECT * FROM $tableName");
                if ($result && pg_num_rows($result) > 0) {
                    $data = pg_fetch_all($result);
                    
                    // Convert to CSV
                    $csvContent = '';
                    if (!empty($data)) {
                        // Header
                        $csvContent .= implode(',', array_map(function($col) {
                            return '"' . str_replace('"', '""', $col) . '"';
                        }, array_keys($data[0]))) . "\n";
                        
                        // Data rows
                        foreach ($data as $row) {
                            $csvContent .= implode(',', array_map(function($val) {
                                return '"' . str_replace('"', '""', $val ?? '') . '"';
                            }, array_values($row))) . "\n";
                        }
                    }
                    
                    $zip->addFromString($tableName . '.csv', $csvContent);
                }
            }
            
            $zip->close();
            @pg_close($pgConn);
            
            // Download ZIP file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="finance_tables_' . date('Y-m-d_H-i-s') . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            
            readfile($zipFile);
            unlink($zipFile);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Download failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    public function recentActivities() {
        $this->getRecentActivities();
    }
    
    public function debugPurchaseOrders() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $prefix = $this->getCompanyPrefix();
            
            // Check if table exists and has data
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            // Get sample data
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' LIMIT 5");
            $stmt->execute();
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sampleData = [];
            $prefixMatches = 0;
            foreach ($samples as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? '';
                
                $matchesPrefix = false;
                if ($prefix && !empty($prefix) && !empty($poNumber)) {
                    $matchesPrefix = stripos($poNumber, $prefix) !== false;
                    if ($matchesPrefix) $prefixMatches++;
                }
                
                $sampleData[] = [
                    'po_number' => $poNumber,
                    'matches_prefix' => $matchesPrefix,
                    'keys' => array_keys($data),
                    'sample_values' => array_slice($data, 0, 8, true)
                ];
            }
            
            echo json_encode([
                'total_records' => $count,
                'current_prefix' => $prefix,
                'prefix_matches' => $prefixMatches,
                'sample_data' => $sampleData,
                'table_exists' => $count > 0
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
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
    
    public function structure() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $tables = [];
            $stmt = $db->prepare("SELECT table_name, record_count, last_sync FROM finance_tables ORDER BY table_name");
            $stmt->execute();
            $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tableInfo as $info) {
                $tables[] = [
                    'name' => $info['table_name'],
                    'records' => $info['record_count'],
                    'last_sync' => $info['last_sync']
                ];
            }
            
            // Get actual counts from finance_data
            $actualCounts = [];
            $stmt = $db->prepare("SELECT table_name, COUNT(*) as actual_count FROM finance_data GROUP BY table_name");
            $stmt->execute();
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($counts as $count) {
                $actualCounts[$count['table_name']] = $count['actual_count'];
            }
            
            echo json_encode([
                'tables' => $tables,
                'actual_counts' => $actualCounts,
                'prefix' => $this->getCompanyPrefix()
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>