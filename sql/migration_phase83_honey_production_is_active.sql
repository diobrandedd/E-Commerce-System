-- Phase 83: Production lot active flag (executive deactivate; hide from scan select)
SET NAMES utf8mb4;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `initial_qty`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND INDEX_NAME = 'idx_honey_production_is_active'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD KEY `idx_honey_production_is_active` (`is_active`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
