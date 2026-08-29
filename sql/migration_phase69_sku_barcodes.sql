-- SKU barcodes: one permanent code per product; pack/check qty columns for SKU lines.
-- Run once: mysql -u root rts_system < sql/migration_phase69_sku_barcodes.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `product_sku_barcodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` ENUM('honey','dept') NOT NULL,
  `variant` VARCHAR(32) NOT NULL DEFAULT '',
  `size` VARCHAR(16) NOT NULL DEFAULT '',
  `product_key` VARCHAR(32) NOT NULL DEFAULT '',
  `code` VARCHAR(32) NOT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_sku_code` (`code`),
  UNIQUE KEY `uq_product_sku_identity` (`kind`, `variant`, `size`, `product_key`),
  KEY `idx_product_sku_kind` (`kind`),
  CONSTRAINT `fk_product_sku_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pack honey lines: allow SKU qty without a unique unit
ALTER TABLE `honey_bottle_pack_session_items`
  MODIFY COLUMN `barcode_id` INT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_bottle_pack_session_items' AND COLUMN_NAME = 'sku_id');
SET @sql := IF(@c = 0,
  'ALTER TABLE `honey_bottle_pack_session_items`
     ADD COLUMN `sku_id` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD COLUMN `sku_code` VARCHAR(32) NULL DEFAULT NULL AFTER `sku_id`,
     ADD COLUMN `qty` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `sku_code`,
     ADD COLUMN `pool` ENUM(''packaging'',''rts_sellable'') NULL DEFAULT NULL AFTER `qty`,
     ADD COLUMN `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL AFTER `pool`,
     ADD COLUMN `variant` VARCHAR(32) NULL DEFAULT NULL AFTER `packaging_row_id`,
     ADD COLUMN `size` VARCHAR(16) NULL DEFAULT NULL AFTER `variant`,
     ADD KEY `idx_pack_honey_sku` (`sku_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

ALTER TABLE `honey_bottle_pack_session_dept_items`
  MODIFY COLUMN `barcode_id` INT UNSIGNED NULL DEFAULT NULL,
  MODIFY COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_bottle_pack_session_dept_items' AND COLUMN_NAME = 'sku_id');
SET @sql := IF(@c = 0,
  'ALTER TABLE `honey_bottle_pack_session_dept_items`
     ADD COLUMN `sku_id` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD COLUMN `sku_code` VARCHAR(32) NULL DEFAULT NULL AFTER `sku_id`,
     ADD COLUMN `qty` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `sku_code`,
     ADD COLUMN `pool` ENUM(''finished'',''rts_sellable'') NULL DEFAULT NULL AFTER `qty`,
     ADD KEY `idx_pack_dept_sku` (`sku_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Inventory check: counted qty for SKU (upsert by code)
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_check_scans' AND COLUMN_NAME = 'counted_qty');
SET @sql := IF(@c = 0,
  'ALTER TABLE `inventory_check_scans`
     ADD COLUMN `counted_qty` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `flag`,
     ADD COLUMN `sku_id` INT UNSIGNED NULL DEFAULT NULL AFTER `barcode_id`,
     MODIFY COLUMN `barcode_id` INT UNSIGNED NULL DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Print jobs for identical SKU sticker sheets (replace unique batch generate for new labels)
CREATE TABLE IF NOT EXISTS `sku_barcode_print_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `request_kind` ENUM('honey','dept','rts','ad_hoc') NOT NULL DEFAULT 'ad_hoc',
  `honey_request_id` INT UNSIGNED NULL DEFAULT NULL,
  `dept_request_id` INT UNSIGNED NULL DEFAULT NULL,
  `rts_label_job_id` INT UNSIGNED NULL DEFAULT NULL,
  `printed_by` INT UNSIGNED NOT NULL,
  `printed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sku_print_sku` (`sku_id`),
  KEY `idx_sku_print_at` (`printed_at`),
  CONSTRAINT `fk_sku_print_sku` FOREIGN KEY (`sku_id`) REFERENCES `product_sku_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sku_print_user` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
