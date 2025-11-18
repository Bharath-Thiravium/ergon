-- Fix Reschedule Follow-up Database Structure
-- Ensure all required tables and columns exist

-- Create contacts table if not exists
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_name` (`name`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create followups table if not exists
CREATE TABLE IF NOT EXISTS `followups` (
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
  INDEX `idx_contact_id` (`contact_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_follow_up_date` (`follow_up_date`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create followup_history table if not exists
CREATE TABLE IF NOT EXISTS `followup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_followup_id` (`followup_id`),
  INDEX `idx_created_by` (`created_by`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns if they don't exist
ALTER TABLE `followups` 
ADD COLUMN IF NOT EXISTS `user_id` int(11) DEFAULT NULL AFTER `contact_id`,
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- Ensure proper status enum values
ALTER TABLE `followups` 
MODIFY COLUMN `status` enum('pending','in_progress','completed','postponed','cancelled') NOT NULL DEFAULT 'pending';

-- Insert sample data if tables are empty (for testing)
INSERT IGNORE INTO `contacts` (`id`, `name`, `phone`, `email`, `company`) VALUES
(1, 'John Smith', '+1234567890', 'john.smith@example.com', 'ABC Corp'),
(2, 'Jane Doe', '+0987654321', 'jane.doe@example.com', 'XYZ Ltd'),
(3, 'Mike Johnson', '+1122334455', 'mike.johnson@example.com', 'Tech Solutions');