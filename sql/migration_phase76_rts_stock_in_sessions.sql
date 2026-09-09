-- RTS sellable stock-in draft/review sessions.
-- Run once: mysql -u root rts_system < sql/migration_phase76_rts_stock_in_sessions.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rts_stock_in_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `started_by` INT UNSIGNED NOT NULL,
  `status` ENUM('draft','confirmed','discarded') NOT NULL DEFAULT 'draft',
  `confirmed_at` DATETIME NULL DEFAULT NULL,
  `confirmed_by` INT UNSIGNED NULL DEFAULT NULL,
  `discarded_at` DATETIME NULL DEFAULT NULL,
  `discarded_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rts_stock_session_owner_status` (`started_by`, `status`),
  CONSTRAINT `fk_rts_stock_session_starter` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rts_stock_session_confirmer` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rts_stock_session_discarder` FOREIGN KEY (`discarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rts_stock_in_session_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `kind` ENUM('honey','dept') NOT NULL,
  `lookup_kind` ENUM('sku','unique_honey','unique_dept') NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `sku_id` INT UNSIGNED NULL DEFAULT NULL,
  `honey_barcode_id` INT UNSIGNED NULL DEFAULT NULL,
  `dept_barcode_id` INT UNSIGNED NULL DEFAULT NULL,
  `variant` VARCHAR(32) NULL DEFAULT NULL,
  `size` VARCHAR(16) NULL DEFAULT NULL,
  `product_key` VARCHAR(32) NULL DEFAULT NULL,
  `product_label` VARCHAR(96) NOT NULL,
  `qty` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rts_stock_session_code` (`session_id`, `code`),
  KEY `idx_rts_stock_item_sku` (`sku_id`),
  KEY `idx_rts_stock_item_honey_bc` (`honey_barcode_id`),
  KEY `idx_rts_stock_item_dept_bc` (`dept_barcode_id`),
  CONSTRAINT `fk_rts_stock_item_session` FOREIGN KEY (`session_id`) REFERENCES `rts_stock_in_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rts_stock_item_sku` FOREIGN KEY (`sku_id`) REFERENCES `product_sku_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rts_stock_item_honey_bc` FOREIGN KEY (`honey_barcode_id`) REFERENCES `honey_bottle_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rts_stock_item_dept_bc` FOREIGN KEY (`dept_barcode_id`) REFERENCES `dept_pouch_barcodes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rts_stock_in_session_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NULL DEFAULT NULL,
  `event_type` ENUM('scan','update','remove','confirm','discard') NOT NULL,
  `request_key` VARCHAR(64) NULL DEFAULT NULL,
  `code` VARCHAR(64) NULL DEFAULT NULL,
  `qty_delta` INT NULL DEFAULT NULL,
  `actor_user_id` INT UNSIGNED NOT NULL,
  `payload_json` JSON NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rts_stock_event_request` (`request_key`),
  KEY `idx_rts_stock_event_session` (`session_id`, `id`),
  CONSTRAINT `fk_rts_stock_event_session` FOREIGN KEY (`session_id`) REFERENCES `rts_stock_in_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rts_stock_event_item` FOREIGN KEY (`item_id`) REFERENCES `rts_stock_in_session_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rts_stock_event_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
