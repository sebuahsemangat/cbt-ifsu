-- Migration: Create settings table
-- Created: 2026-04-20

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('app_name', 'CBT Simulation'),
('bimbel_name', 'Bimbel Lita Sumedang'),
('bimbel_logo', 'logo-bimbel.png');
