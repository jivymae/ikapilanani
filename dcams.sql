-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 28, 2024 at 12:07 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dcams`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `dentist_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_status` enum('approved','pending','cancelled','completed') NOT NULL DEFAULT 'pending',
  `appointment_created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `dentist_id`, `appointment_date`, `appointment_time`, `appointment_status`, `appointment_created_at`) VALUES
(37, 8, 12, '2024-09-28', '10:00:00', 'completed', '2024-09-27 23:39:16'),
(38, 8, 12, '2024-10-05', '03:30:00', 'completed', '2024-09-27 23:57:15');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_services`
--

DROP TABLE IF EXISTS `appointment_services`;
CREATE TABLE IF NOT EXISTS `appointment_services` (
  `appointment_service_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  PRIMARY KEY (`appointment_service_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appointment_services`
--

INSERT INTO `appointment_services` (`appointment_service_id`, `appointment_id`, `service_id`) VALUES
(22, 38, 3),
(21, 37, 2),
(20, 36, 2);

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

DROP TABLE IF EXISTS `barangays`;
CREATE TABLE IF NOT EXISTS `barangays` (
  `barangay_id` int NOT NULL AUTO_INCREMENT,
  `barangay_name` varchar(100) NOT NULL,
  `city_id` int DEFAULT NULL,
  PRIMARY KEY (`barangay_id`),
  KEY `city_id` (`city_id`)
) ENGINE=MyISAM AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `barangay_name`, `city_id`) VALUES
(240, 'Tumpagon', 2),
(239, 'Tuburan', 2),
(238, 'Tignapoloan', 2),
(237, 'Tagpangi', 2),
(236, 'Taglimao', 2),
(235, 'Tablon', 2),
(234, 'San Simon', 2),
(233, 'Puntod', 2),
(232, 'Puerto', 2),
(231, 'Pigsag-an', 2),
(230, 'Patag', 2),
(229, 'Pagatpat', 2),
(228, 'Pagalungan', 2),
(227, 'Nazareth', 2),
(226, 'Mambuaya', 2),
(225, 'Macasandig', 2),
(224, 'Macabalan', 2),
(223, 'Lumbia', 2),
(222, 'Lapasan', 2),
(221, 'Kauswagan', 2),
(220, 'Iponan', 2),
(219, 'Indahag', 2),
(218, 'Gusa', 2),
(217, 'F. S. Catanico', 2),
(216, 'Dansolihon', 2),
(215, 'Cugman', 2),
(214, 'Consolacion', 2),
(213, 'Carmen', 2),
(212, 'Canito-an', 2),
(211, 'Camaman-an', 2),
(210, 'Bulua', 2),
(209, 'Bugo', 2),
(208, 'Bonbon', 2),
(207, 'Besigan', 2),
(206, 'Bayanga', 2),
(205, 'Bayabas', 2),
(204, 'Barangay 40', 1),
(203, 'Barangay 39', 1),
(202, 'Barangay 38', 1),
(201, 'Barangay 37', 1),
(200, 'Barangay 36', 1),
(199, 'Barangay 35', 1),
(198, 'Barangay 34', 1),
(197, 'Barangay 33', 1),
(196, 'Barangay 32', 1),
(195, 'Barangay 31', 1),
(194, 'Barangay 30', 1),
(193, 'Barangay 29', 1),
(192, 'Barangay 28', 1),
(191, 'Barangay 27', 1),
(190, 'Barangay 26', 1),
(189, 'Barangay 25', 1),
(188, 'Barangay 24', 1),
(187, 'Barangay 23', 1),
(186, 'Barangay 22', 1),
(185, 'Barangay 21', 1),
(184, 'Barangay 20', 1),
(183, 'Barangay 19', 1),
(182, 'Barangay 18', 1),
(181, 'Barangay 17', 1),
(180, 'Barangay 16', 1),
(179, 'Barangay 15', 1),
(178, 'Barangay 14', 1),
(177, 'Barangay 13', 1),
(176, 'Barangay 12', 1),
(175, 'Barangay 11', 1),
(174, 'Barangay 10', 1),
(173, 'Barangay 9', 1),
(172, 'Barangay 8', 1),
(171, 'Barangay 7', 1),
(170, 'Barangay 6', 1),
(169, 'Barangay 5', 1),
(168, 'Barangay 4', 1),
(167, 'Barangay 3', 1),
(166, 'Barangay 2', 1),
(165, 'Barangay 1', 1),
(164, 'Balulang', 1),
(163, 'Balubal', 1),
(162, 'Baikingon', 1),
(161, 'Agusan', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_appointments`
--

DROP TABLE IF EXISTS `cancelled_appointments`;
CREATE TABLE IF NOT EXISTS `cancelled_appointments` (
  `appointment_id` int NOT NULL,
  `patient_id` int NOT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
CREATE TABLE IF NOT EXISTS `cities` (
  `city_id` int NOT NULL AUTO_INCREMENT,
  `city_name` varchar(100) NOT NULL,
  `municipality_id` int DEFAULT NULL,
  PRIMARY KEY (`city_id`),
  KEY `municipality_id` (`municipality_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`city_id`, `city_name`, `municipality_id`) VALUES
(1, 'Cagayan de Oro City', 1);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
CREATE TABLE IF NOT EXISTS `countries` (
  `country_id` int NOT NULL AUTO_INCREMENT,
  `country_name` varchar(100) NOT NULL,
  PRIMARY KEY (`country_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`country_id`, `country_name`) VALUES
(1, 'Philippines');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('read','unread') DEFAULT 'unread',
  `attachments` varchar(255) DEFAULT NULL,
  `reply_to` int DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `conversation_id` int DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `user_id` (`user_id`),
  KEY `recipient_id` (`recipient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `user_id`, `recipient_id`, `subject`, `body`, `created_at`, `status`, `attachments`, `reply_to`, `is_deleted`, `priority`, `updated_at`, `conversation_id`) VALUES
(84, 12, 9, '', 'ddsfdaad', '2024-09-27 14:23:01', '', NULL, NULL, 0, 'medium', '2024-09-27 14:23:01', NULL),
(82, 12, 7, '', 'the', '2024-09-25 14:26:51', '', 'uploads/look.png', NULL, 0, 'medium', '2024-09-25 14:26:51', NULL),
(83, 7, 12, '', 'okaayy', '2024-09-25 16:28:10', 'unread', NULL, NULL, 0, 'medium', '2024-09-25 16:28:10', NULL),
(80, 12, 7, '10:30', 'jdnsjcsbtehdbe hfhew', '2024-09-25 06:25:49', 'read', NULL, NULL, 0, 'medium', '2024-09-25 14:25:51', NULL),
(81, 12, 9, 'fsds', 'safaf', '2024-09-25 06:26:15', 'read', 'uploads/dcams pass.png', NULL, 0, 'medium', '2024-09-25 14:26:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `municipalities`
--

DROP TABLE IF EXISTS `municipalities`;
CREATE TABLE IF NOT EXISTS `municipalities` (
  `municipality_id` int NOT NULL AUTO_INCREMENT,
  `municipality_name` varchar(100) NOT NULL,
  `region_id` int DEFAULT NULL,
  PRIMARY KEY (`municipality_id`),
  KEY `country_id` (`region_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `municipalities`
--

INSERT INTO `municipalities` (`municipality_id`, `municipality_name`, `region_id`) VALUES
(1, 'Misamis Oriental', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_token` (`reset_token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_token`, `expires_at`) VALUES
(1, 7, '4a55858a44234647d2c27045f927525f', '2024-09-15 15:35:08'),
(2, 7, 'be363dcb34b906d2409b0e2940250e7e', '2024-09-15 15:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

DROP TABLE IF EXISTS `password_reset_requests`;
CREATE TABLE IF NOT EXISTS `password_reset_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `request_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','completed') DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `username`, `request_time`, `status`) VALUES
(1, 'ice', '2024-09-15 23:12:33', 'completed'),
(2, 'jandy', '2024-09-15 23:24:38', 'completed'),
(3, 'jil', '2024-09-15 23:25:00', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `patient_records`
--

DROP TABLE IF EXISTS `patient_records`;
CREATE TABLE IF NOT EXISTS `patient_records` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `record_date` date NOT NULL,
  `record_details` text,
  `dentist_id` int NOT NULL,
  PRIMARY KEY (`record_id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_records`
--

INSERT INTO `patient_records` (`record_id`, `patient_id`, `record_date`, `record_details`, `dentist_id`) VALUES
(1, 8, '2024-09-27', 'Appointment completed', 12);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','not paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `appointment_id`, `patient_id`, `payment_amount`, `payment_status`, `payment_date`, `payment_method`) VALUES
(36, 38, 8, 150.00, 'paid', '2024-09-27 23:57:15', 'Cash'),
(35, 37, 8, 150.00, 'paid', '2024-09-27 23:39:16', 'Cash'),
(34, 36, 8, 150.00, 'paid', '2024-09-27 23:37:14', 'GCash');

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
CREATE TABLE IF NOT EXISTS `regions` (
  `region_id` int NOT NULL AUTO_INCREMENT,
  `region_name` varchar(100) NOT NULL,
  PRIMARY KEY (`region_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`region_id`, `region_name`) VALUES
(1, '10');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`) VALUES
(1, 'Teeth Cleaning (Prophylaxis)'),
(2, 'Dental Exams and X-rays'),
(3, 'Fillings'),
(4, 'Teeth Whitening'),
(5, 'Root Canal Therapy'),
(6, 'Dental Crowns'),
(7, 'Veneers'),
(8, 'Orthodontic Treatment (Braces, Aligners)'),
(9, 'Dentures'),
(10, 'Tooth Extractions'),
(11, 'Periodontal Treatment'),
(12, 'Sealants'),
(13, 'Night Guards/Mouth Guards'),
(14, 'Fluoride Treatments');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_records`
--

DROP TABLE IF EXISTS `treatment_records`;
CREATE TABLE IF NOT EXISTS `treatment_records` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int DEFAULT NULL,
  `treatment_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `treatment_records`
--

INSERT INTO `treatment_records` (`record_id`, `appointment_id`, `treatment_notes`, `created_at`) VALUES
(1, 37, 'Biogesic 5 times a day \r\nhehre\r\nrkerejw', '2024-09-27 15:46:54'),
(2, 38, 'hiivytyi', '2024-09-27 15:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','dentist','patient') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `barangay_id` int DEFAULT NULL,
  `address` text,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_barangay` (`barangay_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `created_at`, `email`, `first_name`, `last_name`, `phone`, `barangay_id`, `address`, `profile_image`, `reset_code`, `reset_code_expiry`) VALUES
(8, 'ice', '$2y$10$nDDKolRUHTmIIajpg8DVKeiyQtM574O.PxTa1X7XqSBwRn9YkfGnm', 'patient', '2024-09-14 12:45:31', 'ice@gmail.com', 'ice', 'eme', '09197510033', 205, 'zone 2 sampaguita', 'uploads/1678033129626.jpg', '5c0d35d818', '2024-09-15 16:00:16'),
(6, 'admin', '$2y$10$iF0BHSej1BaV4cSvIW1vWeMlwjVCihfaANYDXaEAMAaU5cOmok9aW', 'admin', '2024-09-14 11:54:26', 'admin@gmail.com', 'admin', 'admin', '', NULL, NULL, NULL, NULL, NULL),
(7, 'rere', '$2y$10$c2IKdYLkOegnP1GEh5zo7eZrtjRUZkLqxWzsWaXl4j7kIjrexRD5i', 'patient', '2024-09-14 12:29:37', 'rereacaylarr@gmail.com', 'rere', 'aca', '09197510039', 196, 'zone 1 buara', 'uploads/1678033129626.jpg', NULL, NULL),
(9, 'jandybeloved', '$2y$10$YgBcLeUhu7ICsAptLLKtOOE/aFpVqlMJ829tYTijF6jTjezE.jBse', 'patient', '2024-09-14 12:47:34', 'jandy@gmail.com', 'jandy', 'cometa', '09654014426', 205, 'zone 1', 'uploads/4.jpg', 'sIEhfR', '2024-09-16 13:27:20'),
(10, 'jil', '$2y$10$3RXSn8sTj.GnzgIFiFYG7eEoQhg/Ro6ed5R5tHE9SRqAWsgonLWFq', 'patient', '2024-09-14 12:57:00', 'jillian@yahoo.com', 'jillian', 'cometa', '', NULL, NULL, NULL, 'X4D7RD', '2024-09-16 11:53:55'),
(11, 'root', '$2y$10$FgGxbKedal.SJ6W/DhN3LO4NdeaDh/VJt7tJuWDW27jqoaxD3cBNu', 'patient', '2024-09-16 10:36:43', 'r@gmail.com', 'root', 'square', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'jivy', '$2y$10$Xb7avNUxSwRa/l9eLv00W.yPFRWCb.7O97ug7teCk2H5Z6yY1cq9W', 'dentist', '2024-09-16 12:15:27', 'katsukimishima0@gmail.com', 'jivy', 'cometa', '09066903182', 205, 'Zone1 Buara Bayabas,CDO', '', NULL, NULL),
(13, 'happy', '$2y$10$AgAmB3GlutpAMWFMQtj04e.Bsas8ewGRL4EtmH.ZWnYDcJWc70/re', 'dentist', '2024-09-24 16:45:44', 'happy@gmail.com', 'happy', 'happy ', '09066903182', 231, 'Zone1 Buara Bayabas,CDO', '', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
