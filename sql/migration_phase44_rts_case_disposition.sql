-- RTS case disposition: standard | swapped | leak; denormalized before-shipment photo filename.
-- Run after migration_rts_damaged.sql. Adjust USE to match config/config.php database name.

ALTER TABLE `rts_cases`
  ADD COLUMN `case_disposition` ENUM('standard','swapped','leak') NOT NULL DEFAULT 'standard'
    COMMENT 'standard=normal return; swapped=wrong contents (no sellable sync); leak=damaged sellable'
    AFTER `damaged_tag`,
  ADD COLUMN `before_shipment_photo_filename` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Denormalized copy from delivery_batch_items.photo_filename at save time'
    AFTER `delivery_match_item_id`,
  ADD KEY `idx_rts_case_disposition_opened` (`case_disposition`, `date_opened`);

UPDATE `rts_cases`
SET `case_disposition` = CASE
  WHEN `damaged_reason` = 'leak' THEN 'leak'
  WHEN `damaged_reason` IN ('item_replaced', 'packaging_replaced') THEN 'swapped'
  ELSE 'standard'
END
WHERE `case_disposition` = 'standard';

UPDATE `rts_cases` c
INNER JOIN `delivery_batch_items` i ON i.id = c.delivery_match_item_id
SET c.before_shipment_photo_filename = NULLIF(TRIM(i.photo_filename), '')
WHERE c.delivery_match_item_id IS NOT NULL
  AND (c.before_shipment_photo_filename IS NULL OR c.before_shipment_photo_filename = '');
