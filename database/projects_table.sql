CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT IGNORE INTO `projects` (`id`, `name`, `description`, `department_id`, `status`) VALUES
(1, 'ERGON Development', 'Main employee tracking system development', 1, 'active'),
(2, 'Client Portal', 'Customer facing portal development', 2, 'active'),
(3, 'Mobile App', 'Mobile application for field employees', 1, 'active'),
(4, 'Marketing Campaign', 'Q1 marketing initiatives', 3, 'active');