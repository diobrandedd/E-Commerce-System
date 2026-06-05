---
title: honey_parcel_inventory
source: includes/honey_parcel_inventory.php
generated: 2026-06-05T08:03:22+02:00
type: module
function_count: 3
---


# honey_parcel_inventory

> Source: `includes/honey_parcel_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_pos.php`
- `includes/honey_parcel_item_parser.php`
- `includes/rts_schema.php`

## honey_parcel_apply_deduct {#honey_parcel_apply_deduct}

**Tables:** `online_parcel_deduct_lines`

**Callers (1):**

- `includes/online_order_parcels.php`:564 — `honey_parcel_apply_deduct($pdo, (int) $parcel['id'], $userId, (string) $locked['items_raw']);`

## honey_parcel_revert_deduct {#honey_parcel_revert_deduct}

**Tables:** `online_parcel_deduct_lines`, `honey_sales_log`, `honey_packaging_rows`

**Callers (1):**

- `includes/delivery_edit_requests.php`:152 — `honey_parcel_revert_deduct($pdo, $parcelId);`

## honey_parcel_log_sale {#honey_parcel_log_sale}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:51 — `honey_parcel_log_sale($pdo, $parcelId, $userId, $variant, $size, $qty, $hasParcelCol);`

