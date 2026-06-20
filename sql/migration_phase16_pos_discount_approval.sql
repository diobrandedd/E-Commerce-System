-- Phase 16: POS discount approval workflow + discount-aware sales columns.
-- Run after phase 15.

SET NAMES utf8mb4;

ALTER TABLE `pos_sales`
  ADD COLUMN `subtotal_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `buyer_credentials`,
  ADD COLUMN `discount_percent` TINYINT UNSIGNED NULL AFTER `subtotal_amount`,
  ADD COLUMN `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `discount_percent`;

UPDATE `pos_sales`
SET `subtotal_amount` = `total_amount`,
    `discount_amount` = 0.00
WHERE `subtotal_amount` = 0.00;

ALTER TABLE `pos_receipt_items`
  ADD COLUMN `list_unit_price` DECIMAL(12,2) NULL AFTER `unit_price`,
  ADD COLUMN `discount_percent` TINYINT UNSIGNED NULL AFTER `line_total`;

CREATE TABLE IF NOT EXISTS `pos_discount_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_no` VARCHAR(32) NOT NULL,
  `status` ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `sold_by_pin_id` INT UNSIGNED NOT NULL,
  `buyer_name` VARCHAR(160) NOT NULL,
  `buyer_credentials` VARCHAR(160) NULL,
  `discount_percent` TINYINT UNSIGNED NOT NULL,
  `subtotal_amount` DECIMAL(12,2) NOT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `cart_json` LONGTEXT NOT NULL,
  `pos_sale_id` INT UNSIGNED NULL,
  `decline_note` VARCHAR(255) NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pos_discount_requests_no` (`request_no`),
  KEY `idx_pos_discount_requests_status` (`status`),
  KEY `idx_pos_discount_requests_sale` (`pos_sale_id`),
  KEY `idx_pos_discount_requests_pin` (`sold_by_pin_id`),
  KEY `idx_pos_discount_requests_created_at` (`created_at`),
  CONSTRAINT `fk_pos_discount_requests_pin` FOREIGN KEY (`sold_by_pin_id`) REFERENCES `pos_pins` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pos_discount_requests_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pos_discount_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
