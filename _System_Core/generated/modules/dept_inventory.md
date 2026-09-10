---
title: dept_inventory
source: includes/dept_inventory.php
generated: 2026-09-10T07:09:33+02:00
type: module
function_count: 19
---


# dept_inventory

> Source: `includes/dept_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/dept_pricing.php`
- `includes/dept_pt_settings.php`
- `includes/inventory_edit_log.php`

## dept_inventory_fetch_row {#dept_inventory_fetch_row}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (11):**

- `includes/dept_inventory.php`:35 — `$existing = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:51 — `$row = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:223 — `$finished = dept_inventory_fetch_row($pdo, $productKey);`
- `includes/dept_inventory.php`:643 — `$row = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:691 — `$row = dept_inventory_fetch_row($pdo, $def['key']);`
- `includes/dept_pouch_barcodes.php`:641 — `$finished = dept_inventory_fetch_row($pdo, $productKey);`
- `includes/dept_production.php`:916 — `$finished = dept_inventory_fetch_row($pdo, 'pt');`
- `includes/dept_production.php`:964 — `$finished = dept_inventory_fetch_row($pdo, 'brc');`
- `includes/dept_production.php`:1027 — `$finished = dept_inventory_fetch_row($pdo, 'jar_cacao');`
- `includes/dept_production.php`:1057 — `$finished = dept_inventory_fetch_row($pdo, 'honey_comb');`
- `includes/inventory_check.php`:1121 — `$row = dept_inventory_fetch_row($pdo, $productKey);`

## dept_inventory_ensure_row {#dept_inventory_ensure_row}

**Tables:** `dept_inventory_rows`

**Callers (10):**

- `includes/dept_inventory.php`:539 — `$row = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/dept_inventory.php`:582 — `$row = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/dept_pouch_barcodes.php`:582 — `$fin = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/dept_production.php`:901 — `$row = dept_inventory_ensure_row($pdo, $userId, 'pt');`
- `includes/dept_production.php`:954 — `$row = dept_inventory_ensure_row($pdo, $userId, 'brc');`
- `includes/dept_production.php`:1017 — `$row = dept_inventory_ensure_row($pdo, $userId, 'jar_cacao');`
- `includes/dept_production.php`:1047 — `$row = dept_inventory_ensure_row($pdo, $userId, 'honey_comb');`
- `includes/pack_cancel_restock.php`:592 — `$fin = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/sku_barcodes.php`:1059 — `$fin = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/sku_barcodes.php`:1320 — `$fin = dept_inventory_ensure_row($pdo, $userId, $productKey);`

## dept_inventory_stock_in {#dept_inventory_stock_in}

**Callers (1):**

- `api/v1/honey.php`:850 — `$result = dept_inventory_stock_in(`

## dept_inventory_stock_in_supplier_priced {#dept_inventory_stock_in_supplier_priced}

**Callers (1):**

- `includes/dept_inventory.php`:90 — `return dept_inventory_stock_in_supplier_priced(`

## dept_raw_material_slug_to_product_key {#dept_raw_material_slug_to_product_key}

**Callers (2):**

- `api/v1/honey.php`:875 — `$productKey = dept_raw_material_slug_to_product_key($slug);`
- `includes/dept_inventory.php`:215 — `$productKey = dept_raw_material_slug_to_product_key($slug);`

## dept_raw_bags_on_hand {#dept_raw_bags_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (14):**

- `includes/dept_inventory.php`:225 — `$bagsOnHand = dept_raw_bags_on_hand($pdo, $productKey);`
- `includes/dept_inventory.php`:311 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:377 — `$onHand = dept_raw_bags_on_hand($pdo, $productKey);`
- `includes/dept_inventory.php`:413 — `$before = dept_raw_bags_on_hand($pdo, $productKey);`
- `includes/dept_inventory.php`:469 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:511 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:557 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:765 — `$previous = dept_raw_bags_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:835 — `$onHand = dept_raw_bags_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:853 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:878 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:970 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'brc'),`
- `includes/dept_production.php`:1033 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'jar_cacao'),`
- `includes/dept_production.php`:1063 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'honey_comb'),`

## dept_raw_recent_entries {#dept_raw_recent_entries}

**Tables:** `stock_entries`, `stocks`, `suppliers`

**Callers (1):**

- `includes/dept_inventory.php`:244 — `'entries' => dept_raw_recent_entries($pdo, $productKey, 25),`

## dept_raw_material_snapshot {#dept_raw_material_snapshot}

**Callers (1):**

- `includes/dept_production.php`:978 — `$snap = dept_raw_material_snapshot($pdo, $slug);`

## dept_raw_stock_in_bags {#dept_raw_stock_in_bags}

**Tables:** `stock_entries`

**Callers (2):**

- `api/v1/honey.php`:907 — `$result = dept_raw_stock_in_bags(`
- `includes/dept_inventory.php`:125 — `return dept_raw_stock_in_bags($pdo, $userId, $productKey, $def, $qtyBags, $arrivalDate, $note, $supplierId);`

## dept_raw_deduct_bags_fifo {#dept_raw_deduct_bags_fifo}

**Tables:** `stock_entries`, `stocks`

**Callers (3):**

- `includes/dept_inventory.php`:506 — `dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);`
- `includes/dept_inventory.php`:537 — `dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);`
- `includes/dept_production.php`:362 — `$alloc = dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, (float) $line['qty'], $label);`

## dept_raw_deduct_bags_allow_negative {#dept_raw_deduct_bags_allow_negative}

**Tables:** `stock_entries`, `stocks`

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:593 — `$deduct = dept_raw_deduct_bags_allow_negative(`
- `includes/sku_barcodes.php`:1072 — `$deduct = dept_raw_deduct_bags_allow_negative($pdo, $userId, 'pt', $bags, 'sku_pouch_scan');`

## dept_raw_bags_received_gross {#dept_raw_bags_received_gross}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/dept_inventory.php`:226 — `$bagsGross = dept_raw_bags_received_gross($pdo, $productKey);`

## dept_raw_stock_out_bags {#dept_raw_stock_out_bags}

**Callers (1):**

- `api/v1/honey.php`:942 — `$result = dept_raw_stock_out_bags(`

## dept_inventory_produce_from_bags {#dept_inventory_produce_from_bags}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_inventory_stock_in_manual_cost {#dept_inventory_stock_in_manual_cost}

**Tables:** `stocks`, `dept_inventory_rows`, `stock_entries`

**Callers (1):**

- `includes/dept_inventory.php`:102 — `return dept_inventory_stock_in_manual_cost(`

## dept_inventory_deduct {#dept_inventory_deduct}

**Tables:** `dept_inventory_rows`

**Callers (7):**

- `includes/dept_pos.php`:268 — `dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);`
- `includes/dept_pos.php`:421 — `dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);`
- `includes/dept_pouch_barcodes.php`:1194 — `dept_inventory_deduct($pdo, $productKey, 1.0);`
- `includes/finished_leak_damage.php`:231 — `dept_inventory_deduct($pdo, $productKey, 1.0);`
- `includes/honey_bottle_barcodes.php`:2126 — `dept_inventory_deduct($pdo, $productKey, 1.0);`
- `includes/honey_parcel_inventory.php`:88 — `$deducted = dept_inventory_deduct($pdo, $productKey, (float) $qty);`
- `includes/sku_barcodes.php`:1280 — `dept_inventory_deduct($pdo, $productKey, (float) $qty);`

## dept_inventory_resolve_other_item {#dept_inventory_resolve_other_item}

**Callers (2):**

- `includes/honey_parcel_inventory.php`:39 — `$key = dept_inventory_resolve_other_item((string) ($other['label'] ?? '')) ?? '';`
- `includes/parcel_pack_costing.php`:66 — `$key = dept_inventory_resolve_other_item((string) ($item['label'] ?? '')) ?? '';`

## dept_inventory_catalog_snapshot {#dept_inventory_catalog_snapshot}

**Tables:** `products`

**Callers (4):**

- `api/v1/honey.php`:789 — `$catalog = dept_inventory_catalog_snapshot(`
- `api/v1/honey.php`:832 — `api_ok(['products' => dept_inventory_catalog_snapshot($pdo, $deptKey !== '' ? $deptKey : null)]);`
- `includes/dept_pos.php`:37 — `foreach (dept_inventory_catalog_snapshot($pdo, $deptKey) as $item) {`
- `includes/dept_pos.php`:71 — `$snapshot = dept_inventory_catalog_snapshot($pdo);`

## dept_raw_set_bags_on_hand {#dept_raw_set_bags_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `api/v1/honey.php`:1426 — `$result = dept_raw_set_bags_on_hand(`

