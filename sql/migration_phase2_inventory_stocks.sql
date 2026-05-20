-- Phase 2: stocks, stock_entries, edit_requests
-- Run after migration_phase1_inventory_auth.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `stocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  `current_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `supplier_name` VARCHAR(160) NULL,
  `supplier_code` VARCHAR(64) NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stocks_name_dept` (`name`, `dept_id`),
  KEY `idx_stocks_dept` (`dept_id`),
  KEY `idx_stocks_created_by` (`created_by`),
  CONSTRAINT `fk_stocks_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stocks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `batching_date` DATETIME NOT NULL,
  `supplier_batch_no` VARCHAR(120) NULL,
  `entered_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_entries_stock` (`stock_id`),
  KEY `idx_stock_entries_dept` (`dept_id`),
  KEY `idx_stock_entries_entered_by` (`entered_by`),
  KEY `idx_stock_entries_created_at` (`created_at`),
  CONSTRAINT `fk_stock_entries_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_entries_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_entries_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `edit_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(64) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `field_name` VARCHAR(64) NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `reason` TEXT NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_note` TEXT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edit_requests_status` (`status`),
  KEY `idx_edit_requests_requester` (`requested_by`),
  KEY `idx_edit_requests_reviewed_by` (`reviewed_by`),
  KEY `idx_edit_requests_table_record` (`table_name`, `record_id`),
  CONSTRAINT `fk_edit_requests_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_edit_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

