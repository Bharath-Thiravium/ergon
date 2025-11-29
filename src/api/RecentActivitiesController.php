<?php

namespace Ergon\FinanceSync\Api;

use PDO;
use Psr\Log\LoggerInterface;

class RecentActivitiesController
{
    private PDO $pdo;
    private LoggerInterface $logger;
    
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Get recent activities from MySQL only
     */
    public function getRecentActivities(array $params = []): array
    {
        $companyPrefix = $params['prefix'] ?? null;
        $recordType = $params['record_type'] ?? null;
        $limit = (int)($params['limit'] ?? 20);
        
        if (!$companyPrefix) {
            return $this->errorResponse('Company prefix is required', 400);
        }
        
        try {
            $sql = "
                SELECT 
                    record_type, 
                    document_number, 
                    customer_name, 
                    status, 
                    amount, 
                    created_at,
                    customer_id,
                    outstanding_amount,
                    due_date
                FROM finance_consolidated
                WHERE company_prefix = ?
            ";
            
            $params = [$companyPrefix];
            
            // Optional record type filter
            if ($recordType && in_array($recordType, ['quotation', 'purchase_order', 'invoice', 'payment'])) {
                $sql .= " AND record_type = ?";
                $params[] = $recordType;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $activities = $stmt->fetchAll();
            
            // Add icons and format data
            $formattedActivities = array_map([$this, 'formatActivity'], $activities);
            
            $this->logger->info("Retrieved recent activities", [
                'company_prefix' => $companyPrefix,
                'record_type' => $recordType,
                'count' => count($activities)
            ]);
            
            return $this->successResponse($formattedActivities);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch recent activities: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats(string $companyPrefix): array
    {
        try {
            $sql = "
                SELECT 
                    record_type,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    SUM(outstanding_amount) as total_outstanding
                FROM finance_consolidated
                WHERE company_prefix = ?
                GROUP BY record_type
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyPrefix]);
            $stats = $stmt->fetchAll();
            
            $formattedStats = [];
            foreach ($stats as $stat) {
                $formattedStats[$stat['record_type']] = [
                    'count' => (int)$stat['count'],
                    'total_amount' => (float)$stat['total_amount'],
                    'total_outstanding' => (float)$stat['total_outstanding'],
                    'icon' => $this->getActivityIcon($stat['record_type'])
                ];
            }
            
            return $this->successResponse($formattedStats);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch activity stats: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Get dashboard cash flow stats
     */
    public function getDashboardStats(string $companyPrefix): array
    {
        try {
            $sql = "
                SELECT 
                    expected_inflow,
                    po_commitments,
                    net_cash_flow,
                    last_computed_at
                FROM dashboard_stats
                WHERE company_prefix = ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyPrefix]);
            $stats = $stmt->fetch();
            
            if (!$stats) {
                return $this->errorResponse('No dashboard stats found for this prefix', 404);
            }
            
            $formattedStats = [
                'expected_inflow' => (float)$stats['expected_inflow'],
                'po_commitments' => (float)$stats['po_commitments'],
                'net_cash_flow' => (float)$stats['net_cash_flow'],
                'last_computed_at' => $stats['last_computed_at'],
                'formatted' => [
                    'expected_inflow' => number_format($stats['expected_inflow'], 2),
                    'po_commitments' => number_format($stats['po_commitments'], 2),
                    'net_cash_flow' => number_format($stats['net_cash_flow'], 2)
                ]
            ];
            
            return $this->successResponse($formattedStats);
            
        } catch (\PDOException $e) {
            $this->logger->error("Failed to fetch dashboard stats: " . $e->getMessage());
            return $this->errorResponse('Database error occurred', 500);
        }
    }
    
    /**
     * Format activity with icon and additional data
     */
    private function formatActivity(array $activity): array
    {
        return [
            'record_type' => $activity['record_type'],
            'icon' => $this->getActivityIcon($activity['record_type']),
            'document_number' => $activity['document_number'],
            'customer_name' => $activity['customer_name'],
            'customer_id' => $activity['customer_id'],
            'status' => $activity['status'],
            'amount' => (float)$activity['amount'],
            'outstanding_amount' => (float)$activity['outstanding_amount'],
            'due_date' => $activity['due_date'],
            'created_at' => $activity['created_at'],
            'formatted_amount' => number_format($activity['amount'], 2),
            'is_overdue' => $this->isOverdue($activity)
        ];
    }
    
    /**
     * Get icon for activity type
     */
    private function getActivityIcon(string $recordType): string
    {
        return match ($recordType) {
            'quotation' => '📝',
            'purchase_order' => '🛒',
            'invoice' => '💰',
            'payment' => '💳',
            default => '📄'
        };
    }
    
    /**
     * Check if activity is overdue
     */
    private function isOverdue(array $activity): bool
    {
        if ($activity['record_type'] !== 'invoice' || !$activity['due_date'] || $activity['outstanding_amount'] <= 0) {
            return false;
        }
        
        try {
            $dueDate = new \DateTime($activity['due_date']);
            $today = new \DateTime('today');
            return $today > $dueDate;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Success response format
     */
    private function successResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Error response format
     */
    private function errorResponse(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Handle HTTP request and return JSON response
     */
    public function handleRequest(): void
    {
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . ($_ENV['API_CORS_ORIGINS'] ?? '*'));
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode($this->errorResponse('Method not allowed', 405));
            exit;
        }
        
        // Parse query parameters
        $params = $_GET;
        
        // Route to appropriate method
        $action = $params['action'] ?? 'activities';
        
        try {
            switch ($action) {
                case 'activities':
                    $response = $this->getRecentActivities($params);
                    break;
                case 'stats':
                    if (!isset($params['prefix'])) {
                        $response = $this->errorResponse('Company prefix is required', 400);
                    } else {
                        $response = $this->getActivityStats($params['prefix']);
                    }
                    break;
                case 'dashboard':
                    if (!isset($params['prefix'])) {
                        $response = $this->errorResponse('Company prefix is required', 400);
                    } else {
                        $response = $this->getDashboardStats($params['prefix']);
                    }
                    break;
                default:
                    $response = $this->errorResponse('Invalid action', 400);
            }
            
            http_response_code($response['success'] ? 200 : ($response['code'] ?? 400));
            echo json_encode($response);
            
        } catch (\Exception $e) {
            $this->logger->error("API request failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode($this->errorResponse('Internal server error', 500));
        }
    }
}