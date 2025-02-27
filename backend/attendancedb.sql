-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2025 at 03:31 PM
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
-- Database: `attendancedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `croom_id` int(11) NOT NULL,
  `check_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `check_out` timestamp NULL DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `tbl_attendance`
--
DELIMITER $$
CREATE TRIGGER `trg_croom_occupy` AFTER INSERT ON `tbl_attendance` FOR EACH ROW BEGIN
    -- Set the classroom status to "Occupied"
    UPDATE tbl_crooms 
    SET status_id = (SELECT status_id FROM tbl_croom_stats WHERE status_name = 'occupied') 
    WHERE croom_id = NEW.croom_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_croom_vacant` AFTER UPDATE ON `tbl_attendance` FOR EACH ROW BEGIN
    -- Check if the professor logged out
    IF NEW.check_out IS NOT NULL THEN
        -- If no other active sessions exist, mark the classroom as available
        IF NOT EXISTS (
            SELECT 1 FROM tbl_attendance 
            WHERE croom_id = NEW.croom_id AND check_out IS NULL
        ) THEN
            UPDATE tbl_crooms 
            SET status_id = (SELECT status_id FROM tbl_croom_stats WHERE status_name = 'available') 
            WHERE croom_id = NEW.croom_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_remove_croom_occ` AFTER UPDATE ON `tbl_attendance` FOR EACH ROW BEGIN
    IF NEW.check_out IS NOT NULL THEN
        DELETE FROM tbl_croom_occ WHERE croom_id = NEW.croom_id AND prof_id = NEW.user_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_classroom_schedule`
--

CREATE TABLE `tbl_classroom_schedule` (
  `schedule_id` int(11) NOT NULL,
  `croom_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_classroom_schedule`
--

INSERT INTO `tbl_classroom_schedule` (`schedule_id`, `croom_id`, `professor_id`, `student_id`, `schedule_time`) VALUES
(1, 2, 7, 5, '2025-02-22 00:31:00'),
(2, 2, 7, 5, '2025-02-04 00:39:00'),
(3, 2, 7, 5, '2025-02-06 02:41:00'),
(4, 6, 7, 9, '2025-02-25 00:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_crooms`
--

CREATE TABLE `tbl_crooms` (
  `croom_id` int(11) NOT NULL,
  `room_num` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `current_prof_id` int(11) DEFAULT NULL,
  `current_students` int(11) DEFAULT 0,
  `classroom_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_crooms`
--

INSERT INTO `tbl_crooms` (`croom_id`, `room_num`, `capacity`, `status_id`, `current_prof_id`, `current_students`, `classroom_name`) VALUES
(2, '', 0, 1, NULL, 0, 'Bautista_404'),
(6, '501', 50, 1, NULL, 0, 'Acad_501'),
(7, '601', 100, 1, NULL, 0, 'Belmonte_601');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_croom_occ`
--

CREATE TABLE `tbl_croom_occ` (
  `occ_id` int(11) NOT NULL,
  `croom_id` int(11) NOT NULL,
  `prof_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_croom_stats`
--

CREATE TABLE `tbl_croom_stats` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_croom_stats`
--

INSERT INTO `tbl_croom_stats` (`status_id`, `status_name`) VALUES
(1, 'Available'),
(2, 'Occupied');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_days`
--

CREATE TABLE `tbl_days` (
  `day_id` int(11) NOT NULL,
  `day_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_days`
--

INSERT INTO `tbl_days` (`day_id`, `day_name`) VALUES
(5, 'Friday'),
(1, 'Monday'),
(6, 'Saturday'),
(4, 'Thursday'),
(2, 'Tuesday'),
(3, 'Wednesday');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_occ_stats`
--

CREATE TABLE `tbl_occ_stats` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_occ_stats`
--

INSERT INTO `tbl_occ_stats` (`status_id`, `status_name`) VALUES
(1, 'Occupied'),
(2, 'Vacant');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_rfid`
--

CREATE TABLE `tbl_rfid` (
  `rfid_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rfid_tag` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_roles`
--

CREATE TABLE `tbl_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_roles`
--

INSERT INTO `tbl_roles` (`role_id`, `role_name`) VALUES
(1, 'Faculty'),
(2, 'Professor'),
(3, 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sched`
--

CREATE TABLE `tbl_sched` (
  `sched_id` int(11) NOT NULL,
  `prof_id` int(11) NOT NULL,
  `croom_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `day_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rfid_tag` varchar(20) DEFAULT NULL,
  `student_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `lastname`, `firstname`, `middle_initial`, `email`, `password`, `role_id`, `created_at`, `rfid_tag`, `student_id`) VALUES
(2, 'Detalla', 'Krish', 'O.', 'krishdetalla@ex.com', '', 1, '2025-02-21 16:08:17', '23-1847', '23-1847'),
(3, 'Lomigo', 'Axel', 'L', 'lomigo@example.com', '', 1, '2025-02-21 16:08:58', '23-1919', '0000'),
(4, 'HAHA', 'HATODG', 'C', 'hatdog@gmail.com', '', 1, '2025-02-21 16:10:36', '23-1900', '23-1111'),
(5, 'sasa', 'sasa', 's', 'sasa@sasa.com', '', 3, '2025-02-21 16:12:31', '45-19334', '123456'),
(7, 'Barral', 'Ferdinand', 'J', 'haha@hehe.com', '', 2, '2025-02-21 16:13:47', '913313', '00001'),
(9, 'Jumuad', 'Sam', 'C', 'sam@gmail.com', '', 3, '2025-02-21 16:49:08', '23-5656', '213131'),
(10, 'haha', 'hihi', NULL, 'hahahihi@hhihi.com', '$2y$10$A9Kx3rO0w1LopwoXTkYRt.DolS.eBgw7g4QIhesKQIoAcTN3HmXSa', 1, '2025-02-21 16:57:43', NULL, '23451');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `croom_id` (`croom_id`);

--
-- Indexes for table `tbl_classroom_schedule`
--
ALTER TABLE `tbl_classroom_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `croom_id` (`croom_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tbl_crooms`
--
ALTER TABLE `tbl_crooms`
  ADD PRIMARY KEY (`croom_id`),
  ADD UNIQUE KEY `room_num` (`room_num`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `tbl_croom_occ`
--
ALTER TABLE `tbl_croom_occ`
  ADD PRIMARY KEY (`occ_id`),
  ADD KEY `croom_id` (`croom_id`),
  ADD KEY `prof_id` (`prof_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `tbl_croom_stats`
--
ALTER TABLE `tbl_croom_stats`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `tbl_days`
--
ALTER TABLE `tbl_days`
  ADD PRIMARY KEY (`day_id`),
  ADD UNIQUE KEY `day_name` (`day_name`);

--
-- Indexes for table `tbl_occ_stats`
--
ALTER TABLE `tbl_occ_stats`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `tbl_rfid`
--
ALTER TABLE `tbl_rfid`
  ADD PRIMARY KEY (`rfid_id`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `tbl_sched`
--
ALTER TABLE `tbl_sched`
  ADD PRIMARY KEY (`sched_id`),
  ADD KEY `prof_id` (`prof_id`),
  ADD KEY `croom_id` (`croom_id`),
  ADD KEY `day_id` (`day_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_classroom_schedule`
--
ALTER TABLE `tbl_classroom_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_crooms`
--
ALTER TABLE `tbl_crooms`
  MODIFY `croom_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_croom_occ`
--
ALTER TABLE `tbl_croom_occ`
  MODIFY `occ_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_croom_stats`
--
ALTER TABLE `tbl_croom_stats`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_days`
--
ALTER TABLE `tbl_days`
  MODIFY `day_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_occ_stats`
--
ALTER TABLE `tbl_occ_stats`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_rfid`
--
ALTER TABLE `tbl_rfid`
  MODIFY `rfid_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_sched`
--
ALTER TABLE `tbl_sched`
  MODIFY `sched_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD CONSTRAINT `tbl_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_attendance_ibfk_2` FOREIGN KEY (`croom_id`) REFERENCES `tbl_crooms` (`croom_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_classroom_schedule`
--
ALTER TABLE `tbl_classroom_schedule`
  ADD CONSTRAINT `tbl_classroom_schedule_ibfk_1` FOREIGN KEY (`croom_id`) REFERENCES `tbl_crooms` (`croom_id`),
  ADD CONSTRAINT `tbl_classroom_schedule_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `tbl_users` (`user_id`),
  ADD CONSTRAINT `tbl_classroom_schedule_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `tbl_crooms`
--
ALTER TABLE `tbl_crooms`
  ADD CONSTRAINT `tbl_crooms_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `tbl_croom_stats` (`status_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_croom_occ`
--
ALTER TABLE `tbl_croom_occ`
  ADD CONSTRAINT `tbl_croom_occ_ibfk_1` FOREIGN KEY (`croom_id`) REFERENCES `tbl_crooms` (`croom_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_croom_occ_ibfk_2` FOREIGN KEY (`prof_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_croom_occ_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_croom_occ_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `tbl_occ_stats` (`status_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_rfid`
--
ALTER TABLE `tbl_rfid`
  ADD CONSTRAINT `tbl_rfid_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sched`
--
ALTER TABLE `tbl_sched`
  ADD CONSTRAINT `tbl_sched_ibfk_1` FOREIGN KEY (`prof_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_sched_ibfk_2` FOREIGN KEY (`croom_id`) REFERENCES `tbl_crooms` (`croom_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_sched_ibfk_3` FOREIGN KEY (`day_id`) REFERENCES `tbl_days` (`day_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD CONSTRAINT `tbl_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
