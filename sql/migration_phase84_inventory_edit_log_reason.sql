-- Reason text on inventory edit audit rows (executive corrections).
-- Safe to re-run.

SET @db := DATABASE();
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_edit_log' AND COLUMN_NAME = 'reason'
    ),
    'SELECT 1',
    'ALTER TABLE `inventory_edit_log` ADD COLUMN `reason` VARCHAR(500) NULL DEFAULT NULL AFTER `source`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
