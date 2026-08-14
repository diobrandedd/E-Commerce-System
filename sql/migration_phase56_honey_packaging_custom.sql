-- Custom bottle packaging batches (no production lot / legacy supplier required).
-- Run once: mysql -u root rts_system < sql/migration_phase56_honey_packaging_custom.sql

SET NAMES utf8mb4;

-- Allow packaging rows that are created only for barcode stickers (no gallon flow).
ALTER TABLE `honey_packaging_rows`
  MODIFY COLUMN `production_row_id` INT UNSIGNED NULL DEFAULT NULL;
