-- Honey packaging variant POOL rows + fold live lot on-hand into pools.
-- Run once: mysql -u root rts_system < sql/migration_phase79_honey_packaging_variant_pools.sql

SET NAMES utf8mb4;

-- Ensure one POOL row per Light / Amber / Dark.
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

-- Fold live on-hand from non-POOL rows into matching variant POOL (idempotent: only rows with qty > 0).
UPDATE honey_packaging_rows pool
INNER JOIN (
  SELECT
    variant,
    COALESCE(SUM(current_liter), 0) AS sum_liter,
    COALESCE(SUM(current_250ml), 0) AS sum_250,
    COALESCE(SUM(current_350ml), 0) AS sum_350,
    COALESCE(SUM(current_gallon), 0) AS sum_gallon,
    COALESCE(SUM(initial_liter), 0) AS ini_liter,
    COALESCE(SUM(initial_250ml), 0) AS ini_250,
    COALESCE(SUM(initial_350ml), 0) AS ini_350,
    COALESCE(SUM(initial_gallon), 0) AS ini_gallon
  FROM honey_packaging_rows
  WHERE NOT (batch_no = 'POOL' AND bottling_date = '2000-01-01')
    AND variant IN ('light', 'amber', 'dark')
    AND (
      current_liter > 0 OR current_250ml > 0 OR current_350ml > 0 OR current_gallon > 0
      OR initial_liter > 0 OR initial_250ml > 0 OR initial_350ml > 0 OR initial_gallon > 0
    )
  GROUP BY variant
) src ON src.variant = pool.variant
SET
  pool.current_liter = pool.current_liter + src.sum_liter,
  pool.current_250ml = pool.current_250ml + src.sum_250,
  pool.current_350ml = pool.current_350ml + src.sum_350,
  pool.current_gallon = pool.current_gallon + src.sum_gallon,
  pool.initial_liter = pool.initial_liter + src.ini_liter,
  pool.initial_250ml = pool.initial_250ml + src.ini_250,
  pool.initial_350ml = pool.initial_350ml + src.ini_350,
  pool.initial_gallon = pool.initial_gallon + src.ini_gallon
WHERE pool.batch_no = 'POOL'
  AND pool.bottling_date = '2000-01-01';

-- Zero non-POOL live counts so totals are not double-counted (rows kept for history / FKs).
UPDATE honey_packaging_rows
SET
  current_liter = 0,
  current_250ml = 0,
  current_350ml = 0,
  current_gallon = 0,
  initial_liter = 0,
  initial_250ml = 0,
  initial_350ml = 0,
  initial_gallon = 0
WHERE NOT (batch_no = 'POOL' AND bottling_date = '2000-01-01')
  AND variant IN ('light', 'amber', 'dark')
  AND (
    current_liter > 0 OR current_250ml > 0 OR current_350ml > 0 OR current_gallon > 0
    OR initial_liter > 0 OR initial_250ml > 0 OR initial_350ml > 0 OR initial_gallon > 0
  );
