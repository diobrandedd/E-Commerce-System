-- Unified finished-unit leak/damage scan log (honey + dept stickers).
-- Run once: mysql -u root rts_system < sql/migration_phase63_finished_leak_damage_log.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `finished_unit_leak_damage_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` ENUM('honey','dept') NOT NULL,
  `product_key` VARCHAR(32) NULL DEFAULT NULL,
  `variant` VARCHAR(32) NULL DEFAULT NULL,
  `size` VARCHAR(16) NULL DEFAULT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL DEFAULT '',
  `reason` ENUM('leaked','damaged') NOT NULL,
  `scanned_by` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_finished_leak_unit` (`unit_id`),
  KEY `idx_finished_leak_kind_at` (`kind`, `scanned_at`),
  KEY `idx_finished_leak_reason` (`reason`, `scanned_at`),
  KEY `idx_finished_leak_product` (`product_key`, `scanned_at`),
  CONSTRAINT `fk_finished_leak_user` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate legacy Jar Cacao defect rows when table exists.
SET @defect_tbl := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_defect_log'
);
SET @sql := IF(
  @defect_tbl > 0,
  'INSERT IGNORE INTO finished_unit_leak_damage_log
     (kind, product_key, variant, size, barcode_id, unit_id, code, batch_no, reason, scanned_by, scanned_at, note)
   SELECT ''dept'', product_key, NULL, NULL, barcode_id, unit_id, code, batch_no, ''damaged'', scanned_by, scanned_at, note
   FROM dept_pouch_defect_log',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
