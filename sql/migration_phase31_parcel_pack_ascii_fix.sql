-- Phase 31: Fix parcel pack labels corrupted when phase 30 ran without UTF-8 (Windows pipe).
-- Uses ASCII-only punctuation so tier/stock names survive any import path.

SET NAMES utf8mb4;

-- Tier display labels (ASCII)
UPDATE parcel_pack_tiers SET label = 'Honey - 1x 1L' WHERE tier_key = 'honey_1x_1L';
UPDATE parcel_pack_tiers SET label = 'Honey - 2x 1L' WHERE tier_key = 'honey_2x_1L';
UPDATE parcel_pack_tiers SET label = 'Honey - 3x 1L' WHERE tier_key = 'honey_3x_1L';
UPDATE parcel_pack_tiers SET label = 'Honey - 250/350 solo (TBD)' WHERE tier_key = 'honey_250_350_solo';
UPDATE parcel_pack_tiers SET label = 'PT/BRC - 1-2 units' WHERE tier_key = 'dept_1_2_units';
UPDATE parcel_pack_tiers SET label = 'PT/BRC - 3-4 units' WHERE tier_key = 'dept_3_4_units';
UPDATE parcel_pack_tiers SET label = 'Cacao - 1-2 jars' WHERE tier_key = 'cacao_1_2_jars';
UPDATE parcel_pack_tiers SET label = 'Cacao - 3-4 jars' WHERE tier_key = 'cacao_3_4_jars';

-- Canonical fulfillment stock names (ASCII)
UPDATE stocks SET name = 'Parcel bubble wrap'
WHERE name LIKE 'Parcel bubble wrap%' AND supplier_id IS NULL;

UPDATE stocks SET name = 'Courier pouch bag'
WHERE name LIKE 'Courier pouch bag%' AND supplier_id IS NULL;

UPDATE stocks SET name = 'Parcel insulation foam wrap'
WHERE name LIKE 'Parcel insulation foam wrap%' AND supplier_id IS NULL;

SET @cush_std := (
  SELECT MIN(id) FROM stocks
  WHERE supplier_id IS NULL AND name LIKE 'Parcel cushion%standard%'
);
SET @cush_med := (
  SELECT MIN(id) FROM stocks
  WHERE supplier_id IS NULL AND name LIKE 'Parcel cushion%medium%'
);
SET @cush_lg := (
  SELECT MIN(id) FROM stocks
  WHERE supplier_id IS NULL AND name LIKE 'Parcel cushion%large%'
);
SET @cush_sep := (
  SELECT MIN(id) FROM stocks
  WHERE supplier_id IS NULL AND name LIKE 'Parcel cushion%small separator%'
);

UPDATE stocks SET name = 'Parcel cushion - standard' WHERE id = @cush_std;
UPDATE stocks SET name = 'Parcel cushion - medium' WHERE id = @cush_med;
UPDATE stocks SET name = 'Parcel cushion - large' WHERE id = @cush_lg;
UPDATE stocks SET name = 'Parcel cushion - small separator' WHERE id = @cush_sep;

UPDATE parcel_pack_tier_lines l
INNER JOIN stocks s ON s.id = l.stock_id
SET l.stock_id = @cush_std
WHERE s.supplier_id IS NULL AND s.name LIKE 'Parcel cushion%standard%' AND s.id <> @cush_std;

UPDATE parcel_pack_tier_lines l
INNER JOIN stocks s ON s.id = l.stock_id
SET l.stock_id = @cush_med
WHERE s.supplier_id IS NULL AND s.name LIKE 'Parcel cushion%medium%' AND s.id <> @cush_med;

UPDATE parcel_pack_tier_lines l
INNER JOIN stocks s ON s.id = l.stock_id
SET l.stock_id = @cush_lg
WHERE s.supplier_id IS NULL AND s.name LIKE 'Parcel cushion%large%' AND s.id <> @cush_lg;

UPDATE parcel_pack_tier_lines l
INNER JOIN stocks s ON s.id = l.stock_id
SET l.stock_id = @cush_sep
WHERE s.supplier_id IS NULL AND s.name LIKE 'Parcel cushion%small separator%' AND s.id <> @cush_sep;

-- Remove duplicate tier lines (same tier + sort_order from double migration run)
DELETE l1 FROM parcel_pack_tier_lines l1
INNER JOIN parcel_pack_tier_lines l2
  ON l1.tier_id = l2.tier_id AND l1.sort_order = l2.sort_order AND l1.id > l2.id;

-- Drop orphan duplicate cushion stocks
DELETE s FROM stocks s
WHERE s.supplier_id IS NULL
  AND s.name LIKE 'Parcel cushion%'
  AND s.id NOT IN (@cush_std, @cush_med, @cush_lg, @cush_sep);
