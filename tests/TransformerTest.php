<?php

namespace Ergon\FinanceSync\Tests;

use PHPUnit\Framework\TestCase;
use Ergon\FinanceSync\Transformer;

class TransformerTest extends TestCase
{
    public function testTransformInvoiceRowBasic(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN001',
            'customer_id' => 'CUST001',
            'customer_name' => 'Test Customer',
            'customer_gstin' => '29ABCDE1234F1Z5',
            'total_amount' => '1000.00',
            'taxable_amount' => '847.46',
            'amount_paid' => '500.00',
            'igst_amount' => '152.54',
            'cgst_amount' => '0.00',
            'sgst_amount' => '0.00',
            'due_date' => '2024-01-15',
            'invoice_date' => '2024-01-01',
            'status' => 'pending'
        ];
        
        $result = Transformer::transformInvoiceRow($pgRow, 'ERGN');
        
        $this->assertEquals('invoice', $result['record_type']);
        $this->assertEquals('ERGN001', $result['document_number']);
        $this->assertEquals('CUST001', $result['customer_id']);
        $this->assertEquals('Test Customer', $result['customer_name']);
        $this->assertEquals('29ABCDE1234F1Z5', $result['customer_gstin']);
        $this->assertEquals(1000.00, $result['amount']);
        $this->assertEquals(847.46, $result['taxable_amount']);
        $this->assertEquals(500.00, $result['amount_paid']);
        $this->assertEqualsWithDelta(347.46, $result['outstanding_amount'], 0.01);
        $this->assertEquals(152.54, $result['igst']);
        $this->assertEquals(0.00, $result['cgst']);
        $this->assertEquals(0.00, $result['sgst']);
        $this->assertEquals('2024-01-15', $result['due_date']);
        $this->assertEquals('2024-01-01', $result['invoice_date']);
        $this->assertEquals('ERGN', $result['company_prefix']);
        $this->assertJson($result['raw_data']);
    }
    
    public function testTransformWithNullValues(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN002',
            'customer_id' => 'CUST002',
            'customer_name' => null, // Should fallback to customer_id
            'taxable_amount' => null, // Should coerce to 0
            'amount_paid' => null, // Should coerce to 0
            'igst_amount' => null, // Should coerce to 0
            'status' => 'pending'
        ];
        
        $result = Transformer::transformInvoiceRow($pgRow, 'ERGN');
        
        $this->assertEquals('CUST002', $result['customer_name']); // Fallback
        $this->assertEquals(0.00, $result['taxable_amount']);
        $this->assertEquals(0.00, $result['amount_paid']);
        $this->assertEquals(0.00, $result['outstanding_amount']);
        $this->assertEquals(0.00, $result['igst']);
    }
    
    public function testComputeOutstanding(): void
    {
        $this->assertEquals(500.0, Transformer::computeOutstanding(1000.0, 500.0));
        $this->assertEquals(0.0, Transformer::computeOutstanding(500.0, 1000.0)); // Negative capped to 0
        $this->assertEquals(0.0, Transformer::computeOutstanding(500.0, 500.0)); // Exact match
    }
    
    public function testComputeDaysOverdue(): void
    {
        // Test with outstanding amount
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $this->assertEquals(1, Transformer::computeDaysOverdue($yesterday, 100.0));
        $this->assertEquals(-1, Transformer::computeDaysOverdue($tomorrow, 100.0));
        
        // Test with no outstanding (should return null)
        $this->assertNull(Transformer::computeDaysOverdue($yesterday, 0.0));
        
        // Test with null due date
        $this->assertNull(Transformer::computeDaysOverdue(null, 100.0));
        
        // Test with invalid date
        $this->assertNull(Transformer::computeDaysOverdue('invalid-date', 100.0));
    }
    
    public function testComputeStatus(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Fully paid should be 'paid'
        $this->assertEquals('paid', Transformer::computeStatus('pending', 0.0, $yesterday));
        
        // Overdue should be 'overdue'
        $this->assertEquals('overdue', Transformer::computeStatus('pending', 100.0, $yesterday));
        
        // Not yet due should keep original status
        $this->assertEquals('pending', Transformer::computeStatus('pending', 100.0, $tomorrow));
        
        // No due date should keep original status
        $this->assertEquals('pending', Transformer::computeStatus('pending', 100.0, null));
    }
    
    public function testTransformWithEmptyCustomerName(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN003',
            'customer_id' => 'CUST003',
            'customer_name' => '', // Empty string should fallback
            'status' => 'pending'
        ];
        
        $result = Transformer::transformInvoiceRow($pgRow, 'ERGN');
        
        $this->assertEquals('CUST003', $result['customer_name']);
    }
    
    public function testTransformWithNegativeOutstanding(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN004',
            'customer_id' => 'CUST004',
            'taxable_amount' => '500.00',
            'amount_paid' => '1000.00', // Paid more than taxable
            'status' => 'pending'
        ];
        
        $result = Transformer::transformInvoiceRow($pgRow, 'ERGN');
        
        $this->assertEquals(0.00, $result['outstanding_amount']); // Capped to 0
        $this->assertEquals('paid', $result['status']); // Status should be 'paid'
    }
    
    public function testTransformWithStringNumbers(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN005',
            'customer_id' => 'CUST005',
            'total_amount' => '1,234.56', // String with comma
            'taxable_amount' => '1000',    // String without decimal
            'amount_paid' => '500.0',      // String with single decimal
            'status' => 'pending'
        ];
        
        $result = Transformer::transformInvoiceRow($pgRow, 'ERGN');
        
        // Should handle string conversion gracefully
        $this->assertIsFloat($result['amount']);
        $this->assertIsFloat($result['taxable_amount']);
        $this->assertIsFloat($result['amount_paid']);
        $this->assertIsFloat($result['outstanding_amount']);
    }
}