-- Migrasi Fitur Survei Guru CBT Offline & Sinkronisasi Cloud SiHonor

CREATE TABLE IF NOT EXISTS `cbt_surveys_local` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `survey_id_cloud` INT NOT NULL,
    `kode_akses` VARCHAR(64) NOT NULL UNIQUE,
    `judul` VARCHAR(255) NOT NULL,
    `guru_id_cloud` INT NULL,
    `guru_nama` VARCHAR(150) NOT NULL,
    `guru_nip` VARCHAR(50) NULL,
    `mata_pelajaran` VARCHAR(100) NULL,
    `kelas_sasaran` VARCHAR(100) NULL,
    `total_pertanyaan` INT DEFAULT 0,
    `payload_json` LONGTEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subject_surveys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subject_id` INT NOT NULL,
    `teacher_name` VARCHAR(150) NOT NULL,
    `survey_code` VARCHAR(64) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_survey_code` (`survey_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cbt_survey_responden_local` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NULL,
    `exam_id` INT NULL,
    `subject_id` INT NULL,
    `kode_akses` VARCHAR(64) NOT NULL,
    `teacher_name` VARCHAR(150) NULL,
    `nama_siswa` VARCHAR(100) NULL,
    `kelas_siswa` VARCHAR(100) NULL,
    `nisn` VARCHAR(30) NULL,
    `is_anonim` TINYINT(1) DEFAULT 0,
    `jawaban_json` LONGTEXT NOT NULL,
    `waktu_isi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `synced` TINYINT(1) DEFAULT 0,
    `synced_at` DATETIME NULL,
    INDEX `idx_synced` (`synced`),
    INDEX `idx_student_subject` (`student_id`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Samakan collation tabel survei agar kompatibel dengan tabel subjects & exams
ALTER TABLE `subject_surveys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `student_surveys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `cbt_surveys_local` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE `cbt_survey_responden_local` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Tambah default setting untuk sinkronisasi sihonor jika belum ada
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('cbt_survey_sync_url', 'http://sihonor.page.gd'),
('cbt_survey_api_key', ''),
('cbt_survey_last_pull', ''),
('cbt_survey_last_push', '');
