# Manual Attendance Fix Steps

If the automated script fails, follow these manual steps:

## Step 1: Create New Attendance Table

Run this SQL in phpMyAdmin or your MySQL client:

```sql
-- Create new attendance table with correct structure
DROP TABLE IF EXISTS attendance_new;
CREATE TABLE attendance_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    location_name VARCHAR(255) DEFAULT 'Office',
    status VARCHAR(20) DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_check_in_date (check_in),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Step 2: Backup and Replace Table

```sql
-- Backup old table and replace with new one
DROP TABLE IF EXISTS attendance_backup;
RENAME TABLE attendance TO attendance_backup;
RENAME TABLE attendance_new TO attendance;
```

## Step 3: Create Supporting Tables

```sql
-- Create attendance rules table
CREATE TABLE IF NOT EXISTS attendance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_latitude DECIMAL(10, 8) DEFAULT 0,
    office_longitude DECIMAL(11, 8) DEFAULT 0,
    office_radius_meters INT DEFAULT 200,
    is_gps_required BOOLEAN DEFAULT TRUE,
    grace_period_minutes INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO attendance_rules (office_latitude, office_longitude, office_radius_meters, is_gps_required)
VALUES (0, 0, 200, TRUE);

-- Create shifts table
CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00:00',
    end_time TIME NOT NULL DEFAULT '18:00:00',
    grace_period INT DEFAULT 15,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO shifts (id, name, start_time, end_time, grace_period)
VALUES (1, 'Regular Shift', '09:00:00', '18:00:00', 15);
```

## Step 4: Update Users Table (Optional)

```sql
-- Add shift_id column to users table (MySQL 8.0+)
ALTER TABLE users ADD COLUMN IF NOT EXISTS shift_id INT DEFAULT 1;
```

For older MySQL versions:
```sql
-- Check if column exists first, then add if needed
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name='users' AND column_name='shift_id' AND table_schema=DATABASE()) = 0,
    'ALTER TABLE users ADD COLUMN shift_id INT DEFAULT 1',
    'SELECT "shift_id column already exists"'
));
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;
```

## Step 5: Verify the Fix

```sql
-- Check table structure
DESCRIBE attendance;

-- Check if tables exist
SHOW TABLES LIKE '%attendance%';
SHOW TABLES LIKE 'shifts';

-- Verify data
SELECT COUNT(*) as attendance_records FROM attendance;
SELECT COUNT(*) as users_count FROM users;
```

## Step 6: Test Clock In/Out

1. Go to `/ergon/attendance/clock`
2. Try to clock in
3. Check if record appears in database:
   ```sql
   SELECT * FROM attendance ORDER BY id DESC LIMIT 5;
   ```

## Troubleshooting

### If foreign key constraint fails:
```sql
-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;
-- Run the CREATE TABLE statement
-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
```

### If column already exists error:
- Ignore the error and continue with next steps
- The IF NOT EXISTS clause should handle this

### If attendance still doesn't work:
1. Check browser console for JavaScript errors
2. Verify the UnifiedAttendanceController.php file exists
3. Check that routes.php points to UnifiedAttendanceController
4. Clear browser cache and try again

## Success Indicators

✅ New attendance table created with DATETIME columns
✅ Supporting tables (attendance_rules, shifts) created  
✅ Users can clock in/out without errors
✅ Attendance records appear in database
✅ Attendance list displays properly

## Rollback (if needed)

If something goes wrong:
```sql
-- Restore original table
DROP TABLE IF EXISTS attendance;
RENAME TABLE attendance_backup TO attendance;
```