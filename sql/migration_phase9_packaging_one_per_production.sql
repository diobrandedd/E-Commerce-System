-- One packaging row per production row (partial bottling updates same batch).
SET NAMES utf8mb4;

-- Remove duplicate packaging rows if any exist before adding unique key (keep lowest id).
DELETE pk1 FROM honey_packaging_rows pk1
INNER JOIN honey_packaging_rows pk2
  ON pk1.production_row_id = pk2.production_row_id AND pk1.id > pk2.id;

SET @uq_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_production_row'
);

SET @sql_uq := IF(
  @uq_exists = 0,
  'ALTER TABLE `honey_packaging_rows` ADD UNIQUE KEY `uq_honey_pk_production_row` (`production_row_id`)',
  'SELECT 1'
);
PREPARE stmt_uq FROM @sql_uq;
EXECUTE stmt_uq;
DEALLOCATE PREPARE stmt_uq;
