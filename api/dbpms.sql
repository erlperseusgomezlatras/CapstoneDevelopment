-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 18, 2026 at 02:02 PM
-- Server version: 10.4.17-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbpms`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowed_email_domains`
--

CREATE TABLE `allowed_email_domains` (
  `id` int(11) NOT NULL,
  `domain_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `allowed_email_domains`
--

INSERT INTO `allowed_email_domains` (`id`, `domain_name`, `description`) VALUES
(1, '@phinmaed.com', 'Official Phinma email format');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `assigner_id` varchar(50) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `isCurrent` tinyint(1) DEFAULT 1,
  `date_started` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_timeIn` time NOT NULL,
  `attendance_timeOut` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `attendance_timeIn`, `attendance_timeOut`) VALUES
(1, 'STU-2026-5287', '2026-01-18', '14:48:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `journal`
--

CREATE TABLE `journal` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `week` varchar(20) NOT NULL,
  `grateful` text NOT NULL,
  `proud_of` text NOT NULL,
  `look_forward` text NOT NULL,
  `felt_this_week` enum('Good','Lean toward Good','Middle/Neutral','Lean toward Not Good','Not Good') NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `level_permissions`
--

CREATE TABLE `level_permissions` (
  `id` int(11) NOT NULL,
  `level_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_read` tinyint(1) DEFAULT 0,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `route_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `partnered_schools`
--

CREATE TABLE `partnered_schools` (
  `id` int(11) NOT NULL,
  `school_id_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geofencing_radius` int(11) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `partnered_schools`
--

INSERT INTO `partnered_schools` (`id`, `school_id_code`, `name`, `address`, `latitude`, `longitude`, `geofencing_radius`, `isActive`) VALUES
(1, '2222', 'Misamis Oriental General Comprehensive High School', 'Ramon Chavez Street, Barangay 33, Poblacion, Cagayan de Oro, Northern Mindanao, 9000, Philippines', '8.48057699', '124.64954352', 80, 1);

-- --------------------------------------------------------

--
-- Table structure for table `practicum_checklist`
--

CREATE TABLE `practicum_checklist` (
  `id` int(11) NOT NULL,
  `practicum_id` int(11) NOT NULL,
  `checklist_activities` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `practicum_subjects`
--

CREATE TABLE `practicum_subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `subject_rendered` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_name`, `school_id`) VALUES
(1, 'COC-EDUC-1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `school_id` varchar(50) NOT NULL,
  `level_id` int(11) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `isApproved` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`school_id`, `level_id`, `firstname`, `lastname`, `middlename`, `title`, `email`, `section_id`, `isActive`, `password`, `isApproved`) VALUES
('00000', 3, 'tests', 'test', '', NULL, 'test@phinmaed.com', NULL, 1, '$2y$10$cevVZ2IPseOYik44S1UDxuRGnOZgOuokrS8ecXQvNIUtFnByyjd3G', NULL),
('02-1819-1509', 2, 'earl', 'latras', NULL, NULL, 'earl@phinmaed.com', NULL, 1, '$2y$10$nTdaVq4sO0lkNW1Y0VvEru.FUL1o8IWW5ymwRtskbNUsYInzt53ei', NULL),
('STU-2026-2449', 4, 'Kevin', 'sht', '', NULL, 'kevin@phinmaed.com', NULL, 1, '$2y$10$aBu95YRxRYJCbPchVeDHn.BJkHX4MpaqKuPihLQSAy8w.FrxcxZWq', 0),
('STU-2026-5287', 4, 'example', 'example', '', NULL, 'example@phinmaed.com', 1, 1, '$2y$10$A7OJ0Qu/xl3NmErKVE.6QevpL8HJ.Lvr69oo85zb9tH9opefO/EWe', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_levels`
--

CREATE TABLE `user_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_levels`
--

INSERT INTO `user_levels` (`id`, `name`) VALUES
(1, 'Dean'),
(2, 'Head Teacher'),
(3, 'Coordinator'),
(4, 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `words_affirmation`
--

CREATE TABLE `words_affirmation` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `affirmation_word` text NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `words_inspire`
--

CREATE TABLE `words_inspire` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `inspire_words` text NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowed_email_domains`
--
ALTER TABLE `allowed_email_domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain_name` (`domain_name`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigner_id` (`assigner_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attendance_student` (`student_id`);

--
-- Indexes for table `journal`
--
ALTER TABLE `journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_journal_student` (`student_id`);

--
-- Indexes for table `level_permissions`
--
ALTER TABLE `level_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partnered_schools`
--
ALTER TABLE `partnered_schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `practicum_checklist`
--
ALTER TABLE `practicum_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `practicum_id` (`practicum_id`);

--
-- Indexes for table `practicum_subjects`
--
ALTER TABLE `practicum_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `words_affirmation`
--
ALTER TABLE `words_affirmation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_affirmation_journal` (`journal_id`);

--
-- Indexes for table `words_inspire`
--
ALTER TABLE `words_inspire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inspire_journal` (`journal_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowed_email_domains`
--
ALTER TABLE `allowed_email_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `journal`
--
ALTER TABLE `journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_permissions`
--
ALTER TABLE `level_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partnered_schools`
--
ALTER TABLE `partnered_schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `practicum_checklist`
--
ALTER TABLE `practicum_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `practicum_subjects`
--
ALTER TABLE `practicum_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_levels`
--
ALTER TABLE `user_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `words_affirmation`
--
ALTER TABLE `words_affirmation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `words_inspire`
--
ALTER TABLE `words_inspire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`assigner_id`) REFERENCES `users` (`school_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`school_id`),
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `partnered_schools` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`school_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `journal`
--
ALTER TABLE `journal`
  ADD CONSTRAINT `fk_journal_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`school_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `level_permissions`
--
ALTER TABLE `level_permissions`
  ADD CONSTRAINT `level_permissions_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `user_levels` (`id`),
  ADD CONSTRAINT `level_permissions_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `practicum_checklist`
--
ALTER TABLE `practicum_checklist`
  ADD CONSTRAINT `practicum_checklist_ibfk_1` FOREIGN KEY (`practicum_id`) REFERENCES `practicum_subjects` (`id`);

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `partnered_schools` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `user_levels` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`);

--
-- Constraints for table `words_affirmation`
--
ALTER TABLE `words_affirmation`
  ADD CONSTRAINT `fk_affirmation_journal` FOREIGN KEY (`journal_id`) REFERENCES `journal` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `words_inspire`
--
ALTER TABLE `words_inspire`
  ADD CONSTRAINT `fk_inspire_journal` FOREIGN KEY (`journal_id`) REFERENCES `journal` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
