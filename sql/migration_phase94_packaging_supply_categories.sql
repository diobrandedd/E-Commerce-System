-- Phase 94: User-defined categories for supplies (packaging catalog)
-- Run once: mysql -u root rts_system < sql/migration_phase94_packaging_supply_categories.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `packaging_supply_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_packaging_supply_categories_name` (`name`),
  CONSTRAINT `fk_packaging_supply_categories_user`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stocks'
    AND COLUMN_NAME = 'supply_category_id'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `stocks`
     ADD COLUMN `supply_category_id` INT UNSIGNED NULL DEFAULT NULL AFTER `category`,
     ADD KEY `idx_stocks_supply_category` (`supply_category_id`),
     ADD CONSTRAINT `fk_stocks_supply_category`
       FOREIGN KEY (`supply_category_id`) REFERENCES `packaging_supply_categories` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
