-- Phase 101: Undo confirmed pack sessions (HR approval).
-- Run once: mysql -u root rts_system < sql/migration_phase101_pack_session_undo.sql

SET NAMES utf8mb4;

-- honey_bottle_pack_sessions: allow status=undone + audit columns
ALTER TABLE `honey_bottle_pack_sessions`
  MODIFY COLUMN `status` ENUM('draft','confirmed','discarded','undone') NOT NULL DEFAULT 'draft';

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_bottle_pack_sessions'
    AND COLUMN_NAME = 'undone_at'
);
SET @sql := IF(
  @c = 0,
  'ALTER TABLE `honey_bottle_pack_sessions`
     ADD COLUMN `undone_at` DATETIME NULL DEFAULT NULL AFTER `confirmed_by`,
     ADD COLUMN `undone_by` INT UNSIGNED NULL DEFAULT NULL AFTER `undone_at`,
     ADD KEY `idx_honey_pack_sess_undone` (`undone_at`),
     ADD CONSTRAINT `fk_honey_pack_sess_undoer`
       FOREIGN KEY (`undone_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- override_approval_requests: new action type
ALTER TABLE `override_approval_requests`
  MODIFY COLUMN `action_type` ENUM(
    'inventory_check_finalize',
    'dept_raw_pool_set',
    'packaging_scan_undo',
    'pack_session_undo'
  ) NOT NULL;
