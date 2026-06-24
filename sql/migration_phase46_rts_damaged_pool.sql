-- Phase 46: RTS damaged inventory pool + use-damaged audit
SET NAMES utf8mb4;

ALTER TABLE `rts_inventory_rows`
  ADD COLUMN `inventory_pool` ENUM('sellable','damaged') NOT NULL DEFAULT 'sellable'
    COMMENT 'sellable=normal RTS inventory; damaged=leak damaged pool'
    AFTER `category`,
  ADD KEY `idx_rts_inv_pool` (`inventory_pool`, `category`);

CREATE TABLE IF NOT EXISTS `rts_damaged_use_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rts_inventory_row_id` INT UNSIGNED NOT NULL,
  `outcome` ENUM('mix_production','scrap') NOT NULL,
  `variant` VARCHAR(16) NULL DEFAULT NULL,
  `volume_gallons` DECIMAL(12,4) NULL DEFAULT NULL,
  `size_qtys_json` JSON NULL DEFAULT NULL,
  `qty_other` DECIMAL(12,2) NULL DEFAULT NULL,
  `notes` VARCHAR(512) NULL DEFAULT NULL,
  `production_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `performed_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rts_damaged_use_row` (`rts_inventory_row_id`),
  KEY `idx_rts_damaged_use_created` (`created_at`),
  CONSTRAINT `fk_rts_damaged_use_row` FOREIGN KEY (`rts_inventory_row_id`) REFERENCES `rts_inventory_rows` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rts_damaged_use_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
