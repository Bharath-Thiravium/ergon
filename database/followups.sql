-- Followups Management Tables
-- ERGON Employee Tracker System

-- Create followups table
CREATE TABLE IF NOT EXISTS `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `original_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','postponed','cancelled','rescheduled') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `next_reminder` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_follow_date` (`follow_up_date`),
  KEY `idx_status` (`status`),
  KEY `idx_reminder` (`next_reminder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create followup_history table
CREATE TABLE IF NOT EXISTS `followup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_followup_id` (`followup_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;