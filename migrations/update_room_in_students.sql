-- Migration: Penyesuaian kolom room pada tabel students
-- Memperlebar tipe data menjadi VARCHAR(50) dan mengizinkan NULL
ALTER TABLE `students` MODIFY COLUMN `room` VARCHAR(50) NULL DEFAULT NULL;

-- Standarisasi data lama bernilai '0' atau string kosong menjadi NULL
UPDATE `students` SET `room` = NULL WHERE `room` = '0' OR `room` = '';
