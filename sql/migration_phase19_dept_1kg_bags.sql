-- Phase 19: PT + Jar Cacao supplier bag = 1 kg (not 5 kg / placeholder)
-- Run after phase 18.

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
SET @cacao_supplier := (
  SELECT id FROM suppliers WHERE category = 'cacao' AND is_active = 1 ORDER BY id ASC LIMIT 1
);

-- PT leaf formula: 24 tea bags / (1000g / 2g) = 24/500 = 0.048 bag per pouch
UPDATE product_formula pf
INNER JOIN products p ON p.id = pf.product_id
INNER JOIN stocks s ON s.id = pf.stock_id
SET pf.qty_per_unit = 0.04800000
WHERE p.dept_product_key = 'pt'
  AND s.name = 'Paragis leaf — raw';

-- Jar Cacao: 1 kg bag → 1 sellable jar (pcs)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Jar Cacao jar packaging', @dept_cacao_brc, 'pcs', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao jar packaging' AND supplier_id IS NULL
  );

INSERT INTO `stocks` (`name`, `dept_id`, `supplier_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT
  'Jar Cacao — raw',
  @dept_cacao_brc,
  @cacao_supplier,
  'bag',
  0.00,
  IF(@cacao_supplier IS NULL, NULL, (
    SELECT CONCAT(s.first_name, ' ', s.last_name) FROM suppliers s WHERE s.id = @cacao_supplier LIMIT 1
  )),
  NULL,
  @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks
    WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao — raw'
      AND ((@cacao_supplier IS NULL AND supplier_id IS NULL) OR supplier_id = @cacao_supplier)
  );

DELETE se FROM `stock_entries` se
INNER JOIN `stocks` s ON s.id = se.stock_id
WHERE @dept_cacao_brc IS NOT NULL
  AND s.dept_id = @dept_cacao_brc
  AND s.name = 'Jar Cacao — unit';

DELETE pf FROM `product_formula` pf
INNER JOIN `products` p ON p.id = pf.product_id
WHERE p.dept_product_key = 'jar_cacao';

DELETE s FROM `stocks` s
WHERE @dept_cacao_brc IS NOT NULL
  AND s.dept_id = @dept_cacao_brc
  AND s.name = 'Jar Cacao — unit';

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, raw.id, 1.00000000, raw.current_price
FROM products p
INNER JOIN stocks raw ON raw.dept_id = p.dept_id AND raw.name = 'Jar Cacao — raw'
WHERE p.dept_product_key = 'jar_cacao'
  AND @dept_cacao_brc IS NOT NULL
  AND (
    (@cacao_supplier IS NULL AND raw.supplier_id IS NULL)
    OR raw.supplier_id = @cacao_supplier
  )
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks rs ON rs.id = f.stock_id
    WHERE f.product_id = p.id AND rs.name = 'Jar Cacao — raw'
  )
LIMIT 1;

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, pack.id, 1.00000000, pack.current_price
FROM products p
INNER JOIN stocks pack ON pack.dept_id = p.dept_id AND pack.name = 'Jar Cacao jar packaging' AND pack.supplier_id IS NULL
WHERE p.dept_product_key = 'jar_cacao'
  AND @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ps ON ps.id = f.stock_id
    WHERE f.product_id = p.id AND ps.name = 'Jar Cacao jar packaging'
  );

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * pf.price_at_creation), 0)
  FROM product_formula pf
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key IN ('pt', 'jar_cacao');
