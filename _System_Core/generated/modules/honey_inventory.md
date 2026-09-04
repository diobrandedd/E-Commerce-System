---
title: honey_inventory
source: includes/honey_inventory.php
generated: 2026-09-04T05:33:57+02:00
type: module
function_count: 47
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

## honey_inventory_pricing {#honey_inventory_pricing}

**Callers (2):**

- `includes/honey_inventory.php`:225 — `honey_inventory_pricing();`
- `includes/honey_inventory.php`:243 — `honey_inventory_pricing();`

## honey_role_can_view {#honey_role_can_view}

**Callers (1):**

- `api/v1/honey.php`:77 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`

## honey_role_can_mutate {#honey_role_can_mutate}

**Callers (2):**

- `api/v1/honey.php`:77 — `if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {`
- `api/v1/honey.php`:166 — `if (!honey_role_can_mutate($user)) {`

## honey_role_can_honey_ops {#honey_role_can_honey_ops}

**Callers (1):**

- `api/v1/honey.php`:183 — `if (!honey_role_can_honey_ops($user)) {`

## honey_role_can_dept_produce {#honey_role_can_dept_produce}

**Callers (1):**

- `api/v1/honey.php`:209 — `if (!honey_role_can_dept_produce($user, $productKey)) {`

## honey_role_can_executive_edit {#honey_role_can_executive_edit}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_dept_key_for_product_key {#honey_dept_key_for_product_key}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_supplier_code {#honey_supplier_code}

**Callers (9):**

- `includes/dept_pricing.php`:157 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_bottle_barcodes.php`:2615 — `$supplierCode = honey_supplier_code($supplierName, $bottlingDate);`
- `includes/honey_inventory.php`:197 — `$code = honey_supplier_code($supplierName, $arrivalDate);`
- `includes/honey_inventory.php`:732 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:772 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:938 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:1207 — `? honey_supplier_code($newName, (string) $ctx['arrival_date'])`
- `includes/honey_pricing.php`:134 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_warehouse_barcodes.php`:55 — `$supplierCode = honey_supplier_code($supplierName, $arrivalDate);`

## honey_batch_no {#honey_batch_no}

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:2537 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:420 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`
- `includes/honey_inventory.php`:1551 — `honey_batch_no((string) $pr['supplier_code'], $bottlingDate),`

## honey_normalize_variant {#honey_normalize_variant}

**Callers (43):**

- `includes/honey_bottle_barcodes.php`:235 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:286 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_bottle_barcodes.php`:305 — `if (honey_normalize_variant((string) $pk['variant']) !== $variant) {`
- `includes/honey_bottle_barcodes.php`:453 — `$variant = honey_normalize_variant((string) $pk['variant']);`
- `includes/honey_bottle_barcodes.php`:543 — `$shellVariant = honey_normalize_variant((string) $shell['variant']);`
- `includes/honey_bottle_barcodes.php`:998 — `honey_normalize_variant((string) $row['variant']),`
- `includes/honey_bottle_barcodes.php`:1077 — `$variant = honey_normalize_variant((string) $row['variant']);`
- `includes/honey_bottle_barcodes.php`:1548 — `$variant = honey_normalize_variant((string) $sku['variant']);`
- `includes/honey_bottle_barcodes.php`:1870 — `$variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:1894 — `$variant = honey_normalize_variant((string) $item['variant']);`
- `includes/honey_bottle_barcodes.php`:2156 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2165 — `honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2180 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2236 — `'variant' => honey_normalize_variant((string) $unit['variant']),`
- `includes/honey_bottle_barcodes.php`:2583 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:117 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_inventory.php`:216 — `$variant = honey_normalize_variant((string) ($row['variant'] ?? ''));`
- `includes/honey_inventory.php`:693 — `$variant = honey_normalize_variant($newValue);`
- `includes/honey_inventory.php`:946 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:975 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:1006 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:1190 — `$after = honey_normalize_variant($newValue);`
- `includes/honey_inventory.php`:1483 — `? honey_normalize_variant($variant)`
- `includes/honey_inventory.php`:1484 — `: honey_normalize_variant((string) $wh['variant']);`
- `includes/honey_parcel_inventory.php`:185 — `$variant = honey_normalize_variant($variant);`
- _… and 18 more (run impact.php for full list)_

## honey_unique_packaging_batch_no {#honey_unique_packaging_batch_no}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2535 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_bottle_barcodes.php`:2629 — `$batchNo = honey_unique_packaging_batch_no($pdo, $supplierCode, $variant);`
- `includes/honey_inventory.php`:418 — `$batchNo = honey_unique_packaging_batch_no(`
- `includes/honey_inventory.php`:1549 — `$batchNo = honey_unique_packaging_batch_no(`

## honey_packaging_size_columns {#honey_packaging_size_columns}

**Callers (20):**

- `includes/honey_bottle_barcodes.php`:205 — `[$curCol, $iniCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:239 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2324 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_bottle_barcodes.php`:2428 — `[$col] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:560 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:576 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:656 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:751 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1018 — `[$curCol, $initCol] = honey_packaging_size_columns((string) $size);`
- `includes/honey_inventory.php`:1031 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:1582 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_parcel_inventory.php`:139 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:144 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:164 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_damaged_use.php`:294 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:442 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:608 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:725 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:1021 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/sku_barcodes.php`:1172 — `[$curCol] = honey_packaging_size_columns($size);`

## honey_fetch_warehouse {#honey_fetch_warehouse}

**Tables:** `honey_warehouse_rows`

**Callers (12):**

- `includes/bulk_honey_orders.php`:149 — `$wh = honey_fetch_warehouse($pdo, $wid);`
- `includes/honey_inventory.php`:291 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:338 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:727 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:762 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:872 — `$wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;`
- `includes/honey_inventory.php`:930 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:1089 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:1478 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:70 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:339 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:352 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`

## honey_fetch_production {#honey_fetch_production}

**Tables:** `honey_production_rows`

**Callers (6):**

- `includes/honey_bottle_barcodes.php`:2530 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:386 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:871 — `$pr = $prodId > 0 ? honey_fetch_production($pdo, $prodId) : null;`
- `includes/honey_inventory.php`:970 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:1107 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:1525 — `$pr = honey_fetch_production($pdo, $productionRowId);`

## honey_fetch_packaging {#honey_fetch_packaging}

**Tables:** `honey_packaging_rows`

**Callers (10):**

- `includes/honey_bottle_barcodes.php`:301 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:449 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:863 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_bottle_barcodes.php`:2555 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:2646 — `$pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_inventory.php`:546 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:816 — `$pk = honey_fetch_packaging($pdo, $packId);`
- `includes/honey_inventory.php`:1001 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/honey_inventory.php`:1124 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/sku_barcodes.php`:999 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`

## honey_fetch_packaging_by_production {#honey_fetch_packaging_by_production}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `api/v1/bottle_barcodes.php`:153 — `$before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId);`
- `includes/honey_bottle_barcodes.php`:2526 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:414 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId);`

## honey_warehouse_stock_in {#honey_warehouse_stock_in}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:252 — `$result = honey_warehouse_stock_in(`

## honey_warehouse_has_pricing_columns {#honey_warehouse_has_pricing_columns}

**Callers (2):**

- `api/v1/honey.php`:240 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `includes/honey_inventory.php`:199 — `$hasPricingCols = honey_warehouse_has_pricing_columns($pdo);`

## honey_warehouse_unit_cost_column {#honey_warehouse_unit_cost_column}

**Callers (4):**

- `api/v1/honey.php`:222 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:62 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/bulk_honey_orders.php`:153 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/honey_inventory.php`:252 — `return honey_warehouse_unit_cost_column($pdo) !== null;`

## honey_warehouse_normalize_cost_row {#honey_warehouse_normalize_cost_row}

**Callers (3):**

- `api/v1/honey.php`:233 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`
- `includes/bulk_honey_orders.php`:76 — `? honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($row))`
- `includes/bulk_honey_orders.php`:157 — `honey_warehouse_normalize_cost_row($wh)`

## honey_warehouse_stock_out {#honey_warehouse_stock_out}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:272 — `$result = honey_warehouse_stock_out(`

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

- `api/v1/honey.php`:465 — `$result = honey_production_stock_out(`

## honey_bottling_deduct_packaging_stocks {#honey_bottling_deduct_packaging_stocks}

**Tables:** `departments`, `stocks`

**Callers (1):**

- `includes/honey_inventory.php`:460 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`

## honey_packaging_stock_out_sold {#honey_packaging_stock_out_sold}

**Tables:** `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:506 — `$result = honey_packaging_stock_out_sold(`

## honey_apply_correction {#honey_apply_correction}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_apply_correction_request {#honey_apply_correction_request}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`, `honey_packaging_rows`, `dept_inventory_rows`

**Callers (2):**

- `api/v1/honey.php`:688 — `honey_apply_correction_request($pdo, $req);`
- `includes/honey_inventory.php`:613 — `honey_apply_correction_request($pdo, [`

## honey_cascade_warehouse_metadata {#honey_cascade_warehouse_metadata}

**Tables:** `honey_production_rows`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_inventory.php`:696 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`
- `includes/honey_inventory.php`:736 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:943 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:949 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`

## honey_log_edit {#honey_log_edit}

**Callers (17):**

- `includes/honey_inventory.php`:939 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId, $logSource);`
- `includes/honey_inventory.php`:947 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant, $logSource);`
- `includes/honey_inventory.php`:957 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:959 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:976 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant, $logSource);`
- `includes/honey_inventory.php`:988 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:990 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1007 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant, $logSource);`
- `includes/honey_inventory.php`:1020 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, $old, (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1022 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1032 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, (string) ($pk[$curCol] ?? '0'), (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1034 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1054 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label, $logSource);`
- `includes/honey_inventory.php`:1064 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1066 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty, $logSource);`
- `includes/honey_inventory.php`:1500 — `honey_log_edit($pdo, $userId, 'honey_production_rows', $id, 'seed_qty', null, (string) $qtyGallons, 'monthly_inventory');`
- `includes/honey_inventory.php`:1583 — `honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, 'seed_' . $curCol, null, (string) $normalized[$size], 'monthly_inventory');`

## honey_traceability {#honey_traceability}

**Tables:** `honey_packaging_rows`, `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:524 — `api_ok(honey_traceability(ims_pdo(), $q));`

## honey_build_trace_chain {#honey_build_trace_chain}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_inventory.php`:818 — `$chain = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:833 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:847 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:861 — `$matches[] = honey_build_trace_chain($pdo, $pk);`

## honey_correction_row_options {#honey_correction_row_options}

**Tables:** `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:617 — `api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);`

## honey_executive_adjust {#honey_executive_adjust}

**Callers (1):**

- `api/v1/honey.php`:731 — `honey_executive_adjust(`

## honey_executive_qty_resets_baseline {#honey_executive_qty_resets_baseline}

**Callers (4):**

- `includes/honey_inventory.php`:956 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:987 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:1011 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:1063 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`

## honey_executive_edit_row {#honey_executive_edit_row}

**Tables:** `dept_inventory_rows`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:754 — `honey_executive_edit_row(`
- `includes/honey_inventory.php`:910 — `honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [`

## honey_correction_row_context {#honey_correction_row_context}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/honey_inventory.php`:1285 — `$ctx = honey_correction_row_context($pdo, $req);`

## honey_correction_change_preview {#honey_correction_change_preview}

**Callers (1):**

- `includes/honey_inventory.php`:1287 — `$req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);`

## honey_correction_enrich {#honey_correction_enrich}

**Callers (2):**

- `api/v1/honey.php`:607 — `honey_correction_enrich($pdo, $row);`
- `includes/honey_inventory.php`:1351 — `honey_correction_enrich($pdo, $r);`

## honey_correction_summary {#honey_correction_summary}

**Callers (2):**

- `includes/honey_inventory.php`:1182 — `'headline' => honey_correction_summary($req),`
- `includes/honey_inventory.php`:1288 — `$req['summary'] = honey_correction_summary($req);`

## honey_correction_unread_count {#honey_correction_unread_count}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:636 — `$count = honey_correction_unread_count($pdo);`

## honey_correction_unread_list {#honey_correction_unread_list}

**Tables:** `inventory_correction_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:654 — `$rows = honey_correction_unread_list(ims_pdo(), $limit);`

## honey_correction_mark_viewed {#honey_correction_mark_viewed}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:668 — `honey_correction_mark_viewed(ims_pdo(), $id);`

## honey_packaging_variant_inventory_breakdown {#honey_packaging_variant_inventory_breakdown}

**Callers (1):**

- `includes/honey_inventory.php`:1440 — `$agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);`

## honey_packaging_total_inventory_snapshot {#honey_packaging_total_inventory_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:495 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`
- `api/v1/online_orders.php`:493 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`

## honey_executive_seed_production_row {#honey_executive_seed_production_row}

**Tables:** `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:1366 — `$row = honey_executive_seed_production_row(`

## honey_executive_seed_packaging_row {#honey_executive_seed_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:1392 — `$row = honey_executive_seed_packaging_row(`

