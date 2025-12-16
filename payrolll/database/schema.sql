-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 05:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `payroll_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `break_duration` int(11) DEFAULT 0 COMMENT 'Break duration in minutes',
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `late_minutes` int(11) DEFAULT 0,
  `status` enum('present','absent','on-leave','half-day') DEFAULT 'present',
  `leave_type` enum('vacation','sick','emergency','maternity','paternity','other') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `attendance_date`, `time_in`, `time_out`, `break_duration`, `total_hours`, `overtime_hours`, `late_minutes`, `status`, `leave_type`, `remarks`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-12-13', '12:33:00', '12:33:00', 60, 0.00, 0.00, 213, 'present', NULL, '', NULL, NULL, '2025-12-13 04:34:00', '2025-12-13 04:34:00'),
(2, 3, '2025-12-18', '07:38:00', '19:45:00', 60, 11.12, 3.12, 0, 'present', NULL, 'pogi', NULL, NULL, '2025-12-14 09:39:36', '2025-12-14 09:39:36'),
(3, 1, '2025-12-17', '08:00:00', '18:00:00', 60, 9.00, 1.00, 0, 'present', NULL, 'BRYAN PRESENT', NULL, NULL, '2025-12-14 15:56:42', '2025-12-14 15:56:42'),
(4, 2, '2025-12-17', '08:00:00', '17:00:00', 0, 9.00, 1.00, 0, 'present', NULL, 'TESTING', NULL, NULL, '2025-12-14 15:57:24', '2025-12-14 15:57:24'),
(5, 3, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 15:58:15', '2025-12-14 15:58:15'),
(6, 4, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 15:59:11', '2025-12-14 15:59:11'),
(7, 5, '2025-12-14', '08:00:00', '04:00:00', 0, 0.00, 0.00, 0, 'present', NULL, '', NULL, NULL, '2025-12-14 15:59:32', '2025-12-14 15:59:32'),
(8, 6, '2025-12-17', '08:00:00', '04:00:00', 0, 0.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 16:00:11', '2025-12-14 16:00:11'),
(9, 7, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 16:00:51', '2025-12-14 16:00:51'),
(10, 8, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 16:01:39', '2025-12-14 16:01:39'),
(11, 9, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 16:02:19', '2025-12-14 16:02:19'),
(12, 10, '2025-12-17', '08:00:00', '18:00:00', 0, 10.00, 2.00, 0, 'present', NULL, 'test', NULL, NULL, '2025-12-14 16:03:01', '2025-12-14 16:03:01'),
(13, 11, '2025-12-17', '08:00:00', '16:00:00', 0, 8.00, 0.00, 0, 'present', NULL, 'fasefsef', NULL, NULL, '2025-12-14 16:03:30', '2025-12-14 16:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Employee Created', 'employees', 1, NULL, '{\"employee_id\":\"1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-13 04:29:22'),
(2, 1, 'User Account Created for Employee', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-13 04:29:35'),
(3, 2, 'Leave Request Submitted', 'leave_requests', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-13 04:30:34'),
(4, 1, 'Employee Created', 'employees', 2, NULL, '{\"employee_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-13 04:32:44'),
(5, 1, 'Attendance Recorded', 'attendance', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-13 04:34:00'),
(6, 1, 'Employee Created', 'employees', 3, NULL, '{\"employee_id\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 09:29:16'),
(7, 1, 'Payroll Period Created', 'payroll_periods', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 09:31:41'),
(8, 1, 'Attendance Recorded', 'attendance', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 09:39:36'),
(9, 1, 'User Account Created for Employee', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 09:40:14'),
(10, 3, 'Leave Request Submitted', 'leave_requests', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 09:41:31'),
(11, 1, 'Employee Updated', 'employees', 2, NULL, '{\"employee_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 10:34:44'),
(12, 1, 'User Account Created for Employee', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 10:50:49'),
(13, 1, 'User Account Deactivated', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 11:01:30'),
(14, 1, 'User Account Activated', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 11:01:37'),
(15, 1, 'Employee Created', 'employees', 4, NULL, '{\"employee_id\":\"4\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:37:46'),
(16, 1, 'Employee Created', 'employees', 5, NULL, '{\"employee_id\":\"5\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:40:41'),
(17, 1, 'Employee Updated', 'employees', 5, NULL, '{\"employee_id\":\"5\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:41:07'),
(18, 1, 'Employee Created', 'employees', 6, NULL, '{\"employee_id\":\"6\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:43:33'),
(19, 1, 'Employee Created', 'employees', 7, NULL, '{\"employee_id\":\"7\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:47:06'),
(20, 1, 'Employee Created', 'employees', 8, NULL, '{\"employee_id\":\"8\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:48:46'),
(21, 1, 'Employee Created', 'employees', 9, NULL, '{\"employee_id\":\"9\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:50:50'),
(22, 1, 'Employee Created', 'employees', 10, NULL, '{\"employee_id\":\"10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:52:49'),
(23, 1, 'Employee Created', 'employees', 11, NULL, '{\"employee_id\":\"11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:54:46'),
(24, 1, 'Attendance Recorded', 'attendance', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:56:42'),
(25, 1, 'Attendance Recorded', 'attendance', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:57:24'),
(26, 1, 'Attendance Recorded', 'attendance', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:58:15'),
(27, 1, 'Attendance Recorded', 'attendance', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:59:11'),
(28, 1, 'Attendance Recorded', 'attendance', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 15:59:32'),
(29, 1, 'Attendance Recorded', 'attendance', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:00:11'),
(30, 1, 'Attendance Recorded', 'attendance', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:00:51'),
(31, 1, 'Attendance Recorded', 'attendance', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:01:39'),
(32, 1, 'Attendance Recorded', 'attendance', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:02:19'),
(33, 1, 'Attendance Recorded', 'attendance', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:03:01'),
(34, 1, 'Attendance Recorded', 'attendance', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:03:30'),
(35, 1, 'User Account Created for Employee', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:04:58'),
(36, 1, 'User Account Created for Employee', 'users', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:05:10'),
(37, 1, 'User Account Created for Employee', 'users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:05:25'),
(38, 1, 'User Account Created for Employee', 'users', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:05:37'),
(39, 1, 'User Account Created for Employee', 'users', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:05:47'),
(40, 1, 'User Account Created for Employee', 'users', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:05:58'),
(41, 1, 'User Account Created for Employee', 'users', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:06:09'),
(42, 1, 'User Account Created for Employee', 'users', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:06:22'),
(43, 1, 'User Account Deactivated', 'users', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:06:36'),
(44, 1, 'User Account Deactivated', 'users', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:06:49'),
(45, 1, 'User Account Deactivated', 'users', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:06:59'),
(46, 1, 'User Account Deactivated', 'users', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-14 16:07:16');

-- --------------------------------------------------------

--
-- Table structure for table `deduction_types`
--

CREATE TABLE `deduction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('fixed','percentage','variable') NOT NULL,
  `default_value` decimal(12,2) DEFAULT 0.00,
  `is_government_mandated` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deduction_types`
--

INSERT INTO `deduction_types` (`id`, `name`, `code`, `type`, `default_value`, `is_government_mandated`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, 'SSS Contribution', 'SSS', 'percentage', 0.00, 1, 1, 'Social Security System contribution', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(2, 'PhilHealth Contribution', 'PHILHEALTH', 'percentage', 0.00, 1, 1, 'Philippine Health Insurance Corporation contribution', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(3, 'Pag-IBIG Contribution', 'PAGIBIG', 'percentage', 0.00, 1, 1, 'Home Development Mutual Fund contribution', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(4, 'Income Tax', 'TAX', 'percentage', 0.00, 1, 1, 'Withholding tax', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(5, 'Late Penalty', 'LATE', 'variable', 0.00, 0, 1, 'Penalty for late attendance', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(6, 'Absence Deduction', 'ABSENCE', 'variable', 0.00, 0, 1, 'Deduction for unexcused absences', '2025-12-13 04:28:43', '2025-12-13 04:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `employment_type` enum('full-time','part-time','contract','intern') DEFAULT 'full-time',
  `employment_status` enum('active','on-leave','suspended','terminated') DEFAULT 'active',
  `hire_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `sss_number` varchar(20) DEFAULT NULL,
  `philhealth_number` varchar(20) DEFAULT NULL,
  `pagibig_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `middle_name`, `email`, `phone`, `address`, `date_of_birth`, `gender`, `marital_status`, `position`, `department`, `employment_type`, `employment_status`, `hire_date`, `termination_date`, `base_salary`, `hourly_rate`, `bank_name`, `bank_account`, `tax_id`, `sss_number`, `philhealth_number`, `pagibig_number`, `created_at`, `updated_at`) VALUES
(1, 2, '1', 'BRYAN', 'NIEVES', 'A.', 'nievesbryan2004@gmail.com', '0932134544', 'ASDSADA', NULL, '', '', 'killer', 'HEAD', 'full-time', 'active', '2025-12-13', NULL, 35000.00, 0.00, 'GCASH', '21312312', '2132', '213123', '321321', '123123', '2025-12-13 04:29:22', '2025-12-13 04:29:35'),
(2, 4, '2', 'nayrb', 'NIEVES', 'A.', 'nievesbryan@gmail.com', '0932134544', 'ASDSADA', NULL, '', '', 'killer', 'HEAD', 'full-time', 'active', '2025-12-13', NULL, 35000.00, 145.00, 'GCASH', '21312312', '2132', '213123', '321321', '123123', '2025-12-13 04:32:44', '2025-12-14 10:50:49'),
(3, 3, '3', 'Constancio', 'Magtagobtob', 'Dominador', 'adsas@gmail.com', '09123123123', 'asefsef seeds', '2025-12-14', 'male', 'widowed', 'assasin', 'JAA', 'full-time', 'active', '2025-12-16', NULL, 800000.00, 0.00, 'wakakang', '12312312312', '12312312', '12312312', '123231241', '13424124124', '2025-12-14 09:29:16', '2025-12-14 09:40:14'),
(4, 5, '4', 'Jharelle', 'Algar', 'Del Coro', 'joke@gmail.com', '09059319526', 'Cabangcalan, Mandaue City', '2005-02-17', 'male', 'single', 'Programmer', 'IT', 'full-time', 'active', '2025-12-13', NULL, 25000.00, 0.00, '12345678901', '12345678901', '12345678901', '12345678901', '12345678901', '12345678901', '2025-12-14 15:37:46', '2025-12-14 16:04:58'),
(5, 6, '5', 'Rhafe', 'Albinda', 'K.', 'test@gmail.com', '09059319423', 'Maguikay', '2005-02-17', 'male', 'single', 'Programmer', 'IT', 'full-time', 'active', '2025-12-14', NULL, 25000.00, 0.00, '12345678902', '12345678902', '12345678902', '12345678902', '12345678902', '12345678902', '2025-12-14 15:40:41', '2025-12-14 16:05:10'),
(6, 7, '6', 'Billy', 'Nieves', 'A.', 'testi@gmail.com', '09059319424', 'Paknaan, Mandaue City', '2010-10-03', 'male', 'single', 'Programmer', 'IT', 'full-time', 'active', '2025-12-14', NULL, 25000.00, 0.00, '12345678903', '12345678903', '12345678903', '12345678903', '12345678903', '12345678903', '2025-12-14 15:43:33', '2025-12-14 16:05:25'),
(7, 8, '7', 'Marybel', 'Nieves', 'A.', 'testin@gmail.com', '09059319424', 'Paknaan, Mandaue City', '2014-09-03', 'female', 'single', 'IT', 'CEO', 'full-time', 'active', '2025-12-14', NULL, 20000.00, 0.00, '12345678904', '12345678904', '12345678904', '12345678904', '12345678904', '12345678904', '2025-12-14 15:47:06', '2025-12-14 16:05:37'),
(8, 9, '8', 'Babelyn', 'Nieves', 'A.', 'testing@gmail.com', '09059319425', 'Paknaan, Mandaue City', '1987-12-28', 'female', 'single', 'IT', 'CEO', 'full-time', 'active', '2025-12-14', NULL, 40000.00, 0.00, '12345678905', '12345678905', '12345678905', '12345678905', '12345678905', '1234567895', '2025-12-14 15:48:46', '2025-12-14 16:05:47'),
(9, 10, '9', 'Valerio', 'Nieves', 'A.', 'testinga@gmail.com', '09059319426', 'Paknaan, Mandaue City', '1975-01-29', 'male', 'married', 'HR', 'Marketing', 'full-time', 'active', '2025-12-14', NULL, 45000.00, 0.00, '12345678906', '12345678906', '12345678906', '12345678906', '12345678906', '1234567896', '2025-12-14 15:50:50', '2025-12-14 16:05:58'),
(10, 11, '10', 'Ruperta', 'Cimafranca', 'Nieves', 'testingan@gmail.com', '09059319427', 'Sibonga, Cebu City', '1963-10-31', 'male', 'single', 'HR', 'Marketing', 'full-time', 'active', '2025-12-14', NULL, 45000.00, 0.00, '12345678907', '12345678907', '12345678907', '12345678907', '12345678907', '1234567897', '2025-12-14 15:52:49', '2025-12-14 16:06:09'),
(11, 12, '11', 'Melisa', 'Calago', 'Nieves', 'testingan1@gmail.com', '09059319427', 'Sibonga, Cebu City', '1981-06-25', 'female', 'married', 'HR', 'Marketing', 'full-time', 'active', '2025-12-14', NULL, 45000.00, 0.00, '12345678908', '12345678908', '12345678908', '12345678908', '12345678908', '1234567898', '2025-12-14 15:54:46', '2025-12-14 16:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `employee_deductions`
--

CREATE TABLE `employee_deductions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `deduction_type_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_incentives`
--

CREATE TABLE `employee_incentives` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `incentive_type_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incentive_types`
--

CREATE TABLE `incentive_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('fixed','percentage','variable') NOT NULL,
  `default_value` decimal(12,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incentive_types`
--

INSERT INTO `incentive_types` (`id`, `name`, `code`, `type`, `default_value`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Performance Bonus', 'PERF_BONUS', 'variable', 0.00, 1, 'Performance-based bonus', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(2, 'Attendance Bonus', 'ATT_BONUS', 'fixed', 0.00, 1, 'Bonus for perfect attendance', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(3, 'Overtime Pay', 'OT_PAY', 'variable', 0.00, 1, 'Additional pay for overtime work', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(4, 'Transportation Allowance', 'TRANSPO', 'fixed', 0.00, 1, 'Monthly transportation allowance', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(5, 'Meal Allowance', 'MEAL', 'fixed', 0.00, 1, 'Daily meal allowance', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(6, '13th Month Pay', '13TH_MONTH', 'percentage', 0.00, 1, '13th month pay benefit', '2025-12-13 04:28:43', '2025-12-13 04:28:43'),
(7, 'Bryan', '1', 'fixed', 111.00, 1, NULL, '2025-12-13 04:31:37', '2025-12-13 04:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('vacation','sick','emergency','maternity','paternity','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` decimal(5,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `days_requested`, `reason`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'sick', '2025-12-13', '2025-12-14', 2.00, 'ssss', 'approved', 1, '2025-12-13 12:30:51', NULL, '2025-12-13 04:30:34', '2025-12-13 04:30:51'),
(2, 3, 'vacation', '2025-12-24', '2025-12-26', 3.00, 'christmas vacation', 'approved', 1, '2025-12-14 17:42:29', NULL, '2025-12-14 09:41:31', '2025-12-14 09:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('draft','processing','completed','locked') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `period_name`, `start_date`, `end_date`, `pay_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'christmas', '2025-12-01', '2025-12-30', '2025-12-31', 'draft', 1, '2025-12-14 09:31:41', '2025-12-14 09:31:41');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `payroll_period_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `regular_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `gross_salary` decimal(12,2) NOT NULL,
  `tax_deduction` decimal(12,2) DEFAULT 0.00,
  `sss_deduction` decimal(12,2) DEFAULT 0.00,
  `philhealth_deduction` decimal(12,2) DEFAULT 0.00,
  `pagibig_deduction` decimal(12,2) DEFAULT 0.00,
  `late_deduction` decimal(12,2) DEFAULT 0.00,
  `absence_deduction` decimal(12,2) DEFAULT 0.00,
  `other_deductions` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `bonus` decimal(12,2) DEFAULT 0.00,
  `allowance` decimal(12,2) DEFAULT 0.00,
  `incentive` decimal(12,2) DEFAULT 0.00,
  `other_income` decimal(12,2) DEFAULT 0.00,
  `total_incentives` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL,
  `status` enum('draft','approved','paid','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `payroll_period_id`, `employee_id`, `basic_salary`, `regular_hours`, `overtime_hours`, `overtime_pay`, `gross_salary`, `tax_deduction`, `sss_deduction`, `philhealth_deduction`, `pagibig_deduction`, `late_deduction`, `absence_deduction`, `other_deductions`, `total_deductions`, `bonus`, `allowance`, `incentive`, `other_income`, `total_incentives`, `net_pay`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 400000.00, 0.00, 0.00, 0.00, 400000.00, 80000.00, 44000.00, 12000.00, 8000.00, 0.00, 0.00, 0.00, 144000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 256000.00, 'draft', NULL, '2025-12-14 09:32:54', '2025-12-14 09:32:54'),
(2, 1, 1, 17500.00, 0.00, 0.00, 0.00, 17500.00, 3500.00, 1925.00, 525.00, 350.00, 2130.00, 0.00, 0.00, 8430.00, 0.00, 0.00, 0.00, 0.00, 0.00, 9070.00, 'draft', NULL, '2025-12-14 09:32:54', '2025-12-14 09:32:54'),
(3, 1, 2, 17500.00, 0.00, 0.00, 0.00, 17500.00, 3500.00, 1925.00, 525.00, 350.00, 0.00, 0.00, 0.00, 6300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 11200.00, 'draft', NULL, '2025-12-14 09:32:54', '2025-12-14 09:32:54');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'company_name', 'Your Company Name', 'string', 'Company name', NULL, '2025-12-13 04:28:43'),
(2, 'company_address', '', 'string', 'Company address', NULL, '2025-12-13 04:28:43'),
(3, 'regular_hours_per_day', '8', 'number', 'Regular working hours per day', NULL, '2025-12-13 04:28:43'),
(4, 'overtime_rate_multiplier', '1.25', 'number', 'Overtime rate multiplier (1.25 = 125%)', NULL, '2025-12-13 04:28:43'),
(5, 'tax_rate', '0.20', 'number', 'Default income tax rate (20%)', NULL, '2025-12-13 04:28:43'),
(6, 'sss_rate', '0.11', 'number', 'SSS contribution rate (11%)', NULL, '2025-12-13 04:28:43'),
(7, 'philhealth_rate', '0.03', 'number', 'PhilHealth contribution rate (3%)', NULL, '2025-12-13 04:28:43'),
(8, 'pagibig_rate', '0.02', 'number', 'Pag-IBIG contribution rate (2%)', NULL, '2025-12-13 04:28:43'),
(9, 'late_penalty_per_minute', '10', 'number', 'Late penalty per minute in PHP', NULL, '2025-12-13 04:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','hr','employee','accountant') DEFAULT 'employee',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@payroll.com', '$2y$10$D82LoPDigUHqvxCIhcLrj.qZ99Jecge8Bx0L2hk.KeKLh2LevvqEm', 'admin', 'System', 'Administrator', 1, '2025-12-14 23:32:06', '2025-12-13 04:28:43', '2025-12-14 15:32:06'),
(2, 'bryan', 'nievesbryan2004@gmail.com', '$2y$10$3lqD/Au6riTQ1Ye2UnhvMeWRJRFw8ALD/L6UZooUPcJyxueaBb72a', 'employee', 'BRYAN', 'NIEVES', 1, '2025-12-13 12:35:39', '2025-12-13 04:29:35', '2025-12-14 11:01:37'),
(3, 'constancio', 'adsas@gmail.com', '$2y$10$zevHJgJOh5QU91N52HHTPeB1O29hl6ksu2FxLsjsPRdNKJogGH5a.', 'employee', 'Constancio', 'Magtagobtob', 1, '2025-12-14 17:40:40', '2025-12-14 09:40:14', '2025-12-14 09:40:40'),
(4, 'hr', 'nievesbryan@gmail.com', '$2y$10$GR/Yh4jWnccJydwTlFiAhubqIvJA1xKBF3mMr0wlUdTjda0I4GZQ.', 'hr', 'nayrb', 'NIEVES', 1, '2025-12-14 18:51:03', '2025-12-14 10:50:49', '2025-12-14 10:51:03'),
(5, 'jharelle', 'joke@gmail.com', '$2y$10$puGglk17GhfnUlBLx5P1/ONJvu3VNbJKRI7yZTwXwGE04/lVBbeWu', 'accountant', 'Jharelle', 'Algar', 1, NULL, '2025-12-14 16:04:58', '2025-12-14 16:04:58'),
(6, 'rhafe', 'test@gmail.com', '$2y$10$WUhztnfJnY4UznarmFVqxeNxW9fVTrqUCVh1RIPMm/n52M8onDKgG', 'employee', 'Rhafe', 'Albinda', 1, NULL, '2025-12-14 16:05:10', '2025-12-14 16:05:10'),
(7, 'billy', 'testi@gmail.com', '$2y$10$GTjyw8UYGS3pKVtXqldXM.ey9BryEqdBldNuZikSfoYN1vRNYrRii', 'employee', 'Billy', 'Nieves', 1, NULL, '2025-12-14 16:05:25', '2025-12-14 16:05:25'),
(8, 'marybel', 'testin@gmail.com', '$2y$10$Me6HgQMP5DwdkOWw3F1P2upzcJpx1yaQSJvqwk.iuV3Z6kxcvgX7u', 'employee', 'Marybel', 'Nieves', 1, NULL, '2025-12-14 16:05:37', '2025-12-14 16:05:37'),
(9, 'babelyn', 'testing@gmail.com', '$2y$10$QVciFqFAzkUngRazGNcQAuP1GevNlvM.g7DSxTNnNMcIFO2SLIdmW', 'employee', 'Babelyn', 'Nieves', 0, NULL, '2025-12-14 16:05:47', '2025-12-14 16:07:16'),
(10, 'valerio', 'testinga@gmail.com', '$2y$10$bv9RAjm.j.mmb9NPCtf6lu1KcSNlxmSSV5yXW.Aexw4.ROawSz7D6', 'employee', 'Valerio', 'Nieves', 0, NULL, '2025-12-14 16:05:58', '2025-12-14 16:06:49'),
(11, 'ruperta', 'testingan@gmail.com', '$2y$10$lN67n5JCQ7LfiEVlrX890uzaFB3N146v1a.W8yhsvaxp9UU5DWN2q', 'employee', 'Ruperta', 'Cimafranca', 0, NULL, '2025-12-14 16:06:09', '2025-12-14 16:06:36'),
(12, 'melisa', 'testingan1@gmail.com', '$2y$10$QcH1I7qT4R//l1D50CpDxeGOwdGUqkJAAEaWcdYeClfIX4hW5Y8pq', 'employee', 'Melisa', 'Calago', 0, NULL, '2025-12-14 16:06:22', '2025-12-14 16:06:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `deduction_types`
--
ALTER TABLE `deduction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_employment_status` (`employment_status`);

--
-- Indexes for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deduction_type_id` (`deduction_type_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incentive_type_id` (`incentive_type_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `incentive_types`
--
ALTER TABLE `incentive_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pay_date` (`pay_date`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll_record` (`payroll_period_id`,`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `deduction_types`
--
ALTER TABLE `deduction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incentive_types`
--
ALTER TABLE `incentive_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD CONSTRAINT `employee_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_deductions_ibfk_2` FOREIGN KEY (`deduction_type_id`) REFERENCES `deduction_types` (`id`);

--
-- Constraints for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  ADD CONSTRAINT `employee_incentives_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_incentives_ibfk_2` FOREIGN KEY (`incentive_type_id`) REFERENCES `incentive_types` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
