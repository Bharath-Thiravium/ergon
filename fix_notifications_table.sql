-- Fix notifications table structure
-- Add missing columns for sender_id, receiver_id, module_name, action_type

ALTER TABLE `notifications` 
ADD COLUMN `sender_id` int DEFAULT NULL AFTER `id`,
ADD COLUMN `receiver_id` int DEFAULT NULL AFTER `sender_id`,
ADD COLUMN `module_name` varchar(50) DEFAULT NULL AFTER `message`,
ADD COLUMN `action_type` varchar(50) DEFAULT NULL AFTER `module_name`;

-- Update existing records to use new structure
UPDATE `notifications` SET `receiver_id` = `user_id` WHERE `receiver_id` IS NULL;

-- Add foreign key constraints
ALTER TABLE `notifications` 
ADD CONSTRAINT `fk_notifications_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_notifications_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;