#!/usr/bin/env php
<?php

require_once __DIR__ . '/../bootstrap.php';

use Ergon\FinanceSync\SourceRepo;
use Ergon\FinanceSync\TargetRepo;
use Ergon\FinanceSync\CashflowService;

// Parse command line arguments
$options = getopt('', ['prefix:', 'full', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

// Get configuration
$config = getConfig();

// Override prefix from command line
$companyPrefix = $options['prefix'] ?? $config['company_prefix'];
$fullLoad = isset($options['full']);

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
    
    // Create cashflow service
    $cashflowService = new CashflowService($sourceRepo, $targetRepo, $logger, $config);
    
    // Execute cashflow computation
    echo "Starting cashflow computation for prefix: {$companyPrefix}\n";
    echo "Mode: " . ($fullLoad ? "Full Load" : "Incremental") . "\n";
    echo "Active PO Statuses: " . ($_ENV['CASHFLOW_ACTIVE_PO_STATUSES'] ?? 'Active,Released,Approved') . "\n";
    echo "----------------------------------------\n";
    
    $exitCode = $cashflowService->computeCashflow($companyPrefix, $fullLoad);
    
    echo "----------------------------------------\n";
    echo "Cashflow computation completed with exit code: {$exitCode}\n";
    
    if ($exitCode === 0) {
        // Display computed values
        $stats = $targetRepo->getDashboardStats($companyPrefix);
        if ($stats) {
            echo "✓ Success: Cash flow projections computed\n";
            echo "Expected Inflow: " . number_format($stats['expected_inflow'], 2) . "\n";
            echo "PO Commitments: " . number_format($stats['po_commitments'], 2) . "\n";
            echo "Net Cash Flow: " . number_format($stats['net_cash_flow'], 2) . "\n";
            echo "Last Computed: " . $stats['last_computed_at'] . "\n";
        } else {
            echo "✓ Success: Computation completed but no stats found\n";
        }
    } else {
        echo "✗ Failure: Cashflow computation failed (check logs)\n";
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
    echo "Finance Cashflow Computation Tool\n";
    echo "================================\n\n";
    echo "Usage: php compute_cashflow.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --prefix=PREFIX    Company prefix (e.g., ERGN). Required if not in .env\n";
    echo "  --full             Force full computation (ignore last sync timestamp)\n";
    echo "  --help             Show this help message\n\n";
    echo "Examples:\n";
    echo "  php compute_cashflow.php --prefix=ERGN\n";
    echo "  php compute_cashflow.php --prefix=ERGN --full\n\n";
    echo "Computations:\n";
    echo "  Expected Inflow = SUM(outstanding amounts from invoices)\n";
    echo "  PO Commitments = SUM(active purchase order values)\n";
    echo "  Net Cash Flow = Expected Inflow - PO Commitments\n\n";
    echo "Exit Codes:\n";
    echo "  0 = Success\n";
    echo "  2 = Fatal failure\n\n";
}