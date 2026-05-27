-- Phase 11: POS honey sales linkage (aggregate walk-in sales, no batch at register).
-- Run after phase 10.

SET NAMES utf8mb4;

-- Allow POS sales without a users.id (cashier is pos_pins).
SET @sold_by_nullable := (
  SELECT IS_NULLABLE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'sold_by'
  LIMIT 1
);
SET @sql_sold_by := IF(
  @sold_by_nullable = 'NO',
  'ALTER TABLE `honey_sales_log` MODIFY COLUMN `sold_by` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql_sold_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_pin := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'sold_by_pin_id'
);
SET @sql_pin := IF(
  @has_pin = 0,
  'ALTER TABLE `honey_sales_log`
     ADD COLUMN `sold_by_pin_id` INT UNSIGNED NULL AFTER `sold_by`,
     ADD KEY `idx_honey_sales_pin` (`sold_by_pin_id`),
     ADD CONSTRAINT `fk_honey_sales_pin` FOREIGN KEY (`sold_by_pin_id`) REFERENCES `pos_pins` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql_pin;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_pos_sale := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'pos_sale_id'
);
SET @sql_pos_sale := IF(
  @has_pos_sale = 0,
  'ALTER TABLE `honey_sales_log`
     ADD COLUMN `pos_sale_id` INT UNSIGNED NULL AFTER `sold_by_pin_id`,
     ADD KEY `idx_honey_sales_pos_sale` (`pos_sale_id`),
     ADD CONSTRAINT `fk_honey_sales_pos_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql_pos_sale;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_variant := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pos_receipt_items' AND COLUMN_NAME = 'variant'
);
SET @sql_variant := IF(
  @has_variant = 0,
  'ALTER TABLE `pos_receipt_items`
     ADD COLUMN `variant` VARCHAR(32) NULL AFTER `line_total`,
     ADD COLUMN `size` VARCHAR(16) NULL AFTER `variant`',
  'SELECT 1'
);
PREPARE stmt FROM @sql_variant;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
