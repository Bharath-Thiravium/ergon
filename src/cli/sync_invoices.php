#!/usr/bin/env php
<?php

require_once __DIR__ . '/../bootstrap.php';

use Ergon\FinanceSync\SourceRepo;
use Ergon\FinanceSync\TargetRepo;
use Ergon\FinanceSync\SyncService;

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
    
    // Create sync service
    $syncService = new SyncService($sourceRepo, $targetRepo, $logger, $config);
    
    // Execute sync
    echo "Starting sync for prefix: {$companyPrefix}\n";
    echo "Mode: " . ($fullLoad ? "Full Load" : "Incremental") . "\n";
    if ($limit) {
        echo "Limit: {$limit} rows\n";
    }
    echo "Batch size: {$config['batch_size']}\n";
    echo "----------------------------------------\n";
    
    $exitCode = $syncService->sync($companyPrefix, $fullLoad, $limit);
    
    echo "----------------------------------------\n";
    echo "Sync completed with exit code: {$exitCode}\n";
    
    if ($exitCode === 0) {
        echo "✓ Success: All rows processed successfully\n";
    } elseif ($exitCode === 1) {
        echo "⚠ Partial Success: Some rows had errors (check sync_errors table)\n";
    } else {
        echo "✗ Failure: Sync failed (check logs)\n";
    }
    
    exit($exitCode);
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    if (isset($logger)) {
        $logger->error("CLI Fatal Error: " . $e->getMessage());
    }
    exit(2);
}

function showHelp(): void
{
    echo "Finance Invoice Sync Tool\n";
    echo "========================\n\n";
    echo "Usage: php sync_invoices.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --prefix=PREFIX    Company prefix (e.g., ERGN). Required if not in .env\n";
    echo "  --full             Force full load (ignore last sync timestamp)\n";
    echo "  --limit=N          Limit number of rows to process (for testing)\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php sync_invoices.php --prefix=ERGN\n";
    echo "  php sync_invoices.php --prefix=ERGN --full\n";
    echo "  php sync_invoices.php --prefix=ERGN --limit=100\n\n";
    echo "Exit Codes:\n";
    echo "  0 = Success\n";
    echo "  1 = Partial success (some row errors)\n";
    echo "  2 = Fatal failure\n\n";
}