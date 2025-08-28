-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Aug 28, 2025 at 06:23 AM
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
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(10) DEFAULT NULL,
  `guardian_in_photo` varchar(255) DEFAULT NULL,
  `guardian_in_relation` varchar(50) DEFAULT NULL,
  `guardian_out_photo` varchar(255) DEFAULT NULL,
  `guardian_out_relation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`student_id`, `date`, `status`, `guardian_in_photo`, `guardian_in_relation`, `guardian_out_photo`, `guardian_out_relation`) VALUES
(48, '2025-08-20', 'Present', 'uploads/student_48/1755511404_out_istockphoto-1446885495-612x612.jpg', 'mother', 'uploads/student_48/1755511404_in_ee0748404599f8d781cc857bcc2eb9a3.jpg', 'father'),
(48, '2025-08-26', 'Present', 'uploads/student_48/1755511404_out_istockphoto-1446885495-612x612.jpg', 'mother', 'uploads/student_48/1755511404_in_ee0748404599f8d781cc857bcc2eb9a3.jpg', 'father'),
(56, '2025-08-21', 'Present', 'uploads/student_56/1755754751_in_istockphoto-1446885495-612x612.jpg', 'mother', 'uploads/student_56/1755754783_out_IMG_1381.HEIC', 'father');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`) VALUES
(4, 'chithode'),
(2, 'coimbatore'),
(5, 'kolathur'),
(1, 'pollachi'),
(6, 'tambaram'),
(3, 'tirupur');

-- --------------------------------------------------------

--
-- Table structure for table `guardian_photos`
--

CREATE TABLE `guardian_photos` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `relation` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guardian_photos`
--

INSERT INTO `guardian_photos` (`id`, `student_id`, `photo_path`, `relation`) VALUES
(26, 48, 'uploads/student_48/1755338457_mother 2.jpeg', 'mother'),
(27, 49, 'uploads/student_49/1755338516_father 2.jpeg', 'father in law'),
(28, 50, 'uploads/student_50/1755342086_in_mother 2.jpeg', 'mother'),
(29, 50, 'uploads/student_50/1755342552_in_father 2.jpeg', 'father'),
(30, 51, 'uploads/student_51/1755342905_in_father 2.jpeg', 'father'),
(31, 51, 'uploads/student_51/1755342938_in_mother.jpeg', 'mother'),
(32, 52, 'uploads/student_52/1755344056_in_father 2.jpeg', 'father'),
(33, 53, 'uploads/student_53/1755498759_in_ee0748404599f8d781cc857bcc2eb9a3.jpg', 'father'),
(34, 53, 'uploads/student_53/1755498777_out_istockphoto-1446885495-612x612.jpg', 'mother'),
(35, 48, 'uploads/student_48/1755511404_in_ee0748404599f8d781cc857bcc2eb9a3.jpg', 'father'),
(36, 48, 'uploads/student_48/1755511404_out_istockphoto-1446885495-612x612.jpg', 'mother'),
(37, 54, 'uploads/student_54/1755587576_in_parent.jpg', 'father'),
(38, 55, 'uploads/student_55/1755589880_in_parent.jpg', 'father'),
(39, 55, 'uploads/student_55/1755589913_in_bg1.jpg', 'mother'),
(40, 56, 'uploads/student_56/1755754751_in_istockphoto-1446885495-612x612.jpg', 'mother'),
(41, 56, 'uploads/student_56/1755754783_out_IMG_1381.HEIC', 'father');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `parent_username` varchar(50) NOT NULL,
  `leave_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `student_id`, `parent_username`, `leave_date`, `reason`, `status`, `applied_at`) VALUES
(6, 48, 'po1', '2025-08-29', 'fever', 'Approved', '2025-08-20 10:44:44'),
(7, 48, 'po1', '2025-08-23', 'fever', 'Approved', '2025-08-21 05:41:51'),
(8, 48, 'po1', '2025-08-30', 'cold', 'Approved', '2025-08-27 06:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `student_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `age`, `branch_id`, `approved`) VALUES
(42, 'Rashwant', 4, 5, 0),
(43, 'Ivaan', 4, 5, 0),
(44, 'Lokeshwaran', 2, 5, 0),
(45, 'Lokita', 2, 5, 0),
(46, 'Dhanvanth', 2, 5, 0),
(48, 'Savunthara Nayaki', 2, 1, 1),
(49, 'Abijith', 3, 1, 0),
(50, 'Shivanusha', 3, 1, 0),
(51, 'Shivanika', 4, 1, 1),
(52, 'Vedha', 2, 1, 1),
(53, 'Vedhika', 2, 1, 1),
(54, 'Vishagan', 2, 1, 0),
(55, 'Vishrudha', 2, 1, 0),
(56, 'Aarush', 2, 1, 0),
(57, 'Aarna', 2, 1, 0),
(58, 'Dev Adithya', 2, 1, 0),
(59, 'Kavin', 2, 1, 0),
(60, 'Krithick', 2, 1, 0),
(61, 'Janvi Madhusri', 2, 1, 0),
(62, 'Rakshan', 2, 5, 0),
(63, 'Sirpikaa ', 3, 4, 0),
(64, 'Nithiran ', 3, 4, 0),
(65, 'Ithal', 2, 4, 0),
(66, 'Vihaan ', 3, 4, 0),
(67, 'Bagu Mithran', 2, 4, 0),
(68, 'Aayath', 2, 3, 0),
(70, 'Sanaj. S', 4, 3, 0),
(71, 'Kirthivik dev', 2, 3, 0),
(72, 'Sai pragiya', 2, 1, 0),
(73, 'Lakshitha', 2, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `substitute_guardians`
--

CREATE TABLE `substitute_guardians` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relation` varchar(50) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `substitute_guardians`
--

INSERT INTO `substitute_guardians` (`id`, `student_id`, `name`, `relation`, `photo_path`, `from_date`, `to_date`, `status`, `created_at`) VALUES
(2, 48, 'aathish', 'relative', 'uploads/substitutes/sub_68a7f7af9546d_ee0748404599f8d781cc857bcc2eb9a3.jpg', '2025-08-23', '2025-08-23', 'Rejected', '2025-08-22 10:23:03'),
(3, 48, 'abhilash', 'uncle', 'uploads/substitutes/sub_68a7fb0e91cf8_ee0748404599f8d781cc857bcc2eb9a3.jpg', '2025-08-24', '2025-08-24', 'Approved', '2025-08-22 10:37:26'),
(4, 48, 'mohan', 'uncle', 'uploads/substitutes/sub_68aea43652f50_WhatsApp Image 2025-08-14 at 3.16.09 PM (3).jpeg', '2025-08-28', '2025-08-28', 'Rejected', '2025-08-27 11:52:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`student_id`,`date`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_name` (`branch_name`);

--
-- Indexes for table `guardian_photos`
--
ALTER TABLE `guardian_photos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`photo_path`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `substitute_guardians`
--
ALTER TABLE `substitute_guardians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `guardian_photos`
--
ALTER TABLE `guardian_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `substitute_guardians`
--
ALTER TABLE `substitute_guardians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `parents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `substitute_guardians`
--
ALTER TABLE `substitute_guardians`
  ADD CONSTRAINT `substitute_guardians_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
