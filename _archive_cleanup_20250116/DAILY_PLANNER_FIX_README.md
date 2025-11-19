# Daily Planner Task Display Fix

## Issue
The daily planner was not displaying tasks assigned from others and self-assigned tasks properly.

## Root Cause
The original query in `UnifiedWorkflowController.php` was only looking for tasks where `assigned_to = user_id`, which missed the distinction between:
- **Tasks assigned FROM others** (assigned_by ≠ assigned_to)
- **Tasks assigned BY self** (assigned_by = assigned_to)

## Solution

### 1. Updated Query Logic
Modified the query in `UnifiedWorkflowController::dailyPlanner()` to properly fetch both types of tasks:

```sql
SELECT * FROM tasks 
WHERE (assigned_to = ? OR (assigned_by = ? AND assigned_to = ?)) 
AND status != 'completed' 
ORDER BY 
    CASE 
        WHEN assigned_by != assigned_to THEN 1  -- Tasks from others (higher priority)
        ELSE 2                                   -- Self-assigned tasks
    END,
    created_at DESC 
LIMIT 10
```

### 2. Task Source Identification
Added visual indicators to distinguish task sources:
- **🧑‍🤝‍🧑 [From Others]** - Tasks assigned by other users
- **👤 [Self]** - Self-assigned tasks

### 3. Visual Enhancements
- Added colored left borders to task cards (orange for others, blue for self)
- Improved empty state message with clear explanation
- Added debug link for troubleshooting

### 4. Debug Tools
Created helper scripts:
- `debug_daily_planner.php` - Comprehensive debugging information
- `add_sample_tasks.php` - Adds sample tasks for testing

## Files Modified

1. **app/controllers/UnifiedWorkflowController.php**
   - Updated task fetching query
   - Added task source labeling
   - Improved task prioritization

2. **views/daily_workflow/unified_daily_planner.php**
   - Enhanced task display with source indicators
   - Improved empty state messaging
   - Added CSS styling for task sources

## Testing

1. **Add Sample Tasks:**
   ```
   http://localhost/ergon/add_sample_tasks.php
   ```

2. **Debug Information:**
   ```
   http://localhost/ergon/debug_daily_planner.php
   ```

3. **View Daily Planner:**
   ```
   http://localhost/ergon/workflow/daily-planner
   ```

## Expected Behavior

The daily planner now properly displays:
- ✅ Tasks assigned TO the user by others (higher priority)
- ✅ Tasks assigned BY the user to themselves
- ✅ Clear visual distinction between task sources
- ✅ Proper ordering (tasks from others first)
- ✅ Helpful empty state with action buttons

## Task Priority Order
1. **Tasks from others** (assigned_by ≠ assigned_to) - Higher priority
2. **Self-assigned tasks** (assigned_by = assigned_to) - Lower priority
3. Within each group: Most recent first

This ensures that tasks assigned by others are always visible and prioritized in the daily workflow.