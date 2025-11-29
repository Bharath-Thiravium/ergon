# Chart Card 1 (Quotations Overview) - Revised Logic Implementation

## Overview
This document outlines the implementation of the revised Chart Card 1 logic that fetches raw quotation rows first, then computes metrics in the backend/service layer without using SQL aggregation functions.

## Key Changes

### 1. New Field Mappings (REQUIRED)
| Field (Old)    | Field (New)             | Value             |
| -------------- | ----------------------- | ----------------- |
| Win Rate       | **Placed Quotations**   | count of placed   |
| Avg Deal Size  | **Rejected Quotations** | count of rejected |
| Pipeline Value | **Pending Quotations**  | count of pending  |

### 2. Backend Implementation

#### New Method: `calculateQuotationOverview()`
- **Location**: `app/controllers/FinanceController.php`
- **Purpose**: Implements the revised logic without SQL aggregation functions
- **Process**:
  1. Fetches raw quotation rows using prefix-based filtering
  2. Performs all calculations in backend logic
  3. Stores results in `dashboard_stats` table

#### SQL Query (No Aggregation)
```sql
SELECT id, quotation_number, amount, status
FROM finance_quotations  
WHERE quotation_number LIKE '{company_prefix}%';
```

#### Backend Calculation Logic
```php
// Initialize counters
$placed_count = 0;
$rejected_count = 0; 
$pending_count = 0;

// Calculate counts based on status
foreach ($quotations as $quotation) {
    $status = $quotation['status'];
    
    if ($status === 'placed') {
        $placed_count++;
    } elseif ($status === 'rejected') {
        $rejected_count++;
    } elseif (in_array($status, ['pending', 'draft', 'revised'])) {
        $pending_count++;
    }
}
```

### 3. Database Schema Updates

#### New Columns Added to `dashboard_stats` Table
- `placed_quotations` (INT DEFAULT 0)
- `rejected_quotations` (INT DEFAULT 0) 
- `pending_quotations` (INT DEFAULT 0)
- `total_quotations` (INT DEFAULT 0)

### 4. Frontend Updates

#### Updated Chart Card 1 Meta Section
```html
<div class="chart-card__meta">
    <div class="meta-item"><span>Placed Quotations:</span><strong id="placedQuotations">0</strong></div>
    <div class="meta-item"><span>Rejected Quotations:</span><strong id="rejectedQuotations">0</strong></div>
    <div class="meta-item"><span>Pending Quotations:</span><strong id="pendingQuotations">0</strong></div>
</div>
```

#### Updated Chart Legend
```html
<div class="chart-legend">
    <div class="legend-item"><span class="legend-color" style="background:#3b82f6"></span>Pending (Draft/Revised)</div>
    <div class="legend-item"><span class="legend-color" style="background:#10b981"></span>Placed (Approved)</div>
    <div class="legend-item"><span class="legend-color" style="background:#ef4444"></span>Rejected</div>
</div>
```

#### JavaScript Updates
- Removed amount-based calculations
- Added count-based display logic
- Updated to read from `dashboard_stats` API response

### 5. API Response Updates

#### New Fields in `/ergon/finance/dashboard-stats`
```json
{
    "placedQuotations": 5,
    "rejectedQuotations": 2, 
    "pendingQuotations": 8,
    "totalQuotations": 15
}
```

#### Updated `/ergon/finance/visualization?type=quotations`
- Now returns count-based data instead of amount-based data
- Reads from `dashboard_stats` table for consistency

## Allowed Quotation Statuses
- `draft` - Initial quotation
- `revised` - Updated quotation  
- `placed` - Approved/accepted quotation
- `rejected` - Rejected quotation
- `pending` - Pending review

## Status Mapping Logic
- **Placed**: `status === 'placed'`
- **Rejected**: `status === 'rejected'`  
- **Pending**: `status` in `['pending', 'draft', 'revised']`

## Key Benefits
1. **No SQL Aggregation**: All calculations moved to backend service layer
2. **Count-Based Display**: Shows quotation counts instead of monetary amounts
3. **Consistent Data Source**: Frontend reads only from `dashboard_stats` table
4. **Prefix Filtering**: Supports company-specific quotation filtering
5. **Backend Calculations**: All logic centralized in service layer

## Testing
Run the test file to verify implementation:
```
http://localhost/ergon/test_chart_card_1.php
```

## Files Modified
1. `app/controllers/FinanceController.php` - Backend logic
2. `views/finance/dashboard.php` - Frontend display
3. Database schema - New columns added

## Integration Points
- Integrated with existing `calculateStatCard3Pipeline()` method
- Called during dashboard stats refresh
- Stored alongside other financial metrics in `dashboard_stats` table

This implementation completes the revised Chart Card 1 logic as specified, moving from amount-based metrics to count-based quotation status tracking.