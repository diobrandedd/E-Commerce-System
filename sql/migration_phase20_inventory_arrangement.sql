-- Phase 20: Inventory arrangement — stock categories, on_hand, material adjustment requests
-- Run after phase 19.

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @has_category := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'category'
);
SET @sql_category := IF(
  @has_category = 0,
  "ALTER TABLE `stocks`
     ADD COLUMN `category` ENUM('raw_material','packaging','supply','costing_only') NOT NULL DEFAULT 'costing_only' AFTER `unit`,
     ADD COLUMN `on_hand` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `category`,
     ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `on_hand`",
  'SELECT 1'
);
PREPARE stmt FROM @sql_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `material_adjustment_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `action` ENUM('add','deduct') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_note` TEXT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `applied_entry_id` INT UNSIGNED NULL,
  `executive_viewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mar_status` (`status`),
  KEY `idx_mar_stock` (`stock_id`),
  KEY `idx_mar_requested_by` (`requested_by`),
  CONSTRAINT `fk_mar_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_mar_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_mar_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mar_applied_entry` FOREIGN KEY (`applied_entry_id`) REFERENCES `stock_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill dept raw + packaging BOM stocks
UPDATE `stocks` SET `category` = 'raw_material'
WHERE `name` IN ('Paragis leaf — raw', 'Black Rice Coffee — raw', 'Jar Cacao — raw');

UPDATE `stocks` SET `category` = 'packaging'
WHERE `name` IN ('PT pouch packaging', 'BRC pouch packaging', 'Jar Cacao jar packaging');

UPDATE `stocks` SET `category` = 'raw_material'
WHERE `name` IN ('Raw Honey — Light', 'Raw Honey — Amber', 'Raw Honey — Dark')
   OR `name` LIKE 'Raw Honey — %';

SET @dept_honey := (
  SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1
);

-- Honey packaging catalog (executive-managed on_hand)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Honey Bottles', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey Bottles' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Bottle Caps', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Bottle Caps' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Labels', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Labels' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Boxes', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Boxes' AND supplier_id IS NULL);
