-- Online order parcels registry + ship-time deduct audit.
-- Run once: mysql -u root rts_system < sql/migration_phase18_online_order_parcels.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `online_order_parcels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `csr_batch_id` INT UNSIGNED NULL DEFAULT NULL,
  `csr_line_id` INT UNSIGNED NULL DEFAULT NULL,
  `tracking_number` VARCHAR(96) NOT NULL,
  `items_raw` TEXT NOT NULL,
  `items_parsed_json` JSON NULL,
  `recipient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `courier` VARCHAR(64) NOT NULL DEFAULT '',
  `order_no` VARCHAR(64) NULL DEFAULT NULL,
  `source_pipeline` ENUM('partner','vip','shopee_spx') NOT NULL,
  `status` ENUM('packing','shipped','cancelled','returned') NOT NULL DEFAULT 'packing',
  `ship_date` DATE NULL DEFAULT NULL,
  `return_date` DATE NULL DEFAULT NULL,
  `shipped_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `deducted_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_online_order_parcels_tracking` (`tracking_number`),
  KEY `idx_online_order_parcels_status` (`status`),
  KEY `idx_online_order_parcels_batch` (`csr_batch_id`),
  KEY `idx_online_order_parcels_ship_date` (`ship_date`),
  CONSTRAINT `fk_online_parcels_csr_batch`
    FOREIGN KEY (`csr_batch_id`) REFERENCES `csr_online_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_online_parcels_csr_line`
    FOREIGN KEY (`csr_line_id`) REFERENCES `csr_online_order_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_online_parcels_shipped_by`
    FOREIGN KEY (`shipped_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `online_parcel_deduct_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parcel_id` INT UNSIGNED NOT NULL,
  `packaging_row_id` INT UNSIGNED NOT NULL,
  `variant` VARCHAR(32) NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_online_parcel_deduct_parcel` (`parcel_id`),
  CONSTRAINT `fk_online_parcel_deduct_parcel`
    FOREIGN KEY (`parcel_id`) REFERENCES `online_order_parcels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_online_parcel_deduct_packaging`
    FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional link from delivery photos to parcel registry
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery_batch_items' AND COLUMN_NAME = 'online_parcel_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `delivery_batch_items` ADD COLUMN `online_parcel_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_status`,
   ADD KEY `idx_delivery_items_online_parcel` (`online_parcel_id`),
   ADD CONSTRAINT `fk_delivery_items_online_parcel` FOREIGN KEY (`online_parcel_id`) REFERENCES `online_order_parcels` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'online_parcel_id'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `honey_sales_log` ADD COLUMN `online_parcel_id` INT UNSIGNED NULL DEFAULT NULL AFTER `sale_type`,
   ADD KEY `idx_honey_sales_online_parcel` (`online_parcel_id`),
   ADD CONSTRAINT `fk_honey_sales_online_parcel` FOREIGN KEY (`online_parcel_id`) REFERENCES `online_order_parcels` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
