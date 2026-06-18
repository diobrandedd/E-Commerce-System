-- Phase 38: Generic bubble warehouse SKU, sized staging, damage reporting
-- Run after phase 34. Then: php tools/memory_bank/generate_all.php

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_fulfillment := (
  SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1
);

-- Staging balance: track damaged qty separately from ship consumption
SET @col_damaged := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fulfillment_packing_balances'
    AND COLUMN_NAME = 'damaged_qty'
);
SET @sql_damaged := IF(
  @col_damaged = 0,
  'ALTER TABLE `fulfillment_packing_balances` ADD COLUMN `damaged_qty` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 AFTER `consumed_qty`',
  'SELECT 1'
);
PREPARE stmt_damaged FROM @sql_damaged;
EXECUTE stmt_damaged;
DEALLOCATE PREPARE stmt_damaged;

CREATE TABLE IF NOT EXISTS `fulfillment_packing_damage_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(14,6) NOT NULL,
  `note` VARCHAR(255) NOT NULL,
  `reported_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fpde_stock` (`stock_id`),
  KEY `idx_fpde_created` (`created_at`),
  CONSTRAINT `fk_fpde_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_source := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fulfillment_packing_issue_lines'
    AND COLUMN_NAME = 'source_stock_id'
);
SET @sql_source := IF(
  @col_source = 0,
  'ALTER TABLE `fulfillment_packing_issue_lines`
     ADD COLUMN `source_stock_id` INT UNSIGNED NULL DEFAULT NULL AFTER `stock_id`,
     ADD COLUMN `bubble_width_cm` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `source_stock_id`',
  'SELECT 1'
);
PREPARE stmt_source FROM @sql_source;
EXECUTE stmt_source;
DEALLOCATE PREPARE stmt_source;

-- Generic warehouse bubble roll (width assigned at issue to packing)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Bubble wrap roll', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE name = 'Bubble wrap roll' AND supplier_id IS NULL
      AND name NOT LIKE 'Bubble wrap roll -%'
  );

-- Merge sized bubble warehouse on_hand into generic SKU
SET @generic_id := (
  SELECT id FROM stocks
  WHERE supplier_id IS NULL AND name = 'Bubble wrap roll'
    AND name NOT LIKE 'Bubble wrap roll -%'
  ORDER BY id ASC LIMIT 1
);

SET @sized_sum := (
  SELECT COALESCE(SUM(on_hand), 0)
  FROM stocks
  WHERE supplier_id IS NULL
    AND name IN (
      'Bubble wrap roll - 13 cm',
      'Bubble wrap roll - 17 cm',
      'Bubble wrap roll - 22 cm'
    )
);

UPDATE stocks
SET on_hand = ROUND(on_hand + @sized_sum, 4)
WHERE id = @generic_id AND @generic_id IS NOT NULL AND @sized_sum > 0;

UPDATE stocks
SET on_hand = 0
WHERE supplier_id IS NULL
  AND name IN (
    'Bubble wrap roll - 13 cm',
    'Bubble wrap roll - 17 cm',
    'Bubble wrap roll - 22 cm'
  );
