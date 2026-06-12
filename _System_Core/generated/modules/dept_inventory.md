---
title: dept_inventory
source: includes/dept_inventory.php
generated: 2026-06-12T11:00:10+02:00
type: module
function_count: 16
---


# dept_inventory

> Source: `includes/dept_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`
- `includes/dept_pricing.php`

## dept_inventory_fetch_row {#dept_inventory_fetch_row}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (8):**

- `includes/dept_inventory.php`:35 — `$existing = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:51 — `$row = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:221 — `$finished = dept_inventory_fetch_row($pdo, $productKey);`
- `includes/dept_inventory.php`:507 — `$row = dept_inventory_fetch_row($pdo, $productKey, true);`
- `includes/dept_inventory.php`:555 — `$row = dept_inventory_fetch_row($pdo, $def['key']);`
- `includes/dept_production.php`:656 — `$finished = dept_inventory_fetch_row($pdo, 'pt');`
- `includes/dept_production.php`:704 — `$finished = dept_inventory_fetch_row($pdo, 'brc');`
- `includes/dept_production.php`:756 — `$finished = dept_inventory_fetch_row($pdo, 'jar_cacao');`

## dept_inventory_ensure_row {#dept_inventory_ensure_row}

**Tables:** `dept_inventory_rows`

**Callers (5):**

- `includes/dept_inventory.php`:403 — `$row = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/dept_inventory.php`:446 — `$row = dept_inventory_ensure_row($pdo, $userId, $productKey);`
- `includes/dept_production.php`:641 — `$row = dept_inventory_ensure_row($pdo, $userId, 'pt');`
- `includes/dept_production.php`:694 — `$row = dept_inventory_ensure_row($pdo, $userId, 'brc');`
- `includes/dept_production.php`:746 — `$row = dept_inventory_ensure_row($pdo, $userId, 'jar_cacao');`

## dept_inventory_stock_in {#dept_inventory_stock_in}

**Callers (1):**

- `api/v1/honey.php`:536 — `$result = dept_inventory_stock_in(`

## dept_inventory_stock_in_supplier_priced {#dept_inventory_stock_in_supplier_priced}

**Callers (1):**

- `includes/dept_inventory.php`:90 — `return dept_inventory_stock_in_supplier_priced(`

## dept_raw_material_slug_to_product_key {#dept_raw_material_slug_to_product_key}

**Callers (1):**

- `includes/dept_inventory.php`:213 — `$productKey = dept_raw_material_slug_to_product_key($slug);`

## dept_raw_bags_on_hand {#dept_raw_bags_on_hand}

**Tables:** `stock_entries`, `stocks`

**Callers (10):**

- `includes/dept_inventory.php`:237 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:286 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:352 — `$onHand = dept_raw_bags_on_hand($pdo, $productKey);`
- `includes/dept_inventory.php`:375 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_inventory.php`:421 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),`
- `includes/dept_production.php`:575 — `$onHand = dept_raw_bags_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:593 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:618 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:710 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'brc'),`
- `includes/dept_production.php`:762 — `'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'jar_cacao'),`

## dept_raw_recent_entries {#dept_raw_recent_entries}

**Tables:** `stock_entries`, `stocks`, `suppliers`

**Callers (1):**

- `includes/dept_inventory.php`:240 — `'entries' => dept_raw_recent_entries($pdo, $productKey, 25),`

## dept_raw_material_snapshot {#dept_raw_material_snapshot}

**Callers (1):**

- `includes/dept_production.php`:718 — `$snap = dept_raw_material_snapshot($pdo, $slug);`

## dept_raw_stock_in_bags {#dept_raw_stock_in_bags}

**Tables:** `stock_entries`

**Callers (2):**

- `api/v1/honey.php`:586 — `$result = dept_raw_stock_in_bags(`
- `includes/dept_inventory.php`:125 — `return dept_raw_stock_in_bags($pdo, $userId, $productKey, $def, $qtyBags, $arrivalDate, $note, $supplierId);`

## dept_raw_deduct_bags_fifo {#dept_raw_deduct_bags_fifo}

**Tables:** `stock_entries`, `stocks`

**Callers (3):**

- `includes/dept_inventory.php`:370 — `dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);`
- `includes/dept_inventory.php`:401 — `dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $qtyBags, $label);`
- `includes/dept_production.php`:337 — `$alloc = dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, (float) $line['qty'], $label);`

## dept_raw_stock_out_bags {#dept_raw_stock_out_bags}

**Callers (1):**

- `api/v1/honey.php`:621 — `$result = dept_raw_stock_out_bags(`

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

**Callers (2):**

- `includes/dept_pos.php`:119 — `dept_inventory_deduct($pdo, $line['product_key'], (float) $line['qty']);`
- `includes/honey_parcel_inventory.php`:78 — `$deducted = dept_inventory_deduct($pdo, $productKey, (float) $qty);`

## dept_inventory_resolve_other_item {#dept_inventory_resolve_other_item}

**Callers (2):**

- `includes/honey_parcel_inventory.php`:30 — `$key = dept_inventory_resolve_other_item((string) ($other['label'] ?? '')) ?? '';`
- `includes/parcel_pack_costing.php`:50 — `$key = dept_inventory_resolve_other_item((string) ($item['label'] ?? '')) ?? '';`

## dept_inventory_catalog_snapshot {#dept_inventory_catalog_snapshot}

**Tables:** `products`

**Callers (4):**

- `api/v1/honey.php`:479 — `$catalog = dept_inventory_catalog_snapshot(`
- `api/v1/honey.php`:518 — `api_ok(['products' => dept_inventory_catalog_snapshot($pdo, $deptKey !== '' ? $deptKey : null)]);`
- `includes/dept_pos.php`:12 — `foreach (dept_inventory_catalog_snapshot($pdo, $deptKey) as $item) {`
- `includes/dept_pos.php`:61 — `$snapshot = dept_inventory_catalog_snapshot($pdo);`

