-- Enhanced Attendance System Schema
-- Fix for Missing Enhanced Tables

-- 1. Create shifts table
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_duration` int(11) DEFAULT 60,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create attendance_rules table
CREATE TABLE IF NOT EXISTS `attendance_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_latitude` decimal(10,8) DEFAULT NULL,
  `office_longitude` decimal(11,8) DEFAULT NULL,
  `office_radius_meters` int(11) DEFAULT 200,
  `is_gps_required` tinyint(1) DEFAULT 1,
  `auto_checkout_time` time DEFAULT '18:00:00',
  `half_day_hours` decimal(4,2) DEFAULT 4.00,
  `full_day_hours` decimal(4,2) DEFAULT 8.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create attendance_corrections table
CREATE TABLE IF NOT EXISTS `attendance_corrections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `correction_type` enum('check_in','check_out','both') NOT NULL,
  `original_check_in` datetime DEFAULT NULL,
  `original_check_out` datetime DEFAULT NULL,
  `corrected_check_in` datetime DEFAULT NULL,
  `corrected_check_out` datetime DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `attendance_id` (`attendance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Fix attendance table columns (add check_in/check_out if missing)
-- Note: MySQL doesn't support IF NOT EXISTS for ADD COLUMN, so we handle errors

-- Add check_in column
SET @sql = 'ALTER TABLE `attendance` ADD COLUMN `check_in` datetime DEFAULT NULL';
SET @sql_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_in' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@sql_check = 0, @sql, 'SELECT "check_in column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add check_out column
SET @sql = 'ALTER TABLE `attendance` ADD COLUMN `check_out` datetime DEFAULT NULL';
SET @sql_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'attendance' AND COLUMN_NAME = 'check_out' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@sql_check = 0, @sql, 'SELECT "check_out column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add other columns
SET @sql = 'ALTER TABLE `attendance` ADD COLUMN `shift_id` int(11) DEFAULT 1';
SET @sql_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'attendance' AND COLUMN_NAME = 'shift_id' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@sql_check = 0, @sql, 'SELECT "shift_id column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `attendance` ADD COLUMN `distance_meters` int(11) DEFAULT NULL';
SET @sql_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'attendance' AND COLUMN_NAME = 'distance_meters' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@sql_check = 0, @sql, 'SELECT "distance_meters column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `attendance` ADD COLUMN `is_auto_checkout` tinyint(1) DEFAULT 0';
SET @sql_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'attendance' AND COLUMN_NAME = 'is_auto_checkout' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@sql_check = 0, @sql, 'SELECT "is_auto_checkout column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Copy data from clock_in/clock_out to check_in/check_out if needed
UPDATE `attendance` SET 
  `check_in` = `clock_in`,
  `check_out` = `clock_out`
WHERE `check_in` IS NULL AND `clock_in` IS NOT NULL;

-- 6. Add shift_id to users table if not exists
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `shift_id` int(11) DEFAULT 1;

-- 7. Insert default data
INSERT IGNORE INTO `shifts` (`id`, `name`, `start_time`, `end_time`) VALUES
(1, 'General Shift', '09:00:00', '18:00:00'),
(2, 'Morning Shift', '06:00:00', '14:00:00'),
(3, 'Evening Shift', '14:00:00', '22:00:00');

INSERT IGNORE INTO `attendance_rules` (`id`, `office_latitude`, `office_longitude`, `office_radius_meters`, `is_gps_required`) VALUES
(1, 28.6139, 77.2090, 200, 1);