-- Migration: Menambahkan kolom kelompok pada tabel students
ALTER TABLE `students` ADD COLUMN `kelompok` VARCHAR(50) NULL DEFAULT NULL AFTER `class`;
