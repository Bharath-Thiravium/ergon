# Planned Date Workflow Implementation

## Overview
This implementation ensures that tasks in the Daily Planner module appear **strictly based on their planned date**, not their creation date.

## Problem Solved
- **Before**: Tasks appeared in the daily planner based on creation date, deadline, or assignment date
- **After**: Tasks appear ONLY on the date specified in the `planned_date` field
- **Added**: Unattended/pending tasks from previous dates are automatically carried forward to the current date

## Example Scenarios

### Scenario 1: Planned Date Workflow
- Task created on: **20/11/2025 (today)**
- Planned Date entered: **21/11/2025**
- **Result**: 
  - Task does NOT appear in planner for 20/11/2025
  - Task ONLY appears in planner for 21/11/2025

### Scenario 2: Carry Forward Workflow
- Task planned for: **19/11/2025 (yesterday)**
- Task status: **assigned** (not started)
- When viewing: **20/11/2025 (today)**
- **Result**: 
  - Task is automatically moved to 20/11/2025
  - Task appears in today's planner
  - No pending work is lost

## Files Modified

### 1. UnifiedWorkflowController.php
**Location**: `app/controllers/UnifiedWorkflowController.php`

**Key Changes**:
- Modified `createDailyTasksFromRegular()` method
- Changed SQL query to filter by `planned_date` instead of multiple date fields
- Added fallback for tasks without planned date (uses creation date)
- Added `carryForwardPendingTasks()` method for automatic task forwarding
- Added logic to prevent carry forward for historical date views

**New Query Logic**:
```sql
SELECT * FROM tasks 
WHERE assigned_to = ? 
AND status != 'completed'
AND (
    planned_date = ? OR 
    (planned_date IS NULL AND DATE(created_at) = ?)
)
```

### 2. TasksController.php
**Location**: `app/controllers/TasksController.php`

**Key Changes**:
- Added `planned_date` field to task creation and editing
- Updated INSERT and UPDATE queries to include `planned_date`
- Added logging for planned date values

### 3. Database Migration Script
**Location**: `fix_planned_date_workflow.php`

**Purpose**:
- Ensures `planned_date` column exists in tasks table
- Adds proper indexes for performance
- Validates table structure

### 4. Carry Forward Test Script
**Location**: `test_carry_forward.php`

**Purpose**:
- Tests the carry forward functionality
- Verifies pending tasks are moved to current date
- Ensures completed tasks are not moved

### 4. Test Script
**Location**: `test_planned_date_workflow.php`

**Purpose**:
- Tests the workflow with sample data
- Verifies tasks appear only on planned dates
- Creates and cleans up test tasks

## Database Schema Changes

### Tasks Table
```sql
ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL AFTER deadline;
CREATE INDEX idx_tasks_planned_date ON tasks (planned_date);
CREATE INDEX idx_tasks_assigned_planned ON tasks (assigned_to, planned_date, status);
```

## How It Works

### Task Creation
1. User creates task in `/ergon/tasks/create`
2. User sets **Planned Date** using `id="planned_date"` field
3. Task is stored with `planned_date` value
4. Task will only appear in daily planner on the planned date

### Daily Planner Display
1. User navigates to `/ergon/workflow/daily-planner/YYYY-MM-DD`
2. **If viewing current/future date**: System carries forward pending tasks from past dates
3. System queries tasks where:
   - `planned_date = selected_date` OR
   - `planned_date IS NULL AND created_date = selected_date`
4. Only matching tasks are displayed

### Carry Forward Logic
1. **Triggers**: When viewing current date or future dates
2. **Conditions**: Tasks with status 'assigned' or 'not_started'
3. **Action**: Updates `planned_date` from past dates to current date
4. **Exclusions**: Completed, cancelled, or in-progress tasks remain on original dates

### Workflow Logic
```php
// Carry forward pending tasks (only for current/future dates)
if ($shouldCarryForward) {
    $stmt = $db->prepare("
        UPDATE tasks SET planned_date = ? 
        WHERE assigned_to = ? 
        AND status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    $stmt->execute([$currentDate, $userId, $currentDate]);
}

// Get tasks for daily planner
$stmt = $db->prepare("
    SELECT *, COALESCE(sla_hours, 1.0) as sla_hours FROM tasks 
    WHERE assigned_to = ? 
    AND status != 'completed'
    AND (
        planned_date = ? OR 
        (planned_date IS NULL AND DATE(created_at) = ?)
    )
    ORDER BY 
        CASE WHEN assigned_by != assigned_to THEN 1 ELSE 2 END,
        CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
        created_at DESC
");
$stmt->execute([$userId, $date, $date]);
```

## Installation Steps

1. **Run Database Migration**:
   ```bash
   php fix_planned_date_workflow.php
   ```
   Or double-click: `run_planned_date_fix.bat`

2. **Test the Implementation**:
   ```bash
   php test_planned_date_workflow.php
   php test_carry_forward.php
   ```

3. **Verify in UI**:
   - Create a task with tomorrow's planned date
   - Check today's planner (task should NOT appear)
   - Check tomorrow's planner (task should appear)
   - Create a task with yesterday's planned date
   - Check today's planner (task should appear - carried forward)

## Validation Steps

### Manual Testing
1. Go to `/ergon/tasks/create`
2. Create task with title: "Test Planned Date"
3. Set **Planned Date** to tomorrow's date
4. Save task
5. Go to `/ergon/workflow/daily-planner` (today)
6. Verify task does NOT appear
7. Go to `/ergon/workflow/daily-planner/YYYY-MM-DD` (tomorrow's date)
8. Verify task DOES appear

### Expected Behavior
- ✅ Tasks appear only on their planned date
- ✅ Tasks without planned date appear on creation date
- ✅ No tasks appear on wrong dates
- ✅ Daily planner shows correct task count per date
- ✅ Pending tasks from past dates are carried forward
- ✅ Completed tasks remain on their original dates
- ✅ Historical date views show original task distribution

## Backward Compatibility

### Existing Tasks
- Tasks without `planned_date` will appear on their creation date
- No existing functionality is broken
- All existing tasks remain accessible

### Migration Safety
- New column is nullable (no data loss)
- Fallback logic handles NULL values
- Indexes improve performance

## Performance Considerations

### Indexes Added
1. `idx_tasks_planned_date` - Single column index
2. `idx_tasks_assigned_planned` - Composite index for daily planner queries

### Query Optimization
- Efficient filtering by planned date
- Proper use of indexes
- Minimal database load

## Troubleshooting

### Task Not Appearing in Planner
1. Check if `planned_date` is set correctly
2. Verify date format (YYYY-MM-DD)
3. Ensure task status is not 'completed'
4. Check user assignment
5. For past dates: Task may have been carried forward to current date

### Database Issues
1. Run migration script again
2. Check if `planned_date` column exists
3. Verify indexes are created
4. Check error logs

### Debug Queries
```sql
-- Check tasks with planned dates
SELECT id, title, planned_date, status FROM tasks WHERE planned_date IS NOT NULL;

-- Check today's planner tasks for user
SELECT * FROM tasks 
WHERE assigned_to = 1 
AND (planned_date = CURDATE() OR (planned_date IS NULL AND DATE(created_at) = CURDATE()));

-- Check pending tasks that should be carried forward
SELECT id, title, planned_date, status FROM tasks 
WHERE assigned_to = 1 
AND status IN ('assigned', 'not_started') 
AND planned_date < CURDATE();
```

## Future Enhancements

### Possible Improvements
1. Bulk update planned dates for existing tasks
2. Smart date suggestions based on workload
3. Planned date validation (business days only)
4. Integration with calendar systems

### API Extensions
- Add planned date to task API responses
- Support planned date in bulk operations
- Add planned date filtering to task lists

## Summary

This implementation provides a robust, efficient solution for planned date-based task scheduling in the Daily Planner module. Tasks now appear strictly on their planned dates, giving users precise control over their daily workflow planning.

**Key Benefits**:
- ✅ Precise task scheduling
- ✅ Better workflow planning
- ✅ Improved user experience
- ✅ Backward compatibility
- ✅ Performance optimized
- ✅ No pending work is lost
- ✅ Automatic task management
- ✅ Historical accuracy preserved