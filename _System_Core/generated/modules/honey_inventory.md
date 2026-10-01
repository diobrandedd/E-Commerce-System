---
title: honey_inventory
source: includes/honey_inventory.php
generated: 2026-09-30T09:42:10+02:00
type: module
function_count: 70
---


# honey_inventory

> Source: `includes/honey_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/suppliers.php`
- `includes/honey_volume.php`
- `includes/catalog_bootstrap.php`
- `includes/material_inventory.php`
- `includes/dept_production.php`
- `includes/inventory_edit_log.php`
- `includes/honey_pricing.php`
- `includes/honey_bottle_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/honey_bottle_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/honey_bottle_barcodes.php`

## honey_inventory_pricing {#honey_inventory_pricing}

**Callers (2):**

- `includes/honey_inventory.php`:824 — `honey_inventory_pricing();`
- `includes/honey_inventory.php`:842 — `honey_inventory_pricing();`

## honey_production_has_depleted_at {#honey_production_has_depleted_at}

**Tables:** `honey_production_rows`

**Callers (6):**

- `includes/honey_inventory.php`:56 — `if (!honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:81 — `$sql = honey_production_has_depleted_at($pdo)`
- `includes/honey_inventory.php`:1433 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1454 — `if (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1472 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:2233 — `if ($active === 1 && honey_production_has_depleted_at($pdo) && $cur <= 1e-9) {`

## honey_production_auto_deactivate_stale_empty {#honey_production_auto_deactivate_stale_empty}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:102 — `honey_production_auto_deactivate_stale_empty($pdo);`

## honey_production_auto_reactivate_with_stock {#honey_production_auto_reactivate_with_stock}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:101 — `honey_production_auto_reactivate_with_stock($pdo);`

## honey_production_sync_active_flags {#honey_production_sync_active_flags}

**Callers (2):**

- `includes/honey_inventory.php`:373 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:648 — `honey_production_sync_active_flags($pdo);`

## honey_role_can_view {#honey_role_can_view}

**Callers (1):**

- `api/v1/honey.php`:81 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`

## honey_role_can_mutate {#honey_role_can_mutate}

**Callers (2):**

- `api/v1/honey.php`:81 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`
- `api/v1/honey.php`:203 — `if (!honey_role_can_mutate($user)) {`

## honey_role_can_honey_ops {#honey_role_can_honey_ops}

**Callers (1):**

- `api/v1/honey.php`:218 — `if (!honey_role_can_honey_ops($user)) {`

## honey_role_can_dept_produce {#honey_role_can_dept_produce}

**Callers (1):**

- `api/v1/honey.php`:236 — `if (!honey_role_can_dept_produce($user, $productKey)) {`

## honey_role_can_executive_edit {#honey_role_can_executive_edit}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_dept_key_for_product_key {#honey_dept_key_for_product_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_supplier_code {#honey_supplier_code}

**Callers (9):**

- `includes/dept_pricing.php`:157 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_bottle_barcodes.php`:2787 — `$supplierCode = honey_supplier_code($supplierName, $bottlingDate);`
- `includes/honey_inventory.php`:796 — `$code = honey_supplier_code($supplierName, $arrivalDate);`
- `includes/honey_inventory.php`:1934 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:1974 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2154 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2525 — `? honey_supplier_code($newName, (string) $ctx['arrival_date'])`
- `includes/honey_pricing.php`:134 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_warehouse_barcodes.php`:55 — `$supplierCode = honey_supplier_code($supplierName, $arrivalDate);`

## honey_batch_no {#honey_batch_no}

**Callers (3):**

- `includes/honey_inventory.php`:1276 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:1373 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:2869 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`

## honey_normalize_variant {#honey_normalize_variant}

**Callers (62):**

- `api/v1/bottle_barcodes.php`:499 — `$variant = honey_normalize_variant((string) ($scanned['variant'] ?? ''));`
- `includes/finished_leak_damage.php`:141 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:244 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:295 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:314 — `if (honey_normalize_variant((string) $pk['variant']) !== $variant) {`
- `includes/honey_bottle_barcodes.php`:462 — `$variant = honey_normalize_variant((string) $pk['variant']);`
- `includes/honey_bottle_barcodes.php`:552 — `$shellVariant = honey_normalize_variant((string) $shell['variant']);`
- `includes/honey_bottle_barcodes.php`:1056 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1154 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1689 — `$variant = honey_normalize_variant((string) $sku['variant']);`
- `includes/honey_bottle_barcodes.php`:2011 — `$variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:2035 — `$variant = honey_normalize_variant((string) $item['variant']);`
- `includes/honey_bottle_barcodes.php`:2361 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2370 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2385 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2441 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2763 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:217 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:245 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:272 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:323 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:368 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:523 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:670 — `$v = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_inventory.php`:815 — `$variant = honey_normalize_variant((string) ($row['variant'] ?? ''));`
- _… and 37 more (run impact.php for full list)_

## honey_normalize_bottling_date {#honey_normalize_bottling_date}

**Callers (7):**

- `api/v1/honey.php`:560 — `$params[] = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:776 — `$bottlingDateFilter = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:2764 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:218 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:246 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:273 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:762 — `$date = honey_normalize_bottling_date($bottlingDate);`

## honey_unique_packaging_batch_no {#honey_unique_packaging_batch_no}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2797 — `$batchNo = honey_unique_packaging_batch_no($pdo, $supplierCode, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:1274 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:1371 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:2867 — `$batchNo = honey_unique_packaging_batch_no(`

## honey_fetch_packaging_by_batch_variant_date {#honey_fetch_packaging_by_batch_variant_date}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2772 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $manualCode, $variant, $bottlingDate);`
- `includes/honey_bottle_barcodes.php`:2791 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $supplierCode, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:278 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $batchNo, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:327 — `$existing = honey_fetch_packaging_by_batch_variant_date(`

## honey_ensure_packaging_row_for_stock_in {#honey_ensure_packaging_row_for_stock_in}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/sku_barcodes.php`:1007 — `$todayPk = honey_ensure_packaging_row_for_stock_in(`

## honey_packaging_is_pool_row {#honey_packaging_is_pool_row}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_packaging_ensure_variant_pool {#honey_packaging_ensure_variant_pool}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:1059 — `$poolPk = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:545 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:609 — `$pool = honey_packaging_ensure_variant_pool($pdo, 1, $variant);`
- `includes/inventory_check.php`:355 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`

## honey_production_fifo_deduct_gallons {#honey_production_fifo_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:505 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`
- `includes/honey_inventory.php`:547 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`

## honey_packaging_pool_stock_in {#honey_packaging_pool_stock_in}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:472 — `$stocked = honey_packaging_pool_stock_in(`

## honey_packaging_variant_pools_snapshot {#honey_packaging_variant_pools_snapshot}

**Callers (1):**

- `api/v1/honey.php`:579 — `api_ok(honey_packaging_variant_pools_snapshot(ims_pdo()));`

## honey_production_variant_summary {#honey_production_variant_summary}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:524 — `api_ok(honey_production_variant_summary(ims_pdo()));`

## honey_packaging_size_columns {#honey_packaging_size_columns}

**Callers (22):**

- `includes/finished_leak_damage.php`:149 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:214 — `[$curCol, $iniCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:248 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2529 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2633 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1745 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1761 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1854 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1953 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2266 — `[$curCol, $initCol] = honey_packaging_size_columns((string) $size);`
- `includes/honey_inventory.php`:2314 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2901 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_parcel_inventory.php`:139 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:144 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:164 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/inventory_check_apply.php`:223 — `[$curCol, $iniCol] = honey_packaging_size_columns($size);`
- `includes/rts_damaged_use.php`:294 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:442 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:608 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:725 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:1021 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/sku_barcodes.php`:1284 — `[$curCol] = honey_packaging_size_columns($size);`

## honey_fetch_warehouse {#honey_fetch_warehouse}

**Tables:** `honey_warehouse_rows`

**Callers (12):**

- `includes/bulk_honey_orders.php`:149 — `$wh = honey_fetch_warehouse($pdo, $wid);`
- `includes/honey_inventory.php`:1126 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:1185 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:1929 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:1964 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:2075 — `$wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;`
- `includes/honey_inventory.php`:2146 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2407 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2796 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:70 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:339 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:352 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`

## honey_fetch_production {#honey_fetch_production}

**Tables:** `honey_production_rows`

**Callers (10):**

- `includes/honey_inventory.php`:1233 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1351 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1519 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1621 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1843 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2074 — `$pr = $prodId > 0 ? honey_fetch_production($pdo, $prodId) : null;`
- `includes/honey_inventory.php`:2186 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2222 — `$fresh = honey_fetch_production($pdo, $rowId) ?: $pr;`
- `includes/honey_inventory.php`:2425 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2843 — `$pr = honey_fetch_production($pdo, $productionRowId);`

## honey_fetch_packaging {#honey_fetch_packaging}

**Tables:** `honey_packaging_rows`

**Callers (13):**

- `includes/honey_bottle_barcodes.php`:310 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:458 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:931 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:2734 — `$pack = honey_fetch_packaging($pdo, (int) $ensured['packaging_row_id']);`
- `includes/honey_bottle_barcodes.php`:2814 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:299 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:350 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:1625 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:1731 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:2019 — `$pk = honey_fetch_packaging($pdo, $packId);`
- `includes/honey_inventory.php`:2244 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/honey_inventory.php`:2442 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/sku_barcodes.php`:999 — `$sourcePk = honey_fetch_packaging($pdo, $packagingRowId);`

## honey_fetch_packaging_by_production {#honey_fetch_packaging_by_production}

**Tables:** `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:168 — `$before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1265 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1269 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1356 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, $bottlingDate);`
- `includes/honey_inventory.php`:1367 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`

## honey_warehouse_stock_in {#honey_warehouse_stock_in}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:285 — `$result = honey_warehouse_stock_in(`

## honey_warehouse_has_pricing_columns {#honey_warehouse_has_pricing_columns}

**Callers (2):**

- `api/v1/honey.php`:268 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `includes/honey_inventory.php`:798 — `$hasPricingCols = honey_warehouse_has_pricing_columns($pdo);`

## honey_warehouse_unit_cost_column {#honey_warehouse_unit_cost_column}

**Callers (4):**

- `api/v1/honey.php`:249 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:62 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:153 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/honey_inventory.php`:851 — `return honey_warehouse_unit_cost_column($pdo) !== null;`

## honey_warehouse_normalize_cost_row {#honey_warehouse_normalize_cost_row}

**Callers (3):**

- `api/v1/honey.php`:261 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`
- `includes/bulk_honey_orders.php`:76 — `? honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($row))`
- `includes/bulk_honey_orders.php`:157 — `honey_warehouse_normalize_cost_row($wh)`

## honey_production_daily_group_key {#honey_production_daily_group_key}

**Callers (1):**

- `includes/honey_inventory.php`:933 — `$lotKey = honey_production_daily_group_key(`

## honey_production_upsert_daily_lot {#honey_production_upsert_daily_lot}

**Tables:** `honey_production_rows`, `honey_packaging_rows`, `honey_warehouse_stock_out_events`

**Callers (2):**

- `includes/honey_inventory.php`:1150 — `$prodId = honey_production_upsert_daily_lot(`
- `includes/honey_warehouse_barcodes.php`:264 — `$productionId = honey_production_upsert_daily_lot(`

## honey_warehouse_stock_out {#honey_warehouse_stock_out}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:305 — `$result = honey_warehouse_stock_out(`

## honey_warehouse_bulk_deduct {#honey_warehouse_bulk_deduct}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/bulk_honey_orders.php`:526 — `honey_warehouse_bulk_deduct(`

## honey_warehouse_bulk_restore {#honey_warehouse_bulk_restore}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/bulk_honey_orders.php`:567 — `honey_warehouse_bulk_restore($pdo, (int) $a['warehouse_row_id'], (float) $a['qty_gallons']);`

## honey_production_stock_out {#honey_production_stock_out}

**Tables:** `honey_packaging_rows`, `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:535 — `$result = honey_production_stock_out(`

## honey_production_ensure_packaging_row {#honey_production_ensure_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:2733 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`
- `includes/honey_inventory.php`:1554 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`

## honey_production_deduct_gallons {#honey_production_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:415 — `honey_production_deduct_gallons($pdo, $lotId, $take);`
- `includes/honey_inventory.php`:1558 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`
- `includes/honey_inventory.php`:1646 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`

## honey_production_sync_depleted_at {#honey_production_sync_depleted_at}

**Tables:** `honey_production_rows`

**Callers (5):**

- `includes/honey_inventory.php`:1296 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1425 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1842 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:1879 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2215 — `honey_production_sync_depleted_at($pdo, $rowId);`

## honey_production_clear_depleted_and_activate {#honey_production_clear_depleted_and_activate}

**Tables:** `honey_production_rows`

**Callers (2):**

- `includes/honey_inventory.php`:1020 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`
- `includes/honey_inventory.php`:1098 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`

## honey_production_clear_depleted_at {#honey_production_clear_depleted_at}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:1845 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:1881 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2217 — `honey_production_clear_depleted_at($pdo, $rowId);`

## honey_production_refill_initial_qty {#honey_production_refill_initial_qty}

**Callers (2):**

- `includes/honey_inventory.php`:1009 — `honey_production_refill_initial_qty(`
- `includes/honey_inventory.php`:1088 — `honey_production_refill_initial_qty(`

## honey_production_bottle_stock_in {#honey_production_bottle_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_production_deduct_for_unique_stock_in {#honey_production_deduct_for_unique_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_bottling_deduct_packaging_stocks {#honey_bottling_deduct_packaging_stocks}

**Tables:** `departments`, `stocks`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:506 — `honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:549 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:1319 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1560 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1647 — `honey_bottling_deduct_packaging_stocks(`

## honey_packaging_stock_out_sold {#honey_packaging_stock_out_sold}

**Tables:** `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:590 — `$result = honey_packaging_stock_out_sold(`

## honey_apply_correction {#honey_apply_correction}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_apply_correction_request {#honey_apply_correction_request}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`, `honey_packaging_rows`, `dept_inventory_rows`

**Callers (2):**

- `api/v1/honey.php`:772 — `honey_apply_correction_request($pdo, $req);`
- `includes/honey_inventory.php`:1798 — `honey_apply_correction_request($pdo, [`

## honey_cascade_warehouse_metadata {#honey_cascade_warehouse_metadata}

**Tables:** `honey_production_rows`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_inventory.php`:1898 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`
- `includes/honey_inventory.php`:1938 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2159 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2165 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`

## honey_log_edit {#honey_log_edit}

**Callers (23):**

- `includes/honey_inventory.php`:2155 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId, $logSource, $editReason);`
- `includes/honey_inventory.php`:2163 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2173 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2175 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2192 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2208 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2210 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2230 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'is_active', $oldActive, (string) $active, $logSource, $editReason);`
- `includes/honey_inventory.php`:2253 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2275 — `honey_log_edit(`
- `includes/honey_inventory.php`:2289 — `honey_log_edit(`
- `includes/honey_inventory.php`:2321 — `honey_log_edit(`
- `includes/honey_inventory.php`:2335 — `honey_log_edit(`
- `includes/honey_inventory.php`:2372 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label, $logSource, $editReason);`
- `includes/honey_inventory.php`:2382 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2384 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2818 — `honey_log_edit($pdo, $userId, 'honey_production_rows', $id, 'seed_qty', null, (string) $qtyGallons, 'monthly_inventory');`
- `includes/honey_inventory.php`:2902 — `honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, 'seed_' . $curCol, null, (string) $normalized[$size], 'monthly_inventory');`
- `includes/inventory_check_apply.php`:255 — `honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, $curCol, (string) $old, (string) $new, 'inventory_check_apply');`
- `includes/inventory_check_apply.php`:274 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:294 — `honey_log_edit($pdo, $userId, 'honey_packaging_rows', $lastId, $curCol, '0', (string) $target, 'inventory_check_apply');`
- `includes/inventory_check_apply.php`:311 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:321 — `honey_log_edit(`

## honey_traceability {#honey_traceability}

**Tables:** `honey_packaging_rows`, `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:608 — `api_ok(honey_traceability(ims_pdo(), $q));`

## honey_build_trace_chain {#honey_build_trace_chain}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_inventory.php`:2021 — `$chain = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2036 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2050 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2064 — `$matches[] = honey_build_trace_chain($pdo, $pk);`

## honey_correction_row_options {#honey_correction_row_options}

**Tables:** `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:701 — `api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);`

## honey_executive_adjust {#honey_executive_adjust}

**Callers (1):**

- `api/v1/honey.php`:815 — `honey_executive_adjust(`

## honey_executive_qty_resets_baseline {#honey_executive_qty_resets_baseline}

**Callers (4):**

- `includes/honey_inventory.php`:2172 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2203 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2259 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2381 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`

## honey_executive_edit_row {#honey_executive_edit_row}

**Tables:** `dept_inventory_rows`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:845 — `honey_executive_edit_row(`
- `includes/honey_inventory.php`:2113 — `honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [`

## honey_correction_row_context {#honey_correction_row_context}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/honey_inventory.php`:2603 — `$ctx = honey_correction_row_context($pdo, $req);`

## honey_correction_change_preview {#honey_correction_change_preview}

**Callers (1):**

- `includes/honey_inventory.php`:2605 — `$req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);`

## honey_correction_enrich {#honey_correction_enrich}

**Callers (2):**

- `api/v1/honey.php`:691 — `honey_correction_enrich($pdo, $row);`
- `includes/honey_inventory.php`:2669 — `honey_correction_enrich($pdo, $r);`

## honey_correction_summary {#honey_correction_summary}

**Callers (2):**

- `includes/honey_inventory.php`:2500 — `'headline' => honey_correction_summary($req),`
- `includes/honey_inventory.php`:2606 — `$req['summary'] = honey_correction_summary($req);`

## honey_correction_unread_count {#honey_correction_unread_count}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:720 — `$count = honey_correction_unread_count($pdo);`

## honey_correction_unread_list {#honey_correction_unread_list}

**Tables:** `inventory_correction_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:738 — `$rows = honey_correction_unread_list(ims_pdo(), $limit);`

## honey_correction_mark_viewed {#honey_correction_mark_viewed}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:752 — `honey_correction_mark_viewed(ims_pdo(), $id);`

## honey_packaging_variant_inventory_breakdown {#honey_packaging_variant_inventory_breakdown}

**Callers (1):**

- `includes/honey_inventory.php`:2758 — `$agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);`

## honey_packaging_total_inventory_snapshot {#honey_packaging_total_inventory_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:572 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`
- `api/v1/online_orders.php`:493 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`

## honey_executive_seed_production_row {#honey_executive_seed_production_row}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:1460 — `$row = honey_executive_seed_production_row(`

## honey_executive_seed_packaging_row {#honey_executive_seed_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:1488 — `$row = honey_executive_seed_packaging_row(`

