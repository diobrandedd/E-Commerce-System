-- Packaging lots unique by (batch_no, variant, bottling_date).
-- Same human batch label may exist on different bottling dates.
-- Also drops one-packaging-per-production unique so a gallon lot can bottle across days.
-- Pre-check (must return 0 rows before applying):
--   SELECT batch_no, variant, bottling_date, COUNT(*) c
--   FROM honey_packaging_rows
--   GROUP BY batch_no, variant, bottling_date HAVING c > 1;
-- Run once: mysql -u root rts_system < sql/migration_phase77_honey_packaging_batch_variant_date.sql

SET NAMES utf8mb4;

-- Drop (batch_no, variant) unique (guarded).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_batch_variant'
);
SET @sql := IF(@idx > 0,
  'ALTER TABLE `honey_packaging_rows` DROP INDEX `uq_honey_pk_batch_variant`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Drop one-row-per-production unique (guarded).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_production_row'
);
SET @sql := IF(@idx > 0,
  'ALTER TABLE `honey_packaging_rows` DROP INDEX `uq_honey_pk_production_row`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Add (batch_no, variant, bottling_date) unique (guarded).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_batch_variant_date'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `honey_packaging_rows` ADD UNIQUE KEY `uq_honey_pk_batch_variant_date` (`batch_no`, `variant`, `bottling_date`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Helpful list filter index (non-unique).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'idx_honey_pk_bottling_date'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `honey_packaging_rows` ADD KEY `idx_honey_pk_bottling_date` (`bottling_date`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
