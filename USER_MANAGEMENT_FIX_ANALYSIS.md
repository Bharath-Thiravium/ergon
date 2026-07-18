# User Management - Complete Directory Fix

## ROOT CAUSE ANALYSIS

### Issue 1: KPI Count Filtering
**Location**: `UsersController.php` line 28-30
```php
// WRONG: Only counts user/admin roles, excludes owner/company_owner
$kpiStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role IN ('user', 'admin') AND status = 'active'");
```
**Impact**: Total Users count is inaccurate, excludes owners

### Issue 2: Query Fetching ALL Users But Hiding Some
**Location**: `UsersController.php` line 24-26
```php
// Fetches ALL users including owners/admins, but...
$stmt = $db->prepare("SELECT DISTINCT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.status != ? ORDER BY u.created_at DESC");
```
**Impact**: Data loaded but view filters it out manually in PHP

### Issue 3: View Hides Admins From Admins
**Location**: `users/index.php` line 82-86
```php
// Admin users cannot see other admins/owners (by design)
if ($_SESSION['role'] === 'admin' && in_array($user['role'], ['owner', 'admin'])) {
    continue; // SKIP showing admins to admins
}
```
**Problem**: Correct for security but not comprehensive enough for owners viewing everything

---

## SOLUTION COMPONENTS

### 1. Fix KPI Counts (Line 28-30)
Include ALL roles in count:
```php
$kpiStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
```

### 2. Ensure View Shows All Roles for Owner
The view ALREADY handles this (lines 84-90 separate by role for owner).
No changes needed here.

### 3. Update KPI Card Labels
Current labels only show employee/admin counts.
Need to show Company Owner + Admin totals separately.

### 4. Add Role Column Badges
The view already has role badges (line 152).
Ensure they display correctly with icons:
- 👑 Company Owner
- 🛡️ Admin  
- 👨💼 HR
- 👤 Employee

---

## FILES NEEDING UPDATES

1. **UsersController.php** - Fix KPI query
2. **users/index.php** - Enhance statistics display
3. **User.php** - Add methods for comprehensive user counts

---

## VERIFICATION CHECKLIST

✓ All users fetched from database
✓ Owner sees all roles (company_owner, admin, hr, user)
✓ Admin sees all except owner/other admins
✓ Employee cannot access user management
✓ KPI counts include all active users
✓ Role column visible for each user
✓ Role badges displayed with correct icons
✓ Search works across all roles
✓ Filters work correctly
✓ Tenant isolation maintained
✓ RBAC enforced

