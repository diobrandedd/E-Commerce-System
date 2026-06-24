---
title: rts_schema
source: includes/rts_schema.php
generated: 2026-06-24T10:30:50+02:00
type: module
function_count: 13
---


# rts_schema

> Source: `includes/rts_schema.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_products.php`

## rts_db_clear_column_cache {#rts_db_clear_column_cache}

**Callers (1):**

- `includes/rts_schema.php`:232 — `rts_db_clear_column_cache();`

## rts_db_has_column {#rts_db_has_column}

**Callers (47):**

- `api/v1/deliveries.php`:327 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `api/v1/deliveries.php`:365 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `api/v1/rts.php`:51 — `if (!rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool')) {`
- `includes/deliveries_upload.php`:58 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:19 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:81 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `includes/delivery_edit_requests.php`:120 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:264 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:363 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:418 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/fulfillment_packing.php`:241 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:551 — `if (rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {`
- `includes/fulfillment_packing.php`:580 — `if (rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'source_stock_id')) {`
- `includes/fulfillment_packing.php`:665 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:710 — `$hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');`
- `includes/fulfillment_packing.php`:760 — `if (!rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {`
- `includes/fulfillment_packing.php`:956 — `$hasBubbleCols = rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'bubble_width_cm');`
- `includes/honey_parcel_inventory.php`:56 — `$hasParcelCol = rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id');`
- `includes/honey_parcel_inventory.php`:162 — `if (rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id')) {`
- `includes/online_order_parcels.php`:471 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/online_order_parcels.php`:562 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/online_order_parcels.php`:1046 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/rts_case_handlers.php`:77 — `if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {`
- `includes/rts_case_handlers.php`:85 — `if (rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {`
- `includes/rts_case_handlers.php`:170 — `if (!isset($case['case_disposition']) && rts_db_has_column($pdo, 'rts_cases', 'case_disposition')) {`
- _… and 22 more (run impact.php for full list)_

## rts_cases_void_sql {#rts_cases_void_sql}

**Callers (3):**

- `includes/rts_case_handlers.php`:495 — `$void = rts_cases_void_sql($pdo);`
- `includes/rts_case_handlers.php`:502 — `$dupVoid = rts_cases_void_sql($pdo);`
- `includes/rts_schema.php`:377 — `$void = rts_cases_void_sql($pdo);`

## rts_cases_select_columns {#rts_cases_select_columns}

**Callers (1):**

- `includes/rts_schema.php`:97 — `$select = rts_cases_select_columns($pdo);`

## rts_cases_fetch_by_tracking {#rts_cases_fetch_by_tracking}

**Tables:** `rts_cases`

**Callers (2):**

- `includes/rts_case_handlers.php`:151 — `$case = rts_cases_fetch_by_tracking($pdo, $tracking);`
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

- `includes/rts_case_handlers.php`:174 — `$lineRows = rts_line_items_fetch_for_case($pdo, $lid);`
- `includes/rts_case_handlers.php`:824 — `$row['lines'] = rts_line_items_fetch_for_case($pdo, (int) $row['id']);`

## rts_line_items_ensure_v2_schema {#rts_line_items_ensure_v2_schema}

**Tables:** `rts_line_items`

**Callers (1):**

- `includes/rts_schema.php`:329 — `$schemaErr = rts_line_items_ensure_v2_schema($pdo);`

## rts_cases_insert_row {#rts_cases_insert_row}

**Tables:** `rts_cases`

**Callers (1):**

- `includes/rts_case_handlers.php`:453 — `$caseId = rts_cases_insert_row($pdo, [`

## rts_cases_update_row {#rts_cases_update_row}

**Tables:** `rts_cases`

**Callers (1):**

- `includes/rts_case_handlers.php`:516 — `rts_cases_update_row($pdo, $caseId, [`

## rts_line_items_insert_batch {#rts_line_items_insert_batch}

**Tables:** `rts_line_items`

**Callers (3):**

- `includes/rts_case_handlers.php`:469 — `rts_line_items_insert_batch($pdo, $caseId, $fields['lines']);`
- `includes/rts_case_handlers.php`:533 — `rts_line_items_insert_batch($pdo, $caseId, $fields['lines']);`
- `includes/rts_case_handlers.php`:587 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`

## rts_cases_find_active_id_by_tracking {#rts_cases_find_active_id_by_tracking}

**Tables:** `rts_cases`

**Callers (2):**

- `includes/rts_case_handlers.php`:444 — `if (rts_cases_find_active_id_by_tracking($pdo, $tracking) !== null) {`
- `includes/rts_case_handlers.php`:565 — `$caseId = rts_cases_find_active_id_by_tracking($pdo, $tracking);`

