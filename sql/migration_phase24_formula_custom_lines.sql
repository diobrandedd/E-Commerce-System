-- Phase 24: Custom formula lines (labor, shipping, etc.) — no stock link required

ALTER TABLE `product_formula`
  DROP FOREIGN KEY `fk_product_formula_stock`;

ALTER TABLE `product_formula`
  MODIFY `stock_id` INT UNSIGNED NULL,
  ADD COLUMN `line_label` VARCHAR(160) NULL AFTER `stock_id`;

ALTER TABLE `product_formula`
  ADD CONSTRAINT `fk_product_formula_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT;
