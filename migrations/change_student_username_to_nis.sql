-- Migration: Mengubah kolom username menjadi nis pada tabel students
ALTER TABLE `students` CHANGE `username` `nis` VARCHAR(50) NOT NULL;
ALTER TABLE `students` DROP INDEX `username`, ADD UNIQUE KEY `nis` (`nis`);
