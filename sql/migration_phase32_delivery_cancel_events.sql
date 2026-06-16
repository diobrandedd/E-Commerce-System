-- phase32: deliveries cancellation audit log
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS `delivery_cancel_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `online_parcel_id` INT UNSIGNED NOT NULL,
  `tracking_number` VARCHAR(96) NOT NULL,
  `previous_status` ENUM('packing','shipped','cancelled','returned') NOT NULL,
  `inventory_reverted` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel_source` VARCHAR(32) NOT NULL DEFAULT 'scanner',
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `cancelled_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dce_parcel` (`online_parcel_id`),
  KEY `idx_dce_tracking` (`tracking_number`),
  KEY `idx_dce_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
