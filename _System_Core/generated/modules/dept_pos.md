---
title: dept_pos
source: includes/dept_pos.php
generated: 2026-09-15T10:15:28+02:00
type: module
function_count: 5
---


# dept_pos

> Source: `includes/dept_pos.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/dept_products.php`
- `includes/ar_credits.php`
- `includes/honey_pos.php`
- `includes/dept_pouch_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/pos_sales_helpers.php`
- `includes/rts_inventory_sync.php`
- `includes/pos_sales_helpers.php`
- `includes/rts_inventory_sync.php`

## dept_pos_pouch_unit_ids_from_entry {#dept_pos_pouch_unit_ids_from_entry}

**Callers (2):**

- `includes/dept_pos.php`:86 — `$unitIds = dept_pos_pouch_unit_ids_from_entry($entry);`
- `includes/pos_unified.php`:318 — `$unitIds = dept_pos_pouch_unit_ids_from_entry($entry);`

## dept_pos_catalog {#dept_pos_catalog}

**Callers (1):**

- `api/v1/pos.php`:120 — `api_ok(dept_pos_catalog(ims_pdo(), $deptKey !== '' ? $deptKey : null));`

## dept_pos_build_cart_lines {#dept_pos_build_cart_lines}

**Tables:** `products`

**Callers (3):**

- `includes/dept_pos.php`:210 — `$cleanLines = dept_pos_build_cart_lines($pdo, $items, $discountFactor, $stockSource);`
- `includes/dept_pos.php`:365 — `$cleanLines = dept_pos_build_cart_lines($pdo, $items, 1.0, $stockSource);`
- `includes/pos_discount_requests.php`:38 — `$clean = dept_pos_build_cart_lines($pdo, $items, $discountFactor);`

## dept_pos_checkout {#dept_pos_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:144 — `$result = dept_pos_checkout(ims_pdo(), $pinId, $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `includes/pos_discount_requests.php`:249 — `$result = dept_pos_checkout(`

## dept_pos_ar_checkout {#dept_pos_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:359 — `$result = dept_pos_ar_checkout(ims_pdo(), $pinId, $items, [`

