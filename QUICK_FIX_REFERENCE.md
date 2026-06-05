# QUICK REFERENCE: COMPANY OWNER VISIBILITY FIX

## THE PROBLEM (30 seconds)
Company owner expenses/advances excluded from admin view because of role filter.

## THE SOLUTION (2 changes)

### Change 1: ExpenseController.php
**File:** `app/controllers/ExpenseController.php`  
**Line:** ~104  
**Function:** `getExpensesForAdmin()`  

```diff
- WHERE (u.role = 'user' OR e.user_id = ?)
+ WHERE (u.role IN ('user', 'company_owner') OR e.user_id = ?)
```

### Change 2: ReportsController.php
**File:** `app/controllers/ReportsController.php`  
**Line:** ~188  
**Function:** `monthlyAttendance()`  

```diff
- AND role NOT IN ('company_owner', 'owner')
+ AND role NOT IN ('owner')
```

## OPTIONAL: Add Role Badge

**File:** `views/expenses/index.php` (in table body)  
**Add after employee name:**
```php
<span class="badge badge-info">
  <?= $expense['user_role'] === 'company_owner' ? '👑 Company Owner' : 'Employee' ?>
</span>
```

## VERIFY IT WORKS

| Test | Expected Result |
|------|-----------------|
| Admin → /expenses | Shows company owner expenses ✓ |
| Admin → /advances | Shows company owner advances ✓ |
| Reports → Attendance | Includes company owner ✓ |
| Admin approves owner expense | Works normally ✓ |

## ROLLBACK (if needed)

1. Revert the 2 changes
2. Clear cache
3. Restart application

## IMPACT

- ✅ Admin can now see owner expenses
- ✅ Approval workflow works for owner
- ✅ Financial reports include owner
- ✅ Dashboard counts accurate
- ✅ RBAC still enforced
- ✅ No data loss
- ✅ No breaking changes

## TIME TO DEPLOY

- Implementation: 5 minutes
- Testing: 10 minutes
- Deployment: 2 minutes
- **Total: ~20 minutes**

---

**Questions?** See COMPANY_OWNER_FIX_REPORT.md for full analysis.

