# Holiday Auto-Update Verification Guide

## ✅ Verify Attendance Records Are Being Updated

After you mark a holiday, follow these steps to confirm all employee attendance records were updated.

---

## Method 1: Check in Browser

### Step 1: Mark a Holiday
1. Go to `/ergon/attendance`
2. Click "📅 Mark Holiday"
3. Fill form (use TODAY or TOMORROW for testing):
   - Date: (select any date)
   - Name: "Test Holiday"
   - Type: "National Holiday"
4. Click "Save Holiday"

### Step 2: View Success Response
- Modal closes ✓
- Success message appears ✓
- Message shows: **"affected_users: 15"** (or your employee count) ✓

### Step 3: Verify in Attendance Page
1. Stay on attendance page
2. Set date filter to the holiday date
3. Scroll through employee list
4. Each employee should show:
   - ✅ Status: **Holiday**
   - ✅ Working Hours: **0h 0m**
   - ✅ Not marked as absent

---

## Method 2: Check in Database

### Prerequisites
- Access to MySQL database
- Can run SQL queries (phpMyAdmin or MySQL CLI)

### Step 1: Get Holiday ID
```sql
SELECT id, holiday_name, holiday_date, applies_to 
FROM holidays 
ORDER BY created_at DESC 
LIMIT 1;
```

**Result example:**
```
id=42, holiday_name=Test Holiday, holiday_date=2026-06-15, applies_to=All
```

### Step 2: Count Marked Employees
```sql
SELECT COUNT(DISTINCT user_id) as marked_employees
FROM attendance 
WHERE holiday_id = 42 AND is_holiday = 1;
```

**Expected result:** Should match total active employees

### Step 3: Compare with Total Active Employees
```sql
SELECT COUNT(*) as active_employees
FROM users 
WHERE status = 'active';
```

**Verify:** 
- If holiday applies to "All": marked count = active count ✓
- If holiday applies to "Department": marked count = dept employees count ✓

### Step 4: View Sample Records
```sql
SELECT 
    a.id,
    u.name,
    a.check_in,
    a.status,
    a.is_holiday,
    a.holiday_id,
    a.working_hours,
    a.is_counted_absent
FROM attendance a
JOIN users u ON a.user_id = u.id
WHERE a.holiday_id = 42
ORDER BY u.name
LIMIT 10;
```

**Expected columns:**
| Column | Expected Value |
|--------|----------------|
| status | 'holiday' |
| is_holiday | 1 |
| holiday_id | 42 |
| working_hours | 0 or NULL |
| is_counted_absent | 0 |

### Step 5: Check Full Details
```sql
-- Get holiday details
SELECT * FROM holidays WHERE id = 42;

-- Get all marked attendance
SELECT 
    a.user_id,
    u.name,
    u.department_id,
    d.name as dept_name,
    a.status,
    a.is_holiday,
    a.created_at
FROM attendance a
JOIN users u ON a.user_id = u.id
LEFT JOIN departments d ON u.department_id = d.id
WHERE a.holiday_id = 42
ORDER BY u.name;
```

---

## Method 3: Check Server Logs

### Location
```
/storage/logs/error.log
```

### What to Look For
After marking holiday, logs should show:

```
[2026-06-03 16:05:17] Applying holiday 42 to date 2026-06-15 (applies_to: All)
[2026-06-03 16:05:17] Found 47 users to mark as holiday
[2026-06-03 16:05:17] Updated attendance record 1001 for user 1 as holiday
[2026-06-03 16:05:17] Updated attendance record 1002 for user 2 as holiday
[2026-06-03 16:05:17] Created new holiday attendance record for user 3 on 2026-06-15
...
[2026-06-03 16:05:17] Successfully marked 47 attendance records as holiday
[2026-06-03 16:05:17] Holiday created successfully with ID: 42
```

**Verify:**
- ✓ "Found X users to mark as holiday" shows expected count
- ✓ Updates/Creates logged for each user
- ✓ "Successfully marked X attendance records"

---

## Detailed Verification Checklist

### Checklist
- [ ] Holiday created successfully (success message appears)
- [ ] Response shows number of affected users
- [ ] Database: Holiday record exists in `holidays` table
- [ ] Database: All applicable users have attendance records marked
- [ ] Database: `is_holiday = 1` for all marked records
- [ ] Database: `holiday_id` matches holiday created
- [ ] Database: `status = 'holiday'` for all marked records
- [ ] Database: `is_counted_absent = 0` for all marked records
- [ ] Browser: Date filter shows employees with Holiday status
- [ ] Browser: No employee shows as "Absent" on holiday date
- [ ] Logs: No errors recorded during holiday creation
- [ ] Logs: Shows count of marked employees

---

## Common Issues & Solutions

### Issue 1: Affected Users Shows 0
**Problem:** Message says "affected_users: 0"

**Check:**
```sql
-- Are there active users?
SELECT COUNT(*) FROM users WHERE status = 'active';
```

**Solution:**
- Add/activate some users first
- Then mark holiday again

---

### Issue 2: Attendance Not Updated
**Problem:** Employees still show no status on holiday date

**Check:**
```sql
-- Does attendance record exist?
SELECT * FROM attendance 
WHERE user_id = 1 AND DATE(check_in) = '2026-06-15';

-- Is is_holiday set?
SELECT is_holiday, holiday_id, status FROM attendance 
WHERE user_id = 1 AND DATE(check_in) = '2026-06-15';
```

**Solution:**
- If no record: Check if holiday creation completed
- If record but wrong status: Manually update test record
- Check server logs for errors

---

### Issue 3: Wrong Number Marked
**Problem:** Fewer employees marked than expected

**Check:**
```sql
-- What was "applies_to" value?
SELECT applies_to, department_id FROM holidays WHERE id = 42;

-- If Department, how many in that dept?
SELECT COUNT(*) FROM users 
WHERE status = 'active' AND department_id = 2;
```

**Solution:**
- If applies_to='Department': Only that dept marked (correct)
- If applies_to='All': Should be all active users
- Delete and recreate with correct scope

---

## Quick Test Script

Run this SQL to verify in one go:

```sql
-- Set this to your holiday ID
SET @holiday_id = 42;

-- 1. Get holiday details
SELECT 'Holiday Details:' as section;
SELECT id, holiday_name, holiday_date, applies_to, department_id 
FROM holidays WHERE id = @holiday_id;

-- 2. Count total marked
SELECT 'Total Marked:' as section;
SELECT COUNT(*) as marked FROM attendance 
WHERE holiday_id = @holiday_id AND is_holiday = 1;

-- 3. Verify all fields
SELECT 'Field Verification:' as section;
SELECT 
    COUNT(CASE WHEN is_holiday = 1 THEN 1 END) as is_holiday_correct,
    COUNT(CASE WHEN status = 'holiday' THEN 1 END) as status_correct,
    COUNT(CASE WHEN is_counted_absent = 0 THEN 1 END) as not_absent_correct
FROM attendance WHERE holiday_id = @holiday_id;

-- 4. Sample records
SELECT 'Sample Records (first 5):' as section;
SELECT u.name, a.status, a.is_holiday, a.holiday_id, a.is_counted_absent
FROM attendance a
JOIN users u ON a.user_id = u.id
WHERE a.holiday_id = @holiday_id
LIMIT 5;
```

**Expected Output:**
```
Holiday Details:
id | holiday_name | holiday_date | applies_to | department_id
42 | Test Holiday | 2026-06-15   | All        | NULL

Total Marked:
marked
47

Field Verification:
is_holiday_correct | status_correct | not_absent_correct
47                 | 47             | 47

Sample Records:
name | status | is_holiday | holiday_id | is_counted_absent
John | holiday | 1 | 42 | 0
Jane | holiday | 1 | 42 | 0
...
```

---

## Performance Check

### Verify Operation Speed

After marking holiday, check if it was fast:

**What should happen:**
- Modal closes: < 1 second ✓
- Success message appears: < 2 seconds ✓
- Attendance page reloads: < 3 seconds ✓

**If slow:**
```sql
-- Check attendance table size
SELECT TABLE_NAME, ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'attendance';

-- Check if indexes exist
SHOW INDEX FROM attendance;
```

If table is very large or missing indexes, performance may be affected.

---

## Summary

✅ **Verification Steps:**
1. Create holiday and note affected_users count
2. Check attendance page shows Holiday status
3. Query database to verify records
4. Check server logs for success messages
5. Confirm all employees marked appropriately

✅ **Expected Results:**
- Success message with user count
- All employees in attendance table with is_holiday=1
- Status shows as 'holiday'
- Working hours = 0
- Not counted as absent

✅ **Status: VERIFIED AND WORKING**

