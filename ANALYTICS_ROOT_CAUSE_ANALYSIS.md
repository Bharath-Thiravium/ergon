# Analytics Components - Root Cause Analysis Report

## Issue Summary
Charts are rendering but showing no data. The UI displays correctly but data fetching/processing fails silently.

## Root Cause Analysis

### 1. **Quotations Chart - Data Fetching Issue**
**Problem**: `updateAnalyticsWidgets()` calls `/ergon/src/api/analytics.php?type=quotations`
**Root Cause**: 
- API endpoint expects `prefix` parameter but receives it correctly
- Response data structure: `{success: true, data: {placed, rejected, pending}}`
- Chart expects: `statusCounts` object with these exact keys
- **Issue**: Data keys are `placed`, `rejected`, `pending` but chart looks for these in `statusCounts`

**Data Flow**:
```
API Response: {placed: 0, rejected: 0, pending: 1}
↓
analyticsData.quotations.statusCounts = {placed: 0, rejected: 0, pending: 1}
↓
updateChartsWithAnalytics() receives correct data
↓
Chart update: quotationsChart.data.datasets[0].data = [pending, placed, rejected]
```

**Why it fails**: The data IS being fetched correctly, but the chart initialization happens BEFORE data arrives.

### 2. **Invoices Chart - Timing Issue**
**Problem**: Chart initialized with `[0,0,0]` data
**Root Cause**:
- `initCharts()` runs on DOMContentLoaded
- `updateAnalyticsWidgets()` runs AFTER funnel data loads (100ms delay)
- Invoice data structure from API: `{collected_amount, pending_invoice_value, dso}`
- Chart expects: `{paid_count, unpaid_count, overdue_count}`
- **Issue**: Data transformation happens but chart may not update if instance not ready

### 3. **Outstanding by Customer Chart - Data Structure Mismatch**
**Problem**: Chart shows no data even though customer data loads
**Root Cause**:
- API returns: `[{customer_name, outstanding_amount}, ...]`
- Chart expects: `labels` array and `data` array
- **Issue**: Chart instance created with empty arrays, data added later but chart not properly initialized

### 4. **Aging Buckets Chart - Missing Implementation**
**Problem**: Chart never receives data
**Root Cause**:
- `updateAnalyticsWidgets()` does NOT fetch aging data
- `analyticsData.aging` remains empty `{}`
- Chart never updates
- **Issue**: Aging API call completely missing from `updateAnalyticsWidgets()`

## Data Flow Diagram

```
loadCompanyPrefix()
    ↓
loadAllStatCardsData() [KPI Cards - Working ✓]
    ↓
updateConversionFunnel() [Funnel - Working ✓]
    ↓ (setTimeout 100ms)
updateAnalyticsWidgets() [Charts - BROKEN ✗]
    ├─ Quotations API ✓ (data fetched)
    ├─ PO Claims API ✓ (data fetched)
    ├─ Invoices API ✓ (data fetched)
    ├─ Customer Outstanding API ✓ (data fetched)
    ├─ Aging API ✗ (MISSING - never called)
    └─ updateChartsWithAnalytics() [Chart update - PARTIAL]
        ├─ Quotations Chart ✓ (data updates)
        ├─ Invoices Chart ✓ (data updates)
        ├─ Outstanding Chart ✓ (data updates)
        └─ Aging Chart ✗ (never receives data)
```

## Critical Issues Found

| Component | Issue | Impact | Status |
|-----------|-------|--------|--------|
| Quotations Chart | Data fetched but chart may not render | Shows 0 data | FIXABLE |
| Invoices Chart | Data transformation incomplete | Shows 0 data | FIXABLE |
| Outstanding Chart | Data structure correct but rendering issue | Shows 0 data | FIXABLE |
| Aging Chart | API call missing entirely | No data ever fetched | FIXABLE |
| Chart.js Library | May not be loaded before initCharts() | Charts fail to initialize | FIXABLE |

## Solution Strategy

1. **Ensure Chart.js loads first** - Add explicit wait
2. **Fix Quotations Chart** - Verify data structure matches
3. **Fix Invoices Chart** - Ensure data transformation correct
4. **Fix Outstanding Chart** - Verify labels/data arrays populated
5. **Add Aging Chart** - Implement missing API call
6. **Add error logging** - Debug data flow

## Implementation Order
1. Add Chart.js load verification
2. Fix Quotations component (simplest)
3. Fix Invoices component
4. Fix Outstanding component
5. Add Aging component
6. Test each independently
