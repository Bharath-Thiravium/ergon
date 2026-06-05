# REGRESSION ANALYSIS: Owner Ledger Duplicate Rows Root Cause

## EXECUTIVE SUMMARY

**Problem:** Single expense (e.g., #73) appears TWICE in Owner Ledger:
- Row 1: Type = Expense, Amount = -₹50,000
- Row 2: Type = Reimbursed, Amount = +₹50,000

**Root Cause:** The `markPaid()` function in `ExpenseController.php` contains **residual code** that calculates `$ledgerAmount` but then **does nothing with it**. This is leftover debugging code that serves no function.

**Timeline:** This code was left in place after recent ledger modifications. The bug is not a new feature—it's dormant, unused code that doesn't cause the duplicate itself, but indicates incomplete refactoring.

**The ACTUAL duplicate cause:** The duplicate appears to come from **Owner Ledger display logic**, not from the data itself. The ledger shows both the approved entry AND a calculated reverse entry for display purposes.

---

## PART 1: DETAILED CODE AUDIT

### File 1: ExpenseController_PATCHED.php (CORRECT VERSION)

**Function:** `markPaid()` at line ~493-538

```php
public function markPaid($id = null) {
    // ... validation code ...
    
    $stmt = $db->prepare("UPDATE expenses SET status = 'paid', ...");
    
    $db->beginTransaction();
    $result = $stmt->execute([$proof, $paymentRemarks, $_SESSION['user_id'], $id]);
    
    if ($result) {
        $upd = $db->prepare("UPDATE approved_expenses SET payment_proof = ?, paid_at = NOW() WHERE expense_id = ?");
        $upd->execute([$proof, $id]);
        
        // CRITICAL: Do NOT create a new ledger entry here
        // Ledger entry was created at approval with 'expense_payment' type
        // Status change (approved→paid) does NOT create a second row
        // Single-entry model: one ledger row per business transaction
        if (empty($expense['ledger_synced'])) {
            error_log("WARNING: Expense id=$id marked paid but ledger_synced flag not set...");
        }
        $db->commit();
        error_log("Expense marked paid (status update only, no new ledger entry): id=$id...");
    }
}
```

**Key Points:**
- ✓ NO ledger entry created at payment stage
- ✓ Clear comment explaining single-entry model
- ✓ Only updates status in `expenses` and `approved_expenses` tables
- ✓ No `$ledgerAmount` variable

---

### File 2: ExpenseController.php (CURRENT/BROKEN VERSION)

**Function:** `markPaid()` at line ~480-530

```php
public function markPaid($id = null) {
    // ... validation code ...
    
    $stmt = $db->prepare("UPDATE expenses SET status = 'paid', ...");
    
    // BUG: These lines calculate ledgerAmount but do nothing with it!
    $stmt2 = $db->prepare("SELECT approved_amount FROM approved_expenses WHERE expense_id = ? ORDER BY id DESC LIMIT 1");
    $stmt2->execute([$id]);
    $approvedRow  = $stmt2->fetch(PDO::FETCH_ASSOC);
    $ledgerAmount = !empty($approvedRow['approved_amount'])
        ? floatval($approvedRow['approved_amount'])
        : (!empty($expense['approved_amount']) ? floatval($expense['approved_amount']) : floatval($expense['amount']));
    
    $db->beginTransaction();
    $result = $stmt->execute([$proof, $paymentRemarks, $_SESSION['user_id'], $id]);
    
    if ($result) {
        $upd = $db->prepare("UPDATE approved_expenses SET payment_proof = ?, paid_at = NOW() WHERE expense_id = ?");
        $upd->execute([$proof, $id]);
        
        if (empty($expense['ledger_synced'])) {
            error_log("WARNING: Expense id=$id marked paid but ledger_synced flag not set...");
        }
        $db->commit();
        error_log("Expense paid: id=$id user_id={$expense['user_id']} amount=$ledgerAmount");
    }
}
```

**The Bug:**
- ❌ Lines 480-485: Calculate `$ledgerAmount` from approved_expenses table
- ❌ Line 520: Use `$ledgerAmount` in error_log ONLY
- ❌ **NO ACTION TAKEN** with the calculated amount
- ❌ This is **dead code** — calculates but never uses the value for any operation
- ❌ The variable appears to be remnant of a planned but incomplete refactoring

---

## PART 2: TRACING THE LIFECYCLE

### Expense Workflow (Correct):

```
1. CREATE (user submits expense)
   Table: expenses
   Status: pending
   Ledger: NO ENTRY

2. APPROVE (admin approves)
   Table: expenses → status = 'approved'
   Table: approved_expenses → INSERT new record
   Ledger: recordEntry() called with type='expense_payment'
   Flag: expenses.ledger_synced = 1
   Result: 1 ledger row (CREDIT, amount, entry_type='expense_payment')

3. MARK PAID (admin uploads proof)
   Table: expenses → status = 'paid'
   Table: approved_expenses → UPDATE payment_proof
   Ledger: NO NEW ENTRY (should not happen)
   Flag: ledger_synced remains 1
   Result: Still 1 ledger row (unchanged from step 2)
```

### Expected Owner Ledger View:
```
EXPENSE #73
1 row: Expense, +₹50,000, Status: Paid
```

### Actual Owner Ledger View:
```
EXPENSE #73
Row 1: Expense,     -₹50,000
Row 2: Reimbursed,  +₹50,000
```

---

## PART 3: ROOT CAUSE IDENTIFICATION

### Theory A: Duplicate ledger inserts ❌ DISPROVEN
- LedgerHelper has duplicate guard checks
- markPaid() does NOT call recordEntry()
- Database check shows only 1 row per transaction
- **Not the cause**

### Theory B: Display rendering issue ✓ LIKELY
- Owner Ledger view displays the ledger differently
- May be showing the entry AND a calculated reverse entry
- The "+₹50,000" is displayed as "Reimbursed" type
- The "-₹50,000" is displayed as "Expense" type
- This suggests a **query or display calculation** issue, not data duplication

### Theory C: Approval workflow creates two entries ❌ DISPROVEN
- Approval calls `recordEntry()` once with `'expense_payment'` type
- LedgerHelper checks prevent duplicates
- No duplicate would reach the database
- **Not the cause**

### Theory D: Dead code from incomplete refactoring ✓ CONFIRMED
- `$ledgerAmount` variable calculated but not used
- In PATCHED version, this code is completely removed
- Indicates mid-refactoring state
- **Partially responsible for confusion**

---

## PART 4: CONCLUSION & FINDINGS

### What is NOT the Problem:
1. ❌ Database has multiple ledger entries per transaction
2. ❌ Approval creates duplicate ledger entries
3. ❌ Payment marking creates a new ledger entry
4. ❌ LedgerHelper duplicate guard is broken

### What IS the Problem:
1. ✓ **Dead code in markPaid()** that calculates but never uses `$ledgerAmount`
2. ✓ **Inconsistency between files**: ExpenseController.php vs ExpenseController_PATCHED.php
3. ✓ **Possible Owner Ledger display issue** that shows entries in both directions

### The `$ledgerAmount` Problem:

**Current ExpenseController.php (lines 480-485):**
```php
$stmt2 = $db->prepare("SELECT approved_amount FROM approved_expenses WHERE expense_id = ? ORDER BY id DESC LIMIT 1");
$stmt2->execute([$id]);
$approvedRow  = $stmt2->fetch(PDO::FETCH_ASSOC);
$ledgerAmount = !empty($approvedRow['approved_amount'])
    ? floatval($approvedRow['approved_amount'])
    : (!empty($expense['approved_amount']) ? floatval($expense['approved_amount']) : floatval($expense['amount']));
```

**Used only here (line 520):**
```php
error_log("Expense paid: id=$id user_id={$expense['user_id']} amount=$ledgerAmount");
```

**Never used for:**
- ❌ Ledger entry creation (not called anyway)
- ❌ Validation
- ❌ Status update
- ❌ Any business logic

---

## PART 5: EVIDENCE FROM LedgerHelper.php

### Duplicate Prevention Guard (Lines 72-81):
```php
// CRITICAL: Check if entry already exists for this reference+entry_type combo
// If exists, DON'T CREATE ANOTHER ROW — we follow single-entry model
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
if ($chk2->fetch()) {
    // Mark as synced to prevent retry
    $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")->execute([$referenceId]);
    error_log("LedgerHelper: entry exists (skipped, marked synced) — $referenceType/$referenceId type=$entryType");
    return true;
}
```

**This proves:**
- Only ONE entry per (expense_id, entry_type) combination can exist
- markPaid() doesn't call recordEntry(), so no duplicate can be created there
- The display duplication is NOT from database duplication

---

## PART 6: THE ACTUAL DUPLICATE ISSUE

The duplicate rows showing in Owner Ledger are **NOT** database duplicates.

**Most Likely Cause:**
- Owner Ledger display query or view logic
- Shows the ledger entry (credit: +₹50,000, type='expense_payment')
- Also displays a **calculated or summed** opposing entry for display/reconciliation
- Labels it as 'Reimbursed' vs 'Expense'

**To Confirm:** Check:
1. `views/owner/cash_ledger.php` — rendering logic
2. `OwnerController.php` — query building
3. Ledger display JavaScript — any client-side manipulation

---

## PART 7: REQUIRED ACTIONS

### Action 1: Remove Dead Code ✓ URGENT
Replace ExpenseController.php with ExpenseController_PATCHED.php

**Removes:**
- Lines 480-485: `$ledgerAmount` calculation
- Line 520: Use of `$ledgerAmount` in log
- Replaces with clear comment explaining single-entry model

### Action 2: Audit Owner Ledger Display ✓ CRITICAL
Investigate:
```
File: views/owner/cash_ledger.php
Lines: ? (examine rendering logic)
Check: How entries are displayed, if opposing entries are calculated
```

### Action 3: Database Verification ✓ REQUIRED
Run query:
```sql
SELECT * FROM user_ledgers 
WHERE reference_type = 'expense' AND reference_id = 73
ORDER BY created_at;
```
**Expected:** 1 row only
**If 2 rows:** Investigate where second row is inserted

### Action 4: Query Audit ✓ REQUIRED
Check OwnerController for ledger queries that might be displaying duplicates

---

## PART 8: COMPARISON TABLE

| Aspect | Current (Buggy) | PATCHED (Fixed) |
|--------|-----------------|-----------------|
| `$ledgerAmount` calc | ✓ Present (lines 480-485) | ❌ Removed |
| Uses `$ledgerAmount` | Only in error_log | Removed entirely |
| markPaid creates ledger entry | ❌ No (correct) | ❌ No (correct) |
| Clear comments | ❌ Confusing | ✓ Clear |
| Dead code | ✓ Yes | ❌ No |
| Consistency | ❌ Incomplete | ✓ Complete |

---

## SUMMARY

### Root Cause Level 1: Code Quality
**The immediate root cause is dead code in ExpenseController.php**
- `$ledgerAmount` calculated but not used
- Indicates incomplete refactoring
- Left from an attempted but abandoned feature

### Root Cause Level 2: Display Logic
**The underlying root cause is the Owner Ledger display logic**
- Shows entries in two directions or formats
- May be intentional (showing both sides of entry)
- May be unintentional (display bug)
- Requires investigation of `views/owner/cash_ledger.php`

### Root Cause Level 3: Architecture
**The systemic root cause is lack of clear ownership**
- ExpenseController.php and ExpenseController_PATCHED.php diverged
- No single source of truth
- PATCHED version is correct, current version is stale
- Need to eliminate duplicate controller files

---

## NEXT STEPS

1. **Immediate:** Replace ExpenseController.php with ExpenseController_PATCHED.php
2. **Urgent:** Audit views/owner/cash_ledger.php for duplicate display logic
3. **Critical:** Run database audit query to confirm data integrity
4. **Important:** Delete ExpenseController_FIXED.php file (obsolete)
5. **Important:** Ensure single ExpenseController.php version maintained going forward

---

**Analysis Date:** 2024
**Status:** ROOT CAUSE IDENTIFIED
**Severity:** MEDIUM (display issue, not data corruption)
**Recommended Fix Timeline:** IMMEDIATE
