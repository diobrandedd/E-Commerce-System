---
title: honey_pos
source: includes/honey_pos.php
generated: 2026-07-08T05:43:03+02:00
type: module
function_count: 11
---


# honey_pos

> Source: `includes/honey_pos.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/ar_credits.php`

## honey_pos_variant_image_url {#honey_pos_variant_image_url}

**Callers (1):**

- `includes/honey_pos.php`:132 — `'image' => honey_pos_variant_image_url($variant),`

## honey_pos_resolve_honey_dept_id {#honey_pos_resolve_honey_dept_id}

**Tables:** `departments`

**Callers (2):**

- `includes/honey_pos.php`:83 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/honey_pricing.php`:135 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`

## honey_pos_packaging_availability {#honey_pos_packaging_availability}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/honey_pos.php`:107 — `$availability = honey_pos_packaging_availability($pdo);`

## honey_pos_product_map {#honey_pos_product_map}

**Tables:** `products`

**Callers (4):**

- `includes/honey_pos.php`:108 — `$products = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:291 — `$productMap = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:474 — `$productMap = honey_pos_product_map($pdo);`
- `includes/pos_discount_requests.php`:70 — `$productMap = honey_pos_product_map($pdo);`

## honey_pos_honey_catalog {#honey_pos_honey_catalog}

**Callers (1):**

- `api/v1/pos.php`:63 — `api_ok(honey_pos_honey_catalog(ims_pdo()));`

## honey_pos_available_qty {#honey_pos_available_qty}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_pos.php`:165 — `$available = honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:310 — `$avail = honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:493 — `$avail = honey_pos_available_qty($pdo, $variant, $size);`

## honey_pos_deduct_packaging_aggregate {#honey_pos_deduct_packaging_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_parcel_inventory.php`:62 — `$used = honey_pos_deduct_packaging_aggregate($pdo, $variant, $size, $qty);`
- `includes/honey_pos.php`:366 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`
- `includes/honey_pos.php`:545 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`

## honey_pos_log_sale {#honey_pos_log_sale}

**Tables:** `honey_sales_log`

**Callers (2):**

- `includes/honey_pos.php`:367 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:546 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`

## honey_pos_column_exists {#honey_pos_column_exists}

**Callers (6):**

- `includes/dept_pos.php`:217 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:218 — `$hasPinCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'sold_by_pin_id');`
- `includes/honey_pos.php`:219 — `$hasSaleCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'pos_sale_id');`
- `includes/honey_pos.php`:363 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:460 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:541 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`

## honey_pos_checkout {#honey_pos_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:215 — `$result = honey_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds);`
- `includes/pos_discount_requests.php`:244 — `$result = honey_pos_checkout(`

## honey_pos_ar_checkout {#honey_pos_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:251 — `$result = honey_pos_ar_checkout(ims_pdo(), $pinId, $items, [`

