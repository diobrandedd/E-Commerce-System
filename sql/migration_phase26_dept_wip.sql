-- Phase 26: Dept production WIP + PT tea bag sachet packaging
-- Run after phase 25.

SET NAMES utf8mb4;

SET @bootstrap_user_id := (
  SELECT u.id FROM users u
  INNER JOIN roles r ON r.id = u.role_id
  WHERE r.name = 'executive' AND u.is_active = 1
  ORDER BY u.id ASC LIMIT 1
);
SET @bootstrap_user_id := IFNULL(@bootstrap_user_id, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

SET @dept_paragis := (SELECT id FROM departments WHERE name = 'paragis' LIMIT 1);

CREATE TABLE IF NOT EXISTS `dept_wip_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` INT UNSIGNED NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `wip_unit` VARCHAR(32) NOT NULL DEFAULT 'tea_bag',
  `current_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_wip_product_unit` (`dept_id`, `product_key`, `wip_unit`),
  KEY `idx_dept_wip_product` (`product_key`),
  CONSTRAINT `fk_dept_wip_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dept_wip_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dept_production_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_key` VARCHAR(32) NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `qty` DECIMAL(12,4) NOT NULL,
  `note` VARCHAR(255) NULL,
  `deduct_lines` JSON NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dept_prod_product` (`product_key`, `created_at`),
  CONSTRAINT `fk_dept_prod_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stocks` (`name`, `dept_id`, `unit`, `category`, `on_hand`, `current_price`, `supplier_name`, `supplier_code`, `created_by`)
SELECT 'PT tea bag sachet', @dept_paragis, 'pcs', 'packaging', 0.00, 0.00, NULL, NULL, @bootstrap_user_id
WHERE @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM stocks WHERE dept_id = @dept_paragis AND name = 'PT tea bag sachet' AND supplier_id IS NULL
  );

INSERT INTO `product_formula` (`product_id`, `stock_id`, `qty_per_unit`, `price_at_creation`)
SELECT p.id, sachet.id, 24.00000000, sachet.current_price
FROM products p
INNER JOIN stocks sachet ON sachet.dept_id = p.dept_id AND sachet.name = 'PT tea bag sachet' AND sachet.supplier_id IS NULL
WHERE p.dept_product_key = 'pt'
  AND @dept_paragis IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM product_formula f
    INNER JOIN stocks ss ON ss.id = f.stock_id
    WHERE f.product_id = p.id AND ss.name = 'PT tea bag sachet'
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
WHERE p.dept_product_key = 'pt';
