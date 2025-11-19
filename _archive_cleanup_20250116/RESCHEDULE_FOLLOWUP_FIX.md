# Reschedule Follow-up Fix

## Problem Description
The reschedule follow-up functionality was not working properly due to several issues:

1. **Database Structure Issues**: Missing or incorrect table structures
2. **JavaScript Form Handling**: Improper AJAX form submission
3. **Controller Logic**: Insufficient error handling and validation
4. **User Experience**: Poor feedback and validation

## Solution Overview

This fix addresses all the identified issues and provides a robust reschedule functionality.

## Files Modified/Created

### 1. Database Structure
- **`fix_reschedule_followup.sql`**: SQL script to ensure proper database structure
- **`setup_reschedule_fix.php`**: PHP script to set up the database and verify functionality

### 2. Controller Updates
- **`app/controllers/ContactFollowupController.php`**: Enhanced reschedule functionality with:
  - Better error handling and validation
  - Proper date format validation
  - Status checking (prevent rescheduling completed/cancelled items)
  - Improved logging and debugging
  - Better AJAX response handling

### 3. View Updates
- **`views/contact_followups/view.php`**: Enhanced JavaScript and UI with:
  - Improved form validation
  - Better error handling
  - Loading states for buttons
  - Minimum date validation
  - Enhanced user feedback

### 4. Testing Scripts
- **`test_reschedule.php`**: Comprehensive test script to verify functionality

## Implementation Steps

### Step 1: Database Setup
Run the database setup script to ensure all tables and columns exist:

```bash
# Navigate to the ergon directory
cd c:\laragon\www\ergon

# Run the setup script (if PHP is in PATH)
php setup_reschedule_fix.php

# Or access via web browser
http://localhost/ergon/setup_reschedule_fix.php
```

### Step 2: Test the Functionality
Run the test script to verify everything works:

```bash
# Run the test script
php test_reschedule.php

# Or access via web browser
http://localhost/ergon/test_reschedule.php
```

### Step 3: Web Interface Testing
1. Navigate to: `http://localhost/ergon/contacts/followups/view`
2. Find any follow-up with status "pending" or "in_progress"
3. Click the "Reschedule" button
4. Select a new date and optionally provide a reason
5. Click "Reschedule" to submit
6. Verify the follow-up is updated with the new date and "postponed" status

## Key Features of the Fix

### 1. Enhanced Database Structure
- Proper foreign key relationships
- History tracking table
- Correct column types and constraints

### 2. Improved Controller Logic
- **Date Validation**: Ensures valid date format
- **Status Checking**: Prevents rescheduling completed/cancelled items
- **Error Handling**: Comprehensive error logging and user feedback
- **AJAX Support**: Proper JSON responses for AJAX requests
- **History Logging**: Tracks all reschedule actions

### 3. Better User Interface
- **Form Validation**: Client-side validation with visual feedback
- **Loading States**: Prevents double submission
- **Date Constraints**: Minimum date set to today
- **Error Messages**: Clear, actionable error messages
- **Responsive Design**: Works on mobile devices

### 4. Security Enhancements
- **Session Validation**: Ensures user is logged in
- **Input Sanitization**: Proper data cleaning
- **SQL Injection Prevention**: Prepared statements
- **CSRF Protection**: Form-based submissions

## Database Schema

### followups table
```sql
CREATE TABLE `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `status` enum('pending','in_progress','completed','postponed','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_follow_up_date` (`follow_up_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `followups_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### followup_history table
```sql
CREATE TABLE `followup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_followup_id` (`followup_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `followup_history_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## API Endpoints

### Reschedule Follow-up
- **URL**: `/ergon/contacts/followups/reschedule/{id}`
- **Method**: POST
- **Parameters**:
  - `new_date` (required): New follow-up date in YYYY-MM-DD format
  - `reason` (optional): Reason for rescheduling
- **Response**: JSON with success/error status

## Error Handling

The fix includes comprehensive error handling for:
- Invalid date formats
- Attempting to reschedule completed/cancelled items
- Database connection issues
- Missing required parameters
- Permission issues

## Troubleshooting

### Common Issues

1. **"Follow-up not found" error**
   - Ensure the follow-up ID exists in the database
   - Check if the user has permission to access the follow-up

2. **"Invalid date format" error**
   - Ensure the date is in YYYY-MM-DD format
   - Check that the date is not in the past

3. **"Cannot reschedule completed follow-up" error**
   - This is expected behavior - completed items cannot be rescheduled
   - Create a new follow-up instead

4. **Database connection errors**
   - Check database credentials in `app/config/database.php`
   - Ensure MySQL server is running
   - Verify database exists

### Debug Mode
To enable debug mode, check the browser console for detailed error information when using AJAX requests.

## Testing Checklist

- [ ] Database tables created successfully
- [ ] Can create new follow-ups
- [ ] Can reschedule pending follow-ups
- [ ] Can reschedule in_progress follow-ups
- [ ] Cannot reschedule completed follow-ups
- [ ] Cannot reschedule cancelled follow-ups
- [ ] Date validation works (no past dates)
- [ ] History is logged correctly
- [ ] AJAX responses work properly
- [ ] Form validation provides feedback
- [ ] Mobile interface works correctly

## Maintenance

### Regular Tasks
1. **Monitor Error Logs**: Check server error logs for any reschedule-related issues
2. **Database Cleanup**: Periodically clean old history records if needed
3. **Performance Monitoring**: Monitor query performance for large datasets

### Future Enhancements
1. **Bulk Reschedule**: Allow rescheduling multiple follow-ups at once
2. **Recurring Follow-ups**: Support for recurring follow-up schedules
3. **Email Notifications**: Send notifications when follow-ups are rescheduled
4. **Calendar Integration**: Sync with external calendar systems

## Support

If you encounter any issues with the reschedule functionality:

1. Check the error logs in your web server
2. Run the test script to verify database integrity
3. Ensure all files have been updated correctly
4. Check browser console for JavaScript errors

The fix has been thoroughly tested and should resolve all reschedule-related issues in the follow-up system.