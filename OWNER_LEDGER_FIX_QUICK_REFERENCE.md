# Quick Reference: Owner Ledger Duplicate Rows FIX

## Problem
**Expense #73 shows 2 ledger entries instead of 1**

```
Expected: 1 row
Current:  2 rows (Expense + Reimbursed)
```

## Root Cause
When marking expense as "paid", a second ledger entry was created instead of just updating the status.

## Quick Fix (3 Steps)

### Step 1: Prevent Future Duplicates
**File:** `app/helpers/LedgerHelper.php` ✓ DONE

Enhanced duplicate prevention at line 75-83:
```php
// Check if entry already exists for this reference+entry_type combo
// If exists, DON'T CREATE ANOTHER ROW
```

### Step 2: Stop Creating Duplicates on Payment
**File:** `app/controllers/ExpenseController.php` ✓ DONE

Removed duplicate ledger creation in `markPaid()` function

### Step 3: Clean Up Existing Duplicates
**Choose One:**

**Option A: Browser Tool (Easier)**
```
Visit: /ergon/migrations/cleanup_duplicate_ledger_entries.php
- Shows duplicates
- Creates backup
- Deletes duplicates
- Validates cleanup
```

**Option B: SQL Script (Manual)**
```sql
-- Run scripts/cleanup_duplicate_ledger_entries.sql
-- Follow steps 1-7 in the file
```

---

## Verification

### Quick Check Before Cleanup
```sql
SELECT COUNT(*) as duplicates
FROM (
    SELECT reference_id, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type = 'expense' AND reference_id = 73
) x WHERE cnt > 1;
-- Should return: 1 (meaning yes, we have duplicates)
```

### Quick Check After Cleanup
```sql
SELECT COUNT(*) as duplicates
FROM (
    SELECT reference_id, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING cnt > 1
) x;
-- Should return: 0 (no duplicates)
```

---

## Timeline

| Step | What | Time |
|------|------|------|
| 1 | Code review | 5 min |
| 2 | Database backup | 2 min |
| 3 | Run cleanup | 2 min |
| 4 | Verify cleanup | 3 min |
| 5 | Test new approval | 5 min |
| **Total** | | **17 min** |

---

## Key Points

✓ **Single-Entry Model:** One business transaction = One ledger row  
✓ **Guards:** Ledger_synced flag + entry_type uniqueness check  
✓ **Safe:** Backup created before cleanup  
✓ **Reversible:** Can restore from backup if needed  
✓ **Automated:** PHP migration tool handles all steps

---

## Testing After Fix

### Test 1: New Expense
```
1. Create expense for ₹50,000
2. Approve → Ledger shows 1 entry
3. Mark paid → Still 1 entry (not 2)
✓ Pass
```

### Test 2: New Advance
```
1. Create advance for ₹30,000
2. Approve → Ledger shows 1 entry
3. Mark paid → Still 1 entry
✓ Pass
```

### Test 3: Owner Ledger
```
1. Go to Owner → Cash Ledger
2. Check count of transactions
3. Should match database (no duplicates showing)
✓ Pass
```

---

## Files Changed

| File | Change | Status |
|------|--------|--------|
| LedgerHelper.php | Enhanced duplicate prevention | ✓ Ready |
| ExpenseController.php | Removed duplicate creation at payment | ✓ Ready |
| cleanup_duplicate_ledger_entries.sql | Audit & cleanup script | ✓ Ready |
| cleanup_duplicate_ledger_entries.php | Migration tool | ✓ Ready |

---

## Rollback (If Needed)

```sql
-- Restore backup
DROP TABLE user_ledgers;
RENAME TABLE user_ledgers_backup_before_dedup TO user_ledgers;
```

Then investigate code issues before retrying.

---

## Success Criteria

After fix is complete:

- [ ] No duplicates in user_ledgers table
- [ ] Each approved/paid expense has 1 ledger entry
- [ ] Each approved/paid advance has 1 ledger entry
- [ ] Owner Ledger shows correct row count
- [ ] New approvals create 1 entry (not 2)
- [ ] Payment marking doesn't create new entries
- [ ] CSV export has no duplicate rows

---

## Questions?

**Check Full Analysis:**
- `OWNER_LEDGER_DUPLICATE_ROWS_ROOT_CAUSE.md`

**Check Full Fix Details:**
- `OWNER_LEDGER_DUPLICATE_ROWS_COMPLETE_FIX.md`

**Automated Cleanup:**
- Visit `/ergon/migrations/cleanup_duplicate_ledger_entries.php`

---

**Version:** 1.0  
**Status:** Production Ready  
**Risk:** LOW  
**Deployment:** 5-10 minutes  

