-- Pack cancel barcode restock sessions (courier opened return → finished stock).
-- Run once: mysql -u root rts_system < sql/migration_phase61_pack_cancel_restock.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pack_cancel_restock_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `started_by` INT UNSIGNED NOT NULL,
  `status` ENUM('draft','confirmed','discarded') NOT NULL DEFAULT 'draft',
  `confirmed_at` DATETIME NULL DEFAULT NULL,
  `confirmed_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pack_cancel_restock_status` (`status`),
  CONSTRAINT `fk_pack_cancel_restock_starter` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_cancel_restock_confirmer` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pack_cancel_restock_honey_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pack_cancel_honey_sess_bc` (`session_id`, `barcode_id`),
  KEY `idx_pack_cancel_honey_unit` (`unit_id`),
  CONSTRAINT `fk_pack_cancel_honey_sess` FOREIGN KEY (`session_id`) REFERENCES `pack_cancel_restock_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pack_cancel_honey_bc` FOREIGN KEY (`barcode_id`) REFERENCES `honey_bottle_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_cancel_honey_unit` FOREIGN KEY (`unit_id`) REFERENCES `honey_bottle_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pack_cancel_restock_dept_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pack_cancel_dept_sess_bc` (`session_id`, `barcode_id`),
  KEY `idx_pack_cancel_dept_unit` (`unit_id`),
  KEY `idx_pack_cancel_dept_product` (`product_key`),
  CONSTRAINT `fk_pack_cancel_dept_sess` FOREIGN KEY (`session_id`) REFERENCES `pack_cancel_restock_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pack_cancel_dept_bc` FOREIGN KEY (`barcode_id`) REFERENCES `dept_pouch_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pack_cancel_dept_unit` FOREIGN KEY (`unit_id`) REFERENCES `dept_pouch_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
