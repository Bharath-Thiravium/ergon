<?php

namespace Ergon\FinanceSync\Tests;

use PHPUnit\Framework\TestCase;
use Ergon\FinanceSync\CashflowService;
use Ergon\FinanceSync\SourceRepo;
use Ergon\FinanceSync\TargetRepo;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PDO;

class CashflowTest extends TestCase
{
    private PDO $sourcePdo;
    private PDO $targetPdo;
    private Logger $logger;
    private CashflowService $cashflowService;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite databases for testing
        $this->sourcePdo = new PDO('sqlite::memory:');
        $this->targetPdo = new PDO('sqlite::memory:');
        
        $this->sourcePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->targetPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create null logger for tests
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
        
        $this->setupTestTables();
        $this->insertTestData();
        
        // Create service
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        $config = [
            'batch_size' => 10,
            'sync_table' => 'sync_metadata'
        ];
        
        $this->cashflowService = new CashflowService($sourceRepo, $targetRepo, $this->logger, $config);
        
        // Set environment variable for PO statuses
        $_ENV['CASHFLOW_ACTIVE_PO_STATUSES'] = 'Active,Released,Approved';
    }
    
    public function testExpectedInflowComputation(): void
    {
        // Test data has:
        // Invoice 1: 10000 total, 3000 paid = 7000 outstanding
        // Invoice 2: 5000 total, 5000 paid = 0 outstanding (fully paid)
        // Invoice 3: 8000 total, 2000 paid = 6000 outstanding
        // Expected total inflow: 7000 + 0 + 6000 = 13000
        
        $exitCode = $this->cashflowService->computeCashflow('TEST', true);
        
        $this->assertEquals(0, $exitCode);
        
        // Verify dashboard stats
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        $stats = $targetRepo->getDashboardStats('TEST');
        
        $this->assertNotNull($stats);
        $this->assertEquals('13000.00', $stats['expected_inflow']);
    }
    
    public function testPoCommitmentsComputation(): void
    {
        // Test data has:
        // PO 1: 15000 value, Active status = included
        // PO 2: 8000 value, Cancelled status = excluded
        // PO 3: 12000 value, Released status = included
        // Expected total commitments: 15000 + 12000 = 27000
        
        $exitCode = $this->cashflowService->computeCashflow('TEST', true);
        
        $this->assertEquals(0, $exitCode);
        
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        $stats = $targetRepo->getDashboardStats('TEST');
        
        $this->assertNotNull($stats);
        $this->assertEquals('27000.00', $stats['po_commitments']);
    }
    
    public function testNetCashFlowComputation(): void
    {
        // Expected inflow: 13000
        // PO commitments: 27000
        // Net cash flow: 13000 - 27000 = -14000
        
        $exitCode = $this->cashflowService->computeCashflow('TEST', true);
        
        $this->assertEquals(0, $exitCode);
        
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        $stats = $targetRepo->getDashboardStats('TEST');
        
        $this->assertNotNull($stats);
        $this->assertEquals('13000.00', $stats['expected_inflow']);
        $this->assertEquals('27000.00', $stats['po_commitments']);
        $this->assertEquals('-14000.00', $stats['net_cash_flow']);
    }
    
    public function testZeroOutstandingInvoicesExcluded(): void
    {
        // Insert additional fully paid invoice
        $this->sourcePdo->exec("
            INSERT INTO finance_invoices (
                invoice_number, total_amount, amount_paid, updated_at
            ) VALUES (
                'TEST004', 3000.00, 3000.00, '2024-01-04 10:00:00'
            )
        ");
        
        $exitCode = $this->cashflowService->computeCashflow('TEST', true);
        
        $this->assertEquals(0, $exitCode);
        
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        $stats = $targetRepo->getDashboardStats('TEST');
        
        // Should still be 13000 (fully paid invoice not included)
        $this->assertEquals('13000.00', $stats['expected_inflow']);
    }
    
    public function testInactivePoStatusesExcluded(): void
    {
        // Insert additional PO with inactive status
        $this->sourcePdo->exec("
            INSERT INTO finance_purchase_orders (
                po_number, po_total_value, po_status, updated_at
            ) VALUES (
                'TEST-PO004', 5000.00, 'Draft', '2024-01-04 10:00:00'
            )
        ");
        
        $exitCode = $this->cashflowService->computeCashflow('TEST', true);
        
        $this->assertEquals(0, $exitCode);
        
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        $stats = $targetRepo->getDashboardStats('TEST');
        
        // Should still be 27000 (Draft status not included)
        $this->assertEquals('27000.00', $stats['po_commitments']);
    }
    
    private function setupTestTables(): void
    {
        // Source tables
        $this->sourcePdo->exec("
            CREATE TABLE finance_invoices (
                invoice_number VARCHAR(100) PRIMARY KEY,
                total_amount DECIMAL(15,2) DEFAULT 0,
                amount_paid DECIMAL(15,2) DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->sourcePdo->exec("
            CREATE TABLE finance_purchase_orders (
                po_number VARCHAR(100) PRIMARY KEY,
                po_total_value DECIMAL(15,2) DEFAULT 0,
                po_status VARCHAR(20) DEFAULT 'Draft',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Target tables
        $this->targetPdo->exec("
            CREATE TABLE dashboard_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_prefix VARCHAR(10) NOT NULL,
                expected_inflow DECIMAL(18,2) DEFAULT 0.00,
                po_commitments DECIMAL(18,2) DEFAULT 0.00,
                net_cash_flow DECIMAL(18,2) DEFAULT 0.00,
                last_computed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(company_prefix)
            )
        ");
        
        $this->targetPdo->exec("
            CREATE TABLE sync_metadata (
                company_prefix VARCHAR(10) PRIMARY KEY,
                last_sync_invoices TIMESTAMP,
                last_sync_activities TIMESTAMP,
                last_sync_cashflow TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function insertTestData(): void
    {
        // Insert test invoices
        $this->sourcePdo->exec("
            INSERT INTO finance_invoices (
                invoice_number, total_amount, amount_paid, updated_at
            ) VALUES
            ('TEST001', 10000.00, 3000.00, '2024-01-01 10:00:00'),
            ('TEST002', 5000.00, 5000.00, '2024-01-02 10:00:00'),
            ('TEST003', 8000.00, 2000.00, '2024-01-03 10:00:00')
        ");
        
        // Insert test purchase orders
        $this->sourcePdo->exec("
            INSERT INTO finance_purchase_orders (
                po_number, po_total_value, po_status, updated_at
            ) VALUES
            ('TEST-PO001', 15000.00, 'Active', '2024-01-01 11:00:00'),
            ('TEST-PO002', 8000.00, 'Cancelled', '2024-01-02 11:00:00'),
            ('TEST-PO003', 12000.00, 'Released', '2024-01-03 11:00:00')
        ");
    }
}