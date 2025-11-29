# Stat Card 5 - PO Commitments Implementation

## Overview
Stat Card 5 displays Purchase Order commitments with open/closed PO counts using backend-only calculations, following the exact specification provided.

## Implementation Details

### 1. Database Schema
Updated `dashboard_stats` table:
- `po_commitments` DECIMAL(15,2) - Total value of all POs
- `open_pos` INT - Count of open POs
- `closed_pos` INT - Count of closed POs
- **Removed**: `average_po` column (no longer calculated or displayed)

### 2. Backend Calculation Logic
Located in `FinanceController::calculateStatCard3Pipeline()`:

```php
// Step 1: Fetch raw PO data (no SQL aggregation)
SELECT id, po_number, total_amount, amount_paid, approved_date, received_date
FROM finance_purchase_orders 
WHERE po_number LIKE '{company_prefix}%'

// Step 2: Backend calculations only
foreach ($pos as $po) {
    $poCommitments += $totalAmount;
    
    // Determine PO status
    if (($amountPaid < $totalAmount) || empty($receivedDate)) {
        $openPos++;
    } else {
        $closedPos++;
    }
}
```

### 3. PO Status Logic
- **Open PO**: `(amount_paid < total_amount) OR received_date IS NULL`
- **Closed PO**: `(amount_paid >= total_amount) AND received_date IS NOT NULL`

### 4. Key Requirements Compliance

✅ **No SQL Aggregation**: Uses simple SELECT with prefix filtering only  
✅ **Backend-Only Calculations**: All logic in service layer  
✅ **Total PO Commitments**: Sum of all total_amount values  
✅ **Open/Closed Counts**: Based on payment and receipt status  
✅ **No Average PO**: Removed from calculation and display  
✅ **Dashboard Stats Storage**: Results stored in `dashboard_stats` table  
✅ **Frontend Read-Only**: Frontend never queries `finance_purchase_orders` directly  

### 5. API Response Structure
```json
{
  "pendingPOValue": 195000.00,
  "openPOCount": 3,
  "closedPOCount": 1,
  "totalPOCount": 4
}
```

### 6. Frontend Display
Stat Card 5 shows:
- **PO Commitments**: `po_commitments` value (total of all POs)
- **Open POs**: `open_pos` count
- **Closed POs**: `closed_pos` count
- **Removed**: Average PO value (no longer displayed)

### 7. Data Flow
1. Raw PO data fetched with simple SELECT
2. Backend calculates total commitments and status counts
3. Results stored in `dashboard_stats` table
4. Frontend reads from `dashboard_stats` only
5. No direct queries to `finance_purchase_orders` from frontend

### 8. Testing
Run `test_stat_card_5.php` to verify:
- Database schema correctness
- PO status calculation logic
- API response format
- End-to-end functionality

### 9. Files Modified
- `app/controllers/FinanceController.php` - Backend calculations
- `views/finance/dashboard.php` - Frontend display
- Database schema - Updated PO commitment columns

This implementation ensures PO commitments represent the total value of all purchase orders, with accurate open/closed counts based on payment and receipt status, all calculated in the backend with frontend displaying pre-calculated values from the dashboard_stats table.