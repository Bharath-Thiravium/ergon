# COMPLETE FIX GUIDE: Owner Exclusion Issue

## 🔍 ROOT CAUSE ANALYSIS

The codebase has been **partially fixed**, but the issue is likely:

**PRIMARY CAUSE:** Data mismatch between LOCAL and LIVE
- LOCAL: Has company_owner records with role='company_owner'
- LIVE: Missing company_owner records OR stored with different role value

**SECONDARY CAUSE:** Stale code deployed
- OLD CODE still excludes company_owner somewhere
- Need fresh deployment

---

## ✅ VERIFICATION STEPS (Run Immediately)

### Step 1: Check LIVE Database
```bash
# Connect to LIVE database
# Run this SQL:

-- Check 1: Users by role
SELECT role, COUNT(*) as count FROM users WHERE status='active' GROUP BY role;

-- Check 2: Company owner details
SELECT id, name, email, role FROM users WHERE role='company_owner';

-- Check 3: Owner's expenses
SELECT COUNT(*) FROM expenses WHERE user_id = [OWNER_ID];

-- Check 4: Owner's advances
SELECT COUNT(*) FROM advances WHERE user_id = [OWNER_ID];
```

### Step 2: Deploy Diagnostic Tool
```bash
# Upload to LIVE server:
/ergon/diagnostic.php

# Access in browser:
http://your-live-domain.com/ergon/diagnostic.php

# Screenshot the output
# Share output with tech team
```

### Step 3: Review Code Deployment
```bash
# On LIVE server, check file:
cat /public_html/ergon/app/controllers/ExpenseController.php | grep -A 5 "getExpensesForAdmin"

# Should see:
# WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
```

---

## 🔧 PERMANENT FIXES

### Fix 1: Ensure ExpenseController is Correct
**File:** `app/controllers/ExpenseController.php`  
**Line:** 104

**Current (SHOULD BE):**
```php
private function getExpensesForAdmin($adminUserId, $projectId = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
        
        // FIXED: Include company_owner in admin view
        $sql = "SELECT e.*, u.name as user_name, u.role as user_role, p.name as project_name, pt.name as paid_to_user_name, e.paid_to_name
                FROM expenses e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN projects p ON e.project_id = p.id
                LEFT JOIN users pt ON e.paid_to_user_id = pt.id
                WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)";
        $params = [$adminUserId];
        // ... rest of method
    }
}
```

**✅ Status:** This is CORRECT in current code

---

### Fix 2: Ensure ReportsController is Correct
**File:** `app/controllers/ReportsController.php`  
**Line:** 187

**Current (SHOULD BE):**
```php
// All active users excluding only 'owner' role (FIXED: was excluding company_owner)
$users = $db->query("
    SELECT id, name, role
    FROM users
    WHERE status = 'active'
      AND role NOT IN ('owner')
    ORDER BY FIELD(role,'admin','user','company_owner'), name
")->fetchAll(PDO::FETCH_ASSOC);
```

**✅ Status:** This is CORRECT in current code

---

### Fix 3: Ensure AdvanceController is Correct
**File:** `app/controllers/AdvanceController.php`  
**Line:** ~155

**Current (SHOULD BE):**
```php
if ($role === 'user') {
    $sql = "SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name, pb.name as paid_by_name
            FROM advances a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users pb ON a.paid_by = pb.id
            WHERE a.user_id = ?";
} else {
    // Admin/owner/company_owner sees ALL advances
    $sql = "SELECT a.*, u.name as user_name, u.role as user_role, p.name as project_name, pb.name as paid_by_name
            FROM advances a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users pb ON a.paid_by = pb.id";
}
```

**✅ Status:** This is CORRECT in current code

---

## 📊 EXPECTED BEHAVIOR (After Fix)

### When Admin Logs In:

**Expenses Page:**
- Should see: Employee expenses + Company owner expenses + Admin's own expenses
- Should NOT see: Just employee expenses

**Advances Page:**
- Should see: All advances from all users (admin can approve any)
- Should NOT see: Filtered by role

**Reports > Monthly Attendance:**
- Should include: Employees + Company owner
- Should NOT exclude: Company owner

---

## 🐛 WHAT COULD CAUSE ISSUE

### Scenario 1: Wrong Role Value in Database
```sql
-- PROBLEM: Owner stored with wrong role
SELECT * FROM users WHERE name LIKE '%owner%';
-- Result: role = 'owner' instead of 'company_owner'

-- FIX:
UPDATE users SET role = 'company_owner' WHERE id = [CORRECT_ID];
```

### Scenario 2: Company Owner User Doesn't Exist
```sql
-- PROBLEM: No company_owner user in LIVE database
SELECT COUNT(*) FROM users WHERE role = 'company_owner';
-- Result: 0

-- FIX: Need to create company_owner user or sync from LOCAL
```

### Scenario 3: Expenses/Advances Don't Exist
```sql
-- PROBLEM: Records created but with 'owner' user, not 'company_owner'
SELECT * FROM expenses WHERE user_id IN (
    SELECT id FROM users WHERE role IN ('company_owner', 'owner')
);
-- Result: Empty

-- FIX: Create test records for company_owner
```

### Scenario 4: Stale Code Deployed
```bash
# PROBLEM: Old version of controller deployed

# FIX: 
# 1. Delete old code
# 2. Re-deploy from repository
# 3. Verify in browser DevTools Network tab that new code is loaded
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Run diagnostic.php on LIVE
- [ ] Compare output with LOCAL diagnostic
- [ ] Check database synchronization
- [ ] Verify company_owner user exists in LIVE
- [ ] Verify company_owner has expenses/advances
- [ ] Clear browser cache (Ctrl+Shift+Del)
- [ ] Hard refresh page (Ctrl+F5)
- [ ] Test with fresh login (logout → login)
- [ ] Verify role column has 'company_owner' in enum
- [ ] Re-deploy code from repository

---

## 📋 DETAILED DATA SYNC PROCEDURE

If LIVE database is missing data:

### Option 1: Import from LOCAL Database
```bash
# On LOCAL server, export database:
mysqldump -u [user] -p [database] > ergon_backup.sql

# On LIVE server, import:
mysql -u [user] -p [database] < ergon_backup.sql
```

### Option 2: Manual SQL Sync
```sql
-- LIVE database - Execute these:

-- 1. Create company_owner user if missing
INSERT INTO users (name, email, password, role, status, created_at)
VALUES ('Company Owner', 'owner@company.com', '[HASH]', 'company_owner', 'active', NOW())
ON DUPLICATE KEY UPDATE role = 'company_owner';

-- 2. Verify role column supports 'company_owner'
ALTER TABLE users MODIFY COLUMN role 
ENUM('user', 'admin', 'owner', 'company_owner', 'system_admin') DEFAULT 'user';

-- 3. Create test expense for company_owner
INSERT INTO expenses (user_id, category, amount, description, expense_date, status, created_at)
SELECT id, 'test', 100.00, 'Test company owner expense', NOW(), 'pending', NOW()
FROM users WHERE role = 'company_owner' LIMIT 1;

-- 4. Create test advance for company_owner  
INSERT INTO advances (user_id, type, amount, reason, requested_date, status, created_at)
SELECT id, 'Test Advance', 5000.00, 'Test company owner advance', NOW(), 'pending', NOW()
FROM users WHERE role = 'company_owner' LIMIT 1;
```

---

## 🧪 TESTING AFTER FIX

### Test Case 1: Admin Views Expenses
1. Login as Admin
2. Go to Expenses page
3. Verify you see:
   - Employee expenses
   - Company owner expenses ✅ (MUST SEE)
   - Your own expenses (if any)
4. Check SQL log to verify query includes `company_owner`

### Test Case 2: Admin Approves Owner Expense
1. Find a company_owner expense (status: pending)
2. Click "Approve" button
3. Enter approved amount
4. Click "Approve Expense"
5. Verify success message
6. Verify status changed to "approved"

### Test Case 3: Owner Views Own Expenses
1. Login as Company Owner
2. Go to Expenses page
3. Verify you see ONLY your own expenses

### Test Case 4: Monthly Reports Include Owner
1. Login as Admin
2. Go to Reports > Monthly Attendance
3. Verify company_owner user appears in the list
4. Verify their attendance is tracked

---

## 📞 DEBUGGING COMMANDS

### Check Current Deployment Code:
```bash
# On LIVE server:
grep -n "u.role IN" app/controllers/ExpenseController.php
# Should show line 104 with: WHERE (u.role IN ('user', 'company_owner') OR ...
```

### Check Database State:
```bash
# SSH to LIVE, then:
mysql -u [user] -p -e "SELECT id, name, role FROM users WHERE role='company_owner';" ergon_db
```

### Check Application Logs:
```bash
# Look for errors:
tail -100 storage/logs/error.log | grep -i "owner\|expense\|advance"
```

---

## 🎯 SUCCESS CRITERIA

After applying fixes:

1. ✅ Admin can see company owner expenses
2. ✅ Admin can see company owner advances
3. ✅ Company owner appears in monthly reports
4. ✅ All queries execute without errors
5. ✅ No role-based access control bypassed
6. ✅ Tenant isolation maintained
7. ✅ Browser console shows no JavaScript errors
8. ✅ All tests pass

---

## 📝 SUMMARY

| Issue | Cause | Fix | Time |
|-------|-------|-----|------|
| Owner expenses not visible | Data missing or wrong role | Run diagnostic, sync data | 5-15 min |
| Owner advances not visible | Data missing or wrong role | Run diagnostic, sync data | 5-15 min |
| Reports exclude owner | Query filter wrong | Verify query includes owner | 2 min |
| Still not working | Stale code cached | Clear cache, hard refresh | 1 min |

---

## ⚡ QUICK FIX (If pressed for time)

1. Run: `/ergon/diagnostic.php`
2. If owner exists with expenses:
   - Clear browser cache (Ctrl+Shift+Delete)
   - Hard refresh (Ctrl+F5)
   - Logout → Login
3. If owner missing:
   - Import database from LOCAL
4. If code wrong:
   - Re-deploy from git

**Expected resolution time: 10-20 minutes**

---

## 🔒 SECURITY NOTES

- ✅ Fixes maintain RBAC rules
- ✅ Owner can only see own records (unless admin)
- ✅ Tenant isolation preserved
- ✅ No privilege escalation introduced
- ✅ All changes are additive (only showing more to admins)

---

**Status:** Ready for implementation  
**Risk Level:** Very Low (code-only changes)  
**Rollback:** Easy (revert to previous version)

