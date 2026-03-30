<?php
/**
 * Robust PostgreSQL Sync Service
 * Handles connection failures and provides fallback options
 */

require_once __DIR__ . '/app/config/database.php';

class RobustSyncService {
    private $config;
    private $mysql;
    private $lastError = null;
    
    public function __construct() {
        $this->config = Database::getPostgreSQLConfig();
        $this->mysql = Database::connect();
    }
    
    public function testConnections() {
        $results = [
            'mysql' => $this->testMySQLConnection(),
            'postgresql' => $this->testPostgreSQLConnection()
        ];
        
        return $results;
    }
    
    private function testMySQLConnection() {
        try {
            $stmt = $this->mysql->query("SELECT 1");
            return [
                'status' => 'success',
                'message' => 'MySQL connection successful'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'MySQL connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function testPostgreSQLConnection() {
        try {
            $pg = $this->config['postgresql'];
            
            // Try different connection methods
            $connectionMethods = [
                [
                    'dsn' => "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}",
                    'timeout' => 5,
                    'description' => 'Direct connection'
                ],
                [
                    'dsn' => "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']};sslmode=disable",
                    'timeout' => 10,
                    'description' => 'Connection without SSL'
                ],
                [
                    'dsn' => "pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']};connect_timeout=30",
                    'timeout' => 30,
                    'description' => 'Extended timeout connection'
                ]
            ];
            
            foreach ($connectionMethods as $method) {
                try {
                    $pdo = new PDO(
                        $method['dsn'],
                        $pg['username'],
                        $pg['password'],
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => $method['timeout']
                        ]
                    );
                    
                    // Test query
                    $stmt = $pdo->query("SELECT version()");
                    $version = $stmt->fetch();
                    
                    return [
                        'status' => 'success',
                        'message' => 'PostgreSQL connection successful',
                        'method' => $method['description'],
                        'version' => $version['version'] ?? 'Unknown'
                    ];
                    
                } catch (Exception $e) {
                    $this->lastError = $e->getMessage();
                    continue; // Try next method
                }
            }
            
            return [
                'status' => 'failed',
                'message' => 'All PostgreSQL connection methods failed',
                'last_error' => $this->lastError,
                'config' => [
                    'host' => $pg['host'],
                    'port' => $pg['port'],
                    'database' => $pg['database'],
                    'username' => $pg['username']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'PostgreSQL connection setup failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function syncWithFallback() {
        $connectionTest = $this->testConnections();
        
        if ($connectionTest['postgresql']['status'] === 'failed') {
            return $this->handleSyncFailure($connectionTest);
        }
        
        try {
            // Attempt actual sync
            require_once __DIR__ . '/app/services/DataSyncService.php';
            $syncService = new DataSyncService();
            $results = $syncService->syncAllTables();
            
            return [
                'success' => true,
                'message' => 'Sync completed successfully',
                'results' => $results,
                'connection_test' => $connectionTest
            ];
            
        } catch (Exception $e) {
            return $this->handleSyncFailure($connectionTest, $e->getMessage());
        }
    }
    
    private function handleSyncFailure($connectionTest, $syncError = null) {
        // Log the failure
        $this->logSyncFailure($connectionTest, $syncError);
        
        // Provide recommendations
        $recommendations = $this->generateRecommendations($connectionTest);
        
        return [
            'success' => false,
            'message' => 'Sync failed - connection or sync error',
            'connection_test' => $connectionTest,
            'sync_error' => $syncError,
            'recommendations' => $recommendations,
            'fallback_options' => $this->getFallbackOptions()
        ];
    }
    
    private function logSyncFailure($connectionTest, $syncError) {
        try {
            $stmt = $this->mysql->prepare("
                INSERT INTO sync_log (
                    table_name, 
                    records_synced, 
                    sync_status, 
                    error_message, 
                    sync_started_at, 
                    sync_completed_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $errorMessage = json_encode([
                'connection_test' => $connectionTest,
                'sync_error' => $syncError
            ]);
            
            $stmt->execute([
                'connection_test',
                0,
                'failed',
                $errorMessage
            ]);
            
        } catch (Exception $e) {
            error_log('Failed to log sync failure: ' . $e->getMessage());
        }
    }
    
    private function generateRecommendations($connectionTest) {
        $recommendations = [];
        
        if ($connectionTest['postgresql']['status'] === 'failed') {
            $recommendations[] = 'Check if PostgreSQL server is running on ' . $this->config['postgresql']['host'];
            $recommendations[] = 'Verify network connectivity and firewall settings';
            $recommendations[] = 'Confirm PostgreSQL credentials are correct';
            $recommendations[] = 'Check if PostgreSQL allows remote connections (postgresql.conf)';
            $recommendations[] = 'Verify pg_hba.conf allows connections from your IP';
            
            if (strpos($connectionTest['postgresql']['message'], 'timeout') !== false) {
                $recommendations[] = 'Connection timeout - server may be overloaded or network is slow';
                $recommendations[] = 'Try increasing connection timeout in configuration';
            }
        }
        
        if ($connectionTest['mysql']['status'] === 'failed') {
            $recommendations[] = 'MySQL connection failed - check local database configuration';
        }
        
        return $recommendations;
    }
    
    private function getFallbackOptions() {
        return [
            'manual_data_entry' => 'Enter data manually through the web interface',
            'csv_import' => 'Export data from PostgreSQL as CSV and import manually',
            'api_integration' => 'Use direct API calls to PostgreSQL when connection is restored',
            'scheduled_retry' => 'Set up automatic retry every 15 minutes',
            'local_cache' => 'Use cached data from previous successful sync'
        ];
    }
    
    public function getLastSyncStatus() {
        try {
            $stmt = $this->mysql->prepare("
                SELECT * FROM sync_log 
                ORDER BY sync_started_at DESC 
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createMockData() {
        // Create sample data for testing when PostgreSQL is unavailable
        try {
            $tables = [
                'finance_customers' => [
                    ['customer_id' => 'CUST001', 'customer_name' => 'Test Customer 1', 'customer_gstin' => '29ABCDE1234F1Z5'],
                    ['customer_id' => 'CUST002', 'customer_name' => 'Test Customer 2', 'customer_gstin' => '29ABCDE5678F1Z5']
                ],
                'finance_invoices' => [
                    ['invoice_number' => 'INV001', 'customer_id' => 'CUST001', 'total_amount' => 10000.00, 'status' => 'paid'],
                    ['invoice_number' => 'INV002', 'customer_id' => 'CUST002', 'total_amount' => 15000.00, 'status' => 'pending']
                ]
            ];
            
            foreach ($tables as $tableName => $records) {
                // Create table if not exists
                $this->mysql->exec("CREATE TABLE IF NOT EXISTS $tableName (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id VARCHAR(64),
                    customer_name VARCHAR(255),
                    customer_gstin VARCHAR(64),
                    invoice_number VARCHAR(128),
                    total_amount DECIMAL(18,2),
                    status VARCHAR(64),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                // Insert mock data
                foreach ($records as $record) {
                    $columns = array_keys($record);
                    $placeholders = ':' . implode(', :', $columns);
                    $columnList = implode(', ', $columns);
                    
                    $stmt = $this->mysql->prepare("
                        INSERT INTO $tableName ($columnList) 
                        VALUES ($placeholders)
                        ON DUPLICATE KEY UPDATE updated_at = NOW()
                    ");
                    $stmt->execute($record);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Mock data created successfully',
                'tables' => array_keys($tables)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create mock data: ' . $e->getMessage()
            ];
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    echo "=== Robust PostgreSQL Sync ===\n\n";
    
    $syncService = new RobustSyncService();
    
    // Test connections first
    echo "Testing connections...\n";
    $connectionTest = $syncService->testConnections();
    
    foreach ($connectionTest as $type => $result) {
        echo "$type: " . $result['status'] . " - " . $result['message'] . "\n";
    }
    
    echo "\n";
    
    // Attempt sync with fallback
    $syncResult = $syncService->syncWithFallback();
    
    if ($syncResult['success']) {
        echo "✅ Sync completed successfully!\n";
        if (isset($syncResult['results'])) {
            foreach ($syncResult['results'] as $table => $result) {
                echo "  $table: {$result['records']} records ({$result['status']})\n";
            }
        }
    } else {
        echo "❌ Sync failed\n";
        echo "Error: " . $syncResult['message'] . "\n\n";
        
        if (!empty($syncResult['recommendations'])) {
            echo "Recommendations:\n";
            foreach ($syncResult['recommendations'] as $i => $rec) {
                echo ($i + 1) . ". $rec\n";
            }
        }
        
        echo "\nFallback options:\n";
        foreach ($syncResult['fallback_options'] as $option => $description) {
            echo "- $description\n";
        }
        
        // Offer to create mock data
        echo "\nWould you like to create mock data for testing? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) === 'y') {
            $mockResult = $syncService->createMockData();
            echo $mockResult['message'] . "\n";
        }
        fclose($handle);
    }
}
?>