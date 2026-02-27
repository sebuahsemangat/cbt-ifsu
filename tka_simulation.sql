-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 27, 2026 at 08:50 AM
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
(9, 'Simulasi UTBK', '2026-02-23 11:00:00', '2026-02-28 11:00:00', 1, '2026-02-23 04:13:01');

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
(95, 32, 9, 12, '2026-02-23 12:14:08', '2026-02-23 12:16:35', 177.22, 'finished'),
(96, 28, 9, 12, '2026-02-23 12:14:43', '2026-02-23 12:16:35', 177.22, 'finished'),
(97, 36, 9, 12, '2026-02-23 12:18:50', '2026-02-23 12:21:34', 886.12, 'finished'),
(98, 34, 9, 12, '2026-02-23 12:24:18', '2026-02-23 12:28:52', 443.06, 'finished'),
(99, 27, 9, 12, '2026-02-26 13:30:32', '2026-02-26 13:31:46', 620.28, 'finished'),
(100, 26, 9, 12, '2026-02-26 13:34:01', '2026-02-26 13:34:58', 531.67, 'finished'),
(101, 29, 9, 12, '2026-02-26 13:38:55', '2026-02-26 13:55:23', 354.45, 'finished'),
(102, 30, 9, 12, '2026-02-27 09:03:50', '2026-02-27 09:05:40', 620.28, 'finished'),
(103, 31, 9, 12, '2026-02-27 09:06:33', '2026-02-27 09:12:16', 88.61, 'finished');

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
(44, 12, 15, '<p>Nilai akhir siswa berprestasi di suatu sekolah ditentukan dengan menjumlahkan nilai capaian akademik dan nilai keaktifan dalam kegiatan. Bobot nilai capaian akademik 60%, sedangkan bobot nilai keaktifan dalam kegiatan 40%. Berikut ini adalah nilai lima siswa terbaik di sekolah.</p>', '2026-02-23 04:17:41', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Q</p>\", \"B\": \"<p>P</p>\", \"C\": \"<p>S</p>\", \"D\": \"<p>R</p>\", \"E\": \"<p>T</p>\"}}'),
(45, 12, NULL, '<p>Jumlah pekerja yang berasal dari generasi milenial saat ini semakin banyak. Ahli psikologi kerja menyebutkan bahwa pekerja milenial cenderung memiliki komitmen organisasi yang rendah.</p><p><br></p><p><strong>Manakah pernyataan-pernyataan berikut yang MEMPERLEMAH dan TIDAK MEMPERLEMAH pendapat ahli psikologi kerja tersebut?</strong></p>', '2026-02-23 04:18:47', 'bs', '{\"items\": [{\"text\": \"<p><span style=\\\"background-color: rgb(255, 255, 255); color: rgb(33, 37, 41);\\\">Pekerja milenial sering berpindah-pindah tempat kerja.</span></p>\", \"is_correct\": 0}, {\"text\": \"<p><span style=\\\"background-color: rgb(255, 255, 255); color: rgb(33, 37, 41);\\\">Pekerja milenial memberikan ide-ide terbaik untuk tempat kerjanya.</span></p>\", \"is_correct\": 1}, {\"text\": \"<p><span style=\\\"background-color: rgb(255, 255, 255); color: rgb(33, 37, 41);\\\">Banyak pekerja milenial yang bekerja di perusahaan untuk jangka waktu yang lama.</span></p>\", \"is_correct\": 1}, {\"text\": \"<p><span style=\\\"background-color: rgb(255, 255, 255); color: rgb(33, 37, 41);\\\">Banyak perusahaan mengapresiasi etos kerja pekerja milenial.</span></p>\", \"is_correct\": 1}, {\"text\": \"<p><span style=\\\"background-color: rgb(255, 255, 255); color: rgb(33, 37, 41);\\\">Banyak perusahaan yang mengeluhkan kinerja pekerja milenial.</span></p>\", \"is_correct\": 0}], \"labels\": {\"neg\": \"Tidak Memperlemah\", \"pos\": \"Memperlemah\"}}'),
(46, 12, NULL, '<p>Seorang mahasiswa X harus bekerja tambahan setelah pulang kuliah. Pekerjaan tambahan tersebut menyebabkan waktu belajarnya berkurang. Meskipun X hanya bekerja di waktu malam, perkuliahannya tetap terganggu karena waktu belajarnya berkurang.</p><p><br></p><p><strong>Berdasarkan informasi di atas, manakah pernyataan di bawah ini yang BENAR?\"</strong></p>', '2026-02-23 04:19:41', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>X mencari pekerjaan tambahan ketika jam belajarnya berkurang.</p>\", \"B\": \"<p>Jam kerja di malam hari menyebabkan X mencari pekerjaan tambahan.</p>\", \"C\": \"<p>Pekerjaan tambahan menyebabkan perkuliahan X terganggu.</p>\", \"D\": \"<p>X bekerja pada malam hari karena perkuliahannya terganggu.</p>\", \"E\": \"<p>Waktu belajar X berkurang ketika perkuliahannya terganggu.</p>\"}}'),
(47, 12, NULL, '<p>Buah bit dianggap sebagai buah yang banyak memberikan manfaat karena memiliki kalori yang rendah. Namun, buah bit memiliki kandungan oksalat yang tinggi. Mengonsumsi buah bit terlalu banyak dapat memicu peningkatan sekresi oksalat yang berkontribusi pembentukan batu ginjal. Untuk itu, jika memiliki batu ginjal, sebaiknya kita tidak mengonsumsi buah bit secara berlebihan.</p><p><br></p><p><strong>Berdasarkan informasi tersebu, manakah pernyataan berikut yang PASTI SALAH?</strong></p>', '2026-02-23 04:20:27', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>Walaupun dapat menyebabkan batu ginjal, buah bit tidak akan mengganggu pencernaan.</p>\", \"B\": \"<p>Kandungan oksalat pada buah bit menyebabkan batu ginjal.</p>\", \"C\": \"<p>Konsumsi buah bit berlebihan dapat menyebabkan gangguan pencernaan.</p>\", \"D\": \"<p>Konsumsi buah bit tidak akan menyebabkan gangguan kesehatan.</p>\", \"E\": \"<p>Buah bit banyak dikonsumsi karena memiliki kalori yang rendah.</p>\"}}'),
(48, 12, NULL, '<p class=\"ql-align-justify\">Kadar gula yang tinggi dalam darah meningkatkan risiko penyakit kardiovaskular. Upaya untuk mencegah meningkatnya kadar gula dalam darah dapat dicegah dengan diet karbohidrat. Risiko terjadinya penyakit kardiovaskular dapat berkurang jika jumlah karbohidrat yang dikonsumsi dapat dikendalikan.</p><p><br></p><p><strong>Berdasarkan informasi tersebut, manakah pernyataan berikut yang PASTI BENAR?</strong></p>', '2026-02-23 04:21:32', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Risiko penyakit kardiovaskular berkurang jika diet karbohidrat diterapkan.</p>\", \"B\": \"<p>Peningkatan risiko penyakit kardiovaskular terjadi karena penerapan diet karbohidrat.</p>\", \"C\": \"<p>Menerapkan diet karbohidrat masih memiliki risiko penyakit kardiovaskular.</p>\", \"D\": \"<p>Menerapkan diet karbohidrat dapat meningkatkan kadar gula dalam darah.</p>\", \"E\": \"<p>Konsumsi karbohidrat dapat menurunkan kadar gula dalam darah.</p>\"}}'),
(49, 12, NULL, '<p>Pakar energi A menyatakan, “Walaupun ramah lingkungan, mobil listrik masih membutuhkan energi batu bara.”</p><p>Pakar energi B menyatakan, “Penggunaan mobil listrik efektif mengurangi tingkat polusi di ibu kota.”</p><p>Data menunjukkan penggunaan mobil listrik masih belum meningkat karena harganya yang mahal.</p><p><br></p><p><strong>Manakah pernyataan berikut yang PALING TEPAT mengenai data tersebut?</strong></p>', '2026-02-23 04:23:35', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>Memperkuat pernyataan pakar energi B.</p>\", \"B\": \"<p>Memperlemah pernyataan pakar energi B.</p>\", \"C\": \"<p>Memperkuat pernyataan pakar energi A.</p>\", \"D\": \"<p>Tidak relevan dengan pernyataan pakar energi A dan B.</p>\", \"E\": \"<p>Memperlemah pernyataan pakar energi A.</p>\"}}'),
(50, 12, NULL, '<p>Jumlah siswa perempuan yang mengunjungi perpustakaan sekolah selama lima hari berturut-turut adalah 16, 19, 24, 27, dan 32. Sementara itu, jumlah siswa laki-laki yang berkunjuang selama lima hari yang sama adalah 8, 8, 9, 11, dan 14. Jika tren kunjungan tersebut bersifat konstan, berapa jumlah siswa berkunjung ke perpustakaan pada hari ke-6?</p>', '2026-02-23 04:24:16', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>37 dan 18</p>\", \"B\": \"<p>37 dan 17</p>\", \"C\": \"<p>39 dan 19</p>\", \"D\": \"<p>35 dan 18</p>\", \"E\": \"<p>35 dan 17</p>\"}}'),
(51, 12, NULL, '<p>Sebagian siswa tidak meraih nilai ujian tinggi jika mereka tidak belajar dengan tekun. Kondisi tersebut terjadi ketika siswa tidak berdisiplin dalam mengatur waktu.</p><p><br></p><p><strong>Manakah simpulan berikut yang BENAR?</strong></p>', '2026-02-23 04:25:11', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>Sebagian siswa meraih nilai ujian tinggi apabila tidak berdisiplin dalam mengatur waktu.</p>\", \"B\": \"<p>Siswa berdisiplin dalam mengatur waktu apabila sebagian siswa meraih nilai ujian tinggi.</p>\", \"C\": \"<p>Siswa tidak berdisiplin dalam mengatur waktu apabila sebagian siswa meraih nilai ujian tinggi.</p>\", \"D\": \"<p>Sebagian siswa tidak meraih nilai ujian tinggi apabila tidak berdisiplin dalam mengatur waktu.</p>\", \"E\": \"<p>Sebagian siswa tidak meraih nilai ujian tinggi apabila berdisiplin dalam mengatur waktu.</p>\"}}'),
(52, 12, 16, '<p>Diagram berikut menunjukkan data modal dan pendapatan produk kelontong di Toko P dalam lima tahun terakhir.</p><p><strong>Berdasarkan data di atas, tahun keberapa Toko P memperoleh persentase keuntungan terbesar?</strong></p>', '2026-02-23 04:26:54', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>&nbsp;Tahun ke-3</p>\", \"B\": \"<p>Tahun ke-1</p>\", \"C\": \"<p>Tahun ke-5</p>\", \"D\": \"<p>Tahun ke-4</p>\", \"E\": \"<p>Tahun ke-2</p>\"}}'),
(53, 12, NULL, '<p>Ketika sering belajar di perpustakaan, siswa X dapat mencapai hasil belajar yang lebih baik. Namun, ketika X belajar di rumah, hasil belajarnya cenderung kurang optimal.</p><p><br></p><p><strong>Manakah pernyataan berikut yang PALING MUNGKIN menjelaskan perbedaan kedua kondisi tersebut?</strong></p>', '2026-02-23 04:28:17', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Perpustakaan menyediakan lebih banyak buku pelajaran.</p>\", \"B\": \"<p>Siswa X sering terganggu oleh kebisingan di sekitar perpustakaan.</p>\", \"C\": \"<p>Selain buku pelajaran, perpustakaan juga menyediakan novel legendaris.</p>\", \"D\": \"<p>Siswa X merasa lebih nyaman belajar di rumah daripada di perpustakaan.</p>\", \"E\": \"<p>Siswa X dapat berinteraksi dengan teman sebaya ketika belajar di rumah.</p>\"}}');

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
(15, 12, 'tabel nilai', 'image', '<p><br></p>', '1771820224_699bd4c0936fb.jpg', '2026-02-23 04:17:04'),
(16, 12, 'Grafik modal dan pendapatan', 'image', '<p><br></p>', '1771820763_699bd6dbabb58.jpg', '2026-02-23 04:26:03');

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
(26, 'siswa42', '$2y$10$.lVCLtG2lS0OginKWeoHEerfxyYBNTzdO/9aMLSJbvCwaYwdRuldO', 'Siswa 1', 'XII IPA 1', 0, '2026-02-26 13:33:46', '2026-02-23 04:31:34'),
(27, 'siswa60', '$2y$10$II6B/HqUkQdt2PGG8IhJr.UH0aBA4CurI4l1Gjfp9X99vwZ8KwLBW', 'Siswa 2', 'XII IPA 2', 0, '2026-02-26 13:30:17', '2026-02-23 04:31:34'),
(28, 'siswa25', '$2y$10$fzkKGroo8yq539Z7hnwnVucajKmwQd.4GLbWXkqAHlfKAHxT90fZG', 'Siswa 3', 'XII IPA 3', 0, '2026-02-23 12:14:32', '2026-02-23 04:31:34'),
(29, 'siswa79', '$2y$10$Q2mstrI9qh1sEpCeSo3hnOlVz7UyTwVV5AuvJmBYFgybqn2rBgnH6', 'Siswa 4', 'XII IPA 4', 0, '2026-02-26 13:38:42', '2026-02-23 04:31:35'),
(30, 'siswa859', '$2y$10$woZK3iZGbBeSq4MLYPfcR.ZQkIhVgLpmFZ7pzA8pH2r1.o42SQpmS', 'Siswa 5', 'XII IPA 5', 0, '2026-02-27 09:03:38', '2026-02-23 04:31:35'),
(31, 'siswa57', '$2y$10$8ARhnQ1VOvvauzDadE3veOgultUZZzJq.Sc1esqnui0K4vdK7sD8y', 'Siswa 6', 'XII IPA 6', 0, '2026-02-27 09:06:16', '2026-02-23 04:31:35'),
(32, 'siswa99', '$2y$10$EBJGVjCjs1BmPGHCe2IFBuKx9zCaa0NjFgt5YhStATDhZmj5vburS', 'Siswa 7', 'XII IPA 7', 0, '2026-02-23 12:05:02', '2026-02-23 04:31:35'),
(33, 'siswa29', '$2y$10$E8ahrC8OiXtGyiStHNZOZekIfGIE6zO3cjnw9i6j/ax4LC3QIdVpG', 'Siswa 8', 'XII IPA 8', 0, '2026-02-23 11:57:51', '2026-02-23 04:31:35'),
(34, 'siswa61', '$2y$10$HexjdeOTFjzQGT4T7z4fDOUhBPAzZyuKh9MrGg/29oMVOYhdYGb4W', 'Siswa 9', 'XII IPA 9', 0, '2026-02-23 12:23:36', '2026-02-23 04:31:35'),
(35, 'siswa96', '$2y$10$FCl4o8cqbH59/yiUbnZ9UeP3zY/vIGxWio5gz9EfNlnnx2SM7aele', 'Siswa 10', 'XII IPA 10', 0, '2026-02-23 11:48:14', '2026-02-23 04:31:35'),
(36, 'siswa40', '$2y$10$YzATwM8lZHr0SjUd29YTyeuAyPUa4OoqPiio0653pwYt0qrMXzOpa', 'Siswa 11', 'X', 0, '2026-02-23 12:18:37', '2026-02-23 04:52:55');

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
(373, 95, 49, '\"A\"', 0, 0, 0, '2026-02-23 05:14:47'),
(374, 96, 44, '\"A\"', 0, 0, 0, '2026-02-23 05:14:50'),
(375, 95, 50, '\"E\"', 0, 0, 0, '2026-02-23 05:14:53'),
(376, 96, 48, '\"B\"', 0, 0, 0, '2026-02-23 05:14:54'),
(377, 96, 47, '\"C\"', 0, 0, 0, '2026-02-23 05:14:59'),
(378, 95, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-23 05:15:07'),
(383, 95, 46, '\"E\"', 0, 0, 0, '2026-02-23 05:15:13'),
(384, 96, 46, '\"D\"', 0, 0, 0, '2026-02-23 05:15:13'),
(385, 95, 47, '\"D\"', 0, 0, 0, '2026-02-23 05:15:19'),
(386, 96, 52, '\"E\"', 0, 0, 0, '2026-02-23 05:15:20'),
(387, 95, 51, '\"E\"', 0, 0, 0, '2026-02-23 05:16:03'),
(388, 96, 49, '\"C\"', 0, 0, 0, '2026-02-23 05:16:03'),
(389, 96, 51, '\"C\"', 0, 0, 0, '2026-02-23 05:16:08'),
(390, 95, 44, '\"E\"', 0, 0, 0, '2026-02-23 05:16:08'),
(391, 96, 53, '\"C\"', 0, 0, 0, '2026-02-23 05:16:12'),
(392, 95, 48, '\"C\"', 0, 0, 0, '2026-02-23 05:16:13'),
(393, 96, 50, '\"C\"', 0, 0, 0, '2026-02-23 05:16:18'),
(394, 96, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-23 05:16:30'),
(399, 97, 49, '\"E\"', 0, 0, 0, '2026-02-23 05:19:15'),
(400, 97, 47, '\"D\"', 0, 0, 0, '2026-02-23 05:19:27'),
(401, 97, 46, '\"C\"', 0, 0, 0, '2026-02-23 05:19:41'),
(402, 97, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-23 05:20:04'),
(407, 97, 53, '\"A\"', 0, 0, 0, '2026-02-23 05:20:14'),
(408, 97, 51, '\"D\"', 0, 0, 0, '2026-02-23 05:20:23'),
(409, 97, 48, '\"A\"', 0, 0, 0, '2026-02-23 05:20:30'),
(410, 97, 50, '\"D\"', 0, 0, 0, '2026-02-23 05:20:42'),
(411, 97, 44, '\"C\"', 0, 0, 0, '2026-02-23 05:21:17'),
(412, 97, 52, '\"E\"', 0, 0, 0, '2026-02-23 05:21:24'),
(413, 98, 51, '\"A\"', 0, 0, 0, '2026-02-23 05:24:28'),
(414, 98, 44, '\"C\"', 0, 0, 0, '2026-02-23 05:26:00'),
(416, 98, 49, '\"B\"', 0, 0, 0, '2026-02-23 05:26:11'),
(417, 98, 53, '\"C\"', 0, 0, 0, '2026-02-23 05:26:14'),
(418, 98, 47, '\"A\"', 0, 0, 0, '2026-02-23 05:27:08'),
(419, 98, 48, '\"B\"', 0, 0, 0, '2026-02-23 05:27:11'),
(420, 98, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-23 05:27:22'),
(425, 98, 52, '\"E\"', 0, 0, 0, '2026-02-23 05:27:45'),
(427, 98, 50, '\"D\"', 0, 0, 0, '2026-02-23 05:27:51'),
(429, 98, 46, '\"C\"', 0, 0, 0, '2026-02-23 05:28:12'),
(434, 99, 48, '\"A\"', 0, 0, 0, '2026-02-26 06:30:35'),
(435, 99, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-26 06:30:49'),
(440, 99, 49, '\"C\"', 0, 0, 0, '2026-02-26 06:30:54'),
(441, 99, 44, '\"C\"', 0, 0, 0, '2026-02-26 06:31:02'),
(442, 99, 46, '\"C\"', 0, 0, 0, '2026-02-26 06:31:07'),
(443, 99, 53, '\"C\"', 0, 0, 0, '2026-02-26 06:31:10'),
(444, 99, 51, '\"D\"', 0, 0, 0, '2026-02-26 06:31:14'),
(446, 99, 52, '\"E\"', 0, 0, 0, '2026-02-26 06:31:42'),
(452, 99, 47, '\"C\"', 0, 0, 0, '2026-02-26 06:31:29'),
(453, 99, 50, '\"D\"', 0, 0, 0, '2026-02-26 06:31:31'),
(455, 100, 44, '\"C\"', 1, 0, 0, '2026-02-26 06:34:12'),
(457, 100, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-26 06:34:24'),
(462, 100, 46, '\"E\"', 0, 0, 0, '2026-02-26 06:34:33'),
(463, 100, 50, '\"D\"', 0, 0, 0, '2026-02-26 06:34:36'),
(464, 100, 47, '\"D\"', 0, 0, 0, '2026-02-26 06:34:38'),
(465, 100, 53, '\"E\"', 0, 0, 0, '2026-02-26 06:34:46'),
(466, 100, 51, '\"E\"', 0, 0, 0, '2026-02-26 06:34:49'),
(467, 100, 52, '\"E\"', 0, 0, 0, '2026-02-26 06:34:51'),
(468, 100, 48, '\"E\"', 0, 0, 0, '2026-02-26 06:34:53'),
(469, 100, 49, '\"E\"', 0, 0, 0, '2026-02-26 06:34:56'),
(470, 101, 48, '\"A\"', 0, 0, 0, '2026-02-26 06:53:51'),
(471, 101, 53, '\"D\"', 0, 0, 0, '2026-02-26 06:54:00'),
(472, 101, 49, '\"C\"', 0, 0, 0, '2026-02-26 06:54:20'),
(473, 101, 44, '\"C\"', 0, 0, 0, '2026-02-26 06:54:25'),
(474, 101, 52, '\"B\"', 0, 0, 0, '2026-02-26 06:54:41'),
(476, 101, 47, '\"C\"', 0, 0, 0, '2026-02-26 06:54:46'),
(477, 101, 50, '\"D\"', 0, 0, 0, '2026-02-26 06:54:59'),
(478, 101, 46, '\"C\"', 0, 0, 0, '2026-02-26 06:55:02'),
(479, 101, 45, '[\"S\",\"B\",\"S\",\"S\",\"S\"]', 0, 0, 0, '2026-02-26 06:55:15'),
(486, 102, 52, '\"E\"', 0, 0, 0, '2026-02-27 02:03:55'),
(487, 102, 50, '\"D\"', 0, 0, 0, '2026-02-27 02:04:53'),
(489, 102, 44, '\"C\"', 0, 0, 0, '2026-02-27 02:04:01'),
(490, 102, 48, '\"A\"', 0, 0, 0, '2026-02-27 02:04:09'),
(491, 102, 53, '\"B\"', 0, 0, 0, '2026-02-27 02:05:30'),
(492, 102, 51, '\"D\"', 0, 0, 0, '2026-02-27 02:04:25'),
(493, 102, 47, '\"C\"', 0, 0, 0, '2026-02-27 02:04:27'),
(494, 102, 46, '\"C\"', 0, 0, 0, '2026-02-27 02:04:29'),
(495, 102, 49, '\"C\"', 0, 0, 0, '2026-02-27 02:04:31'),
(496, 102, 45, '[\"S\",\"B\",\"B\",\"B\",\"S\"]', 0, 0, 0, '2026-02-27 02:04:38'),
(514, 103, 49, '\"B\"', 0, 0, 0, '2026-02-27 02:07:05'),
(515, 103, 51, '\"B\"', 0, 0, 0, '2026-02-27 02:08:23'),
(517, 103, 53, '\"C\"', 0, 0, 0, '2026-02-27 02:11:02'),
(518, 103, 50, '\"D\"', 0, 0, 0, '2026-02-27 02:12:10');

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
(12, 9, 'Penalaran Umum', 30, 886.12, 1, '2026-02-23 04:13:37');

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
(42, 9, 'QAD4', '2026-02-23 04:48:22', '2026-02-23 04:58:22'),
(43, 9, 'C37Z', '2026-02-23 04:58:22', '2026-02-23 05:08:22'),
(44, 9, '87K3', '2026-02-23 05:13:58', '2026-02-23 05:23:58'),
(45, 9, '7AJP', '2026-02-23 05:24:13', '2026-02-23 05:34:13'),
(46, 9, '2ZN5', '2026-02-26 06:30:27', '2026-02-26 06:40:27'),
(47, 9, 'Z6YW', '2026-02-26 06:40:28', '2026-02-26 06:50:28'),
(48, 9, 'Y2EN', '2026-02-26 06:53:35', '2026-02-26 07:03:35'),
(49, 9, 'TN3C', '2026-02-27 02:03:45', '2026-02-27 02:13:45'),
(50, 9, '7WKQ', '2026-02-27 06:06:00', '2026-02-27 06:16:00');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=520;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
