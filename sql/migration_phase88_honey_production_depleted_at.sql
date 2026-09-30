-- Phase 88: Track when a production lot first hits empty/overdrawn for 1h auto-deactivate
SET NAMES utf8mb4;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'depleted_at'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `depleted_at` DATETIME NULL DEFAULT NULL AFTER `is_active`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND INDEX_NAME = 'idx_honey_production_depleted_at'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD KEY `idx_honey_production_depleted_at` (`depleted_at`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Lots already empty/overdrawn: stamp depleted_at from transfer time so the 1h grace
-- has already elapsed for long-empty rows (they deactivate on next list/FIFO sweep).
UPDATE `honey_production_rows`
SET `depleted_at` = COALESCE(`transferred_at`, `created_at`, NOW())
WHERE `current_qty` <= 0
  AND `depleted_at` IS NULL;

UPDATE `honey_production_rows`
SET `depleted_at` = NULL
WHERE `current_qty` > 0
  AND `depleted_at` IS NOT NULL;
