<?php

namespace Ergon\FinanceSync;

use DateTime;
use DateTimeZone;

class Transformer
{
    /**
     * Transform a PostgreSQL invoice row to MySQL finance_consolidated format
     */
    public static function transformInvoiceRow(array $pgRow, string $companyPrefix): array
    {
        $taxableAmount = self::coerceToFloat($pgRow['taxable_amount'] ?? 0);
        $amountPaid = self::coerceToFloat($pgRow['amount_paid'] ?? 0);
        $outstanding = self::computeOutstanding($taxableAmount, $amountPaid);
        $dueDate = $pgRow['due_date'] ?? null;
        $daysOverdue = self::computeDaysOverdue($dueDate, $outstanding);
        
        // Compute status with overrides
        $status = self::computeStatus(
            $pgRow['status'] ?? 'pending',
            $outstanding,
            $dueDate
        );
        
        // Fallback customer_name to customer_id if missing
        $customerName = !empty($pgRow['customer_name']) 
            ? $pgRow['customer_name'] 
            : $pgRow['customer_id'];
        
        // Prepare raw_data with computed meta
        $rawData = $pgRow;
        $rawData['_computed'] = [
            'outstanding_amount' => $outstanding,
            'days_overdue' => $daysOverdue,
            'status_computed' => $status
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
            'due_date' => $dueDate,
            'invoice_date' => $pgRow['invoice_date'] ?? null,
            'status' => $status,
            'company_prefix' => $companyPrefix,
            'raw_data' => json_encode($rawData)
        ];
    }
    
    /**
     * Compute outstanding amount (taxable - paid), capped at 0
     */
    public static function computeOutstanding(float $taxable, float $paid): float
    {
        return max(0, $taxable - $paid);
    }
    
    /**
     * Compute days overdue only if outstanding > 0 and due_date exists
     */
    public static function computeDaysOverdue(?string $dueDate, float $outstanding = null): ?int
    {
        if (empty($dueDate) || ($outstanding !== null && $outstanding <= 0)) {
            return null;
        }
        
        try {
            $due = new DateTime($dueDate, new DateTimeZone('UTC'));
            $today = new DateTime('today', new DateTimeZone('UTC'));
            $diff = $today->diff($due);
            
            return $diff->invert ? $diff->days : -$diff->days;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Compute status with business logic overrides
     */
    public static function computeStatus(string $originalStatus, float $outstanding, ?string $dueDate): string
    {
        // If fully paid, mark as paid regardless of original status
        if ($outstanding <= 0) {
            return 'paid';
        }
        
        // If overdue, mark as overdue
        $daysOverdue = self::computeDaysOverdue($dueDate, $outstanding);
        if ($daysOverdue !== null && $daysOverdue > 0) {
            return 'overdue';
        }
        
        // Otherwise return original status
        return $originalStatus;
    }
    
    /**
     * Coerce value to float, handling nulls and strings
     */
    private static function coerceToFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        
        return (float)$value;
    }
}