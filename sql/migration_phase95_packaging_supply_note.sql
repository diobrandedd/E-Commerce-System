-- Phase 95: Free-text note on supplies (where it came from, or any other detail)
-- Run once: mysql -u root rts_system < sql/migration_phase95_packaging_supply_note.sql

SET NAMES utf8mb4;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stocks'
    AND COLUMN_NAME = 'supply_note'
);
SET @after_cat := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stocks'
    AND COLUMN_NAME = 'supply_category_id'
);
SET @sql := IF(
  @col_exists > 0,
  'SELECT 1',
  IF(
    @after_cat > 0,
    'ALTER TABLE `stocks` ADD COLUMN `supply_note` VARCHAR(500) NULL DEFAULT NULL AFTER `supply_category_id`',
    'ALTER TABLE `stocks` ADD COLUMN `supply_note` VARCHAR(500) NULL DEFAULT NULL AFTER `category`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
