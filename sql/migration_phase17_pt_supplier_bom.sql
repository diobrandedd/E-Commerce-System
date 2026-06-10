-- Phase 17: Paragis Tea supplier master (₱/bag) + BOM costing (leaf + packaging)
-- Run after phase 16.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `supplier_dept_prices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `price_per_unit` DECIMAL(12,2) NOT NULL,
  `effective_from` DATE NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdp_supplier_product_date` (`supplier_id`, `product_key`, `effective_from`),
  CONSTRAINT `fk_sdp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_sdp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_paragis := (SELECT id FROM departments WHERE name = 'paragis' LIMIT 1);
SET @paragis_supplier := (
  SELECT id FROM suppliers WHERE category = 'paragis' AND is_active = 1 ORDER BY id ASC LIMIT 1
);

-- PT pouch packaging (manual price on Costing → Stocks)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'PT pouch packaging', @dept_paragis, 'pouch', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_paragis AND name = 'PT pouch packaging' AND supplier_id IS NULL
  );

-- Paragis leaf — raw (per-supplier when supplier exists; else placeholder row)
INSERT INTO `stocks` (`name`, `dept_id`, `supplier_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT
  'Paragis leaf — raw',
  @dept_paragis,
  @paragis_supplier,
  'bag',
  0.00,
  IF(@paragis_supplier IS NULL, NULL, (
    SELECT CONCAT(s.first_name, ' ', s.last_name) FROM suppliers s WHERE s.id = @paragis_supplier LIMIT 1
  )),
  NULL,
  @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks
    WHERE dept_id = @dept_paragis AND name = 'Paragis leaf — raw'
      AND ((@paragis_supplier IS NULL AND supplier_id IS NULL) OR supplier_id = @paragis_supplier)
  );

-- Remove obsolete 1:1 PT placeholder formula + stock
DELETE se FROM `stock_entries` se
INNER JOIN `stocks` s ON s.id = se.stock_id
WHERE @dept_paragis IS NOT NULL
  AND s.dept_id = @dept_paragis
  AND s.name = 'Paragis Tea — unit';

DELETE pf FROM `product_formula` pf
INNER JOIN `products` p ON p.id = pf.product_id
WHERE p.dept_product_key = 'pt';

DELETE s FROM `stocks` s
WHERE @dept_paragis IS NOT NULL
  AND s.dept_id = @dept_paragis
  AND s.name = 'Paragis Tea — unit';

-- Two-line BOM: leaf fraction per pouch + 1 packaging pouch
-- 24 tea bags/pouch × 2 g = 48 g leaf; 48 g / 5000 g per supplier bag = 24/2500 = 0.0096 bag per pouch
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, leaf.id, 0.00960000, leaf.current_price
FROM products p
INNER JOIN stocks leaf ON leaf.dept_id = p.dept_id AND leaf.name = 'Paragis leaf — raw'
WHERE p.dept_product_key = 'pt'
  AND @dept_paragis IS NOT NULL
  AND (
    (@paragis_supplier IS NULL AND leaf.supplier_id IS NULL)
    OR leaf.supplier_id = @paragis_supplier
  )
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ls ON ls.id = f.stock_id
    WHERE f.product_id = p.id AND ls.name = 'Paragis leaf — raw'
  )
LIMIT 1;

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, pack.id, 1.00000000, pack.current_price
FROM products p
INNER JOIN stocks pack ON pack.dept_id = p.dept_id AND pack.name = 'PT pouch packaging' AND pack.supplier_id IS NULL
WHERE p.dept_product_key = 'pt'
  AND @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ps ON ps.id = f.stock_id
    WHERE f.product_id = p.id AND ps.name = 'PT pouch packaging'
  );

-- Refresh PT baseline from new formula lines
UPDATE products p
SET p.baseline_cost = (
  SELECT COALESCE(SUM(pf.qty_per_unit * pf.price_at_creation), 0)
  FROM product_formula pf
  WHERE pf.product_id = p.id
)
WHERE p.dept_product_key = 'pt';
