<?php

namespace Ergon\FinanceSync\Tests;

use PHPUnit\Framework\TestCase;
use PDO;
use Ergon\FinanceSync\SourceRepo;
use Ergon\FinanceSync\TargetRepo;
use Ergon\FinanceSync\SyncService;
use Monolog\Logger;
use Monolog\Handler\NullHandler;

class IntegrationTest extends TestCase
{
    private PDO $sourcePdo;
    private PDO $targetPdo;
    private Logger $logger;
    
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
        
        $this->setupSourceTables();
        $this->setupTargetTables();
        $this->insertTestData();
    }
    
    public function testFullSyncIntegration(): void
    {
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        $config = [
            'batch_size' => 10,
            'sync_table' => 'sync_metadata'
        ];
        
        $syncService = new SyncService($sourceRepo, $targetRepo, $this->logger, $config);
        
        // Execute full sync
        $exitCode = $syncService->sync('TEST', true, null);
        
        $this->assertEquals(0, $exitCode);
        
        // Verify data was synced
        $stmt = $this->targetPdo->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE company_prefix = 'TEST'");
        $result = $stmt->fetch();
        $this->assertEquals(3, $result['count']);
        
        // Verify specific transformations
        $stmt = $this->targetPdo->prepare("SELECT * FROM finance_consolidated WHERE document_number = ?");
        $stmt->execute(['TEST001']);
        $row = $stmt->fetch();
        
        $this->assertEquals('invoice', $row['record_type']);
        $this->assertEquals('TEST001', $row['document_number']);
        $this->assertEquals('CUST001', $row['customer_id']);
        $this->assertEquals('Test Customer 1', $row['customer_name']);
        $this->assertEquals(500.00, $row['outstanding_amount']); // 1000 - 500
        $this->assertEquals('pending', $row['status']);
        $this->assertEquals('TEST', $row['company_prefix']);
        
        // Verify sync run was logged
        $stmt = $this->targetPdo->query("SELECT * FROM sync_runs WHERE company_prefix = 'TEST'");
        $syncRun = $stmt->fetch();
        $this->assertEquals('success', $syncRun['status']);
        $this->assertEquals(3, $syncRun['rows_fetched']);
        $this->assertEquals(3, $syncRun['rows_upserted']);
        $this->assertEquals(0, $syncRun['errors_count']);
    }
    
    public function testActivitiesSync(): void
    {
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        // Insert test activities data
        $this->insertTestActivitiesData();
        
        // Test quotation sync
        $quotations = $sourceRepo->fetchQuotationRows('TEST', true, null, null);
        $this->assertCount(2, $quotations);
        
        // Test purchase order sync
        $pos = $sourceRepo->fetchPurchaseOrderRows('TEST', true, null, null);
        $this->assertCount(1, $pos);
        
        // Test payment sync
        $payments = $sourceRepo->fetchPaymentRows('TEST', true, null, null);
        $this->assertCount(1, $payments);
        
        // Transform and verify quotation
        $quotationRow = ActivityTransformer::transformQuotationRow($quotations[0], 'TEST');
        $this->assertEquals('quotation', $quotationRow['record_type']);
        $this->assertEquals(0.0, $quotationRow['outstanding_amount']);
        
        // Transform and verify payment
        $paymentRow = ActivityTransformer::transformPaymentRow($payments[0], 'TEST');
        $this->assertEquals('payment', $paymentRow['record_type']);
        $this->assertEquals($paymentRow['amount'], $paymentRow['amount_paid']);
        $this->assertEquals(0.0, $paymentRow['outstanding_amount']);
    }
    
    public function testIncrementalSync(): void
    {
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        $config = [
            'batch_size' => 10,
            'sync_table' => 'sync_metadata'
        ];
        
        $syncService = new SyncService($sourceRepo, $targetRepo, $this->logger, $config);
        
        // First sync - should get all records
        $exitCode = $syncService->sync('TEST', false, null);
        $this->assertEquals(0, $exitCode);
        
        // Add new record with future timestamp
        $futureTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->sourcePdo->exec("
            INSERT INTO finance_invoices (
                invoice_number, customer_id, total_amount, taxable_amount, 
                amount_paid, status, invoice_date, updated_at
            ) VALUES (
                'TEST004', 'CUST004', 2000.00, 1800.00, 
                0.00, 'pending', '2024-01-04', '{$futureTime}'
            )
        ");
        
        // Second sync - should only get new record
        $exitCode = $syncService->sync('TEST', false, null);
        $this->assertEquals(0, $exitCode);
        
        // Verify total count is now 4
        $stmt = $this->targetPdo->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE company_prefix = 'TEST'");
        $result = $stmt->fetch();
        $this->assertEquals(4, $result['count']);
    }
    
    public function testLimitedSync(): void
    {
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        $config = [
            'batch_size' => 10,
            'sync_table' => 'sync_metadata'
        ];
        
        $syncService = new SyncService($sourceRepo, $targetRepo, $this->logger, $config);
        
        // Sync with limit of 2
        $exitCode = $syncService->sync('TEST', true, 2);
        $this->assertEquals(0, $exitCode);
        
        // Should only have 2 records
        $stmt = $this->targetPdo->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE company_prefix = 'TEST'");
        $result = $stmt->fetch();
        $this->assertEquals(2, $result['count']);
    }
    
    public function testUpsertBehavior(): void
    {
        $sourceRepo = new SourceRepo($this->sourcePdo, $this->logger);
        $targetRepo = new TargetRepo($this->targetPdo, $this->logger);
        
        $config = [
            'batch_size' => 10,
            'sync_table' => 'sync_metadata'
        ];
        
        $syncService = new SyncService($sourceRepo, $targetRepo, $this->logger, $config);
        
        // First sync
        $exitCode = $syncService->sync('TEST', true, null);
        $this->assertEquals(0, $exitCode);
        
        // Update source data
        $this->sourcePdo->exec("
            UPDATE finance_invoices 
            SET amount_paid = 1000.00, status = 'paid' 
            WHERE invoice_number = 'TEST001'
        ");
        
        // Second sync should update existing record
        $exitCode = $syncService->sync('TEST', true, null);
        $this->assertEquals(0, $exitCode);
        
        // Verify update
        $stmt = $this->targetPdo->prepare("SELECT * FROM finance_consolidated WHERE document_number = ?");
        $stmt->execute(['TEST001']);
        $row = $stmt->fetch();
        
        $this->assertEquals(1000.00, $row['amount_paid']);
        $this->assertEquals(0.00, $row['outstanding_amount']); // Should be 0 now
        $this->assertEquals('paid', $row['status']); // Should be updated to paid
        
        // Should still have only 3 records (upsert, not insert)
        $stmt = $this->targetPdo->query("SELECT COUNT(*) as count FROM finance_consolidated WHERE company_prefix = 'TEST'");
        $result = $stmt->fetch();
        $this->assertEquals(3, $result['count']);
    }
    
    private function setupSourceTables(): void
    {
        // Create PostgreSQL-like tables in SQLite
        $this->sourcePdo->exec("
            CREATE TABLE finance_invoices (
                invoice_number VARCHAR(100) PRIMARY KEY,
                customer_id VARCHAR(50) NOT NULL,
                total_amount DECIMAL(15,2) DEFAULT 0,
                taxable_amount DECIMAL(15,2) DEFAULT 0,
                amount_paid DECIMAL(15,2) DEFAULT 0,
                igst_amount DECIMAL(15,2) DEFAULT 0,
                cgst_amount DECIMAL(15,2) DEFAULT 0,
                sgst_amount DECIMAL(15,2) DEFAULT 0,
                due_date DATE,
                invoice_date DATE,
                status VARCHAR(20) DEFAULT 'pending',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->sourcePdo->exec("
            CREATE TABLE finance_customers (
                customer_id VARCHAR(50) PRIMARY KEY,
                customer_name VARCHAR(255),
                customer_gstin VARCHAR(15)
            )
        ");
        
        $this->sourcePdo->exec("
            CREATE TABLE finance_quotations (
                quotation_number VARCHAR(100) PRIMARY KEY,
                customer_id VARCHAR(50) NOT NULL,
                quotation_amount DECIMAL(15,2) DEFAULT 0,
                quotation_status VARCHAR(20) DEFAULT 'pending',
                quotation_date DATE,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->sourcePdo->exec("
            CREATE TABLE finance_purchase_orders (
                po_number VARCHAR(100) PRIMARY KEY,
                customer_id VARCHAR(50) NOT NULL,
                po_amount DECIMAL(15,2) DEFAULT 0,
                po_status VARCHAR(20) DEFAULT 'pending',
                po_date DATE,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->sourcePdo->exec("
            CREATE TABLE finance_payments (
                payment_id VARCHAR(100) PRIMARY KEY,
                customer_id VARCHAR(50) NOT NULL,
                amount DECIMAL(15,2) DEFAULT 0,
                payment_status VARCHAR(20) DEFAULT 'completed',
                payment_date DATE,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function setupTargetTables(): void
    {
        // Adapt MySQL schema for SQLite
        $this->targetPdo->exec("
            CREATE TABLE finance_consolidated (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                record_type VARCHAR(20) NOT NULL DEFAULT 'invoice',
                document_number VARCHAR(100) NOT NULL,
                customer_id VARCHAR(50) NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_gstin VARCHAR(15),
                amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                taxable_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                amount_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                outstanding_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                igst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                cgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                sgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                due_date DATE,
                invoice_date DATE,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                company_prefix VARCHAR(10) NOT NULL,
                raw_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(company_prefix, document_number)
            )
        ");
        
        $this->targetPdo->exec("
            CREATE TABLE sync_metadata (
                company_prefix VARCHAR(10) PRIMARY KEY,
                last_sync_invoices TIMESTAMP,
                last_sync_activities TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->targetPdo->exec("
            CREATE TABLE sync_runs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_prefix VARCHAR(10) NOT NULL,
                started_at TIMESTAMP NOT NULL,
                ended_at TIMESTAMP NOT NULL,
                rows_fetched INTEGER NOT NULL DEFAULT 0,
                rows_upserted INTEGER NOT NULL DEFAULT 0,
                errors_count INTEGER NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->targetPdo->exec("
            CREATE TABLE sync_errors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                document_number VARCHAR(100) NOT NULL,
                company_prefix VARCHAR(10) NOT NULL,
                error_type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                raw_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function insertTestData(): void
    {
        // Insert test customers
        $this->sourcePdo->exec("
            INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES
            ('CUST001', 'Test Customer 1', '29ABCDE1234F1Z1'),
            ('CUST002', 'Test Customer 2', '29ABCDE1234F1Z2'),
            ('CUST003', 'Test Customer 3', '29ABCDE1234F1Z3')
        ");
        
        // Insert test invoices
        $this->sourcePdo->exec("
            INSERT INTO finance_invoices (
                invoice_number, customer_id, total_amount, taxable_amount, 
                amount_paid, igst_amount, due_date, invoice_date, status, updated_at
            ) VALUES
            ('TEST001', 'CUST001', 1000.00, 1000.00, 500.00, 180.00, '2024-01-15', '2024-01-01', 'pending', '2024-01-01 10:00:00'),
            ('TEST002', 'CUST002', 2000.00, 2000.00, 2000.00, 360.00, '2024-01-20', '2024-01-02', 'paid', '2024-01-02 10:00:00'),
            ('TEST003', 'CUST003', 1500.00, 1500.00, 0.00, 270.00, '2023-12-31', '2023-12-15', 'pending', '2024-01-03 10:00:00')
        ");
    }
    
    private function insertTestActivitiesData(): void
    {
        // Insert test quotations
        $this->sourcePdo->exec("
            INSERT INTO finance_quotations (
                quotation_number, customer_id, quotation_amount, quotation_status, quotation_date, updated_at
            ) VALUES
            ('TEST-Q001', 'CUST001', 5000.00, 'pending', '2024-01-01', '2024-01-01 09:00:00'),
            ('TEST-Q002', 'CUST002', 3000.00, 'approved', '2024-01-02', '2024-01-02 09:00:00')
        ");
        
        // Insert test purchase orders
        $this->sourcePdo->exec("
            INSERT INTO finance_purchase_orders (
                po_number, customer_id, po_amount, po_status, po_date, updated_at
            ) VALUES
            ('TEST-PO001', 'CUST001', 15000.00, 'approved', '2024-01-01', '2024-01-01 11:00:00')
        ");
        
        // Insert test payments
        $this->sourcePdo->exec("
            INSERT INTO finance_payments (
                payment_id, customer_id, amount, payment_status, payment_date, updated_at
            ) VALUES
            ('TEST-PAY001', 'CUST001', 2500.00, 'completed', '2024-01-01', '2024-01-01 15:00:00')
        ");
    }
}