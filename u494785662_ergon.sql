-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 11, 2025 at 07:10 AM
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advances`
--

CREATE TABLE `advances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `repayment_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `clock_in_time` time DEFAULT NULL,
  `clock_out_time` time DEFAULT NULL,
  `date` date NOT NULL,
  `location_lat` decimal(10,8) DEFAULT 0.00000000,
  `location_lng` decimal(11,8) DEFAULT 0.00000000,
  `status` enum('present','absent','late') DEFAULT 'present',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `clock_in_time`, `clock_out_time`, `date`, `location_lat`, `location_lng`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, '09:00:00', '17:30:00', '2025-10-24', 0.00000000, 0.00000000, 'present', '2025-10-24 14:00:20', '2025-10-24 14:00:20'),
(3, 5, '09:15:00', NULL, '2025-10-24', 0.00000000, 0.00000000, 'present', '2025-10-24 14:00:20', '2025-10-24 14:00:20');

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
(5, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:32:49'),
(6, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:01'),
(7, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 07:34:05'),
(8, NULL, 'auth', 'login_failed', 'Failed login attempt', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:26'),
(9, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:13:30'),
(10, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:45:14'),
(11, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 09:58:54'),
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
(22, NULL, 'auth', 'login_success', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-23 11:04:23'),
(23, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:92:82ca:2720:4e8b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 12:13:19'),
(24, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 12:13:20'),
(25, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:92:82ca:2720:4e8b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 12:15:20'),
(26, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 12:15:31'),
(27, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 12:15:42'),
(28, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 12:15:44'),
(29, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 12:15:54'),
(30, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:92:82ca:2720:4e8b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 12:17:39'),
(31, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:92:82ca:2720:4e8b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 12:21:19'),
(32, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:92:82ca:2720:4e8b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-23 12:24:59'),
(33, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:e1e3:b3ca:4332:cd5b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:06:56'),
(34, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:08:40'),
(35, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:13:16'),
(36, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:13:27'),
(37, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:40:33'),
(38, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:18a5:8b2d:1ea7:1ad', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-23 15:40:40'),
(39, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:51:02'),
(40, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:51:06'),
(41, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:54:47'),
(42, NULL, 'auth', 'login_failed', 'Failed login attempt', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:54:55'),
(43, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:55:04'),
(44, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:55:47'),
(45, NULL, 'auth', 'login_failed', 'Failed login attempt', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:55:55'),
(46, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 01:56:08'),
(47, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:31:37'),
(48, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:31:54'),
(49, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:32:57'),
(50, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 02:33:01'),
(51, NULL, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:7958:9905:6d59:7ba9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 04:31:04'),
(52, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:35:49'),
(53, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:35:58'),
(54, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:01'),
(55, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:12'),
(56, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:19'),
(57, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:20'),
(58, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:36'),
(59, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:37'),
(60, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:36:56'),
(61, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:39:51'),
(62, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:440d:1cd0:657e:a11a', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-24 06:41:52'),
(63, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:41:53'),
(64, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:41:53'),
(65, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:45:59'),
(66, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:46:12'),
(67, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:47:21'),
(68, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:50:51'),
(69, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:50:54'),
(70, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:50:57'),
(71, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:51:00'),
(72, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:51:05'),
(73, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:51:10'),
(74, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 06:51:30'),
(75, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:51:39'),
(76, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:51:51'),
(77, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:54:25'),
(78, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:54:33'),
(79, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:55:19'),
(80, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 06:55:27'),
(81, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:18:08'),
(82, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 07:18:09'),
(83, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 08:18:00'),
(84, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 08:54:17'),
(85, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 10:22:00'),
(86, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 11:58:59'),
(87, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 12:01:49'),
(88, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:10:49'),
(89, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:06'),
(90, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:08'),
(91, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:10'),
(92, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:19'),
(93, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:20'),
(94, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 12:11:34'),
(95, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-24 13:16:44'),
(96, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-24 13:17:42'),
(97, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 13:21:12'),
(98, 3, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:241a:f6ea:9f3f:ac1a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-24 13:23:24'),
(99, 3, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 13:23:38'),
(100, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:3429:e7bd:c79a:dcd4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 13:38:22'),
(101, 3, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:1db0:a0fa:c825:d9cd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 14:32:02'),
(102, 1, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:816d:321d:f89f:e998', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:26:30'),
(103, 1, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:816d:321d:f89f:e998', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:46:38'),
(104, 1, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:816d:321d:f89f:e998', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 18:46:44'),
(105, 1, 'auth', 'login_success', 'User logged in successfully', '2406:7400:ca:24bc:816d:321d:f89f:e998', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-24 19:05:52'),
(106, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-25 04:48:49'),
(107, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:49:46'),
(108, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:49:53'),
(109, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:49:55'),
(110, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:28'),
(111, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:29'),
(112, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:30'),
(113, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:30'),
(114, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:30'),
(115, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:30'),
(116, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:50:41'),
(117, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:51:02'),
(118, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 04:51:23'),
(119, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 04:51:28'),
(120, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 04:51:29'),
(121, NULL, 'auth', 'login_failed', 'Failed login attempt', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 04:51:30'),
(122, NULL, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-25 04:51:40'),
(123, 10, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:52:12'),
(124, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:57:29'),
(125, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:58:29'),
(126, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 04:58:33'),
(127, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 05:14:34'),
(128, 1, 'auth', 'login_success', 'User logged in successfully', '2405:201:e067:384e:2de0:7a4:1699:bcd3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-10-25 05:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `automation_log`
--

CREATE TABLE `automation_log` (
  `id` int(11) NOT NULL,
  `operation` varchar(100) NOT NULL,
  `status` enum('success','failed','warning') NOT NULL,
  `details` text DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `automation_triggers`
--

CREATE TABLE `automation_triggers` (
  `id` int(11) NOT NULL,
  `trigger_name` varchar(100) NOT NULL,
  `trigger_type` enum('time_based','event_based','condition_based') NOT NULL,
  `trigger_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`trigger_conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `target_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_users`)),
  `is_active` tinyint(1) DEFAULT 1,
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `execution_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `automation_triggers`
--

INSERT INTO `automation_triggers` (`id`, `trigger_name`, `trigger_type`, `trigger_conditions`, `actions`, `target_users`, `is_active`, `last_executed_at`, `execution_count`, `created_at`, `updated_at`) VALUES
(1, 'Daily Carry Forward', 'time_based', '{\"time\": \"06:00\", \"days\": [\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]}', '{\"action\": \"carry_forward\", \"auto_escalate\": true}', NULL, 1, NULL, 0, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(2, 'Overdue Task Alert', 'condition_based', '{\"condition\": \"task_overdue\", \"threshold_hours\": 24}', '{\"action\": \"send_notification\", \"escalate_priority\": true}', NULL, 1, NULL, 0, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(3, 'SLA Breach Warning', 'condition_based', '{\"condition\": \"sla_approaching\", \"threshold_percent\": 80}', '{\"action\": \"send_alert\", \"notify_manager\": true}', NULL, 1, NULL, 0, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(4, 'Daily Carry Forward', 'time_based', '{\"time\": \"06:00\", \"days\": [\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]}', '{\"action\": \"carry_forward\", \"auto_escalate\": true}', NULL, 1, NULL, 0, '2025-11-09 10:45:30', '2025-11-09 10:45:30'),
(5, 'Overdue Task Alert', 'condition_based', '{\"condition\": \"task_overdue\", \"threshold_hours\": 24}', '{\"action\": \"send_notification\", \"escalate_priority\": true}', NULL, 1, NULL, 0, '2025-11-09 10:45:30', '2025-11-09 10:45:30'),
(6, 'SLA Breach Warning', 'condition_based', '{\"condition\": \"sla_approaching\", \"threshold_percent\": 80}', '{\"action\": \"send_alert\", \"notify_manager\": true}', NULL, 1, NULL, 0, '2025-11-09 10:45:30', '2025-11-09 10:45:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `badge_definitions`
--

INSERT INTO `badge_definitions` (`id`, `name`, `description`, `icon`, `criteria_type`, `criteria_value`, `is_active`, `created_at`) VALUES
(1, 'First Task', 'Complete your first task', '🎯', 'tasks', 1, 1, '2025-10-26 22:02:46'),
(2, 'Task Master', 'Complete 10 tasks', '⭐', 'tasks', 10, 1, '2025-10-26 22:02:46'),
(3, 'Productivity Pro', 'Achieve 90% productivity score', '🚀', 'productivity', 90, 1, '2025-10-26 22:02:46'),
(4, 'Point Collector', 'Earn 100 points', '💎', 'points', 100, 1, '2025-10-26 22:02:46'),
(5, 'Consistent Performer', '5-day task completion streak', '🔥', 'streak', 5, 1, '2025-10-26 22:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `carry_forward_rules`
--

CREATE TABLE `carry_forward_rules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `rule_type` enum('task','followup','both') DEFAULT 'both',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `priority_boost` tinyint(1) DEFAULT 1,
  `auto_escalate_days` int(11) DEFAULT 3,
  `max_carry_forwards` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carry_forward_rules`
--

INSERT INTO `carry_forward_rules` (`id`, `user_id`, `department_id`, `rule_type`, `conditions`, `actions`, `priority_boost`, `auto_escalate_days`, `max_carry_forwards`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'both', '{\"incomplete_tasks\": true, \"overdue_followups\": true}', '{\"boost_priority\": true, \"create_planner_entry\": true}', 1, 2, 5, 1, '2025-11-09 10:45:30', '2025-11-09 10:45:30'),
(2, NULL, NULL, 'task', '{\"status\": [\"assigned\", \"in_progress\"], \"progress_lt\": 100}', '{\"escalate_after_days\": 3, \"notify_manager\": true}', 1, 3, 5, 1, '2025-11-09 10:45:30', '2025-11-09 10:45:30');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `task_id` int(11) DEFAULT NULL,
  `priority_order` int(11) DEFAULT 0,
  `entry_type` enum('task','followup','manual') DEFAULT 'manual',
  `sync_status` enum('synced','pending','failed') DEFAULT 'synced'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_plans`
--

INSERT INTO `daily_plans` (`id`, `user_id`, `department_id`, `plan_date`, `project_name`, `title`, `description`, `task_category`, `category`, `priority`, `estimated_hours`, `status`, `progress`, `actual_hours`, `completion_notes`, `created_at`, `updated_at`, `completed_at`, `is_followup`, `followup_id`) VALUES
(1, 1, 1, '2025-10-26', 'ERGON Development', 'Review Project Documentation', 'Go through all project docs and update requirements', 'Documentation', 'planned', 'high', 2.00, 'in_progress', 60, 0.00, NULL, '2025-10-26 21:44:31', '2025-10-26 21:44:31', NULL, 0, NULL),
(2, 1, 1, '2025-10-26', 'Client Portal', 'Bug Fixing Session', 'Fix reported issues in client portal', 'Bug Fixing', 'planned', 'urgent', 1.50, 'pending', 0, 0.00, NULL, '2025-10-26 21:44:31', '2025-10-26 21:44:31', NULL, 0, NULL),
(3, 1, 1, '2025-10-26', 'ERGON Development', 'Code Review', 'Review pull requests from team members', 'Code Review', 'planned', 'medium', 1.00, 'completed', 100, 0.00, NULL, '2025-10-26 21:44:31', '2025-10-26 21:44:31', NULL, 0, NULL),
(4, 2, 1, '2025-10-21', NULL, 'Setup Development Environment', 'Configure local development setup', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(5, 2, 1, '2025-10-22', NULL, 'Code Review Session', 'Review team code submissions', NULL, 'planned', 'medium', 1.50, 'completed', 100, 1.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(6, 2, 1, '2025-10-23', NULL, 'Bug Fixing', 'Fix critical production bugs', NULL, 'planned', 'urgent', 3.00, 'completed', 100, 3.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(7, 2, 1, '2025-10-24', NULL, 'Database Optimization', 'Optimize slow queries', NULL, 'planned', 'high', 2.50, 'completed', 100, 2.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(8, 2, 1, '2025-10-25', NULL, 'Feature Development', 'Implement new user features', NULL, 'planned', 'medium', 4.00, 'completed', 100, 4.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(9, 2, 1, '2025-10-26', NULL, 'Testing Phase', 'Run comprehensive tests', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(10, 2, 1, '2025-10-27', NULL, 'Documentation Update', 'Update technical documentation', NULL, 'planned', 'medium', 1.50, 'in_progress', 60, 1.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(11, 3, 2, '2025-10-22', NULL, 'Invoice Processing', 'Process monthly invoices', NULL, 'planned', 'high', 3.00, 'completed', 100, 3.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(12, 3, 2, '2025-10-23', NULL, 'Financial Report', 'Generate quarterly report', NULL, 'planned', 'urgent', 4.00, 'completed', 100, 4.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(13, 3, 2, '2025-10-24', NULL, 'Tax Calculations', 'Calculate monthly tax obligations', NULL, 'planned', 'high', 2.00, 'completed', 100, 2.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(14, 3, 2, '2025-10-25', NULL, 'Expense Review', 'Review team expense claims', NULL, 'planned', 'medium', 1.50, 'completed', 100, 1.00, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL),
(15, 3, 2, '2025-10-27', NULL, 'Budget Planning', 'Plan next quarter budget', NULL, 'planned', 'high', 3.00, 'in_progress', 40, 1.50, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_tasks`
--

CREATE TABLE `daily_tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `planned_date` date NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT 1.00,
  `actual_hours` decimal(4,2) DEFAULT 0.00,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `progress` int(11) DEFAULT 0,
  `completion_notes` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_tasks`
--

INSERT INTO `daily_tasks` (`id`, `title`, `description`, `assigned_to`, `planned_date`, `priority`, `estimated_hours`, `actual_hours`, `status`, `progress`, `completion_notes`, `department_id`, `task_category`, `created_at`, `updated_at`) VALUES
(1, 'Software Testing', '', 3, '2025-11-04', 'medium', 1.00, 0.00, 'planned', 0, NULL, 2, 'Testing', '2025-11-04 05:14:25', '2025-11-04 05:14:25'),
(2, 'Software Testing', '', 28, '2025-11-05', 'medium', 1.00, 0.00, 'planned', 0, NULL, 2, '', '2025-11-05 07:37:55', '2025-11-05 07:37:55'),
(3, 'Software Testing', 'Testing the SAP project', 3, '2025-11-08', 'medium', 2.00, 0.00, 'planned', 0, NULL, 2, 'Testing', '2025-11-08 06:27:54', '2025-11-08 06:27:54');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_workflow_status`
--

INSERT INTO `daily_workflow_status` (`id`, `user_id`, `workflow_date`, `total_planned_tasks`, `total_completed_tasks`, `total_planned_hours`, `total_actual_hours`, `productivity_score`, `last_updated`, `created_at`) VALUES
(1, 1, '2025-10-26', 3, 0, 4.50, 0.00, 0.00, '2025-10-26 21:44:31', '2025-10-26 21:44:31'),
(2, 2, '2025-10-21', 1, 1, 2.00, 2.50, 125.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(3, 2, '2025-10-22', 1, 1, 1.50, 1.00, 66.70, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(4, 2, '2025-10-23', 1, 1, 3.00, 3.50, 116.70, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(5, 2, '2025-10-24', 1, 1, 2.50, 2.00, 80.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(6, 2, '2025-10-25', 1, 1, 4.00, 4.50, 112.50, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(7, 2, '2025-10-26', 1, 1, 2.00, 2.00, 100.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(8, 2, '2025-10-27', 1, 0, 1.50, 1.00, 66.70, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(9, 3, '2025-10-22', 1, 1, 3.00, 3.00, 100.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(10, 3, '2025-10-23', 1, 1, 4.00, 4.50, 112.50, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(11, 3, '2025-10-24', 1, 1, 2.00, 2.50, 125.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(12, 3, '2025-10-25', 1, 1, 1.50, 1.00, 66.70, '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(13, 3, '2025-10-27', 1, 0, 3.00, 1.50, 50.00, '2025-10-27 07:01:10', '2025-10-27 07:01:10');

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
(1, 'General', 'General Department', NULL, 'active', '2025-10-24 13:47:11', '2025-10-24 13:47:11'),
(2, 'IT', 'Information Technology', NULL, 'active', '2025-10-24 13:47:11', '2025-10-24 13:47:11'),
(3, 'HR', 'Human Resources', NULL, 'active', '2025-10-24 13:47:11', '2025-10-24 13:47:11'),
(4, 'Finance', 'Finance Department', NULL, 'active', '2025-10-24 13:47:11', '2025-10-24 13:47:11'),
(6, 'IT', 'Information Technology', NULL, 'active', '2025-10-27 07:01:10', '2025-10-27 07:01:10'),
(12, 'Marketing', 'Sales and Marketing our products', 3, 'active', '2025-10-29 05:51:56', '2025-11-08 05:16:44');

-- --------------------------------------------------------

--
-- Table structure for table `evening_updates`
--

CREATE TABLE `evening_updates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT 'Daily Update',
  `accomplishments` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `tomorrow_plan` text DEFAULT NULL,
  `overall_productivity` int(11) DEFAULT 0,
  `planner_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category`, `amount`, `description`, `receipt_path`, `status`, `created_at`, `updated_at`, `expense_date`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(3, 5, 'Material', 120.00, 'Office supplies', NULL, 'pending', '2025-10-24 14:00:20', '2025-10-24 14:00:20', '2025-11-05', NULL, NULL, NULL),
(4, 3, 'travel', 300.00, 'Travel Expenses', NULL, 'pending', '2025-11-05 06:14:30', '2025-11-05 06:14:30', '2025-11-05', NULL, NULL, NULL),
(5, 24, 'travel', 200.00, 'Travel Expense', NULL, 'approved', '2025-11-05 06:15:33', '2025-11-05 06:16:36', '2025-11-05', 1, '2025-11-05 06:16:36', NULL),
(7, 28, 'food', 200.00, 'Lunch Meals', NULL, 'approved', '2025-11-05 07:53:51', '2025-11-08 05:40:40', '2025-11-05', NULL, NULL, NULL),
(9, 24, 'food', 300.00, 'Lunch Meals', NULL, 'approved', '2025-11-08 06:24:47', '2025-11-08 06:25:01', '2025-11-08', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

CREATE TABLE `followups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','postponed','cancelled') DEFAULT 'pending',
  `follow_up_date` date NOT NULL,
  `original_date` date NOT NULL,
  `reminder_time` time DEFAULT NULL,
  `reschedule_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `auto_created` tinyint(1) DEFAULT 0,
  `parent_followup_id` int(11) DEFAULT NULL,
  `escalation_level` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `user_id`, `task_id`, `title`, `description`, `company_name`, `contact_person`, `contact_phone`, `contact_email`, `project_name`, `department_id`, `priority`, `status`, `follow_up_date`, `original_date`, `reminder_time`, `reschedule_count`, `created_at`, `updated_at`, `completed_at`, `auto_created`, `parent_followup_id`, `escalation_level`) VALUES
(1, 1, NULL, 'ESI', '', 'AS', 'Accountant', '9896798216', NULL, 'test', NULL, 'medium', 'in_progress', '2025-11-05', '2025-11-05', '09:00:00', 0, '2025-11-05 06:36:52', '2025-11-05 06:36:52', NULL, 0, NULL, 0),
(2, 28, NULL, 'ESI', 'testing', 'AS', 'HR', '07858986353', NULL, 'AS', NULL, 'medium', 'postponed', '2025-11-05', '2025-11-05', '14:00:00', 0, '2025-11-05 07:39:17', '2025-11-05 07:39:38', NULL, 0, NULL, 0),
(3, 24, NULL, 'ESI', '', 'AS', 'HR', '98754215', NULL, 'AS', NULL, 'medium', 'in_progress', '2025-11-05', '2025-11-05', '14:00:00', 0, '2025-11-05 08:37:39', '2025-11-05 08:37:39', NULL, 0, NULL, 0),
(4, 3, NULL, 'Testing', 'Testing', 'AS', 'HR', '07858986353', NULL, 'AS', NULL, 'medium', 'in_progress', '2025-11-08', '2025-11-08', '09:00:00', 0, '2025-11-08 06:17:45', '2025-11-08 06:17:45', NULL, 0, NULL, 0),
(5, 24, NULL, 'Software Testing', 'Testing', 'AS', 'HR', '98754215', NULL, 'AS', NULL, 'medium', 'in_progress', '2025-11-08', '2025-11-08', '13:00:00', 0, '2025-11-08 07:02:24', '2025-11-08 07:02:24', NULL, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `followup_history`
--

CREATE TABLE `followup_history` (
  `id` int(11) NOT NULL,
  `followup_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `new_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `followup_history`
--

INSERT INTO `followup_history` (`id`, `followup_id`, `action`, `old_value`, `new_value`, `old_date`, `new_date`, `notes`, `created_by`, `created_at`) VALUES
(3, 1, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 1, '2025-11-05 06:36:52'),
(4, 2, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 28, '2025-11-05 07:39:17'),
(5, 2, 'postponed', '2025-11-05', '2025-11-05 at 04:00 PM', NULL, NULL, 'Rescheduled from 2025-11-05 to 2025-11-05 at 04:00 PM. Reason: ', 28, '2025-11-05 07:39:38'),
(6, 3, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 24, '2025-11-05 08:37:39'),
(7, 4, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 3, '2025-11-08 06:17:46'),
(8, 5, 'created', NULL, 'Follow-up created', NULL, NULL, 'Initial creation', 24, '2025-11-08 07:02:24');

-- --------------------------------------------------------

--
-- Table structure for table `followup_items`
--

CREATE TABLE `followup_items` (
  `id` int(11) NOT NULL,
  `followup_id` int(11) NOT NULL,
  `item_text` text NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followup_reminders`
--

CREATE TABLE `followup_reminders` (
  `id` int(11) NOT NULL,
  `followup_id` int(11) NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_time` time DEFAULT '09:00:00',
  `is_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geofence_locations`
--

CREATE TABLE `geofence_locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius_meters` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `geofence_locations`
--

INSERT INTO `geofence_locations` (`id`, `name`, `latitude`, `longitude`, `radius_meters`, `is_active`, `created_at`) VALUES
(1, 'Main Office', 0.00000000, 0.00000000, 200, 1, '2025-10-23 17:53:57');

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
  `days_requested` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `days_requested`, `reason`, `status`, `created_at`, `updated_at`, `rejection_reason`, `approved_by`, `approved_at`) VALUES
(3, 5, 'Personal', '2024-02-20', '2024-02-21', 2, 'Personal matters', 'Approved', '2025-10-24 14:00:20', '2025-11-05 12:02:50', NULL, NULL, NULL),
(4, 1, 'emergency', '2025-10-27', '2025-10-30', 4, 'test', 'Pending', '2025-10-27 12:07:42', '2025-10-27 12:07:42', NULL, NULL, NULL),
(9, 1, 'sick', '2025-10-28', '2025-10-31', 4, 'Fever', 'Pending', '2025-10-28 12:36:36', '2025-10-28 12:36:36', NULL, NULL, NULL),
(11, 1, 'emergency', '2025-10-30', '2025-11-05', 7, 'Emergncy', 'Approved', '2025-10-29 05:22:24', '2025-11-05 11:56:09', NULL, NULL, NULL),
(15, 10, 'sick', '2025-10-30', '2025-10-30', 1, 'Fever and Cold', 'Rejected', '2025-10-29 07:25:35', '2025-11-05 11:55:36', 'reject', NULL, NULL),
(16, 10, 'sick', '2025-10-30', '2025-10-30', 1, 'Fever and Cold', 'Rejected', '2025-10-29 07:25:42', '2025-11-05 06:40:36', 'Reject Leave', NULL, NULL),
(18, 10, 'sick', '2025-10-29', '2025-11-07', 10, 'Fever', 'Approved', '2025-10-29 08:40:03', '2025-11-05 07:55:36', NULL, NULL, NULL),
(20, 28, 'casual', '2025-11-06', '2025-11-06', 1, 'Casual Leave', 'Approved', '2025-11-05 07:51:48', '2025-11-05 07:52:18', NULL, NULL, NULL),
(21, 24, 'casual', '2025-11-06', '2025-11-06', 1, 'Casual Leave', 'Approved', '2025-11-05 08:30:02', '2025-11-05 12:03:19', NULL, NULL, NULL),
(22, 24, 'annual', '2025-11-06', '2025-11-07', 2, 'Festival Leave', 'Pending', '2025-11-05 12:04:50', '2025-11-05 12:04:50', NULL, NULL, NULL),
(23, 24, 'emergency', '2025-11-06', '2025-11-06', 1, 'Medical Emergency Leave', 'Approved', '2025-11-05 12:14:51', '2025-11-08 06:19:30', NULL, NULL, NULL),
(25, 3, 'casual', '2025-11-09', '2025-11-10', 2, 'kodaikanal Tour', 'Rejected', '2025-11-08 05:36:14', '2025-11-08 05:36:47', 'rejected', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `department_id`, `status`, `created_at`) VALUES
(1, 'ERGON Development', 'Employee management system development', 1, 'active', '2025-10-26 21:44:31'),
(2, 'Client Portal', 'Customer self-service portal', 1, 'active', '2025-10-26 21:44:31'),
(3, 'GST Compliance System', 'Automated GST filing system', 2, 'active', '2025-10-26 21:44:31'),
(4, 'Document Management', 'Digital document processing system', 3, 'active', '2025-10-26 21:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `base_location_lat` decimal(10,8) DEFAULT NULL,
  `base_location_lng` decimal(11,8) DEFAULT NULL,
  `attendance_radius` int(11) DEFAULT 200,
  `backup_email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `logo_path`, `base_location_lat`, `base_location_lng`, `attendance_radius`, `backup_email`, `created_at`) VALUES
(1, 'Athena Solutions', NULL, 9.98118500, 78.14318200, 5, 'info@athenas.co.in', '2025-10-23 06:24:06'),
(2, 'ERGON Company', NULL, NULL, NULL, 200, NULL, '2025-10-23 18:35:28'),
(3, 'ERGON Company', NULL, NULL, NULL, 200, NULL, '2025-10-23 18:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `smart_categories`
--

CREATE TABLE `smart_categories` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_type` enum('task','followup','both') DEFAULT 'both',
  `auto_followup` tinyint(1) DEFAULT 0,
  `default_sla_hours` int(11) DEFAULT 24,
  `priority_weight` int(11) DEFAULT 1,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`keywords`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `smart_categories`
--

INSERT INTO `smart_categories` (`id`, `department_id`, `category_name`, `category_type`, `auto_followup`, `default_sla_hours`, `priority_weight`, `keywords`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Development', 'task', 0, 48, 1, '[\"code\", \"develop\", \"build\", \"implement\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(2, 1, 'Bug Fix', 'task', 1, 24, 1, '[\"bug\", \"fix\", \"error\", \"issue\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(3, 1, 'Code Review', 'task', 0, 8, 1, '[\"review\", \"code\", \"pull request\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(4, 1, 'Follow-up', 'followup', 0, 24, 1, '[\"follow\", \"followup\", \"check\", \"update\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(5, 2, 'Recruitment', 'task', 1, 72, 1, '[\"hire\", \"recruit\", \"interview\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(6, 2, 'Training', 'task', 1, 48, 1, '[\"train\", \"onboard\", \"learn\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(7, 2, 'Performance Review', 'task', 1, 168, 1, '[\"review\", \"performance\", \"appraisal\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56'),
(8, 2, 'Follow-up', 'followup', 0, 24, 1, '[\"follow\", \"followup\", \"check\"]', 1, '2025-11-09 10:32:56', '2025-11-09 10:32:56');

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

CREATE TABLE `sync_log` (
  `id` int(11) NOT NULL,
  `entry_type` enum('task','followup','planner','evening_update') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `action` enum('create','update','delete','sync','carry_forward') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `sync_status` enum('success','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_queue`
--

CREATE TABLE `sync_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('attendance','task_update','leave','expense') NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `client_uuid` varchar(36) NOT NULL,
  `synced` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `synced_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `assigned_for` enum('self','other') DEFAULT 'self',
  `task_type` enum('checklist','milestone','timed','ad-hoc') DEFAULT 'ad-hoc',
  `followup_required` tinyint(1) DEFAULT 0,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `deadline` datetime DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `status` enum('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
  `due_date` date DEFAULT NULL,
  `planned_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `depends_on_task_id` int(11) DEFAULT NULL,
  `sla_hours` int(11) DEFAULT 24,
  `parent_task_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `task_category` varchar(100) DEFAULT NULL,
  `carry_forward_count` int(11) DEFAULT 0,
  `auto_escalated` tinyint(1) DEFAULT 0,
  `last_escalation_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_by`, `assigned_to`, `assigned_for`, `task_type`, `followup_required`, `priority`, `deadline`, `progress`, `status`, `due_date`, `planned_date`, `created_at`, `updated_at`, `depends_on_task_id`, `sla_hours`, `parent_task_id`, `department_id`, `task_category`, `carry_forward_count`, `auto_escalated`, `last_escalation_date`) VALUES
(1, 'Software Testing', 'Ergom Software Testing', 3, 25, 'self', 'ad-hoc', 0, 'medium', '2025-11-04 00:00:00', 0, 'assigned', NULL, '2025-11-04', '2025-11-04 05:12:11', '2025-11-11 06:29:28', NULL, 24, NULL, 2, 'Testing', 0, 0, NULL),
(2, 'Ergon Software Testing', 'Testing the all modules and update the errors in gsheet for Ergon Web Application', 1, 28, 'self', 'checklist', 0, 'medium', '2025-11-08 00:00:00', 17, 'in_progress', NULL, '2025-11-05', '2025-11-05 06:22:58', '2025-11-11 06:29:28', NULL, 24, NULL, 2, 'Bug Fixing', 0, 0, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_categories`
--

INSERT INTO `task_categories` (`id`, `department_name`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'IT', 'Development', 'Software development and coding tasks', 1, '2025-10-26 21:44:31'),
(2, 'IT', 'Testing', 'Quality assurance and testing activities', 1, '2025-10-26 21:44:31'),
(3, 'IT', 'Bug Fixing', 'Error resolution and debugging', 1, '2025-10-26 21:44:31'),
(4, 'IT', 'Planning', 'Project planning and architecture', 1, '2025-10-26 21:44:31'),
(5, 'IT', 'Hosting', 'Server management and deployment', 1, '2025-10-26 21:44:31'),
(6, 'IT', 'Maintenance', 'System maintenance and updates', 1, '2025-10-26 21:44:31'),
(7, 'IT', 'Documentation', 'Technical documentation and guides', 1, '2025-10-26 21:44:31'),
(8, 'IT', 'Code Review', 'Peer code review and quality checks', 1, '2025-10-26 21:44:31'),
(9, 'Accounting', 'Ledger Update', 'General ledger maintenance and updates', 1, '2025-10-26 21:44:31'),
(10, 'Accounting', 'Invoice Creation', 'Customer invoice generation', 1, '2025-10-26 21:44:31'),
(11, 'Accounting', 'Quotation Creation', 'Price quotation preparation', 1, '2025-10-26 21:44:31'),
(12, 'Accounting', 'PO Creation', 'Purchase order generation', 1, '2025-10-26 21:44:31'),
(13, 'Accounting', 'PO Follow-up', 'Purchase order tracking and follow-up', 1, '2025-10-26 21:44:31'),
(14, 'Accounting', 'Payment Follow-up', 'Outstanding payment collection', 1, '2025-10-26 21:44:31'),
(15, 'Accounting', 'Ledger Follow-up', 'Account reconciliation and follow-up', 1, '2025-10-26 21:44:31'),
(16, 'Accounting', 'GST Follow-up', 'GST compliance and filing', 1, '2025-10-26 21:44:31'),
(17, 'Accounting', 'Mail Checking', 'Email correspondence and communication', 1, '2025-10-26 21:44:31'),
(18, 'Accounting', 'Financial Reporting', 'Monthly and quarterly reports', 1, '2025-10-26 21:44:31'),
(19, 'Liaison', 'Document Collection', 'Gathering required documents from clients', 1, '2025-10-26 21:44:31'),
(20, 'Liaison', 'Portal Upload', 'Uploading details in government portals', 1, '2025-10-26 21:44:31'),
(21, 'Liaison', 'Documentation', 'Document preparation and verification', 1, '2025-10-26 21:44:31'),
(22, 'Liaison', 'Follow-up', 'Client and government office follow-ups', 1, '2025-10-26 21:44:31'),
(23, 'Liaison', 'Document Submission', 'Physical document submission', 1, '2025-10-26 21:44:31'),
(24, 'Liaison', 'Courier Services', 'Document dispatch and delivery', 1, '2025-10-26 21:44:31'),
(25, 'Liaison', 'Client Meeting', 'Client consultation and meetings', 1, '2025-10-26 21:44:31'),
(26, 'Liaison', 'Government Office Visit', 'Official visits and submissions', 1, '2025-10-26 21:44:31'),
(27, 'Statutory', 'ESI Work', 'Employee State Insurance related tasks', 1, '2025-10-26 21:44:31'),
(28, 'Statutory', 'EPF Work', 'Employee Provident Fund activities', 1, '2025-10-26 21:44:31'),
(29, 'Statutory', 'Mail Checking', 'Official correspondence review', 1, '2025-10-26 21:44:31'),
(30, 'Statutory', 'Document Preparation', 'Statutory document creation', 1, '2025-10-26 21:44:31'),
(31, 'Statutory', 'Fees Payment', 'Government fees and charges payment', 1, '2025-10-26 21:44:31'),
(32, 'Statutory', 'Attendance Collection', 'Employee attendance compilation', 1, '2025-10-26 21:44:31'),
(33, 'Statutory', 'Compliance Filing', 'Regulatory compliance submissions', 1, '2025-10-26 21:44:31'),
(34, 'Statutory', 'Audit Support', 'Audit documentation and support', 1, '2025-10-26 21:44:31'),
(35, 'Marketing', 'Campaign Planning', 'Marketing campaign strategy and planning', 1, '2025-10-26 21:44:31'),
(36, 'Marketing', 'Content Creation', 'Marketing content and material creation', 1, '2025-10-26 21:44:31'),
(37, 'Marketing', 'Social Media Management', 'Social media posts and engagement', 1, '2025-10-26 21:44:31'),
(38, 'Marketing', 'Lead Generation', 'Prospecting and lead identification', 1, '2025-10-26 21:44:31'),
(39, 'Marketing', 'Client Presentation', 'Sales presentations and proposals', 1, '2025-10-26 21:44:31'),
(40, 'Marketing', 'Market Research', 'Industry and competitor analysis', 1, '2025-10-26 21:44:31'),
(41, 'Marketing', 'Event Planning', 'Marketing events and webinars', 1, '2025-10-26 21:44:31'),
(42, 'Marketing', 'Email Marketing', 'Email campaigns and newsletters', 1, '2025-10-26 21:44:31'),
(43, 'Virtual Office', 'Call Handling', 'Professional call answering service', 1, '2025-10-26 21:44:31'),
(44, 'Virtual Office', 'Mail Management', 'Physical mail handling and forwarding', 1, '2025-10-26 21:44:31'),
(45, 'Virtual Office', 'Address Services', 'Business address and registration', 1, '2025-10-26 21:44:31'),
(46, 'Virtual Office', 'Meeting Coordination', 'Virtual meeting setup and management', 1, '2025-10-26 21:44:31'),
(47, 'Virtual Office', 'Reception Services', 'Virtual reception and customer service', 1, '2025-10-26 21:44:31'),
(48, 'Virtual Office', 'Document Scanning', 'Physical document digitization', 1, '2025-10-26 21:44:31'),
(49, 'Virtual Office', 'Appointment Scheduling', 'Calendar and appointment management', 1, '2025-10-26 21:44:31'),
(50, 'Virtual Office', 'Administrative Support', 'General administrative assistance', 1, '2025-10-26 21:44:31'),
(51, 'Human Resources', 'Recruitment', 'Hiring and onboarding tasks', 1, '2025-11-11 07:08:26'),
(52, 'Human Resources', 'Employee Relations', 'Employee management and relations', 1, '2025-11-11 07:08:26'),
(53, 'Human Resources', 'Training', 'Training and development activities', 1, '2025-11-11 07:08:26'),
(54, 'Information Technology', 'Development', 'Software development tasks', 1, '2025-11-11 07:08:26'),
(55, 'Information Technology', 'Support', 'Technical support and maintenance', 1, '2025-11-11 07:08:26'),
(56, 'Information Technology', 'Infrastructure', 'System and infrastructure management', 1, '2025-11-11 07:08:26'),
(57, 'Finance', 'Accounting', 'Financial accounting and bookkeeping', 1, '2025-11-11 07:08:26'),
(58, 'Finance', 'Budgeting', 'Budget planning and analysis', 1, '2025-11-11 07:08:26'),
(59, 'Finance', 'Audit', 'Financial auditing tasks', 1, '2025-11-11 07:08:26'),
(60, 'Marketing', 'Campaign', 'Marketing campaign activities', 1, '2025-11-11 07:08:26'),
(61, 'Marketing', 'Content', 'Content creation and management', 1, '2025-11-11 07:08:26'),
(62, 'Marketing', 'Analytics', 'Marketing analytics and reporting', 1, '2025-11-11 07:08:26'),
(63, 'Operations', 'Process', 'Operational process management', 1, '2025-11-11 07:08:26'),
(64, 'Operations', 'Quality', 'Quality assurance and control', 1, '2025-11-11 07:08:26'),
(65, 'Operations', 'Logistics', 'Supply chain and logistics', 1, '2025-11-11 07:08:26'),
(66, 'Sales', 'Lead Generation', 'Lead generation activities', 1, '2025-11-11 07:08:26'),
(67, 'Sales', 'Client Follow-up', 'Client follow-up and relationship management', 1, '2025-11-11 07:08:26'),
(68, 'Sales', 'Proposal', 'Proposal and contract management', 1, '2025-11-11 07:08:26'),
(69, 'Human Resources', 'Recruitment', 'Hiring and onboarding tasks', 1, '2025-11-11 07:08:47'),
(70, 'Human Resources', 'Employee Relations', 'Employee management and relations', 1, '2025-11-11 07:08:47'),
(71, 'Human Resources', 'Training', 'Training and development activities', 1, '2025-11-11 07:08:47'),
(72, 'Information Technology', 'Development', 'Software development tasks', 1, '2025-11-11 07:08:47'),
(73, 'Information Technology', 'Support', 'Technical support and maintenance', 1, '2025-11-11 07:08:47'),
(74, 'Information Technology', 'Infrastructure', 'System and infrastructure management', 1, '2025-11-11 07:08:47'),
(75, 'Finance', 'Accounting', 'Financial accounting and bookkeeping', 1, '2025-11-11 07:08:47'),
(76, 'Finance', 'Budgeting', 'Budget planning and analysis', 1, '2025-11-11 07:08:47'),
(77, 'Finance', 'Audit', 'Financial auditing tasks', 1, '2025-11-11 07:08:47'),
(78, 'Marketing', 'Campaign', 'Marketing campaign activities', 1, '2025-11-11 07:08:47'),
(79, 'Marketing', 'Content', 'Content creation and management', 1, '2025-11-11 07:08:47'),
(80, 'Marketing', 'Analytics', 'Marketing analytics and reporting', 1, '2025-11-11 07:08:47'),
(81, 'Operations', 'Process', 'Operational process management', 1, '2025-11-11 07:08:47'),
(82, 'Operations', 'Quality', 'Quality assurance and control', 1, '2025-11-11 07:08:47'),
(83, 'Operations', 'Logistics', 'Supply chain and logistics', 1, '2025-11-11 07:08:47'),
(84, 'Sales', 'Lead Generation', 'Lead generation activities', 1, '2025-11-11 07:08:47'),
(85, 'Sales', 'Client Follow-up', 'Client follow-up and relationship management', 1, '2025-11-11 07:08:47'),
(86, 'Sales', 'Proposal', 'Proposal and contract management', 1, '2025-11-11 07:08:47');

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
-- Table structure for table `unified_entries`
--

CREATE TABLE `unified_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_type` enum('task','followup','planner') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` varchar(50) DEFAULT 'pending',
  `progress` int(11) DEFAULT 0,
  `department_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `sla_hours` int(11) DEFAULT 24,
  `carry_forward_count` int(11) DEFAULT 0,
  `last_sync_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('active','inactive') DEFAULT 'active',
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
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `password`, `role`, `is_system_admin`, `phone`, `department`, `status`, `is_first_login`, `temp_password`, `password_reset_required`, `last_login`, `last_ip`, `created_at`, `updated_at`, `date_of_birth`, `gender`, `address`, `emergency_contact`, `designation`, `joining_date`, `salary`, `total_points`, `department_id`) VALUES
(1, 'EMP001', 'Athena Solutions', 'info@athenas.co.in', '$2y$10$JBqFHXhDuRRKMCpBfoPoRebBcDuTYQB4zQYq1eib..FagDLR4uTn2', 'owner', 0, '98653274125', NULL, 'active', 0, NULL, 0, '2025-11-11 06:05:41', '2405:201:e067:384e:458c:d140:e411:54b2', '2025-10-24 06:24:45', '2025-11-11 06:05:41', NULL, NULL, '', NULL, NULL, NULL, NULL, 0, NULL),
(2, 'EMP009', 'Nelson', 'nelson@gmail.com', '$2y$10$CAbMAmft217iQglI7ZNl9uXBFkSO7miOv2jFCDTnm7aY5r.ltgdrW', 'admin', 1, '9517536422', NULL, 'active', 1, 'ADM5540R', 1, '2025-11-11 06:05:49', '2405:201:e067:384e:458c:d140:e411:54b2', '2025-10-24 10:27:20', '2025-11-11 06:05:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 85, NULL),
(3, 'EMP010', 'Clinton', 'clinton@gmail.com', '$2y$10$VRl8Kt5EO0YOPbQMazi1M.QhKHQ9bj7Yy0XTGPUNur1VYkmLMUGmi', 'admin', 1, '9754822211', NULL, 'active', 0, NULL, 0, '2025-11-08 09:36:39', '2405:201:e067:384e:5b7:cd29:dc45:8f67', '2025-10-24 12:20:19', '2025-11-08 09:36:39', NULL, NULL, '', NULL, NULL, NULL, NULL, 50, NULL),
(5, 'ATSO002', 'Athenas Admin', 'admin@athenas.co.in', '$2y$10$SOoTe/E9GJN/6wPY.AO3y.gBy0rRwWSqQ4J8PVvcIyiYSi/Ckrszy', 'user', 0, '09843508653', NULL, 'active', 1, 'EMP2305V', 1, NULL, NULL, '2025-10-24 13:42:24', '2025-10-24 13:42:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(8, 'ATSO003', 'Athenas Admin', 'ad@s.in', '$2y$10$74a/NjODA/K0LdkC1bC4quKLSs0b5hWjA/YFcH68PSn4Q04FDFeCy', 'user', 0, '09843508653000', NULL, 'active', 1, 'EMP2150Y', 1, NULL, NULL, '2025-10-24 13:51:54', '2025-10-24 13:51:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(9, 'ATSO004', 'Albert', 'albert@gmail.com', '$2y$10$9dga9r4cGryl776HAUlB0ukW7FqzNWkFBe4/l5CKsB0QN4/8XL8qu', 'admin', 0, '9517536422', NULL, 'active', 1, 'EMP3738P', 1, NULL, NULL, '2025-10-24 13:53:54', '2025-10-27 10:58:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(10, 'ATSO005', 'John', 'john@gmail.com', '$2y$10$qbtkPEQ3l/3HfEuMa2O2tOztR2ToXPuH25Lv7nmT/8vNIgQDu7SGe', 'user', 0, '98765432121', 'Administration,IT,HR,Finance,Operations,Sales,Marketing', 'active', 0, NULL, 0, '2025-10-29 07:43:35', '2405:201:e067:384e:280b:1539:a249:1558', '2025-10-24 13:54:57', '2025-10-29 07:43:35', '2003-07-07', 'male', 'Madurai', '9898987654', 'Developer', '2025-10-07', 15000.00, 0, NULL),
(15, 'ATSO006', 'saru', 'maliniranjan00111@gmail.com', '$2y$10$mIRcoKY26D3EYbJzHPKq2.OauhjH5FrifGoq6kT9qm4fCSHkKl8Jy', 'user', 0, '09843508653', NULL, 'active', 1, 'EMP9705Y', 1, NULL, NULL, '2025-10-24 13:55:47', '2025-10-24 13:55:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(16, 'EMP002', 'Alice Johnson', 'alice@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, NULL, 'IT', '', 1, NULL, 0, NULL, NULL, '2025-10-27 07:01:10', '2025-10-28 11:30:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(17, 'EMP003', 'Bob Smith', 'bob@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, NULL, 'Accounting', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:01:10', '2025-10-27 07:01:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(18, 'EMP004', 'Carol Davis', 'carol@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, '98765432121', 'Marketing', 'active', 1, NULL, 0, NULL, NULL, '2025-10-27 07:01:10', '2025-10-27 13:22:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(19, 'EMP005', 'David Wilson', 'david@ergon.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, NULL, 'IT', 'inactive', 1, NULL, 0, NULL, NULL, '2025-10-27 07:01:10', '2025-11-11 06:12:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(24, 'EMP006', 'Simon', 'simon@gmail.com', '$2y$10$igZyKNcSD9FiyeYDv25sNeWajPwvPev1Ykg.xnwZdwl9TT6FzUgsa', 'user', 0, '964614315', NULL, 'active', 1, NULL, 0, '2025-11-11 06:06:10', '2405:201:e067:384e:458c:d140:e411:54b2', '2025-10-28 03:54:34', '2025-11-11 06:06:10', NULL, '', '', '', '', NULL, NULL, 0, NULL),
(25, 'EMP012', 'Matthew', 'matthew@gmail.com', '$2y$10$IJb.cYRYyx8C/ZqIy0e/4.kJ0DGcDJWLM7iBUxcWfbs4lR8FoYdkm', 'user', 0, '9856475213', NULL, 'active', 1, NULL, 0, '2025-11-04 04:40:15', '2405:201:e067:384e:f503:d8f4:dfde:7b2e', '2025-10-28 04:03:46', '2025-11-08 05:15:03', '2001-11-15', '', 'Madurai', '9846222358', 'Developer', '2025-10-08', 15000.00, 0, NULL),
(28, 'EMP007', 'Joel Blesswin', 'joel@gmail.com', '$2y$10$ti8zzIRzD67m765A993l2.O8vH45tzU8s16vAcKawFlMQRHV2dBqq', 'admin', 0, '789546852', NULL, 'active', 1, NULL, 0, '2025-11-05 11:47:00', '2405:201:e067:384e:2414:c85e:825b:fc49', '2025-11-05 05:47:21', '2025-11-08 05:15:03', '2002-12-12', 'male', 'East Gate Madurai', '9856474522', 'Sales Person', '2025-11-01', 20000.00, 0, 12),
(29, 'EMP008', 'Levin', 'levin@gmail.com', '$2y$10$nB7pTyUXCyc5Xim8C13xF.2bQcJEM2LxnLeqqg.H1qVaDgqGJL4ba', 'user', 0, '987854824', NULL, 'active', 1, NULL, 0, NULL, NULL, '2025-11-05 05:49:45', '2025-11-05 07:21:00', '2002-10-22', 'male', 'Madurai', '985647544', 'Accountant', '2025-11-01', 18000.00, 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `awarded_on`) VALUES
(1, 2, 1, '2025-10-27 07:01:10'),
(2, 2, 2, '2025-10-27 07:01:10'),
(3, 2, 4, '2025-10-27 07:01:10'),
(4, 3, 1, '2025-10-27 07:01:10'),
(5, 3, 4, '2025-10-27 07:01:10');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_points`
--

INSERT INTO `user_points` (`id`, `user_id`, `points`, `reason`, `reference_type`, `reference_id`, `created_at`) VALUES
(1, 2, 10, 'Task completed', 'task', 1, '2025-10-27 07:01:10'),
(2, 2, 5, 'Task completed', 'task', 2, '2025-10-27 07:01:10'),
(3, 2, 15, 'Task completed', 'task', 3, '2025-10-27 07:01:10'),
(4, 2, 10, 'Task completed', 'task', 4, '2025-10-27 07:01:10'),
(5, 2, 5, 'Task completed', 'task', 5, '2025-10-27 07:01:10'),
(6, 2, 10, 'Task completed', 'task', 6, '2025-10-27 07:01:10'),
(7, 2, 30, 'Weekly completion bonus', 'bonus', NULL, '2025-10-27 07:01:10'),
(8, 3, 10, 'Task completed', 'task', 8, '2025-10-27 07:01:10'),
(9, 3, 15, 'Task completed', 'task', 9, '2025-10-27 07:01:10'),
(10, 3, 10, 'Task completed', 'task', 10, '2025-10-27 07:01:10'),
(11, 3, 5, 'Task completed', 'task', 11, '2025-10-27 07:01:10'),
(12, 3, 10, 'Consistency bonus', 'bonus', NULL, '2025-10-27 07:01:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(50) NOT NULL,
  `preference_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'theme', 'light', '2025-10-24 06:39:58', '2025-10-25 05:27:41'),
(2, 1, 'language', 'en', '2025-10-24 06:39:58', '2025-10-25 04:57:57'),
(3, 1, 'timezone', 'Asia/Kolkata', '2025-10-24 06:39:58', '2025-10-25 04:57:57'),
(4, 1, 'notifications_email', '1', '2025-10-24 06:39:58', '2025-10-24 06:39:58'),
(5, 1, 'notifications_browser', '1', '2025-10-24 06:39:58', '2025-10-24 06:39:58'),
(6, 1, 'dashboard_layout', 'compact', '2025-10-24 06:39:58', '2025-10-25 05:00:05'),
(124, 4, 'theme', 'light', '2025-10-24 13:24:13', '2025-10-25 04:52:38'),
(168, 2, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(169, 3, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(170, 9, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(171, 16, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(172, 19, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(173, 28, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(174, 5, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(175, 8, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(176, 10, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(177, 15, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(178, 17, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(179, 18, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(180, 24, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(181, 25, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(182, 29, 'theme', 'light', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(183, 2, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(184, 3, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(185, 9, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(186, 16, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(187, 19, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(188, 28, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(189, 5, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(190, 8, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(191, 10, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(192, 15, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(193, 17, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(194, 18, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(195, 24, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(196, 25, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28'),
(197, 29, 'dashboard_layout', 'default', '2025-11-11 06:29:28', '2025-11-11 06:29:28');

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
-- Indexes for table `automation_log`
--
ALTER TABLE `automation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_operation` (`operation`),
  ADD KEY `idx_executed_at` (`executed_at`);

--
-- Indexes for table `automation_triggers`
--
ALTER TABLE `automation_triggers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trigger_type` (`trigger_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_last_executed` (`last_executed_at`);

--
-- Indexes for table `badge_definitions`
--
ALTER TABLE `badge_definitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carry_forward_rules`
--
ALTER TABLE `carry_forward_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_rule_type` (`rule_type`),
  ADD KEY `idx_is_active` (`is_active`);

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
  ADD KEY `idx_planner_status` (`completion_status`),
  ADD KEY `fk_daily_planner_task` (`task_id`);

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
  ADD KEY `idx_assigned_date` (`assigned_to`,`planned_date`),
  ADD KEY `idx_status` (`status`);

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
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`planner_date`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_planner_date` (`planner_date`),
  ADD KEY `idx_created_at` (`created_at`);

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
  ADD KEY `department_id` (`department_id`),
  ADD KEY `fk_followups_task` (`task_id`);

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
-- Indexes for table `geofence_locations`
--
ALTER TABLE `geofence_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_leaves_status_date` (`status`,`start_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smart_categories`
--
ALTER TABLE `smart_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_category` (`department_id`,`category_name`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_category_type` (`category_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry_ref` (`entry_type`,`reference_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_sync_status` (`sync_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `sync_queue`
--
ALTER TABLE `sync_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_client_action` (`client_uuid`,`action_type`),
  ADD KEY `idx_sync_queue_user_synced` (`user_id`,`synced`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_tasks_assigned_status` (`assigned_to`,`status`),
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
-- Indexes for table `unified_entries`
--
ALTER TABLE `unified_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date`),
  ADD KEY `idx_entry_type` (`entry_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_due_date` (`due_date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `admin_positions`
--
ALTER TABLE `admin_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advances`
--
ALTER TABLE `advances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `automation_log`
--
ALTER TABLE `automation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `automation_triggers`
--
ALTER TABLE `automation_triggers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `badge_definitions`
--
ALTER TABLE `badge_definitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `carry_forward_rules`
--
ALTER TABLE `carry_forward_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `circulars`
--
ALTER TABLE `circulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `daily_plans`
--
ALTER TABLE `daily_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `daily_tasks`
--
ALTER TABLE `daily_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_task_updates`
--
ALTER TABLE `daily_task_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `daily_workflow_status`
--
ALTER TABLE `daily_workflow_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `evening_updates`
--
ALTER TABLE `evening_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `followup_history`
--
ALTER TABLE `followup_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `followup_items`
--
ALTER TABLE `followup_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followup_reminders`
--
ALTER TABLE `followup_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `geofence_locations`
--
ALTER TABLE `geofence_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `smart_categories`
--
ALTER TABLE `smart_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sync_log`
--
ALTER TABLE `sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_queue`
--
ALTER TABLE `sync_queue`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unified_entries`
--
ALTER TABLE `unified_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

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
  ADD CONSTRAINT `daily_planner_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_daily_planner_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_followups_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
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
-- Constraints for table `sync_queue`
--
ALTER TABLE `sync_queue`
  ADD CONSTRAINT `sync_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
