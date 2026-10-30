-- Phase 96: Permanent freebie barcodes (PT / BRC / Jar Cacao) + pack-scan freebie pool
-- Run once: mysql -u root rts_system < sql/migration_phase96_dept_raw_freebie_barcodes.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `dept_raw_freebie_barcodes` (
  `product_key` VARCHAR(32) NOT NULL,
  `code` VARCHAR(32) NOT NULL,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_key`),
  UNIQUE KEY `uq_drfb_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `dept_raw_freebie_barcodes` (`product_key`, `code`)
VALUES ('pt', 'FRB-PT'), ('brc', 'FRB-BRC'), ('jar_cacao', 'FRB-CACAO');

-- Pack scan lines can now point at the freebie pool (give-out, not finished stock).
SET @pool_type := (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_bottle_pack_session_dept_items'
    AND COLUMN_NAME = 'pool'
);
SET @sql := IF(
  @pool_type IS NULL OR @pool_type LIKE '%''freebie''%',
  'SELECT 1',
  'ALTER TABLE `honey_bottle_pack_session_dept_items`
     MODIFY COLUMN `pool` ENUM(''finished'',''rts_sellable'',''freebie'') NULL DEFAULT NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
