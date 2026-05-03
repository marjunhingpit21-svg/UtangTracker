-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2026 at 03:40 PM
-- Server version: 8.0.42
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `listahan`
--

-- --------------------------------------------------------

--
-- Table structure for table `debt_list`
--

CREATE TABLE `debt_list` (
  `list_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `creditor` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `list_content`
--

CREATE TABLE `list_content` (
  `content_id` int NOT NULL,
  `list_id` int DEFAULT NULL,
  `content_name` varchar(255) DEFAULT NULL,
  `money_owed` double DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `debt_status` enum('paid','unpaid') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `debt_list`
--
ALTER TABLE `debt_list`
  ADD PRIMARY KEY (`list_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `list_content`
--
ALTER TABLE `list_content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `list_id` (`list_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `debt_list`
--
ALTER TABLE `debt_list`
  MODIFY `list_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `list_content`
--
ALTER TABLE `list_content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `debt_list`
--
ALTER TABLE `debt_list`
  ADD CONSTRAINT `debt_list_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `list_content`
--
ALTER TABLE `list_content`
  ADD CONSTRAINT `list_content_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `debt_list` (`list_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
