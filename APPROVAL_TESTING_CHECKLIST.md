# Advance & Expense Approval Testing Checklist ✅

## Pre-Testing Setup

- [ ] Ensure you have admin/owner role in database
- [ ] Have at least one pending advance/expense record
- [ ] Clear browser cache (Ctrl+Shift+Delete)
- [ ] Open browser DevTools (F12)
- [ ] Go to Console tab to watch for errors

---

## Test Suite 1: Advance Approval

### Test 1.1: Modal Opens Without Errors
**Steps**:
1. Navigate to `/advances`
2. Find a pending advance
3. Click ✅ Approve button
4. Modal should appear showing advance details

**Expected Result**: 
- ✅ Modal appears smoothly
- ✅ No JavaScript errors in console
- ✅ Advance details display correctly

**Check**:
- [ ] Modal visible and styled
- [ ] Amount displayed correctly
- [ ] Employee name shows
- [ ] Approve button ready to click

---

### Test 1.2: Approval Form Submission
**Steps**:
1. In the approval modal from Test 1.1
2. Ensure "Approved Amount" = requested amount
3. Add remarks: "Test approval"
4. Click "Approve Advance" button

**Expected Result**:
- ✅ Button shows loading state ("⏳ Approving...")
- ✅ Success message appears
- ✅ Page refreshes after 2 seconds
- ✅ Advance status changes to "Approved"

**Check**:
- [ ] Loading state appears
- [ ] Success notification shows
- [ ] Page reloads
- [ ] Status updated in table

---

### Test 1.3: Error Handling
**Steps**:
1. Open browser DevTools Network tab
2. Go to `/advances`
3. Right-click a pending advance
4. Select "Inspect Element"
5. Find the approve button
6. Change data-id to invalid number (999999)
7. Click approve button

**Expected Result**:
- ✅ Error modal appears
- ✅ Error message shows "Advance not found"
- ✅ No JavaScript errors in console
- ✅ User is informed clearly

**Check**:
- [ ] Error modal displays
- [ ] Message is readable
- [ ] No console errors
- [ ] Modal can be closed

---

## Test Suite 2: Expense Approval

### Test 2.1: Modal Opens Without Errors
**Steps**:
1. Navigate to `/expenses`
2. Find a pending expense
3. Click ✅ Approve button
4. Modal should appear showing expense details

**Expected Result**:
- ✅ Modal appears smoothly
- ✅ No JavaScript errors
- ✅ Expense details display

**Check**:
- [ ] Modal visible
- [ ] Claimed amount shown
- [ ] Description visible
- [ ] Approve button ready

---

### Test 2.2: Expense Approval with Modification
**Steps**:
1. In the approval modal from Test 2.1
2. Change "Approved Amount" to 80% of claimed amount
3. Add remarks: "Reduced due to policy"
4. Click "Approve Expense"

**Expected Result**:
- ✅ Success message
- ✅ Page refreshes
- ✅ Expense shows "Approved" status
- ✅ Approved amount recorded in database

**Check**:
- [ ] Amount reduced correctly
- [ ] Remarks saved
- [ ] Status updated
- [ ] Page refreshed

---

### Test 2.3: Rejection Workflow
**Steps**:
1. Navigate to `/expenses`
2. Find a pending expense
3. Click ❌ Reject button
4. Enter rejection reason: "Missing receipt"
5. Click "Reject Expense"

**Expected Result**:
- ✅ Success message
- ✅ Status changes to "Rejected"
- ✅ Rejection reason saved
- ✅ Employee notified (if notifications enabled)

**Check**:
- [ ] Rejection modal appears
- [ ] Reason field required
- [ ] Success message shows
- [ ] Status changes to Rejected

---

## Test Suite 3: Mark as Paid

### Test 3.1: Mark Advance as Paid
**Steps**:
1. Have an approved advance (from Test 1.2)
2. Click "Mark as Paid" button
3. Enter payment remarks: "Transferred via bank"
4. Click "Mark as Paid"

**Expected Result**:
- ✅ Success message
- ✅ Status changes to "Paid"
- ✅ Ledger entry created
- ✅ No errors in console

**Check**:
- [ ] Modal appears
- [ ] Remarks field works
- [ ] Status updated
- [ ] Success notification

---

### Test 3.2: Mark Expense as Paid with Proof
**Steps**:
1. Have an approved expense (from Test 2.2)
2. Click "Mark as Paid" button
3. Upload a test image as proof
4. Add remarks: "Payment completed"
5. Click "Mark as Paid"

**Expected Result**:
- ✅ File uploads successfully
- ✅ Status changes to "Paid"
- ✅ Proof file saved
- ✅ Remarks recorded

**Check**:
- [ ] File upload works
- [ ] File size validated
- [ ] Status changed
- [ ] Success message

---

## Test Suite 4: Browser DevTools Console Checks

### Test 4.1: No Errors on Page Load
**Steps**:
1. Open `/advances` or `/expenses`
2. Look at Console tab in DevTools
3. Look for any red error messages

**Expected Result**:
- ✅ Only blue info messages (if any)
- ✅ No red error messages
- ✅ No warnings about undefined functions

**Check**:
- [ ] Console is clean
- [ ] No undefined function errors
- [ ] Modal utility functions exist

---

### Test 4.2: Verify Modal Functions Exist
**Steps**:
1. Open DevTools Console
2. Type: `typeof window.showModal`
3. Should show: `"function"`
4. Type: `typeof window.showError`
5. Should show: `"function"`

**Expected Result**:
- ✅ showModal returns "function"
- ✅ showError returns "function"
- ✅ showSuccess returns "function"
- ✅ All functions available

**Check**:
- [ ] showModal = "function"
- [ ] showError = "function"
- [ ] showSuccess = "function"
- [ ] showWarning = "function"

---

## Test Suite 5: Network Requests

### Test 5.1: Verify JSON Responses
**Steps**:
1. Open Network tab in DevTools
2. Click Approve button
3. Check the GET request to `/advances/approve/{id}`
4. Look at Response tab

**Expected Result**:
- ✅ Response is JSON
- ✅ Contains "success": true
- ✅ Contains "advance" object
- ✅ Status code is 200

**Check**:
- [ ] Content-Type: application/json
- [ ] Response has success field
- [ ] Response has data
- [ ] HTTP 200 status

---

### Test 5.2: Error Response Format
**Steps**:
1. Open Network tab
2. Make a request that fails (invalid ID)
3. Check Response tab

**Expected Result**:
- ✅ Response is JSON (not HTML)
- ✅ Contains "success": false
- ✅ Contains "error" message
- ✅ May contain "details"

**Check**:
- [ ] Content-Type is JSON
- [ ] Error message present
- [ ] Proper error format
- [ ] HTTP status code (401/403/500)

---

## Test Suite 6: End-to-End Workflow

### Test 6.1: Complete Advance Flow
**Steps**:
1. Create new advance (if none pending)
2. Approve it (Test 1.2)
3. Mark as paid (Test 3.1)
4. Verify status is "Paid"

**Expected Result**:
- ✅ All steps complete without errors
- ✅ Database updated at each step
- ✅ No console errors
- ✅ All status changes reflected in UI

**Check**:
- [ ] Advance created (status: pending)
- [ ] Approved successfully (status: approved)
- [ ] Marked paid (status: paid)
- [ ] All transitions smooth

---

### Test 6.2: Complete Expense Flow
**Steps**:
1. Submit expense claim
2. Approve with modification (Test 2.2)
3. Mark as paid with proof (Test 3.2)
4. Verify final status is "Paid"

**Expected Result**:
- ✅ All workflow steps succeed
- ✅ Approved amount recorded
- ✅ Payment proof saved
- ✅ Ledger entry created

**Check**:
- [ ] Expense submitted
- [ ] Approved amount differs from claimed
- [ ] Paid with proof attached
- [ ] Final status: Paid

---

## Test Suite 7: Mobile Responsiveness

### Test 7.1: Mobile Modal Display
**Steps**:
1. Open DevTools
2. Press Ctrl+Shift+M (or use device toolbar)
3. Set to iPhone dimensions
4. Navigate to `/advances`
5. Click Approve button

**Expected Result**:
- ✅ Modal displays properly on mobile
- ✅ Form fields are usable
- ✅ Buttons are touchable (44px minimum)
- ✅ No horizontal scroll

**Check**:
- [ ] Modal fits screen
- [ ] Buttons are touchable
- [ ] Text is readable
- [ ] No layout breaking

---

### Test 7.2: Mobile Network Performance
**Steps**:
1. DevTools Network tab
2. Set throttling to "Slow 3G"
3. Click an approval action
4. Watch request complete

**Expected Result**:
- ✅ Request completes even on slow network
- ✅ Timeout handling works
- ✅ User sees loading indicator
- ✅ Error handling if fails

**Check**:
- [ ] Loads on slow network
- [ ] Loading state visible
- [ ] Doesn't timeout prematurely
- [ ] Handles failure gracefully

---

## Test Suite 8: Security Checks

### Test 8.1: Authorization Verification
**Steps**:
1. Logout and login as regular user
2. Try to access `/advances/approve/1`
3. Should be denied

**Expected Result**:
- ✅ User gets 403 Forbidden
- ✅ Error message: "Unauthorized"
- ✅ Stays on advances page

**Check**:
- [ ] Regular users can't approve
- [ ] Only admin/owner can approve
- [ ] Error message clear

---

### Test 8.2: CSRF Protection
**Steps**:
1. Open Developer Tools
2. Look at the form headers
3. Check for CSRF token

**Expected Result**:
- ✅ Forms include CSRF protection
- ✅ Tokens are validated server-side
- ✅ No direct API calls bypass security

**Check**:
- [ ] CSRF tokens present
- [ ] Validation occurs
- [ ] No security gaps

---

## Final Verification

### Database Verification
```sql
-- Check recent advances were approved
SELECT id, status, approved_by, approved_at 
FROM advances 
WHERE status = 'approved' 
ORDER BY approved_at DESC 
LIMIT 5;

-- Check recent expenses were approved
SELECT id, status, approved_by, approved_at 
FROM expenses 
WHERE status = 'approved' 
ORDER BY approved_at DESC 
LIMIT 5;

-- Check ledger entries were created
SELECT id, source_type, source_id, amount, type 
FROM ledger 
WHERE source_type IN ('advance', 'expense') 
ORDER BY created_at DESC 
LIMIT 10;
```

**Check**:
- [ ] Approved records in database
- [ ] Approved amounts correct
- [ ] Ledger entries created
- [ ] Timestamps accurate

---

## Sign-Off Checklist

**Tester Name**: ________________  
**Date**: ________________  
**Environment**: [ ] Local [ ] Staging [ ] Production

### All Tests Passed?
- [ ] Test 1: Advance Modal Opening
- [ ] Test 2: Advance Approval
- [ ] Test 3: Advance Error Handling
- [ ] Test 4: Expense Modal Opening
- [ ] Test 5: Expense Approval
- [ ] Test 6: Rejection Workflow
- [ ] Test 7: Mark as Paid
- [ ] Test 8: Console Checks
- [ ] Test 9: Network Requests
- [ ] Test 10: End-to-End Flow
- [ ] Test 11: Mobile Testing
- [ ] Test 12: Security Checks
- [ ] Database Verification

### Overall Result
- [ ] ✅ ALL TESTS PASSED - System Ready
- [ ] ⚠️ SOME ISSUES - Document below
- [ ] ❌ FAILURES - Do not deploy

### Issues Found (if any)
```
1. ________________________
2. ________________________
3. ________________________
```

### Notes
```
________________________
________________________
________________________
```

**Approved By**: ________________  
**Date**: ________________

---

## Quick Retest (After Fixes)

If any issues found, run these quick tests after fixes:

1. [ ] Approval modal opens
2. [ ] Form submits successfully
3. [ ] Success message appears
4. [ ] Status updates in database
5. [ ] No console errors

**Time**: ~5 minutes

---

**Test Suite Version**: 1.0  
**Last Updated**: 2025  
**Status**: Ready to use

