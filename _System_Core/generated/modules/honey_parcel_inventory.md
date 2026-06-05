---
title: honey_parcel_inventory
source: includes/honey_parcel_inventory.php
generated: 2026-06-05T04:27:29+02:00
type: module
function_count: 2
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

- `includes/online_order_parcels.php`:557 — `honey_parcel_apply_deduct($pdo, (int) $parcel['id'], $userId, (string) $locked['items_raw']);`

## honey_parcel_log_sale {#honey_parcel_log_sale}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_parcel_inventory.php`:48 — `honey_parcel_log_sale($pdo, $parcelId, $userId, $variant, $size, $qty, $hasParcelCol);`

