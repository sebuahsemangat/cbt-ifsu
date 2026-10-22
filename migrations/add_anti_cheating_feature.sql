-- Migration: Tambah Fitur Anti-Cheating
-- 1. Tambah pengaturan anti-cheating di tabel settings (default: 1 / aktif)
INSERT INTO settings (setting_key, setting_value)
SELECT 'enable_anti_cheating', '1'
WHERE NOT EXISTS (
    SELECT 1 FROM settings WHERE setting_key = 'enable_anti_cheating'
);

-- 2. Tambah kolom violation_count dan is_locked pada tabel exam_sessions jika belum ada
SET @dbname = DATABASE();
SET @tablename = 'exam_sessions';

SET @col1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'violation_count');
SET @sql1 = IF(@col1 = 0, 'ALTER TABLE exam_sessions ADD COLUMN violation_count INT NOT NULL DEFAULT 0 AFTER score;', 'SELECT 1;');
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @col2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'is_locked');
SET @sql2 = IF(@col2 = 0, 'ALTER TABLE exam_sessions ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER violation_count;', 'SELECT 1;');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
