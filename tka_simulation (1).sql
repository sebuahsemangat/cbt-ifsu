-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 02, 2026 at 05:31 AM
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
(9, 'Simulasi UTBK', '2026-02-23 11:00:00', '2026-04-03 16:00:00', 1, '2026-02-23 04:13:01'),
(10, 'English Exam Narrative Text', '2026-03-02 07:59:00', '2026-03-07 07:59:00', 1, '2026-03-02 01:00:01');

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
(106, 39, 10, 13, '2026-03-02 10:00:50', '2026-03-02 10:39:17', 80.00, 'finished'),
(107, 54, 10, 13, '2026-03-02 10:00:50', '2026-03-02 10:28:56', 95.00, 'finished'),
(108, 65, 10, 13, '2026-03-02 10:00:51', '2026-03-02 10:28:03', 100.00, 'finished'),
(109, 57, 10, 13, '2026-03-02 10:00:52', '2026-03-02 10:38:03', 75.00, 'finished'),
(110, 40, 10, 13, '2026-03-02 10:00:53', '2026-03-02 10:27:35', 90.00, 'finished'),
(111, 46, 10, 13, '2026-03-02 10:00:54', '2026-03-02 10:47:30', 100.00, 'finished'),
(112, 68, 10, 13, '2026-03-02 10:00:54', '2026-03-02 10:54:30', 85.00, 'finished'),
(113, 38, 10, 13, '2026-03-02 10:00:54', '2026-03-02 10:54:37', 75.00, 'finished'),
(114, 51, 10, 13, '2026-03-02 10:00:54', '2026-03-02 10:39:08', 70.00, 'finished'),
(115, 48, 10, 13, '2026-03-02 10:00:56', '2026-03-02 10:47:49', 80.00, 'finished'),
(116, 49, 10, 13, '2026-03-02 10:00:56', '2026-03-02 10:21:58', 100.00, 'finished'),
(117, 56, 10, 13, '2026-03-02 10:00:58', '2026-03-02 10:36:00', 60.00, 'finished'),
(118, 42, 10, 13, '2026-03-02 10:00:58', '2026-03-02 10:25:17', 95.00, 'finished'),
(119, 60, 10, 13, '2026-03-02 10:00:58', '2026-03-02 10:22:07', 95.00, 'finished'),
(120, 67, 10, 13, '2026-03-02 10:00:59', '2026-03-02 10:54:23', 85.00, 'finished'),
(121, 52, 10, 13, '2026-03-02 10:01:06', '2026-03-02 10:23:53', 100.00, 'finished'),
(122, 44, 10, 13, '2026-03-02 10:01:14', '2026-03-02 10:25:25', 55.00, 'finished'),
(123, 41, 10, 13, '2026-03-02 10:01:17', '2026-03-02 10:38:57', 100.00, 'finished'),
(124, 53, 10, 13, '2026-03-02 10:01:23', '2026-03-02 10:25:02', 90.00, 'finished'),
(125, 61, 10, 13, '2026-03-02 10:01:30', '2026-03-02 10:48:15', 80.00, 'finished'),
(126, 59, 10, 13, '2026-03-02 10:02:09', '2026-03-02 10:49:46', 65.00, 'finished'),
(130, 116, 10, 13, '2026-03-03 10:35:10', '2026-03-03 11:04:27', 80.00, 'finished'),
(131, 101, 10, 13, '2026-03-03 10:35:10', '2026-03-03 11:02:14', 75.00, 'finished'),
(132, 102, 10, 13, '2026-03-03 10:35:10', '2026-03-03 10:48:27', 100.00, 'finished'),
(133, 120, 10, 13, '2026-03-03 10:35:11', '2026-03-03 11:02:25', 90.00, 'finished'),
(134, 122, 10, 13, '2026-03-03 10:35:11', '2026-03-03 11:09:03', 95.00, 'finished'),
(135, 104, 10, 13, '2026-03-03 10:35:11', '2026-03-03 10:55:36', 85.00, 'finished'),
(136, 111, 10, 13, '2026-03-03 10:35:12', '2026-03-03 10:58:15', 90.00, 'finished'),
(137, 125, 10, 13, '2026-03-03 10:35:12', '2026-03-03 11:01:00', 40.00, 'finished'),
(138, 108, 10, 13, '2026-03-03 10:35:12', '2026-03-03 10:47:50', 90.00, 'finished'),
(139, 109, 10, 13, '2026-03-03 10:35:13', '2026-03-03 11:02:09', 75.00, 'finished'),
(140, 112, 10, 13, '2026-03-03 10:35:13', '2026-03-03 11:01:00', 30.00, 'finished'),
(141, 119, 10, 13, '2026-03-03 10:35:19', '2026-03-03 11:01:12', 90.00, 'finished'),
(142, 126, 10, 13, '2026-03-03 10:36:22', '2026-03-03 11:09:03', 85.00, 'finished'),
(143, 117, 10, 13, '2026-03-03 10:37:21', '2026-03-03 11:16:25', 80.00, 'finished'),
(144, 118, 10, 13, '2026-03-03 10:37:25', '2026-03-03 11:10:43', 80.00, 'finished'),
(145, 121, 10, 13, '2026-03-03 10:37:39', '2026-03-03 11:10:43', 70.00, 'finished'),
(147, 98, 10, 13, '2026-03-03 10:42:41', '2026-03-03 11:02:08', 95.00, 'finished'),
(148, 105, 10, 13, '2026-03-03 10:43:53', '2026-03-03 11:09:10', 80.00, 'finished'),
(149, 124, 10, 13, '2026-03-03 11:05:07', '2026-03-03 11:16:38', 80.00, 'finished'),
(150, 107, 10, 13, '2026-03-03 11:05:18', '2026-03-03 11:16:39', 90.00, 'finished'),
(166, 127, 9, 15, '2026-04-02 11:59:17', '2026-04-02 11:59:32', 0.00, 'finished'),
(167, 127, 9, 14, '2026-04-02 11:59:36', '2026-04-02 11:59:52', 0.00, 'finished'),
(168, 127, 9, 12, '2026-04-02 11:59:55', '2026-04-02 12:00:58', 265.84, 'finished');

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
(53, 12, NULL, '<p>Ketika sering belajar di perpustakaan, siswa X dapat mencapai hasil belajar yang lebih baik. Namun, ketika X belajar di rumah, hasil belajarnya cenderung kurang optimal.</p><p><br></p><p><strong>Manakah pernyataan berikut yang PALING MUNGKIN menjelaskan perbedaan kedua kondisi tersebut?</strong></p>', '2026-02-23 04:28:17', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Perpustakaan menyediakan lebih banyak buku pelajaran.</p>\", \"B\": \"<p>Siswa X sering terganggu oleh kebisingan di sekitar perpustakaan.</p>\", \"C\": \"<p>Selain buku pelajaran, perpustakaan juga menyediakan novel legendaris.</p>\", \"D\": \"<p>Siswa X merasa lebih nyaman belajar di rumah daripada di perpustakaan.</p>\", \"E\": \"<p>Siswa X dapat berinteraksi dengan teman sebaya ketika belajar di rumah.</p>\"}}'),
(54, 13, NULL, '<p>What is the main purpose of a narrative text?</p>', '2026-03-02 01:22:29', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>To describe a specific person or place</p>\", \"B\": \"<p>To entertain or amuse the readers</p>\", \"C\": \"<p>To explain how something is made</p>\", \"D\": \"<p>To report a news event</p>\", \"E\": \"<p>To persuade people to do something</p>\"}}'),
(55, 13, NULL, '<p>The part of the story that introduces the characters and the setting is called...</p>', '2026-03-02 01:23:26', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Complication</p>\", \"B\": \"<p>Resolution</p>\", \"C\": \"<p>Orientation</p>\", \"D\": \"<p>Re-orientation</p>\", \"E\": \"<p>Coda</p>\"}}'),
(56, 13, NULL, '<p>In a narrative story, the problem that the characters face is called...</p>', '2026-03-02 01:24:10', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Resolution</p>\", \"B\": \"<p>Orientation</p>\", \"C\": \"<p>Complication</p>\", \"D\": \"<p>Identification</p>\", \"E\": \"<p>Description</p>\"}}'),
(57, 13, NULL, '<p>What tense is mostly used in narrative texts?</p>', '2026-03-02 01:24:55', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>Simple Present Tense</p>\", \"B\": \"<p>Simple Past Tense</p>\", \"C\": \"<p>Future Tense</p>\", \"D\": \"<p>Present Continuous Tense</p>\", \"E\": \"<p>Present Perfect Tense</p>\"}}'),
(58, 13, NULL, '<p>The generic structure of narrative text is...</p>', '2026-03-02 01:25:48', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Orientation - Complication - Resolution</p>\", \"B\": \"<p>Goal - Material - Steps</p>\", \"C\": \"<p>Identification - Description</p>\", \"D\": \"<p>General Classification - Description</p>\", \"E\": \"<p>Newsworthy Event - Sources</p>\"}}'),
(59, 13, NULL, '<p>Which of these is a famous legend from Indonesia?</p>', '2026-03-02 01:26:39', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>Cinderella</p>\", \"B\": \"<p>Sangkuriang</p>\", \"C\": \"<p>Snow White</p>\", \"D\": \"<p>Harry Potter</p>\", \"E\": \"<p>Aladdin</p>\"}}'),
(60, 13, NULL, '<p>In narrative text, \'Resolution\' means...</p>', '2026-03-02 01:27:52', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>The beginning of the story</p>\", \"B\": \"<p>The problem of the story</p>\", \"C\": \"<p>How the problem is solved</p>\", \"D\": \"<p>The description of the hero</p>\", \"E\": \"<p>The list of places</p>\"}}'),
(61, 13, NULL, '<p>Narrative texts usually start with the following words, <strong>EXCEPT...</strong></p>', '2026-03-02 01:28:34', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>Once upon a time</p>\", \"B\": \"<p>Long ago</p>\", \"C\": \"<p>One day</p>\", \"D\": \"<p>First, boil the water</p>\", \"E\": \"<p>A long time ago</p>\"}}'),
(62, 13, NULL, '<p>What is a \'fable\'?</p>', '2026-03-02 01:29:15', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>A story about historical facts</p>\", \"B\": \"<p>A story about animals that act like humans</p>\", \"C\": \"<p>A story about a scary ghost</p>\", \"D\": \"<p>A story about how to use a machine</p>\", \"E\": \"<p>A story about future technology</p>\"}}'),
(63, 13, 17, '<p>What was the ant looking for?</p>', '2026-03-02 01:35:20', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Food</p>\", \"B\": \"<p>A Leaf</p>\", \"C\": \"<p>Water</p>\", \"D\": \"<p>A Friend</p>\", \"E\": \"<p>A Tree</p>\"}}'),
(64, 13, 17, '<p>What happened to the ant when she climbed the grass?</p>', '2026-03-02 01:36:08', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>She fell asleep</p>\", \"B\": \"<p>She slipped and fell into the water</p>\", \"C\": \"<p>She found a leaf</p>\", \"D\": \"<p>She met a dove</p>\", \"E\": \"<p>She ate the grass</p>\"}}'),
(65, 13, 17, '<p>Who helped the ant?</p>', '2026-03-02 01:36:42', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>A hunter</p>\", \"B\": \"<p>Another ant</p>\", \"C\": \"<p>A fish</p>\", \"D\": \"<p>A dove</p>\", \"E\": \"<p>A butterfly</p>\"}}'),
(66, 13, 17, '<p>How did the dove help the ant?</p>', '2026-03-02 01:37:28', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>By flying away</p>\", \"B\": \"<p>By calling for help</p>\", \"C\": \"<p>By dropping a leaf into the water</p>\", \"D\": \"<p>By picking up the ant with its beak</p>\", \"E\": \"<p>By drinking the water</p>\"}}'),
(67, 13, 17, '<p>\"Soon, it carried her safely to dry ground.\" The word \"<strong>her</strong>\" refers to...</p>', '2026-03-02 01:38:09', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>The leaf</p>\", \"B\": \"<p>The dove</p>\", \"C\": \"<p>The spring</p>\", \"D\": \"<p>The grass</p>\", \"E\": \"<p>The ant</p>\"}}'),
(68, 13, NULL, '<p>A story that explains how a place or a mountain was formed is called a...</p>', '2026-03-02 01:39:01', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"<p>Legend</p>\", \"B\": \"<p>Fairytale</p>\", \"C\": \"<p>Fable</p>\", \"D\": \"<p>Short story</p>\", \"E\": \"<p>Poem</p>\"}}'),
(69, 13, 18, '<p>What was the Lion doing at the beginning of the story?</p>', '2026-03-02 01:55:22', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>Eating a mouse</p>\", \"B\": \"<p>Hunting for food</p>\", \"C\": \"<p>Sleeping in the forest</p>\", \"D\": \"<p>Running after a hunter</p>\", \"E\": \"<p>Roaring loudly</p>\"}}'),
(70, 13, 18, '<p>Why did the Lion wake up?</p>', '2026-03-02 01:56:07', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>Because he was hungry</p>\", \"B\": \"<p>Because a Mouse ran on him</p>\", \"C\": \"<p>Because it was morning</p>\", \"D\": \"<p>Because the hunters arrived</p>\", \"E\": \"<p>Because the forest was loud</p>\"}}'),
(71, 13, 18, '<p>What did the Mouse promise to the Lion?</p>', '2026-03-02 01:56:55', 'pg', '{\"type\": \"single_choice\", \"correct\": \"C\", \"options\": {\"A\": \"<p>To give him food</p>\", \"B\": \"<p>To show him the way</p>\", \"C\": \"<p>To help him one day</p>\", \"D\": \"<p>To leave the forest</p>\", \"E\": \"<p>To be his best friend</p>\"}}'),
(72, 13, 18, '<p>How did the Mouse help the Lion?</p>', '2026-03-02 01:57:38', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>By calling the hunters</p>\", \"B\": \"<p>By biting the hunters\' legs</p>\", \"C\": \"<p>By bringing a big knife</p>\", \"D\": \"<p>By cutting the rope with his teeth</p>\", \"E\": \"<p>By hiding the Lion in a cave</p>\"}}'),
(73, 13, 18, '<p>What is the moral value of the story?</p>', '2026-03-02 01:58:17', 'pg', '{\"type\": \"single_choice\", \"correct\": \"B\", \"options\": {\"A\": \"<p>Don\'t wake up a sleeping lion</p>\", \"B\": \"<p>Small friends can be great helpers</p>\", \"C\": \"<p>Lions are the kings of the forest</p>\", \"D\": \"<p>Hunters are very dangerous</p>\", \"E\": \"<p>Teeth must be sharp to eat rope</p>\"}}'),
(74, 14, NULL, '<p>sfdsf</p>', '2026-03-30 04:32:46', 'pg', '{\"type\": \"single_choice\", \"correct\": \"E\", \"options\": {\"A\": \"<p>sdf</p>\", \"B\": \"<p>sdf</p>\", \"C\": \"<p>sdf</p>\", \"D\": \"<p>sdf</p>\", \"E\": \"<p>sdf</p>\"}}'),
(75, 15, NULL, '<p>sdf</p>', '2026-03-30 04:33:02', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"<p>sdf</p>\", \"B\": \"<p>sdf</p>\", \"C\": \"<p>sdf</p>\", \"D\": \"<p>sdf</p>\", \"E\": \"<p>sdf</p>\"}}');

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
(16, 12, 'Grafik modal dan pendapatan', 'image', '<p><br></p>', '1771820763_699bd6dbabb58.jpg', '2026-02-23 04:26:03'),
(17, 13, 'The Ant and the Dove', 'text', '<p><strong>The Ant and the Dove</strong></p><p>One hot day, an ant was searching for some water. After walking around for some time, she came to a spring. To reach the spring, she had to climb up a blade of grass. While making her way up, she slipped and fell into the water. She could have drowned if a dove up a nearby tree had not helped her. Seeing that the ant was in trouble, the dove quickly plucked a leaf and dropped it into the water near the struggling ant. The ant moved towards the leaf and climbed up there. Soon, it carried her safely to dry ground.</p>', NULL, '2026-03-02 01:34:39'),
(18, 13, 'The Lion and The Mouse', 'text', '<h5><strong>The Lion and the Mouse</strong></h5><p>A Lion was sleeping in the forest. A little Mouse began running up and down on him. This woke up the Lion. The Lion placed his big paw on the Mouse and opened his mouth to swallow him. \"Pardon, O King!\" cried the little Mouse. \"If you let me go, I will help you one day.\" The Lion laughed and let him go. A few days later, some hunters caught the Lion in a net. The Mouse heard the Lion\'s roar and ran to help. He cut the rope with his sharp teeth and set the Lion free. The Lion was very happy and thanked the little Mouse.</p>', NULL, '2026-03-02 01:41:41');

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
(26, 'siswa42', '$2y$10$.lVCLtG2lS0OginKWeoHEerfxyYBNTzdO/9aMLSJbvCwaYwdRuldO', 'Apep Wahyudin', 'XII IPA 1', 0, '2026-03-04 14:50:34', '2026-02-23 04:31:34'),
(37, 'aditia78', '$2y$10$MMazaTPGBWM5HeK16aYNEe2ms0SPovJTw7pGdXFv3q48WYRWKvysa', 'ADITIA TRI SUBAGJA', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:20'),
(38, 'alia56', '$2y$10$DP.4MNZZWgIh8GQWp9jWl./kwTHP3wPbEzKxnkWkjrIrYfRCAcxgW', 'ALIA RAHAYU', 'XI-RPL 9', 0, '2026-03-02 10:48:08', '2026-03-02 01:08:20'),
(39, 'alif65', '$2y$10$A1UzmLvneQSO5vpp8RpJA.09qc4s8KtXiNkFyCXzThH8TM37X1Vdm', 'ALIF RIZQI RAMDHANI', 'XI-RPL 9', 0, '2026-03-02 09:58:57', '2026-03-02 01:08:20'),
(40, 'amila64', '$2y$10$3mlvXfThid.3BCIYKyqrWeYGSgemQQwQEG9y.s3JzXWXfKF0Hhhpe', 'AMILA PRATIWI', 'XI-RPL 9', 0, '2026-03-02 10:18:39', '2026-03-02 01:08:20'),
(41, 'arya99', '$2y$10$RNVMtqsJ/FJMXqjAMKNcROXZuVcZByXK8V6DZNfO/Fz5WLYQA0q52', 'ARYA MAULANA MALIK IBRAHIM', 'XI-RPL 9', 0, '2026-03-02 09:59:09', '2026-03-02 01:08:21'),
(42, 'bimo73', '$2y$10$M8bKXxv2enJAG5u.ugFtZOLcaFgXIv3dz4VkNz8BipKlmNN8/opIK', 'BIMO SANDY SANTOSO', 'XI-RPL 9', 0, '2026-03-02 09:59:13', '2026-03-02 01:08:21'),
(43, 'bitta88', '$2y$10$4sMa4l3cJz7Sk8x0yEsbDergUn.f1cCjoeqntUuqq8ipPS38.Oa7e', 'BITTA YULIDA', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:21'),
(44, 'dani69', '$2y$10$KS3Qa2ZacZ.LRHVuZ/PfguQTZTJWj7DrdM/UdYbFDWdyByVTBNVfa', 'DANI DANIAL', 'XI-RPL 9', 0, '2026-03-02 10:00:47', '2026-03-02 01:08:21'),
(45, 'dirly28', '$2y$10$sUhqW5ltRvdMuhsgHeLhM.dMjEaDm0XGL67EnLA8iyfwwt..ZdLaC', 'DIRLY FUJIYANA', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:21'),
(46, 'diva37', '$2y$10$WX5FoVwa6nf2qhXndg1OquglXGSVOW.3bYaQqqjQ1wqmxzDb.ET8O', 'DIVA NUGRAHA', 'XI-RPL 9', 0, '2026-03-02 10:46:50', '2026-03-02 01:08:21'),
(47, 'eka11', '$2y$10$MnJ/KAw3ofOBnOoRKIRoi.G15MgV5KF0MqLu.wEWc0ka/n/MWK3Ya', 'EKA NURDIANSYAH', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:21'),
(48, 'ergan97', '$2y$10$ZV2q6RuM1UQ3FbCknyB7VOrRc9MuB3uAf2POFzKAzqVtytPJgg/Xa', 'ERGAN ERLANA', 'XI-RPL 9', 0, '2026-03-02 10:46:50', '2026-03-02 01:08:21'),
(49, 'erik47', '$2y$10$MJfyneIOPnizD/NTW5299.yNYJdywJBKaC9zA8nm72t1GxfSKOMWa', 'ERIK LUKMAN', 'XI-RPL 9', 0, '2026-03-02 09:59:41', '2026-03-02 01:08:21'),
(50, 'fachri82', '$2y$10$EIVJV515y8hKwJb7rapolet6ZLKHEQ.9U0AJol5kgUkMDmGWP3BgC', 'FACHRI FARIANSYAH AGUSTIN', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:21'),
(51, 'fairus33', '$2y$10$b5Kr1fZmmZMrOdlqbAmyAuus9y/i7xGHogCfJmxdGaE4DYultdr7.', 'FAIRUS ADIL MUZAKY', 'XI-RPL 9', 0, '2026-03-02 09:59:54', '2026-03-02 01:08:21'),
(52, 'firman18', '$2y$10$jq322qtqj.rXo29Kr9ReIOaVufE3uBlXWDqj/qILNcSnf9EHgkvL.', 'FIRMAN MUHAMAD NASRULLOH', 'XI-RPL 9', 0, '2026-03-02 10:00:33', '2026-03-02 01:08:21'),
(53, 'ganip56', '$2y$10$lNfCpl1hg8KjRwgKMrGZsOQ08s3D9pKdIoAA4M09gl/B3Lg/GFCb2', 'GANIP ADIWINATA', 'XI-RPL 9', 0, '2026-03-02 10:00:26', '2026-03-02 01:08:21'),
(54, 'ghadis99', '$2y$10$YqtCTYnw8FkSoRZsJDlO/O.x9nYeCfuDUlw9XSW/HBXMmF3hue3lm', 'GHADIS LIDIA FIRANALLAH', 'XI-RPL 9', 0, '2026-03-02 09:59:32', '2026-03-02 01:08:21'),
(55, 'gustiar38', '$2y$10$q24MoHQ4fRrV2CACp6SGZujdJLcyTjq6LeHGamB0GijPMhdXxJayu', 'GUSTIAR HENDRAWAN', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:21'),
(56, 'ina25', '$2y$10$Qb.hlBrXjDEVTkmtZEoKdeHX0JK7F9d9FB8vx02pLRmuYjMwodKSe', 'INA MEILANI', 'XI-RPL 9', 0, '2026-03-02 10:00:12', '2026-03-02 01:08:21'),
(57, 'kiki53', '$2y$10$7/.hjzHSDUKz7PJGZNOwKeTQ/nnsN9KCO4xeqfHyguGQQegUFE7Ze', 'KIKI ADITIA PRATAMA', 'XI-RPL 9', 0, '2026-03-02 09:59:19', '2026-03-02 01:08:22'),
(58, 'm.fachry46', '$2y$10$RFuLhYoDgKaIJSfqE7Y/eueQRm1RAM.yoFMTd1dI6oVSr9rYi.qMm', 'M. FACHRY AL-FARIZI', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:22'),
(59, 'muhamad90', '$2y$10$gXkcF.eolW0ah6HitIgSZepmaVKAvbcR0IUquMmhGfsBl2PJc.5iO', 'MUHAMAD FAHMI NUR IMAN', 'XI-RPL 9', 0, '2026-03-02 10:47:11', '2026-03-02 01:08:22'),
(60, 'muhamad38', '$2y$10$JHmLiTmpg14Oi6vlbS1XhOOpFIE8Hkj02PW5INlpXjwgV2BlaNrE.', 'MUHAMAD FAJAR GEMILANG RAMADHAN', 'XI-RPL 9', 0, '2026-03-02 09:59:30', '2026-03-02 01:08:22'),
(61, 'muhammad43', '$2y$10$DfRbKGpDff6LNvn76McRAuBFsfTjUg0HBfF7E8PChbPY6FUFzsOxy', 'MUHAMMAD IQBAL RAMADAN', 'XI-RPL 9', 0, '2026-03-02 10:47:08', '2026-03-02 01:08:22'),
(62, 'muhammad18', '$2y$10$mnG4MIuIfKSs1B1HdDQyQ.cMDZ5w07o4gJ3GyUa.9w1OUCmVO93Jm', 'MUHAMMAD RAJIL MAHASIN GUNAWAN', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:22'),
(63, 'muhammad96', '$2y$10$0D9MK2sqoh3DuZ9WNZ1I2.7ei84juWnSQTfKIrDUXZOH9jzuT6f2C', 'MUHAMMAD RIZKA AL-FATH', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:22'),
(64, 'novianti89', '$2y$10$FjHHGMUiKOZEu9ooH3Zsge5hpgSnj4tJqfuCBxZm6KggmSD0p.wdG', 'NOVIANTI ADRILIA', 'XI-RPL 9', 0, '2026-03-02 10:10:31', '2026-03-02 01:08:22'),
(65, 'poppy95', '$2y$10$vzJCNlSprxw0d8uUzLPME..Yv7Ibiyk./XWbXlv1.cLepsYb5RDfu', 'POPPY ARINI', 'XI-RPL 9', 0, '2026-03-02 09:59:05', '2026-03-02 01:08:22'),
(66, 'reva95', '$2y$10$6JmqIxTuXukGefsr8OpGO.TsWHZpRDDiYeAJ9tM/xYamxsmsAgdKW', 'REVA NURAINI', 'XI-RPL 9', 0, '2026-03-02 10:03:05', '2026-03-02 01:08:22'),
(67, 'risma63', '$2y$10$NYYqVKwm8lzZH.HJ28RgGeNqTJIX0DkQbjZqnNBRYycVVD5RlCWSW', 'RISMA', 'XI-RPL 9', 0, '2026-03-02 10:47:20', '2026-03-02 01:08:22'),
(68, 'sonia25', '$2y$10$E6OQNjAIwEbhGO/.ADGzDOLFSWqYZbBHFi6XqMPvmOyatYrpKy.M6', 'SONIA MAYZURA', 'XI-RPL 9', 0, '2026-03-02 10:48:03', '2026-03-02 01:08:22'),
(69, 'syarifah82', '$2y$10$9IdUxtXFA2RARGPbzAGUj.lGSKJtR0YDooc7L/kdOXByBMgzTExcC', 'SYARIFAH RAMADANI', 'XI-RPL 9', 0, NULL, '2026-03-02 01:08:22'),
(70, 'andri27', '$2y$10$uu9vZNEa/u8h.vJMJN10lutnl2EN5J/OLK/AzUTyAixpHyrLcZFne', 'ANDRI FIRMANSYAH', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(71, 'ari85', '$2y$10$AMcL.4rW.ooZ3vXk2I42fe0dnXQsIoA5ra0zgH9Wr/nBVyDuUJT8y', 'ARI SLAMET RIYADI', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(72, 'asep53', '$2y$10$waSrjEYzsbavR/KZHVCRluMDiE/GZMMeG9UHbsFOjxtgCJzPdN9KG', 'ASEP RAHAYU', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(73, 'bagas34', '$2y$10$rfLK4SqygWcFaCp9i1tzWeOGrIcRg0Qik3pB1lGH6pwxzPta3ctju', 'BAGAS DWI SYAHPUTRA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(74, 'cantika49', '$2y$10$tUSpLFisdebTfTpc1nY0VuydqFpEKjajQdz5oDPavl4xvgeJVHVxO', 'CANTIKA DELIZHA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(75, 'dhena21', '$2y$10$Mj6GDe46zksvF2F3gpB0I.VeDyJasxec/D3B2RTTw/5iXulQsQcja', 'DHENA PURMANA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(76, 'dimas44', '$2y$10$Eedrh1Ya0y2AxVS.m8r8KOWhtb4tUMLTgTjoqUUQ9Sbs4xZXMdXXm', 'DIMAS FAHREZA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(77, 'elvan56', '$2y$10$lUyktMYCg9ybMFykEdhmgeSA96q4/Wgj3z.cBb758/q4hsAQkJLtO', 'ELVAN JULIAN NURAHMAN', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:39'),
(78, 'eva42', '$2y$10$EYoIQWAQsC4iZjAbwh/LJeWTgvMMea3Kr1PGcsc.nmyew9GLsW92S', 'EVA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(79, 'fani59', '$2y$10$bzSBPEm4l76lgCN834IksunmATq9zuKkz6ejKvWnWglf.2rKb9j2C', 'FANI SITI SHOLIHAT', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(80, 'fauzi35', '$2y$10$9Tmh3jnWoyd9zxqmwreWJuLzwHXOTidpKRGK0A5PS/SFU12zUGTvq', 'FAUZI MUBAROK', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(81, 'fitri12', '$2y$10$VShSTKLlDHKKE5ie2jghD.h1XW/e5UXdSsDJ96HE6ErMFWTDQ4NTm', 'FITRI HANDAYANI KURNIAWAN', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(82, 'fitri65', '$2y$10$/4M2Si7xM31Yek8ynDmuM.QIsUWHXVNODYCX3l/L7.irCDERQ9S9.', 'FITRI OKTAVIANI', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(83, 'gumilar14', '$2y$10$3nePoI8fwmFw/bn8gYTwUuAOoK2AO.ZWrzweYVuiweVWeXzs3AF0e', 'GUMILAR RAHAYU', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(84, 'gunawan12', '$2y$10$41bFSDxRfk6nOysKD3RVPOxJKbmYkH.mXWMMxQC9Q0zaIppoRuZVC', 'GUNAWAN MANGGALA PUTRA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(85, 'hari48', '$2y$10$lblv7OPYKh4lf8yMnCwlnuwyB4FurxLzsX3fo4orm5LTWGpkeHCl6', 'HARI TANOTO', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(86, 'hera77', '$2y$10$6S/Iu5gBClzvAtY.QJbQBuQi16KW5yWFMxT.vsgakqUKXNrSblely', 'HERA IHSANIA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(87, 'muhamad83', '$2y$10$S5duPKvJT2OPpih2Ti9uFO7ZG8FL494tlplXH3QmSh0uCxNfPcJwm', 'MUHAMAD PAKHRI PAUZI', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(88, 'muhamad91', '$2y$10$YooRAkkOWaYfUG5Cjfl4kO4sFO.aZJZywEZG9jSLWHAeIC/52mUAi', 'MUHAMAD RENDY', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(89, 'muhamad55', '$2y$10$65l6rW6vHkjNZNLjSwHQaua826OCTmgDd6RmVrBRxPBjCwFv9t9wG', 'MUHAMAD RIZKI PEBRIAN', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(90, 'muhamad72', '$2y$10$U96W762t.pDvH2egr/HTQe9Mk/mdGiXBaaNhMLDAAdeqc3WUn/oOq', 'MUHAMAD RIZKY RAMADHAN', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(91, 'muhammad39', '$2y$10$Evn0x1ZH7dNJQVONGoNauObOT52ZhG1dC7w.9mNzXPKUb/rrELfau', 'MUHAMMAD RIZKI PRATAMA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(92, 'nurani11', '$2y$10$Ez5fmtHuC6hBgwJidcCkSe1BsN08XpYWixP1ZemopPqbYrhw.fy8.', 'NURANI RAHMAT PUTRI', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(93, 'pebica75', '$2y$10$3LD5HZILfiY.0innabKpUuenft0BLrgdn0reL.3W/FUkXUJRu5k.i', 'PEBICA AZ ZAHRA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(94, 'putri43', '$2y$10$K4FMWLqV2y6cKORxfMfHheI5w4O.YBmKcPINHd1NDmx94wBCU/xqi', 'PUTRI AISYAH NURSAMYAH', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:40'),
(95, 'rahman49', '$2y$10$Yx7Hvfj3jFQSLZy5UbmqVeeqdFuv/FiSqJD89wzvnXDorQX88.FtS', 'RAHMAN NUR HIDAYAT', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:41'),
(96, 'vauzan36', '$2y$10$MsgNmv8ka372E0y79WeG.ewJ/OhmM0rYAgrQYwicTX.YnC1eg7HTC', 'VAUZAN FAHRI FAZRULOH', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:41'),
(97, 'yesi13', '$2y$10$81FR/PmwQsHQOPi1t4i4/OeMWJ3HJPbNNRxzbCRfJjfa19nwnpZ6a', 'YESI NURMEILANI CANTIKA', 'XI-DKV 2', 0, NULL, '2026-03-03 00:33:41'),
(98, 'aditia87', '$2y$10$5Q6.wEm9kCJizBYzRz8YN.ELfT8dKV.JEwNPodAgJNkUdJS4FnmuW', 'ADITIA RIZKI PRATAMA PUTRA', 'XI-DKV 3', 0, '2026-03-03 10:42:12', '2026-03-03 03:10:21'),
(99, 'alif98', '$2y$10$RYEn0n0vlguGd3KLXiRVD.56vDpd.UUiJGCv1knqClTp0NB/f56cS', 'ALIF PUTRA', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:21'),
(100, 'cica60', '$2y$10$4TGSqe427QnYPjfIYONQxeAh/RogRulVHwHe6.l2TVZ2cdUnNjcJK', 'CICA CINTIA', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:21'),
(101, 'daffa65', '$2y$10$m6wHNkD2SmYPnoA6fEbGD.l6DwzMjmDAvn/IQJhMFo2R5ol9U0cVW', 'DAFFA AHMAD MUDOFFAR', 'XI-DKV 3', 0, '2026-03-03 10:34:44', '2026-03-03 03:10:21'),
(102, 'elfira75', '$2y$10$ncYG/GiBjyjPN1679S6X0uBMbnrrpSpVSlk0dvu54too/dynvmLmO', 'ELFIRA RUDIASTIRA ERVANEZ', 'XI-DKV 3', 0, '2026-03-03 10:33:53', '2026-03-03 03:10:21'),
(103, 'lucky92', '$2y$10$uerPrajXAstyESpT9QwFd.QwIRADhA9KNz/vMgJFKVREnwc7O5MMS', 'LUCKY NUGRAHA', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:22'),
(104, 'm.achbar48', '$2y$10$/XlhC8ObLwaQZcELBfbUVelhB9hrqybq7siOz87WDoW8D3rFOcl7G', 'M. ACHBAR ZHAKARIA', 'XI-DKV 3', 0, '2026-03-03 10:33:47', '2026-03-03 03:10:22'),
(105, 'muhamad20', '$2y$10$AzTl23kvGA4JO8XrQLODGuZng.CaublNWDxhE/kCA3iTV4fr.d38y', 'MUHAMAD GALANG ADIANSAH', 'XI-DKV 3', 0, '2026-03-03 10:33:47', '2026-03-03 03:10:22'),
(106, 'muhammad79', '$2y$10$ohDlYgJ2/p6dMKwjJHR7Zuv4VG7y9JzKgayljjMpF9y8gJuCXVeUe', 'MUHAMMAD HABIBI', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:22'),
(107, 'muhammad81', '$2y$10$zLrGHybEAhTMqclOHo8hSuGwqAWGZdaK7tsWaY5l3CQrVM4ZIVrzq', 'MUHAMMAD NURUL IKHSAN', 'XI-DKV 3', 0, '2026-03-03 11:04:47', '2026-03-03 03:10:22'),
(108, 'nadia59', '$2y$10$3goH3DSd4xC/7RMpFpLLxObBoXTkH04aSYMsuJ8uPnufna9/R/Vzq', 'NADIA LEDISTA', 'XI-DKV 3', 0, '2026-03-03 10:34:00', '2026-03-03 03:10:22'),
(109, 'nailah91', '$2y$10$FHe7BCJPkDFHTXUhLVbQDOF3fOmCw2vlxEYKPmsfbhe2qHwmWuAHq', 'NAILAH NUR HANIFAH', 'XI-DKV 3', 0, '2026-03-03 10:34:10', '2026-03-03 03:10:22'),
(110, 'nindian45', '$2y$10$SuYX6d.5oWjSY9YlAcSeTu8/L/9UdmhlZVvAHCjei2MM7znMyMFuO', 'NINDIAN NOVITRI', 'XI-DKV 3', 0, '2026-03-03 10:33:48', '2026-03-03 03:10:22'),
(111, 'nurul88', '$2y$10$86GY5sX6.2rUl41OL9HqJOJtpnBv63cnbuqLCXJkU3WNqu71akhFC', 'NURUL HILMI AMALYA', 'XI-DKV 3', 0, '2026-03-03 10:33:42', '2026-03-03 03:10:22'),
(112, 'puspa71', '$2y$10$beydCt/CWRII9hNhC4a/3uDlf0hRFBTt5Rml11NOpUvmToeVCMche', 'PUSPA AYU SAFITRI', 'XI-DKV 3', 0, '2026-03-03 10:34:54', '2026-03-03 03:10:22'),
(113, 'rendi94', '$2y$10$FMlN00FikXZNjUJQsSmUGeT0th62wfhwdWUM0TM.fBCdkb/O7e8ie', 'RENDI SAEPUDIN', 'XI-DKV 3', 0, '2026-03-03 11:09:47', '2026-03-03 03:10:22'),
(114, 'rendy54', '$2y$10$Kw0EyAi4/ODetb12rP/yMeTTXQlbNpA1QPXnyf.WrtOxX6Kvp5LdO', 'RENDY EVAN PRATAMA', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:22'),
(115, 'rivaldi64', '$2y$10$JSAuQH293K7h3XQoKHpwX.gULpfItY7U7C3BjNmMGpinTt7lHjlPy', 'RIVALDI PUTRA', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:22'),
(116, 'ryan90', '$2y$10$v3MY.oe6OR76DW8a/AVKq.6TYzIP8RnmYSZUsRIJheRJaAj8ZDMQi', 'RYAN FAJAR NUGRAHA', 'XI-DKV 3', 0, '2026-03-03 10:33:53', '2026-03-03 03:10:22'),
(117, 'ryofan22', '$2y$10$hSEMgRKlctZS34XiF8NZOuLxVHDTFMyfoUtsfYXUsrTHvUC3Rp/kS', 'RYOFAN ANAZRIL', 'XI-DKV 3', 0, '2026-03-03 10:37:10', '2026-03-03 03:10:22'),
(118, 'salma90', '$2y$10$WFjEQS2V.SUc6Vj7FlLO1Ow/.ithErxknb4eriJMMWDvUSoRxxlXi', 'SALMA NUR\'AINI', 'XI-DKV 3', 0, '2026-03-03 10:36:54', '2026-03-03 03:10:22'),
(119, 'sani98', '$2y$10$BwJoXWYXOD0HOmcE76SWXudpqLU9KhGShr/c2t6hyXzBzjBN1l2Ie', 'SANI AGINA SOLIHAT', 'XI-DKV 3', 0, '2026-03-03 10:34:30', '2026-03-03 03:10:23'),
(120, 'teguh53', '$2y$10$hfN.9KrZU8o4Yk9AHifEa.z7K26kJ2h2z6IElwkzj01fQX/BvOGN6', 'TEGUH ZAELANI', 'XI-DKV 3', 0, '2026-03-03 10:34:09', '2026-03-03 03:10:23'),
(121, 'ulfah50', '$2y$10$CFSPX.tfp8NJJ0sZsdIAjuy9UWhyl5swoVx/WE8OwRfQLWPTWZflG', 'ULFAH OCTIVIANI', 'XI-DKV 3', 0, '2026-03-03 10:36:59', '2026-03-03 03:10:23'),
(122, 'widiana77', '$2y$10$clf4yQ0aylP.j9thM0f.LunA0Dg3.URKV0NCIDxqFX0FlmBti0KrW', 'WIDIANA', 'XI-DKV 3', 0, '2026-03-03 10:34:25', '2026-03-03 03:10:23'),
(123, 'willyam80', '$2y$10$m4AAgZ33aYGGIAhiZte8X..DvXtwiG.OA7gTUzsoy5cyDE8GNzGti', 'WILLYAM NURDIANSYAH', 'XI-DKV 3', 0, NULL, '2026-03-03 03:10:23'),
(124, 'yana29', '$2y$10$TLF0jj4ws13V62f.zS7l9eau2z94/ukisvsdKKswDBxuj.7yWsuD6', 'YANA CAHYANA JULIAN', 'XI-DKV 3', 0, '2026-03-03 11:04:35', '2026-03-03 03:10:23'),
(125, 'yeni39', '$2y$10$ypKGTR57aVB3CoM26XSureMNqWOR6vTDNQMXMbyRbiPOiYBFV7/ta', 'YENI FITRIYANI', 'XI-DKV 3', 0, '2026-03-03 10:34:14', '2026-03-03 03:10:23'),
(126, 'yosep48', '$2y$10$GnApBj9DYVX/EvQhB3P2oO6VkOyZP29kWa9nLcz0ASjKnFuzsZqdq', 'YOSEP NUR FADILLAH', 'XI-DKV 3', 0, '2026-03-03 10:35:35', '2026-03-03 03:10:23'),
(127, 'apep85', '$2y$10$LbuK0MiUJbetLCL/N0WI5Oy6vQvKIa47G/a/trjktkVONl/sUe..O', 'Apep', 'XI-RPL 1', 1, '2026-04-02 12:05:51', '2026-03-04 07:47:45');

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
(557, 119, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:01:18'),
(558, 123, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:01:30'),
(559, 108, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:01:47'),
(560, 116, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:01:38'),
(562, 110, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:01:48'),
(563, 119, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:01:50'),
(564, 117, 68, '\"D\"', 0, 0, 0, '2026-03-02 03:01:57'),
(565, 106, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:06:33'),
(567, 113, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:02:10'),
(568, 107, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:02:12'),
(569, 120, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:02:23'),
(570, 112, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:02:28'),
(571, 125, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:02:35'),
(572, 121, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:02:35'),
(573, 112, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:02:38'),
(574, 107, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:02:39'),
(575, 120, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:03:04'),
(576, 121, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:03:07'),
(577, 110, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:03:06'),
(579, 115, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:03:11'),
(580, 110, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:24:30'),
(581, 118, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:03:16'),
(582, 113, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:03:16'),
(583, 119, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:03:19'),
(584, 125, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:03:35'),
(585, 116, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:03:41'),
(586, 126, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:03:42'),
(587, 107, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:03:47'),
(588, 109, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:04:11'),
(591, 116, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:04:00'),
(593, 108, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:04:06'),
(594, 106, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:06:44'),
(595, 114, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:04:09'),
(596, 121, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:04:10'),
(598, 107, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:04:11'),
(599, 115, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:28:28'),
(600, 116, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:04:23'),
(601, 106, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:33:44'),
(602, 110, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:04:31'),
(603, 125, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:04:33'),
(604, 119, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:04:36'),
(605, 110, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:04:39'),
(606, 112, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:04:40'),
(607, 107, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:04:41'),
(608, 111, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:04:42'),
(609, 126, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:04:44'),
(610, 116, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:04:45'),
(611, 117, 65, '\"B\"', 0, 0, 0, '2026-03-02 03:04:47'),
(612, 109, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:04:48'),
(613, 118, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:04:52'),
(614, 106, 59, '\"E\"', 0, 0, 0, '2026-03-02 03:24:26'),
(615, 125, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:05:00'),
(616, 123, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:34:43'),
(617, 110, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:05:02'),
(618, 121, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:05:10'),
(619, 113, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:05:12'),
(620, 114, 60, '\"B\"', 0, 0, 0, '2026-03-02 03:27:56'),
(621, 109, 73, '\"C\"', 0, 0, 0, '2026-03-02 03:06:15'),
(623, 122, 57, '\"A\"', 0, 0, 0, '2026-03-02 03:05:35'),
(624, 117, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:05:36'),
(625, 108, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:05:38'),
(626, 111, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:05:39'),
(627, 116, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:05:39'),
(628, 119, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:05:40'),
(629, 107, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:05:41'),
(630, 120, 54, '\"C\"', 0, 0, 0, '2026-03-02 03:06:12'),
(638, 124, 69, '\"E\"', 0, 0, 0, '2026-03-02 03:05:55'),
(639, 111, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:23:30'),
(640, 116, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:06:46'),
(641, 123, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:06:09'),
(642, 118, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:06:11'),
(645, 126, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:06:14'),
(646, 112, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:06:15'),
(648, 110, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:06:17'),
(649, 117, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:06:25'),
(650, 110, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:08:35'),
(652, 123, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:06:38'),
(653, 126, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:06:37'),
(655, 112, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:06:38'),
(656, 121, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:06:42'),
(660, 117, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:06:47'),
(661, 115, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:06:47'),
(662, 124, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:06:48'),
(663, 120, 60, '\"B\"', 0, 0, 0, '2026-03-02 03:06:50'),
(665, 122, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:06:52'),
(666, 108, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:06:53'),
(667, 112, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:37:18'),
(668, 118, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:06:59'),
(669, 120, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:06:59'),
(670, 116, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:06:59'),
(671, 122, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:07:04'),
(673, 113, 63, '\"B\"', 0, 0, 0, '2026-03-02 03:33:59'),
(674, 123, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:07:18'),
(675, 107, 67, '\"A\"', 1, 0, 0, '2026-03-02 03:07:20'),
(676, 116, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:18:11'),
(678, 108, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:07:20'),
(679, 122, 73, '\"A\"', 0, 0, 0, '2026-03-02 03:07:36'),
(681, 109, 70, '\"E\"', 0, 0, 0, '2026-03-02 03:07:23'),
(682, 123, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:07:26'),
(683, 112, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:49:15'),
(684, 117, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:07:29'),
(685, 118, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:07:34'),
(687, 109, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:07:35'),
(689, 108, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:07:38'),
(691, 117, 57, '\"A\"', 0, 0, 0, '2026-03-02 03:35:12'),
(695, 111, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:24:10'),
(696, 121, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:08:00'),
(697, 119, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:08:01'),
(698, 110, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:08:01'),
(700, 108, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:08:04'),
(701, 107, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:08:04'),
(702, 112, 60, '\"A\"', 0, 0, 0, '2026-03-02 03:28:58'),
(704, 124, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:08:11'),
(705, 125, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:08:13'),
(706, 116, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:08:14'),
(707, 115, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:08:15'),
(708, 119, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:08:16'),
(709, 123, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:08:20'),
(710, 106, 61, '\"E\"', 0, 0, 0, '2026-03-02 03:08:21'),
(711, 122, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:08:27'),
(712, 107, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:08:35'),
(714, 108, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:08:38'),
(715, 124, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:09:31'),
(716, 119, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:08:44'),
(717, 123, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:28:05'),
(719, 106, 68, '\"B\"', 0, 0, 0, '2026-03-02 03:26:20'),
(720, 112, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:09:00'),
(721, 108, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:09:11'),
(722, 118, 67, '\"A\"', 0, 0, 0, '2026-03-02 03:09:22'),
(723, 121, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:09:22'),
(724, 126, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:09:25'),
(725, 119, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:09:28'),
(726, 116, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:09:30'),
(728, 123, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:28:29'),
(730, 107, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:09:50'),
(731, 121, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:09:43'),
(732, 125, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:09:43'),
(733, 108, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:09:47'),
(735, 118, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:09:50'),
(736, 110, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:09:53'),
(737, 119, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:09:54'),
(738, 119, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:10:35'),
(740, 124, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:10:06'),
(741, 108, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:10:09'),
(742, 122, 72, '\"E\"', 0, 0, 0, '2026-03-02 03:10:09'),
(743, 113, 67, '\"D\"', 0, 0, 0, '2026-03-02 03:37:17'),
(744, 110, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:10:14'),
(745, 118, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:10:46'),
(747, 116, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:10:22'),
(749, 121, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:10:24'),
(753, 117, 67, '\"D\"', 0, 0, 0, '2026-03-02 03:10:36'),
(754, 126, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:10:37'),
(756, 112, 73, '\"A\"', 0, 0, 0, '2026-03-02 03:10:47'),
(757, 106, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:23:39'),
(758, 124, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:10:53'),
(759, 108, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:10:57'),
(760, 122, 56, '\"E\"', 0, 0, 0, '2026-03-02 03:10:57'),
(761, 123, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:33:22'),
(762, 109, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:14:53'),
(764, 111, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:25:36'),
(765, 108, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:11:12'),
(768, 118, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:11:21'),
(769, 122, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:11:28'),
(770, 123, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:11:28'),
(771, 115, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:11:34'),
(772, 108, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:11:37'),
(773, 110, 67, '\"B\"', 0, 0, 0, '2026-03-02 03:11:45'),
(774, 117, 63, '\"B\"', 0, 0, 0, '2026-03-02 03:12:00'),
(775, 116, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:04'),
(777, 108, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:12:05'),
(778, 110, 63, '\"A\"', 0, 0, 0, '2026-03-02 03:12:08'),
(779, 121, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:12:09'),
(780, 122, 60, '\"B\"', 0, 0, 0, '2026-03-02 03:12:18'),
(781, 107, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:12:20'),
(782, 110, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:22:47'),
(783, 123, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:24'),
(784, 110, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:12:31'),
(785, 108, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:12:34'),
(786, 125, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:34'),
(787, 118, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:12:35'),
(788, 111, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:35'),
(789, 126, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:37'),
(790, 106, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:12:37'),
(791, 115, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:12:37'),
(792, 120, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:12:38'),
(793, 116, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:13:19'),
(794, 122, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:12:43'),
(795, 121, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:12:44'),
(796, 123, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:12:52'),
(797, 108, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:12:55'),
(798, 110, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:26:42'),
(799, 117, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:34:04'),
(800, 115, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:13:11'),
(802, 118, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:13:15'),
(803, 121, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:13:17'),
(805, 124, 67, '\"A\"', 0, 0, 0, '2026-03-02 03:13:20'),
(806, 114, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:13:23'),
(807, 107, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:13:23'),
(808, 123, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:13:30'),
(809, 116, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:13:33'),
(810, 121, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:13:42'),
(811, 113, 64, '\"E\"', 0, 0, 0, '2026-03-02 03:37:40'),
(813, 124, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:13:49'),
(814, 118, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:13:49'),
(815, 117, 56, '\"D\"', 0, 0, 0, '2026-03-02 03:13:51'),
(816, 119, 67, '\"A\"', 0, 0, 0, '2026-03-02 03:13:54'),
(817, 122, 61, '\"A\"', 0, 0, 0, '2026-03-02 03:14:04'),
(818, 121, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:14:05'),
(819, 114, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:14:08'),
(820, 119, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:14:12'),
(821, 123, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:14:14'),
(822, 108, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:14:14'),
(823, 110, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:14:15'),
(824, 118, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:14:15'),
(825, 124, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:14:16'),
(826, 121, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:14:20'),
(827, 114, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:14:24'),
(828, 108, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:14:25'),
(829, 122, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:14:29'),
(830, 110, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:24:41'),
(831, 117, 54, '\"E\"', 0, 0, 0, '2026-03-02 03:14:35'),
(832, 108, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:27:59'),
(833, 124, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:14:39'),
(834, 123, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:37:25'),
(835, 116, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:14:45'),
(836, 119, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:14:47'),
(837, 118, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:14:48'),
(838, 121, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:14:52'),
(840, 106, 67, '\"A\"', 0, 0, 0, '2026-03-02 03:14:59'),
(841, 107, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:14:59'),
(842, 117, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:15:02'),
(843, 116, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:15:02'),
(844, 124, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:15:08'),
(845, 110, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:25:23'),
(846, 121, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:15:15'),
(847, 123, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:15:18'),
(848, 119, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:15:18'),
(849, 110, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:15:26'),
(850, 121, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:15:29'),
(851, 119, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:16:21'),
(852, 112, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:15:37'),
(853, 114, 67, '\"D\"', 0, 0, 0, '2026-03-02 03:15:39'),
(854, 122, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:15:41'),
(855, 121, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:15:41'),
(856, 118, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:15:44'),
(857, 123, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:15:47'),
(858, 106, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:15:49'),
(859, 114, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:15:53'),
(860, 107, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:16:00'),
(861, 123, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:16:19'),
(863, 113, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:37:49'),
(865, 109, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:16:29'),
(866, 111, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:16:35'),
(867, 125, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:16:36'),
(868, 116, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:17:09'),
(870, 123, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:16:41'),
(871, 106, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:32:15'),
(872, 118, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:16:46'),
(873, 119, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:16:52'),
(874, 106, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:17:03'),
(875, 121, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:16:54'),
(877, 124, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:16:55'),
(878, 125, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:17:02'),
(881, 119, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:17:12'),
(882, 116, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:17:14'),
(883, 111, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:17:16'),
(884, 126, 67, '\"D\"', 0, 0, 0, '2026-03-02 03:17:17'),
(885, 109, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:17:25'),
(886, 111, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:17:26'),
(887, 107, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:17:29'),
(888, 106, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:17:31'),
(889, 117, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:17:32'),
(890, 118, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:17:34'),
(891, 125, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:17:39'),
(892, 116, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:17:41'),
(893, 114, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:28:32'),
(894, 122, 67, '\"A\"', 0, 0, 0, '2026-03-02 03:17:51'),
(895, 125, 70, '\"A\"', 0, 0, 0, '2026-03-02 03:17:54'),
(896, 124, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:17:57'),
(897, 126, 65, '\"C\"', 0, 0, 0, '2026-03-02 03:19:57'),
(898, 106, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:18:06'),
(899, 122, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:18:07'),
(900, 125, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:18:11'),
(902, 109, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:18:12'),
(903, 122, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:18:12'),
(904, 119, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:18:14'),
(905, 113, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:24:26'),
(906, 114, 68, '\"D\"', 0, 0, 0, '2026-03-02 03:18:20'),
(907, 118, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:18:27'),
(909, 125, 73, '\"C\"', 0, 0, 0, '2026-03-02 03:32:28'),
(910, 119, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:18:35'),
(911, 122, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:19:05'),
(913, 107, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:18:42'),
(914, 111, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:18:50'),
(915, 109, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:18:50'),
(916, 114, 61, '\"A\"', 0, 0, 0, '2026-03-02 03:19:04'),
(919, 118, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:19:12'),
(920, 124, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:19:15'),
(922, 107, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:19:24'),
(923, 120, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:19:32'),
(924, 111, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:19:26'),
(925, 114, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:20:01'),
(926, 106, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:19:27'),
(928, 118, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:19:39'),
(929, 112, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:20:15'),
(930, 125, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:19:51'),
(932, 111, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:20:00'),
(934, 125, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:20:05'),
(935, 106, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:20:05'),
(936, 120, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:20:06'),
(940, 125, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:20:18'),
(941, 107, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:20:27'),
(942, 125, 56, '\"B\"', 0, 0, 0, '2026-03-02 03:20:30'),
(943, 111, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:20:39'),
(944, 106, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:20:43'),
(945, 125, 68, '\"C\"', 0, 0, 0, '2026-03-02 03:20:49'),
(950, 107, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:20:57'),
(951, 106, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:33:32'),
(952, 109, 61, '\"C\"', 0, 0, 0, '2026-03-02 03:29:23'),
(954, 111, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:21:12'),
(955, 115, 56, '\"B\"', 0, 0, 0, '2026-03-02 03:21:19'),
(956, 107, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:25:28'),
(960, 112, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:21:41'),
(961, 115, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:21:41'),
(962, 124, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:21:49'),
(963, 126, 66, '\"D\"', 0, 0, 0, '2026-03-02 03:22:14'),
(964, 117, 70, '\"D\"', 0, 0, 0, '2026-03-02 03:22:15'),
(965, 106, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:39:07'),
(966, 124, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:22:17'),
(967, 114, 70, '\"D\"', 0, 0, 0, '2026-03-02 03:22:34'),
(969, 106, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:22:35'),
(972, 111, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:22:57'),
(975, 120, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:23:30'),
(977, 114, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:23:35'),
(978, 124, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:23:38'),
(980, 126, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:23:53'),
(981, 112, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:51:16'),
(982, 114, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:23:59'),
(983, 115, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:24:00'),
(984, 124, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:24:00'),
(985, 117, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:24:02'),
(987, 120, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:24:12'),
(988, 115, 65, '\"C\"', 0, 0, 0, '2026-03-02 03:24:18'),
(989, 114, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:24:20'),
(994, 124, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:24:33'),
(997, 120, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:24:43'),
(998, 115, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:24:48'),
(999, 111, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:24:51'),
(1000, 124, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:24:55'),
(1001, 122, 54, '\"D\"', 0, 0, 0, '2026-03-02 03:24:56'),
(1002, 114, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:24:59'),
(1003, 113, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:25:00'),
(1004, 115, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:25:01'),
(1005, 109, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:25:05'),
(1006, 126, 68, '\"C\"', 0, 0, 0, '2026-03-02 03:25:09'),
(1007, 113, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:25:20'),
(1008, 122, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:25:22'),
(1012, 117, 73, '\"B\"', 0, 0, 0, '2026-03-02 03:25:49'),
(1013, 115, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:26:15'),
(1014, 126, 72, '\"B\"', 0, 0, 0, '2026-03-02 03:26:06'),
(1015, 113, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:26:11'),
(1017, 111, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:26:13'),
(1019, 114, 54, '\"C\"', 0, 0, 0, '2026-03-02 03:26:19'),
(1021, 109, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:26:25'),
(1022, 114, 58, '\"A\"', 0, 0, 0, '2026-03-02 03:26:29'),
(1023, 113, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:26:37'),
(1024, 111, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:26:41'),
(1025, 115, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:26:41'),
(1026, 114, 59, '\"B\"', 0, 0, 0, '2026-03-02 03:26:42'),
(1028, 120, 67, '\"B\"', 0, 0, 0, '2026-03-02 03:26:55'),
(1030, 126, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:27:17'),
(1031, 109, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:27:17'),
(1032, 117, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:32:05'),
(1035, 114, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:39:03'),
(1037, 111, 70, '\"B\"', 0, 0, 0, '2026-03-02 03:27:45'),
(1040, 113, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:28:45'),
(1042, 120, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:28:12'),
(1045, 111, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:28:18'),
(1054, 120, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:29:51'),
(1058, 109, 66, '\"D\"', 0, 0, 0, '2026-03-02 03:29:00'),
(1059, 115, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:29:17'),
(1061, 113, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:30:31'),
(1062, 112, 61, '\"C\"', 0, 0, 0, '2026-03-02 03:29:43'),
(1064, 126, 73, '\"A\"', 0, 0, 0, '2026-03-02 03:29:57'),
(1065, 112, 67, '\"E\"', 0, 0, 0, '2026-03-02 03:50:17'),
(1066, 117, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:32:41'),
(1067, 112, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:53:59'),
(1069, 117, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:30:52'),
(1070, 115, 73, '\"A\"', 0, 0, 0, '2026-03-02 03:31:22'),
(1071, 126, 70, '\"A\"', 0, 0, 0, '2026-03-02 03:31:42'),
(1072, 115, 70, '\"A\"', 0, 0, 0, '2026-03-02 03:31:44'),
(1073, 120, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:32:02'),
(1074, 115, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:32:04'),
(1076, 115, 69, '\"C\"', 0, 0, 0, '2026-03-02 03:32:53'),
(1083, 109, 63, '\"C\"', 0, 0, 0, '2026-03-02 03:32:50'),
(1085, 112, 66, '\"C\"', 0, 0, 0, '2026-03-02 03:32:57'),
(1089, 112, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:52:10'),
(1092, 109, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:34:27'),
(1094, 112, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:34:42'),
(1096, 120, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:34:55'),
(1098, 109, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:35:29'),
(1103, 120, 68, '\"A\"', 0, 0, 0, '2026-03-02 03:53:14'),
(1105, 109, 54, '\"A\"', 0, 0, 0, '2026-03-02 03:36:48'),
(1111, 109, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:37:55'),
(1112, 120, 55, '\"C\"', 0, 0, 0, '2026-03-02 03:38:43'),
(1113, 113, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:38:51'),
(1116, 125, 65, '\"D\"', 0, 0, 0, '2026-03-02 03:48:01'),
(1117, 125, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:48:09'),
(1118, 126, 56, '\"C\"', 0, 0, 0, '2026-03-02 03:48:10'),
(1119, 126, 60, '\"C\"', 0, 0, 0, '2026-03-02 03:48:25'),
(1120, 113, 54, '\"B\"', 0, 0, 0, '2026-03-02 03:54:25'),
(1121, 126, 71, '\"C\"', 0, 0, 0, '2026-03-02 03:49:05'),
(1122, 113, 61, '\"D\"', 0, 0, 0, '2026-03-02 03:49:06'),
(1124, 113, 55, '\"A\"', 0, 0, 0, '2026-03-02 03:52:09'),
(1125, 126, 64, '\"B\"', 0, 0, 0, '2026-03-02 03:49:33'),
(1126, 120, 57, '\"B\"', 0, 0, 0, '2026-03-02 03:49:46'),
(1130, 120, 72, '\"D\"', 0, 0, 0, '2026-03-02 03:50:30'),
(1134, 113, 60, '\"B\"', 0, 0, 0, '2026-03-02 03:51:36'),
(1137, 113, 62, '\"B\"', 0, 0, 0, '2026-03-02 03:53:05'),
(1181, 139, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:35:28'),
(1182, 132, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:35:31'),
(1183, 138, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:35:40'),
(1184, 135, 60, '\"D\"', 0, 0, 0, '2026-03-03 03:37:27'),
(1188, 131, 57, '\"A\"', 0, 0, 0, '2026-03-03 03:36:01'),
(1189, 136, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:57:33'),
(1192, 138, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:36:06'),
(1193, 138, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:36:20'),
(1194, 141, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:37:39'),
(1195, 139, 62, '\"A\"', 0, 0, 0, '2026-03-03 03:36:43'),
(1197, 131, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:36:31'),
(1199, 138, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:36:50'),
(1202, 132, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:36:47'),
(1203, 133, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:38:21'),
(1205, 141, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:37:46'),
(1206, 132, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:37:03'),
(1207, 131, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:37:05'),
(1208, 132, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:37:14'),
(1209, 138, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:37:16'),
(1212, 139, 60, '\"A\"', 0, 0, 0, '2026-03-03 03:37:28'),
(1214, 140, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:37:32'),
(1215, 132, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:37:37'),
(1218, 137, 57, '\"A\"', 0, 0, 0, '2026-03-03 03:37:47'),
(1219, 132, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:37:55'),
(1220, 135, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:38:05'),
(1221, 138, 68, '\"C\"', 0, 0, 0, '2026-03-03 03:47:36'),
(1223, 141, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:38:13'),
(1225, 142, 68, '\"D\"', 0, 0, 0, '2026-03-03 03:38:19'),
(1226, 135, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:38:20'),
(1228, 136, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:38:21'),
(1229, 134, 60, '\"D\"', 0, 0, 0, '2026-03-03 03:38:22'),
(1230, 131, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:38:35'),
(1231, 139, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:38:43'),
(1232, 136, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:38:47'),
(1233, 138, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:38:51'),
(1234, 132, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:48:07'),
(1235, 143, 60, '\"D\"', 0, 0, 0, '2026-03-03 03:38:58'),
(1237, 136, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:39:00'),
(1238, 142, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:39:01'),
(1240, 131, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:39:03'),
(1241, 133, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:39:04'),
(1242, 135, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:39:22'),
(1243, 138, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:39:20'),
(1245, 139, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:39:21'),
(1247, 136, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:39:23'),
(1248, 134, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:39:25'),
(1249, 141, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:40:31'),
(1250, 131, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:39:40'),
(1251, 138, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:39:41'),
(1252, 136, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:39:41'),
(1253, 144, 54, '\"A\"', 0, 0, 0, '2026-03-03 03:41:10'),
(1254, 132, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:39:55'),
(1255, 138, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:39:58'),
(1256, 143, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:40:03'),
(1258, 136, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:40:00'),
(1260, 133, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:41:37'),
(1261, 134, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:40:04'),
(1262, 138, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:40:07'),
(1263, 131, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:40:07'),
(1264, 138, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:40:16'),
(1265, 139, 54, '\"A\"', 0, 0, 0, '2026-03-03 03:40:19'),
(1266, 142, 59, '\"C\"', 0, 0, 0, '2026-03-03 03:40:21'),
(1267, 137, 54, '\"A\"', 1, 0, 0, '2026-03-03 03:40:34'),
(1270, 132, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:40:38'),
(1271, 140, 70, '\"B\"', 1, 0, 0, '2026-03-03 03:40:43'),
(1272, 136, 62, '\"A\"', 0, 0, 0, '2026-03-03 03:57:37'),
(1275, 139, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:40:46'),
(1276, 141, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:40:57'),
(1277, 131, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:40:49'),
(1278, 136, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:40:51'),
(1279, 134, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:40:53'),
(1281, 130, 61, '\"B\"', 0, 0, 0, '2026-03-03 03:41:10'),
(1283, 132, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:41:08'),
(1287, 131, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:41:14'),
(1288, 136, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:57:40'),
(1290, 132, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:41:20'),
(1293, 140, 69, '\"D\"', 1, 0, 0, '2026-03-03 03:41:55'),
(1294, 143, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:41:29'),
(1295, 136, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:41:31'),
(1296, 131, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:41:35'),
(1298, 144, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:41:37'),
(1299, 132, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:41:38'),
(1300, 135, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:41:47'),
(1301, 145, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:41:44'),
(1303, 132, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:41:46'),
(1305, 138, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:41:48'),
(1306, 137, 61, '\"E\"', 1, 0, 0, '2026-03-03 03:41:54'),
(1308, 136, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:41:53'),
(1309, 134, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:41:54'),
(1313, 131, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:41:56'),
(1314, 138, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:41:58'),
(1315, 142, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:42:07'),
(1316, 138, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:42:08'),
(1317, 132, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:45:15'),
(1318, 144, 62, '\"B\"', 1, 0, 0, '2026-03-03 03:42:20'),
(1319, 133, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:42:19'),
(1321, 138, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:42:22'),
(1325, 132, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:42:36'),
(1326, 139, 72, '\"D\"', 0, 0, 0, '2026-03-03 04:00:14'),
(1328, 138, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:42:38'),
(1329, 132, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:42:44'),
(1330, 134, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:42:45'),
(1331, 138, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:42:48'),
(1332, 141, 69, '\"E\"', 0, 0, 0, '2026-03-03 03:48:54'),
(1333, 142, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:42:51'),
(1334, 135, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:43:03'),
(1337, 130, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:43:03'),
(1338, 141, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:43:03'),
(1339, 138, 62, '\"A\"', 0, 0, 0, '2026-03-03 03:47:41'),
(1341, 132, 68, '\"A\"', 1, 0, 0, '2026-03-03 03:43:39'),
(1343, 141, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:43:26'),
(1347, 136, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:56:17'),
(1349, 140, 72, '\"A\"', 0, 0, 0, '2026-03-03 03:43:33'),
(1350, 131, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:43:34'),
(1351, 139, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:59:07'),
(1353, 138, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:43:41'),
(1354, 133, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:43:47'),
(1355, 144, 71, '\"C\"', 1, 0, 0, '2026-03-03 03:43:52'),
(1357, 143, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:43:55'),
(1358, 139, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:57:41'),
(1359, 132, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:43:55'),
(1360, 141, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:43:56'),
(1361, 136, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:44:02'),
(1362, 131, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:44:05'),
(1363, 137, 67, '\"A\"', 0, 0, 0, '2026-03-03 03:44:05'),
(1364, 132, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:44:14'),
(1365, 141, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:44:17'),
(1366, 148, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:44:25'),
(1367, 132, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:44:23'),
(1370, 135, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:44:35'),
(1371, 134, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:44:30'),
(1373, 141, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:54:27'),
(1375, 131, 70, '\"A\"', 0, 0, 0, '2026-03-03 03:44:35'),
(1376, 133, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:44:46'),
(1377, 142, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:08:16'),
(1378, 147, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:44:50'),
(1380, 148, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:44:53'),
(1384, 141, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:44:58'),
(1388, 148, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:56:55'),
(1390, 135, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:45:25'),
(1392, 141, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:45:09'),
(1393, 137, 65, '\"B\"', 0, 0, 0, '2026-03-03 03:45:09'),
(1396, 144, 73, '\"C\"', 1, 0, 0, '2026-03-03 03:45:12'),
(1398, 140, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:45:15'),
(1401, 130, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:45:17'),
(1402, 145, 73, '\"E\"', 0, 0, 0, '2026-03-03 03:45:20'),
(1406, 145, 69, '\"D\"', 0, 0, 0, '2026-03-03 03:48:05'),
(1407, 139, 69, '\"E\"', 0, 0, 0, '2026-03-03 04:01:40'),
(1408, 135, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:45:48'),
(1409, 130, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:47:06'),
(1411, 131, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:45:45'),
(1413, 141, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:45:47'),
(1417, 147, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:45:50'),
(1418, 141, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:45:56'),
(1419, 142, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:46:04'),
(1421, 140, 55, '\"A\"', 0, 0, 0, '2026-03-03 03:46:11'),
(1422, 131, 73, '\"C\"', 0, 0, 0, '2026-03-03 03:47:32'),
(1423, 141, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:50:54'),
(1424, 148, 55, '\"D\"', 0, 0, 0, '2026-03-03 03:57:03'),
(1426, 143, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:46:23'),
(1433, 139, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:46:35'),
(1435, 141, 56, '\"E\"', 0, 0, 0, '2026-03-03 03:46:40'),
(1437, 144, 70, '\"B\"', 1, 0, 0, '2026-03-03 03:46:42'),
(1439, 134, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:46:47'),
(1440, 147, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:46:51'),
(1441, 137, 66, '\"B\"', 0, 0, 0, '2026-03-03 03:46:51'),
(1442, 136, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:57:44'),
(1444, 141, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:46:58'),
(1445, 139, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:47:05'),
(1447, 141, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:47:06'),
(1448, 137, 64, '\"E\"', 0, 0, 0, '2026-03-03 03:47:12'),
(1449, 142, 64, '\"D\"', 0, 0, 0, '2026-03-03 03:47:13'),
(1450, 140, 60, '\"E\"', 0, 0, 0, '2026-03-03 03:47:18'),
(1451, 136, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:47:20'),
(1452, 141, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:47:21'),
(1456, 134, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:47:33'),
(1460, 136, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:47:39'),
(1462, 135, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:47:52'),
(1465, 139, 61, '\"D\"', 0, 0, 0, '2026-03-03 04:02:02'),
(1467, 147, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:47:57'),
(1468, 144, 72, '\"D\"', 1, 0, 0, '2026-03-03 03:48:11'),
(1470, 148, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:48:03'),
(1471, 140, 66, '\"B\"', 0, 0, 0, '2026-03-03 03:48:04'),
(1472, 136, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:48:04'),
(1477, 136, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:48:25'),
(1478, 137, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:48:34'),
(1480, 133, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:48:44'),
(1481, 135, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:49:10'),
(1484, 136, 54, '\"A\"', 0, 0, 0, '2026-03-03 03:55:34'),
(1485, 134, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:49:01'),
(1488, 145, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:49:21'),
(1489, 147, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:49:09'),
(1491, 142, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:49:17'),
(1492, 131, 66, '\"A\"', 0, 0, 0, '2026-03-03 03:49:18'),
(1494, 133, 70, '\"A\"', 0, 0, 0, '2026-03-03 03:50:23'),
(1495, 140, 67, '\"B\"', 0, 0, 0, '2026-03-03 03:49:29'),
(1498, 144, 69, '\"C\"', 1, 0, 0, '2026-03-03 03:49:53'),
(1500, 137, 72, '\"B\"', 0, 0, 0, '2026-03-03 03:49:57'),
(1501, 139, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:51:14'),
(1504, 147, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:50:04'),
(1506, 144, 68, '\"A\"', 1, 0, 0, '2026-03-03 03:50:07'),
(1510, 131, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:50:08'),
(1512, 137, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:50:16'),
(1513, 144, 61, '\"D\"', 1, 0, 0, '2026-03-03 03:59:46'),
(1515, 142, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:50:24'),
(1518, 145, 72, '\"A\"', 0, 0, 0, '2026-03-03 03:50:39'),
(1519, 140, 64, '\"E\"', 0, 0, 0, '2026-03-03 03:50:30'),
(1520, 130, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:50:33'),
(1523, 143, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:50:42'),
(1524, 135, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:50:53'),
(1526, 144, 58, '\"A\"', 1, 0, 0, '2026-03-03 03:50:49'),
(1528, 133, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:50:49'),
(1533, 142, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:51:06'),
(1534, 147, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:51:07'),
(1535, 140, 63, '\"B\"', 0, 0, 0, '2026-03-03 03:51:08'),
(1536, 144, 60, '\"A\"', 1, 0, 0, '2026-03-03 03:51:10'),
(1539, 137, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:51:15'),
(1540, 131, 54, '\"E\"', 0, 0, 0, '2026-03-03 03:51:23'),
(1541, 134, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:51:32'),
(1542, 130, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:51:33'),
(1543, 133, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:51:36'),
(1544, 135, 71, '\"D\"', 0, 0, 0, '2026-03-03 03:52:17'),
(1545, 137, 71, '\"C\"', 0, 0, 0, '2026-03-03 03:51:50'),
(1546, 133, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:52:05'),
(1549, 145, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:52:02'),
(1551, 140, 65, '\"B\"', 0, 0, 0, '2026-03-03 03:52:09'),
(1552, 148, 71, '\"E\"', 0, 0, 0, '2026-03-03 04:08:40'),
(1553, 147, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:52:10'),
(1555, 131, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:52:14'),
(1556, 130, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:52:15'),
(1558, 142, 60, '\"C\"', 0, 0, 0, '2026-03-03 03:52:22'),
(1559, 144, 64, '\"B\"', 1, 0, 0, '2026-03-03 03:52:25'),
(1561, 140, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:52:25'),
(1562, 135, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:52:35'),
(1564, 143, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:52:44'),
(1565, 137, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:52:45'),
(1566, 130, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:52:54'),
(1567, 133, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:52:55'),
(1568, 135, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:52:58'),
(1570, 147, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:52:58'),
(1571, 137, 58, '\"A\"', 0, 0, 0, '2026-03-03 03:53:00'),
(1572, 140, 56, '\"B\"', 0, 0, 0, '2026-03-03 03:53:14'),
(1573, 134, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:53:09'),
(1574, 145, 67, '\"A\"', 0, 0, 0, '2026-03-03 03:53:11'),
(1576, 135, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:53:21'),
(1577, 144, 63, '\"C\"', 1, 0, 0, '2026-03-03 03:53:21'),
(1580, 142, 72, '\"D\"', 0, 0, 0, '2026-03-03 03:53:23'),
(1581, 139, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:53:35'),
(1582, 137, 56, '\"B\"', 0, 0, 0, '2026-03-03 03:53:38'),
(1583, 147, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:53:40'),
(1584, 140, 54, '\"E\"', 0, 0, 0, '2026-03-03 03:53:41'),
(1585, 135, 68, '\"C\"', 0, 0, 0, '2026-03-03 03:54:05'),
(1586, 130, 54, '\"B\"', 0, 0, 0, '2026-03-03 03:53:43'),
(1587, 134, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:53:49'),
(1588, 144, 67, '\"E\"', 1, 0, 0, '2026-03-03 03:53:56'),
(1593, 139, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:54:12'),
(1594, 147, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:54:18'),
(1595, 145, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:54:18'),
(1596, 135, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:54:24'),
(1597, 140, 61, '\"B\"', 0, 0, 0, '2026-03-03 03:54:26'),
(1599, 137, 60, '\"B\"', 0, 0, 0, '2026-03-03 03:54:33'),
(1600, 139, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:54:39'),
(1601, 148, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:54:39'),
(1602, 134, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:54:58'),
(1603, 135, 56, '\"C\"', 0, 0, 0, '2026-03-03 03:55:11'),
(1604, 147, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:55:08'),
(1605, 144, 65, '\"D\"', 1, 0, 0, '2026-03-03 03:55:32'),
(1607, 133, 68, '\"A\"', 0, 0, 0, '2026-03-03 03:55:21'),
(1608, 140, 62, '\"A\"', 0, 0, 0, '2026-03-03 03:55:17'),
(1609, 137, 68, '\"D\"', 0, 0, 0, '2026-03-03 03:55:19'),
(1611, 135, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:55:20'),
(1616, 140, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:55:28'),
(1619, 137, 55, '\"A\"', 0, 0, 0, '2026-03-03 03:55:36'),
(1620, 143, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:57:08'),
(1623, 130, 67, '\"B\"', 0, 0, 0, '2026-03-03 03:55:42'),
(1629, 145, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:56:02'),
(1630, 137, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:56:11'),
(1631, 147, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:56:07'),
(1635, 144, 66, '\"C\"', 1, 0, 0, '2026-03-03 03:56:20'),
(1636, 133, 62, '\"B\"', 0, 0, 0, '2026-03-03 03:56:20'),
(1638, 142, 69, '\"C\"', 0, 0, 0, '2026-03-03 03:56:21'),
(1639, 140, 68, '\"C\"', 0, 0, 0, '2026-03-03 03:56:25'),
(1640, 130, 65, '\"D\"', 0, 0, 0, '2026-03-03 03:56:27'),
(1641, 144, 56, '\"A\"', 1, 0, 0, '2026-03-03 04:10:28'),
(1643, 139, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:56:36'),
(1644, 148, 64, '\"A\"', 0, 0, 0, '2026-03-03 04:08:52'),
(1645, 144, 55, '\"C\"', 1, 0, 0, '2026-03-03 04:10:16'),
(1651, 147, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:56:54'),
(1658, 144, 57, '\"B\"', 1, 0, 0, '2026-03-03 04:04:33'),
(1659, 142, 70, '\"B\"', 0, 0, 0, '2026-03-03 03:57:11'),
(1662, 139, 64, '\"D\"', 0, 0, 0, '2026-03-03 03:57:19'),
(1663, 134, 67, '\"E\"', 0, 0, 0, '2026-03-03 03:57:23'),
(1664, 130, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:57:39'),
(1666, 133, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:57:34'),
(1672, 145, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:57:50'),
(1674, 147, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:57:54'),
(1675, 134, 63, '\"C\"', 0, 0, 0, '2026-03-03 03:57:58'),
(1676, 143, 55, '\"C\"', 0, 0, 0, '2026-03-03 03:57:59'),
(1677, 143, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:58:08'),
(1678, 130, 64, '\"B\"', 0, 0, 0, '2026-03-03 03:58:10'),
(1679, 143, 59, '\"B\"', 0, 0, 0, '2026-03-03 03:58:18'),
(1680, 145, 54, '\"A\"', 0, 0, 0, '2026-03-03 03:58:19'),
(1681, 142, 73, '\"B\"', 0, 0, 0, '2026-03-03 03:58:20'),
(1682, 130, 66, '\"C\"', 0, 0, 0, '2026-03-03 03:58:47'),
(1685, 140, 57, '\"B\"', 0, 0, 0, '2026-03-03 03:59:38'),
(1687, 147, 61, '\"D\"', 0, 0, 0, '2026-03-03 03:59:46'),
(1688, 137, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:00:01'),
(1689, 130, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:00:10'),
(1693, 133, 64, '\"B\"', 0, 0, 0, '2026-03-03 04:00:11'),
(1696, 147, 68, '\"A\"', 0, 0, 0, '2026-03-03 04:00:24'),
(1697, 142, 71, '\"C\"', 0, 0, 0, '2026-03-03 04:00:40'),
(1698, 133, 65, '\"A\"', 0, 0, 0, '2026-03-03 04:00:47'),
(1699, 134, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:00:53'),
(1700, 143, 54, '\"C\"', 0, 0, 0, '2026-03-03 04:00:55'),
(1701, 147, 60, '\"C\"', 0, 0, 0, '2026-03-03 04:00:58'),
(1702, 143, 58, '\"A\"', 0, 0, 0, '2026-03-03 04:03:28'),
(1704, 133, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:01:30'),
(1708, 147, 55, '\"C\"', 0, 0, 0, '2026-03-03 04:01:46'),
(1711, 130, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:01:49'),
(1714, 133, 67, '\"E\"', 0, 0, 0, '2026-03-03 04:01:54'),
(1715, 134, 65, '\"D\"', 0, 0, 0, '2026-03-03 04:01:55'),
(1716, 145, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:02:06'),
(1724, 133, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:02:14'),
(1725, 142, 56, '\"C\"', 0, 0, 0, '2026-03-03 04:02:29'),
(1726, 145, 61, '\"D\"', 0, 0, 0, '2026-03-03 04:04:08'),
(1729, 148, 65, '\"D\"', 0, 0, 0, '2026-03-03 04:08:49'),
(1731, 134, 58, '\"A\"', 0, 0, 0, '2026-03-03 04:02:57'),
(1733, 148, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:08:46'),
(1736, 130, 55, '\"C\"', 0, 0, 0, '2026-03-03 04:03:21'),
(1738, 130, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:03:30'),
(1739, 130, 60, '\"D\"', 0, 0, 0, '2026-03-03 04:03:40'),
(1741, 148, 61, '\"D\"', 0, 0, 0, '2026-03-03 04:03:43'),
(1746, 148, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:04:03'),
(1748, 130, 68, '\"D\"', 0, 0, 0, '2026-03-03 04:04:08'),
(1750, 148, 68, '\"A\"', 0, 0, 0, '2026-03-03 04:04:36'),
(1751, 145, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:05:29'),
(1752, 148, 56, '\"B\"', 0, 0, 0, '2026-03-03 04:05:20'),
(1755, 148, 60, '\"C\"', 0, 0, 0, '2026-03-03 04:05:41'),
(1756, 142, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:06:06'),
(1757, 148, 73, '\"B\"', 0, 0, 0, '2026-03-03 04:06:18'),
(1758, 145, 68, '\"A\"', 0, 0, 0, '2026-03-03 04:06:20'),
(1761, 143, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:06:20'),
(1762, 150, 61, '\"D\"', 0, 0, 0, '2026-03-03 04:06:22'),
(1763, 149, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:06:36'),
(1765, 148, 69, '\"C\"', 0, 0, 0, '2026-03-03 04:06:45'),
(1766, 150, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:06:47'),
(1767, 149, 54, '\"B\"', 0, 0, 0, '2026-03-03 04:06:54'),
(1768, 148, 70, '\"B\"', 0, 0, 0, '2026-03-03 04:06:56'),
(1769, 134, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:07:09'),
(1771, 149, 56, '\"C\"', 0, 0, 0, '2026-03-03 04:07:10'),
(1772, 150, 68, '\"A\"', 0, 0, 0, '2026-03-03 04:07:14'),
(1773, 149, 58, '\"A\"', 0, 0, 0, '2026-03-03 04:07:22'),
(1774, 150, 54, '\"C\"', 0, 0, 0, '2026-03-03 04:07:28'),
(1776, 142, 54, '\"B\"', 0, 0, 0, '2026-03-03 04:07:33'),
(1778, 148, 63, '\"C\"', 0, 0, 0, '2026-03-03 04:07:37'),
(1779, 145, 60, '\"A\"', 0, 0, 0, '2026-03-03 04:09:12'),
(1782, 149, 59, '\"B\"', 0, 0, 0, '2026-03-03 04:07:49'),
(1783, 150, 64, '\"B\"', 0, 0, 0, '2026-03-03 04:07:52'),
(1784, 143, 56, '\"D\"', 0, 0, 0, '2026-03-03 04:08:10'),
(1788, 148, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:08:09'),
(1791, 150, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:08:17'),
(1794, 150, 65, '\"D\"', 0, 0, 0, '2026-03-03 04:08:45'),
(1800, 149, 72, '\"D\"', 0, 0, 0, '2026-03-03 04:09:00'),
(1801, 143, 63, '\"C\"', 0, 0, 0, '2026-03-03 04:09:05'),
(1803, 150, 63, '\"C\"', 0, 0, 0, '2026-03-03 04:09:10'),
(1805, 149, 70, '\"B\"', 0, 0, 0, '2026-03-03 04:09:22'),
(1806, 145, 55, '\"C\"', 0, 0, 0, '2026-03-03 04:09:30'),
(1807, 150, 67, '\"E\"', 0, 0, 0, '2026-03-03 04:09:44'),
(1811, 149, 69, '\"C\"', 0, 0, 0, '2026-03-03 04:09:43'),
(1814, 145, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:10:00'),
(1815, 149, 73, '\"B\"', 0, 0, 0, '2026-03-03 04:10:07'),
(1816, 150, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:10:13'),
(1817, 145, 56, '\"C\"', 0, 0, 0, '2026-03-03 04:10:16'),
(1819, 143, 64, '\"B\"', 0, 0, 0, '2026-03-03 04:10:17'),
(1820, 149, 71, '\"C\"', 0, 0, 0, '2026-03-03 04:10:25'),
(1821, 145, 58, '\"A\"', 0, 0, 0, '2026-03-03 04:10:25'),
(1823, 150, 60, '\"C\"', 0, 0, 0, '2026-03-03 04:10:47'),
(1824, 150, 55, '\"C\"', 0, 0, 0, '2026-03-03 04:11:24'),
(1825, 149, 68, '\"B\"', 0, 0, 0, '2026-03-03 04:11:26'),
(1826, 150, 69, '\"C\"', 0, 0, 0, '2026-03-03 04:11:45'),
(1827, 143, 67, '\"E\"', 0, 0, 0, '2026-03-03 04:11:46'),
(1828, 143, 65, '\"C\"', 0, 0, 0, '2026-03-03 04:11:55'),
(1829, 150, 71, '\"C\"', 0, 0, 0, '2026-03-03 04:12:10'),
(1830, 143, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:12:19'),
(1832, 149, 64, '\"B\"', 0, 0, 0, '2026-03-03 04:12:28'),
(1833, 150, 72, '\"D\"', 0, 0, 0, '2026-03-03 04:12:35'),
(1834, 149, 65, '\"D\"', 0, 0, 0, '2026-03-03 04:12:42'),
(1835, 149, 67, '\"A\"', 0, 0, 0, '2026-03-03 04:13:14'),
(1836, 150, 70, '\"B\"', 0, 0, 0, '2026-03-03 04:13:08'),
(1838, 150, 73, '\"A\"', 0, 0, 0, '2026-03-03 04:13:16'),
(1839, 149, 63, '\"C\"', 0, 0, 0, '2026-03-03 04:13:37'),
(1840, 150, 58, '\"A\"', 0, 0, 0, '2026-03-03 04:13:38'),
(1841, 150, 56, '\"C\"', 0, 0, 0, '2026-03-03 04:14:01'),
(1842, 149, 66, '\"C\"', 0, 0, 0, '2026-03-03 04:14:03'),
(1843, 150, 57, '\"B\"', 0, 0, 0, '2026-03-03 04:15:29'),
(1845, 149, 55, '\"C\"', 0, 0, 0, '2026-03-03 04:16:03'),
(1846, 149, 60, '\"A\"', 0, 0, 0, '2026-03-03 04:16:14'),
(1847, 149, 62, '\"B\"', 0, 0, 0, '2026-03-03 04:16:18'),
(1848, 149, 61, '\"B\"', 0, 0, 0, '2026-03-03 04:16:24'),
(1892, 166, 75, '\"C\"', 0, 0, 0, '2026-04-02 04:59:23'),
(1893, 167, 74, '\"D\"', 1, 0, 0, '2026-04-02 04:59:47'),
(1895, 168, 46, '\"A\"', 0, 0, 0, '2026-04-02 05:00:02'),
(1896, 168, 47, '\"C\"', 0, 0, 0, '2026-04-02 05:00:19'),
(1897, 168, 44, '\"C\"', 0, 0, 0, '2026-04-02 05:00:27'),
(1898, 168, 52, '\"E\"', 0, 0, 0, '2026-04-02 05:00:41'),
(1899, 168, 49, '\"E\"', 0, 0, 0, '2026-04-02 05:00:47');

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
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `exam_id`, `name`, `duration`, `max_score`, `sort_order`, `is_active`, `created_at`) VALUES
(12, 9, 'Penalaran Umum', 30, 886.12, 3, 1, '2026-02-23 04:13:37'),
(13, 10, 'English', 90, 100.00, 13, 1, '2026-03-02 01:01:41'),
(14, 9, 'Matematika', 30, 100.00, 2, 1, '2026-03-30 04:22:08'),
(15, 9, 'IPA', 20, 200.00, 1, 1, '2026-03-30 04:30:50');

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
(50, 9, '7WKQ', '2026-02-27 06:06:00', '2026-02-27 06:16:00'),
(51, 10, 'AJ9C', '2026-03-02 01:49:07', '2026-03-02 01:59:07'),
(52, 10, '2BSG', '2026-03-02 02:04:49', '2026-03-02 02:14:49'),
(53, 10, 'SWE7', '2026-03-02 03:00:39', '2026-03-02 03:10:39'),
(54, 10, 'AF49', '2026-03-02 03:10:39', '2026-03-02 03:20:39'),
(55, 10, 'WL26', '2026-03-02 03:20:40', '2026-03-02 03:30:40'),
(56, 10, '4GK2', '2026-03-02 03:44:12', '2026-03-02 03:54:12'),
(57, 10, 'GBFJ', '2026-03-02 03:55:29', '2026-03-02 04:05:29'),
(58, 10, '6FN2', '2026-03-03 01:08:16', '2026-03-03 01:18:16'),
(59, 10, '7L3G', '2026-03-03 03:09:59', '2026-03-03 03:19:59'),
(60, 10, 'VU73', '2026-03-03 03:34:46', '2026-03-03 03:44:46'),
(61, 10, 'YL36', '2026-03-03 03:50:38', '2026-03-03 04:00:37'),
(62, 10, 'MQCJ', '2026-03-03 04:00:45', '2026-03-03 04:10:45'),
(63, 10, '5EB6', '2026-03-03 04:15:08', '2026-03-03 04:25:08'),
(64, 10, 'GVK8', '2026-03-04 07:50:07', '2026-03-04 08:00:07'),
(65, 9, 'XP37', '2026-03-30 04:34:05', '2026-03-30 04:44:05'),
(66, 9, '9WQY', '2026-03-30 04:52:43', '2026-03-30 05:02:43'),
(67, 9, 'EMH5', '2026-04-02 04:45:59', '2026-04-02 04:55:59'),
(68, 9, '2UDZ', '2026-04-02 04:59:11', '2026-04-02 05:09:11');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1900;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

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
