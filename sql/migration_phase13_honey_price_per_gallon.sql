-- Phase 13: Honey purchase/cost pricing in US gallons (supplier standard)
-- Run after phase 12 if that migration used per-liter column names.
-- Converts existing liter values to gallon: multiply by 3.785411784

SET NAMES utf8mb4;

SET @liters_per_gallon := 3.785411784;

-- supplier_honey_prices: price_per_liter -> price_per_gallon
SET @has_ppl := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplier_honey_prices' AND COLUMN_NAME = 'price_per_liter'
);
SET @has_ppg := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplier_honey_prices' AND COLUMN_NAME = 'price_per_gallon'
);

SET @sql_shp := IF(
  @has_ppl > 0 AND @has_ppg = 0,
  'ALTER TABLE `supplier_honey_prices` CHANGE COLUMN `price_per_liter` `price_per_gallon` DECIMAL(12,2) NOT NULL',
  IF(
    @has_ppg = 0,
    'ALTER TABLE `supplier_honey_prices` ADD COLUMN `price_per_gallon` DECIMAL(12,2) NOT NULL AFTER `variant`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql_shp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_ppl_after := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplier_honey_prices' AND COLUMN_NAME = 'price_per_liter'
);
SET @sql_shp_convert := IF(
  @has_ppl_after = 0 AND @has_ppl > 0,
  'UPDATE `supplier_honey_prices` SET `price_per_gallon` = ROUND(`price_per_gallon` * 3.785411784, 2)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_shp_convert;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- honey_warehouse_rows: unit_cost_per_liter -> unit_cost_per_gallon
SET @has_ucl := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_warehouse_rows' AND COLUMN_NAME = 'unit_cost_per_liter'
);
SET @has_ucg := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_warehouse_rows' AND COLUMN_NAME = 'unit_cost_per_gallon'
);

SET @sql_wh := IF(
  @has_ucl > 0 AND @has_ucg = 0,
  'ALTER TABLE `honey_warehouse_rows` CHANGE COLUMN `unit_cost_per_liter` `unit_cost_per_gallon` DECIMAL(12,2) NULL',
  IF(
    @has_ucg = 0 AND @has_ucl = 0,
    'ALTER TABLE `honey_warehouse_rows`
       ADD COLUMN `unit_cost_per_gallon` DECIMAL(12,2) NULL AFTER `initial_qty`,
       ADD COLUMN `supplier_price_id` INT UNSIGNED NULL AFTER `unit_cost_per_gallon`,
       ADD COLUMN `cost_override` TINYINT(1) NOT NULL DEFAULT 0 AFTER `supplier_price_id`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql_wh;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_ucl_after := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_warehouse_rows' AND COLUMN_NAME = 'unit_cost_per_liter'
);
SET @sql_wh_convert := IF(
  @has_ucl_after = 0 AND @has_ucl > 0,
  'UPDATE `honey_warehouse_rows` SET `unit_cost_per_gallon` = ROUND(`unit_cost_per_gallon` * 3.785411784, 2) WHERE `unit_cost_per_gallon` IS NOT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql_wh_convert;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
