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
  `user_id` int(11) NOT NULL,
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
  FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample contacts if table is empty
INSERT IGNORE INTO `contacts` (`id`, `name`, `phone`, `email`, `company`) VALUES
(1, 'John Smith', '+1234567890', 'john.smith@example.com', 'ABC Corp'),
(2, 'Jane Doe', '+0987654321', 'jane.doe@example.com', 'XYZ Ltd'),
(3, 'Mike Johnson', '+1122334455', 'mike.johnson@example.com', 'Tech Solutions');

-- Insert sample followups if table is empty
INSERT IGNORE INTO `followups` (`id`, `contact_id`, `user_id`, `title`, `description`, `follow_up_date`, `status`) VALUES
(1, 1, 1, 'Initial Contact Follow-up', 'Follow up on the initial meeting discussion about project requirements', '2024-01-15', 'pending'),
(2, 2, 1, 'Proposal Review', 'Review the submitted proposal and discuss next steps', '2024-01-20', 'in_progress'),
(3, 3, 1, 'Contract Negotiation', 'Finalize contract terms and conditions', '2024-01-10', 'completed');