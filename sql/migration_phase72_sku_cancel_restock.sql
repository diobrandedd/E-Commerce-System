-- Quantity-based cancelled restock entries for permanent SKU barcodes.
-- Run once: mysql -u root rts_system < sql/migration_phase72_sku_cancel_restock.sql

SET NAMES utf8mb4;

-- Legacy packaging rows normally point at production. Cancelled SKU returns
-- use one reserved, unassigned row per honey variant instead.
SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND COLUMN_NAME = 'production_row_id'
    AND IS_NULLABLE = 'NO'
);
SET @sql := IF(@c > 0,
  'ALTER TABLE `honey_packaging_rows` MODIFY COLUMN `production_row_id` INT UNSIGNED NULL DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS `pack_cancel_restock_sku_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `sku_id` INT UNSIGNED NOT NULL,
  `qty` INT UNSIGNED NOT NULL,
  `pool` ENUM('packaging','finished','rts_sellable') NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `scanned_by` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pack_cancel_sku_session` (`session_id`),
  KEY `idx_pack_cancel_sku_sku` (`sku_id`),
  KEY `idx_pack_cancel_sku_pool` (`pool`),
  KEY `idx_pack_cancel_sku_packaging` (`packaging_row_id`),
  CONSTRAINT `fk_pack_cancel_sku_session`
    FOREIGN KEY (`session_id`) REFERENCES `pack_cancel_restock_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pack_cancel_sku_sku`
    FOREIGN KEY (`sku_id`) REFERENCES `product_sku_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_cancel_sku_packaging`
    FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_cancel_sku_user`
    FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
