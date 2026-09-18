-- Rebuild finished honey POOL current_* from unique stickers still in packaging.
-- Use on prod ONLY when Products → Honey pools are 0 but honey_bottle_units still has
-- status=in_stock / pool=packaging stickers.
--
-- IMPORTANT:
--   • Counts UNIQUE stickers only. SKU pack stock-ins (barcode + qty, no sticker) are NOT
--     in honey_bottle_units — add those afterward via Products → Honey → Edit if needed.
--   • Packaging stage “stocked-in report” is scan HISTORY, not live on-hand — it can look
--     full even when POOL rows are empty.
--
-- Preview first (do not skip):
--   SELECT variant, size, COUNT(*) c
--   FROM honey_bottle_units
--   WHERE status = 'in_stock' AND pool = 'packaging'
--   GROUP BY variant, size;
--
-- Run: mysql -u root rts_system < sql/migration_phase86_rebuild_honey_pools_from_stickers.sql

SET NAMES utf8mb4;

-- Ensure POOL rows exist.
INSERT INTO honey_packaging_rows (
  production_row_id, supplier_code, batch_no, bottling_date, variant,
  current_liter, initial_liter, current_250ml, initial_250ml,
  current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
)
SELECT NULL, '', 'POOL', '2000-01-01', v.variant, 0, 0, 0, 0, 0, 0, 0, 0, 1
FROM (
  SELECT 'light' AS variant
  UNION ALL SELECT 'amber'
  UNION ALL SELECT 'dark'
) AS v
WHERE NOT EXISTS (
  SELECT 1 FROM honey_packaging_rows p
  WHERE p.batch_no = 'POOL'
    AND p.bottling_date = '2000-01-01'
    AND p.variant = v.variant
);

-- Set POOL on-hand = sticker counts (replace, do not add — pools are empty on the broken prod).
UPDATE honey_packaging_rows pool
LEFT JOIN (
  SELECT
    LOWER(TRIM(variant)) AS variant,
    COALESCE(SUM(size = '250ml'), 0) AS c_250,
    COALESCE(SUM(size = '350ml'), 0) AS c_350,
    COALESCE(SUM(size = 'liter'), 0) AS c_liter,
    COALESCE(SUM(size = 'gallon'), 0) AS c_gallon
  FROM honey_bottle_units
  WHERE status = 'in_stock'
    AND pool = 'packaging'
    AND LOWER(TRIM(variant)) IN ('light', 'amber', 'dark')
  GROUP BY LOWER(TRIM(variant))
) u ON u.variant = pool.variant
SET
  pool.current_250ml = COALESCE(u.c_250, 0),
  pool.current_350ml = COALESCE(u.c_350, 0),
  pool.current_liter = COALESCE(u.c_liter, 0),
  pool.current_gallon = COALESCE(u.c_gallon, 0),
  pool.initial_250ml = COALESCE(u.c_250, 0),
  pool.initial_350ml = COALESCE(u.c_350, 0),
  pool.initial_liter = COALESCE(u.c_liter, 0),
  pool.initial_gallon = COALESCE(u.c_gallon, 0)
WHERE pool.batch_no = 'POOL'
  AND pool.bottling_date = '2000-01-01'
  AND pool.variant IN ('light', 'amber', 'dark');

-- Point live packaging stickers at the variant POOL so future deducts hit the right row.
UPDATE honey_bottle_units u
INNER JOIN honey_packaging_rows pool
  ON pool.batch_no = 'POOL'
 AND pool.bottling_date = '2000-01-01'
 AND pool.variant = LOWER(TRIM(u.variant))
SET u.packaging_row_id = pool.id
WHERE u.status = 'in_stock'
  AND u.pool = 'packaging'
  AND LOWER(TRIM(u.variant)) IN ('light', 'amber', 'dark');
