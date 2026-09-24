---
title: historical_orders_import
source: includes/historical_orders_import.php
generated: 2026-09-24T10:42:23+02:00
type: module
function_count: 25
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

- `includes/historical_orders_import.php`:146 — `$stashDir = historical_orders_storage_root() . '/stash';`

## historical_orders_workbook_script {#historical_orders_workbook_script}

**Callers (1):**

- `includes/historical_orders_import.php`:38 — `$script = historical_orders_workbook_script();`

## historical_orders_python_command {#historical_orders_python_command}

**Callers (1):**

- `includes/historical_orders_import.php`:61 — `$built = historical_orders_python_command(...$args);`

## historical_orders_run_python_parse {#historical_orders_run_python_parse}

**Callers (1):**

- `includes/historical_orders_import.php`:407 — `$data = historical_orders_run_python_parse($path, $sheetName !== '' ? $sheetName : null);`

## historical_orders_user_can_access {#historical_orders_user_can_access}

**Callers (1):**

- `includes/historical_orders_import.php`:110 — `if (!historical_orders_user_can_access($user)) {`

## historical_orders_require_access {#historical_orders_require_access}

**Callers (3):**

- `api/v1/historical_orders.php`:26 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:39 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:55 — `historical_orders_require_access();`

## historical_orders_stash_prune {#historical_orders_stash_prune}

**Callers (2):**

- `includes/historical_orders_import.php`:138 — `historical_orders_stash_prune();`
- `includes/historical_orders_import.php`:174 — `historical_orders_stash_prune();`

## historical_orders_stash_upload {#historical_orders_stash_upload}

**Callers (1):**

- `api/v1/historical_orders.php`:28 — `$token = historical_orders_stash_upload();`

## historical_orders_resolve_stash_path {#historical_orders_resolve_stash_path}

**Callers (1):**

- `includes/historical_orders_import.php`:406 — `$path = historical_orders_resolve_stash_path($token);`

## historical_orders_resolve_month {#historical_orders_resolve_month}

**Callers (2):**

- `includes/historical_orders_import.php`:403 — `$range = historical_orders_resolve_month($year, $month);`
- `includes/historical_orders_import.php`:745 — `$range = historical_orders_resolve_month($year, $month);`

## historical_orders_normalize_tn {#historical_orders_normalize_tn}

**Callers (3):**

- `includes/historical_orders_import.php`:439 — `$tn = historical_orders_normalize_tn($tnRaw);`
- `includes/historical_orders_import.php`:457 — `$tn = historical_orders_normalize_tn($orderNo);`
- `includes/historical_orders_import.php`:496 — `'original_tracking_number' => $trackingMissing ? null : historical_orders_normalize_tn($tnRaw),`

## historical_orders_missing_tn_placeholder {#historical_orders_missing_tn_placeholder}

**Callers (2):**

- `includes/historical_orders_import.php`:459 — `$tn = historical_orders_missing_tn_placeholder($range['yyyymm'], $orderNo, $rowKey);`
- `includes/historical_orders_import.php`:462 — `$tn = historical_orders_missing_tn_placeholder(`

## historical_orders_courier_from_tn {#historical_orders_courier_from_tn}

**Callers (1):**

- `includes/historical_orders_import.php`:482 — `$courier = historical_orders_courier_from_tn($tn, $rowPipeline);`

## historical_orders_default_courier {#historical_orders_default_courier}

**Callers (3):**

- `includes/historical_orders_import.php`:257 — `return historical_orders_default_courier($pipeline);`
- `includes/historical_orders_import.php`:299 — `return historical_orders_default_courier($pipeline);`
- `includes/historical_orders_import.php`:484 — `$courier = historical_orders_default_courier($rowPipeline);`

## historical_orders_pipeline_from_courier {#historical_orders_pipeline_from_courier}

**Callers (1):**

- `includes/historical_orders_import.php`:450 — `$rowPipeline = historical_orders_pipeline_from_courier($courierRaw, $pipeline === 'partner' ? 'partner' : $pipeline);`

## historical_orders_normalize_courier_label {#historical_orders_normalize_courier_label}

**Callers (1):**

- `includes/historical_orders_import.php`:480 — `$courier = historical_orders_normalize_courier_label($courierRaw, $rowPipeline);`

## historical_orders_normalize_partner_seller {#historical_orders_normalize_partner_seller}

**Callers (2):**

- `includes/historical_orders_import.php`:392 — `$partnerSeller = historical_orders_normalize_partner_seller(`
- `includes/historical_orders_import.php`:739 — `$partnerSeller = historical_orders_normalize_partner_seller(`

## historical_orders_prepare_items {#historical_orders_prepare_items}

**Callers (1):**

- `includes/historical_orders_import.php`:443 — `$prepared = historical_orders_prepare_items($itemsSrc);`

## historical_orders_existing_snapshot {#historical_orders_existing_snapshot}

**Callers (1):**

- `includes/historical_orders_import.php`:513 — `$snap = historical_orders_existing_snapshot($existing);`

## historical_orders_preview {#historical_orders_preview}

**Callers (2):**

- `api/v1/historical_orders.php`:42 — `$result = historical_orders_preview(ims_pdo(), $input);`
- `includes/historical_orders_import.php`:759 — `$preview = historical_orders_preview($pdo, [`

## historical_orders_diff_fields {#historical_orders_diff_fields}

**Callers (1):**

- `includes/historical_orders_import.php`:520 — `'diff_fields' => historical_orders_diff_fields($snap, $uploaded),`

## historical_orders_commit {#historical_orders_commit}

**Callers (2):**

- `api/v1/historical_orders.php`:58 — `$result = historical_orders_commit(ims_pdo(), $input);`
- `includes/historical_orders_import.php`:780 — `$commit = historical_orders_commit($pdo, [`

## historical_orders_import_file {#historical_orders_import_file}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## historical_orders_insert_shipped {#historical_orders_insert_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:659 — `historical_orders_insert_shipped($pdo, $row, $rowShip . ' 12:00:00');`

## historical_orders_overwrite_shipped {#historical_orders_overwrite_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:692 — `historical_orders_overwrite_shipped($pdo, (int) ($existing['id'] ?? 0), $uploaded, $rowShip . ' 12:00:00');`

