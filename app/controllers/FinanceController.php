<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class FinanceController extends Controller {
    
    public function dashboard() {
        $this->view('finance/dashboard');
    }
    
    public function analyzeAllTables() {
        if (ob_get_level() > 0) { ob_clean(); }
        // If PHP does not have pg_connect (pgsql extension missing), output a CSV-friendly error
        if (!function_exists('pg_connect')) {
            header('Content-Type: text/csv');
            echo "Error,PostgreSQL extension (pgsql) not enabled in PHP\n";
            exit;
        }
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
            if (!function_exists('pg_connect')) {
                echo json_encode(['error' => 'PostgreSQL extension (pgsql) not enabled in PHP']);
                return;
            }
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
        if (ob_get_level() > 0) { ob_clean(); }
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        
        header('Content-Type: application/json');
        
        try {
            if (!function_exists('pg_connect')) {
                // Create dummy data for demo purposes when PostgreSQL is not available
                $this->createDemoData();
                echo json_encode(['success' => true, 'tables' => 5, 'message' => 'Demo data created (PostgreSQL not available)']);
                exit;
            }
            $conn = @pg_connect("host=72.60.218.167 port=5432 dbname=modernsap user=postgres password=mango sslmode=disable connect_timeout=60");
            
            if (!$conn) {
                echo json_encode(['error' => 'PostgreSQL connection failed']);
                exit;
            }
            
            $db = Database::connect();
            $this->createTables($db);
            
            // Get all tables from PostgreSQL
            $tablesResult = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
            $allTables = [];
            while ($row = pg_fetch_assoc($tablesResult)) {
                $allTables[] = $row['table_name'];
            }
            
            $syncCount = 0;
            $batchSize = 50;
            
            foreach ($allTables as $tableName) {
                try {
                    // Get row count first
                    $countResult = pg_query($conn, "SELECT COUNT(*) FROM \"$tableName\"");
                    if (!$countResult) continue;
                    
                    $rowCount = pg_fetch_row($countResult)[0];
                    
                    if ($rowCount > 0) {
                        $limit = min($batchSize, $rowCount);
                        $dataResult = pg_query($conn, "SELECT * FROM \"$tableName\" LIMIT $limit");
                        $data = [];
                        
                        if ($dataResult) {
                            while ($dataRow = pg_fetch_assoc($dataResult)) {
                                $data[] = $dataRow;
                            }
                        }
                        
                        if (!empty($data)) {
                            $this->storeTableData($db, $tableName, $data);
                            $syncCount++;
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            pg_close($conn);
            
        } catch (Exception $e) {
            if (ob_get_level() > 0) { ob_clean(); }
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        
        if (ob_get_level() > 0) { ob_clean(); }
        echo json_encode(['success' => true, 'tables' => $syncCount]);
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
        if (ob_get_level() > 0) { ob_clean(); }
        
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
            // Load customer name mapping to resolve customer_id -> display name
            $customerNames = $this->getCustomerNamesMapping($db);
            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                
                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                
                if ($outstanding > 0) {
                    $dueDate = $data['due_date'] ?? date('Y-m-d');
                    $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));

                    $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                    $customerName = 'Unknown';
                    if ($customerId && isset($customerNames[$customerId])) {
                        $customerName = $customerNames[$customerId];
                    } elseif (!empty($data['customer_name'])) {
                        $customerName = $data['customer_name'];
                    } elseif (!empty($data['customer_gstin'])) {
                        $customerName = 'GST: ' . $data['customer_gstin'];
                    }

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
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOutstandingByCustomer() {
        header('Content-Type: application/json');

        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();

            // optional limit parameter for top N customers
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            if ($limit <= 0) $limit = 10;

            // Load all invoices
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // customerId => total outstanding
            $map = [];
            $customerNames = $this->getCustomerNamesMapping($db);

            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';

                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;

                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                if ($outstanding <= 0) continue;

                $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                $customerName = null;
                if ($customerId && isset($customerNames[$customerId])) {
                    $customerName = $customerNames[$customerId];
                } elseif (!empty($data['customer_name'])) {
                    $customerName = $data['customer_name'];
                } elseif (!empty($data['customer_gstin'])) {
                    $customerName = 'GST: ' . $data['customer_gstin'];
                } else {
                    $customerName = 'Customer ' . ($customerId ?: 'Unknown');
                }

                if (!isset($map[$customerName])) $map[$customerName] = 0;
                $map[$customerName] += $outstanding;
            }

            // sort descending
            arsort($map);

            // top N and aggregate others
            $labels = [];
            $data = [];
            $others = 0;
            $i = 0;
            foreach ($map as $name => $amt) {
                if ($i < $limit) {
                    $labels[] = $name;
                    $data[] = $amt;
                } else {
                    $others += $amt;
                }
                $i++;
            }
            if ($others > 0) {
                $labels[] = 'Others';
                $data[] = $others;
            }

            echo json_encode(['labels' => $labels, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getAgingBuckets() {
        header('Content-Type: application/json');

        try {
            $db = Database::connect();
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

                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;

                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                if ($outstanding <= 0) continue;

                $dueDate = $data['due_date'] ?? date('Y-m-d');
                $days = max(0, floor((time() - strtotime($dueDate)) / (24 * 3600)));

                if ($days <= 30) {
                    $buckets['0-30'] += $outstanding;
                } elseif ($days <= 60) {
                    $buckets['31-60'] += $outstanding;
                } elseif ($days <= 90) {
                    $buckets['61-90'] += $outstanding;
                } else {
                    $buckets['90+'] += $outstanding;
                }
            }

            echo json_encode(['labels' => array_keys($buckets), 'data' => array_values($buckets)]);
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
    
    public function analyzeFinanceFields() {
        header('Content-Type: text/plain');
        
        try {
            $db = Database::connect();
            echo "=== FINANCE DATABASE FIELD ANALYSIS ===\n\n";
            
            $tables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_customers'];
            
            foreach ($tables as $tableName) {
                echo "TABLE: $tableName\n";
                echo str_repeat("-", 50) . "\n";
                
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ? LIMIT 3");
                $stmt->execute([$tableName]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($results)) {
                    echo "No data found\n\n";
                    continue;
                }
                
                $allFields = [];
                foreach ($results as $i => $row) {
                    $data = json_decode($row['data'], true);
                    if ($data) {
                        echo "Sample " . ($i + 1) . ":\n";
                        foreach ($data as $key => $value) {
                            $allFields[] = $key;
                            if (stripos($key, 'name') !== false || 
                                stripos($key, 'company') !== false || 
                                stripos($key, 'customer') !== false || 
                                stripos($key, 'address') !== false || 
                                stripos($key, 'location') !== false || 
                                stripos($key, 'delivery') !== false || 
                                stripos($key, 'shipping') !== false || 
                                stripos($key, 'dispatch') !== false) {
                                echo "  $key: " . (is_string($value) ? substr($value, 0, 100) : json_encode($value)) . "\n";
                            }
                        }
                        echo "\n";
                    }
                }
                
                $allFields = array_unique($allFields);
                echo "All fields: " . implode(", ", $allFields) . "\n\n";
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    public function getRecentActivities() {
        header('Content-Type: application/json');
        
        // Debug mode - show raw data structure
        if (isset($_GET['debug'])) {
            try {
                $db = Database::connect();
                $this->createTables($db);
                $stmt = $db->query("SELECT * FROM finance_data LIMIT 3");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['debug' => 'finance_data structure', 'sample_records' => $results], JSON_PRETTY_PRINT);
                return;
            } catch (Exception $e) {
                echo json_encode(['debug_error' => $e->getMessage()]);
                return;
            }
        }
        
        try {
            $db = Database::connect();
            $this->createTables($db);
            $prefix = $this->getCompanyPrefix();
            $customerNames = $this->getCustomerNamesMapping($db);
            $activities = [];
            
            // Get recent quotations
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' ORDER BY id DESC LIMIT 10");
            $stmt->execute();
            $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($quotationResults as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? '';
                
                if (!str_contains(strtoupper($quotationNumber), $prefix)) continue;
                
                $customerId = $data['customer_id'] ?? '';
                $customerName = $this->resolveCustomerName($customerId, $data, $customerNames);
                
                $totalAmount = floatval($data['total_amount'] ?? 0);
                $taxRate = floatval($data['tax_rate'] ?? $data['gst_rate'] ?? 0.18);
                $taxAmount = $totalAmount * $taxRate;
                $taxableAmount = $totalAmount - $taxAmount;
                
                $activities[] = [
                    'type' => 'quotation',
                    'document_number' => $quotationNumber,
                    'customer_name' => $customerName,
                    'customer_gstin' => $data['customer_gstin'] ?? '',
                    'taxable_amount' => $taxableAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'dispatch_location' => $this->resolveDispatchLocation($data),
                    'date' => $data['quotation_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? 'draft',
                    'valid_until' => $data['valid_until'] ?? 'N/A'
                ];
            }
            
            // Get recent purchase orders
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' ORDER BY id DESC LIMIT 10");
            $stmt->execute();
            $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($poResults as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? '';
                
                if (!str_contains(strtoupper($poNumber), $prefix)) continue;
                
                $customerId = $data['customer_id'] ?? '';
                $customerName = $this->resolveCustomerName($customerId, $data, $customerNames);
                
                $totalAmount = floatval($data['total_amount'] ?? 0);
                $taxRate = floatval($data['tax_rate'] ?? $data['gst_rate'] ?? 0.18);
                $taxAmount = $totalAmount * $taxRate;
                $taxableAmount = $totalAmount - $taxAmount;
                
                $activities[] = [
                    'type' => 'purchase_order',
                    'document_number' => $poNumber,
                    'customer_name' => $customerName,
                    'customer_gstin' => $data['customer_gstin'] ?? '',
                    'taxable_amount' => $taxableAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'dispatch_location' => $this->resolveDispatchLocation($data),
                    'date' => $data['po_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => $data['status'] ?? 'pending'
                ];
            }
            
            // Get recent invoices
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' ORDER BY id DESC LIMIT 10");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                
                $customerId = $data['customer_id'] ?? '';
                $customerName = $this->resolveCustomerName($customerId, $data, $customerNames);
                
                $totalAmount = floatval($data['total_amount'] ?? 0);
                $taxRate = floatval($data['tax_rate'] ?? $data['gst_rate'] ?? 0.18);
                $taxAmount = $totalAmount * $taxRate;
                $taxableAmount = $totalAmount - $taxAmount;
                
                $activities[] = [
                    'type' => $data['invoice_type'] === 'proforma' ? 'proforma_invoice' : 'invoice',
                    'document_number' => $invoiceNumber,
                    'customer_name' => $customerName,
                    'customer_gstin' => $data['customer_gstin'] ?? '',
                    'taxable_amount' => $taxableAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'dispatch_location' => $this->resolveDispatchLocation($data),
                    'date' => $data['invoice_date'] ?? $data['created_date'] ?? date('Y-m-d'),
                    'status' => $data['payment_status'] ?? 'unpaid',
                    'due_date' => $data['due_date'] ?? 'N/A',
                    'outstanding_amount' => floatval($data['outstanding_amount'] ?? 0)
                ];
            }
            
            // Sort activities by date (most recent first)
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Limit to 15 most recent activities
            $activities = array_slice($activities, 0, 15);
            
            echo json_encode(['activities' => $activities]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function resolveCustomerName($customerId, $data, $customerNames) {
        // First try to get actual customer name from database
        if ($customerId && isset($customerNames[$customerId])) {
            return $customerNames[$customerId];
        }
        
        // Check all possible name fields in order of preference (same as Revenue Conversion Funnel)
        $nameFields = [
            'company_name', 'customer_company_name', 'client_name', 'client_company_name',
            'display_name', 'customer_display_name', 'name', 'customer_name', 
            'company', 'customer_company', 'organization_name', 'firm_name'
        ];
        
        foreach ($nameFields as $field) {
            if (!empty($data[$field]) && $data[$field] !== ($data['customer_gstin'] ?? '')) {
                return $data[$field];
            }
        }
        
        // Last resort - use GST number
        if (!empty($data['customer_gstin'])) {
            return 'GST: ' . $data['customer_gstin'];
        }
        return 'Customer ' . ($customerId ?: 'Unknown');
    }
    
    private function resolveDispatchLocation($data) {
        // Check all possible address fields in order of preference (same as Revenue Conversion Funnel)
        $addressFields = [
            'delivery_address', 'shipping_address', 'dispatch_address', 'dispatch_location',
            'customer_address', 'client_address', 'billing_address', 'site_address',
            'project_address', 'installation_address', 'service_address', 'address'
        ];
        
        foreach ($addressFields as $field) {
            if (!empty($data[$field])) {
                return $data[$field];
            }
        }
        
        return 'Not specified';
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
                // Load customer mapping
                $customerNames = $this->getCustomerNamesMapping($db);
                foreach ($results as $row) {
                    $data = json_decode($row['data'], true);
                    $invoiceNumber = $data['invoice_number'] ?? '';
                    
                    if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;
                    
                    $outstanding = floatval($data['outstanding_amount'] ?? 0);
                    
                    if ($outstanding > 0) {
                        $dueDate = $data['due_date'] ?? date('Y-m-d');
                        $daysOverdue = max(0, (time() - strtotime($dueDate)) / (24 * 3600));

                        $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                        $customerName = 'Unknown';
                        if ($customerId && isset($customerNames[$customerId])) {
                            $customerName = $customerNames[$customerId];
                        } elseif (!empty($data['customer_name'])) {
                            $customerName = $data['customer_name'];
                        } elseif (!empty($data['customer_gstin'])) {
                            $customerName = 'GST: ' . $data['customer_gstin'];
                        }
                        
                        echo '"' . $invoiceNumber . '","' . 
                             str_replace('"', '""', $customerName) . '","' . 
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

    // Export top-N outstanding by customer as CSV
    public function exportOutstanding() {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($limit <= 0) $limit = 10;

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="outstanding_by_customer_top' . $limit . '_' . date('Y-m-d') . '.csv"');

        try {
            $db = Database::connect();
            $prefix = $this->getCompanyPrefix();

            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            $customerNames = $this->getCustomerNamesMapping($db);

            foreach ($results as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';
                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;

                $outstanding = floatval($data['outstanding_amount'] ?? 0);
                if ($outstanding <= 0) continue;

                $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                $customerName = null;
                if ($customerId && isset($customerNames[$customerId])) {
                    $customerName = $customerNames[$customerId];
                } elseif (!empty($data['customer_name'])) {
                    $customerName = $data['customer_name'];
                } elseif (!empty($data['customer_gstin'])) {
                    $customerName = 'GST: ' . $data['customer_gstin'];
                } else {
                    $customerName = 'Customer ' . ($customerId ?: 'Unknown');
                }

                if (!isset($map[$customerName])) $map[$customerName] = 0;
                $map[$customerName] += $outstanding;
            }

            arsort($map);

            // Output CSV header
            echo "Customer,Outstanding\n";
            $i = 0; $others = 0;
            foreach ($map as $name => $amt) {
                if ($i < $limit) {
                    echo '"' . str_replace('"', '""', $name) . '",' . $amt . "\n";
                } else {
                    $others += $amt;
                }
                $i++;
            }
            if ($others > 0) {
                echo '"Others",' . $others . "\n";
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
    
    public function downloadDatabase() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="complete_database_' . date('Y-m-d_H-i-s') . '.csv"');
        
        try {
            $db = Database::connect();
            
            echo "Complete Database Export - " . date('Y-m-d H:i:s') . "\n\n";
            
            // Get all table names
            $stmt = $db->prepare("SELECT DISTINCT table_name FROM finance_data ORDER BY table_name");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $tableName) {
                echo "\n=== TABLE: {$tableName} ===\n";
                
                // Get sample records
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ? LIMIT 3");
                $stmt->execute([$tableName]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($records)) {
                    // Get all possible columns from all records
                    $allColumns = [];
                    foreach ($records as $record) {
                        $data = json_decode($record['data'], true);
                        if ($data) {
                            $allColumns = array_merge($allColumns, array_keys($data));
                        }
                    }
                    $allColumns = array_unique($allColumns);
                    
                    // Write headers
                    echo implode(',', $allColumns) . "\n";
                    
                    // Write sample data
                    foreach ($records as $record) {
                        $data = json_decode($record['data'], true);
                        $row = [];
                        foreach ($allColumns as $col) {
                            $value = $data[$col] ?? '';
                            $row[] = '"' . str_replace('"', '""', $value) . '"';
                        }
                        echo implode(',', $row) . "\n";
                    }
                } else {
                    echo "No data available\n";
                }
            }
            
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
        
        // Check if finance_data table exists with correct structure
        $stmt = $db->query("SHOW TABLES LIKE 'finance_data'");
        if ($stmt->rowCount() == 0) {
            $db->exec("CREATE TABLE finance_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(100),
                data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(table_name)
            )");
        } else {
            // Check if table_name column exists
            try {
                $db->query("SELECT table_name FROM finance_data LIMIT 1");
            } catch (Exception $e) {
                // Add missing column
                $db->exec("ALTER TABLE finance_data ADD COLUMN table_name VARCHAR(100)");
                $db->exec("ALTER TABLE finance_data ADD INDEX(table_name)");
            }
        }
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
            
            // Check if finance_customer(s) table has data (handle both singular/plural)
            $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name IN ('finance_customers','finance_customer')");
            $stmt->execute();
            $customerCount = $stmt->fetchColumn();

            // Get customer names from finance_customer(s) table if available
            $customerNames = [];
            if ($customerCount > 0) {
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name IN ('finance_customers','finance_customer')");
                $stmt->execute();
                $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($customerResults as $row) {
                    $data = json_decode($row['data'], true);
                    $customerId = isset($data['id']) ? (string)$data['id'] : '';
                    $customerName = $data['display_name'] ?? $data['name'] ?? '';
                    $customerGstin = $data['gstin'] ?? $data['customer_gstin'] ?? '';

                    if ($customerId && $customerName) {
                        $customerNames[$customerId] = [
                            'name' => $customerName,
                            'gstin' => $customerGstin
                        ];
                    }
                }
            }

            // Get customers from quotations as the primary source of linked customers
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations'");
            $stmt->execute();
            $quotationResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($quotationResults as $row) {
                $data = json_decode($row['data'], true);
                $quotationNumber = $data['quotation_number'] ?? '';

                if (!str_contains(strtoupper($quotationNumber), $prefix)) continue;

                $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                $customerGstin = $data['customer_gstin'] ?? '';

                if ($customerId) {
                    if (isset($customerNames[$customerId])) {
                        $customerInfo = $customerNames[$customerId];
                        $gstin = $customerInfo['gstin'] ?: $customerGstin;
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $gstin,
                            'display' => $customerInfo['name'] . ($gstin ? " (GST: {$gstin})" : '')
                        ];
                    } else {
                        // No detailed customer record found — create readable fallback using GST if available
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $customerGstin,
                            'display' => ($customerGstin ? "Customer {$customerId} (GST: {$customerGstin})" : "Customer {$customerId}")
                        ];
                    }
                }
            }

            // Also aggregate customers referenced by Purchase Orders
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders'");
            $stmt->execute();
            $poResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($poResults as $row) {
                $data = json_decode($row['data'], true);
                $poNumber = $data['internal_po_number'] ?? $data['po_number'] ?? '';

                if (!str_contains(strtoupper($poNumber), $prefix)) continue;

                $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                $customerGstin = $data['customer_gstin'] ?? '';

                if ($customerId && !isset($customers[$customerId])) {
                    if (isset($customerNames[$customerId])) {
                        $customerInfo = $customerNames[$customerId];
                        $gstin = $customerInfo['gstin'] ?: $customerGstin;
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $gstin,
                            'display' => $customerInfo['name'] . ($gstin ? " (GST: {$gstin})" : '')
                        ];
                    } else {
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $customerGstin,
                            'display' => ($customerGstin ? "Customer {$customerId} (GST: {$customerGstin})" : "Customer {$customerId}")
                        ];
                    }
                }
            }

            // Also aggregate customers referenced by Invoices
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoiceResults as $row) {
                $data = json_decode($row['data'], true);
                $invoiceNumber = $data['invoice_number'] ?? '';

                if (!str_contains(strtoupper($invoiceNumber), $prefix)) continue;

                $customerId = isset($data['customer_id']) ? (string)$data['customer_id'] : '';
                $customerGstin = $data['customer_gstin'] ?? '';

                if ($customerId && !isset($customers[$customerId])) {
                    if (isset($customerNames[$customerId])) {
                        $customerInfo = $customerNames[$customerId];
                        $gstin = $customerInfo['gstin'] ?: $customerGstin;
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $gstin,
                            'display' => $customerInfo['name'] . ($gstin ? " (GST: {$gstin})" : '')
                        ];
                    } else {
                        $customers[$customerId] = [
                            'id' => $customerId,
                            'gstin' => $customerGstin,
                            'display' => ($customerGstin ? "Customer {$customerId} (GST: {$customerGstin})" : "Customer {$customerId}")
                        ];
                    }
                }
            }
            
            uasort($customers, function($a, $b) {
                return strcmp($a['display'], $b['display']);
            });
            
            echo json_encode([
                'customers' => array_values($customers),
                'debug' => [
                    'customer_table_records' => $customerCount,
                    'prefix' => $prefix
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function getConversionFunnel($db, $customerFilter = '') {
        $prefix = $this->getCompanyPrefix();
        
        // Get customer names mapping first
        $customerNames = $this->getCustomerNamesMapping($db);
        
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
            
            if (str_contains(strtoupper($quotationNumber), $prefix)) {
                if ($customerFilter === '' || $customerId === $customerFilter) {
                    $quotationCount++;
                    $quotationValue += floatval($data['total_amount'] ?? 0);
                }
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
            
            if (str_contains(strtoupper($poNumber), $prefix)) {
                if ($customerFilter === '' || $customerId === $customerFilter) {
                    $poCount++;
                    $poValue += floatval($data['total_amount'] ?? 0);
                }
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
            
            if (str_contains(strtoupper($invoiceNumber), $prefix)) {
                if ($customerFilter === '' || $customerId === $customerFilter) {
                    $invoiceCount++;
                    $total = floatval($data['total_amount'] ?? 0);
                    $outstanding = floatval($data['outstanding_amount'] ?? 0);
                    $invoiceValue += $total;
                    $paymentValue += ($total - $outstanding);
                }
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
    
    private function getCustomerNamesMapping($db) {
        $customerNames = [];
        
        // Get customer names from finance_customers table only
        $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_customers'");
        $stmt->execute();
        $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($customerResults as $row) {
            $data = json_decode($row['data'], true);
            $customerId = $data['id'] ?? '';
            $customerName = $data['name'] ?? '';
            
            if ($customerId && $customerName) {
                $customerNames[$customerId] = $customerName;
            }
        }
        
        return $customerNames;
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
    
    private function createDemoData() {
        try {
            $db = Database::connect();
            $this->createTables($db);
            
            $demoData = [
                'finance_quotations' => [
                    ['quotation_number' => 'BKC-Q-001', 'customer_id' => '1', 'total_amount' => 50000, 'status' => 'draft'],
                    ['quotation_number' => 'BKC-Q-002', 'customer_id' => '2', 'total_amount' => 75000, 'status' => 'revised']
                ],
                'finance_invoices' => [
                    ['invoice_number' => 'BKC-INV-001', 'customer_id' => '1', 'total_amount' => 50000, 'outstanding_amount' => 25000, 'due_date' => date('Y-m-d')],
                    ['invoice_number' => 'BKC-INV-002', 'customer_id' => '2', 'total_amount' => 75000, 'outstanding_amount' => 0, 'due_date' => date('Y-m-d')]
                ],
                'finance_customers' => [
                    ['id' => '1', 'name' => 'Demo Customer 1', 'display_name' => 'Demo Customer 1', 'gstin' => '29ABCDE1234F1Z5'],
                    ['id' => '2', 'name' => 'Demo Customer 2', 'display_name' => 'Demo Customer 2', 'gstin' => '29ABCDE5678F1Z5']
                ]
            ];
            
            foreach ($demoData as $tableName => $records) {
                $this->storeTableData($db, $tableName, $records);
            }
            
        } catch (Exception $e) {
            error_log('Demo data creation failed: ' . $e->getMessage());
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