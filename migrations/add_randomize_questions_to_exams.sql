-- Migration: Tambah kolom randomize_questions pada tabel exams dan question_order pada tabel exam_sessions
-- Default randomize_questions = 1 (Random/Acak)

ALTER TABLE `exams` ADD COLUMN `randomize_questions` TINYINT(1) DEFAULT 1 AFTER `allow_review`;
ALTER TABLE `exam_sessions` ADD COLUMN `question_order` TEXT NULL AFTER `score`;
