-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 15, 2025 at 06:24 AM
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
-- Table structure for table `advances`
--

CREATE TABLE `advances` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) DEFAULT 'General Advance',
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `requested_date` date DEFAULT NULL,
  `repayment_months` int DEFAULT '1',
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `location_name` varchar(255) DEFAULT 'Office',
  `status` varchar(20) DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_backup_temp`
--

CREATE TABLE `attendance_backup_temp` (
  `id` int NOT NULL DEFAULT '0',
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
-- Table structure for table `attendance_old`
--

CREATE TABLE `attendance_old` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT 'Office',
  `status` varchar(20) DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_rules`
--

CREATE TABLE `attendance_rules` (
  `id` int NOT NULL,
  `office_latitude` decimal(10,8) DEFAULT '0.00000000',
  `office_longitude` decimal(11,8) DEFAULT '0.00000000',
  `office_radius_meters` int DEFAULT '200',
  `is_gps_required` tinyint(1) DEFAULT '1',
  `grace_period_minutes` int DEFAULT '15',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance_rules`
--

INSERT INTO `attendance_rules` (`id`, `office_latitude`, `office_longitude`, `office_radius_meters`, `is_gps_required`, `grace_period_minutes`, `created_at`) VALUES
(1, 0.00000000, 0.00000000, 200, 1, 15, '2025-11-14 15:07:43'),
(2, 0.00000000, 0.00000000, 200, 1, 15, '2025-11-14 15:10:23');

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
(13, 'Finance & Accounts', 'Consolidated Finance, Accounting and Financial Operations', 16, 'active', '2025-10-27 09:35:18', '2025-11-13 07:44:43'),
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
  `expense_date` date NOT NULL DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `created_at`, `updated_at`, `expense_date`) VALUES
(13, 1, 'Travel', 500.00, 'Test expense for approval testing', NULL, 'pending', '2025-11-15 02:28:32', '2025-11-15 02:28:32', '2025-11-15'),
(14, 1, 'accommodation', 435.00, 'Testing from Bharath', NULL, 'pending', '2025-11-15 03:37:10', '2025-11-15 03:37:10', '2025-10-31');

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
  `reminder_time` time DEFAULT NULL,
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

INSERT INTO `followups` (`id`, `user_id`, `task_id`, `title`, `description`, `company_name`, `contact_person`, `contact_phone`, `contact_email`, `project_name`, `department_id`, `priority`, `status`, `follow_up_date`, `original_date`, `reminder_time`, `reschedule_count`, `created_at`, `updated_at`, `completed_at`, `reminder_sent`, `next_reminder`) VALUES
(22, 16, 6, 'Follow-up: BKGE Ledger', 'Auto-created follow-up for task: BKGE Ledger', 'Company Name', 'Contact Person', '123-456-7890', NULL, 'Project Name', NULL, 'medium', 'completed', '2025-11-10', '2025-11-10', '10:00:00', 0, '2025-11-09 14:12:59', '2025-11-09 14:36:17', '2025-11-09 14:18:53', 0, NULL);

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
(71, 22, 'completed', 'pending', 'completed', NULL, NULL, 'Follow-up marked as completed', 16, '2025-11-09 14:18:53');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rejection_reason` text
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
  `email` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `success`, `attempted_at`, `ip_address`, `user_agent`) VALUES
(1, 'test@test.com', 0, '2025-11-13 10:39:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(2, 'test@test.com', 0, '2025-11-13 10:39:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(3, 'test@test.com', 0, '2025-11-13 10:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(4, 'test@test.com', 0, '2025-11-13 10:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(5, 'test@test.com', 0, '2025-11-13 10:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(6, 'test@test.com', 0, '2025-11-13 10:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(7, 'test@test.com', 0, '2025-11-13 10:39:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(8, 'test@test.com', 0, '2025-11-13 10:39:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(9, 'test@test.com', 0, '2025-11-13 10:39:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(10, 'test@test.com', 0, '2025-11-13 10:39:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(11, 'admin@athenas.co.in', 1, '2025-11-13 11:02:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(12, 'harini@athenas.co.in', 1, '2025-11-13 12:08:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(13, 'accounts@athenas.co.in', 0, '2025-11-13 12:08:24', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(14, 'admin@athenas.co.in', 1, '2025-11-13 12:08:36', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(15, 'info@athenas.co.in', 1, '2025-11-13 12:27:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(16, 'admin@athenas.co.in', 0, '2025-11-13 12:44:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(17, 'admin@athenas.co.in', 1, '2025-11-13 12:44:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(18, 'info@athenas.co.in', 1, '2025-11-13 12:45:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(19, 'accountant@athenas.co.in', 1, '2025-11-13 13:04:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(20, 'info@athenas.co.in', 1, '2025-11-13 13:11:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(21, 'info@athenas.co.in', 1, '2025-11-13 15:40:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(22, 'info@athenas.co.in', 1, '2025-11-13 15:58:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),
(23, 'info@athenas.co.in', 1, '2025-11-13 16:49:48', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(24, 'info@athenas.co.in', 1, '2025-11-13 19:31:57', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(25, 'info@athenas.co.in', 1, '2025-11-13 19:48:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(26, 'info@athenas.co.in', 1, '2025-11-14 06:49:56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(27, 'info@athenas.co.in', 1, '2025-11-14 08:08:21', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(28, 'info@athenas.co.in', 1, '2025-11-14 12:33:08', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(29, 'info@athenas.co.in', 1, '2025-11-14 13:13:18', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(30, 'harini@athenas.co.in', 1, '2025-11-14 17:26:54', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(31, 'info@athenas.co.in', 1, '2025-11-14 18:01:58', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(32, 'info@athenas.co.in', 1, '2025-11-14 20:27:58', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(33, 'admin@athenas.co.in', 0, '2025-11-14 21:08:56', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(34, 'admin@athenas.co.in', 1, '2025-11-14 21:09:12', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(35, 'info@athenas.co.in', 1, '2025-11-15 06:58:39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(36, 'harini@athenas.co.in', 1, '2025-11-15 06:59:19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(37, 'info@athenas.co.in', 1, '2025-11-15 07:54:17', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(38, 'info@athenas.co.in', 1, '2025-11-15 09:45:33', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'),
(39, 'harini@athenas.co.in', 1, '2025-11-15 09:49:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(50) DEFAULT 'info',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`, `type`, `updated_at`) VALUES
(12, 1, 'TEST: New Task', 'You have a new task', NULL, 0, '2025-11-08 05:00:47', 'info', '2025-11-08 05:00:47'),
(13, 1, 'TEST: Leave Approved', 'Leave approved', NULL, 0, '2025-11-08 05:00:47', 'success', '2025-11-08 05:00:47'),
(14, 1, 'TEST: Expense Pending', 'Expense pending', NULL, 0, '2025-11-08 05:00:47', 'warning', '2025-11-08 05:00:47'),
(15, 1, 'TEST: System Update', 'System updated', NULL, 1, '2025-11-08 05:00:47', 'info', '2025-11-08 05:00:47'),
(16, 2, 'Welcome to ERGON', 'Welcome to the employee tracking system!', NULL, 0, '2025-11-08 15:51:44', 'success', '2025-11-08 15:51:44'),
(17, 2, 'New Task Assigned', 'You have been assigned a new task for project development', NULL, 0, '2025-11-08 15:51:44', 'info', '2025-11-08 15:51:44'),
(18, 2, 'Leave Request Update', 'Your leave request status has been updated', NULL, 0, '2025-11-08 15:51:44', 'info', '2025-11-08 15:51:44'),
(19, 2, 'Expense Claim Submitted', 'Your expense claim has been submitted for approval', NULL, 0, '2025-11-08 15:51:44', 'warning', '2025-11-08 15:51:44'),
(20, 2, 'System Update', 'System has been updated with new features', NULL, 0, '2025-11-08 15:51:44', 'info', '2025-11-08 15:51:44'),
(21, 2, 'Pending Approvals', 'You have pending approval requests to review', NULL, 0, '2025-11-08 15:51:44', 'warning', '2025-11-08 15:51:44'),
(22, 2, 'Advance Request', 'New advance request requires your approval', NULL, 0, '2025-11-08 15:51:44', 'warning', '2025-11-08 15:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_change_log`
--

CREATE TABLE `password_change_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `changed_at` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit_log`
--

CREATE TABLE `rate_limit_log` (
  `id` int NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL DEFAULT 'login',
  `attempted_at` datetime NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rate_limit_log`
--

INSERT INTO `rate_limit_log` (`id`, `identifier`, `action`, `attempted_at`, `success`, `ip_address`) VALUES
(1, '::1', 'login', '2025-11-13 10:39:47', 0, '::1'),
(2, '::1', 'login', '2025-11-13 10:39:52', 0, '::1'),
(3, '::1', 'login', '2025-11-13 10:39:53', 0, '::1'),
(4, '::1', 'login', '2025-11-13 10:39:53', 0, '::1'),
(5, '::1', 'login', '2025-11-13 10:39:53', 0, '::1'),
(6, '::1', 'login', '2025-11-13 10:39:53', 0, '::1'),
(7, '::1', 'login', '2025-11-13 10:39:53', 0, '::1'),
(8, '::1', 'login', '2025-11-13 10:39:54', 0, '::1'),
(9, '::1', 'login', '2025-11-13 10:39:54', 0, '::1'),
(10, '::1', 'login', '2025-11-13 10:39:54', 0, '::1'),
(11, '::1', 'password_reset', '2025-11-13 10:42:12', 1, '::1'),
(12, '::1', 'login', '2025-11-13 11:02:31', 1, '::1'),
(13, '127.0.0.1', 'login', '2025-11-13 12:08:08', 1, '127.0.0.1'),
(14, '127.0.0.1', 'login', '2025-11-13 12:08:24', 0, '127.0.0.1'),
(15, '127.0.0.1', 'login', '2025-11-13 12:08:36', 1, '127.0.0.1'),
(16, '::1', 'login', '2025-11-13 12:27:59', 1, '::1'),
(17, '::1', 'login', '2025-11-13 12:44:39', 0, '::1'),
(18, '::1', 'login', '2025-11-13 12:44:51', 1, '::1'),
(19, '::1', 'login', '2025-11-13 12:45:02', 1, '::1'),
(20, '::1', 'login', '2025-11-13 13:04:20', 1, '::1'),
(21, '::1', 'login', '2025-11-13 13:11:33', 1, '::1'),
(22, '::1', 'login', '2025-11-13 15:40:17', 1, '::1'),
(23, '::1', 'login', '2025-11-13 15:58:54', 1, '::1'),
(24, '127.0.0.1', 'login', '2025-11-13 16:49:48', 1, '127.0.0.1'),
(25, '127.0.0.1', 'login', '2025-11-13 19:31:57', 1, '127.0.0.1'),
(26, '127.0.0.1', 'login', '2025-11-13 19:48:08', 1, '127.0.0.1'),
(27, '127.0.0.1', 'login', '2025-11-14 06:49:56', 1, '127.0.0.1'),
(28, '127.0.0.1', 'login', '2025-11-14 08:08:21', 1, '127.0.0.1'),
(29, '127.0.0.1', 'login', '2025-11-14 12:33:08', 1, '127.0.0.1'),
(30, '127.0.0.1', 'login', '2025-11-14 13:13:18', 1, '127.0.0.1'),
(31, '127.0.0.1', 'login', '2025-11-14 17:26:54', 1, '127.0.0.1'),
(32, '127.0.0.1', 'login', '2025-11-14 18:01:58', 1, '127.0.0.1'),
(33, '127.0.0.1', 'login', '2025-11-14 20:27:58', 1, '127.0.0.1'),
(34, '127.0.0.1', 'login', '2025-11-14 21:08:56', 0, '127.0.0.1'),
(35, '127.0.0.1', 'login', '2025-11-14 21:09:12', 1, '127.0.0.1'),
(36, '127.0.0.1', 'login', '2025-11-15 06:58:39', 1, '127.0.0.1'),
(37, '127.0.0.1', 'login', '2025-11-15 06:59:19', 1, '127.0.0.1'),
(38, '127.0.0.1', 'login', '2025-11-15 07:54:17', 1, '127.0.0.1'),
(39, '127.0.0.1', 'login', '2025-11-15 09:45:33', 1, '127.0.0.1'),
(40, '::1', 'login', '2025-11-15 09:49:51', 1, '::1');

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

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL DEFAULT '09:00:00',
  `end_time` time NOT NULL DEFAULT '18:00:00',
  `grace_period` int DEFAULT '15',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `grace_period`, `is_active`, `created_at`) VALUES
(1, 'Regular Shift', '09:00:00', '18:00:00', 15, 1, '2025-11-14 15:07:43');

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
  `assigned_for` enum('self','other') DEFAULT 'self',
  `task_type` enum('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `deadline` datetime DEFAULT NULL,
  `progress` int DEFAULT '0',
  `status` enum('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `depends_on_task_id` int DEFAULT NULL,
  `sla_hours` int DEFAULT '24',
  `department_id` int DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `followup_required` tinyint(1) DEFAULT '0',
  `planned_date` date DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `followup_date` date DEFAULT NULL,
  `followup_time` time DEFAULT NULL
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
(412, 'Liaison', 'Document Collection', 'Gathering required documents from clients', 1, '2025-11-11 08:08:53'),
(413, 'Liaison', 'Portal Upload', 'Uploading details in government portals', 1, '2025-11-11 08:08:53'),
(414, 'Liaison', 'Documentation', 'Document preparation and verification', 1, '2025-11-11 08:08:53'),
(415, 'Liaison', 'Follow-up', 'Client and government office follow-ups', 1, '2025-11-11 08:08:53'),
(416, 'Liaison', 'Document Submission', 'Physical document submission', 1, '2025-11-11 08:08:53'),
(417, 'Liaison', 'Courier Services', 'Document dispatch and delivery', 1, '2025-11-11 08:08:53'),
(418, 'Liaison', 'Client Meeting', 'Client consultation and meetings', 1, '2025-11-11 08:08:53'),
(419, 'Liaison', 'Government Office Visit', 'Official visits and submissions', 1, '2025-11-11 08:08:53'),
(420, 'Human Resources', 'Recruitment', 'Hiring and recruitment activities', 1, '2025-11-11 08:08:53'),
(421, 'Human Resources', 'Training', 'Employee training and development', 1, '2025-11-11 08:08:53'),
(422, 'Human Resources', 'Performance Review', 'Employee performance evaluations', 1, '2025-11-11 08:08:53'),
(423, 'Human Resources', 'Policy Development', 'HR policy creation and updates', 1, '2025-11-11 08:08:53'),
(424, 'Human Resources', 'Employee Relations', 'Managing employee relations and issues', 1, '2025-11-11 08:08:53'),
(425, 'Human Resources', 'Compliance', 'HR compliance and regulatory tasks', 1, '2025-11-11 08:08:53'),
(426, 'Operations', 'Process Improvement', 'Improving operational processes', 1, '2025-11-11 08:08:53'),
(427, 'Operations', 'Quality Control', 'Quality assurance and control', 1, '2025-11-11 08:08:53'),
(428, 'Operations', 'Vendor Management', 'Managing vendor relationships', 1, '2025-11-11 08:08:53'),
(429, 'Operations', 'Inventory Management', 'Managing inventory and supplies', 1, '2025-11-11 08:08:53'),
(430, 'Operations', 'Logistics', 'Logistics and supply chain management', 1, '2025-11-11 08:08:53'),
(431, 'Operations', 'Facility Management', 'Managing office facilities', 1, '2025-11-11 08:08:53'),
(432, 'Finance & Accounts', 'Ledger Update', 'General ledger maintenance and updates', 1, '2025-11-11 08:08:53'),
(433, 'Finance & Accounts', 'Invoice Creation', 'Customer invoice generation', 1, '2025-11-11 08:08:53'),
(434, 'Finance & Accounts', 'Quotation Creation', 'Price quotation preparation', 1, '2025-11-11 08:08:53'),
(435, 'Finance & Accounts', 'PO Creation', 'Purchase order generation', 1, '2025-11-11 08:08:53'),
(436, 'Finance & Accounts', 'PO Follow-up', 'Purchase order tracking and follow-up', 1, '2025-11-11 08:08:53'),
(437, 'Finance & Accounts', 'Payment Follow-up', 'Outstanding payment collection', 1, '2025-11-11 08:08:53'),
(438, 'Finance & Accounts', 'Ledger Follow-up', 'Account reconciliation and follow-up', 1, '2025-11-11 08:08:53'),
(439, 'Finance & Accounts', 'GST Follow-up', 'GST compliance and filing', 1, '2025-11-11 08:08:53'),
(440, 'Finance & Accounts', 'Mail Checking', 'Email correspondence and communication', 1, '2025-11-11 08:08:53'),
(441, 'Finance & Accounts', 'Financial Reporting', 'Monthly and quarterly reports', 1, '2025-11-11 08:08:53'),
(442, 'Finance & Accounts', 'Accounting', 'General accounting and bookkeeping', 1, '2025-11-11 08:08:53'),
(443, 'Finance & Accounts', 'Budgeting', 'Budget planning and management', 1, '2025-11-11 08:08:53'),
(444, 'Finance & Accounts', 'Financial Analysis', 'Financial data analysis and reporting', 1, '2025-11-11 08:08:53'),
(445, 'Finance & Accounts', 'Audit', 'Internal and external audit activities', 1, '2025-11-11 08:08:53'),
(446, 'Finance & Accounts', 'Tax Planning', 'Tax preparation and planning', 1, '2025-11-11 08:08:53'),
(447, 'Finance & Accounts', 'Invoice Processing', 'Processing invoices and payments', 1, '2025-11-11 08:08:53'),
(448, 'Finance & Accounts', 'Bank Reconciliation', 'Bank account reconciliation', 1, '2025-11-11 08:08:53'),
(449, 'Finance & Accounts', 'Expense Tracking', 'Expense monitoring and analysis', 1, '2025-11-11 08:08:53'),
(450, 'Finance & Accounts', 'Petty Cash Management', 'Petty cash handling', 1, '2025-11-11 08:08:53'),
(451, 'Finance & Accounts', 'Vendor Payment', 'Vendor payment processing', 1, '2025-11-11 08:08:53'),
(452, 'Finance & Accounts', 'Customer Payment Processing', 'Customer payment handling', 1, '2025-11-11 08:08:53'),
(453, 'Finance & Accounts', 'Cash Flow Management', 'Cash flow planning and monitoring', 1, '2025-11-11 08:08:53'),
(454, 'Finance & Accounts', 'Investment Analysis', 'Investment evaluation and analysis', 1, '2025-11-11 08:08:53'),
(455, 'Finance & Accounts', 'Cost Analysis', 'Cost evaluation and optimization', 1, '2025-11-11 08:08:53'),
(456, 'Finance & Accounts', 'GST Filing', 'GST return preparation and filing', 1, '2025-11-11 08:08:53'),
(457, 'Finance & Accounts', 'TDS Processing', 'TDS calculation and filing', 1, '2025-11-11 08:08:53'),
(458, 'Finance & Accounts', 'Loan Management', 'Loan processing and management', 1, '2025-11-11 08:08:53'),
(459, 'Finance & Accounts', 'Asset Management', 'Asset tracking and management', 1, '2025-11-11 08:08:53'),
(460, 'Information Technology', 'Development', 'Software development and coding tasks', 1, '2025-11-11 08:08:53'),
(461, 'Information Technology', 'Testing', 'Quality assurance and testing activities', 1, '2025-11-11 08:08:53'),
(462, 'Information Technology', 'Bug Fixing', 'Error resolution and debugging', 1, '2025-11-11 08:08:53'),
(463, 'Information Technology', 'Planning', 'Project planning and architecture', 1, '2025-11-11 08:08:53'),
(464, 'Information Technology', 'Hosting', 'Server management and deployment', 1, '2025-11-11 08:08:53'),
(465, 'Information Technology', 'Maintenance', 'System maintenance and updates', 1, '2025-11-11 08:08:53'),
(466, 'Information Technology', 'Documentation', 'Technical documentation and guides', 1, '2025-11-11 08:08:53'),
(467, 'Information Technology', 'Code Review', 'Peer code review and quality checks', 1, '2025-11-11 08:08:53'),
(468, 'Information Technology', 'Deployment', 'Application deployment and release', 1, '2025-11-11 08:08:53'),
(469, 'Information Technology', 'System Analysis', 'System analysis and design', 1, '2025-11-11 08:08:53'),
(470, 'Information Technology', 'Database Design', 'Database design and optimization', 1, '2025-11-11 08:08:53'),
(471, 'Information Technology', 'API Development', 'API development and integration', 1, '2025-11-11 08:08:53'),
(472, 'Information Technology', 'Frontend Development', 'Frontend development tasks', 1, '2025-11-11 08:08:53'),
(473, 'Information Technology', 'Backend Development', 'Backend development tasks', 1, '2025-11-11 08:08:53'),
(474, 'Information Technology', 'DevOps', 'DevOps and automation tasks', 1, '2025-11-11 08:08:53'),
(475, 'Information Technology', 'Cloud Management', 'Cloud infrastructure management', 1, '2025-11-11 08:08:53'),
(476, 'Information Technology', 'Security Implementation', 'Security implementation and monitoring', 1, '2025-11-11 08:08:53'),
(477, 'Information Technology', 'System Administration', 'System administration tasks', 1, '2025-11-11 08:08:53'),
(478, 'Information Technology', 'Database Management', 'Database administration', 1, '2025-11-11 08:08:53'),
(479, 'Information Technology', 'Security Updates', 'Security patches and updates', 1, '2025-11-11 08:08:53'),
(480, 'Information Technology', 'Backup Management', 'Data backup and recovery', 1, '2025-11-11 08:08:53'),
(481, 'Information Technology', 'Network Management', 'Network administration', 1, '2025-11-11 08:08:53'),
(482, 'Information Technology', 'User Support', 'Technical user support', 1, '2025-11-11 08:08:53'),
(483, 'Information Technology', 'Software Installation', 'Software installation and configuration', 1, '2025-11-11 08:08:53'),
(484, 'Information Technology', 'Hardware Maintenance', 'Hardware maintenance and repair', 1, '2025-11-11 08:08:53'),
(485, 'Information Technology', 'Performance Monitoring', 'System performance monitoring', 1, '2025-11-11 08:08:53'),
(486, 'Marketing & Sales', 'Campaign Planning', 'Marketing campaign strategy and planning', 1, '2025-11-11 08:08:53'),
(487, 'Marketing & Sales', 'Content Creation', 'Marketing content and material creation', 1, '2025-11-11 08:08:53'),
(488, 'Marketing & Sales', 'Social Media Management', 'Social media posts and engagement', 1, '2025-11-11 08:08:53'),
(489, 'Marketing & Sales', 'Lead Generation', 'Prospecting and lead identification', 1, '2025-11-11 08:08:53'),
(490, 'Marketing & Sales', 'Client Presentation', 'Sales presentations and proposals', 1, '2025-11-11 08:08:53'),
(491, 'Marketing & Sales', 'Market Research', 'Industry and competitor analysis', 1, '2025-11-11 08:08:53'),
(492, 'Marketing & Sales', 'Event Planning', 'Marketing events and webinars', 1, '2025-11-11 08:08:53'),
(493, 'Marketing & Sales', 'Email Marketing', 'Email campaigns and newsletters', 1, '2025-11-11 08:08:53'),
(494, 'Marketing & Sales', 'Client Meeting', 'Meeting with clients and prospects', 1, '2025-11-11 08:08:53'),
(495, 'Marketing & Sales', 'Proposal Writing', 'Creating sales proposals and quotes', 1, '2025-11-11 08:08:53'),
(496, 'Marketing & Sales', 'Customer Support', 'Supporting existing customers', 1, '2025-11-11 08:08:53'),
(497, 'Marketing & Sales', 'Brand Management', 'Brand development and management', 1, '2025-11-11 08:08:53'),
(498, 'Marketing & Sales', 'Digital Marketing', 'Digital marketing campaigns', 1, '2025-11-11 08:08:53'),
(499, 'Marketing & Sales', 'SEO/SEM', 'Search engine optimization and marketing', 1, '2025-11-11 08:08:53'),
(500, 'Marketing & Sales', 'Public Relations', 'Public relations and communications', 1, '2025-11-11 08:08:53'),
(501, 'Marketing & Sales', 'Customer Surveys', 'Customer feedback and surveys', 1, '2025-11-11 08:08:53'),
(502, 'Marketing & Sales', 'Competitor Analysis', 'Competitive analysis and research', 1, '2025-11-11 08:08:53'),
(503, 'Marketing & Sales', 'Product Promotion', 'Product promotion and marketing', 1, '2025-11-11 08:08:53'),
(504, 'Marketing & Sales', 'Sales Presentation', 'Sales presentation development', 1, '2025-11-11 08:08:53'),
(505, 'Marketing & Sales', 'Deal Negotiation', 'Deal negotiation and closing', 1, '2025-11-11 08:08:53'),
(506, 'Marketing & Sales', 'Customer Onboarding', 'New customer onboarding', 1, '2025-11-11 08:08:53'),
(507, 'Marketing & Sales', 'Account Management', 'Existing account management', 1, '2025-11-11 08:08:53'),
(508, 'Marketing & Sales', 'Sales Reporting', 'Sales performance reporting', 1, '2025-11-11 08:08:53'),
(509, 'Marketing & Sales', 'CRM Management', 'CRM system management', 1, '2025-11-11 08:08:53'),
(510, 'Marketing & Sales', 'Territory Management', 'Sales territory management', 1, '2025-11-11 08:08:53'),
(511, 'Marketing & Sales', 'Product Demo', 'Product demonstration', 1, '2025-11-11 08:08:53'),
(512, 'Marketing & Sales', 'Contract Management', 'Contract negotiation and management', 1, '2025-11-11 08:08:53');

-- --------------------------------------------------------

--
-- Stand-in structure for view `unified_dashboard_view`
-- (See below for the actual view)
--
CREATE TABLE `unified_dashboard_view` (
`assigned_user` varchar(100)
,`category` varchar(100)
,`created_at` timestamp
,`department_id` int
,`department_name` varchar(100)
,`description` mediumtext
,`due_date` datetime
,`entry_type` varchar(8)
,`id` int
,`priority` varchar(6)
,`progress` bigint
,`sla_hours` bigint
,`status` varchar(11)
,`title` varchar(255)
,`updated_at` timestamp
,`user_id` int
);

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
  `department_id` int DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `password`, `role`, `is_system_admin`, `phone`, `department`, `status`, `is_first_login`, `temp_password`, `password_reset_required`, `last_login`, `last_ip`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `designation`, `joining_date`, `salary`, `total_points`, `department_id`, `reset_token`, `reset_token_expires`, `password_changed_at`) VALUES
(1, 'EMP006', 'Athenas Owner', 'info@athenas.co.in', '$2y$10$GKrksmX0Pmp5DoXJ9YskPOZ0x9O192vodYSVg4mRswfgg4kNGfYUq', 'owner', 0, NULL, 'General', 'active', 0, 'owner123', 0, '2025-11-15 09:45:33', '127.0.0.1', '2025-10-23 06:24:06', '2025-11-15 04:15:33', '1990-01-01', 'male', 'Test Address Update', '9999999999', 'Test Designation', '2024-01-01', 50000.00, 0, 1, NULL, NULL, NULL),
(2, 'EMP007', 'Admin User', 'admin@athenas.co.in', '$2y$10$KRbwc2i0WMXb7h69dnWKI.xNGCt6DAUi2DBHWouGlOwZQJvOYp8Ki', 'admin', 0, '1234567890', 'Administration', 'active', 0, NULL, 0, '2025-11-14 21:09:12', '127.0.0.1', '2025-10-23 06:24:06', '2025-11-14 15:39:12', NULL, NULL, NULL, NULL, 'System Administrator', '2024-01-01', 50000.00, 0, NULL, NULL, NULL, NULL),
(16, 'ATSO003', 'Harini', 'harini@athenas.co.in', '$2y$10$GcyIHTtTvWon4pAZeWQFNei6jnNdEzP0G.onwzEaP1XCowHylNEbu', 'admin', 0, '6380795088', 'Finance & Accounts,Liaison,Marketing & Sales,Operations', 'active', 1, 'RST7498R', 1, '2025-11-15 09:49:51', '::1', '2025-10-24 02:34:52', '2025-11-15 04:19:51', '2004-06-20', 'female', 'Plot No: 81,Poriyalar Nagar 4th Street,Near By Yadava college,Thirupalai', '9876787689', 'Accountant', '2024-06-27', 15000.00, 0, NULL, NULL, NULL, NULL),
(33, 'EMP008', 'Sarumalini', 'sarumalini@athenas.co.in', '$2y$10$Lb6iRwumSs8oRdmBDdSBGObnGKpcPbACWXD3sCpwFqYS1BhgAKKCK', 'user', 0, NULL, NULL, 'active', 1, NULL, 0, '2025-10-27 20:40:48', '::1', '2025-10-27 14:44:36', '2025-11-13 07:12:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(36, NULL, 'Bharath', 'accountant@athenas.co.in', '$2y$10$ID9ajaqXQtplWZJ8lfZU4uayZkoYVdo.bytu/z1.AQLZ/a7eDhEOS', 'admin', 0, NULL, NULL, 'active', 1, NULL, 0, '2025-11-13 13:04:20', '::1', '2025-11-13 07:33:41', '2025-11-13 07:34:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `created_at`, `updated_at`) VALUES
(1, 36, 'theme', 'light', '2025-11-13 07:41:13', '2025-11-13 07:41:22'),
(5, 1, 'theme', 'light', '2025-11-13 07:41:36', '2025-11-13 14:01:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advances`
--
ALTER TABLE `advances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_advances_user_status_new` (`user_id`,`status`),
  ADD KEY `idx_advances_user_id_new` (`user_id`),
  ADD KEY `idx_advances_status_new` (`status`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `attendance_old`
--
ALTER TABLE `attendance_old`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_check_in_date` (`check_in`);

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
-- Indexes for table `daily_planner`
--
ALTER TABLE `daily_planner`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_planner_user_date` (`user_id`,`plan_date`),
  ADD KEY `idx_daily_planner_department` (`department_id`),
  ADD KEY `idx_planner_status` (`completion_status`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `head_id` (`head_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_departments_name_new` (`name`);

--
-- Indexes for table `evening_updates`
--
ALTER TABLE `evening_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expenses_status` (`status`),
  ADD KEY `idx_expenses_user_status_new` (`user_id`,`status`),
  ADD KEY `idx_expenses_user_id_new` (`user_id`),
  ADD KEY `idx_expenses_status_new` (`status`),
  ADD KEY `idx_expenses_date_new` (`expense_date`);

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
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leaves_status_date` (`status`,`start_date`),
  ADD KEY `idx_leaves_user_status_new` (`user_id`,`status`),
  ADD KEY `idx_leaves_user_id_new` (`user_id`),
  ADD KEY `idx_leaves_status_new` (`status`),
  ADD KEY `idx_leaves_start_date_new` (`start_date`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`attempted_at`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read_new` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_user_id_new` (`user_id`),
  ADD KEY `idx_notifications_created_at_new` (`created_at`);

--
-- Indexes for table `password_change_log`
--
ALTER TABLE `password_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier_action_time` (`identifier`,`action`,`attempted_at`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

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
  ADD KEY `idx_tasks_assigned_to_new` (`assigned_to`),
  ADD KEY `idx_tasks_status_new` (`status`),
  ADD KEY `idx_tasks_project_new` (`project_name`),
  ADD KEY `idx_tasks_created_at_new` (`created_at`),
  ADD KEY `idx_tasks_priority_new` (`priority`),
  ADD KEY `idx_tasks_user_status_new` (`assigned_to`,`status`),
  ADD KEY `idx_tasks_status_due_new` (`status`,`due_date`);

--
-- Indexes for table `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_name`);

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
  ADD KEY `idx_users_employee_id` (`employee_id`),
  ADD KEY `idx_users_department_new` (`department_id`),
  ADD KEY `idx_users_status_new` (`status`),
  ADD KEY `idx_users_role_new` (`role`),
  ADD KEY `idx_users_email_new` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_key` (`user_id`,`preference_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advances`
--
ALTER TABLE `advances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_old`
--
ALTER TABLE `attendance_old`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_rules`
--
ALTER TABLE `attendance_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `daily_planner`
--
ALTER TABLE `daily_planner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `evening_updates`
--
ALTER TABLE `evening_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `followup_history`
--
ALTER TABLE `followup_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `password_change_log`
--
ALTER TABLE `password_change_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_limit_log`
--
ALTER TABLE `rate_limit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=513;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

-- --------------------------------------------------------

--
-- Structure for view `unified_dashboard_view`
--
DROP TABLE IF EXISTS `unified_dashboard_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `unified_dashboard_view`  AS SELECT 'task' AS `entry_type`, `t`.`id` AS `id`, `t`.`title` AS `title`, `t`.`description` AS `description`, `t`.`assigned_to` AS `user_id`, `t`.`priority` AS `priority`, `t`.`status` AS `status`, `t`.`progress` AS `progress`, `t`.`deadline` AS `due_date`, `t`.`department_id` AS `department_id`, `t`.`task_category` AS `category`, `t`.`sla_hours` AS `sla_hours`, `t`.`created_at` AS `created_at`, `t`.`updated_at` AS `updated_at`, `u`.`name` AS `assigned_user`, `d`.`name` AS `department_name` FROM ((`tasks` `t` left join `users` `u` on((`t`.`assigned_to` = `u`.`id`))) left join `departments` `d` on((`t`.`department_id` = `d`.`id`)))union all select 'followup' AS `entry_type`,`f`.`id` AS `id`,`f`.`title` AS `title`,`f`.`description` AS `description`,`f`.`user_id` AS `user_id`,(case when (`f`.`follow_up_date` < curdate()) then 'high' when (`f`.`follow_up_date` = curdate()) then 'medium' else 'low' end) AS `priority`,`f`.`status` AS `status`,(case when (`f`.`status` = 'completed') then 100 else 0 end) AS `progress`,`f`.`follow_up_date` AS `due_date`,`f`.`department_id` AS `department_id`,'follow-up' AS `category`,24 AS `sla_hours`,`f`.`created_at` AS `created_at`,`f`.`updated_at` AS `updated_at`,`u2`.`name` AS `assigned_user`,`d2`.`name` AS `department_name` from ((`followups` `f` left join `users` `u2` on((`f`.`user_id` = `u2`.`id`))) left join `departments` `d2` on((`f`.`department_id` = `d2`.`id`)))  ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_old`
--
ALTER TABLE `attendance_old`
  ADD CONSTRAINT `attendance_old_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_planner`
--
ALTER TABLE `daily_planner`
  ADD CONSTRAINT `daily_planner_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_planner_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `followups_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `followup_history`
--
ALTER TABLE `followup_history`
  ADD CONSTRAINT `followup_history_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `followups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `password_change_log`
--
ALTER TABLE `password_change_log`
  ADD CONSTRAINT `password_change_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
