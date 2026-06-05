# ✅ Ledger Schema Validation Guide

Quick reference for verifying the fix worked.

## 🚀 Deploy Fix (5 minutes)

### Step 1: Run Migration
```
1. Visit: https://yourdomain.com/ergon/migrations/run_migration.php
2. Wait for page to load
3. Look for green checkmark: "✓ User ledgers table created"
   OR "✓ Added created_by column to user_ledgers"
```

**Success Indicators:**
- ✅ Green success messages
- ✅ No red error boxes
- ✅ Page says "Migration Completed Successfully!"

**If Error:**
- [ ] Check database credentials in `app/config/database.php`
- [ ] Verify database user has ALTER TABLE permissions
- [ ] See `storage/logs/php-errors.log` for details

---

## 🔍 Verify Schema Fix

### In PhpMyAdmin:

**Query 1: Verify table exists**
```sql
SHOW TABLES LIKE 'user_ledgers';
```
Expected: One row with `user_ledgers`

**Query 2: Verify created_by column exists**
```sql
SHOW COLUMNS FROM user_ledgers LIKE 'created_by';
```
Expected: One row showing `created_by` column with type `int(11)`

**Query 3: Full table structure**
```sql
DESCRIBE user_ledgers;
```
Expected columns:
```
id                INT, Primary Key
user_id           INT
reference_type    VARCHAR(50)
reference_id      INT
entry_type        VARCHAR(50)
direction         VARCHAR(10)
amount            DECIMAL(12,2)
balance_after     DECIMAL(12,2)
created_by        INT          ← THIS IS WHAT WAS MISSING
created_at        TIMESTAMP
```

---

## ✅ Test Workflows (10 minutes)

### Test 1: Expense Approval
```
1. Login as Employee (role: user)
2. Go to Expenses → Create Expense
   - Category: Travel
   - Amount: 1000
   - Description: Test expense
   - Click Submit
   - See: "Expense submitted"
   
3. Logout, Login as Admin (role: admin)
4. Go to Expenses → Find test expense
5. Click Approve
   - Set approved amount: 900
   - Add remarks: "Partially approved"
   - Click Approve
   - See: "Expense approved successfully"
   
6. Check database:
   SELECT * FROM user_ledgers WHERE reference_type='expense' ORDER BY id DESC LIMIT 1;
   - Should show one row with created_by = admin's user_id
```

**Expected Result:**
- No 500 error
- Ledger entry created
- `created_by` field populated with admin ID

### Test 2: Advance Approval
```
1. Login as Employee
2. Go to Advances → Request Advance
   - Type: General
   - Amount: 5000
   - Reason: Project expense
   - Click Submit
   
3. Logout, Login as Admin
4. Go to Advances → Find test advance
5. Click Approve
   - Set approved amount: 5000
   - Add remarks: "Approved"
   - Click Approve
   - See: "Advance approved successfully"
   
6. Check database:
   SELECT * FROM user_ledgers WHERE reference_type='advance' ORDER BY id DESC LIMIT 1;
   - Should show one row with created_by = admin's user_id
```

**Expected Result:**
- No 500 error
- Ledger entry created
- `created_by` field populated

---

## 🎯 Success Validation

### All Checkpoints:

- [ ] Migration runs without errors
- [ ] `SHOW TABLES LIKE 'user_ledgers'` returns table
- [ ] `SHOW COLUMNS FROM user_ledgers LIKE 'created_by'` returns column
- [ ] Expense approval completes (no 500 error)
- [ ] Ledger entry shows in `user_ledgers` table
- [ ] `created_by` field in ledger entry matches approver's user_id
- [ ] Advance approval completes (no 500 error)
- [ ] Advance ledger entry shows with correct `created_by`
- [ ] Monthly report shows approved amounts
- [ ] No errors in `storage/logs/php-errors.log`

---

## 🐛 Troubleshooting

### Error: "Unknown column 'created_by'"
**Solution:**
```sql
ALTER TABLE user_ledgers ADD COLUMN created_by INT NULL;
```

### Error: "Column 'created_by' already exists"
**Not an error** - migration checked first. Just means it was already added.

### Error: "Access denied for user..."
**Solution:**
- Check database credentials in `app/config/database.php`
- Verify user has ALTER TABLE permissions
- User should be database owner or have full permissions

### Ledger entry not created
**Check:**
```sql
SELECT * FROM user_ledgers WHERE reference_type IN ('expense', 'advance') ORDER BY id DESC;
```

If empty:
- Check `storage/logs/php-errors.log`
- Verify `ledger_synced` flag in expenses/advances table
- Check LedgerHelper is being called (add debug log)

---

## 📊 Verification Queries (Ready to Copy-Paste)

### Full Validation Script for PhpMyAdmin

Run each query in order. All should show expected results.

```sql
-- 1. Table exists
SHOW TABLES LIKE 'user_ledgers';
-- Expected: 1 row

-- 2. Column exists
SHOW COLUMNS FROM user_ledgers LIKE 'created_by';
-- Expected: 1 row

-- 3. Full structure
DESCRIBE user_ledgers;
-- Expected: 10 rows including created_by

-- 4. Sample data (if available)
SELECT 
    id, 
    user_id, 
    reference_type, 
    entry_type, 
    amount, 
    direction, 
    created_by, 
    created_at
FROM user_ledgers 
ORDER BY id DESC 
LIMIT 5;
-- Expected: Rows with created_by populated

-- 5. Count by reference type
SELECT 
    reference_type, 
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM user_ledgers
GROUP BY reference_type;
-- Expected: Expenses and advances with their amounts
```

---

## ⏱️ Timeline

| Step | Task | Time | Status |
|------|------|------|--------|
| 1 | Run migration | 2-3 min | - |
| 2 | Verify schema (queries) | 2-3 min | - |
| 3 | Test expense approval | 3-5 min | - |
| 4 | Test advance approval | 3-5 min | - |
| 5 | Verify ledger entries | 2-3 min | - |
| **Total** | | **15-20 min** | ✅ |

---

## 🎓 What Was Fixed

**The Issue:**
- Expense/advance approval tried to create ledger entry with `created_by` field
- Database column didn't exist → SQL error

**The Fix:**
- Enhanced migration to detect and add missing `created_by` column
- Made migration idempotent (safe to run multiple times)

**Files Changed:**
- `migrations/run_migration.php` - Step 9 enhanced with column check

**No Changes Needed:**
- Code is correct (LedgerHelper, controllers)
- Schema definition is correct (migrations)
- Just needed to ensure database matches expected schema

---

## ✨ You're Done!

After running migration and passing all checks, the fix is complete.

Expense and advance approval workflows will:
- ✅ Create ledger entries
- ✅ Record approver audit trail (`created_by`)
- ✅ Calculate balances correctly
- ✅ Show in reports

**Questions?** Check the complete audit report: `LEDGER_SCHEMA_AUDIT.md`
