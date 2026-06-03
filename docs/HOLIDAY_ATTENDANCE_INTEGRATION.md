# Holiday Attendance Integration Guide

## 🎯 Overview

When you mark a holiday, the system **automatically updates the attendance records for ALL applicable employees** on that date.

---

## 📋 How It Works

### Step 1: Admin Creates Holiday
Admin clicks "Mark Holiday" and fills form:
- Holiday Date: `2026-06-15`
- Holiday Name: `Independence Day`
- Holiday Type: `National Holiday`
- Applies to: `All` (all employees) OR `Department` (specific department)

### Step 2: System Automatically:
1. Creates record in `holidays` table
2. Finds all active employees (or specific department)
3. For each employee:
   - **If attendance exists on that date:** Updates it to `status='holiday'`, `is_holiday=1`
   - **If no attendance exists:** Creates new attendance record marked as holiday
4. Updates `is_counted_absent=0` so it doesn't count as absent

### Step 3: Result
All employees show as "Holiday" in attendance on that date - they don't lose productivity or show as absent.

---

## 📊 Database Changes Made

### Attendance Table Updates
When holiday is marked, the `attendance` table is updated:

```sql
-- For employees WITH existing attendance on that date:
UPDATE attendance 
SET is_holiday = 1, 
    holiday_id = 42, 
    status = 'holiday', 
    is_counted_absent = 0 
WHERE user_id = 1 
AND DATE(check_in) = '2026-06-15'

-- For employees WITH NO attendance on that date:
INSERT INTO attendance 
(user_id, holiday_id, check_in, status, location_name, is_holiday, is_counted_absent, created_at)
VALUES 
(1, 42, '2026-06-15 00:00:00', 'holiday', 'Holiday', 1, 0, NOW())
```

---

## 🔄 Scope Options

### Option 1: Apply to ALL Employees
```
[ Date: 2026-06-15 ]
[ Name: Independence Day ]
[ Type: National ]
[ Applies to: ✓ All Employees ]
```

**Result:** All active users in system get marked as holiday
- Queries: `SELECT * FROM users WHERE status='active'`
- Updates/Inserts: One record per employee

### Option 2: Apply to Specific Department
```
[ Date: 2026-06-15 ]
[ Name: Team Building ]
[ Type: Company ]
[ Applies to: ✓ Department: Sales ]
```

**Result:** Only Sales department employees get marked as holiday
- Queries: `SELECT * FROM users WHERE status='active' AND department_id=5`
- Updates/Inserts: One record per department member

---

## 📝 Verification

### Check Attendance Records After Holiday Created

**In Database:**
```sql
-- Count holidays created
SELECT COUNT(*) FROM holidays WHERE is_active=1;

-- View a specific holiday
SELECT * FROM holidays WHERE holiday_name='Independence Day';

-- See affected attendance records
SELECT a.user_id, u.name, a.check_in, a.status, a.is_holiday, a.holiday_id
FROM attendance a
JOIN users u ON a.user_id = u.id
WHERE a.holiday_id = 42
ORDER BY u.name;

-- Count how many employees marked as holiday
SELECT COUNT(DISTINCT user_id) 
FROM attendance 
WHERE holiday_id = 42 AND is_holiday = 1;
```

**In Attendance Page:**
1. Navigate to `/ergon/attendance`
2. Filter to the holiday date
3. All employees should show:
   - Status: "Holiday"
   - Working Hours: "0h 0m" (not counted)
   - Not marked as absent

---

## 🔍 Logging

The system logs all operations for debugging. Check `/storage/logs/error.log`:

```
[Date] Holiday create request - POST data: array ( 'holiday_date' => '2026-06-15', ... )
[Date] Holiday data prepared: {"holiday_date":"2026-06-15","holiday_name":"Independence Day",...}
[Date] Applying holiday 42 to date 2026-06-15 (applies_to: All)
[Date] Found 15 users to mark as holiday
[Date] Updated attendance record 1001 for user 1 as holiday
[Date] Updated attendance record 1002 for user 2 as holiday
[Date] Created new holiday attendance record for user 3 on 2026-06-15
...
[Date] Successfully marked 15 attendance records as holiday
[Date] Holiday created successfully with ID: 42
```

---

## 📤 API Response

When you create a holiday, the API returns:

```json
{
  "success": true,
  "message": "Holiday created successfully",
  "holiday_id": 42,
  "affected_users": 15,
  "holiday_date": "2026-06-15",
  "holiday_name": "Independence Day"
}
```

**What it means:**
- `affected_users`: 15 employees were automatically marked as holiday
- `holiday_id`: Can be used to reference this holiday later
- `holiday_date`: The date this holiday applies to

---

## 🎯 Attendance Record Details

### What Gets Created/Updated

When marking a holiday, each employee gets an attendance record with:

| Field | Value | Meaning |
|-------|-------|---------|
| `user_id` | Employee ID | Which employee |
| `holiday_id` | 42 | Links to this holiday |
| `check_in` | 2026-06-15 00:00:00 | Date of holiday |
| `check_out` | NULL | Not clocked out |
| `status` | 'holiday' | Marked as holiday |
| `is_holiday` | 1 | Boolean flag |
| `is_counted_absent` | 0 | Not counted as absent |
| `location_name` | 'Holiday' | Special marker |
| `working_hours` | 0 | No working hours |

---

## 🗑️ Deleting a Holiday

When admin deletes a holiday:

```sql
-- Holiday marked inactive
UPDATE holidays SET is_active = 0 WHERE id = 42;

-- Attendance records updated
UPDATE attendance 
SET is_holiday = 0, 
    holiday_id = NULL, 
    is_counted_absent = 1 
WHERE holiday_id = 42;
```

**Result:** Employees no longer marked as holiday on that date

---

## 🔄 Updating a Holiday

When admin updates a holiday (name, type, description):

```sql
-- Holiday details updated
UPDATE holidays 
SET holiday_name = 'Independence Day (Updated)',
    holiday_type = 'National',
    updated_at = NOW()
WHERE id = 42;

-- Attendance records refreshed
UPDATE attendance 
SET status = 'holiday' 
WHERE holiday_id = 42 AND is_holiday = 1;
```

---

## ✅ Workflow Summary

```
┌─────────────────────────────────────────────────────┐
│  Admin Marks Holiday                                │
│  [ Mark Holiday Button Click ]                      │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │ Modal Form Opens     │
        │ - Date              │
        │ - Name              │
        │ - Type              │
        │ - Description       │
        │ - Applies To        │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │ Form Submitted       │
        │ POST /ergon/holiday/ │
        │ create               │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │ Holiday Record       │
        │ Created in DB        │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ Find Affected Employees          │
        │ - If "All": All active users     │
        │ - If "Dept": Dept members only   │
        └──────────┬───────────────────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ For Each Employee:               │
        │ Check if attendance exists       │
        │ - YES: UPDATE to holiday status  │
        │ - NO: CREATE holiday record      │
        └──────────┬───────────────────────┘
                   │
                   ▼
        ┌──────────────────────────────────┐
        │ Success!                         │
        │ - Modal closes                   │
        │ - Success message shows          │
        │ - All employees marked holiday   │
        │ - Attendance page reflects it    │
        └──────────────────────────────────┘
```

---

## 🧪 Testing

### Test Scenario 1: Mark Company-Wide Holiday

1. Go to Attendance page
2. Click "Mark Holiday"
3. Fill:
   - Date: 2026-06-20
   - Name: "Company Party"
   - Type: "Company Holiday"
   - Applies to: ✓ All
4. Submit

**Verify:**
```sql
-- Should create 1 holiday
SELECT COUNT(*) FROM holidays WHERE holiday_name='Company Party';

-- Should mark ALL active users
SELECT COUNT(*) FROM attendance 
WHERE holiday_id = <holiday_id> AND status='holiday';

-- Should match active user count
SELECT COUNT(*) FROM users WHERE status='active';
```

### Test Scenario 2: Mark Department Holiday

1. Go to Attendance page
2. Click "Mark Holiday"
3. Fill:
   - Date: 2026-06-21
   - Name: "Sales Training"
   - Type: "Company Holiday"
   - Applies to: ✓ Department: Sales
4. Submit

**Verify:**
```sql
-- Should mark only Sales dept
SELECT COUNT(DISTINCT a.user_id) 
FROM attendance a
JOIN users u ON a.user_id = u.id
WHERE a.holiday_id = <holiday_id> 
AND u.department_id = <sales_dept_id>;

-- Should match Sales employee count
SELECT COUNT(*) FROM users 
WHERE status='active' AND department_id = <sales_dept_id>;
```

---

## 🐛 Troubleshooting

### Issue: Employees not marked as holiday

**Cause 1: Attendance table doesn't have required columns**
```sql
-- Check columns exist
DESCRIBE attendance;
-- Should have: is_holiday, holiday_id, is_counted_absent
```

**Solution:** Add missing columns if needed

**Cause 2: User status not 'active'**
```sql
-- Check user statuses
SELECT id, name, status FROM users;
-- Should have status = 'active'
```

**Solution:** Change user status to 'active'

**Cause 3: Database transaction failed**
```sql
-- Check holidays table
SELECT * FROM holidays WHERE id = <holiday_id>;

-- Check attendance records
SELECT * FROM attendance WHERE holiday_id = <holiday_id>;
```

**Solution:** Review error logs, recreate holiday

### Issue: Wrong count of employees marked

**Check the applies_to value:**
```sql
SELECT applies_to, department_id FROM holidays WHERE id = <holiday_id>;
```

If `applies_to='Department'` but should be `'All'`, need to recreate holiday.

---

## 📚 Related Documentation

- Holiday Management: `HOLIDAY_MANAGEMENT_ARCHITECTURE.md`
- API Reference: `MARK_HOLIDAY_BUTTON_ATTENDANCE_PAGE.md`
- Setup Guide: `HOLIDAY_TABLE_SETUP.md`

---

## ✨ Features

✅ Auto-marks all applicable employees
✅ Creates records if they don't exist
✅ Updates records if they exist
✅ Respects department scope
✅ Prevents counting as absent
✅ Preserves attendance data
✅ Soft delete (keeps history)
✅ Detailed logging for debugging
✅ Comprehensive error handling
✅ Transaction-safe operations

---

**Status:** ✅ IMPLEMENTED & WORKING

