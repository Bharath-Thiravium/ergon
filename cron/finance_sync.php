<?php
/**
 * Automated SAP Finance ETL Process
 * Extract from SAP API → Transform → Load to SQL → Calculate Analytics
 * Run this via cron job on Hostinger
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/FinanceETLService.php';

function runFinanceETL() {
    try {
        echo "Starting Finance ETL Process...\n";
        
        $etlService = new FinanceETLService();
        
        // Get available company prefixes from existing data or run for all
        $prefixes = getCompanyPrefixes();
        
        if (empty($prefixes)) {
            // Run ETL without prefix filter (all companies)
            echo "Running ETL for all companies...\n";
            $result = $etlService->runETL(null);
            
            if ($result['success']) {
                echo "ETL completed successfully: {$result['records_processed']} records processed\n";
            } else {
                echo "ETL failed: {$result['error']}\n";
            }
        } else {
            // Run ETL for each company prefix with error isolation
            foreach ($prefixes as $prefix) {
                try {
                    echo "Running ETL for company prefix: $prefix\n";
                    $result = $etlService->runETL($prefix);
                    
                    if ($result['success']) {
                        echo "ETL completed for $prefix: {$result['records_processed']} records processed\n";
                    } else {
                        echo "ETL failed for $prefix: {$result['error']}\n";
                    }
                } catch (Exception $e) {
                    echo "ETL error for $prefix: " . $e->getMessage() . "\n";
                    continue; // Continue with next prefix
                }
            }
        }
        
        echo "Finance ETL Process completed\n";
        
    } catch (Exception $e) {
        echo "ETL error: " . $e->getMessage() . "\n";
    }
}

function getCompanyPrefixes() {
    try {
        $db = Database::connect();
        
        // Get distinct prefixes from existing dashboard_stats
        $stmt = $db->prepare("SELECT DISTINCT company_prefix FROM dashboard_stats WHERE company_prefix IS NOT NULL AND company_prefix != ''");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $results ?: ['BKC']; // Default to BKC if no prefixes found
        
    } catch (Exception $e) {
        echo "Warning: Could not get company prefixes: " . $e->getMessage() . "\n";
        return ['BKC']; // Default fallback
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    runFinanceETL();
}
?>
