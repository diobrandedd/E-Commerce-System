-- Phase 41: Generic insulation foam warehouse roll; sized SKUs for recipe yield only
-- Cut size (9x12 / 12.5x12) applied at parcel ship for cacao recipes — not at issue.
-- Run after phase 38/40. Then: php tools/memory_bank/generate_all.php

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

-- Generic warehouse insulation foam roll
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Insulation foam roll', @dept_fulfillment, 'roll', 'packaging', 0.00, 0.00, 0, @bootstrap_user_id
WHERE @dept_fulfillment IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE supplier_id IS NULL AND LOWER(TRIM(name)) = 'insulation foam roll'
  );

SET @foam_generic_id := (
  SELECT id FROM stocks
  WHERE supplier_id IS NULL AND LOWER(TRIM(name)) = 'insulation foam roll'
  ORDER BY id ASC LIMIT 1
);

SET @foam_sized_sum := (
  SELECT COALESCE(SUM(on_hand), 0)
  FROM stocks
  WHERE supplier_id IS NULL
    AND name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12')
);

UPDATE stocks
SET on_hand = ROUND(on_hand + @foam_sized_sum, 4)
WHERE id = @foam_generic_id AND @foam_generic_id IS NOT NULL AND @foam_sized_sum > 0;

UPDATE stocks
SET on_hand = 0
WHERE supplier_id IS NULL
  AND name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12');

-- Merge sized staging balances into generic foam staging
SET @has_fpb := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fulfillment_packing_balances'
);

SET @has_damaged := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fulfillment_packing_balances'
    AND COLUMN_NAME = 'damaged_qty'
);

SET @merge_foam_sql := IF(
  @has_fpb > 0 AND @foam_generic_id IS NOT NULL AND @has_damaged > 0,
  CONCAT(
    'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty, damaged_qty) ',
    'SELECT ', @foam_generic_id, ', COALESCE(SUM(b.issued_qty), 0), COALESCE(SUM(b.consumed_qty), 0), COALESCE(SUM(b.damaged_qty), 0) ',
    'FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id ',
    "WHERE s.supplier_id IS NULL AND s.name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12') ",
    'ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty), ',
    'consumed_qty = consumed_qty + VALUES(consumed_qty), damaged_qty = damaged_qty + VALUES(damaged_qty)'
  ),
  IF(
    @has_fpb > 0 AND @foam_generic_id IS NOT NULL,
    CONCAT(
      'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty) ',
      'SELECT ', @foam_generic_id, ', COALESCE(SUM(b.issued_qty), 0), COALESCE(SUM(b.consumed_qty), 0) ',
      'FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id ',
      "WHERE s.supplier_id IS NULL AND s.name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12') ",
      'ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty), consumed_qty = consumed_qty + VALUES(consumed_qty)'
    ),
    'SELECT 1'
  )
);
PREPARE st_foam FROM @merge_foam_sql;
EXECUTE st_foam;
DEALLOCATE PREPARE st_foam;

SET @cleanup_foam_bal_sql := IF(
  @has_fpb > 0,
  "DELETE b FROM fulfillment_packing_balances b JOIN stocks s ON s.id = b.stock_id
   WHERE s.supplier_id IS NULL
     AND s.name IN ('Insulation foam roll - 9x12', 'Insulation foam roll - 12.5x12')",
  'SELECT 1'
);
PREPARE st_foam_clean FROM @cleanup_foam_bal_sql;
EXECUTE st_foam_clean;
DEALLOCATE PREPARE st_foam_clean;
