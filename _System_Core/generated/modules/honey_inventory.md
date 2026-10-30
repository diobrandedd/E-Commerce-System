---
title: honey_inventory
source: includes/honey_inventory.php
generated: 2026-10-30T07:50:12+01:00
type: module
function_count: 76
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

- `includes/honey_inventory.php`:1106 — `honey_inventory_pricing();`
- `includes/honey_inventory.php`:1124 — `honey_inventory_pricing();`

## honey_production_has_depleted_at {#honey_production_has_depleted_at}

**Tables:** `honey_production_rows`

**Callers (8):**

- `includes/honey_inventory.php`:78 — `if (!honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:106 — `if (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:136 — `if (!honey_production_has_voided_at($pdo) || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:976 — `} elseif (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1715 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1737 — `if (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1754 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:2465 — `if ($active === 1 && honey_production_has_depleted_at($pdo) && $cur <= 1e-9) {`

## honey_production_has_voided_at {#honey_production_has_voided_at}

**Tables:** `honey_production_rows`

**Callers (12):**

- `includes/honey_inventory.php`:82 — `$voidPred = honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:109 — `if (honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:119 — `. (honey_production_has_voided_at($pdo) ? ' OR voided_at IS NOT NULL' : '')`
- `includes/honey_inventory.php`:136 — `if (!honey_production_has_voided_at($pdo) || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:436 — `$voidFilter = honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:511 — `. (honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '') . '`
- `includes/honey_inventory.php`:806 — `$voidFilter = honey_production_has_voided_at($pdo) ? ' AND pr.voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:828 — `if (honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:896 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:925 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:958 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:1740 — `if (honey_production_has_voided_at($pdo)) {`

## honey_production_auto_deactivate_stale_empty {#honey_production_auto_deactivate_stale_empty}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:161 — `honey_production_auto_deactivate_stale_empty($pdo);`

## honey_production_auto_reactivate_with_stock {#honey_production_auto_reactivate_with_stock}

**Tables:** `honey_production_rows`

**Callers (2):**

- `includes/honey_inventory.php`:160 — `honey_production_auto_reactivate_with_stock($pdo);`
- `includes/honey_inventory.php`:975 — `honey_production_auto_reactivate_with_stock($pdo);`

## honey_production_auto_void_stale_empty {#honey_production_auto_void_stale_empty}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:162 — `honey_production_auto_void_stale_empty($pdo);`

## honey_production_sync_active_flags {#honey_production_sync_active_flags}

**Callers (3):**

- `includes/honey_inventory.php`:433 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:804 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:895 — `honey_production_sync_active_flags($pdo);`

## honey_role_can_view {#honey_role_can_view}

**Callers (1):**

- `api/v1/honey.php`:88 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`

## honey_role_can_mutate {#honey_role_can_mutate}

**Callers (2):**

- `api/v1/honey.php`:88 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`
- `api/v1/honey.php`:210 — `if (!honey_role_can_mutate($user)) {`

## honey_role_can_honey_ops {#honey_role_can_honey_ops}

**Callers (1):**

- `api/v1/honey.php`:225 — `if (!honey_role_can_honey_ops($user)) {`

## honey_role_can_dept_produce {#honey_role_can_dept_produce}

**Callers (1):**

- `api/v1/honey.php`:243 — `if (!honey_role_can_dept_produce($user, $productKey)) {`

## honey_role_can_executive_edit {#honey_role_can_executive_edit}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_dept_key_for_product_key {#honey_dept_key_for_product_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_supplier_code {#honey_supplier_code}

**Callers (9):**

- `includes/dept_pricing.php`:157 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_bottle_barcodes.php`:2942 — `$supplierCode = honey_supplier_code($supplierName, $bottlingDate);`
- `includes/honey_inventory.php`:1078 — `$code = honey_supplier_code($supplierName, $arrivalDate);`
- `includes/honey_inventory.php`:2166 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2206 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2386 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2757 — `? honey_supplier_code($newName, (string) $ctx['arrival_date'])`
- `includes/honey_pricing.php`:140 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_warehouse_barcodes.php`:55 — `$supplierCode = honey_supplier_code($supplierName, $arrivalDate);`

## honey_batch_no {#honey_batch_no}

**Callers (3):**

- `includes/honey_inventory.php`:1558 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:1655 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:3101 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`

## honey_normalize_variant {#honey_normalize_variant}

**Callers (65):**

- `api/v1/bottle_barcodes.php`:499 — `$variant = honey_normalize_variant((string) ($scanned['variant'] ?? ''));`
- `includes/finished_leak_damage.php`:141 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:225 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:288 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:339 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:358 — `if (honey_normalize_variant((string) $pk['variant']) !== $variant) {`
- `includes/honey_bottle_barcodes.php`:506 — `$variant = honey_normalize_variant((string) $pk['variant']);`
- `includes/honey_bottle_barcodes.php`:596 — `$shellVariant = honey_normalize_variant((string) $shell['variant']);`
- `includes/honey_bottle_barcodes.php`:1130 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1228 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1831 — `$variant = honey_normalize_variant((string) $sku['variant']);`
- `includes/honey_bottle_barcodes.php`:2153 — `$variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:2177 — `$variant = honey_normalize_variant((string) $item['variant']);`
- `includes/honey_bottle_barcodes.php`:2516 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2525 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2540 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2596 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2918 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:277 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:305 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:332 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:383 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:428 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:585 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:837 — `$v = honey_normalize_variant((string) $row['variant']);`
- _… and 40 more (run impact.php for full list)_

## honey_normalize_bottling_date {#honey_normalize_bottling_date}

**Callers (7):**

- `api/v1/honey.php`:614 — `$params[] = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:820 — `$bottlingDateFilter = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:2919 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:278 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:306 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:333 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:1044 — `$date = honey_normalize_bottling_date($bottlingDate);`

## honey_unique_packaging_batch_no {#honey_unique_packaging_batch_no}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2952 — `$batchNo = honey_unique_packaging_batch_no($pdo, $supplierCode, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:1556 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:1653 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:3099 — `$batchNo = honey_unique_packaging_batch_no(`

## honey_fetch_packaging_by_batch_variant_date {#honey_fetch_packaging_by_batch_variant_date}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2927 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $manualCode, $variant, $bottlingDate);`
- `includes/honey_bottle_barcodes.php`:2946 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $supplierCode, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:338 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $batchNo, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:387 — `$existing = honey_fetch_packaging_by_batch_variant_date(`

## honey_ensure_packaging_row_for_stock_in {#honey_ensure_packaging_row_for_stock_in}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/sku_barcodes.php`:1028 — `$todayPk = honey_ensure_packaging_row_for_stock_in(`

## honey_packaging_is_pool_row {#honey_packaging_is_pool_row}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_packaging_ensure_variant_pool {#honey_packaging_ensure_variant_pool}

**Tables:** `honey_packaging_rows`

**Callers (6):**

- `includes/honey_bottle_barcodes.php`:229 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId > 0 ? $userId : 1, $variant);`
- `includes/honey_bottle_barcodes.php`:1133 — `$poolPk = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:607 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:671 — `$pool = honey_packaging_ensure_variant_pool($pdo, 1, $variant);`
- `includes/inventory_check.php`:541 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/inventory_check_apply.php`:224 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`

## honey_production_fifo_deduct_gallons {#honey_production_fifo_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:505 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`
- `includes/honey_inventory.php`:609 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`

## honey_packaging_pool_stock_in {#honey_packaging_pool_stock_in}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:472 — `$stocked = honey_packaging_pool_stock_in(`

## honey_packaging_variant_pools_snapshot {#honey_packaging_variant_pools_snapshot}

**Callers (1):**

- `api/v1/honey.php`:633 — `api_ok(honey_packaging_variant_pools_snapshot(ims_pdo()));`

## honey_production_lots_attach_stock_out_actors {#honey_production_lots_attach_stock_out_actors}

**Tables:** `honey_warehouse_stock_out_events`, `honey_production_rows`, `users`

**Callers (2):**

- `includes/honey_inventory.php`:853 — `$lots = honey_production_lots_attach_stock_out_actors($pdo, $byVariant[$variant]);`
- `includes/honey_inventory.php`:914 — `'lots' => honey_production_lots_attach_stock_out_actors($pdo, $st->fetchAll(PDO::FETCH_ASSOC) ?: []),`

## honey_production_variant_summary {#honey_production_variant_summary}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:531 — `api_ok(honey_production_variant_summary(ims_pdo()));`

## honey_production_voided_list {#honey_production_voided_list}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:540 — `api_ok(honey_production_voided_list(ims_pdo(), $variant));`

## honey_production_void_lot {#honey_production_void_lot}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:553 — `$row = honey_production_void_lot(`

## honey_production_unvoid_lot {#honey_production_unvoid_lot}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:571 — `$row = honey_production_unvoid_lot(`

## honey_packaging_size_columns {#honey_packaging_size_columns}

**Callers (22):**

- `includes/finished_leak_damage.php`:149 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:231 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:258 — `[$curCol, $iniCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:292 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2684 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2788 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1977 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1993 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2086 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2185 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2498 — `[$curCol, $initCol] = honey_packaging_size_columns((string) $size);`
- `includes/honey_inventory.php`:2546 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:3133 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_parcel_inventory.php`:139 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:144 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:164 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/inventory_check_apply.php`:223 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_damaged_use.php`:294 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:442 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:608 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:725 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:1021 — `[$curCol] = honey_packaging_size_columns($size);`

## honey_fetch_warehouse {#honey_fetch_warehouse}

**Tables:** `honey_warehouse_rows`

**Callers (12):**

- `includes/bulk_honey_orders.php`:149 — `$wh = honey_fetch_warehouse($pdo, $wid);`
- `includes/honey_inventory.php`:1408 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:1467 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:2161 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2196 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:2307 — `$wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;`
- `includes/honey_inventory.php`:2378 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2639 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:3028 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:76 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:345 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:358 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`

## honey_fetch_production {#honey_fetch_production}

**Tables:** `honey_production_rows`

**Callers (14):**

- `includes/honey_inventory.php`:928 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:943 — `$fresh = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:961 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:983 — `$fresh = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1515 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1633 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1801 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1903 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:2075 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2306 — `$pr = $prodId > 0 ? honey_fetch_production($pdo, $prodId) : null;`
- `includes/honey_inventory.php`:2418 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2454 — `$fresh = honey_fetch_production($pdo, $rowId) ?: $pr;`
- `includes/honey_inventory.php`:2657 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:3075 — `$pr = honey_fetch_production($pdo, $productionRowId);`

## honey_fetch_packaging {#honey_fetch_packaging}

**Tables:** `honey_packaging_rows`

**Callers (13):**

- `includes/honey_bottle_barcodes.php`:354 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:502 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:1005 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:2889 — `$pack = honey_fetch_packaging($pdo, (int) $ensured['packaging_row_id']);`
- `includes/honey_bottle_barcodes.php`:2969 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:359 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:410 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:1907 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:1963 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:2251 — `$pk = honey_fetch_packaging($pdo, $packId);`
- `includes/honey_inventory.php`:2476 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/honey_inventory.php`:2674 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/sku_barcodes.php`:1020 — `$sourcePk = honey_fetch_packaging($pdo, $packagingRowId);`

## honey_fetch_packaging_by_production {#honey_fetch_packaging_by_production}

**Tables:** `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:168 — `$before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1547 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1551 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1638 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, $bottlingDate);`
- `includes/honey_inventory.php`:1649 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`

## honey_warehouse_stock_in {#honey_warehouse_stock_in}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:292 — `$result = honey_warehouse_stock_in(`

## honey_warehouse_has_pricing_columns {#honey_warehouse_has_pricing_columns}

**Callers (2):**

- `api/v1/honey.php`:275 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `includes/honey_inventory.php`:1080 — `$hasPricingCols = honey_warehouse_has_pricing_columns($pdo);`

## honey_warehouse_unit_cost_column {#honey_warehouse_unit_cost_column}

**Callers (4):**

- `api/v1/honey.php`:256 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:62 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:153 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/honey_inventory.php`:1133 — `return honey_warehouse_unit_cost_column($pdo) !== null;`

## honey_warehouse_normalize_cost_row {#honey_warehouse_normalize_cost_row}

**Callers (3):**

- `api/v1/honey.php`:268 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`
- `includes/bulk_honey_orders.php`:76 — `? honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($row))`
- `includes/bulk_honey_orders.php`:157 — `honey_warehouse_normalize_cost_row($wh)`

## honey_production_daily_group_key {#honey_production_daily_group_key}

**Callers (1):**

- `includes/honey_inventory.php`:1215 — `$lotKey = honey_production_daily_group_key(`

## honey_production_upsert_daily_lot {#honey_production_upsert_daily_lot}

**Tables:** `honey_production_rows`, `honey_packaging_rows`, `honey_warehouse_stock_out_events`

**Callers (2):**

- `includes/honey_inventory.php`:1432 — `$prodId = honey_production_upsert_daily_lot(`
- `includes/honey_warehouse_barcodes.php`:264 — `$productionId = honey_production_upsert_daily_lot(`

## honey_warehouse_stock_out {#honey_warehouse_stock_out}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:312 — `$result = honey_warehouse_stock_out(`

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

- `api/v1/honey.php`:589 — `$result = honey_production_stock_out(`

## honey_production_ensure_packaging_row {#honey_production_ensure_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:2888 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`
- `includes/honey_inventory.php`:1836 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`

## honey_production_deduct_gallons {#honey_production_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:476 — `honey_production_deduct_gallons($pdo, $lotId, $take);`
- `includes/honey_inventory.php`:1840 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`
- `includes/honey_inventory.php`:1928 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`

## honey_production_sync_depleted_at {#honey_production_sync_depleted_at}

**Tables:** `honey_production_rows`

**Callers (5):**

- `includes/honey_inventory.php`:1578 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1707 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:2074 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2111 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2447 — `honey_production_sync_depleted_at($pdo, $rowId);`

## honey_production_clear_depleted_and_activate {#honey_production_clear_depleted_and_activate}

**Tables:** `honey_production_rows`

**Callers (2):**

- `includes/honey_inventory.php`:1302 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`
- `includes/honey_inventory.php`:1380 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`

## honey_production_clear_depleted_at {#honey_production_clear_depleted_at}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:2077 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2113 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2449 — `honey_production_clear_depleted_at($pdo, $rowId);`

## honey_production_refill_initial_qty {#honey_production_refill_initial_qty}

**Callers (2):**

- `includes/honey_inventory.php`:1291 — `honey_production_refill_initial_qty(`
- `includes/honey_inventory.php`:1370 — `honey_production_refill_initial_qty(`

## honey_production_bottle_stock_in {#honey_production_bottle_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_production_deduct_for_unique_stock_in {#honey_production_deduct_for_unique_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_bottling_deduct_packaging_stocks {#honey_bottling_deduct_packaging_stocks}

**Callers (5):**

- `api/v1/bottle_barcodes.php`:506 — `honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:611 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:1601 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1842 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1929 — `honey_bottling_deduct_packaging_stocks(`

## honey_packaging_stock_out_sold {#honey_packaging_stock_out_sold}

**Tables:** `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:644 — `$result = honey_packaging_stock_out_sold(`

## honey_apply_correction {#honey_apply_correction}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_apply_correction_request {#honey_apply_correction_request}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`, `honey_packaging_rows`, `dept_inventory_rows`

**Callers (2):**

- `api/v1/honey.php`:826 — `honey_apply_correction_request($pdo, $req);`
- `includes/honey_inventory.php`:2030 — `honey_apply_correction_request($pdo, [`

## honey_cascade_warehouse_metadata {#honey_cascade_warehouse_metadata}

**Tables:** `honey_production_rows`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_inventory.php`:2130 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`
- `includes/honey_inventory.php`:2170 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2391 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2397 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`

## honey_log_edit {#honey_log_edit}

**Callers (22):**

- `includes/honey_inventory.php`:2387 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId, $logSource, $editReason);`
- `includes/honey_inventory.php`:2395 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2405 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2407 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2424 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2440 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2442 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2462 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'is_active', $oldActive, (string) $active, $logSource, $editReason);`
- `includes/honey_inventory.php`:2485 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2507 — `honey_log_edit(`
- `includes/honey_inventory.php`:2521 — `honey_log_edit(`
- `includes/honey_inventory.php`:2553 — `honey_log_edit(`
- `includes/honey_inventory.php`:2567 — `honey_log_edit(`
- `includes/honey_inventory.php`:2604 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label, $logSource, $editReason);`
- `includes/honey_inventory.php`:2614 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2616 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:3050 — `honey_log_edit($pdo, $userId, 'honey_production_rows', $id, 'seed_qty', null, (string) $qtyGallons, 'monthly_inventory');`
- `includes/honey_inventory.php`:3134 — `honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, 'seed_' . $curCol, null, (string) $normalized[$size], 'monthly_inventory');`
- `includes/inventory_check_apply.php`:230 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:255 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:265 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:293 — `honey_log_edit(`

## honey_traceability {#honey_traceability}

**Tables:** `honey_packaging_rows`, `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:662 — `api_ok(honey_traceability(ims_pdo(), $q));`

## honey_build_trace_chain {#honey_build_trace_chain}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_inventory.php`:2253 — `$chain = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2268 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2282 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2296 — `$matches[] = honey_build_trace_chain($pdo, $pk);`

## honey_correction_row_options {#honey_correction_row_options}

**Tables:** `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:755 — `api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);`

## honey_executive_adjust {#honey_executive_adjust}

**Callers (1):**

- `api/v1/honey.php`:869 — `honey_executive_adjust(`

## honey_executive_qty_resets_baseline {#honey_executive_qty_resets_baseline}

**Callers (4):**

- `includes/honey_inventory.php`:2404 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2435 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2491 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2613 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`

## honey_executive_edit_row {#honey_executive_edit_row}

**Tables:** `dept_inventory_rows`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:899 — `honey_executive_edit_row(`
- `includes/honey_inventory.php`:2345 — `honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [`

## honey_correction_row_context {#honey_correction_row_context}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/honey_inventory.php`:2835 — `$ctx = honey_correction_row_context($pdo, $req);`

## honey_correction_change_preview {#honey_correction_change_preview}

**Callers (1):**

- `includes/honey_inventory.php`:2837 — `$req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);`

## honey_correction_enrich {#honey_correction_enrich}

**Callers (2):**

- `api/v1/honey.php`:745 — `honey_correction_enrich($pdo, $row);`
- `includes/honey_inventory.php`:2901 — `honey_correction_enrich($pdo, $r);`

## honey_correction_summary {#honey_correction_summary}

**Callers (2):**

- `includes/honey_inventory.php`:2732 — `'headline' => honey_correction_summary($req),`
- `includes/honey_inventory.php`:2838 — `$req['summary'] = honey_correction_summary($req);`

## honey_correction_unread_count {#honey_correction_unread_count}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:774 — `$count = honey_correction_unread_count($pdo);`

## honey_correction_unread_list {#honey_correction_unread_list}

**Tables:** `inventory_correction_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:792 — `$rows = honey_correction_unread_list(ims_pdo(), $limit);`

## honey_correction_mark_viewed {#honey_correction_mark_viewed}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:806 — `honey_correction_mark_viewed(ims_pdo(), $id);`

## honey_packaging_variant_inventory_breakdown {#honey_packaging_variant_inventory_breakdown}

**Callers (1):**

- `includes/honey_inventory.php`:2990 — `$agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);`

## honey_packaging_total_inventory_snapshot {#honey_packaging_total_inventory_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:626 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`
- `api/v1/online_orders.php`:493 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`

## honey_executive_seed_production_row {#honey_executive_seed_production_row}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:1587 — `$row = honey_executive_seed_production_row(`

## honey_executive_seed_packaging_row {#honey_executive_seed_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:1615 — `$row = honey_executive_seed_packaging_row(`

