-- Phase 4: stock price history + product cost history
-- Run after phase 3 migration.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_price_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `old_price` DECIMAL(12,2) NOT NULL,
  `new_price` DECIMAL(12,2) NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_price_history_stock` (`stock_id`),
  KEY `idx_stock_price_history_changed_by` (`changed_by`),
  KEY `idx_stock_price_history_changed_at` (`changed_at`),
  CONSTRAINT `fk_stock_price_history_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_price_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_cost_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `old_cost` DECIMAL(12,2) NOT NULL,
  `new_cost` DECIMAL(12,2) NOT NULL,
  `triggered_by_stock_id` INT UNSIGNED NOT NULL,
  `recalculated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_cost_history_product` (`product_id`),
  KEY `idx_product_cost_history_trigger_stock` (`triggered_by_stock_id`),
  KEY `idx_product_cost_history_recalculated_at` (`recalculated_at`),
  CONSTRAINT `fk_product_cost_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_cost_history_trigger_stock` FOREIGN KEY (`triggered_by_stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

