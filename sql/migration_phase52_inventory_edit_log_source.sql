-- Monthly inventory edit log: optional source for filters (monthly_inventory / executive_edit / set_on_hand).
SET @db := DATABASE();
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_edit_log' AND COLUMN_NAME = 'source'
    ),
    'SELECT 1',
    'ALTER TABLE `inventory_edit_log` ADD COLUMN `source` VARCHAR(64) NULL DEFAULT NULL AFTER `edited_by`, ADD KEY `idx_iel_source` (`source`), ADD KEY `idx_iel_created` (`created_at`)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
