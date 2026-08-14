-- Honey bottle barcode units: requests, batches, codes, inventory, pack sessions.
-- Run once: mysql -u root rts_system < sql/migration_phase54_honey_bottle_barcodes.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `honey_bottle_barcode_seq` (
  `id` TINYINT UNSIGNED NOT NULL,
  `next_seq` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `honey_bottle_barcode_seq` (`id`, `next_seq`) VALUES (1, 1);

CREATE TABLE IF NOT EXISTS `honey_bottle_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requested_by` INT UNSIGNED NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `variant` VARCHAR(32) NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','barcodes_generated','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_bottle_req_status` (`status`),
  KEY `idx_honey_bottle_req_by` (`requested_by`),
  KEY `idx_honey_bottle_req_pack` (`packaging_row_id`),
  CONSTRAINT `fk_honey_bottle_req_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_bottle_req_pack` FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_barcode_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NULL DEFAULT NULL,
  `source` ENUM('request','backfill') NOT NULL DEFAULT 'request',
  `generated_by` INT UNSIGNED NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `batch_date` DATE NOT NULL,
  `variant` VARCHAR(32) NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NOT NULL,
  `quantity_generated` INT UNSIGNED NOT NULL,
  `printed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_bc_batch_request` (`request_id`),
  KEY `idx_honey_bc_batch_pack` (`packaging_row_id`),
  KEY `idx_honey_bc_batch_source` (`source`),
  CONSTRAINT `fk_honey_bc_batch_request` FOREIGN KEY (`request_id`) REFERENCES `honey_bottle_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_honey_bc_batch_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_bc_batch_pack` FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_bottle_barcodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NOT NULL,
  `status` ENUM('generated','printed','scanned','void') NOT NULL DEFAULT 'generated',
  `scanned_at` DATETIME NULL DEFAULT NULL,
  `scanned_by` INT UNSIGNED NULL DEFAULT NULL,
  `void_reason` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_honey_bottle_code` (`code`),
  UNIQUE KEY `uq_honey_bottle_seq` (`sequence_no`),
  KEY `idx_honey_bottle_bc_batch` (`batch_id`),
  KEY `idx_honey_bottle_bc_status` (`status`),
  CONSTRAINT `fk_honey_bottle_bc_batch` FOREIGN KEY (`batch_id`) REFERENCES `honey_barcode_batches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_bottle_bc_scanner` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_bottle_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `barcode_id` INT UNSIGNED NOT NULL,
  `variant` VARCHAR(32) NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NOT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `pool` ENUM('packaging','rts_sellable','rts_damaged') NOT NULL DEFAULT 'packaging',
  `status` ENUM('in_stock','packed','sold','shipped','rts','damaged','void') NOT NULL DEFAULT 'in_stock',
  `created_date` DATE NOT NULL,
  `consumed_at` DATETIME NULL DEFAULT NULL,
  `consumed_ref_type` VARCHAR(32) NULL DEFAULT NULL,
  `consumed_ref_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_honey_bottle_unit_barcode` (`barcode_id`),
  KEY `idx_honey_bottle_unit_status` (`status`, `variant`, `size`),
  KEY `idx_honey_bottle_unit_pack` (`packaging_row_id`),
  KEY `idx_honey_bottle_unit_pool` (`pool`, `status`),
  CONSTRAINT `fk_honey_bottle_unit_bc` FOREIGN KEY (`barcode_id`) REFERENCES `honey_bottle_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_bottle_unit_pack` FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_bottle_pack_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `started_by` INT UNSIGNED NOT NULL,
  `status` ENUM('draft','confirmed','discarded') NOT NULL DEFAULT 'draft',
  `confirmed_at` DATETIME NULL DEFAULT NULL,
  `confirmed_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_pack_sess_status` (`status`),
  CONSTRAINT `fk_honey_pack_sess_starter` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_pack_sess_confirmer` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_bottle_pack_session_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_honey_pack_sess_barcode` (`session_id`, `barcode_id`),
  KEY `idx_honey_pack_sess_unit` (`unit_id`),
  CONSTRAINT `fk_honey_pack_item_sess` FOREIGN KEY (`session_id`) REFERENCES `honey_bottle_pack_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_honey_pack_item_bc` FOREIGN KEY (`barcode_id`) REFERENCES `honey_bottle_barcodes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_pack_item_unit` FOREIGN KEY (`unit_id`) REFERENCES `honey_bottle_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_unit := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'bottle_unit_id'
);
SET @sql_unit := IF(
  @has_unit = 0,
  'ALTER TABLE `honey_sales_log`
     ADD COLUMN `bottle_unit_id` INT UNSIGNED NULL DEFAULT NULL AFTER `pos_sale_id`,
     ADD KEY `idx_honey_sales_bottle_unit` (`bottle_unit_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_unit;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_sess := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_sales_log' AND COLUMN_NAME = 'pack_session_id'
);
SET @sql_sess := IF(
  @has_sess = 0,
  'ALTER TABLE `honey_sales_log`
     ADD COLUMN `pack_session_id` INT UNSIGNED NULL DEFAULT NULL AFTER `bottle_unit_id`,
     ADD KEY `idx_honey_sales_pack_session` (`pack_session_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql_sess;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
