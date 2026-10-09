-- Phase 92: Raw freebie sample balances + audit log (PT / BRC / Jar Cacao)
-- Run once: mysql -u root rts_system < sql/migration_phase92_dept_raw_freebies.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `dept_raw_freebie_balances` (
  `product_key` VARCHAR(32) NOT NULL,
  `on_hand` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `dept_raw_freebie_balances` (`product_key`, `on_hand`)
VALUES ('pt', 0), ('brc', 0), ('jar_cacao', 0);

CREATE TABLE IF NOT EXISTS `dept_raw_freebie_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_key` VARCHAR(32) NOT NULL,
  `action` ENUM('create', 'give_out') NOT NULL,
  `qty_samples` INT UNSIGNED NOT NULL,
  `grams_per_sample` DECIMAL(12,4) NULL DEFAULT NULL,
  `grams_total` DECIMAL(14,4) NULL DEFAULT NULL,
  `bags_deducted` DECIMAL(14,6) NULL DEFAULT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `reported_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_drf_product_created` (`product_key`, `created_at`),
  KEY `idx_drf_created` (`created_at`),
  CONSTRAINT `fk_drf_user`
    FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
