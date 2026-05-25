-- Phase 8: Supplier master (executive) + link to honey warehouse stock-in
-- Run after phase 6/7. Safe to re-run (skips existing column/FK).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `contact_number` VARCHAR(32) NOT NULL,
  `payment_account` VARCHAR(80) NOT NULL,
  `payment_account_number` VARCHAR(64) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `valid_id_filename` VARCHAR(190) NOT NULL,
  `category` ENUM('honey','paragis','black_rice','cacao') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_category_active` (`category`, `is_active`),
  KEY `idx_suppliers_name` (`last_name`, `first_name`),
  CONSTRAINT `fk_suppliers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_warehouse_rows'
    AND COLUMN_NAME = 'supplier_id'
);
SET @sql_add_col := IF(
  @col_exists = 0,
  'ALTER TABLE `honey_warehouse_rows` ADD COLUMN `supplier_id` INT UNSIGNED NULL AFTER `supplier_name`',
  'SELECT 1'
);
PREPARE stmt_col FROM @sql_add_col;
EXECUTE stmt_col;
DEALLOCATE PREPARE stmt_col;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_warehouse_rows'
    AND INDEX_NAME = 'idx_honey_wh_supplier_id'
);
SET @sql_add_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `honey_warehouse_rows` ADD KEY `idx_honey_wh_supplier_id` (`supplier_id`)',
  'SELECT 1'
);
PREPARE stmt_idx FROM @sql_add_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_warehouse_rows'
    AND CONSTRAINT_NAME = 'fk_honey_wh_supplier'
);
SET @sql_add_fk := IF(
  @fk_exists = 0,
  'ALTER TABLE `honey_warehouse_rows` ADD CONSTRAINT `fk_honey_wh_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT',
  'SELECT 1'
);
PREPARE stmt_fk FROM @sql_add_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;
