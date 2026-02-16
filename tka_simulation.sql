-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 16, 2026 at 08:32 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tka_simulation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'lita', '$2y$10$sAKcIpqwblGxlp59Cyx1l.Xyv8vYcQxRdftooRY9C5T8n.WnBIPX.', 'Lita', '2026-01-29 08:07:37'),
(4, 'lingga', '$2y$10$iNx62TujJw6zWo0IejnqsugxKfvvpHOKP1Wlg/4mzS07YQK73a93e', 'Lingga Tampomas', '2026-02-16 06:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_name`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(8, 'Simulasi UTBK', '2026-02-13 08:58:00', '2026-02-17 08:58:00', 1, '2026-02-13 01:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `score` decimal(10,2) DEFAULT '0.00',
  `status` enum('ongoing','finished') DEFAULT 'ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_sessions`
--

INSERT INTO `exam_sessions` (`id`, `student_id`, `exam_id`, `subject_id`, `start_time`, `end_time`, `score`, `status`) VALUES
(81, 15, 8, 11, '2026-02-16 14:37:10', '2026-02-16 14:40:31', 886.12, 'finished');

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` int NOT NULL,
  `question_id` int NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int NOT NULL,
  `subject_id` int NOT NULL,
  `reference_id` int DEFAULT NULL,
  `question_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `question_type` enum('pg','bs','multi','isian') NOT NULL,
  `answer_data` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `subject_id`, `reference_id`, `question_text`, `created_at`, `question_type`, `answer_data`) VALUES
(26, 11, 14, '<p><span style=\"color: rgb(44, 49, 58);\">Berdasarkan grafik di atas, total penjualan pakaian terbanyak terjadi pada hari ….</span></p>', '2026-02-13 02:11:50', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>Rabu</p>\", \"B\": \"<p>Selasa</p>\", \"C\": \"<p>Jumat</p>\", \"D\": \"<p>Sabtu</p>\", \"E\": \"<p>Kamis</p>\"}}'),
(27, 11, NULL, '<p><span style=\"color: rgb(44, 49, 58);\">Lima peserta ujian, yaitu Q, R, S, T, U mengikuti seleksi masuk PTN. Nilai TPA mereka sebagai berikut. Nilai U lebih tinggi dari R, tetapi U lebih rendah dari S. Nilai R lebih tinggi dari Q dan T lebih rendah dari Q.</span></p>', '2026-02-13 02:12:42', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Q</p>\", \"B\": \"<p>R</p>\", \"C\": \"<p>S</p>\", \"D\": \"<p>T</p>\", \"E\": \"<p>U</p>\"}}'),
(28, 11, NULL, '<p><span style=\"color: rgb(44, 49, 58);\">Ziko memiliki lima anak perempuan. Mereka adalah Humairoh, Aisyah, Hafsoh, Ruqoyyah, dan Fatimah. Humairoh lebih kaya dari Ruqoyyah, Aisyah lebih miskin dari Roqoyyah dan lebih kaya dari Fatimah, dan Fatimah lebih kaya dari Hafsoh. Di antara anak perempuan Ziko, siapakah yang paling kaya?</span></p>', '2026-02-13 02:13:45', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>Aisyah</p>\", \"B\": \"<p>Fatimah</p>\", \"C\": \"<p>Hafson</p>\", \"D\": \"<p>Humairroh</p>\", \"E\": \"<p>Ruqoyyah</p>\"}}'),
(29, 11, NULL, '<p class=\"ql-align-justify\">Ada lima keranjang buah-buahan dengan berat yang berbeda. Keranjang salak lebih berat daripada jambu. Keranjang manggis lebih ringan daripada jambu. Keranjang pisang lebih berat daripada apel. Keranjang jambu lebih berat daripada pisang. Keranjang apel tidak lebih ringan daripada manggis.</p><p class=\"ql-align-justify\">Keranjang buah terberat kedua adalah keranjang ….</p>', '2026-02-13 02:14:26', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Jambu</p>\", \"B\": \"<p>Salak</p>\", \"C\": \"<p>Pisang</p>\", \"D\": \"<p>Manggis</p>\", \"E\": \"<p>Apel</p>\"}}'),
(30, 11, NULL, '<p class=\"ql-align-justify\">Dalam perjalanan menuju sekolah, lima sekawan Slamet, Joni, Anto, Rika, dan Rahma selalu berangkat bersama. Joni selalu menjemput Slamet, setelah ia dijemput oleh Anto. Rika menjadi anak terakhir yang dijemput. Sementara rumah Rahma terletak di antara rumah Joni dan rumah Anto.</p><p class=\"ql-align-justify\">Berikut ini pernyataan yang BENAR adalah ….</p>', '2026-02-13 02:15:37', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>Rumah Rahma terletak paling jauh</p>\", \"B\": \"<p>Rumah Joni terletak paling jauh</p>\", \"C\": \"<p>Rumah Rika terletak paling jauh</p>\", \"D\": \"<p>Rumah Slamet terletak paling dekat</p>\", \"E\": \"<p>Rumah Rika terletak paling dekat</p>\"}}'),
(31, 11, NULL, '<p><span style=\"color: rgb(13, 13, 13);\">Perhatikan deret bilangan berikut: 3, -5, -1, -4, 7, -1, -9, 8, A, B. Nilai dari 2A - B adalah ...</span></p>', '2026-02-14 07:54:01', 'isian', '{\"type\": \"short_answer\", \"correct\": \"11\"}'),
(32, 11, NULL, '<p>Hari setelah Senin adalah...</p>', '2026-02-14 07:54:23', 'isian', '{\"type\": \"short_answer\", \"correct\": \"SELASA\"}'),
(34, 11, NULL, 'Suatu toko memberikan diskon 20% untuk sebuah jaket. Setelah diskon, harga jaket menjadi Rp240.000. Berapakah harga awal jaket tersebut?', '2026-02-16 07:31:31', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"Rp280.000\", \"B\": \"Rp300.000\", \"C\": \"Rp320.000\", \"D\": \"Rp340.000\", \"E\": \"Rp360.000\"}}'),
(35, 11, NULL, 'Jika semua A adalah B, dan sebagian B adalah C, maka pernyataan yang pasti benar adalah...', '2026-02-16 07:31:31', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"Semua A adalah C\", \"B\": \"Semua C adalah A\", \"C\": \"Sebagian A mungkin C\", \"D\": \"Tidak ada A yang C\", \"E\": \"Semua B adalah A\"}}'),
(36, 11, NULL, 'Tentukan benar atau salah untuk pernyataan berikut tentang logika dan pola bilangan:', '2026-02-16 07:31:31', 'bs', '[{\"text\": \"Jika suatu pernyataan bernilai benar, maka negasinya pasti bernilai salah\", \"is_correct\": 1}, {\"text\": \"Pola bilangan 2, 4, 8, 16, ... merupakan barisan aritmetika\", \"is_correct\": 0}, {\"text\": \"Jika p → q bernilai salah, maka p bernilai benar dan q bernilai salah\", \"is_correct\": 1}, {\"text\": \"Pola 1, 4, 9, 16, ... merupakan bilangan kuadrat\", \"is_correct\": 1}]'),
(37, 11, NULL, 'Pilih semua pernyataan yang merupakan contoh penalaran induktif:', '2026-02-16 07:31:31', 'multi', '[{\"text\": \"Menyimpulkan pola dari beberapa contoh khusus\", \"is_correct\": 1}, {\"text\": \"Menarik kesimpulan umum dari fakta-fakta khusus\", \"is_correct\": 1}, {\"text\": \"Menarik kesimpulan dari aturan umum ke kasus khusus\", \"is_correct\": 0}, {\"text\": \"Mengamati tren lalu memprediksi kejadian berikutnya\", \"is_correct\": 1}, {\"text\": \"Menggunakan definisi umum untuk membuktikan kasus tertentu\", \"is_correct\": 0}]'),
(38, 11, NULL, 'Pilih semua yang termasuk ciri soal penalaran umum:', '2026-02-16 07:31:31', 'multi', '[{\"text\": \"Menguji kemampuan berpikir logis\", \"is_correct\": 1}, {\"text\": \"Menghafal tanggal dan tahun kejadian\", \"is_correct\": 0}, {\"text\": \"Menguji kemampuan memahami pola\", \"is_correct\": 1}, {\"text\": \"Menguji kemampuan menarik kesimpulan\", \"is_correct\": 1}, {\"text\": \"Menguji kemampuan mengingat rumus saja\", \"is_correct\": 0}]'),
(39, 11, NULL, 'Sebutkan istilah untuk proses menarik kesimpulan dari pernyataan umum ke pernyataan khusus.', '2026-02-16 07:31:31', 'isian', '{\"type\": \"short_answer\", \"correct\": \"Deduktif\"}'),
(40, 11, NULL, 'Sebutkan nama barisan bilangan dengan selisih tetap antar suku.', '2026-02-16 07:31:31', 'isian', '{\"type\": \"short_answer\", \"correct\": \"Aritmetika\"}'),
(43, 11, NULL, '<p>Jumlah pekerja yang berasal dari generasi milenial saat ini semakin banyak. Ahli psikologi kerja menyebutkan bahwa pekerja milenial cenderung memiliki komitmen organisasi yang rendah.</p><p>Manakah pernyataan-pernyataan berikut yang BENAR dan SALAH pendapat ahli psikologi kerja tersebut?</p>', '2026-02-16 07:48:19', 'bs', '{\"items\": [{\"text\": \"<p>Pekerja milenial sering berpindah-pindah tempat kerja.</p>\", \"is_correct\": 0}, {\"text\": \"<p>Pekerja milenial memberikan ide-ide terbaik untuk tempat kerjanya.</p>\", \"is_correct\": 1}, {\"text\": \"<p>Banyak pekerja milenial yang bekerja di perusahaan untuk jangka waktu yang lama.</p>\", \"is_correct\": 1}, {\"text\": \"<p>Banyak perusahaan mengapresiasi etos kerja pekerja milenial.</p>\", \"is_correct\": 1}, {\"text\": \"<p>Banyak perusahaan yang mengeluhkan kinerja pekerja milenial.</p>\", \"is_correct\": 0}], \"labels\": {\"neg\": \"Tidak Memperlemah\", \"pos\": \"Memperlemah\"}}');

-- --------------------------------------------------------

--
-- Table structure for table `references`
--

CREATE TABLE `references` (
  `id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('text','image') DEFAULT 'text',
  `content` text,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `references`
--

INSERT INTO `references` (`id`, `subject_id`, `title`, `type`, `content`, `image_path`, `created_at`) VALUES
(14, 11, 'Grafik penjualan pakaian', 'text', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(44, 49, 58);\">Grafik berikut menunjukkan penjualan pakaian model A, model B, dan model C di toko X dari hari Selasa sampai hari Sabtu pada minggu kedua bulan Januari 2023.</span></p><p><img src=\"data:image/png;base64,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\"></p>', NULL, '2026-02-13 02:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `total_score` decimal(5,2) NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `student_id`, `exam_id`, `total_score`, `completed_at`) VALUES
(2, 13, 5, 0.00, '2026-02-06 09:33:08'),
(3, 19, 7, 0.00, '2026-02-12 06:45:47'),
(4, 15, 8, 775.36, '2026-02-14 08:44:50');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `is_logged_in` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `username`, `password`, `full_name`, `class`, `is_logged_in`, `last_login`, `created_at`) VALUES
(15, 'lita11', '$2y$10$MSktbJKnvZ5RIMweVyaRnO6m3KUveD5PbxD3cMYKFDlMAEZar6Lm.', 'Lita', 'XII IPA 1', 0, '2026-02-16 14:36:57', '2026-02-12 04:52:07'),
(16, 'apepwahyudin53', '$2y$10$z8AukipRA2hZN1BfW./ad.jmmCSHSB48ZM6kLJsRtur3jtc1QWKWS', 'Apep Wahyudin 2', 'XII IPA 11', 0, '2026-02-14 15:15:37', '2026-02-12 04:58:41'),
(17, 'budisantoso44', '$2y$10$ONGpw7jsOvOdxSINCoNVUOspSMFRoIQn7GjM0Fy.W8IJUhsn0BQkW', 'Budi Santoso', 'XII IPA 2', 0, '2026-02-13 09:16:55', '2026-02-12 04:58:41'),
(18, 'shintakurzawa78', '$2y$10$Zm6XouZXG6w22qzN0WBOWOZtMtl8lLUabA5wdUUeFmc4R8AH9qsxC', 'Shinta Kurzawa', 'XII RPL 2', 0, NULL, '2026-02-12 04:58:41'),
(19, 'ramakusnandar13', '$2y$10$24jHz5guauTTPOr.u6aK9.AAwr9gFRUwWOWNnkMk4iTi69ofWoPuC', 'Rama Kusnandar', 'XII IPA 3', 0, '2026-02-12 14:07:53', '2026-02-12 04:58:41'),
(20, 'm.46', '$2y$10$YrCiuJgkmDcVTK10VOHFAeO2E7lrZ/kh3ghbB1EtAShAmtcbZGFIe', 'M. Ahmad Sanusi', 'XII IPA 1', 0, '2026-02-16 14:15:05', '2026-02-12 07:11:28'),
(21, 'mrahman86', '$2y$10$QKTSX04NkamBt6HS7ms0jemw8Tyq849pOJ0BFVESGHx8LBK18D08e', 'M Rahman Sudrajat', 'XII IPS 2', 0, NULL, '2026-02-16 07:53:45'),
(22, 'm.gusman64', '$2y$10$MriTiqkBrIYc5A5Us/NLwuDAahw4Z0/rsFQsdrwunN8i5V1O3IcPC', 'M. Gusman Sinaga', 'XII IPS 3', 0, NULL, '2026-02-16 07:54:12'),
(23, 'aiharyati54', '$2y$10$wNBMx0JquiLrMIG10Stotueesu5BFwiz7rQyorkle4GTWfeicIg3K', 'Ai Haryati Sholehah', 'XII IPA 1', 0, NULL, '2026-02-16 07:55:17'),
(24, 'm.ridwan10', '$2y$10$ta1E15/zOWt8DLXMrCVYtulnWPGXI3rjaGR4hU.3RWpeQIi/U1fT6', 'M. Ridwan Sanusi', 'XII IPA 2', 0, NULL, '2026-02-16 07:55:18'),
(25, 'agus85', '$2y$10$.xKp3TcpFeov0VyMkqhk7eTHfwJ/Qr.KW.AS0WbuWarCoFxmR8GBe', 'Agus Muhibban', 'XII IPA 2', 0, NULL, '2026-02-16 07:55:18');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_data` text,
  `is_doubt` tinyint(1) DEFAULT '0',
  `time_spent` int DEFAULT '0',
  `option_id` int DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `session_id`, `question_id`, `answer_data`, `is_doubt`, `time_spent`, `option_id`, `answered_at`) VALUES
(270, 81, 32, '\"selasa\"', 0, 0, NULL, '2026-02-16 07:37:18'),
(271, 81, 36, '[\"B\",\"S\",\"B\",\"B\"]', 0, 0, NULL, '2026-02-16 07:37:44'),
(272, 81, 27, '\"C\"', 0, 0, NULL, '2026-02-16 07:37:52'),
(273, 81, 37, '[0,1,3]', 0, 0, NULL, '2026-02-16 07:38:14'),
(274, 81, 28, '\"D\"', 0, 0, NULL, '2026-02-16 07:38:19'),
(275, 81, 35, '\"C\"', 0, 0, NULL, '2026-02-16 07:38:29'),
(276, 81, 31, '\"11\"', 0, 0, NULL, '2026-02-16 07:38:36'),
(278, 81, 38, '[0,2,3]', 0, 0, NULL, '2026-02-16 07:39:14'),
(279, 81, 39, '\"dEdukTif\"', 0, 0, NULL, '2026-02-16 07:39:28'),
(280, 81, 40, '\"Aritmetika\"', 0, 0, NULL, '2026-02-16 07:39:40'),
(281, 81, 26, '\"E\"', 0, 0, NULL, '2026-02-16 07:39:46'),
(282, 81, 34, '\"B\"', 0, 0, NULL, '2026-02-16 07:40:08'),
(283, 81, 29, '\"A\"', 0, 0, NULL, '2026-02-16 07:40:24'),
(284, 81, 30, '\"E\"', 0, 0, NULL, '2026-02-16 07:40:29');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `exam_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration` int NOT NULL,
  `max_score` decimal(10,2) DEFAULT '100.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `exam_id`, `name`, `duration`, `max_score`, `is_active`, `created_at`) VALUES
(11, 8, 'Penalaran Umum', 20, 886.12, 1, '2026-02-13 02:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` int NOT NULL,
  `exam_id` int NOT NULL,
  `token_code` char(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`id`, `exam_id`, `token_code`, `created_at`, `expires_at`) VALUES
(34, 8, '7DSX', '2026-02-13 02:17:11', '2026-02-13 02:27:11'),
(35, 8, 'ANY3', '2026-02-14 07:56:10', '2026-02-14 08:06:10'),
(36, 8, 'HTUQ', '2026-02-14 08:15:43', '2026-02-14 08:25:43'),
(37, 8, '4MXH', '2026-02-14 08:25:43', '2026-02-14 08:35:43'),
(38, 8, 'TRGL', '2026-02-14 08:38:26', '2026-02-14 08:48:26'),
(39, 8, 'SMPG', '2026-02-16 07:14:51', '2026-02-16 07:24:51'),
(40, 8, '6MQY', '2026-02-16 07:37:06', '2026-02-16 07:47:06'),
(41, 8, 'RGWQ', '2026-02-16 07:49:49', '2026-02-16 07:59:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `fk_session_exam` (`exam_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `fk_question_reference` (`reference_id`);

--
-- Indexes for table `references`
--
ALTER TABLE `references`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_answer` (`session_id`,`question_id`,`option_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `option_id` (`option_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subject_exam` (`exam_id`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=285;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD CONSTRAINT `exam_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_sessions_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_question_reference` FOREIGN KEY (`reference_id`) REFERENCES `references` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `references`
--
ALTER TABLE `references`
  ADD CONSTRAINT `references_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subject_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
