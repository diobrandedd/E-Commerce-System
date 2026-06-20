-- Phase 14: Link costing stocks to suppliers (per-supplier honey raw material lines)
-- Run after phase 13.

SET NAMES utf8mb4;

SET @has_supplier_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'supplier_id'
);
SET @sql_col := IF(
  @has_supplier_id = 0,
  'ALTER TABLE `stocks` ADD COLUMN `supplier_id` INT UNSIGNED NULL AFTER `dept_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND CONSTRAINT_NAME = 'fk_stocks_supplier'
);
SET @sql_fk := IF(
  @has_fk = 0,
  'ALTER TABLE `stocks`
     ADD KEY `idx_stocks_supplier` (`supplier_id`),
     ADD CONSTRAINT `fk_stocks_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT',
  'SELECT 1'
);
PREPARE stmt FROM @sql_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_old_uq := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND INDEX_NAME = 'uq_stocks_name_dept'
);
SET @sql_drop_uq := IF(
  @has_old_uq > 0,
  'ALTER TABLE `stocks` DROP INDEX `uq_stocks_name_dept`',
  'SELECT 1'
);
PREPARE stmt FROM @sql_drop_uq;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_new_uq := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND INDEX_NAME = 'uq_stocks_dept_name_supplier'
);
SET @sql_uq := IF(
  @has_new_uq = 0,
  'ALTER TABLE `stocks` ADD UNIQUE KEY `uq_stocks_dept_name_supplier` (`dept_id`, `name`, `supplier_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_uq;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
