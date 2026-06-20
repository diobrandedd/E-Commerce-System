-- Phase 28: Deduplicate honey bottle packaging SKUs (encoding / double-migration cleanup)
-- Run after phase 27. Canonical names use ASCII hyphen: "Honey bottle w/ cap - {size}"

SET NAMES utf8mb4;

SET @dept_honey := (
  SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1
);

-- Remove duplicate formula lines (keep one bottle line per product; prefer non-??? stock)
DELETE pf FROM product_formula pf
INNER JOIN stocks s ON s.id = pf.stock_id
WHERE s.dept_id = @dept_honey
  AND s.name LIKE 'Honey bottle w/ cap%'
  AND s.name LIKE '%???%';

-- Deactivate corrupted ??? rows
UPDATE stocks SET is_active = 0
WHERE dept_id = @dept_honey
  AND name LIKE 'Honey bottle w/ cap%'
  AND name LIKE '%???%';

-- Rename surviving rows to canonical ASCII names (handles em-dash / mojibake variants)
UPDATE stocks SET name = 'Honey bottle w/ cap - 250ml'
WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
  AND name LIKE 'Honey bottle w/ cap%250ml%'
  AND name NOT LIKE '%???%';

UPDATE stocks SET name = 'Honey bottle w/ cap - 350ml'
WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
  AND name LIKE 'Honey bottle w/ cap%350ml%'
  AND name NOT LIKE '%???%';

UPDATE stocks SET name = 'Honey bottle w/ cap - 1L'
WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
  AND name LIKE 'Honey bottle w/ cap%1L%'
  AND name NOT LIKE '%???%';

UPDATE stocks SET name = 'Honey bottle w/ cap - gallon'
WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
  AND name LIKE 'Honey bottle w/ cap%gallon%'
  AND name NOT LIKE '%???%';

-- Collapse any extra active duplicates per canonical name (merge on_hand, keep lowest id)
UPDATE stocks s
INNER JOIN (
  SELECT name, MIN(id) AS keep_id, SUM(on_hand) AS total_on_hand
  FROM stocks
  WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
    AND name IN (
      'Honey bottle w/ cap - 250ml',
      'Honey bottle w/ cap - 350ml',
      'Honey bottle w/ cap - 1L',
      'Honey bottle w/ cap - gallon'
    )
  GROUP BY name
  HAVING COUNT(*) > 1
) d ON d.name = s.name AND s.id <> d.keep_id AND s.dept_id = @dept_honey
SET s.is_active = 0;

UPDATE stocks k
INNER JOIN (
  SELECT name, MIN(id) AS keep_id, SUM(on_hand) AS total_on_hand
  FROM stocks
  WHERE dept_id = @dept_honey AND supplier_id IS NULL AND is_active = 1
    AND name LIKE 'Honey bottle w/ cap - %'
  GROUP BY name
) d ON d.keep_id = k.id
SET k.on_hand = d.total_on_hand;

-- Repoint any formula still on deactivated bottle stocks
UPDATE product_formula pf
INNER JOIN stocks old_s ON old_s.id = pf.stock_id
INNER JOIN products p ON p.id = pf.product_id
INNER JOIN stocks new_s ON new_s.dept_id = old_s.dept_id AND new_s.supplier_id IS NULL AND new_s.is_active = 1
  AND new_s.name = CASE p.size
    WHEN '250ml' THEN 'Honey bottle w/ cap - 250ml'
    WHEN '350ml' THEN 'Honey bottle w/ cap - 350ml'
    WHEN 'liter' THEN 'Honey bottle w/ cap - 1L'
    WHEN 'gallon' THEN 'Honey bottle w/ cap - gallon'
  END
SET pf.stock_id = new_s.id
WHERE old_s.name LIKE 'Honey bottle w/ cap%'
  AND (old_s.is_active = 0 OR old_s.name LIKE '%???%');

-- Remove duplicate formula rows after repoint
DELETE pf FROM product_formula pf
INNER JOIN (
  SELECT MIN(id) AS keep_id, product_id, stock_id
  FROM product_formula
  GROUP BY product_id, stock_id
  HAVING COUNT(*) > 1
) d ON pf.product_id = d.product_id AND pf.stock_id = d.stock_id AND pf.id <> d.keep_id;
