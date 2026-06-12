-- Phase 30: Parcel pack costing tiers + fulfillment packaging SKUs
-- Run after phase 29.

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

CREATE TABLE IF NOT EXISTS `parcel_pack_tiers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `family` ENUM('honey', 'dept_pt_brc', 'jar_cacao') NOT NULL,
  `tier_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(128) NOT NULL,
  `match_rule` JSON NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parcel_pack_tiers_key` (`tier_key`),
  KEY `idx_parcel_pack_tiers_family` (`family`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parcel_pack_tier_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tier_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NULL DEFAULT NULL,
  `line_label` VARCHAR(128) NULL DEFAULT NULL,
  `qty_typical` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `qty_min` DECIMAL(12,4) NULL DEFAULT NULL,
  `qty_max` DECIMAL(12,4) NULL DEFAULT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_parcel_pack_tier_lines_tier` (`tier_id`),
  CONSTRAINT `fk_parcel_pack_tier_lines_tier`
    FOREIGN KEY (`tier_id`) REFERENCES `parcel_pack_tiers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parcel_pack_tier_lines_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fulfillment packaging SKUs (honey dept pool)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel bubble wrap', @dept_fulfillment, 'wrap', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel bubble wrap' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel cushion — standard', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel cushion — standard' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel cushion — medium', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel cushion — medium' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel cushion — large', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel cushion — large' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel cushion — small separator', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel cushion — small separator' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Courier pouch bag', @dept_fulfillment, 'pcs', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Courier pouch bag' AND supplier_id IS NULL LIMIT 1);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Parcel insulation foam wrap', @dept_fulfillment, 'wrap', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE name = 'Parcel insulation foam wrap' AND supplier_id IS NULL LIMIT 1);

SET @stock_bubble := (SELECT id FROM stocks WHERE name = 'Parcel bubble wrap' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_cush_std := (SELECT id FROM stocks WHERE name = 'Parcel cushion — standard' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_cush_med := (SELECT id FROM stocks WHERE name = 'Parcel cushion — medium' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_cush_lg := (SELECT id FROM stocks WHERE name = 'Parcel cushion — large' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_cush_sep := (SELECT id FROM stocks WHERE name = 'Parcel cushion — small separator' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_pouch := (SELECT id FROM stocks WHERE name = 'Courier pouch bag' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);
SET @stock_foam := (SELECT id FROM stocks WHERE name = 'Parcel insulation foam wrap' AND supplier_id IS NULL ORDER BY id ASC LIMIT 1);

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'honey', 'honey_1x_1L', 'Honey — 1× 1L', '{"honey_1L_count":1}', 10
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'honey_1x_1L');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'honey', 'honey_2x_1L', 'Honey — 2× 1L', '{"honey_1L_count":2}', 20
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'honey_2x_1L');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'honey', 'honey_3x_1L', 'Honey — 3× 1L', '{"honey_1L_count":3}', 30
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'honey_3x_1L');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`, `is_active`)
SELECT 'honey', 'honey_250_350_solo', 'Honey — 250/350 solo (TBD)', '{"honey_250_350_solo":true}', 40, 0
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'honey_250_350_solo');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'dept_pt_brc', 'dept_1_2_units', 'PT/BRC — 1–2 units', '{"dept_units_min":1,"dept_units_max":2}', 10
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'dept_1_2_units');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'dept_pt_brc', 'dept_3_4_units', 'PT/BRC — 3–4 units', '{"dept_units_min":3,"dept_units_max":4}', 20
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'dept_3_4_units');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'jar_cacao', 'cacao_1_2_jars', 'Cacao — 1–2 jars', '{"jar_units_min":1,"jar_units_max":2}', 10
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'cacao_1_2_jars');

INSERT INTO `parcel_pack_tiers` (`family`, `tier_key`, `label`, `match_rule`, `sort_order`)
SELECT 'jar_cacao', 'cacao_3_4_jars', 'Cacao — 3–4 jars', '{"jar_units_min":3,"jar_units_max":4}', 20
WHERE NOT EXISTS (SELECT 1 FROM parcel_pack_tiers WHERE tier_key = 'cacao_3_4_jars');

-- Tier lines (idempotent via NOT EXISTS on tier+stock+label)
SET @tier_h1 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'honey_1x_1L' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h1, @stock_bubble, 2.5, 2, 3, 'wrap', 1
WHERE @tier_h1 IS NOT NULL AND @stock_bubble IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h1 AND stock_id = @stock_bubble);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h1, @stock_cush_std, 1, 1, 1, 'pcs', 2
WHERE @tier_h1 IS NOT NULL AND @stock_cush_std IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h1 AND stock_id = @stock_cush_std);

SET @tier_h2 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'honey_2x_1L' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h2, @stock_bubble, 3.5, 3, 4, 'wrap', 1
WHERE @tier_h2 IS NOT NULL AND @stock_bubble IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h2 AND stock_id = @stock_bubble);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h2, @stock_cush_med, 1, 1, 1, 'pcs', 2
WHERE @tier_h2 IS NOT NULL AND @stock_cush_med IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h2 AND stock_id = @stock_cush_med);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h2, @stock_cush_sep, 1, 1, 1, 'pcs', 3
WHERE @tier_h2 IS NOT NULL AND @stock_cush_sep IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h2 AND stock_id = @stock_cush_sep);

SET @tier_h3 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'honey_3x_1L' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h3, @stock_bubble, 3.5, 3, 4, 'wrap', 1
WHERE @tier_h3 IS NOT NULL AND @stock_bubble IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h3 AND stock_id = @stock_bubble);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h3, @stock_cush_lg, 1, 1, 1, 'pcs', 2
WHERE @tier_h3 IS NOT NULL AND @stock_cush_lg IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h3 AND stock_id = @stock_cush_lg);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_h3, @stock_cush_sep, 2, 2, 2, 'pcs', 3
WHERE @tier_h3 IS NOT NULL AND @stock_cush_sep IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_h3 AND stock_id = @stock_cush_sep);

SET @tier_d12 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'dept_1_2_units' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_d12, @stock_cush_std, 1, 1, 1, 'pcs', 1
WHERE @tier_d12 IS NOT NULL AND @stock_cush_std IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_d12 AND stock_id = @stock_cush_std);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_d12, @stock_pouch, 1, 1, 1, 'pcs', 2
WHERE @tier_d12 IS NOT NULL AND @stock_pouch IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_d12 AND stock_id = @stock_pouch);

SET @tier_d34 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'dept_3_4_units' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_d34, @stock_cush_med, 1, 1, 1, 'pcs', 1
WHERE @tier_d34 IS NOT NULL AND @stock_cush_med IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_d34 AND stock_id = @stock_cush_med);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_d34, @stock_pouch, 1, 1, 1, 'pcs', 2
WHERE @tier_d34 IS NOT NULL AND @stock_pouch IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_d34 AND stock_id = @stock_pouch);

SET @tier_c12 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'cacao_1_2_jars' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c12, @stock_foam, 1, 1, 1, 'wrap', 1
WHERE @tier_c12 IS NOT NULL AND @stock_foam IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c12 AND stock_id = @stock_foam);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c12, @stock_cush_std, 1, 1, 1, 'pcs', 2
WHERE @tier_c12 IS NOT NULL AND @stock_cush_std IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c12 AND stock_id = @stock_cush_std);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c12, @stock_pouch, 1, 1, 1, 'pcs', 3
WHERE @tier_c12 IS NOT NULL AND @stock_pouch IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c12 AND stock_id = @stock_pouch);

SET @tier_c34 := (SELECT id FROM parcel_pack_tiers WHERE tier_key = 'cacao_3_4_jars' LIMIT 1);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c34, @stock_foam, 3.5, 3, 4, 'wrap', 1
WHERE @tier_c34 IS NOT NULL AND @stock_foam IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c34 AND stock_id = @stock_foam);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c34, @stock_cush_med, 1, 1, 1, 'pcs', 2
WHERE @tier_c34 IS NOT NULL AND @stock_cush_med IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c34 AND stock_id = @stock_cush_med);
INSERT INTO parcel_pack_tier_lines (tier_id, stock_id, qty_typical, qty_min, qty_max, unit, sort_order)
SELECT @tier_c34, @stock_pouch, 1, 1, 1, 'pcs', 3
WHERE @tier_c34 IS NOT NULL AND @stock_pouch IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM parcel_pack_tier_lines WHERE tier_id = @tier_c34 AND stock_id = @stock_pouch);
