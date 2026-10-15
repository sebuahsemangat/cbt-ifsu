-- Migration: Menambahkan kolom remaining_seconds pada tabel exam_sessions untuk menyimpan sisa durasi ujian siswa
ALTER TABLE `exam_sessions` ADD COLUMN `remaining_seconds` INT NULL DEFAULT NULL AFTER `end_time`;
