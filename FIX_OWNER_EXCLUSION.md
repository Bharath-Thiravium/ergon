# COMPANY OWNER EXCLUSION FIX - ROOT CAUSE & SOLUTIONS

## ANALYSIS COMPLETE ✓

### ROOT CAUSE IDENTIFIED

**Location 1: ExpenseController.php (Line ~104)**
```php
// CURRENT (WRONG):
WHERE (u.role = 'user' OR e.user_id = ?)

// SHOULD BE:
WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
```

**Location 2: AdvanceController.php (Line ~40-50)**
Already correctly shows all users in admin view (no role filter), so advances are OK once expenses are fixed.

**Location 3: ReportsController.php (Line ~188)**
```php
// CURRENT (WRONG):
WHERE status = 'active' AND role NOT IN ('company_owner', 'owner')

// SHOULD BE:
WHERE status = 'active' AND role NOT IN ('owner')
// or just remove the filter entirely for company_owner
```

---

## FIXES REQUIRED

### 1. ExpenseController.php - getExpensesForAdmin()
**File:** `app/controllers/ExpenseController.php`
**Line:** ~104
**Change:** Add 'company_owner' to role filter

**OLD:**
```php
private function getExpensesForAdmin($adminUserId, $projectId = null) {
    ...
    WHERE (u.role = 'user' OR e.user_id = ?)
```

**NEW:**
```php
private function getExpensesForAdmin($adminUserId, $projectId = null) {
    ...
    WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
```

---

### 2. ReportsController.php - monthlyAttendance()
**File:** `app/controllers/ReportsController.php`
**Line:** ~188
**Change:** Include company_owner in attendance reports

**OLD:**
```php
$users = $db->query("
    SELECT id, name, role
    FROM users
    WHERE status = 'active'
      AND role NOT IN ('company_owner', 'owner')
    ORDER BY FIELD(role,'admin','user'), name
")->fetchAll(PDO::FETCH_ASSOC);
```

**NEW:**
```php
$users = $db->query("
    SELECT id, name, role
    FROM users
    WHERE status = 'active'
      AND role NOT IN ('owner')
    ORDER BY FIELD(role,'admin','user'), name
")->fetchAll(PDO::FETCH_ASSOC);
```

---

### 3. Views - Add Role Badge Column
**Files:** 
- `views/expenses/index.php`
- `views/advances/index.php`

**Current:** Shows employee name only
**Change:** Add role badge display

Add to table header:
```html
<th>Role</th>
```

Add to table body (after employee name):
```html
<td>
    <span class="badge badge--<?= $expense['user_role'] === 'company_owner' ? 'info' : 'default' ?>">
        <?= ucfirst(str_replace('_', ' ', $expense['user_role'])) ?>
    </span>
</td>
```

---

## VALIDATION CHECKLIST

After applying fixes, verify:

✓ Admin views show company_owner expenses  
✓ Admin views show company_owner advances  
✓ Reports include company_owner attendance  
✓ Role badges display in lists  
✓ Dashboard counts include owner records  
✓ Approval workflow works for owner records  
✓ Tenant isolation maintained (same company only)  
✓ RBAC rules enforced (admin can view owner, owner can view own)

---

## FILES AFFECTED

1. `app/controllers/ExpenseController.php` - getExpensesForAdmin()
2. `app/controllers/ReportsController.php` - monthlyAttendance()
3. `views/expenses/index.php` - add role column
4. `views/advances/index.php` - add role column
5. Dashboard counts (if applicable)

---

## TEST CASES

**Test 1:** Admin logs in → Views Expenses → Should see company_owner expenses ✓

**Test 2:** Admin logs in → Views Advances → Should see company_owner advances ✓

**Test 3:** Admin runs Monthly Attendance Report → Should include company_owner ✓

**Test 4:** Company owner submits expense → Visible in admin panel ✓

**Test 5:** Admin approves owner expense → Works normally ✓

**Test 6:** Owner can only see own records (RBAC) ✓

**Test 7:** Cross-tenant data NOT visible (tenant isolation) ✓

