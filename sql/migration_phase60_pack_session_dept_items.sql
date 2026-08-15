-- Pack scan mixed products: dept pouch/jar lines beside honey bottle items.
-- Run once: mysql -u root rts_system < sql/migration_phase60_pack_session_dept_items.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `honey_bottle_pack_session_dept_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pack_dept_sess_barcode` (`session_id`, `barcode_id`),
  KEY `idx_pack_dept_sess` (`session_id`),
  KEY `idx_pack_dept_unit` (`unit_id`),
  KEY `idx_pack_dept_product` (`product_key`),
  CONSTRAINT `fk_pack_dept_item_sess` FOREIGN KEY (`session_id`) REFERENCES `honey_bottle_pack_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pack_dept_item_bc` FOREIGN KEY (`barcode_id`) REFERENCES `dept_pouch_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_dept_item_unit` FOREIGN KEY (`unit_id`) REFERENCES `dept_pouch_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
