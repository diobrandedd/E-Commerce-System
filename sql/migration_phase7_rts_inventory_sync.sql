-- Phase 7: RTS inventory mirror + sync state (Spec v3 §7)
-- Run after phase 6.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rts_inventory_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_line_item_id` INT UNSIGNED NOT NULL,
  `category` ENUM('honey','other') NOT NULL DEFAULT 'honey',
  `product_label` VARCHAR(120) NOT NULL,
  `variant` VARCHAR(32) NULL,
  `current_liter` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_liter` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_250ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_250ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_350ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_350ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_gallon` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_gallon` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rts_inv_line_item` (`source_line_item_id`),
  KEY `idx_rts_inv_category` (`category`),
  CONSTRAINT `fk_rts_inv_line_item` FOREIGN KEY (`source_line_item_id`) REFERENCES `rts_line_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent: skip if phase 7 was partially run before (errno 121 = duplicate constraint name)
SET @fk_rts_inv_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_sales_log'
    AND CONSTRAINT_NAME = 'fk_honey_sales_rts_inv'
);
SET @sql_fk_rts_inv := IF(
  @fk_rts_inv_exists = 0,
  'ALTER TABLE `honey_sales_log` ADD CONSTRAINT `fk_honey_sales_rts_inv` FOREIGN KEY (`rts_inventory_row_id`) REFERENCES `rts_inventory_rows` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt_fk_rts_inv FROM @sql_fk_rts_inv;
EXECUTE stmt_fk_rts_inv;
DEALLOCATE PREPARE stmt_fk_rts_inv;

CREATE TABLE IF NOT EXISTS `rts_sync_state` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `last_line_item_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_synced_at` DATETIME NULL,
  `last_error` VARCHAR(512) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `rts_sync_state` (`id`, `last_line_item_id`) VALUES (1, 0);
