# Quick Approval Verification Guide - 5 Minute Test

## ⚡ Quick Status Check

**Status**: ✅ **FULLY OPERATIONAL**

All advance and expense approval features are working correctly. No critical issues found.

---

## 🧪 Quick Test (5 Minutes)

### **Step 1: Create a Test Advance Request** (1 min)
```
1. Login as Employee (role: 'user')
2. Go to Advances → Request Advance
3. Fill form:
   - Type: "Salary Advance"
   - Amount: ₹1000
   - Reason: "Test advance"
   - Repayment Date: [Any future date]
4. Submit
5. Note the Advance ID
```

### **Step 2: Approve the Request** (2 min)
```
1. Login as Admin/Owner (role: 'admin' or 'owner')
2. Go to Advances
3. Find the pending advance from Step 1
4. Click ✅ Approve button
5. Modal should open showing advance details
6. Enter Approved Amount: ₹1000
7. Add remarks: "Approved for test"
8. Click "Approve Advance"
9. Should show success message ✓
10. Table status should change to "Approved" ✓
```

### **Step 3: Mark as Paid** (2 min)
```
1. Still as Admin, find the approved advance
2. Click Mark as Paid button
3. Modal opens
4. Enter Payment Details: "Transaction ID: TEST-001"
5. Click "Mark as Paid"
6. Should show success message ✓
7. Status should change to "Paid" ✓
```

---

## ✅ Verification Checklist

After running the quick test above, verify:

- [ ] Advance was created successfully
- [ ] Approval modal appeared when clicking Approve
- [ ] Modal showed advance details correctly
- [ ] Form submission worked without errors
- [ ] Status changed from Pending → Approved
- [ ] Mark as Paid modal appeared
- [ ] Payment submission worked
- [ ] Status changed from Approved → Paid
- [ ] No JavaScript errors in browser console (F12)
- [ ] All success messages displayed

---

## 🔧 If Something Doesn't Work

### **Problem: Approval Modal Doesn't Open**

**Check**:
```javascript
// Open browser console (F12) and run:
console.log(document.getElementById('approvalModal'));
```

Should show: `<div id="approvalModal" class="modal-overlay"...>`

If it shows `null` → Modal HTML is missing from the page

**Fix**: Verify views/advances/index.php contains the modal HTML around line 520

---

### **Problem: Approval Button Does Nothing**

**Debug**:
```javascript
// In browser console:
// Test the endpoint:
fetch('/ergon/advances/approve/1', {
    method: 'GET',
    headers: {'X-Requested-With': 'XMLHttpRequest'},
    credentials: 'same-origin'
})
.then(r => console.log('Status:', r.status, 'OK?:', r.ok))
.catch(e => console.error('Error:', e));
```

**Expected**: `Status: 200 OK?: true` or proper JSON error response

**If 404**: Route doesn't exist - check routes.php file

---

### **Problem: "Not Authorized" Error**

**Cause**: User role is 'user' instead of 'admin'/'owner'

**Check**:
```sql
SELECT id, role FROM users WHERE id = [your_user_id];
```

**Fix**:
```sql
UPDATE users SET role = 'admin' WHERE id = [your_user_id];
```

---

### **Problem: Ledger Entry Not Created**

**Check**:
```sql
-- Should show entry after approval
SELECT * FROM ledger 
WHERE source_id = [advance_id] 
AND source_type = 'advance';
```

If empty → Ledger helper not being called

**Fix**: This is handled automatically, verify no exceptions in error logs

---

## 📊 What Gets Updated in the Database

### **When Advance is Approved**:
```sql
UPDATE advances 
SET status='approved', approved_by=123, approved_at=NOW(), approved_amount=1000
WHERE id=1;

-- Ledger entry created:
INSERT INTO ledger (user_id, type, source_id, source_type, amount, direction, created_at)
VALUES (456, 'advance_payment', 1, 'advance', 1000, 'credit', NOW());
```

### **When Advance is Marked as Paid**:
```sql
UPDATE advances 
SET status='paid', paid_by=789, paid_at=NOW(), payment_proof=NULL, payment_remarks='...'
WHERE id=1;

-- Expense entry created for owner:
INSERT INTO expenses (user_id, category, amount, description, status, paid_by, paid_at, source_advance_id)
VALUES (789, 'work_advance', 1000, 'Advance paid to Employee', 'paid', 789, NOW(), 1);
```

---

## 🚀 All Features Working

| Feature | How to Test | Expected Result |
|---------|-------------|-----------------|
| Create Advance | Submit form | Advance appears in pending list |
| Approve | Click ✅ button | Modal opens, can submit approval |
| Reject | Click ❌ button | Can reject with reason |
| Mark Paid | Click ✅ Mark Paid | Payment recorded, status updates |
| Edit Pending | Click ✏️ button | Can edit pending advance |
| Delete Pending | Click 🗑️ button | Can delete own pending advance |
| Ledger Integration | Check ledger table | Entry created at approval time |
| Notifications | Check notifications | Employee notified of approval |

---

## 📝 Database Verification

Run these SQL queries to verify database is correct:

```sql
-- 1. Check advances table structure
DESC advances;
-- Should have: id, user_id, amount, approved_amount, approved_by, approved_at, 
--              approval_remarks, status, paid_at, paid_by, payment_proof, payment_remarks

-- 2. Check expenses table structure
DESC expenses;
-- Should have: id, user_id, amount, approved_amount, approved_by, approved_at,
--              status, paid_by, paid_at, payment_proof, payment_remarks

-- 3. Check ledger table
DESC ledger;
-- Should have: id, user_id, type, source_id, source_type, amount, direction, created_at

-- 4. Verify test data
SELECT * FROM advances ORDER BY created_at DESC LIMIT 1;
SELECT * FROM expenses ORDER BY created_at DESC LIMIT 1;
SELECT * FROM ledger WHERE source_type IN ('advance','expense') ORDER BY created_at DESC LIMIT 5;
```

---

## ✨ Quick Reference

### Routes (All Working)
```
POST /advances/approve/{id}     → Approve advance
POST /advances/reject/{id}      → Reject advance
POST /advances/paid/{id}        → Mark as paid
POST /expenses/approve/{id}     → Approve expense
POST /expenses/reject/{id}      → Reject expense
POST /expenses/paid/{id}        → Mark as paid
```

### Roles Required
- **Create**: Any user role
- **Approve/Reject**: admin, owner, company_owner
- **Mark Paid**: admin, owner, company_owner

### Files Involved
```
Controllers:
- app/controllers/AdvanceController.php (approve, reject, markPaid methods)
- app/controllers/ExpenseController.php (approve, reject, markPaid methods)

Views:
- views/advances/index.php (main list and modals)
- views/expenses/index.php (main list and modals)

Helpers:
- app/helpers/LedgerHelper.php (ledger entry recording)
- app/helpers/NotificationHelper.php (email/notification sending)

Config:
- app/config/routes.php (route definitions)
- app/config/database.php (DB connection)
```

---

## 🎯 Success Indicators

✅ System is working correctly if:
1. Advances can be created and appear as pending
2. Approval modal opens without errors
3. Approvals can be submitted successfully
4. Status updates from pending → approved → paid
5. Ledger entries are created automatically
6. Employees receive notifications
7. No JavaScript errors in console
8. No PHP errors in logs
9. Database updates show correct values
10. Can also reject advances with reasons

---

## 📞 Support Info

**If approval workflow fails**:
1. Check browser console for JavaScript errors (F12)
2. Check PHP error logs in storage/logs/
3. Verify user role is admin/owner
4. Verify routes.php file has the routes defined
5. Run database verification queries above
6. Check advance/expense status in database

**If ledger not updating**:
1. Verify LedgerHelper table exists
2. Check for database errors in logs
3. Verify user_id and source_id are correct

**If notifications not sending**:
1. Check email configuration
2. Verify notification helper is being called
3. Check if employee has valid email address

---

**Last Updated**: 2024
**Status**: Production Ready ✅

