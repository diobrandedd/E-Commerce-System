-- Phase 37: Cushion roll consolidation + foam roll conversion.
-- - Keep LAD cushion as piece SKU (0.5 for half-LAD use)
-- - Consolidate legacy cushion column roll variants into two SKUs
-- - Convert insulation foam sheet SKUs into roll SKUs with foam_piece yield unit
-- Run once, then: php tools/seed_fulfillment_pack_tiers.php

SET NAMES utf8mb4;

SET @dept_fulfillment := (
  SELECT id FROM departments WHERE name = 'fulfillment' LIMIT 1
);
SET @dept_fulfillment := COALESCE(
  @dept_fulfillment,
  (
    SELECT dept_id FROM stocks
    WHERE supplier_id IS NULL
      AND name IN (
        'Cushion column roll - 25',
        'Cushion column roll - 35 GAL',
        'Cushion column roll - 40 GAL',
        'Cushion column roll - 16 LAD',
        'Cushion column roll - 60 LAD'
      )
    LIMIT 1
  ),
  (
    SELECT dept_id FROM stocks
    WHERE supplier_id IS NULL AND category = 'packaging'
    ORDER BY id ASC LIMIT 1
  )
);
SET @bootstrap_user_id := (
  SELECT id FROM users ORDER BY id ASC LIMIT 1
);

INSERT INTO `stocks`
  (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion roll - main', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 100.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE supplier_id IS NULL AND name = 'Cushion roll - main');

INSERT INTO `stocks`
  (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Cushion roll - divider', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, 50.0000, 'column', @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE supplier_id IS NULL AND name = 'Cushion roll - divider');

SET @main_new_id := (
  SELECT id FROM stocks WHERE supplier_id IS NULL AND name = 'Cushion roll - main' LIMIT 1
);
SET @divider_new_id := (
  SELECT id FROM stocks WHERE supplier_id IS NULL AND name = 'Cushion roll - divider' LIMIT 1
);

-- Merge legacy roll on_hand into new consolidated rows.
UPDATE stocks s
SET s.on_hand = ROUND(
  s.on_hand + COALESCE((
    SELECT SUM(old.on_hand)
    FROM stocks old
    WHERE old.supplier_id IS NULL
      AND old.name IN ('Cushion column roll - 25', 'Cushion column roll - 35 GAL', 'Cushion column roll - 40 GAL')
  ), 0),
  4
)
WHERE s.id = @main_new_id AND @main_new_id IS NOT NULL;

UPDATE stocks s
SET s.on_hand = ROUND(
  s.on_hand + COALESCE((
    SELECT SUM(old.on_hand)
    FROM stocks old
    WHERE old.supplier_id IS NULL
      AND old.name IN ('Cushion column roll - 16 LAD', 'Cushion column roll - 60 LAD')
  ), 0),
  4
)
WHERE s.id = @divider_new_id AND @divider_new_id IS NOT NULL;

UPDATE stocks
SET on_hand = 0,
    is_active = 0
WHERE supplier_id IS NULL
  AND name IN (
    'Cushion column roll - 25',
    'Cushion column roll - 35 GAL',
    'Cushion column roll - 40 GAL',
    'Cushion column roll - 16 LAD',
    'Cushion column roll - 60 LAD'
  );

-- Keep LAD piece active; retire only legacy liter cushion pieces.
UPDATE stocks
SET is_active = 0,
    on_hand = 0
WHERE supplier_id IS NULL
  AND name IN ('Cushion piece - 1 liter', 'Cushion piece - 2 liters', 'Cushion piece - 3 liters', 'Cushion piece - half LAD');

UPDATE stocks
SET is_active = 1
WHERE supplier_id IS NULL
  AND name = 'Cushion piece - LAD';

-- Convert foam sheet SKUs to roll SKUs.
UPDATE stocks
SET name = 'Insulation foam roll - 9x12'
WHERE supplier_id IS NULL AND name = 'Insulation foam sheet - 9x12';

UPDATE stocks
SET name = 'Insulation foam roll - 12.5x12'
WHERE supplier_id IS NULL AND name = 'Insulation foam sheet - 12.5x12';

UPDATE stocks
SET unit = 'roll',
    production_deduct = 0,
    pool_yield_unit = 'foam_piece',
    pool_yield_qty = NULL
WHERE supplier_id IS NULL
  AND name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12');

-- Merge old staging balances (phase 34+) into consolidated cushion rows.
SET @has_fpb := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fulfillment_packing_balances'
);

SET @merge_main_sql := IF(
  @has_fpb > 0 AND @main_new_id IS NOT NULL,
  CONCAT(
    'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty) ',
    'SELECT ', @main_new_id, ', COALESCE(SUM(b.issued_qty), 0), COALESCE(SUM(b.consumed_qty), 0) ',
    'FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id ',
    "WHERE s.supplier_id IS NULL AND s.name IN ('Cushion column roll - 25', 'Cushion column roll - 35 GAL', 'Cushion column roll - 40 GAL') ",
    'ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty), consumed_qty = consumed_qty + VALUES(consumed_qty)'
  ),
  'SELECT 1'
);
PREPARE st_main FROM @merge_main_sql;
EXECUTE st_main;
DEALLOCATE PREPARE st_main;

SET @merge_div_sql := IF(
  @has_fpb > 0 AND @divider_new_id IS NOT NULL,
  CONCAT(
    'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty) ',
    'SELECT ', @divider_new_id, ', COALESCE(SUM(b.issued_qty), 0), COALESCE(SUM(b.consumed_qty), 0) ',
    'FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id ',
    "WHERE s.supplier_id IS NULL AND s.name IN ('Cushion column roll - 16 LAD', 'Cushion column roll - 60 LAD') ",
    'ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty), consumed_qty = consumed_qty + VALUES(consumed_qty)'
  ),
  'SELECT 1'
);
PREPARE st_div FROM @merge_div_sql;
EXECUTE st_div;
DEALLOCATE PREPARE st_div;

SET @cleanup_bal_sql := IF(
  @has_fpb > 0,
  "DELETE b FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id
   WHERE s.supplier_id IS NULL
     AND s.name IN ('Cushion column roll - 25', 'Cushion column roll - 35 GAL', 'Cushion column roll - 40 GAL', 'Cushion column roll - 16 LAD', 'Cushion column roll - 60 LAD')",
  'SELECT 1'
);
PREPARE st_clean FROM @cleanup_bal_sql;
EXECUTE st_clean;
DEALLOCATE PREPARE st_clean;
