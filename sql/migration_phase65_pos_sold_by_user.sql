-- Phase 65: record IMS cashier on POS sales (nullable PIN for account POS).
-- Safe before code deploy.

SET NAMES utf8mb4;

ALTER TABLE `pos_sales`
  MODIFY `sold_by_pin_id` INT UNSIGNED NULL;

ALTER TABLE `pos_sales`
  ADD COLUMN `sold_by_user_id` INT UNSIGNED NULL AFTER `sold_by_pin_id`;

ALTER TABLE `pos_sales`
  ADD KEY `idx_pos_sales_sold_by_user` (`sold_by_user_id`);

ALTER TABLE `pos_sales`
  ADD CONSTRAINT `fk_pos_sales_sold_by_user`
    FOREIGN KEY (`sold_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
