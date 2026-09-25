-- Leak/damage log: support SKU qty rows (no unique sticker unit).
-- Run once: mysql ... < sql/migration_phase87_leak_damage_sku.sql
-- Safe to re-run.

SET NAMES utf8mb4;

SET @db := DATABASE();

-- qty
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log' AND COLUMN_NAME = 'qty'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` ADD COLUMN `qty` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `code`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- sku_id
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log' AND COLUMN_NAME = 'sku_id'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` ADD COLUMN `sku_id` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nullable barcode_id
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log'
        AND COLUMN_NAME = 'barcode_id' AND IS_NULLABLE = 'YES'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` MODIFY COLUMN `barcode_id` INT UNSIGNED NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nullable unit_id
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log'
        AND COLUMN_NAME = 'unit_id' AND IS_NULLABLE = 'YES'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` MODIFY COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop UNIQUE(unit_id); keep non-unique index (sticker uniqueness enforced in PHP).
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log'
        AND INDEX_NAME = 'uq_finished_leak_unit'
    ),
    'ALTER TABLE `finished_unit_leak_damage_log` DROP INDEX `uq_finished_leak_unit`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log'
        AND INDEX_NAME = 'idx_finished_leak_unit'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` ADD KEY `idx_finished_leak_unit` (`unit_id`)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'finished_unit_leak_damage_log'
        AND INDEX_NAME = 'idx_finished_leak_sku'
    ),
    'SELECT 1',
    'ALTER TABLE `finished_unit_leak_damage_log` ADD KEY `idx_finished_leak_sku` (`sku_id`)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
