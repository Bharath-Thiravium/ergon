-- =============================================
-- DATABASE CLEANUP SCRIPT FOR ERGON PROJECT
-- =============================================

-- 1. DROP BACKUP/LEGACY TABLES
DROP TABLE IF EXISTS `attendance_backup_temp`;
DROP TABLE IF EXISTS `attendance_new`;
DROP TABLE IF EXISTS `attendance_old`;
DROP TABLE IF EXISTS `followups_backup`;
DROP TABLE IF EXISTS `followups_backup_legacy`;
DROP TABLE IF EXISTS `followup_history_backup`;
DROP TABLE IF EXISTS `followup_history_backup_legacy`;

-- 2. DROP UNUSED/EMPTY TABLES
DROP TABLE IF EXISTS `evening_updates`;
DROP TABLE IF EXISTS `user_preferences`;
DROP TABLE IF EXISTS `user_sessions`;

-- 3. CLEAN TEST DATA FROM PRODUCTION
DELETE FROM `expenses` WHERE description LIKE '%test%' OR description LIKE '%Test%';
DELETE FROM `tasks` WHERE title LIKE '%test%' OR title LIKE '%Test%';
DELETE FROM `daily_tasks` WHERE title LIKE '%test%' OR title LIKE '%Test%';

-- 4. REMOVE INACTIVE/TERMINATED USER DATA
DELETE FROM `attendance` WHERE user_id IN (SELECT id FROM users WHERE status IN ('terminated', 'suspended'));
DELETE FROM `leaves` WHERE user_id IN (SELECT id FROM users WHERE status = 'terminated');
DELETE FROM `expenses` WHERE user_id IN (SELECT id FROM users WHERE status = 'terminated');

-- 5. CLEAN OLD AUDIT LOGS (KEEP LAST 6 MONTHS)
DELETE FROM `audit_logs` WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
DELETE FROM `login_attempts` WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
DELETE FROM `rate_limit_log` WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- 6. REMOVE DUPLICATE/REDUNDANT INDEXES (SAFE DROP)
SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_users_email_v2` ON `users`'), 'SELECT "Index idx_users_email_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_email_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_users_role_v2` ON `users`'), 'SELECT "Index idx_users_role_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_role_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_users_department_v2` ON `users`'), 'SELECT "Index idx_users_department_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_department_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_assigned_to_new` ON `tasks`'), 'SELECT "Index idx_tasks_assigned_to_new does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_assigned_to_new');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_status_new` ON `tasks`'), 'SELECT "Index idx_tasks_status_new does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_status_new');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_assigned_to_v2` ON `tasks`'), 'SELECT "Index idx_tasks_assigned_to_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_assigned_to_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_status_v2` ON `tasks`'), 'SELECT "Index idx_tasks_status_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_status_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_priority_v2` ON `tasks`'), 'SELECT "Index idx_tasks_priority_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_priority_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) > 0, CONCAT('DROP INDEX `idx_tasks_due_date_v2` ON `tasks`'), 'SELECT "Index idx_tasks_due_date_v2 does not exist"') FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_due_date_v2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. OPTIMIZE TABLES AFTER CLEANUP
OPTIMIZE TABLE `users`;
OPTIMIZE TABLE `tasks`;
OPTIMIZE TABLE `attendance`;
OPTIMIZE TABLE `expenses`;
OPTIMIZE TABLE `audit_logs`;