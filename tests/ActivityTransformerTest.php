<?php

namespace Ergon\FinanceSync\Tests;

use PHPUnit\Framework\TestCase;
use Ergon\FinanceSync\ActivityTransformer;

class ActivityTransformerTest extends TestCase
{
    public function testTransformQuotationRow(): void
    {
        $pgRow = [
            'quotation_number' => 'ERGN-Q001',
            'customer_id' => 'CUST001',
            'customer_name' => 'Test Customer',
            'customer_gstin' => '29ABCDE1234F1Z5',
            'quotation_amount' => '5000.00',
            'quotation_status' => 'pending'
        ];
        
        $result = ActivityTransformer::transformQuotationRow($pgRow, 'ERGN');
        
        $this->assertEquals('quotation', $result['record_type']);
        $this->assertEquals('ERGN-Q001', $result['document_number']);
        $this->assertEquals('CUST001', $result['customer_id']);
        $this->assertEquals('Test Customer', $result['customer_name']);
        $this->assertEquals(5000.00, $result['amount']);
        $this->assertEquals(0.0, $result['taxable_amount']);
        $this->assertEquals(0.0, $result['amount_paid']);
        $this->assertEquals(0.0, $result['outstanding_amount']);
        $this->assertEquals(0.0, $result['igst']);
        $this->assertEquals('pending', $result['status']);
        $this->assertEquals('ERGN', $result['company_prefix']);
        $this->assertJson($result['raw_data']);
    }
    
    public function testTransformPurchaseOrderRow(): void
    {
        $pgRow = [
            'po_number' => 'ERGN-PO001',
            'customer_id' => 'CUST002',
            'customer_name' => 'Supplier ABC',
            'po_amount' => '15000.00',
            'po_status' => 'approved'
        ];
        
        $result = ActivityTransformer::transformPurchaseOrderRow($pgRow, 'ERGN');
        
        $this->assertEquals('purchase_order', $result['record_type']);
        $this->assertEquals('ERGN-PO001', $result['document_number']);
        $this->assertEquals('CUST002', $result['customer_id']);
        $this->assertEquals('Supplier ABC', $result['customer_name']);
        $this->assertEquals(15000.00, $result['amount']);
        $this->assertEquals(0.0, $result['outstanding_amount']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('ERGN', $result['company_prefix']);
    }
    
    public function testTransformInvoiceActivityRow(): void
    {
        $pgRow = [
            'invoice_number' => 'ERGN-INV001',
            'customer_id' => 'CUST003',
            'customer_name' => 'Invoice Customer',
            'total_amount' => '10000.00',
            'taxable_amount' => '8474.58',
            'amount_paid' => '5000.00',
            'igst_amount' => '1525.42',
            'due_date' => '2024-01-15',
            'invoice_date' => '2024-01-01',
            'status' => 'pending'
        ];
        
        $result = ActivityTransformer::transformInvoiceActivityRow($pgRow, 'ERGN');
        
        $this->assertEquals('invoice', $result['record_type']);
        $this->assertEquals('ERGN-INV001', $result['document_number']);
        $this->assertEquals(3474.58, $result['outstanding_amount']);
        $this->assertEquals(1525.42, $result['igst']);
        $this->assertEquals('2024-01-15', $result['due_date']);
        $this->assertEquals('ERGN', $result['company_prefix']);
    }
    
    public function testTransformPaymentRow(): void
    {
        $pgRow = [
            'payment_id' => 'PAY001',
            'customer_id' => 'CUST004',
            'customer_name' => 'Payment Customer',
            'amount' => '2500.00',
            'payment_status' => 'completed'
        ];
        
        $result = ActivityTransformer::transformPaymentRow($pgRow, 'ERGN');
        
        $this->assertEquals('payment', $result['record_type']);
        $this->assertEquals('PAY001', $result['document_number']);
        $this->assertEquals(2500.00, $result['amount']);
        $this->assertEquals(2500.00, $result['amount_paid']);
        $this->assertEquals(0.0, $result['outstanding_amount']);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('ERGN', $result['company_prefix']);
    }
    
    public function testQuotationWithMissingCustomerName(): void
    {
        $pgRow = [
            'quotation_number' => 'ERGN-Q002',
            'customer_id' => 'CUST005',
            'customer_name' => null,
            'quotation_amount' => '3000.00'
        ];
        
        $result = ActivityTransformer::transformQuotationRow($pgRow, 'ERGN');
        
        $this->assertEquals('CUST005', $result['customer_name']);
    }
    
    public function testInvoiceWithOverdueStatus(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $pgRow = [
            'invoice_number' => 'ERGN-INV002',
            'customer_id' => 'CUST007',
            'customer_name' => 'Overdue Customer',
            'taxable_amount' => '5000.00',
            'amount_paid' => '2000.00',
            'due_date' => $yesterday,
            'status' => 'pending'
        ];
        
        $result = ActivityTransformer::transformInvoiceActivityRow($pgRow, 'ERGN');
        
        $this->assertEquals('overdue', $result['status']);
        $this->assertEquals(3000.00, $result['outstanding_amount']);
    }
}