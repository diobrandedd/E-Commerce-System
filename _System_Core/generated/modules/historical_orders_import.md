---
title: historical_orders_import
source: includes/historical_orders_import.php
generated: 2026-07-22T10:13:31+02:00
type: module
function_count: 16
---


# historical_orders_import

> Source: `includes/historical_orders_import.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/online_orders_generator.php`
- `includes/order_remarks.php`
- `includes/honey_parcel_item_parser.php`
- `includes/waybill_stamper.php`
- `includes/rts_storage.php`

## historical_orders_storage_root {#historical_orders_storage_root}

**Callers (1):**

- `includes/historical_orders_import.php`:76 — `$stashDir = historical_orders_storage_root() . '/stash';`

## historical_orders_user_can_access {#historical_orders_user_can_access}

**Callers (1):**

- `includes/historical_orders_import.php`:40 — `if (!historical_orders_user_can_access($user)) {`

## historical_orders_require_access {#historical_orders_require_access}

**Callers (3):**

- `api/v1/historical_orders.php`:26 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:39 — `historical_orders_require_access();`
- `api/v1/historical_orders.php`:55 — `historical_orders_require_access();`

## historical_orders_stash_prune {#historical_orders_stash_prune}

**Callers (2):**

- `includes/historical_orders_import.php`:68 — `historical_orders_stash_prune();`
- `includes/historical_orders_import.php`:104 — `historical_orders_stash_prune();`

## historical_orders_stash_upload {#historical_orders_stash_upload}

**Callers (1):**

- `api/v1/historical_orders.php`:28 — `$token = historical_orders_stash_upload();`

## historical_orders_resolve_stash_path {#historical_orders_resolve_stash_path}

**Callers (1):**

- `includes/historical_orders_import.php`:265 — `$path = historical_orders_resolve_stash_path($token);`

## historical_orders_resolve_month {#historical_orders_resolve_month}

**Callers (1):**

- `includes/historical_orders_import.php`:259 — `$range = historical_orders_resolve_month($year, $month);`

## historical_orders_synthetic_tn {#historical_orders_synthetic_tn}

**Callers (1):**

- `includes/historical_orders_import.php`:299 — `$tn = historical_orders_synthetic_tn($parcelRef);`

## historical_orders_default_courier {#historical_orders_default_courier}

**Callers (1):**

- `includes/historical_orders_import.php`:272 — `$courier = historical_orders_default_courier($pipeline);`

## historical_orders_prepare_items {#historical_orders_prepare_items}

**Callers (1):**

- `includes/historical_orders_import.php`:300 — `$prepared = historical_orders_prepare_items($remarks);`

## historical_orders_existing_snapshot {#historical_orders_existing_snapshot}

**Callers (1):**

- `includes/historical_orders_import.php`:323 — `$snap = historical_orders_existing_snapshot($existing);`

## historical_orders_preview {#historical_orders_preview}

**Callers (1):**

- `api/v1/historical_orders.php`:42 — `$result = historical_orders_preview(ims_pdo(), $input);`

## historical_orders_diff_fields {#historical_orders_diff_fields}

**Callers (1):**

- `includes/historical_orders_import.php`:330 — `'diff_fields' => historical_orders_diff_fields($snap, $uploaded),`

## historical_orders_commit {#historical_orders_commit}

**Callers (1):**

- `api/v1/historical_orders.php`:58 — `$result = historical_orders_commit(ims_pdo(), $input);`

## historical_orders_insert_shipped {#historical_orders_insert_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:459 — `historical_orders_insert_shipped($pdo, $row, $createdAt);`

## historical_orders_overwrite_shipped {#historical_orders_overwrite_shipped}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/historical_orders_import.php`:488 — `historical_orders_overwrite_shipped($pdo, (int) ($existing['id'] ?? 0), $uploaded, $createdAt);`

