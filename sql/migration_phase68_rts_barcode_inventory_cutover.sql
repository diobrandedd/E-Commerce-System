-- RTS barcode inventory cutover (phase 68).
-- First run: add flag + dept unit pool; enable cutover + hard-reset sellable currents.
-- Re-run: column/pool adds are no-ops; hard reset only runs when sellable_from_cases column is newly added.
-- Run: mysql -u root rts_system < sql/migration_phase68_rts_barcode_inventory_cutover.sql
--
-- BEFORE production enable: dump sellable currents if you may need rollback of qty numbers.
-- Rollback sync only: UPDATE rts_sync_state SET sellable_from_cases = 1 WHERE id = 1;
-- (does NOT restore zeroed currents — restore from dump)

SET NAMES utf8mb4;

SET @had_flag := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rts_sync_state'
    AND COLUMN_NAME = 'sellable_from_cases'
);

SET @sql := IF(@had_flag = 0,
  'ALTER TABLE `rts_sync_state`
     ADD COLUMN `sellable_from_cases` TINYINT UNSIGNED NOT NULL DEFAULT 1
       COMMENT ''1=sync sellable from cases; 0=barcode-backed sellable only''
     AFTER `last_error`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'dept_pouch_units'
    AND COLUMN_NAME = 'pool'
);
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_units`
     ADD COLUMN `pool` ENUM(''finished'',''rts_sellable'',''rts_damaged'') NOT NULL DEFAULT ''finished''
       AFTER `status`,
     ADD KEY `idx_dept_pouch_unit_pool` (`pool`, `status`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill existing in-stock dept units as finished (default already); status=rts legacy → rts_sellable
UPDATE `dept_pouch_units`
SET `pool` = 'rts_sellable'
WHERE `status` = 'rts' AND `pool` = 'finished';

UPDATE `dept_pouch_units`
SET `pool` = 'rts_damaged'
WHERE `status` = 'damaged' AND `pool` = 'finished';

-- First-time cutover enable + hard reset sellable on-hand (option 1)
SET @sql := IF(@had_flag = 0,
  'UPDATE `rts_sync_state` SET `sellable_from_cases` = 0 WHERE `id` = 1',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@had_flag = 0,
  'UPDATE `rts_inventory_rows`
     SET `current_liter` = 0,
         `current_250ml` = 0,
         `current_350ml` = 0,
         `current_gallon` = 0,
         `current_qty` = 0
     WHERE COALESCE(`inventory_pool`, ''sellable'') = ''sellable''',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
