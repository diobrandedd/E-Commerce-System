-- Phase 29: Pool packaging tracks whole rolls/spools (not pcs); fix PT yarn formula qty
-- Run after phase 28.

SET NAMES utf8mb4;

-- Pool SKUs: on_hand is counted in rolls/spools
UPDATE stocks SET unit = 'roll'
WHERE category = 'packaging' AND production_deduct = 0
  AND name IN (
    'Paragis Tea Bag Yarn',
    'PT tea bag paper roll',
    'Honey induction seal',
    'Honey cap seal',
    'Jar Cacao foil'
  );

-- PT yarn/paper: formula qty = tea bags consumed per pouch (cost via yield, not production deduct)
UPDATE product_formula pf
INNER JOIN products p ON p.id = pf.product_id
INNER JOIN stocks s ON s.id = pf.stock_id
SET pf.qty_per_unit = 24.00000000
WHERE p.dept_product_key = 'pt'
  AND s.name IN ('Paragis Tea Bag Yarn', 'PT tea bag paper roll');

-- BRC pouch line if missing
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, pack.id, 1.00000000, pack.current_price
FROM products p
INNER JOIN stocks pack ON pack.dept_id = p.dept_id AND pack.name = 'BRC pouch packaging' AND pack.supplier_id IS NULL
WHERE p.dept_product_key = 'brc'
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ps ON ps.id = f.stock_id
    WHERE f.product_id = p.id AND ps.name = 'BRC pouch packaging'
  );

UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(
    pf.qty_per_unit * CASE WHEN pf.stock_id IS NULL THEN pf.price_at_creation ELSE s.current_price END
  ), 0)
  FROM product_formula pf
  LEFT JOIN stocks s ON s.id = pf.stock_id
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key IN ('pt', 'brc');
