-- Phase 10: expanded correction types, executive viewed tracking, edit audit log.

SET @db := DATABASE();

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_correction_requests' AND COLUMN_NAME = 'correction_type'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `inventory_correction_requests`
     ADD COLUMN `correction_type` ENUM(''qty_delta'',''set_qty'',''variant'',''supplier'',''packaging_size'') NOT NULL DEFAULT ''qty_delta'' AFTER `row_id`,
     ADD COLUMN `new_value` VARCHAR(255) NULL AFTER `correction_type`',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_correction_requests' AND COLUMN_NAME = 'executive_viewed_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `inventory_correction_requests`
     ADD COLUMN `executive_viewed_at` DATETIME NULL AFTER `reviewed_at`,
     ADD KEY `idx_icr_exec_unread` (`status`, `executive_viewed_at`)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `inventory_edit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `row_table` VARCHAR(64) NOT NULL,
  `row_id` INT UNSIGNED NOT NULL,
  `field_name` VARCHAR(64) NOT NULL,
  `old_value` VARCHAR(255) NULL,
  `new_value` VARCHAR(255) NULL,
  `edited_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iel_row` (`row_table`, `row_id`),
  CONSTRAINT `fk_iel_edited_by` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
