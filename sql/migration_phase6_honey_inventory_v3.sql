-- Phase 6: Honey Inventory Spec v3 (warehouse -> production -> packaging)
-- Run after phase 5. Keeps generic stocks/products/costing unchanged.

SET NAMES utf8mb4;

INSERT IGNORE INTO `roles` (`name`) VALUES ('hr');

CREATE TABLE IF NOT EXISTS `honey_warehouse_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_name` VARCHAR(160) NOT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `arrival_date` DATE NOT NULL,
  `variant` ENUM('light','amber','dark','other') NOT NULL,
  `current_qty` DECIMAL(12,2) NOT NULL,
  `initial_qty` DECIMAL(12,2) NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_wh_supplier_code` (`supplier_code`),
  KEY `idx_honey_wh_arrival` (`arrival_date`),
  KEY `idx_honey_wh_variant` (`variant`),
  CONSTRAINT `fk_honey_wh_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_production_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_row_id` INT UNSIGNED NOT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `arrival_date` DATE NOT NULL,
  `variant` ENUM('light','amber','dark','other') NOT NULL,
  `current_qty` DECIMAL(12,2) NOT NULL,
  `initial_qty` DECIMAL(12,2) NOT NULL,
  `transferred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_pr_wh` (`warehouse_row_id`),
  KEY `idx_honey_pr_supplier_code` (`supplier_code`),
  CONSTRAINT `fk_honey_pr_wh` FOREIGN KEY (`warehouse_row_id`) REFERENCES `honey_warehouse_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_pr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_packaging_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_row_id` INT UNSIGNED NOT NULL,
  `supplier_code` VARCHAR(32) NOT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `bottling_date` DATE NOT NULL,
  `variant` ENUM('light','amber','dark','other') NOT NULL,
  `current_liter` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_liter` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_250ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_250ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_350ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_350ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `current_gallon` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `initial_gallon` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_honey_pk_batch_no` (`batch_no`),
  KEY `idx_honey_pk_prod` (`production_row_id`),
  KEY `idx_honey_pk_bottling` (`bottling_date`),
  CONSTRAINT `fk_honey_pk_prod` FOREIGN KEY (`production_row_id`) REFERENCES `honey_production_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_honey_pk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `honey_sales_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` ENUM('packaging','rts') NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL,
  `rts_inventory_row_id` INT UNSIGNED NULL,
  `batch_no` VARCHAR(48) NULL,
  `variant` VARCHAR(32) NULL,
  `qty_liter` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty_250ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty_350ml` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty_gallon` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `qty_other` DECIMAL(12,2) NULL COMMENT 'non-honey RTS products',
  `product_label` VARCHAR(120) NULL,
  `sale_type` ENUM('walk_in','parcel') NOT NULL,
  `sold_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_honey_sales_packaging` (`packaging_row_id`),
  KEY `idx_honey_sales_batch` (`batch_no`),
  KEY `idx_honey_sales_sold_by` (`sold_by`),
  CONSTRAINT `fk_honey_sales_packaging` FOREIGN KEY (`packaging_row_id`) REFERENCES `honey_packaging_rows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_honey_sales_sold_by` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_correction_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stage` ENUM('warehouse','production','packaging','rts','dept') NOT NULL,
  `row_table` VARCHAR(64) NOT NULL,
  `row_id` INT UNSIGNED NOT NULL,
  `action` ENUM('add','deduct') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `size` ENUM('liter','250ml','350ml','gallon') NULL,
  `reason` TEXT NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_note` TEXT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_icr_status` (`status`),
  KEY `idx_icr_requested_by` (`requested_by`),
  KEY `idx_icr_stage_row` (`stage`, `row_table`, `row_id`),
  CONSTRAINT `fk_icr_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_icr_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phase 7 tables (dept inventory) — created here for single migration run
CREATE TABLE IF NOT EXISTS `dept_inventory_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` INT UNSIGNED NOT NULL,
  `product_label` VARCHAR(120) NOT NULL,
  `current_qty` DECIMAL(12,2) NOT NULL,
  `initial_qty` DECIMAL(12,2) NOT NULL,
  `source_note` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept_inv_dept` (`dept_id`),
  CONSTRAINT `fk_dept_inv_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dept_inv_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
