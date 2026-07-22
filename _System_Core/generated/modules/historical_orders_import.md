---
title: historical_orders_import
source: includes/historical_orders_import.php
generated: 2026-07-22T11:02:41+02:00
type: module
function_count: 21
---


# historical_orders_import

> Source: `includes/historical_orders_import.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/online_orders_generator.php`
- `includes/honey_parcel_item_parser.php`
- `includes/waybill_stamper.php`
- `includes/rts_storage.php`

## historical_orders_storage_root {#historical_orders_storage_root}

**Callers (1):**

- `includes/historical_orders_import.php`:142 — `$stashDir = historical_orders_storage_root() . '/stash';`

## historical_orders_workbook_script {#historical_orders_workbook_script}

**Callers (1):**

- `includes/historical_orders_import.php`:38 — `$script = historical_orders_workbook_script();`

## historical_orders_python_command {#historical_orders_python_command}

**Callers (1):**

- `includes/historical_orders_import.php`:57 — `$built = historical_orders_python_command('parse', $workbookPath);`

## historical_orders_run_python_parse {#historical_orders_run_python_parse}

**Callers (1):**

- `includes/historical_orders_import.php`:349 — `$data = historical_orders_run_python_parse($path);`

## historical_orders_user_can_access {#historical_orders_user_can_access}

**Callers (1):**

- `includes/historical_orders_import.php`:106 — `if (!historical_orders_user_can_access($user)) {`

## historical_orders_require_access {#historical_orders_require_access}

**Callers (3):**

- `api/v1/historical_orders.php`:26 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:39 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:55 — `historical_orders_require_access();`

## historical_orders_stash_prune {#historical_orders_stash_prune}

**Callers (2):**

- `includes/historical_orders_import.php`:134 — `historical_orders_stash_prune();`
- `includes/historical_orders_import.php`:170 — `historical_orders_stash_prune();`

## historical_orders_stash_upload {#historical_orders_stash_upload}

**Callers (1):**

- `api/v1/historical_orders.php`:28 — `$token = historical_orders_stash_upload();`

## historical_orders_resolve_stash_path {#historical_orders_resolve_stash_path}

**Callers (1):**

- `includes/historical_orders_import.php`:348 — `$path = historical_orders_resolve_stash_path($token);`

## historical_orders_resolve_month {#historical_orders_resolve_month}

**Callers (1):**

- `includes/historical_orders_import.php`:346 — `$range = historical_orders_resolve_month($year, $month);`

## historical_orders_normalize_tn {#historical_orders_normalize_tn}

**Callers (1):**

- `includes/historical_orders_import.php`:376 — `$tn = historical_orders_normalize_tn($tnRaw);`

## historical_orders_missing_tn_placeholder {#historical_orders_missing_tn_placeholder}

**Callers (1):**

- `includes/historical_orders_import.php`:386 — `$tn = historical_orders_missing_tn_placeholder(`

## historical_orders_courier_from_tn {#historical_orders_courier_from_tn}

**Callers (1):**

- `includes/historical_orders_import.php`:392 — `$courier = historical_orders_courier_from_tn($tn, $pipeline);`

## historical_orders_default_courier {#historical_orders_default_courier}

**Callers (1):**

- `includes/historical_orders_import.php`:249 — `return historical_orders_default_courier($pipeline);`

## historical_orders_prepare_items {#historical_orders_prepare_items}

**Callers (1):**

- `includes/historical_orders_import.php`:380 — `$prepared = historical_orders_prepare_items($itemsSrc);`

## historical_orders_existing_snapshot {#historical_orders_existing_snapshot}

**Callers (1):**

- `includes/historical_orders_import.php`:415 — `$snap = historical_orders_existing_snapshot($existing);`

## historical_orders_preview {#historical_orders_preview}

**Callers (1):**

- `api/v1/historical_orders.php`:42 — `$result = historical_orders_preview(ims_pdo(), $input);`

## historical_orders_diff_fields {#historical_orders_diff_fields}

**Callers (1):**

- `includes/historical_orders_import.php`:422 — `'diff_fields' => historical_orders_diff_fields($snap, $uploaded),`

## historical_orders_commit {#historical_orders_commit}

**Callers (1):**

- `api/v1/historical_orders.php`:58 — `$result = historical_orders_commit(ims_pdo(), $input);`

## historical_orders_insert_shipped {#historical_orders_insert_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:551 — `historical_orders_insert_shipped($pdo, $row, $createdAt);`

## historical_orders_overwrite_shipped {#historical_orders_overwrite_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:580 — `historical_orders_overwrite_shipped($pdo, (int) ($existing['id'] ?? 0), $uploaded, $createdAt);`

