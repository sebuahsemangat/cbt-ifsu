-- Migrasi Tambahan: Fitur Sinkronisasi & Upload Nilai Lab ke Cloud cbt.page.gd
-- Penambahan kolom aman dengan DEFAULT 0 dan NULL agar tidak mempengaruhi query yang sudah berjalan

-- 1. Tambah kolom synced dan synced_at pada exam_sessions jika belum ada
SET @dbname = DATABASE();
SET @tablename = "exam_sessions";
SET @columnname = "synced";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE exam_sessions ADD COLUMN synced TINYINT(1) NOT NULL DEFAULT 0 AFTER status, ADD COLUMN synced_at DATETIME NULL DEFAULT NULL AFTER synced;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Konfigurasi default sinkronisasi cloud CBT pada tabel settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('cbt_cloud_sync_url', 'http://cbt.page.gd'),
('cbt_cloud_sync_token', 'cbt-cloud-secret-key-2026'),
('cbt_cloud_last_upload', NULL)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
