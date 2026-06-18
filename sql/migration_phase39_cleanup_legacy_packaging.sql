-- Phase 39: Legacy packaging SKU cleanup (not table drops — all tables are in use)
-- Run after phase 38. Then: php tools/sync_all_costing_from_prices.php

SET NAMES utf8mb4;

-- Generic warehouse bubble: no yield (width/yield live on sized staging SKUs only)
UPDATE stocks
SET pool_yield_qty = NULL, pool_yield_unit = NULL
WHERE supplier_id IS NULL
  AND LOWER(TRIM(name)) = 'bubble wrap roll';

-- Sized bubble: staging/recipe only — never warehouse price or on_hand
UPDATE stocks
SET on_hand = 0, current_price = 0.000000
WHERE supplier_id IS NULL
  AND name IN (
    'Bubble wrap roll - 13 cm',
    'Bubble wrap roll - 17 cm',
    'Bubble wrap roll - 22 cm'
  );

-- Phase-30 generic parcel SKUs (superseded by fulfillment spreadsheet SKUs)
UPDATE stocks
SET is_active = 0
WHERE supplier_id IS NULL
  AND name IN (
    'Parcel bubble wrap',
    'Parcel cushion - standard',
    'Parcel cushion - medium',
    'Parcel cushion - large',
    'Parcel cushion - small separator',
    'Courier pouch bag',
    'Parcel insulation foam wrap'
  )
  AND is_active = 1;

-- Legacy column roll SKUs (merged into main/divider) — keep deactivated.
-- Custom liter cushions stay active (purchased pieces); half-LAD uses Cushion piece - LAD @ 0.5 pcs.
UPDATE stocks
SET is_active = 0
WHERE supplier_id IS NULL
  AND name IN (
    'Cushion piece - half LAD',
    'Cushion column roll - 25',
    'Cushion column roll - 16 LAD',
    'Cushion column roll - 35 GAL',
    'Cushion column roll - 40 GAL',
    'Cushion column roll - 60 LAD'
  )
  AND is_active = 1;

-- Legacy honey bottle placeholders (replaced by Honey bottle w/ cap - {size})
UPDATE stocks
SET is_active = 0
WHERE supplier_id IS NULL
  AND name LIKE 'Honey bottle w/ cap ???%'
  AND is_active = 1;

-- Remove parcel tier lines that still point at deactivated stocks (if any)
DELETE l FROM parcel_pack_tier_lines l
INNER JOIN stocks s ON s.id = l.stock_id
WHERE s.is_active = 0;
