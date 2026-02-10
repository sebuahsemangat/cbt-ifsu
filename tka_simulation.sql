-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 07, 2026 at 08:13 AM
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
(1, 'lita', '$2y$10$rZXNoxdq.N6DWnMWUeXQ0ugYTBg4F90F6hOP0e2ewIK3c3A5wpKLS', 'Lita', '2026-01-29 08:07:37');

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
(3, 'Simulasi TKA SMA', '2026-02-02 08:02:00', '2026-02-09 10:02:00', 1, '2026-01-30 02:02:40'),
(6, 'Simulasi UTBK 2', '2026-02-06 15:00:00', '2026-02-06 17:00:00', 1, '2026-02-06 09:48:28');

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
(56, 13, 3, 9, '2026-02-07 13:34:34', '2026-02-07 13:35:09', 100.00, 'finished'),
(57, 13, 3, 8, '2026-02-07 13:35:10', '2026-02-07 13:35:41', 75.00, 'finished'),
(58, 13, 3, 7, '2026-02-07 13:35:46', '2026-02-07 13:36:49', 83.33, 'finished'),
(59, 14, 3, 9, '2026-02-07 14:46:29', '2026-02-07 14:47:13', 50.00, 'finished'),
(60, 14, 3, 8, '2026-02-07 14:47:21', '2026-02-07 14:52:39', 12.50, 'finished'),
(61, 14, 3, 7, '2026-02-07 14:52:42', '2026-02-07 14:53:05', 0.00, 'finished'),
(62, 6, 3, 9, '2026-02-07 15:06:03', '2026-02-07 15:06:44', 50.00, 'finished'),
(63, 6, 3, 8, '2026-02-07 15:06:50', '2026-02-07 15:08:13', 0.00, 'finished'),
(64, 6, 3, 7, '2026-02-07 15:08:14', '2026-02-07 15:08:20', 0.00, 'finished');

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
(13, 7, 11, '<p><span style=\"color: rgb(68, 68, 68);\">Apabila Mirna ingin memperoleh keuntungan maksimum, tentukan</span></p><p><span style=\"color: rgb(68, 68, 68);\">Benar atau Salah untuk setiap pernyataan berikut!</span></p>', '2026-02-04 09:59:19', 'bs', '[{\"text\": \"Mirna harus memproduksi 200 kotak kue bolu.\", \"is_correct\": 0}, {\"text\": \"Mirna  harus  memproduksi  kue  brownies lebih banyak.\", \"is_correct\": 0}, {\"text\": \"Keuntungan maksimum  yang dapat diperoleh Mirna adalah Rp3.100.000,00.\", \"is_correct\": 1}]'),
(14, 7, NULL, '<p><span style=\"color: rgb(68, 68, 68);\">Fungsi didefinisikan oleh: <img src=\"data:image/png;base64,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\" style=\"\" width=\"223\"></span></p>', '2026-02-05 06:27:22', 'bs', '[{\"text\": \"Grafik fungsi ƒ terbuka ke atas.\", \"is_correct\": 1}, {\"text\": \"Grafik fungsi ƒ  memotong garis y = -18\", \"is_correct\": 0}, {\"text\": \"Grafik fungsi ƒ  tidak melalui kuadran tiga.\", \"is_correct\": 1}]'),
(15, 9, 12, '<p><span style=\"color: rgb(68, 68, 68);\">During the internship, what did the writer do every morning?</span></p>', '2026-02-06 09:54:36', 'pg', '{\"type\": \"single_choice\", \"correct\": \"D\", \"options\": {\"A\": \"Made schedules and explained them to the coaches.\", \"B\": \"Played football with the team and got the first aid kit.\", \"C\": \"Prepared a club gift, guided the coaches, and gave t-shirts.\", \"D\": \"Woke up early, arrived on time, and followed the instructions.\", \"E\": \"Set up cones, brought water, and checked emergency schedules.\"}}'),
(16, 7, NULL, '<p>Dari selembar karton berbentuk persegi yang berukuran sisi 30 cm akan dibuat kotak tanpa tutup, dengan cara menggunting empat persegi di setiap pojok karton, seperti pada gambar. Volume kotak terbesar yang dapat dibuat adalah ....</p><p><img src=\"data:image/png;base64,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\"></p>', '2026-02-07 06:24:21', 'pg', '{\"type\": \"single_choice\", \"correct\": \"A\", \"options\": {\"A\": \"2.000 cm³\", \"B\": \"3.000 cm³\", \"C\": \"4.000 cm³\", \"D\": \"5.000 cm³\", \"E\": \"6.000 cm³\"}}'),
(17, 7, 13, '<p><span style=\"color: rgb(68, 68, 68);\">Tentukan Benar atau Salah untuk setiap pernyataan berikut terkait dengan besar sudut pada trapesium 𝐴𝐵𝐶𝐷!</span></p>', '2026-02-07 06:34:01', 'bs', '[{\"text\": \"<p><img src=\\\"data:image/png;base64,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\\\"></p>\", \"is_correct\": 1}, {\"text\": \"<p><img src=\\\"data:image/png;base64,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\\\"></p>\", \"is_correct\": 1}, {\"text\": \"<p><img src=\\\"data:image/png;base64,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\\\"></p>\", \"is_correct\": 0}]');

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
(11, 7, 'Mirna akan memproduksi dua', 'text', '<p><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Mirna akan memproduksi dua jenis kue dengan modal Rp8.000.000,00. Biaya produksi kue bolu sebesar Rp15.000,00 per kotak dan dijual dengan laba 40%. Sedangkan biaya produksi kue brownies sebesar Rp20.000,00 per kotak dan dijual dengan laba 35%. Setiap harinya, Mirna dapat memproduksi paling banyak 500 kotak kue.</span></p>', NULL, '2026-02-04 09:58:37'),
(12, 9, 'My Experience', 'text', '<p class=\"ql-align-center\"><strong style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">My Experience as an Intern at a Sports Club</strong></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">Last summer, I had the opportunity to work as an intern at a local sports club. It was my first time working in a professional sports environment, and I was both nervous and excited. The internship went through a few phases, starting from orientation until finally jumping in to help during practices and games.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">On my first day, I met the head coach, who welcomed me and explained my tasks. I had to set up cones for drills, bring water for the players, and check the training schedules. The first few days were challenging because I was not used to the morning routine. Every morning I had to wake up early, arrive on time, and follow instructions carefully. I worried about making mistakes, but the coaches were kind and guided me.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">In the second month of my internship, I was trained to handle emergency situations. I got the chance to put that knowledge into practice when something unexpected happened. During football practice, a player fell and hurt his ankle. Everyone stopped, and I quickly ran to get the first aid kit. I helped the coach assess the injury and ensured the player stayed calm while we got him seated safely. Luckily, it wasn’t serious, but the experience reinforced an important lesson: staying alert and prepared during emergencies can make a big difference.</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">At the end of my internship, the coaches held an evaluation and reflection session where they shared the results of their feedback. They highlighted my strengths in teamwork, responsibility, and communication, and even gave me a club T-shirt as a gift. I felt proud because I had improved in these areas. It was a great experience, and</span></p><p class=\"ql-align-justify\"><span style=\"background-color: rgb(255, 255, 255); color: rgb(68, 68, 68);\">I hope to work in a sports club again in the future.</span></p><p><br></p>', NULL, '2026-02-06 09:53:35'),
(13, 7, 'trapesium', 'image', '<p><br></p>', '1770445956_6986dc845a205.jpg', '2026-02-07 06:32:36');

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
(2, 13, 5, 0.00, '2026-02-06 09:33:08');

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
(6, 'neneng22', '$2y$10$ScB0rg.C24T8Uz7C7zNqBO9jdtqgZXDLcpJM9.7Hwbk8cSO84QDZi', 'Neneng', 0, '2026-02-07 15:04:35', '2026-01-30 02:36:10'),
(7, 'prisaborusegu68', '$2y$10$hJw866nJQcBzNqUbK.PYlO9JuYH5XkEgTH3F2Ii95P0S4hXmyP6t6', 'Prisa Borusegu', 0, '2026-02-05 15:33:06', '2026-01-30 02:36:10'),
(8, 'hasansadikin66', '$2y$10$ftxqC/cjItCy0g.egJaA5ujmIgQL468BwR8DfOfZznuCY82cZim6C', 'Hasan Sadikin', 0, NULL, '2026-01-30 02:47:31'),
(9, 'ahmadagung45', '$2y$10$A61XmX4Syv1mS9MPqljjGuyA5zp.z2NEwR2wpRT0doVd3QNerIFdq', 'Ahmad Agung', 0, '2026-02-06 16:25:54', '2026-01-30 02:47:40'),
(10, 'candrakirana84', '$2y$10$v5vl/UwENN2jguwTQ7FAVe.12JxqZRaNQm72WbgcvHy9v4w6zNb.q', 'Candra Kirana', 0, '2026-02-05 15:38:02', '2026-01-30 02:48:00'),
(11, 'gunawandwicahyo34', '$2y$10$7yDbdVIF3p8V4sX4x1OjkeanQC5vjkSrbCs0eLuGMLUGcr/WXzBFW', 'Gunawan Dwi Cahyo', 0, NULL, '2026-01-30 02:48:13'),
(12, 'sintiaanisarahmawati69', '$2y$10$bubbfppd6QygwzKidg/ezOONrarjUygO5ZwZdbUlxfnATT82nOn7e', 'Sintia Anisa Rahmawati', 0, NULL, '2026-01-30 02:48:39'),
(13, 'agus77', '$2y$10$7EmLRy2lxWikOdnnVbdVO.p.xvImkqLRE86e4vfmBW0Xu.PeDk.Iy', 'Agus Kuncoro Jati', 0, '2026-02-07 13:25:04', '2026-01-30 02:52:00'),
(14, 'agus41', '$2y$10$o1INC6yiDqXp4hHpszsITOi00yI0i0wr2Tmrjvv.l5CmBucE/3eaS', 'Agus Setia Budi', 0, '2026-02-07 14:46:03', '2026-01-30 02:52:12');

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
(168, 56, 11, '[0,3]', 0, 0, NULL, '2026-02-07 06:34:38'),
(169, 56, 15, '\"D\"', 0, 0, NULL, '2026-02-07 06:35:06'),
(170, 57, 8, '[0,3]', 0, 0, NULL, '2026-02-07 06:35:19'),
(171, 57, 9, '[\"B\",\"S\",\"B\"]', 0, 0, NULL, '2026-02-07 06:35:24'),
(172, 57, 2, '\"C\"', 0, 0, NULL, '2026-02-07 06:35:27'),
(173, 57, 4, '\"D\"', 0, 0, NULL, '2026-02-07 06:35:30'),
(174, 57, 3, '\"A\"', 0, 0, NULL, '2026-02-07 06:35:32'),
(175, 57, 5, '\"C\"', 0, 0, NULL, '2026-02-07 06:35:34'),
(176, 57, 6, '\"B\"', 0, 0, NULL, '2026-02-07 06:35:37'),
(177, 57, 7, '\"B\"', 0, 0, NULL, '2026-02-07 06:35:39'),
(178, 58, 14, '[\"B\",\"S\",\"B\"]', 0, 0, NULL, '2026-02-07 06:35:50'),
(179, 58, 13, '[\"B\",\"S\",\"B\"]', 0, 0, NULL, '2026-02-07 06:35:54'),
(180, 58, 16, '\"A\"', 0, 0, NULL, '2026-02-07 06:36:08'),
(181, 58, 12, '\"B\"', 0, 0, NULL, '2026-02-07 06:36:14'),
(182, 58, 17, '[\"B\",\"B\",\"S\"]', 0, 0, NULL, '2026-02-07 06:36:45'),
(183, 58, 10, '\"B\"', 0, 0, NULL, '2026-02-07 06:36:47'),
(184, 59, 11, '[3]', 0, 0, NULL, '2026-02-07 07:47:09'),
(185, 59, 15, '\"D\"', 0, 0, NULL, '2026-02-07 07:47:03'),
(186, 60, 5, '\"B\"', 0, 0, NULL, '2026-02-07 07:52:28'),
(187, 60, 2, '\"C\"', 0, 0, NULL, '2026-02-07 07:52:35'),
(188, 60, 7, '\"C\"', 0, 0, NULL, '2026-02-07 07:51:59'),
(189, 62, 11, '[0,3]', 0, 0, NULL, '2026-02-07 08:06:27'),
(190, 63, 9, '[\"B\",\"S\",\"B\"]', 1, 0, NULL, '2026-02-07 08:07:24');

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
(7, 3, 'Matematika Lanjutan', 30, 1, '2026-01-30 02:13:37'),
(8, 3, 'Bahasa Indonesia', 45, 1, '2026-01-30 08:58:01'),
(9, 3, 'Bahasa Inggris Teknik', 40, 1, '2026-02-03 05:30:54');

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
(17, 3, 'FR9P', '2026-02-04 09:59:39', '2026-02-04 10:09:39'),
(18, 3, '6LQ4', '2026-02-05 06:32:50', '2026-02-05 06:42:50'),
(19, 3, 'W78G', '2026-02-05 06:56:49', '2026-02-05 07:06:49'),
(20, 3, 'P24E', '2026-02-05 07:15:06', '2026-02-05 07:25:06'),
(21, 3, '8Y7X', '2026-02-05 08:27:34', '2026-02-05 08:37:34'),
(22, 3, 'W4RP', '2026-02-05 08:37:40', '2026-02-05 08:47:40'),
(23, 3, 'SFBA', '2026-02-05 09:04:47', '2026-02-05 09:14:47'),
(24, 3, 'S6U8', '2026-02-06 09:25:34', '2026-02-06 09:35:34'),
(26, 3, '5YUE', '2026-02-06 09:54:59', '2026-02-06 10:04:59'),
(27, 3, '9MG4', '2026-02-07 06:25:13', '2026-02-07 06:35:13'),
(28, 3, 'EZ4Q', '2026-02-07 06:35:14', '2026-02-07 06:45:14'),
(29, 3, 'LM42', '2026-02-07 07:46:22', '2026-02-07 07:56:22'),
(30, 3, 'YPQ3', '2026-02-07 07:59:35', '2026-02-07 08:09:35');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
