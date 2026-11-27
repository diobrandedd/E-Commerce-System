---
title: honey_inventory
source: includes/honey_inventory.php
generated: 2026-11-27T06:40:45+01:00
type: module
function_count: 75
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

- `includes/honey_inventory.php`:1173 — `honey_inventory_pricing();`
- `includes/honey_inventory.php`:1191 — `honey_inventory_pricing();`

## honey_production_has_depleted_at {#honey_production_has_depleted_at}

**Tables:** `honey_production_rows`

**Callers (8):**

- `includes/honey_inventory.php`:78 — `if (!honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:106 — `if (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:136 — `if (!honey_production_has_voided_at($pdo) || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1043 — `} elseif (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1782 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1804 — `if (honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:1821 — `if ($productionRowId < 1 || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:2532 — `if ($active === 1 && honey_production_has_depleted_at($pdo) && $cur <= 1e-9) {`

## honey_production_has_voided_at {#honey_production_has_voided_at}

**Tables:** `honey_production_rows`

**Callers (13):**

- `includes/honey_inventory.php`:82 — `$voidPred = honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:109 — `if (honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:119 — `. (honey_production_has_voided_at($pdo) ? ' OR voided_at IS NOT NULL' : '')`
- `includes/honey_inventory.php`:136 — `if (!honey_production_has_voided_at($pdo) || !honey_production_has_depleted_at($pdo)) {`
- `includes/honey_inventory.php`:436 — `$voidFilter = honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:511 — `. (honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '') . '`
- `includes/honey_inventory.php`:586 — `$voidFilter = honey_production_has_voided_at($pdo) ? ' AND voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:873 — `$voidFilter = honey_production_has_voided_at($pdo) ? ' AND pr.voided_at IS NULL' : '';`
- `includes/honey_inventory.php`:895 — `if (honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:963 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:992 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:1025 — `if (!honey_production_has_voided_at($pdo)) {`
- `includes/honey_inventory.php`:1807 — `if (honey_production_has_voided_at($pdo)) {`

## honey_production_auto_deactivate_stale_empty {#honey_production_auto_deactivate_stale_empty}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:161 — `honey_production_auto_deactivate_stale_empty($pdo);`

## honey_production_auto_reactivate_with_stock {#honey_production_auto_reactivate_with_stock}

**Tables:** `honey_production_rows`

**Callers (2):**

- `includes/honey_inventory.php`:160 — `honey_production_auto_reactivate_with_stock($pdo);`
- `includes/honey_inventory.php`:1042 — `honey_production_auto_reactivate_with_stock($pdo);`

## honey_production_auto_void_stale_empty {#honey_production_auto_void_stale_empty}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_inventory.php`:162 — `honey_production_auto_void_stale_empty($pdo);`

## honey_production_sync_active_flags {#honey_production_sync_active_flags}

**Callers (4):**

- `includes/honey_inventory.php`:433 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:584 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:871 — `honey_production_sync_active_flags($pdo);`
- `includes/honey_inventory.php`:962 — `honey_production_sync_active_flags($pdo);`

## honey_role_can_view {#honey_role_can_view}

**Callers (1):**

- `api/v1/honey.php`:85 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`

## honey_role_can_mutate {#honey_role_can_mutate}

**Callers (2):**

- `api/v1/honey.php`:85 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`
- `api/v1/honey.php`:207 — `if (!honey_role_can_mutate($user)) {`

## honey_role_can_honey_ops {#honey_role_can_honey_ops}

**Callers (1):**

- `api/v1/honey.php`:222 — `if (!honey_role_can_honey_ops($user)) {`

## honey_role_can_dept_produce {#honey_role_can_dept_produce}

**Callers (1):**

- `api/v1/honey.php`:240 — `if (!honey_role_can_dept_produce($user, $productKey)) {`

## honey_role_can_executive_edit {#honey_role_can_executive_edit}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_dept_key_for_product_key {#honey_dept_key_for_product_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_supplier_code {#honey_supplier_code}

**Callers (9):**

- `includes/dept_pricing.php`:157 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_bottle_barcodes.php`:3477 — `$supplierCode = honey_supplier_code($supplierName, $bottlingDate);`
- `includes/honey_inventory.php`:1145 — `$code = honey_supplier_code($supplierName, $arrivalDate);`
- `includes/honey_inventory.php`:2233 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2273 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2453 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:2824 — `? honey_supplier_code($newName, (string) $ctx['arrival_date'])`
- `includes/honey_pricing.php`:140 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_warehouse_barcodes.php`:55 — `$supplierCode = honey_supplier_code($supplierName, $arrivalDate);`

## honey_batch_no {#honey_batch_no}

**Callers (2):**

- `includes/honey_inventory.php`:1625 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:1722 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`

## honey_normalize_variant {#honey_normalize_variant}

**Callers (68):**

- `api/v1/bottle_barcodes.php`:660 — `$variant = honey_normalize_variant((string) ($scanned['variant'] ?? ''));`
- `includes/finished_leak_damage.php`:141 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:232 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:295 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:346 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:365 — `if (honey_normalize_variant((string) $pk['variant']) !== $variant) {`
- `includes/honey_bottle_barcodes.php`:513 — `$variant = honey_normalize_variant((string) $pk['variant']);`
- `includes/honey_bottle_barcodes.php`:603 — `$shellVariant = honey_normalize_variant((string) $shell['variant']);`
- `includes/honey_bottle_barcodes.php`:1030 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:1393 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1491 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:2098 — `$variant = honey_normalize_variant((string) $sku['variant']);`
- `includes/honey_bottle_barcodes.php`:2420 — `$variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:2444 — `$variant = honey_normalize_variant((string) $item['variant']);`
- `includes/honey_bottle_barcodes.php`:2752 — `$variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:2793 — `$variant = honey_normalize_variant((string) ($item['variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:3051 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:3060 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:3075 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:3131 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:3453 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:277 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:305 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:332 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:383 — `$variant = honey_normalize_variant($variant);`
- _… and 43 more (run impact.php for full list)_

## honey_normalize_bottling_date {#honey_normalize_bottling_date}

**Callers (7):**

- `api/v1/honey.php`:606 — `$params[] = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:827 — `$bottlingDateFilter = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_bottle_barcodes.php`:3454 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:278 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:306 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:333 — `$bottlingDate = honey_normalize_bottling_date($bottlingDate);`
- `includes/honey_inventory.php`:1111 — `$date = honey_normalize_bottling_date($bottlingDate);`

## honey_unique_packaging_batch_no {#honey_unique_packaging_batch_no}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:3487 — `$batchNo = honey_unique_packaging_batch_no($pdo, $supplierCode, $variant, $bottlingDate);`
- `includes/honey_inventory.php`:1623 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:1720 — `$batchNo = honey_unique_packaging_batch_no(`

## honey_fetch_packaging_by_batch_variant_date {#honey_fetch_packaging_by_batch_variant_date}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:3462 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $manualCode, $variant, $bottlingDate);`
- `includes/honey_bottle_barcodes.php`:3481 — `$existing = honey_fetch_packaging_by_batch_variant_date($pdo, $supplierCode, $variant, $bottlingDate);`
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

**Callers (7):**

- `includes/honey_bottle_barcodes.php`:236 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId > 0 ? $userId : 1, $variant);`
- `includes/honey_bottle_barcodes.php`:1125 — `$pool = honey_packaging_ensure_variant_pool($pdo, $actorUserId, $variant);`
- `includes/honey_bottle_barcodes.php`:1396 — `$poolPk = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:674 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/honey_inventory.php`:738 — `$pool = honey_packaging_ensure_variant_pool($pdo, 1, $variant);`
- `includes/inventory_check.php`:616 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`
- `includes/inventory_check_apply.php`:227 — `$pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);`

## honey_production_fifo_deduct_gallons {#honey_production_fifo_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:666 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`
- `includes/honey_inventory.php`:676 — `$fifo = honey_production_fifo_deduct_gallons($pdo, $variant, $usedGallons);`

## honey_production_credit_gallons_for_variant {#honey_production_credit_gallons_for_variant}

**Tables:** `honey_production_rows`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1194 — `$credit = honey_production_credit_gallons_for_variant($pdo, $variant, $usedGallons);`

## honey_packaging_pool_stock_in {#honey_packaging_pool_stock_in}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:633 — `$stocked = honey_packaging_pool_stock_in(`

## honey_packaging_variant_pools_snapshot {#honey_packaging_variant_pools_snapshot}

**Callers (1):**

- `api/v1/honey.php`:625 — `api_ok(honey_packaging_variant_pools_snapshot(ims_pdo()));`

## honey_production_lots_attach_stock_out_actors {#honey_production_lots_attach_stock_out_actors}

**Tables:** `honey_warehouse_stock_out_events`, `honey_production_rows`, `users`

**Callers (2):**

- `includes/honey_inventory.php`:920 — `$lots = honey_production_lots_attach_stock_out_actors($pdo, $byVariant[$variant]);`
- `includes/honey_inventory.php`:981 — `'lots' => honey_production_lots_attach_stock_out_actors($pdo, $st->fetchAll(PDO::FETCH_ASSOC) ?: []),`

## honey_production_variant_summary {#honey_production_variant_summary}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:523 — `api_ok(honey_production_variant_summary(ims_pdo()));`

## honey_production_voided_list {#honey_production_voided_list}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:532 — `api_ok(honey_production_voided_list(ims_pdo(), $variant));`

## honey_production_void_lot {#honey_production_void_lot}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:545 — `$row = honey_production_void_lot(`

## honey_production_unvoid_lot {#honey_production_unvoid_lot}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:563 — `$row = honey_production_unvoid_lot(`

## honey_packaging_size_columns {#honey_packaging_size_columns}

**Callers (21):**

- `includes/finished_leak_damage.php`:149 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:238 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:265 — `[$curCol, $iniCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:299 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:3219 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:3323 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2044 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2060 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2153 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2252 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:2565 — `[$curCol, $initCol] = honey_packaging_size_columns((string) $size);`
- `includes/honey_inventory.php`:2613 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_parcel_inventory.php`:139 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:144 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:164 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/inventory_check_apply.php`:226 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_damaged_use.php`:294 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:442 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:608 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:725 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:1021 — `[$curCol] = honey_packaging_size_columns($size);`

## honey_fetch_warehouse {#honey_fetch_warehouse}

**Tables:** `honey_warehouse_rows`

**Callers (11):**

- `includes/bulk_honey_orders.php`:149 — `$wh = honey_fetch_warehouse($pdo, $wid);`
- `includes/honey_inventory.php`:1475 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:1534 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:2228 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2263 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:2374 — `$wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;`
- `includes/honey_inventory.php`:2445 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:2706 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_pricing.php`:76 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:345 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:358 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`

## honey_fetch_production {#honey_fetch_production}

**Tables:** `honey_production_rows`

**Callers (13):**

- `includes/honey_inventory.php`:995 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1010 — `$fresh = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1028 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1050 — `$fresh = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1582 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1700 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1868 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1970 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:2142 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2373 — `$pr = $prodId > 0 ? honey_fetch_production($pdo, $prodId) : null;`
- `includes/honey_inventory.php`:2485 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:2521 — `$fresh = honey_fetch_production($pdo, $rowId) ?: $pr;`
- `includes/honey_inventory.php`:2724 — `$pr = honey_fetch_production($pdo, $rowId);`

## honey_fetch_packaging {#honey_fetch_packaging}

**Tables:** `honey_packaging_rows`

**Callers (13):**

- `includes/honey_bottle_barcodes.php`:361 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:509 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:1268 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:3424 — `$pack = honey_fetch_packaging($pdo, (int) $ensured['packaging_row_id']);`
- `includes/honey_bottle_barcodes.php`:3504 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:359 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:410 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:1974 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:2030 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:2318 — `$pk = honey_fetch_packaging($pdo, $packId);`
- `includes/honey_inventory.php`:2543 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/honey_inventory.php`:2741 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/sku_barcodes.php`:1020 — `$sourcePk = honey_fetch_packaging($pdo, $packagingRowId);`

## honey_fetch_packaging_by_production {#honey_fetch_packaging_by_production}

**Tables:** `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:329 — `$before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1614 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, date('Y-m-d'));`
- `includes/honey_inventory.php`:1618 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1705 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId, $bottlingDate);`
- `includes/honey_inventory.php`:1716 — `$prior = honey_fetch_packaging_by_production($pdo, $productionRowId);`

## honey_warehouse_stock_in {#honey_warehouse_stock_in}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:284 — `$result = honey_warehouse_stock_in(`

## honey_warehouse_has_pricing_columns {#honey_warehouse_has_pricing_columns}

**Callers (2):**

- `api/v1/honey.php`:272 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `includes/honey_inventory.php`:1147 — `$hasPricingCols = honey_warehouse_has_pricing_columns($pdo);`

## honey_warehouse_unit_cost_column {#honey_warehouse_unit_cost_column}

**Callers (4):**

- `api/v1/honey.php`:253 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:62 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:153 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/honey_inventory.php`:1200 — `return honey_warehouse_unit_cost_column($pdo) !== null;`

## honey_warehouse_normalize_cost_row {#honey_warehouse_normalize_cost_row}

**Callers (3):**

- `api/v1/honey.php`:265 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`
- `includes/bulk_honey_orders.php`:76 — `? honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($row))`
- `includes/bulk_honey_orders.php`:157 — `honey_warehouse_normalize_cost_row($wh)`

## honey_production_daily_group_key {#honey_production_daily_group_key}

**Callers (1):**

- `includes/honey_inventory.php`:1282 — `$lotKey = honey_production_daily_group_key(`

## honey_production_upsert_daily_lot {#honey_production_upsert_daily_lot}

**Tables:** `honey_production_rows`, `honey_packaging_rows`, `honey_warehouse_stock_out_events`

**Callers (2):**

- `includes/honey_inventory.php`:1499 — `$prodId = honey_production_upsert_daily_lot(`
- `includes/honey_warehouse_barcodes.php`:264 — `$productionId = honey_production_upsert_daily_lot(`

## honey_warehouse_stock_out {#honey_warehouse_stock_out}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:304 — `$result = honey_warehouse_stock_out(`

## honey_warehouse_bulk_deduct {#honey_warehouse_bulk_deduct}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/bulk_honey_orders.php`:526 — `honey_warehouse_bulk_deduct(`

## honey_warehouse_bulk_restore {#honey_warehouse_bulk_restore}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `includes/bulk_honey_orders.php`:587 — `honey_warehouse_bulk_restore($pdo, (int) $a['warehouse_row_id'], (float) $a['qty_gallons']);`

## honey_production_stock_out {#honey_production_stock_out}

**Tables:** `honey_packaging_rows`, `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:581 — `$result = honey_production_stock_out(`

## honey_production_ensure_packaging_row {#honey_production_ensure_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:3423 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`
- `includes/honey_inventory.php`:1903 — `$ensured = honey_production_ensure_packaging_row($pdo, $userId, $productionRowId);`

## honey_production_deduct_gallons {#honey_production_deduct_gallons}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:476 — `honey_production_deduct_gallons($pdo, $lotId, $take);`
- `includes/honey_inventory.php`:1907 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`
- `includes/honey_inventory.php`:1995 — `honey_production_deduct_gallons($pdo, $productionRowId, $usedGallons);`

## honey_production_sync_depleted_at {#honey_production_sync_depleted_at}

**Tables:** `honey_production_rows`

**Callers (5):**

- `includes/honey_inventory.php`:1645 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:1774 — `honey_production_sync_depleted_at($pdo, $productionRowId);`
- `includes/honey_inventory.php`:2141 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2178 — `honey_production_sync_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2514 — `honey_production_sync_depleted_at($pdo, $rowId);`

## honey_production_clear_depleted_and_activate {#honey_production_clear_depleted_and_activate}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:620 — `honey_production_clear_depleted_and_activate($pdo, $lotId);`
- `includes/honey_inventory.php`:1369 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`
- `includes/honey_inventory.php`:1447 — `honey_production_clear_depleted_and_activate($pdo, $productionId);`

## honey_production_clear_depleted_at {#honey_production_clear_depleted_at}

**Tables:** `honey_production_rows`

**Callers (3):**

- `includes/honey_inventory.php`:2144 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2180 — `honey_production_clear_depleted_at($pdo, $rowId);`
- `includes/honey_inventory.php`:2516 — `honey_production_clear_depleted_at($pdo, $rowId);`

## honey_production_refill_initial_qty {#honey_production_refill_initial_qty}

**Callers (3):**

- `includes/honey_inventory.php`:612 — `$newInitial = honey_production_refill_initial_qty($priorCurrent, $priorInitial, $gallons);`
- `includes/honey_inventory.php`:1358 — `honey_production_refill_initial_qty(`
- `includes/honey_inventory.php`:1437 — `honey_production_refill_initial_qty(`

## honey_production_bottle_stock_in {#honey_production_bottle_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_production_deduct_for_unique_stock_in {#honey_production_deduct_for_unique_stock_in}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_bottling_deduct_packaging_stocks {#honey_bottling_deduct_packaging_stocks}

**Callers (5):**

- `api/v1/bottle_barcodes.php`:667 — `honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:678 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks(`
- `includes/honey_inventory.php`:1668 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1909 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`
- `includes/honey_inventory.php`:1996 — `honey_bottling_deduct_packaging_stocks(`

## honey_packaging_stock_out_sold {#honey_packaging_stock_out_sold}

**Tables:** `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:636 — `$result = honey_packaging_stock_out_sold(`

## honey_apply_correction {#honey_apply_correction}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_apply_correction_request {#honey_apply_correction_request}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`, `honey_packaging_rows`, `dept_inventory_rows`

**Callers (2):**

- `api/v1/honey.php`:818 — `honey_apply_correction_request($pdo, $req);`
- `includes/honey_inventory.php`:2097 — `honey_apply_correction_request($pdo, [`

## honey_cascade_warehouse_metadata {#honey_cascade_warehouse_metadata}

**Tables:** `honey_production_rows`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_inventory.php`:2197 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`
- `includes/honey_inventory.php`:2237 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2458 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:2464 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`

## honey_log_edit {#honey_log_edit}

**Callers (20):**

- `includes/honey_inventory.php`:2454 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId, $logSource, $editReason);`
- `includes/honey_inventory.php`:2462 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2472 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2474 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2491 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2507 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2509 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2529 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'is_active', $oldActive, (string) $active, $logSource, $editReason);`
- `includes/honey_inventory.php`:2552 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant, $logSource, $editReason);`
- `includes/honey_inventory.php`:2574 — `honey_log_edit(`
- `includes/honey_inventory.php`:2588 — `honey_log_edit(`
- `includes/honey_inventory.php`:2620 — `honey_log_edit(`
- `includes/honey_inventory.php`:2634 — `honey_log_edit(`
- `includes/honey_inventory.php`:2671 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label, $logSource, $editReason);`
- `includes/honey_inventory.php`:2681 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty, $logSource, $editReason);`
- `includes/honey_inventory.php`:2683 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty, $logSource, $editReason);`
- `includes/inventory_check_apply.php`:233 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:258 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:268 — `honey_log_edit(`
- `includes/inventory_check_apply.php`:296 — `honey_log_edit(`

## honey_traceability {#honey_traceability}

**Tables:** `honey_packaging_rows`, `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:654 — `api_ok(honey_traceability(ims_pdo(), $q));`

## honey_build_trace_chain {#honey_build_trace_chain}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_inventory.php`:2320 — `$chain = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2335 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2349 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:2363 — `$matches[] = honey_build_trace_chain($pdo, $pk);`

## honey_correction_row_options {#honey_correction_row_options}

**Tables:** `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:747 — `api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);`

## honey_executive_adjust {#honey_executive_adjust}

**Callers (1):**

- `api/v1/honey.php`:861 — `honey_executive_adjust(`

## honey_executive_qty_resets_baseline {#honey_executive_qty_resets_baseline}

**Callers (4):**

- `includes/honey_inventory.php`:2471 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2502 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2558 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:2680 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`

## honey_executive_edit_row {#honey_executive_edit_row}

**Tables:** `dept_inventory_rows`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:885 — `honey_executive_edit_row(`
- `includes/honey_inventory.php`:2412 — `honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [`

## honey_correction_row_context {#honey_correction_row_context}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/honey_inventory.php`:2902 — `$ctx = honey_correction_row_context($pdo, $req);`

## honey_correction_change_preview {#honey_correction_change_preview}

**Callers (1):**

- `includes/honey_inventory.php`:2904 — `$req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);`

## honey_correction_enrich {#honey_correction_enrich}

**Callers (2):**

- `api/v1/honey.php`:737 — `honey_correction_enrich($pdo, $row);`
- `includes/honey_inventory.php`:2968 — `honey_correction_enrich($pdo, $r);`

## honey_correction_summary {#honey_correction_summary}

**Callers (2):**

- `includes/honey_inventory.php`:2799 — `'headline' => honey_correction_summary($req),`
- `includes/honey_inventory.php`:2905 — `$req['summary'] = honey_correction_summary($req);`

## honey_correction_unread_count {#honey_correction_unread_count}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:766 — `$count = honey_correction_unread_count($pdo);`

## honey_correction_unread_list {#honey_correction_unread_list}

**Tables:** `inventory_correction_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:784 — `$rows = honey_correction_unread_list(ims_pdo(), $limit);`

## honey_correction_mark_viewed {#honey_correction_mark_viewed}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:798 — `honey_correction_mark_viewed(ims_pdo(), $id);`

## honey_packaging_variant_inventory_breakdown {#honey_packaging_variant_inventory_breakdown}

**Callers (1):**

- `includes/honey_inventory.php`:3057 — `$agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);`

## honey_packaging_total_inventory_snapshot {#honey_packaging_total_inventory_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:618 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`
- `api/v1/online_orders.php`:493 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`

