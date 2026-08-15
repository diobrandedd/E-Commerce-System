-- Allow the same human batch label (e.g. JR063026) on different honey variants.
-- Uniqueness becomes (batch_no, variant). Sticker barcodes stay globally unique.
-- Run once: mysql -u root rts_system < sql/migration_phase59_honey_batch_no_per_variant.sql

SET NAMES utf8mb4;

-- Drop global unique on batch_no (guarded).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_batch_no'
);
SET @sql := IF(@idx > 0,
  'ALTER TABLE `honey_packaging_rows` DROP INDEX `uq_honey_pk_batch_no`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Add (batch_no, variant) unique (guarded).
SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'honey_packaging_rows'
    AND INDEX_NAME = 'uq_honey_pk_batch_variant'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `honey_packaging_rows` ADD UNIQUE KEY `uq_honey_pk_batch_variant` (`batch_no`, `variant`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
