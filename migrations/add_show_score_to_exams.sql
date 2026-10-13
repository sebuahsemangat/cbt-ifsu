-- Migration: Tambah kolom show_score pada tabel exams
-- Default show_score = 0 (Nonaktif / Jangan tampilkan nilai ke siswa)

ALTER TABLE `exams` ADD COLUMN `show_score` TINYINT(1) DEFAULT 0 AFTER `randomize_questions`;
