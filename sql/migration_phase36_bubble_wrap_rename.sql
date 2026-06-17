-- Phase 36: Drop "Fulfillment " prefix from bubble wrap SKU names (ops clarity).
-- After this: php tools/seed_fulfillment_pack_tiers.php

SET NAMES utf8mb4;

UPDATE stocks
SET name = 'Bubble wrap roll - 13 cm'
WHERE supplier_id IS NULL AND name = 'Fulfillment bubble wrap roll - 13 cm';

UPDATE stocks
SET name = 'Bubble wrap roll - 17 cm'
WHERE supplier_id IS NULL AND name = 'Fulfillment bubble wrap roll - 17 cm';

UPDATE stocks
SET name = 'Bubble wrap roll - 22 cm'
WHERE supplier_id IS NULL AND name = 'Fulfillment bubble wrap roll - 22 cm';
