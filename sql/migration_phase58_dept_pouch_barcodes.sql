-- Generalize Paragis pouch barcodes to any dept product (Paragis Tea, Black Rice Coffee).
-- Renames paragis_pouch_* -> dept_pouch_* (keeps existing rows) and adds product_key.
-- Run once: mysql -u root rts_system < sql/migration_phase58_dept_pouch_barcodes.sql

SET NAMES utf8mb4;

-- 1) Rename tables (guarded so a re-run is a no-op).
SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_requests');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_requests` TO `dept_pouch_requests`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_barcode_batches');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_barcode_batches` TO `dept_pouch_barcode_batches`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_barcodes');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_barcodes` TO `dept_pouch_barcodes`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_codes_issued');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_codes_issued` TO `dept_pouch_codes_issued`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_units');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_units` TO `dept_pouch_units`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @t := (SELECT COUNT(*) FROM information_schema.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paragis_pouch_barcode_seq');
SET @sql := IF(@t > 0, 'RENAME TABLE `paragis_pouch_barcode_seq` TO `dept_pouch_barcode_seq`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) product_key on requests / batches / units. Existing rows are Paragis Tea.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_requests' AND COLUMN_NAME = 'product_key');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_requests`
     ADD COLUMN `product_key` VARCHAR(32) NOT NULL DEFAULT ''pt'' AFTER `requested_by`,
     ADD KEY `idx_dept_pouch_req_product` (`product_key`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_barcode_batches' AND COLUMN_NAME = 'product_key');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_barcode_batches`
     ADD COLUMN `product_key` VARCHAR(32) NOT NULL DEFAULT ''pt'' AFTER `generated_by`,
     ADD KEY `idx_dept_pouch_batch_product` (`product_key`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_units' AND COLUMN_NAME = 'product_key');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_units`
     ADD COLUMN `product_key` VARCHAR(32) NOT NULL DEFAULT ''pt'' AFTER `barcode_id`,
     ADD KEY `idx_dept_pouch_unit_product` (`product_key`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
