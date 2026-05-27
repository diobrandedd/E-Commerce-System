-- Phase 15: Higher precision for costing qty and honey-derived stock prices
-- Fixes gallon-bottle line cost drift (e.g. ₱500/gal → 499.99 when qty/price were rounded).

SET NAMES utf8mb4;

ALTER TABLE `stocks`
  MODIFY COLUMN `current_price` DECIMAL(14,6) NOT NULL DEFAULT 0.000000;

ALTER TABLE `product_formula`
  MODIFY COLUMN `qty_per_unit` DECIMAL(16,8) NOT NULL;

ALTER TABLE `stock_price_history`
  MODIFY COLUMN `old_price` DECIMAL(14,6) NOT NULL,
  MODIFY COLUMN `new_price` DECIMAL(14,6) NOT NULL;

ALTER TABLE `product_cost_history`
  MODIFY COLUMN `old_cost` DECIMAL(14,6) NOT NULL,
  MODIFY COLUMN `new_cost` DECIMAL(14,6) NOT NULL;

-- Fix gallon-bottle honey lines that were truncated to DECIMAL(12,4)
UPDATE `product_formula` pf
INNER JOIN `products` p ON p.id = pf.product_id
INNER JOIN `stocks` s ON s.id = pf.stock_id
SET pf.qty_per_unit = 3.785411784
WHERE LOWER(TRIM(p.size)) = 'gallon'
  AND s.name REGEXP '^Raw Honey — (Light|Amber|Dark)$';

-- Re-derive per-liter honey stock prices from latest supplier ₱/gallon (6 dp)
UPDATE `stocks` s
INNER JOIN (
  SELECT shp.supplier_id, shp.variant, shp.price_per_gallon
  FROM supplier_honey_prices shp
  INNER JOIN (
    SELECT supplier_id, variant, MAX(id) AS max_id
    FROM supplier_honey_prices
    GROUP BY supplier_id, variant
  ) latest ON latest.max_id = shp.id
) pr ON pr.supplier_id = s.supplier_id
  AND s.name = CONCAT('Raw Honey — ', CONCAT(UPPER(LEFT(pr.variant, 1)), SUBSTRING(pr.variant, 2)))
SET s.current_price = ROUND(pr.price_per_gallon / 3.785411784, 6)
WHERE s.supplier_id IS NOT NULL
  AND s.name REGEXP '^Raw Honey — (Light|Amber|Dark)$';
