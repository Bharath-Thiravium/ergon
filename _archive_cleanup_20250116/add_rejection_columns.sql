-- Add only the missing tracking columns

ALTER TABLE `expenses` ADD COLUMN `rejected_by` INT NULL;
ALTER TABLE `expenses` ADD COLUMN `rejected_at` TIMESTAMP NULL;

ALTER TABLE `leaves` ADD COLUMN `rejected_by` INT NULL;
ALTER TABLE `leaves` ADD COLUMN `rejected_at` TIMESTAMP NULL;

ALTER TABLE `advances` ADD COLUMN `rejected_by` INT NULL;
ALTER TABLE `advances` ADD COLUMN `rejected_at` TIMESTAMP NULL;