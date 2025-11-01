-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 29, 2025 at 02:23 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

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
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `repayment_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('present','absent','late') DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location` varchar(255) DEFAULT 'Office'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(3, 2, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 06:43:10'),
(4, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 06:46:50'),
(5, 1, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:32:49'),
(6, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:01'),
(7, 2, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:05'),
(8, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:26'),
(9, 2, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:30'),
(10, 2, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:45:14'),
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
(38, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:05:01'),
(39, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:07:42'),
(40, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:10'),
(41, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:13'),
(42, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:17'),
(43, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:08:21'),
(44, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:09:00'),
(45, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:13:51'),
(46, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:21'),
(47, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:28'),
(48, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:16:51'),
(49, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 17:18:38'),
(50, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:07:57'),
(51, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:08:03'),
(52, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 18:31:06'),
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
(67, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:13:11'),
(68, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 03:22:49'),
(69, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 03:25:55'),
(70, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 04:18:29'),
(71, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:04:31'),
(72, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:04:36'),
(73, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:32:29'),
(74, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:32:35'),
(75, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:33:02'),
(76, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:33:06'),
(77, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:30'),
(78, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:33'),
(79, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:34'),
(80, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:51'),
(81, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 05:42:58'),
(82, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:14:30'),
(83, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:17:20'),
(84, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:35:32'),
(85, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:42:58'),
(86, 1, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:51:41'),
(87, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:51:45'),
(88, NULL, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:15:33'),
(89, 2, 'auth', 'login_success', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:15:37'),
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
(1, 'Year-End Holiday Schedule', 'Please note the office will be closed from Dec 24-26 for Christmas holidays. Regular operations resume Dec 27.', 1, 'All', '2024-12-15 03:30:00'),
(2, 'New Security Protocols', 'Updated security protocols are now in effect. Please ensure all devices are updated and follow new password requirements.', 2, 'All', '2024-12-18 06:00:00'),
(3, 'Q4 Performance Reviews', 'Q4 performance reviews will be conducted in the first week of January. Please prepare your self-assessment forms.', 2, 'User', '2024-12-20 10:15:00');

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
(3, 1, 1, '2025-10-27', 'ERGON Development', 'Code Review', 'Review pull requests from team members', 'Code Review', 'planned', 'medium', 1.00, 'completed', 100, 0.00, NULL, '2025-10-26 21:44:23', '2025-10-26 21:44:23', NULL, 0, NULL),
(20, 29, 1, '2025-10-21', NULL, 'Setup Development Environment', 'Configure local development setup', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(21, 29, 1, '2025-10-22', NULL, 'Code Review Session', 'Review team code submissions', NULL, 'planned', 'medium', 1.50, 'completed', 100, 1.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(22, 29, 1, '2025-10-23', NULL, 'Bug Fixing', 'Fix critical production bugs', NULL, 'planned', 'urgent', 3.00, 'completed', 100, 3.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(23, 29, 1, '2025-10-24', NULL, 'Database Optimization', 'Optimize slow queries', NULL, 'planned', 'high', 2.50, 'completed', 100, 2.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(24, 29, 1, '2025-10-25', NULL, 'Feature Development', 'Implement new user features', NULL, 'planned', 'medium', 4.00, 'completed', 100, 4.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(25, 29, 1, '2025-10-26', NULL, 'Testing Phase', 'Run comprehensive tests', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(26, 29, 1, '2025-10-27', NULL, 'Documentation Update', 'Update technical documentation', NULL, 'planned', 'medium', 1.50, 'in_progress', 60, 1.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(27, 30, NULL, '2025-10-22', NULL, 'Invoice Processing', 'Process monthly invoices', NULL, 'planned', 'high', 3.00, 'completed', 100, 3.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(28, 30, NULL, '2025-10-23', NULL, 'Financial Report', 'Generate quarterly report', NULL, 'planned', 'urgent', 4.00, 'completed', 100, 4.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(29, 30, NULL, '2025-10-24', NULL, 'Tax Calculations', 'Calculate monthly tax obligations', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(30, 30, NULL, '2025-10-25', NULL, 'Expense Review', 'Review team expense claims', NULL, 'planned', 'medium', 1.50, 'completed', 100, 1.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(31, 30, NULL, '2025-10-27', NULL, 'Budget Planning', 'Plan next quarter budget', NULL, 'planned', 'high', 3.00, 'in_progress', 40, 1.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(32, 31, NULL, '2025-10-23', NULL, 'Campaign Design', 'Design social media campaign', NULL, 'planned', 'high', 3.00, 'completed', 100, 3.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(33, 31, NULL, '2025-10-24', NULL, 'Content Creation', 'Create blog content', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(34, 31, NULL, '2025-10-25', NULL, 'Market Research', 'Research competitor strategies', NULL, 'planned', 'medium', 2.50, 'completed', 100, 2.00, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(35, 31, NULL, '2025-10-27', NULL, 'Client Presentation', 'Prepare client presentation', NULL, 'planned', 'urgent', 2.00, 'in_progress', 75, 1.50, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, 0, NULL),
(36, 2, NULL, '2025-10-27', NULL, 'Security Patch Implementation', 'Apply critical security patches to production servers', NULL, 'planned', 'urgent', 2.00, 'pending', 100, 2.50, '', '2025-10-27 07:07:08', '2025-10-27 13:55:01', NULL, 0, NULL),
(42, 29, NULL, '2025-10-27', NULL, 'Security Patch Implementation', 'Apply critical security patches to production servers', NULL, 'planned', 'urgent', 2.00, 'completed', 100, 2.50, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL),
(43, 31, NULL, '2025-10-27', NULL, 'Marketing Task Day 1', 'Daily marketing task completion', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL),
(44, 31, NULL, '2025-10-26', NULL, 'Marketing Task Day 2', 'Daily marketing task completion', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL),
(45, 31, NULL, '2025-10-25', NULL, 'Marketing Task Day 3', 'Daily marketing task completion', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL),
(46, 31, NULL, '2025-10-24', NULL, 'Marketing Task Day 4', 'Daily marketing task completion', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL),
(47, 31, NULL, '2025-10-23', NULL, 'Marketing Task Day 5', 'Daily marketing task completion', NULL, 'planned', 'medium', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:10:34', '2025-10-27 07:10:34', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `assigned_to` int NOT NULL,
  `planned_date` date NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT '1.00',
  `actual_hours` decimal(4,2) DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `progress` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

--
-- Dumping data for table `daily_task_updates`
--

INSERT INTO `daily_task_updates` (`id`, `plan_id`, `progress_before`, `progress_after`, `hours_worked`, `update_notes`, `blockers`, `next_steps`, `update_type`, `created_at`) VALUES
(1, 36, 100, 100, 2.50, '', NULL, NULL, 'progress', '2025-10-27 13:55:01');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_workflow_status`
--

INSERT INTO `daily_workflow_status` (`id`, `user_id`, `workflow_date`, `total_planned_tasks`, `total_completed_tasks`, `total_planned_hours`, `total_actual_hours`, `productivity_score`, `last_updated`, `created_at`) VALUES
(1, 1, '2025-10-27', 3, 0, 4.50, 0.00, 0.00, '2025-10-26 21:44:23', '2025-10-26 21:44:23'),
(2, 29, '2025-10-21', 1, 1, 2.00, 2.50, 125.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(3, 29, '2025-10-22', 1, 1, 1.50, 1.00, 66.70, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(4, 29, '2025-10-23', 1, 1, 3.00, 3.50, 116.70, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(5, 29, '2025-10-24', 1, 1, 2.50, 2.00, 80.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(6, 29, '2025-10-25', 1, 1, 4.00, 4.50, 112.50, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(7, 29, '2025-10-26', 1, 1, 2.00, 2.00, 100.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(8, 29, '2025-10-27', 1, 0, 1.50, 1.00, 66.70, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(9, 30, '2025-10-22', 1, 1, 3.00, 3.00, 100.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(10, 30, '2025-10-23', 1, 1, 4.00, 4.50, 112.50, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(11, 30, '2025-10-24', 1, 1, 2.00, 2.50, 125.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(12, 30, '2025-10-25', 1, 1, 1.50, 1.00, 66.70, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(13, 30, '2025-10-27', 1, 1, 3.00, 2.80, 95.00, '2025-10-27 07:10:34', '2025-10-27 07:04:42'),
(14, 31, '2025-10-23', 1, 1, 3.00, 3.50, 116.70, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(15, 31, '2025-10-24', 1, 1, 2.00, 2.00, 100.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(16, 31, '2025-10-25', 1, 1, 2.50, 2.00, 80.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42'),
(17, 31, '2025-10-27', 1, 0, 2.00, 1.50, 75.00, '2025-10-27 07:04:42', '2025-10-27 07:04:42');

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
(13, 'Finance & Accounts', 'Consolidated Finance, Accounting and Financial Operations', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18'),
(14, 'Information Technology', 'Consolidated IT Development, Infrastructure and Support', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18'),
(15, 'Marketing & Sales', 'Consolidated Marketing, Sales and Business Development', NULL, 'active', '2025-10-27 09:35:18', '2025-10-27 09:35:18');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

CREATE TABLE `followups` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','postponed','cancelled') DEFAULT 'pending',
  `follow_up_date` date NOT NULL,
  `original_date` date NOT NULL,
  `reschedule_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT '0',
  `next_reminder` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `user_id`, `task_id`, `title`, `description`, `company_name`, `contact_person`, `contact_phone`, `contact_email`, `project_name`, `department_id`, `priority`, `status`, `follow_up_date`, `original_date`, `reschedule_count`, `created_at`, `updated_at`, `completed_at`, `reminder_sent`, `next_reminder`) VALUES
(1, 1, NULL, 'Website Redesign Discussion', 'Discuss new website requirements and timeline. Client wants modern responsive design with e-commerce integration.', 'TechCorp Solutions', 'John Smith', '+1-555-0101', NULL, 'Corporate Website', NULL, 'medium', 'pending', '2024-01-20', '2024-01-20', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(2, 1, NULL, 'Mobile App Development Quote', 'Follow up on mobile app development proposal. They need iOS and Android apps for their delivery service.', 'StartupXYZ', 'Sarah Johnson', '+1-555-0102', NULL, 'Mobile App Project', NULL, 'medium', 'pending', '2024-01-15', '2024-01-15', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(3, 1, NULL, 'Database Migration Planning', 'Plan the migration of legacy database to cloud infrastructure. Critical for their digital transformation.', 'TechCorp Solutions', 'John Smith', '+1-555-0101', NULL, 'Legacy System Upgrade', NULL, 'medium', 'pending', '2024-01-25', '2024-01-25', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(4, 1, NULL, 'Security Audit Results', 'Present security audit findings and remediation plan. High priority due to compliance requirements.', 'FinanceFirst Bank', 'Michael Brown', '+1-555-0103', NULL, 'Security Assessment', NULL, 'medium', 'in_progress', '2024-01-22', '2024-01-22', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(5, 1, NULL, 'E-commerce Platform Demo', 'Demonstrate e-commerce platform capabilities and pricing options. They want to launch by Q2.', 'RetailMax Inc', 'Emily Davis', '+1-555-0104', NULL, 'Online Store Setup', NULL, 'medium', 'completed', '2024-01-15', '2024-01-15', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(6, 1, NULL, 'Cloud Migration Strategy', 'Discuss cloud migration roadmap and cost analysis. They have 200+ servers to migrate.', 'ManufacturingPro', 'David Wilson', '+1-555-0105', NULL, 'Infrastructure Modernization', NULL, 'medium', 'postponed', '2024-01-28', '2024-01-20', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(7, 1, NULL, 'API Integration Support', 'Help with payment gateway and shipping API integrations. Technical support needed urgently.', 'StartupXYZ', 'Sarah Johnson', '+1-555-0102', NULL, 'Third-party Integrations', NULL, 'medium', 'pending', '2024-01-24', '2024-01-24', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(8, 1, NULL, 'Training Session Scheduling', 'Schedule cybersecurity training for 50+ employees. Must complete before audit deadline.', 'FinanceFirst Bank', 'Michael Brown', '+1-555-0103', NULL, 'Staff Training Program', NULL, 'medium', 'pending', '2024-01-30', '2024-01-25', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(9, 1, NULL, 'Performance Optimization Review', 'Review website performance metrics and optimization recommendations. Site speed is critical for sales.', 'RetailMax Inc', 'Emily Davis', '+1-555-0104', NULL, 'Website Performance', NULL, 'medium', 'pending', '2024-01-26', '2024-01-26', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(10, 1, NULL, 'Contract Renewal Discussion', 'Discuss renewal terms for annual support contract. They want to add more services this year.', 'ManufacturingPro', 'David Wilson', '+1-555-0105', NULL, 'Annual Support Contract', NULL, 'medium', 'completed', '2024-01-16', '2024-01-16', 0, '2025-10-28 11:04:18', '2025-10-28 11:04:18', NULL, 0, NULL),
(11, 1, NULL, 'Mobile App UI/UX Review', 'Review and finalize the user interface design for the mobile application. Focus on user experience and accessibility features.', 'StartupXYZ', 'Sarah Johnson', '555-0102', NULL, 'Mobile App Design', NULL, 'medium', 'completed', '2024-01-21', '2024-01-18', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(12, 1, NULL, 'Payment Gateway Integration', 'Integrate Stripe and PayPal payment gateways into the mobile app. Ensure secure transaction processing and error handling.', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', NULL, 'E-commerce Backend', NULL, 'medium', 'in_progress', '2024-01-23', '2024-01-20', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(13, 1, NULL, 'App Store Submission Process', 'Discuss app store submission requirements for both iOS and Android platforms. Prepare marketing materials and descriptions.', 'Digital Ventures', 'Sarah Johnson', '555-0102', NULL, 'App Publishing', NULL, 'medium', 'postponed', '2024-01-15', '2024-01-15', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(14, 1, NULL, 'Beta Testing Coordination', 'Coordinate beta testing with selected users. Set up feedback collection system and bug tracking process.', 'StartupXYZ', 'Sarah Johnson', '555-0102', NULL, 'Quality Assurance', NULL, 'medium', 'pending', '2024-01-29', '2024-01-25', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(15, 1, NULL, 'Push Notification Setup', 'Configure push notification system for order updates, promotions, and user engagement. Test delivery across devices.', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', NULL, 'Mobile Features', NULL, 'medium', 'cancelled', '2024-01-31', '2024-01-31', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(16, 1, NULL, 'Database Optimization Review', 'Review database queries and optimize performance for mobile app. Focus on reducing load times and improving user experience.', 'Digital Ventures', 'Sarah Johnson', '555-0102', NULL, 'Performance Tuning', NULL, 'medium', 'in_progress', '2024-02-02', '2024-01-28', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(17, 1, NULL, 'Security Audit Follow-up', 'Follow up on security audit recommendations. Implement additional security measures for user data protection and API security.', 'StartupXYZ', 'Sarah Johnson', '555-0102', NULL, 'Security Assessment', NULL, 'medium', 'completed', '2024-02-05', '2024-02-05', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(18, 1, NULL, 'Marketing Integration Planning', 'Plan integration with marketing tools like Google Analytics, Facebook Pixel, and email marketing platforms for user tracking.', 'TechFlow Solutions', 'Sarah Johnson', '555-0102', NULL, 'Marketing Tools', NULL, 'medium', 'postponed', '2024-01-12', '2024-01-10', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(19, 1, NULL, 'Launch Strategy Discussion', 'Discuss go-to-market strategy, launch timeline, and post-launch support plan. Coordinate with marketing and sales teams.', 'Digital Ventures', 'Sarah Johnson', '555-0102', NULL, 'Product Launch', NULL, 'medium', 'pending', '2024-02-10', '2024-02-08', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL),
(20, 1, NULL, 'Post-Launch Support Planning', 'Plan ongoing support and maintenance services. Discuss SLA requirements, update schedules, and feature enhancement roadmap.', 'StartupXYZ', 'Sarah Johnson', '555-0102', NULL, 'Maintenance Contract', NULL, 'medium', 'pending', '2024-02-12', '2024-02-12', 0, '2025-10-28 11:09:19', '2025-10-28 11:09:19', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `followup_history`
--

CREATE TABLE `followup_history` (
  `id` int NOT NULL,
  `followup_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text,
  `new_value` text,
  `old_date` date DEFAULT NULL,
  `new_date` date DEFAULT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followup_history`
--

INSERT INTO `followup_history` (`id`, `followup_id`, `action`, `old_value`, `new_value`, `old_date`, `new_date`, `notes`, `created_by`, `created_at`) VALUES
(38, 1, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(39, 2, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(40, 2, 'postponed', '2024-01-15', '2024-01-18', NULL, NULL, 'Client requested delay due to budget review', 1, '2025-10-28 11:04:18'),
(41, 3, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(42, 4, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(43, 4, 'in_progress', 'pending', 'in_progress', NULL, NULL, 'Started working on security audit presentation', 1, '2025-10-28 11:04:18'),
(44, 5, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(45, 5, 'completed', 'pending', 'completed', NULL, NULL, 'Successfully demonstrated platform, client signed contract', 1, '2025-10-28 11:04:18'),
(46, 6, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(47, 6, 'postponed', '2024-01-20', '2024-01-28', NULL, NULL, 'Client needs more time for internal approvals', 1, '2025-10-28 11:04:18'),
(48, 8, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(49, 8, 'rescheduled', '2024-01-25', '2024-01-30', NULL, NULL, 'Moved to accommodate client schedule', 1, '2025-10-28 11:04:18'),
(50, 10, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-10-28 11:04:18'),
(51, 10, 'completed', 'pending', 'completed', NULL, NULL, 'Contract renewed with additional services', 1, '2025-10-28 11:04:18'),
(52, 11, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial UI/UX review task created', 1, '2025-10-28 11:09:19'),
(53, 11, 'in_progress', 'pending', 'in_progress', NULL, NULL, 'Started working on UI mockups', 1, '2025-10-28 11:09:19'),
(54, 11, 'completed', 'in_progress', 'completed', NULL, NULL, 'UI/UX review completed successfully, client approved designs', 1, '2025-10-28 11:09:19'),
(55, 12, 'created', NULL, 'Follow-up created', NULL, NULL, 'Payment gateway integration task created', 1, '2025-10-28 11:09:19'),
(56, 12, 'in_progress', 'pending', 'in_progress', NULL, NULL, 'Started Stripe integration development', 1, '2025-10-28 11:09:19'),
(57, 13, 'created', NULL, 'Follow-up created', NULL, NULL, 'App store submission planning initiated', 1, '2025-10-28 11:09:19'),
(58, 13, 'postponed', '2024-01-15', '2024-01-27', NULL, NULL, 'Client requested delay for additional features', 1, '2025-10-28 11:09:19'),
(59, 14, 'created', NULL, 'Follow-up created', NULL, NULL, 'Beta testing coordination setup', 1, '2025-10-28 11:09:19'),
(60, 15, 'created', NULL, 'Follow-up created', NULL, NULL, 'Push notification system planning', 1, '2025-10-28 11:09:19'),
(61, 15, 'cancelled', 'pending', 'cancelled', NULL, NULL, 'Client decided to use third-party service instead', 1, '2025-10-28 11:09:19'),
(62, 16, 'created', NULL, 'Follow-up created', NULL, NULL, 'Database optimization review scheduled', 1, '2025-10-28 11:09:19'),
(63, 16, 'in_progress', 'pending', 'in_progress', NULL, NULL, 'Started performance analysis and query optimization', 1, '2025-10-28 11:09:19'),
(64, 17, 'created', NULL, 'Follow-up created', NULL, NULL, 'Security audit follow-up scheduled', 1, '2025-10-28 11:09:19'),
(65, 17, 'completed', 'pending', 'completed', NULL, NULL, 'All security recommendations implemented successfully', 1, '2025-10-28 11:09:19'),
(66, 18, 'created', NULL, 'Follow-up created', NULL, NULL, 'Marketing integration planning started', 1, '2025-10-28 11:09:19'),
(67, 18, 'postponed', '2024-01-12', '2024-02-07', NULL, NULL, 'Postponed until after app launch for better focus', 1, '2025-10-28 11:09:19'),
(68, 19, 'created', NULL, 'Follow-up created', NULL, NULL, 'Launch strategy discussion planned', 1, '2025-10-28 11:09:19'),
(69, 20, 'created', NULL, 'Follow-up created', NULL, NULL, 'Support planning initiated for post-launch phase', 1, '2025-10-28 11:09:19');

-- --------------------------------------------------------

--
-- Table structure for table `followup_items`
--

CREATE TABLE `followup_items` (
  `id` int NOT NULL,
  `followup_id` int NOT NULL,
  `item_text` text NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followup_reminders`
--

CREATE TABLE `followup_reminders` (
  `id` int NOT NULL,
  `followup_id` int NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_time` time DEFAULT '09:00:00',
  `is_sent` tinyint(1) DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `user_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
(5, 'Athens', 'QHSE & Sustainability Software', NULL, 'active', '2025-10-26 21:46:24'),
(6, 'SAP', 'ERP Software', NULL, 'active', '2025-10-26 21:46:51'),
(7, 'TU14', 'Liaison Work', 5, 'active', '2025-10-26 21:47:19');

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
  `company_name` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `base_location_lat` decimal(10,8) DEFAULT NULL,
  `base_location_lng` decimal(11,8) DEFAULT NULL,
  `attendance_radius` int DEFAULT '200',
  `backup_email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `logo_path`, `base_location_lat`, `base_location_lng`, `attendance_radius`, `backup_email`, `created_at`) VALUES
(1, 'ERGON', NULL, 9.98148400, 78.14335500, 10, 'info@athenas.co.in', '2025-10-23 06:24:06'),
(4, 'Test Company 1761637731', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:48:51'),
(5, 'Test Company 1761637743', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:49:03'),
(6, 'Test Company 1761637748', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:49:08'),
(7, 'Test Company 1761637913', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:51:53'),
(8, 'Test Company 1761638026', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:53:46'),
(9, 'Test Company 1761638078', NULL, NULL, NULL, 200, NULL, '2025-10-28 07:54:38'),
(10, 'Test Company 1761639441', NULL, NULL, NULL, 200, NULL, '2025-10-28 08:17:21');

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
  `sla_hours` int DEFAULT '24'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('active','inactive','deleted','suspended') DEFAULT 'active',
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
  `department_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `password`, `role`, `is_system_admin`, `phone`, `department`, `status`, `is_first_login`, `temp_password`, `password_reset_required`, `last_login`, `last_ip`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `designation`, `joining_date`, `salary`, `total_points`, `department_id`) VALUES
(1, NULL, 'Athenas Owner', 'info@athenas.co.in', '$2y$10$GKrksmX0Pmp5DoXJ9YskPOZ0x9O192vodYSVg4mRswfgg4kNGfYUq', 'owner', 0, NULL, 'General', 'active', 0, 'owner123', 0, '2025-10-28 13:51:33', '127.0.0.1', '2025-10-23 06:24:06', '2025-10-28 08:21:33', '1990-01-01', 'male', 'Test Address Update', '9999999999', 'Test Designation', '2024-01-01', 50000.00, 0, 1),
(2, NULL, 'Admin User', 'admin@athenas.co.in', '$2y$10$pY8Jv.8ILMtE1qy8V13yGuE/94vq3DnZfMnoQNllteZ5YnBBTpYpC', 'admin', 0, '1234567890', 'Administration', 'active', 0, NULL, 0, '2025-10-27 23:01:22', '127.0.0.1', '2025-10-23 06:24:06', '2025-10-27 17:31:22', NULL, NULL, NULL, NULL, 'System Administrator', '2024-01-01', 50000.00, 0, NULL),
(7, 'EMP001', 'System Admin', 'admin@ergon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 0, NULL, 'General', 'active', 1, NULL, 0, NULL, NULL, '2025-10-23 18:35:21', '2025-10-24 13:52:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(16, 'ATSO003', 'Harini', 'harini@athenas.co.in', '$2y$10$GcyIHTtTvWon4pAZeWQFNei6jnNdEzP0G.onwzEaP1XCowHylNEbu', 'user', 0, '6380795088', 'Finance & Accounts,Liaison,Marketing & Sales,Operations', 'active', 1, 'RST7498R', 1, '2025-10-27 19:26:11', '127.0.0.1', '2025-10-24 02:34:52', '2025-10-27 13:56:11', '2004-06-20', 'female', 'Plot No: 81,Poriyalar Nagar 4th Street,Near By Yadava college,Thirupalai', '9876787689', 'Accountant', '2024-06-27', 15000.00, 0, NULL),
(29, 'EMP002', 'Alice Johnson', 'alice@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, '8767654567', 'IT', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:04:42', '2025-10-28 05:52:06', '2004-01-01', 'female', '', '', 'Accountant', '2025-01-01', 15000.00, 85, NULL),
(30, 'EMP003', 'Bob Smith', 'bob@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, NULL, 'Accounting', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50, NULL),
(31, 'EMP004', 'Carol Davis', 'carol@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, NULL, 'Marketing', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 35, NULL),
(32, 'EMP005', 'David Wilson', 'david@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, NULL, 'IT', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:04:42', '2025-10-27 07:04:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(33, NULL, 'Sarumalini', 'sarumalini@athenas.co.in', '$2y$10$Lb6iRwumSs8oRdmBDdSBGObnGKpcPbACWXD3sCpwFqYS1BhgAKKCK', 'admin', 0, NULL, NULL, 'active', 1, NULL, 0, '2025-10-27 20:40:48', '::1', '2025-10-27 14:44:36', '2025-10-27 15:10:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(34, NULL, 'Test Admin', 'test@admin.com', '$2y$10$sUymqyjUCq401pBlEsZtCuvpB.OPjY7vI69MONSMD/Gv0sKjWE/MC', 'admin', 0, NULL, NULL, 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 14:46:20', '2025-10-27 14:46:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL);

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

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `awarded_on`) VALUES
(1, 29, 1, '2025-10-27 07:04:43'),
(2, 29, 2, '2025-10-27 07:04:43'),
(3, 29, 4, '2025-10-27 07:04:43'),
(4, 30, 1, '2025-10-27 07:04:43'),
(5, 30, 4, '2025-10-27 07:04:43'),
(6, 31, 1, '2025-10-27 07:04:43'),
(7, 2, 1, '2025-10-27 07:07:08'),
(9, 29, 3, '2025-10-27 07:10:34'),
(10, 30, 3, '2025-10-27 07:10:34'),
(11, 31, 3, '2025-10-27 07:10:34'),
(12, 31, 5, '2025-10-27 07:10:34');

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

--
-- Dumping data for table `user_points`
--

INSERT INTO `user_points` (`id`, `user_id`, `points`, `reason`, `reference_type`, `reference_id`, `created_at`) VALUES
(1, 29, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(2, 29, 5, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(3, 29, 15, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(4, 29, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(5, 29, 5, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(6, 29, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(7, 29, 30, 'Weekly completion bonus', 'bonus', NULL, '2025-10-27 07:04:42'),
(8, 30, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(9, 30, 15, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(10, 30, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(11, 30, 5, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(12, 30, 10, 'Consistency bonus', 'bonus', NULL, '2025-10-27 07:04:42'),
(13, 31, 10, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(14, 31, 5, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(15, 31, 5, 'Task completed', 'task', NULL, '2025-10-27 07:04:42'),
(16, 31, 15, 'Quality work bonus', 'bonus', NULL, '2025-10-27 07:04:42'),
(17, 2, 15, 'Urgent task completed', 'task', 36, '2025-10-27 07:07:08'),
(18, 29, 15, 'Urgent task completed', 'task', 42, '2025-10-27 07:10:34'),
(19, 31, 5, 'Daily task completed', 'task', 43, '2025-10-27 07:10:34'),
(20, 31, 5, 'Daily task completed', 'task', 44, '2025-10-27 07:10:34'),
(21, 31, 5, 'Daily task completed', 'task', 45, '2025-10-27 07:10:34'),
(22, 31, 5, 'Daily task completed', 'task', 46, '2025-10-27 07:10:34'),
(23, 31, 5, 'Daily task completed', 'task', 47, '2025-10-27 07:10:34');

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
(6, 1, 'dashboard_layout', 'expanded');

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_type` (`type`);

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
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_planned_date` (`planned_date`),
  ADD KEY `idx_department` (`department_id`);

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
  ADD KEY `idx_user_date` (`user_id`,`follow_up_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_company` (`company_name`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `followup_history`
--
ALTER TABLE `followup_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `followup_id` (`followup_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `followup_items`
--
ALTER TABLE `followup_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `followup_id` (`followup_id`);

--
-- Indexes for table `followup_reminders`
--
ALTER TABLE `followup_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `followup_id` (`followup_id`);

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
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_tasks_due_date` (`due_date`);

--
-- Indexes for table `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_name`);

--
-- Indexes for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- AUTO_INCREMENT for dumped tables
--

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `daily_plans`
--
ALTER TABLE `daily_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `followup_history`
--
ALTER TABLE `followup_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `followup_items`
--
ALTER TABLE `followup_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followup_reminders`
--
ALTER TABLE `followup_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

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
-- Constraints for table `advances`
--
ALTER TABLE `advances`
  ADD CONSTRAINT `advances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advances_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followups`
--
ALTER TABLE `followups`
  ADD CONSTRAINT `followups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followups_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `daily_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `followups_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `followup_history`
--
ALTER TABLE `followup_history`
  ADD CONSTRAINT `followup_history_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followup_items`
--
ALTER TABLE `followup_items`
  ADD CONSTRAINT `followup_items_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followup_reminders`
--
ALTER TABLE `followup_reminders`
  ADD CONSTRAINT `followup_reminders_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
