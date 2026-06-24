---
title: honey_pricing
source: includes/honey_pricing.php
generated: 2026-06-24T05:31:05+02:00
type: module
function_count: 19
---


# honey_pricing

> Source: `includes/honey_pricing.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/honey_pos.php`
- `includes/honey_volume.php`
- `api/v1/_costing_helpers.php`

## honey_pricing_variant_for_stock {#honey_pricing_variant_for_stock}

**Callers (2):**

- `includes/honey_pricing.php`:30 — `$variant = honey_pricing_variant_for_stock($variant);`
- `includes/honey_pricing.php`:264 — `$variant = honey_pricing_variant_for_stock((string) ($row['variant'] ?? ''));`

## honey_pricing_stock_name {#honey_pricing_stock_name}

**Callers (1):**

- `includes/honey_pricing.php`:145 — `$stockName = honey_pricing_stock_name($variant);`

## honey_pricing_resolve_active_price {#honey_pricing_resolve_active_price}

**Tables:** `supplier_honey_prices`

**Callers (2):**

- `includes/honey_pricing.php`:87 — `$priceRow = honey_pricing_resolve_active_price($pdo, $supplierId, $variant, $arrival);`
- `includes/honey_pricing.php`:140 — `$priceRow = honey_pricing_resolve_active_price($pdo, $supplierId, $variant, $asOf);`

## honey_pricing_apply_row_cost {#honey_pricing_apply_row_cost}

**Tables:** `honey_warehouse_rows`

**Callers (3):**

- `includes/honey_pricing.php`:90 — `honey_pricing_apply_row_cost($pdo, $warehouseRowId, null, null, false);`
- `includes/honey_pricing.php`:97 — `honey_pricing_apply_row_cost($pdo, $warehouseRowId, $unit, $priceId, false);`
- `includes/honey_pricing.php`:349 — `honey_pricing_apply_row_cost($pdo, $warehouseRowId, $unitCost, null, true);`

## honey_pricing_snapshot_warehouse_row {#honey_pricing_snapshot_warehouse_row}

**Callers (2):**

- `includes/honey_inventory.php`:169 — `$pricing = honey_pricing_snapshot_warehouse_row($pdo, $wid);`
- `includes/honey_pricing.php`:309 — `$result = honey_pricing_snapshot_warehouse_row($pdo, (int) $wid);`

## honey_pricing_stocks_has_supplier_id {#honey_pricing_stocks_has_supplier_id}

**Callers (1):**

- `includes/honey_pricing.php`:125 — `if ($supplierId < 1 || !honey_pricing_stocks_has_supplier_id($pdo)) {`

## honey_pricing_upsert_supplier_costing_stocks {#honey_pricing_upsert_supplier_costing_stocks}

**Tables:** `stocks`

**Callers (4):**

- `includes/honey_inventory.php`:187 — `honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);`
- `includes/honey_pricing.php`:211 — `$variants = honey_pricing_upsert_supplier_costing_stocks($pdo, (int) $supplierId, $userId);`
- `includes/honey_pricing.php`:293 — `honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);`
- `includes/honey_pricing.php`:318 — `honey_pricing_upsert_supplier_costing_stocks($pdo, $supplierId, $userId);`

## honey_pricing_sync_variant_stock_cost {#honey_pricing_sync_variant_stock_cost}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_pricing_recalc_products_using_stock {#honey_pricing_recalc_products_using_stock}

**Callers (1):**

- `includes/honey_pricing.php`:170 — `honey_pricing_recalc_products_using_stock($pdo, $stockId, 0.0, $pricePerLiter, $userId);`

## honey_pricing_sync_all_variant_stocks {#honey_pricing_sync_all_variant_stocks}

**Tables:** `suppliers`

**Callers (1):**

- `api/v1/honey.php`:1085 — `$synced = honey_pricing_sync_all_variant_stocks(ims_pdo(), (int) $user['id']);`

## honey_pricing_list_supplier_prices {#honey_pricing_list_supplier_prices}

**Tables:** `supplier_honey_prices`, `suppliers`

**Callers (1):**

- `api/v1/honey.php`:978 — `$prices = honey_pricing_list_supplier_prices($pdo, $supplierId > 0 ? $supplierId : null);`

## honey_pricing_save_supplier_prices {#honey_pricing_save_supplier_prices}

**Tables:** `supplier_honey_prices`

**Callers (1):**

- `api/v1/honey.php`:1023 — `$saved = honey_pricing_save_supplier_prices($pdo, $supplierId, $variants, $effectiveFrom, (int) $user['id']);`

## honey_pricing_reprice_pending_for_supplier {#honey_pricing_reprice_pending_for_supplier}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:1024 — `$repriced = honey_pricing_reprice_pending_for_supplier($pdo, $supplierId, (int) $user['id']);`

## honey_pricing_pending_warehouse_rows {#honey_pricing_pending_warehouse_rows}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:1046 — `'rows' => honey_pricing_pending_warehouse_rows($pdo),`

## honey_pricing_set_warehouse_row_cost {#honey_pricing_set_warehouse_row_cost}

**Callers (1):**

- `api/v1/honey.php`:1067 — `$row = honey_pricing_set_warehouse_row_cost(ims_pdo(), $warehouseRowId, (float) $raw, (int) $user['id']);`

## honey_pricing_serialize_warehouse_row {#honey_pricing_serialize_warehouse_row}

**Callers (1):**

- `includes/honey_pricing.php`:351 — `return honey_pricing_serialize_warehouse_row($pdo, $warehouseRowId);`

## honey_pricing_enrich_warehouse_row {#honey_pricing_enrich_warehouse_row}

**Callers (2):**

- `api/v1/honey.php`:174 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`
- `includes/honey_pricing.php`:362 — `return honey_pricing_enrich_warehouse_row($wh);`

## honey_pricing_role_can_view_cost {#honey_pricing_role_can_view_cost}

**Callers (1):**

- `api/v1/honey.php`:180 — `'can_view_cost' => honey_pricing_role_can_view_cost($role),`

## honey_pricing_pending_count {#honey_pricing_pending_count}

**Tables:** `honey_warehouse_rows`

**Callers (5):**

- `api/v1/honey.php`:181 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `api/v1/honey.php`:1006 — `'pending_count' => honey_pricing_pending_count($pdo),`
- `api/v1/honey.php`:1036 — `'pending_count' => honey_pricing_pending_count($pdo),`
- `api/v1/honey.php`:1047 — `'pending_count' => honey_pricing_pending_count($pdo),`
- `api/v1/honey.php`:1074 — `'pending_count' => honey_pricing_pending_count(ims_pdo()),`

