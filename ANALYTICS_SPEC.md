# Analytics Widgets Specification

## Overview
Additional analytics widgets appear BELOW the 4-stage Revenue Conversion Funnel.
Each component queries actual finance tables with prefix + optional customer filtering.

---

## 1. QUOTATION DONUT CHART

**Purpose:** Show quotation distribution by status.

**Values:**
- Placed Quotations → status = 'PLACED'
- Rejected Quotations → status = 'REJECTED'
- Pending Quotations → status = 'PENDING'

**SQL:**
```sql
SELECT 
    SUM(CASE WHEN status='PLACED' THEN 1 ELSE 0 END) AS placed_count,
    SUM(CASE WHEN status='REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
    SUM(CASE WHEN status IN ('PENDING','DRAFT','REVISED') THEN 1 ELSE 0 END) AS pending_count
FROM finance_quotations
WHERE company_prefix = :prefix
  AND (:customer_id IS NULL OR customer_id = :customer_id);
```

---

## 2. PURCHASE ORDERS — CLAIM PERCENTAGE DISTRIBUTION

**Purpose:** Split PO values into claim percentage ranges.

**Ranges:**
- Below 40%
- 40% – 60%
- 60% – 80%
- 80% – 99%
- 100% Fully Claimed

**Formula:** claim_percentage = (amount_paid / po_amount) * 100

**SQL:**
```sql
SELECT
  CASE
    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 < 40 THEN '<40%'
    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 40 AND 60 THEN '40-60%'
    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 60 AND 80 THEN '60-80%'
    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 BETWEEN 80 AND 99 THEN '80-99%'
    WHEN (amount_paid / NULLIF(po_amount, 0)) * 100 >= 100 THEN '100%'
  END AS bucket,
  COUNT(*) AS count
FROM finance_purchase_orders
WHERE company_prefix = :prefix
  AND (:customer_id IS NULL OR customer_id = :customer_id)
GROUP BY bucket
ORDER BY FIELD(bucket, '<40%', '40-60%', '60-80%', '80-99%', '100%');
```

**Fulfillment Rate:** (SUM(amount_paid) / SUM(po_amount)) * 100

---

## 3. INVOICE & COLLECTIONS

**KPIs:**
- Total Invoice Value
- Pending Invoice Value
- DSO (Days Sales Outstanding)

**SQL:**
```sql
SELECT
  SUM(total_amount) AS total_invoice_value,
  SUM(taxable_amount - amount_paid) AS pending_invoice_value,
  CASE 
    WHEN SUM(total_amount) > 0 
    THEN (SUM(taxable_amount - amount_paid) / (SUM(total_amount) / 30))
    ELSE 0
  END AS dso
FROM finance_invoices
WHERE company_prefix = :prefix
  AND (:customer_id IS NULL OR customer_id = :customer_id);
```

**DSO Calculation:**
DSO = (pending_invoice_value / (total_invoice_value / 30))

---

## 4. OUTSTANDING & CUSTOMER EXPOSURE

### 4.1 Customer Outstanding Bar Chart (Without GST)

**SQL:**
```sql
SELECT 
  customer_id,
  SUM(taxable_amount - amount_paid) AS outstanding
FROM finance_invoices
WHERE company_prefix = :prefix
  AND (:customer_id IS NULL OR customer_id = :customer_id)
GROUP BY customer_id
ORDER BY outstanding DESC
LIMIT 10;
```

### 4.2 Claimable Amount (With GST)

**SQL:**
```sql
SELECT 
  customer_id,
  SUM((taxable_amount - amount_paid) + COALESCE(igst_amount, 0) + COALESCE(cgst_amount, 0) + COALESCE(sgst_amount, 0)) AS outstanding_with_gst
FROM finance_invoices
WHERE company_prefix = :prefix
  AND (:customer_id IS NULL OR customer_id = :customer_id)
GROUP BY customer_id
ORDER BY outstanding_with_gst DESC
LIMIT 10;
```

### 4.3 Concentration Risk Donut Chart

**SQL:**
```sql
WITH customer_totals AS (
  SELECT 
    customer_id,
    SUM(taxable_amount - amount_paid) AS outstanding
  FROM finance_invoices
  WHERE company_prefix = :prefix
    AND (:customer_id IS NULL OR customer_id = :customer_id)
  GROUP BY customer_id
),
top_3 AS (
  SELECT SUM(outstanding) AS top_3_sum
  FROM (SELECT outstanding FROM customer_totals ORDER BY outstanding DESC LIMIT 3) t
),
total AS (
  SELECT SUM(outstanding) AS total_outstanding FROM customer_totals
)
SELECT 
  ROUND((top_3.top_3_sum / total.total_outstanding) * 100, 2) AS concentration_risk_pct,
  ROUND(100 - ((top_3.top_3_sum / total.total_outstanding) * 100), 2) AS other_customers_pct
FROM top_3, total;
```

**Formula:** (top_3_outstanding / total_outstanding) * 100

---

## Implementation Notes

- All queries filter by `company_prefix` and optional `:customer_id` parameter
- Use actual finance tables: `finance_quotations`, `finance_purchase_orders`, `finance_invoices`
- Apply prefix + customer filtering consistently
- Charts render below the 4-stage funnel
- Support dynamic customer filtering via dropdown
- Use prepared statements with named parameters (`:prefix`, `:customer_id`)
- Handle NULL values with COALESCE for tax columns
