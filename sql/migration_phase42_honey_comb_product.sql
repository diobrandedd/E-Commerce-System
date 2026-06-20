-- Phase 42: Honey Comb — production BOM (raw + jar w/ cap + sticker), dept product, finished inventory
-- Parcel/fulfillment tier honey_comb_solo (Excel row 13) unchanged — ship-time only.
-- Run after phase 41.

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_honey := (
  SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1
);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey comb jar packaging', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey comb jar packaging' AND supplier_id IS NULL
  );

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey comb sticker', @dept_honey, 'pcs', 'packaging', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey comb sticker' AND supplier_id IS NULL
  );

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `production_deduct`, `created_by`)
SELECT 'Honey comb — raw', @dept_honey, 'bag', 'raw_material', 0.00, 0.00, 1, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_honey AND name = 'Honey comb — raw' AND supplier_id IS NULL
  );

INSERT INTO `products` (`name`, `dept_id`, `dept_product_key`, `size`, `variant`, `selling_price`, `baseline_cost`, `created_by`)
SELECT 'Honey Comb', @dept_honey, 'honey_comb', NULL, NULL, 0.00, 0.00, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM products WHERE dept_product_key = 'honey_comb'
  );

-- 1 kg bag → 500 g per jar (2 jars/bag). Adjust qty if ops yield differs.
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 0.50000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Honey comb — raw' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'honey_comb' AND @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Honey comb — raw'
  );

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Honey comb jar packaging' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'honey_comb' AND @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Honey comb jar packaging'
  );

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Honey comb sticker' AND s.supplier_id IS NULL
WHERE p.dept_product_key = 'honey_comb' AND @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'Honey comb sticker'
  );

INSERT INTO `dept_inventory_rows` (`dept_id`, `product_key`, `product_label`, `current_qty`, `initial_qty`, `created_by`)
SELECT @dept_honey, 'honey_comb', 'Honey Comb', 0, 0, @bootstrap_user_id
WHERE @dept_honey IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM dept_inventory_rows WHERE dept_id = @dept_honey AND product_key = 'honey_comb'
  );

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * s.current_price), 0)
  FROM product_formula pf
  INNER JOIN stocks s ON s.id = pf.stock_id
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key = 'honey_comb';
