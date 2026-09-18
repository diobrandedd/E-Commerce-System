-- Check Inventory: one scan line per barcode per event (no batch/date docs key).
-- Merges duplicate (event_id, code) rows by summing counted_qty onto the lowest id.
-- Safe to re-run.

-- Fold duplicates before tightening the unique key.
UPDATE inventory_check_scans keep
INNER JOIN (
  SELECT event_id, code, MIN(id) AS keep_id, SUM(counted_qty) AS total_qty
  FROM inventory_check_scans
  GROUP BY event_id, code
  HAVING COUNT(*) > 1
) d ON d.keep_id = keep.id
SET keep.counted_qty = d.total_qty;

DELETE s FROM inventory_check_scans s
INNER JOIN (
  SELECT event_id, code, MIN(id) AS keep_id
  FROM inventory_check_scans
  GROUP BY event_id, code
  HAVING COUNT(*) > 1
) d ON d.event_id = s.event_id AND d.code = s.code AND s.id <> d.keep_id;

SET @db := DATABASE();
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_check_scans'
        AND INDEX_NAME = 'uq_inv_check_scan_sku_doc'
    ),
    'ALTER TABLE `inventory_check_scans` DROP INDEX `uq_inv_check_scan_sku_doc`',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1 FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_check_scans'
        AND INDEX_NAME = 'uq_inv_check_scan_code'
    ),
    'SELECT 1',
    'ALTER TABLE `inventory_check_scans` ADD UNIQUE KEY `uq_inv_check_scan_code` (`event_id`, `code`)'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
