-- Reset stale production "of X gal" baselines when lots are empty or overdrawn.
-- Run once (safe to re-run): mysql -u root rts_system < sql/migration_phase80_honey_production_reset_empty_initial.sql

SET NAMES utf8mb4;

-- Empty or overdrawn lots should not keep a leftover cycle max (e.g. "0% of 205 gal").
UPDATE honey_production_rows
SET initial_qty = 0
WHERE current_qty <= 0
  AND initial_qty > 0;
