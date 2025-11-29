# Stat Card 4 - GST Liability Implementation

## Overview
Stat Card 4 displays GST liability calculated only on outstanding invoices using backend-only calculations, following the exact specification provided.

## Implementation Details

### 1. Database Schema
Added three new columns to `dashboard_stats` table:
- `igst_liability` DECIMAL(15,2) - IGST liability on outstanding invoices
- `cgst_sgst_total` DECIMAL(15,2) - Combined CGST+SGST liability  
- `gst_liability` DECIMAL(15,2) - Total GST liability (IGST + CGST+SGST)

### 2. Backend Calculation Logic
Located in `FinanceController::calculateStatCard3Pipeline()`:

```php
// Step 1: Fetch raw invoice data (no SQL aggregation)
SELECT id, invoice_number, taxable_amount, igst, cgst, sgst, amount_paid
FROM finance_invoices 
WHERE invoice_number LIKE '{company_prefix}%'

// Step 2: Backend calculations only
foreach ($invoices as $invoice) {
    $pendingBase = $taxableAmount - $amountPaid;
    
    if ($pendingBase > 0) {
        $igstLiability += $igst;
        $cgstSgstTotal += ($cgst + $sgst);
    }
}

$gstLiability = $igstLiability + $cgstSgstTotal;
```

### 3. Key Requirements Compliance

✅ **No SQL Aggregation**: Uses simple SELECT with prefix filtering only  
✅ **Backend-Only Calculations**: All logic in service layer  
✅ **Outstanding Invoices Only**: GST liability calculated only where `pending_base > 0`  
✅ **Separate IGST/CGST+SGST**: Tracks components separately  
✅ **Dashboard Stats Storage**: Results stored in `dashboard_stats` table  
✅ **Frontend Read-Only**: Frontend never queries `finance_invoices` directly  

### 4. API Response Structure
```json
{
  "igstLiability": 15000.00,
  "cgstSgstTotal": 8500.00, 
  "gstLiability": 23500.00,
  "pendingGSTAmount": 23500.00
}
```

### 5. Frontend Display
Stat Card 4 shows:
- **IGST**: `igst_liability` value
- **CGST+SGST**: `cgst_sgst_total` value  
- **Total GST Liability**: `gst_liability` value

### 6. Data Flow
1. Raw invoice data fetched with simple SELECT
2. Backend calculates GST liability on outstanding amounts only
3. Results stored in `dashboard_stats` table
4. Frontend reads from `dashboard_stats` only
5. No direct queries to `finance_invoices` from frontend

### 7. Testing
Run `test_stat_card_4.php` to verify:
- Database schema correctness
- Calculation logic accuracy
- API response format
- End-to-end functionality

### 8. Files Modified
- `app/controllers/FinanceController.php` - Backend calculations
- `views/finance/dashboard.php` - Frontend display
- Database schema - Added GST liability columns

This implementation ensures GST liability is calculated correctly on outstanding invoices only, with all processing done in the backend and frontend displaying pre-calculated values from the dashboard_stats table.