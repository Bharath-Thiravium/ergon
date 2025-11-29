<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';

class NewFinanceController extends Controller {
    
    private $db;
    private $prefix = '';
    
    public function __construct() {
        $this->db = Database::connect();
        $this->createTables();
        $this->prefix = $this->getCompanyPrefix();
    }
    
    // Main dashboard view
    public function dashboard() {
        $this->view('finance/new_dashboard');
    }
    
    // Single unified API endpoint for all data
    public function api() {
        header('Content-Type: application/json');
        
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'stats':
                    echo json_encode($this->getStats());
                    break;
                case 'funnel':
                    echo json_encode($this->getFunnel());
                    break;
                case 'charts':
                    echo json_encode($this->getCharts());
                    break;
                case 'outstanding':
                    echo json_encode($this->getOutstanding());
                    break;
                case 'customers':
                    echo json_encode($this->getCustomers());
                    break;
                case 'activities':
                    echo json_encode($this->getActivities());
                    break;
                case 'sync':
                    echo json_encode($this->syncData());
                    break;
                case 'prefix':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        echo json_encode($this->updatePrefix());
                    } else {
                        echo json_encode(['prefix' => $this->prefix]);
                    }
                    break;
                default:
                    echo json_encode(['error' => 'Invalid action']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // Get all KPI stats
    private function getStats() {
        $stmt = $this->db->prepare("SELECT * FROM finance_stats WHERE company_prefix = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$this->prefix]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats) {
            $this->calculateStats();
            $stmt->execute([$this->prefix]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return [
            'totalRevenue' => floatval($stats['total_revenue'] ?? 0),
            'amountReceived' => floatval($stats['amount_received'] ?? 0),
            'outstandingAmount' => floatval($stats['outstanding_amount'] ?? 0),
            'gstLiability' => floatval($stats['gst_liability'] ?? 0),
            'poCommitments' => floatval($stats['po_commitments'] ?? 0),
            'claimableAmount' => floatval($stats['claimable_amount'] ?? 0),
            'collectionRate' => floatval($stats['collection_rate'] ?? 0),
            'overdueAmount' => floatval($stats['overdue_amount'] ?? 0),
            'openPOs' => intval($stats['open_pos'] ?? 0),
            'closedPOs' => intval($stats['closed_pos'] ?? 0),
            'lastUpdated' => $stats['updated_at'] ?? null
        ];
    }
    
    // Get conversion funnel data
    private function getFunnel() {
        $stmt = $this->db->prepare("SELECT * FROM finance_funnel WHERE company_prefix = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$this->prefix]);
        $funnel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$funnel) {
            $this->calculateFunnel();
            $stmt->execute([$this->prefix]);
            $funnel = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return [
            'quotations' => intval($funnel['quotations_count'] ?? 0),
            'quotationValue' => floatval($funnel['quotations_value'] ?? 0),
            'purchaseOrders' => intval($funnel['po_count'] ?? 0),
            'poValue' => floatval($funnel['po_value'] ?? 0),
            'invoices' => intval($funnel['invoices_count'] ?? 0),
            'invoiceValue' => floatval($funnel['invoices_value'] ?? 0),
            'payments' => intval($funnel['payments_count'] ?? 0),
            'paymentValue' => floatval($funnel['payments_value'] ?? 0),
            'quotationToPO' => floatval($funnel['quotation_to_po'] ?? 0),
            'poToInvoice' => floatval($funnel['po_to_invoice'] ?? 0),
            'invoiceToPayment' => floatval($funnel['invoice_to_payment'] ?? 0)
        ];
    }
    
    // Get chart data
    private function getCharts() {
        return [
            'quotations' => $this->getQuotationChart(),
            'invoices' => $this->getInvoiceChart(),
            'aging' => $this->getAgingChart(),
            'outstanding' => $this->getOutstandingChart()
        ];
    }
    
    // Get outstanding invoices
    private function getOutstanding() {
        $stmt = $this->db->prepare("
            SELECT invoice_number, customer_name, due_date, outstanding_amount, 
                   DATEDIFF(CURDATE(), due_date) as days_overdue
            FROM finance_invoices 
            WHERE company_prefix = ? AND outstanding_amount > 0
            ORDER BY outstanding_amount DESC
            LIMIT 20
        ");
        $stmt->execute([$this->prefix]);
        
        $invoices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invoices[] = [
                'invoice_number' => $row['invoice_number'],
                'customer_name' => $row['customer_name'],
                'due_date' => $row['due_date'],
                'outstanding_amount' => floatval($row['outstanding_amount']),
                'days_overdue' => max(0, intval($row['days_overdue'])),
                'status' => intval($row['days_overdue']) > 0 ? 'Overdue' : 'Pending'
            ];
        }
        
        return ['invoices' => $invoices];
    }
    
    // Get customers list
    private function getCustomers() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT customer_name, customer_gstin
            FROM finance_invoices 
            WHERE company_prefix = ?
            ORDER BY customer_name
        ");
        $stmt->execute([$this->prefix]);
        
        $customers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $customers[] = [
                'name' => $row['customer_name'],
                'gstin' => $row['customer_gstin'],
                'display' => $row['customer_name'] . ($row['customer_gstin'] ? ' (' . $row['customer_gstin'] . ')' : '')
            ];
        }
        
        return ['customers' => $customers];
    }
    
    // Get recent activities
    private function getActivities() {
        $activities = [];
        
        // Recent invoices
        $stmt = $this->db->prepare("
            SELECT 'invoice' as type, invoice_number as number, total_amount as amount, 
                   invoice_date as date, CASE WHEN outstanding_amount > 0 THEN 'pending' ELSE 'paid' END as status
            FROM finance_invoices 
            WHERE company_prefix = ?
            ORDER BY invoice_date DESC
            LIMIT 5
        ");
        $stmt->execute([$this->prefix]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'type' => $row['type'],
                'title' => 'Invoice ' . $row['number'],
                'amount' => floatval($row['amount']),
                'date' => $row['date'],
                'status' => $row['status'],
                'icon' => '💰'
            ];
        }
        
        // Recent quotations
        $stmt = $this->db->prepare("
            SELECT 'quotation' as type, quotation_number as number, total_amount as amount,
                   created_date as date, status
            FROM finance_quotations 
            WHERE company_prefix = ?
            ORDER BY created_date DESC
            LIMIT 3
        ");
        $stmt->execute([$this->prefix]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'type' => $row['type'],
                'title' => 'Quotation ' . $row['number'],
                'amount' => floatval($row['amount']),
                'date' => $row['date'],
                'status' => $row['status'],
                'icon' => '📝'
            ];
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return ['activities' => array_slice($activities, 0, 8)];
    }
    
    // Sync data from PostgreSQL
    private function syncData() {
        $pgConn = $this->connectPostgreSQL();
        if (!$pgConn) {
            return ['success' => false, 'error' => 'PostgreSQL connection failed'];
        }
        
        $synced = 0;
        
        // Sync invoices
        $result = pg_query($pgConn, "SELECT * FROM finance_invoices");
        if ($result) {
            $this->syncInvoices(pg_fetch_all($result) ?: []);
            $synced++;
        }
        
        // Sync quotations
        $result = pg_query($pgConn, "SELECT * FROM finance_quotations");
        if ($result) {
            $this->syncQuotations(pg_fetch_all($result) ?: []);
            $synced++;
        }
        
        // Sync purchase orders
        $result = pg_query($pgConn, "SELECT * FROM finance_purchase_orders");
        if ($result) {
            $this->syncPurchaseOrders(pg_fetch_all($result) ?: []);
            $synced++;
        }
        
        // Sync customers
        $result = pg_query($pgConn, "SELECT * FROM finance_customers");
        if ($result) {
            $this->syncCustomers(pg_fetch_all($result) ?: []);
            $synced++;
        }
        
        pg_close($pgConn);
        
        // Recalculate stats after sync
        $this->calculateStats();
        $this->calculateFunnel();
        
        return ['success' => true, 'synced' => $synced];
    }
    
    // Update company prefix
    private function updatePrefix() {
        $newPrefix = strtoupper(trim($_POST['prefix'] ?? ''));
        
        $stmt = $this->db->prepare("
            INSERT INTO finance_settings (setting_key, setting_value) 
            VALUES ('company_prefix', ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$newPrefix, $newPrefix]);
        
        $this->prefix = $newPrefix;
        
        return ['success' => true, 'prefix' => $newPrefix];
    }
    
    // Calculate all stats
    private function calculateStats() {
        $stats = [
            'company_prefix' => $this->prefix,
            'total_revenue' => 0,
            'amount_received' => 0,
            'outstanding_amount' => 0,
            'gst_liability' => 0,
            'po_commitments' => 0,
            'claimable_amount' => 0,
            'collection_rate' => 0,
            'overdue_amount' => 0,
            'open_pos' => 0,
            'closed_pos' => 0
        ];
        
        // Calculate invoice stats
        $stmt = $this->db->prepare("
            SELECT 
                SUM(total_amount) as total_revenue,
                SUM(amount_paid) as amount_received,
                SUM(outstanding_amount) as outstanding_amount,
                SUM(CASE WHEN due_date < CURDATE() AND outstanding_amount > 0 THEN outstanding_amount ELSE 0 END) as overdue_amount,
                SUM(igst + cgst + sgst) as gst_liability
            FROM finance_invoices 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $invoiceStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invoiceStats) {
            $stats['total_revenue'] = floatval($invoiceStats['total_revenue'] ?? 0);
            $stats['amount_received'] = floatval($invoiceStats['amount_received'] ?? 0);
            $stats['outstanding_amount'] = floatval($invoiceStats['outstanding_amount'] ?? 0);
            $stats['overdue_amount'] = floatval($invoiceStats['overdue_amount'] ?? 0);
            $stats['gst_liability'] = floatval($invoiceStats['gst_liability'] ?? 0);
            $stats['collection_rate'] = $stats['total_revenue'] > 0 ? 
                ($stats['amount_received'] / $stats['total_revenue']) * 100 : 0;
        }
        
        // Calculate PO stats
        $stmt = $this->db->prepare("
            SELECT 
                SUM(total_amount) as po_commitments,
                SUM(CASE WHEN status IN ('open', 'pending') THEN 1 ELSE 0 END) as open_pos,
                SUM(CASE WHEN status IN ('closed', 'completed') THEN 1 ELSE 0 END) as closed_pos,
                SUM(total_amount - amount_paid) as claimable_amount
            FROM finance_purchase_orders 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $poStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($poStats) {
            $stats['po_commitments'] = floatval($poStats['po_commitments'] ?? 0);
            $stats['open_pos'] = intval($poStats['open_pos'] ?? 0);
            $stats['closed_pos'] = intval($poStats['closed_pos'] ?? 0);
            $stats['claimable_amount'] = floatval($poStats['claimable_amount'] ?? 0);
        }
        
        // Save stats
        $this->saveStats($stats);
    }
    
    // Calculate funnel data
    private function calculateFunnel() {
        $funnel = ['company_prefix' => $this->prefix];
        
        // Quotations
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, SUM(total_amount) as value
            FROM finance_quotations 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $quotations = $stmt->fetch(PDO::FETCH_ASSOC);
        $funnel['quotations_count'] = intval($quotations['count'] ?? 0);
        $funnel['quotations_value'] = floatval($quotations['value'] ?? 0);
        
        // Purchase Orders
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, SUM(total_amount) as value
            FROM finance_purchase_orders 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $funnel['po_count'] = intval($pos['count'] ?? 0);
        $funnel['po_value'] = floatval($pos['value'] ?? 0);
        
        // Invoices
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, SUM(total_amount) as value
            FROM finance_invoices 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $invoices = $stmt->fetch(PDO::FETCH_ASSOC);
        $funnel['invoices_count'] = intval($invoices['count'] ?? 0);
        $funnel['invoices_value'] = floatval($invoices['value'] ?? 0);
        
        // Payments
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count, SUM(amount_paid) as value
            FROM finance_invoices 
            WHERE company_prefix = ? AND amount_paid > 0
        ");
        $stmt->execute([$this->prefix]);
        $payments = $stmt->fetch(PDO::FETCH_ASSOC);
        $funnel['payments_count'] = intval($payments['count'] ?? 0);
        $funnel['payments_value'] = floatval($payments['value'] ?? 0);
        
        // Calculate conversion rates
        $funnel['quotation_to_po'] = $funnel['quotations_count'] > 0 ? 
            ($funnel['po_count'] / $funnel['quotations_count']) * 100 : 0;
        $funnel['po_to_invoice'] = $funnel['po_count'] > 0 ? 
            ($funnel['invoices_count'] / $funnel['po_count']) * 100 : 0;
        $funnel['invoice_to_payment'] = $funnel['invoices_count'] > 0 ? 
            ($funnel['payments_count'] / $funnel['invoices_count']) * 100 : 0;
        
        // Save funnel
        $this->saveFunnel($funnel);
    }
    
    // Chart methods
    private function getQuotationChart() {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count
            FROM finance_quotations 
            WHERE company_prefix = ?
            GROUP BY status
        ");
        $stmt->execute([$this->prefix]);
        
        $data = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = strtolower($row['status']);
            if (isset($data[$status])) {
                $data[$status] = intval($row['count']);
            }
        }
        
        return [
            'labels' => array_keys($data),
            'data' => array_values($data)
        ];
    }
    
    private function getInvoiceChart() {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN outstanding_amount = 0 THEN total_amount ELSE 0 END) as paid,
                SUM(CASE WHEN outstanding_amount > 0 AND due_date >= CURDATE() THEN outstanding_amount ELSE 0 END) as unpaid,
                SUM(CASE WHEN outstanding_amount > 0 AND due_date < CURDATE() THEN outstanding_amount ELSE 0 END) as overdue
            FROM finance_invoices 
            WHERE company_prefix = ?
        ");
        $stmt->execute([$this->prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'labels' => ['Paid', 'Unpaid', 'Overdue'],
            'data' => [
                floatval($row['paid'] ?? 0),
                floatval($row['unpaid'] ?? 0),
                floatval($row['overdue'] ?? 0)
            ]
        ];
    }
    
    private function getAgingChart() {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 0 AND 30 THEN outstanding_amount ELSE 0 END) as current_30,
                SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN outstanding_amount ELSE 0 END) as days_31_60,
                SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN outstanding_amount ELSE 0 END) as days_61_90,
                SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN outstanding_amount ELSE 0 END) as days_90_plus
            FROM finance_invoices 
            WHERE company_prefix = ? AND outstanding_amount > 0
        ");
        $stmt->execute([$this->prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'labels' => ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
            'data' => [
                floatval($row['current_30'] ?? 0),
                floatval($row['days_31_60'] ?? 0),
                floatval($row['days_61_90'] ?? 0),
                floatval($row['days_90_plus'] ?? 0)
            ]
        ];
    }
    
    private function getOutstandingChart() {
        $stmt = $this->db->prepare("
            SELECT customer_name, SUM(outstanding_amount) as amount
            FROM finance_invoices 
            WHERE company_prefix = ? AND outstanding_amount > 0
            GROUP BY customer_name
            ORDER BY amount DESC
            LIMIT 10
        ");
        $stmt->execute([$this->prefix]);
        
        $labels = [];
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $labels[] = $row['customer_name'];
            $data[] = floatval($row['amount']);
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    // Database operations
    private function createTables() {
        // Settings table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Stats table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                total_revenue DECIMAL(15,2) DEFAULT 0,
                amount_received DECIMAL(15,2) DEFAULT 0,
                outstanding_amount DECIMAL(15,2) DEFAULT 0,
                gst_liability DECIMAL(15,2) DEFAULT 0,
                po_commitments DECIMAL(15,2) DEFAULT 0,
                claimable_amount DECIMAL(15,2) DEFAULT 0,
                collection_rate DECIMAL(5,2) DEFAULT 0,
                overdue_amount DECIMAL(15,2) DEFAULT 0,
                open_pos INT DEFAULT 0,
                closed_pos INT DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_prefix (company_prefix)
            )
        ");
        
        // Funnel table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_funnel (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                quotations_count INT DEFAULT 0,
                quotations_value DECIMAL(15,2) DEFAULT 0,
                po_count INT DEFAULT 0,
                po_value DECIMAL(15,2) DEFAULT 0,
                invoices_count INT DEFAULT 0,
                invoices_value DECIMAL(15,2) DEFAULT 0,
                payments_count INT DEFAULT 0,
                payments_value DECIMAL(15,2) DEFAULT 0,
                quotation_to_po DECIMAL(5,2) DEFAULT 0,
                po_to_invoice DECIMAL(5,2) DEFAULT 0,
                invoice_to_payment DECIMAL(5,2) DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_prefix (company_prefix)
            )
        ");
        
        // Invoices table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                invoice_number VARCHAR(100),
                customer_name VARCHAR(255),
                customer_gstin VARCHAR(50),
                total_amount DECIMAL(15,2) DEFAULT 0,
                taxable_amount DECIMAL(15,2) DEFAULT 0,
                amount_paid DECIMAL(15,2) DEFAULT 0,
                outstanding_amount DECIMAL(15,2) DEFAULT 0,
                igst DECIMAL(15,2) DEFAULT 0,
                cgst DECIMAL(15,2) DEFAULT 0,
                sgst DECIMAL(15,2) DEFAULT 0,
                invoice_date DATE,
                due_date DATE,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_prefix (company_prefix),
                INDEX idx_invoice_number (invoice_number),
                INDEX idx_outstanding (outstanding_amount)
            )
        ");
        
        // Quotations table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_quotations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                quotation_number VARCHAR(100),
                customer_name VARCHAR(255),
                total_amount DECIMAL(15,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'pending',
                created_date DATE,
                valid_until DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_prefix (company_prefix),
                INDEX idx_quotation_number (quotation_number)
            )
        ");
        
        // Purchase Orders table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS finance_purchase_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_prefix VARCHAR(10),
                po_number VARCHAR(100),
                internal_po_number VARCHAR(100),
                vendor_name VARCHAR(255),
                total_amount DECIMAL(15,2) DEFAULT 0,
                amount_paid DECIMAL(15,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'open',
                po_date DATE,
                received_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_prefix (company_prefix),
                INDEX idx_po_number (po_number)
            )
        ");
    }
    
    private function saveStats($stats) {
        $sql = "
            INSERT INTO finance_stats (
                company_prefix, total_revenue, amount_received, outstanding_amount,
                gst_liability, po_commitments, claimable_amount, collection_rate,
                overdue_amount, open_pos, closed_pos
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_revenue = VALUES(total_revenue),
                amount_received = VALUES(amount_received),
                outstanding_amount = VALUES(outstanding_amount),
                gst_liability = VALUES(gst_liability),
                po_commitments = VALUES(po_commitments),
                claimable_amount = VALUES(claimable_amount),
                collection_rate = VALUES(collection_rate),
                overdue_amount = VALUES(overdue_amount),
                open_pos = VALUES(open_pos),
                closed_pos = VALUES(closed_pos),
                updated_at = NOW()
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $stats['company_prefix'],
            $stats['total_revenue'],
            $stats['amount_received'],
            $stats['outstanding_amount'],
            $stats['gst_liability'],
            $stats['po_commitments'],
            $stats['claimable_amount'],
            $stats['collection_rate'],
            $stats['overdue_amount'],
            $stats['open_pos'],
            $stats['closed_pos']
        ]);
    }
    
    private function saveFunnel($funnel) {
        $sql = "
            INSERT INTO finance_funnel (
                company_prefix, quotations_count, quotations_value, po_count, po_value,
                invoices_count, invoices_value, payments_count, payments_value,
                quotation_to_po, po_to_invoice, invoice_to_payment
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quotations_count = VALUES(quotations_count),
                quotations_value = VALUES(quotations_value),
                po_count = VALUES(po_count),
                po_value = VALUES(po_value),
                invoices_count = VALUES(invoices_count),
                invoices_value = VALUES(invoices_value),
                payments_count = VALUES(payments_count),
                payments_value = VALUES(payments_value),
                quotation_to_po = VALUES(quotation_to_po),
                po_to_invoice = VALUES(po_to_invoice),
                invoice_to_payment = VALUES(invoice_to_payment),
                updated_at = NOW()
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $funnel['company_prefix'],
            $funnel['quotations_count'],
            $funnel['quotations_value'],
            $funnel['po_count'],
            $funnel['po_value'],
            $funnel['invoices_count'],
            $funnel['invoices_value'],
            $funnel['payments_count'],
            $funnel['payments_value'],
            $funnel['quotation_to_po'],
            $funnel['po_to_invoice'],
            $funnel['invoice_to_payment']
        ]);
    }
    
    // Sync methods
    private function syncInvoices($data) {
        $this->db->exec("DELETE FROM finance_invoices WHERE company_prefix = '{$this->prefix}'");
        
        foreach ($data as $row) {
            $invoiceNumber = $row['invoice_number'] ?? '';
            if (!$this->prefix || strpos($invoiceNumber, $this->prefix) === 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO finance_invoices (
                        company_prefix, invoice_number, customer_name, customer_gstin,
                        total_amount, taxable_amount, amount_paid, outstanding_amount,
                        igst, cgst, sgst, invoice_date, due_date, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $this->prefix,
                    $invoiceNumber,
                    $row['customer_name'] ?? '',
                    $row['customer_gstin'] ?? '',
                    floatval($row['total_amount'] ?? 0),
                    floatval($row['taxable_amount'] ?? $row['total_amount'] ?? 0),
                    floatval($row['amount_paid'] ?? 0),
                    floatval($row['outstanding_amount'] ?? 0),
                    floatval($row['igst'] ?? 0),
                    floatval($row['cgst'] ?? 0),
                    floatval($row['sgst'] ?? 0),
                    $row['invoice_date'] ?? null,
                    $row['due_date'] ?? null,
                    $row['status'] ?? 'pending'
                ]);
            }
        }
    }
    
    private function syncQuotations($data) {
        $this->db->exec("DELETE FROM finance_quotations WHERE company_prefix = '{$this->prefix}'");
        
        foreach ($data as $row) {
            $quotationNumber = $row['quotation_number'] ?? '';
            if (!$this->prefix || strpos($quotationNumber, $this->prefix) === 0) {
                $stmt = $this->db->prepare("
                    INSERT INTO finance_quotations (
                        company_prefix, quotation_number, customer_name, total_amount,
                        status, created_date, valid_until
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $this->prefix,
                    $quotationNumber,
                    $row['customer_name'] ?? '',
                    floatval($row['total_amount'] ?? 0),
                    $row['status'] ?? 'pending',
                    $row['created_date'] ?? null,
                    $row['valid_until'] ?? null
                ]);
            }
        }
    }
    
    private function syncPurchaseOrders($data) {
        $this->db->exec("DELETE FROM finance_purchase_orders WHERE company_prefix = '{$this->prefix}'");
        
        foreach ($data as $row) {
            $poNumber = $row['po_number'] ?? $row['internal_po_number'] ?? '';
            if (!$this->prefix || stripos($poNumber, $this->prefix) !== false) {
                $stmt = $this->db->prepare("
                    INSERT INTO finance_purchase_orders (
                        company_prefix, po_number, internal_po_number, vendor_name,
                        total_amount, amount_paid, status, po_date, received_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $this->prefix,
                    $row['po_number'] ?? '',
                    $row['internal_po_number'] ?? '',
                    $row['vendor_name'] ?? '',
                    floatval($row['total_amount'] ?? 0),
                    floatval($row['amount_paid'] ?? 0),
                    $row['status'] ?? 'open',
                    $row['po_date'] ?? null,
                    $row['received_date'] ?? null
                ]);
            }
        }
    }
    
    private function syncCustomers($data) {
        // Customers are extracted from invoices and quotations
        // No separate customer sync needed in this simplified version
    }
    
    private function connectPostgreSQL() {
        $pgHost = '72.60.218.167';
        $pgPort = '5432';
        $pgDb = 'modernsap';
        $pgUser = 'postgres';
        $pgPass = 'mango';
        
        return @pg_connect("host=$pgHost port=$pgPort dbname=$pgDb user=$pgUser password=$pgPass");
    }
    
    private function getCompanyPrefix() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM finance_settings WHERE setting_key = 'company_prefix'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? strtoupper(trim($result['setting_value'])) : '';
        } catch (Exception $e) {
            return '';
        }
    }
}
?>