# Planned Date Fix Summary

## Issue
Tasks created with a specific `planned_date` were showing up on today's date instead of the selected `planned_date` in the Daily Planner.

## Root Cause
The `fetchAssignedTasksForDate` method in `DailyPlanner.php` was using complex date comparison logic that wasn't properly matching tasks with future planned dates.

## Solution
Simplified and improved the future date query logic in the `fetchAssignedTasksForDate` method:

### Before (Complex CASE statement):
```sql
SELECT t.*, 
CASE 
    WHEN DATE(t.planned_date) = ? THEN 'planned_date'
    WHEN DATE(t.deadline) = ? THEN 'deadline'
    ELSE 'other'
END as source_field
FROM tasks t
WHERE t.assigned_to = ? 
AND t.status NOT IN ('completed', 'cancelled')
AND (
    DATE(t.planned_date) = ? OR
    (DATE(t.deadline) = ? AND (t.planned_date IS NULL OR t.planned_date = ''))
)
```

### After (UNION query with direct comparison):
```sql
SELECT t.*, 'planned_date' as source_field
FROM tasks t
WHERE t.assigned_to = ? 
AND t.status NOT IN ('completed', 'cancelled', 'deleted')
AND t.planned_date = ?

UNION ALL

SELECT t.*, 'deadline' as source_field
FROM tasks t
WHERE t.assigned_to = ? 
AND t.status NOT IN ('completed', 'cancelled', 'deleted')
AND DATE(t.deadline) = ?
AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00')
```

## Key Improvements

1. **Direct Comparison**: Uses `t.planned_date = ?` instead of `DATE(t.planned_date) = ?` for more reliable matching
2. **UNION Query**: Separates planned_date and deadline logic for clearer execution
3. **Explicit Null Checks**: More comprehensive checks for empty planned_date values
4. **Added 'deleted' Status**: Excludes deleted tasks from results
5. **Clear Source Field**: Each part of the UNION explicitly sets the source_field

## Expected Behavior
- Tasks with `planned_date = '2025-11-25'` will appear only on 2025-11-25
- Tasks with `deadline = '2025-11-25'` and no planned_date will appear on 2025-11-25
- Tasks will be properly inserted into `daily_tasks` with correct `source_field` values
- No more tasks appearing on wrong dates

## Files Modified
- `app/models/DailyPlanner.php` - Updated `fetchAssignedTasksForDate` method

## Testing
The fix should resolve the issue where:
- Test task ID 169 with planned_date = '2025-11-25' was not found on the planned date
- Tasks were incorrectly appearing on today's date instead of their planned date

The simplified UNION approach provides more predictable and reliable date matching behavior.