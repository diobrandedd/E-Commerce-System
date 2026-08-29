-- SKU barcode history: track stock-in progress on requests + audit log.
-- Run once: mysql -u root rts_system < sql/migration_phase70_sku_barcode_history.sql

SET NAMES utf8mb4;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_bottle_requests' AND COLUMN_NAME = 'sku_scanned_qty');
SET @sql := IF(@c = 0,
  'ALTER TABLE `honey_bottle_requests` ADD COLUMN `sku_scanned_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `quantity`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_requests' AND COLUMN_NAME = 'sku_scanned_qty');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_requests` ADD COLUMN `sku_scanned_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `quantity`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS `sku_barcode_stock_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` ENUM('honey','dept') NOT NULL,
  `sku_id` INT UNSIGNED NOT NULL,
  `qty` INT UNSIGNED NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `honey_request_id` INT UNSIGNED NULL DEFAULT NULL,
  `dept_request_id` INT UNSIGNED NULL DEFAULT NULL,
  `scanned_by` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sku_stock_sku` (`sku_id`),
  KEY `idx_sku_stock_honey_req` (`honey_request_id`),
  KEY `idx_sku_stock_dept_req` (`dept_request_id`),
  KEY `idx_sku_stock_at` (`scanned_at`),
  CONSTRAINT `fk_sku_stock_sku` FOREIGN KEY (`sku_id`) REFERENCES `product_sku_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sku_stock_user` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
