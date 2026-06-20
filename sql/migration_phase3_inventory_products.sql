-- Phase 3: products, product_formula, stock_deductions, beginning_inventory
-- Run after phase 1 and phase 2 migrations.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `size` ENUM('liter','350ml','250ml','gallon') NULL,
  `variant` ENUM('light','amber','dark') NULL,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `baseline_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_dept` (`dept_id`),
  KEY `idx_products_created_by` (`created_by`),
  KEY `idx_products_name` (`name`),
  CONSTRAINT `fk_products_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_formula` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty_per_unit` DECIMAL(12,4) NOT NULL,
  `price_at_creation` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_formula_product` (`product_id`),
  KEY `idx_product_formula_stock` (`stock_id`),
  CONSTRAINT `fk_product_formula_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_formula_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_deductions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `deducted_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_deductions_stock` (`stock_id`),
  KEY `idx_stock_deductions_dept` (`dept_id`),
  KEY `idx_stock_deductions_by` (`deducted_by`),
  KEY `idx_stock_deductions_created_at` (`created_at`),
  CONSTRAINT `fk_stock_deductions_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_deductions_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_deductions_by` FOREIGN KEY (`deducted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `beginning_inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `set_by` INT UNSIGNED NOT NULL,
  `month_year` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_beginning_inventory_scope` (`dept_id`, `stock_id`, `month_year`),
  KEY `idx_beginning_inventory_stock` (`stock_id`),
  KEY `idx_beginning_inventory_set_by` (`set_by`),
  KEY `idx_beginning_inventory_month` (`month_year`),
  CONSTRAINT `fk_beginning_inventory_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_beginning_inventory_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_beginning_inventory_set_by` FOREIGN KEY (`set_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

