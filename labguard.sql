-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 12:14 PM
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
-- Database: `labguard`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_tbl`
--

CREATE TABLE `admin_tbl` (
  `admin_user_id` int(11) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_tbl`
--

INSERT INTO `admin_tbl` (`admin_user_id`, `admin_id`, `role_id`, `lastname`, `firstname`, `mi`, `email`, `password`, `photo`, `created_at`) VALUES
(4, 'A002', 2, 'admin1', 'admin', 'D', 'admin1@example.com', 'admin123', 'default.jpg', '2025-02-27 16:56:15'),
(5, 'A001', 1, 'Doe', 'John', 'D', 'admin@example.com', '$2y$10$Nm7mlGGwttFKJInmTYftlO3rodVpjuzEoCBAW.u0TMAWsPJZ81IR.', 'default.jpg', '2025-02-27 17:00:46');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_tbl`
--

CREATE TABLE `attendance_tbl` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `prof_id` int(11) DEFAULT NULL,
  `subject_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('check_in','check_out','ended') NOT NULL DEFAULT 'ended',
  `time_out` timestamp NULL DEFAULT NULL,
  `a_status` enum('Present','Absent','Late','Ended') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_tbl`
--

INSERT INTO `attendance_tbl` (`attendance_id`, `student_id`, `prof_id`, `subject_id`, `schedule_id`, `rfid_tag`, `time_in`, `status`, `time_out`, `a_status`, `timestamp`) VALUES
(452, NULL, 7, 5, 14, '0004742408', '2025-04-03 13:59:12', 'check_in', NULL, 'Present', '2025-04-03 13:59:12'),
(454, 8, NULL, 5, 14, '0004806268', '2025-04-03 13:59:35', 'check_in', '2025-04-03 13:59:35', 'Present', '2025-04-03 13:59:35'),
(461, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:11:10', 'check_out', '2025-04-03 14:11:10', 'Present', '2025-04-03 14:11:10'),
(462, 6, NULL, 5, 14, '12345', '2025-04-03 14:11:10', 'check_out', '2025-04-03 14:11:10', 'Present', '2025-04-03 14:11:10'),
(463, 7, NULL, 5, 14, '123456', '2025-04-03 14:11:10', 'check_out', '2025-04-03 14:11:10', 'Present', '2025-04-03 14:11:10'),
(464, 8, NULL, 5, 14, '0004806268', '2025-04-03 14:11:10', 'check_out', '2025-04-03 14:11:10', 'Present', '2025-04-03 14:11:10'),
(465, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:11:13', 'check_in', NULL, 'Present', '2025-04-03 14:11:13'),
(467, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:17:54', 'check_out', '2025-04-03 14:17:54', 'Present', '2025-04-03 14:17:54'),
(468, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:17:57', 'check_in', NULL, 'Present', '2025-04-03 14:17:57'),
(471, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:19:19', 'check_out', '2025-04-03 14:19:19', 'Present', '2025-04-03 14:19:19'),
(472, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:19:23', 'check_in', NULL, 'Present', '2025-04-03 14:19:23'),
(474, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:19:39', 'check_out', '2025-04-03 14:19:39', 'Present', '2025-04-03 14:19:39'),
(475, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:21:44', 'check_in', NULL, 'Present', '2025-04-03 14:21:44'),
(477, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:22:08', 'check_out', '2025-04-03 14:22:08', 'Present', '2025-04-03 14:22:08'),
(478, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:25:04', 'check_in', NULL, 'Present', '2025-04-03 14:25:04'),
(480, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:25:10', 'check_out', '2025-04-03 14:25:10', 'Present', '2025-04-03 14:25:10'),
(481, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:34:11', 'check_in', NULL, 'Present', '2025-04-03 14:34:11'),
(483, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:34:20', 'check_out', '2025-04-03 14:34:20', 'Present', '2025-04-03 14:34:20'),
(484, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:51:21', 'check_in', NULL, 'Present', '2025-04-03 14:51:21'),
(485, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:46', 'check_out', '2025-04-03 14:53:46', 'Present', '2025-04-03 14:53:46'),
(486, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:47', 'check_in', NULL, 'Present', '2025-04-03 14:53:47'),
(487, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:50', 'check_out', '2025-04-03 14:53:50', 'Present', '2025-04-03 14:53:50'),
(488, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:52', 'check_in', NULL, 'Present', '2025-04-03 14:53:52'),
(489, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:53', 'check_out', '2025-04-03 14:53:53', 'Present', '2025-04-03 14:53:53'),
(490, NULL, 7, 5, 14, '0004742408', '2025-04-03 14:53:56', 'check_in', NULL, 'Present', '2025-04-03 14:53:56'),
(492, NULL, 7, 5, 14, '0004742408', '2025-04-03 15:08:55', 'check_out', '2025-04-03 15:08:55', 'Present', '2025-04-03 15:08:55'),
(493, NULL, 7, 5, 14, '0004742408', '2025-04-03 15:09:03', 'check_in', NULL, 'Present', '2025-04-03 15:09:03'),
(494, NULL, 7, 5, 14, '0004742408', '2025-04-03 15:10:29', 'check_out', '2025-04-03 15:10:29', 'Present', '2025-04-03 15:10:29'),
(495, NULL, 7, 5, 14, '0004742408', '2025-04-03 15:23:02', 'check_in', NULL, 'Present', '2025-04-03 15:23:02'),
(497, NULL, 7, 5, 14, '0004742408', '2025-04-03 15:23:37', 'check_out', '2025-04-03 15:23:37', 'Present', '2025-04-03 15:23:37'),
(498, NULL, 9, 5, 15, '0005056696', '2025-05-06 09:50:12', 'check_out', '2025-05-06 09:50:17', 'Present', '2025-05-06 09:50:12'),
(499, NULL, 9, 5, 15, '0005056696', '2025-05-06 09:53:08', 'check_out', '2025-05-06 09:53:09', 'Present', '2025-05-06 09:53:08'),
(500, NULL, 9, 5, 15, '0005056696', '2025-05-06 09:53:39', 'check_out', '2025-05-06 09:53:41', 'Present', '2025-05-06 09:53:39'),
(501, NULL, 9, 5, 15, '0005056696', '2025-05-06 10:04:07', 'check_out', '2025-05-06 10:04:08', 'Ended', '2025-05-06 10:04:07'),
(502, NULL, 9, 5, 15, '0005056696', '2025-05-06 10:08:22', 'check_out', '2025-05-06 10:08:23', 'Ended', '2025-05-06 10:08:22'),
(503, NULL, 9, 5, 15, '0005056696', '2025-05-06 10:12:36', 'check_out', '2025-05-06 10:12:37', 'Ended', '2025-05-06 10:12:36'),
(504, NULL, 9, 5, 15, '0005056696', '2025-05-06 10:12:50', 'check_out', '2025-05-06 10:12:51', 'Ended', '2025-05-06 10:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_tbl`
--

CREATE TABLE `faculty_tbl` (
  `faculty_user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_tbl`
--

INSERT INTO `faculty_tbl` (`faculty_user_id`, `employee_id`, `role_id`, `lastname`, `firstname`, `mi`, `email`, `password`, `rfid_tag`, `photo`, `created_at`) VALUES
(3, 'E004', 2, 'Loreno', 'Jhon Ray', 'M', 'jr@gmail.com', '$2y$10$UTof.FDsW4ttbQMxHfCnsusIDOcQMl8iwPvxAHKV7UpdvQWbd3E/m', '12345', NULL, '2025-03-02 13:54:51');

-- --------------------------------------------------------

--
-- Table structure for table `prof_tbl`
--

CREATE TABLE `prof_tbl` (
  `prof_user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prof_tbl`
--

INSERT INTO `prof_tbl` (`prof_user_id`, `employee_id`, `role_id`, `lastname`, `firstname`, `mi`, `email`, `password`, `rfid_tag`, `photo`, `created_at`) VALUES
(4, 'E003', 1, 'Nadera', 'Quirra Mae', 'D', 'nadera@hehe.com', '$2y$10$IV6itziaSZBHmJ11sY.rdOL8f5.2IaCxSz0d8Z.J1YMAw2JIOcXaq', 'E003', 'images (1).jpg', '2025-02-27 11:50:18'),
(7, 'E004', 1, 'Loreno', 'Jhon Ray', 'M', 'jhonrey.loreno77@gmail.com', '$2y$10$X9ucPUdK//09FiVJLoUGB.tZcG42KGaqnnAVGSwEuvECObGLVVuYq', '0004742408', 'download (2).jpg', '2025-03-02 06:50:24'),
(8, 'E006', 3, 'Barral', 'Ibarra', 'C', 'ibarra@ex.com', '$2y$10$6MroWDKBbshgvFgfgluYmOXcKDxRYh4n5EqrpQsnGx0H4R4qiFLBq', '0004794867', 'Nitro_Wallpaper_01_3840x2400.jpg', '2025-03-27 08:23:42'),
(9, 'E005', 3, 'Golfo', 'Yzabella', 'C', 'golfo@gmail.com', '$2y$10$ZB3wzFPR5xABgwjL2oTOpO4kGhZdOWBbtkAPjwz2QdRq69DslKts.', '0005056696', '364147777_2556534734502108_424220739274862162_n.jpg', '2025-05-06 03:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `roles_tbl`
--

CREATE TABLE `roles_tbl` (
  `role_id` int(11) NOT NULL,
  `role_name` enum('Admin','Faculty','Professor','Student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles_tbl`
--

INSERT INTO `roles_tbl` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Faculty'),
(3, 'Professor'),
(4, 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `room_tbl`
--

CREATE TABLE `room_tbl` (
  `room_id` int(11) NOT NULL,
  `room_number` int(5) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `status` enum('Vacant','Occupied','Maintenance') NOT NULL DEFAULT 'Vacant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_tbl`
--

INSERT INTO `room_tbl` (`room_id`, `room_number`, `room_name`, `status`) VALUES
(1, 603, 'Bautista_603', 'Vacant'),
(2, 503, 'Bautista_503', 'Vacant'),
(3, 504, 'Bautista_504', 'Vacant'),
(4, 604, 'Bautista_604', 'Vacant');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_tbl`
--

CREATE TABLE `schedule_tbl` (
  `schedule_id` int(11) NOT NULL,
  `prof_user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `schedule_time` time NOT NULL,
  `schedule_day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_tbl`
--

INSERT INTO `schedule_tbl` (`schedule_id`, `prof_user_id`, `subject_id`, `section_id`, `room_id`, `schedule_time`, `schedule_day`) VALUES
(2, 4, 5, 1, 1, '13:00:00', 'Saturday'),
(4, 4, 5, 1, 1, '13:00:00', 'Monday'),
(13, 8, 5, 4, 1, '13:09:00', 'Friday'),
(14, 7, 5, 1, 1, '21:33:00', 'Thursday'),
(15, 9, 5, 1, 1, '17:46:00', 'Tuesday');

-- --------------------------------------------------------

--
-- Table structure for table `section_tbl`
--

CREATE TABLE `section_tbl` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `section_level` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_tbl`
--

INSERT INTO `section_tbl` (`section_id`, `section_name`, `section_level`) VALUES
(1, 'SBIT2I', '2nd Year'),
(4, 'SBIT2G', '2nd Year');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollment_tbl`
--

CREATE TABLE `student_enrollment_tbl` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `status` enum('Officially Enrolled','Unofficially Dropped','Dropped') NOT NULL DEFAULT 'Officially Enrolled',
  `date_enrolled` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_tbl`
--

CREATE TABLE `student_tbl` (
  `student_user_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_tbl`
--

INSERT INTO `student_tbl` (`student_user_id`, `student_id`, `role_id`, `lastname`, `firstname`, `mi`, `email`, `rfid_tag`, `section_id`, `photo`, `created_at`) VALUES
(6, '23-1940', 4, 'Loreno', 'Jhon Ray', 'M', 'jr@gmail.com', '12345', 1, '457510754_1971541849947481_8741327121448384375_n.jpg', '2025-03-02 14:48:16'),
(7, '23-1923', 4, 'Jumuad', 'Sam', 'J', 'sam@gmail.com', '123456', 1, '410587651_408008551551370_2003064156466002817_n.jpg', '2025-03-02 14:51:04'),
(8, '232323', 4, 'Detalla', 'Krish', 'D', 'krish@gmail.com', '0004806268', 1, '358038281_662271262426045_4841641984669283118_n.jpg', '2025-03-02 14:51:44'),
(9, '3434343', 4, 'Gutaba', 'JM', 'M', 'jm@gmail.com', '3434343', 4, 'The_Reason_For_His_Words_T.jpg', '2025-03-02 14:53:04'),
(10, '12122', 4, 'De Vera', 'Vaughn', 'M', 'von@gmail.com', '3232332', 4, '0b93331cdd60dee1b109734e9435088aa83ad3899ec2701f2e3d0c3142c034cc.png', '2025-03-02 14:55:17'),
(20, '2323', 4, 'test', 'test', 't', 'admin1@gmail.com', '3434', 1, '367505845_326918929676243_3258514212974443694_n.jpg', '2025-05-06 03:50:59');

-- --------------------------------------------------------

--
-- Table structure for table `subject_tbl`
--

CREATE TABLE `subject_tbl` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `prof_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_tbl`
--

INSERT INTO `subject_tbl` (`subject_id`, `subject_code`, `subject_name`, `prof_user_id`) VALUES
(5, 'ASD', 'Analysis and Design', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  ADD PRIMARY KEY (`admin_user_id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `attendance_tbl`
--
ALTER TABLE `attendance_tbl`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `prof_id` (`prof_id`);

--
-- Indexes for table `faculty_tbl`
--
ALTER TABLE `faculty_tbl`
  ADD PRIMARY KEY (`faculty_user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `prof_tbl`
--
ALTER TABLE `prof_tbl`
  ADD PRIMARY KEY (`prof_user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `roles_tbl`
--
ALTER TABLE `roles_tbl`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `room_tbl`
--
ALTER TABLE `room_tbl`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `schedule_tbl`
--
ALTER TABLE `schedule_tbl`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `prof_user_id` (`prof_user_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `section_tbl`
--
ALTER TABLE `section_tbl`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_name` (`section_name`);

--
-- Indexes for table `student_enrollment_tbl`
--
ALTER TABLE `student_enrollment_tbl`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `student_tbl`
--
ALTER TABLE `student_tbl`
  ADD PRIMARY KEY (`student_user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `subject_tbl`
--
ALTER TABLE `subject_tbl`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `prof_user_id` (`prof_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  MODIFY `admin_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attendance_tbl`
--
ALTER TABLE `attendance_tbl`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT for table `faculty_tbl`
--
ALTER TABLE `faculty_tbl`
  MODIFY `faculty_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prof_tbl`
--
ALTER TABLE `prof_tbl`
  MODIFY `prof_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `roles_tbl`
--
ALTER TABLE `roles_tbl`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_tbl`
--
ALTER TABLE `room_tbl`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `schedule_tbl`
--
ALTER TABLE `schedule_tbl`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `section_tbl`
--
ALTER TABLE `section_tbl`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_enrollment_tbl`
--
ALTER TABLE `student_enrollment_tbl`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_tbl`
--
ALTER TABLE `student_tbl`
  MODIFY `student_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `subject_tbl`
--
ALTER TABLE `subject_tbl`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  ADD CONSTRAINT `admin_tbl_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles_tbl` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_tbl`
--
ALTER TABLE `attendance_tbl`
  ADD CONSTRAINT `attendance_tbl_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_tbl` (`student_user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_tbl_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject_tbl` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_tbl_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `schedule_tbl` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_tbl_ibfk_4` FOREIGN KEY (`prof_id`) REFERENCES `prof_tbl` (`prof_user_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty_tbl`
--
ALTER TABLE `faculty_tbl`
  ADD CONSTRAINT `faculty_tbl_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles_tbl` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `prof_tbl`
--
ALTER TABLE `prof_tbl`
  ADD CONSTRAINT `prof_tbl_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles_tbl` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_tbl`
--
ALTER TABLE `schedule_tbl`
  ADD CONSTRAINT `schedule_tbl_ibfk_1` FOREIGN KEY (`prof_user_id`) REFERENCES `prof_tbl` (`prof_user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_tbl_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject_tbl` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_tbl_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `section_tbl` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_tbl_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `room_tbl` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_enrollment_tbl`
--
ALTER TABLE `student_enrollment_tbl`
  ADD CONSTRAINT `student_enrollment_tbl_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_tbl` (`student_user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_enrollment_tbl_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject_tbl` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_tbl`
--
ALTER TABLE `student_tbl`
  ADD CONSTRAINT `student_tbl_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles_tbl` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_tbl_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `section_tbl` (`section_id`) ON DELETE SET NULL;

--
-- Constraints for table `subject_tbl`
--
ALTER TABLE `subject_tbl`
  ADD CONSTRAINT `subject_tbl_ibfk_1` FOREIGN KEY (`prof_user_id`) REFERENCES `prof_tbl` (`prof_user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
