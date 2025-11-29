#!/usr/bin/env php
<?php

require_once __DIR__ . '/../bootstrap.php';

use Ergon\FinanceSync\SourceRepo;
use Ergon\FinanceSync\TargetRepo;
use Ergon\FinanceSync\ActivityTransformer;

// Parse command line arguments
$options = getopt('', ['prefix:', 'full', 'limit:', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Get configuration
$config = getConfig();

// Override prefix from command line
$companyPrefix = $options['prefix'] ?? $config['company_prefix'];
$fullLoad = isset($options['full']);
$limit = isset($options['limit']) ? (int)$options['limit'] : null;

// Validate required parameters
if (empty($companyPrefix)) {
    echo "Error: Company prefix is required. Use --prefix=ERGN or set COMPANY_PREFIX in .env\n";
    exit(1);
}

try {
    // Create connections and logger
    $pgConnection = createPostgresConnection();
    $mysqlConnection = createMysqlConnection();
    $logger = createLogger();
    
    // Create repositories
    $sourceRepo = new SourceRepo($pgConnection, $logger);
    $targetRepo = new TargetRepo($mysqlConnection, $logger);
    
    // Execute activities sync
    echo "Starting activities sync for prefix: {$companyPrefix}\n";
    echo "Mode: " . ($fullLoad ? "Full Load" : "Incremental") . "\n";
    if ($limit) {
        echo "Limit: {$limit} rows per activity type\n";
    }
    echo "Batch size: {$config['batch_size']}\n";
    echo "----------------------------------------\n";
    
    $exitCode = syncActivities($sourceRepo, $targetRepo, $logger, $config, $companyPrefix, $fullLoad, $limit);
    
    echo "----------------------------------------\n";
    echo "Activities sync completed with exit code: {$exitCode}\n";
    
    if ($exitCode === 0) {
        echo "✓ Success: All activities processed successfully\n";
    } elseif ($exitCode === 1) {
        echo "⚠ Partial Success: Some activities had errors (check sync_errors table)\n";
    } else {
        echo "✗ Failure: Activities sync failed (check logs)\n";
    }
    
    exit($exitCode);
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    if (isset($logger)) {
        $logger->error("CLI Fatal Error: " . $e->getMessage());
    }
    exit(2);
}

function syncActivities($sourceRepo, $targetRepo, $logger, $config, $companyPrefix, $fullLoad, $limit): int
{
    $startedAt = date('Y-m-d H:i:s');
    $totalFetched = 0;
    $totalUpserted = 0;
    $totalErrors = 0;
    
    try {
        // Create tables if needed
        $targetRepo->createTablesIfNotExist();
        
        // Get last sync timestamp for incremental sync
        $lastSyncAt = null;
        if (!$fullLoad) {
            $lastSyncAt = $targetRepo->getLastSyncTimestamp(
                $companyPrefix,
                $config['sync_table'],
                'activities'
            );
        }
        
        // Activity types to sync
        $activityTypes = [
            'quotations' => 'transformQuotationRow',
            'purchase_orders' => 'transformPurchaseOrderRow',
            'invoices' => 'transformInvoiceActivityRow',
            'payments' => 'transformPaymentRow'
        ];
        
        $maxTimestamp = null;
        
        foreach ($activityTypes as $type => $transformMethod) {
            echo "Processing {$type}...\n";
            
            // Fetch method name
            $fetchMethod = 'fetch' . str_replace('_', '', ucwords($type, '_')) . 'Rows';
            
            // Process in batches
            $batchSize = $config['batch_size'];
            $currentLimit = $limit;
            
            do {
                $rows = $sourceRepo->$fetchMethod(
                    $companyPrefix,
                    $fullLoad,
                    $lastSyncAt,
                    $currentLimit
                );
                
                if (empty($rows)) {
                    break;
                }
                
                $totalFetched += count($rows);
                
                // Transform rows
                $transformedRows = [];
                foreach ($rows as $row) {
                    try {
                        $transformedRows[] = ActivityTransformer::$transformMethod($row, $companyPrefix);
                    } catch (Exception $e) {
                        $logger->error("Transform failed for {$type} row", [
                            'document_number' => $row['quotation_number'] ?? $row['po_number'] ?? $row['invoice_number'] ?? $row['payment_id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        $totalErrors++;
                    }
                }
                
                // Upsert batch
                if (!empty($transformedRows)) {
                    $result = $targetRepo->upsertBatch($transformedRows, $companyPrefix);
                    $totalUpserted += $result['upserted'];
                    $totalErrors += count($result['errors']);
                }
                
                // Track max timestamp
                $batchMaxTimestamp = $sourceRepo->getMaxTimestamp($rows);
                if ($batchMaxTimestamp && ($maxTimestamp === null || $batchMaxTimestamp > $maxTimestamp)) {
                    $maxTimestamp = $batchMaxTimestamp;
                }
                
                // Adjust limit for next batch
                if ($currentLimit) {
                    $currentLimit -= count($rows);
                    if ($currentLimit <= 0) {
                        break;
                    }
                }
                
            } while (count($rows) === $batchSize);
            
            echo "Completed {$type}: " . count($transformedRows ?? []) . " rows\n";
        }
        
        // Update last sync timestamp if we have new data
        if ($maxTimestamp && !$fullLoad) {
            $targetRepo->updateLastSyncTimestamp(
                $companyPrefix,
                $maxTimestamp,
                $config['sync_table'],
                'activities'
            );
        }
        
        $endedAt = date('Y-m-d H:i:s');
        $status = $totalErrors > 0 ? 'partial_failure' : 'success';
        
        // Log sync run
        $targetRepo->logSyncRun(
            $companyPrefix,
            $startedAt,
            $endedAt,
            $totalFetched,
            $totalUpserted,
            $totalErrors,
            $status
        );
        
        $logger->info("Activities sync completed", [
            'company_prefix' => $companyPrefix,
            'status' => $status,
            'fetched' => $totalFetched,
            'upserted' => $totalUpserted,
            'errors' => $totalErrors,
            'duration' => strtotime($endedAt) - strtotime($startedAt) . 's'
        ]);
        
        return $totalErrors > 0 ? 1 : 0;
        
    } catch (Exception $e) {
        $endedAt = date('Y-m-d H:i:s');
        
        $logger->error("Activities sync failed", [
            'company_prefix' => $companyPrefix,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Log failed run
        $targetRepo->logSyncRun(
            $companyPrefix,
            $startedAt,
            $endedAt,
            $totalFetched,
            $totalUpserted,
            $totalErrors,
            'failure'
        );
        
        return 2; // Fatal error exit code
    }
}

function showHelp(): void
{
    echo "Finance Activities Sync Tool\n";
    echo "===========================\n\n";
    echo "Usage: php sync_activities.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --prefix=PREFIX    Company prefix (e.g., ERGN). Required if not in .env\n";
    echo "  --full             Force full load (ignore last sync timestamp)\n";
    echo "  --limit=N          Limit number of rows per activity type (for testing)\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php sync_activities.php --prefix=ERGN\n";
    echo "  php sync_activities.php --prefix=ERGN --full\n";
    echo "  php sync_activities.php --prefix=ERGN --limit=50\n\n";
    echo "Activity Types Synced:\n";
    echo "  📝 Quotations\n";
    echo "  🛒 Purchase Orders\n";
    echo "  💰 Invoices\n";
    echo "  💳 Payments\n\n";
    echo "Exit Codes:\n";
    echo "  0 = Success\n";
    echo "  1 = Partial success (some row errors)\n";
    echo "  2 = Fatal failure\n\n";
}