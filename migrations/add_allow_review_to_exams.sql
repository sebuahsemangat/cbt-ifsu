-- Migration: Menambahkan kolom allow_review pada tabel exams (default 0 / nonaktif)
ALTER TABLE `exams` ADD COLUMN `allow_review` TINYINT(1) DEFAULT 0 AFTER `is_active`;
