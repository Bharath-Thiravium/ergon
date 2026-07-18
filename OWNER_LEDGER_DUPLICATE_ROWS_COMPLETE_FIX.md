# Owner Ledger Duplicate Rows - COMPLETE FIX PACKAGE

## Executive Summary

**Problem:** Expense #73 creates 2 ledger rows instead of 1  
**Root Cause:** Missing duplicate prevention when payment status changes  
**Solution:** Implement single-entry model + cleanup existing duplicates  
**Status:** Ready for deployment

---

## Changes Made

### 1. Enhanced Duplicate Prevention (LedgerHelper.php)

**File:** `app/helpers/LedgerHelper.php`

**Changes:**
- Added explicit comment emphasizing single-entry model
- Entry_type + reference_type + reference_id uniqueness check (line 75-83)
- Post-insert integrity check warns if more than 1 entry exists (line 128-131)
- Updated log messages to clarify duplicate prevention

**Key Guard:**
```php
// Line 75-83: Secondary guard prevents creating duplicate entry_type combos
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ? 
    AND entry_type = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
if ($chk2->fetch()) {
    // Entry exists → DON'T CREATE ANOTHER
    return true;
}
```

### 2. Status Update Without New Entry (ExpenseController.php)

**File:** `app/controllers/ExpenseController.php`  
**Function:** `markPaid()`  
**Lines:** ~480-530

**Key Change:**
- Removed any ledger entry creation during payment marking
- Payment is a status update ONLY (approved → paid)
- Ledger entry created at approval remains untouched
- Updated log message: "no new ledger entry — status update only"

**Critical Comment Added:**
```php
// CRITICAL: Do NOT create a new ledger entry here
// Ledger entry was created at approval with 'expense_payment' type
// Status change (approved→paid) does NOT create a second row
// Single-entry model: one ledger row per business transaction
```

### 3. Same for Advances (AdvanceController.php)

**File:** `app/controllers/AdvanceController.php`  
**Function:** `markPaid()`

**Status:** Already correct - does NOT create duplicate ledger entry at payment  
**Log Message:** Added clarity about single-entry model

---

## Database Cleanup Scripts

### Audit Script
**File:** `scripts/cleanup_duplicate_ledger_entries.sql`

Provides comprehensive SQL queries to:
1. Find all duplicate transactions
2. Show detailed duplicate report
3. Count total duplicates
4. Validate before cleanup
5. Execute cleanup (DELETE duplicates)
6. Verify no duplicates remain
7. Reconciliation report
8. Restore procedure (if needed)

### PHP Migration
**File:** `migrations/cleanup_duplicate_ledger_entries.php`

Browser-accessible cleanup tool:
- Automatic backup creation
- Safe deletion with verification
- Pre/post validation
- Detailed HTML report
- Reconciliation checking

**Usage:**
```
Visit: /ergon/migrations/cleanup_duplicate_ledger_entries.php
```

---

## Verification Checklist

### Pre-Cleanup Verification

```sql
-- 1. Find duplicates (should show issue)
SELECT reference_type, reference_id, COUNT(*) as count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id
HAVING COUNT(*) > 1;

-- Expected: Shows Expense #73 with count=2

-- 2. Check ledger_synced flag
SELECT id, status, ledger_synced 
FROM expenses 
WHERE id = 73;

-- Expected: ledger_synced = 1 (entry already created)
```

### Post-Cleanup Verification

```sql
-- 1. No more duplicates
SELECT COUNT(*) as duplicate_count
FROM (
    SELECT reference_type, reference_id, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING cnt > 1
) as x;

-- Expected: 0

-- 2. Each expense has exactly 1 ledger entry
SELECT e.id, COUNT(ul.id) as ledger_count
FROM expenses e
LEFT JOIN user_ledgers ul ON ul.reference_type='expense' AND ul.reference_id=e.id
WHERE e.status IN ('approved', 'paid')
GROUP BY e.id
HAVING ledger_count != 1;

-- Expected: 0 rows

-- 3. Each advance has exactly 1 ledger entry
SELECT a.id, COUNT(ul.id) as ledger_count
FROM advances a
LEFT JOIN user_ledgers ul ON ul.reference_type='advance' AND ul.reference_id=a.id
WHERE a.status IN ('approved', 'paid')
GROUP BY a.id
HAVING ledger_count != 1;

-- Expected: 0 rows

-- 4. Verify ledger entry types
SELECT DISTINCT entry_type, COUNT(*) as count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY entry_type;

-- Expected: 
-- expense_payment: N
-- advance_payment: N
-- (NO 'expense_reimbursed', 'advance_settled', etc.)
```

---

## Testing Scenarios

### Scenario 1: New Expense Approval
1. Create new expense for ₹50,000
2. Approve expense
3. Check ledger: Should show 1 entry
4. Mark paid
5. Check ledger: Still 1 entry (status updated, not new entry)

### Scenario 2: New Advance Approval
1. Create new advance for ₹30,000
2. Approve advance
3. Check ledger: Should show 1 entry
4. Mark paid
5. Check ledger: Still 1 entry

### Scenario 3: Owner Ledger Report
1. Go to Owner → Cash Ledger
2. Filter by date range
3. Verify: Each transaction shows 1 row, not duplicates
4. Download CSV: Row count matches UI display

---

## Rollback Procedure

If issues discovered during cleanup:

```sql
-- Restore from backup
DROP TABLE user_ledgers;
RENAME TABLE user_ledgers_backup_before_dedup TO user_ledgers;

-- Then investigate code issues before retrying
```

---

## Production Deployment

### Step 1: Code Update
- Deploy updated LedgerHelper.php
- Deploy updated ExpenseController.php
- Verify no issues in staging

### Step 2: Pre-Cleanup Audit
- Run audit queries from SQL script
- Document duplicates found
- Verify backup procedure works

### Step 3: Backup & Cleanup
- Run cleanup script (migration or SQL)
- Monitor for errors
- Verify cleanup completeness

### Step 4: Validation
- Run reconciliation queries
- Check Owner Ledger UI
- Verify CSV export
- Test new approval workflows

---

## Business Rules Enforced

### Single-Entry Model

One business transaction = One ledger row

```
EXPENSE LIFECYCLE          LEDGER ACTION
─────────────────────────────────────────
1. Created (pending)    → No entry
2. Approved            → INSERT 1 entry
3. Paid/Reimbursed    → UPDATE same entry (status only)

NEVER:
- Create entry at pending
- Create second entry at payment
- Create entry_reimbursed, entry_settled, etc.
```

### Ledger Entry Types

**Valid:**
- `expense_payment` → Expense approved for payment
- `advance_payment` → Advance approved for distribution
- `expense_reversal` → Expense correction (creates offsetting entry)
- `advance_reversal` → Advance correction (creates offsetting entry)
- `manual_adjustment` → Manual correction by admin

**Invalid (DO NOT USE):**
- ~~`expense_reimbursed`~~ → Creates duplicate
- ~~`advance_settled`~~ → Creates duplicate
- ~~`paid`~~ → Not a proper entry_type

---

## Code Guard Layers

### Layer 1: Source Table Flag
```php
if (!empty($row['ledger_synced'])) {
    // Already processed, skip
    return true;
}
```

### Layer 2: Entry Type Uniqueness
```php
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? 
    AND reference_id = ? AND entry_type = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
if ($chk2->fetch()) {
    // Entry exists, don't create duplicate
    return true;
}
```

### Layer 3: Post-Insert Verification
```php
$verify = $db->prepare(
    "SELECT COUNT(*) FROM user_ledgers 
     WHERE reference_type = ? AND reference_id = ? 
     AND entry_type = ?"
);
$verify->execute([$referenceType, $referenceId, $entryType]);
$count = (int) $verify->fetchColumn();
if ($count !== 1) {
    error_log("ERROR integrity violation — found $count rows (expected 1)");
}
```

---

## Files Modified

### Code Changes
1. `app/helpers/LedgerHelper.php` - Enhanced duplicate prevention
2. `app/controllers/ExpenseController.php` - Removed duplicate ledger creation at payment
3. `app/controllers/AdvanceController.php` - Verified already correct

### New Files
1. `scripts/cleanup_duplicate_ledger_entries.sql` - SQL audit & cleanup
2. `migrations/cleanup_duplicate_ledger_entries.php` - PHP migration tool

### Documentation
1. `OWNER_LEDGER_DUPLICATE_ROWS_ROOT_CAUSE.md` - Root cause analysis

---

## Monitoring & Maintenance

### Error Logs to Watch
```
LedgerHelper: ERROR integrity violation — found X rows for X/X type=X (expected 1)
WARNING: Expense id=X marked paid but ledger_synced flag not set
```

If these appear: Investigate code path creating duplicate

### Monthly Audit
```sql
-- Check for new duplicates (should be 0)
SELECT COUNT(*) as duplicate_transactions
FROM (
    SELECT reference_type, reference_id, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING cnt > 1
) as x;
```

---

## Summary

### Before Fix
- Expense #73 → 2 ledger entries
- Owner Ledger → Shows "2 Entries" for single transaction
- CSV Export → Duplicate rows
- Potential balance calculation errors

### After Fix
- Expense #73 → 1 ledger entry
- Owner Ledger → Shows "1 Entry" for single transaction
- CSV Export → One row per transaction
- Accurate balance calculations
- Duplicate prevention at multiple layers
- Single-entry model enforced

---

**Status:** Ready for Production  
**Risk Level:** LOW (Guards in place, cleanup script verified)  
**Rollback:** Simple (backup table available)  
**Deployment Time:** 5-10 minutes  
**Validation Time:** 15-20 minutes

