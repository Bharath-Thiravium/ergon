<?php

class FinanceController {
    private $mysqlConnection;
    
    public function __construct() {
        $this->mysqlConnection = $this->getMysqlConnection();
    }
    
    private function getMysqlConnection() {
        $config = require_once __DIR__ . '/../config/database.php';
        $mysql = $config['mysql'];
        
        try {
            $pdo = new PDO(
                "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset=utf8mb4",
                $mysql['username'],
                $mysql['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            Logger::error("MySQL connection failed", ['error' => $e->getMessage()]);
            throw new Exception("Database connection failed");
        }
    }
    
    public function dashboardStats($request) {
        $prefix = PrefixFallback::validateAndFallback($request->get('prefix'));
        
        try {
            $stmt = $this->mysqlConnection->prepare(
                'SELECT * FROM dashboard_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1'
            );
            $stmt->execute([$prefix]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return $this->jsonResponse(404, ['error' => 'No data found for prefix: ' . $prefix]);
            }
            
            // Convert numeric strings to proper numbers for JSON
            $numericFields = [
                'total_revenue', 'avg_invoice', 'amount_received', 'collection_rate',
                'outstanding_amount', 'overdue_amount', 'outstanding_percentage',
                'igst_liability', 'cgst_sgst_total', 'gst_liability', 'po_commitments',
                'claimable_amount', 'claim_rate'
            ];
            
            foreach ($numericFields as $field) {
                if (isset($row[$field])) {
                    $row[$field] = (float) $row[$field];
                }
            }
            
            return $this->jsonResponse(200, $row);
            
        } catch (Exception $e) {
            Logger::error("dashboardStats error", ['prefix' => $prefix, 'error' => $e->getMessage()]);
            return $this->jsonResponse(500, ['error' => 'Internal server error']);
        }
    }
    
    public function funnelStats($request) {
        $prefix = PrefixFallback::validateAndFallback($request->get('prefix'));
        
        try {
            $stmt = $this->mysqlConnection->prepare(
                'SELECT * FROM funnel_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1'
            );
            $stmt->execute([$prefix]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return $this->jsonResponse(404, ['error' => 'No funnel data found for prefix: ' . $prefix]);
            }
            
            // Convert numeric fields
            $numericFields = [
                'quotation_value', 'po_value', 'po_conversion_rate',
                'invoice_value', 'payment_value'
            ];
            
            foreach ($numericFields as $field) {
                if (isset($row[$field])) {
                    $row[$field] = (float) $row[$field];
                }
            }
            
            return $this->jsonResponse(200, $row);
            
        } catch (Exception $e) {
            Logger::error("funnelStats error", ['prefix' => $prefix, 'error' => $e->getMessage()]);
            return $this->jsonResponse(500, ['error' => 'Internal server error']);
        }
    }
    
    public function chartStats($request) {
        $prefix = PrefixFallback::validateAndFallback($request->get('prefix'));
        
        try {
            $stmt = $this->mysqlConnection->prepare(
                'SELECT * FROM chart_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1'
            );
            $stmt->execute([$prefix]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return $this->jsonResponse(404, ['error' => 'No chart data found for prefix: ' . $prefix]);
            }
            
            // Decode JSON fields
            $jsonFields = [
                'quotations_overview', 'po_fulfillment_buckets', 'invoice_distribution',
                'outstanding_top_customers', 'aging_buckets'
            ];
            
            foreach ($jsonFields as $field) {
                if (isset($row[$field]) && is_string($row[$field])) {
                    $row[$field] = json_decode($row[$field], true);
                }
            }
            
            return $this->jsonResponse(200, $row);
            
        } catch (Exception $e) {
            Logger::error("chartStats error", ['prefix' => $prefix, 'error' => $e->getMessage()]);
            return $this->jsonResponse(500, ['error' => 'Internal server error']);
        }
    }
    
    public function poStats($request) {
        $prefix = PrefixFallback::validateAndFallback($request->get('prefix'));
        
        try {
            // Optional detailed PO endpoint - fetch from dashboard_stats for now
            $stmt = $this->mysqlConnection->prepare(
                'SELECT company_prefix, generated_at, po_commitments, open_po, closed_po 
                 FROM dashboard_stats WHERE company_prefix = ? ORDER BY generated_at DESC LIMIT 1'
            );
            $stmt->execute([$prefix]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return $this->jsonResponse(404, ['error' => 'No PO data found for prefix: ' . $prefix]);
            }
            
            $row['po_commitments'] = (float) $row['po_commitments'];
            
            return $this->jsonResponse(200, $row);
            
        } catch (Exception $e) {
            Logger::error("poStats error", ['prefix' => $prefix, 'error' => $e->getMessage()]);
            return $this->jsonResponse(500, ['error' => 'Internal server error']);
        }
    }
    
    public function triggerSync($request) {
        // CSRF protection
        if (!$this->validateCSRFToken($request)) {
            return $this->jsonResponse(403, ['error' => 'Invalid CSRF token']);
        }
        
        // Authentication check
        if (!$this->isAuthenticated($request)) {
            return $this->jsonResponse(401, ['error' => 'Authentication required']);
        }
        
        $prefix = $request->post('prefix');
        if ($prefix) {
            $prefix = PrefixFallback::validate($prefix);
            if (!$prefix) {
                return $this->jsonResponse(400, ['error' => 'Invalid prefix format']);
            }
        }
        
        try {
            $etl = new FinanceETLService();
            
            if ($prefix) {
                // Sync specific prefix
                $etl->runForPrefix($prefix);
                Logger::info("Manual ETL sync triggered", ['prefix' => $prefix, 'user' => $this->getCurrentUser()]);
                return $this->jsonResponse(200, ['status' => 'started', 'prefix' => $prefix]);
            } else {
                // Sync all prefixes
                $etl->runAllPrefixes();
                Logger::info("Manual ETL sync triggered for all prefixes", ['user' => $this->getCurrentUser()]);
                return $this->jsonResponse(200, ['status' => 'started', 'scope' => 'all_prefixes']);
            }
            
        } catch (Exception $e) {
            Logger::error("ETL sync trigger failed", [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
                'user' => $this->getCurrentUser()
            ]);
            return $this->jsonResponse(500, ['error' => 'Sync failed to start']);
        }
    }
    
    public function health($request) {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'database' => 'connected',
                'last_etl_runs' => []
            ];
            
            // Check database connectivity
            $this->mysqlConnection->query('SELECT 1');
            
            // Get last ETL run status for each prefix
            $stmt = $this->mysqlConnection->prepare(
                'SELECT company_prefix, generated_at, 
                        TIMESTAMPDIFF(MINUTE, generated_at, NOW()) as minutes_ago
                 FROM dashboard_stats 
                 ORDER BY generated_at DESC 
                 LIMIT 10'
            );
            $stmt->execute();
            $runs = $stmt->fetchAll();
            
            foreach ($runs as $run) {
                $health['last_etl_runs'][] = [
                    'prefix' => $run['company_prefix'],
                    'generated_at' => $run['generated_at'],
                    'minutes_ago' => (int) $run['minutes_ago'],
                    'status' => $run['minutes_ago'] < 120 ? 'recent' : 'stale'
                ];
            }
            
            // Overall system status
            $staleRuns = array_filter($health['last_etl_runs'], function($run) {
                return $run['status'] === 'stale';
            });
            
            if (count($staleRuns) > 2) {
                $health['status'] = 'degraded';
                $health['warning'] = 'Multiple prefixes have stale data';
            }
            
            return $this->jsonResponse(200, $health);
            
        } catch (Exception $e) {
            Logger::error("Health check failed", ['error' => $e->getMessage()]);
            return $this->jsonResponse(500, [
                'status' => 'unhealthy',
                'error' => 'System check failed',
                'timestamp' => date('c')
            ]);
        }
    }
    
    private function validateCSRFToken($request) {
        $token = $request->header('X-CSRF-Token') ?: $request->post('_token');
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }
    
    private function isAuthenticated($request) {
        // Implement your authentication logic here
        // For example, check session or JWT token
        return isset($_SESSION['user_id']) || $this->validateJWTToken($request);
    }
    
    private function validateJWTToken($request) {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }
        
        // Implement JWT validation logic
        // Return true if valid, false otherwise
        return false; // Placeholder
    }
    
    private function getCurrentUser() {
        return $_SESSION['username'] ?? 'unknown';
    }
    
    private function jsonResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}