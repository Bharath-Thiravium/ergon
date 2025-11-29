<?php

namespace Ergon\FinanceSync;

class ActivityTransformer
{
    /**
     * Transform quotation row to finance_consolidated format
     */
    public static function transformQuotationRow(array $pgRow, string $companyPrefix): array
    {
        $customerName = !empty($pgRow['customer_name']) 
            ? $pgRow['customer_name'] 
            : $pgRow['customer_id'];
        
        $rawData = $pgRow;
        $rawData['_computed'] = [
            'record_type' => 'quotation',
            'outstanding_amount' => 0.0,
            'days_overdue' => null
        ];
        
        return [
            'record_type' => 'quotation',
            'document_number' => $pgRow['quotation_number'],
            'customer_id' => $pgRow['customer_id'],
            'customer_name' => $customerName,
            'customer_gstin' => $pgRow['customer_gstin'] ?? null,
            'amount' => self::coerceToFloat($pgRow['quotation_amount'] ?? 0),
            'taxable_amount' => 0.0,
            'amount_paid' => 0.0,
            'outstanding_amount' => 0.0,
            'igst' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'due_date' => null,
            'invoice_date' => null,
            'status' => $pgRow['quotation_status'] ?? 'pending',
            'company_prefix' => $companyPrefix,
            'raw_data' => json_encode($rawData)
        ];
    }
    
    /**
     * Transform purchase order row to finance_consolidated format
     */
    public static function transformPurchaseOrderRow(array $pgRow, string $companyPrefix): array
    {
        $customerName = !empty($pgRow['customer_name']) 
            ? $pgRow['customer_name'] 
            : $pgRow['customer_id'];
        
        $rawData = $pgRow;
        $rawData['_computed'] = [
            'record_type' => 'purchase_order',
            'outstanding_amount' => 0.0,
            'days_overdue' => null
        ];
        
        return [
            'record_type' => 'purchase_order',
            'document_number' => $pgRow['po_number'],
            'customer_id' => $pgRow['customer_id'],
            'customer_name' => $customerName,
            'customer_gstin' => $pgRow['customer_gstin'] ?? null,
            'amount' => self::coerceToFloat($pgRow['po_amount'] ?? $pgRow['po_total'] ?? 0),
            'taxable_amount' => 0.0,
            'amount_paid' => 0.0,
            'outstanding_amount' => 0.0,
            'igst' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'due_date' => null,
            'invoice_date' => null,
            'status' => $pgRow['po_status'] ?? 'pending',
            'company_prefix' => $companyPrefix,
            'raw_data' => json_encode($rawData)
        ];
    }
    
    /**
     * Transform invoice activity row to finance_consolidated format
     */
    public static function transformInvoiceActivityRow(array $pgRow, string $companyPrefix): array
    {
        $taxableAmount = self::coerceToFloat($pgRow['taxable_amount'] ?? 0);
        $amountPaid = self::coerceToFloat($pgRow['amount_paid'] ?? 0);
        $outstanding = max(0, $taxableAmount - $amountPaid);
        
        $customerName = !empty($pgRow['customer_name']) 
            ? $pgRow['customer_name'] 
            : $pgRow['customer_id'];
        
        $rawData = $pgRow;
        $rawData['_computed'] = [
            'record_type' => 'invoice',
            'outstanding_amount' => $outstanding,
            'days_overdue' => self::computeDaysOverdue($pgRow['due_date'] ?? null, $outstanding)
        ];
        
        return [
            'record_type' => 'invoice',
            'document_number' => $pgRow['invoice_number'],
            'customer_id' => $pgRow['customer_id'],
            'customer_name' => $customerName,
            'customer_gstin' => $pgRow['customer_gstin'] ?? null,
            'amount' => self::coerceToFloat($pgRow['total_amount'] ?? 0),
            'taxable_amount' => $taxableAmount,
            'amount_paid' => $amountPaid,
            'outstanding_amount' => $outstanding,
            'igst' => self::coerceToFloat($pgRow['igst_amount'] ?? 0),
            'cgst' => self::coerceToFloat($pgRow['cgst_amount'] ?? 0),
            'sgst' => self::coerceToFloat($pgRow['sgst_amount'] ?? 0),
            'due_date' => $pgRow['due_date'] ?? null,
            'invoice_date' => $pgRow['invoice_date'] ?? null,
            'status' => self::computeInvoiceStatus($pgRow['status'] ?? 'pending', $outstanding, $pgRow['due_date'] ?? null),
            'company_prefix' => $companyPrefix,
            'raw_data' => json_encode($rawData)
        ];
    }
    
    /**
     * Transform payment row to finance_consolidated format
     */
    public static function transformPaymentRow(array $pgRow, string $companyPrefix): array
    {
        $amount = self::coerceToFloat($pgRow['amount'] ?? 0);
        
        $customerName = !empty($pgRow['customer_name']) 
            ? $pgRow['customer_name'] 
            : $pgRow['customer_id'];
        
        $rawData = $pgRow;
        $rawData['_computed'] = [
            'record_type' => 'payment',
            'outstanding_amount' => 0.0,
            'days_overdue' => null
        ];
        
        return [
            'record_type' => 'payment',
            'document_number' => $pgRow['payment_id'] ?? $pgRow['receipt_number'] ?? 'PAY-' . ($pgRow['id'] ?? uniqid()),
            'customer_id' => $pgRow['customer_id'],
            'customer_name' => $customerName,
            'customer_gstin' => $pgRow['customer_gstin'] ?? null,
            'amount' => $amount,
            'taxable_amount' => 0.0,
            'amount_paid' => $amount,
            'outstanding_amount' => 0.0,
            'igst' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'due_date' => null,
            'invoice_date' => null,
            'status' => $pgRow['payment_status'] ?? 'completed',
            'company_prefix' => $companyPrefix,
            'raw_data' => json_encode($rawData)
        ];
    }
    
    /**
     * Compute invoice status with overdue logic
     */
    private static function computeInvoiceStatus(string $originalStatus, float $outstanding, ?string $dueDate): string
    {
        if ($outstanding <= 0) {
            return 'paid';
        }
        
        if ($dueDate && $outstanding > 0) {
            try {
                $due = new \DateTime($dueDate);
                $today = new \DateTime('today');
                if ($today > $due) {
                    return 'overdue';
                }
            } catch (\Exception $e) {
                // Invalid date, keep original status
            }
        }
        
        return $originalStatus;
    }
    
    /**
     * Compute days overdue
     */
    private static function computeDaysOverdue(?string $dueDate, float $outstanding): ?int
    {
        if (empty($dueDate) || $outstanding <= 0) {
            return null;
        }
        
        try {
            $due = new \DateTime($dueDate);
            $today = new \DateTime('today');
            $diff = $today->diff($due);
            
            return $diff->invert ? $diff->days : -$diff->days;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Coerce value to float
     */
    private static function coerceToFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        
        return (float)$value;
    }
}