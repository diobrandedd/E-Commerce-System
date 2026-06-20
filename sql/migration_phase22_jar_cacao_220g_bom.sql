-- Phase 22: Jar Cacao BOM — 1 kg bag → ~4.55 jars @ 220 g (not 1 jar @ 1 kg)

UPDATE product_formula pf
INNER JOIN stocks s ON s.id = pf.stock_id
INNER JOIN products p ON p.id = pf.product_id
SET pf.qty_per_unit = 0.22000000,
    pf.price_at_creation = s.current_price
WHERE p.dept_product_key = 'jar_cacao'
  AND s.name LIKE 'Jar Cacao%raw'
  AND s.supplier_id IS NOT NULL;

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * s.current_price), 0)
  FROM product_formula pf
  INNER JOIN stocks s ON s.id = pf.stock_id
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key = 'jar_cacao';
