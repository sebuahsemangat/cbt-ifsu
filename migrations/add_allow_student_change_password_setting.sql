-- Migration: Menambahkan pengaturan allow_student_change_password pada tabel settings (default 1 / aktif)
INSERT INTO `settings` (`setting_key`, `setting_value`) 
VALUES ('allow_student_change_password', '1')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
