-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 22, 2025 at 11:12 AM
-- Server version: 10.6.23-MariaDB-cll-lve
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bulletin_board`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `created_by` int(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `announcement_date` date DEFAULT NULL,
  `announcement_time` time DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `created_at`, `announcement_date`, `announcement_time`, `is_archived`) VALUES
(102, 'TESTING NG DRAG AND DROP', 'HELLO OPOOPOPPOOP', 1, '2025-10-20 09:15:57', '2025-10-22', '09:00:00', 1),
(103, 'TTHIS IS AN OFFICER', 'ASDFGHJKLDSFSDGFFG', 18, '2025-10-21 06:31:45', '2025-10-22', '09:00:00', 1),
(104, 'THIS IS AN ANNOUNCMTNEN BY OFFICER', 'ASDFGHJKLSSADAFDGASDASD', 18, '2025-10-21 06:36:01', '2025-10-24', '00:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_media`
--

CREATE TABLE `announcement_media` (
  `id` bigint(20) NOT NULL,
  `announcement_id` int(20) NOT NULL,
  `media_id` int(20) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_media`
--

INSERT INTO `announcement_media` (`id`, `announcement_id`, `media_id`, `display_order`, `created_at`) VALUES
(191, 102, 330, 1, '2025-10-20 16:15:57'),
(192, 102, 331, 0, '2025-10-20 16:15:57'),
(193, 102, 332, 2, '2025-10-20 16:15:57'),
(194, 103, 333, 1, '2025-10-21 13:31:45'),
(195, 104, 334, 1, '2025-10-21 13:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `content_verification`
--

CREATE TABLE `content_verification` (
  `id` int(11) NOT NULL,
  `content_type` enum('announcement','event','faculty','officer') NOT NULL,
  `content_id` int(11) NOT NULL,
  `action_type` enum('create','update','delete') NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  `duration` int(11) DEFAULT 1,
  `time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `created_by` int(255) NOT NULL,
  `media_id` int(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `date`, `duration`, `time`, `location`, `created_by`, `media_id`, `is_archived`, `created_at`, `updated_at`) VALUES
(27, 'General Assembly', '📣 𝗔𝘁𝘁𝗲𝗻𝘁𝗶𝗼𝗻, 𝗧𝗲𝗰𝗵𝗻𝗼𝗰𝗿𝗮𝘁𝘀!\r\n\r\nThe College of Computer Studies invites all 𝘾𝘾𝙎 𝙨𝙩𝙪𝙙𝙚𝙣𝙩𝙨 to join our 𝐆𝐞𝐧𝐞𝐫𝐚𝐥 𝐀𝐬𝐬𝐞𝐦𝐛𝐥𝐲 this coming 𝗦𝗲𝗽𝘁𝗲𝗺𝗯𝗲𝗿 𝟮, 𝟮𝟬𝟮𝟱 at the 𝗦𝗣𝗖𝗕 𝗔𝗩𝗥! 💻✨\r\n\r\n🕗 Schedule:\r\n𝟭𝘀𝘁 𝗬𝗲𝗮𝗿 & 𝟯𝗿𝗱 𝗬𝗲𝗮𝗿 — 8:00 AM to 12:00 PM\r\n𝟮𝗻𝗱 𝗬𝗲𝗮𝗿 & 𝟰𝘁𝗵 𝗬𝗲𝗮𝗿 — 1:00 PM to 5:00 PM\r\n\r\n📍 Venue: 𝗦𝗣𝗖𝗕 𝗔𝗩𝗥\r\n👕 Attire: 𝗦𝗰𝗵𝗼𝗼𝗹 𝗨𝗻𝗶𝗳𝗼𝗿𝗺\r\n\r\nLet’s unite, get informed, and kick off the school year together! 🚀\r\n\r\n#CCSGeneralAssembly2025 #OLFUAC #FCMS', '2025-09-30', 1, '07:00:00', 'SPCB AVR', 1, 323, 0, '2025-09-19 02:09:10', '2025-09-30 04:11:06'),
(47, 'Computing Convention', 'This is for the Computing Convention', '2025-10-03', 1, '11:59:00', 'BAGUIO', 1, NULL, 0, '2025-09-30 03:56:07', '2025-09-30 04:11:55'),
(48, 'TESTING', 'TESTTTTT', '2025-10-23', 1, '00:08:00', 'SPCB', 1, 329, 0, '2025-10-20 16:05:41', '2025-10-20 16:05:41');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(255) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) NOT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `position` text NOT NULL,
  `description` text DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `media_id` int(255) DEFAULT NULL,
  `user_id` int(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT NULL,
  `full_name` varchar(500) GENERATED ALWAYS AS (concat_ws(' ',nullif(`prefix`,''),`fname`,nullif(`mname`,''),`lname`,case when `suffix` is not null and `suffix` <> '' then concat(', ',`suffix`) else '' end)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `prefix`, `fname`, `mname`, `lname`, `suffix`, `name`, `position`, `description`, `specialization`, `media_id`, `user_id`, `created_at`, `type`) VALUES
(4, 'Engr.', 'June', 'D.', 'Francisco', 'Jr., MIT', 'Engr. June D. Francisco Jr., MIT', 'CCS Faculty, Canvas Coordinator', NULL, 'IOT', 24, 19, '2025-04-11 08:02:09', 'faculty'),
(6, NULL, 'Jessie', 'P.', 'Benocas', 'MSIT', 'Jessie P. Benocas, MSIT', 'CCS Faculty', NULL, NULL, 34, 20, '2025-05-06 06:27:45', 'faculty'),
(7, NULL, 'Benjamin', 'B.', 'Gandeza', 'Jr., MOS, MIE, MCP, MIT', 'Benjamin B. Gandeza, Jr., MOS, MIE, MCP, MIT', 'CCS Program Head, Alumni Coordinator, FCMS Adviser', NULL, 'Python, Administration', 32, 21, '2025-05-07 17:19:14', 'faculty'),
(8, NULL, 'Arjay', 'B.', 'Cristobal', 'MIT(c)', 'Arjay B. Cristobal, MIT(c)', 'CCS Faculty', NULL, NULL, 30, 22, '2025-05-07 17:21:25', 'faculty'),
(9, NULL, 'Wilfredo', 'O.', 'Tomas', 'MSIT', 'Wilfredo O. Tomas, MSIT', 'CCS Faculty', NULL, 'COMPTIA, COMPTIA+', 36, 23, '2025-05-07 18:33:12', 'faculty'),
(10, NULL, 'Dianalyn', 'D.', 'Pagsanjan', 'MIT(c)', 'Dianalyn D. Pagsanjan, MIT(c)', 'SHS Faculty', NULL, 'Senior High School', 38, 24, '2025-05-07 18:52:08', 'faculty'),
(11, NULL, 'Arvin', 'Jonathan M.', 'Retuya', NULL, 'Arvin Jonathan M. Retuya', 'CCS Faculty', NULL, NULL, 39, 25, '2025-05-07 18:52:56', 'faculty'),
(12, NULL, 'Edward', 'N.', 'Cruz', 'MIT', 'Edward N. Cruz, MIT', 'CCS Faculty', NULL, 'Computer Science', 40, 26, '2025-05-07 18:54:46', 'faculty'),
(13, NULL, 'Michael', 'Angelo F.', 'Manalo', 'MIT', 'Michael Angelo F. Manalo, MIT', 'CCS Faculty', NULL, 'Cybersecurity', 41, 27, '2025-05-07 18:55:47', 'faculty'),
(15, 'Prof.', 'Raymond', 'S.', 'Macatangga', 'DIT, DBA', 'Prof. Raymond S. Macatangga, DIT, DBA', 'College Dean', 'He is the Dean of the College of Computer Studies', 'Administration', 75, 28, '2025-05-27 15:28:55', 'faculty'),
(16, 'Engr.', 'Jefferson', 'M.', 'Malayao', 'MIT (c)', 'Engr. Jefferson M. Malayao, MIT (c)', 'SHS Faculty', NULL, 'Senior High School', 76, 29, '2025-05-27 15:36:35', 'faculty'),
(17, NULL, 'Alexis', 'A.', 'Libunao', 'MSIT (c)', 'Alexis A. Libunao, MSIT (c)', 'CCS Faculty', NULL, NULL, 77, 30, '2025-05-27 15:37:58', 'faculty'),
(18, 'Engr.', 'Alneslyn', 'C.', 'Bucud', 'LPT, MIT (c)', 'Engr. Alneslyn C. Bucud, LPT, MIT (c)', 'CCS Faculty, Practicum Coordinator', NULL, 'Mathematics', 78, 31, '2025-05-27 15:40:50', 'faculty'),
(19, NULL, 'Marissa', 'M.', 'Bautista', 'MSIT (c)', 'Marissa M. Bautista, MSIT (c)', 'CCS Faculty, SHS Faculty', NULL, NULL, 79, 32, '2025-05-27 15:42:27', 'faculty'),
(20, 'Engr.', 'Pinky', 'G.', 'Dacuso', 'MAT, MIT (c)', 'Engr. Pinky G. Dacuso, MAT, MIT (c)', 'CCS Faculty, SHS Faculty, SOCI Coordinator', NULL, 'Information Technology', 80, 33, '2025-05-27 15:43:22', 'faculty'),
(21, NULL, 'Reynold', 'A.', 'Delizo', 'MSIT', 'Reynold A. Delizo, MSIT', 'CCS Faculty, Research Coordinator', NULL, 'Research', 81, 34, '2025-05-27 15:44:06', 'faculty'),
(22, NULL, 'Maria', 'Luzzel', 'Isok', NULL, 'Maria Luzzel Isok', 'College Secretary', NULL, NULL, 82, 35, '2025-05-27 15:45:01', 'faculty'),
(40, 'Engr.', 'Sean John', NULL, 'Duque', 'MSIT, MIT', 'Engr. Sean John Duque, MSIT, MIT', 'CCS Faculty, SHS Faculty', 'THIS IS A TEST FACULTY ACCOUNT IF THERE IS A CREATION IN ACCOUNT', 'Mathematics', NULL, 50, '2025-10-22 15:09:33', 'faculty');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `created_by` int(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `created_by`, `action`, `created_at`) VALUES
(1, 1, 'login', '2025-03-26 22:17:45'),
(2, 1, 'login', '2025-04-08 15:11:28'),
(3, 1, 'login', '2025-04-10 15:19:24'),
(4, 1, 'login', '2025-04-11 06:08:24'),
(5, 1, 'login', '2025-04-11 06:09:25'),
(6, 1, 'login', '2025-04-11 06:09:52'),
(7, 1, 'login', '2025-04-11 06:11:26'),
(8, 1, 'login', '2025-04-11 07:08:10'),
(9, 1, 'login', '2025-04-11 09:20:13'),
(10, 1, 'login', '2025-04-11 09:24:04'),
(11, 1, 'login', '2025-04-11 09:33:25'),
(12, 1, 'login', '2025-04-11 09:33:52'),
(16, 1, 'login', '2025-05-02 06:15:44'),
(17, 1, 'login', '2025-05-02 06:56:00'),
(18, 1, 'login', '2025-05-03 01:42:43'),
(19, 1, 'login', '2025-05-05 05:36:43'),
(20, 1, 'login', '2025-05-05 12:05:07'),
(21, 1, 'login', '2025-05-06 05:41:24'),
(22, 1, 'login', '2025-05-06 06:36:48'),
(23, 1, 'login', '2025-05-06 06:47:06'),
(24, 1, 'login', '2025-05-07 15:07:14'),
(25, 1, 'login', '2025-05-09 17:29:31'),
(26, 1, 'login', '2025-05-09 17:35:20'),
(27, 1, 'login', '2025-05-09 17:38:05'),
(28, 1, 'login', '2025-05-09 17:41:24'),
(29, 1, 'login', '2025-05-09 17:46:21'),
(30, 1, 'login', '2025-05-09 17:57:00'),
(31, 1, 'login', '2025-05-09 17:59:25'),
(32, 1, 'login', '2025-05-09 17:59:53'),
(33, 1, 'login', '2025-05-09 18:00:25'),
(34, 1, 'login', '2025-05-09 18:01:15'),
(35, 1, 'login', '2025-05-09 18:04:19'),
(36, 1, 'login', '2025-05-09 18:06:07'),
(37, 1, 'login', '2025-05-09 18:07:43'),
(38, 1, 'login', '2025-05-10 06:56:00'),
(39, 1, 'login', '2025-05-10 07:20:54'),
(40, 1, 'login', '2025-05-10 12:01:45'),
(41, 1, 'login', '2025-05-12 13:33:35'),
(42, 1, 'login', '2025-05-13 01:05:12'),
(43, 1, 'login', '2025-05-13 01:18:03'),
(44, 1, 'login', '2025-05-13 01:19:09'),
(46, 1, 'login', '2025-05-13 03:43:32'),
(47, 1, 'login', '2025-05-13 04:18:33'),
(49, 1, 'login', '2025-05-13 04:23:03'),
(51, 1, 'login', '2025-05-13 04:25:07'),
(52, 1, 'login', '2025-05-13 15:30:17'),
(53, 1, 'login', '2025-05-14 11:28:13'),
(55, 1, 'login', '2025-05-14 12:54:09'),
(57, 1, 'login', '2025-05-14 16:54:02'),
(58, 1, 'login', '2025-05-14 16:54:46'),
(59, 1, 'login', '2025-05-15 13:25:53'),
(63, 1, 'login', '2025-05-15 15:00:37'),
(66, 1, 'login', '2025-05-15 17:38:59'),
(67, 1, 'login', '2025-05-16 04:50:15'),
(68, 1, 'login', '2025-05-16 05:50:30'),
(70, 1, 'login', '2025-05-16 06:16:46'),
(71, 1, 'login', '2025-05-16 07:01:49'),
(73, 1, 'login', '2025-05-16 07:06:04'),
(74, 1, 'login', '2025-05-16 08:00:10'),
(75, 1, 'login', '2025-05-16 08:20:44'),
(76, 1, 'login', '2025-05-27 15:14:32'),
(77, 1, 'login', '2025-05-28 13:01:27'),
(78, 1, 'login', '2025-05-28 15:17:34'),
(79, 1, 'login', '2025-05-28 15:26:06'),
(80, 1, 'login', '2025-05-28 18:03:49'),
(81, 1, 'login', '2025-05-28 18:10:54'),
(82, 1, 'login', '2025-05-28 18:14:44'),
(83, 1, 'login', '2025-05-28 18:18:17'),
(84, 1, 'login', '2025-05-28 18:36:30'),
(85, 1, 'login', '2025-05-28 18:37:34'),
(86, 1, 'login', '2025-05-28 18:52:08'),
(87, 1, 'login', '2025-05-28 18:52:47'),
(88, 1, 'login', '2025-05-28 18:53:15'),
(89, 1, 'login', '2025-05-28 18:54:02'),
(90, 1, 'login', '2025-05-28 18:54:21'),
(91, 1, 'login', '2025-05-28 18:55:26'),
(92, 1, 'login', '2025-05-28 18:56:43'),
(93, 1, 'login', '2025-05-28 19:00:19'),
(94, 1, 'login', '2025-05-28 19:00:40'),
(95, 1, 'login', '2025-05-28 19:05:58'),
(96, 1, 'login', '2025-05-28 19:08:40'),
(97, 1, 'login', '2025-05-28 19:09:19'),
(98, 1, 'login', '2025-05-28 19:11:02'),
(99, 1, 'login', '2025-05-28 19:17:07'),
(100, 1, 'login', '2025-05-28 19:19:26'),
(101, 1, 'login', '2025-05-28 19:23:59'),
(102, 1, 'login', '2025-05-28 19:30:30'),
(103, 1, 'login', '2025-05-28 19:32:50'),
(104, 1, 'login', '2025-05-28 19:34:03'),
(105, 1, 'login', '2025-05-28 19:58:03'),
(106, 1, 'login', '2025-05-28 20:41:49'),
(107, 1, 'login', '2025-05-28 20:50:11'),
(108, 1, 'login', '2025-05-28 20:55:09'),
(109, 1, 'login', '2025-05-28 20:56:10'),
(110, 1, 'login', '2025-05-28 21:41:22'),
(111, 1, 'login', '2025-05-28 21:43:33'),
(112, 1, 'login', '2025-05-28 23:15:26'),
(113, 1, 'login', '2025-05-28 23:16:32'),
(115, 1, 'login', '2025-06-04 18:21:00'),
(116, 1, 'login', '2025-06-04 20:43:03'),
(117, 1, 'login', '2025-06-04 21:22:04'),
(118, 1, 'login', '2025-06-04 21:25:00'),
(119, 1, 'login', '2025-06-04 21:34:49'),
(120, 1, 'login', '2025-06-05 17:50:36'),
(121, 1, 'login', '2025-06-12 03:49:02'),
(122, 1, 'login', '2025-06-13 07:02:12'),
(123, 1, 'login', '2025-06-18 02:42:44'),
(124, 1, 'login', '2025-07-02 18:15:11'),
(125, 1, 'login', '2025-07-03 03:59:19'),
(126, 1, 'login', '2025-08-09 18:28:39'),
(127, 1, 'login', '2025-08-09 18:31:18'),
(128, 1, 'login', '2025-08-28 19:21:40'),
(129, 1, 'login', '2025-08-29 07:19:15'),
(130, 1, 'login', '2025-08-29 13:14:22'),
(131, 1, 'login', '2025-08-30 00:55:06'),
(132, 1, 'login', '2025-08-31 14:25:55'),
(133, 1, 'login', '2025-08-31 16:38:30'),
(134, 1, 'login', '2025-09-08 04:47:46'),
(135, 1, 'login', '2025-09-08 05:49:59'),
(136, 1, 'login', '2025-09-08 08:01:03'),
(137, 1, 'login', '2025-09-09 00:54:34'),
(138, 1, 'login', '2025-09-09 12:02:50'),
(139, 1, 'login', '2025-09-11 15:45:28'),
(140, 1, 'login', '2025-09-11 19:23:57'),
(141, 1, 'login', '2025-09-11 19:24:21'),
(142, 1, 'login', '2025-09-11 19:31:19'),
(143, 1, 'login', '2025-09-11 19:31:52'),
(144, 1, 'login', '2025-09-11 19:33:02'),
(145, 1, 'login', '2025-09-11 19:33:55'),
(146, 1, 'login', '2025-09-11 19:34:41'),
(147, 1, 'login', '2025-09-11 19:41:07'),
(148, 1, 'login', '2025-09-11 19:42:19'),
(149, 1, 'login', '2025-09-11 19:53:19'),
(150, 1, 'login', '2025-09-11 19:53:36'),
(151, 1, 'login', '2025-09-11 20:26:22'),
(152, 1, 'login', '2025-09-11 20:26:49'),
(153, 1, 'login', '2025-09-11 20:26:50'),
(154, 1, 'login', '2025-09-11 20:27:37'),
(155, 1, 'login', '2025-09-11 20:35:37'),
(156, 1, 'login', '2025-09-11 21:10:48'),
(157, 1, 'login', '2025-09-11 21:13:21'),
(158, 1, 'login', '2025-09-11 21:14:32'),
(159, 1, 'login', '2025-09-13 00:13:54'),
(160, 1, 'login', '2025-09-13 00:31:39'),
(161, 1, 'login', '2025-09-13 00:32:55'),
(162, 1, 'login', '2025-09-13 00:44:10'),
(163, 1, 'login', '2025-09-13 03:48:47'),
(164, 1, 'login', '2025-09-13 03:52:47'),
(165, 1, 'login', '2025-09-14 08:03:18'),
(166, 1, 'login', '2025-09-14 10:55:36'),
(167, 1, 'login', '2025-09-14 11:16:41'),
(168, 1, 'login', '2025-09-14 11:24:25'),
(169, 1, 'login', '2025-09-14 11:36:57'),
(170, 1, 'login', '2025-09-14 11:51:34'),
(171, 1, 'login', '2025-09-14 19:11:04'),
(172, 1, 'login', '2025-09-14 19:12:52'),
(173, 1, 'login', '2025-09-14 19:14:34'),
(174, 1, 'login', '2025-09-14 19:22:42'),
(175, 1, 'login', '2025-09-14 19:29:11'),
(176, 1, 'login', '2025-09-14 19:30:51'),
(177, 1, 'login', '2025-09-14 19:34:21'),
(178, 1, 'login', '2025-09-14 19:37:04'),
(179, 1, 'login', '2025-09-14 19:37:53'),
(180, 1, 'login', '2025-09-14 19:38:46'),
(181, 1, 'login', '2025-09-14 19:42:35'),
(182, 1, 'login', '2025-09-14 19:43:53'),
(183, 1, 'login', '2025-09-14 19:44:32'),
(184, 1, 'login', '2025-09-14 19:45:33'),
(185, 1, 'login', '2025-09-14 19:49:24'),
(186, 1, 'login', '2025-09-14 19:52:00'),
(187, 1, 'login', '2025-09-14 19:52:37'),
(188, 1, 'login', '2025-09-14 20:03:37'),
(189, 1, 'login', '2025-09-14 20:04:24'),
(190, 1, 'login', '2025-09-14 20:06:55'),
(191, 1, 'login', '2025-09-14 20:07:37'),
(192, 1, 'login', '2025-09-14 20:08:22'),
(193, 1, 'login', '2025-09-14 20:13:42'),
(194, 1, 'login', '2025-09-14 20:21:25'),
(195, 1, 'login', '2025-09-14 20:22:52'),
(196, 1, 'login', '2025-09-14 20:23:58'),
(197, 1, 'login', '2025-09-14 20:24:30'),
(198, 1, 'login', '2025-09-14 20:25:27'),
(199, 1, 'login', '2025-09-14 20:25:48'),
(200, 1, 'login', '2025-09-14 20:43:51'),
(201, 1, 'login', '2025-09-14 20:44:41'),
(202, 1, 'login', '2025-09-14 20:46:33'),
(203, 1, 'login', '2025-09-14 20:48:20'),
(204, 1, 'login', '2025-09-14 20:49:04'),
(205, 1, 'login', '2025-09-14 20:50:47'),
(206, 1, 'login', '2025-09-14 20:54:03'),
(207, 1, 'login', '2025-09-14 20:55:48'),
(208, 1, 'login', '2025-09-14 20:56:05'),
(209, 1, 'login', '2025-09-14 21:00:53'),
(210, 1, 'login', '2025-09-14 21:01:09'),
(211, 1, 'login', '2025-09-14 21:01:41'),
(212, 1, 'login', '2025-09-14 21:02:49'),
(213, 1, 'login', '2025-09-14 21:03:17'),
(214, 1, 'login', '2025-09-14 21:23:26'),
(215, 1, 'login', '2025-09-14 21:31:23'),
(216, 1, 'login', '2025-09-14 21:51:32'),
(217, 1, 'login', '2025-09-14 22:20:22'),
(218, 1, 'login', '2025-09-14 22:47:37'),
(219, 1, 'login', '2025-09-14 22:56:15'),
(220, 1, 'login', '2025-09-14 22:56:56'),
(221, 1, 'login', '2025-09-14 22:57:09'),
(222, 1, 'login', '2025-09-14 22:57:30'),
(223, 1, 'login', '2025-09-14 22:57:44'),
(224, 1, 'login', '2025-09-14 22:58:08'),
(225, 1, 'login', '2025-09-14 22:58:42'),
(226, 1, 'login', '2025-09-14 23:24:53'),
(227, 1, 'login', '2025-09-14 23:27:26'),
(228, 1, 'login', '2025-09-14 23:37:41'),
(229, 1, 'login', '2025-09-14 23:40:52'),
(230, 1, 'login', '2025-09-14 23:47:32'),
(231, 1, 'login', '2025-09-14 23:57:59'),
(232, 1, 'login', '2025-09-14 23:59:18'),
(233, 1, 'login', '2025-09-15 00:15:49'),
(234, 1, 'login', '2025-09-15 00:17:41'),
(235, 1, 'login', '2025-09-15 01:20:03'),
(236, 1, 'login', '2025-09-15 01:58:48'),
(237, 1, 'login', '2025-09-15 02:14:45'),
(238, 1, 'login', '2025-09-15 02:56:10'),
(239, 1, 'login', '2025-09-15 05:21:26'),
(240, 1, 'login', '2025-09-15 05:22:16'),
(241, 1, 'login', '2025-09-15 05:24:18'),
(242, 1, 'login', '2025-09-16 19:27:53'),
(243, 1, 'login', '2025-09-16 19:30:47'),
(244, 1, 'login', '2025-09-16 19:32:46'),
(245, 1, 'login', '2025-09-17 00:48:15'),
(246, 1, 'login', '2025-09-17 00:50:09'),
(247, 1, 'login', '2025-09-17 00:51:15'),
(248, 1, 'login', '2025-09-17 01:09:16'),
(249, 1, 'login', '2025-09-17 01:13:26'),
(250, 1, 'login', '2025-09-17 02:21:01'),
(251, 1, 'login', '2025-09-17 02:21:22'),
(252, 1, 'login', '2025-09-17 03:45:13'),
(253, 1, 'login', '2025-09-17 03:48:53'),
(254, 1, 'login', '2025-09-17 03:49:50'),
(255, 1, 'login', '2025-09-17 04:00:53'),
(256, 1, 'login', '2025-09-17 04:06:43'),
(257, 1, 'login', '2025-09-17 04:40:26'),
(258, 1, 'login', '2025-09-17 04:46:10'),
(259, 1, 'login', '2025-09-17 05:05:04'),
(260, 1, 'login', '2025-09-17 05:05:27'),
(261, 1, 'login', '2025-09-17 08:57:56'),
(262, 1, 'login', '2025-09-18 02:24:07'),
(263, 1, 'login', '2025-09-18 03:30:47'),
(264, 1, 'login', '2025-09-18 06:26:23'),
(265, 1, 'login', '2025-09-18 06:29:07'),
(266, 1, 'login', '2025-09-18 06:29:49'),
(267, 1, 'login', '2025-09-18 06:32:05'),
(268, 1, 'login', '2025-09-18 06:32:45'),
(269, 1, 'login', '2025-09-18 06:37:42'),
(270, 1, 'login', '2025-09-18 06:38:35'),
(271, 1, 'login', '2025-09-18 06:39:08'),
(272, 1, 'login', '2025-09-18 06:42:46'),
(273, 1, 'login', '2025-09-18 07:30:03'),
(274, 1, 'login', '2025-09-18 07:31:05'),
(275, 1, 'login', '2025-09-18 07:32:28'),
(276, 1, 'login', '2025-09-18 07:34:13'),
(277, 1, 'login', '2025-09-18 07:54:49'),
(278, 1, 'login', '2025-09-18 09:51:43'),
(279, 1, 'login', '2025-09-18 09:53:32'),
(280, 1, 'login', '2025-09-18 10:50:03'),
(281, 1, 'login', '2025-09-18 11:34:21'),
(282, 1, 'login', '2025-09-18 12:42:33'),
(283, 1, 'login', '2025-09-18 13:23:45'),
(284, 1, 'login', '2025-09-18 13:32:14'),
(285, 1, 'login', '2025-09-18 13:32:45'),
(286, 1, 'login', '2025-09-18 13:32:58'),
(287, 1, 'login', '2025-09-18 13:33:51'),
(288, 1, 'login', '2025-09-18 13:37:33'),
(289, 1, 'login', '2025-09-18 13:38:05'),
(290, 1, 'login', '2025-09-18 13:38:23'),
(291, 1, 'login', '2025-09-18 13:39:48'),
(292, 1, 'login', '2025-09-18 13:40:10'),
(293, 1, 'login', '2025-09-18 13:45:09'),
(294, 1, 'login', '2025-09-18 13:45:29'),
(295, 1, 'login', '2025-09-18 13:47:38'),
(296, 1, 'login', '2025-09-18 14:07:39'),
(297, 1, 'login', '2025-09-18 14:15:03'),
(298, 1, 'login', '2025-09-18 14:15:16'),
(299, 1, 'login', '2025-09-18 14:15:32'),
(300, 1, 'login', '2025-09-18 14:15:57'),
(301, 1, 'login', '2025-09-18 14:17:38'),
(302, 1, 'login', '2025-09-18 14:19:37'),
(303, 1, 'login', '2025-09-18 14:20:32'),
(304, 1, 'login', '2025-09-18 14:20:56'),
(305, 1, 'login', '2025-09-18 14:21:07'),
(306, 1, 'login', '2025-09-18 14:22:48'),
(307, 1, 'login', '2025-09-18 14:29:33'),
(308, 1, 'login', '2025-09-18 14:30:29'),
(309, 1, 'login', '2025-09-18 15:01:53'),
(310, 1, 'login', '2025-09-18 20:52:30'),
(311, 1, 'login', '2025-09-18 21:21:24'),
(312, 1, 'login', '2025-09-18 21:42:45'),
(313, 1, 'login', '2025-09-18 21:43:08'),
(314, 1, 'login', '2025-09-18 21:43:30'),
(315, 1, 'login', '2025-09-18 21:44:18'),
(316, 1, 'login', '2025-09-18 21:47:54'),
(317, 1, 'login', '2025-09-18 21:48:03'),
(318, 1, 'login', '2025-09-18 21:50:38'),
(319, 1, 'login', '2025-09-18 21:51:28'),
(320, 1, 'login', '2025-09-18 21:52:30'),
(321, 1, 'login', '2025-09-18 21:52:55'),
(322, 1, 'login', '2025-09-18 21:54:12'),
(323, 1, 'login', '2025-09-18 21:54:38'),
(324, 1, 'login', '2025-09-18 21:55:03'),
(325, 1, 'login', '2025-09-18 21:59:55'),
(326, 1, 'login', '2025-09-18 22:00:27'),
(327, 1, 'login', '2025-09-18 22:01:14'),
(328, 1, 'login', '2025-09-18 22:01:38'),
(329, 1, 'login', '2025-09-18 22:04:23'),
(330, 1, 'login', '2025-09-18 22:05:02'),
(331, 1, 'login', '2025-09-18 22:07:42'),
(332, 1, 'login', '2025-09-18 22:13:21'),
(333, 1, 'login', '2025-09-18 22:13:32'),
(334, 1, 'login', '2025-09-18 22:14:00'),
(335, 1, 'login', '2025-09-18 22:14:22'),
(336, 1, 'login', '2025-09-18 22:16:52'),
(337, 1, 'login', '2025-09-18 22:17:02'),
(338, 1, 'login', '2025-09-18 22:17:38'),
(339, 1, 'login', '2025-09-18 22:17:47'),
(340, 1, 'login', '2025-09-18 22:19:00'),
(341, 1, 'login', '2025-09-18 22:24:57'),
(342, 1, 'login', '2025-09-18 22:25:23'),
(343, 1, 'login', '2025-09-18 22:29:00'),
(344, 1, 'login', '2025-09-18 22:29:17'),
(345, 1, 'login', '2025-09-18 22:31:57'),
(346, 1, 'login', '2025-09-18 22:32:25'),
(347, 1, 'login', '2025-09-18 22:33:42'),
(348, 1, 'login', '2025-09-18 22:34:47'),
(349, 1, 'login', '2025-09-18 22:35:27'),
(350, 1, 'login', '2025-09-18 22:38:38'),
(351, 1, 'login', '2025-09-18 22:40:35'),
(352, 1, 'login', '2025-09-18 22:40:56'),
(353, 1, 'login', '2025-09-18 22:41:23'),
(354, 1, 'login', '2025-09-18 22:48:37'),
(355, 1, 'login', '2025-09-18 22:48:56'),
(356, 1, 'login', '2025-09-18 22:51:38'),
(357, 1, 'login', '2025-09-18 22:53:34'),
(358, 1, 'login', '2025-09-18 22:56:03'),
(359, 1, 'login', '2025-09-18 22:56:28'),
(360, 1, 'login', '2025-09-18 23:00:28'),
(361, 1, 'login', '2025-09-18 23:05:59'),
(362, 1, 'login', '2025-09-19 00:24:52'),
(363, 1, 'login', '2025-09-19 01:03:33'),
(364, 1, 'login', '2025-09-19 01:14:40'),
(365, 1, 'login', '2025-09-19 01:35:27'),
(366, 1, 'login', '2025-09-19 01:38:32'),
(367, 1, 'login', '2025-09-19 01:41:02'),
(368, 1, 'login', '2025-09-19 01:43:15'),
(369, 1, 'login', '2025-09-19 01:43:36'),
(370, 1, 'login', '2025-09-19 01:43:36'),
(371, 1, 'login', '2025-09-19 01:45:10'),
(372, 1, 'login', '2025-09-19 01:47:01'),
(373, 1, 'login', '2025-09-19 01:49:53'),
(374, 1, 'login', '2025-09-19 01:53:22'),
(375, 1, 'login', '2025-09-19 01:55:19'),
(376, 1, 'login', '2025-09-19 01:56:55'),
(377, 1, 'login', '2025-09-19 01:57:46'),
(378, 1, 'login', '2025-09-19 01:58:19'),
(379, 1, 'login', '2025-09-19 02:00:02'),
(380, 1, 'login', '2025-09-19 02:21:32'),
(381, 1, 'login', '2025-09-19 02:28:11'),
(382, 1, 'login', '2025-09-19 02:30:41'),
(383, 1, 'login', '2025-09-19 02:33:54'),
(385, 1, 'login', '2025-09-19 02:58:48'),
(386, 1, 'login', '2025-09-19 06:07:48'),
(387, 12, 'login', '2025-09-19 06:33:38'),
(388, 12, 'login', '2025-09-19 06:39:25'),
(389, 12, 'login', '2025-09-19 06:42:08'),
(390, 12, 'login', '2025-09-19 06:52:46'),
(391, 12, 'login', '2025-09-19 07:42:35'),
(392, 12, 'login', '2025-09-19 07:54:33'),
(393, 12, 'login', '2025-09-19 08:18:00'),
(394, 12, 'login', '2025-09-19 08:18:20'),
(395, 12, 'login', '2025-09-19 08:37:10'),
(396, 12, 'login', '2025-09-19 08:50:20'),
(397, 12, 'login', '2025-09-19 09:36:09'),
(398, 12, 'login', '2025-09-19 09:36:45'),
(399, 12, 'login', '2025-09-19 09:50:05'),
(400, 1, 'login', '2025-09-19 09:53:44'),
(401, 12, 'login', '2025-09-19 09:56:39'),
(402, 13, 'login', '2025-09-19 09:59:16'),
(403, 13, 'login', '2025-09-19 09:59:59'),
(404, 1, 'login', '2025-09-19 10:00:33'),
(405, 1, 'login', '2025-09-19 13:34:52'),
(406, 1, 'login', '2025-09-21 05:19:02'),
(407, 12, 'login', '2025-09-21 22:00:45'),
(408, 1, 'login', '2025-09-29 03:57:12'),
(409, 1, 'login', '2025-09-29 14:03:13'),
(410, 1, 'login', '2025-09-29 21:32:29'),
(411, 1, 'login', '2025-09-30 02:14:12'),
(412, 12, 'login', '2025-09-30 03:53:26'),
(413, 1, 'login', '2025-09-30 03:55:06'),
(414, 12, 'login', '2025-09-30 04:32:21'),
(415, 12, 'login', '2025-09-30 05:08:12'),
(416, 1, 'login', '2025-10-10 11:01:39'),
(417, 1, 'login', '2025-10-10 13:45:08'),
(418, 1, 'login', '2025-10-16 14:34:34'),
(419, 1, 'login', '2025-10-19 09:25:33'),
(420, 1, 'login', '2025-10-19 09:49:35'),
(421, 1, 'login', '2025-10-19 10:01:37'),
(422, 1, 'login', '2025-10-19 10:01:53'),
(423, 1, 'login', '2025-10-20 07:02:40'),
(424, 1, 'login', '2025-10-20 10:02:10'),
(425, 1, 'login', '2025-10-20 14:32:36'),
(426, 1, 'login', '2025-10-20 15:52:05'),
(427, 1, 'login', '2025-10-20 15:58:14'),
(428, 1, 'login', '2025-10-20 15:58:22'),
(429, 1, 'login', '2025-10-20 15:58:36'),
(430, 12, 'login', '2025-10-20 16:07:36'),
(431, 1, 'login', '2025-10-20 16:09:15'),
(432, 1, 'login', '2025-10-21 07:09:56'),
(433, 1, 'login', '2025-10-21 12:30:59'),
(434, 1, 'login', '2025-10-21 12:34:48'),
(435, 1, 'login', '2025-10-21 13:16:32'),
(436, 18, 'login', '2025-10-21 13:31:15'),
(437, 1, 'login', '2025-10-21 13:35:16'),
(438, 18, 'login', '2025-10-21 13:35:27'),
(439, 1, 'login', '2025-10-21 16:27:12'),
(440, 1, 'login', '2025-10-21 23:58:31'),
(441, 1, 'login', '2025-10-22 14:59:54'),
(442, 21, 'login', '2025-10-22 15:02:19'),
(443, 1, 'login', '2025-10-22 15:07:16'),
(444, 1, 'login', '2025-10-22 15:11:31'),
(445, 1, 'login', '2025-10-22 15:12:29'),
(446, 21, 'login', '2025-10-22 15:13:07'),
(447, 1, 'login', '2025-10-22 15:16:07'),
(448, 1, 'login', '2025-10-22 15:17:35'),
(449, 21, 'login', '2025-10-22 15:24:02'),
(450, 21, 'login', '2025-10-22 15:31:28'),
(451, 1, 'login', '2025-10-22 15:31:34'),
(452, 50, 'login', '2025-10-22 15:32:50'),
(453, 1, 'login', '2025-10-22 15:38:43'),
(454, 21, 'login', '2025-10-22 15:51:22'),
(455, 1, 'login', '2025-10-22 15:55:49'),
(456, 50, 'login', '2025-10-22 15:56:45'),
(457, 1, 'login', '2025-10-22 15:59:12'),
(458, 50, 'login', '2025-10-22 16:09:49'),
(459, 1, 'login', '2025-10-22 16:15:23'),
(460, 21, 'login', '2025-10-22 16:19:39'),
(461, 1, 'login', '2025-10-22 16:36:11'),
(462, 28, 'login', '2025-10-22 16:38:33'),
(463, 1, 'login', '2025-10-22 16:39:02'),
(464, 21, 'login', '2025-10-22 16:46:59'),
(465, 1, 'login', '2025-10-22 17:31:26'),
(466, 21, 'login', '2025-10-22 17:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `multimedia_content`
--

CREATE TABLE `multimedia_content` (
  `id` int(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `multimedia_content`
--

INSERT INTO `multimedia_content` (`id`, `file_path`, `uploaded_by`, `category`, `uploaded_at`) VALUES
(3, 'multimedia/announcements/67f7eb542a2fc_SIAA ERD.jpg', 1, 'general', '0000-00-00 00:00:00'),
(9, 'multimedia/announcements/media_67f81be80cdc29.76424528.jpg', 1, '', '0000-00-00 00:00:00'),
(12, 'multimedia/announcements/67f82381333fd_image20.JPG', 1, 'general', '0000-00-00 00:00:00'),
(17, 'multimedia/officers/681b820fde9e9_june.jpg', 1, '', '0000-00-00 00:00:00'),
(18, 'multimedia/officers/681b82175e81b_june.jpg', 1, '', '0000-00-00 00:00:00'),
(19, 'multimedia/announcements/681b831f7f0ce_june.jpg', 1, 'general', '2025-05-07 23:58:23'),
(20, 'multimedia/media_681b8401891042.93235059.png', 1, '', '2025-05-08 00:02:09'),
(21, 'multimedia/media_681b840919be01.26738261.jpg', 1, '', '2025-05-08 00:02:17'),
(22, 'multimedia/announcements/media_681b845f1f40d4.64884015.jpg', 1, '', '2025-05-08 00:03:43'),
(23, 'multimedia/officers/681b879c1e13b_june.jpg', 1, '', '2025-05-08 00:17:32'),
(24, 'multimedia/faculty/683766bbb7db4.png', 1, '', '2025-05-08 00:50:21'),
(25, 'multimedia/faculty/681b8f4d70dc3.jpg', 1, '', '2025-05-08 00:50:21'),
(29, 'multimedia/announcements/681ba2aa6f1f4_neshamah.png', 1, '', '2025-05-08 02:12:58'),
(30, 'multimedia/faculty/6837671a37cec.png', 1, '', '2025-05-08 02:20:09'),
(31, 'multimedia/faculty/681ba459e62df.jpg', 1, '', '2025-05-08 02:20:09'),
(32, 'multimedia/faculty/681bab96f0165.png', 1, '', '2025-05-08 02:29:54'),
(33, 'multimedia/faculty/681ba6a2b3b23.jpg', 1, '', '2025-05-08 02:29:54'),
(34, 'multimedia/faculty/6837675284c3a.png', 1, '', '2025-05-08 02:31:04'),
(35, 'multimedia/faculty/681ba6e8ab7ab.jpg', 1, '', '2025-05-08 02:31:04'),
(36, 'multimedia/faculty/681ba887e2934.png', 1, '', '2025-05-08 02:34:14'),
(37, 'multimedia/faculty/681ba7a6f0215.jpg', 1, '', '2025-05-08 02:34:14'),
(38, 'multimedia/faculty/681babd8517a8.png', 1, '', '2025-05-08 02:52:08'),
(39, 'multimedia/faculty/681bac083b6f7.png', 1, '', '2025-05-08 02:52:56'),
(40, 'multimedia/faculty/681bac76bdfba.png', 1, '', '2025-05-08 02:54:46'),
(41, 'multimedia/faculty/681efa06de696.png', 1, '', '2025-05-08 02:55:47'),
(42, 'multimedia/officers/681bb2c20003d_495268675_1251078759774071_1061084148971587387_n.png', 1, '', '2025-05-08 03:21:38'),
(43, 'multimedia/announcements/media_681f0604146cf4.03807932.png', 1, '', '2025-05-10 15:53:40'),
(44, 'multimedia/announcements/682203c224259_photo3.jpg', 1, '', '2025-05-12 22:20:50'),
(45, 'multimedia/announcements/682365a46352c_Editor _ Mermaid Chart-2025-05-09-062041.png', 1, '', '2025-05-13 23:30:44'),
(46, 'multimedia/announcements/682365af24c89_487828872_1019652846162196_5048343499840301687_n.jpg', 1, '', '2025-05-13 23:30:55'),
(48, 'events/68247fb0a3a4a.jpg', 1, '', '2025-05-14 19:34:08'),
(65, 'events/68262823e447c.jpg', 1, '', '2025-05-16 01:45:07'),
(66, 'events/6826299513cce.png', 1, '', '2025-05-16 01:51:17'),
(70, 'events/6826d282741bd.jpg', 1, '', '2025-05-16 13:52:02'),
(73, 'events/6826f7751e393.png', 1, '', '2025-05-16 16:29:41'),
(75, 'multimedia/faculty/6835da3713f07.png', 1, '', '2025-05-27 23:28:55'),
(76, 'multimedia/faculty/683766eba7dbf.png', 1, '', '2025-05-27 23:36:35'),
(77, 'multimedia/faculty/6837672fa84ec.png', 1, '', '2025-05-27 23:37:58'),
(78, 'multimedia/faculty/683766661cd4d.png', 1, '', '2025-05-27 23:40:50'),
(79, 'multimedia/faculty/683766d390e5e.png', 1, '', '2025-05-27 23:42:27'),
(80, 'multimedia/faculty/68376648116bf.png', 1, '', '2025-05-27 23:43:22'),
(81, 'multimedia/faculty/68376624d8b28.png', 1, '', '2025-05-27 23:44:06'),
(82, 'multimedia/faculty/6837676b5fbff.png', 1, '', '2025-05-27 23:45:01'),
(86, 'multimedia/officers/68376aeeec353_Luisa.png', 1, '', '2025-05-28 12:58:38'),
(87, 'multimedia/officers/68376af9069f2_Ashi.png', 1, '', '2025-05-28 12:58:49'),
(88, 'multimedia/officers/68376b0144ab5_cherish.png', 1, '', '2025-05-28 12:58:57'),
(89, 'multimedia/officers/68376b096330e_john.png', 1, '', '2025-05-28 12:59:05'),
(90, 'multimedia/officers/68376b116f34e_kyla.png', 1, '', '2025-05-28 12:59:13'),
(91, 'multimedia/officers/68376b1945764_charlene.png', 1, '', '2025-05-28 12:59:21'),
(92, 'multimedia/officers/68376b2386631_rienze.png', 1, '', '2025-05-28 12:59:31'),
(93, 'multimedia/officers/68376b2b9e99b_roj.png', 1, '', '2025-05-28 12:59:39'),
(94, 'multimedia/officers/68376b34371ee_vismonte.png', 1, '', '2025-05-28 12:59:48'),
(95, 'multimedia/officers/68376b3e827f7_vanessa.png', 1, '', '2025-05-28 12:59:58'),
(96, 'multimedia/officers/68376b477eddb_Aliyah.png', 1, '', '2025-05-28 13:00:07'),
(97, 'multimedia/officers/68376b5056000_von.png', 1, '', '2025-05-28 13:00:16'),
(98, 'multimedia/officers/68376b5e5f624_lester.png', 1, '', '2025-05-28 13:00:30'),
(99, 'multimedia/officers/68376b68b4698_mat.png', 1, '', '2025-05-28 13:00:40'),
(100, 'multimedia/officers/68376b7739cd3_kuya lex.png', 1, '', '2025-05-28 13:00:55'),
(101, 'multimedia/announcements/media_68377a4b5bc517.88787545.jpg', 1, '', '2025-05-28 14:04:11'),
(102, 'multimedia/announcements/media_68377a4b5c2c90.34820670.jpg', 1, '', '2025-05-28 14:04:11'),
(103, 'multimedia/announcements/media_68377a4b5c6847.86153660.jpg', 1, '', '2025-05-28 14:04:11'),
(104, 'multimedia/announcements/media_68377a4b5c9dd1.03748121.jpg', 1, '', '2025-05-28 14:04:11'),
(105, 'multimedia/announcements/media_68377a4b5cd3f8.90496605.jpg', 1, '', '2025-05-28 14:04:11'),
(106, 'multimedia/announcements/media_68377a4b5d0ed7.21115308.jpg', 1, '', '2025-05-28 14:04:11'),
(107, 'multimedia/announcements/media_68377a4b5d90f5.83543904.jpg', 1, '', '2025-05-28 14:04:11'),
(108, 'multimedia/announcements/media_68377a4b5dc7a1.89553771.jpg', 1, '', '2025-05-28 14:04:11'),
(109, 'multimedia/announcements/media_68377a4b5dfc98.15281565.jpg', 1, '', '2025-05-28 14:04:11'),
(110, 'multimedia/announcements/media_68377a4b5e5ef4.03001682.jpg', 1, '', '2025-05-28 14:04:11'),
(111, 'multimedia/announcements/media_68377a4b5ed2a4.79802672.jpg', 1, '', '2025-05-28 14:04:11'),
(112, 'multimedia/announcements/media_68377a4b5f0ed2.15721786.jpg', 1, '', '2025-05-28 14:04:11'),
(113, 'multimedia/announcements/media_68377dff802117.65866367.jpg', 1, '', '2025-05-28 14:19:59'),
(115, 'events/68377f7aa208e.png', 1, '', '2025-05-28 14:26:18'),
(119, 'events/683786d69e693.jpg', 1, '', '2025-05-28 14:57:42'),
(120, 'multimedia/announcements/media_68c74d775116b9.54998258.jpg', 1, '', '2025-09-14 16:19:19'),
(121, 'events/68c74fb14b944.jpg', 1, '', '2025-09-14 16:28:49'),
(122, 'events/68c75962a8961.jpg', 1, '', '2025-09-14 17:10:10'),
(123, 'multimedia/announcements/media_68c769f9744806.13010559.jpg', 1, '', '2025-09-14 18:20:57'),
(129, 'multimedia/announcements/media_68c774ceb37c52.25572121.jpg', 1, '', '2025-09-14 19:07:10'),
(133, 'multimedia/announcements/media_68c9bb47781e60.94613557.jpg', 1, '', '2025-09-16 12:32:23'),
(134, 'multimedia/announcements/media_68c9bb4778cb93.00593193.jpg', 1, '', '2025-09-16 12:32:23'),
(141, 'multimedia/announcements/media_68ca0a05967cc2.82170359.png', 1, '', '2025-09-16 18:08:21'),
(142, 'multimedia/announcements/media_68ca0a0596e705.08945623.png', 1, '', '2025-09-16 18:08:21'),
(144, 'multimedia/announcements/media_68ca0b90d4b089.29481130.png', 1, '', '2025-09-16 18:14:56'),
(145, 'multimedia/announcements/media_68ca0b90d52796.37031560.png', 1, '', '2025-09-16 18:14:56'),
(146, 'announcements/68ca0ba154e1f.png', 1, '', '2025-09-16 18:15:13'),
(152, 'multimedia/announcements/media_68ca0d0a669f60.93201769.png', 1, '', '2025-09-16 18:21:14'),
(153, 'multimedia/announcements/media_68ca0d0a670d78.99251879.png', 1, '', '2025-09-16 18:21:14'),
(157, 'multimedia/announcements/media_68ca0dff66a8b9.76108686.png', 1, '', '2025-09-16 18:25:19'),
(158, 'multimedia/announcements/media_68ca0dff670f74.46798773.png', 1, '', '2025-09-16 18:25:19'),
(159, 'announcements/68ca0e16830aa.png', 1, '', '2025-09-16 18:25:42'),
(160, 'multimedia/announcements/media_68cb7d093b0ad6.47849073.jfif', 1, '', '2025-09-17 20:31:21'),
(161, 'announcements/68cb7d2896845.png', 1, '', '2025-09-17 20:31:52'),
(162, 'multimedia/announcements/media_68cbaa140809f9.93541436.png', 1, '', '2025-09-17 23:43:32'),
(163, 'announcements/68cbaa24d2df5.jfif', 1, '', '2025-09-17 23:43:48'),
(164, 'announcements/68cbaa400b519.png', 1, '', '2025-09-17 23:44:16'),
(165, 'announcements/68cbaa6e4dd1d.png', 1, '', '2025-09-17 23:45:02'),
(166, 'announcements/68cbaaef6b60d.png', 1, '', '2025-09-17 23:47:11'),
(167, 'events/68cbadce559f3.png', 1, '', '2025-09-17 23:59:26'),
(169, 'multimedia/faculty/68cbaf988d51c.png', 1, '', '2025-09-18 00:07:04'),
(170, 'multimedia/announcements/media_68cbd660619f80.28792294.jpeg', 1, '', '2025-09-18 02:52:32'),
(171, 'announcements/68cbd6a9ac177.jpg', 1, '', '2025-09-18 02:53:45'),
(173, 'events/68cbd75570dcd.jpg', 1, '', '2025-09-18 02:56:37'),
(174, 'announcements/68cbd77594e0c.jpg', 1, '', '2025-09-18 02:57:09'),
(175, 'announcements/68cbe40340a50.png', 1, '', '2025-09-18 03:50:43'),
(176, 'announcements/68cbe42714a4e.png', 1, '', '2025-09-18 03:51:19'),
(177, 'events/68cc21d8c41a0.png', 1, '', '2025-09-18 08:14:32'),
(193, 'events/68cc76208152d.jpg', 1, '', '2025-09-18 14:14:08'),
(194, 'events/68cc766dbf8ba.jpg', 1, '', '2025-09-18 14:15:25'),
(195, 'events/68cc76b8492c5.jpg', 1, '', '2025-09-18 14:16:40'),
(213, 'multimedia/announcements/media_68cc93d0ec6556.55087881.png', 1, '', '2025-09-18 16:20:48'),
(215, 'multimedia/announcements/media_68cc9a1901eb88.69955588.jpg', 1, '', '2025-09-18 16:47:37'),
(216, 'multimedia/announcements/media_68cc9a19024ea7.75692646.jpg', 1, '', '2025-09-18 16:47:37'),
(217, 'multimedia/announcements/media_68cc9a190285a1.57752656.jpg', 1, '', '2025-09-18 16:47:37'),
(218, 'multimedia/announcements/media_68cc9a1902baf7.19212800.jpg', 1, '', '2025-09-18 16:47:37'),
(219, 'multimedia/announcements/media_68cc9a1902ee15.29224172.jpg', 1, '', '2025-09-18 16:47:37'),
(220, 'multimedia/announcements/media_68cc9a19032742.00586668.jpg', 1, '', '2025-09-18 16:47:37'),
(221, 'multimedia/announcements/media_68cc9a19035fe2.72520029.jpg', 1, '', '2025-09-18 16:47:37'),
(222, 'multimedia/announcements/media_68cc9a1903a8e4.67513761.jpg', 1, '', '2025-09-18 16:47:37'),
(223, 'announcements/68cc9a3bc10cf.jpg', 1, '', '2025-09-18 16:48:11'),
(224, 'multimedia/announcements/media_68cc9ab357e0d9.34310159.jpg', 1, '', '2025-09-18 16:50:11'),
(226, 'announcements/68cca03744c12.jpg', 1, '', '2025-09-18 17:13:43'),
(227, 'announcements/68cca037451f0.jpg', 1, '', '2025-09-18 17:13:43'),
(228, 'announcements/68cca03745b59.jpg', 1, '', '2025-09-18 17:13:43'),
(229, 'announcements/68cca03745eb4.jpg', 1, '', '2025-09-18 17:13:43'),
(230, 'announcements/68cca037461dc.jpg', 1, '', '2025-09-18 17:13:43'),
(231, 'announcements/68cca03746543.jpg', 1, '', '2025-09-18 17:13:43'),
(232, 'announcements/68cca037468a6.jpg', 1, '', '2025-09-18 17:13:43'),
(233, 'announcements/68cca03746c06.jpg', 1, '', '2025-09-18 17:13:43'),
(234, 'multimedia/announcements/media_68cca180d1af65.11106393.jpg', 1, '', '2025-09-18 17:19:12'),
(257, 'events/68ccb630a1859.jpg', 1, '', '2025-09-18 18:47:28'),
(258, 'announcements/media_68ccb734764814.61036387.jpg', 1, '', '2025-09-18 18:51:48'),
(260, 'announcements/media_68ccb954b98cf6.06892568.jpg', 1, '', '2025-09-18 19:00:52'),
(261, 'announcements/media_68ccb9af043fb8.40514020.jpg', 1, '', '2025-09-18 19:02:23'),
(264, 'events/68ccbe7a417c2.png', 1, '', '2025-09-18 19:22:50'),
(266, 'events/68ccc135c4540.jpg', 1, '', '2025-09-18 19:34:29'),
(268, 'multimedia/announcements/media_68ccf9af37aab0.51935229.jpg', 12, '', '2025-09-18 23:35:27'),
(270, 'multimedia/announcements/media_68ccf9e242b9b3.19418053.jpg', 1, '', '2025-09-18 23:36:18'),
(272, 'multimedia/announcements/media_68cd0c75c00c19.72473170.png', 12, '', '2025-09-19 00:55:33'),
(274, 'multimedia/announcements/media_68cd0cbb4a1aa2.83884337.png', 12, '', '2025-09-19 00:56:43'),
(277, 'announcements/media_68cd112470aa22.94376846.png', 12, '', '2025-09-19 01:15:32'),
(278, 'announcements/68cd12c4d3d64.jpg', 12, '', '2025-09-19 01:22:28'),
(279, 'announcements/media_68cd152c445925.02110530.png', 1, '', '2025-09-19 01:32:44'),
(280, 'announcements/media_68cd1540769b62.58871719.png', 1, '', '2025-09-19 01:33:04'),
(281, 'announcements/68cd15519a748.png', 1, '', '2025-09-19 01:33:21'),
(282, 'events/68cd1699be250.jpg', 12, '', '2025-09-19 01:38:49'),
(283, 'events/68cd191ec44b7.jpg', 12, '', '2025-09-19 01:49:34'),
(285, 'announcements/media_68cd1ac73949c2.45909871.jpg', 1, '', '2025-09-19 01:56:39'),
(286, 'announcements/media_68cd1aef7eae69.63521532.jpg', 1, '', '2025-09-19 01:57:19'),
(287, 'announcements/media_68cd1b12cb9433.47470550.jpg', 1, '', '2025-09-19 01:57:54'),
(288, 'announcements/media_68cd1b7a295766.60117501.jpg', 1, '', '2025-09-19 01:59:38'),
(289, 'announcements/media_68cd1b7a29eee4.17233003.jpg', 1, '', '2025-09-19 01:59:38'),
(290, 'announcements/media_68cd1b7a2a3f40.15848232.jpg', 1, '', '2025-09-19 01:59:38'),
(291, 'announcements/media_68cd1b7a2a79b9.42130588.jpg', 1, '', '2025-09-19 01:59:38'),
(292, 'announcements/media_68cd1b7a2afba9.90023575.jpg', 1, '', '2025-09-19 01:59:38'),
(293, 'announcements/media_68cd1b7a2cd940.00674670.jpg', 1, '', '2025-09-19 01:59:38'),
(294, 'announcements/media_68cd1b7a2d1480.61133349.jpg', 1, '', '2025-09-19 01:59:38'),
(295, 'announcements/media_68cd1b7a2d4cb1.44566857.jpg', 1, '', '2025-09-19 01:59:38'),
(296, 'events/68cd1c1786ccc.png', 12, '', '2025-09-19 02:02:15'),
(298, 'events/68cd1c7df1a29.jpg', 12, '', '2025-09-19 02:03:57'),
(299, 'events/68cd1cd390804.jpg', 12, '', '2025-09-19 02:05:23'),
(303, 'events/68cd1de3d52c9.jpg', 12, '', '2025-09-19 02:09:55'),
(307, 'events/68cd2479c0464.jpg', 12, '', '2025-09-19 02:38:01'),
(308, 'events/68cd25fbc4966.jpg', 12, '', '2025-09-19 02:44:27'),
(309, 'events/68cd2604d11a6.jpg', 12, '', '2025-09-19 02:44:36'),
(310, 'events/68cd26158294b.jpg', 12, '', '2025-09-19 02:44:53'),
(311, 'events/68cd26ea1cc7f.jpg', 12, '', '2025-09-19 02:48:26'),
(312, 'events/68cd2706ea9bb.jpg', 12, '', '2025-09-19 02:48:54'),
(313, 'events/68cd2aac939cb.jpg', 1, '', '2025-09-19 03:04:28'),
(315, 'events/68cd2c3059cba.jpg', 1, '', '2025-09-19 03:10:56'),
(317, 'events/68cd2c8dcbacc.jpg', 1, '', '2025-09-19 03:12:29'),
(320, 'events/68cd2d421a5fc.jpg', 1, '', '2025-09-19 03:15:30'),
(321, 'events/68da04ede0039.png', 1, '', '2025-09-28 21:02:53'),
(322, 'events/68da06416eb55.png', 1, '', '2025-09-28 21:08:33'),
(323, 'events/68dafbf8b60bf.jpeg', 1, '', '2025-09-29 14:36:56'),
(325, 'multimedia/officers/68f64c235866b_images.png', 1, '', '2025-10-20 07:50:11'),
(326, 'multimedia/officers/68f64d792dcc7_Untitled.png', 1, '', '2025-10-20 07:55:53'),
(327, 'multimedia/officers/68f650bf86308_dsa.jpg', 1, '', '2025-10-20 08:09:51'),
(328, 'multimedia/officers/68f651c719e2f_Untitled.png', 1, '', '2025-10-20 08:14:15'),
(329, 'events/68f65dd50005d.jpg', 1, '', '2025-10-20 09:05:41'),
(330, 'announcements/media_68f6603d378330.67667059.png', 1, '', '2025-10-20 09:15:57'),
(331, 'announcements/media_68f6603d383b45.12094690.png', 1, '', '2025-10-20 09:15:57'),
(332, 'announcements/media_68f6603d386206.13944306.jpg', 1, '', '2025-10-20 09:15:57'),
(333, 'announcements/media_68f78b41855619.22986194.png', 18, '', '2025-10-21 06:31:45'),
(334, 'announcements/media_68f78c41b7d3b0.91632113.png', 18, '', '2025-10-21 06:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `news_update`
--

CREATE TABLE `news_update` (
  `id` int(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `published_by` int(255) NOT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0,
  `media_id` int(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When the news item expires (NULL = never expires)',
  `auto_expire` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether to automatically expire this news item'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='News items for marquee display - duration field removed as marquee now uses infinite scroll';

--
-- Dumping data for table `news_update`
--

INSERT INTO `news_update` (`id`, `title`, `content`, `published_by`, `published_at`, `is_active`, `priority`, `media_id`, `expires_at`, `auto_expire`) VALUES
(1, 'System Update', 'The bulletin board system has been updated with new features for better user experience.', 1, '2025-09-09 14:22:10', 1, 1, NULL, NULL, 0),
(2, 'Academic Calendar', 'Please check the academic calendar for important dates and deadlines this semester.', 1, '2025-09-09 14:22:10', 1, 2, NULL, NULL, 0),
(3, 'Library Hours', 'Library hours have been extended during finals week. Check the schedule for more details.', 1, '2025-09-09 14:22:10', 1, 3, NULL, NULL, 0),
(5, 'FACULTY ANNS', 'THIS IS A TEST OF FACULTY ANNOUNCEMENT', 21, '2025-10-22 16:55:03', 1, 8, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `partylist_id` int(11) NOT NULL,
  `multimedia_id` int(11) DEFAULT NULL,
  `hierarchy_level` int(11) NOT NULL DEFAULT 0 COMMENT 'Level in the organizational hierarchy (0 being the highest)',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Reference to parent officer ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `name`, `position`, `partylist_id`, `multimedia_id`, `hierarchy_level`, `parent_id`) VALUES
(1, 'Luisa Erika Caban', 'President', 1, 86, 0, NULL),
(2, 'Ahsi Lei Immaculata', 'Vice President', 1, 87, 1, NULL),
(3, 'Cherish Bautista', 'Secretary', 1, 88, 1, NULL),
(4, 'John Allan Laraya', 'Treasurer', 1, 89, 1, NULL),
(5, 'Kyla Marie Lopez', 'Auditor', 1, 90, 1, NULL),
(6, 'Charlene Prado', 'PRO Internal', 1, 91, 1, NULL),
(7, 'Rienze Gonzaga', 'PRO External', 1, 92, 1, NULL),
(8, 'Roj Valderama', '1st Yr. Rep', 1, 93, 1, NULL),
(9, 'Liniere Vismonte', '2nd Yr. Rep', 1, 94, 1, NULL),
(10, 'Vanessa Baui', '3rd Yr. Rep', 1, 95, 1, NULL),
(11, 'Aliyah Bautista', '4th Yr. Rep', 1, 96, 1, NULL),
(12, 'Von Ignacio', 'Senior Multimedia', 1, 97, 1, NULL),
(13, 'Lester Prias', 'Multimedia', 1, 98, 1, NULL),
(14, 'Matthew Marquez', 'E-Sports Multimedia', 1, 99, 1, NULL),
(15, 'Lancaster Cuevas', 'E-Sports Multimedia', 1, 100, 1, NULL),
(40, 'John Lorenz T. Malsi', 'President', 7, NULL, 0, NULL),
(41, 'Rienze N. Gonzaga Jr.', 'Internal Vice President', 7, NULL, 1, NULL),
(42, 'Irish A. Balana', 'External Vice President', 7, NULL, 1, NULL),
(43, 'Jessica I. Sicam', 'Secretary', 7, NULL, 1, NULL),
(44, 'Yzabelle S. Alba', 'Assistant Secretary', 7, NULL, 2, NULL),
(45, 'Stephen Inman T. Testa', 'Treasurer', 7, NULL, 1, NULL),
(46, 'Radley Curt D. Lamina', 'Asst. Treasurer', 7, NULL, 2, NULL),
(47, 'Cherish Mae R. Bautista', 'Asst. Treasurer', 7, NULL, 2, NULL),
(48, 'Cyril Dyorjet S. Lugtu', 'Auditor', 7, NULL, 1, NULL),
(49, 'Mathew James B. Tacata', 'PRO Internal', 7, NULL, 1, NULL),
(50, 'Michael Angelo N. Cruz', 'PRO External', 7, NULL, 1, NULL),
(51, 'Celina Louise C. Cajipe', '1st Year Representative', 7, NULL, 1, NULL),
(52, 'Rogelio III V. Valderama', '2nd Year Representative', 7, NULL, 1, NULL),
(53, 'Neil Matthew A. Marquez', '3rd Year Representative', 7, NULL, 1, NULL),
(54, 'Ahsi Lei L. Immaculata', '4th Year Representative', 7, NULL, 1, NULL),
(55, 'Royette Joseph L. Buñi', 'Marshall Head', 7, NULL, 1, NULL),
(56, 'Haris Jay D. Francisco', 'Marshall Head', 7, NULL, 1, NULL),
(57, 'Charlene Clyde G. Prado', 'Senior Multimedia', 7, NULL, 1, NULL),
(58, 'Raphael Lorenzo V. de Silva', 'Multimedia Team', 7, NULL, 2, NULL),
(59, 'Ivan Benedict Bastarriche', 'Multimedia Team', 7, NULL, 2, NULL),
(60, 'John Lester R. Prias', 'Multimedia Team', 7, NULL, 2, NULL),
(61, 'Leejhan Aron C. Fuller', 'Multimedia Team', 7, NULL, 2, NULL),
(62, 'Lancaster Lexus Y. Cuevas', 'Senior Esports', 7, NULL, 1, NULL),
(63, 'Frederick Neil C. Batas', 'Esports Team', 7, NULL, 2, NULL),
(64, 'Sean John A. Duque', 'Esports Team', 7, NULL, 2, NULL),
(65, 'John Paolo A. Domingo', 'Esports Team', 7, NULL, 2, NULL),
(66, 'John Rey D. Ocfemia', 'Esports Team', 7, NULL, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `partylist`
--

CREATE TABLE `partylist` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partylist`
--

INSERT INTO `partylist` (`id`, `name`, `is_selected`) VALUES
(1, 'HIRAYA', 0),
(7, 'SIKLAB', 1);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(4, 'Faculty'),
(3, 'Officer'),
(2, 'Secretary');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'default-avatar.png',
  `is_active` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `middle_name`, `last_name`, `password`, `role_id`, `created_at`, `profile_picture`, `is_active`) VALUES
(1, 'seanjohn', 'Sean John', 'Agravante', 'Duque', '$2y$10$PW2iHIfvxRNbRoanqqvoeuOGdcuuno176izwY8aTGDSlVGPjKANO.', 1, '2025-03-26 21:54:11', 'profile_1749072173.jpg', 1),
(12, 'jocfems', 'John Rey', '', 'Ocfemia', '$2y$10$gjTWW5PYbLo/R.Vt5bWND.Aukrhg.xgR5qTOaF8tEMnybEJMIgefS', 1, '2025-09-19 06:33:11', 'default-avatar.png', 1),
(13, 'charlene_off', 'Charlene', 'D', 'Prado', '$2y$10$sFAF7kZF/IZUYNS04ycR9.jh9QnwOlGXr2Pluwplviy6OYGahNhm6', 3, '2025-09-19 09:58:34', 'default-avatar.png', 1),
(14, 'lexus_off', 'Lancaster Lexus', 'Y.', 'Cuevas', '$2y$10$R/hcDSHhwQHaFGTZUhtG8OfdufTfbOrgZrPiPivdzWDIwgAPSJnDC', 3, '2025-09-19 10:02:26', 'default-avatar.png', 1),
(15, 'jpao_off', 'John Paolo', 'A.', 'Domingo', '$2y$10$2IQ2E/JO33qwH1V3yCy4i.5cgv0LjOH9sXt1VFOPiB4DB5BCMFKF.', 3, '2025-09-19 10:03:41', 'default-avatar.png', 1),
(16, 'neil_off', 'Neil', '', 'Batas', '$2y$10$OqAHFNHy5AZjQzmm2oq/reX3I7H/ZngjYwIzsHezDcuHZCqEKGb2G', 2, '2025-09-19 10:29:30', 'default-avatar.png', 1),
(17, 'secretary_account', 'Secretary', '', 'Account', '$2y$10$VPmAYdz5tBvzR4Rlwaf1s.nzSGFKkZxa0iKSVxsXJ6YyD3DrPLqby', 2, '2025-09-21 05:20:14', 'default-avatar.png', 1),
(18, 'officer_account', 'Officer', '', 'Account', '$2y$10$xmFeaIXLPDt8oc5CqUuDMe29j.COo7izUAZm8jd7R454Vdenkcmfe', 3, '2025-09-21 05:22:04', 'default-avatar.png', 1),
(19, 'junefrancisco', 'June', 'D.', 'Francisco', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(20, 'jessiebenocas', 'Jessie', 'P.', 'Benocas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(21, 'benjamingandeza', 'Benjamin', 'B.', 'Gandeza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(22, 'arjaycristobal', 'Arjay', 'B.', 'Cristobal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(23, 'wilfredotomas', 'Wilfredo', 'O.', 'Tomas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(24, 'dianalynpagsanjan', 'Dianalyn', 'D.', 'Pagsanjan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(25, 'arvinretuya', 'Arvin', 'Jonathan M.', 'Retuya', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(26, 'edwardcruz', 'Edward', 'N.', 'Cruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(27, 'michaelmanalo', 'Michael', 'Angelo F.', 'Manalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(28, 'raymondmacatangga', 'Raymond', 'S.', 'Macatangga', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(29, 'jeffersonmalayao', 'Jefferson', 'M.', 'Malayao', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(30, 'alexislibunao', 'Alexis', 'A.', 'Libunao', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(31, 'alneslynbucud', 'Alneslyn', 'C.', 'Bucud', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(32, 'marissabautista', 'Marissa', 'M.', 'Bautista', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(33, 'pinkydacuso', 'Pinky', 'G.', 'Dacuso', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(34, 'reynolddelizo', 'Reynold', 'A.', 'Delizo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(35, 'mariaisok', 'Maria', 'Luzzel', 'Isok', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, '2025-10-22 14:57:49', 'default-avatar.png', 1),
(50, 'seanjohnduque', 'Sean John', '', 'Duque', '$2y$10$ui/HTKYXsoVuW37lkueoSOWOGf.7TZSp9AQNAohdd/wnpTAWC0OtK', 4, '2025-10-22 15:09:33', 'profile_1761147210.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_type`, `activity_details`, `created_at`) VALUES
(1, 1, 'status_updated', 'Admin deactivated account for user ID 3', '2025-05-13 01:48:30'),
(2, 1, 'status_updated', 'Admin activated account for user ID 3', '2025-05-13 01:48:35'),
(3, 1, 'status_updated', 'Admin deactivated account for user ID 3', '2025-05-13 01:49:43'),
(4, 1, 'status_updated', 'Admin activated account for user ID 3', '2025-05-13 01:50:36'),
(5, 1, 'status_updated', 'Admin deactivated account for user ID 3', '2025-05-13 01:50:39'),
(6, 1, 'status_updated', 'Admin deactivated account for user ID 2', '2025-05-13 01:50:42'),
(7, 1, 'status_updated', 'Admin activated account for user ID 3', '2025-05-13 01:50:43'),
(8, 1, 'status_updated', 'Admin activated account for user ID 2', '2025-05-13 01:50:45'),
(9, 1, 'account_created', 'Admin created new account for user: faineishi', '2025-05-13 02:07:03'),
(10, 1, 'account_created', 'Admin created new account for user: fiona_taba', '2025-05-13 02:08:47'),
(11, 1, 'account_created', 'Admin created new account for user: tabachoy', '2025-05-13 02:10:48'),
(12, 1, 'status_updated', 'Admin deactivated account for user ID 6', '2025-05-13 02:12:14'),
(13, 1, 'account_created', 'Admin created new account for user: Account Testing', '2025-05-13 02:12:40'),
(14, 1, 'status_updated', 'Admin activated account for user ID 6', '2025-05-13 02:12:48'),
(15, 1, 'status_updated', 'Admin deactivated account for user ID 7', '2025-05-13 03:43:39'),
(16, 1, 'status_updated', 'Admin activated account for user ID 7', '2025-05-14 15:41:51'),
(17, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:14'),
(18, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:15'),
(19, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:15'),
(20, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:15'),
(21, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:17'),
(22, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:18'),
(23, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:30'),
(24, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:30'),
(25, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:30'),
(26, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:39'),
(27, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:22:39'),
(28, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:23:46'),
(29, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:23:59'),
(30, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:24:51'),
(31, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:24:57'),
(32, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:27:39'),
(33, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:27:49'),
(34, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:29:03'),
(35, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:29:10'),
(36, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-14 16:31:30'),
(37, 1, 'account_created', 'Admin created new account for user: paopao', '2025-05-14 16:31:54'),
(38, 1, 'account_created', 'Admin created new account for user: lanlan', '2025-05-14 16:35:18'),
(39, 1, 'user_updated', 'Admin updated user information for user ID 8', '2025-05-14 16:36:17'),
(40, 1, 'account_created', 'Admin created new account for user: dsadadasdad', '2025-05-14 16:38:15'),
(41, 1, 'user_updated', 'Admin updated user information for user ID 1', '2025-05-16 05:38:07'),
(42, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-16 05:38:17'),
(43, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-16 05:38:24'),
(44, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-16 05:38:30'),
(45, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-16 05:38:33'),
(46, 1, 'user_updated', 'Admin updated user information for user ID 7', '2025-05-28 13:10:44'),
(47, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-28 13:10:54'),
(48, 1, 'user_updated', 'Admin updated user information for user ID 10', '2025-05-28 21:27:43'),
(49, 1, 'status_updated', 'Admin deactivated account for user ID 10', '2025-05-28 21:34:49'),
(50, 1, 'status_updated', 'Admin activated account for user ID 10', '2025-05-28 21:34:52'),
(51, 1, 'account_created', 'Admin created new account for user: johnjohn', '2025-05-28 23:17:04'),
(52, 1, 'status_updated', 'Admin deactivated account for user ID 11', '2025-06-04 21:35:11'),
(53, 1, 'status_updated', 'Admin activated account for user ID 11', '2025-06-04 21:38:16'),
(54, 1, 'account_created', 'Admin created new account for user: jocfems', '2025-09-19 06:33:11'),
(55, 12, 'account_created', 'Admin created new account for user: charlene_off', '2025-09-19 09:58:34'),
(56, 12, 'account_created', 'Admin created new account for user: lexus_off', '2025-09-19 10:02:26'),
(57, 12, 'account_created', 'Admin created new account for user: jpao_off', '2025-09-19 10:03:41'),
(58, 1, 'account_created', 'Admin created new account for user: neil_off', '2025-09-19 10:29:30'),
(59, 1, 'account_created', 'Admin created new account for user: secretary_account', '2025-09-21 05:20:14'),
(60, 1, 'account_created', 'Admin created new account for user: officer_account', '2025-09-21 05:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `last_activity`, `created_at`) VALUES
(318, 13, 'aqm7pq5rli773b9eo710k179hu', '122.54.201.25', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-09-19 10:53:03', '2025-09-19 09:59:59'),
(345, 12, '7vq6oab7uhtg29r90pggb0e2eq', '27.49.188.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 17:16:10', '2025-10-20 16:07:36'),
(381, 21, 'rni01dhifetu0spc1al3f8lg10', '27.49.188.135', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-22 18:11:46', '2025-10-22 17:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `welcome_message`
--

CREATE TABLE `welcome_message` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When the welcome message expires (NULL = never expires)',
  `auto_expire` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether to automatically expire this message'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `welcome_message`
--

INSERT INTO `welcome_message` (`id`, `message`, `is_active`, `created_by`, `created_at`, `updated_at`, `expires_at`, `auto_expire`) VALUES
(1, 'HELLO WORLD', 1, 1, '2025-09-09 14:22:10', '2025-09-19 02:24:03', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_announcement_fk` (`created_by`);

--
-- Indexes for table `announcement_media`
--
ALTER TABLE `announcement_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcement_media` (`announcement_id`,`media_id`),
  ADD KEY `announcement_multimedia_fk` (`media_id`);

--
-- Indexes for table `content_verification`
--
ALTER TABLE `content_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_content_verification_type` (`content_type`),
  ADD KEY `idx_content_verification_status` (`status`),
  ADD KEY `idx_content_verification_submitted_by` (`submitted_by`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_events_fk` (`created_by`),
  ADD KEY `events_multimedia_fk` (`media_id`),
  ADD KEY `idx_events_archived` (`is_archived`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_faculty_user` (`user_id`),
  ADD KEY `user_featured_fk` (`user_id`),
  ADD KEY `featured_multimedia_fk` (`media_id`),
  ADD KEY `idx_faculty_alphabetical` (`lname`,`fname`,`mname`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_logs_fk` (`created_by`);

--
-- Indexes for table `multimedia_content`
--
ALTER TABLE `multimedia_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_multimedia_fk` (`uploaded_by`);

--
-- Indexes for table `news_update`
--
ALTER TABLE `news_update`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_news_fk` (`published_by`),
  ADD KEY `news_multimedia_fk` (`media_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_auto_expire` (`auto_expire`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `officers_ibfk_1` (`partylist_id`),
  ADD KEY `officers_multimedia` (`multimedia_id`),
  ADD KEY `idx_hierarchy_level` (`hierarchy_level`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `partylist`
--
ALTER TABLE `partylist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_role_fk` (`role_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity_log_user_id` (`user_id`),
  ADD KEY `idx_user_activity_log_created_at` (`created_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session` (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `welcome_message`
--
ALTER TABLE `welcome_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `welcome_message_created_by_fk` (`created_by`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_auto_expire` (`auto_expire`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `announcement_media`
--
ALTER TABLE `announcement_media`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `content_verification`
--
ALTER TABLE `content_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=467;

--
-- AUTO_INCREMENT for table `multimedia_content`
--
ALTER TABLE `multimedia_content`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=335;

--
-- AUTO_INCREMENT for table `news_update`
--
ALTER TABLE `news_update`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `partylist`
--
ALTER TABLE `partylist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- AUTO_INCREMENT for table `welcome_message`
--
ALTER TABLE `welcome_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `user_announcement_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcement_media`
--
ALTER TABLE `announcement_media`
  ADD CONSTRAINT `announcement_multimedia_fk` FOREIGN KEY (`media_id`) REFERENCES `multimedia_content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_verification`
--
ALTER TABLE `content_verification`
  ADD CONSTRAINT `content_verification_ibfk_1` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `content_verification_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_multimedia_fk` FOREIGN KEY (`media_id`) REFERENCES `multimedia_content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_events_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `featured_multimedia_fk` FOREIGN KEY (`media_id`) REFERENCES `multimedia_content` (`id`),
  ADD CONSTRAINT `user_featured_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `user_logs_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `multimedia_content`
--
ALTER TABLE `multimedia_content`
  ADD CONSTRAINT `user_multimedia_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `news_update`
--
ALTER TABLE `news_update`
  ADD CONSTRAINT `news_multimedia_fk` FOREIGN KEY (`media_id`) REFERENCES `multimedia_content` (`id`),
  ADD CONSTRAINT `user_news_fk` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `officers`
--
ALTER TABLE `officers`
  ADD CONSTRAINT `fk_officer_parent` FOREIGN KEY (`parent_id`) REFERENCES `officers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `officers_ibfk_1` FOREIGN KEY (`partylist_id`) REFERENCES `partylist` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `officers_multimedia` FOREIGN KEY (`multimedia_id`) REFERENCES `multimedia_content` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `user_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `welcome_message`
--
ALTER TABLE `welcome_message`
  ADD CONSTRAINT `welcome_message_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
