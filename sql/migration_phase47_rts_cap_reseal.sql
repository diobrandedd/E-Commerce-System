-- Phase 47: cap_reseal outcome + repack rows without line-item source
SET NAMES utf8mb4;

ALTER TABLE `rts_damaged_use_events`
  MODIFY COLUMN `outcome` ENUM('mix_production','scrap','cap_reseal') NOT NULL;

ALTER TABLE `rts_inventory_rows`
  MODIFY COLUMN `source_line_item_id` INT UNSIGNED NULL DEFAULT NULL;
