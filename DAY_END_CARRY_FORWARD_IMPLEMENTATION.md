# Day-End Carry Forward Implementation

## Overview
Tasks are now carried forward **ONLY at day-end** (after 11:59 PM), not on page refresh or user actions.

## Key Changes Made

### 1. Removed Automatic Carry Forward
- **Before**: Tasks carried forward on every page visit/refresh
- **After**: Tasks remain on original planned date until day-end

### 2. Day-End Cron Job
**File**: `cron/daily_carry_forward.php`
- Runs daily at 12:01 AM
- Carries forward unattended tasks from previous days
- Prevents duplicate carry forwards with logging table
- Only moves tasks with status 'assigned' or 'not_started'

### 3. Scheduled Task Setup
**File**: `setup_daily_carry_forward.bat`
- Creates Windows scheduled task
- Runs daily at 12:01 AM automatically
- Can be run manually for testing

## How It Works

### Day-End Process (12:01 AM)
1. **Find Pending Tasks**: Tasks with `planned_date < today` and status 'assigned'/'not_started'
2. **Check Duplicates**: Ensure not already carried forward today
3. **Move Tasks**: Update `planned_date` to today
4. **Log Action**: Record in `carry_forward_log` table
5. **Prevent Duplicates**: Skip if already processed today

### Daily Planner Display
- Shows tasks with `planned_date = today`
- Shows tasks carried forward from previous days
- **NO carry forward on page refresh/visit**

## Setup Instructions

### 1. Set Up Scheduled Task
```bash
# Run as Administrator
setup_daily_carry_forward.bat
```

### 2. Manual Testing
```bash
# Test the carry forward logic
php test_manual_carry_forward.php
```

### 3. Verify Setup
- Check Windows Task Scheduler for "Ergon Daily Carry Forward"
- Task should run daily at 12:01 AM

## Database Changes

### New Table: carry_forward_log
```sql
CREATE TABLE carry_forward_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Workflow Logic

### Before Day-End
- Tasks stay on their original planned dates
- Daily planner shows only tasks planned for that specific date
- **No automatic carry forward on user actions**

### At Day-End (12:01 AM)
- Cron job runs automatically
- Finds all unattended tasks from past dates
- Moves them to current date
- Logs the action to prevent duplicates

### After Day-End
- Carried forward tasks appear in today's planner
- Original planned dates are updated to today
- Users see their pending work from previous days

## Benefits

✅ **No unwanted carry forwards** - Tasks don't move on page refresh
✅ **Predictable behavior** - Carry forward only happens at day-end
✅ **No duplicates** - Logging prevents multiple carry forwards
✅ **Audit trail** - Complete log of all carry forward actions
✅ **System controlled** - Not dependent on user actions

## Testing

### Create Test Scenario
1. Create task with yesterday's planned date
2. Set status to 'assigned'
3. Visit today's planner - task should NOT appear
4. Run manual carry forward test
5. Visit today's planner - task should now appear

### Manual Test Commands
```bash
# Test the cron job manually
php cron/daily_carry_forward.php

# Or use the test wrapper
php test_manual_carry_forward.php
```

## Monitoring

### Log Files
- Location: `storage/logs/carry_forward_YYYY-MM.log`
- Contains: Daily execution logs, task counts, errors

### Database Logs
- Table: `carry_forward_log`
- Tracks: Which tasks were moved, when, and for which users

## Troubleshooting

### Task Not Carried Forward
1. Check if cron job is running: Task Scheduler
2. Check logs: `storage/logs/carry_forward_*.log`
3. Verify task status is 'assigned' or 'not_started'
4. Check if already carried forward today

### Scheduled Task Issues
1. Run `setup_daily_carry_forward.bat` as Administrator
2. Verify PHP path in scheduled task
3. Test manually: `php cron/daily_carry_forward.php`

## Summary

The system now properly implements day-end carry forward:
- ✅ Tasks carry forward ONLY at day-end
- ✅ No carry forward on page refresh/visit
- ✅ Automated daily process at 12:01 AM
- ✅ Duplicate prevention with logging
- ✅ Complete audit trail
- ✅ Predictable, system-controlled behavior