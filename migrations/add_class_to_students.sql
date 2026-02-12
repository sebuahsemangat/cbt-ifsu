-- Migration: Add class column to students table
-- Created: 2026-02-12

ALTER TABLE `students` 
ADD COLUMN `class` VARCHAR(50) DEFAULT NULL AFTER `full_name`;
