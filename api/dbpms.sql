-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 21, 2026 at 06:02 AM
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
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `academic_session_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_Active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`academic_session_id`, `school_year_id`, `semester_id`, `created_at`, `updated_at`, `is_Active`) VALUES
(1, 1, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(2, 1, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(3, 1, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(4, 2, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(5, 2, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(6, 2, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(7, 3, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(8, 3, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(9, 3, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(10, 4, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(11, 4, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(12, 4, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(13, 5, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(14, 5, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(15, 5, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(16, 6, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(17, 6, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(18, 6, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(19, 7, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(20, 7, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(21, 7, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(22, 8, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(23, 8, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(24, 8, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(25, 9, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(26, 9, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(27, 9, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(28, 10, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(29, 10, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(30, 10, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(31, 11, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(32, 11, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(33, 11, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(34, 12, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(35, 12, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(36, 12, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(37, 13, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(38, 13, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(39, 13, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(40, 14, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(41, 14, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(42, 14, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(43, 15, 1, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(44, 15, 2, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0),
(45, 15, 3, '2025-10-18 08:04:50', '2025-10-18 08:04:50', 0);

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
  `attendance_timeOut` time DEFAULT NULL,
  `session_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
(1, '2222', 'Misamis Oriental General Comprehensive High School', 'Misamis Oriental General Comprehensive High School, Don Apolinar Velez Street, Barangay 16, Poblacion, Cagayan de Oro, Northern Mindanao, 9000, Philippines', '8.48051027', '124.64937087', 80, 1);

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
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `school_year_id` int(11) NOT NULL,
  `school_year` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`school_year_id`, `school_year`, `created_at`, `updated_at`) VALUES
(1, 'SY 25-26', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(2, 'SY 26-27', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(3, 'SY 27-28', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(4, 'SY 28-29', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(5, 'SY 29-30', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(6, 'SY 30-31', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(7, 'SY 31-32', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(8, 'SY 32-33', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(9, 'SY 33-34', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(10, 'SY 34-35', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(11, 'SY 35-36', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(12, 'SY 36-37', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(13, 'SY 37-38', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(14, 'SY 38-39', '2025-10-18 08:03:38', '2025-10-18 08:03:38'),
(15, 'SY 39-40', '2025-10-18 08:03:38', '2025-10-18 08:03:38');

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
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `created_at`, `updated_at`) VALUES
(1, '1st Semester', '2025-10-18 08:04:05', '2025-10-18 08:04:05'),
(2, '2nd Semester', '2025-10-18 08:04:05', '2025-10-18 08:04:05'),
(3, 'Summer', '2025-10-18 08:04:05', '2025-10-18 08:04:05');

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
('00000', 3, 'tests', 'test', '', NULL, 'test@phinmaed.com', 1, 1, '$2y$10$cevVZ2IPseOYik44S1UDxuRGnOZgOuokrS8ecXQvNIUtFnByyjd3G', NULL),
('11111', 2, 'earl', 'latras', '', NULL, 'earl@phinmaed.com', NULL, 1, '$2y$10$wkjwZfWjHHTUG0YQJ8kRiOTTMh5nV2g7Ms7FiDYbEb1gDuWjuse6m', NULL),
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
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`academic_session_id`),
  ADD UNIQUE KEY `uq_session` (`school_year_id`,`semester_id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `semester_id` (`semester_id`);

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
  ADD KEY `fk_attendance_student` (`student_id`),
  ADD KEY `fk_attendance_session` (`session_id`);

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
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`school_year_id`),
  ADD UNIQUE KEY `school_year` (`school_year`),
  ADD KEY `school_year_2` (`school_year`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD UNIQUE KEY `semester_name` (`semester_name`),
  ADD KEY `semester_name_2` (`semester_name`);

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
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `academic_session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `school_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD CONSTRAINT `fk_session_school_year` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`school_year_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_session_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_attendance_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`academic_session_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
