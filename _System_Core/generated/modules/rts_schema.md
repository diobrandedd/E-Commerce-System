---
title: rts_schema
source: includes/rts_schema.php
generated: 2026-06-05T08:03:28+02:00
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

- `includes/rts_schema.php`:229 — `rts_db_clear_column_cache();`

## rts_db_has_column {#rts_db_has_column}

**Callers (22):**

- `api/case.php`:552 — `if (rts_db_has_column($pdo, 'rts_cases', 'updated_at')) {`
- `api/v1/deliveries.php`:206 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `api/v1/deliveries.php`:244 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `includes/deliveries_upload.php`:58 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:19 — `$hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/delivery_edit_requests.php`:81 — `$parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')`
- `includes/delivery_edit_requests.php`:120 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:264 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:363 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/delivery_edit_requests.php`:418 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/honey_parcel_inventory.php`:30 — `$hasParcelCol = rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id');`
- `includes/honey_parcel_inventory.php`:99 — `if (rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id')) {`
- `includes/online_order_parcels.php`:235 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/online_order_parcels.php`:326 — `$hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');`
- `includes/online_order_parcels.php`:573 — `if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {`
- `includes/rts_schema.php`:47 — `return rts_db_has_column($pdo, 'rts_cases', 'voided_at')`
- `includes/rts_schema.php`:77 — `if (rts_db_has_column($pdo, 'rts_cases', $col)) {`
- `includes/rts_schema.php`:83 — `if (rts_db_has_column($pdo, 'rts_cases', $col)) {`
- `includes/rts_schema.php`:177 — `if (rts_db_has_column($pdo, 'rts_line_items', $col)) {`
- `includes/rts_schema.php`:260 — `if (!array_key_exists($col, $row) || !rts_db_has_column($pdo, 'rts_cases', $col)) {`
- `includes/rts_schema.php`:300 — `if (rts_db_has_column($pdo, 'rts_cases', 'updated_at')) {`
- `includes/rts_schema.php`:305 — `if (!array_key_exists($col, $row) || !rts_db_has_column($pdo, 'rts_cases', $col)) {`

## rts_cases_void_sql {#rts_cases_void_sql}

**Callers (3):**

- `api/case.php`:302 — `$void = rts_cases_void_sql($pdo);`
- `api/case.php`:313 — `$dupVoid = rts_cases_void_sql($pdo);`
- `includes/rts_schema.php`:355 — `$void = rts_cases_void_sql($pdo);`

## rts_cases_select_columns {#rts_cases_select_columns}

**Callers (1):**

- `includes/rts_schema.php`:95 — `$select = rts_cases_select_columns($pdo);`

## rts_cases_fetch_by_tracking {#rts_cases_fetch_by_tracking}

**Tables:** `rts_cases`

**Callers (1):**

- `api/case.php`:43 — `$case = rts_cases_fetch_by_tracking($pdo, $tracking);`

## rts_v1_pack_to_honey_size {#rts_v1_pack_to_honey_size}

**Callers (1):**

- `includes/rts_schema.php`:131 — `'honey_size' => rts_v1_pack_to_honey_size((string) ($row['pack_size'] ?? '')),`

## rts_line_item_row_to_api {#rts_line_item_row_to_api}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_line_items_fetch_for_case {#rts_line_items_fetch_for_case}

**Tables:** `rts_line_items`

**Callers (1):**

- `api/case.php`:70 — `$lineRows = rts_line_items_fetch_for_case($pdo, $lid);`

## rts_line_items_ensure_v2_schema {#rts_line_items_ensure_v2_schema}

**Tables:** `rts_line_items`

**Callers (1):**

- `includes/rts_schema.php`:322 — `$schemaErr = rts_line_items_ensure_v2_schema($pdo);`

## rts_cases_insert_row {#rts_cases_insert_row}

**Tables:** `rts_cases`

**Callers (1):**

- `api/case.php`:501 — `$caseId = rts_cases_insert_row($pdo, [`

## rts_cases_update_row {#rts_cases_update_row}

**Tables:** `rts_cases`

**Callers (1):**

- `api/case.php`:325 — `rts_cases_update_row($pdo, $caseId, [`

## rts_line_items_insert_batch {#rts_line_items_insert_batch}

**Tables:** `rts_line_items`

**Callers (3):**

- `api/case.php`:341 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`
- `api/case.php`:515 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`
- `api/case.php`:551 — `rts_line_items_insert_batch($pdo, $caseId, $lines);`

## rts_cases_find_active_id_by_tracking {#rts_cases_find_active_id_by_tracking}

**Tables:** `rts_cases`

**Callers (2):**

- `api/case.php`:495 — `if (rts_cases_find_active_id_by_tracking($pdo, $tracking) !== null) {`
- `api/case.php`:543 — `$caseId = rts_cases_find_active_id_by_tracking($pdo, $tracking);`

