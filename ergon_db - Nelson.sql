-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 28, 2025 at 02:48 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ergon_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int NOT NULL,
  `account_code` varchar(10) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_code`, `account_name`, `account_type`, `balance`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'E001', 'General Expenses', 'expense', 1400.00, 1, '2025-11-20 10:35:34', '2025-11-27 12:56:51'),
(2, 'E002', 'Travel Expenses', 'expense', 1825.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(3, 'E003', 'Office Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(4, 'E004', 'Miscellaneous Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(5, 'L001', 'Accounts Payable', 'liability', 3225.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(6, 'A001', 'Cash', 'asset', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(7, 'A002', 'Bank Account', 'asset', 0.00, 1, '2025-11-20 10:35:35', '2025-11-20 10:35:35');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `activity_type` enum('login','logout','task_update','break_start','break_end','system_ping') DEFAULT 'system_ping',
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_positions`
--

CREATE TABLE `admin_positions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_department` varchar(100) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `assigned_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advances`
--

CREATE TABLE `advances` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `advance_type` varchar(100) DEFAULT NULL,
  `repayment_date` date DEFAULT NULL,
  `requested_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `admin_remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `advances`
--

INSERT INTO `advances` (`id`, `user_id`, `type`, `amount`, `reason`, `advance_type`, `repayment_date`, `requested_date`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `admin_remarks`, `created_at`, `updated_at`, `rejected_by`, `rejected_at`) VALUES
(1, 1, 'Travel Advance', 500.00, 'Medical Emergency', NULL, NULL, '2025-10-31', 'approved', 37, '2025-11-20 16:31:15', NULL, NULL, '2025-10-31 05:50:03', '2025-11-20 11:01:15', NULL, NULL),
(3, 37, 'Project Advance', 2000.00, 'Need some money for Project', NULL, NULL, '2025-10-31', 'rejected', NULL, NULL, 'Reject', NULL, '2025-10-31 11:30:35', '2025-11-08 09:38:12', NULL, NULL),
(6, 36, 'Emergency Advance', 2000.00, 'Medical Emergency', NULL, NULL, '2025-11-01', 'approved', NULL, NULL, NULL, NULL, '2025-11-01 08:18:07', '2025-11-08 09:38:53', NULL, NULL),
(9, 37, 'Emergency Advance', 5000.00, 'Medical Emergency', NULL, NULL, '2025-11-08', 'rejected', NULL, NULL, 'Reject this Advance Request', NULL, '2025-11-08 09:37:22', '2025-11-20 11:10:52', 1, '2025-11-20 11:10:52'),
(10, 37, 'Emergency Advance', 3000.00, 'Medical Emergency', NULL, NULL, '2025-11-08', 'approved', 1, '2025-11-20 16:33:11', NULL, NULL, '2025-11-08 11:59:40', '2025-11-20 11:03:11', NULL, NULL),
(11, 2, 'Salary', 1000.00, 'Test advance', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 05:27:32', '2025-11-11 05:27:32', NULL, NULL),
(12, 2, 'Salary', 1000.00, 'Test advance', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 05:28:13', '2025-11-11 05:28:13', NULL, NULL),
(13, 3, 'Salary Advance', 5000.00, 'Test advance notification', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 05:40:59', '2025-11-11 05:40:59', NULL, NULL),
(14, 2, 'Emergency', 2000.00, 'Live test advance request', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 05:53:46', '2025-11-11 05:53:46', NULL, NULL),
(15, 3, 'Emergency', 3000.00, 'Final test advance', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 05:59:12', '2025-11-11 05:59:12', NULL, NULL),
(17, 36, 'Travel Advance', 5000.00, 'Business Trip', NULL, NULL, '2025-11-11', 'pending', NULL, NULL, NULL, NULL, '2025-11-11 11:25:57', '2025-11-11 11:25:57', NULL, NULL),
(18, 37, 'Travel Advance', 5500.00, 'Business trip', NULL, NULL, '2025-11-20', 'approved', 1, '2025-11-20 16:40:22', NULL, NULL, '2025-11-20 11:01:52', '2025-11-20 11:10:22', NULL, NULL),
(19, 48, 'Salary Advance', 5000.00, 'Business Trip', NULL, '2025-11-23', '2025-11-22', 'pending', NULL, NULL, NULL, NULL, '2025-11-22 12:54:49', '2025-11-22 12:59:28', NULL, NULL),
(21, 49, 'Salary Advance', 1500.00, 'Team Event - Refreshments & supplies', NULL, '2025-11-28', '2025-11-28', 'rejected', NULL, NULL, 'reject this advance', NULL, '2025-11-28 11:16:31', '2025-11-28 14:46:55', 37, '2025-11-28 14:46:55'),
(22, 49, 'Project Advance', 5000.00, 'Project Materials - Demo model materials', NULL, '2025-11-29', '2025-11-28', 'pending', NULL, NULL, NULL, NULL, '2025-11-28 11:17:08', '2025-11-28 11:17:08', NULL, NULL),
(23, 49, 'Salary Advance', 5000.00, 'Personal need', NULL, '2025-11-28', '2025-11-28', 'approved', 37, '2025-11-28 20:16:22', NULL, NULL, '2025-11-28 12:21:35', '2025-11-28 14:46:22', NULL, NULL);

--
-- Triggers `advances`
--
DELIMITER $$
CREATE TRIGGER `advance_notification_insert` AFTER INSERT ON `advances` FOR EACH ROW BEGIN
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, action_url)
            SELECT NEW.user_id, u.id, 'info', 'approval', 
                   CONCAT('New Advance Request from ', (SELECT name FROM users WHERE id = NEW.user_id)),
                   CONCAT('Advance request for $', NEW.amount, ' - ', NEW.reason),
                   'advance', NEW.id, 'advance', 'pending', CONCAT('/ergon/advances/view/', NEW.id)
            FROM users u 
            WHERE u.role IN ('admin', 'owner') AND u.status = 'active';
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `advance_notification_update` AFTER UPDATE ON `advances` FOR EACH ROW BEGIN
            IF OLD.status != NEW.status AND NEW.status IN ('approved', 'rejected') THEN
                INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, approver_id, action_url)
                VALUES (NEW.approved_by, NEW.user_id, 
                       CASE WHEN NEW.status = 'approved' THEN 'success' ELSE 'warning' END,
                       'approval', 
                       CONCAT('Advance Request ', UPPER(NEW.status)),
                       CONCAT('Your advance request has been ', NEW.status, ' - Amount: $', NEW.amount),
                       'advance', NEW.id, 'advance', NEW.status, NEW.approved_by, CONCAT('/ergon/advances/view/', NEW.id));
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int NOT NULL,
  `requested_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `clock_in_time` time DEFAULT NULL,
  `clock_out_time` time DEFAULT NULL,
  `date` date DEFAULT (curdate()),
  `location_lat` decimal(10,8) DEFAULT '0.00000000',
  `location_lng` decimal(11,8) DEFAULT '0.00000000',
  `status` enum('present','absent','late','on_leave') DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location` varchar(255) DEFAULT 'Office',
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `shift_id` int DEFAULT '1',
  `distance_meters` int DEFAULT NULL,
  `is_auto_checkout` tinyint(1) DEFAULT '0',
  `location_name` varchar(255) DEFAULT 'Office',
  `manual_entry` tinyint(1) DEFAULT '0',
  `edited_by` int DEFAULT NULL,
  `edit_reason` text,
  `working_hours` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `clock_in`, `clock_out`, `latitude`, `longitude`, `clock_in_time`, `clock_out_time`, `date`, `location_lat`, `location_lng`, `status`, `created_at`, `updated_at`, `location`, `check_in`, `check_out`, `shift_id`, `distance_meters`, `is_auto_checkout`, `location_name`, `manual_entry`, `edited_by`, `edit_reason`, `working_hours`) VALUES
(6, 1, '2025-10-31 07:48:23', '2025-10-31 08:06:05', 12.97160000, 77.59460000, NULL, NULL, '2025-10-31', 0.00000000, 0.00000000, 'present', '2025-10-31 07:48:23', '2025-11-03 04:34:42', 'Office', '2025-10-31 13:18:23', '2025-10-31 13:36:05', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(7, 37, '2025-10-31 07:49:12', '2025-10-31 07:49:20', 0.00000000, 0.00000000, NULL, NULL, '2025-10-31', 0.00000000, 0.00000000, 'present', '2025-10-31 07:49:12', '2025-11-03 04:34:42', 'Office', '2025-10-31 13:19:12', '2025-10-31 13:19:20', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(8, 1, '2025-10-31 08:20:18', NULL, 9.99424000, 78.14512640, NULL, NULL, '2025-10-31', 0.00000000, 0.00000000, 'present', '2025-10-31 08:20:18', '2025-11-03 04:34:42', 'Office', '2025-10-31 13:50:18', NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(10, 37, '2025-10-31 09:24:06', '2025-10-31 11:01:54', 0.00000000, 0.00000000, NULL, NULL, '2025-10-31', 0.00000000, 0.00000000, 'present', '2025-10-31 09:24:06', '2025-11-03 04:34:42', 'Office', '2025-10-31 14:54:06', '2025-10-31 16:31:54', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(11, 37, '2025-11-01 09:28:47', NULL, 9.98135275, 78.14313950, NULL, NULL, '2025-11-01', 0.00000000, 0.00000000, 'present', '2025-11-01 09:28:47', '2025-11-03 04:34:42', 'Office', '2025-11-01 14:58:47', NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(13, 1, '2025-11-01 11:08:54', '2025-11-01 12:28:24', 9.98768640, 78.14512640, NULL, NULL, '2025-11-01', 0.00000000, 0.00000000, 'present', '2025-11-01 11:08:54', '2025-11-03 04:34:42', 'Office', '2025-11-01 16:38:54', '2025-11-01 17:58:24', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(15, 37, '2025-11-03 04:31:13', '2025-11-03 05:54:34', 9.98135275, 78.14313950, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 04:31:13', '2025-11-03 08:24:59', 'Office', '2025-11-03 10:01:13', '2025-11-03 13:54:59', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(16, 37, '2025-11-03 05:55:31', '2025-11-03 05:55:35', 9.98135275, 78.14313950, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 05:55:31', '2025-11-03 05:55:35', 'Office', NULL, NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(17, 37, '2025-11-03 06:00:29', '2025-11-03 06:00:31', 9.98135275, 78.14313950, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 06:00:29', '2025-11-03 06:00:31', 'Office', NULL, NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(19, 37, '2025-11-03 06:20:38', '2025-11-03 06:20:40', 9.98135275, 78.14313950, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 06:20:38', '2025-11-03 06:20:40', 'Office', NULL, NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(20, 37, '2025-11-03 06:26:43', '2025-11-03 06:26:46', 9.98135275, 78.14313950, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 06:26:43', '2025-11-03 06:26:46', 'Office', NULL, NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(33, 37, '2025-11-03 09:25:44', NULL, 0.00000000, 0.00000000, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 09:25:44', '2025-11-03 09:25:44', 'Office', NULL, NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(34, 16, NULL, NULL, 28.61390000, 77.20900000, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 09:26:12', '2025-11-03 09:26:14', 'Test Office', '2025-11-03 14:56:12', '2025-11-03 14:56:14', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(39, 16, NULL, NULL, 0.00000000, 0.00000000, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 13:10:40', '2025-11-03 13:12:35', 'Office', '2025-11-03 18:40:40', '2025-11-03 18:42:35', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(40, 16, NULL, NULL, 0.00000000, 0.00000000, NULL, NULL, '2025-11-03', 0.00000000, 0.00000000, 'present', '2025-11-03 13:36:07', '2025-11-03 13:36:09', 'Office', '2025-11-03 19:06:07', '2025-11-03 19:06:09', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(48, 37, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-08', 0.00000000, 0.00000000, 'present', '2025-11-08 11:42:00', '2025-11-08 11:42:00', 'Office', '2025-11-08 17:12:00', NULL, 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(63, 37, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-14', 0.00000000, 0.00000000, 'present', '2025-11-14 06:49:32', '2025-11-14 06:49:53', 'Office', '2025-11-14 12:19:32', '2025-11-14 12:19:53', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(69, 37, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18', 0.00000000, 0.00000000, 'present', '2025-11-18 12:31:29', '2025-11-18 13:23:53', 'Office', '2025-11-18 18:01:29', '2025-11-18 18:53:53', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(70, 48, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-19', 0.00000000, 0.00000000, 'present', '2025-11-19 06:48:48', '2025-11-19 14:01:36', 'Office', '2025-11-19 12:18:48', '2025-11-19 19:31:36', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(77, 49, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20', 0.00000000, 0.00000000, 'present', '2025-11-20 12:31:59', '2025-11-20 12:31:59', 'Office', '2025-11-20 09:00:00', NULL, 1, NULL, 0, 'On Approved Leave', 0, NULL, NULL, NULL),
(78, 47, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20', 0.00000000, 0.00000000, 'present', '2025-11-20 12:33:59', '2025-11-20 12:34:00', 'Office', '2025-11-20 18:03:59', '2025-11-20 18:04:00', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(79, 48, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20', 0.00000000, 0.00000000, 'present', '2025-11-20 13:05:45', '2025-11-20 13:08:08', 'Office', '2025-11-20 18:35:45', '2025-11-20 18:38:08', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(80, 16, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21', 0.00000000, 0.00000000, 'present', '2025-11-21 05:37:56', '2025-11-21 05:38:13', 'Office', '2025-11-21 09:00:00', '2025-11-21 11:00:00', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(81, 49, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21', 0.00000000, 0.00000000, 'present', '2025-11-21 05:39:53', '2025-11-21 05:41:11', 'Office', '2025-11-21 11:09:53', '2025-11-21 11:11:11', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(82, 48, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21', 0.00000000, 0.00000000, 'present', '2025-11-21 05:52:28', '2025-11-21 05:52:50', 'Office', '2025-11-21 09:00:00', '2025-11-21 11:22:50', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(86, 37, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21', 0.00000000, 0.00000000, 'present', '2025-11-21 09:16:17', '2025-11-21 09:16:21', 'Office', '2025-11-21 09:00:00', '2025-11-21 14:46:21', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(87, 48, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22', 0.00000000, 0.00000000, 'present', '2025-11-22 11:20:16', '2025-11-22 11:24:53', 'Office', '2025-11-22 16:50:16', '2025-11-22 16:54:53', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(88, 16, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22', 0.00000000, 0.00000000, 'present', '2025-11-22 11:26:03', '2025-11-22 11:26:19', 'Office', '2025-11-22 09:00:00', '2025-11-22 16:00:00', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(89, 37, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22', 0.00000000, 0.00000000, 'present', '2025-11-22 12:00:40', '2025-11-22 12:11:19', 'Office', '2025-11-22 17:30:40', '2025-11-22 17:41:19', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_corrections`
--

CREATE TABLE `attendance_corrections` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `attendance_id` int DEFAULT NULL,
  `correction_date` date NOT NULL,
  `original_check_in` datetime DEFAULT NULL,
  `original_check_out` datetime DEFAULT NULL,
  `requested_check_in` datetime DEFAULT NULL,
  `requested_check_out` datetime DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_rules`
--

CREATE TABLE `attendance_rules` (
  `id` int NOT NULL,
  `auto_checkout_time` time DEFAULT '18:00:00',
  `half_day_hours` decimal(3,1) DEFAULT '4.0',
  `full_day_hours` decimal(3,1) DEFAULT '8.0',
  `late_threshold_minutes` int DEFAULT '15',
  `office_latitude` decimal(10,8) DEFAULT '0.00000000',
  `office_longitude` decimal(11,8) DEFAULT '0.00000000',
  `office_radius_meters` int DEFAULT '200',
  `weekend_days` varchar(20) DEFAULT 'saturday,sunday',
  `is_gps_required` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance_rules`
--

INSERT INTO `attendance_rules` (`id`, `auto_checkout_time`, `half_day_hours`, `full_day_hours`, `late_threshold_minutes`, `office_latitude`, `office_longitude`, `office_radius_meters`, `weekend_days`, `is_gps_required`, `created_at`, `updated_at`) VALUES
(1, '18:00:00', 4.0, 8.0, 15, 28.61390000, 77.20900000, 200, 'saturday,sunday', 1, '2025-11-01 12:14:28', '2025-11-01 12:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `module`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 06:31:44'),
(2, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 06:43:05'),
(3, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 06:43:10'),
(4, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 06:46:50'),
(5, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:32:49'),
(6, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:01'),
(7, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:05'),
(8, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:26'),
(9, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:30'),
(10, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:45:14'),
(11, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:58:54'),
(12, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 10:23:15'),
(13, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 10:23:50'),
(14, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 10:25:31'),
(15, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 10:29:48'),
(16, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:01:27'),
(17, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:01:35'),
(18, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:01:41'),
(19, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:01:43'),
(20, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:02:06'),
(21, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:02:19'),
(22, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:04:23'),
(23, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 12:59:10'),
(24, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 13:01:58'),
(25, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 15:35:38'),
(26, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 15:38:27'),
(27, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 15:38:35'),
(28, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:41:51'),
(29, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:41:59'),
(30, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:42:11'),
(31, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:42:15'),
(32, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:42:24'),
(33, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:42:32'),
(34, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:42:37'),
(35, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:00:49'),
(36, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:00:55'),
(37, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:04:26'),
(38, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:05:01'),
(39, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:07:42'),
(40, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:10'),
(41, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:13'),
(42, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:17'),
(43, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:21'),
(44, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:09:00'),
(45, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:13:51'),
(46, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:21'),
(47, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:28'),
(48, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:51'),
(49, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:18:38'),
(50, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:07:57'),
(51, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:08:03'),
(52, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:31:06'),
(53, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:31:10'),
(54, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:36:56'),
(55, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:38:52'),
(56, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:39:14'),
(57, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:40:21'),
(58, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:40:27'),
(59, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:40:29'),
(60, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:43:22'),
(61, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:22:05'),
(62, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:26:34'),
(63, NULL, 'auth', 'login_failed', 'Failed login attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:26:54'),
(64, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:07:44'),
(65, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:07:49'),
(66, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:13:07'),
(67, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:13:11'),
(68, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 03:22:49'),
(69, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 03:25:55'),
(70, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 04:18:29'),
(71, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:04:31'),
(72, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:04:36'),
(73, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:32:29'),
(74, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:32:35'),
(75, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:33:02'),
(76, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:33:06'),
(77, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:30'),
(78, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:33'),
(79, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:34'),
(80, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:51'),
(81, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:58'),
(82, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:14:30'),
(83, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:17:20'),
(84, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:35:32'),
(85, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:42:58'),
(86, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:51:41'),
(87, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:51:45'),
(88, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:15:33'),
(89, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:15:37'),
(90, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 09:48:25'),
(91, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:02:26'),
(92, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:03:10'),
(93, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:03:20'),
(94, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:04:22'),
(95, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:04:34'),
(96, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:04:42'),
(97, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:04:43'),
(98, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:05:02'),
(99, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 14:45:41'),
(100, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:19:12'),
(101, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:19:18'),
(102, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:46:20'),
(103, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `badge_definitions`
--

CREATE TABLE `badge_definitions` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 0xF09F8F86,
  `criteria_type` enum('points','tasks','streak','productivity') NOT NULL,
  `criteria_value` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `badge_definitions`
--

INSERT INTO `badge_definitions` (`id`, `name`, `description`, `icon`, `criteria_type`, `criteria_value`, `is_active`, `created_at`) VALUES
(1, 'First Task', 'Complete your first task', '🎯', 'tasks', 1, 1, '2025-10-26 22:02:34'),
(2, 'Task Master', 'Complete 10 tasks', '⭐', 'tasks', 10, 1, '2025-10-26 22:02:34'),
(3, 'Productivity Pro', 'Achieve 90% productivity score', '🚀', 'productivity', 90, 1, '2025-10-26 22:02:34'),
(4, 'Point Collector', 'Earn 100 points', '💎', 'points', 100, 1, '2025-10-26 22:02:34'),
(5, 'Consistent Performer', '5-day task completion streak', '🔥', 'streak', 5, 1, '2025-10-26 22:02:34');

-- --------------------------------------------------------

--
-- Table structure for table `circulars`
--

CREATE TABLE `circulars` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `posted_by` int NOT NULL,
  `visible_to` enum('All','Admin','User') DEFAULT 'All',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `circulars`
--

INSERT INTO `circulars` (`id`, `title`, `message`, `posted_by`, `visible_to`, `created_at`) VALUES
(1, 'Year-End Holiday Schedule', 'Please note the office will be closed from Dec 24-26 for Christmas holidays. Regular operations resume Dec 27.', 1, 'All', '2024-12-15 03:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `phone`, `email`, `company`, `created_at`) VALUES
(1, 'John Smith', '+1-555-0123', 'john@company.com', 'Tech Corp', '2025-11-17 07:55:16'),
(2, 'Sarah Johnson', '+1-555-0124', 'sarah@business.com', 'Business Inc', '2025-11-17 07:55:16'),
(3, 'Mike Wilson', '+1-555-0125', 'mike@startup.com', 'Startup LLC', '2025-11-17 07:55:16'),
(4, 'Emma Davis', '+1-555-0126', 'emma@consulting.com', 'Davis Consulting', '2025-11-17 07:55:16'),
(5, 'Robert Brown', '+1-555-0127', 'robert@solutions.com', 'Brown Solutions', '2025-11-17 07:55:16'),
(6, 'NELSON', '9896798216', 'nelson@athenas.co.in', 'AS', '2025-11-17 07:59:17'),
(7, 'Jessie', '9875421552', 'jessie@gmail.com', 'Athenas', '2025-11-22 08:19:46'),
(8, 'Chris Evans', '98765 43211', 'chrisevans@gmail.com', 'BKG', '2025-11-28 09:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `daily_performance`
--

CREATE TABLE `daily_performance` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `total_planned_minutes` int DEFAULT '0',
  `total_active_minutes` decimal(10,2) DEFAULT '0.00',
  `total_tasks` int DEFAULT '0',
  `completed_tasks` int DEFAULT '0',
  `in_progress_tasks` int DEFAULT '0',
  `postponed_tasks` int DEFAULT '0',
  `completion_percentage` decimal(5,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_performance`
--

INSERT INTO `daily_performance` (`id`, `user_id`, `date`, `total_planned_minutes`, `total_active_minutes`, `total_tasks`, `completed_tasks`, `in_progress_tasks`, `postponed_tasks`, `completion_percentage`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-11-19', 6420, 0.00, 9, 1, 1, 1, 11.11, '2025-11-19 04:59:42', '2025-11-19 08:24:24'),
(3, 37, '2025-11-19', 2340, 0.00, 8, 1, 2, 3, 12.50, '2025-11-19 08:25:07', '2025-11-19 10:35:17'),
(5, 48, '2025-11-19', 15, 11.28, 1, 0, 0, 1, 0.00, '2025-11-19 14:05:55', '2025-11-19 14:05:55'),
(6, 48, '2025-11-20', 10, 0.00, 3, 0, 0, 1, 0.00, '2025-11-20 07:30:03', '2025-11-20 08:29:29'),
(8, 48, '2025-11-23', 21, 0.00, 3, 0, 0, 0, 0.00, '2025-11-20 08:13:39', '2025-11-20 08:13:39'),
(11, 48, '2025-11-21', 18, 37.43, 5, 3, 0, 1, 60.00, '2025-11-21 05:18:11', '2025-11-21 09:07:53'),
(12, 37, '2025-11-21', 87, 367.13, 6, 2, 1, 2, 33.33, '2025-11-21 05:55:47', '2025-11-21 11:57:29'),
(71, 37, '2025-11-22', 15, 305.32, 6, 0, 1, 0, 0.00, '2025-11-22 13:05:08', '2025-11-22 13:05:08'),
(72, 37, '2025-11-24', 3090, 1104.57, 24, 1, 1, 9, 4.17, '2025-11-24 05:55:31', '2025-11-24 14:28:38'),
(92, 37, '2025-11-25', 600, 242.75, 10, 0, 2, 2, 0.00, '2025-11-25 04:02:13', '2025-11-25 11:43:07'),
(110, 1, '2025-11-25', 60, 0.03, 1, 0, 1, 0, 0.00, '2025-11-25 10:46:34', '2025-11-25 10:46:34'),
(114, 37, '2025-11-26', 480, 241.48, 8, 0, 2, 1, 0.00, '2025-11-26 09:11:21', '2025-11-26 09:11:21');

-- --------------------------------------------------------

--
-- Table structure for table `daily_planner`
--

CREATE TABLE `daily_planner` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `plan_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT '0.00',
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `completion_percentage` int DEFAULT '0',
  `completion_status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `reminder_time` time DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_planners`
--

CREATE TABLE `daily_planners` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `plan_date` date NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT NULL,
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `completion_status` enum('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
  `completion_percentage` int DEFAULT '0',
  `notes` text,
  `reminder_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_planner_audit`
--

CREATE TABLE `daily_planner_audit` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_date` date DEFAULT NULL,
  `task_count` int DEFAULT '0',
  `details` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_planner_audit`
--

INSERT INTO `daily_planner_audit` (`id`, `user_id`, `action`, `target_date`, `task_count`, `details`, `timestamp`) VALUES
(1, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 04:48:40'),
(2, 37, 'view_access', '2025-11-20', 0, NULL, '2025-11-21 04:48:49'),
(3, 37, 'view_access', '2025-11-19', 8, NULL, '2025-11-21 04:48:52'),
(4, 37, 'view_access', '2025-11-20', 0, NULL, '2025-11-21 04:49:13'),
(5, 37, 'view_access', '2025-11-20', 0, NULL, '2025-11-21 04:49:28'),
(6, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 04:49:32'),
(7, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 04:49:33'),
(8, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 04:49:42'),
(9, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 04:49:48'),
(10, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:50:25'),
(11, 48, 'view_access', '2025-11-20', 2, NULL, '2025-11-21 04:50:33'),
(12, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:51:06'),
(13, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:51:23'),
(14, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:51:26'),
(15, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:51:29'),
(16, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:51:35'),
(17, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:51:59'),
(18, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:53:01'),
(19, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:53:08'),
(20, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:53:27'),
(21, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 04:53:33'),
(22, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:53:39'),
(23, 48, 'view_access', '2025-11-19', 3, NULL, '2025-11-21 04:54:33'),
(24, 48, 'view_access', '2025-11-19', 7, NULL, '2025-11-21 04:54:50'),
(25, 48, 'view_access', '2025-11-20', 4, NULL, '2025-11-21 04:54:59'),
(26, 49, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:13:13'),
(27, 49, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:13:16'),
(28, 49, 'view_access', '2025-11-20', 2, NULL, '2025-11-21 05:13:19'),
(29, 49, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:13:25'),
(30, 49, 'view_access', '2025-11-20', 2, NULL, '2025-11-21 05:13:27'),
(31, 49, 'view_access', '2025-11-19', 0, NULL, '2025-11-21 05:13:33'),
(32, 49, 'view_access', '2025-11-18', 0, NULL, '2025-11-21 05:13:37'),
(33, 49, 'view_access', '2025-11-20', 2, NULL, '2025-11-21 05:13:39'),
(34, 49, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:13:50'),
(35, 49, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:14:25'),
(36, 49, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:14:27'),
(37, 49, 'view_access', '2025-11-20', 2, NULL, '2025-11-21 05:15:11'),
(38, 49, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:15:56'),
(39, 48, 'view_access', '2025-11-21', 3, NULL, '2025-11-21 05:17:29'),
(40, 48, 'view_access', '2025-11-21', 4, NULL, '2025-11-21 05:17:33'),
(41, 48, 'view_access', '2025-11-21', 4, NULL, '2025-11-21 05:18:26'),
(42, 48, 'view_access', '2025-11-21', 4, NULL, '2025-11-21 05:18:33'),
(43, 48, 'view_access', '2025-11-21', 4, NULL, '2025-11-21 05:21:54'),
(44, 37, 'view_access', '2025-11-21', 0, NULL, '2025-11-21 05:26:22'),
(45, 37, 'view_access', '2025-11-20', 0, NULL, '2025-11-21 05:26:30'),
(46, 37, 'view_access', '2025-11-19', 8, NULL, '2025-11-21 05:26:32'),
(47, 37, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:29:05'),
(48, 49, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:30:36'),
(49, 49, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:30:38'),
(50, 37, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:31:15'),
(51, 49, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:32:14'),
(52, 37, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:34:08'),
(53, 37, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:35:18'),
(54, 37, 'view_access', '2025-11-21', 1, NULL, '2025-11-21 05:36:53'),
(55, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:36:56'),
(56, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:37:24'),
(57, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:55:37'),
(58, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 05:55:37'),
(59, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 06:04:53'),
(60, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 06:07:30'),
(61, 48, 'view_access', '2025-11-21', 4, NULL, '2025-11-21 06:09:42'),
(62, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 06:09:48'),
(63, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 06:10:40'),
(64, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 06:39:27'),
(65, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 06:46:12'),
(66, 37, 'view_access', '2025-11-21', 2, NULL, '2025-11-21 08:24:25'),
(67, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 08:24:33'),
(68, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 08:34:01'),
(69, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 08:34:32'),
(70, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 08:34:42'),
(71, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:02:03'),
(72, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:02'),
(73, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:16'),
(74, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:17'),
(75, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:19'),
(76, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:20'),
(77, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:33'),
(78, 48, 'view_access', '2025-11-21', 5, NULL, '2025-11-21 09:04:46'),
(79, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:43:31\"}', '2025-11-21 09:13:31'),
(80, 47, 'view_access', '2025-11-21', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-21 14:50:09\"}', '2025-11-21 09:20:09'),
(81, 47, 'historical_view_access', '2025-11-20', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-21 14:50:42\"}', '2025-11-21 09:20:42'),
(82, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:50:50\"}', '2025-11-21 09:20:50'),
(83, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:52:01\"}', '2025-11-21 09:22:01'),
(84, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:53:32\"}', '2025-11-21 09:23:32'),
(85, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:53:50\"}', '2025-11-21 09:23:50'),
(86, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:53:58\"}', '2025-11-21 09:23:58'),
(87, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:53:59\"}', '2025-11-21 09:23:59'),
(88, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:54:05\"}', '2025-11-21 09:24:05'),
(89, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 14:54:11\"}', '2025-11-21 09:24:11'),
(90, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:00:06\"}', '2025-11-21 09:30:06'),
(91, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:04:07\"}', '2025-11-21 09:34:07'),
(92, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:05:32\"}', '2025-11-21 09:35:32'),
(93, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:05:39\"}', '2025-11-21 09:35:39'),
(94, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:09:47\"}', '2025-11-21 09:39:47'),
(95, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:09:51\"}', '2025-11-21 09:39:51'),
(96, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:09:53\"}', '2025-11-21 09:39:53'),
(97, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:09:56\"}', '2025-11-21 09:39:56'),
(98, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:10:21\"}', '2025-11-21 09:40:21'),
(99, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:10:22\"}', '2025-11-21 09:40:22'),
(100, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:10:24\"}', '2025-11-21 09:40:24'),
(101, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:11:15\"}', '2025-11-21 09:41:15'),
(102, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:11:17\"}', '2025-11-21 09:41:17'),
(103, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:32\"}', '2025-11-21 09:42:32'),
(104, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:35\"}', '2025-11-21 09:42:35'),
(105, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:39\"}', '2025-11-21 09:42:39'),
(106, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:41\"}', '2025-11-21 09:42:41'),
(107, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:41\"}', '2025-11-21 09:42:41'),
(108, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:43\"}', '2025-11-21 09:42:43'),
(109, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:12:44\"}', '2025-11-21 09:42:44'),
(110, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:14\"}', '2025-11-21 09:43:14'),
(111, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:19\"}', '2025-11-21 09:43:19'),
(112, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:29\"}', '2025-11-21 09:43:29'),
(113, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:33\"}', '2025-11-21 09:43:33'),
(114, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:33\"}', '2025-11-21 09:43:33'),
(115, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:13:43\"}', '2025-11-21 09:43:43'),
(116, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:14:52\"}', '2025-11-21 09:44:52'),
(117, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:15:07\"}', '2025-11-21 09:45:07'),
(118, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:16:15\"}', '2025-11-21 09:46:15'),
(119, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:16:17\"}', '2025-11-21 09:46:17'),
(120, 37, 'view_access', '2025-11-21', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-21 15:23:02\"}', '2025-11-21 09:53:02'),
(121, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:06\"}', '2025-11-21 09:53:06'),
(122, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:15\"}', '2025-11-21 09:53:15'),
(123, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:18\"}', '2025-11-21 09:53:18'),
(124, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:19\"}', '2025-11-21 09:53:19'),
(125, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:23\"}', '2025-11-21 09:53:23'),
(126, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:24\"}', '2025-11-21 09:53:24'),
(127, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:23:26\"}', '2025-11-21 09:53:26'),
(128, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:26:16\"}', '2025-11-21 09:56:16'),
(129, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:26:27\"}', '2025-11-21 09:56:27'),
(130, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:30:19\"}', '2025-11-21 10:00:19'),
(131, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:32:21\"}', '2025-11-21 10:02:21'),
(132, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:32:24\"}', '2025-11-21 10:02:24'),
(133, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:32:26\"}', '2025-11-21 10:02:26'),
(134, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:32:46\"}', '2025-11-21 10:02:46'),
(135, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 15:37:45\"}', '2025-11-21 10:07:45'),
(136, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:13:28\"}', '2025-11-21 10:43:28'),
(137, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 16:18:14\"}', '2025-11-21 10:48:14'),
(138, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:19:57\"}', '2025-11-21 10:49:57'),
(139, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:22:54\"}', '2025-11-21 10:52:54'),
(140, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:26:22\"}', '2025-11-21 10:56:22'),
(141, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:29:21\"}', '2025-11-21 10:59:21'),
(142, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:29:48\"}', '2025-11-21 10:59:48'),
(143, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:11\"}', '2025-11-21 11:01:11'),
(144, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:12\"}', '2025-11-21 11:01:12'),
(145, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:13\"}', '2025-11-21 11:01:13'),
(146, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:14\"}', '2025-11-21 11:01:14'),
(147, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:45\"}', '2025-11-21 11:01:45'),
(148, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:31:49\"}', '2025-11-21 11:01:49'),
(149, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:23\"}', '2025-11-21 11:02:23'),
(150, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:24\"}', '2025-11-21 11:02:24'),
(151, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:24\"}', '2025-11-21 11:02:24'),
(152, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:25\"}', '2025-11-21 11:02:25'),
(153, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:25\"}', '2025-11-21 11:02:25'),
(154, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:26\"}', '2025-11-21 11:02:26'),
(155, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:32:29\"}', '2025-11-21 11:02:29'),
(156, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:35:08\"}', '2025-11-21 11:05:08'),
(157, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:35:09\"}', '2025-11-21 11:05:09'),
(158, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:35:41\"}', '2025-11-21 11:05:41'),
(159, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:35:48\"}', '2025-11-21 11:05:48'),
(160, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:37:33\"}', '2025-11-21 11:07:33'),
(161, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:37:34\"}', '2025-11-21 11:07:34'),
(162, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:40:35\"}', '2025-11-21 11:10:35'),
(163, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:44:43\"}', '2025-11-21 11:14:43'),
(164, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:44:44\"}', '2025-11-21 11:14:44'),
(165, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:44:45\"}', '2025-11-21 11:14:45'),
(166, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:44:45\"}', '2025-11-21 11:14:45'),
(167, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:44:45\"}', '2025-11-21 11:14:45'),
(168, 37, 'view_access', '2025-11-21', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-21 16:47:03\"}', '2025-11-21 11:17:03'),
(169, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:47:06\"}', '2025-11-21 11:17:06'),
(170, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:50:46\"}', '2025-11-21 11:20:46'),
(171, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:50:49\"}', '2025-11-21 11:20:49'),
(172, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:50:49\"}', '2025-11-21 11:20:49'),
(173, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:50:50\"}', '2025-11-21 11:20:50'),
(174, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:53:41\"}', '2025-11-21 11:23:41'),
(175, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 16:58:31\"}', '2025-11-21 11:28:31'),
(176, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:01:02\"}', '2025-11-21 11:31:02'),
(177, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:01:10\"}', '2025-11-21 11:31:10'),
(178, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:09:47\"}', '2025-11-21 11:39:47'),
(179, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:02\"}', '2025-11-21 11:40:02'),
(180, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:30\"}', '2025-11-21 11:40:30'),
(181, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:37\"}', '2025-11-21 11:40:37'),
(182, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:51\"}', '2025-11-21 11:40:51'),
(183, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:54\"}', '2025-11-21 11:40:54'),
(184, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:10:55\"}', '2025-11-21 11:40:55'),
(185, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:12:30\"}', '2025-11-21 11:42:30'),
(186, 37, 'view_access', '2025-11-21', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-21 17:12:33\"}', '2025-11-21 11:42:33'),
(187, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:12:34\"}', '2025-11-21 11:42:34'),
(188, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:12:52\"}', '2025-11-21 11:42:52'),
(189, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:13:03\"}', '2025-11-21 11:43:03'),
(190, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:13:14\"}', '2025-11-21 11:43:14'),
(191, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:19:50\"}', '2025-11-21 11:49:50'),
(192, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:20:11\"}', '2025-11-21 11:50:11'),
(193, 37, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 17:23:00\"}', '2025-11-21 11:53:00'),
(194, 37, 'view_access', '2025-11-21', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-21 17:23:01\"}', '2025-11-21 11:53:01'),
(195, 37, 'view_access', '2025-11-21', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-21 17:26:58\"}', '2025-11-21 11:56:58'),
(196, 37, 'view_access', '2025-11-21', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-21 17:27:32\"}', '2025-11-21 11:57:32'),
(197, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 18:08:10\"}', '2025-11-21 12:38:10'),
(198, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 18:08:11\"}', '2025-11-21 12:38:11'),
(199, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 18:08:13\"}', '2025-11-21 12:38:13'),
(200, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 18:08:30\"}', '2025-11-21 12:38:30'),
(201, 48, 'view_access', '2025-11-21', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-21 18:09:15\"}', '2025-11-21 12:39:15'),
(202, 48, 'view_access', '2025-11-21', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-21 18:09:19\"}', '2025-11-21 12:39:19'),
(203, 37, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 13:30:46\"}', '2025-11-22 08:00:46'),
(204, 37, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 13:30:57\"}', '2025-11-22 08:00:57'),
(205, 37, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 13:31:10\"}', '2025-11-22 08:01:10'),
(206, 37, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 13:32:06\"}', '2025-11-22 08:02:06'),
(207, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 13:32:12\"}', '2025-11-22 08:02:12'),
(208, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 13:34:02\"}', '2025-11-22 08:04:02'),
(209, 48, 'view_access', '2025-11-22', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-22 14:46:22\"}', '2025-11-22 09:16:22'),
(210, 48, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 14:46:26\"}', '2025-11-22 09:16:26'),
(211, 48, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 14:47:27\"}', '2025-11-22 09:17:27'),
(212, 48, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 14:47:31\"}', '2025-11-22 09:17:31'),
(213, 48, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 14:47:42\"}', '2025-11-22 09:17:42'),
(214, 48, 'view_access', '2025-11-22', 3, '{\"view_type\":\"current\",\"task_count\":3,\"date_accessed\":\"2025-11-22 14:48:03\"}', '2025-11-22 09:18:03'),
(215, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:48:05\"}', '2025-11-22 09:18:05'),
(216, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:48:47\"}', '2025-11-22 09:18:47'),
(217, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:48:52\"}', '2025-11-22 09:18:52'),
(218, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:49:31\"}', '2025-11-22 09:19:31'),
(219, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:49:34\"}', '2025-11-22 09:19:34'),
(220, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:49:38\"}', '2025-11-22 09:19:38'),
(221, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:49:40\"}', '2025-11-22 09:19:40'),
(222, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 14:51:23\"}', '2025-11-22 09:21:23'),
(223, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 17:58:00\"}', '2025-11-22 12:28:00'),
(224, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 17:58:12\"}', '2025-11-22 12:28:12'),
(225, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 17:58:39\"}', '2025-11-22 12:28:39'),
(226, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 17:58:56\"}', '2025-11-22 12:28:56'),
(227, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 17:59:01\"}', '2025-11-22 12:29:01'),
(228, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:05:28\"}', '2025-11-22 12:35:28'),
(229, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:05:48\"}', '2025-11-22 12:35:48'),
(230, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:31:28\"}', '2025-11-22 13:01:28'),
(231, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:31:34\"}', '2025-11-22 13:01:34'),
(232, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:31:36\"}', '2025-11-22 13:01:36'),
(233, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:32:39\"}', '2025-11-22 13:02:39'),
(234, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:32:46\"}', '2025-11-22 13:02:46'),
(235, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:33:13\"}', '2025-11-22 13:03:13'),
(236, 37, 'view_access', '2025-11-22', 5, '{\"view_type\":\"current\",\"task_count\":5,\"date_accessed\":\"2025-11-22 18:33:47\"}', '2025-11-22 13:03:47'),
(237, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:33:51\"}', '2025-11-22 13:03:51'),
(238, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:53\"}', '2025-11-22 13:12:53'),
(239, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:54\"}', '2025-11-22 13:12:54'),
(240, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:55\"}', '2025-11-22 13:12:55'),
(241, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:55\"}', '2025-11-22 13:12:55'),
(242, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:55\"}', '2025-11-22 13:12:55'),
(243, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:55\"}', '2025-11-22 13:12:55'),
(244, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:42:56\"}', '2025-11-22 13:12:56'),
(245, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:03\"}', '2025-11-22 13:13:03'),
(246, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:03\"}', '2025-11-22 13:13:03'),
(247, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:04\"}', '2025-11-22 13:13:04'),
(248, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:04\"}', '2025-11-22 13:13:04'),
(249, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:07\"}', '2025-11-22 13:13:07'),
(250, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:10\"}', '2025-11-22 13:13:10'),
(251, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:16\"}', '2025-11-22 13:13:16'),
(252, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:18\"}', '2025-11-22 13:13:18'),
(253, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:19\"}', '2025-11-22 13:13:19'),
(254, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:19\"}', '2025-11-22 13:13:19'),
(255, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:19\"}', '2025-11-22 13:13:19'),
(256, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:20\"}', '2025-11-22 13:13:20'),
(257, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:20\"}', '2025-11-22 13:13:20'),
(258, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:20\"}', '2025-11-22 13:13:20'),
(259, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:20\"}', '2025-11-22 13:13:20'),
(260, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:23\"}', '2025-11-22 13:13:23'),
(261, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:25\"}', '2025-11-22 13:13:25'),
(262, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:26\"}', '2025-11-22 13:13:26'),
(263, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:27\"}', '2025-11-22 13:13:27'),
(264, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:27\"}', '2025-11-22 13:13:27'),
(265, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:27\"}', '2025-11-22 13:13:27'),
(266, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:28\"}', '2025-11-22 13:13:28'),
(267, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:28\"}', '2025-11-22 13:13:28'),
(268, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:28\"}', '2025-11-22 13:13:28'),
(269, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:28\"}', '2025-11-22 13:13:28'),
(270, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:29\"}', '2025-11-22 13:13:29'),
(271, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:29\"}', '2025-11-22 13:13:29'),
(272, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:29\"}', '2025-11-22 13:13:29'),
(273, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:29\"}', '2025-11-22 13:13:29'),
(274, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:30\"}', '2025-11-22 13:13:30'),
(275, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:30\"}', '2025-11-22 13:13:30'),
(276, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:31\"}', '2025-11-22 13:13:31'),
(277, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:31\"}', '2025-11-22 13:13:31'),
(278, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:32\"}', '2025-11-22 13:13:32'),
(279, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:42\"}', '2025-11-22 13:13:42'),
(280, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:43:44\"}', '2025-11-22 13:13:44'),
(281, 37, 'view_access', '2025-11-22', 6, '{\"view_type\":\"current\",\"task_count\":6,\"date_accessed\":\"2025-11-22 18:45:19\"}', '2025-11-22 13:15:19'),
(282, 48, 'view_access', '2025-11-22', 4, '{\"view_type\":\"current\",\"task_count\":4,\"date_accessed\":\"2025-11-22 19:08:38\"}', '2025-11-22 13:38:38'),
(283, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:19:28\"}', '2025-11-24 04:49:28'),
(284, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:19:31\"}', '2025-11-24 04:49:31'),
(285, 37, 'historical_view_access', '2025-11-23', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:19:35\"}', '2025-11-24 04:49:35'),
(286, 37, 'historical_view_access', '2025-11-22', 4, '{\"view_type\":\"historical\",\"task_count\":4,\"date_accessed\":\"2025-11-24 10:19:39\"}', '2025-11-24 04:49:39'),
(287, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:19:48\"}', '2025-11-24 04:49:48'),
(288, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:22:33\"}', '2025-11-24 04:52:33'),
(289, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:22:35\"}', '2025-11-24 04:52:35'),
(290, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:23:25\"}', '2025-11-24 04:53:25'),
(291, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:23:27\"}', '2025-11-24 04:53:27'),
(292, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 10:23:30\"}', '2025-11-24 04:53:30'),
(293, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 10:23:32\"}', '2025-11-24 04:53:32'),
(294, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:48:51\"}', '2025-11-24 05:18:51'),
(295, 37, 'historical_view_access', '2025-11-23', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-24 10:48:53\"}', '2025-11-24 05:18:53'),
(296, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 10:48:56\"}', '2025-11-24 05:18:56'),
(297, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 10:50:11\"}', '2025-11-24 05:20:11'),
(298, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 10:50:16\"}', '2025-11-24 05:20:16'),
(299, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:50:21\"}', '2025-11-24 05:20:21'),
(300, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:50:25\"}', '2025-11-24 05:20:25'),
(301, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:50:31\"}', '2025-11-24 05:20:31'),
(302, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:51:25\"}', '2025-11-24 05:21:25'),
(303, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:51:26\"}', '2025-11-24 05:21:26'),
(304, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:51:27\"}', '2025-11-24 05:21:27'),
(305, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:51:28\"}', '2025-11-24 05:21:28'),
(306, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 10:51:29\"}', '2025-11-24 05:21:29'),
(307, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:09\"}', '2025-11-24 05:33:09'),
(308, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:10\"}', '2025-11-24 05:33:10'),
(309, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:10\"}', '2025-11-24 05:33:10'),
(310, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:11\"}', '2025-11-24 05:33:11'),
(311, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:11\"}', '2025-11-24 05:33:11'),
(312, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:11\"}', '2025-11-24 05:33:11'),
(313, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:03:11\"}', '2025-11-24 05:33:11'),
(314, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:36\"}', '2025-11-24 05:36:36'),
(315, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:39\"}', '2025-11-24 05:36:39'),
(316, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:41\"}', '2025-11-24 05:36:41'),
(317, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:41\"}', '2025-11-24 05:36:41'),
(318, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:41\"}', '2025-11-24 05:36:41'),
(319, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:41\"}', '2025-11-24 05:36:41'),
(320, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:42\"}', '2025-11-24 05:36:42'),
(321, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:42\"}', '2025-11-24 05:36:42'),
(322, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:42\"}', '2025-11-24 05:36:42'),
(323, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:42\"}', '2025-11-24 05:36:42'),
(324, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:43\"}', '2025-11-24 05:36:43'),
(325, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:06:43\"}', '2025-11-24 05:36:43'),
(326, 37, 'view_access', '2025-11-24', 2, '{\"view_type\":\"current\",\"task_count\":2,\"date_accessed\":\"2025-11-24 11:07:13\"}', '2025-11-24 05:37:13'),
(327, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:10:26\"}', '2025-11-24 05:40:26'),
(328, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:10:43\"}', '2025-11-24 05:40:43'),
(329, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:28\"}', '2025-11-24 05:41:28'),
(330, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:32\"}', '2025-11-24 05:41:32'),
(331, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:33\"}', '2025-11-24 05:41:33'),
(332, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:36\"}', '2025-11-24 05:41:36'),
(333, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:36\"}', '2025-11-24 05:41:36'),
(334, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:11:37\"}', '2025-11-24 05:41:37'),
(335, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:11:49\"}', '2025-11-24 05:41:49'),
(336, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:11:50\"}', '2025-11-24 05:41:50'),
(337, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:11:50\"}', '2025-11-24 05:41:50'),
(338, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:24:11\"}', '2025-11-24 05:54:11'),
(339, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:24:12\"}', '2025-11-24 05:54:12'),
(340, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:15\"}', '2025-11-24 05:55:15'),
(341, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:16\"}', '2025-11-24 05:55:16'),
(342, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:17\"}', '2025-11-24 05:55:17'),
(343, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:18\"}', '2025-11-24 05:55:18'),
(344, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:22\"}', '2025-11-24 05:55:22'),
(345, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:53\"}', '2025-11-24 05:55:53'),
(346, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:54\"}', '2025-11-24 05:55:54'),
(347, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:54\"}', '2025-11-24 05:55:54'),
(348, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:55\"}', '2025-11-24 05:55:55'),
(349, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:25:58\"}', '2025-11-24 05:55:58'),
(350, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:26:00\"}', '2025-11-24 05:56:00'),
(351, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:27:17\"}', '2025-11-24 05:57:17'),
(352, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:27:18\"}', '2025-11-24 05:57:18'),
(353, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:27:18\"}', '2025-11-24 05:57:18'),
(354, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:27:18\"}', '2025-11-24 05:57:18'),
(355, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:27:59\"}', '2025-11-24 05:57:59'),
(356, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:28:02\"}', '2025-11-24 05:58:02'),
(357, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:29:14\"}', '2025-11-24 05:59:14'),
(358, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:29:18\"}', '2025-11-24 05:59:18'),
(359, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:29:21\"}', '2025-11-24 05:59:21'),
(360, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:29:24\"}', '2025-11-24 05:59:24'),
(361, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:27\"}', '2025-11-24 06:11:27'),
(362, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:28\"}', '2025-11-24 06:11:28'),
(363, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:28\"}', '2025-11-24 06:11:28'),
(364, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:50\"}', '2025-11-24 06:11:50'),
(365, 37, 'historical_view_access', '2025-11-23', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:41:53\"}', '2025-11-24 06:11:53'),
(366, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:57\"}', '2025-11-24 06:11:57');
INSERT INTO `daily_planner_audit` (`id`, `user_id`, `action`, `target_date`, `task_count`, `details`, `timestamp`) VALUES
(367, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:41:58\"}', '2025-11-24 06:11:58'),
(368, 37, 'view_access', '2025-11-24', 0, '{\"view_type\":\"current\",\"task_count\":0,\"date_accessed\":\"2025-11-24 11:42:09\"}', '2025-11-24 06:12:09'),
(369, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:42:13\"}', '2025-11-24 06:12:13'),
(370, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:42:15\"}', '2025-11-24 06:12:15'),
(371, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:43:34\"}', '2025-11-24 06:13:34'),
(372, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:44:41\"}', '2025-11-24 06:14:41'),
(373, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:44:43\"}', '2025-11-24 06:14:43'),
(374, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:44:46\"}', '2025-11-24 06:14:46'),
(375, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:44:47\"}', '2025-11-24 06:14:47'),
(376, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:45:01\"}', '2025-11-24 06:15:01'),
(377, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:45:02\"}', '2025-11-24 06:15:02'),
(378, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:57\"}', '2025-11-24 06:22:57'),
(379, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:57\"}', '2025-11-24 06:22:57'),
(380, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:57\"}', '2025-11-24 06:22:57'),
(381, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:58\"}', '2025-11-24 06:22:58'),
(382, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:58\"}', '2025-11-24 06:22:58'),
(383, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:52:58\"}', '2025-11-24 06:22:58'),
(384, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:53:00\"}', '2025-11-24 06:23:00'),
(385, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:53:29\"}', '2025-11-24 06:23:29'),
(386, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:08\"}', '2025-11-24 06:24:08'),
(387, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:13\"}', '2025-11-24 06:24:13'),
(388, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:15\"}', '2025-11-24 06:24:15'),
(389, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:21\"}', '2025-11-24 06:24:21'),
(390, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:23\"}', '2025-11-24 06:24:23'),
(391, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:54:57\"}', '2025-11-24 06:24:57'),
(392, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:55:01\"}', '2025-11-24 06:25:01'),
(393, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 11:55:06\"}', '2025-11-24 06:25:06'),
(394, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:00:26\"}', '2025-11-24 06:30:26'),
(395, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:00:29\"}', '2025-11-24 06:30:29'),
(396, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:00:31\"}', '2025-11-24 06:30:31'),
(397, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:00:32\"}', '2025-11-24 06:30:32'),
(398, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:00:33\"}', '2025-11-24 06:30:33'),
(399, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:03:56\"}', '2025-11-24 06:33:56'),
(400, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:04:06\"}', '2025-11-24 06:34:06'),
(401, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:09\"}', '2025-11-24 06:39:09'),
(402, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:13\"}', '2025-11-24 06:39:13'),
(403, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:14\"}', '2025-11-24 06:39:14'),
(404, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:15\"}', '2025-11-24 06:39:15'),
(405, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:17\"}', '2025-11-24 06:39:17'),
(406, 37, 'view_access', '2025-11-25', 1, '{\"view_type\":\"planning\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:09:18\"}', '2025-11-24 06:39:18'),
(407, 37, 'view_access', '2025-11-24', 1, '{\"view_type\":\"current\",\"task_count\":1,\"date_accessed\":\"2025-11-24 12:18:44\"}', '2025-11-24 06:48:44'),
(408, 37, 'view_access', '2025-11-26', 0, '{\"view_type\":\"planning\",\"task_count\":0,\"date_accessed\":\"2025-11-24 12:18:46\"}', '2025-11-24 06:48:46'),
(409, 37, 'historical_view_access', '2025-11-23', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-24 12:36:43\"}', '2025-11-24 07:06:43'),
(410, 37, 'historical_view_access', '2025-11-23', 0, '{\"view_type\":\"historical\",\"task_count\":0,\"date_accessed\":\"2025-11-24 12:37:32\"}', '2025-11-24 07:07:32'),
(411, 37, 'historical_view_access', '2025-11-22', 4, '{\"view_type\":\"historical\",\"task_count\":4,\"date_accessed\":\"2025-11-24 12:37:34\"}', '2025-11-24 07:07:34');

-- --------------------------------------------------------

--
-- Table structure for table `daily_plans`
--

CREATE TABLE `daily_plans` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `plan_date` date NOT NULL,
  `project_name` varchar(200) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `task_category` varchar(100) DEFAULT NULL,
  `category` enum('planned','unplanned') DEFAULT 'planned',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT '1.00',
  `status` enum('pending','in_progress','completed','blocked','cancelled') DEFAULT 'pending',
  `progress` int DEFAULT '0',
  `actual_hours` decimal(4,2) DEFAULT '0.00',
  `completion_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_followup` tinyint(1) DEFAULT '0',
  `followup_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_plans`
--

INSERT INTO `daily_plans` (`id`, `user_id`, `department_id`, `plan_date`, `project_name`, `title`, `description`, `task_category`, `category`, `priority`, `estimated_hours`, `status`, `progress`, `actual_hours`, `completion_notes`, `created_at`, `updated_at`, `completed_at`, `is_followup`, `followup_id`) VALUES
(1, 1, 1, '2025-10-27', 'ERGON Development', 'Review Project Documentation', 'Go through all project docs and update requirements', 'Documentation', 'planned', 'high', 2.00, 'in_progress', 60, 0.00, NULL, '2025-10-26 21:44:23', '2025-10-26 21:44:23', NULL, 0, NULL),
(2, 1, 1, '2025-10-27', 'Client Portal', 'Bug Fixing Session', 'Fix reported issues in client portal', 'Bug Fixing', 'planned', 'urgent', 1.50, 'pending', 0, 0.00, NULL, '2025-10-26 21:44:23', '2025-10-26 21:44:23', NULL, 0, NULL),
(3, 1, 1, '2025-10-27', 'ERGON Development', 'Code Review', 'Review pull requests from team members', 'Code Review', 'planned', 'medium', 1.00, 'completed', 100, 0.00, NULL, '2025-10-26 21:44:23', '2025-10-26 21:44:23', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `original_task_id` int DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `planned_start_time` time DEFAULT NULL,
  `planned_duration` int DEFAULT '60',
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(50) DEFAULT 'not_started',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `start_time` timestamp NULL DEFAULT NULL,
  `pause_time` timestamp NULL DEFAULT NULL,
  `pause_start_time` timestamp NULL DEFAULT NULL,
  `resume_time` timestamp NULL DEFAULT NULL,
  `completion_time` timestamp NULL DEFAULT NULL,
  `active_seconds` int DEFAULT '0',
  `pause_duration` int DEFAULT '0',
  `completed_percentage` int DEFAULT '0',
  `postponed_from_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_pause_duration` int DEFAULT '0',
  `sla_end_time` timestamp NULL DEFAULT NULL,
  `late_duration` int DEFAULT '0',
  `postponed_to_date` date DEFAULT NULL,
  `source_field` varchar(50) DEFAULT NULL,
  `rollover_source_date` date DEFAULT NULL,
  `rollover_timestamp` timestamp NULL DEFAULT NULL,
  `remaining_sla_seconds` int DEFAULT '0',
  `overdue_start_time` timestamp NULL DEFAULT NULL,
  `start_ts_ms` bigint DEFAULT NULL,
  `sla_end_ts_ms` bigint DEFAULT NULL,
  `pause_start_ts_ms` bigint DEFAULT NULL,
  `paused_accum_ms` bigint DEFAULT '0',
  `overdue_start_ts_ms` bigint DEFAULT NULL,
  `sla_duration_seconds` int DEFAULT '900',
  `progress_percent` int DEFAULT '0',
  `total_pause_duration_ms` bigint DEFAULT '0',
  `sla_time_spent_ms` bigint DEFAULT '0',
  `overdue_time_spent_ms` bigint DEFAULT '0',
  `total_used_time_ms` bigint DEFAULT '0',
  `pause_end_ts_ms` bigint DEFAULT NULL,
  `used_time_ms` bigint DEFAULT '0',
  `remaining_sla_time` int DEFAULT '0',
  `time_used` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_tasks`
--

INSERT INTO `daily_tasks` (`id`, `user_id`, `task_id`, `original_task_id`, `scheduled_date`, `title`, `description`, `planned_start_time`, `planned_duration`, `priority`, `status`, `created_at`, `start_time`, `pause_time`, `pause_start_time`, `resume_time`, `completion_time`, `active_seconds`, `pause_duration`, `completed_percentage`, `postponed_from_date`, `updated_at`, `total_pause_duration`, `sla_end_time`, `late_duration`, `postponed_to_date`, `source_field`, `rollover_source_date`, `rollover_timestamp`, `remaining_sla_seconds`, `overdue_start_time`, `start_ts_ms`, `sla_end_ts_ms`, `pause_start_ts_ms`, `paused_accum_ms`, `overdue_start_ts_ms`, `sla_duration_seconds`, `progress_percent`, `total_pause_duration_ms`, `sla_time_spent_ms`, `overdue_time_spent_ms`, `total_used_time_ms`, `pause_end_ts_ms`, `used_time_ms`, `remaining_sla_time`, `time_used`) VALUES
(1960, 48, 105, NULL, '2025-11-19', '[Self] Followup And task interconnection test 2', 'Followup And task interconnection test 2', NULL, 1440, 'medium', 'not_started', '2025-11-21 04:54:50', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-21 04:54:50', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2666, 37, 199, 199, '2025-11-24', 'Test Task - Planner 1-Admin Myself (Nov24)', 'Test Task - Planner 1-Admin Myself (Nov24)', NULL, 60, 'medium', 'completed', '2025-11-24 07:52:34', '2025-11-24 08:24:02', '2025-11-24 09:40:09', NULL, '2025-11-24 09:41:46', '2025-11-24 09:42:09', 1716, 2971, 100, NULL, '2025-11-24 09:42:09', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2668, 1, 201, 201, '2025-11-24', 'Test Task - 13:41:46', 'This is a test task created by the fix script', NULL, 15, 'medium', 'not_started', '2025-11-24 08:11:46', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-24 08:11:46', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2670, 37, 202, 202, '2025-11-25', 'Test Task - Planner 3-Admin Myself (Nov25)', 'Test Task - Planner 3-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-24 08:26:42', '2025-11-25 08:53:41', NULL, NULL, '2025-11-25 11:34:37', NULL, 1891, 4700, 40, NULL, '2025-11-27 11:08:05', 3065, '2026-03-09 19:19:04', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 1764051740774, 900, 0, 0, 0, 0, 0, NULL, 0, 9013467, 928),
(2671, 1, 203, 203, '2025-11-27', 'Future Task - Created Today', 'This task is created today but planned for future date', NULL, 60, 'medium', 'not_started', '2025-11-24 08:33:57', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-24 08:33:57', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2673, 37, 200, 200, '2025-11-25', 'Test Task - Planner 2-Admin Myself (Nov24)', 'Test Task - Planner 2-Admin Myself (Nov24)', NULL, 60, 'medium', 'assigned', '2025-11-24 08:35:46', '2025-11-25 06:59:00', NULL, '2025-11-25 10:27:17', '2025-11-25 10:27:14', NULL, 3808, 4518, 16, '2025-11-24', '2025-11-27 11:08:24', 0, '2026-03-09 16:29:00', 0, NULL, 'postponed', NULL, NULL, 0, NULL, 1764053940469, 1764054840469, 1764058295071, 0, 1764051740893, 900, 0, 389857, 900000, 6554174, 7454174, NULL, 0, 0, 0),
(2676, 37, 204, 204, '2025-11-25', 'Test Task - Planner 4-Admin Myself (Nov24)', 'Test Task - Planner 4-Admin Myself (Nov24)', NULL, 60, 'medium', 'postponed', '2025-11-24 09:09:13', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-25', '2025-11-25 06:22:20', 0, NULL, 0, '2025-11-27', 'postponed', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 1764051740988, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2682, 37, 207, 207, '2025-11-24', 'Test Task - Planner 6 -Admin Myself (Nov24)', 'Test Task - Planner 6 -Admin Myself (Nov24)', NULL, 60, 'medium', 'rolled_over', '2025-11-24 11:53:12', '2025-11-24 11:53:20', NULL, '2025-11-24 13:35:53', '2025-11-24 13:35:50', NULL, 914, 5239, 0, NULL, '2025-11-25 03:54:59', 0, '2026-03-08 21:23:20', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2702, 37, 208, 208, '2025-11-25', 'Test Task - Planner 7-Admin Myself (Nov25)', 'Test Task - Planner 7-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-25 03:56:04', '2025-11-25 03:56:21', NULL, '2025-11-25 10:43:46', '2025-11-25 10:43:32', NULL, 34, 10931, 10, NULL, '2025-11-27 12:07:32', 13480, '2025-11-25 10:43:32', 0, NULL, 'planned_date', NULL, NULL, 0, '2025-11-25 10:43:32', NULL, NULL, 1764053932711, 0, 1764051740254, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 14),
(2713, 37, 215, 215, '2025-11-25', 'Test - task & Planner 9 Admin Myself (Nov 25)', 'Test - task & Planner 9 Admin Myself (Nov 25)', NULL, 60, 'medium', 'rolled_over', '2025-11-25 07:24:47', '2025-11-25 07:24:52', NULL, '2025-11-25 09:13:10', '2025-11-25 08:53:39', NULL, 1197, 126, 15, NULL, '2025-11-26 09:10:46', 0, '2026-03-09 16:54:52', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, 1764055492243, 1764056392243, 1764057891708, 0, 1764055645711, 900, 0, 132958, 77439, 50559, 127998, NULL, 0, 0, 0),
(2714, 37, 216, 216, '2025-11-25', 'Test Task - Planner 10-Admin Myself (Nov25)', 'Test Task - Planner 10-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-25 08:09:17', '2025-11-25 08:09:26', NULL, '2025-11-25 11:30:34', '2025-11-25 11:20:10', NULL, 715, 0, 12, NULL, '2025-11-26 09:10:46', 11353, '2025-11-25 11:20:10', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, 1764058166733, 1764059066733, 1764058257231, 0, NULL, 900, 0, 0, 90489, 0, 90489, NULL, 0, 900, 624),
(2716, 1, NULL, NULL, '2025-11-25', 'Workflow Test Task', 'Testing complete workflow', NULL, 60, 'high', 'in_progress', '2025-11-25 10:46:28', '2025-11-25 10:46:28', NULL, NULL, '2025-11-25 10:46:33', NULL, 2, 0, 50, NULL, '2025-11-25 10:46:34', 3, '2025-11-25 11:01:33', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 2),
(2718, 37, 221, 221, '2025-11-25', 'Test Task - Planner 11-Admin Myself (Nov25)', 'Test Task - Planner 11-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-25 11:11:17', '2025-11-25 11:11:27', NULL, '2025-11-25 11:21:19', '2025-11-25 11:21:11', NULL, 446, 0, 0, NULL, '2025-11-26 09:10:46', 146, '2025-11-25 11:36:11', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 446),
(2719, 37, 222, 222, '2025-11-25', 'Test Task - Planner 12-Admin Myself (Nov25)', 'Test Task - Planner 12-Admin Myself (Nov25)', NULL, 60, 'medium', 'postponed', '2025-11-25 11:35:25', '2025-11-25 11:35:29', NULL, '2025-11-25 11:36:49', NULL, NULL, 80, 0, 0, '2025-11-25', '2025-11-25 11:41:56', 0, '2026-03-09 21:05:29', 0, '2025-11-27', 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 80),
(2720, 37, 222, 222, '2025-11-27', 'Test Task - Planner 12-Admin Myself (Nov25)', 'Test Task - Planner 12-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-25 11:41:56', NULL, NULL, NULL, NULL, NULL, 80, 0, 0, '2025-11-25', '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'postponed', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2722, 37, 224, 224, '2025-11-25', 'Test Task - Planner 13-Admin Myself (Nov25)', 'Test Task - Planner 13-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-25 11:59:18', '2025-11-25 11:59:22', NULL, NULL, '2025-11-25 11:59:48', NULL, 4, 0, 0, NULL, '2025-11-26 09:10:46', 22, '2025-11-25 12:14:48', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 4),
(2723, 37, 200, 200, '2025-11-26', 'Test Task - Planner 2-Admin Myself (Nov24)', 'Test Task - Planner 2-Admin Myself (Nov24)', NULL, 60, 'medium', 'assigned', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 3808, 4518, 16, NULL, '2025-11-27 11:08:24', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2724, 37, 202, 202, '2025-11-26', 'Test Task - Planner 3-Admin Myself (Nov25)', 'Test Task - Planner 3-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 1891, 4700, 40, NULL, '2025-11-27 11:08:05', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2725, 37, 208, 208, '2025-11-26', 'Test Task - Planner 7-Admin Myself (Nov25)', 'Test Task - Planner 7-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 34, 10931, 10, NULL, '2025-11-27 12:07:32', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2727, 37, 215, 215, '2025-11-26', 'Test - task & Planner 9 Admin Myself (Nov 25)', 'Test - task & Planner 9 Admin Myself (Nov 25)', NULL, 60, 'medium', 'rolled_over', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 1197, 126, 15, NULL, '2025-11-27 10:55:28', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2728, 37, 216, 216, '2025-11-26', 'Test Task - Planner 10-Admin Myself (Nov25)', 'Test Task - Planner 10-Admin Myself (Nov25)', NULL, 60, 'medium', 'postponed', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 715, 0, 12, '2025-11-26', '2025-11-26 09:11:21', 0, NULL, 0, '2025-11-27', 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2729, 37, 221, 221, '2025-11-26', 'Test Task - Planner 11-Admin Myself (Nov25)', 'Test Task - Planner 11-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 446, 0, 0, NULL, '2025-11-27 10:55:28', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2730, 37, 224, 224, '2025-11-26', 'Test Task - Planner 13-Admin Myself (Nov25)', 'Test Task - Planner 13-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-26 09:10:46', NULL, NULL, NULL, NULL, NULL, 4, 0, 0, NULL, '2025-11-27 10:55:28', 0, NULL, 0, NULL, 'rollover', '2025-11-25', '2025-11-26 09:10:46', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2731, 37, 216, 216, '2025-11-27', 'Test Task - Planner 10-Admin Myself (Nov25)', 'Test Task - Planner 10-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-26 09:11:21', NULL, NULL, NULL, NULL, NULL, 715, 0, 12, '2025-11-26', '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'postponed', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2732, 37, 200, 200, '2025-11-27', 'Test Task - Planner 2-Admin Myself (Nov24)', 'Test Task - Planner 2-Admin Myself (Nov24)', NULL, 60, 'medium', 'assigned', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 3808, 4518, 16, NULL, '2025-11-27 11:08:24', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2733, 37, 202, 202, '2025-11-27', 'Test Task - Planner 3-Admin Myself (Nov25)', 'Test Task - Planner 3-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 1891, 4700, 40, NULL, '2025-11-27 11:08:05', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2734, 37, 208, 208, '2025-11-27', 'Test Task - Planner 7-Admin Myself (Nov25)', 'Test Task - Planner 7-Admin Myself (Nov25)', NULL, 60, 'medium', 'assigned', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 34, 10931, 10, NULL, '2025-11-27 12:07:32', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2736, 37, 215, 215, '2025-11-27', 'Test - task & Planner 9 Admin Myself (Nov 25)', 'Test - task & Planner 9 Admin Myself (Nov 25)', NULL, 60, 'medium', 'rolled_over', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 1197, 126, 15, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2737, 37, 221, 221, '2025-11-27', 'Test Task - Planner 11-Admin Myself (Nov25)', 'Test Task - Planner 11-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 446, 0, 0, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2738, 37, 224, 224, '2025-11-27', 'Test Task - Planner 13-Admin Myself (Nov25)', 'Test Task - Planner 13-Admin Myself (Nov25)', NULL, 60, 'medium', 'rolled_over', '2025-11-27 10:55:28', NULL, NULL, NULL, NULL, NULL, 4, 0, 0, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-26', '2025-11-27 10:55:28', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2739, 49, 227, 227, '2025-11-28', 'Finish Q1 Sales Report', 'Compile sales data, revenue charts, and insights for board meeting.\r\nInclude expenses vs revenue comparison.', NULL, 60, 'medium', 'not_started', '2025-11-28 12:43:52', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-28 12:43:52', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2740, 49, 226, 226, '2025-11-28', 'Finish Q1 Sales Report', 'Compile sales data, revenue charts, and insights for board meeting.\r\nInclude expenses vs revenue comparison.', NULL, 60, 'medium', 'not_started', '2025-11-28 12:43:52', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-28 12:43:52', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2741, 37, 215, 215, '2025-11-28', 'Test - task & Planner 9 Admin Myself (Nov 25)', 'Test - task & Planner 9 Admin Myself (Nov 25)', NULL, 60, 'medium', 'on_break', '2025-11-28 14:13:54', NULL, NULL, NULL, NULL, NULL, 1197, 126, 15, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-27', '2025-11-28 14:13:54', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2742, 37, 216, 216, '2025-11-28', 'Test Task - Planner 10-Admin Myself (Nov25)', 'Test Task - Planner 10-Admin Myself (Nov25)', NULL, 60, 'medium', 'not_started', '2025-11-28 14:13:54', NULL, NULL, NULL, NULL, NULL, 715, 0, 12, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-27', '2025-11-28 14:13:54', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2743, 37, 221, 221, '2025-11-28', 'Test Task - Planner 11-Admin Myself (Nov25)', 'Test Task - Planner 11-Admin Myself (Nov25)', NULL, 60, 'medium', 'on_break', '2025-11-28 14:13:54', NULL, NULL, NULL, NULL, NULL, 446, 0, 0, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-27', '2025-11-28 14:13:54', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2744, 37, 222, 222, '2025-11-28', 'Test Task - Planner 12-Admin Myself (Nov25)', 'Test Task - Planner 12-Admin Myself (Nov25)', NULL, 60, 'medium', 'not_started', '2025-11-28 14:13:54', NULL, NULL, NULL, NULL, NULL, 80, 0, 0, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-27', '2025-11-28 14:13:54', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(2745, 37, 224, 224, '2025-11-28', 'Test Task - Planner 13-Admin Myself (Nov25)', 'Test Task - Planner 13-Admin Myself (Nov25)', NULL, 60, 'medium', 'in_progress', '2025-11-28 14:13:54', NULL, NULL, NULL, NULL, NULL, 4, 0, 0, NULL, '2025-11-28 14:13:54', 0, NULL, 0, NULL, 'rollover', '2025-11-27', '2025-11-28 14:13:54', 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_task_history`
--

CREATE TABLE `daily_task_history` (
  `id` int NOT NULL,
  `daily_task_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text,
  `new_value` text,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_task_history`
--

INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1, 1218, 'postponed', '2025-11-19', '2025-11-20', 'Task postponed to 2025-11-20', 1, '2025-11-19 04:59:42'),
(2, 1210, 'postponed', '2025-11-19', '2025-11-20', 'Task postponed to 2025-11-20', 1, '2025-11-19 08:24:24'),
(3, 1270, 'postponed', '2025-11-19', '2025-11-20', 'Task postponed to 2025-11-20', 37, '2025-11-19 08:25:07'),
(4, 1276, 'postponed', '2025-11-19', '2025-11-20', 'Task postponed to 2025-11-20', 37, '2025-11-19 10:35:17'),
(5, 1460, 'postponed', '2025-11-19', '2025-11-20', 'Task postponed to 2025-11-20', 48, '2025-11-19 14:05:55'),
(6, 1862, 'postponed', '2025-11-20', '2025-11-23', 'Task postponed to 2025-11-23', 48, '2025-11-20 07:30:03'),
(7, 1868, 'postponed', '2025-11-20', '2025-11-23', 'Task postponed to 2025-11-23', 48, '2025-11-20 08:13:39'),
(8, 1871, 'postponed', '2025-11-20', '2025-11-23', 'Task postponed to 2025-11-23', 48, '2025-11-20 08:29:29'),
(9, 1883, 'postponed', '2025-11-20', '2025-11-23', 'Task postponed to 2025-11-23', 48, '2025-11-20 08:45:15'),
(10, 1930, 'rolled_over', '2025-11-20', '2025-11-21', '🔄 Rolled over from: 2025-11-20', 1, '2025-11-21 04:48:40'),
(11, 1931, 'rolled_over', '2025-11-20', '2025-11-21', '🔄 Rolled over from: 2025-11-20', 48, '2025-11-21 04:48:40'),
(12, 1932, 'rolled_over', '2025-11-20', '2025-11-21', '🔄 Rolled over from: 2025-11-20', 48, '2025-11-21 04:48:40'),
(13, 1933, 'rolled_over', '2025-11-20', '2025-11-21', '🔄 Rolled over from: 2025-11-20', 49, '2025-11-21 04:48:40'),
(14, 1981, 'postponed', '2025-11-21', '2025-11-22', 'Task postponed to 2025-11-22', 48, '2025-11-21 05:18:11'),
(15, 2009, 'status_changed', 'in_progress', 'completed', '', 37, '2025-11-21 05:55:47'),
(16, 2009, 'progress_updated', '0%', '100%', '', 37, '2025-11-21 05:55:47'),
(17, 2009, 'postponed', '2025-11-21', '2025-11-22', 'Task postponed to 2025-11-22', 37, '2025-11-21 06:04:51'),
(18, 1859, 'progress_updated', '0%', '68%', '', 48, '2025-11-21 08:33:54'),
(19, 1859, 'progress_updated', '68%', '71%', '', 48, '2025-11-21 08:34:37'),
(20, 1859, 'progress_updated', '71%', '67%', '', 48, '2025-11-21 09:01:24'),
(21, 1859, 'progress_updated', '67%', '50%', '', 48, '2025-11-21 09:01:57'),
(22, 1931, 'status_changed', 'in_progress', 'completed', '', 48, '2025-11-21 09:03:53'),
(23, 1931, 'progress_updated', '0%', '100%', '', 48, '2025-11-21 09:03:53'),
(24, 1859, 'progress_updated', '50%', '26%', '', 48, '2025-11-21 09:04:26'),
(25, 1859, 'status_changed', 'in_progress', 'completed', '', 48, '2025-11-21 09:04:41'),
(26, 1859, 'progress_updated', '26%', '100%', '', 48, '2025-11-21 09:04:41'),
(27, 1932, 'status_changed', 'in_progress', 'completed', '', 48, '2025-11-21 09:07:53'),
(28, 1932, 'progress_updated', '50%', '100%', '', 48, '2025-11-21 09:07:53'),
(29, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:22:02', 37, '2025-11-21 09:22:02'),
(30, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:22:07', 37, '2025-11-21 09:22:07'),
(31, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:30:10', 37, '2025-11-21 09:30:10'),
(32, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:30:14', 37, '2025-11-21 09:30:14'),
(33, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:30:38', 37, '2025-11-21 09:30:38'),
(34, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:34:10', 37, '2025-11-21 09:34:10'),
(35, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:42:38', 37, '2025-11-21 09:42:38'),
(36, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:42:50', 37, '2025-11-21 09:42:50'),
(37, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:43:34', 37, '2025-11-21 09:43:34'),
(38, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:43:45', 37, '2025-11-21 09:43:45'),
(39, 1992, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 09:53:31', 37, '2025-11-21 09:53:31'),
(40, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 09:53:37', 37, '2025-11-21 09:53:37'),
(41, 2141, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-21 09:56:38', 37, '2025-11-21 09:56:38'),
(42, 2141, 'progress_updated', '0%', '5%', '', 37, '2025-11-21 10:02:30'),
(43, 2141, 'progress_updated', '5%', '7%', '', 37, '2025-11-21 10:03:22'),
(44, 2141, 'status_changed', 'in_progress', 'completed', '', 37, '2025-11-21 10:05:54'),
(45, 2141, 'progress_updated', '7%', '100%', '', 37, '2025-11-21 10:05:54'),
(46, 1992, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-21 10:50:06'),
(47, 1992, 'progress_updated', '0%', '10%', '', 37, '2025-11-21 10:50:06'),
(48, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 10:52:56', 37, '2025-11-21 10:52:56'),
(49, 1992, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-21 10:53:00'),
(50, 1992, 'progress_updated', '10%', '3%', '', 37, '2025-11-21 10:53:00'),
(51, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 10:56:25', 37, '2025-11-21 10:56:25'),
(52, 1992, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-21 10:56:30'),
(53, 1992, 'progress_updated', '3%', '4%', '', 37, '2025-11-21 10:56:30'),
(54, 1992, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 10:59:25', 37, '2025-11-21 10:59:25'),
(55, 1992, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-21 10:59:31'),
(56, 1992, 'progress_updated', '4%', '8%', '', 37, '2025-11-21 10:59:31'),
(57, 1992, 'progress_updated', '8%', '12%', '', 37, '2025-11-21 10:59:56'),
(58, 1992, 'progress_updated', '12%', '15%', '', 37, '2025-11-21 11:01:18'),
(59, 1992, 'progress_updated', '15%', '19%', '', 37, '2025-11-21 11:02:33'),
(60, 1992, 'progress_updated', '19%', '10%', '', 37, '2025-11-21 11:05:14'),
(61, 1992, 'progress_updated', '10%', '15%', '', 37, '2025-11-21 11:05:51'),
(62, 1992, 'progress_updated', '15%', '18%', '', 37, '2025-11-21 11:07:38'),
(63, 1992, 'progress_updated', '18%', '11%', '', 37, '2025-11-21 11:10:38'),
(64, 1992, 'progress_updated', '11%', '15%', '', 37, '2025-11-21 11:14:49'),
(65, 2238, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-21 11:17:09', 37, '2025-11-21 11:17:09'),
(66, 2238, 'progress_updated', '0%', '2%', '', 37, '2025-11-21 11:17:14'),
(67, 2238, 'progress_updated', '2%', '7%', '', 37, '2025-11-21 11:20:53'),
(68, 2238, 'progress_updated', '7%', '10%', '', 37, '2025-11-21 11:28:38'),
(69, 2238, 'progress_updated', '10%', '13%', '', 37, '2025-11-21 11:30:52'),
(70, 1992, 'progress_updated', '15%', '19%', '', 37, '2025-11-21 11:30:57'),
(71, 1992, 'progress_updated', '19%', '14%', '', 37, '2025-11-21 11:31:06'),
(72, 1992, 'progress_updated', '14%', '19%', '', 37, '2025-11-21 11:31:17'),
(73, 1992, 'progress_updated', '19%', '22%', '', 37, '2025-11-21 11:39:50'),
(74, 1992, 'progress_updated', '22%', '30%', '', 37, '2025-11-21 11:39:58'),
(75, 1992, 'progress_updated', '30%', '99%', '', 37, '2025-11-21 11:40:22'),
(76, 1992, 'status_changed', 'in_progress', 'completed', '', 37, '2025-11-21 11:40:26'),
(77, 1992, 'progress_updated', '99%', '100%', '', 37, '2025-11-21 11:40:26'),
(78, 2238, 'progress_updated', '13%', '15%', '', 37, '2025-11-21 11:40:43'),
(79, 2238, 'progress_updated', '15%', '35%', '', 37, '2025-11-21 11:40:49'),
(80, 2238, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 11:41:10', 37, '2025-11-21 11:41:10'),
(81, 2275, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-21 11:42:37', 37, '2025-11-21 11:42:37'),
(82, 2275, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 11:43:00', 37, '2025-11-21 11:43:00'),
(83, 2275, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 11:43:01', 37, '2025-11-21 11:43:01'),
(84, 2275, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 11:49:53', 37, '2025-11-21 11:49:53'),
(85, 2275, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 11:49:57', 37, '2025-11-21 11:49:57'),
(86, 2275, 'progress_updated', '0%', '10%', '', 37, '2025-11-21 11:50:06'),
(87, 2275, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 11:50:13', 37, '2025-11-21 11:50:13'),
(88, 2275, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 11:51:25', 37, '2025-11-21 11:51:25'),
(89, 2238, 'postponed', '2025-11-21', '2025-11-22', 'Task postponed to 2025-11-22', 37, '2025-11-21 11:57:29'),
(90, 2275, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 11:58:18', 37, '2025-11-21 11:58:18'),
(91, 2275, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 11:58:19', 37, '2025-11-21 11:58:19'),
(92, 2024, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-21 12:38:15', 48, '2025-11-21 12:38:15'),
(93, 2024, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 12:38:17', 48, '2025-11-21 12:38:17'),
(94, 2024, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-21 12:38:18', 48, '2025-11-21 12:38:18'),
(95, 2307, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-21 12:39:21', 48, '2025-11-21 12:39:21'),
(96, 2307, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-21 12:39:22', 48, '2025-11-21 12:39:22'),
(97, 2309, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 1, '2025-11-22 08:00:46'),
(98, 2310, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 49, '2025-11-22 08:00:46'),
(99, 2311, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 49, '2025-11-22 08:00:46'),
(100, 2312, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 48, '2025-11-22 08:00:46'),
(101, 2313, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 48, '2025-11-22 08:00:46'),
(102, 2314, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 37, '2025-11-22 08:00:46'),
(103, 2315, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 37, '2025-11-22 08:00:46'),
(104, 2316, 'rolled_over', '2025-11-21', '2025-11-22', '🔄 Rolled over from: 2025-11-21', 37, '2025-11-22 08:00:46'),
(105, 2314, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-22 08:00:54', 37, '2025-11-22 08:00:54'),
(106, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 08:01:22', 37, '2025-11-22 08:01:22'),
(107, 2314, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 08:03:13', 37, '2025-11-22 08:03:13'),
(108, 2326, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-22 08:04:00', 37, '2025-11-22 08:04:00'),
(109, 2326, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 08:04:05', 37, '2025-11-22 08:04:05'),
(110, 2312, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-22 09:18:44', 48, '2025-11-22 09:18:44'),
(111, 2312, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 09:18:49', 48, '2025-11-22 09:18:49'),
(112, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:28:31', 37, '2025-11-22 12:28:31'),
(113, 2314, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 12:28:32', 37, '2025-11-22 12:28:32'),
(114, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:28:34', 37, '2025-11-22 12:28:34'),
(115, 2314, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 12:28:37', 37, '2025-11-22 12:28:37'),
(116, 2316, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-22 12:28:54', 37, '2025-11-22 12:28:54'),
(117, 2316, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:29:05', 37, '2025-11-22 12:29:05'),
(118, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:29:07', 37, '2025-11-22 12:29:07'),
(119, 2314, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 12:35:31', 37, '2025-11-22 12:35:31'),
(120, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:35:36', 37, '2025-11-22 12:35:36'),
(121, 2315, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-22 12:35:45', 37, '2025-11-22 12:35:45'),
(122, 2315, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 12:35:55', 37, '2025-11-22 12:35:55'),
(123, 2316, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 13:04:59', 37, '2025-11-22 13:04:59'),
(124, 2316, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 13:05:04', 37, '2025-11-22 13:05:04'),
(125, 2314, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-22 13:05:08'),
(126, 2314, 'progress_updated', '35%', '66%', '', 37, '2025-11-22 13:05:08'),
(127, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 13:05:14', 37, '2025-11-22 13:05:14'),
(128, 2314, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-22 13:05:19', 37, '2025-11-22 13:05:19'),
(129, 2314, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-22 13:05:20', 37, '2025-11-22 13:05:20'),
(130, 2628, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 11:25:19', 37, '2025-11-24 05:55:19'),
(131, 2628, 'progress_updated', '0%', '14%', '', 37, '2025-11-24 05:55:31'),
(132, 2628, 'postponed', '2025-11-24', '2025-11-25', 'Task postponed to 2025-11-25', 37, '2025-11-24 05:55:50'),
(133, 2629, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 12:03:57', 37, '2025-11-24 06:33:57'),
(134, 2629, 'progress_updated', '0%', '3%', '', 37, '2025-11-24 06:34:02'),
(135, 2629, 'progress_updated', '3%', '7%', '', 37, '2025-11-24 06:34:11'),
(136, 1194, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(137, 1195, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(138, 1196, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(139, 1197, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(140, 1199, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(141, 1200, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(142, 1201, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(143, 1202, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(144, 1203, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(145, 1204, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(146, 1205, 'rollover_detected', '2025-11-18', '2025-11-24', 'Task detected for rollover from 2025-11-18', 37, '2025-11-24 07:06:28'),
(147, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(148, 1272, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(149, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(150, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(151, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(152, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(153, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:28'),
(154, 2275, 'rollover_detected', '2025-11-21', '2025-11-24', 'Task detected for rollover from 2025-11-21', 37, '2025-11-24 07:06:28'),
(155, 2290, 'rollover_detected', '2025-11-21', '2025-11-24', 'Task detected for rollover from 2025-11-21', 37, '2025-11-24 07:06:28'),
(156, 2321, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(157, 2326, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(158, 2389, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(159, 2616, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(160, 2314, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(161, 2315, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(162, 2316, 'rollover_detected', '2025-11-22', '2025-11-24', 'Task detected for rollover from 2025-11-22', 37, '2025-11-24 07:06:28'),
(163, 2630, 'rollover', '1194', '2630', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(164, 2631, 'rollover', '1195', '2631', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(165, 2632, 'rollover', '1196', '2632', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(166, 2633, 'rollover', '1197', '2633', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(167, 2634, 'rollover', '1199', '2634', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(168, 2635, 'rollover', '1200', '2635', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(169, 2636, 'rollover', '1201', '2636', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(170, 2637, 'rollover', '1202', '2637', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(171, 2638, 'rollover', '1203', '2638', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:28'),
(172, 2639, 'rollover', '1204', '2639', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:29'),
(173, 2640, 'rollover', '1205', '2640', '🔄 Rolled over from: 2025-11-18', 37, '2025-11-24 07:06:29'),
(174, 2641, 'rollover', '1272', '2641', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-24 07:06:29'),
(175, 2642, 'rollover', '2275', '2642', '🔄 Rolled over from: 2025-11-21', 37, '2025-11-24 07:06:29'),
(176, 2643, 'rollover', '2290', '2643', '🔄 Rolled over from: 2025-11-21', 37, '2025-11-24 07:06:29'),
(177, 2644, 'rollover', '2321', '2644', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-24 07:06:29'),
(178, 2645, 'rollover', '2326', '2645', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-24 07:06:29'),
(179, 2646, 'rollover', '2389', '2646', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-24 07:06:29'),
(180, 2647, 'rollover', '2616', '2647', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-24 07:06:29'),
(181, 2648, 'rollover', '2314', '2648', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-24 07:06:29'),
(182, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(183, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(184, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(185, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(186, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(187, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:06:36'),
(188, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(189, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(190, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(191, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(192, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(193, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:11'),
(194, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(195, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(196, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(197, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(198, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(199, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:07:29'),
(200, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(201, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(202, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(203, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(204, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(205, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:08:23'),
(206, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(207, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(208, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(209, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(210, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(211, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:22'),
(212, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(213, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(214, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(215, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(216, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(217, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:24'),
(218, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(219, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(220, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(221, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(222, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(223, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:26'),
(224, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(225, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(226, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(227, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(228, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(229, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:09:38'),
(230, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(231, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(232, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(233, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(234, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(235, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:26'),
(236, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(237, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(238, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(239, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(240, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(241, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:27'),
(242, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(243, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(244, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(245, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(246, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(247, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:28'),
(248, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(249, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(250, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(251, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(252, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(253, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:29'),
(254, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(255, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(256, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(257, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(258, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(259, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(260, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(261, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(262, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(263, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(264, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(265, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:30'),
(266, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(267, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(268, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(269, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(270, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(271, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:24:31'),
(272, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(273, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(274, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(275, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(276, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(277, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:29'),
(278, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(279, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(280, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(281, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(282, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(283, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:25:32'),
(284, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(285, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(286, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(287, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(288, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(289, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:51'),
(290, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(291, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(292, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(293, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(294, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(295, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:35:53'),
(296, 2649, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:47'),
(297, 2650, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:47'),
(298, 2651, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:48'),
(299, 2652, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:48'),
(300, 2653, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:48'),
(301, 2654, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:48'),
(302, 2655, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:38:48'),
(303, 2656, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:39:38'),
(304, 2657, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:39:38'),
(305, 2658, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:39:38'),
(306, 2663, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:41:07'),
(307, 2664, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:41:07'),
(308, 2665, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 1, '2025-11-24 07:41:07'),
(309, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(310, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(311, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(312, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(313, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(314, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:39'),
(315, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(316, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(317, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(318, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(319, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(320, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:40'),
(321, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(322, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(323, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(324, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(325, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(326, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:41'),
(327, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(328, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(329, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(330, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(331, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(332, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:42'),
(333, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(334, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(335, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(336, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(337, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(338, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:55'),
(339, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(340, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(341, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(342, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(343, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(344, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:57'),
(345, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(346, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(347, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(348, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(349, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(350, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(351, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(352, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(353, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(354, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(355, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(356, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:41:58'),
(357, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(358, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(359, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(360, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(361, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(362, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:52'),
(363, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(364, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(365, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(366, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(367, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(368, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:50:53'),
(369, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:17'),
(370, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:17'),
(371, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:18'),
(372, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:18'),
(373, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:18'),
(374, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:51:18'),
(375, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(376, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(377, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(378, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(379, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(380, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:34'),
(381, 2666, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 07:52:34'),
(382, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(383, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(384, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(385, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(386, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(387, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:42'),
(388, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(389, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(390, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(391, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(392, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(393, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:52:43'),
(394, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(395, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(396, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(397, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(398, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(399, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:10'),
(400, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(401, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(402, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(403, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(404, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(405, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(406, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(407, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(408, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(409, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(410, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(411, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:11'),
(412, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12'),
(413, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12'),
(414, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12'),
(415, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12'),
(416, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(417, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:12'),
(418, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(419, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(420, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(421, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(422, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(423, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:13'),
(424, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(425, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(426, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(427, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(428, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(429, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:57'),
(430, 2667, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 07:57:57'),
(431, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(432, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(433, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(434, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(435, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(436, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:57:58'),
(437, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(438, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(439, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(440, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(441, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(442, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 07:58:00'),
(443, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(444, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(445, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(446, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(447, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(448, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:30'),
(449, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(450, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(451, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(452, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(453, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(454, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:09:31'),
(455, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(456, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(457, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(458, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(459, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(460, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:25'),
(461, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(462, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(463, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(464, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(465, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(466, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(467, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(468, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(469, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(470, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(471, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(472, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(473, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(474, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(475, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(476, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(477, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(478, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(479, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(480, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(481, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(482, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(483, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(484, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:26'),
(485, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(486, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(487, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(488, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(489, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(490, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:28'),
(491, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(492, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(493, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(494, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(495, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(496, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:57'),
(497, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(498, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(499, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(500, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(501, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(502, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:12:59'),
(503, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(504, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(505, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(506, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(507, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(508, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:31'),
(509, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(510, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(511, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(512, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(513, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(514, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:13:33'),
(515, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(516, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(517, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(518, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(519, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(520, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:28'),
(521, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(522, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(523, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(524, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(525, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(526, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:46'),
(527, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(528, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(529, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(530, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(531, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(532, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:23:55'),
(533, 2666, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 13:54:02', 37, '2025-11-24 08:24:02'),
(534, 2666, 'time_start', '0', NULL, 'Action: start at 2025-11-24 13:54:02. Duration: 0s.', 37, '2025-11-24 08:24:02'),
(535, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:57'),
(536, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:57'),
(537, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:57'),
(538, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:58'),
(539, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:58'),
(540, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:25:58'),
(541, 2669, 'fetched', NULL, 'created_date', '📌 Source: created_date on 2025-11-24', 37, '2025-11-24 08:25:58'),
(542, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(543, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(544, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(545, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(546, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(547, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:01'),
(548, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(549, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(550, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(551, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(552, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(553, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:07'),
(554, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(555, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(556, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(557, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(558, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(559, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:26:39'),
(560, 2670, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-24 08:26:42'),
(561, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(562, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(563, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(564, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(565, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(566, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:32'),
(567, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(568, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(569, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(570, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(571, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(572, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:33:34'),
(573, 2666, 'time_pause', '577', NULL, 'Action: pause at 2025-11-24 14:03:39. Duration: 577s.', 37, '2025-11-24 08:33:39'),
(574, 2666, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:03:39', 37, '2025-11-24 08:33:39'),
(575, 2671, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-27', 1, '2025-11-24 08:33:57'),
(576, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:33'),
(577, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:34'),
(578, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:34'),
(579, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:34'),
(580, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:34'),
(581, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:34:34'),
(582, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(583, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(584, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(585, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(586, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(587, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:35:16'),
(588, 2630, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:05:33. Duration: 0s.', 37, '2025-11-24 08:35:33'),
(589, 2630, 'postponed', '2025-11-24', '2025-11-25', 'Task postponed to 2025-11-25', 37, '2025-11-24 08:35:33'),
(590, 2672, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-25', 37, '2025-11-24 08:35:33'),
(591, 2667, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:05:46. Duration: 0s.', 37, '2025-11-24 08:35:46'),
(592, 2667, 'postponed', '2025-11-24', '2025-11-25', 'Task postponed to 2025-11-25', 37, '2025-11-24 08:35:46'),
(593, 2673, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-25', 37, '2025-11-24 08:35:46'),
(594, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:58'),
(595, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:59'),
(596, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:59'),
(597, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:59'),
(598, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:59'),
(599, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:40:59'),
(600, 2646, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:11:12. Duration: 0s.', 37, '2025-11-24 08:41:12'),
(601, 2646, 'postponed', '2025-11-24', '2025-11-25', 'Task postponed to 2025-11-25', 37, '2025-11-24 08:41:12'),
(602, 2674, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-25', 37, '2025-11-24 08:41:12'),
(603, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(604, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(605, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(606, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(607, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(608, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:01'),
(609, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(610, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(611, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(612, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(613, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(614, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:11'),
(615, 2645, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:29:37. Duration: 0s.', 37, '2025-11-24 08:59:37'),
(616, 2645, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:29:37', 37, '2025-11-24 08:59:37'),
(617, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(618, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(619, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(620, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(621, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(622, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:43'),
(623, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(624, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(625, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(626, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(627, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(628, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 08:59:54'),
(629, 2641, 'time_pause', '0', NULL, 'Action: pause at 2025-11-24 14:30:01. Duration: 0s.', 37, '2025-11-24 09:00:01'),
(630, 2641, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:30:01', 37, '2025-11-24 09:00:01'),
(631, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(632, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(633, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(634, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(635, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(636, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:03'),
(637, 2632, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 14:30:15', 37, '2025-11-24 09:00:15'),
(638, 2632, 'time_start', '0', NULL, 'Action: start at 2025-11-24 14:30:15. Duration: 0s.', 37, '2025-11-24 09:00:15'),
(639, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(640, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(641, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(642, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(643, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(644, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:17'),
(645, 2632, 'time_pause', '9', NULL, 'Action: pause at 2025-11-24 14:30:24. Duration: 9s.', 37, '2025-11-24 09:00:24'),
(646, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:30:24', 37, '2025-11-24 09:00:24'),
(647, 2642, 'time_pause', '0', NULL, 'Action: pause at 2025-11-24 14:30:30. Duration: 0s.', 37, '2025-11-24 09:00:30'),
(648, 2642, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:30:30', 37, '2025-11-24 09:00:30'),
(649, 2642, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:30:31. Duration: 0s.', 37, '2025-11-24 09:00:31'),
(650, 2642, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:30:31', 37, '2025-11-24 09:00:31'),
(651, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(652, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(653, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(654, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(655, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(656, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:32'),
(657, 2645, 'time_pause', '61', NULL, 'Action: pause at 2025-11-24 14:30:38. Duration: 61s.', 37, '2025-11-24 09:00:38'),
(658, 2645, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:30:38', 37, '2025-11-24 09:00:38'),
(659, 2645, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:30:42. Duration: 0s.', 37, '2025-11-24 09:00:42'),
(660, 2645, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:30:42', 37, '2025-11-24 09:00:42'),
(661, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(662, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(663, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(664, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(665, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(666, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:43'),
(667, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(668, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(669, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(670, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(671, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(672, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(673, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(674, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(675, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(676, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(677, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(678, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:46'),
(679, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(680, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(681, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(682, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(683, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(684, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(685, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(686, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(687, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(688, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(689, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(690, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(691, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(692, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(693, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(694, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(695, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(696, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(697, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(698, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(699, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(700, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(701, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(702, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:00:47'),
(703, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(704, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(705, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(706, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(707, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(708, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:50'),
(709, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(710, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(711, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(712, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(713, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(714, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:03:52'),
(715, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(716, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(717, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(718, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(719, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(720, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:22'),
(721, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(722, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(723, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(724, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(725, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(726, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:24'),
(727, 2642, 'time_pause', '355', NULL, 'Action: pause at 2025-11-24 14:36:26. Duration: 355s.', 37, '2025-11-24 09:06:26'),
(728, 2642, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:36:26', 37, '2025-11-24 09:06:26'),
(729, 2642, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:36:27. Duration: 0s.', 37, '2025-11-24 09:06:27'),
(730, 2642, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:36:27', 37, '2025-11-24 09:06:27'),
(731, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(732, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(733, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(734, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(735, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(736, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:28'),
(737, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(738, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(739, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(740, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(741, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(742, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:30'),
(743, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(744, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(745, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(746, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(747, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(748, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:32'),
(749, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(750, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(751, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(752, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(753, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(754, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(755, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(756, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(757, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(758, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(759, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(760, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(761, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(762, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(763, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(764, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(765, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(766, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:33'),
(767, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(768, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(769, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(770, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(771, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(772, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:06:37'),
(773, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(774, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(775, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(776, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(777, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(778, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:07:30'),
(779, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(780, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(781, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(782, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(783, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(784, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:08:36'),
(785, 2675, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 09:08:36'),
(786, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(787, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(788, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(789, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(790, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(791, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:05'),
(792, 2675, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:39:13. Duration: 0s.', 37, '2025-11-24 09:09:13'),
(793, 2675, 'postponed', '2025-11-24', '2025-11-25', 'Task postponed to 2025-11-25', 37, '2025-11-24 09:09:13'),
(794, 2676, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-25', 37, '2025-11-24 09:09:13'),
(795, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(796, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18'),
(797, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18'),
(798, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18'),
(799, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18'),
(800, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:09:18'),
(801, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(802, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(803, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(804, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(805, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(806, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:03'),
(807, 2631, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:43:18. Duration: 0s.', 37, '2025-11-24 09:13:18'),
(808, 2631, 'postponed', '2025-11-24', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-24 09:13:18'),
(809, 2677, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-24 09:13:18'),
(810, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:31'),
(811, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:31'),
(812, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:31'),
(813, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:32'),
(814, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:32'),
(815, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:32'),
(816, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(817, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(818, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(819, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(820, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(821, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:13:34'),
(822, 2634, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:43:41. Duration: 0s.', 37, '2025-11-24 09:13:41'),
(823, 2634, 'postponed', '2025-11-24', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-24 09:13:41'),
(824, 2678, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-24 09:13:41'),
(825, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(826, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(827, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(828, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(829, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(830, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:16'),
(831, 2636, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 14:47:25. Duration: 0s.', 37, '2025-11-24 09:17:25'),
(832, 2636, 'postponed', '2025-11-24', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-24 09:17:25'),
(833, 2679, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-24 09:17:25'),
(834, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(835, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(836, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(837, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(838, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(839, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:17:48'),
(840, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(841, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(842, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(843, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(844, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(845, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:05'),
(846, 2680, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 09:19:05'),
(847, 2680, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 14:49:25', 37, '2025-11-24 09:19:25'),
(848, 2680, 'time_start', '0', NULL, 'Action: start at 2025-11-24 14:49:25. Duration: 0s.', 37, '2025-11-24 09:19:25'),
(849, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(850, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(851, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(852, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(853, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(854, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:19:29'),
(855, 2680, 'time_pause', '25', NULL, 'Action: pause at 2025-11-24 14:49:50. Duration: 25s.', 37, '2025-11-24 09:19:50'),
(856, 2680, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 14:49:50', 37, '2025-11-24 09:19:50'),
(857, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(858, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(859, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(860, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(861, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(862, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:20:57'),
(863, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:51:15. Duration: 0s.', 37, '2025-11-24 09:21:15'),
(864, 2632, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:51:15', 37, '2025-11-24 09:21:15'),
(865, 2641, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:51:22. Duration: 0s.', 37, '2025-11-24 09:21:22'),
(866, 2641, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:51:22', 37, '2025-11-24 09:21:22'),
(867, 2648, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:51:24. Duration: 0s.', 37, '2025-11-24 09:21:24'),
(868, 2648, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:51:24', 37, '2025-11-24 09:21:24'),
(869, 2666, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 14:51:33. Duration: 0s.', 37, '2025-11-24 09:21:33'),
(870, 2666, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 14:51:33', 37, '2025-11-24 09:21:33'),
(871, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(872, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(873, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(874, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(875, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(876, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:26'),
(877, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(878, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(879, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(880, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(881, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(882, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:27'),
(883, 2642, 'time_pause', '1750', NULL, 'Action: pause at 2025-11-24 15:05:37. Duration: 1750s.', 37, '2025-11-24 09:35:37'),
(884, 2642, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:05:37', 37, '2025-11-24 09:35:37'),
(885, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(886, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(887, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(888, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(889, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(890, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:39'),
(891, 2641, 'time_pause', '863', NULL, 'Action: pause at 2025-11-24 15:05:45. Duration: 863s.', 37, '2025-11-24 09:35:45'),
(892, 2641, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:05:45', 37, '2025-11-24 09:35:45'),
(893, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(894, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(895, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(896, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(897, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(898, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:46'),
(899, 2645, 'time_pause', '2105', NULL, 'Action: pause at 2025-11-24 15:05:47. Duration: 2105s.', 37, '2025-11-24 09:35:47'),
(900, 2645, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:05:47', 37, '2025-11-24 09:35:47'),
(901, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(902, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(903, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(904, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(905, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(906, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:35:51'),
(907, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(908, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(909, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(910, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(911, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(912, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:09'),
(913, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(914, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(915, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(916, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(917, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(918, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:10'),
(919, 2648, 'time_pause', '1069', NULL, 'Action: pause at 2025-11-24 15:09:13. Duration: 1069s.', 37, '2025-11-24 09:39:13'),
(920, 2648, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:09:13', 37, '2025-11-24 09:39:13'),
(921, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(922, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(923, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(924, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(925, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(926, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:20'),
(927, 2632, 'time_pause', '1091', NULL, 'Action: pause at 2025-11-24 15:09:26. Duration: 1091s.', 37, '2025-11-24 09:39:26'),
(928, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:09:26', 37, '2025-11-24 09:39:26'),
(929, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(930, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(931, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(932, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(933, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(934, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:29'),
(935, 2632, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-24 09:39:44'),
(936, 2632, 'progress_updated', '0%', '94%', '', 37, '2025-11-24 09:39:44'),
(937, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(938, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(939, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(940, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(941, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(942, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:39:46'),
(943, 2666, 'time_pause', '1116', NULL, 'Action: pause at 2025-11-24 15:10:09. Duration: 1116s.', 37, '2025-11-24 09:40:09'),
(944, 2666, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:10:09', 37, '2025-11-24 09:40:09'),
(945, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(946, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(947, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(948, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(949, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(950, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:16'),
(951, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(952, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(953, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(954, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(955, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(956, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:39'),
(957, 2666, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 15:11:46. Duration: 0s.', 37, '2025-11-24 09:41:46'),
(958, 2666, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 15:11:46', 37, '2025-11-24 09:41:46'),
(959, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(960, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(961, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(962, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(963, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(964, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:41:47'),
(965, 2666, 'time_complete', '23', NULL, 'Action: complete at 2025-11-24 15:12:09. Duration: 23s.', 37, '2025-11-24 09:42:09'),
(966, 2666, 'status_changed', 'in_progress', 'completed', '', 37, '2025-11-24 09:42:09'),
(967, 2666, 'progress_updated', '0%', '100%', '', 37, '2025-11-24 09:42:09'),
(968, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(969, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(970, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(971, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(972, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(973, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:45'),
(974, 2642, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 15:12:52. Duration: 0s.', 37, '2025-11-24 09:42:52'),
(975, 2642, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 15:12:52', 37, '2025-11-24 09:42:52'),
(976, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(977, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(978, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(979, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(980, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(981, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 09:42:56'),
(982, 2642, 'time_pause', '24', NULL, 'Action: pause at 2025-11-24 15:13:16. Duration: 24s.', 37, '2025-11-24 09:43:16'),
(983, 2642, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 15:13:16', 37, '2025-11-24 09:43:16'),
(984, 2642, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 15:13:18. Duration: 0s.', 37, '2025-11-24 09:43:18'),
(985, 2642, 'resumed', 'paused', 'in_progress', 'Task resumed at 2025-11-24 15:13:18', 37, '2025-11-24 09:43:18'),
(986, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(987, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(988, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(989, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(990, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(991, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:04:44'),
(992, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(993, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(994, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(995, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(996, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(997, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:06:50'),
(998, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(999, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(1000, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(1001, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(1002, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(1003, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:50'),
(1004, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1005, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1006, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1007, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1008, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1009, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:44:51'),
(1010, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1011, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1012, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1013, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1014, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1015, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:33'),
(1016, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1017, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1018, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1019, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1020, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1021, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:48:34'),
(1022, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1023, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1024, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1025, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1026, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1027, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:40'),
(1028, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1029, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1030, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1031, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1032, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1033, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:45'),
(1034, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1035, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1036, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1037, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1038, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1039, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1040, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1041, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1042, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1043, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1044, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1045, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1046, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1047, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1048, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1049, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1050, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1051, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:46'),
(1052, 2680, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 16:21:47. Duration: 0s.', 37, '2025-11-24 10:51:47'),
(1053, 2680, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 16:21:47', 37, '2025-11-24 10:51:47'),
(1054, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1055, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1056, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1057, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1058, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1059, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:51:53'),
(1060, 2632, 'time_pause', '5444', NULL, 'Action: pause at 2025-11-24 16:21:59. Duration: 5444s.', 37, '2025-11-24 10:51:59'),
(1061, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:21:59', 37, '2025-11-24 10:51:59'),
(1062, 2632, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-24 10:52:02'),
(1063, 2632, 'progress_updated', '94%', '97%', '', 37, '2025-11-24 10:52:08'),
(1064, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1065, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1066, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1067, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1068, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1069, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:53:57'),
(1070, 2632, 'time_pause', '5564', NULL, 'Action: pause at 2025-11-24 16:23:59. Duration: 5564s.', 37, '2025-11-24 10:53:59'),
(1071, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:23:59', 37, '2025-11-24 10:53:59'),
(1072, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1073, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1074, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1075, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1076, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1077, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:36'),
(1078, 2642, 'time_pause', '4339', NULL, 'Action: pause at 2025-11-24 16:25:37. Duration: 4339s.', 37, '2025-11-24 10:55:37'),
(1079, 2642, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:25:37', 37, '2025-11-24 10:55:37'),
(1080, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1081, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1082, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1083, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1084, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1085, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 10:55:51'),
(1086, 2680, 'time_pause', '246', NULL, 'Action: pause at 2025-11-24 16:25:53. Duration: 246s.', 37, '2025-11-24 10:55:53'),
(1087, 2680, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:25:53', 37, '2025-11-24 10:55:53'),
(1088, 2635, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 16:26:00', 37, '2025-11-24 10:56:00'),
(1089, 2635, 'time_start', '0', NULL, 'Action: start at 2025-11-24 16:26:00. Duration: 0s.', 37, '2025-11-24 10:56:00'),
(1090, 2648, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 16:26:08. Duration: 0s.', 37, '2025-11-24 10:56:08'),
(1091, 2648, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 16:26:08', 37, '2025-11-24 10:56:08'),
(1092, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1093, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1094, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1095, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1096, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1097, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:42'),
(1098, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1099, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1100, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1101, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1102, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1103, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:45'),
(1104, 2648, 'time_pause', '279', NULL, 'Action: pause at 2025-11-24 16:30:47. Duration: 279s.', 37, '2025-11-24 11:00:47'),
(1105, 2648, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:30:47', 37, '2025-11-24 11:00:47'),
(1106, 2638, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 16:30:50', 37, '2025-11-24 11:00:50'),
(1107, 2638, 'time_start', '0', NULL, 'Action: start at 2025-11-24 16:30:50. Duration: 0s.', 37, '2025-11-24 11:00:50'),
(1108, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1109, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1110, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1111, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1112, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1113, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:00:56'),
(1114, 2638, 'time_pause', '13', NULL, 'Action: pause at 2025-11-24 16:31:03. Duration: 13s.', 37, '2025-11-24 11:01:03'),
(1115, 2638, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:31:03', 37, '2025-11-24 11:01:03'),
(1116, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1117, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1118, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1119, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1120, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1121, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:05'),
(1122, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 16:31:07. Duration: 0s.', 37, '2025-11-24 11:01:07'),
(1123, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 16:31:07', 37, '2025-11-24 11:01:07'),
(1124, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1125, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1126, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1127, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1128, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1129, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:13'),
(1130, 2638, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 16:31:19. Duration: 0s.', 37, '2025-11-24 11:01:19'),
(1131, 2638, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 16:31:19', 37, '2025-11-24 11:01:19'),
(1132, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1133, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1134, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1135, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1136, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1137, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:01:21'),
(1138, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1139, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1140, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1141, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1142, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1143, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:14:57'),
(1144, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1145, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1146, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1147, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1148, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1149, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:24:59'),
(1150, 2632, 'time_pause', '1433', NULL, 'Action: pause at 2025-11-24 16:55:00. Duration: 1433s.', 37, '2025-11-24 11:25:00'),
(1151, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:55:00', 37, '2025-11-24 11:25:00'),
(1152, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1153, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1154, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1155, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1156, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1157, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:25:28'),
(1158, 2633, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 16:55:33', 37, '2025-11-24 11:25:33'),
(1159, 2633, 'time_start', '0', NULL, 'Action: start at 2025-11-24 16:55:33. Duration: 0s.', 37, '2025-11-24 11:25:33'),
(1160, 2648, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 16:56:57. Duration: 0s.', 37, '2025-11-24 11:26:57'),
(1161, 2648, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 16:56:57', 37, '2025-11-24 11:26:57'),
(1162, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1163, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1164, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1165, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1166, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1167, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:27:24'),
(1168, 2648, 'time_pause', '30', NULL, 'Action: pause at 2025-11-24 16:57:27. Duration: 30s.', 37, '2025-11-24 11:27:27'),
(1169, 2648, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 16:57:27', 37, '2025-11-24 11:27:27'),
(1170, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1171, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1172, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1173, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1174, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1175, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:11'),
(1176, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:03:13. Duration: 0s.', 37, '2025-11-24 11:33:13'),
(1177, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:03:13', 37, '2025-11-24 11:33:13'),
(1178, 2638, 'time_pause', '1916', NULL, 'Action: pause at 2025-11-24 17:03:15. Duration: 1916s.', 37, '2025-11-24 11:33:15'),
(1179, 2638, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:03:15', 37, '2025-11-24 11:33:15');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1180, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1181, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1182, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1183, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1184, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1185, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:22'),
(1186, 2632, 'time_pause', '13', NULL, 'Action: pause at 2025-11-24 17:03:26. Duration: 13s.', 37, '2025-11-24 11:33:26'),
(1187, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:03:26', 37, '2025-11-24 11:33:26'),
(1188, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1189, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1190, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1191, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1192, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1193, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:28'),
(1194, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:03:40. Duration: 0s.', 37, '2025-11-24 11:33:40'),
(1195, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:03:40', 37, '2025-11-24 11:33:40'),
(1196, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:42'),
(1197, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:43'),
(1198, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:43'),
(1199, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:43'),
(1200, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:43'),
(1201, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:43'),
(1202, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1203, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1204, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1205, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1206, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1207, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:33:44'),
(1208, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1209, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1210, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1211, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1212, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1213, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:35'),
(1214, 2632, 'time_pause', '176', NULL, 'Action: pause at 2025-11-24 17:06:36. Duration: 176s.', 37, '2025-11-24 11:36:36'),
(1215, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:06:36', 37, '2025-11-24 11:36:36'),
(1216, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1217, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1218, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1219, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1220, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1221, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:47'),
(1222, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:06:49. Duration: 0s.', 37, '2025-11-24 11:36:49'),
(1223, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:06:49', 37, '2025-11-24 11:36:49'),
(1224, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1225, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1226, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1227, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1228, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1229, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:49'),
(1230, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1231, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1232, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1233, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1234, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1235, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:36:53'),
(1236, 2638, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:06:54. Duration: 0s.', 37, '2025-11-24 11:36:54'),
(1237, 2638, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:06:54', 37, '2025-11-24 11:36:54'),
(1238, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1239, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1240, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1241, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1242, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1243, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:50'),
(1244, 2633, 'time_pause', '860', NULL, 'Action: pause at 2025-11-24 17:09:53. Duration: 860s.', 37, '2025-11-24 11:39:54'),
(1245, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:09:53', 37, '2025-11-24 11:39:54'),
(1246, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1247, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1248, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1249, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1250, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1251, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:39:56'),
(1252, 2632, 'time_pause', '191', NULL, 'Action: pause at 2025-11-24 17:10:00. Duration: 191s.', 37, '2025-11-24 11:40:00'),
(1253, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:10:00', 37, '2025-11-24 11:40:00'),
(1254, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1255, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1256, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1257, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1258, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1259, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:40:00'),
(1260, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:10:03. Duration: 0s.', 37, '2025-11-24 11:40:03'),
(1261, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:10:03', 37, '2025-11-24 11:40:03'),
(1262, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1263, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1264, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1265, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1266, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1267, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:54'),
(1268, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1269, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1270, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1271, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1272, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1273, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:56'),
(1274, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1275, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1276, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1277, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1278, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1279, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1280, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1281, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1282, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1283, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1284, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1285, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1286, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1287, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1288, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1289, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1290, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1291, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:57'),
(1292, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1293, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1294, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1295, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1296, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1297, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1298, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1299, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1300, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1301, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1302, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1303, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1304, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1305, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1306, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1307, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1308, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1309, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1310, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1311, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1312, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1313, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1314, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:58'),
(1315, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1316, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1317, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1318, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1319, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1320, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1321, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:46:59'),
(1322, 2635, 'time_pause', '3060', NULL, 'Action: pause at 2025-11-24 17:17:00. Duration: 3060s.', 37, '2025-11-24 11:47:00'),
(1323, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:17:00', 37, '2025-11-24 11:47:00'),
(1324, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1325, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1326, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1327, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1328, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1329, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:47:05'),
(1330, 2638, 'time_pause', '697', NULL, 'Action: pause at 2025-11-24 17:18:31. Duration: 697s.', 37, '2025-11-24 11:48:31'),
(1331, 2638, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:18:31', 37, '2025-11-24 11:48:31'),
(1332, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1333, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1334, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1335, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1336, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1337, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:10'),
(1338, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:20:12. Duration: 0s.', 37, '2025-11-24 11:50:12'),
(1339, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:20:12', 37, '2025-11-24 11:50:12'),
(1340, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1341, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1342, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1343, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1344, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1345, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:15'),
(1346, 2633, 'time_pause', '5', NULL, 'Action: pause at 2025-11-24 17:20:17. Duration: 5s.', 37, '2025-11-24 11:50:17'),
(1347, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:20:17', 37, '2025-11-24 11:50:18'),
(1348, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1349, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1350, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1351, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1352, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1353, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:50:20'),
(1354, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1355, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1356, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1357, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1358, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1359, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:19'),
(1360, 2681, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 11:51:19'),
(1361, 2681, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 17:21:34', 37, '2025-11-24 11:51:34'),
(1362, 2681, 'time_start', '0', NULL, 'Action: start at 2025-11-24 17:21:34. Duration: 0s.', 37, '2025-11-24 11:51:34'),
(1363, 2681, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 17:21:42', 37, '2025-11-24 11:51:42'),
(1364, 2681, 'time_start', '0', NULL, 'Action: start at 2025-11-24 17:21:42. Duration: 0s.', 37, '2025-11-24 11:51:42'),
(1365, 2681, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 17:21:45', 37, '2025-11-24 11:51:45'),
(1366, 2681, 'time_start', '0', NULL, 'Action: start at 2025-11-24 17:21:45. Duration: 0s.', 37, '2025-11-24 11:51:45'),
(1367, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1368, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1369, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1370, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1371, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1372, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:45'),
(1373, 2681, 'time_pause', '7', NULL, 'Action: pause at 2025-11-24 17:21:52. Duration: 7s.', 37, '2025-11-24 11:51:52'),
(1374, 2681, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:21:52', 37, '2025-11-24 11:51:52'),
(1375, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1376, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1377, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1378, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1379, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1380, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:51:57'),
(1381, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:21:58. Duration: 0s.', 37, '2025-11-24 11:51:58'),
(1382, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:21:58', 37, '2025-11-24 11:51:58'),
(1383, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1384, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1385, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1386, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1387, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1388, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:52:21'),
(1389, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1390, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1391, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1392, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1393, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1394, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:53:12'),
(1395, 2682, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-24', 37, '2025-11-24 11:53:12'),
(1396, 2682, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 17:23:20', 37, '2025-11-24 11:53:20'),
(1397, 2682, 'time_start', '0', NULL, 'Action: start at 2025-11-24 17:23:20. Duration: 0s.', 37, '2025-11-24 11:53:20'),
(1398, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1399, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1400, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1401, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1402, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1403, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:09'),
(1404, 2682, 'time_pause', '55', NULL, 'Action: pause at 2025-11-24 17:24:15. Duration: 55s.', 37, '2025-11-24 11:54:15'),
(1405, 2682, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:24:15', 37, '2025-11-24 11:54:15'),
(1406, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1407, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1408, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1409, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1410, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1411, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:54:36'),
(1412, 2682, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:24:47. Duration: 0s.', 37, '2025-11-24 11:54:47'),
(1413, 2682, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:24:47', 37, '2025-11-24 11:54:47'),
(1414, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1415, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1416, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1417, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1418, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1419, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 11:57:27'),
(1420, 2648, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:27:30. Duration: 0s.', 37, '2025-11-24 11:57:30'),
(1421, 2648, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:27:30', 37, '2025-11-24 11:57:30'),
(1422, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1423, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1424, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1425, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1426, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1427, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:50'),
(1428, 2632, 'time_pause', '1730', NULL, 'Action: pause at 2025-11-24 17:38:53. Duration: 1730s.', 37, '2025-11-24 12:08:53'),
(1429, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:38:53', 37, '2025-11-24 12:08:53'),
(1430, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1431, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1432, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1433, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1434, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1435, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:54'),
(1436, 2633, 'time_pause', '1018', NULL, 'Action: pause at 2025-11-24 17:38:56. Duration: 1018s.', 37, '2025-11-24 12:08:56'),
(1437, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:38:56', 37, '2025-11-24 12:08:56'),
(1438, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1439, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1440, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1441, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1442, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1443, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:08:57'),
(1444, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:39:00. Duration: 0s.', 37, '2025-11-24 12:09:00'),
(1445, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:39:00', 37, '2025-11-24 12:09:00'),
(1446, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:00'),
(1447, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:01'),
(1448, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:01'),
(1449, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:01'),
(1450, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:01'),
(1451, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:01'),
(1452, 2682, 'time_pause', '856', NULL, 'Action: pause at 2025-11-24 17:39:03. Duration: 856s.', 37, '2025-11-24 12:09:03'),
(1453, 2682, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:39:03', 37, '2025-11-24 12:09:03'),
(1454, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1455, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1456, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1457, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1458, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1459, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:03'),
(1460, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1461, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1462, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1463, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1464, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1465, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:21'),
(1466, 2633, 'time_pause', '29', NULL, 'Action: pause at 2025-11-24 17:39:29. Duration: 29s.', 37, '2025-11-24 12:09:29'),
(1467, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:39:29', 37, '2025-11-24 12:09:29'),
(1468, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1469, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1470, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1471, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1472, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1473, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:33'),
(1474, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:39:37. Duration: 0s.', 37, '2025-11-24 12:09:37'),
(1475, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:39:37', 37, '2025-11-24 12:09:37'),
(1476, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1477, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1478, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1479, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1480, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1481, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:09:38'),
(1482, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1483, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1484, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1485, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1486, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1487, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:24'),
(1488, 2633, 'time_pause', '528', NULL, 'Action: pause at 2025-11-24 17:48:25. Duration: 528s.', 37, '2025-11-24 12:18:25'),
(1489, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:48:25', 37, '2025-11-24 12:18:25'),
(1490, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1491, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1492, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1493, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1494, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1495, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:57'),
(1496, 2648, 'time_pause', '1288', NULL, 'Action: pause at 2025-11-24 17:48:58. Duration: 1288s.', 37, '2025-11-24 12:18:58'),
(1497, 2648, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:48:58', 37, '2025-11-24 12:18:58'),
(1498, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1499, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1500, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1501, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1502, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1503, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:18:58'),
(1504, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:49:01. Duration: 0s.', 37, '2025-11-24 12:19:01'),
(1505, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:49:01', 37, '2025-11-24 12:19:01'),
(1506, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1507, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1508, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1509, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1510, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1511, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:22:25'),
(1512, 2632, 'time_pause', '207', NULL, 'Action: pause at 2025-11-24 17:52:28. Duration: 207s.', 37, '2025-11-24 12:22:28'),
(1513, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:52:28', 37, '2025-11-24 12:22:28'),
(1514, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1515, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1516, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1517, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1518, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1519, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:15'),
(1520, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:53:16. Duration: 0s.', 37, '2025-11-24 12:23:16'),
(1521, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:53:16', 37, '2025-11-24 12:23:16'),
(1522, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1523, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1524, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1525, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1526, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1527, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:23:17'),
(1528, 2632, 'time_pause', '7', NULL, 'Action: pause at 2025-11-24 17:53:23. Duration: 7s.', 37, '2025-11-24 12:23:23'),
(1529, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:53:23', 37, '2025-11-24 12:23:23'),
(1530, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1531, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1532, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1533, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1534, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1535, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:25:29'),
(1536, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 17:55:31. Duration: 0s.', 37, '2025-11-24 12:25:31'),
(1537, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 17:55:31', 37, '2025-11-24 12:25:31'),
(1538, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1539, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1540, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1541, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1542, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1543, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:50'),
(1544, 2632, 'time_pause', '265', NULL, 'Action: pause at 2025-11-24 17:59:56. Duration: 265s.', 37, '2025-11-24 12:29:56'),
(1545, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 17:59:56', 37, '2025-11-24 12:29:56'),
(1546, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1547, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1548, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1549, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1550, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1551, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:29:59'),
(1552, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 18:00:12. Duration: 0s.', 37, '2025-11-24 12:30:12'),
(1553, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 18:00:12', 37, '2025-11-24 12:30:12'),
(1554, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17'),
(1555, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17'),
(1556, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17'),
(1557, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17'),
(1558, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17'),
(1559, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:30:17');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1560, 2632, 'time_pause', '15', NULL, 'Action: pause at 2025-11-24 18:00:27. Duration: 15s.', 37, '2025-11-24 12:30:27'),
(1561, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 18:00:27', 37, '2025-11-24 12:30:27'),
(1562, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1563, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1564, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1565, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1566, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1567, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1568, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1569, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1570, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1571, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1572, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1573, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1574, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1575, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1576, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1577, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1578, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1579, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1580, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1581, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1582, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1583, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1584, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1585, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:05'),
(1586, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1587, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1588, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1589, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1590, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1591, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1592, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1593, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1594, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1595, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1596, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1597, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1598, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1599, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1600, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1601, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1602, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1603, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:06'),
(1604, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 18:04:07. Duration: 0s.', 37, '2025-11-24 12:34:07'),
(1605, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 18:04:07', 37, '2025-11-24 12:34:07'),
(1606, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1607, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1608, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1609, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1610, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1611, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:09'),
(1612, 2632, 'time_pause', '6', NULL, 'Action: pause at 2025-11-24 18:04:13. Duration: 6s.', 37, '2025-11-24 12:34:13'),
(1613, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 18:04:13', 37, '2025-11-24 12:34:13'),
(1614, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1615, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1616, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1617, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1618, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1619, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:34:15'),
(1620, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1621, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1622, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1623, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1624, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1625, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 12:44:36'),
(1626, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1627, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1628, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1629, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1630, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1631, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:35:49'),
(1632, 2682, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:05:50. Duration: 0s.', 37, '2025-11-24 13:35:50'),
(1633, 2682, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:05:50', 37, '2025-11-24 13:35:50'),
(1634, 2682, 'time_pause', '3', NULL, 'Action: pause at 2025-11-24 19:05:53. Duration: 3s.', 37, '2025-11-24 13:35:53'),
(1635, 2682, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:05:53', 37, '2025-11-24 13:35:53'),
(1636, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1637, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1638, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1639, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1640, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1641, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:36:37'),
(1642, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:07:04. Duration: 0s.', 37, '2025-11-24 13:37:04'),
(1643, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:07:04', 37, '2025-11-24 13:37:04'),
(1644, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1645, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1646, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1647, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1648, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1649, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:32'),
(1650, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1651, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1652, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1653, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1654, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1655, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:36'),
(1656, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1657, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1658, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1659, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1660, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1661, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:37'),
(1662, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1663, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1664, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1665, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1666, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1667, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1668, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1669, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1670, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1671, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1672, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1673, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1674, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1675, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1676, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1677, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1678, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1679, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1680, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1681, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1682, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1683, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1684, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1685, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:38'),
(1686, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1687, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1688, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1689, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1690, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1691, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:40'),
(1692, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1693, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1694, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1695, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1696, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1697, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1698, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1699, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1700, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1701, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1702, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1703, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 13:45:41'),
(1704, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:15:54. Duration: 0s.', 37, '2025-11-24 13:45:54'),
(1705, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:15:54', 37, '2025-11-24 13:45:54'),
(1706, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1707, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1708, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1709, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1710, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1711, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:00:50'),
(1712, 2632, 'time_pause', '898', NULL, 'Action: pause at 2025-11-24 19:30:52. Duration: 898s.', 37, '2025-11-24 14:00:52'),
(1713, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:30:52', 37, '2025-11-24 14:00:52'),
(1714, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:30:56. Duration: 0s.', 37, '2025-11-24 14:00:56'),
(1715, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:30:56', 37, '2025-11-24 14:00:56'),
(1716, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1717, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1718, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1719, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1720, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1721, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:01:10'),
(1722, 2632, 'time_pause', '16', NULL, 'Action: pause at 2025-11-24 19:31:12. Duration: 16s.', 37, '2025-11-24 14:01:12'),
(1723, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:31:12', 37, '2025-11-24 14:01:12'),
(1724, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:31:31. Duration: 0s.', 37, '2025-11-24 14:01:31'),
(1725, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:31:31', 37, '2025-11-24 14:01:31'),
(1726, 2637, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 19:31:35', 37, '2025-11-24 14:01:35'),
(1727, 2637, 'time_start', '0', NULL, 'Action: start at 2025-11-24 19:31:35. Duration: 0s.', 37, '2025-11-24 14:01:35'),
(1728, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1729, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1730, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1731, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1732, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1733, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:02:43'),
(1734, 2633, 'time_pause', '1546', NULL, 'Action: pause at 2025-11-24 19:32:50. Duration: 1546s.', 37, '2025-11-24 14:02:50'),
(1735, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:32:50', 37, '2025-11-24 14:02:50'),
(1736, 2633, 'status_changed', 'on_break', 'in_progress', 'Progress updated via daily planner', 37, '2025-11-24 14:02:57'),
(1737, 2633, 'progress_updated', '0%', '23%', 'Progress updated via daily planner', 37, '2025-11-24 14:02:57'),
(1738, 2633, 'time_pause', '1570', NULL, 'Action: pause at 2025-11-24 19:33:14. Duration: 1570s.', 37, '2025-11-24 14:03:14'),
(1739, 2633, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:33:14', 37, '2025-11-24 14:03:14'),
(1740, 2648, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:33:26. Duration: 0s.', 37, '2025-11-24 14:03:27'),
(1741, 2648, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:33:26', 37, '2025-11-24 14:03:27'),
(1742, 2648, 'time_pause', '1', NULL, 'Action: pause at 2025-11-24 19:33:27. Duration: 1s.', 37, '2025-11-24 14:03:27'),
(1743, 2648, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:33:27', 37, '2025-11-24 14:03:27'),
(1744, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1745, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1746, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1747, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1748, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1749, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:03:59'),
(1750, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1751, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1752, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1753, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1754, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1755, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:44'),
(1756, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1757, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1758, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1759, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1760, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1761, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:53'),
(1762, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1763, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1764, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1765, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1766, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1767, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:11:54'),
(1768, 2632, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:41:56. Duration: 0s.', 37, '2025-11-24 14:11:56'),
(1769, 2632, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:41:56', 37, '2025-11-24 14:11:56'),
(1770, 2632, 'time_pause', '2', NULL, 'Action: pause at 2025-11-24 19:41:58. Duration: 2s.', 37, '2025-11-24 14:11:58'),
(1771, 2632, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:41:58', 37, '2025-11-24 14:11:58'),
(1772, 2632, 'status_changed', 'on_break', 'in_progress', 'Progress updated via daily planner', 37, '2025-11-24 14:12:04'),
(1773, 2632, 'progress_updated', '97%', '13%', 'Progress updated via daily planner', 37, '2025-11-24 14:12:04'),
(1774, 2632, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 19:42:10. Duration: 0s.', 37, '2025-11-24 14:12:10'),
(1775, 2632, 'postponed', '2025-11-24', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-24 14:12:10'),
(1776, 2683, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-24 14:12:10'),
(1777, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1778, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1779, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1780, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1781, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1782, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:12:31'),
(1783, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1784, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1785, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1786, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1787, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1788, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:13:16'),
(1789, 2635, 'time_pause', '892', NULL, 'Action: pause at 2025-11-24 19:46:23. Duration: 892s.', 37, '2025-11-24 14:16:23'),
(1790, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:46:23', 37, '2025-11-24 14:16:23'),
(1791, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:46:29. Duration: 0s.', 37, '2025-11-24 14:16:29'),
(1792, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:46:29', 37, '2025-11-24 14:16:29'),
(1793, 2635, 'time_pause', '4', NULL, 'Action: pause at 2025-11-24 19:46:33. Duration: 4s.', 37, '2025-11-24 14:16:33'),
(1794, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:46:33', 37, '2025-11-24 14:16:33'),
(1795, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:46:34. Duration: 0s.', 37, '2025-11-24 14:16:34'),
(1796, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:46:34', 37, '2025-11-24 14:16:34'),
(1797, 2635, 'time_pause', '11', NULL, 'Action: pause at 2025-11-24 19:46:45. Duration: 11s.', 37, '2025-11-24 14:16:45'),
(1798, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:46:45', 37, '2025-11-24 14:16:45'),
(1799, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:46:58. Duration: 0s.', 37, '2025-11-24 14:16:58'),
(1800, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:46:58', 37, '2025-11-24 14:16:58'),
(1801, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1802, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1803, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1804, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1805, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1806, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:17:28'),
(1807, 2635, 'time_pause', '57', NULL, 'Action: pause at 2025-11-24 19:47:55. Duration: 57s.', 37, '2025-11-24 14:17:55'),
(1808, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:47:55', 37, '2025-11-24 14:17:55'),
(1809, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:48:07. Duration: 0s.', 37, '2025-11-24 14:18:07'),
(1810, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:48:07', 37, '2025-11-24 14:18:07'),
(1811, 2635, 'time_pause', '5', NULL, 'Action: pause at 2025-11-24 19:48:12. Duration: 5s.', 37, '2025-11-24 14:18:12'),
(1812, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:48:12', 37, '2025-11-24 14:18:12'),
(1813, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:48:17. Duration: 0s.', 37, '2025-11-24 14:18:17'),
(1814, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:48:17', 37, '2025-11-24 14:18:17'),
(1815, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1816, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1817, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1818, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1819, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1820, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:23'),
(1821, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1822, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1823, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1824, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1825, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1826, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1827, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1828, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1829, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1830, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1831, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1832, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1833, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1834, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1835, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1836, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1837, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1838, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1839, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1840, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1841, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1842, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1843, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1844, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1845, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1846, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1847, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1848, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1849, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1850, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1851, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1852, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1853, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1854, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1855, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1856, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1857, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1858, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1859, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1860, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1861, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1862, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1863, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1864, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1865, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1866, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1867, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1868, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1869, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1870, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1871, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1872, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1873, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1874, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1875, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1876, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1877, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1878, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1879, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1880, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1881, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1882, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1883, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1884, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1885, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1886, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:24'),
(1887, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1888, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1889, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1890, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1891, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1892, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1893, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1894, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1895, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1896, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1897, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1898, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1899, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1900, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1901, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1902, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1903, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1904, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1905, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1906, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1907, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1908, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1909, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1910, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1911, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1912, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1913, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1914, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1915, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1916, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1917, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1918, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1919, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1920, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1921, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1922, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1923, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1924, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1925, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1926, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1927, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1928, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1929, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1930, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1931, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1932, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1933, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1934, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1935, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1936, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1937, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1938, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1939, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1940, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1941, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1942, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1943, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1944, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1945, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1946, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1947, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1948, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1949, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1950, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1951, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1952, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1953, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1954, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1955, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1956, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1957, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1958, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1959, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1960, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1961, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1962, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1963, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1964, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1965, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1966, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1967, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1968, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1969, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1970, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1971, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1972, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1973, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1974, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1975, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1976, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1977, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1978, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1979, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1980, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1981, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1982, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1983, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1984, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1985, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1986, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1987, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1988, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:25'),
(1989, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1990, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1991, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1992, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1993, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1994, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:21:26'),
(1995, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(1996, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(1997, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(1998, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(1999, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(2000, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:33'),
(2001, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2002, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2003, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2004, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2005, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2006, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2007, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2008, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2009, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2010, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2011, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2012, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2013, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2014, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2015, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2016, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2017, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2018, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2019, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2020, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2021, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2022, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2023, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2024, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2025, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2026, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2027, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2028, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2029, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2030, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2031, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2032, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2033, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2034, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2035, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2036, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:24:34'),
(2037, 2635, 'time_pause', '381', NULL, 'Action: pause at 2025-11-24 19:54:38. Duration: 381s.', 37, '2025-11-24 14:24:38'),
(2038, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:54:38', 37, '2025-11-24 14:24:38'),
(2039, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:54:44. Duration: 0s.', 37, '2025-11-24 14:24:44'),
(2040, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:54:44', 37, '2025-11-24 14:24:44'),
(2041, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2042, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2043, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2044, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2045, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2046, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:25:05'),
(2047, 2635, 'time_pause', '28', NULL, 'Action: pause at 2025-11-24 19:55:12. Duration: 28s.', 37, '2025-11-24 14:25:12'),
(2048, 2635, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:55:12', 37, '2025-11-24 14:25:12'),
(2049, 2637, 'time_pause', '1445', NULL, 'Action: pause at 2025-11-24 19:55:40. Duration: 1445s.', 37, '2025-11-24 14:25:40'),
(2050, 2637, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-24 19:55:40', 37, '2025-11-24 14:25:40'),
(2051, 2637, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:55:54. Duration: 0s.', 37, '2025-11-24 14:25:54'),
(2052, 2637, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:55:54', 37, '2025-11-24 14:25:54'),
(2053, 2639, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-24 19:56:06', 37, '2025-11-24 14:26:06'),
(2054, 2639, 'time_start', '0', NULL, 'Action: start at 2025-11-24 19:56:06. Duration: 0s.', 37, '2025-11-24 14:26:06'),
(2055, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2056, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2057, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2058, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2059, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2060, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:50'),
(2061, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2062, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2063, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2064, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2065, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2066, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2067, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2068, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2069, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2070, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2071, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2072, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2073, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2074, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2075, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2076, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2077, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2078, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2079, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2080, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2081, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2082, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2083, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2084, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:51'),
(2085, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2086, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2087, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2088, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2089, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2090, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2091, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2092, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2093, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2094, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2095, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2096, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2097, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2098, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2099, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2100, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2101, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2102, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2103, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2104, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2105, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2106, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2107, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2108, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:27:52'),
(2109, 2639, 'progress_updated', '0%', '14%', 'Progress updated via daily planner', 37, '2025-11-24 14:28:32'),
(2110, 2639, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-24 19:58:38. Duration: 0s.', 37, '2025-11-24 14:28:38'),
(2111, 2639, 'postponed', '2025-11-24', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-24 14:28:38'),
(2112, 2684, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-24 14:28:38'),
(2113, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2114, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2115, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2116, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2117, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2118, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:28:53'),
(2119, 2633, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:59:20. Duration: 0s.', 37, '2025-11-24 14:29:20'),
(2120, 2633, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:59:20', 37, '2025-11-24 14:29:20'),
(2121, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2122, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2123, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2124, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2125, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2126, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:29:22'),
(2127, 2635, 'time_resume', '0', NULL, 'Action: resume at 2025-11-24 19:59:57. Duration: 0s.', 37, '2025-11-24 14:29:57'),
(2128, 2635, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-24 19:59:57', 37, '2025-11-24 14:29:57'),
(2129, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2130, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2131, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2132, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2133, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2134, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:07'),
(2135, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2136, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2137, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2138, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2139, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2140, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:30:15'),
(2141, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2142, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2143, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2144, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2145, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2146, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:12'),
(2147, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2148, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2149, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2150, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2151, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2152, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2153, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2154, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2155, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2156, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2157, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2158, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2159, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2160, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2161, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2162, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2163, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2164, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2165, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2166, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2167, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2168, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2169, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2170, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:13'),
(2171, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2172, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2173, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2174, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2175, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2176, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:32:14'),
(2177, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2178, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2179, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2180, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2181, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2182, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:39'),
(2183, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2184, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2185, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2186, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2187, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2188, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:40'),
(2189, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2190, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2191, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2192, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2193, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2194, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:41'),
(2195, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2196, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2197, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2198, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2199, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2200, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2201, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2202, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2203, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2204, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2205, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2206, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2207, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2208, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2209, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2210, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2211, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2212, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2213, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2214, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2215, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2216, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2217, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2218, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2219, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2220, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2221, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2222, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2223, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2224, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2225, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2226, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2227, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2228, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2229, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2230, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2231, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2232, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2233, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2234, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2235, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2236, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2237, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2238, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2239, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2240, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2241, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2242, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:42'),
(2243, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2244, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2245, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2246, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2247, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2248, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2249, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2250, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2251, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2252, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2253, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2254, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2255, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2256, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2257, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2258, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2259, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2260, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2261, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2262, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2263, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2264, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2265, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2266, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2267, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2268, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2269, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2270, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2271, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2272, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2273, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2274, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2275, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2276, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2277, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2278, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2279, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2280, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2281, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2282, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2283, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2284, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2285, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2286, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2287, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2288, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2289, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2290, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2291, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2292, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2293, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2294, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2295, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2296, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2297, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2298, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2299, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2300, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2301, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2302, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2303, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2304, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2305, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2306, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2307, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2308, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2309, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2310, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2311, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2312, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(2313, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2314, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2315, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2316, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2317, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2318, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2319, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2320, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2321, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2322, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2323, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2324, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2325, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2326, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2327, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2328, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2329, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2330, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2331, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2332, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2333, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2334, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2335, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2336, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2337, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2338, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2339, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2340, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2341, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2342, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2343, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2344, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2345, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2346, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2347, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2348, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2349, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2350, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2351, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2352, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2353, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2354, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2355, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2356, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2357, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2358, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2359, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2360, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2361, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2362, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2363, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2364, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2365, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2366, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2367, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2368, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2369, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2370, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2371, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2372, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2373, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2374, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2375, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2376, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2377, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2378, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2379, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2380, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:43'),
(2381, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2382, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2383, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2384, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2385, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2386, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2387, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2388, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2389, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2390, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2391, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2392, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2393, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2394, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2395, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2396, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2397, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2398, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2399, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2400, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2401, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2402, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2403, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2404, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2405, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2406, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2407, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2408, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2409, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2410, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2411, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2412, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2413, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2414, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2415, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2416, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2417, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2418, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2419, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2420, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2421, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2422, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2423, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2424, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2425, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2426, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2427, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2428, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2429, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2430, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2431, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2432, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2433, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2434, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2435, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2436, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2437, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2438, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2439, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2440, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2441, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2442, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2443, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2444, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2445, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2446, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2447, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2448, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2449, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2450, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2451, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2452, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2453, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2454, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2455, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2456, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2457, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2458, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2459, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2460, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2461, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2462, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2463, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2464, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2465, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2466, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2467, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2468, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2469, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2470, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2471, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2472, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2473, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2474, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2475, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2476, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2477, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2478, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2479, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2480, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2481, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2482, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2483, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2484, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2485, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2486, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2487, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2488, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2489, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2490, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2491, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2492, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2493, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2494, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2495, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2496, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2497, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2498, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2499, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2500, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2501, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2502, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2503, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2504, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2505, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2506, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2507, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2508, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2509, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2510, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2511, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2512, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:44'),
(2513, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2514, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2515, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2516, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2517, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2518, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2519, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2520, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2521, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2522, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2523, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2524, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2525, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2526, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2527, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2528, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2529, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2530, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2531, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2532, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2533, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2534, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2535, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2536, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2537, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2538, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2539, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2540, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2541, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2542, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2543, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2544, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2545, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2546, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2547, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2548, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2549, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2550, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2551, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2552, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2553, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2554, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2555, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2556, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2557, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2558, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2559, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2560, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2561, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2562, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2563, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2564, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2565, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2566, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2567, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2568, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2569, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2570, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2571, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2572, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2573, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2574, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2575, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2576, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2577, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2578, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2579, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2580, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2581, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2582, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2583, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2584, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2585, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2586, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2587, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2588, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2589, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2590, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2591, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2592, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2593, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2594, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2595, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2596, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:45'),
(2597, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2598, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2599, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2600, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2601, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2602, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2603, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2604, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2605, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2606, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2607, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2608, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2609, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2610, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2611, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2612, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2613, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2614, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2615, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2616, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2617, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2618, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2619, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2620, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2621, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2622, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2623, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2624, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2625, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2626, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2627, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2628, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2629, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2630, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2631, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2632, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2633, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2634, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2635, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2636, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2637, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2638, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2639, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2640, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2641, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2642, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2643, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2644, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2645, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2646, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2647, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2648, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2649, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2650, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2651, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2652, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2653, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2654, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2655, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2656, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:46'),
(2657, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2658, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2659, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2660, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2661, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2662, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2663, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2664, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2665, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2666, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2667, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2668, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2669, 1269, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2670, 1273, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2671, 1274, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2672, 1275, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2673, 1277, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2674, 1278, 'rollover_detected', '2025-11-19', '2025-11-24', 'Task detected for rollover from 2025-11-19', 37, '2025-11-24 14:33:47'),
(2675, 1269, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2676, 1273, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2677, 1274, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2678, 1275, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2679, 1277, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2680, 1278, 'rollover_detected', '2025-11-19', '2025-11-25', 'Task detected for rollover from 2025-11-19', 37, '2025-11-25 03:54:59'),
(2681, 2315, 'rollover_detected', '2025-11-22', '2025-11-25', 'Task detected for rollover from 2025-11-22', 37, '2025-11-25 03:54:59'),
(2682, 2316, 'rollover_detected', '2025-11-22', '2025-11-25', 'Task detected for rollover from 2025-11-22', 37, '2025-11-25 03:54:59'),
(2683, 2633, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2684, 2635, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59');
INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(2685, 2637, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2686, 2638, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2687, 2640, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2688, 2641, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2689, 2642, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2690, 2643, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2691, 2644, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2692, 2645, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2693, 2647, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2694, 2648, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2695, 2680, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2696, 2682, 'rollover_detected', '2025-11-24', '2025-11-25', 'Task detected for rollover from 2025-11-24', 37, '2025-11-25 03:54:59'),
(2697, 2685, 'rollover', '1269', '2685', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2698, 2686, 'rollover', '1273', '2686', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2699, 2687, 'rollover', '1274', '2687', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2700, 2688, 'rollover', '1275', '2688', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2701, 2689, 'rollover', '1277', '2689', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2702, 2690, 'rollover', '1278', '2690', '🔄 Rolled over from: 2025-11-19', 37, '2025-11-25 03:54:59'),
(2703, 2691, 'rollover', '2315', '2691', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-25 03:54:59'),
(2704, 2692, 'rollover', '2316', '2692', '🔄 Rolled over from: 2025-11-22', 37, '2025-11-25 03:54:59'),
(2705, 2693, 'rollover', '2633', '2693', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2706, 2694, 'rollover', '2638', '2694', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2707, 2695, 'rollover', '2641', '2695', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2708, 2696, 'rollover', '2644', '2696', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2709, 2697, 'rollover', '2645', '2697', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2710, 2698, 'rollover', '2647', '2698', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2711, 2699, 'rollover', '2648', '2699', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2712, 2700, 'rollover', '2680', '2700', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2713, 2701, 'rollover', '2682', '2701', '🔄 Rolled over from: 2025-11-24', 37, '2025-11-25 03:54:59'),
(2714, 2702, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 03:56:04'),
(2715, 2702, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 09:26:21', 37, '2025-11-25 03:56:21'),
(2716, 2702, 'time_start', '0', NULL, 'Action: start at 2025-11-25 09:26:21. Duration: 0s.', 37, '2025-11-25 03:56:21'),
(2717, 2693, 'time_pause', '0', NULL, 'Action: pause at 2025-11-25 09:26:30. Duration: 0s.', 37, '2025-11-25 03:56:30'),
(2718, 2693, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:26:30', 37, '2025-11-25 03:56:31'),
(2719, 2702, 'time_pause', '14', NULL, 'Action: pause at 2025-11-25 09:26:35. Duration: 14s.', 37, '2025-11-25 03:56:35'),
(2720, 2702, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:26:35', 37, '2025-11-25 03:56:35'),
(2721, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 09:26:40. Duration: 0s.', 37, '2025-11-25 03:56:40'),
(2722, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 09:26:40', 37, '2025-11-25 03:56:40'),
(2723, 2691, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 09:26:41. Duration: 1s.', 37, '2025-11-25 03:56:41'),
(2724, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:26:41', 37, '2025-11-25 03:56:41'),
(2725, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 09:26:44. Duration: 0s.', 37, '2025-11-25 03:56:44'),
(2726, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 09:26:44', 37, '2025-11-25 03:56:44'),
(2727, 2691, 'time_pause', '2', NULL, 'Action: pause at 2025-11-25 09:26:46. Duration: 2s.', 37, '2025-11-25 03:56:46'),
(2728, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:26:46', 37, '2025-11-25 03:56:46'),
(2729, 2703, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-26', 37, '2025-11-25 03:59:35'),
(2730, 2692, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 09:32:13. Duration: 0s.', 37, '2025-11-25 04:02:13'),
(2731, 2692, 'postponed', '2025-11-25', '2025-11-26', 'Task postponed to 2025-11-26', 37, '2025-11-25 04:02:13'),
(2732, 2704, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-26', 37, '2025-11-25 04:02:13'),
(2733, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 09:34:09. Duration: 0s.', 37, '2025-11-25 04:04:09'),
(2734, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 09:34:09', 37, '2025-11-25 04:04:09'),
(2735, 2691, 'time_pause', '208', NULL, 'Action: pause at 2025-11-25 09:37:37. Duration: 208s.', 37, '2025-11-25 04:07:37'),
(2736, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:37:37', 37, '2025-11-25 04:07:37'),
(2737, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 09:53:06. Duration: 0s.', 37, '2025-11-25 04:23:06'),
(2738, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 09:53:06', 37, '2025-11-25 04:23:06'),
(2739, 2691, 'time_pause', '4', NULL, 'Action: pause at 2025-11-25 09:53:10. Duration: 4s.', 37, '2025-11-25 04:23:10'),
(2740, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 09:53:10', 37, '2025-11-25 04:23:10'),
(2741, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 09:53:18. Duration: 0s.', 37, '2025-11-25 04:23:18'),
(2742, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 09:53:18', 37, '2025-11-25 04:23:18'),
(2743, 2705, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 04:28:01'),
(2744, 2705, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 09:58:53', 37, '2025-11-25 04:28:53'),
(2745, 2705, 'time_start', '0', NULL, 'Action: start at 2025-11-25 09:58:53. Duration: 0s.', 37, '2025-11-25 04:28:53'),
(2746, 2676, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 10:00:15. Duration: 0s.', 37, '2025-11-25 04:30:15'),
(2747, 2676, 'postponed', '2025-11-25', '2025-11-27', 'Task postponed to 2025-11-27', 37, '2025-11-25 04:30:15'),
(2748, 2706, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-27', 37, '2025-11-25 04:30:15'),
(2749, 2691, 'progress_updated', '10%', '15%', '', 37, '2025-11-25 04:33:36'),
(2750, 2691, 'progress_updated', '15%', '35%', '', 37, '2025-11-25 04:34:55'),
(2751, 2691, 'time_pause', '785', NULL, 'Action: pause at 2025-11-25 10:06:23. Duration: 785s.', 37, '2025-11-25 04:36:23'),
(2752, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 10:06:23', 37, '2025-11-25 04:36:23'),
(2753, 2705, 'progress_updated', '0%', '9%', '', 37, '2025-11-25 04:37:38'),
(2754, 2705, 'progress_updated', '9%', '39%', '', 37, '2025-11-25 04:37:47'),
(2755, 2705, 'progress_updated', '39%', '12%', '', 37, '2025-11-25 04:37:53'),
(2756, 2705, 'progress_updated', '12%', '0%', '', 37, '2025-11-25 04:40:19'),
(2757, 2705, 'progress_updated', '0%', '24%', '', 37, '2025-11-25 04:40:25'),
(2758, 2694, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 10:11:08. Duration: 0s.', 37, '2025-11-25 04:41:08'),
(2759, 2694, 'postponed', '2025-11-25', '2025-11-27', 'Task postponed to 2025-11-27', 37, '2025-11-25 04:41:08'),
(2760, 2707, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-27', 37, '2025-11-25 04:41:08'),
(2761, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 10:15:00. Duration: 0s.', 37, '2025-11-25 04:45:00'),
(2762, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 10:15:00', 37, '2025-11-25 04:45:00'),
(2763, 2691, 'time_pause', '2746', NULL, 'Action: pause at 2025-11-25 11:00:46. Duration: 2746s.', 37, '2025-11-25 05:30:46'),
(2764, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:00:46', 37, '2025-11-25 05:30:46'),
(2765, 2705, 'time_pause', '3724', NULL, 'Action: pause at 2025-11-25 11:00:57. Duration: 3724s.', 37, '2025-11-25 05:30:57'),
(2766, 2705, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:00:57', 37, '2025-11-25 05:30:57'),
(2767, 2705, 'progress_updated', '24%', '0%', '', 37, '2025-11-25 05:35:52'),
(2768, 2705, 'progress_updated', '0%', '7%', '', 37, '2025-11-25 05:35:55'),
(2769, 2705, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:08:40. Duration: 0s.', 37, '2025-11-25 05:38:40'),
(2770, 2705, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:08:40', 37, '2025-11-25 05:38:40'),
(2771, 2705, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 11:08:41. Duration: 1s.', 37, '2025-11-25 05:38:41'),
(2772, 2705, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:08:41', 37, '2025-11-25 05:38:41'),
(2773, 2705, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:13:40. Duration: 0s.', 37, '2025-11-25 05:43:40'),
(2774, 2705, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:13:40', 37, '2025-11-25 05:43:40'),
(2775, 2705, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 11:13:41. Duration: 1s.', 37, '2025-11-25 05:43:41'),
(2776, 2705, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:13:41', 37, '2025-11-25 05:43:41'),
(2777, 2705, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:18:02. Duration: 0s.', 37, '2025-11-25 05:48:02'),
(2778, 2705, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:18:02', 37, '2025-11-25 05:48:02'),
(2779, 2699, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:18:09. Duration: 0s.', 37, '2025-11-25 05:48:09'),
(2780, 2699, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:18:09', 37, '2025-11-25 05:48:09'),
(2781, 2686, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 11:33:40. Duration: 0s.', 37, '2025-11-25 06:03:40'),
(2782, 2686, 'postponed', '2025-11-25', '2025-11-27', 'Task postponed to 2025-11-27', 37, '2025-11-25 06:03:40'),
(2783, 2708, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-27', 37, '2025-11-25 06:03:40'),
(2784, 2699, 'time_pause', '2055', NULL, 'Action: pause at 2025-11-25 11:52:24. Duration: 2055s.', 37, '2025-11-25 06:22:24'),
(2785, 2699, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:52:24', 37, '2025-11-25 06:22:24'),
(2786, 2705, 'time_pause', '2067', NULL, 'Action: pause at 2025-11-25 11:52:29. Duration: 2067s.', 37, '2025-11-25 06:22:29'),
(2787, 2705, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:52:29', 37, '2025-11-25 06:22:29'),
(2788, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:52:39. Duration: 0s.', 37, '2025-11-25 06:22:39'),
(2789, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:52:39', 37, '2025-11-25 06:22:39'),
(2790, 2691, 'time_pause', '2', NULL, 'Action: pause at 2025-11-25 11:52:41. Duration: 2s.', 37, '2025-11-25 06:22:41'),
(2791, 2691, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:52:41', 37, '2025-11-25 06:22:41'),
(2792, 2700, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 11:53:21. Duration: 0s.', 37, '2025-11-25 06:23:21'),
(2793, 2700, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 11:53:21', 37, '2025-11-25 06:23:21'),
(2794, 2700, 'time_pause', '3', NULL, 'Action: pause at 2025-11-25 11:53:24. Duration: 3s.', 37, '2025-11-25 06:23:24'),
(2795, 2700, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 11:53:24', 37, '2025-11-25 06:23:24'),
(2796, 2700, 'status_changed', 'on_break', 'in_progress', '', 37, '2025-11-25 06:23:40'),
(2797, 2700, 'progress_updated', '0%', '13%', '', 37, '2025-11-25 06:23:40'),
(2798, 2700, 'time_pause', '1032', NULL, 'Action: pause at 2025-11-25 12:10:33. Duration: 1032s.', 37, '2025-11-25 06:40:33'),
(2799, 2700, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:10:33', 37, '2025-11-25 06:40:33'),
(2800, 2691, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:10:44. Duration: 0s.', 37, '2025-11-25 06:40:44'),
(2801, 2691, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:10:44', 37, '2025-11-25 06:40:44'),
(2802, 2700, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:11:02. Duration: 0s.', 37, '2025-11-25 06:41:02'),
(2803, 2700, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:11:02', 37, '2025-11-25 06:41:02'),
(2804, 2700, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 12:11:17. Duration: 0s.', 37, '2025-11-25 06:41:17'),
(2805, 2700, 'postponed', '2025-11-25', '2025-11-28', 'Task postponed to 2025-11-28', 37, '2025-11-25 06:41:17'),
(2806, 2709, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-28', 37, '2025-11-25 06:41:17'),
(2807, 2702, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:28:46. Duration: 0s.', 37, '2025-11-25 06:58:46'),
(2808, 2702, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:28:46', 37, '2025-11-25 06:58:46'),
(2809, 2702, 'time_pause', '6', NULL, 'Action: pause at 2025-11-25 12:28:52. Duration: 6s.', 37, '2025-11-25 06:58:52'),
(2810, 2702, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:28:52', 37, '2025-11-25 06:58:52'),
(2811, 2673, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 12:29:00', 37, '2025-11-25 06:59:00'),
(2812, 2673, 'time_start', '0', NULL, 'Action: start at 2025-11-25 12:29:00. Duration: 0s.', 37, '2025-11-25 06:59:00'),
(2813, 2673, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 12:29:01. Duration: 1s.', 37, '2025-11-25 06:59:01'),
(2814, 2673, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:29:01', 37, '2025-11-25 06:59:01'),
(2815, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:29:02. Duration: 0s.', 37, '2025-11-25 06:59:02'),
(2816, 2673, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:29:02', 37, '2025-11-25 06:59:02'),
(2817, 2710, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 07:00:22'),
(2818, 2710, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 12:30:29', 37, '2025-11-25 07:00:29'),
(2819, 2710, 'time_start', '0', NULL, 'Action: start at 2025-11-25 12:30:29. Duration: 0s.', 37, '2025-11-25 07:00:29'),
(2820, 2710, 'time_pause', '7', NULL, 'Action: pause at 2025-11-25 12:30:36. Duration: 7s.', 37, '2025-11-25 07:00:36'),
(2821, 2710, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:30:36', 37, '2025-11-25 07:00:36'),
(2822, 2710, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:33:02. Duration: 0s.', 37, '2025-11-25 07:03:02'),
(2823, 2710, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:33:02', 37, '2025-11-25 07:03:02'),
(2824, 2711, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 07:03:51'),
(2825, 2711, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 12:33:55', 37, '2025-11-25 07:03:55'),
(2826, 2711, 'time_start', '0', NULL, 'Action: start at 2025-11-25 12:33:55. Duration: 0s.', 37, '2025-11-25 07:03:55'),
(2827, 2711, 'time_pause', '8', NULL, 'Action: pause at 2025-11-25 12:34:03. Duration: 8s.', 37, '2025-11-25 07:04:03'),
(2828, 2711, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:34:03', 37, '2025-11-25 07:04:03'),
(2829, 2711, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:36:46. Duration: 0s.', 37, '2025-11-25 07:06:46'),
(2830, 2711, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:36:46', 37, '2025-11-25 07:06:46'),
(2831, 2712, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 07:22:27'),
(2832, 2712, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 12:52:33', 37, '2025-11-25 07:22:33'),
(2833, 2712, 'time_start', '0', NULL, 'Action: start at 2025-11-25 12:52:33. Duration: 0s.', 37, '2025-11-25 07:22:33'),
(2834, 2712, 'time_pause', '7', NULL, 'Action: pause at 2025-11-25 12:52:40. Duration: 7s.', 37, '2025-11-25 07:22:40'),
(2835, 2712, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:52:40', 37, '2025-11-25 07:22:40'),
(2836, 2712, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:52:45. Duration: 0s.', 37, '2025-11-25 07:22:45'),
(2837, 2712, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:52:45', 37, '2025-11-25 07:22:45'),
(2838, 2713, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 07:24:48'),
(2839, 2713, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 12:54:52', 37, '2025-11-25 07:24:52'),
(2840, 2713, 'time_start', '0', NULL, 'Action: start at 2025-11-25 12:54:52. Duration: 0s.', 37, '2025-11-25 07:24:52'),
(2841, 2713, 'time_pause', '26', NULL, 'Action: pause at 2025-11-25 12:55:18. Duration: 26s.', 37, '2025-11-25 07:25:18'),
(2842, 2713, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 12:55:18', 37, '2025-11-25 07:25:18'),
(2843, 2713, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 12:57:24. Duration: 0s.', 37, '2025-11-25 07:27:24'),
(2844, 2713, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 12:57:24', 37, '2025-11-25 07:27:24'),
(2845, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 13:38:30. Duration: 0s.', 37, '2025-11-25 08:08:30'),
(2846, 2673, 'resumed', 'on_break', 'overdue', 'Task resumed at 2025-11-25 13:38:30', 37, '2025-11-25 08:08:30'),
(2847, 2673, 'time_pause', '0', NULL, 'Action: pause at 2025-11-25 13:38:33. Duration: 0s.', 37, '2025-11-25 08:08:33'),
(2848, 2673, 'paused', 'overdue', 'on_break', 'Task paused at 2025-11-25 13:38:33', 37, '2025-11-25 08:08:33'),
(2849, 2714, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 08:09:17'),
(2850, 2714, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 13:39:26', 37, '2025-11-25 08:09:26'),
(2851, 2714, 'time_start', '0', NULL, 'Action: start at 2025-11-25 13:39:26. Duration: 0s.', 37, '2025-11-25 08:09:26'),
(2852, 2714, 'time_pause', '91', NULL, 'Action: pause at 2025-11-25 13:40:57. Duration: 91s.', 37, '2025-11-25 08:10:57'),
(2853, 2714, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 13:40:57', 37, '2025-11-25 08:10:57'),
(2854, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 13:41:26. Duration: 0s.', 37, '2025-11-25 08:11:26'),
(2855, 2673, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 13:41:26', 37, '2025-11-25 08:11:26'),
(2856, 2673, 'time_pause', '2', NULL, 'Action: pause at 2025-11-25 13:41:28. Duration: 2s.', 37, '2025-11-25 08:11:28'),
(2857, 2673, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 13:41:28', 37, '2025-11-25 08:11:28'),
(2858, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 13:41:30. Duration: 0s.', 37, '2025-11-25 08:11:30'),
(2859, 2673, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 13:41:30', 37, '2025-11-25 08:11:30'),
(2860, 2673, 'time_pause', '5', NULL, 'Action: pause at 2025-11-25 13:41:35. Duration: 5s.', 37, '2025-11-25 08:11:35'),
(2861, 2673, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 13:41:35', 37, '2025-11-25 08:11:35'),
(2862, 2715, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 08:39:27'),
(2863, 2705, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 14:23:35. Duration: 0s.', 37, '2025-11-25 08:53:35'),
(2864, 2705, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 14:23:35', 37, '2025-11-25 08:53:35'),
(2865, 2713, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 14:23:39. Duration: 0s.', 37, '2025-11-25 08:53:39'),
(2866, 2713, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 14:23:39', 37, '2025-11-25 08:53:39'),
(2867, 2670, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 14:23:41', 37, '2025-11-25 08:53:41'),
(2868, 2670, 'time_start', '0', NULL, 'Action: start at 2025-11-25 14:23:41. Duration: 0s.', 37, '2025-11-25 08:53:41'),
(2869, 2670, 'time_pause', '4', NULL, 'Action: pause at 2025-11-25 14:23:45. Duration: 4s.', 37, '2025-11-25 08:53:45'),
(2870, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 14:23:45', 37, '2025-11-25 08:53:45'),
(2871, 2705, 'progress_updated', '7%', '20%', '', 37, '2025-11-25 09:03:34'),
(2872, 2705, 'time_pause', '601', NULL, 'Action: pause at 2025-11-25 14:33:36. Duration: 601s.', 37, '2025-11-25 09:03:36'),
(2873, 2705, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 14:33:36', 37, '2025-11-25 09:03:36'),
(2874, 2713, 'progress_updated', '0%', '15%', '', 37, '2025-11-25 09:04:48'),
(2875, 2713, 'time_pause', '1171', NULL, 'Action: pause at 2025-11-25 14:43:10. Duration: 1171s.', 37, '2025-11-25 09:13:10'),
(2876, 2713, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 14:43:10', 37, '2025-11-25 09:13:10'),
(2877, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 14:43:17. Duration: 0s.', 37, '2025-11-25 09:13:17'),
(2878, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 14:43:17', 37, '2025-11-25 09:13:17'),
(2879, 2715, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 14:43:27', 37, '2025-11-25 09:13:27'),
(2880, 2715, 'time_start', '0', NULL, 'Action: start at 2025-11-25 14:43:27. Duration: 0s.', 37, '2025-11-25 09:13:27'),
(2881, 2670, 'time_pause', '529', NULL, 'Action: pause at 2025-11-25 14:52:06. Duration: 529s.', 37, '2025-11-25 09:22:06'),
(2882, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 14:52:06', 37, '2025-11-25 09:22:06'),
(2883, 2715, 'time_pause', '530', NULL, 'Action: pause at 2025-11-25 14:52:17. Duration: 530s.', 37, '2025-11-25 09:22:17'),
(2884, 2715, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 14:52:17', 37, '2025-11-25 09:22:17'),
(2885, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 14:53:48. Duration: 0s.', 37, '2025-11-25 09:23:48'),
(2886, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 14:53:48', 37, '2025-11-25 09:23:48'),
(2887, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 14:53:55. Duration: 0s.', 37, '2025-11-25 09:23:55'),
(2888, 2673, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 14:53:55', 37, '2025-11-25 09:23:55'),
(2889, 2670, 'time_pause', '416', NULL, 'Action: pause at 2025-11-25 15:00:44. Duration: 416s.', 37, '2025-11-25 09:30:44'),
(2890, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:00:44', 37, '2025-11-25 09:30:44'),
(2891, 2673, 'time_pause', '3797', NULL, 'Action: pause at 2025-11-25 15:57:12. Duration: 3797s.', 37, '2025-11-25 10:27:12'),
(2892, 2673, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:12', 37, '2025-11-25 10:27:12'),
(2893, 2673, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:57:14. Duration: 0s.', 37, '2025-11-25 10:27:14'),
(2894, 2673, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:57:14', 37, '2025-11-25 10:27:14'),
(2895, 2673, 'time_pause', '3', NULL, 'Action: pause at 2025-11-25 15:57:17. Duration: 3s.', 37, '2025-11-25 10:27:17'),
(2896, 2673, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:17', 37, '2025-11-25 10:27:17'),
(2897, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:57:19. Duration: 0s.', 37, '2025-11-25 10:27:19'),
(2898, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:57:19', 37, '2025-11-25 10:27:19'),
(2899, 2670, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 15:57:20. Duration: 1s.', 37, '2025-11-25 10:27:20'),
(2900, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:20', 37, '2025-11-25 10:27:20'),
(2901, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:57:29. Duration: 0s.', 37, '2025-11-25 10:27:29'),
(2902, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:57:29', 37, '2025-11-25 10:27:29'),
(2903, 2670, 'time_pause', '5', NULL, 'Action: pause at 2025-11-25 15:57:34. Duration: 5s.', 37, '2025-11-25 10:27:34'),
(2904, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:34', 37, '2025-11-25 10:27:34'),
(2905, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:57:48. Duration: 0s.', 37, '2025-11-25 10:27:48'),
(2906, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:57:48', 37, '2025-11-25 10:27:48'),
(2907, 2670, 'time_pause', '4', NULL, 'Action: pause at 2025-11-25 15:57:52. Duration: 4s.', 37, '2025-11-25 10:27:52'),
(2908, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:52', 37, '2025-11-25 10:27:52'),
(2909, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:57:55. Duration: 0s.', 37, '2025-11-25 10:27:55'),
(2910, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:57:55', 37, '2025-11-25 10:27:55'),
(2911, 2670, 'time_pause', '4', NULL, 'Action: pause at 2025-11-25 15:57:59. Duration: 4s.', 37, '2025-11-25 10:27:59'),
(2912, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 15:57:59', 37, '2025-11-25 10:27:59'),
(2913, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 15:58:04. Duration: 0s.', 37, '2025-11-25 10:28:04'),
(2914, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 15:58:04', 37, '2025-11-25 10:28:04'),
(2915, 2670, 'time_pause', '670', NULL, 'Action: pause at 2025-11-25 16:09:14. Duration: 670s.', 37, '2025-11-25 10:39:14'),
(2916, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:09:14 with 9013467s remaining', 37, '2025-11-25 10:39:14'),
(2917, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:09:16. Duration: 0s.', 37, '2025-11-25 10:39:16'),
(2918, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:09:16 with 9013467s remaining', 37, '2025-11-25 10:39:16'),
(2919, 2670, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 16:09:17. Duration: 1s.', 37, '2025-11-25 10:39:17'),
(2920, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:09:17 with 9013467s remaining', 37, '2025-11-25 10:39:17'),
(2921, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:09:18. Duration: 0s.', 37, '2025-11-25 10:39:18'),
(2922, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:09:18 with 9013467s remaining', 37, '2025-11-25 10:39:18'),
(2923, 2670, 'time_pause', '7', NULL, 'Action: pause at 2025-11-25 16:09:25. Duration: 7s.', 37, '2025-11-25 10:39:25'),
(2924, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:09:25 with 9013467s remaining', 37, '2025-11-25 10:39:25'),
(2925, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:09:41. Duration: 0s.', 37, '2025-11-25 10:39:41'),
(2926, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:09:41 with 9013467s remaining', 37, '2025-11-25 10:39:41'),
(2927, 2670, 'time_pause', '143', NULL, 'Action: pause at 2025-11-25 16:12:04. Duration: 143s.', 37, '2025-11-25 10:42:04'),
(2928, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:12:04 with 9013467s remaining', 37, '2025-11-25 10:42:04'),
(2929, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:12:06. Duration: 0s.', 37, '2025-11-25 10:42:06'),
(2930, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:12:06 with 9013467s remaining', 37, '2025-11-25 10:42:06'),
(2931, 2702, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:13:32. Duration: 0s.', 37, '2025-11-25 10:43:32'),
(2932, 2702, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:13:32 with 0s remaining', 37, '2025-11-25 10:43:32'),
(2933, 2670, 'time_pause', '95', NULL, 'Action: pause at 2025-11-25 16:13:41. Duration: 95s.', 37, '2025-11-25 10:43:41'),
(2934, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:13:41 with 9013467s remaining', 37, '2025-11-25 10:43:41'),
(2935, 2702, 'time_pause', '14', NULL, 'Action: pause at 2025-11-25 16:13:46. Duration: 14s.', 37, '2025-11-25 10:43:46'),
(2936, 2702, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:13:46 with 900s remaining', 37, '2025-11-25 10:43:46'),
(2937, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:13:48. Duration: 0s.', 37, '2025-11-25 10:43:48'),
(2938, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:13:48 with 9013467s remaining', 37, '2025-11-25 10:43:48'),
(2939, 2670, 'time_pause', '2', NULL, 'Action: pause at 2025-11-25 16:13:50. Duration: 2s.', 37, '2025-11-25 10:43:50'),
(2940, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:13:50 with 9013467s remaining', 37, '2025-11-25 10:43:50'),
(2941, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:13:59. Duration: 0s.', 37, '2025-11-25 10:43:59'),
(2942, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:13:59 with 9013467s remaining', 37, '2025-11-25 10:43:59'),
(2943, 2670, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 16:14:00. Duration: 1s.', 37, '2025-11-25 10:44:00'),
(2944, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:14:00 with 9013467s remaining', 37, '2025-11-25 10:44:00'),
(2945, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:14:01. Duration: 0s.', 37, '2025-11-25 10:44:01'),
(2946, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:14:01 with 9013467s remaining', 37, '2025-11-25 10:44:01'),
(2947, 2670, 'time_pause', '1', NULL, 'Action: pause at 2025-11-25 16:14:02. Duration: 1s.', 37, '2025-11-25 10:44:02'),
(2948, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:14:02 with 9013467s remaining', 37, '2025-11-25 10:44:02'),
(2949, 2716, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 16:16:28', 1, '2025-11-25 10:46:28'),
(2950, 2716, 'time_start', '0', NULL, 'Action: start at 2025-11-25 16:16:28. Duration: 0s.', 1, '2025-11-25 10:46:28'),
(2951, 2716, 'time_pause', '2', NULL, 'Action: pause at 2025-11-25 16:16:30. Duration: 2s.', 1, '2025-11-25 10:46:30'),
(2952, 2716, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:16:30 with 900s remaining', 1, '2025-11-25 10:46:30'),
(2953, 2716, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:16:33. Duration: 0s.', 1, '2025-11-25 10:46:33'),
(2954, 2716, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:16:33 with 900s remaining', 1, '2025-11-25 10:46:33'),
(2955, 2716, 'progress_updated', '0%', '50%', 'Halfway done', 1, '2025-11-25 10:46:34'),
(2956, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:25:36. Duration: 0s.', 37, '2025-11-25 10:55:36'),
(2957, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:25:36 with 9013467s remaining', 37, '2025-11-25 10:55:36'),
(2958, 2670, 'time_pause', '5', NULL, 'Action: pause at 2025-11-25 16:25:41. Duration: 5s.', 37, '2025-11-25 10:55:41'),
(2959, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:25:41 with 9013467s remaining', 37, '2025-11-25 10:55:41'),
(2960, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:25:43. Duration: 0s.', 37, '2025-11-25 10:55:43'),
(2961, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:25:43 with 9013467s remaining', 37, '2025-11-25 10:55:43'),
(2962, 2670, 'time_pause', '3', NULL, 'Action: pause at 2025-11-25 16:25:46. Duration: 3s.', 37, '2025-11-25 10:55:46'),
(2963, 2670, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:25:46 with 9013467s remaining', 37, '2025-11-25 10:55:46'),
(2964, 2717, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 10:59:21'),
(2965, 2717, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 16:29:28', 37, '2025-11-25 10:59:28'),
(2966, 2717, 'time_start', '0', NULL, 'Action: start at 2025-11-25 16:29:28. Duration: 0s.', 37, '2025-11-25 10:59:28'),
(2967, 2717, 'time_pause', '44', NULL, 'Action: pause at 2025-11-25 16:30:12. Duration: 44s.', 37, '2025-11-25 11:00:12'),
(2968, 2717, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:30:12 with 900s remaining', 37, '2025-11-25 11:00:12'),
(2969, 2717, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:30:19. Duration: 0s.', 37, '2025-11-25 11:00:19'),
(2970, 2717, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:30:19 with 900s remaining', 37, '2025-11-25 11:00:19'),
(2971, 2717, 'time_pause', '6', NULL, 'Action: pause at 2025-11-25 16:30:25. Duration: 6s.', 37, '2025-11-25 11:00:25'),
(2972, 2717, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:30:25 with 900s remaining', 37, '2025-11-25 11:00:25'),
(2973, 2717, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:30:28. Duration: 0s.', 37, '2025-11-25 11:00:28'),
(2974, 2717, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:30:28 with 900s remaining', 37, '2025-11-25 11:00:28'),
(2975, 2717, 'time_pause', '61', NULL, 'Action: pause at 2025-11-25 16:31:29. Duration: 61s.', 37, '2025-11-25 11:01:29'),
(2976, 2717, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:31:29 with 900s remaining', 37, '2025-11-25 11:01:29'),
(2977, 2717, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:32:32. Duration: 0s.', 37, '2025-11-25 11:02:32'),
(2978, 2717, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:32:32 with 900s remaining', 37, '2025-11-25 11:02:32'),
(2979, 2715, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:40:15. Duration: 0s.', 37, '2025-11-25 11:10:15'),
(2980, 2715, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:40:15 with 0s remaining', 37, '2025-11-25 11:10:15'),
(2981, 2715, 'time_pause', '11', NULL, 'Action: pause at 2025-11-25 16:40:26. Duration: 11s.', 37, '2025-11-25 11:10:26'),
(2982, 2715, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:40:26 with 900s remaining', 37, '2025-11-25 11:10:26'),
(2983, 2718, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 11:11:17'),
(2984, 2718, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 16:41:27', 37, '2025-11-25 11:11:27'),
(2985, 2718, 'time_start', '0', NULL, 'Action: start at 2025-11-25 16:41:27. Duration: 0s.', 37, '2025-11-25 11:11:27'),
(2986, 2718, 'time_pause', '69', NULL, 'Action: pause at 2025-11-25 16:42:36. Duration: 69s.', 37, '2025-11-25 11:12:36'),
(2987, 2718, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:42:36 with 900s remaining', 37, '2025-11-25 11:12:36'),
(2988, 2718, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:44:38. Duration: 0s.', 37, '2025-11-25 11:14:38'),
(2989, 2718, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:44:38 with 900s remaining', 37, '2025-11-25 11:14:38'),
(2990, 2714, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:50:10. Duration: 0s.', 37, '2025-11-25 11:20:10'),
(2991, 2714, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:50:10 with 0s remaining', 37, '2025-11-25 11:20:10'),
(2992, 2718, 'time_pause', '342', NULL, 'Action: pause at 2025-11-25 16:50:20. Duration: 342s.', 37, '2025-11-25 11:20:20'),
(2993, 2718, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:50:20 with 900s remaining', 37, '2025-11-25 11:20:20'),
(2994, 2718, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:50:41. Duration: 0s.', 37, '2025-11-25 11:20:41'),
(2995, 2718, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:50:41 with 900s remaining', 37, '2025-11-25 11:20:41'),
(2996, 2718, 'time_pause', '27', NULL, 'Action: pause at 2025-11-25 16:51:08. Duration: 27s.', 37, '2025-11-25 11:21:08'),
(2997, 2718, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:51:08 with 900s remaining', 37, '2025-11-25 11:21:08'),
(2998, 2718, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 16:51:11. Duration: 0s.', 37, '2025-11-25 11:21:11'),
(2999, 2718, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 16:51:11 with 900s remaining', 37, '2025-11-25 11:21:11'),
(3000, 2718, 'time_pause', '8', NULL, 'Action: pause at 2025-11-25 16:51:19. Duration: 8s.', 37, '2025-11-25 11:21:19'),
(3001, 2718, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 16:51:19 with 900s remaining', 37, '2025-11-25 11:21:19'),
(3002, 2714, 'progress_updated', '0%', '12%', '', 37, '2025-11-25 11:30:30'),
(3003, 2714, 'time_pause', '624', NULL, 'Action: pause at 2025-11-25 17:00:34. Duration: 624s.', 37, '2025-11-25 11:30:34'),
(3004, 2714, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 17:00:34 with 900s remaining', 37, '2025-11-25 11:30:34'),
(3005, 2670, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 17:04:37. Duration: 0s.', 37, '2025-11-25 11:34:37'),
(3006, 2670, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 17:04:37 with 9013467s remaining', 37, '2025-11-25 11:34:37'),
(3007, 2719, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 11:35:25'),
(3008, 2719, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 17:05:29', 37, '2025-11-25 11:35:29'),
(3009, 2719, 'time_start', '0', NULL, 'Action: start at 2025-11-25 17:05:29. Duration: 0s.', 37, '2025-11-25 11:35:29'),
(3010, 2719, 'time_pause', '80', NULL, 'Action: pause at 2025-11-25 17:06:49. Duration: 80s.', 37, '2025-11-25 11:36:49'),
(3011, 2719, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 17:06:49 with 900s remaining', 37, '2025-11-25 11:36:49'),
(3012, 2719, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-25 17:11:56. Duration: 0s.', 37, '2025-11-25 11:41:56'),
(3013, 2719, 'postponed', '2025-11-25', '2025-11-27', 'Task postponed to 2025-11-27', 37, '2025-11-25 11:41:56'),
(3014, 2720, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-27', 37, '2025-11-25 11:41:56'),
(3015, 2721, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 11:42:47'),
(3016, 2721, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 17:12:54', 37, '2025-11-25 11:42:54'),
(3017, 2721, 'time_start', '0', NULL, 'Action: start at 2025-11-25 17:12:54. Duration: 0s.', 37, '2025-11-25 11:42:54'),
(3018, 2721, 'progress_updated', '0%', '7%', '', 37, '2025-11-25 11:43:07'),
(3019, 2721, 'time_pause', '33', NULL, 'Action: pause at 2025-11-25 17:13:27. Duration: 33s.', 37, '2025-11-25 11:43:27'),
(3020, 2721, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 17:13:27 with 900s remaining', 37, '2025-11-25 11:43:27'),
(3021, 2721, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 17:14:07. Duration: 0s.', 37, '2025-11-25 11:44:07'),
(3022, 2721, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 17:14:07 with 900s remaining', 37, '2025-11-25 11:44:07'),
(3023, 2722, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-25', 37, '2025-11-25 11:59:18'),
(3024, 2722, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-25 17:29:22', 37, '2025-11-25 11:59:22'),
(3025, 2722, 'time_start', '0', NULL, 'Action: start at 2025-11-25 17:29:22. Duration: 0s.', 37, '2025-11-25 11:59:22'),
(3026, 2722, 'time_pause', '4', NULL, 'Action: pause at 2025-11-25 17:29:26. Duration: 4s.', 37, '2025-11-25 11:59:26'),
(3027, 2722, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-25 17:29:26 with 900s remaining', 37, '2025-11-25 11:59:26'),
(3028, 2722, 'time_resume', '0', NULL, 'Action: resume at 2025-11-25 17:29:48. Duration: 0s.', 37, '2025-11-25 11:59:48'),
(3029, 2722, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-25 17:29:48 with 900s remaining', 37, '2025-11-25 11:59:48'),
(3030, 2673, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3031, 2670, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3032, 2702, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3033, 2705, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3034, 2713, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3035, 2714, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3036, 2718, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3037, 2722, 'rollover_detected', '2025-11-25', '2025-11-26', 'Task detected for rollover from 2025-11-25', 37, '2025-11-26 09:10:46'),
(3038, 2723, 'rollover', '2673', '2723', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3039, 2724, 'rollover', '2670', '2724', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3040, 2725, 'rollover', '2702', '2725', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3041, 2726, 'rollover', '2705', '2726', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3042, 2727, 'rollover', '2713', '2727', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3043, 2728, 'rollover', '2714', '2728', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3044, 2729, 'rollover', '2718', '2729', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3045, 2730, 'rollover', '2722', '2730', '🔄 Rolled over from: 2025-11-25', 37, '2025-11-26 09:10:46'),
(3046, 2728, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-26 14:41:21. Duration: 0s.', 37, '2025-11-26 09:11:21'),
(3047, 2728, 'postponed', '2025-11-26', '2025-11-27', 'Task postponed to 2025-11-27', 37, '2025-11-26 09:11:21'),
(3048, 2731, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-27', 37, '2025-11-26 09:11:21'),
(3049, 2724, 'progress_updated', '0%', '10%', '', 37, '2025-11-26 09:47:22'),
(3050, 2724, 'progress_updated', '10%', '59%', '', 37, '2025-11-26 09:47:31'),
(3051, 2723, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3052, 2724, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3053, 2725, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3054, 2726, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3055, 2727, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3056, 2729, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3057, 2730, 'rollover_detected', '2025-11-26', '2025-11-27', 'Task detected for rollover from 2025-11-26', 37, '2025-11-27 10:55:28'),
(3058, 2732, 'rollover', '2723', '2732', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3059, 2733, 'rollover', '2724', '2733', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3060, 2734, 'rollover', '2725', '2734', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3061, 2735, 'rollover', '2726', '2735', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3062, 2736, 'rollover', '2727', '2736', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3063, 2737, 'rollover', '2729', '2737', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3064, 2738, 'rollover', '2730', '2738', '🔄 Rolled over from: 2025-11-26', 37, '2025-11-27 10:55:28'),
(3065, 2739, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-28', 49, '2025-11-28 12:43:52'),
(3066, 2740, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-28', 49, '2025-11-28 12:43:52'),
(3067, 2736, 'rollover_detected', '2025-11-27', '2025-11-28', 'Task detected for rollover from 2025-11-27', 37, '2025-11-28 14:13:54'),
(3068, 2731, 'rollover_detected', '2025-11-27', '2025-11-28', 'Task detected for rollover from 2025-11-27', 37, '2025-11-28 14:13:54'),
(3069, 2737, 'rollover_detected', '2025-11-27', '2025-11-28', 'Task detected for rollover from 2025-11-27', 37, '2025-11-28 14:13:54'),
(3070, 2720, 'rollover_detected', '2025-11-27', '2025-11-28', 'Task detected for rollover from 2025-11-27', 37, '2025-11-28 14:13:54'),
(3071, 2738, 'rollover_detected', '2025-11-27', '2025-11-28', 'Task detected for rollover from 2025-11-27', 37, '2025-11-28 14:13:54'),
(3072, 2741, 'rollover', '2736', '2741', '🔄 Rolled over from: 2025-11-27', 37, '2025-11-28 14:13:54'),
(3073, 2742, 'rollover', '2731', '2742', '🔄 Rolled over from: 2025-11-27', 37, '2025-11-28 14:13:54'),
(3074, 2743, 'rollover', '2737', '2743', '🔄 Rolled over from: 2025-11-27', 37, '2025-11-28 14:13:54'),
(3075, 2744, 'rollover', '2720', '2744', '🔄 Rolled over from: 2025-11-27', 37, '2025-11-28 14:13:54'),
(3076, 2745, 'rollover', '2738', '2745', '🔄 Rolled over from: 2025-11-27', 37, '2025-11-28 14:13:54');

-- --------------------------------------------------------

--
-- Table structure for table `daily_task_updates`
--

CREATE TABLE `daily_task_updates` (
  `id` int NOT NULL,
  `plan_id` int NOT NULL,
  `progress_before` int DEFAULT '0',
  `progress_after` int NOT NULL,
  `hours_worked` decimal(4,2) DEFAULT '0.00',
  `update_notes` text,
  `blockers` text,
  `next_steps` text,
  `update_type` enum('progress','completion','blocker','status_change') DEFAULT 'progress',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_workflow_status`
--

CREATE TABLE `daily_workflow_status` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `workflow_date` date NOT NULL,
  `total_planned_tasks` int DEFAULT '0',
  `total_completed_tasks` int DEFAULT '0',
  `total_planned_hours` decimal(4,2) DEFAULT '0.00',
  `total_actual_hours` decimal(4,2) DEFAULT '0.00',
  `productivity_score` decimal(5,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `morning_submitted_at` timestamp NULL DEFAULT NULL,
  `evening_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_workflow_status`
--

INSERT INTO `daily_workflow_status` (`id`, `user_id`, `workflow_date`, `total_planned_tasks`, `total_completed_tasks`, `total_planned_hours`, `total_actual_hours`, `productivity_score`, `last_updated`, `created_at`, `morning_submitted_at`, `evening_updated_at`) VALUES
(1, 1, '2025-10-27', 3, 0, 4.50, 0.00, 0.00, '2025-10-26 21:44:23', '2025-10-26 21:44:23', NULL, NULL),
(18, 37, '2025-11-10', 0, 1, 0.00, 7.25, 100.00, '2025-11-10 12:21:46', '2025-11-10 10:14:46', '2025-11-10 10:14:46', '2025-11-10 12:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `head_id` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Human Resources', 'Employee management and organizational development', NULL, 'active', '2025-10-23 06:24:06', '2025-10-23 06:24:06'),
(5, 'Operations', 'Daily business operations and logistics', NULL, 'active', '2025-10-23 06:24:06', '2025-10-23 06:24:06'),
(6, 'Liaison', 'Interdepartmental coordination and external stakeholder communication.', 1, 'active', '2025-10-26 21:55:53', '2025-10-26 21:55:53'),
(13, 'Finance & Accounts', 'Consolidated Finance, Accounting and Financial Operations', 37, 'active', '2025-10-27 09:35:18', '2025-11-13 07:30:46'),
(14, 'Information Technology', 'Consolidated IT Development, Infrastructure and Support', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18'),
(15, 'Marketing & Sales', 'Consolidated Marketing, Sales and Business Development', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `evening_updates`
--

CREATE TABLE `evening_updates` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `planner_id` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `progress_percentage` int DEFAULT '0',
  `actual_hours_spent` decimal(4,2) DEFAULT '0.00',
  `completion_status` enum('not_started','in_progress','completed','blocked') DEFAULT 'not_started',
  `blockers` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expense_date` date NOT NULL DEFAULT (curdate()),
  `attachment` varchar(255) DEFAULT NULL,
  `rejection_reason` text,
  `approved_by` int DEFAULT NULL,
  `journal_entry_id` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `created_at`, `updated_at`, `expense_date`, `attachment`, `rejection_reason`, `approved_by`, `journal_entry_id`, `approved_at`) VALUES
(11, 1, 'travel', 700.00, 'Bus Travel', NULL, 'approved', '2025-10-30 13:15:38', '2025-11-27 12:58:50', '2025-10-30', NULL, NULL, 1, 7, '2025-11-27 12:58:50'),
(13, 37, 'food', 100.00, 'Lunch Meals', NULL, 'approved', '2025-10-31 11:27:19', '2025-11-01 07:52:14', '2025-10-31', NULL, NULL, NULL, NULL, NULL),
(14, 36, 'travel', 300.00, 'Bus Travel', NULL, 'rejected', '2025-11-01 04:15:05', '2025-11-01 07:34:26', '2025-11-01', NULL, 'Rejected for these expenses', NULL, NULL, NULL),
(18, 36, 'travel', 300.00, 'Bus travel expenses of meet the client', NULL, 'pending', '2025-11-01 07:44:12', '2025-11-01 08:10:57', '2025-11-01', NULL, NULL, NULL, NULL, NULL),
(19, 38, 'food', 300.00, 'Lunch meals', NULL, 'pending', '2025-11-01 08:00:54', '2025-11-01 08:00:54', '2025-11-01', NULL, NULL, NULL, NULL, NULL),
(22, 16, 'food', 200.00, '...........', NULL, 'approved', '2025-11-03 13:38:58', '2025-11-22 11:10:51', '2025-11-03', NULL, NULL, 37, 5, '2025-11-22 11:10:51'),
(23, 36, 'travel', 300.00, 'Bus Travel', NULL, 'pending', '2025-11-10 13:02:59', '2025-11-10 13:02:59', '2025-11-10', NULL, NULL, NULL, NULL, NULL),
(24, 37, 'travel', 300.00, 'Bus Travel', NULL, 'pending', '2025-11-10 13:11:34', '2025-11-10 13:24:52', '2025-11-10', NULL, NULL, NULL, NULL, NULL),
(28, 1, 'travel', 400.00, 'Travel Expenses', NULL, 'pending', '2025-11-10 13:50:11', '2025-11-10 13:50:11', '2025-11-10', NULL, NULL, NULL, NULL, NULL),
(29, 1, 'food', 200.00, 'Morning Breakfast', NULL, 'pending', '2025-11-11 03:56:52', '2025-11-11 03:56:52', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(33, 1, 'food', 100.00, '................', NULL, 'approved', '2025-11-11 04:40:19', '2025-11-27 12:56:51', '2025-11-11', NULL, NULL, 1, 6, '2025-11-27 12:56:51'),
(35, 3, 'Travel', 250.00, 'Test expense notification', NULL, 'pending', '2025-11-11 05:19:46', '2025-11-11 05:19:46', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(37, 3, 'Travel', 250.00, 'Test expense notification', NULL, 'pending', '2025-11-11 05:21:41', '2025-11-11 05:21:41', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(38, 37, 'travel', 500.00, 'Bus Travel', NULL, 'pending', '2025-11-11 05:22:23', '2025-11-11 05:22:23', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(39, 36, 'travel', 300.00, 'Bus Travel', NULL, 'pending', '2025-11-11 05:23:20', '2025-11-11 05:23:20', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(40, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:26:27', '2025-11-11 05:26:27', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(41, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:26:27', '2025-11-11 05:26:27', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(42, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:26:27', '2025-11-11 05:26:27', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(43, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:26:28', '2025-11-11 05:26:28', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(44, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:26:43', '2025-11-11 05:26:43', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(45, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:27:32', '2025-11-11 05:27:32', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(46, 2, 'Travel', 100.00, 'Test expense', NULL, 'pending', '2025-11-11 05:28:13', '2025-11-11 05:28:13', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(47, 3, 'Travel', 250.00, 'Test expense notification', NULL, 'pending', '2025-11-11 05:28:23', '2025-11-11 05:28:23', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(48, 3, 'Travel', 250.00, 'Test expense notification', NULL, 'pending', '2025-11-11 05:40:59', '2025-11-11 05:40:59', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(49, 2, 'Travel', 150.00, 'Live test expense claim', NULL, 'pending', '2025-11-11 05:53:46', '2025-11-11 05:53:46', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(50, 3, 'Travel', 200.00, 'Final test expense', NULL, 'pending', '2025-11-11 05:59:12', '2025-11-11 05:59:12', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(52, 40, 'food', 300.00, 'Lunch Meals', NULL, 'pending', '2025-11-11 07:27:41', '2025-11-11 07:27:41', '2025-11-11', NULL, NULL, NULL, NULL, NULL),
(53, 39, 'food', 1000.00, 'Team Lunch', NULL, 'pending', '2025-11-15 10:53:42', '2025-11-15 10:53:50', '2025-11-15', NULL, NULL, NULL, NULL, NULL),
(54, 39, 'travel', 100.00, 'Travel expense', NULL, 'pending', '2025-11-15 11:25:25', '2025-11-15 11:30:06', '2025-11-15', '1763206206_IMG_20251115_120320.jpg', NULL, NULL, NULL, NULL),
(55, 49, 'travel', 300.00, 'Bus Travel Expense', NULL, 'rejected', '2025-11-20 10:26:38', '2025-11-20 10:36:00', '2025-11-20', '1763634398_wp2581906-superman-man-of-steel-wallpaper.jpg', 'Reject this Expense', NULL, NULL, NULL),
(57, 49, 'food', 300.00, 'Team Dinner', NULL, 'approved', '2025-11-20 10:42:14', '2025-11-20 10:51:19', '2025-11-20', '1763635334_IMG_20251115_120320.jpg', NULL, 37, 1, '2025-11-20 10:51:19'),
(58, 48, 'travel', 300.00, 'Bus travel for meet the client', NULL, 'approved', '2025-11-20 10:59:21', '2025-11-20 11:00:35', '2025-11-20', '1763636361_IMG_20251115_115757_466.jpg', NULL, 37, 2, '2025-11-20 11:00:35'),
(59, 47, 'food', 300.00, 'lunch meals', NULL, 'approved', '2025-11-20 10:59:59', '2025-11-20 11:00:50', '2025-11-20', NULL, NULL, 1, 3, '2025-11-20 11:00:50'),
(60, 48, 'travel', 500.00, 'Bus Travel', NULL, 'approved', '2025-11-22 11:14:34', '2025-11-28 12:47:50', '2025-11-22', '1763810074_IMG_20251115_120242.jpg', NULL, 37, 9, '2025-11-28 12:47:50'),
(61, 37, 'travel', 300.00, 'travel expense', NULL, 'pending', '2025-11-27 14:11:07', '2025-11-27 14:11:07', '2025-11-27', NULL, NULL, NULL, NULL, NULL),
(62, 48, 'travel', 500.00, 'travel expense', NULL, 'pending', '2025-11-27 14:11:51', '2025-11-27 14:11:51', '2025-11-27', NULL, NULL, NULL, NULL, NULL),
(102, 49, 'office_supplies', 750.00, 'Office Stationery', NULL, 'pending', '2025-11-28 11:14:39', '2025-11-28 11:14:39', '2025-11-28', NULL, NULL, NULL, NULL, NULL),
(103, 37, 'office_supplies', 1250.00, 'Software Subscription', NULL, 'pending', '2025-11-28 11:15:13', '2025-11-28 11:15:13', '2025-11-28', NULL, NULL, NULL, NULL, NULL),
(106, 49, 'travel', 325.00, 'Bus fare to Meet client', NULL, 'approved', '2025-11-28 12:24:04', '2025-11-28 12:25:00', '2025-11-28', NULL, NULL, 1, 8, '2025-11-28 12:25:00');

--
-- Triggers `expenses`
--
DELIMITER $$
CREATE TRIGGER `expense_notification_insert` AFTER INSERT ON `expenses` FOR EACH ROW BEGIN
            INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, action_url)
            SELECT NEW.user_id, u.id, 'info', 'approval', 
                   CONCAT('New Expense Request from ', (SELECT name FROM users WHERE id = NEW.user_id)),
                   CONCAT('Expense request for ', NEW.description, ' - Amount: $', NEW.amount),
                   'expense', NEW.id, 'expense', 'pending', CONCAT('/ergon/expenses/view/', NEW.id)
            FROM users u 
            WHERE u.role IN ('admin', 'owner') AND u.status = 'active';
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `expense_notification_update` AFTER UPDATE ON `expenses` FOR EACH ROW BEGIN
            IF OLD.status != NEW.status AND NEW.status IN ('approved', 'rejected') THEN
                INSERT INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, approver_id, action_url)
                VALUES (NEW.approved_by, NEW.user_id, 
                       CASE WHEN NEW.status = 'approved' THEN 'success' ELSE 'warning' END,
                       'approval', 
                       CONCAT('Expense Request ', UPPER(NEW.status)),
                       CONCAT('Your expense request has been ', NEW.status, ' - Amount: $', NEW.amount),
                       'expense', NEW.id, 'expense', NEW.status, NEW.approved_by, CONCAT('/ergon/expenses/view/', NEW.id));
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

CREATE TABLE `followups` (
  `id` int NOT NULL,
  `contact_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `followup_type` enum('standalone','task') DEFAULT 'standalone',
  `task_id` int DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `status` enum('pending','in_progress','completed','postponed','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `contact_id`, `user_id`, `title`, `description`, `followup_type`, `task_id`, `follow_up_date`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Test Follow-up', 'Test description', 'standalone', NULL, '2025-11-20', 'cancelled', NULL, '2025-11-17 12:24:49', '2025-11-18 05:06:44'),
(3, 6, NULL, 'Follow-up: followup 3', 'Follow-up: followup 3', 'task', 55, '2025-11-20', 'cancelled', NULL, '2025-11-17 12:29:35', '2025-11-18 05:07:45'),
(4, 6, NULL, 'Test Followup 3', 'Test Followup 3', 'task', 57, '2025-11-21', 'completed', '2025-11-21 06:24:05', '2025-11-18 04:48:54', '2025-11-21 06:24:05'),
(5, 6, NULL, 'Follow-up: Test Followup task 4', 'Test Followup task 4', 'task', 58, '2025-11-18', 'completed', '2025-11-19 05:40:44', '2025-11-18 05:38:05', '2025-11-19 05:40:44'),
(9, 6, NULL, 'Follow-up: test fask and followup 5', 'test fask and followup 5', 'task', 95, '2025-11-18', 'completed', '2025-11-19 05:41:27', '2025-11-18 09:59:53', '2025-11-19 05:41:27'),
(10, 6, NULL, 'Followup And task interconnection test 1', 'Followup And task interconnection test 1', 'task', 104, '2025-11-20', 'cancelled', NULL, '2025-11-19 05:57:30', '2025-11-22 08:11:51'),
(11, 5, NULL, 'Followup And task interconnection test 1', 'Followup And task interconnection test 1', 'task', 105, '2025-11-19', 'completed', '2025-11-19 06:38:12', '2025-11-19 06:01:54', '2025-11-19 06:38:12'),
(12, 6, 37, 'Test task And followup user to admin 1', 'Test task And followup user to admin 1', 'task', 106, '2025-11-19', 'cancelled', NULL, '2025-11-19 11:56:38', '2025-11-21 06:06:45'),
(13, 6, 48, 'Follow-up:  Test task -Followup (Planner also) 16 - Admin to user 21/11/25', ' Test task -Follow-up (Planner also) 16 - Admin to user 21/11/25', 'task', 134, '2025-11-24', 'postponed', NULL, '2025-11-21 06:09:34', '2025-11-22 08:27:28'),
(14, 6, 37, 'TESTING ERGON Project', 'TESTING ERGON Project', 'standalone', NULL, '2025-11-22', 'pending', NULL, '2025-11-22 12:44:53', '2025-11-22 12:44:53'),
(17, 1, 37, 'TESTING SAP Project', 'TESTING SAP Project', 'standalone', NULL, '2025-11-22', 'pending', NULL, '2025-11-22 12:50:43', '2025-11-22 12:50:43'),
(19, 6, 37, 'Follow-up: Test Task -planner 27 admin myself 24 nov', 'Test Task -planner 27 admin myself 24 nov', 'task', 148, '2025-11-24', 'pending', NULL, '2025-11-24 05:44:14', '2025-11-24 05:44:14'),
(20, 6, 37, 'Follow-up: Test Task -planner 27 admin myself 24 nov', 'Test Task -planner 27 admin myself 24 nov', 'task', 149, '2025-11-24', 'pending', NULL, '2025-11-24 05:55:09', '2025-11-24 05:55:09'),
(21, 6, 37, 'Follow-up:  Test Task -planner 27 admin myself 24 nov', ' Test Task -planner 27 admin myself 24 nov', 'task', 150, '2025-11-24', 'pending', NULL, '2025-11-24 06:13:08', '2025-11-24 06:13:08'),
(22, 6, 37, 'Follow-up:  Test Task -planner 28 admin myself 24 nov', ' Test Task -planner 28 admin myself 24 nov', 'task', 151, '2025-11-24', 'pending', NULL, '2025-11-24 06:14:25', '2025-11-24 06:14:25'),
(23, 6, 37, 'Follow-up: Test Task -planner 28 admin myself 24 nov', 'Test Task -planner 28 admin myself 24 nov', 'task', 152, '2025-11-25', 'pending', NULL, '2025-11-24 06:24:05', '2025-11-24 06:24:05'),
(24, 6, 37, 'Follow-up: Test Task -planner 29 admin myself 25 nov', 'Test Task -planner 29 admin myself 25 nov', 'task', 153, '2025-11-25', 'pending', NULL, '2025-11-24 06:30:23', '2025-11-24 06:30:23'),
(25, 6, 37, 'Follow-up: Test Task -planner 26 admin myself 24 nov', 'Test Task -planner 26 admin myself 24 nov', 'task', 154, '2025-11-24', 'pending', NULL, '2025-11-24 07:09:19', '2025-11-24 07:09:19'),
(26, 6, 37, 'Follow-up: Test Task -planner 26 admin myself 24 nov', 'Test Task -planner 26 admin myself 24 nov', 'task', 155, '2025-11-24', 'pending', NULL, '2025-11-24 07:25:27', '2025-11-24 07:25:27'),
(27, 6, 37, 'Follow-up: Test Task - Planner 1-Admin Myself (Nov24)', 'Test Task - Planner 1-Admin Myself (Nov24)', 'task', 199, '2025-11-24', 'pending', NULL, '2025-11-24 07:52:24', '2025-11-24 07:52:24'),
(28, 6, 37, 'Follow-up: Test Task - Planner 2-Admin Myself (Nov24)', 'Test Task - Planner 2-Admin Myself (Nov24)', 'task', 200, '2025-11-24', 'pending', NULL, '2025-11-24 07:57:54', '2025-11-24 07:57:54'),
(29, 6, 37, 'Follow-up: Test Task - Planner 7-Admin Myself (Nov25)', 'Test Task - Planner 7-Admin Myself (Nov25)', 'task', 208, '2025-11-25', 'pending', NULL, '2025-11-25 03:56:02', '2025-11-25 03:56:02'),
(30, 6, 37, 'Follow-up: Test Task - Planner 8-Admin Myself (Nov26)', 'Test Task - Planner 8-Admin Myself (Nov26)', 'task', 209, '2025-11-26', 'pending', NULL, '2025-11-25 03:57:31', '2025-11-25 03:57:31'),
(31, 6, 37, 'Follow-up: Test - task & Planner 7 Admin Myself (Nov 25)', 'Test - task & Planner 7 Admin Myself (Nov 25)', 'task', 210, '2025-11-25', 'pending', NULL, '2025-11-25 04:27:10', '2025-11-25 04:27:10'),
(32, 6, 37, 'Follow-up: Test - task & Planner 8 Admin Myself (Nov 25)', 'Test - task & Planner 8 Admin Myself (Nov 25)', 'task', 211, '2025-11-25', 'pending', NULL, '2025-11-25 04:27:59', '2025-11-25 04:27:59'),
(33, 8, 1, 'Followup testing 1(nov 28)', 'Followup testing 1(nov 28)', 'standalone', NULL, '2025-11-28', 'pending', NULL, '2025-11-28 09:23:38', '2025-11-28 09:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `followup_history`
--

CREATE TABLE `followup_history` (
  `id` int NOT NULL,
  `followup_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followup_history`
--

INSERT INTO `followup_history` (`id`, `followup_id`, `action`, `old_value`, `notes`, `created_by`, `created_at`) VALUES
(2, 3, 'rescheduled', '2025-11-22', 'Rescheduled from 2025-11-22 to 2025-11-17. Reason: ', 1, '2025-11-17 13:27:11'),
(3, 3, 'rescheduled', '2025-11-17', 'Rescheduled from 2025-11-17 to 2025-11-19. Reason: Rescheduling', 1, '2025-11-17 13:27:24'),
(4, 3, 'rescheduled', '2025-11-19', 'Rescheduled from 2025-11-19 to 2025-11-20. Reason: Rescheduling', 1, '2025-11-18 05:02:40'),
(5, 1, 'cancelled', 'postponed', 'Follow-up cancelled. Reason: Testing cancel functionality', 1, '2025-11-18 05:06:44'),
(6, 3, 'cancelled', 'postponed', 'Follow-up cancelled. Reason: cancel this followup', 1, '2025-11-18 05:07:45'),
(7, 4, 'rescheduled', '2025-11-18', 'Rescheduled from 2025-11-18 to 2025-11-19. Reason: ', 1, '2025-11-18 05:07:59'),
(8, 4, 'rescheduled', '2025-11-19', 'Rescheduled from 2025-11-19 to 2025-11-20. Reason: the Followup date has been changed', 1, '2025-11-18 05:08:37'),
(10, 5, 'completed', 'pending', 'Follow-up completed', 1, '2025-11-19 05:40:44'),
(11, 9, 'completed', 'pending', 'Follow-up completed', 1, '2025-11-19 05:41:27'),
(12, 11, 'completed', 'pending', 'Follow-up completed', 48, '2025-11-19 06:38:12'),
(13, 12, 'cancelled', 'pending', 'Follow-up cancelled. Reason: Cancellation', 37, '2025-11-21 06:06:45'),
(14, 4, 'rescheduled', '2025-11-20', 'Rescheduled from 2025-11-20 to 2025-11-21. Reason: Rescheduling this followup', 37, '2025-11-21 06:07:11'),
(15, 13, 'rescheduled', '2025-11-22', 'Rescheduled from 2025-11-22 to 2025-11-23. Reason: ', 48, '2025-11-21 06:10:26'),
(16, 4, 'completed', 'postponed', 'Follow-up completed', 37, '2025-11-21 06:24:05'),
(17, 13, 'status_changed', 'postponed', 'Status updated from linked task completion', 37, '2025-11-21 06:34:15'),
(18, 10, 'cancelled', 'pending', 'Follow-up cancelled. Reason: Cancell', 37, '2025-11-22 08:11:51'),
(19, 13, 'rescheduled', '2025-11-23', 'Rescheduled from 2025-11-23 to 2025-11-24. Reason: ', 37, '2025-11-22 08:27:28'),
(20, 14, 'created', NULL, 'Follow-up created', 37, '2025-11-22 12:44:53'),
(21, 15, 'created', NULL, 'Follow-up created', 37, '2025-11-22 12:45:29'),
(22, 16, 'created', NULL, 'Follow-up created', 37, '2025-11-22 12:49:16'),
(23, 17, 'created', NULL, 'Follow-up created', 37, '2025-11-22 12:50:43'),
(24, 33, 'created', NULL, 'Follow-up created', 1, '2025-11-28 09:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int NOT NULL,
  `entry_date` date NOT NULL,
  `description` text,
  `total_amount` decimal(15,2) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `reference_type`, `reference_id`, `entry_date`, `description`, `total_amount`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'expense', 57, '2025-11-20', 'Team Dinner', 300.00, 37, '2025-11-20 10:51:19', '2025-11-20 10:51:19'),
(2, 'expense', 58, '2025-11-20', 'Bus travel for meet the client', 300.00, 37, '2025-11-20 11:00:35', '2025-11-20 11:00:35'),
(3, 'expense', 59, '2025-11-20', 'lunch meals', 300.00, 1, '2025-11-20 11:00:50', '2025-11-20 11:00:50'),
(4, 'expense', 27, '2025-11-22', 'Lunch Meals', 500.00, 1, '2025-11-22 11:10:29', '2025-11-22 11:10:29'),
(5, 'expense', 22, '2025-11-22', '...........', 200.00, 37, '2025-11-22 11:10:51', '2025-11-22 11:10:51'),
(6, 'expense', 33, '2025-11-27', '................', 100.00, 1, '2025-11-27 12:56:51', '2025-11-27 12:56:51'),
(7, 'expense', 11, '2025-11-27', 'Bus Travel', 700.00, 1, '2025-11-27 12:58:50', '2025-11-27 12:58:50'),
(8, 'expense', 106, '2025-11-28', 'Bus fare to Meet client', 325.00, 1, '2025-11-28 12:25:00', '2025-11-28 12:25:00'),
(9, 'expense', 60, '2025-11-28', 'Bus Travel', 500.00, 37, '2025-11-28 12:47:50', '2025-11-28 12:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int NOT NULL,
  `journal_entry_id` int NOT NULL,
  `account_id` int NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `account_id`, `debit_amount`, `credit_amount`, `description`, `created_at`) VALUES
(1, 1, 1, 300.00, 0.00, 'Expense: Team Dinner', '2025-11-20 10:51:19'),
(2, 1, 5, 0.00, 300.00, 'Payable: Team Dinner', '2025-11-20 10:51:19'),
(3, 2, 2, 300.00, 0.00, 'Expense: Bus travel for meet the client', '2025-11-20 11:00:35'),
(4, 2, 5, 0.00, 300.00, 'Payable: Bus travel for meet the client', '2025-11-20 11:00:35'),
(5, 3, 1, 300.00, 0.00, 'Expense: lunch meals', '2025-11-20 11:00:50'),
(6, 3, 5, 0.00, 300.00, 'Payable: lunch meals', '2025-11-20 11:00:50'),
(7, 4, 1, 500.00, 0.00, 'Expense: Lunch Meals', '2025-11-22 11:10:29'),
(8, 4, 5, 0.00, 500.00, 'Payable: Lunch Meals', '2025-11-22 11:10:29'),
(9, 5, 1, 200.00, 0.00, 'Expense: ...........', '2025-11-22 11:10:51'),
(10, 5, 5, 0.00, 200.00, 'Payable: ...........', '2025-11-22 11:10:51'),
(11, 6, 1, 100.00, 0.00, 'Expense: ................', '2025-11-27 12:56:51'),
(12, 6, 5, 0.00, 100.00, 'Payable: ................', '2025-11-27 12:56:51'),
(13, 7, 2, 700.00, 0.00, 'Expense: Bus Travel', '2025-11-27 12:58:50'),
(14, 7, 5, 0.00, 700.00, 'Payable: Bus Travel', '2025-11-27 12:58:50'),
(15, 8, 2, 325.00, 0.00, 'Expense: Bus fare to Meet client', '2025-11-28 12:25:00'),
(16, 8, 5, 0.00, 325.00, 'Payable: Bus fare to Meet client', '2025-11-28 12:25:00'),
(17, 9, 2, 500.00, 0.00, 'Expense: Bus Travel', '2025-11-28 12:47:50'),
(18, 9, 5, 0.00, 500.00, 'Payable: Bus Travel', '2025-11-28 12:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int DEFAULT NULL,
  `days_requested` int NOT NULL,
  `reason` text,
  `contact_during_leave` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `days_requested`, `reason`, `contact_during_leave`, `status`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(8, 37, 'emergency', '2025-11-01', '2025-11-04', 4, 4, 'Medical Emergency', NULL, 'Pending', '2025-10-31 11:16:00', '2025-10-31 11:16:00', NULL, NULL, NULL),
(13, 16, 'casual', '2025-11-04', '2025-11-04', 1, 1, 'Casual Leave', NULL, 'Rejected', '2025-11-03 13:06:06', '2025-11-08 10:32:53', NULL, NULL, 'Reject'),
(15, 37, 'emergency', '2025-11-09', '2025-11-11', 1, 3, 'Emergency Leave', '9857451522', 'Pending', '2025-11-08 09:40:56', '2025-11-27 13:30:46', NULL, NULL, NULL),
(19, 1, 'sick', '2025-11-11', '2025-11-11', 1, 1, 'Fever And Cold', NULL, 'Pending', '2025-11-10 13:47:02', '2025-11-10 13:47:02', NULL, NULL, NULL),
(30, 37, 'sick', '2025-11-20', '2025-11-21', 1, 2, 'Sick leave', NULL, 'Rejected', '2025-11-11 06:05:20', '2025-11-20 10:22:18', NULL, NULL, 'Reject This Leave'),
(42, 49, 'casual', '2025-11-20', '2025-11-20', 1, 1, 'Festival Leave', NULL, 'Approved', '2025-11-20 12:31:15', '2025-11-20 12:31:59', NULL, NULL, NULL),
(45, 47, 'casual', '2025-11-21', '2025-11-21', 1, 1, 'Personal Leave', NULL, 'Approved', '2025-11-21 09:19:30', '2025-11-21 09:19:49', NULL, NULL, NULL),
(46, 37, 'casual', '2025-11-23', '2025-11-23', 1, 1, 'Reason Leave', '9563557841', 'Pending', '2025-11-22 11:49:31', '2025-11-27 13:30:34', NULL, NULL, NULL),
(47, 48, 'casual', '2025-11-27', '2025-11-30', 4, 4, 'Kodaikanal tour', NULL, 'Approved', '2025-11-27 10:20:14', '2025-11-28 14:01:22', NULL, NULL, NULL),
(49, 37, 'casual', '2025-11-28', '2025-11-28', 1, 1, 'personal leave', '9715557845', 'Pending', '2025-11-27 13:31:09', '2025-11-27 13:31:09', NULL, NULL, NULL),
(50, 47, 'sick', '2025-11-28', '2025-11-28', 1, 1, 'Fever & rest', '9715557845', 'Pending', '2025-11-28 11:19:01', '2025-11-28 11:19:32', NULL, NULL, NULL),
(51, 49, 'annual', '2025-11-28', '2025-11-28', 1, 1, 'Family vacation', '9876543210', 'Rejected', '2025-11-28 11:20:32', '2025-11-28 14:09:01', NULL, NULL, 'Reject this leave'),
(52, 49, 'emergency', '2025-11-28', '2025-11-28', 1, 1, 'Medical Emergency', '9876543210', 'Pending', '2025-11-28 13:04:24', '2025-11-28 13:04:24', NULL, NULL, NULL),
(56, 49, 'casual', '2025-11-28', '2025-11-28', 1, 1, 'medical leave', '9856475123', 'Pending', '2025-11-28 13:13:44', '2025-11-28 13:13:44', NULL, NULL, NULL),
(57, 49, 'annual', '2025-11-28', '2025-11-28', 1, 1, 'Festival leave', '98765 43210', 'Pending', '2025-11-28 13:15:20', '2025-11-28 13:15:20', NULL, NULL, NULL),
(58, 49, 'sick', '2025-11-28', '2025-11-28', 1, 1, 'Heavy fever &amp; Headache', '9856475123', 'Approved', '2025-11-28 13:16:57', '2025-11-28 14:01:31', NULL, NULL, NULL),
(59, 49, 'casual', '2025-11-28', '2025-11-28', 1, 1, 'personal work', '9856475123', 'Rejected', '2025-11-28 14:04:06', '2025-11-28 14:04:55', NULL, NULL, 'reject this leave because today conducting the client meeting');

--
-- Triggers `leaves`
--
DELIMITER $$
CREATE TRIGGER `calculate_leave_days` BEFORE INSERT ON `leaves` FOR EACH ROW SET NEW.total_days = DATEDIFF(NEW.end_date, NEW.start_date) + 1
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int DEFAULT '1',
  `last_attempt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `receiver_id` int NOT NULL,
  `module_name` varchar(50) DEFAULT 'system',
  `action_type` varchar(50) DEFAULT 'info',
  `message` text NOT NULL,
  `reference_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('info','success','warning','error','urgent') DEFAULT 'info',
  `category` enum('task','approval','system','reminder','announcement') DEFAULT 'system',
  `action_url` varchar(500) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `priority` tinyint(1) DEFAULT '1',
  `module_type` enum('leave','expense','advance','task','system') DEFAULT 'system',
  `status_change` enum('pending','approved','rejected','assigned','completed') DEFAULT NULL,
  `approver_id` int DEFAULT NULL,
  `reminder_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `is_batched` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `sender_id`, `receiver_id`, `module_name`, `action_type`, `message`, `reference_id`, `is_read`, `created_at`, `type`, `category`, `action_url`, `reference_type`, `priority`, `module_type`, `status_change`, `approver_id`, `reminder_date`, `title`, `is_batched`, `read_at`) VALUES
(1003, 49, 1, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $450.00', 105, 1, '2025-11-28 12:20:16', 'info', 'approval', '/ergon/expenses/view/105', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, '2025-11-28 12:52:38'),
(1004, 49, 37, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $450.00', 105, 0, '2025-11-28 12:20:16', 'info', 'approval', '/ergon/expenses/view/105', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1005, 49, 47, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $450.00', 105, 0, '2025-11-28 12:20:16', 'info', 'approval', '/ergon/expenses/view/105', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1006, 49, 50, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $450.00', 105, 0, '2025-11-28 12:20:16', 'info', 'approval', '/ergon/expenses/view/105', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1007, 49, 51, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $450.00', 105, 0, '2025-11-28 12:20:16', 'info', 'approval', '/ergon/expenses/view/105', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1010, 49, 1, 'system', 'info', 'Advance request for $5000.00 - Personal need', 23, 0, '2025-11-28 12:21:35', 'info', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'pending', NULL, NULL, 'New Advance Request from Joel', 0, NULL),
(1011, 49, 37, 'system', 'info', 'Advance request for $5000.00 - Personal need', 23, 0, '2025-11-28 12:21:35', 'info', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'pending', NULL, NULL, 'New Advance Request from Joel', 0, NULL),
(1012, 49, 47, 'system', 'info', 'Advance request for $5000.00 - Personal need', 23, 0, '2025-11-28 12:21:35', 'info', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'pending', NULL, NULL, 'New Advance Request from Joel', 0, NULL),
(1013, 49, 50, 'system', 'info', 'Advance request for $5000.00 - Personal need', 23, 0, '2025-11-28 12:21:35', 'info', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'pending', NULL, NULL, 'New Advance Request from Joel', 0, NULL),
(1014, 49, 51, 'system', 'info', 'Advance request for $5000.00 - Personal need', 23, 0, '2025-11-28 12:21:35', 'info', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'pending', NULL, NULL, 'New Advance Request from Joel', 0, NULL),
(1017, 49, 1, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $325.00', 106, 1, '2025-11-28 12:24:04', 'info', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, '2025-11-28 12:52:50'),
(1018, 49, 37, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1019, 49, 47, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1020, 49, 50, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1021, 49, 51, 'system', 'info', 'Expense request for Bus fare to Meet client - Amount: $325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'pending', NULL, NULL, 'New Expense Request from Joel', 0, NULL),
(1024, 49, 1, 'system', 'info', 'Expense claim from Joel - ₹325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', NULL, 'expense', 1, 'system', NULL, NULL, NULL, 'New Expense Claim', 0, NULL),
(1025, 49, 37, 'system', 'info', 'Expense claim from Joel - ₹325.00', 106, 0, '2025-11-28 12:24:04', 'info', 'approval', NULL, 'expense', 1, 'system', NULL, NULL, NULL, 'New Expense Claim', 0, NULL),
(1026, 49, 47, 'system', 'info', 'Expense claim from Joel - ₹325.00', 0, 0, '2025-11-28 12:24:04', 'info', 'approval', NULL, 'expense', 1, 'system', NULL, NULL, NULL, 'New Expense Claim', 0, NULL),
(1027, 49, 50, 'system', 'info', 'Expense claim from Joel - ₹325.00', 0, 0, '2025-11-28 12:24:04', 'info', 'approval', NULL, 'expense', 1, 'system', NULL, NULL, NULL, 'New Expense Claim', 0, NULL),
(1028, 49, 51, 'system', 'info', 'Expense claim from Joel - ₹325.00', 0, 0, '2025-11-28 12:24:04', 'info', 'approval', NULL, 'expense', 1, 'system', NULL, NULL, NULL, 'New Expense Claim', 0, NULL),
(1029, 1, 49, 'system', 'info', 'Your expense request has been approved - Amount: $325.00', 106, 1, '2025-11-28 12:25:00', 'success', 'approval', '/ergon/expenses/view/106', 'expense', 1, 'expense', 'approved', 1, NULL, 'Expense Request APPROVED', 0, '2025-11-28 12:31:37'),
(1030, 1, 49, 'system', 'info', 'You have been assigned a new task: Finish Q1 Sales Report', 227, 0, '2025-11-28 12:43:47', 'info', 'system', NULL, 'tasks', 1, 'system', NULL, NULL, NULL, 'Tasks Assigned', 0, NULL),
(1031, 37, 48, 'system', 'info', 'Your expense request has been approved - Amount: $500.00', 60, 0, '2025-11-28 12:47:50', 'success', 'approval', '/ergon/expenses/view/60', 'expense', 1, 'expense', 'approved', 37, NULL, 'Expense Request APPROVED', 0, NULL),
(1032, 49, 1, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 52, 0, '2025-11-28 13:04:24', 'info', 'approval', '/ergon/leaves/view/52', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1033, 49, 37, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 52, 0, '2025-11-28 13:04:24', 'info', 'approval', '/ergon/leaves/view/52', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1034, 49, 47, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 52, 0, '2025-11-28 13:04:24', 'info', 'approval', '/ergon/leaves/view/52', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1035, 49, 50, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 52, 0, '2025-11-28 13:04:24', 'info', 'approval', '/ergon/leaves/view/52', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1036, 49, 51, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 52, 0, '2025-11-28 13:04:24', 'info', 'approval', '/ergon/leaves/view/52', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1039, 49, 1, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 53, 0, '2025-11-28 13:05:46', 'info', 'approval', '/ergon/leaves/view/53', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1040, 49, 37, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 53, 0, '2025-11-28 13:05:46', 'info', 'approval', '/ergon/leaves/view/53', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1041, 49, 47, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 53, 0, '2025-11-28 13:05:46', 'info', 'approval', '/ergon/leaves/view/53', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1042, 49, 50, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 53, 0, '2025-11-28 13:05:46', 'info', 'approval', '/ergon/leaves/view/53', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1043, 49, 51, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 53, 0, '2025-11-28 13:05:46', 'info', 'approval', '/ergon/leaves/view/53', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1046, 49, 1, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 54, 0, '2025-11-28 13:06:09', 'info', 'approval', '/ergon/leaves/view/54', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1047, 49, 37, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 54, 0, '2025-11-28 13:06:09', 'info', 'approval', '/ergon/leaves/view/54', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1048, 49, 47, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 54, 0, '2025-11-28 13:06:09', 'info', 'approval', '/ergon/leaves/view/54', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1049, 49, 50, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 54, 0, '2025-11-28 13:06:09', 'info', 'approval', '/ergon/leaves/view/54', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1050, 49, 51, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 54, 0, '2025-11-28 13:06:09', 'info', 'approval', '/ergon/leaves/view/54', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1053, 49, 1, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 55, 1, '2025-11-28 13:12:15', 'info', 'approval', '/ergon/leaves/view/55', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, '2025-11-28 13:17:27'),
(1054, 49, 37, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 55, 0, '2025-11-28 13:12:15', 'info', 'approval', '/ergon/leaves/view/55', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1055, 49, 47, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 55, 0, '2025-11-28 13:12:15', 'info', 'approval', '/ergon/leaves/view/55', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1056, 49, 50, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 55, 0, '2025-11-28 13:12:15', 'info', 'approval', '/ergon/leaves/view/55', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1057, 49, 51, 'system', 'info', 'Leave request for emergency from 2025-11-28 to 2025-11-28', 55, 0, '2025-11-28 13:12:15', 'info', 'approval', '/ergon/leaves/view/55', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1060, 49, 1, 'system', 'info', 'Leave request for casual from 2025-11-28 to 2025-11-28', 56, 1, '2025-11-28 13:13:44', 'info', 'approval', '/ergon/leaves/view/56', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, '2025-11-28 13:17:27'),
(1061, 49, 37, 'system', 'info', 'Leave request for casual from 2025-11-28 to 2025-11-28', 56, 0, '2025-11-28 13:13:44', 'info', 'approval', '/ergon/leaves/view/56', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1062, 49, 47, 'system', 'info', 'Leave request for casual from 2025-11-28 to 2025-11-28', 56, 0, '2025-11-28 13:13:44', 'info', 'approval', '/ergon/leaves/view/56', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1063, 49, 50, 'system', 'info', 'Leave request for casual from 2025-11-28 to 2025-11-28', 56, 0, '2025-11-28 13:13:44', 'info', 'approval', '/ergon/leaves/view/56', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1064, 49, 51, 'system', 'info', 'Leave request for casual from 2025-11-28 to 2025-11-28', 56, 0, '2025-11-28 13:13:44', 'info', 'approval', '/ergon/leaves/view/56', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1067, 49, 1, 'system', 'info', 'Leave request for annual from 2025-11-28 to 2025-11-28', 57, 0, '2025-11-28 13:15:20', 'info', 'approval', '/ergon/leaves/view/57', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1068, 49, 37, 'system', 'info', 'Leave request for annual from 2025-11-28 to 2025-11-28', 57, 0, '2025-11-28 13:15:20', 'info', 'approval', '/ergon/leaves/view/57', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1069, 49, 47, 'system', 'info', 'Leave request for annual from 2025-11-28 to 2025-11-28', 57, 0, '2025-11-28 13:15:20', 'info', 'approval', '/ergon/leaves/view/57', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1070, 49, 50, 'system', 'info', 'Leave request for annual from 2025-11-28 to 2025-11-28', 57, 0, '2025-11-28 13:15:20', 'info', 'approval', '/ergon/leaves/view/57', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1071, 49, 51, 'system', 'info', 'Leave request for annual from 2025-11-28 to 2025-11-28', 57, 0, '2025-11-28 13:15:20', 'info', 'approval', '/ergon/leaves/view/57', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1074, 49, 1, 'system', 'info', 'Leave request for sick from 2025-11-28 to 2025-11-28', 58, 0, '2025-11-28 13:16:57', 'info', 'approval', '/ergon/leaves/view/58', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1075, 49, 37, 'system', 'info', 'Leave request for sick from 2025-11-28 to 2025-11-28', 58, 0, '2025-11-28 13:16:57', 'info', 'approval', '/ergon/leaves/view/58', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1076, 49, 47, 'system', 'info', 'Leave request for sick from 2025-11-28 to 2025-11-28', 58, 0, '2025-11-28 13:16:57', 'info', 'approval', '/ergon/leaves/view/58', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1077, 49, 50, 'system', 'info', 'Leave request for sick from 2025-11-28 to 2025-11-28', 58, 0, '2025-11-28 13:16:57', 'info', 'approval', '/ergon/leaves/view/58', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1078, 49, 51, 'system', 'info', 'Leave request for sick from 2025-11-28 to 2025-11-28', 58, 0, '2025-11-28 13:16:57', 'info', 'approval', '/ergon/leaves/view/58', 'leave', 1, 'leave', 'pending', NULL, NULL, 'New Leave Request from Joel', 0, NULL),
(1081, 37, 49, 'system', 'info', 'Your leave request has been rejected. Reason: reject this leave because today conducting the client meeting', 59, 0, '2025-11-28 14:04:55', 'warning', 'approval', NULL, 'leave', 1, 'system', NULL, NULL, NULL, 'Leave Rejected', 0, NULL),
(1082, 37, 49, 'system', 'info', 'Your leave request has been rejected. Reason: Reject this leave', 51, 0, '2025-11-28 14:09:01', 'warning', 'approval', NULL, 'leave', 1, 'system', NULL, NULL, NULL, 'Leave Rejected', 0, NULL),
(1083, 37, 49, 'system', 'info', 'Your advance request has been approved - Amount: $5000.00', 23, 0, '2025-11-28 14:46:22', 'success', 'approval', '/ergon/advances/view/23', 'advance', 1, 'advance', 'approved', 37, NULL, 'Advance Request APPROVED', 0, NULL),
(1084, NULL, 49, 'system', 'info', 'Your advance request has been rejected - Amount: $1500.00', 21, 0, '2025-11-28 14:46:55', 'warning', 'approval', '/ergon/advances/view/21', 'advance', 1, 'advance', 'rejected', NULL, NULL, 'Advance Request REJECTED', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_queue`
--

CREATE TABLE `notification_queue` (
  `id` int NOT NULL,
  `event_data` json NOT NULL,
  `priority` int DEFAULT '2',
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `status` enum('active','completed','on_hold','cancelled','withheld','rejected') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `department_id`, `status`, `created_at`) VALUES
(5, 'Athens', 'QHSE & Sustainability Software', 14, 'active', '2025-10-26 21:46:24'),
(6, 'SAP', 'ERP Software', NULL, 'active', '2025-10-26 21:46:51'),
(7, 'TU14', 'Liaison Work', 5, 'active', '2025-10-26 21:47:19');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit_log`
--

CREATE TABLE `rate_limit_log` (
  `id` int NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rate_limit_log`
--

INSERT INTO `rate_limit_log` (`id`, `identifier`, `action`, `attempted_at`, `success`, `ip_address`) VALUES
(1, '127.0.0.1', 'login', '2025-11-19 10:11:31', 1, '127.0.0.1'),
(2, '::1', 'login', '2025-11-19 10:21:10', 1, '::1'),
(3, '::1', 'login', '2025-11-19 10:22:01', 1, '::1'),
(4, '::1', 'login', '2025-11-19 10:22:31', 1, '::1'),
(5, '127.0.0.1', 'login', '2025-11-19 10:22:44', 1, '127.0.0.1'),
(6, '::1', 'login', '2025-11-19 10:22:56', 1, '::1'),
(7, '127.0.0.1', 'login', '2025-11-19 10:26:25', 1, '127.0.0.1'),
(8, '::1', 'login', '2025-11-19 10:26:31', 1, '::1'),
(9, '::1', 'login', '2025-11-19 10:39:29', 1, '::1'),
(10, '::1', 'login', '2025-11-19 12:36:04', 1, '::1'),
(11, '::1', 'login', '2025-11-19 14:07:09', 1, '::1'),
(12, '::1', 'login', '2025-11-20 05:54:34', 1, '::1'),
(13, '::1', 'login', '2025-11-20 05:55:03', 1, '::1'),
(14, '127.0.0.1', 'login', '2025-11-20 05:55:36', 1, '127.0.0.1'),
(15, '127.0.0.1', 'login', '2025-11-20 07:14:08', 1, '127.0.0.1'),
(16, '::1', 'login', '2025-11-20 08:31:11', 1, '::1'),
(17, '127.0.0.1', 'login', '2025-11-20 09:29:45', 0, '127.0.0.1'),
(18, '127.0.0.1', 'login', '2025-11-20 09:29:49', 0, '127.0.0.1'),
(19, '127.0.0.1', 'login', '2025-11-20 09:30:03', 0, '127.0.0.1'),
(20, '127.0.0.1', 'login', '2025-11-20 09:30:26', 1, '127.0.0.1'),
(21, '::1', 'login', '2025-11-20 10:00:39', 1, '::1'),
(22, '::1', 'login', '2025-11-20 10:15:10', 1, '::1'),
(23, '::1', 'login', '2025-11-20 10:16:33', 1, '::1'),
(24, '::1', 'login', '2025-11-20 10:17:03', 1, '::1'),
(25, '::1', 'login', '2025-11-20 10:55:08', 1, '::1'),
(26, '::1', 'login', '2025-11-20 10:55:50', 1, '::1'),
(27, '::1', 'login', '2025-11-20 10:57:11', 0, '::1'),
(28, '::1', 'login', '2025-11-20 10:57:29', 0, '::1'),
(29, '::1', 'login', '2025-11-20 10:57:45', 1, '::1'),
(30, '127.0.0.1', 'login', '2025-11-20 10:58:42', 1, '127.0.0.1'),
(31, '::1', 'login', '2025-11-20 11:00:12', 1, '::1'),
(32, '127.0.0.1', 'login', '2025-11-20 11:20:43', 1, '127.0.0.1'),
(33, '127.0.0.1', 'login', '2025-11-20 11:34:02', 1, '127.0.0.1'),
(34, '127.0.0.1', 'login', '2025-11-20 11:37:44', 1, '127.0.0.1'),
(35, '127.0.0.1', 'login', '2025-11-20 11:38:10', 1, '127.0.0.1'),
(36, '::1', 'login', '2025-11-20 11:38:35', 1, '::1'),
(37, '127.0.0.1', 'login', '2025-11-20 11:46:23', 1, '127.0.0.1'),
(38, '::1', 'login', '2025-11-20 12:00:39', 1, '::1'),
(39, '::1', 'login', '2025-11-20 12:33:44', 1, '::1'),
(40, '127.0.0.1', 'login', '2025-11-20 13:05:40', 1, '127.0.0.1'),
(41, '127.0.0.1', 'login', '2025-11-21 03:46:32', 1, '127.0.0.1'),
(42, '::1', 'login', '2025-11-21 03:46:42', 1, '::1'),
(43, '::1', 'login', '2025-11-21 04:48:13', 1, '::1'),
(44, '127.0.0.1', 'login', '2025-11-21 05:13:09', 1, '127.0.0.1'),
(45, '::1', 'login', '2025-11-21 05:17:25', 1, '::1'),
(46, '::1', 'login', '2025-11-21 05:26:18', 1, '::1'),
(47, '127.0.0.1', 'login', '2025-11-21 05:52:42', 1, '127.0.0.1'),
(48, '::1', 'login', '2025-11-21 06:46:21', 1, '::1'),
(49, '::1', 'login', '2025-11-21 06:46:35', 1, '::1'),
(50, '127.0.0.1', 'login', '2025-11-21 06:46:56', 1, '127.0.0.1'),
(51, '127.0.0.1', 'login', '2025-11-21 07:43:34', 1, '127.0.0.1'),
(52, '127.0.0.1', 'login', '2025-11-21 07:47:42', 1, '127.0.0.1'),
(53, '::1', 'login', '2025-11-21 07:54:38', 1, '::1'),
(54, '::1', 'login', '2025-11-21 07:55:01', 1, '::1'),
(55, '127.0.0.1', 'login', '2025-11-21 07:55:17', 1, '127.0.0.1'),
(56, '127.0.0.1', 'login', '2025-11-21 07:55:46', 1, '127.0.0.1'),
(57, '::1', 'login', '2025-11-21 08:54:34', 1, '::1'),
(58, '::1', 'login', '2025-11-21 09:17:50', 1, '::1'),
(59, '::1', 'login', '2025-11-21 09:20:47', 1, '::1'),
(60, '::1', 'login', '2025-11-21 09:43:09', 1, '::1'),
(61, '::1', 'login', '2025-11-21 09:43:40', 1, '::1'),
(62, '::1', 'login', '2025-11-21 09:56:23', 1, '::1'),
(63, '::1', 'login', '2025-11-21 10:48:44', 1, '::1'),
(64, '::1', 'login', '2025-11-21 11:05:26', 1, '::1'),
(65, '127.0.0.1', 'login', '2025-11-21 12:38:24', 1, '127.0.0.1'),
(66, '127.0.0.1', 'login', '2025-11-21 13:01:14', 1, '127.0.0.1'),
(67, '::1', 'login', '2025-11-22 04:31:22', 1, '::1'),
(68, '::1', 'login', '2025-11-22 04:31:33', 0, '::1'),
(69, '::1', 'login', '2025-11-22 04:31:34', 0, '::1'),
(70, '::1', 'login', '2025-11-22 04:31:37', 1, '::1'),
(71, '127.0.0.1', 'login', '2025-11-22 08:00:42', 1, '127.0.0.1'),
(72, '127.0.0.1', 'login', '2025-11-22 08:30:37', 0, '127.0.0.1'),
(73, '127.0.0.1', 'login', '2025-11-22 08:31:10', 0, '127.0.0.1'),
(74, '127.0.0.1', 'login', '2025-11-22 08:32:07', 0, '127.0.0.1'),
(75, '127.0.0.1', 'login', '2025-11-22 08:55:13', 1, '127.0.0.1'),
(76, '127.0.0.1', 'login', '2025-11-22 09:21:35', 1, '127.0.0.1'),
(77, '::1', 'login', '2025-11-22 09:22:05', 1, '::1'),
(78, '::1', 'login', '2025-11-22 10:21:59', 1, '::1'),
(79, '::1', 'login', '2025-11-22 10:22:56', 1, '::1'),
(80, '127.0.0.1', 'login', '2025-11-22 10:41:26', 1, '127.0.0.1'),
(81, '127.0.0.1', 'login', '2025-11-22 10:47:11', 1, '127.0.0.1'),
(82, '127.0.0.1', 'login', '2025-11-22 11:13:59', 1, '127.0.0.1'),
(83, '::1', 'login', '2025-11-24 04:49:21', 1, '::1'),
(84, '::1', 'login', '2025-11-24 08:12:53', 1, '::1'),
(85, '::1', 'login', '2025-11-24 09:34:56', 1, '::1'),
(86, '127.0.0.1', 'login', '2025-11-24 12:09:17', 1, '127.0.0.1'),
(87, '::1', 'login', '2025-11-25 03:54:16', 1, '::1'),
(88, '127.0.0.1', 'login', '2025-11-25 03:54:22', 1, '127.0.0.1'),
(89, '::1', 'login', '2025-11-25 03:54:42', 1, '::1'),
(90, '127.0.0.1', 'login', '2025-11-25 04:41:52', 1, '127.0.0.1'),
(91, '::1', 'login', '2025-11-26 06:44:23', 1, '::1'),
(92, '127.0.0.1', 'login', '2025-11-26 09:10:17', 1, '127.0.0.1'),
(93, '127.0.0.1', 'login', '2025-11-27 09:19:06', 1, '127.0.0.1'),
(94, '::1', 'login', '2025-11-27 09:42:34', 1, '::1'),
(95, '::1', 'login', '2025-11-27 10:47:09', 1, '::1'),
(96, '::1', 'login', '2025-11-28 06:30:11', 1, '::1'),
(97, '::1', 'login', '2025-11-28 08:16:47', 1, '::1'),
(98, '127.0.0.1', 'login', '2025-11-28 08:21:22', 1, '127.0.0.1'),
(99, '127.0.0.1', 'login', '2025-11-28 08:29:02', 1, '127.0.0.1'),
(100, '127.0.0.1', 'login', '2025-11-28 08:29:12', 1, '127.0.0.1'),
(101, '127.0.0.1', 'login', '2025-11-28 08:29:56', 0, '127.0.0.1'),
(102, '127.0.0.1', 'login', '2025-11-28 08:30:08', 1, '127.0.0.1'),
(103, '127.0.0.1', 'login', '2025-11-28 08:30:46', 1, '127.0.0.1'),
(104, '127.0.0.1', 'login', '2025-11-28 08:47:12', 1, '127.0.0.1'),
(105, '127.0.0.1', 'login', '2025-11-28 08:48:09', 0, '127.0.0.1'),
(106, '127.0.0.1', 'login', '2025-11-28 08:48:16', 1, '127.0.0.1'),
(107, '127.0.0.1', 'login', '2025-11-28 09:07:46', 1, '127.0.0.1'),
(108, '::1', 'login', '2025-11-28 11:17:27', 1, '::1'),
(109, '::1', 'login', '2025-11-28 11:17:32', 0, '::1'),
(110, '::1', 'login', '2025-11-28 11:17:45', 1, '::1'),
(111, '::1', 'login', '2025-11-28 11:42:46', 1, '::1'),
(112, '127.0.0.1', 'login', '2025-11-28 12:18:08', 1, '127.0.0.1'),
(113, '::1', 'login', '2025-11-28 12:18:20', 1, '::1'),
(114, '::1', 'login', '2025-11-28 12:18:43', 1, '::1'),
(115, '127.0.0.1', 'login', '2025-11-28 12:47:38', 1, '127.0.0.1'),
(116, '127.0.0.1', 'login', '2025-11-28 12:48:38', 1, '127.0.0.1'),
(117, '127.0.0.1', 'login', '2025-11-28 13:19:18', 1, '127.0.0.1'),
(118, '127.0.0.1', 'login', '2025-11-28 14:05:51', 1, '127.0.0.1'),
(119, '127.0.0.1', 'login', '2025-11-28 14:09:14', 1, '127.0.0.1');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `request_uri` varchar(500) DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `user_id`, `event_type`, `event_description`, `ip_address`, `user_agent`, `request_uri`, `additional_data`, `created_at`) VALUES
(1, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:17:20'),
(2, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:24:08'),
(3, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:28:00'),
(4, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:29:48'),
(5, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:29:55'),
(6, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:30:05'),
(7, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:34:07'),
(8, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:35:25'),
(9, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:37:25'),
(10, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:37:32'),
(11, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:38:21'),
(12, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:39:49'),
(13, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:39:54'),
(14, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:40:57'),
(15, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:41:02'),
(16, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:41:58'),
(17, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:42:02'),
(18, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:24'),
(19, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(20, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(21, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(22, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(23, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(24, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(25, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(26, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:45:30'),
(27, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '/ergon/login', '{\"success\": true, \"user_id\": 1}', '2025-10-25 09:49:40');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `company_name` varchar(255) DEFAULT 'ERGON Company',
  `company_email` varchar(255) DEFAULT '',
  `company_phone` varchar(20) DEFAULT '',
  `company_address` text,
  `working_hours_start` time DEFAULT '09:00:00',
  `working_hours_end` time DEFAULT '18:00:00',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `base_location_lat` decimal(10,8) DEFAULT '0.00000000',
  `base_location_lng` decimal(11,8) DEFAULT '0.00000000',
  `attendance_radius` int DEFAULT '200',
  `office_address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `company_email`, `company_phone`, `company_address`, `working_hours_start`, `working_hours_end`, `timezone`, `base_location_lat`, `base_location_lng`, `attendance_radius`, `office_address`, `created_at`, `updated_at`) VALUES
(1, 'Athena Solutions', '', '', NULL, '09:30:00', '19:00:00', 'Asia/Kolkata', 9.95321500, 78.12721200, 400, 'madurai', '2025-10-30 09:48:31', '2025-11-28 13:18:42');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period` int DEFAULT '15' COMMENT 'Grace period in minutes',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `grace_period`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Morning Shift', '09:00:00', '18:00:00', 15, 1, '2025-11-01 12:14:28', '2025-11-01 12:14:28'),
(2, 'Night Shift', '22:00:00', '06:00:00', 15, 1, '2025-11-01 12:14:28', '2025-11-01 12:14:28'),
(3, 'Flexible', '00:00:00', '23:59:59', 30, 1, '2025-11-01 12:14:28', '2025-11-01 12:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `sla_history`
--

CREATE TABLE `sla_history` (
  `id` int NOT NULL,
  `daily_task_id` int NOT NULL,
  `action` varchar(20) NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `duration_seconds` int DEFAULT '0',
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sla_history`
--

INSERT INTO `sla_history` (`id`, `daily_task_id`, `action`, `timestamp`, `duration_seconds`, `notes`) VALUES
(1, 707, 'start', '2025-11-18 06:40:20', 0, 'Task started'),
(2, 735, 'start', '2025-11-18 06:40:26', 0, 'Task started'),
(3, 744, 'start', '2025-11-18 06:40:31', 0, 'Task started'),
(4, 758, 'start', '2025-11-18 06:40:32', 0, 'Task started'),
(5, 772, 'start', '2025-11-18 06:40:40', 0, 'Task started'),
(6, 772, 'pause', '2025-11-18 06:40:45', 5, 'Task paused'),
(7, 772, 'resume', '2025-11-18 06:40:48', 3, 'Task resumed'),
(8, 772, 'pause', '2025-11-18 06:40:49', 1, 'Task paused'),
(9, 772, 'resume', '2025-11-18 06:40:50', 1, 'Task resumed'),
(10, 786, 'start', '2025-11-18 06:41:50', 0, 'Task started'),
(11, 786, 'pause', '2025-11-18 06:42:28', 38, 'Task paused'),
(12, 786, 'resume', '2025-11-18 06:42:29', 1, 'Task resumed'),
(13, 800, 'start', '2025-11-18 06:42:32', 0, 'Task started'),
(14, 800, 'pause', '2025-11-18 06:42:38', 6, 'Task paused'),
(15, 800, 'resume', '2025-11-18 06:42:39', 1, 'Task resumed'),
(16, 800, 'pause', '2025-11-18 06:42:40', 1, 'Task paused'),
(17, 801, 'start', '2025-11-18 06:42:42', 0, 'Task started'),
(18, 814, 'start', '2025-11-18 06:42:49', 0, 'Task started'),
(19, 814, 'pause', '2025-11-18 06:42:53', 4, 'Task paused'),
(20, 815, 'start', '2025-11-18 06:42:55', 0, 'Task started'),
(21, 828, 'start', '2025-11-18 06:43:24', 0, 'Task started'),
(22, 829, 'start', '2025-11-18 06:43:39', 0, 'Task started'),
(23, 884, 'start', '2025-11-18 06:51:49', 0, 'Task started'),
(24, 982, 'start', '2025-11-18 06:54:45', 0, 'Task started'),
(25, 996, 'start', '2025-11-18 06:54:49', 0, 'Task started'),
(26, 1024, 'start', '2025-11-18 06:54:51', 0, 'Task started'),
(27, 1024, 'pause', '2025-11-18 06:54:51', 0, 'Task paused'),
(28, 1024, 'resume', '2025-11-18 06:54:52', 1, 'Task resumed'),
(29, 1024, 'pause', '2025-11-18 06:54:53', 1, 'Task paused'),
(30, 1024, 'resume', '2025-11-18 06:54:54', 1, 'Task resumed'),
(31, 1024, 'pause', '2025-11-18 06:54:54', 0, 'Task paused'),
(32, 1024, 'resume', '2025-11-18 06:54:55', 1, 'Task resumed'),
(33, 1094, 'start', '2025-11-18 07:01:24', 0, 'Task started'),
(34, 1108, 'start', '2025-11-18 07:06:01', 0, 'Task started'),
(35, 1108, 'pause', '2025-11-18 07:06:09', 8, 'Task paused'),
(36, 1206, 'start', '2025-11-18 07:26:58', 0, 'Task started'),
(37, 1192, 'start', '2025-11-18 07:34:08', 0, 'Task started'),
(38, 1192, 'pause', '2025-11-18 07:34:18', 10, 'Task paused'),
(39, 1192, 'resume', '2025-11-18 07:35:12', 54, 'Task resumed'),
(40, 1198, 'start', '2025-11-18 07:35:29', 0, 'Task started'),
(41, 1192, 'progress_updated', '2025-11-18 13:33:50', 0, 'Progress updated from 0% to 45%. Progress updated via modal'),
(42, 1192, 'pause', '2025-11-18 08:04:01', 1729, 'Task paused'),
(43, 1192, 'progress_updated', '2025-11-18 13:34:05', 0, 'Progress updated from 45% to 58%. Progress updated via modal'),
(44, 1192, 'progress_updated', '2025-11-18 13:34:13', 0, 'Progress updated from 58% to 100%. Progress updated via modal'),
(45, 1198, 'progress_updated', '2025-11-18 13:34:28', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(46, 1193, 'start', '2025-11-18 08:16:25', 0, 'Task started'),
(47, 1193, 'pause', '2025-11-18 08:16:32', 7, 'Task paused'),
(48, 1193, 'progress_updated', '2025-11-18 13:46:37', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(49, 1217, 'start', '2025-11-18 23:23:20', 0, 'Task started'),
(50, 1217, 'progress_updated', '2025-11-19 05:00:09', 0, 'Progress updated from 0% to 27%. Progress updated via modal'),
(51, 1219, 'pause', '2025-11-18 23:53:36', 69, 'Task paused'),
(52, 1219, 'resume', '2025-11-19 00:02:26', 530, 'Task resumed'),
(53, 1221, 'pause', '2025-11-19 00:02:31', 1, 'Task paused'),
(54, 1221, 'resume', '2025-11-19 00:02:32', 1, 'Task resumed'),
(55, 1221, 'pause', '2025-11-19 00:02:42', 10, 'Task paused'),
(56, 1219, 'progress_updated', '2025-11-19 05:33:19', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(57, 1233, 'start', '2025-11-19 01:36:19', 0, 'Task started'),
(58, 1233, 'pause', '2025-11-19 01:36:21', 2, 'Task paused'),
(59, 1234, 'start', '2025-11-19 01:42:28', 0, 'Task started'),
(60, 1237, 'start', '2025-11-19 01:52:50', 0, 'Task started'),
(61, 1237, 'progress_updated', '2025-11-19 07:22:53', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(62, 1222, 'start', '2025-11-19 02:01:24', 0, 'Task started'),
(63, 1222, 'progress_updated', '2025-11-19 07:31:29', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(64, 1238, 'start', '2025-11-19 02:08:52', 0, 'Task started'),
(65, 1269, 'start', '2025-11-19 02:21:29', 0, 'Task started'),
(66, 1369, 'start', '2025-11-19 02:47:28', 0, 'Task started'),
(67, 1369, 'pause', '2025-11-19 02:47:33', 5, 'Task paused'),
(68, 1369, 'resume', '2025-11-19 02:47:34', 1, 'Task resumed'),
(69, 1369, 'pause', '2025-11-19 02:47:35', 1, 'Task paused'),
(70, 1207, 'start', '2025-11-19 02:53:23', 0, 'Task started'),
(71, 1208, 'start', '2025-11-19 02:53:24', 0, 'Task started'),
(72, 1207, 'progress_updated', '2025-11-19 08:23:31', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(73, 1271, 'start', '2025-11-19 02:55:13', 0, 'Task started'),
(74, 1271, 'progress_updated', '2025-11-19 08:25:16', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(75, 1272, 'start', '2025-11-19 05:03:50', 0, 'Task started'),
(76, 1369, 'resume', '2025-11-19 06:09:54', 12139, 'Task resumed'),
(77, 1376, 'start', '2025-11-19 06:35:23', 0, 'Task started'),
(78, 1378, 'start', '2025-11-19 06:35:33', 0, 'Task started'),
(79, 1380, 'start', '2025-11-19 06:35:34', 0, 'Task started'),
(80, 1382, 'start', '2025-11-19 06:35:36', 0, 'Task started'),
(81, 1384, 'start', '2025-11-19 06:35:37', 0, 'Task started'),
(82, 1386, 'start', '2025-11-19 06:35:40', 0, 'Task started'),
(83, 1390, 'start', '2025-11-19 06:35:54', 0, 'Task started'),
(84, 1392, 'pause', '2025-11-19 06:41:22', 328, 'Task paused'),
(85, 1394, 'resume', '2025-11-19 06:41:28', 0, 'Task resumed'),
(86, 1395, 'start', '2025-11-19 06:41:39', 0, 'Task started'),
(87, 1398, 'pause', '2025-11-19 06:41:43', 349, 'Task paused'),
(88, 1398, 'resume', '2025-11-19 06:41:48', 5, 'Task resumed'),
(89, 1410, 'start', '2025-11-19 07:23:43', 0, 'Task started'),
(90, 1413, 'pause', '2025-11-19 07:24:58', 75, 'Task paused'),
(91, 1413, 'resume', '2025-11-19 07:27:17', 139, 'Task resumed'),
(92, 1445, 'start', '2025-11-19 08:02:12', 0, 'Task started'),
(93, 1456, 'pause', '2025-11-19 08:25:12', 6213, 'Task paused'),
(94, 1456, 'progress_updated', '2025-11-19 13:55:16', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(95, 1456, 'progress_updated', '2025-11-19 13:55:26', 0, 'Progress updated from 100% to 100%. Progress updated via modal'),
(96, 1457, 'progress_updated', '2025-11-19 13:55:46', 0, 'Progress updated from 0% to 100%. Progress updated via modal'),
(97, 1457, 'progress_updated', '2025-11-19 13:55:54', 0, 'Progress updated from 100% to 100%. Progress updated via modal'),
(98, 1458, 'pause', '2025-11-19 08:31:46', 4083, 'Task paused'),
(99, 1458, 'resume', '2025-11-19 08:32:54', 68, 'Task resumed'),
(100, 1458, 'completed', '2025-11-19 14:03:06', 0, 'Task completed with 100% progress. Progress updated via modal'),
(101, 1469, 'start', '2025-11-20 00:29:00', 0, 'Task started'),
(102, 1884, 'start', '2025-11-20 03:27:17', 0, 'Task started'),
(103, 1885, 'start', '2025-11-20 03:27:25', 0, 'Task started'),
(104, 1884, 'completed', '2025-11-20 08:57:34', 0, 'Task completed with 100% progress. Progress updated via modal'),
(105, 1885, 'pause', '2025-11-20 03:29:44', 139, 'Task paused'),
(106, 1885, 'resume', '2025-11-20 03:31:00', 76, 'Task resumed'),
(107, 1885, 'pause', '2025-11-20 03:31:48', 48, 'Task paused'),
(108, 1885, 'resume', '2025-11-20 03:54:29', 1361, 'Task resumed'),
(109, 1885, 'progress_updated', '2025-11-20 09:24:41', 0, 'Progress updated from 0% to 49%. Progress updated via modal'),
(110, 1885, 'progress_updated', '2025-11-20 09:24:51', 0, 'Progress updated from 49% to 29%. Progress updated via modal'),
(111, 1885, 'progress_updated', '2025-11-20 09:25:04', 0, 'Progress updated from 29% to 50%. Progress updated via modal'),
(112, 1902, 'start', '2025-11-20 04:22:39', 0, 'Task started'),
(113, 1902, 'completed', '2025-11-20 09:52:41', 0, 'Task completed with 100% progress. Progress updated via modal'),
(114, 1908, 'start', '2025-11-20 04:28:14', 0, 'Task started'),
(115, 1908, 'completed', '2025-11-20 09:58:17', 0, 'Task completed with 100% progress. Progress updated via modal'),
(116, 1916, 'start', '2025-11-20 04:30:45', 0, 'Task started'),
(117, 1916, 'completed', '2025-11-20 10:00:51', 0, 'Task completed with 100% progress. Progress updated via modal'),
(118, 1921, 'start', '2025-11-20 04:42:22', 0, 'Task started'),
(119, 1921, 'completed', '2025-11-20 10:12:26', 0, 'Task completed with 100% progress. Progress updated via modal'),
(120, 1926, 'start', '2025-11-20 04:43:33', 0, 'Task started'),
(121, 1926, 'completed', '2025-11-20 10:13:38', 0, 'Task completed with 100% progress. Progress updated via modal'),
(122, 1929, 'start', '2025-11-20 04:45:16', 0, 'Task started'),
(123, 1929, 'completed', '2025-11-20 10:15:20', 0, 'Task completed with 100% progress. Progress updated via modal');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text,
  `assigned_by` int NOT NULL,
  `assigned_to` int NOT NULL,
  `task_type` enum('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `deadline` datetime DEFAULT NULL,
  `progress` int DEFAULT '0',
  `status` enum('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `depends_on_task_id` int DEFAULT NULL,
  `sla_hours` decimal(8,4) DEFAULT '0.2500',
  `sla_hours_part` int DEFAULT '0',
  `sla_minutes_part` int DEFAULT '15',
  `overall_progress` int DEFAULT '0',
  `total_time_spent` decimal(6,2) DEFAULT '0.00',
  `estimated_hours` decimal(4,2) DEFAULT '0.00',
  `last_progress_update` timestamp NULL DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `reminder_time` time DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT '0',
  `followup_required` tinyint(1) DEFAULT '0',
  `planned_date` date DEFAULT NULL,
  `estimated_duration` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT 'regular',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `actual_time_seconds` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_by`, `assigned_to`, `task_type`, `priority`, `deadline`, `progress`, `status`, `due_date`, `created_at`, `updated_at`, `depends_on_task_id`, `sla_hours`, `sla_hours_part`, `sla_minutes_part`, `overall_progress`, `total_time_spent`, `estimated_hours`, `last_progress_update`, `department_id`, `task_category`, `company_name`, `contact_person`, `contact_phone`, `project_name`, `follow_up_date`, `reminder_time`, `reminder_sent`, `followup_required`, `planned_date`, `estimated_duration`, `project_id`, `type`, `assigned_at`, `actual_time_seconds`) VALUES
(58, 'Test Followup task 4', 'Test Followup task 4', 1, 37, 'ad-hoc', 'medium', NULL, 13, 'in_progress', NULL, '2025-11-18 05:38:05', '2025-11-24 14:12:04', NULL, 2.0000, 0, 15, 0, 0.00, 0.00, NULL, 6, 'Follow-up', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-18', NULL, 7, 'regular', '2025-11-18 05:38:05', 0),
(104, 'Followup And task interconnection test 1', 'Followup And task interconnection test 1', 1, 48, 'ad-hoc', 'medium', '2025-11-19 00:00:00', 100, 'completed', NULL, '2025-11-19 05:57:30', '2025-11-19 13:55:26', NULL, 1.0000, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Testing', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, 5, 'regular', NULL, 0),
(105, 'Followup And task interconnection test 2', 'Followup And task interconnection test 2', 48, 48, 'ad-hoc', 'medium', '2025-11-19 00:00:00', 100, 'completed', NULL, '2025-11-19 06:01:54', '2025-11-19 06:38:12', NULL, 24.0000, 0, 15, 0, 0.00, 0.00, NULL, 6, 'Follow-up', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, 7, 'regular', NULL, 0),
(113, 'Test Pending Task', NULL, 1, 1, 'ad-hoc', 'medium', NULL, 0, 'assigned', NULL, '2025-11-20 06:36:39', '2025-11-20 06:37:19', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-20', NULL, NULL, 'regular', NULL, 0),
(199, 'Test Task - Planner 1-Admin Myself (Nov24)', 'Test Task - Planner 1-Admin Myself (Nov24)', 37, 37, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 100, 'completed', NULL, '2025-11-24 07:52:24', '2025-11-24 09:42:09', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Network Management', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-24', NULL, 5, 'regular', NULL, 0),
(200, 'Test Task - Planner 2-Admin Myself (Nov24)', 'Test Task - Planner 2-Admin Myself (Nov24)', 37, 37, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 16, 'assigned', NULL, '2025-11-24 07:57:54', '2025-11-27 11:08:24', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Planning', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-24', NULL, 5, 'regular', NULL, 0),
(201, 'Test Task - 13:41:46', 'This is a test task created by the fix script', 1, 1, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 12, 'in_progress', NULL, '2025-11-24 08:11:46', '2025-11-25 04:00:34', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 13, 'Ledger Follow-up', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-24', NULL, NULL, 'regular', NULL, 0),
(202, 'Test Task - Planner 3-Admin Myself (Nov25)', 'Test Task - Planner 3-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 40, 'assigned', NULL, '2025-11-24 08:25:54', '2025-11-27 11:08:05', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Maintenance', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(203, 'Future Task - Created Today', 'This task is created today but planned for future date', 1, 1, 'ad-hoc', 'medium', NULL, 0, 'assigned', NULL, '2025-11-24 08:33:57', '2025-11-24 08:33:57', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-27', NULL, NULL, 'regular', NULL, 0),
(204, 'Test Task - Planner 4-Admin Myself (Nov24)', 'Test Task - Planner 4-Admin Myself (Nov24)', 37, 37, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 0, 'assigned', NULL, '2025-11-24 09:08:32', '2025-11-24 09:08:32', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Planning', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-24', NULL, 5, 'regular', NULL, 0),
(205, 'Test Task - Planner 5 -Admin Myself (Nov24)', 'Test Task - Planner 5 -Admin Myself (Nov24)', 37, 37, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 13, 'in_progress', NULL, '2025-11-24 09:19:01', '2025-11-25 06:23:40', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Performance Monitoring', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-24', NULL, 5, 'regular', NULL, 0),
(207, 'Test Task - Planner 6 -Admin Myself (Nov24)', 'Test Task - Planner 6 -Admin Myself (Nov24)', 37, 37, 'ad-hoc', 'medium', '2025-11-24 00:00:00', 0, 'assigned', NULL, '2025-11-24 11:53:08', '2025-11-24 11:53:08', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Network Management', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-24', NULL, 5, 'regular', NULL, 0),
(208, 'Test Task - Planner 7-Admin Myself (Nov25)', 'Test Task - Planner 7-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 10, 'assigned', NULL, '2025-11-25 03:56:02', '2025-11-27 12:07:32', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Network Management', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(215, 'Test - task & Planner 9 Admin Myself (Nov 25)', 'Test - task & Planner 9 Admin Myself (Nov 25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 15, 'in_progress', NULL, '2025-11-25 07:24:45', '2025-11-25 09:04:48', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Maintenance', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(216, 'Test Task - Planner 10-Admin Myself (Nov25)', 'Test Task - Planner 10-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 12, 'in_progress', NULL, '2025-11-25 08:09:14', '2025-11-25 11:30:30', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 14, 'Network Management', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(221, 'Test Task - Planner 11-Admin Myself (Nov25)', 'Test Task - Planner 11-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 0, 'assigned', NULL, '2025-11-25 11:11:13', '2025-11-25 11:11:13', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 1, 'Policy Development', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(222, 'Test Task - Planner 12-Admin Myself (Nov25)', 'Test Task - Planner 12-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 0, 'assigned', NULL, '2025-11-25 11:35:22', '2025-11-25 11:35:22', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 6, 'Follow-up', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(224, 'Test Task - Planner 13-Admin Myself (Nov25)', 'Test Task - Planner 13-Admin Myself (Nov25)', 37, 37, 'ad-hoc', 'medium', '2025-11-25 00:00:00', 0, 'assigned', NULL, '2025-11-25 11:59:16', '2025-11-25 11:59:16', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 13, 'Invoice Processing', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-25', NULL, 5, 'regular', NULL, 0),
(225, 'due date test', 'due date test', 1, 48, 'ad-hoc', 'medium', '2025-11-27 00:00:00', 0, 'assigned', NULL, '2025-11-27 12:40:18', '2025-11-27 13:21:34', NULL, 0.2833, 0, 15, 0, 0.00, 0.00, NULL, 1, 'Performance Review', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-27', NULL, 5, 'regular', NULL, 0),
(226, 'Finish Q1 Sales Report', 'Compile sales data, revenue charts, and insights for board meeting.\r\nInclude expenses vs revenue comparison.', 1, 49, 'ad-hoc', 'medium', '2025-11-28 00:00:00', 0, 'assigned', NULL, '2025-11-28 12:42:42', '2025-11-28 12:42:42', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 5, 'Vendor Management', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-28', NULL, 7, 'regular', NULL, 0),
(227, 'Finish Q1 Sales Report', 'Compile sales data, revenue charts, and insights for board meeting.\r\nInclude expenses vs revenue comparison.', 1, 49, 'ad-hoc', 'medium', '2025-11-28 00:00:00', 0, 'assigned', NULL, '2025-11-28 12:43:47', '2025-11-28 12:43:47', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 5, 'Quality Control', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, '2025-11-28', NULL, 7, 'regular', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_categories`
--

CREATE TABLE `task_categories` (
  `id` int NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task_categories`
--

INSERT INTO `task_categories` (`id`, `department_name`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(19, 'Liaison', 'Document Collection', 'Gathering required documents from clients', 1, '2025-10-26 21:44:23'),
(20, 'Liaison', 'Portal Upload', 'Uploading details in government portals', 1, '2025-10-26 21:44:23'),
(21, 'Liaison', 'Documentation', 'Document preparation and verification', 1, '2025-10-26 21:44:23'),
(22, 'Liaison', 'Follow-up', 'Client and government office follow-ups', 1, '2025-10-26 21:44:23'),
(23, 'Liaison', 'Document Submission', 'Physical document submission', 1, '2025-10-26 21:44:23'),
(24, 'Liaison', 'Courier Services', 'Document dispatch and delivery', 1, '2025-10-26 21:44:23'),
(25, 'Liaison', 'Client Meeting', 'Client consultation and meetings', 1, '2025-10-26 21:44:23'),
(26, 'Liaison', 'Government Office Visit', 'Official visits and submissions', 1, '2025-10-26 21:44:23'),
(27, 'Statutory', 'ESI Work', 'Employee State Insurance related tasks', 1, '2025-10-26 21:44:23'),
(28, 'Statutory', 'EPF Work', 'Employee Provident Fund activities', 1, '2025-10-26 21:44:23'),
(29, 'Statutory', 'Mail Checking', 'Official correspondence review', 1, '2025-10-26 21:44:23'),
(30, 'Statutory', 'Document Preparation', 'Statutory document creation', 1, '2025-10-26 21:44:23'),
(31, 'Statutory', 'Fees Payment', 'Government fees and charges payment', 1, '2025-10-26 21:44:23'),
(32, 'Statutory', 'Attendance Collection', 'Employee attendance compilation', 1, '2025-10-26 21:44:23'),
(33, 'Statutory', 'Compliance Filing', 'Regulatory compliance submissions', 1, '2025-10-26 21:44:23'),
(34, 'Statutory', 'Audit Support', 'Audit documentation and support', 1, '2025-10-26 21:44:23'),
(43, 'Virtual Office', 'Call Handling', 'Professional call answering service', 1, '2025-10-26 21:44:23'),
(44, 'Virtual Office', 'Mail Management', 'Physical mail handling and forwarding', 1, '2025-10-26 21:44:23'),
(45, 'Virtual Office', 'Address Services', 'Business address and registration', 1, '2025-10-26 21:44:23'),
(46, 'Virtual Office', 'Meeting Coordination', 'Virtual meeting setup and management', 1, '2025-10-26 21:44:23'),
(47, 'Virtual Office', 'Reception Services', 'Virtual reception and customer service', 1, '2025-10-26 21:44:23'),
(48, 'Virtual Office', 'Document Scanning', 'Physical document digitization', 1, '2025-10-26 21:44:23'),
(49, 'Virtual Office', 'Appointment Scheduling', 'Calendar and appointment management', 1, '2025-10-26 21:44:23'),
(50, 'Virtual Office', 'Administrative Support', 'General administrative assistance', 1, '2025-10-26 21:44:23'),
(57, 'Human Resources', 'Recruitment', 'Hiring and recruitment activities', 1, '2025-10-27 09:22:00'),
(58, 'Human Resources', 'Training', 'Employee training and development', 1, '2025-10-27 09:22:00'),
(59, 'Human Resources', 'Performance Review', 'Employee performance evaluations', 1, '2025-10-27 09:22:00'),
(60, 'Human Resources', 'Policy Development', 'HR policy creation and updates', 1, '2025-10-27 09:22:00'),
(61, 'Human Resources', 'Employee Relations', 'Managing employee relations and issues', 1, '2025-10-27 09:22:00'),
(62, 'Human Resources', 'Compliance', 'HR compliance and regulatory tasks', 1, '2025-10-27 09:22:00'),
(76, 'Operations', 'Process Improvement', 'Improving operational processes', 1, '2025-10-27 09:22:00'),
(77, 'Operations', 'Quality Control', 'Quality assurance and control', 1, '2025-10-27 09:22:00'),
(78, 'Operations', 'Vendor Management', 'Managing vendor relationships', 1, '2025-10-27 09:22:00'),
(79, 'Operations', 'Inventory Management', 'Managing inventory and supplies', 1, '2025-10-27 09:22:00'),
(80, 'Operations', 'Logistics', 'Logistics and supply chain management', 1, '2025-10-27 09:22:00'),
(81, 'Operations', 'Facility Management', 'Managing office facilities', 1, '2025-10-27 09:22:00'),
(82, 'Finance & Accounts', 'Ledger Update', 'General ledger maintenance and updates', 1, '2025-10-27 09:35:18'),
(83, 'Finance & Accounts', 'Invoice Creation', 'Customer invoice generation', 1, '2025-10-27 09:35:18'),
(84, 'Finance & Accounts', 'Quotation Creation', 'Price quotation preparation', 1, '2025-10-27 09:35:18'),
(85, 'Finance & Accounts', 'PO Creation', 'Purchase order generation', 1, '2025-10-27 09:35:18'),
(86, 'Finance & Accounts', 'PO Follow-up', 'Purchase order tracking and follow-up', 1, '2025-10-27 09:35:18'),
(87, 'Finance & Accounts', 'Payment Follow-up', 'Outstanding payment collection', 1, '2025-10-27 09:35:18'),
(88, 'Finance & Accounts', 'Ledger Follow-up', 'Account reconciliation and follow-up', 1, '2025-10-27 09:35:18'),
(89, 'Finance & Accounts', 'GST Follow-up', 'GST compliance and filing', 1, '2025-10-27 09:35:18'),
(90, 'Finance & Accounts', 'Mail Checking', 'Email correspondence and communication', 1, '2025-10-27 09:35:18'),
(91, 'Finance & Accounts', 'Financial Reporting', 'Monthly and quarterly reports', 1, '2025-10-27 09:35:18'),
(92, 'Finance & Accounts', 'Accounting', 'General accounting and bookkeeping', 1, '2025-10-27 09:35:18'),
(93, 'Finance & Accounts', 'Budgeting', 'Budget planning and management', 1, '2025-10-27 09:35:18'),
(94, 'Finance & Accounts', 'Financial Analysis', 'Financial data analysis and reporting', 1, '2025-10-27 09:35:18'),
(95, 'Finance & Accounts', 'Audit', 'Internal and external audit activities', 1, '2025-10-27 09:35:18'),
(96, 'Finance & Accounts', 'Tax Planning', 'Tax preparation and planning', 1, '2025-10-27 09:35:18'),
(97, 'Finance & Accounts', 'Invoice Processing', 'Processing invoices and payments', 1, '2025-10-27 09:35:18'),
(98, 'Information Technology', 'Development', 'Software development and coding tasks', 1, '2025-10-27 09:35:18'),
(99, 'Information Technology', 'Testing', 'Quality assurance and testing activities', 1, '2025-10-27 09:35:18'),
(100, 'Information Technology', 'Bug Fixing', 'Error resolution and debugging', 1, '2025-10-27 09:35:18'),
(101, 'Information Technology', 'Planning', 'Project planning and architecture', 1, '2025-10-27 09:35:18'),
(102, 'Information Technology', 'Hosting', 'Server management and deployment', 1, '2025-10-27 09:35:18'),
(103, 'Information Technology', 'Maintenance', 'System maintenance and updates', 1, '2025-10-27 09:35:18'),
(104, 'Information Technology', 'Documentation', 'Technical documentation and guides', 1, '2025-10-27 09:35:18'),
(105, 'Information Technology', 'Code Review', 'Peer code review and quality checks', 1, '2025-10-27 09:35:18'),
(106, 'Information Technology', 'Deployment', 'Application deployment and release', 1, '2025-10-27 09:35:18'),
(107, 'Marketing & Sales', 'Campaign Planning', 'Marketing campaign strategy and planning', 1, '2025-10-27 09:35:18'),
(108, 'Marketing & Sales', 'Content Creation', 'Marketing content and material creation', 1, '2025-10-27 09:35:18'),
(109, 'Marketing & Sales', 'Social Media Management', 'Social media posts and engagement', 1, '2025-10-27 09:35:18'),
(110, 'Marketing & Sales', 'Lead Generation', 'Prospecting and lead identification', 1, '2025-10-27 09:35:18'),
(111, 'Marketing & Sales', 'Client Presentation', 'Sales presentations and proposals', 1, '2025-10-27 09:35:18'),
(112, 'Marketing & Sales', 'Market Research', 'Industry and competitor analysis', 1, '2025-10-27 09:35:18'),
(113, 'Marketing & Sales', 'Event Planning', 'Marketing events and webinars', 1, '2025-10-27 09:35:18'),
(114, 'Marketing & Sales', 'Email Marketing', 'Email campaigns and newsletters', 1, '2025-10-27 09:35:18'),
(115, 'Marketing & Sales', 'Client Meeting', 'Meeting with clients and prospects', 1, '2025-10-27 09:35:18'),
(116, 'Marketing & Sales', 'Proposal Writing', 'Creating sales proposals and quotes', 1, '2025-10-27 09:35:18'),
(117, 'Marketing & Sales', 'Customer Support', 'Supporting existing customers', 1, '2025-10-27 09:35:18'),
(118, 'Finance & Accounts', 'Bank Reconciliation', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(119, 'Finance & Accounts', 'Expense Tracking', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(120, 'Finance & Accounts', 'Petty Cash Management', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(121, 'Finance & Accounts', 'Vendor Payment', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(122, 'Finance & Accounts', 'Customer Payment Processing', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(123, 'Finance & Accounts', 'Cash Flow Management', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(124, 'Finance & Accounts', 'Investment Analysis', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(125, 'Finance & Accounts', 'Cost Analysis', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(126, 'Finance & Accounts', 'Profit & Loss Review', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(127, 'Finance & Accounts', 'Balance Sheet Preparation', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(128, 'Finance & Accounts', 'GST Filing', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(129, 'Finance & Accounts', 'TDS Processing', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(130, 'Finance & Accounts', 'Loan Management', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(131, 'Finance & Accounts', 'Asset Management', 'Comprehensive task category for Finance & Accounts department', 1, '2025-10-27 09:35:18'),
(132, 'Information Technology', 'System Analysis', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(133, 'Information Technology', 'Database Design', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(134, 'Information Technology', 'API Development', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(135, 'Information Technology', 'Frontend Development', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(136, 'Information Technology', 'Backend Development', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(137, 'Information Technology', 'DevOps', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(138, 'Information Technology', 'Cloud Management', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(139, 'Information Technology', 'Security Implementation', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(140, 'Information Technology', 'System Administration', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(141, 'Information Technology', 'Database Management', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(142, 'Information Technology', 'Security Updates', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(143, 'Information Technology', 'Backup Management', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(144, 'Information Technology', 'Network Management', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(145, 'Information Technology', 'User Support', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(146, 'Information Technology', 'Software Installation', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(147, 'Information Technology', 'Hardware Maintenance', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(148, 'Information Technology', 'Performance Monitoring', 'Comprehensive task category for Information Technology department', 1, '2025-10-27 09:35:18'),
(149, 'Marketing & Sales', 'Brand Management', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(150, 'Marketing & Sales', 'Digital Marketing', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(151, 'Marketing & Sales', 'SEO/SEM', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(152, 'Marketing & Sales', 'Public Relations', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(153, 'Marketing & Sales', 'Customer Surveys', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(154, 'Marketing & Sales', 'Competitor Analysis', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(155, 'Marketing & Sales', 'Product Promotion', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(156, 'Marketing & Sales', 'Sales Presentation', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(157, 'Marketing & Sales', 'Deal Negotiation', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(158, 'Marketing & Sales', 'Customer Onboarding', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(159, 'Marketing & Sales', 'Account Management', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(160, 'Marketing & Sales', 'Sales Reporting', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(161, 'Marketing & Sales', 'CRM Management', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(162, 'Marketing & Sales', 'Territory Management', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(163, 'Marketing & Sales', 'Product Demo', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18'),
(164, 'Marketing & Sales', 'Contract Management', 'Comprehensive task category for Marketing & Sales department', 1, '2025-10-27 09:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `task_history`
--

CREATE TABLE `task_history` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text,
  `new_value` text,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task_history`
--

INSERT INTO `task_history` (`id`, `task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1, 57, 'status_changed', 'assigned', 'in_progress', '', 1, '2025-11-18 05:21:01'),
(2, 57, 'progress_updated', '0%', '33%', '', 1, '2025-11-18 05:21:01'),
(3, 58, 'status_changed', 'assigned', 'in_progress', '', 1, '2025-11-18 05:52:45'),
(4, 58, 'progress_updated', '0%', '10%', '', 1, '2025-11-18 05:52:45'),
(5, 58, 'progress_updated', '10%', '15%', '', 1, '2025-11-18 05:52:50'),
(6, 58, 'progress_updated', '15%', '16%', '', 1, '2025-11-18 05:52:57'),
(7, 58, 'progress_updated', '16%', '75%', '', 1, '2025-11-18 05:59:34'),
(8, 58, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-18 06:00:28'),
(9, 59, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-18 06:02:33'),
(10, 60, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 06:03:43'),
(11, 86, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 06:17:00'),
(12, 87, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-18 06:28:43'),
(13, 87, 'status_changed', 'assigned', 'in_progress', '', 37, '2025-11-18 06:32:55'),
(14, 87, 'progress_updated', '0%', '16%', '', 37, '2025-11-18 06:32:55'),
(15, 87, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-18 08:03:34'),
(16, 94, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 09:13:54'),
(17, 95, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 09:59:53'),
(18, 96, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-18 10:07:11'),
(19, 97, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 10:33:19'),
(20, 98, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 10:36:31'),
(21, 98, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-18 10:37:30'),
(22, 99, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-18 10:57:11'),
(23, 99, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-18 10:57:22'),
(24, 99, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-18 10:57:40'),
(25, 100, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-18 11:09:09'),
(26, 101, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 12:03:25'),
(27, 102, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-18 12:56:54'),
(28, 103, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-18 13:05:06'),
(29, 102, 'status_changed', 'assigned', 'in_progress', '', 1, '2025-11-18 13:26:18'),
(30, 102, 'progress_updated', '0%', '10%', '', 1, '2025-11-18 13:26:18'),
(31, 102, 'status_changed', 'in_progress', 'assigned', '', 1, '2025-11-18 13:26:28'),
(32, 102, 'progress_updated', '10%', '0%', '', 1, '2025-11-18 13:26:28'),
(33, 99, 'progress_updated', '0%', '45%', 'Progress updated via modal', 1, '2025-11-18 13:33:50'),
(34, 99, 'progress_updated', '45%', '58%', 'Progress updated via modal', 1, '2025-11-18 13:34:05'),
(35, 99, 'status_changed', 'on_break', 'in_progress', 'Progress updated via modal', 1, '2025-11-18 13:34:05'),
(36, 99, 'progress_updated', '58%', '100%', 'Progress updated via modal', 1, '2025-11-18 13:34:13'),
(37, 99, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 1, '2025-11-18 13:34:13'),
(38, 101, 'progress_updated', '0%', '100%', 'Progress updated via modal', 1, '2025-11-18 13:34:28'),
(39, 101, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 1, '2025-11-18 13:34:28'),
(40, 96, 'progress_updated', '0%', '100%', 'Progress updated via modal', 1, '2025-11-18 13:46:37'),
(41, 96, 'status_changed', 'on_break', 'completed', 'Progress updated via modal', 1, '2025-11-18 13:46:37'),
(42, 87, 'progress_updated', '0%', '27%', 'Progress updated via modal', 1, '2025-11-19 05:00:09'),
(43, 58, 'progress_updated', '0%', '100%', 'Progress updated via modal', 1, '2025-11-19 05:33:19'),
(44, 58, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 1, '2025-11-19 05:33:19'),
(45, 104, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-19 05:57:30'),
(46, 105, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 06:01:54'),
(47, 105, 'status_changed', 'assigned', 'completed', 'Status updated from linked follow-up completion', 48, '2025-11-19 06:38:12'),
(48, 105, 'progress_updated', '0%', '100%', 'Progress updated from linked follow-up completion', 48, '2025-11-19 06:38:12'),
(49, 104, 'status_changed', 'assigned', 'in_progress', '', 37, '2025-11-19 06:56:32'),
(50, 104, 'progress_updated', '0%', '5%', '', 37, '2025-11-19 06:56:32'),
(51, 102, 'progress_updated', '0%', '100%', 'Progress updated via modal', 1, '2025-11-19 07:22:53'),
(52, 102, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 1, '2025-11-19 07:22:53'),
(53, 98, 'progress_updated', '0%', '100%', 'Progress updated via modal', 1, '2025-11-19 07:31:29'),
(54, 98, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 1, '2025-11-19 07:31:29'),
(55, 106, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 11:56:38'),
(56, 106, 'updated', 'Task details', 'Task updated', 'Task details were modified', 48, '2025-11-19 11:58:18'),
(57, 106, 'status_changed', 'assigned', 'in_progress', '', 48, '2025-11-19 12:00:31'),
(58, 106, 'status_changed', 'in_progress', 'assigned', '', 48, '2025-11-19 12:01:04'),
(59, 107, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 12:05:12'),
(60, 108, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 12:53:15'),
(61, 109, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 13:00:33'),
(62, 104, 'progress_updated', '0%', '100%', 'Progress updated via modal', 48, '2025-11-19 13:55:16'),
(63, 104, 'status_changed', 'on_break', 'completed', 'Progress updated via modal', 48, '2025-11-19 13:55:16'),
(64, 109, 'progress_updated', '0%', '100%', 'Progress updated via modal', 48, '2025-11-19 13:55:46'),
(65, 109, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 48, '2025-11-19 13:55:46'),
(66, 108, 'progress_updated', '0%', '100%', 'Progress updated via modal', 48, '2025-11-19 14:03:06'),
(67, 108, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 48, '2025-11-19 14:03:06'),
(68, 110, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 14:05:10'),
(69, 111, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-19 14:14:54'),
(70, 112, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 06:09:17'),
(71, 115, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:01:32'),
(72, 117, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:04:15'),
(73, 118, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:05:24'),
(74, 119, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:26:09'),
(75, 120, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:27:04'),
(76, 121, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:27:57'),
(77, 122, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 07:28:58'),
(78, 120, 'progress_updated', '0%', '100%', 'Progress updated via modal', 48, '2025-11-20 08:57:34'),
(79, 120, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 48, '2025-11-20 08:57:34'),
(80, 112, 'progress_updated', '0%', '49%', 'Progress updated via modal', 48, '2025-11-20 09:24:41'),
(81, 112, 'progress_updated', '49%', '29%', 'Progress updated via modal', 48, '2025-11-20 09:24:51'),
(82, 112, 'progress_updated', '29%', '50%', 'Progress updated via modal', 48, '2025-11-20 09:25:04'),
(83, 123, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 09:29:18'),
(84, 124, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-20 09:50:35'),
(85, 124, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-20 09:51:31'),
(86, 124, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-20 09:52:03'),
(87, 124, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 09:52:41'),
(88, 124, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 09:52:41'),
(89, 125, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-20 09:57:51'),
(90, 125, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-20 09:58:04'),
(91, 125, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 09:58:17'),
(92, 125, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 09:58:17'),
(93, 126, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-20 10:00:19'),
(94, 126, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 10:00:51'),
(95, 126, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 10:00:51'),
(96, 127, 'created', '', 'Task created', 'Task was created with initial details', 49, '2025-11-20 10:11:08'),
(97, 127, 'updated', 'Task details', 'Task updated', 'Task details were modified', 49, '2025-11-20 10:12:15'),
(98, 127, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 10:12:26'),
(99, 127, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 10:12:26'),
(100, 128, 'created', '', 'Task created', 'Task was created with initial details', 49, '2025-11-20 10:13:26'),
(101, 128, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 10:13:38'),
(102, 128, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 10:13:38'),
(103, 129, 'created', '', 'Task created', 'Task was created with initial details', 49, '2025-11-20 10:14:42'),
(104, 129, 'progress_updated', '0%', '100%', 'Progress updated via modal', 49, '2025-11-20 10:15:20'),
(105, 129, 'status_changed', 'in_progress', 'completed', 'Progress updated via modal', 49, '2025-11-20 10:15:20'),
(106, 130, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-21 05:14:19'),
(107, 131, 'created', '', 'Task created', 'Task was created with initial details', 49, '2025-11-21 05:17:11'),
(108, 132, 'created', '', 'Task created', 'Task was created with initial details', 49, '2025-11-21 05:28:58'),
(109, 133, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 05:36:50'),
(110, 134, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 06:09:34'),
(111, 134, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-21 06:34:15'),
(112, 133, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-21 06:34:26'),
(113, 133, 'progress_updated', '100%', '64%', '', 37, '2025-11-21 06:34:30'),
(114, 133, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-21 06:34:35'),
(115, 135, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 09:52:59'),
(116, 136, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 11:16:59'),
(117, 137, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 11:42:24'),
(118, 138, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-21 11:52:57'),
(119, 139, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-21 12:39:12'),
(120, 136, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-22 08:00:54'),
(121, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 08:01:22'),
(122, 140, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-22 08:02:02'),
(123, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 08:03:13'),
(124, 141, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-22 09:17:16'),
(125, 141, 'updated', 'Task details', 'Task updated', 'Task details were modified', 48, '2025-11-22 09:18:01'),
(126, 131, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 48, '2025-11-22 09:18:44'),
(127, 131, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 48, '2025-11-22 09:18:49'),
(128, 141, 'updated', 'Task details', 'Task updated', 'Task details were modified', 48, '2025-11-22 09:19:27'),
(129, 142, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-22 12:25:55'),
(130, 141, 'status_changed', 'assigned', 'in_progress', '', 48, '2025-11-22 12:26:39'),
(131, 141, 'progress_updated', '0%', '11%', '', 48, '2025-11-22 12:26:39'),
(132, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:28:31'),
(133, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 12:28:32'),
(134, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:28:34'),
(135, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 12:28:37'),
(136, 138, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-22 12:28:54'),
(137, 138, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:29:05'),
(138, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:29:07'),
(139, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 12:35:31'),
(140, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:35:36'),
(141, 137, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-22 12:35:45'),
(142, 137, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 12:35:55'),
(143, 143, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-22 13:02:26'),
(144, 143, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-22 13:03:42'),
(145, 138, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 13:04:59'),
(146, 138, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 13:05:04'),
(147, 136, 'progress_updated', '', '66%', 'Progress updated to 66% via Daily Planner', 37, '2025-11-22 13:05:08'),
(148, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 13:05:14'),
(149, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-22 13:05:19'),
(150, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-22 13:05:20'),
(151, 144, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-22 13:35:06'),
(152, 144, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-22 13:35:28'),
(153, 145, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 04:53:22'),
(154, 146, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 05:19:54'),
(155, 147, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 05:41:24'),
(156, 148, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 05:44:14'),
(157, 149, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 05:55:09'),
(158, 150, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 06:13:08'),
(159, 151, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 06:14:25'),
(160, 151, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-24 06:14:58'),
(161, 152, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 06:24:05'),
(162, 152, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-24 06:24:51'),
(163, 153, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 06:30:23'),
(164, 154, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 07:09:19'),
(165, 155, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 07:25:26'),
(166, 199, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 07:52:24'),
(167, 200, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 07:57:54'),
(168, 201, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-24 08:13:28'),
(169, 199, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-24 08:24:02'),
(170, 202, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 08:25:55'),
(171, 199, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 08:33:39'),
(172, 140, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 08:59:37'),
(173, 103, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:00:01'),
(174, 58, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-24 09:00:15'),
(175, 58, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:00:24'),
(176, 137, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:00:30'),
(177, 137, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:00:31'),
(178, 140, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:00:38'),
(179, 140, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:00:42'),
(180, 137, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:06:26'),
(181, 137, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:06:27'),
(182, 204, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 09:08:32'),
(183, 205, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 09:19:01'),
(184, 205, 'status_changed', 'not_started', 'in_progress', 'Task started via Daily Planner', 37, '2025-11-24 09:19:25'),
(185, 205, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:19:50'),
(186, 58, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:21:15'),
(187, 103, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:21:22'),
(188, 136, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:21:24'),
(189, 199, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:21:33'),
(190, 137, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:35:37'),
(191, 103, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:35:45'),
(192, 140, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:35:47'),
(193, 136, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:39:13'),
(194, 58, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:39:26'),
(195, 58, 'progress_updated', '', '94%', 'Progress updated to 94% via Daily Planner', 37, '2025-11-24 09:39:44'),
(196, 199, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:40:09'),
(197, 199, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:41:46'),
(198, 199, 'progress_updated', '', '100%', 'Progress updated to 100% via Daily Planner', 37, '2025-11-24 09:42:09'),
(199, 199, 'status_changed', 'in_progress', 'completed', 'Task completed via Daily Planner', 37, '2025-11-24 09:42:09'),
(200, 137, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:42:52'),
(201, 137, 'status_changed', 'in_progress', 'on_break', 'Task paused via Daily Planner', 37, '2025-11-24 09:43:16'),
(202, 137, 'status_changed', 'on_break', 'in_progress', 'Task resumed via Daily Planner', 37, '2025-11-24 09:43:18'),
(203, 58, 'progress_updated', '', '94%', 'Progress updated to 94% via Daily Planner', 37, '2025-11-24 10:52:02'),
(204, 58, 'progress_updated', '', '97%', 'Progress updated to 97% via Daily Planner', 37, '2025-11-24 10:52:08'),
(205, 206, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 11:51:15'),
(206, 207, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-24 11:53:08'),
(207, 208, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 03:56:02'),
(208, 209, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 03:57:31'),
(209, 201, 'status_changed', 'assigned', 'in_progress', '', 37, '2025-11-25 04:00:34'),
(210, 201, 'progress_updated', '0%', '12%', '', 37, '2025-11-25 04:00:34'),
(211, 210, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 04:27:10'),
(212, 211, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 04:27:59'),
(213, 212, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 07:00:18'),
(214, 213, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 07:03:48'),
(215, 214, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 07:22:24'),
(216, 215, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 07:24:45'),
(217, 216, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 08:09:14'),
(218, 217, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 08:38:26'),
(219, 218, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 08:38:57'),
(220, 219, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 08:39:23'),
(221, 220, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 10:59:19'),
(222, 221, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 11:11:13'),
(223, 222, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 11:35:22'),
(224, 223, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 11:42:45'),
(225, 224, 'created', '', 'Task created', 'Task was created with initial details', 37, '2025-11-25 11:59:16'),
(226, 202, 'status_changed', 'in_progress', 'assigned', 'Progress updated via daily planner', 37, '2025-11-27 11:08:05'),
(227, 202, 'progress_updated', '59%', '40%', 'Progress updated via daily planner', 37, '2025-11-27 11:08:05'),
(228, 200, 'progress_updated', '0%', '16%', 'Progress updated via daily planner', 37, '2025-11-27 11:08:24'),
(229, 208, 'progress_updated', '0%', '10%', 'Progress updated via daily planner', 37, '2025-11-27 12:07:32'),
(230, 225, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-27 12:40:18'),
(231, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 48, '2025-11-27 12:42:00'),
(232, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:45:09'),
(233, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:56:00'),
(234, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:59:05'),
(235, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:59:11'),
(236, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:59:12'),
(237, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 12:59:13'),
(238, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-27 13:00:38'),
(239, 225, 'updated', 'Task details', 'Task updated', 'Task details were modified', 37, '2025-11-27 13:21:34'),
(240, 226, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 12:42:42'),
(241, 227, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 12:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `task_updates`
--

CREATE TABLE `task_updates` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `progress` int DEFAULT NULL,
  `comment` text,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int NOT NULL,
  `daily_task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL,
  `active_duration` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `daily_task_id`, `user_id`, `action`, `timestamp`, `active_duration`, `created_at`) VALUES
(1, 1218, 1, 'postpone', '2025-11-18 23:29:42', 0, '2025-11-19 04:59:42'),
(2, 1219, 1, 'start', '2025-11-18 23:34:41', 0, '2025-11-19 05:04:41'),
(3, 1220, 1, 'start', '2025-11-18 23:34:46', 0, '2025-11-19 05:04:46'),
(4, 1221, 1, 'start', '2025-11-18 23:34:57', 0, '2025-11-19 05:04:57'),
(5, 1219, 1, 'start', '2025-11-18 23:46:14', 0, '2025-11-19 05:16:14'),
(6, 1229, 48, 'start', '2025-11-18 23:50:00', 0, '2025-11-19 05:20:00'),
(7, 1219, 1, 'start', '2025-11-18 23:52:27', 0, '2025-11-19 05:22:27'),
(8, 1210, 1, 'postpone', '2025-11-19 02:54:23', 0, '2025-11-19 08:24:24'),
(9, 1270, 37, 'postpone', '2025-11-19 02:55:07', 0, '2025-11-19 08:25:07'),
(10, 1276, 37, 'postpone', '2025-11-19 05:05:17', 0, '2025-11-19 10:35:17'),
(11, 1460, 48, 'postpone', '2025-11-19 08:35:55', 0, '2025-11-19 14:05:55'),
(12, 1862, 48, 'postpone', '2025-11-20 02:00:02', 0, '2025-11-20 07:30:03'),
(13, 1868, 48, 'postpone', '2025-11-20 02:43:39', 0, '2025-11-20 08:13:39'),
(14, 1871, 48, 'postpone', '2025-11-20 02:59:29', 0, '2025-11-20 08:29:29'),
(15, 1883, 48, 'postpone', '2025-11-20 03:15:15', 0, '2025-11-20 08:45:15'),
(16, 1981, 48, 'postpone', '2025-11-20 23:48:11', 0, '2025-11-21 05:18:11'),
(17, 1992, 37, 'pause', '2025-11-21 00:01:51', 116, '2025-11-21 05:31:51'),
(18, 1992, 37, 'pause', '2025-11-21 00:01:59', 0, '2025-11-21 05:31:59'),
(19, 1992, 37, 'resume', '2025-11-21 00:05:12', 0, '2025-11-21 05:35:12'),
(20, 1992, 37, 'pause', '2025-11-21 00:05:12', 0, '2025-11-21 05:35:12'),
(21, 1992, 37, 'resume', '2025-11-21 00:05:21', 0, '2025-11-21 05:35:21'),
(22, 1992, 37, 'pause', '2025-11-21 00:07:29', 128, '2025-11-21 05:37:29'),
(23, 2009, 37, 'complete', '2025-11-21 00:25:47', 4, '2025-11-21 05:55:47'),
(24, 2009, 37, 'complete', '2025-11-21 00:27:53', 130, '2025-11-21 05:57:53'),
(25, 2009, 37, 'complete', '2025-11-21 00:27:57', 134, '2025-11-21 05:57:57'),
(26, 2009, 37, 'pause', '2025-11-21 00:33:41', 478, '2025-11-21 06:03:41'),
(27, 2009, 37, 'postpone', '2025-11-21 00:34:51', 0, '2025-11-21 06:04:51'),
(28, 1931, 48, 'complete', '2025-11-21 03:33:53', 3, '2025-11-21 09:03:53'),
(29, 1931, 48, 'complete', '2025-11-21 03:33:55', 5, '2025-11-21 09:03:55'),
(30, 1931, 48, 'complete', '2025-11-21 03:33:57', 7, '2025-11-21 09:03:57'),
(31, 1931, 48, 'complete', '2025-11-21 03:34:00', 10, '2025-11-21 09:04:00'),
(32, 1859, 48, 'complete', '2025-11-21 03:34:41', 1852, '2025-11-21 09:04:41'),
(33, 1932, 48, 'complete', '2025-11-21 03:37:53', 182, '2025-11-21 09:07:53'),
(34, 1992, 37, 'resume', '2025-11-21 03:52:02', 0, '2025-11-21 09:22:02'),
(35, 1992, 37, 'pause', '2025-11-21 03:52:07', 5, '2025-11-21 09:22:07'),
(36, 1992, 37, 'resume', '2025-11-21 04:00:10', 0, '2025-11-21 09:30:10'),
(37, 1992, 37, 'pause', '2025-11-21 04:00:14', 4, '2025-11-21 09:30:14'),
(38, 1992, 37, 'resume', '2025-11-21 04:00:38', 0, '2025-11-21 09:30:38'),
(39, 1992, 37, 'pause', '2025-11-21 04:04:10', 212, '2025-11-21 09:34:10'),
(40, 1992, 37, 'resume', '2025-11-21 04:12:38', 0, '2025-11-21 09:42:38'),
(41, 1992, 37, 'pause', '2025-11-21 04:12:50', 12, '2025-11-21 09:42:50'),
(42, 1992, 37, 'resume', '2025-11-21 04:13:34', 0, '2025-11-21 09:43:34'),
(43, 1992, 37, 'pause', '2025-11-21 04:13:45', 11, '2025-11-21 09:43:45'),
(44, 1992, 37, 'resume', '2025-11-21 04:23:31', 0, '2025-11-21 09:53:31'),
(45, 1992, 37, 'pause', '2025-11-21 04:23:37', 6, '2025-11-21 09:53:37'),
(46, 2141, 37, 'start', '2025-11-21 04:26:38', 0, '2025-11-21 09:56:38'),
(47, 2141, 37, 'complete', '2025-11-21 04:35:54', 556, '2025-11-21 10:05:54'),
(48, 2141, 37, 'complete', '2025-11-21 04:37:10', 632, '2025-11-21 10:07:10'),
(49, 1992, 37, 'pause', '2025-11-21 05:22:56', 3565, '2025-11-21 10:52:56'),
(50, 1992, 37, 'pause', '2025-11-21 05:26:25', 3774, '2025-11-21 10:56:25'),
(51, 1992, 37, 'pause', '2025-11-21 05:29:25', 3954, '2025-11-21 10:59:25'),
(52, 2238, 37, 'start', '2025-11-21 05:47:09', 0, '2025-11-21 11:17:09'),
(53, 1992, 37, 'complete', '2025-11-21 06:10:26', 6415, '2025-11-21 11:40:26'),
(54, 2238, 37, 'pause', '2025-11-21 06:11:10', 1441, '2025-11-21 11:41:10'),
(55, 2275, 37, 'start', '2025-11-21 06:12:37', 0, '2025-11-21 11:42:37'),
(56, 2275, 37, 'pause', '2025-11-21 06:13:00', 23, '2025-11-21 11:43:00'),
(57, 2275, 37, 'resume', '2025-11-21 06:13:01', 0, '2025-11-21 11:43:01'),
(58, 2275, 37, 'pause', '2025-11-21 06:19:53', 412, '2025-11-21 11:49:53'),
(59, 2275, 37, 'resume', '2025-11-21 06:19:57', 0, '2025-11-21 11:49:57'),
(60, 2275, 37, 'pause', '2025-11-21 06:20:13', 16, '2025-11-21 11:50:13'),
(61, 2275, 37, 'resume', '2025-11-21 06:21:25', 0, '2025-11-21 11:51:25'),
(62, 2238, 37, 'postpone', '2025-11-21 06:27:29', 0, '2025-11-21 11:57:29'),
(63, 2275, 37, 'pause', '2025-11-21 06:28:18', 413, '2025-11-21 11:58:18'),
(64, 2275, 37, 'resume', '2025-11-21 06:28:19', 0, '2025-11-21 11:58:19'),
(65, 2024, 48, 'start', '2025-11-21 07:08:15', 0, '2025-11-21 12:38:15'),
(66, 2024, 48, 'pause', '2025-11-21 07:08:17', 2, '2025-11-21 12:38:17'),
(67, 2024, 48, 'resume', '2025-11-21 07:08:18', 0, '2025-11-21 12:38:18'),
(68, 2307, 48, 'start', '2025-11-21 07:09:21', 0, '2025-11-21 12:39:21'),
(69, 2307, 48, 'pause', '2025-11-21 07:09:22', 1, '2025-11-21 12:39:22'),
(70, 2314, 37, 'start', '2025-11-22 02:30:54', 0, '2025-11-22 08:00:54'),
(71, 2314, 37, 'pause', '2025-11-22 02:31:22', 28, '2025-11-22 08:01:22'),
(72, 2314, 37, 'resume', '2025-11-22 02:33:13', 0, '2025-11-22 08:03:13'),
(73, 2326, 37, 'start', '2025-11-22 02:34:00', 0, '2025-11-22 08:04:00'),
(74, 2326, 37, 'pause', '2025-11-22 02:34:05', 5, '2025-11-22 08:04:05'),
(75, 2312, 48, 'start', '2025-11-22 03:48:44', 0, '2025-11-22 09:18:44'),
(76, 2312, 48, 'pause', '2025-11-22 03:48:49', 5, '2025-11-22 09:18:49'),
(77, 2314, 37, 'pause', '2025-11-22 06:58:31', 15918, '2025-11-22 12:28:31'),
(78, 2314, 37, 'resume', '2025-11-22 06:58:32', 0, '2025-11-22 12:28:32'),
(79, 2314, 37, 'pause', '2025-11-22 06:58:34', 2, '2025-11-22 12:28:34'),
(80, 2314, 37, 'resume', '2025-11-22 06:58:37', 0, '2025-11-22 12:28:37'),
(81, 2316, 37, 'start', '2025-11-22 06:58:54', 0, '2025-11-22 12:28:54'),
(82, 2316, 37, 'pause', '2025-11-22 06:59:05', 11, '2025-11-22 12:29:05'),
(83, 2314, 37, 'pause', '2025-11-22 06:59:07', 30, '2025-11-22 12:29:07'),
(84, 2314, 37, 'resume', '2025-11-22 07:05:31', 0, '2025-11-22 12:35:31'),
(85, 2314, 37, 'pause', '2025-11-22 07:05:36', 5, '2025-11-22 12:35:36'),
(86, 2315, 37, 'start', '2025-11-22 07:05:45', 0, '2025-11-22 12:35:45'),
(87, 2315, 37, 'pause', '2025-11-22 07:05:55', 10, '2025-11-22 12:35:55'),
(88, 2316, 37, 'resume', '2025-11-22 07:34:59', 0, '2025-11-22 13:04:59'),
(89, 2316, 37, 'pause', '2025-11-22 07:35:04', 5, '2025-11-22 13:05:04'),
(90, 2314, 37, 'pause', '2025-11-22 07:35:14', 1783, '2025-11-22 13:05:14'),
(91, 2314, 37, 'resume', '2025-11-22 07:35:19', 0, '2025-11-22 13:05:19'),
(92, 2314, 37, 'pause', '2025-11-22 07:35:20', 1, '2025-11-22 13:05:20'),
(93, 2628, 37, 'start', '2025-11-24 05:55:19', 0, '2025-11-24 05:55:19'),
(94, 2628, 37, 'postpone', '2025-11-24 05:55:50', 0, '2025-11-24 05:55:50'),
(95, 2629, 37, 'start', '2025-11-24 06:33:57', 0, '2025-11-24 06:33:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','admin','user') DEFAULT 'user',
  `is_system_admin` tinyint(1) DEFAULT '0',
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended','terminated') DEFAULT 'active',
  `is_first_login` tinyint(1) DEFAULT '1',
  `temp_password` varchar(20) DEFAULT NULL,
  `password_reset_required` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `total_points` int DEFAULT '0',
  `department_id` int DEFAULT NULL,
  `shift_id` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `password`, `role`, `is_system_admin`, `phone`, `department`, `status`, `is_first_login`, `temp_password`, `password_reset_required`, `last_login`, `last_ip`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `designation`, `joining_date`, `salary`, `total_points`, `department_id`, `shift_id`) VALUES
(1, 'EMP001', 'Athenas Owner', 'info@athenas.co.in', '$2y$10$GKrksmX0Pmp5DoXJ9YskPOZ0x9O192vodYSVg4mRswfgg4kNGfYUq', 'owner', 0, NULL, 'General', 'active', 0, 'owner123', 0, '2025-11-28 17:48:43', '::1', '2025-10-23 06:24:06', '2025-11-28 12:18:43', '1990-01-01', 'male', 'Test Address Update', '9999999999', 'Test Designation', '2024-01-01', 50000.00, 0, 1, 1),
(16, 'ATSO003', 'Harini', 'harini@athenas.co.in', '$2y$10$GcyIHTtTvWon4pAZeWQFNei6jnNdEzP0G.onwzEaP1XCowHylNEbu', 'user', 0, '6380795088', 'Finance & Accounts,Liaison,Marketing & Sales,Operations', 'active', 1, 'RST7498R', 1, '2025-11-03 17:04:46', '127.0.0.1', '2025-10-24 02:34:52', '2025-11-03 11:34:46', '2004-06-20', 'female', 'Plot No: 81,Poriyalar Nagar 4th Street,Near By Yadava college,Thirupalai', '9876787689', 'Accountant', '2024-06-27', 15000.00, 0, NULL, 1),
(37, 'EMP014', 'Nelson', 'nelson@gmail.com', '$2y$10$3/U5i7ZhLU0uNLs5in.P4e2U9A6OZ1ytRDAvDUhqcgQTDS1tdeVlK', 'admin', 0, '9517536422', '6', 'active', 1, NULL, 0, '2025-11-28 17:48:20', '::1', '2025-10-30 05:16:49', '2025-11-28 12:18:20', '2002-12-07', 'male', 'Madurai', '9856472431', 'Accountant', '2025-03-27', 20000.00, 0, 1, 1),
(47, 'EMP015', 'Clinton', 'clinton@gmail.com', '$2y$10$EhHRL4UfewBXKDplubEEe.TsZIA/SIbfDfuNKimFdjd3dznokASQ6', 'admin', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-28 16:47:45', '::1', '2025-11-17 07:25:46', '2025-11-28 11:17:45', '2001-03-01', 'male', 'mdu', '8566754675', 'HR', '2025-11-01', 25000.00, 0, 1, 1),
(48, 'EMP016', 'Simon', 'simon@gmail.com', '$2y$10$EX4Lj5s.w8MlGYA.Q8TNruNnZelKTsRot2jK01sGsE3F0peOAtkSe', 'user', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-28 19:35:51', '127.0.0.1', '2025-11-17 08:25:15', '2025-11-28 14:05:51', '2000-08-11', 'male', 'mdu', '9856472431', 'HR', '2025-11-01', 25000.00, 0, 1, 1),
(49, 'EMP017', 'Joel', 'joel@gmail.com', '$2y$10$87IngWyxJyHdbh8FqvLsDuR5ejsVyY/GlNNWfG9e0vpZjm9rzBRMS', 'user', 0, '7541025356', NULL, 'active', 1, NULL, 0, '2025-11-28 19:39:14', '127.0.0.1', '2025-11-19 04:47:06', '2025-11-28 14:09:14', '2002-12-13', 'male', 'Madurai', '7847578945', 'Developer', '2025-11-01', 15000.00, 0, 14, 1),
(50, 'EMP018', 'Admin', 'admin@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 0, NULL, NULL, 'active', 1, NULL, 0, NULL, NULL, '2025-11-19 08:45:45', '2025-11-21 12:57:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1),
(51, 'EMP019', 'Test User', 'testuser@gmail.com', '$2y$10$/iJOeUmV0PHnrtZ2yxlb0eCu0Ah6r9mxXrSjYL2H6G.FYhHxSLse.', 'admin', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-21 18:31:14', '127.0.0.1', '2025-11-21 12:59:19', '2025-11-21 13:29:10', '2001-03-01', 'female', 'India', '9856472431', 'Developer', '2025-11-01', 20000.00, 0, 13, 1),
(53, 'EMP020', 'Test User 1', 'testuser1@gmail.com', '$2y$10$hPFM5YlfooBHAzhzbcqEmeEObmSVtGu16/8eLUYT089.Uosv0tDsK', 'user', 0, '9517536482', NULL, 'terminated', 1, NULL, 0, NULL, NULL, '2025-11-21 13:02:41', '2025-11-21 13:03:01', '2002-11-04', 'male', 'India', '9856472431', 'Developer', '2025-11-01', 15000.00, 0, 13, 1),
(56, 'EMP021', 'Test User 2', 'testuser2@gmail.com', '$2y$10$f9VqXa2PYeW7XiJZuMJcQuhxAvlrtuAiZjcnG/YwDp2MOCZrD0gIW', 'admin', 0, '9517536482', NULL, 'terminated', 1, NULL, 0, '2025-11-22 16:17:11', '127.0.0.1', '2025-11-22 10:46:18', '2025-11-22 11:08:08', '2002-06-12', 'male', 'mdu', '9856472431', 'HR', '2025-11-01', 20000.00, 0, 14, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `badge_id` int NOT NULL,
  `awarded_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_departments`
--

CREATE TABLE `user_departments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `department_id` int NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `fcm_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') DEFAULT 'android',
  `device_info` text,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `points` int NOT NULL,
  `reason` varchar(200) NOT NULL,
  `reference_type` enum('task','attendance','workflow','bonus') DEFAULT 'task',
  `reference_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preference_key` varchar(50) NOT NULL,
  `preference_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`) VALUES
(1, 1, 'theme', 'light'),
(2, 1, 'language', 'en'),
(3, 1, 'timezone', 'Asia/Kolkata'),
(4, 1, 'notifications_email', '1'),
(5, 1, 'notifications_browser', '1'),
(6, 1, 'dashboard_layout', 'expanded'),
(118, 37, 'theme', 'light'),
(136, 36, 'theme', 'light'),
(420, 2, 'theme', 'light'),
(507, 40, 'theme', 'light'),
(666, 39, 'theme', 'light');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`created_at`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Indexes for table `admin_positions`
--
ALTER TABLE `admin_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_admin` (`user_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `advances`
--
ALTER TABLE `advances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_attendance_date` (`date`);

--
-- Indexes for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `attendance_id` (`attendance_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance_rules`
--
ALTER TABLE `attendance_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_module` (`user_id`,`module`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `badge_definitions`
--
ALTER TABLE `badge_definitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `circulars`
--
ALTER TABLE `circulars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_performance`
--
ALTER TABLE `daily_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `daily_planner`
--
ALTER TABLE `daily_planner`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_planner_user_date` (`user_id`,`plan_date`),
  ADD KEY `idx_daily_planner_department` (`department_id`),
  ADD KEY `idx_planner_status` (`completion_status`);

--
-- Indexes for table `daily_planners`
--
ALTER TABLE `daily_planners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_user_date` (`user_id`,`plan_date`),
  ADD KEY `idx_status` (`completion_status`);

--
-- Indexes for table `daily_planner_audit`
--
ALTER TABLE `daily_planner_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_date` (`target_date`);

--
-- Indexes for table `daily_plans`
--
ALTER TABLE `daily_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`plan_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`plan_date`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_followup` (`is_followup`,`followup_id`);

--
-- Indexes for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_tasks_user_date` (`user_id`,`scheduled_date`),
  ADD KEY `idx_daily_tasks_status` (`status`),
  ADD KEY `idx_daily_tasks_start_time` (`start_time`),
  ADD KEY `idx_original_task_id` (`original_task_id`),
  ADD KEY `idx_rollover_source` (`rollover_source_date`),
  ADD KEY `idx_user_task_date` (`user_id`,`original_task_id`,`scheduled_date`),
  ADD KEY `idx_user_date` (`user_id`,`scheduled_date`),
  ADD KEY `idx_status_timer` (`status`,`start_time`),
  ADD KEY `idx_sla_end_time` (`sla_end_time`),
  ADD KEY `idx_pause_start_time` (`pause_start_time`);

--
-- Indexes for table `daily_task_history`
--
ALTER TABLE `daily_task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_task_id` (`daily_task_id`);

--
-- Indexes for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan_id` (`plan_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`workflow_date`),
  ADD KEY `idx_workflow_date` (`workflow_date`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `head_id` (`head_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `evening_updates`
--
ALTER TABLE `evening_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `planner_id` (`planner_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `idx_user_date` (`user_id`,`date`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_expenses_status` (`status`);

--
-- Indexes for table `followups`
--
ALTER TABLE `followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_id` (`task_id`);

--
-- Indexes for table `followup_history`
--
ALTER TABLE `followup_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_followup_id` (`followup_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_entry` (`journal_entry_id`),
  ADD KEY `idx_account` (`account_id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_leaves_status_date` (`status`,`start_date`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_blocked` (`blocked_until`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module_type` (`module_type`),
  ADD KEY `idx_status_change` (`status_change`),
  ADD KEY `idx_reminder_date` (`reminder_date`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_event` (`event_type`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sla_history`
--
ALTER TABLE `sla_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_task_id` (`daily_task_id`),
  ADD KEY `idx_sla_history_task` (`daily_task_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_tasks_due_date` (`due_date`),
  ADD KEY `idx_tasks_assigned_to` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_deadline` (`deadline`);

--
-- Indexes for table `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_name`);

--
-- Indexes for table `task_history`
--
ALTER TABLE `task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_id` (`task_id`);

--
-- Indexes for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_task_id` (`daily_task_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_time_logs_task` (`daily_task_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_users_system_admin` (`is_system_admin`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_employee_id` (`employee_id`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_badge` (`user_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`),
  ADD KEY `idx_user_badges` (`user_id`);

--
-- Indexes for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_dept` (`user_id`,`department_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_token` (`user_id`,`fcm_token`);

--
-- Indexes for table `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_points` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pref` (`user_id`,`preference_key`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `admin_positions`
--
ALTER TABLE `admin_positions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advances`
--
ALTER TABLE `advances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_rules`
--
ALTER TABLE `attendance_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `badge_definitions`
--
ALTER TABLE `badge_definitions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `circulars`
--
ALTER TABLE `circulars`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `daily_performance`
--
ALTER TABLE `daily_performance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `daily_planner`
--
ALTER TABLE `daily_planner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_planners`
--
ALTER TABLE `daily_planners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_planner_audit`
--
ALTER TABLE `daily_planner_audit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=412;

--
-- AUTO_INCREMENT for table `daily_plans`
--
ALTER TABLE `daily_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2746;

--
-- AUTO_INCREMENT for table `daily_task_history`
--
ALTER TABLE `daily_task_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3077;

--
-- AUTO_INCREMENT for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `evening_updates`
--
ALTER TABLE `evening_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `followup_history`
--
ALTER TABLE `followup_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1085;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sla_history`
--
ALTER TABLE `sla_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_departments`
--
ALTER TABLE `user_departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=875;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_positions`
--
ALTER TABLE `admin_positions`
  ADD CONSTRAINT `admin_positions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_positions_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  ADD CONSTRAINT `attendance_corrections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_corrections_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_corrections_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `circulars`
--
ALTER TABLE `circulars`
  ADD CONSTRAINT `circulars_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_planner`
--
ALTER TABLE `daily_planner`
  ADD CONSTRAINT `daily_planner_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_planner_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_planners`
--
ALTER TABLE `daily_planners`
  ADD CONSTRAINT `daily_planners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_planners_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_plans`
--
ALTER TABLE `daily_plans`
  ADD CONSTRAINT `daily_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_plans_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  ADD CONSTRAINT `daily_task_updates_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `daily_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  ADD CONSTRAINT `daily_workflow_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `evening_updates`
--
ALTER TABLE `evening_updates`
  ADD CONSTRAINT `evening_updates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evening_updates_ibfk_2` FOREIGN KEY (`planner_id`) REFERENCES `daily_planner` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evening_updates_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD CONSTRAINT `task_updates_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_updates_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badge_definitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD CONSTRAINT `user_departments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
