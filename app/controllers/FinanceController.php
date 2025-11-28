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
            
            // Always ensure quotation data is calculated
            $quotationStats = $this->calculateQuotationOverview($db, $prefix);
            
            // ALWAYS read from dashboard_stats table - never query finance_invoices directly
            $stmt = $db->prepare("SELECT * FROM dashboard_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1");
            $stmt->execute([$prefix]);
            $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dashboardStats) {
                // Return pre-calculated stats from dashboard_stats table
                echo json_encode([
                    'totalInvoiceAmount' => floatval($dashboardStats['total_revenue']),
                    'invoiceReceived' => floatval($dashboardStats['amount_received']),
                    'pendingInvoiceAmount' => floatval($dashboardStats['outstanding_amount']),
                    'pendingGSTAmount' => floatval($dashboardStats['gst_liability']),
                    'pendingPOValue' => floatval($dashboardStats['po_commitments']),
                    'claimableAmount' => floatval($dashboardStats['claimable_amount']),
                    'claimablePOCount' => intval($dashboardStats['claimable_pos']),
                    'claimablePos' => intval($dashboardStats['claimable_pos']),
                    'openPOCount' => intval($dashboardStats['open_pos']),
                    'closedPOCount' => intval($dashboardStats['closed_pos']),
                    'totalPOCount' => intval($dashboardStats['open_pos']) + intval($dashboardStats['closed_pos']),
                    'claimRate' => floatval($dashboardStats['claim_rate']),
                    'conversionFunnel' => $this->getConversionFunnel($db, $customerFilter),
                    'cashFlow' => [
                        'expectedInflow' => floatval($dashboardStats['outstanding_amount']),
                        'poCommitments' => floatval($dashboardStats['po_commitments'])
                    ],
                    // Stat Card 3 metrics from dashboard_stats (backend calculated)
                    'outstandingAmount' => floatval($dashboardStats['outstanding_amount']),
                    'pendingInvoices' => intval($dashboardStats['pending_invoices']),
                    'customersPending' => intval($dashboardStats['customers_pending']),
                    'overdueAmount' => floatval($dashboardStats['overdue_amount']),
                    'outstandingPercentage' => floatval($dashboardStats['outstanding_percentage']),
                    // Stat Card 4: GST Liability metrics from dashboard_stats (backend calculated)
                    'igstLiability' => floatval($dashboardStats['igst_liability']),
                    'cgstSgstTotal' => floatval($dashboardStats['cgst_sgst_total']),
                    'gstLiability' => floatval($dashboardStats['gst_liability']),
                    // Chart Card 1: Quotations Overview (NEW - backend calculated counts only)
                    'placedQuotations' => intval($dashboardStats['placed_quotations'] ?? $quotationStats['placed_quotations'] ?? 0),
                    'rejectedQuotations' => intval($dashboardStats['rejected_quotations'] ?? $quotationStats['rejected_quotations'] ?? 0),
                    'pendingQuotations' => intval($dashboardStats['pending_quotations'] ?? $quotationStats['pending_quotations'] ?? 0),
                    'totalQuotations' => intval($dashboardStats['total_quotations'] ?? $quotationStats['total_quotations'] ?? 0),
                    'source' => 'dashboard_stats',
                    'generated_at' => $dashboardStats['generated_at']
                ]);
                return;
            } else {
                // Calculate stats immediately if none exist
                $this->calculateStatCard3Pipeline($db, null, $prefix);
                
                // Try reading again
                $stmt = $db->prepare("SELECT * FROM dashboard_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1");
                $stmt->execute([$prefix]);
                $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($dashboardStats) {
                    echo json_encode([
                        'totalInvoiceAmount' => floatval($dashboardStats['total_revenue']),
                        'invoiceReceived' => floatval($dashboardStats['amount_received']),
                        'pendingInvoiceAmount' => floatval($dashboardStats['outstanding_amount']),
                        'pendingGSTAmount' => floatval($dashboardStats['gst_liability']),
                        'pendingPOValue' => floatval($dashboardStats['po_commitments']),
                        'claimableAmount' => floatval($dashboardStats['claimable_amount']),
                        'outstandingAmount' => floatval($dashboardStats['outstanding_amount']),
                        'pendingInvoices' => intval($dashboardStats['pending_invoices']),
                        'customersPending' => intval($dashboardStats['customers_pending']),
                        'overdueAmount' => floatval($dashboardStats['overdue_amount']),
                        'outstandingPercentage' => floatval($dashboardStats['outstanding_percentage']),
                        'igstLiability' => floatval($dashboardStats['igst_liability']),
                        'cgstSgstTotal' => floatval($dashboardStats['cgst_sgst_total']),
                        'gstLiability' => floatval($dashboardStats['gst_liability']),
                        'openPOCount' => intval($dashboardStats['open_pos']),
                        'closedPOCount' => intval($dashboardStats['closed_pos']),
                        'placedQuotations' => intval($dashboardStats['placed_quotations'] ?? 0),
                        'rejectedQuotations' => intval($dashboardStats['rejected_quotations'] ?? 0),
                        'pendingQuotations' => intval($dashboardStats['pending_quotations'] ?? 0),
                        'totalQuotations' => intval($dashboardStats['total_quotations'] ?? 0),
                        'conversionFunnel' => $this->getConversionFunnel($db, $customerFilter),
                        'cashFlow' => ['expectedInflow' => floatval($dashboardStats['outstanding_amount']), 'poCommitments' => floatval($dashboardStats['po_commitments'])],
                        'source' => 'calculated'
                    ]);
                } else {
                    echo json_encode(['message' => 'No data available', 'source' => 'empty']);
                }
                return;
            }
            
            // Fallback to old calculation if no dashboard_stats found
            $funnelStats = $this->calculateFunnelStats($db, $prefix);
            $chartStats = $this->calculateChartStats($db, $prefix);
            
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
                // Calculate claimable amount for all POs with any amount processed
                $billedAmount = floatval($data['billed_amount'] ?? $data['invoiced_amount'] ?? $data['delivered_amount'] ?? 0);
                $paidAmount = floatval($data['paid_amount'] ?? $data['payment_amount'] ?? $data['received_amount'] ?? 0);
                $receivedAmount = floatval($data['received_qty'] ?? $data['delivered_qty'] ?? 0);
                
                // If no explicit billed amount but has received/delivered quantity, use total amount
                if ($billedAmount == 0 && $receivedAmount > 0) {
                    $billedAmount = $totalAmount;
                }
                
                // If PO is completed/delivered but no billed amount, assume fully billed
                if ($billedAmount == 0 && in_array($status, ['completed', 'delivered', 'closed', 'received'])) {
                    $billedAmount = $totalAmount;
                }
                
                $claimable = $billedAmount - $paidAmount;
                if ($claimable > 0) {
                    $claimableAmount += $claimable;
                    $claimablePOCount++;
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
                'conversionFunnel' => $funnelStats ?: $this->getConversionFunnel($db, $customerFilter),
                'funnelStats' => $funnelStats,
                'chartData' => $chartStats,
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
            
            // Read from dashboard_stats table (backend calculated)
            $stmt = $db->prepare("SELECT placed_quotations, rejected_quotations, pending_quotations, total_quotations FROM dashboard_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1");
            $stmt->execute([$prefix]);
            $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dashboardStats) {
                // Return count-based data from backend calculations
                $statusData = [
                    'Pending' => intval($dashboardStats['pending_quotations']),
                    'Placed' => intval($dashboardStats['placed_quotations']),
                    'Rejected' => intval($dashboardStats['rejected_quotations'])
                ];
                
                echo json_encode([
                    'labels' => array_keys($statusData),
                    'data' => array_values($statusData),
                    'total' => intval($dashboardStats['total_quotations']),
                    'count' => intval($dashboardStats['total_quotations'])
                ]);
            } else {
                // Fallback: calculate directly if no dashboard_stats available
                $quotationStats = $this->calculateQuotationOverview($db, $prefix);
                
                $statusData = [
                    'Pending' => $quotationStats['pending_quotations'],
                    'Placed' => $quotationStats['placed_quotations'],
                    'Rejected' => $quotationStats['rejected_quotations']
                ];
                
                echo json_encode([
                    'labels' => array_keys($statusData),
                    'data' => array_values($statusData),
                    'total' => $quotationStats['total_quotations'],
                    'count' => $quotationStats['total_quotations']
                ]);
            }
            
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
            
            // Recent Invoices
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' ORDER BY id DESC LIMIT 5");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                if ($prefix && strpos($invoiceNumber, $prefix) !== 0) continue;
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $total = floatval($data['total_amount'] ?? 0);
                $activities[] = [
                    'type' => 'invoice',
                    'icon' => '💰',
                    'title' => "Invoice {$invoiceNumber}",
                    'description' => $outstanding > 0 ? "₹{$total} - Outstanding: ₹{$outstanding}" : "₹{$total} - Paid",
                    'date' => $data['invoice_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => $outstanding > 0 ? 'pending' : 'completed'
                ];
            }
            
            // Recent Quotations
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' ORDER BY id DESC LIMIT 3");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
                if ($prefix && strpos($quotationNumber, $prefix) !== 0) continue;
                
                $amount = floatval($data['total_amount'] ?? $data['amount'] ?? 0);
                $activities[] = [
                    'type' => 'quotation',
                    'icon' => '📝',
                    'title' => "Quotation {$quotationNumber}",
                    'description' => "₹{$amount} - " . ucfirst($data['status'] ?? 'draft'),
                    'date' => $data['created_date'] ?? $data['date'] ?? date('Y-m-d'),
                    'status' => strtolower($data['status'] ?? 'draft')
                ];
            }
            
            // Recent Purchase Orders
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' ORDER BY id DESC LIMIT 3");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['po_number'] ?? $data['internal_po_number'] ?? '';
                if ($prefix && !stripos($poNumber, $prefix)) continue;
                
                $total = floatval($data['total_amount'] ?? 0);
                $paid = floatval($data['amount_paid'] ?? 0);
                $activities[] = [
                    'type' => 'purchase_order',
                    'icon' => '🛒',
                    'title' => "PO {$poNumber}",
                    'description' => "₹{$total}" . ($paid > 0 ? " - Paid: ₹{$paid}" : " - Open"),
                    'date' => $data['po_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => ($paid >= $total && !empty($data['received_date'])) ? 'completed' : 'open'
                ];
            }
            
            // Recent Payments
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_payments' ORDER BY id DESC LIMIT 2");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $data = json_decode($row['data'], true);
                $paymentRef = $data['payment_reference'] ?? $data['reference'] ?? 'Payment';
                
                $amount = floatval($data['amount'] ?? $data['payment_amount'] ?? 0);
                $activities[] = [
                    'type' => 'payment',
                    'icon' => '💳',
                    'title' => "Payment Received",
                    'description' => "₹{$amount} - {$paymentRef}",
                    'date' => $data['payment_date'] ?? $data['date'] ?? date('Y-m-d'),
                    'status' => 'completed'
                ];
            }
            
            // Sort by date (newest first)
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
    
    public function createTables($db) {
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
        
        $db->exec("CREATE TABLE IF NOT EXISTS dashboard_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_prefix VARCHAR(10),
            total_revenue DECIMAL(15,2) DEFAULT 0,
            invoice_count INT DEFAULT 0,
            average_invoice DECIMAL(15,2) DEFAULT 0,
            amount_received DECIMAL(15,2) DEFAULT 0,
            collection_rate DECIMAL(5,2) DEFAULT 0,
            paid_invoices INT DEFAULT 0,
            outstanding_amount DECIMAL(15,2) DEFAULT 0,
            outstanding_percentage DECIMAL(5,2) DEFAULT 0,
            overdue_amount DECIMAL(15,2) DEFAULT 0,
            pending_invoices INT DEFAULT 0,
            customers_pending INT DEFAULT 0,
            customer_count INT DEFAULT 0,
            po_commitments DECIMAL(15,2) DEFAULT 0,
            open_pos INT DEFAULT 0,
            closed_pos INT DEFAULT 0,
            claimable_amount DECIMAL(15,2) DEFAULT 0,
            claimable_pos INT DEFAULT 0,
            claim_rate DECIMAL(5,2) DEFAULT 0,
            igst_liability DECIMAL(15,2) DEFAULT 0,
            cgst_sgst_total DECIMAL(15,2) DEFAULT 0,
            gst_liability DECIMAL(15,2) DEFAULT 0,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_prefix (company_prefix)
        )");
        
        // Add new columns for Stat Card 3 if they don't exist
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN pending_invoices INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN customers_pending INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        // Add GST liability columns for Stat Card 4
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN igst_liability DECIMAL(15,2) DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN cgst_sgst_total DECIMAL(15,2) DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN gst_liability DECIMAL(15,2) DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        // Add closed_pos column for Stat Card 5
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN closed_pos INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
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
    
    public function refreshStats() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            $prefix = $this->getCompanyPrefix();
            
            // Calculate all stats including quotations
            $this->calculateStatCard3Pipeline($db, null, $prefix);
            
            echo json_encode(['success' => true, 'message' => 'Stats refreshed for prefix: ' . $prefix]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Implements Chart Card 1 (Quotations Overview) - Revised Logic
     * Fetches raw quotation rows first, then computes metrics in backend/service layer
     * New field mappings:
     * - Win Rate (OLD) → Placed Quotations (NEW): count of placed quotations
     * - Avg Deal Size (OLD) → Rejected Quotations (NEW): count of rejected quotations  
     * - Pipeline Value (OLD) → Pending Quotations (NEW): count of pending quotations
     */
    private function calculateQuotationOverview($db, $prefix) {
        // Step 1: Fetch raw quotation rows using prefix-based filtering (no SQL aggregation)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotations = [];
        foreach ($quotationResults as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? '';
            
            // Apply prefix-based filtering
            if (!$prefix || strpos($quotationNumber, $prefix) === 0) {
                $quotations[] = [
                    'id' => $data['id'] ?? '',
                    'quotation_number' => $quotationNumber,
                    'amount' => floatval($data['amount'] ?? $data['total_amount'] ?? 0),
                    'status' => strtolower($data['status'] ?? 'draft')
                ];
            }
        }
        
        // Step 2: Move all calculations to backend logic (no SQL aggregation functions)
        $placed_count = 0;
        $rejected_count = 0;
        $pending_count = 0;
        $total_quotations = count($quotations);
        
        // Step 3: Calculate counts based on status
        foreach ($quotations as $quotation) {
            $status = $quotation['status'];
            
            if ($status === 'placed') {
                $placed_count++;
            } elseif ($status === 'rejected') {
                $rejected_count++;
            } elseif (in_array($status, ['pending', 'draft', 'revised'])) {
                $pending_count++;
            }
        }
        
        // Step 4: Store calculated values into dashboard_stats
        $this->updateQuotationStats($db, $prefix, [
            'placed_quotations' => $placed_count,
            'rejected_quotations' => $rejected_count, 
            'pending_quotations' => $pending_count,
            'total_quotations' => $total_quotations
        ]);
        
        return [
            'placed_quotations' => $placed_count,
            'rejected_quotations' => $rejected_count,
            'pending_quotations' => $pending_count,
            'total_quotations' => $total_quotations
        ];
    }
    
    private function updateQuotationStats($db, $prefix, $stats) {
        // Add quotation columns to dashboard_stats if they don't exist
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN placed_quotations INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN rejected_quotations INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN pending_quotations INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN total_quotations INT DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists
        }
        
        // Update the dashboard_stats table with new quotation metrics
        $stmt = $db->prepare("
            INSERT INTO dashboard_stats (company_prefix, placed_quotations, rejected_quotations, pending_quotations, total_quotations, generated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                placed_quotations = VALUES(placed_quotations),
                rejected_quotations = VALUES(rejected_quotations),
                pending_quotations = VALUES(pending_quotations),
                total_quotations = VALUES(total_quotations),
                generated_at = NOW()
        ");
        
        $stmt->execute([
            $prefix,
            $stats['placed_quotations'],
            $stats['rejected_quotations'],
            $stats['pending_quotations'],
            $stats['total_quotations']
        ]);
    }

    /**
     * Implements Stat Card 3 & 4 pipeline with backend calculations only
     * Follows the exact specification: fetch raw data, calculate in backend, store results
     */
    private function calculateStatCard3Pipeline($db, $pgConn, $prefix) {
        // Step 1: Fetch raw invoice rows from existing finance_data table
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invoices = [];
        foreach ($invoiceResults as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            if (!$prefix || strpos($invoiceNumber, $prefix) === 0) {
                // Calculate GST components from available data
                $totalAmount = floatval($data['total_amount'] ?? 0);
                $taxableAmount = floatval($data['taxable_amount'] ?? $totalAmount);
                $totalTax = floatval($data['total_tax'] ?? $data['tax_amount'] ?? $data['gst_amount'] ?? 0);
                
                // If no explicit tax, calculate 18% GST on taxable amount
                if ($totalTax == 0 && $taxableAmount > 0) {
                    $totalTax = $taxableAmount * 0.18;
                }
                
                // Extract individual GST components or split total tax
                $igst = floatval($data['igst'] ?? 0);
                $cgst = floatval($data['cgst'] ?? 0);
                $sgst = floatval($data['sgst'] ?? 0);
                
                // If individual components not available, assume CGST+SGST (9% each)
                if ($igst == 0 && $cgst == 0 && $sgst == 0 && $totalTax > 0) {
                    $cgst = $totalTax / 2;
                    $sgst = $totalTax / 2;
                }
                
                $invoices[] = [
                    'invoice_number' => $invoiceNumber,
                    'taxable_amount' => $taxableAmount,
                    'amount_paid' => floatval($data['amount_paid'] ?? 0),
                    'total_amount' => $totalAmount,
                    'due_date' => $data['due_date'] ?? '',
                    'customer_gstin' => $data['customer_gstin'] ?? $data['customer_id'] ?? '',
                    'igst' => $igst,
                    'cgst' => $cgst,
                    'sgst' => $sgst
                ];
            }
        }
        
        // Step 2: Perform all calculations in backend/service layer
        $outstandingAmount = 0;
        $pendingInvoices = 0;
        $customersPending = [];
        $overdueAmount = 0;
        $totalTaxableAmount = 0;
        $claimableAmount = 0;
        $claimablePos = 0;
        $totalInvoiceAmount = 0;
        $today = date('Y-m-d');
        
        // Stat Card 4: GST Liability calculations
        $igstLiability = 0;
        $cgstSgstTotal = 0;
        
        foreach ($invoices as $invoice) {
            $taxableAmount = floatval($invoice['taxable_amount'] ?? 0);
            $totalAmount = floatval($invoice['total_amount'] ?? 0);
            $amountPaid = floatval($invoice['amount_paid'] ?? 0);
            $dueDate = $invoice['due_date'] ?? '';
            $customerGstin = $invoice['customer_gstin'] ?? '';
            $igst = floatval($invoice['igst'] ?? 0);
            $cgst = floatval($invoice['cgst'] ?? 0);
            $sgst = floatval($invoice['sgst'] ?? 0);
            
            // Stat Card 3: Outstanding amount uses only taxable_amount (no GST)
            $pendingAmount = $taxableAmount - $amountPaid;
            $totalTaxableAmount += $taxableAmount;
            
            // Stat Card 6: Claimable amount uses total_amount (includes GST)
            $claimable = $totalAmount - $amountPaid;
            $totalInvoiceAmount += $totalAmount;
            
            if ($pendingAmount > 0) {
                $outstandingAmount += $pendingAmount;
                $pendingInvoices++;
                
                if ($customerGstin) {
                    $customersPending[$customerGstin] = true;
                }
                
                if ($dueDate && $dueDate < $today) {
                    $overdueAmount += $pendingAmount;
                }
                
                // Stat Card 4: GST liability only on outstanding invoices
                $igstLiability += $igst;
                $cgstSgstTotal += ($cgst + $sgst);
            }
            
            if ($claimable > 0) {
                $claimableAmount += $claimable;
                $claimablePos++;
            }
        }
        
        $customersPendingCount = count($customersPending);
        $outstandingPercentage = $totalTaxableAmount > 0 ? ($outstandingAmount / $totalTaxableAmount) * 100 : 0;
        $claimRate = $totalInvoiceAmount > 0 ? ($claimableAmount / $totalInvoiceAmount) * 100 : 0;
        
        // Stat Card 4: Total GST Liability
        $gstLiability = $igstLiability + $cgstSgstTotal;
        
        // Calculate other stats
        $amountReceived = 0;
        $paidInvoices = 0;
        
        foreach ($invoices as $invoice) {
            $paid = floatval($invoice['amount_paid'] ?? 0);
            $amountReceived += $paid;
            if ($paid > 0) $paidInvoices++;
        }
        
        // Stat Card 5: PO Commitments (Backend calculations only)
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $poCommitments = 0;
        $openPos = 0;
        $closedPos = 0;
        
        foreach ($poResults as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['po_number'] ?? $data['internal_po_number'] ?? '';
            if (!$prefix || stripos($poNumber, $prefix) !== false) {
                $totalAmount = floatval($data['total_amount'] ?? 0);
                $amountPaid = floatval($data['amount_paid'] ?? 0);
                $receivedDate = $data['received_date'] ?? null;
                
                $poCommitments += $totalAmount;
                
                // Determine PO status
                if (($amountPaid < $totalAmount) || empty($receivedDate)) {
                    $openPos++;
                } else {
                    $closedPos++;
                }
            }
        }
        
        // Step 6: Calculate Chart Card 1 (Quotations Overview) metrics
        $quotationStats = $this->calculateQuotationOverview($db, $prefix);
        
        // Ensure quotation stats are always included
        if (empty($quotationStats)) {
            $quotationStats = ['placed_quotations' => 0, 'rejected_quotations' => 0, 'pending_quotations' => 0, 'total_quotations' => 0];
        }
        
        // Step 7: Store computed results in dashboard_stats table
        $stats = [
            'company_prefix' => $prefix,
            'total_revenue' => $totalInvoiceAmount,
            'invoice_count' => count($invoices),
            'average_invoice' => count($invoices) > 0 ? $totalInvoiceAmount / count($invoices) : 0,
            'amount_received' => $amountReceived,
            'collection_rate' => $totalInvoiceAmount > 0 ? ($amountReceived / $totalInvoiceAmount) * 100 : 0,
            'paid_invoices' => $paidInvoices,
            'outstanding_amount' => $outstandingAmount,
            'pending_invoices' => $pendingInvoices,
            'customers_pending' => $customersPendingCount,
            'overdue_amount' => $overdueAmount,
            'outstanding_percentage' => $outstandingPercentage,
            'customer_count' => $customersPendingCount,
            'po_commitments' => $poCommitments,
            'open_pos' => $openPos,
            'closed_pos' => $closedPos,
            'claimable_amount' => $claimableAmount,
            'claimable_pos' => $claimablePos,
            'claim_rate' => $claimRate,
            'igst_liability' => $igstLiability,
            'cgst_sgst_total' => $cgstSgstTotal,
            'gst_liability' => $gstLiability,
            // Chart Card 1: Quotations Overview (NEW)
            'placed_quotations' => $quotationStats['placed_quotations'],
            'rejected_quotations' => $quotationStats['rejected_quotations'],
            'pending_quotations' => $quotationStats['pending_quotations'],
            'total_quotations' => $quotationStats['total_quotations']
        ];
        
        $this->saveDashboardStats($db, $stats);
    }
    
    private function calculateStatsForPrefix($db, $pgConn, $prefix) {
        // Fetch raw invoice data using simple SELECT without aggregation
        $invoiceQuery = "SELECT id, invoice_number, taxable_amount, amount_paid, cgst, sgst, total_amount, due_date, customer_gstin FROM finance_invoices WHERE invoice_number LIKE '$prefix%'";
        $invoiceResult = @pg_query($pgConn, $invoiceQuery);
        $invoices = $invoiceResult ? pg_fetch_all($invoiceResult) : [];
        
        // Fetch raw PO data
        $poQuery = "SELECT id, po_number, total_amount, amount_paid FROM finance_purchase_orders WHERE po_number LIKE '$prefix%'";
        $poResult = @pg_query($pgConn, $poQuery);
        $pos = $poResult ? pg_fetch_all($poResult) : [];
        
        // Calculate metrics
        $stats = $this->calculateMetrics($invoices ?: [], $pos ?: []);
        $stats['company_prefix'] = $prefix;
        
        // Save to dashboard_stats
        $this->saveDashboardStats($db, $stats);
    }
    
    private function calculateMetrics($invoices, $pos) {
        $stats = [];
        
        // Stat Card 1: Total Invoice Amount
        $totalRevenue = 0;
        $invoiceCount = count($invoices);
        foreach ($invoices as $inv) {
            $totalRevenue += floatval($inv['total_amount'] ?? 0);
        }
        $stats['total_revenue'] = $totalRevenue;
        $stats['invoice_count'] = $invoiceCount;
        $stats['average_invoice'] = $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;
        
        // Stat Card 2: Amount Received
        $amountReceived = 0;
        $paidInvoices = 0;
        foreach ($invoices as $inv) {
            $paid = floatval($inv['amount_paid'] ?? 0);
            $amountReceived += $paid;
            if ($paid > 0) $paidInvoices++;
        }
        $stats['amount_received'] = $amountReceived;
        $stats['collection_rate'] = $totalRevenue > 0 ? ($amountReceived / $totalRevenue) * 100 : 0;
        $stats['paid_invoices'] = $paidInvoices;
        
        // Stat Card 3: Outstanding Amount (Backend calculations only)
        $outstandingAmount = 0;
        $pendingInvoices = 0;
        $customersPending = [];
        $overdueAmount = 0;
        $totalTaxableAmount = 0;
        $today = date('Y-m-d');
        
        foreach ($invoices as $inv) {
            $taxableAmount = floatval($inv['taxable_amount'] ?? 0);
            $amountPaid = floatval($inv['amount_paid'] ?? 0);
            $dueDate = $inv['due_date'] ?? '';
            $customerGstin = $inv['customer_gstin'] ?? '';
            
            // Calculate pending amount using only taxable_amount (no GST)
            $pendingAmount = $taxableAmount - $amountPaid;
            
            $totalTaxableAmount += $taxableAmount;
            
            if ($pendingAmount > 0) {
                $outstandingAmount += $pendingAmount;
                $pendingInvoices++;
                
                // Track unique customers with pending amounts
                if ($customerGstin) {
                    $customersPending[$customerGstin] = true;
                }
                
                // Calculate overdue amount
                if ($dueDate && $dueDate < $today) {
                    $overdueAmount += $pendingAmount;
                }
            }
        }
        
        $stats['outstanding_amount'] = $outstandingAmount;
        $stats['pending_invoices'] = $pendingInvoices; // Renamed from overdue
        $stats['customers_pending'] = count($customersPending);
        $stats['overdue_amount'] = $overdueAmount; // Raw overdue in money
        $stats['outstanding_percentage'] = $totalTaxableAmount > 0 ? ($outstandingAmount / $totalTaxableAmount) * 100 : 0;
        $stats['customer_count'] = count($customersPending); // For backward compatibility
        
        // Stat Card 5: PO Commitments
        $poCommitments = 0;
        $openPos = 0;
        foreach ($pos as $po) {
            $total = floatval($po['total_amount'] ?? 0);
            $paid = floatval($po['amount_paid'] ?? 0);
            $poCommitments += $total;
            if ($total > $paid) $openPos++;
        }
        $stats['po_commitments'] = $poCommitments;
        $stats['open_pos'] = $openPos;
        $stats['average_po'] = count($pos) > 0 ? $poCommitments / count($pos) : 0;
        
        // Stat Card 6: Claimable Amount
        $claimableAmount = 0;
        $claimablePos = 0;
        foreach ($pos as $po) {
            $total = floatval($po['total_amount'] ?? 0);
            $paid = floatval($po['amount_paid'] ?? 0);
            $claimable = $total - $paid;
            if ($claimable > 0) {
                $claimableAmount += $claimable;
                $claimablePos++;
            }
        }
        $stats['claimable_amount'] = $claimableAmount;
        $stats['claimable_pos'] = $claimablePos;
        $stats['claim_rate'] = $poCommitments > 0 ? ($claimableAmount / $poCommitments) * 100 : 0;
        
        return $stats;
    }
    
    private function saveDashboardStats($db, $stats) {
        // Add quotation columns if they don't exist
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN placed_quotations INT DEFAULT 0");
        } catch (Exception $e) {}
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN rejected_quotations INT DEFAULT 0");
        } catch (Exception $e) {}
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN pending_quotations INT DEFAULT 0");
        } catch (Exception $e) {}
        try {
            $db->exec("ALTER TABLE dashboard_stats ADD COLUMN total_quotations INT DEFAULT 0");
        } catch (Exception $e) {}
        
        $sql = "INSERT INTO dashboard_stats (
                    company_prefix, total_revenue, invoice_count, average_invoice,
                    amount_received, collection_rate, paid_invoices,
                    outstanding_amount, outstanding_percentage, overdue_amount, 
                    pending_invoices, customers_pending, customer_count,
                    po_commitments, open_pos, closed_pos,
                    claimable_amount, claimable_pos, claim_rate,
                    igst_liability, cgst_sgst_total, gst_liability,
                    placed_quotations, rejected_quotations, pending_quotations, total_quotations,
                    generated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    total_revenue = VALUES(total_revenue),
                    invoice_count = VALUES(invoice_count),
                    average_invoice = VALUES(average_invoice),
                    amount_received = VALUES(amount_received),
                    collection_rate = VALUES(collection_rate),
                    paid_invoices = VALUES(paid_invoices),
                    outstanding_amount = VALUES(outstanding_amount),
                    outstanding_percentage = VALUES(outstanding_percentage),
                    overdue_amount = VALUES(overdue_amount),
                    pending_invoices = VALUES(pending_invoices),
                    customers_pending = VALUES(customers_pending),
                    customer_count = VALUES(customer_count),
                    po_commitments = VALUES(po_commitments),
                    open_pos = VALUES(open_pos),
                    closed_pos = VALUES(closed_pos),
                    claimable_amount = VALUES(claimable_amount),
                    claimable_pos = VALUES(claimable_pos),
                    claim_rate = VALUES(claim_rate),
                    igst_liability = VALUES(igst_liability),
                    cgst_sgst_total = VALUES(cgst_sgst_total),
                    gst_liability = VALUES(gst_liability),
                    placed_quotations = VALUES(placed_quotations),
                    rejected_quotations = VALUES(rejected_quotations),
                    pending_quotations = VALUES(pending_quotations),
                    total_quotations = VALUES(total_quotations),
                    generated_at = NOW()";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $stats['company_prefix'],
            $stats['total_revenue'],
            $stats['invoice_count'],
            $stats['average_invoice'],
            $stats['amount_received'],
            $stats['collection_rate'],
            $stats['paid_invoices'],
            $stats['outstanding_amount'],
            $stats['outstanding_percentage'],
            $stats['overdue_amount'],
            $stats['pending_invoices'],
            $stats['customers_pending'],
            $stats['customer_count'],
            $stats['po_commitments'],
            $stats['open_pos'],
            $stats['closed_pos'],
            $stats['claimable_amount'],
            $stats['claimable_pos'],
            $stats['claim_rate'],
            $stats['igst_liability'],
            $stats['cgst_sgst_total'],
            $stats['gst_liability'],
            $stats['placed_quotations'] ?? 0,
            $stats['rejected_quotations'] ?? 0,
            $stats['pending_quotations'] ?? 0,
            $stats['total_quotations'] ?? 0
        ]);
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
    
    private function calculateFunnelStats($db, $prefix) {
        $quotations = $this->fetchQuotationData($db, $prefix);
        $pos = $this->fetchPOData($db, $prefix);
        $invoices = $this->fetchInvoiceData($db, $prefix);
        
        $quotation_count = count($quotations);
        $quotation_value = array_sum(array_column($quotations, 'amount'));
        
        $po_count = count($pos);
        $po_value = array_sum(array_column($pos, 'amount'));
        $po_conversion_rate = $quotation_count > 0 ? ($po_count / $quotation_count) * 100 : 0;
        
        $invoice_count = count($invoices);
        $invoice_value = array_sum(array_column($invoices, 'total'));
        $invoice_conversion_rate = $po_count > 0 ? ($invoice_count / $po_count) * 100 : 0;
        
        $payment_count = count(array_filter($invoices, fn($inv) => $inv['paid'] > 0));
        $payment_value = array_sum(array_column($invoices, 'paid'));
        $payment_conversion_rate = $invoice_count > 0 ? ($payment_count / $invoice_count) * 100 : 0;
        
        return [
            'quotations' => $quotation_count,
            'quotationValue' => $quotation_value,
            'purchaseOrders' => $po_count,
            'poValue' => $po_value,
            'quotationToPO' => round($po_conversion_rate),
            'invoices' => $invoice_count,
            'invoiceValue' => $invoice_value,
            'poToInvoice' => round($invoice_conversion_rate),
            'payments' => $payment_count,
            'paymentValue' => $payment_value,
            'invoiceToPayment' => round($payment_conversion_rate)
        ];
    }
    
    private function calculateChartStats($db, $prefix) {
        return [
            'quotationChart' => ['draft' => 0, 'revised' => 0, 'converted' => 0],
            'poChart' => ['open' => 0, 'fulfilled' => 0],
            'invoiceChart' => ['paid' => 0, 'unpaid' => 0, 'overdue' => 0],
            'agingChart' => ['current' => 0, 'watch' => 0, 'concern' => 0, 'critical' => 0]
        ];
    }
    
    private function fetchQuotationData($db, $prefix) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $quotations = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $quotationNumber = $data['quotation_number'] ?? $data['quote_number'] ?? '';
            if (!$prefix || strpos($quotationNumber, $prefix) === 0) {
                $quotations[] = [
                    'number' => $quotationNumber,
                    'amount' => floatval($data['total_amount'] ?? $data['amount'] ?? 0),
                    'status' => $data['status'] ?? 'Draft'
                ];
            }
        }
        return $quotations;
    }
    
    private function fetchPOData($db, $prefix) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pos = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? $data['purchase_order_number'] ?? $data['number'] ?? '';
            
            // Skip prefix check if no prefix or match any of the PO number fields
            $matchesPrefix = !$prefix || 
                            (!empty($poNumber) && stripos($poNumber, $prefix) !== false) ||
                            (!empty($data['po_number']) && stripos($data['po_number'], $prefix) !== false) ||
                            (!empty($data['internal_po_number']) && stripos($data['internal_po_number'], $prefix) !== false);
            
            if ($matchesPrefix) {
                $amount = floatval($data['total_amount'] ?? $data['amount'] ?? $data['value'] ?? $data['po_amount'] ?? $data['order_amount'] ?? 0);
                if ($amount > 0) {
                    $pos[] = [
                        'number' => $poNumber,
                        'amount' => $amount
                    ];
                }
            }
        }
        return $pos;
    }
    
    private function fetchInvoiceData($db, $prefix) {
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $invoices = [];
        foreach ($results as $row) {
            $data = json_decode($row['data'], true);
            $invoiceNumber = $data['invoice_number'] ?? '';
            if (!$prefix || strpos($invoiceNumber, $prefix) === 0) {
                $total = floatval($data['total_amount'] ?? $data['amount'] ?? 0);
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                $invoices[] = [
                    'number' => $invoiceNumber,
                    'total' => $total,
                    'paid' => $total - $outstanding
                ];
            }
        }
        return $invoices;
    }
    
    public function getCompanyPrefix() {
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