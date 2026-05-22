-- Phase 5: POS sales, receipt items, and AR (accounts receivable) credits.
-- Run after phase 4 migration.
--
-- These tables back the SPA POSPage, ARCreditsPage, and ReportsPage.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_sales` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_no` VARCHAR(32) NOT NULL,
  `buyer_name` VARCHAR(160) NOT NULL DEFAULT 'Walk-in',
  `buyer_credentials` VARCHAR(160) NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sold_by_pin_id` INT UNSIGNED NOT NULL,
  `sale_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pos_sales_receipt_no` (`receipt_no`),
  KEY `idx_pos_sales_sold_by` (`sold_by_pin_id`),
  KEY `idx_pos_sales_sale_date` (`sale_date`),
  CONSTRAINT `fk_pos_sales_pin` FOREIGN KEY (`sold_by_pin_id`) REFERENCES `pos_pins` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_receipt_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `line_total` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pos_receipt_items_sale` (`sale_id`),
  KEY `idx_pos_receipt_items_product` (`product_id`),
  CONSTRAINT `fk_pos_receipt_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pos_receipt_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ar_credits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `price_at_time` DECIMAL(12,2) NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('outstanding','paid') NOT NULL DEFAULT 'outstanding',
  `taken_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME NULL,
  `marked_paid_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ar_credits_user` (`user_id`),
  KEY `idx_ar_credits_product` (`product_id`),
  KEY `idx_ar_credits_status` (`status`),
  KEY `idx_ar_credits_taken_at` (`taken_at`),
  CONSTRAINT `fk_ar_credits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ar_credits_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ar_credits_marked_paid_by` FOREIGN KEY (`marked_paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
