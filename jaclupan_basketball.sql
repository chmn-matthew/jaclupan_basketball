-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 09:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jaclupan_basketball`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `admin_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `name`) VALUES
(1, 'Under 12 Division'),
(2, 'Under 16 Division'),
(3, 'Under 20 Division'),
(4, 'Under 30 Division'),
(5, '31-39 Division'),
(6, '40UP Division');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `jersey_number` int(11) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `photo_path` varchar(255) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `birth_certificate_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `team_id`, `name`, `birthdate`, `address`, `jersey_number`, `position`, `photo_path`, `document_path`, `birth_certificate_path`, `created_at`) VALUES
(1, 1, 'Matthew Neil Bonghanoy Cabrillos', '2002-08-18', 'Jaclupan, Talisay City, Cebu', 1, NULL, 'uploads/photos/player_68cbae452ac864.55449113.jpg', NULL, NULL, '2025-09-18 07:01:25'),
(3, 1, 'mat', '3242-12-13', 'jaclupan', 3, NULL, 'uploads/photos/player_68cbaef228a167.49210181.jpg', 'uploads/documents/doc_68cbaef228d107.09550145.jpg', NULL, '2025-09-18 07:04:18'),
(5, 1, 'ginolos', '0000-00-00', 'Jaclupan, Talisay City, Cebu', 2, NULL, 'uploads/photos/player_68cbafdc282eb5.21345130.jpg', 'uploads/documents/doc_68cbafdc286a36.71648187.jpg', NULL, '2025-09-18 07:08:12'),
(6, 1, 'ginolos', '1223-03-12', 'Jaclupan, Talisay City, Cebu', 4, NULL, 'uploads/photos/player_68cbafe94bfcf2.19703657.jpg', 'uploads/documents/doc_68cbafe94c2819.86455491.jpg', NULL, '2025-09-18 07:08:25'),
(7, 1, 'ginolos', '0000-00-00', 'Jaclupan, Talisay City, Cebu', 5, NULL, 'uploads/photos/player_68cbaff972b629.93537929.png', 'uploads/documents/doc_68cbaff9730007.08024613.jpg', NULL, '2025-09-18 07:08:41'),
(8, 1, 'ginolos', '0000-00-00', 'Jaclupan, Talisay City, Cebu', 6, NULL, 'uploads/photos/player_68cbb01324c140.80172212.jpg', 'uploads/documents/doc_68cbb01324f211.25994402.jpg', 'uploads/documents/birth_68cbb013253692.31015182.jpg', '2025-09-18 07:09:07'),
(9, 1, 'ginolos', '0000-00-00', 'Jaclupan, Talisay City, Cebu', 7, NULL, 'uploads/photos/player_68cbb025f39448.45005498.jpg', 'uploads/documents/doc_68cbb025f3d440.20924648.jpg', 'uploads/documents/birth_68cbb025f40de0.20587074.jpg', '2025-09-18 07:09:26'),
(10, 1, 'Matthew Neil Bonghanoy Cabrillos', '0000-00-00', 'Jaclupan, Talisay City, Cebu', 8, NULL, 'uploads/photos/player_68cbb0460b2da3.95257443.jpg', 'uploads/documents/doc_68cbb0460b62a7.31720434.jpg', NULL, '2025-09-18 07:09:58'),
(11, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 9, NULL, 'uploads/photos/player_68cbb057d58ab2.80043287.jpg', 'uploads/documents/doc_68cbb057d5c169.17041448.jpg', NULL, '2025-09-18 07:10:15'),
(12, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 10, NULL, 'uploads/photos/player_68cbb08710aa63.15839935.jpg', 'uploads/documents/doc_68cbb08710e5a2.61638879.jpg', NULL, '2025-09-18 07:11:03'),
(13, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 11, NULL, 'uploads/photos/player_68cbb0943ea271.09462813.jpg', 'uploads/documents/doc_68cbb0943ed4b2.16804891.jpg', NULL, '2025-09-18 07:11:16'),
(14, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 12, NULL, 'uploads/photos/player_68cbb0a62b3ac6.03878485.jpg', 'uploads/documents/doc_68cbb0a62b6854.19094543.jpg', NULL, '2025-09-18 07:11:34'),
(15, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 13, NULL, 'uploads/photos/player_68cbb0b570d8f8.88866114.jpg', 'uploads/documents/doc_68cbb0b5711303.86833149.jpg', NULL, '2025-09-18 07:11:49'),
(16, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 14, NULL, 'uploads/photos/player_68cbb0c362e2d3.02104785.jpg', 'uploads/documents/doc_68cbb0c3631595.32816998.jpg', NULL, '2025-09-18 07:12:03'),
(17, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 15, NULL, 'uploads/photos/player_68cbb0ceae1296.64865927.jpg', 'uploads/documents/doc_68cbb0ceae3e14.29649080.jpg', NULL, '2025-09-18 07:12:14'),
(18, 1, 'Matthew Neil Bonghanoy Cabrillos', '1200-02-18', 'Jaclupan, Talisay City, Cebu', 16, NULL, 'uploads/photos/player_68cbb0daadaad8.69512633.jpg', 'uploads/documents/doc_68cbb0daaded35.57818476.jpg', NULL, '2025-09-18 07:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `coach_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `division` varchar(50) NOT NULL,
  `players` text NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `players_registered` tinyint(1) NOT NULL DEFAULT 0,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `team_name`, `coach_name`, `contact_number`, `email`, `division`, `players`, `logo_path`, `players_registered`, `registration_date`, `username`, `password`) VALUES
(1, 'Gerald Flores', 'Vernie Obenza', '09602328527', 'matthewcabrillos110@gmail.com', 'UNDER_30', '', NULL, 1, '2025-09-18 06:36:11', 'gerald', '$2y$10$CCepUzQN80F2j9wWsc/3reTKiiZ7edmq3AYq21M3Iy4RZuwLkUUJ2');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `user_type`, `created_at`) VALUES
(1, 'mat', '$2y$10$hkz0kkt/srN5vOP4QqHUl.KSGY7bmgj4j5wHvKovG5q5FZPIxSMf6', '1@gmail.com', 'Matthew', '09123', 'user', '2025-09-17 15:18:24'),
(2, '123', '$2y$10$jD9Etk7Cm96bcCgPudvUb.RPw5.BYQBKcON7.mukf7s.FFNJWGBHe', '123@gmail.com', 'Matthew Neil Bonghanoy Cabrillos', '0123', 'user', '2025-09-18 05:55:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_jersey` (`team_id`,`jersey_number`),
  ADD KEY `idx_players_team_id` (`team_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `fk_players_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
