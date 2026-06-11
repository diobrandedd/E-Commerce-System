-- Phase 23: Black Rice Coffee BOM — 1 kg bag → 10 pouches @ 100 g (not 20 kg → 200)

UPDATE product_formula pf
INNER JOIN stocks s ON s.id = pf.stock_id
INNER JOIN products p ON p.id = pf.product_id
SET pf.qty_per_unit = 0.10000000,
    pf.price_at_creation = s.current_price
WHERE p.dept_product_key = 'brc'
  AND s.name LIKE 'Black Rice Coffee%raw'
  AND s.supplier_id IS NOT NULL;

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * s.current_price), 0)
  FROM product_formula pf
  INNER JOIN stocks s ON s.id = pf.stock_id
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key = 'brc';
