-- ========================================================================
-- SCRIPT PENYESUAIAN STRUKTUR TABEL `students` UNTUK SERVER HOSTING
-- ========================================================================

-- KASUS 1: Jika kolom `room` BELUM PERNAH ADA di database hosting
-- Jalankan perintah ini:
ALTER TABLE `students` 
ADD COLUMN `room` VARCHAR(50) NULL DEFAULT NULL AFTER `kelompok`;

-- KASUS 2: Jika kolom `room` SUDAH ADA tetapi tipenya masih VARCHAR(11) / NOT NULL
-- Jalankan perintah ini:
ALTER TABLE `students` 
MODIFY COLUMN `room` VARCHAR(50) NULL DEFAULT NULL;

-- STANDARISASI DATA: Ubah nilai '0' atau string kosong lama menjadi NULL
UPDATE `students` 
SET `room` = NULL 
WHERE `room` = '0' OR `room` = '';

-- ========================================================================
-- CATATAN TAMBAHAN (OPSIONAL):
-- Jika database hosting Anda ternyata juga belum memiliki kolom `kelompok`,
-- jalankan perintah berikut terlebih dahulu:
-- ALTER TABLE `students` ADD COLUMN `kelompok` VARCHAR(50) NULL DEFAULT NULL AFTER `class`;
-- ========================================================================
