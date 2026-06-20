-- Phase 35: half LAD is not a separate SKU — deduct 0.5× LAD piece in recipes.
-- After this: php tools/seed_fulfillment_pack_tiers.php

SET NAMES utf8mb4;

SET @half_lad_id := (
  SELECT id FROM stocks WHERE name = 'Cushion piece - half LAD' AND supplier_id IS NULL LIMIT 1
);
SET @lad_id := (
  SELECT id FROM stocks WHERE name = 'Cushion piece - LAD' AND supplier_id IS NULL LIMIT 1
);

-- Ensure LAD balance row exists (phase 34)
INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty)
SELECT @lad_id, 0, 0 FROM DUAL
WHERE @lad_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM fulfillment_packing_balances WHERE stock_id = @lad_id)
  AND EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fulfillment_packing_balances'
  );

UPDATE fulfillment_packing_balances lad
INNER JOIN fulfillment_packing_balances half ON half.stock_id = @half_lad_id
SET lad.issued_qty = lad.issued_qty + half.issued_qty,
    lad.consumed_qty = lad.consumed_qty + half.consumed_qty
WHERE lad.stock_id = @lad_id AND @half_lad_id IS NOT NULL AND @lad_id IS NOT NULL;

DELETE FROM fulfillment_packing_balances
WHERE stock_id = @half_lad_id AND @half_lad_id IS NOT NULL;

-- Warehouse: each half-LAD on-hand counts as 0.5 full LAD
UPDATE stocks lad
INNER JOIN stocks half ON half.id = @half_lad_id
SET lad.on_hand = lad.on_hand + (half.on_hand * 0.5),
    half.on_hand = 0
WHERE lad.id = @lad_id AND @half_lad_id IS NOT NULL AND @lad_id IS NOT NULL;

UPDATE stocks SET is_active = 0
WHERE id = @half_lad_id AND @half_lad_id IS NOT NULL;
