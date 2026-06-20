-- Phase 40: Reactivate purchased customized cushion pieces (1L / 2L / 3L)
-- Ops buys these per spreadsheet "Cushion type (customize)" — not cut from rolls.
-- Run after phase 37/39. Then: php tools/seed_fulfillment_pack_tiers.php

SET NAMES utf8mb4;

UPDATE stocks
SET is_active = 1
WHERE supplier_id IS NULL
  AND name IN (
    'Cushion piece - 1 liter',
    'Cushion piece - 2 liters',
    'Cushion piece - 3 liters'
  );
