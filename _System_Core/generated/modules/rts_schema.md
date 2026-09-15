---
title: rts_schema
source: includes/rts_schema.php
generated: 2026-09-15T04:36:24+02:00
type: module
function_count: 13
---


# rts_schema

> Source: `includes/rts_schema.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_products.php`

## rts_db_clear_column_cache {#rts_db_clear_column_cache}

**Callers (2):**

- `includes/api_tokens.php`:227 — `rts_db_clear_column_cache();`
- `includes/rts_schema.php`:232 — `rts_db_clear_column_cache();`

## rts_db_has_column {#rts_db_has_column}

**Callers (72):**

- `api/v1/deliveries.php`:308 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `api/v1/deliveries.php`:347 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `includes/api_tokens.php`:220 — `if (!rts_db_has_column($pdo, 'pos_sales', 'refunded_at')) {`
- `includes/deliveries_upload.php`:58 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:19 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:81 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `includes/delivery_edit_requests.php`:120 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:264 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:363 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:418 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/dept_pouch_barcodes.php`:1243 — `$hasPool = rts_db_has_column($pdo, 'dept_pouch_units', 'pool');`
- `includes/dept_pouch_barcodes.php`:1297 — `$hasPool = rts_db_has_column($pdo, 'dept_pouch_units', 'pool');`
- `includes/dept_pouch_barcodes.php`:1363 — `if (!rts_db_has_column($pdo, 'dept_pouch_units', 'pool')) {`
- `includes/fulfillment_packing.php`:242 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:552 — `if (rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {`
- `includes/fulfillment_packing.php`:581 — `if (rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'source_stock_id')) {`
- `includes/fulfillment_packing.php`:666 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:711 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:761 — `if (!rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {`
- `includes/fulfillment_packing.php`:957 — `$hasBubbleCols = rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'bubble_width_cm');`
- `includes/honey_bottle_barcodes.php`:2000 — `$hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');`
- `includes/honey_bottle_barcodes.php`:2001 — `$hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');`
- `includes/honey_bottle_barcodes.php`:2276 — `$hasUnit = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');`
- `includes/honey_bottle_barcodes.php`:2277 — `$hasSess = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');`
- `includes/honey_bottle_barcodes.php`:2278 — `$hasPos = rts_db_has_column($pdo, 'honey_sales_log', 'pos_sale_id');`
- _… and 47 more (run impact.php for full list)_

## rts_cases_void_sql {#rts_cases_void_sql}

**Callers (3):**

- `includes/rts_case_handlers.php`:559 — `$void = rts_cases_void_sql($pdo);`
- `includes/rts_case_handlers.php`:566 — `$dupVoid = rts_cases_void_sql($pdo);`
- `includes/rts_schema.php`:377 — `$void = rts_cases_void_sql($pdo);`

## rts_cases_select_columns {#rts_cases_select_columns}

**Callers (1):**

- `includes/rts_schema.php`:97 — `$select = rts_cases_select_columns($pdo);`

## rts_cases_fetch_by_tracking {#rts_cases_fetch_by_tracking}

**Tables:** `rts_cases`

**Callers (2):**

- `includes/rts_case_handlers.php`:175 — `$case = rts_cases_fetch_by_tracking($pdo, $tracking);`
- `includes/rts_prefill.php`:297 — `$existingCase = rts_cases_fetch_by_tracking($pdo, $normalized);`

## rts_v1_pack_to_honey_size {#rts_v1_pack_to_honey_size}

**Callers (1):**

- `includes/rts_schema.php`:133 — `'honey_size' => rts_v1_pack_to_honey_size((string) ($row['pack_size'] ?? '')),`

## rts_line_item_row_to_api {#rts_line_item_row_to_api}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_line_items_fetch_for_case {#rts_line_items_fetch_for_case}

**Tables:** `rts_line_items`

**Callers (2):**

- `includes/rts_case_handlers.php`:198 — `$lineRows = rts_line_items_fetch_for_case($pdo, $lid);`
- `includes/rts_case_handlers.php`:904 — `$row['lines'] = rts_line_items_fetch_for_case($pdo, (int) $row['id']);`

## rts_line_items_ensure_v2_schema {#rts_line_items_ensure_v2_schema}

**Tables:** `rts_line_items`

**Callers (2):**

- `includes/rts_case_handlers.php`:463 — `$schemaErr = rts_line_items_ensure_v2_schema($pdo);`
- `includes/rts_schema.php`:329 — `$schemaErr = rts_line_items_ensure_v2_schema($pdo);`

## rts_cases_insert_row {#rts_cases_insert_row}

**Tables:** `rts_cases`

**Callers (1):**

- `includes/rts_case_handlers.php`:487 — `$caseId = rts_cases_insert_row($pdo, [`

## rts_cases_update_row {#rts_cases_update_row}

**Tables:** `rts_cases`

**Callers (1):**

- `includes/rts_case_handlers.php`:580 — `rts_cases_update_row($pdo, $caseId, [`

## rts_line_items_insert_batch {#rts_line_items_insert_batch}

**Tables:** `rts_line_items`

**Callers (3):**

- `includes/rts_case_handlers.php`:503 — `rts_line_items_insert_batch($pdo, $caseId, $fields['lines']);`
- `includes/rts_case_handlers.php`:651 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`
- `includes/rts_inventory_sync.php`:252 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`

## rts_cases_find_active_id_by_tracking {#rts_cases_find_active_id_by_tracking}

**Tables:** `rts_cases`

**Callers (2):**

- `includes/rts_case_handlers.php`:478 — `if (rts_cases_find_active_id_by_tracking($pdo, $tracking) !== null) {`
- `includes/rts_case_handlers.php`:628 — `$caseId = rts_cases_find_active_id_by_tracking($pdo, $tracking);`

