-- Phase 90: Permanent barcodes on packaging material stocks
SET NAMES utf8mb4;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stocks'
    AND COLUMN_NAME = 'barcode'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `stocks` ADD COLUMN `barcode` VARCHAR(32) NULL DEFAULT NULL AFTER `name`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stocks'
    AND INDEX_NAME = 'uq_stocks_barcode'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `stocks` ADD UNIQUE KEY `uq_stocks_barcode` (`barcode`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill packaging rows missing a barcode (PKG + zero-padded id).
UPDATE `stocks`
SET `barcode` = CONCAT('PKG', LPAD(`id`, 6, '0'))
WHERE `category` = 'packaging'
  AND (`barcode` IS NULL OR TRIM(`barcode`) = '');
