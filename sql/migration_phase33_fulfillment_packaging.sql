-- Phase 33: Fulfillment packaging SKUs + parcel fulfillment deduct log
-- Run after phase 32. Then: php tools/seed_fulfillment_pack_tiers.php

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

CREATE TABLE IF NOT EXISTS `online_parcel_fulfillment_deduct_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parcel_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(14,6) NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_opfdl_parcel` (`parcel_id`),
  KEY `idx_opfdl_stock` (`stock_id`),
  CONSTRAINT `fk_opfdl_parcel`
    FOREIGN KEY (`parcel_id`) REFERENCES `online_order_parcels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_opfdl_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deactivate legacy generic fulfillment SKUs (phase 30)
UPDATE stocks SET is_active = 0
WHERE supplier_id IS NULL
  AND name IN (
    'Parcel bubble wrap',
    'Parcel cushion - standard',
    'Parcel cushion - medium',
    'Parcel cushion - large',
    'Parcel cushion - small separator',
    'Courier pouch bag',
    'Parcel insulation foam wrap'
  );

-- Bubble wrap rolls
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Bubble wrap roll - 13 cm', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 264.0000, 'bubble_piece', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Bubble wrap roll - 13 cm' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Bubble wrap roll - 17 cm', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 204.0000, 'bubble_piece', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Bubble wrap roll - 17 cm' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Bubble wrap roll - 22 cm', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 136.0000, 'bubble_piece', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Bubble wrap roll - 22 cm' AND supplier_id IS NULL);

-- Cushion pre-cut pieces
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - 1 liter', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - 1 liter' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - 2 liters', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - 2 liters' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - 3 liters', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - 3 liters' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - LAD', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - LAD' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - half LAD', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - half LAD' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cushion piece - honey comb', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion piece - honey comb' AND supplier_id IS NULL);

-- Cushion column rolls
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion column roll - 25', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 25.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion column roll - 25' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion column roll - 16 LAD', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 16.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion column roll - 16 LAD' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion column roll - 35 GAL', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 35.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion column roll - 35 GAL' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion column roll - 40 GAL', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 40.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion column roll - 40 GAL' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion column roll - 60 LAD', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 60.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cushion column roll - 60 LAD' AND supplier_id IS NULL);

-- Courier pouches
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Courier pouch - medium', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Courier pouch - medium' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Courier pouch - large', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Courier pouch - large' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Courier pouch - x-large', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Courier pouch - x-large' AND supplier_id IS NULL);

-- Foam sheets
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Insulation foam sheet - 9x12', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Insulation foam sheet - 9x12' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Insulation foam sheet - 12.5x12', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Insulation foam sheet - 12.5x12' AND supplier_id IS NULL);

-- Cardboard
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Cardboard box - whole', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Cardboard box - whole' AND supplier_id IS NULL);
