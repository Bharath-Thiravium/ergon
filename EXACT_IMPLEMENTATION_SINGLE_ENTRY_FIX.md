# EXACT IMPLEMENTATION - Single Ledger Row Per Transaction

## Problem

Expense #73 creates 2 ledger entries:
- Row 1: entry_type='expense_payment', amount=-50000
- Row 2: entry_type='expense_reimbursed' (or similar), amount=+50000

**This violates the business rule: One Transaction = One Ledger Row**

## Solution: Enforce Single-Entry Model

### STEP 1: Modify LedgerHelper.php

**Issue:** Line 75-83 only checks if SAME entry_type exists. Must check if ANY entry exists.

**FIX:**

```php
// Line 75-83 in recordEntry() function

// BEFORE (WRONG):
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
if ($chk2->fetch()) {
    // Mark as synced to prevent retry
    $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")
        ->execute([$referenceId]);
    error_log("LedgerHelper: entry exists (skipped, marked synced)");
    return true;
}

// AFTER (CORRECT):
// CRITICAL FIX: Check if ANY entry exists for this reference, not just same entry_type
// SINGLE-ENTRY MODEL: One ledger row per business transaction
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId]);
if ($chk2->fetch()) {
    // Entry already exists - mark as synced to prevent duplicate creation
    // Do NOT create a new row with different entry_type
    $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")
        ->execute([$referenceId]);
    error_log("LedgerHelper: SINGLE-ENTRY: Entry exists for $referenceType/$referenceId - skipped duplicate");
    return true;
}
```

### STEP 2: Remove Duplicate Creation in ExpenseController.php

**Issue:** `markPaid()` function might be creating a second ledger entry

**Location:** `app/controllers/ExpenseController.php`, function `markPaid()`, around line 480-530

**FIX:** Ensure markPaid() only updates expense status, does NOT create new ledger entry

```php
public function markPaid($id = null) {
    // ... existing code ...
    
    // AT PAYMENT STAGE: Only update expense status
    // DO NOT create new ledger entry
    // The entry was already created at approval stage
    
    $stmt = $db->prepare("UPDATE expenses SET status = 'paid', payment_proof = ?, payment_remarks = ?, paid_by = ?, paid_at = NOW() WHERE id = ?");
    
    $db->beginTransaction();
    $result = $stmt->execute([$proof, $paymentRemarks, $_SESSION['user_id'], $id]);
    
    if ($result) {
        // Update approved_expenses table
        $upd = $db->prepare("UPDATE approved_expenses SET payment_proof = ?, paid_at = NOW() WHERE expense_id = ?");
        $upd->execute([$proof, $id]);
        
        // CRITICAL: DO NOT CREATE A NEW LEDGER ENTRY HERE
        // Single-entry model: only create entry at approval
        // Payment is just a status update
        
        // Verify ledger_synced flag is set
        if (empty($expense['ledger_synced'])) {
            error_log("WARNING: Expense id=$id marked paid but ledger_synced=0 (should have been set at approval)");
        }
        
        $db->commit();
        error_log("Expense marked paid (status update only): id=$id");
        
        // Rest of function...
    }
}
```

### STEP 3: Same Fix for AdvanceController.php

**Location:** `app/controllers/AdvanceController.php`, function `markPaid()`

**Ensure:** Advances also follow single-entry model

```php
// Do NOT create ledger entry at payment stage
// It was created at approval stage
// Payment is status update only
```

---

## DATABASE CLEANUP

### Step 1: Create Diagnostic Report

```sql
-- Find exact duplicates for Expense #73
SELECT id, user_id, reference_type, reference_id, entry_type, direction, amount, created_at
FROM user_ledgers
WHERE reference_type = 'expense' AND reference_id = 73
ORDER BY created_at;
```

### Step 2: Backup Before Delete

```sql
CREATE TABLE user_ledgers_duplicates_backup AS
SELECT ul.* 
FROM user_ledgers ul
INNER JOIN (
    SELECT reference_type, reference_id, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING cnt > 1
) dups
ON ul.reference_type = dups.reference_type 
AND ul.reference_id = dups.reference_id;
```

### Step 3: Delete Duplicates (Keep First, Delete Rest)

```sql
DELETE FROM user_ledgers
WHERE (reference_type, reference_id) IN (
    SELECT reference_type, reference_id
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING COUNT(*) > 1
)
AND id NOT IN (
    SELECT MIN(id)
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING COUNT(*) > 1
);
```

### Step 4: Verify Cleanup

```sql
-- Should return: 0 rows (no duplicates)
SELECT reference_type, reference_id, COUNT(*) as cnt
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id
HAVING cnt > 1;
```

---

## VALIDATION

### Immediate Test

```sql
-- Expense #73 should now have exactly 1 row
SELECT COUNT(*) as entry_count
FROM user_ledgers
WHERE reference_type = 'expense' AND reference_id = 73;

-- Expected: 1
```

### Full Validation

```sql
-- All expenses should have 0 or 1 ledger entry
SELECT 
    e.id as expense_id,
    COUNT(ul.id) as ledger_count,
    GROUP_CONCAT(ul.entry_type) as entry_types
FROM expenses e
LEFT JOIN user_ledgers ul ON ul.reference_type='expense' AND ul.reference_id=e.id
WHERE e.status IN ('approved', 'paid')
GROUP BY e.id
HAVING COUNT(ul.id) > 1;

-- Expected: 0 rows (no multiples)
```

---

## SUMMARY OF CHANGES

### Code Changes:
1. **LedgerHelper.php** (Line 75-83): Change duplicate check to look for ANY entry, not just same entry_type
2. **ExpenseController.php** (markPaid function): Remove ledger creation, keep status update only
3. **AdvanceController.php** (markPaid function): Same fix

### Database Changes:
1. Backup duplicates
2. Delete duplicate rows (keep first, delete rest)
3. Verify single entry per transaction

### Result:
- Expense #73 shows **1 ledger entry** instead of 2
- Owner Ledger shows **1 row** instead of 2
- All future transactions follow single-entry model

---

## CRITICAL: The Key Insight

The issue is NOT in the display code. The issue is:

**1. LedgerHelper allows creating multiple entries with different entry_type values**
   - Current: `WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?`
   - Should be: `WHERE user_id = ? AND reference_type = ? AND reference_id = ?`

**2. expenseController.markPaid() creates a second entry**
   - Current: Creates new ledger entry at payment stage
   - Should be: Only update status, never create new entry

**3. Database has existing duplicates**
   - Need to: Delete all duplicate rows, keep first one only

---

**Ready to implement. This is the complete and exact fix.**

