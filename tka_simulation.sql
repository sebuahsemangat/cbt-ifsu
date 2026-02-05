-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 04, 2026 at 10:03 AM
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
(1, 'admintka', '$2y$10$rZXNoxdq.N6DWnMWUeXQ0ugYTBg4F90F6hOP0e2ewIK3c3A5wpKLS', 'Apep Wahyudin', '2026-01-29 08:07:37');

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
(3, 'Simulasi TKA SMA', '2026-02-02 08:02:00', '2026-02-09 10:02:00', 1, '2026-01-30 02:02:40');

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
  `score` decimal(5,2) DEFAULT '0.00',
  `status` enum('ongoing','finished') DEFAULT 'ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_sessions`
--

INSERT INTO `exam_sessions` (`id`, `student_id`, `exam_id`, `subject_id`, `start_time`, `end_time`, `score`, `status`) VALUES
(15, 13, 3, 9, '2026-02-04 16:59:46', '2026-02-04 17:00:15', 100.00, 'finished'),
(16, 13, 3, 8, '2026-02-04 17:00:21', '2026-02-04 17:01:06', 62.50, 'finished'),
(17, 13, 3, 7, '2026-02-04 17:01:19', '2026-02-04 17:02:00', 0.00, 'finished');

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
  `question_type` enum('pg','bs','multi') NOT NULL,
  `answer_data` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `subject_id`, `reference_id`, `question_text`, `created_at`, `question_type`, `answer_data`) VALUES
(2, 8, 5, '<p><span style=\"color: rgb(19, 19, 21);\">Apa gagasan utama paragraf tersebut?</span></p>', '2026-02-02 09:15:55', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"Pentingnya panel surya dan baterai untuk mengurangi emisi.\", \"B\": \"Dampak negatif penambangan mineral kritis bagi lingkungan.\", \"C\": \"Paradoks dalam transisi energi yang memerlukan solusi komprehensif.\", \"D\": \"Perbedaan antara teknologi energi terbarukan dan penambangan.\", \"E\": \"Kegagalan teknologi dalam mengatasi masalah lingkungan.\"}}'),
(3, 8, 5, '<p><span style=\"color: rgb(19, 19, 21);\">Kata “paradoks” pada kalimat (1) memiliki makna…</span></p>', '2026-02-02 09:16:40', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \" Sebuah situasi yang seolah-olah bertentangan, tetapi ternyata benar.\", \"B\": \" Sebuah argumen yang tidak memiliki dasar yang kuat.\", \"C\": \"Sebuah kesalahan logika dalam penarikan kesimpulan.\", \"D\": \"Sebuah perbandingan antara dua hal yang berbeda.\", \"E\": \"Sebuah kegagalan yang dapat diprediksi.\"}}'),
(4, 8, 5, '<p><span style=\"color: rgb(19, 19, 21);\">Kalimat yang berisi opini penulis terdapat pada nomor…</span></p>', '2026-02-02 09:17:38', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"(1)\", \"B\": \"(2)\", \"C\": \"(3)\", \"D\": \"(4)\", \"E\": \"(1) dan (2)\"}}'),
(5, 8, 6, '<p><span style=\"color: rgb(19, 19, 21);\">Suasana yang dominan dalam kutipan cerpen tersebut adalah…</span></p>', '2026-02-02 09:19:13', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"Gembira\", \"B\": \"Marah\", \"C\": \"Melankolis\", \"D\": \"Tegang\", \"E\": \"Misterius\"}}'),
(6, 8, 6, '<p><span style=\"color: rgb(19, 19, 21);\">Majas yang digunakan dalam kalimat “daun-daun kering yang menari-nari ditiup angin” adalah…</span></p>', '2026-02-02 09:19:49', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"Metafora\", \"B\": \"Personifikasi\", \"C\": \"Hiperbola\", \"D\": \"Simile\", \"E\": \"Litotes\"}}'),
(7, 8, 6, '<p><span style=\"color: rgb(19, 19, 21);\">Amanat yang tersirat dari kutipan cerpen tersebut adalah…</span></p>', '2026-02-02 09:20:23', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"Kita harus merawat pohon agar daunnya tidak gugur.\", \"B\": \"Kehilangan adalah bagian alami dari siklus kehidupan.\", \"C\": \"Kenangan masa lalu harus segera dilupakan.\", \"D\": \"Musim gugur selalu membawa kesedihan.\", \"E\": \"Nasihat orang tua harus selalu diingat.\"}}'),
(8, 8, 7, '<p><span style=\"color: rgb(68, 68, 68);\">Mengapa lautan menghadapi ancaman pada 2050?</span></p>', '2026-02-02 09:23:29', 'multi', '[{\"text\": \"Sampah plastik di laut akan lebih banyak daripada jumlah ikan.\", \"is_correct\": 1}, {\"text\": \"Lautan akan terus mengalami pemanasan dan semakin asam.\", \"is_correct\": 0}, {\"text\": \"Manusia terus memompa lebih banyak CO2 ke atmosfer.\", \"is_correct\": 0}, {\"text\": \"Jumlah ikan yang terancam punah semakin banyak.\", \"is_correct\": 0}, {\"text\": \"Penggunaan plastik terus meningkat dan tidak dikelola dengan baik\", \"is_correct\": 1}]'),
(9, 8, 7, '<p><strong>“Solusi tersebut tidak mudah.”</strong></p><p>Mengapa penulis menggunakan kalimat tersebut pada teks?</p>', '2026-02-02 09:24:56', 'bs', '[{\"text\": \"Menginformasikan kepada pembaca bahwa pemecahan masalah sampah merupakan tanggung jawab utama kita sebagai masyarakat sipil sehingga sulit untuk diselesaikan.\", \"is_correct\": 0}, {\"text\": \"Menunjukkan kepada pembaca bahwa permasalahan sampah plastik sangat rumit dan tidak bisa diselesaikan dalam jangka waktu singkat.\", \"is_correct\": 1}, {\"text\": \"Membuktikan kepada pembaca bahwa  permasalahan sampah yang rumit disebabkan pemecahan masalah yang bergantung pada perekonomian negara berkembang.\", \"is_correct\": 0}]'),
(10, 7, 9, '<p><span style=\"color: rgb(68, 68, 68);\">Daerah yang memenuhi sistem pertidaksamaan linear&nbsp;<img src=\"https://pusmendik.kemdikbud.go.id/tka/cbt_images/95089_d25340b2a7ed58e850e0298630c96c79.png\" height=\"18\" width=\"249\">&nbsp;adalah ....</span></p>', '2026-02-02 09:32:20', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"I\", \"B\": \"II\", \"C\": \"III\", \"D\": \"IV\", \"E\": \"VI\"}}'),
(11, 9, 10, '<p>How can we decide a certain table is perfect for studying according to the text?</p><p>There is more than one correct answer. Click on every correct answer!</p>', '2026-02-03 05:33:23', 'multi', '[{\"text\": \"It has good lighting\", \"is_correct\": 1}, {\"text\": \"It provides white noise\", \"is_correct\": 0}, {\"text\": \"It is located near the entrance\", \"is_correct\": 0}, {\"text\": \"It is far from the toilet\", \"is_correct\": 1}, {\"text\": \"It provides stationery\", \"is_correct\": 0}]'),
(12, 7, NULL, '<p><span style=\"color: rgb(68, 68, 68);\">Harga 3 buah buku dan 2 buah penggaris Rp18.000,00. Jika harga sebuah buku Rp1.000,00 lebih mahal dari sebuah penggaris, harga 2 buah buku dan 5 buah penggaris adalah ....</span></p>', '2026-02-04 09:36:59', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"Rp19.000\", \"B\": \"Rp23.000\", \"C\": \"Rp25.000\", \"D\": \"Rp27.000\", \"E\": \"Rp30.000\"}}'),
(13, 7, 11, '<p><span style=\"color: rgb(68, 68, 68);\">Apabila Mirna ingin memperoleh keuntungan maksimum, tentukan</span></p><p><span style=\"color: rgb(68, 68, 68);\">Benar atau Salah untuk setiap pernyataan berikut!</span></p>', '2026-02-04 09:59:19', 'bs', '[{\"text\": \"Mirna harus memproduksi 200 kotak kue bolu.\", \"is_correct\": 0}, {\"text\": \"Mirna  harus  memproduksi  kue  brownies lebih banyak.\", \"is_correct\": 0}, {\"text\": \"Keuntungan maksimum  yang dapat diperoleh Mirna adalah Rp3.100.000,00.\", \"is_correct\": 1}]');

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
(5, 8, 'Transisi Energi', 'text', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(19, 19, 21);\">(1) Transisi energi menuju sumber terbarukan merupakan sebuah keniscayaan global, tetapi juga menghadirkan paradoks. (2) Di satu sisi, teknologi seperti panel surya dan baterai kendaraan listrik sangat penting untuk mengurangi emisi karbon. (3) Di sisi lain, produksi teknologi ini bergantung pada penambangan mineral kritis seperti litium, kobalt, dan nikel yang sering kali merusak lingkungan dan memicu konflik sosial di negara-negara produsen. (4) Oleh karena itu, solusi transisi energi tidak bisa hanya berfokus pada teknologi, tetapi juga harus mempertimbangkan aspek keberlanjutan rantai pasok secara menyeluruh.</span></p>', NULL, '2026-02-02 09:14:52'),
(6, 8, 'Senja Itu', 'text', '<p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(19, 19, 21);\">Senja itu, kota terasa lebih senyap dari biasanya. Aku duduk di bangku taman, memandangi daun-daun kering yang menari-nari ditiup angin. Setiap embusannya seolah membisikkan kembali kenangan tentang Ayah, yang pergi tepat di musim gugur seperti ini. Ia pernah berkata, “Jangan takut pada daun yang gugur, Nak. Itu hanya cara pohon untuk tetap hidup.” Kini, aku mengerti, Ayah sedang mengajariku tentang kehilangan.</span></p>', NULL, '2026-02-02 09:18:31'),
(7, 8, 'Lautan', 'text', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Lautan mengalami ancaman pemanasan dan menjadi semakin asam karena kita terus memompa lebih banyak CO</span><sub style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">2</sub><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\"> ke atmosfer. Selain itu, lautan juga menghadapi ancaman lain dari manusia, yakni penggunaan plastik. Kehidupan modern saat ini didominasi oleh kemasan makanan berbahan plastik yang diolah dengan teknologi tinggi dan digunakan setiap hari oleh masyarakat. Dalam sebuah laporan telah disebutkan bahwa pada tahun 2050 sampah plastik di laut akan lebih banyak daripada jumlah ikan apabila kecenderungan ini terus berlanjut. Hal tersebut merupakan sebuah ancaman besar.</span></p><p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Dibutuhkan tindakan skala besar untuk berpindah dari kecenderungan tersebut mengingat banyak pihak yang terlibat dalam bidang pekerjaan ini. Masyarakat sektor swasta dan masyarakat sipil perlu mobilisasi untuk menangkap peluang ekonomi lain di luar dari pengelolaan plastik.</span></p><p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Solusi tersebut tidak mudah. Fakta bahwa harga minyak yang rendah mengakibatkan biaya yang dibutuhkan untuk daur ulang plastik jauh lebih mahal daripada memproduksi yang baru. Kondisi lain yang menunjukkan ekonomi di negara berkembang tumbuh lebih besar menjadikan penggunaan plastik juga meningkat. Solusi yang dibutuhkan adalah cara kita menggunakan plastik. Misalnya dengan mengurangi penggunaan plastik dalam kemasan atau menggunakannya kembali sebanyak yang kita bisa.</span></p><p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Produsen plastik juga dapat membantu dalam pengelolaan plastik, seperti memproduksi barang-barang plastik yang dapat digunakan kembali atau beralih ke plastik yang dapat dijadikan kompos. Solusi apa pun akan membawa perubahan besar dalam cara menggunakan produk yang terbuat dari plastik.</span></p><p><br></p>', NULL, '2026-02-02 09:22:22'),
(9, 7, 'pertidaksamaan linear multivariabel', 'image', '<p><br></p>', '1770024687_69806eef6e601.jpg', '2026-02-02 09:31:27'),
(10, 9, 'How To Study', 'image', '<p><br></p>', '1770096717_6981884de9053.jpg', '2026-02-03 05:31:58'),
(11, 7, 'Mirna akan memproduksi dua', 'text', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Mirna akan memproduksi dua jenis kue dengan modal Rp8.000.000,00. Biaya produksi kue bolu sebesar Rp15.000,00 per kotak dan dijual dengan laba 40%. Sedangkan biaya produksi kue brownies sebesar Rp20.000,00 per kotak dan dijual dengan laba 35%. Setiap harinya, Mirna dapat memproduksi paling banyak 500 kotak kue.</span></p>', NULL, '2026-02-04 09:58:37');

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
(1, 13, 3, 33.33, '2026-02-04 09:33:45');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `is_logged_in` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `username`, `password`, `full_name`, `is_logged_in`, `last_login`, `created_at`) VALUES
(2, 'jajangnurjaman59', '$2y$10$jfYzm4Sx0fYTRMAaMc1SKe4iHF4RPbbB/rnSjawW8nKtqRxUlj.gm', 'Jajang Nurjaman', 0, NULL, '2026-01-30 02:33:48'),
(3, 'patriciomatricardi83', '$2y$10$1wl06HoUzHF2vYpDQeVjc.h9w56OgUekwffm5X.kc6Zm9j1xhjtze', 'Patricio Matricardi', 0, NULL, '2026-01-30 02:33:48'),
(4, 'jensraven61', '$2y$10$/vzEnz.2PYubG.241trsreEl7ennS.orR0zmoYG9MqCzRK8wKZnPG', 'Jens Raven', 0, NULL, '2026-01-30 02:33:48'),
(5, 'boahsartika78', '$2y$10$Gpxm0oIo5jrFAZwC5jrg3O.sBVrQemsczLFeZc8wWqVYN7kIwACXm', 'Boah Sartika', 0, NULL, '2026-01-30 02:36:09'),
(6, 'neneng22', '$2y$10$ScB0rg.C24T8Uz7C7zNqBO9jdtqgZXDLcpJM9.7Hwbk8cSO84QDZi', 'Neneng', 0, NULL, '2026-01-30 02:36:10'),
(7, 'prisaborusegu68', '$2y$10$hJw866nJQcBzNqUbK.PYlO9JuYH5XkEgTH3F2Ii95P0S4hXmyP6t6', 'Prisa Borusegu', 0, NULL, '2026-01-30 02:36:10'),
(8, 'hasansadikin66', '$2y$10$ftxqC/cjItCy0g.egJaA5ujmIgQL468BwR8DfOfZznuCY82cZim6C', 'Hasan Sadikin', 0, NULL, '2026-01-30 02:47:31'),
(9, 'ahmadagung45', '$2y$10$A61XmX4Syv1mS9MPqljjGuyA5zp.z2NEwR2wpRT0doVd3QNerIFdq', 'Ahmad Agung', 0, NULL, '2026-01-30 02:47:40'),
(10, 'candrakirana84', '$2y$10$v5vl/UwENN2jguwTQ7FAVe.12JxqZRaNQm72WbgcvHy9v4w6zNb.q', 'Candra Kirana', 0, NULL, '2026-01-30 02:48:00'),
(11, 'gunawandwicahyo34', '$2y$10$7yDbdVIF3p8V4sX4x1OjkeanQC5vjkSrbCs0eLuGMLUGcr/WXzBFW', 'Gunawan Dwi Cahyo', 0, NULL, '2026-01-30 02:48:13'),
(12, 'sintiaanisarahmawati69', '$2y$10$bubbfppd6QygwzKidg/ezOONrarjUygO5ZwZdbUlxfnATT82nOn7e', 'Sintia Anisa Rahmawati', 0, NULL, '2026-01-30 02:48:39'),
(13, 'agus77', '$2y$10$7EmLRy2lxWikOdnnVbdVO.p.xvImkqLRE86e4vfmBW0Xu.PeDk.Iy', 'Agus Kuncoro Jati', 1, '2026-02-04 16:59:33', '2026-01-30 02:52:00'),
(14, 'agus41', '$2y$10$o1INC6yiDqXp4hHpszsITOi00yI0i0wr2Tmrjvv.l5CmBucE/3eaS', 'Agus Setia Budi', 0, '2026-02-04 16:50:06', '2026-01-30 02:52:12');

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
(36, 15, 11, '[0,3]', 0, 0, NULL, '2026-02-04 10:00:12'),
(37, 16, 8, '[2,4]', 0, 0, NULL, '2026-02-04 10:00:30'),
(38, 16, 9, '[0]', 0, 0, NULL, '2026-02-04 10:00:33'),
(39, 16, 4, '\"D\"', 0, 0, NULL, '2026-02-04 10:00:43'),
(40, 16, 2, '\"C\"', 0, 0, NULL, '2026-02-04 10:00:48'),
(41, 16, 3, '\"A\"', 0, 0, NULL, '2026-02-04 10:00:53'),
(42, 16, 7, '\"E\"', 0, 0, NULL, '2026-02-04 10:00:56'),
(43, 16, 5, '\"C\"', 0, 0, NULL, '2026-02-04 10:00:59'),
(44, 16, 6, '\"B\"', 0, 0, NULL, '2026-02-04 10:01:02');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `exam_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `exam_id`, `name`, `duration`, `is_active`, `created_at`) VALUES
(7, 3, 'Matematika', 45, 1, '2026-01-30 02:13:37'),
(8, 3, 'Bahasa Indonesia', 45, 1, '2026-01-30 08:58:01'),
(9, 3, 'Bahasa Inggris', 45, 1, '2026-02-03 05:30:54');

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
(1, 3, 'CXHM', '2026-02-03 04:45:54', '2026-02-03 04:55:54'),
(2, 3, '37BW', '2026-02-03 04:55:54', '2026-02-03 05:05:54'),
(3, 3, 'D5HV', '2026-02-03 05:07:39', '2026-02-03 05:17:39'),
(4, 3, 'ANF9', '2026-02-03 05:19:06', '2026-02-03 05:29:06'),
(5, 3, 'KHMD', '2026-02-03 05:33:58', '2026-02-03 05:43:58'),
(6, 3, 'H2N5', '2026-02-03 05:53:58', '2026-02-03 06:03:58'),
(7, 3, 'TL5W', '2026-02-03 07:22:47', '2026-02-03 07:32:47'),
(8, 3, '7JGL', '2026-02-03 07:41:34', '2026-02-03 07:51:34'),
(9, 3, 'U63D', '2026-02-03 07:52:03', '2026-02-03 08:02:03'),
(10, 3, 'EMRP', '2026-02-04 05:04:25', '2026-02-04 05:14:25'),
(11, 3, 'ZMRQ', '2026-02-04 05:32:59', '2026-02-04 05:42:59'),
(12, 3, 'LMX2', '2026-02-04 05:49:10', '2026-02-04 05:59:10'),
(13, 3, 'VGDU', '2026-02-04 07:54:23', '2026-02-04 08:04:23'),
(14, 3, 'MYFH', '2026-02-04 09:22:15', '2026-02-04 09:32:15'),
(15, 3, '674H', '2026-02-04 09:37:12', '2026-02-04 09:47:12'),
(16, 3, '3FWM', '2026-02-04 09:48:50', '2026-02-04 09:58:50'),
(17, 3, 'FR9P', '2026-02-04 09:59:39', '2026-02-04 10:09:39');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
