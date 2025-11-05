-- ERGON Employee Tracker - Refactored Database Schema
-- Complete Task Management, Daily Workflow & Follow-up System

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Core System Tables
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','admin','user') DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `reporting_to` int DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `is_first_login` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `head_id` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `project_manager_id` int DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `budget` decimal(12,2) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Task Management System
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_number` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `project_id` int DEFAULT NULL,
  `parent_task_id` int DEFAULT NULL,
  `assigned_by` int NOT NULL,
  `assigned_to` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `task_type` enum('development','testing','documentation','meeting','review','support','maintenance','followup') DEFAULT 'development',
  `priority` enum('low','medium','high','urgent','critical') DEFAULT 'medium',
  `status` enum('draft','assigned','in_progress','review','completed','cancelled','on_hold') DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT '1.00',
  `actual_hours` decimal(5,2) DEFAULT '0.00',
  `progress_percentage` int DEFAULT '0',
  `completion_notes` text,
  `tags` varchar(500) DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `checklist_items` json DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `recurrence_pattern` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_number` (`task_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `task_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT '0',
  `attachments` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `task_time_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `hours_logged` decimal(4,2) DEFAULT '0.00',
  `description` text,
  `is_billable` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Daily Workflow System
CREATE TABLE IF NOT EXISTS `daily_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `plan_date` date NOT NULL,
  `shift_start` time DEFAULT '09:00:00',
  `shift_end` time DEFAULT '18:00:00',
  `total_planned_hours` decimal(4,2) DEFAULT '8.00',
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`plan_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `daily_plan_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `daily_plan_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `task_type` enum('assigned_task','self_task','meeting','break','training','admin') DEFAULT 'assigned_task',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `planned_start_time` time DEFAULT NULL,
  `planned_end_time` time DEFAULT NULL,
  `estimated_hours` decimal(4,2) DEFAULT '1.00',
  `actual_start_time` time DEFAULT NULL,
  `actual_end_time` time DEFAULT NULL,
  `actual_hours` decimal(4,2) DEFAULT '0.00',
  `progress_percentage` int DEFAULT '0',
  `status` enum('planned','in_progress','completed','cancelled','postponed') DEFAULT 'planned',
  `completion_notes` text,
  `blockers` text,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `daily_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `report_date` date NOT NULL,
  `daily_plan_id` int NOT NULL,
  `total_planned_hours` decimal(4,2) DEFAULT '0.00',
  `total_actual_hours` decimal(4,2) DEFAULT '0.00',
  `total_tasks_planned` int DEFAULT '0',
  `total_tasks_completed` int DEFAULT '0',
  `productivity_score` decimal(5,2) DEFAULT '0.00',
  `achievements` text,
  `challenges` text,
  `tomorrow_priorities` text,
  `overall_rating` enum('poor','fair','good','excellent') DEFAULT 'good',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `manager_feedback` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Follow-up System
CREATE TABLE IF NOT EXISTS `followup_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `default_priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `default_reminder_days` int DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `followups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `followup_number` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `assigned_to` int NOT NULL,
  `created_by` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `related_task_id` int DEFAULT NULL,
  `client_company` varchar(200) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_email` varchar(120) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_address` text,
  `priority` enum('low','medium','high','urgent','critical') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled','postponed','rescheduled') DEFAULT 'pending',
  `followup_type` enum('call','email','meeting','visit','document','payment','other') DEFAULT 'call',
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `completed_time` time DEFAULT NULL,
  `outcome` text,
  `next_action` text,
  `next_followup_date` date DEFAULT NULL,
  `reminder_enabled` tinyint(1) DEFAULT '1',
  `reminder_sent` tinyint(1) DEFAULT '0',
  `reminder_count` int DEFAULT '0',
  `attachments` json DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `recurrence_pattern` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `followup_number` (`followup_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `followup_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `followup_id` int NOT NULL,
  `action_type` enum('created','updated','completed','cancelled','rescheduled','reminder_sent','status_changed') NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `notes` text,
  `performed_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Supporting Tables
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `break_start` timestamp NULL DEFAULT NULL,
  `break_end` timestamp NULL DEFAULT NULL,
  `total_hours` decimal(4,2) DEFAULT '0.00',
  `break_hours` decimal(4,2) DEFAULT '0.00',
  `overtime_hours` decimal(4,2) DEFAULT '0.00',
  `status` enum('present','absent','late','half_day','on_leave') DEFAULT 'present',
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('task','followup','system','reminder','approval') DEFAULT 'system',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `record_id` int DEFAULT NULL,
  `description` text,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT 'ERGON Company',
  `company_email` varchar(255) DEFAULT '',
  `company_phone` varchar(20) DEFAULT '',
  `working_hours_start` time DEFAULT '09:00:00',
  `working_hours_end` time DEFAULT '18:00:00',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `task_auto_numbering` tinyint(1) DEFAULT '1',
  `followup_auto_numbering` tinyint(1) DEFAULT '1',
  `daily_plan_required` tinyint(1) DEFAULT '1',
  `evening_report_required` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;