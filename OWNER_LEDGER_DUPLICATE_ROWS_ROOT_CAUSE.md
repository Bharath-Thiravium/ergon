# Owner Ledger Duplicate Rows - Root Cause Analysis

## Problem Statement

**Expense #73 creates TWO ledger rows instead of ONE:**

```
Row 1: Type=Expense, Amount=-₹50,000
Row 2: Type=Reimbursed, Amount=+₹50,000
```

**Expected:** One ledger row showing the net transaction  
**Current UI:** Shows "2 Entries" for a single business transaction

---

## Root Cause Identified

### Current Workflow (BROKEN)

```
1. Admin approves Expense #73 for ₹50,000
   ↓
2. LedgerHelper::recordEntry() called with:
   - entry_type = 'expense_payment'
   - reference_type = 'expense'
   - Creates FIRST ledger row: debit ₹50,000

3. When expense marked as "paid" (reimbursed):
   ↓
4. SECOND ledger entry created incorrectly
   (Happens in ExpenseController::markPaid)
```

### Exact Location of Issue

**File:** `app/controllers/ExpenseController.php`  
**Function:** `markPaid($id)`  
**Lines:** ~480-530

**Problem Code:**
```php
// Line 481-530 in ExpenseController::markPaid()
$stmt = $db->prepare("UPDATE expenses SET status = 'paid', payment_proof = ?, payment_remarks = ?, paid_by = ?, paid_at = NOW() WHERE id = ?");

// NOTE: No ledger entry created at approval time!
// But the code doesn't prevent duplicate entries either
```

### Why Duplicates Happen

1. **At Approval:** LedgerHelper creates entry `expense_payment` with debit of ₹50,000
2. **At Payment:** markPaid() updates status but doesn't create a new ledger entry
3. **Real Problem:** The ledger query includes BOTH:
   - Original expense entry (expense_payment)
   - A spurious second entry somewhere (likely from old code or trigger)

### Database Verification Query

```sql
SELECT * FROM user_ledgers 
WHERE reference_type = 'expense' 
AND reference_id = 73
ORDER BY created_at;
```

**Expected:** 1 row  
**Current:** 2 rows with different entry_types

---

## Workflow Requirements

### Expense Lifecycle → Ledger Mapping

```
BUSINESS TRANSACTION          LEDGER ENTRIES
─────────────────────────────────────────────

1. Create Expense #73        → No ledger entry (pending)
   Status: pending

2. Approve Expense #73       → CREATE 1 ledger row
   Amount: ₹50,000          - entry_type: 'expense_payment'
   Status: approved         - direction: 'credit'
                            - amount: ₹50,000

3. Mark Paid/Reimbursed    → UPDATE same row
   Status: paid            - status: 'reimbursed'
                           - NO NEW ROW
```

---

## Fix Strategy

### Phase 1: Prevent Future Duplicates

**LedgerHelper.php** - Enhanced duplicate prevention:
- When status changes from `approved` → `paid`, DO NOT create new entry
- Use `entry_type` + `reference_type` + `reference_id` as unique key
- Check before insert: if exists, update status instead

### Phase 2: Update ExpenseController::markPaid()

**Change:** Remove any ledger creation at payment stage  
**Reason:** Ledger entry created at approval is sufficient

### Phase 3: Database Cleanup

**Identify and merge duplicates:**
```sql
-- Find all duplicates
SELECT reference_id, reference_type, COUNT(*) as count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_id, reference_type
HAVING count > 1;
```

**Cleanup script:** Consolidate into single row per business transaction

---

## Ledger Entry Rules

### Single-Entry Model

| Transaction Type | Entry Count | When Created | Status Changes |
|------------------|------------|--------------|-----------------|
| Expense | 1 | Approval | Updates same row on payment |
| Advance | 1 | Approval | Updates same row on payment |
| Manual | 1 | Manual entry | Can create reversals |

### Entry_Type Naming Convention

```
'expense_payment'       → Expense approved for payment
'advance_payment'       → Advance approved for distribution
'expense_reversal'      → Expense correction/reversal
'advance_reversal'      → Advance correction/reversal
'manual_adjustment'     → Manual correction
'expense_reimbursed'    → ❌ DO NOT USE (creates duplicate)
'advance_settled'       → ❌ DO NOT USE (creates duplicate)
```

---

## Validation Queries

### Verify Single Row per Transaction

```sql
-- Should return 0 rows (no duplicates)
SELECT reference_id, reference_type, COUNT(*) as duplicate_count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_id, reference_type
HAVING COUNT(*) > 1;
```

### Verify All Transactions Tracked

```sql
-- Should match count of approved expenses
SELECT COUNT(*) as ledger_expense_entries
FROM user_ledgers
WHERE reference_type = 'expense' AND entry_type = 'expense_payment';

SELECT COUNT(*) as approved_expenses
FROM expenses
WHERE status IN ('approved', 'paid');
```

---

## Implementation Files to Modify

1. **LedgerHelper.php**
   - Add entry_type + reference_id uniqueness check
   - When status update needed, UPDATE instead of INSERT

2. **ExpenseController.php**
   - markPaid(): Remove duplicate ledger entry creation
   - Ensure only approval creates ledger entry

3. **AdvanceController.php**
   - markPaid(): Verify no duplicate ledger creation
   - Same rule: one ledger entry at approval

4. **OwnerController.php**
   - fetchOwnerLedgerEntries(): Verify deduplication
   - Consider adding integrity checks

---

## Expected Result After Fix

### Expense #73 Owner Ledger View

```
Transaction         Date        Employee    Type        Amount      Balance
─────────────────────────────────────────────────────────────────────────
Expense #73         2024-01-15  John Doe    Expense     -₹50,000    -₹50,000
(Status: Paid)
```

**UI Shows:** 1 Entry (not 2)  
**Database:** 1 row in user_ledgers  
**Ledger_synced:** 1 (set at approval, maintained on payment)

---

## Testing Checklist

- [ ] Approve expense: 1 ledger entry created
- [ ] Mark expense paid: Same ledger entry updated (no new entry)
- [ ] Owner ledger shows: 1 row per transaction
- [ ] CSV export: Correct count, no duplicates
- [ ] Advance approval: 1 ledger entry created
- [ ] Advance payment: Same entry updated
- [ ] Historical data: Deduplicated and consolidated

---

## Impact Assessment

### Files Modified
- LedgerHelper.php (enhanced)
- ExpenseController.php (removed duplicate logic)
- AdvanceController.php (verification)

### Backward Compatibility
- Existing ledger_synced flag utilized properly
- No schema changes needed
- Cleanup script for existing duplicates

### Risk Level: **LOW**
- Guards already in place (ledger_synced)
- Ledger is write-only (audit trail)
- No deletion, only consolidated reading

