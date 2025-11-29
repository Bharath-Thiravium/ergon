<?php

require_once __DIR__ . '/../services/FinanceETLService.php';
require_once __DIR__ . '/../services/PrefixFallback.php';

class FinanceController {
    private $etl;

    public function __construct() {
        $this->etl = new FinanceETLService();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'dashboard-stats';
        header('Content-Type: application/json');

        try {
            switch ($action) {
                case 'sync':
                    $this->sync();
                    break;
                case 'dashboard-stats':
                    $this->dashboardStats();
                    break;
                case 'company-prefix':
                    $this->companyPrefix();
                    break;
                case 'outstanding-invoices':
                    $this->outstandingInvoices();
                    break;
                case 'customers':
                    $this->customers();
                    break;
                case 'refresh-stats':
                    $this->refreshStats();
                    break;
                case 'funnel-containers':
                    $this->funnelContainers();
                    break;
                default:
                    $this->error('Unknown action');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function validatePrefix($prefix) {
        if (!$prefix || !preg_match('/^[A-Z]{2,4}$/', $prefix)) {
            throw new Exception("Invalid prefix format");
        }
        return $prefix;
    }

    private function sync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('POST method required');
        }

        $prefix = $this->validatePrefix($_POST['company_prefix'] ?? '');
        $result = $this->etl->runETL($prefix);

        echo json_encode([
            'success' => true,
            'records_processed' => $result['records_processed'] ?? 0,
            'prefix' => $prefix
        ]);
    }

    private function dashboardStats() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);

        $stats = $this->getDashboardStats($prefix);
        echo json_encode($stats);
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

    private function companyPrefix() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $prefix = $_POST['company_prefix'] ?? '';
            $prefix = $prefix ? $this->validatePrefix($prefix) : '';
            
            $_SESSION['company_prefix'] = $prefix;
            echo json_encode(['success' => true, 'prefix' => $prefix]);
        } else {
            $prefix = $_SESSION['company_prefix'] ?? $this->getDefaultPrefix();
            echo json_encode(['success' => true, 'prefix' => $prefix]);
        }
    }

    private function outstandingInvoices() {
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

        echo json_encode(['success' => true, 'invoices' => $invoices]);
    }

    private function customers() {
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

        echo json_encode(['success' => true, 'customers' => $customers]);
    }

    private function getDefaultPrefix() {
        $fallback = new PrefixFallback();
        return $fallback->getLatestActivePrefix();
    }

    private function refreshStats() {
        $prefix = $_GET['prefix'] ?? $this->getDefaultPrefix();
        $prefix = $this->validatePrefix($prefix);
        
        $result = $this->etl->runETL($prefix);
        
        echo json_encode([
            'success' => true,
            'records_processed' => $result['records_processed'] ?? 0,
            'prefix' => $prefix,
            'message' => 'ETL refresh completed'
        ]);
    }

    private function funnelContainers() {
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
            
            echo json_encode(['success' => true, 'containers' => $containers]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No funnel data available']);
        }
    }

    private function error($message) {
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}