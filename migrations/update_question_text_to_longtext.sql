-- Migration: Ubah tipe data question_text dan content menjadi LONGTEXT
-- Alasan: Menghindari pemotongan data (truncation) saat soal berisi gambar base64 besar yang melebihi batas 64KB (TEXT biasa)

ALTER TABLE `questions` MODIFY `question_text` LONGTEXT NOT NULL;
ALTER TABLE `references` MODIFY `content` LONGTEXT NOT NULL;
