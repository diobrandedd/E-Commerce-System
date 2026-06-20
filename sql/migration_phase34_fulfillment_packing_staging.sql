-- Phase 34: Fulfillment packing staging + on_hold + re-waybill
-- Run after phase 33.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `fulfillment_packing_balances` (
  `stock_id` INT UNSIGNED NOT NULL,
  `issued_qty` DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
  `consumed_qty` DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`),
  CONSTRAINT `fk_fpb_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fulfillment_packing_issues` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `issued_by` INT UNSIGNED NULL DEFAULT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `est_parcels_typical` INT UNSIGNED NULL DEFAULT NULL,
  `est_parcels_label` VARCHAR(128) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fpi_created` (`created_at`),
  KEY `idx_fpi_issued_by` (`issued_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fulfillment_packing_issue_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `issue_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(14,6) NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  PRIMARY KEY (`id`),
  KEY `idx_fpil_issue` (`issue_id`),
  KEY `idx_fpil_stock` (`stock_id`),
  CONSTRAINT `fk_fpil_issue`
    FOREIGN KEY (`issue_id`) REFERENCES `fulfillment_packing_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fpil_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `online_parcel_tracking_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parcel_id` INT UNSIGNED NOT NULL,
  `old_tracking` VARCHAR(96) NOT NULL,
  `new_tracking` VARCHAR(96) NOT NULL,
  `changed_by` INT UNSIGNED NULL DEFAULT NULL,
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_opth_parcel` (`parcel_id`),
  KEY `idx_opth_old` (`old_tracking`),
  KEY `idx_opth_new` (`new_tracking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `online_parcel_hold_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `online_parcel_id` INT UNSIGNED NOT NULL,
  `tracking_number` VARCHAR(96) NOT NULL,
  `previous_status` VARCHAR(32) NOT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `held_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ophe_parcel` (`online_parcel_id`),
  KEY `idx_ophe_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_type := (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'online_order_parcels'
    AND COLUMN_NAME = 'status'
  LIMIT 1
);
SET @sql := IF(
  @col_type IS NOT NULL AND @col_type NOT LIKE '%on_hold%',
  'ALTER TABLE `online_order_parcels` MODIFY COLUMN `status` ENUM(''packing'',''shipped'',''on_hold'',''cancelled'',''returned'') NOT NULL DEFAULT ''packing''',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @dce_col := (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'delivery_cancel_events'
    AND COLUMN_NAME = 'previous_status'
  LIMIT 1
);
SET @sql2 := IF(
  @dce_col IS NOT NULL AND @dce_col NOT LIKE '%on_hold%',
  'ALTER TABLE `delivery_cancel_events` MODIFY COLUMN `previous_status` ENUM(''packing'',''shipped'',''on_hold'',''cancelled'',''returned'') NOT NULL',
  'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
