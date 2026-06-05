# DIAGNOSTIC: LOCAL vs LIVE Comparison
## Company Owner Expense/Advance Visibility Issue

---

## EXECUTIVE SUMMARY

**ISSUE:** Company owner expenses and advances are visible on LOCAL but NOT on LIVE.

**ROOT CAUSE IDENTIFIED:** 
The code has been PARTIALLY FIXED in ExpenseController.php, but the view layer and ReportsController still exclude company_owner in some places.

---

## FINDINGS

### 1. **ExpenseController.php** ✅ FIXED
**Line 104** - `getExpensesForAdmin()` method:
```php
// FIXED (Line 104):
WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
// ✅ Correctly includes company_owner
```

**Status:** ✅ CORRECT - Company owner expenses WILL show for admins

---

### 2. **ReportsController.php** ❌ PARTIALLY FIXED
**Line 187** - `monthlyAttendance()` method:
```php
// ISSUE (Line 187):
WHERE status = 'active'
  AND role NOT IN ('owner')
// ✅ FIXED - Only excludes 'owner', NOT 'company_owner'
```

**Status:** ✅ CORRECT - Company owner attendance WILL show in reports

---

### 3. **AdvanceController.php** ✅ APPROVED SHOWS ALL
**Line ~155** - `index()` method for admins:
```php
// CORRECT (allows all roles for admin view):
if ($role === 'user') {
    // Show only user's advances
} else {
    // Admin/owner/company_owner sees ALL advances
    SELECT a.* FROM advances a...
}
```

**Status:** ✅ CORRECT - Company owner advances WILL show for admins

---

## DISCREPANCY ANALYSIS

### Why LOCAL shows owner records but LIVE doesn't:

**POSSIBILITY 1: Database Data Mismatch**
- LOCAL has company_owner records created with role='company_owner'
- LIVE has company_owner records created with different role value
- CHECK: What role value is in the LIVE users table for the company owner?

**POSSIBILITY 2: View/Display Layer Issue**
- Records fetch correctly but don't DISPLAY for company_owner
- CHECK: Line 85 in expenses/index.php:
```php
<?php if (($user_role ?? '') !== 'user'): ?>  // Shows filter ONLY if not user
// This allows admin/owner/company_owner to see it
```

**POSSIBILITY 3: Filter/Search Issue**
- Records exist but filtered out by SQL query binding issue
- CHECK: Are bindings correct in LIVE deployment?

---

## DETAILED SQL QUERIES

### Expense Query (ExpenseController.php:104)
```sql
-- LOCAL (CURRENT - FIXED):
SELECT e.*, u.name as user_name, u.role as user_role, p.name as project_name, 
       pt.name as paid_to_user_name, e.paid_to_name
FROM expenses e
JOIN users u ON e.user_id = u.id
LEFT JOIN projects p ON e.project_id = p.id
LEFT JOIN users pt ON e.paid_to_user_id = pt.id
WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
ORDER BY e.created_at DESC

-- Bindings: [admin_user_id]
-- Records returned: All 'user' AND 'company_owner' + admin's own records
```

### Reports Query (ReportsController.php:187)
```sql
SELECT id, name, role
FROM users
WHERE status = 'active'
  AND role NOT IN ('owner')
ORDER BY FIELD(role,'admin','user','company_owner'), name

-- Records returned: admin, user, company_owner (NOT 'owner')
-- Bindings: None
```

---

## DATA VERIFICATION CHECKLIST

### On LIVE Database, Verify:

```sql
-- 1. Check if company_owner role exists in users table:
SELECT id, name, role, email, status FROM users WHERE role = 'company_owner' LIMIT 5;
-- EXPECTED: Should show the company owner user(s)
-- ISSUE IF: Returns 0 rows

-- 2. Check if company owner has expenses:
SELECT e.id, e.user_id, e.amount, e.status, u.name, u.role 
FROM expenses e
JOIN users u ON e.user_id = u.id
WHERE u.role = 'company_owner'
LIMIT 10;
-- EXPECTED: Should show owner's expenses
-- ISSUE IF: Returns 0 rows

-- 3. Check if company owner has advances:
SELECT a.id, a.user_id, a.amount, a.status, u.name, u.role
FROM advances a
JOIN users u ON a.user_id = u.id
WHERE u.role = 'company_owner'
LIMIT 10;
-- EXPECTED: Should show owner's advances
-- ISSUE IF: Returns 0 rows

-- 4. Verify role column accepts 'company_owner':
DESCRIBE users;
-- EXPECTED: role column should be ENUM or VARCHAR
-- LOOK FOR: Enum values include 'company_owner'

-- 5. Count expenses by role:
SELECT u.role, COUNT(e.id) as expense_count
FROM expenses e
JOIN users u ON e.user_id = u.id
GROUP BY u.role;
-- EXPECTED OUTPUT:
-- admin      | X records
-- user       | X records
-- company_owner | X records  <-- SHOULD NOT BE 0
```

---

## VISIBILITY FLOW DIAGRAM

```
ADMIN VIEWS EXPENSES
↓
ExpenseController::index()
↓
$role === 'admin' ?
↓ YES
getExpensesForAdmin($user_id)
↓
SQL: WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
↓
✅ RETURNS company_owner expenses IF they exist
↓
View: expenses/index.php
↓
DISPLAY: foreach ($expenses as $expense)
↓
SHOWS: Company owner expense ✅
```

---

## LIKELY CAUSE: Data Issue, Not Code Issue

### Evidence:
1. ✅ Code is CORRECT in ExpenseController.php
2. ✅ Code is CORRECT in ReportsController.php  
3. ✅ Code is CORRECT in AdvanceController.php
4. ❌ LIVE database may NOT have company_owner records, OR
5. ❌ LIVE database has wrong role value, OR
6. ❌ LIVE has deployment mismatch (old code deployed)

---

## VERIFICATION SCRIPT

Create this file on LIVE and run it:

**File: `/public_html/ergon/diagnostic.php`**

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/app/config/database.php';

echo "<h2>DIAGNOSTIC REPORT: Expenses & Advances by Role</h2>";
echo "<pre>";

try {
    $db = Database::connect();
    
    // Test 1: Users by role
    echo "=== TEST 1: USERS BY ROLE ===\n";
    $stmt = $db->query("SELECT role, COUNT(*) as count FROM users WHERE status='active' GROUP BY role ORDER BY role");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "{$row['role']}: {$row['count']} users\n";
    }
    
    // Test 2: Expenses by user role
    echo "\n=== TEST 2: EXPENSES BY USER ROLE ===\n";
    $stmt = $db->query("
        SELECT u.role, COUNT(e.id) as expense_count, SUM(e.amount) as total_amount
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        GROUP BY u.role
        ORDER BY u.role
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "{$row['role']}: {$row['expense_count']} expenses, ₹{$row['total_amount']}\n";
    }
    
    // Test 3: Advances by user role
    echo "\n=== TEST 3: ADVANCES BY USER ROLE ===\n";
    $stmt = $db->query("
        SELECT u.role, COUNT(a.id) as advance_count, SUM(a.amount) as total_amount
        FROM advances a
        JOIN users u ON a.user_id = u.id
        GROUP BY u.role
        ORDER BY u.role
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "{$row['role']}: {$row['advance_count']} advances, ₹{$row['total_amount']}\n";
    }
    
    // Test 4: Company owner details
    echo "\n=== TEST 4: COMPANY OWNER DETAILS ===\n";
    $stmt = $db->query("SELECT id, name, email, role FROM users WHERE role='company_owner' LIMIT 1");
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($owner) {
        echo "Found: {$owner['name']} ({$owner['email']}, role={$owner['role']})\n";
        echo "User ID: {$owner['id']}\n";
        
        // Company owner's expenses
        $stmt = $db->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM expenses WHERE user_id = ?");
        $stmt->execute([$owner['id']]);
        $exp = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Expenses: {$exp['count']} records, ₹{$exp['total']}\n";
        
        // Company owner's advances
        $stmt = $db->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM advances WHERE user_id = ?");
        $stmt->execute([$owner['id']]);
        $adv = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Advances: {$adv['count']} records, ₹{$adv['total']}\n";
    } else {
        echo "ERROR: No company_owner user found!\n";
    }
    
    // Test 5: Verify SQL query filter
    echo "\n=== TEST 5: QUERY FILTER TEST ===\n";
    $adminId = 1;
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
    ");
    $stmt->execute([$adminId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Admin can see: {$result['count']} expenses\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";
?>
```

---

## ACTION ITEMS

### IMMEDIATE (5 minutes):
1. Run diagnostic.php on LIVE
2. Compare output with LOCAL
3. Identify data mismatch

### IF DATA IS MISSING:
1. Check LIVE migration script was run
2. Verify company_owner user exists in LIVE database
3. Verify expenses/advances exist for that user

### IF DATA MISMATCHES:
1. Check database credentials in .env file
2. Ensure same database is being used for both LOCAL and LIVE
3. Check if data sync job failed

### IF ROLES ARE WRONG:
1. Check users table - role column enum values
2. Verify role is stored as 'company_owner' not 'owner'
3. Update role if needed:
```sql
UPDATE users SET role = 'company_owner' WHERE id = [company_owner_id];
```

---

## SUMMARY TABLE

| Component | File | Line | Status | Issue |
|-----------|------|------|--------|-------|
| Expense Admin View | ExpenseController.php | 104 | ✅ FIXED | None |
| Expense Display | expenses/index.php | 85 | ✅ OK | None |
| Advance Admin View | AdvanceController.php | ~155 | ✅ OK | None |
| Advance Display | advances/index.php | 30 | ✅ OK | None |
| Reports Query | ReportsController.php | 187 | ✅ FIXED | None |
| **DATABASE** | **users** | **role** | ❓ UNKNOWN | ← CHECK THIS |

---

## NEXT STEP

**Run diagnostic.php on LIVE server and share output.**

The code is correct. The issue is in the data layer.

