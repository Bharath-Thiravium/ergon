-- Check for any triggers on attendance table that might assign project_id
SHOW TRIGGERS LIKE 'attendance';

-- Check for any stored procedures that might assign project_id
SHOW PROCEDURE STATUS WHERE Db = DATABASE();

-- Check attendance table structure
DESCRIBE attendance;