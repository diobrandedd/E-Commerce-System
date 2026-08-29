-- Phase 71: supplier/date barcodes for honey warehouse stock-out.
-- Run after phase 70:
--   mysql -u root rts_system < sql/migration_phase71_honey_warehouse_barcodes.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `honey_warehouse_barcode_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `supplier_name` VARCHAR(160) NOT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `arrival_date` DATE NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','printed','cancelled') NOT NULL DEFAULT 'pending',
  `requested_by` INT UNSIGNED NOT NULL,
  `generated_by` INT UNSIGNED NULL DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `printed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hwb_request_status` (`status`),
  KEY `idx_hwb_request_code` (`supplier_code`),
  KEY `idx_hwb_request_arrival` (`arrival_date`),
  CONSTRAINT `fk_hwb_request_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_hwb_request_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_hwb_request_generated` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_warehouse_stock_out_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_row_id` INT UNSIGNED NOT NULL,
  `production_row_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `arrival_date` DATE NOT NULL,
  `variant` ENUM('light','amber','dark','other') NOT NULL,
  `quantity_gallons` DECIMAL(12,2) NOT NULL,
  `stock_out_date` DATE NOT NULL,
  `stocked_out_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hwb_event_daily` (`stock_out_date`, `supplier_code`, `variant`),
  KEY `idx_hwb_event_warehouse` (`warehouse_row_id`),
  KEY `idx_hwb_event_production` (`production_row_id`),
  KEY `idx_hwb_event_created` (`created_at`),
  CONSTRAINT `fk_hwb_event_warehouse` FOREIGN KEY (`warehouse_row_id`) REFERENCES `honey_warehouse_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_hwb_event_production` FOREIGN KEY (`production_row_id`) REFERENCES `honey_production_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_hwb_event_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_hwb_event_user` FOREIGN KEY (`stocked_out_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'stock_out_date'
);
SET @sql := IF(
  @c = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `stock_out_date` DATE NULL AFTER `arrival_date`',
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'daily_group_key'
);
SET @sql := IF(
  @c = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `daily_group_key` VARCHAR(128) NULL AFTER `stock_out_date`',
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND INDEX_NAME = 'uq_honey_production_daily_group'
);
SET @sql := IF(
  @c = 0,
  'ALTER TABLE `honey_production_rows` ADD UNIQUE KEY `uq_honey_production_daily_group` (`daily_group_key`)',
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND INDEX_NAME = 'idx_honey_production_stock_out_date'
);
SET @sql := IF(
  @c = 0,
  'ALTER TABLE `honey_production_rows` ADD KEY `idx_honey_production_stock_out_date` (`stock_out_date`)',
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
