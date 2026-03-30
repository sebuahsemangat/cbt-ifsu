-- Migration: Add sort_order column to subjects table
ALTER TABLE subjects ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER max_score;

-- Set initial sort_order based on existing IDs (preserving current order)
UPDATE subjects SET sort_order = id;
