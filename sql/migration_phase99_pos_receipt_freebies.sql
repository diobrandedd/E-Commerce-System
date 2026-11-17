-- Phase 99: POS receipt freebie lines (never in pos_receipt_items — keeps revenue reports clean)
-- Run once: mysql -u root rts_system < sql/migration_phase99_pos_receipt_freebies.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_receipt_freebies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `qty` INT UNSIGNED NOT NULL,
  `code` VARCHAR(64) NULL DEFAULT NULL,
  `freebie_log_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prf_sale` (`sale_id`),
  KEY `idx_prf_product` (`product_key`),
  CONSTRAINT `fk_prf_sale`
    FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
