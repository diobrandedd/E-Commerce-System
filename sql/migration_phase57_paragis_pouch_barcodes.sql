-- Paragis Tea pouch barcodes: requests, batches, codes, units, never-reuse ledger.
-- Run once: mysql -u root rts_system < sql/migration_phase57_paragis_pouch_barcodes.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `paragis_pouch_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requested_by` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','barcodes_generated','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` VARCHAR(512) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pt_pouch_req_status` (`status`),
  KEY `idx_pt_pouch_req_by` (`requested_by`),
  KEY `idx_pt_pouch_req_batch` (`batch_no`),
  CONSTRAINT `fk_pt_pouch_req_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paragis_pouch_barcode_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NULL DEFAULT NULL,
  `generated_by` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `batch_date` DATE NOT NULL,
  `quantity_generated` INT UNSIGNED NOT NULL,
  `printed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pt_pouch_batch_request` (`request_id`),
  KEY `idx_pt_pouch_batch_no` (`batch_no`),
  CONSTRAINT `fk_pt_pouch_batch_request` FOREIGN KEY (`request_id`) REFERENCES `paragis_pouch_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pt_pouch_batch_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paragis_pouch_barcodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL,
  `status` ENUM('generated','printed','scanned','void') NOT NULL DEFAULT 'generated',
  `scanned_at` DATETIME NULL DEFAULT NULL,
  `scanned_by` INT UNSIGNED NULL DEFAULT NULL,
  `void_reason` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pt_pouch_code` (`code`),
  UNIQUE KEY `uq_pt_pouch_seq` (`sequence_no`),
  KEY `idx_pt_pouch_bc_batch` (`batch_id`),
  KEY `idx_pt_pouch_bc_status` (`status`),
  CONSTRAINT `fk_pt_pouch_bc_batch` FOREIGN KEY (`batch_id`) REFERENCES `paragis_pouch_barcode_batches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pt_pouch_bc_scanner` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paragis_pouch_codes_issued` (
  `code` VARCHAR(64) NOT NULL,
  `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paragis_pouch_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `barcode_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `status` ENUM('in_stock','packed','sold','shipped','rts','damaged','void') NOT NULL DEFAULT 'in_stock',
  `created_date` DATE NOT NULL,
  `consumed_at` DATETIME NULL DEFAULT NULL,
  `consumed_ref_type` VARCHAR(32) NULL DEFAULT NULL,
  `consumed_ref_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pt_pouch_unit_barcode` (`barcode_id`),
  KEY `idx_pt_pouch_unit_status` (`status`),
  KEY `idx_pt_pouch_unit_batch` (`batch_no`),
  CONSTRAINT `fk_pt_pouch_unit_bc` FOREIGN KEY (`barcode_id`) REFERENCES `paragis_pouch_barcodes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paragis_pouch_barcode_seq` (
  `id` TINYINT UNSIGNED NOT NULL,
  `next_seq` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `paragis_pouch_barcode_seq` (`id`, `next_seq`) VALUES (1, 1);
