-- Phase 82: Check inventory — stock apply metadata on compare runs
-- Run: mysql -u root rts_system < sql/migration_phase82_inventory_check_stock_apply.sql

SET NAMES utf8mb4;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'inventory_check_compare_runs'
             AND COLUMN_NAME = 'stock_applied');
SET @sql := IF(@c = 0,
  'ALTER TABLE `inventory_check_compare_runs`
     ADD COLUMN `stock_applied` TINYINT(1) NOT NULL DEFAULT 0 AFTER `run_at`,
     ADD COLUMN `stock_applied_at` DATETIME NULL DEFAULT NULL AFTER `stock_applied`,
     ADD COLUMN `stock_applied_by` INT UNSIGNED NULL DEFAULT NULL AFTER `stock_applied_at`,
     ADD COLUMN `barcodes_voided_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `stock_applied_by`,
     ADD COLUMN `merged_event_ids` VARCHAR(255) NULL DEFAULT NULL AFTER `barcodes_voided_count`,
     ADD KEY `idx_inv_check_run_applied` (`stock_applied`),
     ADD CONSTRAINT `fk_inv_check_run_applied_by` FOREIGN KEY (`stock_applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
