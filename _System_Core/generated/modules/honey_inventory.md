---
title: honey_inventory
source: includes/honey_inventory.php
generated: 2026-06-18T09:06:29+02:00
type: module
function_count: 39
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
- `includes/honey_pricing.php`

## honey_inventory_pricing {#honey_inventory_pricing}

**Callers (2):**

- `includes/honey_inventory.php`:165 — `honey_inventory_pricing();`
- `includes/honey_inventory.php`:183 — `honey_inventory_pricing();`

## honey_role_can_view {#honey_role_can_view}

**Callers (1):**

- `api/v1/honey.php`:60 — `if (!honey_role_can_view((string) $user['role']) && !honey_role_can_mutate((string) $user['role'])) {`

## honey_role_can_mutate {#honey_role_can_mutate}

**Callers (2):**

- `api/v1/honey.php`:60 — `if (!honey_role_can_view((string) $user['role']) && !honey_role_can_mutate((string) $user['role'])) {`
- `api/v1/honey.php`:70 — `if (!honey_role_can_mutate((string) $user['role'])) {`

## honey_role_can_executive_edit {#honey_role_can_executive_edit}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_supplier_code {#honey_supplier_code}

**Callers (7):**

- `includes/dept_pricing.php`:157 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`
- `includes/honey_inventory.php`:137 — `$code = honey_supplier_code($supplierName, $arrivalDate);`
- `includes/honey_inventory.php`:633 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:673 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:809 — `$code = honey_supplier_code($supplierName, (string) $wh['arrival_date']);`
- `includes/honey_inventory.php`:1078 — `? honey_supplier_code($newName, (string) $ctx['arrival_date'])`
- `includes/honey_pricing.php`:134 — `$supplierCode = honey_supplier_code($supplierName, date('Y-m-d'));`

## honey_batch_no {#honey_batch_no}

**Callers (1):**

- `includes/honey_inventory.php`:311 — `$batchNo = honey_batch_no((string) $pr['supplier_code'], $bottlingDate);`

## honey_normalize_variant {#honey_normalize_variant}

**Callers (10):**

- `includes/honey_inventory.php`:156 — `$variant = honey_normalize_variant((string) ($row['variant'] ?? ''));`
- `includes/honey_inventory.php`:594 — `$variant = honey_normalize_variant($newValue);`
- `includes/honey_inventory.php`:817 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:846 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:877 — `$variant = honey_normalize_variant((string) $fields['variant']);`
- `includes/honey_inventory.php`:1061 — `$after = honey_normalize_variant($newValue);`
- `includes/honey_parcel_inventory.php`:178 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_pos.php`:142 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_pos.php`:159 — `$variant = honey_normalize_variant($variant);`
- `includes/honey_pos.php`:210 — `$variant = honey_normalize_variant($variant);`

## honey_packaging_size_columns {#honey_packaging_size_columns}

**Callers (10):**

- `includes/honey_inventory.php`:461 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:477 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:557 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:652 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_inventory.php`:889 — `[$curCol, $initCol] = honey_packaging_size_columns((string) $size);`
- `includes/honey_inventory.php`:902 — `[$curCol, $initCol] = honey_packaging_size_columns($size);`
- `includes/honey_parcel_inventory.php`:132 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:143 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/honey_pos.php`:163 — `[$curCol] = honey_packaging_size_columns($size);`
- `includes/rts_inventory_sync.php`:153 — `[$curCol] = honey_packaging_size_columns($size);`

## honey_fetch_warehouse {#honey_fetch_warehouse}

**Tables:** `honey_warehouse_rows`

**Callers (10):**

- `includes/honey_inventory.php`:231 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:628 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:663 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_inventory.php`:747 — `$wh = $pr ? honey_fetch_warehouse($pdo, (int) $pr['warehouse_row_id']) : null;`
- `includes/honey_inventory.php`:801 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_inventory.php`:960 — `$wh = honey_fetch_warehouse($pdo, $rowId);`
- `includes/honey_pricing.php`:70 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:312 — `$wh = honey_fetch_warehouse($pdo, (int) $wid);`
- `includes/honey_pricing.php`:344 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`
- `includes/honey_pricing.php`:357 — `$wh = honey_fetch_warehouse($pdo, $warehouseRowId);`

## honey_fetch_production {#honey_fetch_production}

**Tables:** `honey_production_rows`

**Callers (4):**

- `includes/honey_inventory.php`:279 — `$pr = honey_fetch_production($pdo, $productionRowId);`
- `includes/honey_inventory.php`:746 — `$pr = honey_fetch_production($pdo, (int) $pk['production_row_id']);`
- `includes/honey_inventory.php`:841 — `$pr = honey_fetch_production($pdo, $rowId);`
- `includes/honey_inventory.php`:978 — `$pr = honey_fetch_production($pdo, $rowId);`

## honey_fetch_packaging {#honey_fetch_packaging}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_inventory.php`:447 — `$pk = honey_fetch_packaging($pdo, $packagingRowId);`
- `includes/honey_inventory.php`:872 — `$pk = honey_fetch_packaging($pdo, $rowId);`
- `includes/honey_inventory.php`:995 — `$pk = honey_fetch_packaging($pdo, $rowId);`

## honey_fetch_packaging_by_production {#honey_fetch_packaging_by_production}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/honey_inventory.php`:307 — `$existing = honey_fetch_packaging_by_production($pdo, $productionRowId);`

## honey_warehouse_stock_in {#honey_warehouse_stock_in}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:113 — `$result = honey_warehouse_stock_in(`

## honey_warehouse_has_pricing_columns {#honey_warehouse_has_pricing_columns}

**Callers (2):**

- `api/v1/honey.php`:101 — `'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,`
- `includes/honey_inventory.php`:139 — `$hasPricingCols = honey_warehouse_has_pricing_columns($pdo);`

## honey_warehouse_unit_cost_column {#honey_warehouse_unit_cost_column}

**Callers (2):**

- `api/v1/honey.php`:83 — `$costCol = honey_warehouse_unit_cost_column($pdo);`
- `includes/honey_inventory.php`:192 — `return honey_warehouse_unit_cost_column($pdo) !== null;`

## honey_warehouse_normalize_cost_row {#honey_warehouse_normalize_cost_row}

**Callers (1):**

- `api/v1/honey.php`:94 — `static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),`

## honey_warehouse_stock_out {#honey_warehouse_stock_out}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:133 — `$result = honey_warehouse_stock_out(`

## honey_production_stock_out {#honey_production_stock_out}

**Tables:** `honey_packaging_rows`, `honey_production_rows`

**Callers (1):**

- `api/v1/honey.php`:169 — `$result = honey_production_stock_out(`

## honey_bottling_deduct_packaging_stocks {#honey_bottling_deduct_packaging_stocks}

**Tables:** `departments`, `stocks`

**Callers (1):**

- `includes/honey_inventory.php`:361 — `$packagingDeduct = honey_bottling_deduct_packaging_stocks($pdo, $userId, $sizeQtys, $batchNo);`

## honey_packaging_stock_out_sold {#honey_packaging_stock_out_sold}

**Tables:** `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:209 — `$result = honey_packaging_stock_out_sold(`

## honey_apply_correction {#honey_apply_correction}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_apply_correction_request {#honey_apply_correction_request}

**Tables:** `honey_production_rows`, `honey_warehouse_rows`, `honey_packaging_rows`, `dept_inventory_rows`

**Callers (2):**

- `api/v1/honey.php`:388 — `honey_apply_correction_request($pdo, $req);`
- `includes/honey_inventory.php`:514 — `honey_apply_correction_request($pdo, [`

## honey_cascade_warehouse_metadata {#honey_cascade_warehouse_metadata}

**Tables:** `honey_production_rows`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_inventory.php`:597 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`
- `includes/honey_inventory.php`:637 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:814 — `honey_cascade_warehouse_metadata($pdo, $rowId, $supplierId, null);`
- `includes/honey_inventory.php`:820 — `honey_cascade_warehouse_metadata($pdo, $rowId, null, $variant);`

## honey_log_edit {#honey_log_edit}

**Tables:** `inventory_edit_log`

**Callers (15):**

- `includes/honey_inventory.php`:810 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'supplier_id', (string) $wh['supplier_id'], (string) $supplierId);`
- `includes/honey_inventory.php`:818 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $wh['variant'], $variant);`
- `includes/honey_inventory.php`:828 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $wh['current_qty'], (string) $qty);`
- `includes/honey_inventory.php`:830 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $wh['initial_qty'], (string) $qty);`
- `includes/honey_inventory.php`:847 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pr['variant'], $variant);`
- `includes/honey_inventory.php`:859 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $pr['current_qty'], (string) $qty);`
- `includes/honey_inventory.php`:861 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $pr['initial_qty'], (string) $qty);`
- `includes/honey_inventory.php`:878 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'variant', (string) $pk['variant'], $variant);`
- `includes/honey_inventory.php`:891 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, $old, (string) $qty);`
- `includes/honey_inventory.php`:893 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty);`
- `includes/honey_inventory.php`:903 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $curCol, (string) ($pk[$curCol] ?? '0'), (string) $qty);`
- `includes/honey_inventory.php`:905 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, $initCol, (string) ($pk[$initCol] ?? '0'), (string) $qty);`
- `includes/honey_inventory.php`:925 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'product_label', (string) $row['product_label'], $label);`
- `includes/honey_inventory.php`:935 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'current_qty', (string) $row['current_qty'], (string) $qty);`
- `includes/honey_inventory.php`:937 — `honey_log_edit($pdo, $userId, $rowTable, $rowId, 'initial_qty', (string) $row['initial_qty'], (string) $qty);`

## honey_traceability {#honey_traceability}

**Tables:** `honey_packaging_rows`, `honey_production_rows`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:227 — `api_ok(honey_traceability(ims_pdo(), $q));`

## honey_build_trace_chain {#honey_build_trace_chain}

**Tables:** `honey_sales_log`

**Callers (3):**

- `includes/honey_inventory.php`:713 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:723 — `$matches[] = honey_build_trace_chain($pdo, $pk);`
- `includes/honey_inventory.php`:737 — `$matches[] = honey_build_trace_chain($pdo, $pk);`

## honey_correction_row_options {#honey_correction_row_options}

**Tables:** `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/honey.php`:322 — `api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);`

## honey_executive_adjust {#honey_executive_adjust}

**Callers (1):**

- `api/v1/honey.php`:431 — `honey_executive_adjust(`

## honey_executive_qty_resets_baseline {#honey_executive_qty_resets_baseline}

**Callers (4):**

- `includes/honey_inventory.php`:827 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:858 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:882 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`
- `includes/honey_inventory.php`:934 — `$resetBaseline = honey_executive_qty_resets_baseline($fields);`

## honey_executive_edit_row {#honey_executive_edit_row}

**Tables:** `dept_inventory_rows`, `honey_warehouse_rows`, `honey_production_rows`, `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:454 — `honey_executive_edit_row(`
- `includes/honey_inventory.php`:785 — `honey_executive_edit_row($pdo, 0, $stage, $rowTable, $rowId, [`

## honey_correction_row_context {#honey_correction_row_context}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/honey_inventory.php`:1156 — `$ctx = honey_correction_row_context($pdo, $req);`

## honey_correction_change_preview {#honey_correction_change_preview}

**Callers (1):**

- `includes/honey_inventory.php`:1158 — `$req['change_preview'] = honey_correction_change_preview($pdo, $req, $ctx);`

## honey_correction_enrich {#honey_correction_enrich}

**Callers (2):**

- `api/v1/honey.php`:312 — `honey_correction_enrich($pdo, $row);`
- `includes/honey_inventory.php`:1222 — `honey_correction_enrich($pdo, $r);`

## honey_correction_summary {#honey_correction_summary}

**Callers (2):**

- `includes/honey_inventory.php`:1053 — `'headline' => honey_correction_summary($req),`
- `includes/honey_inventory.php`:1159 — `$req['summary'] = honey_correction_summary($req);`

## honey_correction_unread_count {#honey_correction_unread_count}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:341 — `$count = honey_correction_unread_count($pdo);`

## honey_correction_unread_list {#honey_correction_unread_list}

**Tables:** `inventory_correction_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:354 — `$rows = honey_correction_unread_list(ims_pdo(), $limit);`

## honey_correction_mark_viewed {#honey_correction_mark_viewed}

**Tables:** `inventory_correction_requests`

**Callers (1):**

- `api/v1/honey.php`:368 — `honey_correction_mark_viewed(ims_pdo(), $id);`

## honey_packaging_variant_inventory_breakdown {#honey_packaging_variant_inventory_breakdown}

**Callers (1):**

- `includes/honey_inventory.php`:1311 — `$agg = honey_packaging_variant_inventory_breakdown($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []);`

## honey_packaging_total_inventory_snapshot {#honey_packaging_total_inventory_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/honey.php`:199 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`
- `api/v1/online_orders.php`:402 — `api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));`

