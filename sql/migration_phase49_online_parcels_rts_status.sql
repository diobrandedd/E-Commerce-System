-- Phase 49: RTS status on online_order_parcels (sync when CSR records RTS scan).
-- Run once: mysql -u root rts_system < sql/migration_phase49_online_parcels_rts_status.sql

SET NAMES utf8mb4;

SET @status_col := (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'online_order_parcels'
    AND COLUMN_NAME = 'status'
  LIMIT 1
);
SET @sql_status := IF(
  @status_col IS NOT NULL AND @status_col NOT LIKE '%rts%',
  'ALTER TABLE `online_order_parcels` MODIFY COLUMN `status` ENUM(''packing'',''shipped'',''on_hold'',''cancelled'',''returned'',''rts'') NOT NULL DEFAULT ''packing''',
  'SELECT 1'
);
PREPARE stmt_status FROM @sql_status;
EXECUTE stmt_status;
DEALLOCATE PREPARE stmt_status;

SET @dce_col := (
  SELECT COLUMN_TYPE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'delivery_cancel_events'
    AND COLUMN_NAME = 'previous_status'
  LIMIT 1
);
SET @sql_dce := IF(
  @dce_col IS NOT NULL AND @dce_col NOT LIKE '%rts%',
  'ALTER TABLE `delivery_cancel_events` MODIFY COLUMN `previous_status` ENUM(''packing'',''shipped'',''on_hold'',''cancelled'',''returned'',''rts'') NOT NULL',
  'SELECT 1'
);
PREPARE stmt_dce FROM @sql_dce;
EXECUTE stmt_dce;
DEALLOCATE PREPARE stmt_dce;

SET @rts_case_col := (
  SELECT COLUMN_NAME FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'online_order_parcels'
    AND COLUMN_NAME = 'rts_case_id'
  LIMIT 1
);
SET @sql_rts_case := IF(
  @rts_case_col IS NULL,
  'ALTER TABLE `online_order_parcels`
     ADD COLUMN `rts_case_id` INT UNSIGNED NULL DEFAULT NULL AFTER `deducted_at`,
     ADD COLUMN `status_before_rts` ENUM(''packing'',''shipped'',''on_hold'',''cancelled'',''returned'') NULL DEFAULT NULL AFTER `rts_case_id`,
     ADD COLUMN `rts_recorded_at` DATETIME NULL DEFAULT NULL AFTER `status_before_rts`,
     ADD KEY `idx_parcels_rts_case` (`rts_case_id`)',
  'SELECT 1'
);
PREPARE stmt_rts_case FROM @sql_rts_case;
EXECUTE stmt_rts_case;
DEALLOCATE PREPARE stmt_rts_case;
