-- ==========================================================
-- HỆ THỐNG QUẢN LÝ NỀN NẾP & TƯ VẤN TÂM LÝ LG3 (DATABASE SCHEMA)
-- Phiên bản: 2.0.0
-- Tương thích: MariaDB 10.6+ / MySQL 8.0+
-- ==========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: 192.168.1.105    Database: quan_ly_ne_nep
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `academic_score`
--

DROP TABLE IF EXISTS `academic_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `total_score` float DEFAULT NULL,
  `period_count` int(11) DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  `bonus_score` float DEFAULT 0,
  `school_year` varchar(15) DEFAULT '2026-2027',
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `idx_aca_class_week_year` (`class_id`,`week_number`,`school_year`),
  KEY `idx_aca_week_year` (`week_number`,`school_year`),
  CONSTRAINT `academic_score_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classroom` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=406 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banned_ips`
--

DROP TABLE IF EXISTS `banned_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banned_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `banned_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `career_advice_logs`
--

DROP TABLE IF EXISTS `career_advice_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `career_advice_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `user_query` text NOT NULL,
  `ai_response` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `school_year` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chess_matches`
--

DROP TABLE IF EXISTS `chess_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chess_matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player1_id` int(11) NOT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'PENDING',
  `fen` varchar(100) NOT NULL DEFAULT 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
  `turn_user_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `player1_id` (`player1_id`),
  KEY `player2_id` (`player2_id`),
  KEY `turn_user_id` (`turn_user_id`),
  KEY `winner_id` (`winner_id`),
  CONSTRAINT `chess_matches_ibfk_1` FOREIGN KEY (`player1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chess_matches_ibfk_2` FOREIGN KEY (`player2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chess_matches_ibfk_3` FOREIGN KEY (`turn_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chess_matches_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classroom`
--

DROP TABLE IF EXISTS `classroom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classroom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `grade` int(11) NOT NULL,
  `competition_group` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consulting_questions`
--

DROP TABLE IF EXISTS `consulting_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consulting_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_code` varchar(20) NOT NULL,
  `test_type` varchar(20) NOT NULL,
  `group_code` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `en_content` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type_group` (`test_type`,`group_code`)
) ENGINE=InnoDB AUTO_INCREMENT=366 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `violation_record` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `friendships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_1` int(11) NOT NULL,
  `user_id_2` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg3_exam_config`
--

DROP TABLE IF EXISTS `lg3_exam_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lg3_exam_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `subject_key` varchar(100) NOT NULL,
  `col_code` varchar(10) NOT NULL,
  `score_type` enum('TN','TL','TONG') DEFAULT 'TONG',
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_col_idx` (`exam_id`,`col_code`)
) ENGINE=InnoDB AUTO_INCREMENT=418 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg3_exam_scores`
--

DROP TABLE IF EXISTS `lg3_exam_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lg3_exam_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `sbd` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  `dob` varchar(50) DEFAULT NULL,
  `col1` float DEFAULT 0,
  `col2` float DEFAULT 0,
  `col3` float DEFAULT 0,
  `col4` float DEFAULT 0,
  `col5` float DEFAULT 0,
  `col6` float DEFAULT 0,
  `col7` float DEFAULT 0,
  `col8` float DEFAULT 0,
  `col9` float DEFAULT 0,
  `col10` float DEFAULT 0,
  `col11` float DEFAULT 0,
  `col12` float DEFAULT 0,
  `col13` float DEFAULT 0,
  `col14` float DEFAULT 0,
  `col15` float DEFAULT 0,
  `col16` float DEFAULT 0,
  `col17` float DEFAULT 0,
  `col18` float DEFAULT 0,
  `col19` float DEFAULT 0,
  `col20` float DEFAULT 0,
  `col21` float DEFAULT 0,
  `col22` float DEFAULT 0,
  `col23` float DEFAULT 0,
  `col24` float DEFAULT 0,
  `col25` float DEFAULT 0,
  `col26` float DEFAULT 0,
  `col27` float DEFAULT 0,
  `col28` float DEFAULT 0,
  `col29` float DEFAULT 0,
  `col30` float DEFAULT 0,
  `col31` float DEFAULT 0,
  `col32` float DEFAULT 0,
  `col33` float DEFAULT 0,
  `col34` float DEFAULT 0,
  `col35` float DEFAULT 0,
  `col36` float DEFAULT 0,
  `col37` float DEFAULT 0,
  `col38` float DEFAULT 0,
  `col39` float DEFAULT 0,
  `col40` float DEFAULT 0,
  `col41` float DEFAULT 0,
  `col42` float DEFAULT 0,
  `col43` float DEFAULT 0,
  `col44` float DEFAULT 0,
  `col45` float DEFAULT 0,
  `col46` float DEFAULT 0,
  `col47` float DEFAULT 0,
  `col48` float DEFAULT 0,
  `col49` float DEFAULT 0,
  `col50` float DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sbd_exam_idx` (`sbd`,`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7282 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg3_exams`
--

DROP TABLE IF EXISTS `lg3_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lg3_exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(255) NOT NULL,
  `exam_name_en` varchar(255) NOT NULL DEFAULT '',
  `school_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'tin_tuc',
  `content` longtext NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `attachment_url` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news_comments`
--

DROP TABLE IF EXISTS `news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `news_id` (`news_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `news_comments_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `news_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_queue`
--

DROP TABLE IF EXISTS `notification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT 'VIOLATION',
  `payload` text NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=883 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `psychology_logs`
--

DROP TABLE IF EXISTS `psychology_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `psychology_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `question` text NOT NULL,
  `advice` text NOT NULL,
  `risk_level` varchar(20) DEFAULT 'NORMAL',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `school_year` varchar(15) DEFAULT '2026-2027',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `psychology_messages`
--

DROP TABLE IF EXISTS `psychology_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `psychology_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reply_id` int(11) DEFAULT NULL,
  `reactions` varchar(50) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `psychology_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `psychology_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_subscription`
--

DROP TABLE IF EXISTS `push_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) DEFAULT NULL,
  `auth` varchar(255) DEFAULT NULL,
  `device_model` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `platform` varchar(20) DEFAULT 'web',
  `session_id` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_platform` (`user_id`,`platform`),
  KEY `idx_session` (`session_id`),
  CONSTRAINT `push_subscription_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sse_events`
--

DROP TABLE IF EXISTS `sse_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sse_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(64) NOT NULL,
  `payload` mediumtext NOT NULL,
  `scope` varchar(64) DEFAULT 'all',
  `created_at` timestamp(3) NULL DEFAULT current_timestamp(3),
  PRIMARY KEY (`id`),
  KEY `idx_id` (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1713 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE push event queue';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_url` varchar(200) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `has_exemption` tinyint(1) DEFAULT NULL,
  `exemption_reason` varchar(200) DEFAULT NULL,
  `dob` varchar(50) DEFAULT NULL,
  `pending_name` varchar(100) DEFAULT NULL,
  `pending_dob` varchar(50) DEFAULT NULL,
  `has_pending_changes` tinyint(1) DEFAULT 0,
  `facebook_url` varchar(255) DEFAULT NULL,
  `tiktok_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `zalo_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `threads_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `class_id` (`class_id`),
  KEY `idx_student_name` (`name`(50)),
  KEY `idx_student_code_class` (`class_id`,`code`)
) ENGINE=InnoDB AUTO_INCREMENT=100001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subject`
--

DROP TABLE IF EXISTS `subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_results`
--

DROP TABLE IF EXISTS `test_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `test_type` varchar(50) NOT NULL,
  `result_data` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `school_year` varchar(15) DEFAULT '2026-2027',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4838 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `typing_status`
--

DROP TABLE IF EXISTS `typing_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `typing_status` (
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `last_typed` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sender_id`,`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `token_selector` varchar(255) DEFAULT NULL,
  `last_active` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `selector` varchar(255) NOT NULL,
  `hashed_validator` varchar(128) DEFAULT NULL,
  `expiry` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_tokens_users` (`user_id`),
  CONSTRAINT `fk_user_tokens_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'TEACHER',
  `homeroom_class_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `is_default_password` varchar(10) NOT NULL DEFAULT 'on',
  `two_factor_secret` varchar(100) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8482 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `violation_record`
--

DROP TABLE IF EXISTS `violation_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `violation_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_created` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL COMMENT 'Thá»i gian thá»±c táº¿ báº¥m nÃºt LÆ°u',
  `student_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `violation_type_id` int(11) DEFAULT NULL,
  `recorded_violation_name` varchar(200) DEFAULT NULL,
  `recorded_points` float DEFAULT NULL,
  `reporter` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `week_number` int(11) DEFAULT 1,
  `evidence_img` varchar(200) DEFAULT NULL,
  `school_year` varchar(15) DEFAULT '2026-2027',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  KEY `violation_type_id` (`violation_type_id`),
  KEY `idx_vio_class_week_year` (`class_id`,`week_number`,`school_year`,`is_deleted`),
  KEY `idx_vio_type_school_year` (`violation_type_id`,`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=1466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `violation_type`
--

DROP TABLE IF EXISTS `violation_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `violation_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(200) NOT NULL,
  `content_en` varchar(200) DEFAULT NULL,
  `points` float NOT NULL,
  `scope` varchar(20) DEFAULT NULL,
  `short_code` varchar(20) DEFAULT NULL,
  `max_penalty_points` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=583 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-08-25 20:59:08


/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: 192.168.1.105    Database: quan_ly_ne_nep
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `config`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES
(1,'start_date','2026-08-30'),
(2,'excluded_dates','[]'),
(3,'ota_auto_update','0'),
(4,'ota_delay_days','0'),
(13,'end_hk1_date','2027-01-15'),
(14,'end_year_date','2027-05-31'),
(15,'ranking_rules','{\"max_base\":60,\"divisor\":6,\"weight_aca\":0.5,\"weight_con\":0.5}'),
(17,'ticker_school',''),
(51,'current_school_year','2026-2027'),
(52,'start_date_of_year','2026-08-30'),
(53,'start_date_2025-2026','2025-08-30'),
(54,'excluded_dates_2025-2026','[\"2026-02-14\",\"2026-02-15\",\"2026-02-16\",\"2026-02-17\",\"2026-02-18\",\"2026-02-19\",\"2026-02-20\"]'),
(55,'end_hk1_date_2025-2026','2026-01-11'),
(56,'end_year_date_2025-2026','2026-05-31'),
(57,'start_date_2026-2027','2026-08-30'),
(58,'excluded_dates_2026-2027','[]'),
(59,'end_hk1_date_2026-2027','2027-01-15'),
(60,'end_year_date_2026-2027','2027-05-31');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `violation_type`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `violation_type` WRITE;
/*!40000 ALTER TABLE `violation_type` DISABLE KEYS */;
INSERT INTO `violation_type` VALUES
(47,'Đi học muộn','Late to school',0.2,'GATE','DIMUON',NULL),
(48,'Không sơ vin','Not tucking in shirt',0.5,'GATE','KSOVIN',NULL),
(49,'Đi dép lê/dép không quai','Wearing slippers/sandals without straps',0.5,'GATE','DEPLE',NULL),
(50,'Không đeo thẻ học sinh','Not wearing student ID badge',0.5,'GATE','KTHE',NULL),
(51,'Đeo thẻ muộn (Kiểm tra mới đeo)','Wearing badge late (only when checked)',0.2,'GATE','DTM',NULL),
(52,'Không mặc áo đồng phục','Not wearing school uniform',0.5,'GATE','KDP',NULL),
(53,'Không đội mũ bảo hiểm','Not wearing a helmet',0.5,'GATE','MBH',NULL),
(54,'Vi phạm khác','Other violations',0.5,'GATE','KHAC_GATE',NULL),
(72,'Đồng phục','Uniform',2,'CLASS','DP',2),
(73,'Sĩ số và Tập trung','Attendance/Assembly',1,'CLASS','SS',1),
(74,'Vệ sinh','Cleanliness/Sanitation',1,'CLASS','VS',1),
(75,'Bảo vệ cơ sở vật chất','Protecting School Property',1,'CLASS','CSVC',1),
(76,'Truy bài','Study Session check',1,'CLASS','TB',1),
(77,'Xếp xe và An toàn giao thông','Parking, Traffic Safety',1,'CLASS','XE',1),
(78,'Sơ vin','Tuck in shirt',1,'CLASS','SV',1),
(79,'Đeo thẻ học sinh và huy hiệu','Student ID/Badge',1,'CLASS','THE',1),
(80,'Đầu tóc và Giày dép','Hair/Footwear',1,'CLASS','DT',1);
/*!40000 ALTER TABLE `violation_type` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping data for table `subject`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `subject` WRITE;
/*!40000 ALTER TABLE `subject` DISABLE KEYS */;
INSERT INTO `subject` VALUES
(3,'Toán','TOAN'),
(4,'Ngữ Văn','VAN'),
(5,'Tiếng Anh','ANH'),
(6,'Vật Lý','LY'),
(7,'Hóa Học','HOA'),
(8,'Sinh Học','SINH'),
(10,'Lịch Sử','SU'),
(11,'Địa Lý','DIA'),
(12,'Giáo Dục Kinh Tế và Pháp Luật','KTPL'),
(13,'Công Nghệ Công Nghiệp','CNCN'),
(14,'Công Nghệ Nông Nghiệp','CNNN'),
(15,'Tin Học','TIN');
/*!40000 ALTER TABLE `subject` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-08-25 20:59:57



-- ==========================================================
-- DỮ LIỆU MẪU BAN ĐẦU (SAMPLE DATA)
-- ==========================================================

-- 1. Lớp học mẫu
INSERT INTO `classroom` (`id`, `name`, `grade`, `competition_group`) VALUES
(1, '10A1', 10, 1),
(2, '10A2', 10, 1),
(3, '11A1', 11, 2),
(4, '12A1', 12, 3);

-- 2. Học sinh mẫu
INSERT INTO `student` (`id`, `code`, `name`, `class_id`, `dob`, `has_pending_changes`) VALUES
(1, '10A101', 'Nguyễn Văn An', 1, '2010-01-15', 0),
(2, '10A102', 'Trần Thị Bình', 1, '2010-05-20', 0),
(3, '11A101', 'Lê Hoàng Cường', 3, '2009-08-10', 0),
(4, '12A101', 'Phạm Minh Đức', 4, '2008-11-25', 0);

-- 3. Tài khoản người dùng mẫu
-- Mật khẩu Admin: Admin@123
-- Mật khẩu Giáo viên: Teacher@123
-- Mật khẩu Học sinh: Student@123
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `homeroom_class_id`, `full_name`, `email`, `email_verified`, `is_default_password`) VALUES
(1, 'admin', '$2y$12$ceHt7ZxEqzq2Bz9WfwXAw.o2Sq6TTbBjjw/cJGksD3h2FMdL2qdf2', 'ADMIN', NULL, 'Quản Trị Viên Hệ Thống', 'admin@example.com', 1, 'off'),
(2, 'gv_chunhiem', '$2y$12$u1hV3T5v8fJmVHtgUN0qG.lNoK4aSVVCVyLDZwmjmY9z129E2xTca', 'TEACHER', 1, 'Thầy Giáo Demo (CN 10A1)', 'teacher@example.com', 1, 'off'),
(3, '10a101', '$2y$12$59oHcRnMDilpYtVzFHr8t.X1Sx7DkI8xxAKCdCwUTZc9xVV2Zd3Ae', 'STUDENT', NULL, 'Nguyễn Văn An', 'student@example.com', 1, 'off');


SET FOREIGN_KEY_CHECKS=1;
COMMIT;
