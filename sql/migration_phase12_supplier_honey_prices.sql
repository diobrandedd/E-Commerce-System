-- Phase 12: Supplier honey purchase prices + warehouse cost snapshot
-- Run after phase 11.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `supplier_honey_prices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `variant` ENUM('light','amber','dark') NOT NULL,
  `price_per_gallon` DECIMAL(12,2) NOT NULL,
  `effective_from` DATE NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shp_supplier_variant_date` (`supplier_id`, `variant`, `effective_from`),
  CONSTRAINT `fk_shp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_shp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_unit_cost := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_warehouse_rows' AND COLUMN_NAME = 'unit_cost_per_gallon'
);
SET @sql_unit := IF(
  @has_unit_cost = 0,
  'ALTER TABLE `honey_warehouse_rows`
     ADD COLUMN `unit_cost_per_gallon` DECIMAL(12,2) NULL AFTER `initial_qty`,
     ADD COLUMN `supplier_price_id` INT UNSIGNED NULL AFTER `unit_cost_per_gallon`,
     ADD COLUMN `cost_override` TINYINT(1) NOT NULL DEFAULT 0 AFTER `supplier_price_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql_unit;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk_shp := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_warehouse_rows' AND CONSTRAINT_NAME = 'fk_honey_wh_supplier_price'
);
SET @sql_fk := IF(
  @has_fk_shp = 0,
  'ALTER TABLE `honey_warehouse_rows`
     ADD KEY `idx_honey_wh_supplier_price` (`supplier_price_id`),
     ADD CONSTRAINT `fk_honey_wh_supplier_price` FOREIGN KEY (`supplier_price_id`) REFERENCES `supplier_honey_prices` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
