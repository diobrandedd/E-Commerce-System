-- RTS: damaged parcel flag + link to pre-delivery delivery_batch_items row (same tracking).
-- Run after deliveries tables exist. Adjust USE to match config/config.php database name.

ALTER TABLE `rts_cases`
  ADD COLUMN `damaged_reason` ENUM('leak','item_replaced','packaging_replaced') NULL DEFAULT NULL COMMENT 'NULL = not damaged' AFTER `documentation_photo`,
  ADD COLUMN `delivery_match_item_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'delivery_batch_items.id for before-delivery photo' AFTER `damaged_reason`,
  ADD KEY `idx_rts_damaged_reason` (`damaged_reason`);

ALTER TABLE `rts_cases`
  ADD CONSTRAINT `fk_rts_case_delivery_item`
  FOREIGN KEY (`delivery_match_item_id`) REFERENCES `delivery_batch_items` (`id`) ON DELETE SET NULL;
