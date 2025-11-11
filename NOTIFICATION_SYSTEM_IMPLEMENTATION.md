# Owner Notifications System - Complete Implementation

## Overview
This document outlines the complete implementation of the Owner Notifications system for the ERGON Employee Tracker. The system ensures that all pending requests and updates from Admin and Employee users are displayed in the Owner Panel → Notifications.

## Database Schema

### Notifications Table
```sql
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receiver_read (receiver_id, is_read),
    INDEX idx_created_at (created_at)
);
```

## Core Components

### 1. Notification Model (`app/models/Notification.php`)
- **Purpose**: Handles CRUD operations for notifications
- **Key Methods**:
  - `create($data)`: Creates new notification
  - `getForUser($userId, $limit)`: Retrieves notifications for a user
  - `getUnreadCount($userId)`: Gets count of unread notifications
  - `markAsRead($id, $userId)`: Marks notification as read
  - `markAllAsRead($userId)`: Marks all notifications as read
  - `notify()`: Static method for quick notification creation
  - `notifyOwners()`: Static method to notify all owners

### 2. Notification Helper (`app/helpers/NotificationHelper.php`)
- **Purpose**: Universal notification creation service
- **Key Methods**:
  - `notifyOwners($senderId, $module, $action, $message, $referenceId)`: Notifies all active owners
  - `notifyUser($senderId, $receiverId, $module, $action, $message, $referenceId)`: Notifies specific user

### 3. Notification Controller (`app/controllers/NotificationController.php`)
- **Purpose**: Handles notification display and management
- **Key Methods**:
  - `index()`: Displays notification list
  - `getUnreadCount()`: API endpoint for unread count
  - `markAsRead()`: API endpoint to mark as read
  - `markAllAsRead()`: API endpoint to mark all as read

### 4. Notification View (`views/notifications/index.php`)
- **Purpose**: User interface for notifications
- **Features**:
  - Real-time notification display
  - Filter by notification type
  - Mark as read functionality
  - Auto-refresh every 30 seconds
  - Dark/light theme support

## Notification Triggers

### 1. Leave Requests (`app/controllers/LeaveController.php`)
**Trigger Point**: `create()` method after successful leave creation
```php
NotificationHelper::notifyOwners(
    $userId,
    'leave',
    'request',
    "{$user['name']} has requested leave from {$startDate} to {$endDate}",
    $leaveId
);
```

### 2. Expense Claims (`app/controllers/ExpenseController.php`)
**Trigger Point**: `create()` method after successful expense creation
```php
NotificationHelper::notifyOwners(
    $userId,
    'expense',
    'claim',
    "{$user['name']} submitted expense claim of ₹{$amount} for {$description}",
    $expenseId
);
```

### 3. Task Assignments (`app/controllers/TasksController.php`)
**Trigger Point**: `store()` method after successful task creation
```php
// Notify assigned user
NotificationHelper::notifyUser(
    $assignedBy,
    $assignedTo,
    'task',
    'assigned',
    "You have been assigned a new task: {$title}",
    $taskId
);

// Notify owners about new task creation
NotificationHelper::notifyOwners(
    $assignedBy,
    'task',
    'created',
    "New task '{$title}' assigned to {$assignedUser['name']}",
    $taskId
);
```

### 4. Attendance Alerts (`app/controllers/AttendanceController.php`)
**Trigger Point**: `clock()` method when late arrival detected
```php
if ($currentTime > '09:30:00') {
    NotificationHelper::notifyOwners(
        $userId,
        'attendance',
        'late_arrival',
        "{$user['name']} arrived late at " . date('H:i'),
        $attendanceId
    );
}
```

### 5. Advance Requests (`app/controllers/AdvanceController.php`)
**Trigger Point**: `store()` method after successful advance creation
```php
NotificationHelper::notifyOwners(
    $userId,
    'advance',
    'request',
    "{$user['name']} requested advance of ₹" . number_format($amount, 2),
    $advanceId
);
```

### 6. Daily Workflow (`app/controllers/DailyWorkflowController.php`)
**Trigger Points**:
- Morning planner submission
- Evening update submission

```php
// Morning planner
NotificationHelper::notifyOwners(
    $userId,
    'planner',
    'submitted',
    "{$user['name']} submitted daily plan with " . count($plans) . " tasks",
    null
);

// Evening update
NotificationHelper::notifyOwners(
    $userId,
    'evening_update',
    'submitted',
    "{$user['name']} submitted evening update with {$productivityScore}% productivity",
    null
);
```

### 7. Follow-ups (`app/controllers/FollowupController.php`)
**Trigger Point**: `store()` method after successful followup creation
```php
NotificationHelper::notifyOwners(
    $userId,
    'followup',
    'created',
    "{$user['name']} created follow-up: {$title}",
    $followupId
);
```

## API Endpoints

### 1. Fetch Notifications (`api/fetch_notifications.php`)
- **Purpose**: Real-time notification fetching
- **Method**: GET
- **Response**: JSON with notifications and unread count

### 2. Mark as Read (`/ergon/notifications/mark-as-read`)
- **Purpose**: Mark single notification as read
- **Method**: POST
- **Parameters**: `id` (notification ID)

### 3. Mark All as Read (`/ergon/notifications/mark-all-read`)
- **Purpose**: Mark all notifications as read
- **Method**: POST

### 4. Unread Count (`/ergon/api/notifications/unread-count`)
- **Purpose**: Get unread notification count
- **Method**: GET
- **Response**: JSON with count

## Frontend Features

### 1. Real-time Updates
- Auto-refresh every 30 seconds
- Visual indicators for new notifications
- Unread count badges

### 2. Filtering
- Filter by notification type (leave, expense, task, etc.)
- Show all or unread only

### 3. Actions
- Mark individual notifications as read
- Mark all notifications as read
- View referenced items (opens in new tab)

### 4. Responsive Design
- Mobile-friendly interface
- Dark/light theme support
- Accessible design

## Installation & Setup

### 1. Database Setup
The notification system automatically creates required tables when accessed. No manual setup needed.

### 2. Backfill Existing Data
Run the backfill script to create notifications for existing pending items:
```
http://localhost/ergon/backfill_notifications.php
```

### 3. Testing
Run the comprehensive test script:
```
http://localhost/ergon/test_complete_notification_system.php
```

## Usage

### For Owners
1. Navigate to `/ergon/notifications`
2. View all pending notifications
3. Filter by type if needed
4. Click notifications to view details
5. Mark as read when processed

### For Developers
1. Use `NotificationHelper::notifyOwners()` for owner notifications
2. Use `NotificationHelper::notifyUser()` for user-specific notifications
3. Always include reference_id for linking back to source data

## Security Considerations

### 1. Role-based Access
- Only owners receive owner notifications
- Users can only see their own notifications
- Proper session validation

### 2. Data Validation
- All inputs sanitized
- SQL injection prevention
- XSS protection

### 3. Performance
- Indexed database queries
- Pagination support
- Efficient AJAX polling

## Troubleshooting

### Common Issues

1. **Notifications not appearing**
   - Check database connection
   - Verify notification table exists
   - Run diagnosis script: `/ergon/diagnose_notifications_complete.php`

2. **Notification creation failing**
   - Check NotificationHelper inclusion
   - Verify user permissions
   - Check error logs

3. **Frontend not updating**
   - Check JavaScript console for errors
   - Verify API endpoints are accessible
   - Check session validity

### Debug Tools

1. **Diagnosis Script**: `/ergon/diagnose_notifications_complete.php`
2. **Test Script**: `/ergon/test_complete_notification_system.php`
3. **Backfill Script**: `/ergon/backfill_notifications.php`

## File Structure

```
ergon/
├── app/
│   ├── controllers/
│   │   ├── NotificationController.php
│   │   ├── LeaveController.php (with notifications)
│   │   ├── ExpenseController.php (with notifications)
│   │   ├── TasksController.php (with notifications)
│   │   ├── AttendanceController.php (with notifications)
│   │   ├── AdvanceController.php (with notifications)
│   │   ├── DailyWorkflowController.php (with notifications)
│   │   └── FollowupController.php (with notifications)
│   ├── models/
│   │   └── Notification.php
│   └── helpers/
│       └── NotificationHelper.php
├── views/
│   └── notifications/
│       └── index.php
├── api/
│   └── fetch_notifications.php
├── backfill_notifications.php
├── test_complete_notification_system.php
└── diagnose_notifications_complete.php
```

## Conclusion

The Owner Notifications system is now fully implemented and integrated across all modules. It provides real-time visibility into all pending requests and system activities, ensuring owners can efficiently manage and respond to employee needs.

The system is designed to be:
- **Scalable**: Easy to add new notification types
- **Maintainable**: Clean separation of concerns
- **User-friendly**: Intuitive interface with modern UX
- **Reliable**: Comprehensive error handling and logging
- **Secure**: Role-based access and data validation

For any issues or enhancements, refer to the troubleshooting section or contact the development team.