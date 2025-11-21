# Daily Task Rollover Feature

## Overview
The Daily Task Rollover feature automatically moves uncompleted tasks from previous days to the current day, ensuring no tasks are forgotten or lost.

## How It Works

### Automatic Rollover
- **When**: Runs daily at midnight via Windows Task Scheduler
- **What**: Moves uncompleted tasks (status: not_started, in_progress, on_break) to the next day
- **Conditions**: Only tasks with completion percentage < 100% are rolled over

### Manual Rollover
- **Trigger**: When users access today's Daily Planner
- **API Endpoint**: `/ergon/api/rollover_tasks.php` (admin only)
- **Cron Script**: `/ergon/cron/daily_rollover.php`

## Visual Indicators

### In Daily Planner
- Rolled-over tasks show: 🔄 "Rolled over from: [date]"
- Green border and background tint for rolled-over tasks
- Clear distinction from postponed tasks

### Task Status
- Original task remains on previous date with status tracking
- New task created for current date with progress preserved
- History logged for audit trail

## Setup Instructions

### 1. Windows Task Scheduler (Automatic)
```batch
# Run as Administrator
setup_daily_rollover.bat
```

### 2. Manual Setup
```batch
schtasks /create /tn "Ergon Daily Task Rollover" /tr "php.exe \"c:\laragon\www\ergon\cron\daily_rollover.php\"" /sc daily /st 00:01 /f
```

### 3. Test Rollover
```bash
# Command line test
php c:\laragon\www\ergon\cron\daily_rollover.php

# Web test (admin only)
http://localhost/ergon/cron/daily_rollover.php?manual=1

# API test (admin only)
http://localhost/ergon/api/rollover_tasks.php
```

## Database Changes

### New Columns Added
- `daily_tasks.postponed_from_date` - Tracks rollover source date
- Task history logging for rollover actions

### Rollover Logic
1. Find uncompleted tasks from previous day
2. Check if task already exists for current day
3. Create new daily task entry with preserved progress
4. Log rollover action in task history
5. Update daily performance metrics

## Benefits

### For Users
- Never lose track of incomplete tasks
- Automatic task continuity across days
- Clear visual indication of rolled-over work
- Preserved progress and timing data

### For Management
- Better task completion tracking
- Audit trail of task movements
- Performance metrics across days
- Reduced manual task management

## Configuration

### Rollover Rules
- Only uncompleted tasks (< 100% progress)
- Excludes already postponed tasks
- Prevents duplicate tasks on target date
- Maintains original task relationships

### Logging
- All rollovers logged to `/ergon/cron/rollover.log`
- Task history updated with rollover actions
- Performance metrics recalculated

## Troubleshooting

### Common Issues
1. **Rollover not running**: Check Windows Task Scheduler
2. **Duplicate tasks**: Check database constraints
3. **Missing tasks**: Check rollover log file
4. **Permission errors**: Run setup as Administrator

### Debug Commands
```php
// Enable debug mode in browser console
enableSLADebug();

// Check rollover status
DailyPlanner::runDailyRollover();

// Manual API call
fetch('/ergon/api/rollover_tasks.php');
```

## Files Modified/Created

### Core Files
- `app/models/DailyPlanner.php` - Rollover logic
- `app/controllers/UnifiedWorkflowController.php` - Auto-trigger
- `views/daily_workflow/unified_daily_planner.php` - Visual indicators

### Automation Files
- `cron/daily_rollover.php` - Cron job script
- `api/rollover_tasks.php` - Manual API endpoint
- `setup_daily_rollover.bat` - Windows setup script

### Documentation
- `DAILY_TASK_ROLLOVER.md` - This file

## Future Enhancements

### Planned Features
- Configurable rollover rules per user
- Smart rollover based on task priority
- Weekend/holiday rollover handling
- Email notifications for rolled-over tasks
- Bulk rollover management interface

### Integration Points
- Task module synchronization
- Calendar integration
- Notification system
- Reporting dashboard