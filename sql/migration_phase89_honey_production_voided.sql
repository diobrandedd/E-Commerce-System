-- Phase 89: Soft-void empty production lots (hide from main list; refill can unvoid)
SET NAMES utf8mb4;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'voided_at'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `voided_at` DATETIME NULL DEFAULT NULL AFTER `depleted_at`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND COLUMN_NAME = 'voided_by'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD COLUMN `voided_by` INT UNSIGNED NULL DEFAULT NULL AFTER `voided_at`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_production_rows'
    AND INDEX_NAME = 'idx_honey_production_voided_at'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `honey_production_rows` ADD KEY `idx_honey_production_voided_at` (`voided_at`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Long-empty inactive lots: stamp voided_at so first Production load hides them.
UPDATE `honey_production_rows`
SET `voided_at` = COALESCE(`depleted_at`, `transferred_at`, `created_at`, NOW()),
    `is_active` = 0
WHERE `current_qty` <= 0
  AND COALESCE(`is_active`, 1) = 0
  AND `voided_at` IS NULL
  AND (
    (`depleted_at` IS NOT NULL AND `depleted_at` <= (NOW() - INTERVAL 2 DAY))
    OR (`depleted_at` IS NULL AND COALESCE(`transferred_at`, `created_at`) <= (NOW() - INTERVAL 2 DAY))
  );

UPDATE `honey_production_rows`
SET `voided_at` = NULL, `voided_by` = NULL
WHERE `current_qty` > 0
  AND `voided_at` IS NOT NULL;
