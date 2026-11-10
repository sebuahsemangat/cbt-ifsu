-- ========================================================
-- CBT Simulation - Clean Database Initialization
-- File: cbt_clean_install.sql
-- Dibuat untuk setup komputer CBT baru.
-- Data ujian, soal, stimulus, dan siswa akan ditarik dari Cloud (cbt.page.gd).
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `cbt_ifsu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cbt_ifsu`;

-- --------------------------------------------------------
-- Struktur tabel: `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `cbt_survey_responden_local`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cbt_survey_responden_local`;
CREATE TABLE `cbt_survey_responden_local` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `kode_akses` varchar(64) NOT NULL,
  `teacher_name` varchar(150) DEFAULT NULL,
  `nama_siswa` varchar(100) DEFAULT NULL,
  `kelas_siswa` varchar(100) DEFAULT NULL,
  `nisn` varchar(30) DEFAULT NULL,
  `is_anonim` tinyint(1) DEFAULT 0,
  `jawaban_json` longtext NOT NULL,
  `waktu_isi` datetime DEFAULT current_timestamp(),
  `synced` tinyint(1) DEFAULT 0,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_synced` (`synced`),
  KEY `idx_student_subject` (`student_id`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `cbt_surveys_local`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cbt_surveys_local`;
CREATE TABLE `cbt_surveys_local` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id_cloud` int(11) NOT NULL,
  `kode_akses` varchar(64) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `guru_id_cloud` int(11) DEFAULT NULL,
  `guru_nama` varchar(150) NOT NULL,
  `guru_nip` varchar(50) DEFAULT NULL,
  `mata_pelajaran` varchar(100) DEFAULT NULL,
  `kelas_sasaran` varchar(100) DEFAULT NULL,
  `total_pertanyaan` int(11) DEFAULT 0,
  `payload_json` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_akses` (`kode_akses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `exam_classes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exam_classes`;
CREATE TABLE `exam_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_class` (`exam_id`,`class_name`),
  KEY `idx_exam_id` (`exam_id`),
  KEY `idx_class_name` (`class_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `exam_sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exam_sessions`;
CREATE TABLE `exam_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `remaining_seconds` int(11) DEFAULT NULL,
  `score` decimal(10,2) DEFAULT 0.00,
  `violation_count` int(11) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `question_order` text DEFAULT NULL,
  `status` enum('ongoing','finished') DEFAULT 'ongoing',
  `synced` tinyint(1) NOT NULL DEFAULT 0,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `fk_session_exam` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `exams`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `allow_review` tinyint(1) DEFAULT 0,
  `randomize_questions` tinyint(1) DEFAULT 1,
  `show_score` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `options`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `questions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `question_text` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `question_type` enum('pg','bs','multi','isian') NOT NULL,
  `answer_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `references`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `references`;
CREATE TABLE `references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('text','image','sound') DEFAULT 'text',
  `content` longtext DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `sound_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_title` (`subject_id`,`title`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `scores`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `scores`;
CREATE TABLE `scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `total_score` decimal(5,2) NOT NULL,
  `completed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `student_answers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `student_answers`;
CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_data` text DEFAULT NULL,
  `is_doubt` tinyint(1) DEFAULT 0,
  `time_spent` int(11) DEFAULT 0,
  `option_id` int(11) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_answer` (`session_id`,`question_id`,`option_id`),
  KEY `question_id` (`question_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `student_surveys`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `student_surveys`;
CREATE TABLE `student_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `survey_code` varchar(64) NOT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `status` enum('submitted','failed','skipped') DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_subject_survey` (`student_id`,`subject_id`),
  KEY `idx_exam_id` (`exam_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `fk_student_surveys_subject` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `students`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `kelompok` varchar(50) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `is_logged_in` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `subject_surveys`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subject_surveys`;
CREATE TABLE `subject_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `survey_code` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_survey_code` (`survey_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `subjects`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL,
  `max_score` decimal(10,2) DEFAULT 100.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_subject_exam` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel: `tokens`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tokens`;
CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `token_code` char(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Data akun Administrator: `admins`
-- (Wajib ada agar dapat login ke admin panel untuk sync)
-- --------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(5, 'admin', '$2y$10$Z6Tc2ohFR7PWxXv1Q1CyXuqx5ILWX8ktTrlK59seB4CKfnzLP.gP6', 'Administrator', '2026-10-13 12:06:42');

-- --------------------------------------------------------
-- Data Pengaturan Sistem: `settings`
-- (Wajib ada agar terhubung ke server cloud cbt.page.gd)
-- --------------------------------------------------------
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'app_name', 'CBT - Online Exam', '2026-04-20 15:19:12', '2026-10-13 12:07:26'),
(2, 'bimbel_name', 'SMK Informatika Sumedang', '2026-04-20 15:19:12', '2026-10-13 12:07:26'),
(3, 'bimbel_logo', 'logo-bimbel-1791870361.png', '2026-04-20 15:19:12', '2026-10-30 15:37:12'),
(12, 'theme_color', '#b39b00', '2026-04-23 10:23:29', '2026-04-23 10:24:03'),
(50, 'allow_student_change_password', '0', '2026-10-17 16:13:23', '2026-10-17 16:13:57'),
(54, 'min_exam_duration_percent', '50', '2026-10-22 11:45:05', '2026-10-22 11:45:05'),
(55, 'enable_anti_cheating', '0', '2026-10-22 12:18:26', '2026-10-30 13:08:31'),
(73, 'cbt_survey_sync_url', 'http://sihonor.page.gd', '2026-10-29 14:05:02', '2026-10-29 14:05:02'),
(74, 'cbt_survey_api_key', 'cbt_45f623df45b432f817e7d5a033e2641003a85f4fcbb188d5', '2026-10-29 14:05:02', '2026-10-29 14:05:02'),
(76, 'cbt_survey_last_pull', '2026-10-29 17:04:40', '2026-10-29 17:04:40', '2026-10-29 17:04:40'),
(78, 'cbt_cloud_sync_url', 'http://cbt.page.gd', '2026-10-30 12:57:23', '2026-10-30 12:57:23'),
(79, 'cbt_cloud_sync_token', 'cbt-cloud-secret-key-2026', '2026-10-30 12:57:23', '2026-10-30 12:57:23'),
(80, 'cbt_cloud_last_upload', NULL, '2026-10-30 12:57:23', '2026-10-30 16:06:48');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;