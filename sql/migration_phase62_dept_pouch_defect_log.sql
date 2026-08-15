-- Jar Cacao (and later dept) finished-unit defect scans.
-- Run once: mysql -u root rts_system < sql/migration_phase62_dept_pouch_defect_log.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `dept_pouch_defect_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_key` VARCHAR(32) NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL DEFAULT '',
  `scanned_by` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_defect_unit` (`unit_id`),
  KEY `idx_dept_defect_product_at` (`product_key`, `scanned_at`),
  KEY `idx_dept_defect_barcode` (`barcode_id`),
  CONSTRAINT `fk_dept_defect_barcode` FOREIGN KEY (`barcode_id`) REFERENCES `dept_pouch_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dept_defect_unit` FOREIGN KEY (`unit_id`) REFERENCES `dept_pouch_units` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dept_defect_user` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
