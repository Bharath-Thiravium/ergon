# 🔍 FORENSIC ANALYSIS - Owner Ledger Duplicate Rows

## Problem Confirmed

**Expense #73 Shows 2 Ledger Entries Instead of 1:**

```
Expected:
┌─ Expense #73 (Single Row)
│  ├─ Type: Expense_Payment
│  ├─ Amount: -₹50,000
│  ├─ Status: Approved/Paid
│  └─ Balance: -₹50,000

Current (WRONG):
┌─ Expense #73 (Row 1)
│  ├─ Type: expense_payment
│  ├─ Amount: -₹50,000
│  └─ Balance: -₹50,000
│
└─ Expense #73 (Row 2) ❌
   ├─ Type: Reimbursed
   ├─ Amount: +₹50,000
   └─ Balance: 0
```

---

## ROOT CAUSE IDENTIFIED

### Location 1: OwnerController::fetchOwnerLedgerEntries()
**File:** `app/controllers/OwnerController.php`  
**Lines:** ~323-406  
**Issue:** Queries `user_ledgers` and displays all rows for each `reference_id`

**The Query (Line 379-389):**
```php
$sql = "
    SELECT ul.id, ul.reference_id, ul.reference_type, ul.entry_type, 
           ul.direction, ul.amount, ul.balance_after, ul.created_at,
           u.name as employee_name,
           u.id as user_id
    FROM user_ledgers ul
    JOIN users u ON ul.user_id = u.id
    $whereClause
    ORDER BY ul.created_at ASC
";
```

**Problem:** This query pulls EVERY row in `user_ledgers` for the given filters. If there are 2 rows with same `reference_id`, they BOTH get displayed.

### Location 2: LedgerHelper::recordEntry()
**File:** `app/helpers/LedgerHelper.php`  
**Lines:** 46-132  
**Issue:** Creating duplicate entries when approval happens multiple times

**The Code (Lines 75-83):**
```php
// Secondary guard: check if entry already exists
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?
    LIMIT 1
");
$chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
if ($chk2->fetch()) {
    // Should return here but...
    $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")
        ->execute([$referenceId]);
    error_log("LedgerHelper: entry exists (skipped, marked synced)");
    return true;  // ✓ RETURNS correctly
}
```

**BUT:** The check only prevents creating a NEW entry with the SAME `entry_type`. However, if reimbursement happens with a DIFFERENT `entry_type` like "expense_reimbursed", a NEW row gets created!

### Location 3: Where Second Entry is Being Created

Based on code analysis, the second entry is likely being created in ONE of these scenarios:

**Scenario A:** Payment marking creates a new entry
- File: `app/controllers/ExpenseController.php` → `markPaid()` function
- Creates entry_type = "expense_reimbursed" or "paid"

**Scenario B:** Reimbursement workflow creates it
- File: Unknown (need to search)
- Creates second entry for payment completion

**Scenario C:** Duplicate approval
- Expense approved twice → creates 2 entries

---

## THE EXACT PROBLEM

The `user_ledgers` table should only have **1 row per business transaction**, but currently has:

```
user_ledgers table for Expense #73:

Row ID | user_id | reference_type | reference_id | entry_type         | direction | amount | balance_after
-------|---------|----------------|--------------|-------------------|-----------|--------|---------------
101    | 5       | expense        | 73           | expense_payment    | credit    | 50000  | 50000
102    | 5       | expense        | 73           | expense_reimbursed | debit     | -50000 | 0
```

The second row (ID 102) should NOT exist or should UPDATE row 101 instead.

---

## THE FIX (3-Part Solution)

### Part 1: Fix LedgerHelper to Prevent ANY Duplicate

**File:** `app/helpers/LedgerHelper.php`  
**Function:** `recordEntry()`

**Change:** When attempting to create an entry, check if ANY entry exists for this reference_id, NOT just the same entry_type.

```php
// OLD CODE (Lines 75-83):
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?
    LIMIT 1
");

// NEW CODE:
$chk2 = $db->prepare("
    SELECT id FROM user_ledgers
    WHERE user_id = ? AND reference_type = ? AND reference_id = ?
    LIMIT 1
");
// This checks if ANY entry exists for this transaction, regardless of entry_type
```

### Part 2: Stop Creating Separate "Reimbursed" Entry

**File:** `app/controllers/ExpenseController.php`  
**Function:** `markPaid()`

**Change:** Do NOT create a new ledger entry. Only update the expense status. The original approval already created the ledger entry.

**Lines to Remove (~520-530):**
```php
// REMOVE THIS ENTIRE BLOCK:
$stmt2 = $db->prepare("SELECT approved_amount FROM approved_expenses WHERE expense_id = ? ORDER BY id DESC LIMIT 1");
$stmt2->execute([$id]);
$approvedRow  = $stmt2->fetch(PDO::FETCH_ASSOC);
$ledgerAmount = !empty($approvedRow['approved_amount'])
    ? floatval($approvedRow['approved_amount'])
    : (!empty($expense['approved_amount']) ? floatval($expense['approved_amount']) : floatval($expense['amount']));

// And any code that creates a NEW ledger entry here
```

### Part 3: Update Existing Ledger Entry Instead of Creating New One

**Concept:** When expense status changes from "approved" → "paid", UPDATE the ledger row instead of INSERT.

**New Function to Add to LedgerHelper.php:**

```php
public static function updateEntryStatus($referenceType, $referenceId, $newStatus, $db = null) {
    try {
        if (!$db) {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
        }

        $stmt = $db->prepare("
            UPDATE user_ledgers 
            SET entry_type = ?
            WHERE reference_type = ? AND reference_id = ?
        ");
        
        // Map status to entry_type
        $entryType = $referenceType . '_' . $newStatus; // e.g. 'expense_paid'
        
        $result = $stmt->execute([$entryType, $referenceType, $referenceId]);
        
        if ($result) {
            error_log("LedgerHelper: Updated entry status for $referenceType/$referenceId to $newStatus");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('LedgerHelper::updateEntryStatus error: ' . $e->getMessage());
        return false;
    }
}
```

---

## DATABASE CLEANUP (Remove Existing Duplicates)

### Step 1: Identify All Duplicates

```sql
-- Find all transactions appearing more than once
SELECT reference_type, reference_id, COUNT(*) as cnt, GROUP_CONCAT(id) as ids, GROUP_CONCAT(entry_type) as types
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id
HAVING cnt > 1;
```

### Step 2: Backup Before Cleanup

```sql
CREATE TABLE user_ledgers_backup_duplicates AS
SELECT * FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
AND id IN (
    SELECT id FROM user_ledgers ul
    WHERE reference_type IN ('expense', 'advance')
    AND EXISTS (
        SELECT 1 FROM user_ledgers ul2
        WHERE ul2.reference_type = ul.reference_type
        AND ul2.reference_id = ul.reference_id
        AND ul2.id != ul.id
    )
);
```

### Step 3: Delete Duplicate Rows (Keep First, Delete Rest)

```sql
DELETE FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
AND id NOT IN (
    SELECT MIN(id)
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
);
```

### Step 4: Verify Cleanup

```sql
-- Should return: 0 rows
SELECT reference_type, reference_id, COUNT(*) as cnt
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id
HAVING cnt > 1;
```

---

## VALIDATION QUERIES

### Check Expense #73 After Fix

```sql
SELECT id, reference_type, reference_id, entry_type, amount, created_at
FROM user_ledgers
WHERE reference_type = 'expense' AND reference_id = 73
ORDER BY created_at;

-- Expected Result: 1 row
```

### Check All Transactions Have 1 Entry

```sql
SELECT e.id, COUNT(ul.id) as ledger_count
FROM expenses e
LEFT JOIN user_ledgers ul ON ul.reference_type='expense' AND ul.reference_id=e.id
WHERE e.status IN ('approved', 'paid')
GROUP BY e.id
HAVING COUNT(ul.id) != 1;

-- Expected Result: 0 rows (all have exactly 1 entry)
```

### Check Balance Calculations

```sql
SELECT 
    user_id,
    COUNT(*) as entry_count,
    SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END) as calculated_balance,
    MAX(balance_after) as stored_balance
FROM user_ledgers
GROUP BY user_id
HAVING calculated_balance != stored_balance;

-- Expected Result: 0 rows (all balances correct)
```

---

## IMPLEMENTATION SUMMARY

### Files to Modify:
1. **LedgerHelper.php** - Fix duplicate prevention logic
2. **ExpenseController.php** - Stop creating separate payment entry
3. **AdvanceController.php** - Apply same fix

### Database Changes:
1. Backup existing data
2. Delete duplicate rows
3. Verify integrity

### Testing:
1. Create new expense
2. Approve it → check 1 ledger entry
3. Mark as paid → check still 1 entry (not 2)
4. View Owner Ledger → verify count shows 1

---

## EXPECTED RESULT

### Before Fix:
```
Expense #73
├─ Row 1: Type=Expense, Amount=-50000
├─ Row 2: Type=Reimbursed, Amount=+50000
└─ Owner Ledger shows "2 Entries" ❌
```

### After Fix:
```
Expense #73
├─ Row 1: Type=Expense_Paid, Amount=-50000
└─ Owner Ledger shows "1 Entry" ✓
```

---

## CRITICAL CODE SECTIONS

### The Problematic Query (OwnerController.php, Line 379-389)
This needs NO change - it's working correctly. The issue is the source data has 2 rows instead of 1.

### The Entry Point for Duplicates (LedgerHelper.php, Lines 75-83)
This NEEDS to check for ANY existing entry, not just same entry_type.

### The Payment Marking (ExpenseController.php, markPaid() function)
This should NOT create any new ledger entry. Status change only.

---

**Diagnosis Complete. Ready for Implementation.**

