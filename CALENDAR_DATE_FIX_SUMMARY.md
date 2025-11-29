# Calendar Date Fix Summary

## Root Cause Analysis

The calendar date mismatch issue was caused by:

1. **Missing planned_date in calendar query**: The TasksController's calendar() method was only fetching tasks with `deadline` or `due_date`, but not `planned_date`.

2. **JavaScript filtering priority**: The calendar JavaScript was checking `task.deadline || task.due_date` but not prioritizing `planned_date`.

3. **API endpoint inconsistency**: The API tasks endpoint wasn't including `planned_date` in the query.

## Fixes Implemented

### 1. TasksController.php - calendar() method
**File**: `app/controllers/TasksController.php`

**Before**:
```php
$stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? AND (t.deadline IS NOT NULL OR t.due_date IS NOT NULL) ORDER BY COALESCE(t.deadline, t.due_date) ASC");
```

**After**:
```php
$stmt = $db->prepare("SELECT t.*, u.name as assigned_user FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = ? AND (t.deadline IS NOT NULL OR t.due_date IS NOT NULL OR t.planned_date IS NOT NULL) ORDER BY COALESCE(t.planned_date, t.deadline, t.due_date) ASC");
```

### 2. Calendar View JavaScript - Task Filtering
**File**: `views/tasks/calendar.php`

**Before**:
```javascript
const taskDate = task.deadline || task.due_date;
```

**After**:
```javascript
const taskDate = task.planned_date || task.deadline || task.due_date;
```

### 3. API Controller - tasks() method
**File**: `app/controllers/ApiController.php`

**Enhanced to include planned_date in query**:
```php
$stmt = $db->prepare("SELECT t.*, u.name as assigned_by_name FROM tasks t LEFT JOIN users u ON t.assigned_by = u.id WHERE t.assigned_to = ? ORDER BY COALESCE(t.planned_date, t.deadline, t.created_at) DESC");
```

### 4. Added Debug Logging
- Added console logging to track date matching
- Added refresh button for testing
- Added user session handling in JavaScript

## Expected Behavior After Fix

1. **Correct Date Display**: Tasks with `planned_date` set to today will appear in today's calendar box.

2. **Priority Order**: The system now prioritizes dates in this order:
   - `planned_date` (when you plan to work on the task)
   - `deadline` (when the task must be completed)
   - `due_date` (legacy field)

3. **No Timezone Offset**: Tasks should appear exactly on the planned date without shifting to the next day.

## Testing

1. **Create a test task** with planned_date = today
2. **Check calendar view** - task should appear in today's box
3. **Use browser console** to see debug logs showing date matching

## Verification Steps

1. Go to `/ergon/test_calendar_fix.php` to create test tasks
2. Visit `/ergon/tasks/calendar` to see the calendar
3. Check browser console for debug information
4. Verify tasks appear on correct dates

## Files Modified

1. `app/controllers/TasksController.php` - Updated calendar() method
2. `views/tasks/calendar.php` - Updated JavaScript filtering and added debugging
3. `app/controllers/ApiController.php` - Enhanced tasks() API method

## Additional Files Created

1. `debug_calendar_dates.php` - Debug script for testing
2. `test_calendar_fix.php` - Test page with sample tasks
3. `CALENDAR_DATE_FIX_SUMMARY.md` - This summary document

The fix ensures that tasks appear on their correct planned dates in the calendar view without any timezone-related shifting.