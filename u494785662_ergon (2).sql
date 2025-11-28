-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 06:07 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u494785662_ergon`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`u494785662_ergon`@`127.0.0.1` PROCEDURE `CreateIndexIfNotExists` (IN `indexName` VARCHAR(64), IN `tableName` VARCHAR(64), IN `columnList` VARCHAR(255))   BEGIN
    DECLARE indexExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO indexExists
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = tableName
    AND index_name = indexName;
    
    IF indexExists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', indexName, ' ON ', tableName, '(', columnList, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(10) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(7, 'A002', 'Bank Account', 'asset', 0.00, 1, '2025-11-20 10:35:35', '2025-11-20 10:35:35'),
(1, 'E001', 'General Expenses', 'expense', 1400.00, 1, '2025-11-20 10:35:34', '2025-11-27 12:56:51'),
(2, 'E002', 'Travel Expenses', 'expense', 1825.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(3, 'E003', 'Office Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(4, 'E004', 'Miscellaneous Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(5, 'L001', 'Accounts Payable', 'liability', 3225.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(6, 'A001', 'Cash', 'asset', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(7, 'A002', 'Bank Account', 'asset', 0.00, 1, '2025-11-20 10:35:35', '2025-11-20 10:35:35'),
(1, 'E001', 'General Expenses', 'expense', 1400.00, 1, '2025-11-20 10:35:34', '2025-11-27 12:56:51'),
(2, 'E002', 'Travel Expenses', 'expense', 1825.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(3, 'E003', 'Office Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(4, 'E004', 'Miscellaneous Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(5, 'L001', 'Accounts Payable', 'liability', 3225.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(6, 'A001', 'Cash', 'asset', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(7, 'A002', 'Bank Account', 'asset', 0.00, 1, '2025-11-20 10:35:35', '2025-11-20 10:35:35'),
(1, 'E001', 'General Expenses', 'expense', 1400.00, 1, '2025-11-20 10:35:34', '2025-11-27 12:56:51'),
(2, 'E002', 'Travel Expenses', 'expense', 1825.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(3, 'E003', 'Office Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(4, 'E004', 'Miscellaneous Expenses', 'expense', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(5, 'L001', 'Accounts Payable', 'liability', 3225.00, 1, '2025-11-20 10:35:34', '2025-11-28 12:47:50'),
(6, 'A001', 'Cash', 'asset', 0.00, 1, '2025-11-20 10:35:34', '2025-11-20 10:35:34'),
(7, 'A002', 'Bank Account', 'asset', 0.00, 1, '2025-11-20 10:35:35', '2025-11-20 10:35:35'),
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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','task_update','break_start','break_end','system_ping') DEFAULT 'system_ping',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_positions`
--

CREATE TABLE `admin_positions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_department` varchar(100) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `assigned_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advances`
--

CREATE TABLE `advances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `advance_type` varchar(100) DEFAULT NULL,
  `repayment_date` date DEFAULT NULL,
  `requested_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `advances`
--

INSERT INTO `advances` (`id`, `user_id`, `type`, `amount`, `reason`, `advance_type`, `repayment_date`, `requested_date`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `admin_remarks`, `created_at`, `updated_at`, `rejected_by`, `rejected_at`) VALUES
(1, 49, 'Travel Advance', 2000.00, 'Client site visit', NULL, '2025-11-28', '2025-11-28', 'approved', 1, '2025-11-28 13:06:40', NULL, NULL, '2025-11-28 06:45:10', '2025-11-28 07:36:40', NULL, NULL),
(2, 48, 'Project Advance', 3500.00, 'Project Materials', NULL, '2025-11-29', '2025-11-28', 'rejected', NULL, NULL, 'Budget not approved for this project', NULL, '2025-11-28 07:04:02', '2025-11-28 07:30:06', 37, '2025-11-28 07:30:06'),
(3, 37, 'Project Advance', 2000.00, 'Need some money for Project', NULL, NULL, '2025-10-31', 'rejected', NULL, NULL, 'Reject', NULL, '2025-10-31 11:30:35', '2025-11-08 09:38:12', NULL, NULL),
(4, 37, 'Project Advance', 600.00, 'Training Program Fee - UI/UX certification', NULL, '2025-11-28', '2025-11-28', 'pending', NULL, NULL, NULL, NULL, '2025-11-28 07:29:34', '2025-11-28 07:29:34', NULL, NULL),
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
            INSERT IGNORE INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, action_url)
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
                INSERT IGNORE INTO notifications (sender_id, receiver_id, type, category, title, message, reference_type, reference_id, module_type, status_change, approver_id, action_url)
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
  `id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `clock_in_time` time DEFAULT NULL,
  `clock_out_time` time DEFAULT NULL,
  `date` date DEFAULT curdate(),
  `location_lat` decimal(10,8) DEFAULT 0.00000000,
  `location_lng` decimal(11,8) DEFAULT 0.00000000,
  `status` enum('present','absent','late','on_leave') DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) DEFAULT 'Office',
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `shift_id` int(11) DEFAULT 1,
  `distance_meters` int(11) DEFAULT NULL,
  `is_auto_checkout` tinyint(1) DEFAULT 0,
  `location_name` varchar(255) DEFAULT 'Office',
  `manual_entry` tinyint(1) DEFAULT 0,
  `edited_by` int(11) DEFAULT NULL,
  `edit_reason` text DEFAULT NULL,
  `working_hours` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `clock_in`, `clock_out`, `latitude`, `longitude`, `clock_in_time`, `clock_out_time`, `date`, `location_lat`, `location_lng`, `status`, `created_at`, `updated_at`, `location`, `check_in`, `check_out`, `shift_id`, `distance_meters`, `is_auto_checkout`, `location_name`, `manual_entry`, `edited_by`, `edit_reason`, `working_hours`) VALUES
(1, 37, NULL, NULL, 9.98136100, 78.14307000, NULL, NULL, '2025-11-28', 0.00000000, 0.00000000, 'present', '2025-11-28 05:32:33', '2025-11-28 16:55:47', 'Office', '2025-11-28 11:02:33', '2025-11-28 22:25:47', 1, NULL, 0, 'Office', 0, NULL, NULL, NULL),
(2, 65, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28', 0.00000000, 0.00000000, 'present', '2025-11-28 06:47:19', '2025-11-28 06:47:19', 'Office', '2025-11-28 12:17:19', NULL, 1, NULL, 0, 'Manual Entry', 0, NULL, NULL, NULL),
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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attendance_id` int(11) DEFAULT NULL,
  `correction_date` date NOT NULL,
  `original_check_in` datetime DEFAULT NULL,
  `original_check_out` datetime DEFAULT NULL,
  `requested_check_in` datetime DEFAULT NULL,
  `requested_check_out` datetime DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_rules`
--

CREATE TABLE `attendance_rules` (
  `id` int(11) NOT NULL,
  `auto_checkout_time` time DEFAULT '18:00:00',
  `half_day_hours` decimal(3,1) DEFAULT 4.0,
  `full_day_hours` decimal(3,1) DEFAULT 8.0,
  `late_threshold_minutes` int(11) DEFAULT 15,
  `office_latitude` decimal(10,8) DEFAULT 0.00000000,
  `office_longitude` decimal(11,8) DEFAULT 0.00000000,
  `office_radius_meters` int(11) DEFAULT 200,
  `weekend_days` varchar(20) DEFAULT 'saturday,sunday',
  `is_gps_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT '?',
  `criteria_type` enum('points','tasks','streak','productivity') NOT NULL,
  `criteria_value` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_stats`
--

CREATE TABLE `chart_stats` (
  `id` int(11) NOT NULL,
  `company_prefix` varchar(50) NOT NULL,
  `quotation_pipeline_draft` int(11) DEFAULT 0,
  `quotation_pipeline_revised` int(11) DEFAULT 0,
  `quotation_pipeline_converted` int(11) DEFAULT 0,
  `win_rate` decimal(5,2) DEFAULT 0.00,
  `avg_deal_size` decimal(15,2) DEFAULT 0.00,
  `pipeline_value` decimal(15,2) DEFAULT 0.00,
  `po_count` int(11) DEFAULT 0,
  `po_fulfillment_rate` decimal(5,2) DEFAULT 0.00,
  `po_avg_lead_time` int(11) DEFAULT 0,
  `po_open_commitments` decimal(15,2) DEFAULT 0.00,
  `invoice_paid_count` int(11) DEFAULT 0,
  `invoice_unpaid_count` int(11) DEFAULT 0,
  `invoice_overdue_count` int(11) DEFAULT 0,
  `dso_days` int(11) DEFAULT 0,
  `bad_debt_risk` decimal(15,2) DEFAULT 0.00,
  `collection_efficiency` decimal(5,2) DEFAULT 0.00,
  `outstanding_total` decimal(15,2) DEFAULT 0.00,
  `top_customer_outstanding` decimal(15,2) DEFAULT 0.00,
  `concentration_risk` decimal(5,2) DEFAULT 0.00,
  `top3_exposure` decimal(15,2) DEFAULT 0.00,
  `customer_diversity` int(11) DEFAULT 0,
  `aging_current` decimal(15,2) DEFAULT 0.00,
  `aging_watch` decimal(15,2) DEFAULT 0.00,
  `aging_concern` decimal(15,2) DEFAULT 0.00,
  `aging_critical` decimal(15,2) DEFAULT 0.00,
  `provision_required` decimal(15,2) DEFAULT 0.00,
  `recovery_rate` decimal(5,2) DEFAULT 0.00,
  `credit_quality` varchar(20) DEFAULT 'Good',
  `payment_total` decimal(15,2) DEFAULT 0.00,
  `payment_velocity_daily` decimal(15,2) DEFAULT 0.00,
  `forecast_accuracy` decimal(5,2) DEFAULT 0.00,
  `cash_conversion_days` int(11) DEFAULT 0,
  `generated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circulars`
--

CREATE TABLE `circulars` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `posted_by` int(11) NOT NULL,
  `visible_to` enum('All','Admin','User') DEFAULT 'All',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `phone`, `email`, `company`, `created_at`) VALUES
(1, 'Ramesh Kumar', '7584001154', 'ramesh.kumar@example.com', 'Athena Solutions', '2025-11-28 05:28:38'),
(2, 'Karthik S', '9003581122', 'karthik.s@example.com', 'Athenas', '2025-11-28 06:16:01'),
(3, 'Vikram Sharma', '9988776655', 'vikram.sharma@clientmail.com', 'Athenas', '2025-11-28 06:42:00'),
(4, 'Priya Sharma', '98765 21121', 'priya.sharma@example.com', 'Athena Solutions', '2025-11-28 07:07:51'),
(5, 'Priya Sharma', '98765 43211', 'priya.sharma@example.com', 'Athena Solutions', '2025-11-28 07:15:33'),
(6, 'Nelson Raj', '9751156031', 'nelsonraj@gmail.com', 'Athenas', '2025-11-28 18:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `daily_performance`
--

CREATE TABLE `daily_performance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_planned_minutes` int(11) DEFAULT 0,
  `total_active_minutes` decimal(10,2) DEFAULT 0.00,
  `total_tasks` int(11) DEFAULT 0,
  `completed_tasks` int(11) DEFAULT 0,
  `in_progress_tasks` int(11) DEFAULT 0,
  `postponed_tasks` int(11) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_performance`
--

INSERT INTO `daily_performance` (`id`, `user_id`, `date`, `total_planned_minutes`, `total_active_minutes`, `total_tasks`, `completed_tasks`, `in_progress_tasks`, `postponed_tasks`, `completion_percentage`, `created_at`, `updated_at`) VALUES
(1, 57, '2025-11-28', 60, 0.00, 1, 0, 0, 1, 0.00, '2025-11-28 07:24:20', '2025-11-28 07:24:20');

-- --------------------------------------------------------

--
-- Table structure for table `daily_planner`
--

CREATE TABLE `daily_planner` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `plan_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT 0.00,
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `completion_percentage` int(11) DEFAULT 0,
  `completion_status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `reminder_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_planners`
--

CREATE TABLE `daily_planners` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `plan_date` date NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT NULL,
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `completion_status` enum('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
  `completion_percentage` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `reminder_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_planner_audit`
--

CREATE TABLE `daily_planner_audit` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_date` date DEFAULT NULL,
  `task_count` int(11) DEFAULT 0,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_plans`
--

CREATE TABLE `daily_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `plan_date` date NOT NULL,
  `project_name` varchar(200) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `category` enum('planned','unplanned') DEFAULT 'planned',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT 1.00,
  `status` enum('pending','in_progress','completed','blocked','cancelled') DEFAULT 'pending',
  `progress` int(11) DEFAULT 0,
  `actual_hours` decimal(4,2) DEFAULT 0.00,
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_followup` tinyint(1) DEFAULT 0,
  `followup_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `original_task_id` int(11) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `planned_start_time` time DEFAULT NULL,
  `planned_duration` int(11) DEFAULT 60,
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(50) DEFAULT 'not_started',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `start_time` timestamp NULL DEFAULT NULL,
  `pause_time` timestamp NULL DEFAULT NULL,
  `pause_start_time` timestamp NULL DEFAULT NULL,
  `resume_time` timestamp NULL DEFAULT NULL,
  `completion_time` timestamp NULL DEFAULT NULL,
  `active_seconds` int(11) DEFAULT 0,
  `pause_duration` int(11) DEFAULT 0,
  `completed_percentage` int(11) DEFAULT 0,
  `postponed_from_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_pause_duration` int(11) DEFAULT 0,
  `sla_end_time` timestamp NULL DEFAULT NULL,
  `late_duration` int(11) DEFAULT 0,
  `postponed_to_date` date DEFAULT NULL,
  `source_field` varchar(50) DEFAULT NULL,
  `rollover_source_date` date DEFAULT NULL,
  `rollover_timestamp` timestamp NULL DEFAULT NULL,
  `remaining_sla_seconds` int(11) DEFAULT 0,
  `overdue_start_time` timestamp NULL DEFAULT NULL,
  `start_ts_ms` bigint(20) DEFAULT NULL,
  `sla_end_ts_ms` bigint(20) DEFAULT NULL,
  `pause_start_ts_ms` bigint(20) DEFAULT NULL,
  `paused_accum_ms` bigint(20) DEFAULT 0,
  `overdue_start_ts_ms` bigint(20) DEFAULT NULL,
  `sla_duration_seconds` int(11) DEFAULT 900,
  `progress_percent` int(11) DEFAULT 0,
  `total_pause_duration_ms` bigint(20) DEFAULT 0,
  `sla_time_spent_ms` bigint(20) DEFAULT 0,
  `overdue_time_spent_ms` bigint(20) DEFAULT 0,
  `total_used_time_ms` bigint(20) DEFAULT 0,
  `pause_end_ts_ms` bigint(20) DEFAULT NULL,
  `used_time_ms` bigint(20) DEFAULT 0,
  `remaining_sla_time` int(11) DEFAULT 0,
  `time_used` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_tasks`
--

INSERT INTO `daily_tasks` (`id`, `user_id`, `task_id`, `original_task_id`, `scheduled_date`, `title`, `description`, `planned_start_time`, `planned_duration`, `priority`, `status`, `created_at`, `start_time`, `pause_time`, `pause_start_time`, `resume_time`, `completion_time`, `active_seconds`, `pause_duration`, `completed_percentage`, `postponed_from_date`, `updated_at`, `total_pause_duration`, `sla_end_time`, `late_duration`, `postponed_to_date`, `source_field`, `rollover_source_date`, `rollover_timestamp`, `remaining_sla_seconds`, `overdue_start_time`, `start_ts_ms`, `sla_end_ts_ms`, `pause_start_ts_ms`, `paused_accum_ms`, `overdue_start_ts_ms`, `sla_duration_seconds`, `progress_percent`, `total_pause_duration_ms`, `sla_time_spent_ms`, `overdue_time_spent_ms`, `total_used_time_ms`, `pause_end_ts_ms`, `used_time_ms`, `remaining_sla_time`, `time_used`) VALUES
(1, 37, 2, 2, '2025-11-28', 'Optimize Employee Attendance API', 'Improve the attendance API response time by optimizing database queries and fixing duplicate record issues. Ensure the API returns accurate data and passes all basic validation checks.', NULL, 60, 'medium', 'in_progress', '2025-11-28 06:59:13', '2025-11-28 07:04:51', NULL, NULL, '2025-11-28 07:26:54', NULL, 1124, 0, 0, NULL, '2025-11-28 07:26:54', 199, '2025-11-28 07:41:54', 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 900, 1124),
(2, 65, 3, 3, '2025-11-29', 'Update Client Portal Dashboard', 'Revamp the client portal dashboard by updating widgets, optimizing load times, and fixing alignment issues. Ensure compatibility across browsers and devices, and confirm that all dashboard metrics display correctly.', NULL, 60, 'low', 'not_started', '2025-11-28 07:02:24', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-28 07:02:24', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(3, 57, 4, 4, '2025-11-28', 'Fix Login Authentication Issue', 'Investigate and resolve login authentication errors on the web portal. Verify correct handling of invalid credentials, password resets, and session timeouts. Ensure no users are incorrectly logged out and all security measures are intact.', NULL, 60, 'medium', 'postponed', '2025-11-28 07:22:56', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-28', '2025-11-28 07:24:20', 0, NULL, 0, '2025-11-29', 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(4, 57, 4, 4, '2025-11-29', 'Fix Login Authentication Issue', 'Investigate and resolve login authentication errors on the web portal. Verify correct handling of invalid credentials, password resets, and session timeouts. Ensure no users are incorrectly logged out and all security measures are intact.', NULL, 60, 'medium', 'not_started', '2025-11-28 07:24:20', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-28', '2025-11-28 07:24:20', 0, NULL, 0, NULL, 'postponed', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0),
(5, 57, 5, 5, '2025-11-28', 'Mobile App Release Checklist', 'Complete all release steps: build, QA test, upload to Play Store, release notes, version update.', NULL, 60, 'medium', 'not_started', '2025-11-28 08:28:21', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2025-11-28 08:28:21', 0, NULL, 0, NULL, 'planned_date', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 900, 0, 0, 0, 0, 0, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `daily_task_history`
--

CREATE TABLE `daily_task_history` (
  `id` int(11) NOT NULL,
  `daily_task_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_task_history`
--

INSERT INTO `daily_task_history` (`id`, `daily_task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-28', 37, '2025-11-28 06:59:13'),
(2, 2, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-29', 65, '2025-11-28 07:02:24'),
(3, 1, 'started', 'not_started', 'in_progress', 'Task started at 2025-11-28 12:34:51', 37, '2025-11-28 07:04:51'),
(4, 1, 'time_start', '0', NULL, 'Action: start at 2025-11-28 12:34:51. Duration: 0s.', 37, '2025-11-28 07:04:51'),
(5, 3, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-28', 57, '2025-11-28 07:22:56'),
(6, 1, 'time_pause', '1092', NULL, 'Action: pause at 2025-11-28 12:53:03. Duration: 1092s.', 37, '2025-11-28 07:23:03'),
(7, 1, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-28 12:53:03 with 900s remaining', 37, '2025-11-28 07:23:03'),
(8, 1, 'time_resume', '0', NULL, 'Action: resume at 2025-11-28 12:53:18. Duration: 0s.', 37, '2025-11-28 07:23:18'),
(9, 1, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-28 12:53:18 with 900s remaining', 37, '2025-11-28 07:23:18'),
(10, 1, 'time_pause', '3', NULL, 'Action: pause at 2025-11-28 12:53:21. Duration: 3s.', 37, '2025-11-28 07:23:21'),
(11, 1, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-28 12:53:21 with 900s remaining', 37, '2025-11-28 07:23:21'),
(12, 3, 'time_postpone', '0', NULL, 'Action: postpone at 2025-11-28 12:54:20. Duration: 0s.', 57, '2025-11-28 07:24:20'),
(13, 3, 'postponed', '2025-11-28', '2025-11-29', 'Task postponed to 2025-11-29', 57, '2025-11-28 07:24:20'),
(14, 4, 'created', NULL, 'postponed_entry', 'Postponed task entry created for 2025-11-29', 57, '2025-11-28 07:24:20'),
(15, 1, 'time_resume', '0', NULL, 'Action: resume at 2025-11-28 12:55:14. Duration: 0s.', 37, '2025-11-28 07:25:14'),
(16, 1, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-28 12:55:14 with 900s remaining', 37, '2025-11-28 07:25:14'),
(17, 1, 'time_pause', '24', NULL, 'Action: pause at 2025-11-28 12:55:38. Duration: 24s.', 37, '2025-11-28 07:25:38'),
(18, 1, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-28 12:55:38 with 900s remaining', 37, '2025-11-28 07:25:38'),
(19, 1, 'time_resume', '0', NULL, 'Action: resume at 2025-11-28 12:56:00. Duration: 0s.', 37, '2025-11-28 07:26:00'),
(20, 1, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-28 12:56:00 with 900s remaining', 37, '2025-11-28 07:26:00'),
(21, 1, 'time_pause', '5', NULL, 'Action: pause at 2025-11-28 12:56:05. Duration: 5s.', 37, '2025-11-28 07:26:05'),
(22, 1, 'paused', 'in_progress', 'on_break', 'Task paused at 2025-11-28 12:56:05 with 900s remaining', 37, '2025-11-28 07:26:05'),
(23, 1, 'time_resume', '0', NULL, 'Action: resume at 2025-11-28 12:56:54. Duration: 0s.', 37, '2025-11-28 07:26:54'),
(24, 1, 'resumed', 'on_break', 'in_progress', 'Task resumed at 2025-11-28 12:56:54 with 900s remaining', 37, '2025-11-28 07:26:54'),
(25, 5, 'fetched', NULL, 'planned_date', '📌 Source: planned_date on 2025-11-28', 57, '2025-11-28 08:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `daily_task_updates`
--

CREATE TABLE `daily_task_updates` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `progress_before` int(11) DEFAULT 0,
  `progress_after` int(11) NOT NULL,
  `hours_worked` decimal(4,2) DEFAULT 0.00,
  `update_notes` text DEFAULT NULL,
  `blockers` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `update_type` enum('progress','completion','blocker','status_change') DEFAULT 'progress',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_workflow_status`
--

CREATE TABLE `daily_workflow_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workflow_date` date NOT NULL,
  `total_planned_tasks` int(11) DEFAULT 0,
  `total_completed_tasks` int(11) DEFAULT 0,
  `total_planned_hours` decimal(4,2) DEFAULT 0.00,
  `total_actual_hours` decimal(4,2) DEFAULT 0.00,
  `productivity_score` decimal(5,2) DEFAULT 0.00,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `morning_submitted_at` timestamp NULL DEFAULT NULL,
  `evening_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_stats`
--

CREATE TABLE `dashboard_stats` (
  `id` int(11) NOT NULL,
  `company_prefix` varchar(10) DEFAULT NULL,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `invoice_count` int(11) DEFAULT 0,
  `average_invoice` decimal(15,2) DEFAULT 0.00,
  `amount_received` decimal(15,2) DEFAULT 0.00,
  `collection_rate` decimal(5,2) DEFAULT 0.00,
  `paid_invoices` int(11) DEFAULT 0,
  `outstanding_amount` decimal(15,2) DEFAULT 0.00,
  `outstanding_percentage` decimal(5,2) DEFAULT 0.00,
  `overdue_amount` decimal(15,2) DEFAULT 0.00,
  `customer_count` int(11) DEFAULT 0,
  `po_commitments` decimal(15,2) DEFAULT 0.00,
  `open_pos` int(11) DEFAULT 0,
  `average_po` decimal(15,2) DEFAULT 0.00,
  `claimable_amount` decimal(15,2) DEFAULT 0.00,
  `claimable_pos` int(11) DEFAULT 0,
  `claim_rate` decimal(5,2) DEFAULT 0.00,
  `generated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dashboard_stats`
--

INSERT INTO `dashboard_stats` (`id`, `company_prefix`, `total_revenue`, `invoice_count`, `average_invoice`, `amount_received`, `collection_rate`, `paid_invoices`, `outstanding_amount`, `outstanding_percentage`, `overdue_amount`, `customer_count`, `po_commitments`, `open_pos`, `average_po`, `claimable_amount`, `claimable_pos`, `claim_rate`, `generated_at`) VALUES
(1, 'BKC', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 0, 0.00, 0.00, 0, 0.00, '2025-11-28 14:53:48');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Human Resources', 'Employee management and organizational development', NULL, 'active', '2025-10-23 06:24:06', '2025-10-23 06:24:06'),
(5, 'Operations', 'Daily business operations and logistics', NULL, 'active', '2025-10-23 06:24:06', '2025-10-23 06:24:06'),
(6, 'Liaison', 'Interdepartmental coordination and external stakeholder communication.', 1, 'active', '2025-10-26 21:55:53', '2025-10-26 21:55:53'),
(13, 'Finance & Accounts', 'Consolidated Finance, Accounting and Financial Operations', 37, 'active', '2025-10-27 09:35:18', '2025-11-27 11:09:53'),
(14, 'Information Technology', 'Consolidated IT Development, Infrastructure and Support', 37, 'active', '2025-10-27 09:35:18', '2025-11-26 04:12:49'),
(15, 'Marketing & Sales', 'Consolidated Marketing, Sales and Business Development', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18'),
(22, 'Warehouse & Inventory', 'Warehouse & Inventory manages stock storage, tracking, and movement to maintain accurate inventory and smooth supply operations.', 47, 'active', '2025-11-26 19:19:44', '2025-11-26 19:19:44');

-- --------------------------------------------------------

--
-- Table structure for table `evening_updates`
--

CREATE TABLE `evening_updates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `planner_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `actual_hours_spent` decimal(4,2) DEFAULT 0.00,
  `completion_status` enum('not_started','in_progress','completed','blocked') DEFAULT 'not_started',
  `blockers` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expense_date` date NOT NULL DEFAULT curdate(),
  `attachment` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `journal_entry_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `created_at`, `updated_at`, `expense_date`, `attachment`, `rejection_reason`, `approved_by`, `journal_entry_id`, `approved_at`) VALUES
(1, 1, 'Travel', 500.00, 'Test expense for approval testing', NULL, 'pending', '2025-11-28 06:25:10', '2025-11-28 06:25:10', '2025-11-28', NULL, NULL, NULL, NULL, NULL),
(2, 37, 'travel', 850.00, 'Auto fare to visit ABC Corp', NULL, 'approved', '2025-11-28 06:30:26', '2025-11-28 06:42:07', '2025-11-28', '1764311426_Vintage-bus-ticket-template-Retro-trans-Graphics-69926109-1.png', NULL, 1, 1, '2025-11-28 06:42:07'),
(3, 49, 'other', 350.00, 'Small New Year gift', NULL, 'rejected', '2025-11-28 06:37:40', '2025-11-28 06:41:48', '2025-11-28', '1764311860_Bill.jpg', 'Personal expense – not allowed', NULL, NULL, NULL),
(5, 49, 'travel', 350.00, 'Auto fare to visit ABC Corp', NULL, 'pending', '2025-11-28 10:53:10', '2025-11-28 10:53:10', '2025-11-28', NULL, NULL, NULL, NULL, NULL),
(6, 48, 'travel', 400.00, 'Food Meals', NULL, 'pending', '2025-11-28 16:57:32', '2025-11-28 16:57:32', '2025-11-28', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `finance_data`
--

CREATE TABLE `finance_data` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_data`
--

INSERT INTO `finance_data` (`id`, `table_name`, `data`, `created_at`) VALUES
(40, 'finance_customers', '{\"customer_name\":\"ABC Corp\",\"contact_email\":\"contact@abc.com\"}', '2025-11-28 08:29:06'),
(41, 'finance_customers', '{\"customer_name\":\"XYZ Ltd\",\"contact_email\":\"info@xyz.com\"}', '2025-11-28 08:29:06'),
(989, 'finance_invoices', '{\"id\":\"29\",\"invoice_number\":\"TCINV002\",\"invoice_date\":\"2025-11-24\",\"due_date\":\"2025-12-24\",\"reference\":\"\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"gst_type\":\"igst\",\"subtotal\":\"1989000.00\",\"total_tax\":\"358020.00\",\"total_amount\":\"2347020.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"358020.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"payment_status\":\"unpaid\",\"paid_amount\":\"0.00\",\"outstanding_amount\":\"2347020.00\",\"last_payment_date\":null,\"payment_due_date\":null,\"invoice_type\":\"tax_invoice\",\"status\":\"draft\",\"notes\":\"Tax invoice for GST filing\",\"terms_and_conditions\":\"\",\"created_at\":\"2025-11-24 08:14:21.528095+00\",\"updated_at\":\"2025-11-24 08:14:21.528102+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"shipping_address_id\":null,\"proforma_invoice_id\":null,\"purchase_order_id\":\"49\",\"gst_transaction_id\":\"\",\"gstr1_filing_date\":null,\"is_filed_in_gstr1\":\"f\",\"place_of_supply\":\"\",\"reverse_charge_applicable\":\"f\",\"quotation_id\":null,\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null,\"is_revised\":\"f\",\"revised_at\":null,\"revised_by_id\":null,\"revision_count\":\"0\"}', '2025-11-28 14:07:35'),
(990, 'finance_invoices', '{\"id\":\"30\",\"invoice_number\":\"TCINV003\",\"invoice_date\":\"2025-11-28\",\"due_date\":\"2025-12-28\",\"reference\":\"\",\"customer_gstin\":\"24AAICT7384F1Z3\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"gst_type\":\"igst\",\"subtotal\":\"48000.00\",\"total_tax\":\"5760.00\",\"total_amount\":\"53760.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"5760.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"payment_status\":\"unpaid\",\"paid_amount\":\"0.00\",\"outstanding_amount\":\"53760.00\",\"last_payment_date\":null,\"payment_due_date\":null,\"invoice_type\":\"tax_invoice\",\"status\":\"draft\",\"notes\":\"Tax invoice for GST filing\",\"terms_and_conditions\":\"Payment terms: Net 30 days. GST as applicable. Late payments may incur additional charges.\",\"created_at\":\"2025-11-28 08:45:28.933184+00\",\"updated_at\":\"2025-11-28 08:45:28.933195+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"16\",\"shipping_address_id\":null,\"proforma_invoice_id\":null,\"purchase_order_id\":\"58\",\"gst_transaction_id\":\"\",\"gstr1_filing_date\":null,\"is_filed_in_gstr1\":\"f\",\"place_of_supply\":\"\",\"reverse_charge_applicable\":\"f\",\"quotation_id\":null,\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null,\"is_revised\":\"f\",\"revised_at\":null,\"revised_by_id\":null,\"revision_count\":\"0\"}', '2025-11-28 14:07:35'),
(991, 'finance_invoices', '{\"id\":\"22\",\"invoice_number\":\"BKGE-INV-25-26-001\",\"invoice_date\":\"2025-09-22\",\"due_date\":\"2025-12-20\",\"reference\":\"\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"gst_type\":\"igst\",\"subtotal\":\"980000.00\",\"total_tax\":\"176400.00\",\"total_amount\":\"1156400.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"176400.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"payment_status\":\"unpaid\",\"paid_amount\":\"0.00\",\"outstanding_amount\":\"1156400.00\",\"last_payment_date\":null,\"payment_due_date\":null,\"invoice_type\":\"tax_invoice\",\"status\":\"draft\",\"notes\":\"Tax invoice for GST filing\",\"terms_and_conditions\":\"\",\"created_at\":\"2025-11-20 06:19:10.50336+00\",\"updated_at\":\"2025-11-20 06:19:10.503369+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"shipping_address_id\":null,\"proforma_invoice_id\":null,\"purchase_order_id\":\"55\",\"gst_transaction_id\":\"\",\"gstr1_filing_date\":null,\"is_filed_in_gstr1\":\"f\",\"place_of_supply\":\"\",\"reverse_charge_applicable\":\"f\",\"quotation_id\":null,\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null,\"is_revised\":\"f\",\"revised_at\":null,\"revised_by_id\":null,\"revision_count\":\"0\"}', '2025-11-28 14:07:35'),
(992, 'finance_invoices', '{\"id\":\"23\",\"invoice_number\":\"BKGE-INV-25-26-002\",\"invoice_date\":\"2025-09-22\",\"due_date\":\"2025-12-20\",\"reference\":\"\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"gst_type\":\"igst\",\"subtotal\":\"725210.00\",\"total_tax\":\"130537.80\",\"total_amount\":\"855747.80\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"130537.80\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"payment_status\":\"unpaid\",\"paid_amount\":\"0.00\",\"outstanding_amount\":\"855747.80\",\"last_payment_date\":null,\"payment_due_date\":null,\"invoice_type\":\"tax_invoice\",\"status\":\"draft\",\"notes\":\"Tax invoice for GST filing\",\"terms_and_conditions\":\"\",\"created_at\":\"2025-11-20 06:21:41.576889+00\",\"updated_at\":\"2025-11-20 06:21:41.576898+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"shipping_address_id\":null,\"proforma_invoice_id\":null,\"purchase_order_id\":\"55\",\"gst_transaction_id\":\"\",\"gstr1_filing_date\":null,\"is_filed_in_gstr1\":\"f\",\"place_of_supply\":\"\",\"reverse_charge_applicable\":\"f\",\"quotation_id\":null,\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null,\"is_revised\":\"f\",\"revised_at\":null,\"revised_by_id\":null,\"revision_count\":\"0\"}', '2025-11-28 14:07:35'),
(993, 'finance_invoices', '{\"id\":\"28\",\"invoice_number\":\"SEINV006\",\"invoice_date\":\"2025-11-24\",\"due_date\":\"2025-12-24\",\"reference\":\"\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"gst_type\":\"exempt\",\"subtotal\":\"2421.00\",\"total_tax\":\"0.00\",\"total_amount\":\"2421.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"payment_status\":\"unpaid\",\"paid_amount\":\"0.00\",\"outstanding_amount\":\"2421.00\",\"last_payment_date\":null,\"payment_due_date\":null,\"invoice_type\":\"tax_invoice\",\"status\":\"draft\",\"notes\":\"Tax invoice for GST filing\",\"terms_and_conditions\":\"\",\"created_at\":\"2025-11-24 05:48:43.071443+00\",\"updated_at\":\"2025-11-24 05:48:43.071456+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"proforma_invoice_id\":null,\"purchase_order_id\":\"8\",\"gst_transaction_id\":\"\",\"gstr1_filing_date\":null,\"is_filed_in_gstr1\":\"f\",\"place_of_supply\":\"\",\"reverse_charge_applicable\":\"f\",\"quotation_id\":null,\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null,\"is_revised\":\"f\",\"revised_at\":null,\"revised_by_id\":null,\"revision_count\":\"0\"}', '2025-11-28 14:07:35'),
(994, 'finance_quotations', '{\"id\":\"33\",\"quotation_number\":\"TCQUO001\",\"quotation_date\":\"2025-02-07\",\"valid_until\":\"2025-12-20\",\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"30000.00\",\"total_tax\":\"5400.00\",\"total_amount\":\"35400.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"5400.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"sent\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-20 10:11:10.671906+00\",\"updated_at\":\"2025-11-24 08:30:17.181007+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"revised_by_id\":null,\"shipping_address_id\":null,\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"t\",\"po_created_at\":\"2025-11-24 08:28:27.440999+00\",\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"35400.00\",\"remaining_proforma_balance\":\"30000.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(995, 'finance_quotations', '{\"id\":\"34\",\"quotation_number\":\"BKGE-QT-25-26-001\",\"quotation_date\":\"2025-11-26\",\"valid_until\":\"2025-12-26\",\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"subtotal\":\"409090.00\",\"total_tax\":\"73636.20\",\"total_amount\":\"482726.20\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"73636.20\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"sent\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-26 05:01:55.534465+00\",\"updated_at\":\"2025-11-26 05:05:49.616398+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"revised_by_id\":null,\"shipping_address_id\":\"1763614021854\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"482726.20\",\"remaining_proforma_balance\":\"409090.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(996, 'finance_quotations', '{\"id\":\"26\",\"quotation_number\":\"SEQUO013\",\"quotation_date\":\"2025-05-10\",\"valid_until\":\"2025-06-10\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"6100.00\",\"total_tax\":\"0.00\",\"total_amount\":\"6100.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:06:02.291006+00\",\"updated_at\":\"2025-11-18 13:06:02.291017+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"revised_by_id\":null,\"shipping_address_id\":\"1763462740501\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(997, 'finance_quotations', '{\"id\":\"35\",\"quotation_number\":\"TCQUO002\",\"quotation_date\":\"2025-08-10\",\"valid_until\":\"2025-12-28\",\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"97000.00\",\"total_tax\":\"17460.00\",\"total_amount\":\"114460.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"17460.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-28 09:04:52.322953+00\",\"updated_at\":\"2025-11-28 09:04:52.322963+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"revised_by_id\":null,\"shipping_address_id\":\"1763638507469\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(998, 'finance_quotations', '{\"id\":\"28\",\"quotation_number\":\"SEQUO015\",\"quotation_date\":\"2025-06-23\",\"valid_until\":\"2025-06-23\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"30000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"30000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:10:38.890914+00\",\"updated_at\":\"2025-11-18 13:10:38.890921+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"revised_by_id\":null,\"shipping_address_id\":\"1763462740501\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(999, 'finance_quotations', '{\"id\":\"27\",\"quotation_number\":\"SEQUO014\",\"quotation_date\":\"2025-07-18\",\"valid_until\":\"2025-08-18\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"97000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"97000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:09:04.872093+00\",\"updated_at\":\"2025-11-18 13:10:51.478828+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"revised_by_id\":null,\"shipping_address_id\":\"1763462740501\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(1000, 'finance_quotations', '{\"id\":\"29\",\"quotation_number\":\"SEQUO016\",\"quotation_date\":\"2025-07-09\",\"valid_until\":\"2025-08-09\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"33ABCPT7999Q1ZG\",\"company_gstin\":\"\",\"subtotal\":\"9243.00\",\"total_tax\":\"0.00\",\"total_amount\":\"9243.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:12:41.276297+00\",\"updated_at\":\"2025-11-18 13:12:41.276304+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"11\",\"revised_by_id\":null,\"shipping_address_id\":null,\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(1001, 'finance_quotations', '{\"id\":\"30\",\"quotation_number\":\"SEQUO017\",\"quotation_date\":\"2025-07-28\",\"valid_until\":\"2025-07-28\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"33AAAFD0525H1Z4\",\"company_gstin\":\"\",\"subtotal\":\"120000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"120000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:19:19.458947+00\",\"updated_at\":\"2025-11-18 13:19:19.458957+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"12\",\"revised_by_id\":null,\"shipping_address_id\":null,\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(1002, 'finance_quotations', '{\"id\":\"31\",\"quotation_number\":\"SEQUO018\",\"quotation_date\":\"2025-09-03\",\"valid_until\":\"2025-10-09\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"33ABCPT7999Q1ZG\",\"company_gstin\":\"\",\"subtotal\":\"76015.80\",\"total_tax\":\"0.00\",\"total_amount\":\"76015.80\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:21:21.151418+00\",\"updated_at\":\"2025-11-18 13:21:21.151425+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"11\",\"revised_by_id\":null,\"shipping_address_id\":null,\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(1003, 'finance_quotations', '{\"id\":\"32\",\"quotation_number\":\"SEQUO019\",\"quotation_date\":\"2025-11-03\",\"valid_until\":\"2025-12-03\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"25850.00\",\"total_tax\":\"0.00\",\"total_amount\":\"25850.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"is_revised\":\"f\",\"revision_count\":\"0\",\"revised_at\":null,\"created_at\":\"2025-11-18 13:30:59.726774+00\",\"updated_at\":\"2025-11-20 07:06:38.928323+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"revised_by_id\":null,\"shipping_address_id\":\"1763462740501\",\"invoice_created\":\"f\",\"invoice_created_at\":null,\"po_created\":\"f\",\"po_created_at\":null,\"claim_type\":null,\"invoice_claimed_amount\":\"0.00\",\"proforma_claimed_amount\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"proforma_created\":\"f\",\"is_rejected\":\"f\",\"rejected_at\":null,\"rejected_by_id\":null,\"rejection_reason\":null}', '2025-11-28 14:07:35'),
(1004, 'finance_customer', '{\"id\":\"11\",\"customer_code\":\"SECUS003\",\"customer_type\":\"business\",\"name\":\"TAMILVANAN INDUSTRIES\",\"display_name\":\"TAMILVANAN INDUSTRIES\",\"email\":\"\",\"phone\":\"9965563637\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"187, Tiruchuli Kallikudi Road, Vaiyampatti road,\",\"billing_address_line2\":\"Virudhunagar , TamiKariapattilnadu-626001\",\"billing_city\":\"Virudhunagar\",\"billing_state\":\"Tamilnadu\",\"billing_pincode\":\"626001\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"t\",\"shipping_address_line1\":\"\",\"shipping_address_line2\":\"\",\"shipping_city\":\"\",\"shipping_state\":\"\",\"shipping_pincode\":\"\",\"shipping_country\":\"\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33ABCPT7999Q1ZG\",\"pan_number\":\"ABCPT7999Q\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-14 12:23:53.004821+00\",\"updated_at\":\"2025-11-14 12:34:00.010612+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1005, 'finance_customer', '{\"id\":\"9\",\"customer_code\":\"SECUS001\",\"customer_type\":\"business\",\"name\":\"Prozeal Green Energy Limited\",\"display_name\":\"Prozeal Green Energy Ltd\",\"email\":\"\",\"phone\":\"7940191727\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Prozeal Green Energy Ltd, 1209 to 1212 , West Wing, Stratum @Venus Grounds,\",\"billing_address_line2\":\"Nehru Nagar, Ahmedabad, Gujarat-380015\",\"billing_city\":\"Ahmedabad\",\"billing_state\":\"Gujarat\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Prozeal Green Energy Ltd, 1209 to 1212 , West Wing, Stratum @Venus Grounds,\",\"shipping_address_line2\":\"Nehru Nagar, Ahmedabad, Gujarat-380015\",\"shipping_city\":\"Ahmedabad\",\"shipping_state\":\"Gujarat\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"24AAHCP3289L1ZY\",\"pan_number\":\"AAHCP3289L\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-14 10:40:54.500909+00\",\"updated_at\":\"2025-11-18 10:53:19.170363+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"24\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1006, 'finance_customer', '{\"id\":\"27\",\"customer_code\":\"MAK47CUS001\",\"customer_type\":\"business\",\"name\":\"TVS Electronics Limited\",\"display_name\":\"TVS Electronics Limited\",\"email\":\"\",\"phone\":\"\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Arihant E-Park 9th Floor, L.B.Road, Adyar, Chennai-600 020.\",\"billing_address_line2\":\"\",\"billing_city\":\"Chennai\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"630305\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Arihant E-Park 9th Floor, L.B.Road, Adyar, Chennai-600 020.\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Chennai\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"630305\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33AAACI0886K1ZI\",\"pan_number\":\"AAACI0886K\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-20 03:41:11.223956+00\",\"updated_at\":\"2025-11-20 03:54:30.45901+00\",\"company_id\":\"16\",\"created_by_id\":\"33\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1007, 'finance_customer', '{\"id\":\"24\",\"customer_code\":\"ASCUS001\",\"customer_type\":\"business\",\"name\":\"Prozeal Green Energy  Ltd\",\"display_name\":\"Prozeal Green Energy  Ltd\",\"email\":\"\",\"phone\":\"\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"1209 and 1210 , West Wing, 12th Floor, Stratum @ Venus Grounds, Near Jhansi Ki Rani Statue, Satellite Rd\",\"billing_address_line2\":\"Nehru Nagar,, Ahmedabad, Gujarat-380015\",\"billing_city\":\"Ahmedabad\",\"billing_state\":\"Gujarat\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"1209 and 1210 , West Wing, 12th Floor, Stratum @ Venus Grounds, Near Jhansi Ki Rani Statue, Satellite Rd\",\"shipping_address_line2\":\"Nehru Nagar,, Ahmedabad, Gujarat-380015\",\"shipping_city\":\"Ahmedabad\",\"shipping_state\":\"Gujarat\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"24AAHCP3289L1ZY\",\"pan_number\":\"AAHCP3289L\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-18 17:46:04.089137+00\",\"updated_at\":\"2025-11-18 17:46:04.089143+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"2\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1008, 'finance_customer', '{\"id\":\"12\",\"customer_code\":\"SECUS004\",\"customer_type\":\"business\",\"name\":\"DSK Electricals\",\"display_name\":\"DSK Electricals\",\"email\":\"\",\"phone\":\"6380795088\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"DSK Electricals  B1-C1 First Floor, Gemini parson commercial complex No.600,Anna Salai Chennai: 600006.\",\"billing_address_line2\":\"\",\"billing_city\":\"Chennai\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"600006\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"DSK Electricals  B1-C1 First Floor, Gemini parson commercial complex No.600,Anna Salai Chennai: 600006.\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Chennai\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"600006\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33AAAFD0525H1Z4\",\"pan_number\":\"AAAFD0525H\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-16 05:44:54.16114+00\",\"updated_at\":\"2025-11-20 07:08:52.518625+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"3\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1009, 'finance_customer', '{\"id\":\"26\",\"customer_code\":\"ASCUS003\",\"customer_type\":\"business\",\"name\":\"Pollax Solar Solutions Pvt Ltd\",\"display_name\":\"Pollax Solar Solutions Pvt Ltd\",\"email\":\"\",\"phone\":\"\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Unit No : 7 & 8, FIRST FLOOR, PINNACLE BUILDING, MANAMATHY, Manambathi, Kanchipuram,\",\"billing_address_line2\":\"\",\"billing_city\":\"Kanchipuram\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"630305\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Unit No : 7 & 8, FIRST FLOOR, PINNACLE BUILDING, MANAMATHY, Manambathi, Kanchipuram,\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Kanchipuram\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"630305\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33AANCP0666M1Z0\",\"pan_number\":\"AANCP0666M\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-18 17:56:22.371938+00\",\"updated_at\":\"2025-11-19 15:50:51.498614+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1010, 'finance_customer', '{\"id\":\"10\",\"customer_code\":\"SECUS002\",\"customer_type\":\"business\",\"name\":\"MAK47\",\"display_name\":\"MAK47\",\"email\":\"manicanter3050@gmail.com\",\"phone\":\"9787873050\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"No 146, Achani Village, Veppangulam, Sivagangai, Tamilnadu-630305\",\"billing_address_line2\":\"\",\"billing_city\":\"Sivagangai\",\"billing_state\":\"Tamilnadu\",\"billing_pincode\":\"630305\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"No 146, Achani Village, Veppangulam, Sivagangai, Tamilnadu-630305\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Sivagangai\",\"shipping_state\":\"Tamilnadu\",\"shipping_pincode\":\"630305\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33CTQPM7467J1ZX\",\"pan_number\":\"CTQPM7467J\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-14 12:10:46.102548+00\",\"updated_at\":\"2025-11-20 07:12:11.091762+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1011, 'finance_customer', '{\"id\":\"23\",\"customer_code\":\"BKCCUS007\",\"customer_type\":\"business\",\"name\":\"Prozeal Green Energy Private Limited\",\"display_name\":\"Prozeal Green Energy Private Limited\",\"email\":\"\",\"phone\":\"\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"1209 to 1212 , West Wing, 12th Floor, Stratum @Venus Grounds, Near Jhansi Ki Rani Statue, Satellite Rd,\",\"billing_address_line2\":\"Nehru Nagar,\",\"billing_city\":\"Ahmedabad,\",\"billing_state\":\"Gujarat\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"1209 to 1212 , West Wing, 12th Floor, Stratum @Venus Grounds, Near Jhansi Ki Rani Statue, Satellite Rd,\",\"shipping_address_line2\":\"Nehru Nagar,\",\"shipping_city\":\"Ahmedabad,\",\"shipping_state\":\"Gujarat\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"24AAHCP3289L1ZY\",\"pan_number\":\"AAHCP3289L\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-18 10:49:24.393808+00\",\"updated_at\":\"2025-11-20 05:50:58.485345+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\":\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1012, 'finance_customer', '{\"id\":\"28\",\"customer_code\":\"BKGECUS001\",\"customer_type\":\"business\",\"name\":\"Prozeal Green Energy Limited\",\"display_name\":\"Prozeal Green Energy Ltd\",\"email\":\"\",\"phone\":\"6374075829\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Prozeal Green Energy Limited, 1209 to 1212 , West Wing, Stratum @Venus Grounds,\",\"billing_address_line2\":\"Nehru Nagar, Ahmedabad, Gujarat-380015\",\"billing_city\":\"Ahmedabad\",\"billing_state\":\"Gujarat\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Prozeal Green Energy Limited, 1209 to 1212 , West Wing, Stratum @Venus Grounds,\",\"shipping_address_line2\":\"Nehru Nagar, Ahmedabad, Gujarat-380015\",\"shipping_city\":\"Ahmedabad\",\"shipping_state\":\"Gujarat\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"24AAHCP3289L1ZY\",\"pan_number\":\"AAHCP3289L\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-20 04:51:17.020097+00\",\"updated_at\":\"2025-11-20 06:11:23.658684+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"24\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1013, 'finance_customer', '{\"id\":\"25\",\"customer_code\":\"ASCUS002\",\"customer_type\":\"business\",\"name\":\"Lucmen Energgy Pvt Ltd\",\"display_name\":\"Lucmen Energgy Pvt Ltd\",\"email\":\"\",\"phone\":\"\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Plot No 74, Ramani Flats, 4th Street, Ashtalakshmi Nagar, West Thambaram,  Chennai \\u2013 600048.\",\"billing_address_line2\":\"\",\"billing_city\":\"Chennai\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"630305\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Plot No 74, Ramani Flats, 4th Street, Ashtalakshmi Nagar, West Thambaram,  Chennai \\u2013 600048.\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Chennai\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"630305\",\"shipping_country\":\"India\",\"business_type\":\"other\",\"industry\":\"\",\"gstin\":\"33AADCL2517M1ZK\",\"pan_number\":\"AADCL2517M\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-18 17:51:06.378623+00\",\"updated_at\":\"2025-11-20 10:02:05.026413+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1014, 'finance_customer', '{\"id\":\"16\",\"customer_code\":\"TCCUS004\",\"customer_type\":\"business\",\"name\":\"Torrent Saurya Urja 5 Pvt Ltd\",\"display_name\":\"Torrent Saurya Urja 5 Pvt Ltd\",\"email\":\"\",\"phone\":\"9080654027\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Samanvay 600 Tapovan, Ambavadi,  Ahmedabad, Gujarat \\u2013 380015\",\"billing_address_line2\":\"\",\"billing_city\":\"Ahmedabad\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Samanvay 600 Tapovan, Ambavadi,  Ahmedabad, Gujarat \\u2013 380015\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Ahmedabad\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"trust\",\"industry\":\"\",\"gstin\":\"24AAICT7384F1Z3\",\"pan_number\":\"AAICT7384F\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-17 10:42:06.758387+00\",\"updated_at\":\"2025-11-20 12:32:03.253156+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"2\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1015, 'finance_customer', '{\"id\":\"13\",\"customer_code\":\"TCCUS001\",\"customer_type\":\"business\",\"name\":\"Prozeal Green Energy Ltd\",\"display_name\":\"Prozeal Green Energy Ltd\",\"email\":\"\",\"phone\":\"7940191727\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"1209 to 1212 , West Wing, Stratum @Venus Grounds, Nehru Nagar, Ahmedabad, Gujarat-380015\",\"billing_address_line2\":\"\",\"billing_city\":\"Ahmedabad\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"380015\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"1209 to 1212 , West Wing, Stratum @Venus Grounds, Nehru Nagar, Ahmedabad, Gujarat-380015\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Ahmedabad\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"380015\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"24AAHCP3289L1ZY\",\"pan_number\":\"AAHCP3289L\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-17 10:24:45.450336+00\",\"updated_at\":\"2025-11-20 12:14:16.786069+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"2\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1016, 'finance_customer', '{\"id\":\"15\",\"customer_code\":\"TCCUS003\",\"customer_type\":\"business\",\"name\":\"Thiagarajar Mills (P) Ltd\",\"display_name\":\"Thiagarajar Mills (P) Ltd\",\"email\":\"\",\"phone\":\"9080654027\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Ottapidaram,Tuticorin,  Tamil Nadu - 628401.\",\"billing_address_line2\":\"\",\"billing_city\":\"Tuticorin\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"628401\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Ottapidaram,Tuticorin,  Tamil Nadu - 628401.\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Tuticorin\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"628401\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33AAACT4304R1Z8\",\"pan_number\":\"AAACT4304R\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-17 10:36:42.301635+00\",\"updated_at\":\"2025-11-20 12:35:17.272042+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1017, 'finance_customer', '{\"id\":\"14\",\"customer_code\":\"TCCUS002\",\"customer_type\":\"business\",\"name\":\"Prathama Solarconnect Energy Private Limited\",\"display_name\":\"Prathama Solarconnect Energy Private Limited\",\"email\":\"\",\"phone\":\"9080654027\",\"mobile\":\"\",\"website\":\"\",\"billing_address_line1\":\"Survey No.147, Sub Division 3B, Veppankulam Village, Karaikudi Taluka, Sivagangai Dist - 630302, Tamil Nadu\",\"billing_address_line2\":\"\",\"billing_city\":\"Sivagangai Dist\",\"billing_state\":\"TamilNadu\",\"billing_pincode\":\"630302\",\"billing_country\":\"India\",\"shipping_same_as_billing\":\"f\",\"shipping_address_line1\":\"Survey No.147, Sub Division 3B, Veppankulam Village, Karaikudi Taluka, Sivagangai Dist - 630302, Tamil Nadu\",\"shipping_address_line2\":\"\",\"shipping_city\":\"Sivagangai Dist\",\"shipping_state\":\"TamilNadu\",\"shipping_pincode\":\"630302\",\"shipping_country\":\"India\",\"business_type\":\"\",\"industry\":\"\",\"gstin\":\"33AAKCP3080G1ZI\",\"pan_number\":\"AAKCP3080G\",\"aadhar_number\":\"\",\"bank_name\":\"\",\"bank_account_number\":\"\",\"bank_ifsc_code\":\"\",\"bank_branch\":\"\",\"credit_limit\":\"0.00\",\"payment_terms\":\"\",\"currency\":\"INR\",\"project_area\":\"\",\"notes\":\"\",\"is_active\":\"t\",\"created_at\":\"2025-11-17 10:32:49.66764+00\",\"updated_at\":\"2025-11-20 12:02:48.502418+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"gst_registration_date\":null,\"is_gst_registered\":\"t\",\"state_code\":\"33\",\"account_holder_name\":\"\",\"bank_verification_status\":\"pending\",\"bank_verified_date\":null,\"last_statement_import\":null,\"statement_import_enabled\":\"f\",\"opening_balance\":\"0.00\",\"opening_balance_date\":null}', '2025-11-28 14:07:36'),
(1018, 'finance_purchase_orders', '{\"id\":\"14\",\"po_number\":\"PGEL\\/ 24-25\\/ 4784\",\"po_date\":\"2025-05-22\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU007\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"2421.00\",\"total_tax\":\"0.00\",\"total_amount\":\"2421.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:23:33.775391+00\",\"updated_at\":\"2025-11-20 05:56:54.050684+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1019, 'finance_purchase_orders', '{\"id\":\"57\",\"po_number\":\"PGEL\\/25-26\\/4537\",\"po_date\":\"2025-09-19\",\"po_file\":\"po_files\\/BK_GREEN_ENERGY_-_4537_-_Material_Sifting_-_Signed_Copy.pdf\",\"internal_po_number\":\"BKGE-PO-25-26-001\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"subtotal\":\"448810.00\",\"total_tax\":\"80785.80\",\"total_amount\":\"529595.80\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"80785.80\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 07:11:22.085989+00\",\"updated_at\":\"2025-11-20 07:11:22.086004+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"shipping_address_id\":\"1763614021854\",\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1020, 'finance_purchase_orders', '{\"id\":\"56\",\"po_number\":\"PGEL\\/25-26\\/3955\",\"po_date\":\"2025-08-13\",\"po_file\":\"po_files\\/3955_-_BK_GREEN_ENERGY_-_13.08.2025_-_TU22_PHENIX_18MW_24_1.pdf\",\"internal_po_number\":\"BKGEPOU002\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"subtotal\":\"15300000.00\",\"total_tax\":\"2754000.00\",\"total_amount\":\"18054000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"2754000.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 05:28:15.755137+00\",\"updated_at\":\"2025-11-20 07:29:29.359815+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"shipping_address_id\":\"1763614097268\",\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1021, 'finance_purchase_orders', '{\"id\":\"12\",\"po_number\":\"PGEL\\/25-26\\/4232\",\"po_date\":\"2025-09-13\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU005\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"30000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"30000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:11:14.193503+00\",\"updated_at\":\"2025-11-20 05:49:59.414211+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36');
INSERT INTO `finance_data` (`id`, `table_name`, `data`, `created_at`) VALUES
(1022, 'finance_purchase_orders', '{\"id\":\"13\",\"po_number\":\"PGEL\\/25-26\\/5153\",\"po_date\":\"2025-11-08\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU006\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"25850.00\",\"total_tax\":\"0.00\",\"total_amount\":\"25850.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:15:56.804144+00\",\"updated_at\":\"2025-11-19 02:15:56.804154+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1023, 'finance_purchase_orders', '{\"id\":\"11\",\"po_number\":\"PGEL\\/25-26\\/748\",\"po_date\":\"2025-06-26\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU004\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"97000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"97000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:07:37.834837+00\",\"updated_at\":\"2025-11-20 05:50:12.524101+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1024, 'finance_purchase_orders', '{\"id\":\"8\",\"po_number\":\"PGEL\\/ 24-25\\/ 4784\",\"po_date\":\"2025-05-22\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU001\",\"quotation_date\":\"2025-05-22\",\"valid_until\":\"2025-06-18\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"2421.00\",\"total_tax\":\"0.00\",\"total_amount\":\"2421.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"completed\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"percentage\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"2421.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"completed\",\"created_at\":\"2025-11-18 16:00:40.815424+00\",\"updated_at\":\"2025-11-24 05:33:29.339538+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1025, 'finance_purchase_orders', '{\"id\":\"9\",\"po_number\":\"PGEL\\/25-26\\/2457\",\"po_date\":\"2025-06-07\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU002\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"6100.00\",\"total_tax\":\"0.00\",\"total_amount\":\"6100.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:03:25.2343+00\",\"updated_at\":\"2025-11-20 05:50:33.539132+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1026, 'finance_purchase_orders', '{\"id\":\"10\",\"po_number\":\"PGEL\\/24-25\\/5565\",\"po_date\":\"2025-06-26\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU003\",\"quotation_date\":\"2025-11-19\",\"valid_until\":\"2025-12-19\",\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"97000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"97000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 02:05:35.161251+00\",\"updated_at\":\"2025-11-19 02:05:35.161259+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1027, 'finance_purchase_orders', '{\"id\":\"20\",\"po_number\":\"PGEL\\/24-25\\/386\",\"po_date\":\"2024-05-10\",\"po_file\":\"\",\"internal_po_number\":\"BKCPOU006\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"550000.00\",\"total_tax\":\"99000.00\",\"total_amount\":\"649000.00\",\"cgst_amount\":\"49500.00\",\"sgst_amount\":\"49500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 08:44:46.109276+00\",\"updated_at\":\"2025-11-19 08:44:46.109285+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1028, 'finance_purchase_orders', '{\"id\":\"16\",\"po_number\":\"PGEL\\/24-25\\/199\",\"po_date\":\"2024-04-27\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_199_-_YbJ96jh.pdf\",\"internal_po_number\":\"BKCPOU002\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"3278075.00\",\"total_tax\":\"590053.50\",\"total_amount\":\"3868128.50\",\"cgst_amount\":\"295026.75\",\"sgst_amount\":\"295026.75\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 07:04:30.993775+00\",\"updated_at\":\"2025-11-19 07:07:36.712418+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1029, 'finance_purchase_orders', '{\"id\":\"58\",\"po_number\":\"3130042470\",\"po_date\":\"2025-11-20\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU005\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAICT7384F1Z3\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"48000.00\",\"total_tax\":\"5760.00\",\"total_amount\":\"53760.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"5760.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"percentage\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"53760.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"completed\",\"created_at\":\"2025-11-20 11:14:57.856766+00\",\"updated_at\":\"2025-11-20 11:14:57.856783+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"16\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1030, 'finance_purchase_orders', '{\"id\":\"35\",\"po_number\":\"PGEL\\/25-26\\/2200\",\"po_date\":\"2025-05-30\",\"po_file\":\"po_files\\/2200_-_B_K_Constructions_-_30.05.2025_-_OTTAPIDARAM_33KV_TL.pdf\",\"internal_po_number\":\"BKCPOU021\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"900000.00\",\"total_tax\":\"162000.00\",\"total_amount\":\"1062000.00\",\"cgst_amount\":\"81000.00\",\"sgst_amount\":\"81000.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:52:15.426203+00\",\"updated_at\":\"2025-11-19 09:52:15.426212+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1031, 'finance_purchase_orders', '{\"id\":\"37\",\"po_number\":\"PGEL\\/24-25\\/4110\",\"po_date\":\"2024-12-20\",\"po_file\":\"po_files\\/3144_4w0OFe4.pdf\",\"internal_po_number\":\"BKCPOU023\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"2990000.00\",\"total_tax\":\"538200.00\",\"total_amount\":\"3528200.00\",\"cgst_amount\":\"269100.00\",\"sgst_amount\":\"269100.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:56:34.287879+00\",\"updated_at\":\"2025-11-19 12:25:43.731557+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1032, 'finance_purchase_orders', '{\"id\":\"36\",\"po_number\":\"PGEL\\/24-25\\/5185\",\"po_date\":\"2025-02-24\",\"po_file\":\"po_files\\/5185.pdf\",\"internal_po_number\":\"BKCPOU022\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"3050000.00\",\"total_tax\":\"549000.00\",\"total_amount\":\"3599000.00\",\"cgst_amount\":\"274500.00\",\"sgst_amount\":\"274500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:54:07.249216+00\",\"updated_at\":\"2025-11-19 09:54:07.249227+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1033, 'finance_purchase_orders', '{\"id\":\"18\",\"po_number\":\"PGEL\\/24-25\\/5,115\",\"po_date\":\"2025-08-05\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_5115_-_B_K_Constructions.pdf\",\"internal_po_number\":\"BKCPOU004\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"250000.00\",\"total_tax\":\"45000.00\",\"total_amount\":\"295000.00\",\"cgst_amount\":\"22500.00\",\"sgst_amount\":\"22500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 07:10:37.25478+00\",\"updated_at\":\"2025-11-19 07:10:37.254792+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1034, 'finance_purchase_orders', '{\"id\":\"25\",\"po_number\":\"PGEL\\/24-25\\/1171\",\"po_date\":\"2024-06-20\",\"po_file\":\"po_files\\/PGEL-PO-2425-1171_C804xFS.pdf\",\"internal_po_number\":\"BKCPOU011\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1150000.00\",\"total_tax\":\"207000.00\",\"total_amount\":\"1357000.00\",\"cgst_amount\":\"103500.00\",\"sgst_amount\":\"103500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:31:50.022179+00\",\"updated_at\":\"2025-11-19 09:34:27.194045+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1035, 'finance_purchase_orders', '{\"id\":\"21\",\"po_number\":\"PGEL\\/24-25\\/1172\",\"po_date\":\"2025-04-01\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_1172_R1_-_B_K_Constructions_1_rzzyxWr.pdf\",\"internal_po_number\":\"BKCPOU007\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1250000.00\",\"total_tax\":\"225000.00\",\"total_amount\":\"1475000.00\",\"cgst_amount\":\"112500.00\",\"sgst_amount\":\"112500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 08:46:14.98514+00\",\"updated_at\":\"2025-11-19 08:59:08.548182+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1036, 'finance_purchase_orders', '{\"id\":\"15\",\"po_number\":\"PGEL\\/24-25\\/1150\",\"po_date\":\"2024-06-20\",\"po_file\":\"po_files\\/PGEL-PO-2425-1150_1.pdf\",\"internal_po_number\":\"BKCPOU001\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1200000.00\",\"total_tax\":\"216000.00\",\"total_amount\":\"1416000.00\",\"cgst_amount\":\"108000.00\",\"sgst_amount\":\"108000.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 06:31:16.09434+00\",\"updated_at\":\"2025-11-19 06:31:16.094361+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1037, 'finance_purchase_orders', '{\"id\":\"17\",\"po_number\":\"PGEL\\/24-25\\/385\",\"po_date\":\"2024-05-10\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_385_-_B_K_Construction.pdf\",\"internal_po_number\":\"BKCPOU003\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"415000.00\",\"total_tax\":\"74700.00\",\"total_amount\":\"489700.00\",\"cgst_amount\":\"37350.00\",\"sgst_amount\":\"37350.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 07:07:18.719301+00\",\"updated_at\":\"2025-11-19 07:07:18.719311+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1038, 'finance_purchase_orders', '{\"id\":\"24\",\"po_number\":\"PGEL\\/24-25\\/5219\",\"po_date\":\"2025-02-26\",\"po_file\":\"po_files\\/Supply_Order_Item_V1_B_mM3UMFy.K_Construction.pdf\",\"internal_po_number\":\"BKCPOU010\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"6199600.00\",\"total_tax\":\"1115928.00\",\"total_amount\":\"7315528.00\",\"cgst_amount\":\"557964.00\",\"sgst_amount\":\"557964.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:26:36.366688+00\",\"updated_at\":\"2025-11-19 09:29:38.336497+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1039, 'finance_purchase_orders', '{\"id\":\"22\",\"po_number\":\"PGEL\\/24-25\\/200\",\"po_date\":\"2024-04-27\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_200_Parvathi_Dyeing_Pvt_Ltd_1_6TOZtp4.pdf\",\"internal_po_number\":\"BKCPOU008\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"2430515.00\",\"total_tax\":\"437492.70\",\"total_amount\":\"2868007.70\",\"cgst_amount\":\"218746.35\",\"sgst_amount\":\"218746.35\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:20:12.80148+00\",\"updated_at\":\"2025-11-19 09:20:12.801497+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1040, 'finance_purchase_orders', '{\"id\":\"23\",\"po_number\":\"PGEL\\/25-26\\/3473\",\"po_date\":\"2025-07-15\",\"po_file\":\"po_files\\/3473_-_B_K_Constructions_-_15.07.2025_-_SPV_PROZEAL.pdf\",\"internal_po_number\":\"BKCPOU009\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"165750.00\",\"total_tax\":\"29835.00\",\"total_amount\":\"195585.00\",\"cgst_amount\":\"14917.50\",\"sgst_amount\":\"14917.50\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:22:02.947351+00\",\"updated_at\":\"2025-11-19 09:22:02.947434+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1041, 'finance_purchase_orders', '{\"id\":\"30\",\"po_number\":\"PGEL\\/24-25\\/201\",\"po_date\":\"2024-04-27\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_201_-_B_K_Constr.pdf\",\"internal_po_number\":\"BKCPOU016\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"2062070.00\",\"total_tax\":\"371172.60\",\"total_amount\":\"2433242.60\",\"cgst_amount\":\"185586.30\",\"sgst_amount\":\"185586.30\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:42:25.110866+00\",\"updated_at\":\"2025-11-19 09:42:25.110876+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1042, 'finance_purchase_orders', '{\"id\":\"29\",\"po_number\":\"PGEL\\/24-25\\/1170\",\"po_date\":\"2024-06-20\",\"po_file\":\"po_files\\/PGEL-PO-2425-1170.pdf\",\"internal_po_number\":\"BKCPOU015\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1150000.00\",\"total_tax\":\"207000.00\",\"total_amount\":\"1357000.00\",\"cgst_amount\":\"103500.00\",\"sgst_amount\":\"103500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:39:53.414204+00\",\"updated_at\":\"2025-11-19 09:39:53.414221+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1043, 'finance_purchase_orders', '{\"id\":\"27\",\"po_number\":\"PGEL\\/24-25\\/1149\",\"po_date\":\"2024-06-20\",\"po_file\":\"po_files\\/PGEL-PO-2425-1149.pdf\",\"internal_po_number\":\"BKCPOU013\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1150000.00\",\"total_tax\":\"207000.00\",\"total_amount\":\"1357000.00\",\"cgst_amount\":\"103500.00\",\"sgst_amount\":\"103500.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:36:15.013433+00\",\"updated_at\":\"2025-11-19 09:36:15.013447+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1044, 'finance_purchase_orders', '{\"id\":\"26\",\"po_number\":\"PGEL\\/24-25\\/388\",\"po_date\":\"2024-05-10\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_388_-_B_K_Construction.pdf\",\"internal_po_number\":\"BKCPOU012\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"415000.00\",\"total_tax\":\"74700.00\",\"total_amount\":\"489700.00\",\"cgst_amount\":\"37350.00\",\"sgst_amount\":\"37350.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:32:58.34153+00\",\"updated_at\":\"2025-11-19 09:32:58.341541+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1045, 'finance_purchase_orders', '{\"id\":\"28\",\"po_number\":\"PGEL\\/24-25\\/384\",\"po_date\":\"2024-05-10\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_384_-_B_K_Construction.pdf\",\"internal_po_number\":\"BKCPOU014\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"415000.00\",\"total_tax\":\"74700.00\",\"total_amount\":\"489700.00\",\"cgst_amount\":\"37350.00\",\"sgst_amount\":\"37350.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:37:19.908343+00\",\"updated_at\":\"2025-11-19 09:37:19.908354+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1046, 'finance_purchase_orders', '{\"id\":\"31\",\"po_number\":\"PGEL\\/24-25\\/387\",\"po_date\":\"2024-05-10\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_387_-_B_K_Construction.pdf\",\"internal_po_number\":\"BKCPOU017\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"415000.00\",\"total_tax\":\"74700.00\",\"total_amount\":\"489700.00\",\"cgst_amount\":\"37350.00\",\"sgst_amount\":\"37350.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:43:45.455826+00\",\"updated_at\":\"2025-11-19 09:43:45.455835+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1047, 'finance_purchase_orders', '{\"id\":\"32\",\"po_number\":\"PGEL\\/24-25\\/5591\",\"po_date\":\"2025-03-26\",\"po_file\":\"po_files\\/Purchase_Order_PGEL_PO_24-25_5591_-_B_K_Constructions.pdf\",\"internal_po_number\":\"BKCPOU018\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"110880.00\",\"total_tax\":\"19958.40\",\"total_amount\":\"130838.40\",\"cgst_amount\":\"9979.20\",\"sgst_amount\":\"9979.20\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:44:49.666953+00\",\"updated_at\":\"2025-11-19 09:44:49.66697+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1048, 'finance_purchase_orders', '{\"id\":\"33\",\"po_number\":\"PGEL\\/24-25\\/5100\",\"po_date\":\"2025-11-19\",\"po_file\":\"po_files\\/PGEL-24-25-5100.pdf\",\"internal_po_number\":\"BKCPOU019\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1333400.00\",\"total_tax\":\"240012.00\",\"total_amount\":\"1573412.00\",\"cgst_amount\":\"120006.00\",\"sgst_amount\":\"120006.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:47:12.040312+00\",\"updated_at\":\"2025-11-19 09:47:12.040322+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1049, 'finance_purchase_orders', '{\"id\":\"34\",\"po_number\":\"PGEL\\/24-25\\/5101\",\"po_date\":\"2025-02-20\",\"po_file\":\"po_files\\/PGEL-24-25-5101_4ltRobQ.pdf\",\"internal_po_number\":\"BKCPOU020\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"47520.00\",\"total_tax\":\"8553.60\",\"total_amount\":\"56073.60\",\"cgst_amount\":\"4276.80\",\"sgst_amount\":\"4276.80\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 09:48:37.33517+00\",\"updated_at\":\"2025-11-19 09:49:40.994449+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1050, 'finance_purchase_orders', '{\"id\":\"45\",\"po_number\":\"PGEL\\/24-25\\/4575\",\"po_date\":\"2025-01-17\",\"po_file\":\"\",\"internal_po_number\":\"ASPOU004\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33RZHPS7692D1ZJ\",\"subtotal\":\"57000.00\",\"total_tax\":\"10260.00\",\"total_amount\":\"67260.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"10260.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 17:08:30.163217+00\",\"updated_at\":\"2025-11-19 17:08:30.163227+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"customer_id\":\"24\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1051, 'finance_purchase_orders', '{\"id\":\"46\",\"po_number\":\"PGEL\\/25-26\\/2696\",\"po_date\":\"2025-06-11\",\"po_file\":\"\",\"internal_po_number\":\"ASPOU005\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33RZHPS7692D1ZJ\",\"subtotal\":\"64000.00\",\"total_tax\":\"11520.00\",\"total_amount\":\"75520.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"11520.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 17:13:14.888465+00\",\"updated_at\":\"2025-11-19 17:13:14.888482+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"customer_id\":\"24\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1052, 'finance_purchase_orders', '{\"id\":\"47\",\"po_number\":\"PGEL\\/25-26\\/2697\",\"po_date\":\"2025-06-11\",\"po_file\":\"\",\"internal_po_number\":\"ASPOU006\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33RZHPS7692D1ZJ\",\"subtotal\":\"64000.00\",\"total_tax\":\"11520.00\",\"total_amount\":\"75520.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"11520.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 17:20:36.793937+00\",\"updated_at\":\"2025-11-19 17:20:36.793945+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"customer_id\":\"24\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1053, 'finance_purchase_orders', '{\"id\":\"48\",\"po_number\":\"PGEL\\/25-26\\/2744\",\"po_date\":\"2025-06-12\",\"po_file\":\"\",\"internal_po_number\":\"ASPOU007\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33RZHPS7692D1ZJ\",\"subtotal\":\"37297.00\",\"total_tax\":\"6713.46\",\"total_amount\":\"44010.46\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"6713.46\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 17:22:51.409226+00\",\"updated_at\":\"2025-11-19 17:22:51.409238+00\",\"company_id\":\"15\",\"created_by_id\":\"32\",\"customer_id\":\"24\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1054, 'finance_purchase_orders', '{\"id\":\"49\",\"po_number\":\"PGEL\\/24-25\\/649\",\"po_date\":\"2025-03-24\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU001\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"1989000.00\",\"total_tax\":\"358020.00\",\"total_amount\":\"2347020.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"358020.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"percentage\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"2347020.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"completed\",\"created_at\":\"2025-11-20 01:43:41.580959+00\",\"updated_at\":\"2025-11-24 08:13:24.566436+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1055, 'finance_purchase_orders', '{\"id\":\"50\",\"po_number\":\"PGEL\\/25-26\\/4234\",\"po_date\":\"2025-09-13\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU002\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"60000.00\",\"total_tax\":\"10800.00\",\"total_amount\":\"70800.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"10800.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 01:50:35.5366+00\",\"updated_at\":\"2025-11-20 01:50:35.536611+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1056, 'finance_purchase_orders', '{\"id\":\"38\",\"po_number\":\"PGEL\\/24-25\\/3144\",\"po_date\":\"2024-10-21\",\"po_file\":\"po_files\\/3144_mAlFKVq.pdf\",\"internal_po_number\":\"BKCPOU024\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"7800000.00\",\"total_tax\":\"1404000.00\",\"total_amount\":\"9204000.00\",\"cgst_amount\":\"702000.00\",\"sgst_amount\":\"702000.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":null,\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"1840800.00\",\"remaining_proforma_balance\":\"6240000.00\",\"remaining_invoice_balance\":\"7363200.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"partial\",\"created_at\":\"2025-11-19 12:23:12.279395+00\",\"updated_at\":\"2025-11-19 12:24:43.145851+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1057, 'finance_purchase_orders', '{\"id\":\"51\",\"po_number\":\"9100002088\",\"po_date\":\"2025-10-07\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU003\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAICT7384F1Z3\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"48000.00\",\"total_tax\":\"5760.00\",\"total_amount\":\"53760.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"5760.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 01:52:48.938601+00\",\"updated_at\":\"2025-11-20 01:52:48.93861+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"16\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1058, 'finance_purchase_orders', '{\"id\":\"52\",\"po_number\":\"530000231\",\"po_date\":\"2025-11-12\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU004\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAKCP3080G1ZI\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"82036.00\",\"total_tax\":\"9844.32\",\"total_amount\":\"91880.32\",\"cgst_amount\":\"4922.16\",\"sgst_amount\":\"4922.16\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 01:54:20.92175+00\",\"updated_at\":\"2025-11-20 01:54:20.921761+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"14\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1059, 'finance_purchase_orders', '{\"id\":\"53\",\"po_number\":\"SE-001-2526\",\"po_date\":\"2025-04-22\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU008\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"33CTQPM7467J1ZX\",\"company_gstin\":\"\",\"subtotal\":\"143000.00\",\"total_tax\":\"0.00\",\"total_amount\":\"143000.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 05:06:49.045991+00\",\"updated_at\":\"2025-11-20 05:06:49.046005+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"10\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1060, 'finance_purchase_orders', '{\"id\":\"39\",\"po_number\":\"PGEL\\/25-26\\/2963\",\"po_date\":\"2025-06-19\",\"po_file\":\"po_files\\/Bk_Construction__Extra___Work_3.pdf\",\"internal_po_number\":\"BKCPOU025\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"2000000.00\",\"total_tax\":\"360000.00\",\"total_amount\":\"2360000.00\",\"cgst_amount\":\"180000.00\",\"sgst_amount\":\"180000.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 12:26:35.847952+00\",\"updated_at\":\"2025-11-19 12:26:35.847971+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1061, 'finance_purchase_orders', '{\"id\":\"40\",\"po_number\":\"PGEL\\/24-25\\/2406\",\"po_date\":\"2024-09-09\",\"po_file\":\"po_files\\/Supply_Order_Item_1_T74dsji.pdf\",\"internal_po_number\":\"BKCPOU026\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1800000.00\",\"total_tax\":\"324000.00\",\"total_amount\":\"2124000.00\",\"cgst_amount\":\"162000.00\",\"sgst_amount\":\"162000.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 12:27:37.254415+00\",\"updated_at\":\"2025-11-19 12:28:00.465105+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1062, 'finance_purchase_orders', '{\"id\":\"41\",\"po_number\":\"PGEL\\/24-25\\/4232\",\"po_date\":\"2025-11-19\",\"po_file\":\"po_files\\/Supply_Order_Item_V2_Extra_Work_2_lPUGHXW.pdf\",\"internal_po_number\":\"BKCPOU027\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"cgst_sgst\",\"customer_gstin\":\"33AAHCS2941J1ZB\",\"company_gstin\":\"33JJFPK6756J1ZP\",\"subtotal\":\"1461675.00\",\"total_tax\":\"263101.50\",\"total_amount\":\"1724776.50\",\"cgst_amount\":\"131550.75\",\"sgst_amount\":\"131550.75\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-19 12:31:45.997834+00\",\"updated_at\":\"2025-11-19 12:34:09.416041+00\",\"company_id\":\"14\",\"created_by_id\":\"31\",\"customer_id\":\"23\",\"shipping_address_id\":null,\"quotation_id\":null}', '2025-11-28 14:07:36');
INSERT INTO `finance_data` (`id`, `table_name`, `data`, `created_at`) VALUES
(1063, 'finance_purchase_orders', '{\"id\":\"54\",\"po_number\":\"SE-002-2526\",\"po_date\":\"2025-05-13\",\"po_file\":\"\",\"internal_po_number\":\"SEPOU009\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"exempt\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"\",\"subtotal\":\"6100.00\",\"total_tax\":\"0.00\",\"total_amount\":\"6100.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"0.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"0.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"not_started\",\"created_at\":\"2025-11-20 05:08:43.891355+00\",\"updated_at\":\"2025-11-20 07:16:35.929537+00\",\"company_id\":\"11\",\"created_by_id\":\"29\",\"customer_id\":\"9\",\"shipping_address_id\":\"1763462740501\",\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1064, 'finance_purchase_orders', '{\"id\":\"59\",\"po_number\":\"PGEL\\/25-26\\/4233\",\"po_date\":\"2025-09-13\",\"po_file\":\"\",\"internal_po_number\":\"TCPOU006\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33BIHPD1104L1ZS\",\"subtotal\":\"60000.00\",\"total_tax\":\"10800.00\",\"total_amount\":\"70800.00\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"10800.00\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":null,\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"70800.00\",\"remaining_proforma_balance\":\"0.00\",\"remaining_invoice_balance\":\"0.00\",\"proforma_status\":\"not_started\",\"invoice_status\":\"completed\",\"created_at\":\"2025-11-20 12:42:48.097577+00\",\"updated_at\":\"2025-11-20 12:42:48.097592+00\",\"company_id\":\"13\",\"created_by_id\":\"30\",\"customer_id\":\"13\",\"shipping_address_id\":\"1763638507469\",\"quotation_id\":null}', '2025-11-28 14:07:36'),
(1065, 'finance_purchase_orders', '{\"id\":\"55\",\"po_number\":\"PGEL\\/25-26\\/4096\",\"po_date\":\"2025-08-13\",\"po_file\":\"po_files\\/4096_-_BK_GREEN_ENERGY_-_Colortone_Textiles_Pvt_Ltd.pdf\",\"internal_po_number\":\"BKGEPOU001\",\"quotation_date\":null,\"valid_until\":null,\"reference\":\"\",\"gst_type\":\"igst\",\"customer_gstin\":\"24AAHCP3289L1ZY\",\"company_gstin\":\"33DYJPK9079P1ZF\",\"subtotal\":\"15135991.17\",\"total_tax\":\"2724478.41\",\"total_amount\":\"17860469.58\",\"cgst_amount\":\"0.00\",\"sgst_amount\":\"0.00\",\"igst_amount\":\"2724478.41\",\"discount_percentage\":\"0.00\",\"discount_amount\":\"0.00\",\"shipping_charges\":\"0.00\",\"other_charges\":\"0.00\",\"status\":\"draft\",\"notes\":\"\",\"terms_and_conditions\":\"\",\"claim_type\":\"quantity\",\"proforma_claimed_amount\":\"0.00\",\"invoice_claimed_amount\":\"2012147.80\",\"remaining_proforma_balance\":\"13430781.17\",\"remaining_invoice_balance\":\"15848321.78\",\"proforma_status\":\"not_started\",\"invoice_status\":\"partial\",\"created_at\":\"2025-11-20 05:08:54.03599+00\",\"updated_at\":\"2025-11-20 07:28:33.967382+00\",\"company_id\":\"17\",\"created_by_id\":\"34\",\"customer_id\":\"28\",\"shipping_address_id\":\"1763614021854\",\"quotation_id\":null}', '2025-11-28 14:07:36');

-- --------------------------------------------------------

--
-- Table structure for table `finance_tables`
--

CREATE TABLE `finance_tables` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_count` int(11) DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_prefix` varchar(10) DEFAULT 'BKC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_tables`
--

INSERT INTO `finance_tables` (`id`, `table_name`, `record_count`, `last_sync`, `company_prefix`) VALUES
(1, 'settings', 0, '2025-11-28 17:50:19', 'BKGE'),
(2, 'finance_quotations', 10, '2025-11-28 14:07:35', 'BKC'),
(3, 'finance_invoices', 5, '2025-11-28 14:07:35', 'BKC'),
(4, 'finance_customers', 2, '2025-11-28 08:29:06', 'BKC'),
(37, 'finance_customer', 14, '2025-11-28 14:07:36', 'BKC'),
(38, 'finance_purchase_orders', 48, '2025-11-28 14:07:36', 'BKC');

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

CREATE TABLE `followups` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `followup_type` enum('standalone','task') DEFAULT 'standalone',
  `task_id` int(11) DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `status` enum('pending','in_progress','completed','postponed','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `contact_id`, `user_id`, `title`, `description`, `followup_type`, `task_id`, `follow_up_date`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 66, 'Follow-up: Implement Dashboard UI Updates', 'Follow up on UI update status and confirm completion of pending components.', 'task', 1, '2025-11-28', 'pending', NULL, '2025-11-28 05:32:50', '2025-11-28 05:32:50'),
(2, 0, 37, 'Follow-up: Optimize Employee Attendance API', 'Improve the attendance API response time by optimizing database queries and fixing duplicate record issues. Ensure the API returns accurate data and passes all basic validation checks.', 'task', 2, '2025-11-29', 'pending', NULL, '2025-11-28 06:39:30', '2025-11-28 06:55:59'),
(3, 4, 57, 'Follow-up: Test Followup task 4', '\"Ensure login authentication works correctly and resolve any related errors.\"', 'task', 4, '2025-11-28', 'pending', NULL, '2025-11-28 07:17:19', '2025-11-28 07:17:19'),
(4, 3, 57, 'Follow-up: Mobile App Release Checklist', 'Mobile App Release Checklist', 'task', 5, '2025-11-28', 'pending', NULL, '2025-11-28 08:27:38', '2025-11-28 08:27:38'),
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
  `id` int(11) NOT NULL,
  `followup_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `funnel_stats`
--

CREATE TABLE `funnel_stats` (
  `id` int(11) NOT NULL,
  `company_prefix` varchar(50) NOT NULL,
  `quotation_count` int(11) DEFAULT 0,
  `quotation_value` decimal(15,2) DEFAULT 0.00,
  `po_count` int(11) DEFAULT 0,
  `po_value` decimal(15,2) DEFAULT 0.00,
  `po_conversion_rate` decimal(5,2) DEFAULT 0.00,
  `invoice_count` int(11) DEFAULT 0,
  `invoice_value` decimal(15,2) DEFAULT 0.00,
  `invoice_conversion_rate` decimal(5,2) DEFAULT 0.00,
  `payment_count` int(11) DEFAULT 0,
  `payment_value` decimal(15,2) DEFAULT 0.00,
  `payment_conversion_rate` decimal(5,2) DEFAULT 0.00,
  `generated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `reference_type`, `reference_id`, `entry_date`, `description`, `total_amount`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'expense', 2, '2025-11-28', 'Auto fare to visit ABC Corp', 850.00, 1, '2025-11-28 06:42:07', '2025-11-28 06:42:07'),
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
  `id` int(11) NOT NULL,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `account_id`, `debit_amount`, `credit_amount`, `description`, `created_at`) VALUES
(1, 1, 1, 850.00, 0.00, 'Expense: Auto fare to visit ABC Corp', '2025-11-28 06:42:07'),
(2, 1, 1, 0.00, 850.00, 'Payable: Auto fare to visit ABC Corp', '2025-11-28 06:42:07'),
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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) DEFAULT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `contact_during_leave` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `days_requested`, `reason`, `contact_during_leave`, `status`, `created_at`, `updated_at`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(2, 49, 'annual', '2025-11-28', '2025-11-29', 3, 2, 'Family vacation', '9787877867', 'Pending', '2025-11-28 06:07:58', '2025-11-28 06:26:30', NULL, NULL, NULL),
(3, 49, 'casual', '2025-11-28', '2025-11-29', 2, 2, 'Personal work - Updating Aadhaar Details', '9876543210', 'Approved', '2025-11-28 06:15:00', '2025-11-28 06:25:53', NULL, NULL, NULL),
(8, 37, 'emergency', '2025-11-01', '2025-11-04', 4, 4, 'Medical Emergency', NULL, 'Pending', '2025-10-31 11:16:00', '2025-10-31 11:16:00', NULL, NULL, NULL),
(13, 16, 'casual', '2025-11-04', '2025-11-04', 1, 1, 'Casual Leave', NULL, 'Rejected', '2025-11-03 13:06:06', '2025-11-08 10:32:53', NULL, NULL, 'Reject'),
(15, 37, 'emergency', '2025-11-09', '2025-11-11', 3, 3, 'Emergency Leave', '9857451522', 'Pending', '2025-11-08 09:40:56', '2025-11-27 13:30:46', NULL, NULL, NULL),
(19, 1, 'sick', '2025-11-11', '2025-11-11', 1, 1, 'Fever And Cold', NULL, 'Pending', '2025-11-10 13:47:02', '2025-11-10 13:47:02', NULL, NULL, NULL),
(30, 37, 'sick', '2025-11-20', '2025-11-21', 2, 2, 'Sick leave', NULL, 'Rejected', '2025-11-11 06:05:20', '2025-11-20 10:22:18', NULL, NULL, 'Reject This Leave'),
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
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `uuid` char(36) DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `module_name` varchar(50) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `template_key` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `reference_id` int(11) DEFAULT NULL,
  `delivery_channel_set` varchar(100) DEFAULT 'inapp',
  `priority` tinyint(4) DEFAULT 2,
  `is_read` tinyint(1) DEFAULT 0,
  `status` enum('queued','delivered','failed','deleted') DEFAULT 'queued',
  `retry_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_audit_logs`
--

CREATE TABLE `notification_audit_logs` (
  `id` bigint(20) NOT NULL,
  `notification_uuid` char(36) NOT NULL,
  `channel` varchar(50) NOT NULL,
  `status` enum('attempted','success','failed') NOT NULL,
  `response` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `attempt_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_channels`
--

CREATE TABLE `notification_channels` (
  `id` int(11) NOT NULL,
  `channel_name` varchar(50) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel` varchar(50) NOT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `frequency` enum('instant','daily_digest','weekly_digest') DEFAULT 'instant',
  `dnd_start` time DEFAULT NULL,
  `dnd_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `locale` varchar(10) DEFAULT 'en',
  `subject` varchar(255) NOT NULL,
  `body_html` text DEFAULT NULL,
  `body_text` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','completed','on_hold','cancelled','withheld','rejected') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `department_id`, `status`, `created_at`) VALUES
(1, 'SAP', 'SAP (Systems, Applications, and Products) is an enterprise software that helps companies manage and integrate key business processes.', 13, 'active', '2025-11-25 13:48:34'),
(2, 'ERGON - HR system', 'Ergon is a workflow management system that handles tasks, follow-ups, attendance, expenses, advances, leave management, and user details—all in one platform to streamline daily operations and improve team productivity.', 1, 'active', '2025-11-26 04:17:29'),
(3, 'Athens', 'EHS project', 5, 'active', '2025-11-26 19:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit_log`
--

CREATE TABLE `rate_limit_log` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rate_limit_log`
--

INSERT INTO `rate_limit_log` (`id`, `identifier`, `action`, `attempted_at`, `success`, `ip_address`) VALUES
(1, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 05:09:28', 1, '2405:201:e067:384e:c002:c597:336f:7af4'),
(2, '2405:201:e067:384e:455:c3e4:5b42:2420', 'login', '2025-11-28 05:24:37', 1, '2405:201:e067:384e:455:c3e4:5b42:2420'),
(3, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 05:31:10', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(4, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 06:06:17', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(5, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 06:42:31', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(6, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 06:45:29', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(7, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 06:47:46', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(8, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 06:59:54', 0, '2405:201:e067:384e:c002:c597:336f:7af4'),
(9, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:00:10', 0, '2405:201:e067:384e:c002:c597:336f:7af4'),
(10, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:00:55', 0, '2405:201:e067:384e:c002:c597:336f:7af4'),
(11, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:01:40', 1, '2405:201:e067:384e:c002:c597:336f:7af4'),
(12, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:22:04', 1, '2405:201:e067:384e:c002:c597:336f:7af4'),
(13, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 07:30:37', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(14, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 07:31:11', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(15, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 07:33:42', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(16, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:34:39', 1, '2405:201:e067:384e:c002:c597:336f:7af4'),
(17, '2405:201:e067:384e:c002:c597:336f:7af4', 'login', '2025-11-28 07:35:04', 1, '2405:201:e067:384e:c002:c597:336f:7af4'),
(18, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:28:14', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(19, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:34:31', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(20, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:34:47', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(21, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:38:30', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(22, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:48:27', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(23, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:48:41', 0, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(24, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:48:47', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(25, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:49:03', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(26, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:49:57', 0, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(27, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:50:06', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(28, '2405:201:e067:384e:c57f:a4aa:8356:768f', 'login', '2025-11-28 08:50:53', 1, '2405:201:e067:384e:c57f:a4aa:8356:768f'),
(29, '2405:201:e067:384e:e49c:b9f3:8fbc:3b03', 'login', '2025-11-28 12:58:55', 1, '2405:201:e067:384e:e49c:b9f3:8fbc:3b03'),
(30, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:53:09', 0, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(31, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:53:25', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(32, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:54:33', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(33, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:56:22', 0, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(34, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:56:27', 0, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(35, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:56:40', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(36, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 16:57:02', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(37, '2409:4072:6c9a:69c2:aef1:8983:e8e9:33bd', 'login', '2025-11-28 17:49:49', 1, '2409:4072:6c9a:69c2:aef1:8983:e8e9:33bd'),
(38, '2409:40f4:1020:888:a5a2:9426:628f:c589', 'login', '2025-11-28 17:56:56', 0, '2409:40f4:1020:888:a5a2:9426:628f:c589'),
(39, '2409:40f4:1020:888:a5a2:9426:628f:c589', 'login', '2025-11-28 17:57:09', 1, '2409:40f4:1020:888:a5a2:9426:628f:c589'),
(40, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 17:59:59', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(41, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 18:02:17', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(42, '2409:40f4:1020:888:a5a2:9426:628f:c589', 'login', '2025-11-28 18:05:43', 1, '2409:40f4:1020:888:a5a2:9426:628f:c589'),
(43, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 18:07:36', 0, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53'),
(44, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', 'login', '2025-11-28 18:07:43', 1, '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT 'ERGON Company',
  `company_email` varchar(255) DEFAULT '',
  `company_phone` varchar(20) DEFAULT '',
  `company_address` text DEFAULT NULL,
  `working_hours_start` time DEFAULT '09:00:00',
  `working_hours_end` time DEFAULT '18:00:00',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `base_location_lat` decimal(10,8) DEFAULT 0.00000000,
  `base_location_lng` decimal(11,8) DEFAULT 0.00000000,
  `attendance_radius` int(11) DEFAULT 200,
  `office_address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `company_email`, `company_phone`, `company_address`, `working_hours_start`, `working_hours_end`, `timezone`, `base_location_lat`, `base_location_lng`, `attendance_radius`, `office_address`, `created_at`, `updated_at`) VALUES
(1, 'Athena Solutions', '', '', NULL, '09:00:00', '18:00:00', 'Asia/Kolkata', 9.98164400, 78.14335300, 300, NULL, '2025-11-28 05:23:37', '2025-11-28 05:32:19');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `grace_period` int(11) DEFAULT 15 COMMENT 'Grace period in minutes',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sla_history`
--

CREATE TABLE `sla_history` (
  `id` int(11) NOT NULL,
  `daily_task_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `duration_seconds` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `task_type` enum('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `deadline` datetime DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `status` enum('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `depends_on_task_id` int(11) DEFAULT NULL,
  `sla_hours` decimal(8,4) DEFAULT 0.2500,
  `sla_hours_part` int(11) DEFAULT 0,
  `sla_minutes_part` int(11) DEFAULT 15,
  `overall_progress` int(11) DEFAULT 0,
  `total_time_spent` decimal(6,2) DEFAULT 0.00,
  `estimated_hours` decimal(4,2) DEFAULT 0.00,
  `last_progress_update` timestamp NULL DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `reminder_time` time DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `followup_required` tinyint(1) DEFAULT 0,
  `planned_date` date DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'regular',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `actual_time_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_by`, `assigned_to`, `task_type`, `priority`, `deadline`, `progress`, `status`, `due_date`, `created_at`, `updated_at`, `depends_on_task_id`, `sla_hours`, `sla_hours_part`, `sla_minutes_part`, `overall_progress`, `total_time_spent`, `estimated_hours`, `last_progress_update`, `department_id`, `task_category`, `company_name`, `contact_person`, `contact_phone`, `project_name`, `follow_up_date`, `reminder_time`, `reminder_sent`, `followup_required`, `planned_date`, `estimated_duration`, `project_id`, `type`, `assigned_at`, `actual_time_seconds`) VALUES
(2, 'Optimize Employee Attendance API', 'Improve the attendance API response time by optimizing database queries and fixing duplicate record issues. Ensure the API returns accurate data and passes all basic validation checks.', 1, 37, 'ad-hoc', 'medium', '2025-11-29 00:00:00', 13, 'in_progress', NULL, '2025-11-28 06:39:30', '2025-11-28 06:55:59', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 6, 'Follow-up', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-28', NULL, 1, 'regular', NULL, 0),
(3, 'Update Client Portal Dashboard', 'Revamp the client portal dashboard by updating widgets, optimizing load times, and fixing alignment issues. Ensure compatibility across browsers and devices, and confirm that all dashboard metrics display correctly.', 1, 65, 'ad-hoc', 'low', '2025-11-30 00:00:00', 16, 'in_progress', NULL, '2025-11-28 06:45:54', '2025-11-28 06:56:46', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 15, 'Email Marketing', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-29', NULL, 2, 'regular', NULL, 0),
(4, 'Fix Login Authentication Issue', 'Investigate and resolve login authentication errors on the web portal. Verify correct handling of invalid credentials, password resets, and session timeouts. Ensure no users are incorrectly logged out and all security measures are intact.', 1, 57, 'ad-hoc', 'medium', '2025-11-29 00:00:00', 0, 'in_progress', NULL, '2025-11-28 07:12:33', '2025-11-28 07:17:19', NULL, 0.2500, 0, 15, 0, 0.00, 0.00, NULL, 1, 'Policy Development', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-28', NULL, 3, 'regular', NULL, 0),
(5, 'Mobile App Release Checklist', 'Complete all release steps: build, QA test, upload to Play Store, release notes, version update.', 48, 57, 'ad-hoc', 'medium', '2025-11-28 00:00:00', 0, 'assigned', NULL, '2025-11-28 08:27:38', '2025-11-28 08:27:38', NULL, 0.2667, 0, 15, 0, 0.00, 0.00, NULL, 14, 'API Development', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2025-11-28', NULL, 1, 'regular', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_categories`
--

CREATE TABLE `task_categories` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task_history`
--

INSERT INTO `task_history` (`id`, `task_id`, `action`, `old_value`, `new_value`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'created', '', 'Task created', 'Initial task creation: Implement Dashboard UI Updates', 66, '2025-11-28 05:32:50'),
(2, 1, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 05:32:50'),
(3, 2, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 06:39:30'),
(4, 3, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 06:45:54'),
(5, 2, 'status_changed', 'assigned', 'in_progress', '', 1, '2025-11-28 06:53:07'),
(6, 2, 'progress_updated', '2%', '30%', '', 1, '2025-11-28 06:53:07'),
(7, 2, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-28 06:55:59'),
(8, 3, 'status_changed', 'assigned', 'in_progress', '', 1, '2025-11-28 06:56:46'),
(9, 3, 'progress_updated', '8%', '16%', '', 1, '2025-11-28 06:56:46'),
(10, 4, 'created', '', 'Task created', 'Task was created with initial details', 1, '2025-11-28 07:12:33'),
(11, 4, 'updated', 'Task details', 'Task updated', 'Task details were modified', 1, '2025-11-28 07:17:19'),
(12, 5, 'created', '', 'Task created', 'Task was created with initial details', 48, '2025-11-28 08:27:38');

-- --------------------------------------------------------

--
-- Table structure for table `task_updates`
--

CREATE TABLE `task_updates` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `daily_task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL,
  `active_duration` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','admin','user') DEFAULT 'user',
  `is_system_admin` tinyint(1) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended','terminated') DEFAULT 'active',
  `is_first_login` tinyint(1) DEFAULT 1,
  `temp_password` varchar(20) DEFAULT NULL,
  `password_reset_required` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `department_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `password`, `role`, `is_system_admin`, `phone`, `department`, `status`, `is_first_login`, `temp_password`, `password_reset_required`, `last_login`, `last_ip`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `designation`, `joining_date`, `salary`, `total_points`, `department_id`, `shift_id`) VALUES
(1, 'EMP001', 'Bharath', 'info@athenas.co.in', '$2y$10$GKrksmX0Pmp5DoXJ9YskPOZ0x9O192vodYSVg4mRswfgg4kNGfYUq', 'owner', 0, '986532741', 'General', 'active', 0, 'owner123', 0, '2025-11-28 23:27:09', '2409:40f4:1020:888:a5a2:9426:628f:c589', '2025-10-23 06:24:06', '2025-11-28 17:57:09', '1990-01-01', 'male', 'Pajanamadam Street,Madurai', '9786756787', 'Test Designation', '2024-01-01', 50000.00, 0, 1, 1),
(16, 'ATSO003', 'Harini', 'harini@athenas.co.in', '$2y$10$GcyIHTtTvWon4pAZeWQFNei6jnNdEzP0G.onwzEaP1XCowHylNEbu', 'user', 0, '6380795088', 'Finance & Accounts,Liaison,Marketing & Sales,Operations', 'active', 1, 'RST7498R', 1, '2025-11-03 17:04:46', '127.0.0.1', '2025-10-24 02:34:52', '2025-11-03 11:34:46', '2004-06-20', 'female', 'Plot No: 81,Poriyalar Nagar 4th Street,Near By Yadava college,Thirupalai', '9876787689', 'Accountant', '2024-06-27', 15000.00, 0, NULL, 1),
(37, 'EMP014', 'Nelson', 'nelson@gmail.com', '$2y$10$3/U5i7ZhLU0uNLs5in.P4e2U9A6OZ1ytRDAvDUhqcgQTDS1tdeVlK', 'admin', 0, '9517536422', '6', 'active', 1, NULL, 0, '2025-11-28 23:35:43', '2409:40f4:1020:888:a5a2:9426:628f:c589', '2025-10-30 05:16:49', '2025-11-28 18:05:43', '2002-12-07', 'male', 'Madurai', '9856472431', 'Accountant', '2025-03-27', 20000.00, 0, 1, 1),
(47, 'EMP015', 'Clinton', 'clinton@gmail.com', '$2y$10$1ZAd3iKOgoJ4.7JB/SGVLu7R8gDrjbmheLsTnDXLbkfiawSiSUIDa', 'admin', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-28 23:37:43', '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', '2025-11-17 07:25:46', '2025-11-28 18:07:43', '2001-03-01', 'male', 'mdu', '8566754675', 'HR', '2025-11-01', 25000.00, 0, 1, 1),
(48, 'EMP016', 'Simon', 'simon@gmail.com', '$2y$10$Vvi8Qj1g/X/S8cgb0TnPy.h7HQ4WjfBV6/kcP0SSWBI8wFOzfWTRa', 'user', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-28 23:29:59', '2402:3a80:28:ecbb:dd9d:7765:72f9:dc53', '2025-11-17 08:25:15', '2025-11-28 17:59:59', '2000-08-11', 'male', 'mdu', '9856472431', 'HR', '2025-11-01', 25000.00, 0, 14, 1),
(49, 'EMP017', 'Joel', 'joel@gmail.com', '$2y$10$GXvfDNHz8WqVmqgfv61XHe8tczx1t8A4st9LN2ABW2i0csZjpgtNq', 'user', 0, '7541025356', NULL, 'active', 1, NULL, 0, '2025-11-28 14:20:53', '2405:201:e067:384e:c57f:a4aa:8356:768f', '2025-11-19 04:47:06', '2025-11-28 08:50:53', '2002-12-13', 'male', 'Madurai', '7847578945', 'Developer', '2025-11-01', 15000.00, 0, 14, 1),
(50, 'EMP018', 'Admin', 'admin@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 0, '9874563210', NULL, 'active', 1, NULL, 0, NULL, NULL, '2025-11-19 08:45:45', '2025-11-27 11:02:08', '2003-08-22', 'female', 'Narimadu,Madurai', '9898987654', 'Developer', '2025-10-02', 20000.00, 0, NULL, 1),
(51, 'EMP019', 'Test User', 'testuser@gmail.com', '$2y$10$/iJOeUmV0PHnrtZ2yxlb0eCu0Ah6r9mxXrSjYL2H6G.FYhHxSLse.', 'admin', 0, '9517536482', NULL, 'terminated', 1, NULL, 0, '2025-11-21 18:31:14', '127.0.0.1', '2025-11-21 12:59:19', '2025-11-26 04:04:59', '2001-03-01', 'female', 'India', '9856472431', 'Developer', '2025-11-01', 20000.00, 0, 13, 1),
(53, 'EMP020', 'Test User 1', 'testuser1@gmail.com', '$2y$10$anWjTF8/a9lf..MPRexmMO9DVlWWHAfNvDKHpte8OAkhvVL0iuh8e', 'user', 0, '9517536482', NULL, 'terminated', 1, NULL, 0, NULL, NULL, '2025-11-21 13:02:41', '2025-11-26 07:56:47', '2002-11-04', 'male', 'India', '9856472431', 'Developer', '2025-11-01', 15000.00, 0, 13, 1),
(56, 'EMP021', 'Test User 2', 'testuser2@gmail.com', '$2y$10$Tb9K32n.wQKAxXgbXI0R7ejBaXQ/vBR0PxacaBQqV.VX9Vjb90OPq', 'admin', 0, '9517536482', NULL, 'terminated', 1, NULL, 0, '2025-11-22 16:17:11', '127.0.0.1', '2025-11-22 10:46:18', '2025-11-27 10:46:48', '2002-06-12', 'male', 'mdu', '9856472431', 'HR', '2025-11-01', 20000.00, 0, 14, 1),
(57, 'EMP022', 'John', 'john@gmail.com', '$2y$10$j6JRH16yyfW8RP/gqz9OcujJQ5GcRVkNpOZUdxN02Q7RhdxZUne5y', 'user', 0, '9517536482', NULL, 'active', 1, NULL, 0, '2025-11-28 14:18:47', '2405:201:e067:384e:c57f:a4aa:8356:768f', '2025-11-26 05:13:44', '2025-11-28 08:48:47', '2002-10-16', 'male', 'madurai', '9856472431', 'Accountant', '2025-11-01', 20000.00, 0, 14, 1),
(58, 'EMP023', 'Arjun Selvakumar', 'arjun.s@techportal.co', '$2y$10$BEH/spyMw.02iXxKbRFlJeJTgYWSUOmk7KXaLgmkhqNcWtI8h5Vd6', 'admin', 0, '8123459076', NULL, 'active', 1, NULL, 0, '2025-11-26 19:09:10', '2405:201:e067:384e:c4f2:f26a:8d04:12b9', '2025-11-26 05:19:57', '2025-11-26 13:39:10', '2004-08-08', 'male', 'No. 48, Rajaji Street, Anna Nagar West, Chennai – 600101', '9445089213', 'Operations Coordinator', '2017-11-07', 32000.00, 0, 13, 1),
(65, 'EMP024', 'Yazhini', 'syazhini229@gmail.com', '$2y$10$iBoN66w33jhDgKMBRr87eeCaPmW6Xv0cgHmGtIsnoEkTRIHGvY6DS', 'user', 0, '6380328692', NULL, 'active', 1, NULL, 0, '2025-11-28 12:31:40', '2405:201:e067:384e:c002:c597:336f:7af4', '2025-11-26 05:51:40', '2025-11-28 07:01:40', '2004-08-08', 'female', 'No. 48, Rajaji Street, Anna Nagar West, Chennai – 600101', '9445089213', 'Operations Coordinator', '2017-11-07', 32000.00, 0, 13, 1),
(66, 'EMP025', 'Client Owner', 'clientowner@athenas.co.in', '$2y$10$nZU0whmG2uaMyGO94NHyju.iIruuZyXcdA/tpX/KpfB5WSKJ9gOQy', 'owner', 0, '', NULL, 'active', 1, NULL, 0, NULL, NULL, '2025-11-27 11:43:00', '2025-11-27 11:43:40', NULL, '', '', '', '', NULL, NULL, 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_departments`
--

CREATE TABLE `user_departments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fcm_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') DEFAULT 'android',
  `device_info` text DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `reason` varchar(200) NOT NULL,
  `reference_type` enum('task','attendance','workflow','bonus') DEFAULT 'task',
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(50) NOT NULL,
  `preference_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

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
-- Indexes for table `chart_stats`
--
ALTER TABLE `chart_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_prefix` (`company_prefix`);

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
-- Indexes for table `dashboard_stats`
--
ALTER TABLE `dashboard_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_prefix` (`company_prefix`);

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
-- Indexes for table `finance_data`
--
ALTER TABLE `finance_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_name` (`table_name`);

--
-- Indexes for table `finance_tables`
--
ALTER TABLE `finance_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_name` (`table_name`);

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
-- Indexes for table `funnel_stats`
--
ALTER TABLE `funnel_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_prefix` (`company_prefix`);

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
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_status_priority` (`status`,`priority`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `notification_audit_logs`
--
ALTER TABLE `notification_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_uuid` (`notification_uuid`),
  ADD KEY `idx_attempt_at` (`attempt_at`);

--
-- Indexes for table `notification_channels`
--
ALTER TABLE `notification_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `channel_name` (`channel_name`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_channel` (`user_id`,`channel`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_locale` (`template_key`,`locale`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_positions`
--
ALTER TABLE `admin_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advances`
--
ALTER TABLE `advances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_rules`
--
ALTER TABLE `attendance_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `badge_definitions`
--
ALTER TABLE `badge_definitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_stats`
--
ALTER TABLE `chart_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circulars`
--
ALTER TABLE `circulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `daily_performance`
--
ALTER TABLE `daily_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_planner`
--
ALTER TABLE `daily_planner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_planners`
--
ALTER TABLE `daily_planners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_planner_audit`
--
ALTER TABLE `daily_planner_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_plans`
--
ALTER TABLE `daily_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `daily_task_history`
--
ALTER TABLE `daily_task_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_stats`
--
ALTER TABLE `dashboard_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `evening_updates`
--
ALTER TABLE `evening_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `finance_data`
--
ALTER TABLE `finance_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1066;

--
-- AUTO_INCREMENT for table `finance_tables`
--
ALTER TABLE `finance_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `followup_history`
--
ALTER TABLE `followup_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `funnel_stats`
--
ALTER TABLE `funnel_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_audit_logs`
--
ALTER TABLE `notification_audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_channels`
--
ALTER TABLE `notification_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sla_history`
--
ALTER TABLE `sla_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_departments`
--
ALTER TABLE `user_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
