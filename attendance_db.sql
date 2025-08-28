-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 07:19 AM
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
  `guardian_photo` varchar(255) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`student_id`, `date`, `status`, `guardian_photo`, `guardian_relation`) VALUES
(18, '2025-08-02', 'Absent', 'uploads/1754108404_1.png', 'image'),
(19, '2025-08-02', 'Present', 'uploads/1754108404_future.png', 'hr'),
(20, '2025-08-02', 'Present', 'uploads/1754108404_2.png', 're'),
(21, '2025-08-02', 'Present', 'uploads/1754108404_3.png', 'gr'),
(22, '2025-08-02', 'Present', 'uploads/1754108404_1.png', 'gr'),
(23, '2025-08-02', 'Present', 'uploads/1754108404_2.png', 'tth'),
(24, '2025-08-02', 'Present', 'uploads/1754108404_tn happykids logo.jfif', 're'),
(25, '2025-08-02', 'Present', 'uploads/1754108404_tn happykids logo.jfif', 'reg'),
(26, '2025-08-02', 'Present', 'uploads/1754108404_1.png', 'regq'),
(27, '2025-08-02', 'Present', 'uploads/1754108404_2.png', 'regq'),
(28, '2025-08-02', 'Present', 'uploads/1754108404_1.png', 'regq'),
(29, '2025-08-02', 'Present', 'uploads/1754108404_3.png', 'reg'),
(30, '2025-08-02', 'Present', 'uploads/1754108404_WhatsApp Image 2025-06-25 at 12.52.54 PM.jpeg', 'reg');

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
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `age`, `branch_id`) VALUES
(18, 'Aarush', 3, 1),
(19, 'Abijith', 3, 1),
(20, 'Aarna', 3, 1),
(21, 'Krithik', 3, 1),
(22, 'Janvimadhusri', 3, 1),
(23, 'Shivaanusha', 3, 1),
(24, 'Sowndharanayagi', 3, 1),
(25, 'Shivanika', 3, 1),
(26, 'Vedha', 3, 1),
(27, 'Vishrutha', 3, 1),
(28, 'Devaathithya', 3, 1),
(29, 'Kavin', 3, 1),
(30, 'Vishagan', 3, 1);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
