-- Phase 27: Packaging bootstrap — production_deduct, pool_yield_qty, full BOM + honey products
-- Run after phase 26.

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_paragis := (SELECT id FROM departments WHERE name = 'paragis' LIMIT 1);
SET @dept_cacao_brc := (SELECT id FROM departments WHERE name = 'cacao_brc' LIMIT 1);
SET @dept_honey := (
  SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1
);

-- Schema: production_deduct + pool yield
SET @col_pd := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'production_deduct'
);
SET @sql_pd := IF(
  @col_pd = 0,
  'ALTER TABLE `stocks` ADD COLUMN `production_deduct` TINYINT(1) NOT NULL DEFAULT 1 AFTER `category`',
  'SELECT 1'
);
PREPARE stmt_pd FROM @sql_pd;
EXECUTE stmt_pd;
DEALLOCATE PREPARE stmt_pd;

SET @col_py := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'pool_yield_qty'
);
SET @sql_py := IF(
  @col_py = 0,
  'ALTER TABLE `stocks`
     ADD COLUMN `pool_yield_qty` DECIMAL(14,4) NULL DEFAULT NULL AFTER `production_deduct`,
     ADD COLUMN `pool_yield_unit` VARCHAR(32) NULL DEFAULT NULL AFTER `pool_yield_qty`',
  'SELECT 1'
);
PREPARE stmt_py FROM @sql_py;
EXECUTE stmt_py;
DEALLOCATE PREPARE stmt_py;

-- Ensure packaging category on dept packaging rows
UPDATE stocks SET category = 'packaging'
WHERE category IS NULL OR category = 'costing_only'
  AND name IN (
    'PT pouch packaging', 'BRC pouch packaging', 'Jar Cacao jar packaging',
    'Honey Bottles', 'Bottle Caps', 'Labels', 'Boxes', 'PT tea bag sachet'
  );

-- Paragis pool + existing pouch
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'PT tea bag paper roll', @dept_paragis, 'roll', 'packaging', 0.00, 0.00, 0, 10000.0000, 'tea_bag', @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_paragis AND name = 'PT tea bag paper roll' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Paragis Tea Bag Yarn', @dept_paragis, 'roll', 'packaging', 0.00, 0.00, 0, 10000.0000, 'tea_bag', @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_paragis AND name = 'Paragis Tea Bag Yarn' AND supplier_id IS NULL);

UPDATE stocks SET production_deduct = 0, pool_yield_qty = 10000.0000, pool_yield_unit = 'tea_bag', category = 'packaging', unit = 'roll'
WHERE dept_id = @dept_paragis AND name IN ('Paragis Tea Bag Yarn', 'PT tea bag paper roll') AND supplier_id IS NULL;

UPDATE stocks SET production_deduct = 1, pool_yield_qty = NULL, pool_yield_unit = NULL, category = 'packaging'
WHERE dept_id = @dept_paragis AND name = 'PT pouch packaging' AND supplier_id IS NULL;

-- Jar packaging
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Jar Cacao sticker', @dept_cacao_brc, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao sticker' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Jar Cacao foil', @dept_cacao_brc, 'roll', 'packaging', 0.00, 0.00, 0, 8000.0000, 'jar', @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao foil' AND supplier_id IS NULL);

UPDATE stocks SET production_deduct = 1, pool_yield_qty = NULL, pool_yield_unit = NULL
WHERE dept_id = @dept_cacao_brc AND name IN ('Jar Cacao jar packaging', 'Jar Cacao sticker') AND supplier_id IS NULL;

UPDATE stocks SET production_deduct = 0, pool_yield_qty = 8000.0000, pool_yield_unit = 'jar', category = 'packaging'
WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao foil' AND supplier_id IS NULL;

-- Honey packaging (size-specific bottles + shared consumables)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey bottle w/ cap - 250ml', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey bottle w/ cap - 250ml' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey bottle w/ cap - 350ml', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey bottle w/ cap - 350ml' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey bottle w/ cap - 1L', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey bottle w/ cap - 1L' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey bottle w/ cap - gallon', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey bottle w/ cap - gallon' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey sticker', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey sticker' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Honey induction seal', @dept_honey, 'roll', 'packaging', 0.00, 0.00, 0, 10000.0000, 'bottle', @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey induction seal' AND supplier_id IS NULL);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `pool_yield_qty`, `pool_yield_unit`, `created_by`)
SELECT 'Honey cap seal', @dept_honey, 'roll', 'packaging', 0.00, 0.00, 0, 10000.0000, 'bottle', @bootstrap_user_id
WHERE @dept_honey IS NOT NULL AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey cap seal' AND supplier_id IS NULL);

UPDATE stocks SET production_deduct = 0, pool_yield_qty = 10000.0000, pool_yield_unit = 'bottle', category = 'packaging'
WHERE dept_id = @dept_honey AND name IN ('Honey induction seal', 'Honey cap seal') AND supplier_id IS NULL;

UPDATE stocks SET production_deduct = 1, pool_yield_qty = NULL, pool_yield_unit = NULL, category = 'packaging'
WHERE dept_id = @dept_honey AND name LIKE 'Honey bottle w/ cap -%' AND supplier_id IS NULL;

UPDATE stocks SET production_deduct = 1, category = 'packaging'
WHERE dept_id = @dept_honey AND name = 'Honey sticker' AND supplier_id IS NULL;

-- Deprecate generic honey packaging (inactive; formulas use size-specific SKUs)
UPDATE stocks SET is_active = 0, production_deduct = 1
WHERE dept_id = @dept_honey AND name IN ('Honey Bottles', 'Bottle Caps', 'Labels', 'Boxes') AND supplier_id IS NULL;

-- Deprecate discrete sachet SKU (paper roll is canonical pool line)
UPDATE stocks SET is_active = 0, production_deduct = 1
WHERE dept_id = @dept_paragis AND name = 'PT tea bag sachet' AND supplier_id IS NULL;

-- Remove PT tea bag sachet from formula (paper roll is canonical pool line)
DELETE pf FROM product_formula pf
INNER JOIN products p ON p.id = pf.product_id
INNER JOIN stocks s ON s.id = pf.stock_id
WHERE p.dept_product_key = 'pt' AND s.name = 'PT tea bag sachet';

-- PT full formula
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 24.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Paragis Tea Bag Yarn' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'pt' AND @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Paragis Tea Bag Yarn'
  );

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 24.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'PT tea bag paper roll' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'pt' AND @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'PT tea bag paper roll'
  );

-- Jar full formula
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Jar Cacao sticker' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'jar_cacao' AND @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Jar Cacao sticker'
  );

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Jar Cacao foil' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'jar_cacao' AND @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Jar Cacao foil'
  );

-- Honey products (12 SKUs) + formulas
INSERT INTO `products` (`name`, `dept_id`, `size`, `variant`, `selling_price`, `baseline_cost`, `created_by`)
SELECT 'Honey', @dept_honey, sz.size_key, sz.variant_key, 0.00, 0.00, @bootstrap_user_id
FROM (
  SELECT '250ml' AS size_key, 'light' AS variant_key UNION ALL
  SELECT '250ml', 'amber' UNION ALL SELECT '250ml', 'dark' UNION ALL
  SELECT '350ml', 'light' UNION ALL SELECT '350ml', 'amber' UNION ALL SELECT '350ml', 'dark' UNION ALL
  SELECT 'liter', 'light' UNION ALL SELECT 'liter', 'amber' UNION ALL SELECT 'liter', 'dark' UNION ALL
  SELECT 'gallon', 'light' UNION ALL SELECT 'gallon', 'amber' UNION ALL SELECT 'gallon', 'dark'
) sz
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM products hp
    WHERE hp.dept_id = @dept_honey AND hp.size = sz.size_key AND hp.variant = sz.variant_key
      AND hp.dept_product_key IS NULL
  );

-- Honey raw formula lines (liters by size; one stock row per variant)
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, rh.pick_id,
  CASE p.size
    WHEN '250ml' THEN 0.25000000
    WHEN '350ml' THEN 0.35000000
    WHEN 'liter' THEN 1.00000000
    WHEN 'gallon' THEN 3.78541178
    ELSE 0
  END,
  rh.pick_price
FROM products p
INNER JOIN (
  SELECT dept_id, name, MIN(id) AS pick_id, MIN(current_price) AS pick_price
  FROM stocks
  WHERE name IN ('Raw Honey — Light', 'Raw Honey — Amber', 'Raw Honey — Dark')
  GROUP BY dept_id, name
) rh ON rh.dept_id = p.dept_id
  AND rh.name = CONCAT('Raw Honey — ', CONCAT(UPPER(LEFT(p.variant, 1)), SUBSTRING(p.variant, 2)))
WHERE p.dept_id = @dept_honey AND p.variant IN ('light', 'amber', 'dark')
  AND p.size IN ('250ml', '350ml', 'liter', 'gallon')
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f WHERE f.product_id = p.id AND f.stock_id = rh.pick_id
  );

-- Honey bottle (size-specific)
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, bt.id, 1.00000000, bt.current_price
FROM products p
INNER JOIN stocks bt ON bt.dept_id = p.dept_id AND bt.supplier_id IS NULL
  AND bt.name = CASE p.size
    WHEN '250ml' THEN 'Honey bottle w/ cap - 250ml'
    WHEN '350ml' THEN 'Honey bottle w/ cap - 350ml'
    WHEN 'liter' THEN 'Honey bottle w/ cap - 1L'
    WHEN 'gallon' THEN 'Honey bottle w/ cap - gallon'
  END
WHERE p.dept_id = @dept_honey AND p.size IN ('250ml', '350ml', 'liter', 'gallon')
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f WHERE f.product_id = p.id AND f.stock_id = bt.id
  );

-- Honey sticker + seals
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.supplier_id IS NULL
  AND s.name IN ('Honey sticker', 'Honey induction seal', 'Honey cap seal')
WHERE p.dept_id = @dept_honey AND p.variant IN ('light', 'amber', 'dark')
  AND p.size IN ('250ml', '350ml', 'liter', 'gallon')
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f WHERE f.product_id = p.id AND f.stock_id = s.id
  );

-- Recalc baseline_cost (simple sum; live current_cost uses pool yield in app SQL)
UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(
    pf.qty_per_unit * CASE WHEN pf.stock_id IS NULL THEN pf.price_at_creation ELSE s.current_price END
  ), 0)
  FROM product_formula pf
  LEFT JOIN stocks s ON s.id = pf.stock_id
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key IN ('pt', 'brc', 'jar_cacao')
   OR (p.dept_id = @dept_honey AND p.variant IN ('light', 'amber', 'dark'));
