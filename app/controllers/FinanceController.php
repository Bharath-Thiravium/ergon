<?php

require_once __DIR__ . '/../services/FinanceETLService.php';
require_once __DIR__ . '/../services/PrefixFallback.php';

class FinanceController {
    private $etl;

    public function __construct() {
        // Suppress all output for API calls
        ini_set('display_errors', 0);
        error_reporting(0);
        
        $this->etl = new FinanceETLService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function handleRequest() {
        ini_set('display_errors', 0);
        error_reporting(0);
        
        $action = $_GET['action'] ?? 'dashboard';
        
        try {
            switch ($action) {
                case 'sync':
                    $this->jsonResponse($this->syncData());
                    break;
                case 'dashboard-stats':
                    $this->jsonResponse($this->getDashboardStatsData());
                    break;
                case 'company-prefix':
                    $this->jsonResponse($this->getCompanyPrefixData());
                    break;
                case 'outstanding-invoices':
                    $this->jsonResponse($this->getOutstandingInvoicesData());
                    break;
                case 'customers':
                    $this->jsonResponse($this->getCustomersData());
                    break;
                case 'refresh-stats':
                    $this->jsonResponse($this->refreshStatsData());
                    break;
                case 'funnel-containers':
                    $this->jsonResponse($this->getFunnelContainersData());
                    break;
                case 'dashboard':
                default:
                    $this->dashboard();
                    break;
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function jsonResponse($data, $status = 200) {
        // Clean any existing output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set response headers
        http_response_code($status);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Output JSON and exit immediately
        echo json_encode($data);
        exit;
    }

    private function validatePrefix($prefix) {
        if (!$prefix || !preg_match('/^[A-Z]{2,4}$/', $prefix)) {
            throw new Exception("Invalid prefix format");
        }
        return $prefix;
    }

    private function syncData() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('POST method required');
        }

        $prefix = $this->validatePrefix($_POST['company_prefix'] ?? '');
        $result = $this->etl->runETL($prefix);

        return [
            'success' => true,
            'records_processed' => $result['records_processed'] ?? 0,
            'prefix' => $prefix
        ];
    }

    private function getDashboardStatsData() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);

        return $this->getDashboardStats($prefix);
    }

    private function getDashboardStats($prefix) {
        $pdo = $this->etl->getMysqlConnection();
        $stmt = $pdo->prepare("SELECT * FROM dashboard_stats WHERE company_prefix = ?");
        $stmt->execute([$prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || ($row['total_revenue'] ?? 0) == 0) {
            $fallback = new PrefixFallback();
            $activePrefix = $fallback->getLatestActivePrefix();
            
            if ($activePrefix && $activePrefix !== $prefix) {
                $stmt->execute([$activePrefix]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $row['message'] = "Showing data for active company: $activePrefix";
                    $row['source'] = 'fallback';
                }
            }
        }

        if ($row) {
            $row['source'] = $row['source'] ?? 'etl_dashboard_stats';
        }

        return $row ?: ['source' => 'empty', 'message' => 'No data available'];
    }

    private function getCompanyPrefixData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prefix = $_POST['company_prefix'] ?? '';
            $prefix = $prefix ? $this->validatePrefix($prefix) : '';
            
            $_SESSION['company_prefix'] = $prefix;
            return ['success' => true, 'prefix' => $prefix];
        } else {
            $prefix = $_SESSION['company_prefix'] ?? $this->getDefaultPrefix();
            return ['success' => true, 'prefix' => $prefix];
        }
    }

    private function getOutstandingInvoicesData() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);

        $pdo = $this->etl->getMysqlConnection();
        $stmt = $pdo->prepare("
            SELECT document_number as invoice_number, customer_name, outstanding_amount, 
                   due_date, DATEDIFF(NOW(), due_date) as days_overdue, status
            FROM finance_consolidated 
            WHERE company_prefix = ? AND record_type = 'invoice' AND outstanding_amount > 0 
            ORDER BY outstanding_amount DESC LIMIT 50
        ");
        $stmt->execute([$prefix]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'invoices' => $invoices];
    }

    private function getCustomersData() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);

        $pdo = $this->etl->getMysqlConnection();
        $stmt = $pdo->prepare("
            SELECT DISTINCT customer_id, customer_name,
                   CONCAT(customer_name, ' (', customer_id, ')') as display
            FROM finance_consolidated 
            WHERE company_prefix = ? AND customer_name IS NOT NULL
            ORDER BY customer_name
        ");
        $stmt->execute([$prefix]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'customers' => $customers];
    }

    private function getDefaultPrefix() {
        $fallback = new PrefixFallback();
        return $fallback->getLatestActivePrefix();
    }

    private function refreshStatsData() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);
        
        $result = $this->etl->runETL($prefix);
        
        return [
            'success' => true,
            'records_processed' => $result['records_processed'] ?? 0,
            'prefix' => $prefix,
            'message' => 'ETL refresh completed'
        ];
    }

    private function getFunnelContainersData() {
        require_once __DIR__ . '/../services/FunnelStatsService.php';
        
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);
        
        $funnelService = new FunnelStatsService();
        $funnelService->calculateFunnelStats($prefix);
        $stats = $funnelService->getFunnelStats($prefix);
        
        if ($stats) {
            $containers = [
                'container1' => [
                    'quotations_count' => $stats['quotation_count'],
                    'quotations_total_value' => $stats['quotation_value']
                ],
                'container2' => [
                    'po_count' => $stats['po_count'],
                    'po_total_value' => $stats['po_value'],
                    'po_conversion_rate' => $stats['po_conversion_rate']
                ],
                'container3' => [
                    'invoice_count' => $stats['invoice_count'],
                    'invoice_total_value' => $stats['invoice_value'],
                    'invoice_conversion_rate' => $stats['invoice_conversion_rate']
                ],
                'container4' => [
                    'payment_count' => $stats['payment_count'],
                    'total_payment_received' => $stats['payment_value'],
                    'payment_conversion_rate' => $stats['payment_conversion_rate']
                ]
            ];
            
            return ['success' => true, 'containers' => $containers];
        } else {
            return ['success' => false, 'error' => 'No funnel data available'];
        }
    }

    private function dashboard() {
        // Only show dashboard view for non-API requests
        if (!isset($_GET['action']) || $_GET['action'] === 'dashboard') {
            require_once __DIR__ . '/../../views/finance/dashboard.php';
        }
    }


}