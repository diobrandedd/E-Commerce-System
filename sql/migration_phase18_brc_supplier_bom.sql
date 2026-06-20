-- Phase 18: Black Rice Coffee supplier master (₱/bag) + BOM costing (raw + packaging)
-- Run after phase 17.

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_cacao_brc := (SELECT id FROM departments WHERE name = 'cacao_brc' LIMIT 1);
SET @brc_supplier := (
  SELECT id FROM suppliers WHERE category = 'black_rice' AND is_active = 1 ORDER BY id ASC LIMIT 1
);

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'BRC pouch packaging', @dept_cacao_brc, 'pouch', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'BRC pouch packaging' AND supplier_id IS NULL
  );

INSERT INTO `stocks` (`name`, `dept_id`, `supplier_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT
  'Black Rice Coffee — raw',
  @dept_cacao_brc,
  @brc_supplier,
  'bag',
  0.00,
  IF(@brc_supplier IS NULL, NULL, (
    SELECT CONCAT(s.first_name, ' ', s.last_name) FROM suppliers s WHERE s.id = @brc_supplier LIMIT 1
  )),
  NULL,
  @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks
    WHERE dept_id = @dept_cacao_brc AND name = 'Black Rice Coffee — raw'
      AND ((@brc_supplier IS NULL AND supplier_id IS NULL) OR supplier_id = @brc_supplier)
  );

DELETE se FROM `stock_entries` se
INNER JOIN `stocks` s ON s.id = se.stock_id
WHERE @dept_cacao_brc IS NOT NULL
  AND s.dept_id = @dept_cacao_brc
  AND s.name = 'Black Rice Coffee — unit';

DELETE pf FROM `product_formula` pf
INNER JOIN `products` p ON p.id = pf.product_id
WHERE p.dept_product_key = 'brc';

DELETE s FROM `stocks` s
WHERE @dept_cacao_brc IS NOT NULL
  AND s.dept_id = @dept_cacao_brc
  AND s.name = 'Black Rice Coffee — unit';

-- 100 g per pouch / 20,000 g per bag = 0.005 bag per pouch
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, raw.id, 0.00500000, raw.current_price
FROM products p
INNER JOIN stocks raw ON raw.dept_id = p.dept_id AND raw.name = 'Black Rice Coffee — raw'
WHERE p.dept_product_key = 'brc'
  AND @dept_cacao_brc IS NOT NULL
  AND (
    (@brc_supplier IS NULL AND raw.supplier_id IS NULL)
    OR raw.supplier_id = @brc_supplier
  )
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks rs ON rs.id = f.stock_id
    WHERE f.product_id = p.id AND rs.name = 'Black Rice Coffee — raw'
  )
LIMIT 1;

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, pack.id, 1.00000000, pack.current_price
FROM products p
INNER JOIN stocks pack ON pack.dept_id = p.dept_id AND pack.name = 'BRC pouch packaging' AND pack.supplier_id IS NULL
WHERE p.dept_product_key = 'brc'
  AND @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ps ON ps.id = f.stock_id
    WHERE f.product_id = p.id AND ps.name = 'BRC pouch packaging'
  );

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * pf.price_at_creation), 0)
  FROM product_formula pf
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key = 'brc';
