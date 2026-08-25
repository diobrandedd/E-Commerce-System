-- Check Inventory (physical stock take): shared daily count events, scans, and compare runs.
-- Run once: mysql -u root rts_system < sql/migration_phase67_inventory_check.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_check_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `count_date` DATE NOT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `opened_by` INT UNSIGNED NOT NULL,
  `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_by` INT UNSIGNED NULL DEFAULT NULL,
  `closed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_check_date` (`count_date`),
  KEY `idx_inv_check_status` (`status`),
  CONSTRAINT `fk_inv_check_opener` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inv_check_closer` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per physical unit seen on the shelf. Batch / variant / size are snapshotted
-- so a past count still reads correctly after a batch row is edited or depleted.
CREATE TABLE IF NOT EXISTS `inventory_check_scans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `kind` ENUM('honey','dept') NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `barcode_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NULL DEFAULT NULL,
  `code` VARCHAR(64) NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(48) NULL DEFAULT NULL,
  `batch_no` VARCHAR(48) NULL DEFAULT NULL,
  `bottling_date` DATE NULL DEFAULT NULL,
  `variant` VARCHAR(32) NULL DEFAULT NULL,
  `size` VARCHAR(16) NULL DEFAULT NULL,
  `flag` ENUM('ok','not_in_stock','void') NOT NULL DEFAULT 'ok',
  `scanned_by` INT UNSIGNED NOT NULL,
  `scanned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_check_scan_code` (`event_id`, `code`),
  KEY `idx_inv_check_scan_event` (`event_id`),
  KEY `idx_inv_check_scan_group` (`event_id`, `kind`, `product_key`),
  KEY `idx_inv_check_scan_pack` (`event_id`, `packaging_row_id`),
  CONSTRAINT `fk_inv_check_scan_event` FOREIGN KEY (`event_id`) REFERENCES `inventory_check_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_check_scan_user` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_check_compare_runs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `scope` VARCHAR(255) NOT NULL DEFAULT '',
  `line_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `mismatch_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `counted_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `system_total` INT NOT NULL DEFAULT 0,
  `run_by` INT UNSIGNED NOT NULL,
  `run_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_check_run_event` (`event_id`),
  CONSTRAINT `fk_inv_check_run_event` FOREIGN KEY (`event_id`) REFERENCES `inventory_check_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_check_run_user` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_check_variance_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id` INT UNSIGNED NOT NULL,
  `kind` ENUM('honey','dept') NOT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `packaging_row_id` INT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(48) NULL DEFAULT NULL,
  `batch_no` VARCHAR(48) NULL DEFAULT NULL,
  `bottling_date` DATE NULL DEFAULT NULL,
  `variant` VARCHAR(32) NULL DEFAULT NULL,
  `size` VARCHAR(16) NULL DEFAULT NULL,
  `counted_qty` INT NOT NULL DEFAULT 0,
  `system_qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `variance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_inv_check_var_run` (`run_id`),
  CONSTRAINT `fk_inv_check_var_run` FOREIGN KEY (`run_id`) REFERENCES `inventory_check_compare_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role presets only apply to new accounts, so give every existing active user the
-- scan key; executives already pass every permission check by role.
INSERT INTO `user_permissions` (`user_id`, `permission_key`)
SELECT u.id, 'inventory.check.scan'
FROM `users` u
WHERE NOT EXISTS (
  SELECT 1 FROM `user_permissions` p
  WHERE p.user_id = u.id AND p.permission_key = 'inventory.check.scan'
);

-- Production managers also get the full report + the ability to open/close a count.
INSERT INTO `user_permissions` (`user_id`, `permission_key`)
SELECT u.id, k.perm
FROM `users` u
JOIN `roles` r ON r.id = u.role_id
JOIN (
  SELECT 'inventory.check.view_all' AS perm
  UNION ALL SELECT 'inventory.check.manage'
) k
WHERE r.name = 'prod_manager'
  AND NOT EXISTS (
    SELECT 1 FROM `user_permissions` p
    WHERE p.user_id = u.id AND p.permission_key = k.perm
  );
