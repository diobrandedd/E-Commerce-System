-- Phase 16: Dept products catalog (PT, BRC, Jar Cacao)
-- Run after phase 15. Seeds stocks, products, formulas; adds product_key columns.

SET NAMES utf8mb4;

ALTER TABLE `dept_inventory_rows`
  ADD COLUMN IF NOT EXISTS `product_key` VARCHAR(32) NULL AFTER `dept_id`,
  ADD COLUMN IF NOT EXISTS `last_stock_in_at` DATETIME NULL AFTER `source_note`;

ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `dept_product_key` VARCHAR(32) NULL AFTER `name`;

CREATE UNIQUE INDEX IF NOT EXISTS `uq_dept_inv_dept_product_key`
  ON `dept_inventory_rows` (`dept_id`, `product_key`);

CREATE UNIQUE INDEX IF NOT EXISTS `uq_products_dept_product_key`
  ON `products` (`dept_product_key`);

CREATE TABLE IF NOT EXISTS `online_parcel_dept_deduct_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parcel_id` INT UNSIGNED NOT NULL,
  `dept_row_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_opddl_parcel` (`parcel_id`),
  KEY `idx_opddl_dept_row` (`dept_row_id`),
  CONSTRAINT `fk_opddl_dept_row` FOREIGN KEY (`dept_row_id`) REFERENCES `dept_inventory_rows` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bootstrap user for created_by (first executive, else first user)
SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_paragis := (SELECT id FROM departments WHERE name = 'paragis' LIMIT 1);
SET @dept_cacao_brc := (SELECT id FROM departments WHERE name = 'cacao_brc' LIMIT 1);

-- Stocks (costing ingredients)
INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Paragis Tea — unit', @dept_paragis, 'pcs', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_paragis AND name = 'Paragis Tea — unit');

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Black Rice Coffee — unit', @dept_cacao_brc, 'pcs', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'Black Rice Coffee — unit');

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'Jar Cacao — unit', @dept_cacao_brc, 'pcs', 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stocks WHERE dept_id = @dept_cacao_brc AND name = 'Jar Cacao — unit');

-- Products (sellable SKUs for costing + POS)
INSERT INTO `products` (`name`, `dept_product_key`, `dept_id`, `size`, `variant`, `selling_price`, `baseline_cost`, `created_by`)
SELECT 'Paragis Tea', 'pt', @dept_paragis, NULL, NULL, 0.00, 0.00, @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM products WHERE dept_product_key = 'pt');

INSERT INTO `products` (`name`, `dept_product_key`, `dept_id`, `size`, `variant`, `selling_price`, `baseline_cost`, `created_by`)
SELECT 'Black Rice Coffee', 'brc', @dept_cacao_brc, NULL, NULL, 0.00, 0.00, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM products WHERE dept_product_key = 'brc');

INSERT INTO `products` (`name`, `dept_product_key`, `dept_id`, `size`, `variant`, `selling_price`, `baseline_cost`, `created_by`)
SELECT 'Jar Cacao', 'jar_cacao', @dept_cacao_brc, NULL, NULL, 0.00, 0.00, @bootstrap_user_id
WHERE @dept_cacao_brc IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM products WHERE dept_product_key = 'jar_cacao');

-- Formulas: 1:1 finished unit
INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Paragis Tea — unit'
WHERE p.dept_product_key = 'pt'
  AND NOT EXISTS (SELECT 1 FROM product_formula f WHERE f.product_id = p.id);

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Black Rice Coffee — unit'
WHERE p.dept_product_key = 'brc'
  AND NOT EXISTS (SELECT 1 FROM product_formula f WHERE f.product_id = p.id);

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, s.id, 1.00000000, s.current_price
FROM products p
INNER JOIN stocks s ON s.dept_id = p.dept_id AND s.name = 'Jar Cacao — unit'
WHERE p.dept_product_key = 'jar_cacao'
  AND NOT EXISTS (SELECT 1 FROM product_formula f WHERE f.product_id = p.id);

-- Merge legacy free-text dept rows into canonical keys where possible
UPDATE dept_inventory_rows dir
INNER JOIN departments d ON d.id = dir.dept_id
SET dir.product_key = 'pt',
    dir.product_label = 'Paragis Tea'
WHERE dir.product_key IS NULL
  AND d.name = 'paragis'
  AND LOWER(dir.product_label) REGEXP 'paragis|pouch.?pt|\\bpt\\b';

UPDATE dept_inventory_rows dir
INNER JOIN departments d ON d.id = dir.dept_id
SET dir.product_key = 'brc',
    dir.product_label = 'Black Rice Coffee'
WHERE dir.product_key IS NULL
  AND d.name = 'cacao_brc'
  AND LOWER(dir.product_label) REGEXP 'black rice|brc|pouch.?brc';

UPDATE dept_inventory_rows dir
INNER JOIN departments d ON d.id = dir.dept_id
SET dir.product_key = 'jar_cacao',
    dir.product_label = 'Jar Cacao'
WHERE dir.product_key IS NULL
  AND d.name = 'cacao_brc'
  AND LOWER(dir.product_label) REGEXP 'cacao|tablea|jar';
