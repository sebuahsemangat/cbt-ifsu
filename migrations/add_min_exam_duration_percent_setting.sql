-- Migration: Tambah pengaturan batas minimal durasi pengerjaan ujian (%)
-- Nilai default: 50%
INSERT INTO settings (setting_key, setting_value)
SELECT 'min_exam_duration_percent', '50'
WHERE NOT EXISTS (
    SELECT 1 FROM settings WHERE setting_key = 'min_exam_duration_percent'
);
