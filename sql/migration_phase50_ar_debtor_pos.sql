-- Phase 50: POS AR checkout — debtor fields, pos_sales.is_ar, ar_credits.pos_sale_id
-- Run after phase 49.

SET NAMES utf8mb4;

ALTER TABLE `pos_sales`
  ADD COLUMN `is_ar` TINYINT(1) NOT NULL DEFAULT 0 AFTER `total_amount`,
  ADD KEY `idx_pos_sales_is_ar` (`is_ar`);

ALTER TABLE `ar_credits`
  MODIFY COLUMN `user_id` INT UNSIGNED NULL,
  ADD COLUMN `debtor_user_id` INT UNSIGNED NULL AFTER `user_id`,
  ADD COLUMN `debtor_name` VARCHAR(160) NOT NULL DEFAULT '' AFTER `debtor_user_id`,
  ADD COLUMN `debtor_mobile` VARCHAR(32) NULL AFTER `debtor_name`,
  ADD COLUMN `pos_sale_id` INT UNSIGNED NULL AFTER `debtor_mobile`,
  ADD COLUMN `source` ENUM('ims_take','pos') NOT NULL DEFAULT 'ims_take' AFTER `pos_sale_id`,
  ADD COLUMN `recorded_by_pin_id` INT UNSIGNED NULL AFTER `source`,
  ADD KEY `idx_ar_credits_debtor_user` (`debtor_user_id`),
  ADD KEY `idx_ar_credits_pos_sale` (`pos_sale_id`),
  ADD CONSTRAINT `fk_ar_credits_debtor_user` FOREIGN KEY (`debtor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ar_credits_pos_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ar_credits_recorded_pin` FOREIGN KEY (`recorded_by_pin_id`) REFERENCES `pos_pins` (`id`) ON DELETE SET NULL;

UPDATE `ar_credits` ar
INNER JOIN `users` u ON u.id = ar.user_id
SET ar.debtor_user_id = ar.user_id,
    ar.debtor_name = u.name,
    ar.source = 'ims_take'
WHERE ar.debtor_name = '';
