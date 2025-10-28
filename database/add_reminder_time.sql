-- Add reminder time column to followups table
ALTER TABLE `followups` ADD COLUMN `reminder_time` TIME DEFAULT NULL AFTER `next_reminder`;