---
title: honey_pos
source: includes/honey_pos.php
generated: 2026-09-17T11:13:17+02:00
type: module
function_count: 16
---


# honey_pos

> Source: `includes/honey_pos.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/ar_credits.php`
- `includes/pos_sales_helpers.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/honey_bottle_barcodes.php`
- `includes/pos_sales_helpers.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/honey_bottle_barcodes.php`
- `includes/rts_schema.php`
- `includes/rts_schema.php`

## honey_pos_variant_image_url {#honey_pos_variant_image_url}

**Callers (1):**

- `includes/honey_pos.php`:132 — `'image' => honey_pos_variant_image_url($variant),`

## honey_pos_resolve_honey_dept_id {#honey_pos_resolve_honey_dept_id}

**Tables:** `departments`

**Callers (5):**

- `includes/honey_pos.php`:83 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/honey_pricing.php`:135 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/pos_unified.php`:39 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/sku_barcodes.php`:292 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/sku_barcodes.php`:333 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`

## honey_pos_packaging_availability {#honey_pos_packaging_availability}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/honey_pos.php`:107 — `$availability = honey_pos_packaging_availability($pdo);`

## honey_pos_product_map {#honey_pos_product_map}

**Tables:** `products`

**Callers (4):**

- `includes/honey_pos.php`:108 — `$products = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:321 — `$productMap = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:556 — `$productMap = honey_pos_product_map($pdo);`
- `includes/pos_discount_requests.php`:71 — `$productMap = honey_pos_product_map($pdo);`

## honey_pos_honey_catalog {#honey_pos_honey_catalog}

**Callers (1):**

- `api/v1/pos.php`:108 — `api_ok(honey_pos_honey_catalog(ims_pdo()));`

## honey_pos_available_qty {#honey_pos_available_qty}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_pos.php`:165 — `$available = honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:355 — `: honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:590 — `: honey_pos_available_qty($pdo, $variant, $size);`

## honey_pos_deduct_packaging_aggregate {#honey_pos_deduct_packaging_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_parcel_inventory.php`:69 — `$used = honey_pos_deduct_packaging_aggregate($pdo, $variant, $size, $qty);`
- `includes/honey_pos.php`:433 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`
- `includes/honey_pos.php`:664 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`

## honey_pos_log_sale {#honey_pos_log_sale}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_pos.php`:431 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:434 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:662 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:665 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`

## honey_pos_line_unit_ids {#honey_pos_line_unit_ids}

**Callers (3):**

- `includes/honey_pos.php`:330 — `$unitIds = honey_pos_line_unit_ids($entry);`
- `includes/honey_pos.php`:565 — `$unitIds = honey_pos_line_unit_ids($entry);`
- `includes/pos_unified.php`:245 — `$unitIds = honey_pos_line_unit_ids($entry);`

## honey_pos_column_exists {#honey_pos_column_exists}

**Callers (23):**

- `includes/dept_pos.php`:341 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:218 — `$hasPinCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'sold_by_pin_id');`
- `includes/honey_pos.php`:219 — `$hasSaleCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'pos_sale_id');`
- `includes/honey_pos.php`:410 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:532 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:640 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:757 — `$hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');`
- `includes/honey_pos.php`:777 — `$hasSub = honey_pos_column_exists($pdo, 'pos_sales', 'subtotal_amount');`
- `includes/honey_pos.php`:778 — `$hasDiscAmt = honey_pos_column_exists($pdo, 'pos_sales', 'discount_amount');`
- `includes/honey_pos.php`:803 — `$hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:804 — `$hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');`
- `includes/honey_pos.php`:933 — `$hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_sales_report.php`:587 — `$hasSellerName = honey_pos_column_exists($pdo, 'pos_sales', 'seller_name');`
- `includes/honey_sales_report.php`:602 — `$hasRefunded = honey_pos_column_exists($pdo, 'pos_sales', 'refunded_at');`
- `includes/pos_discount_requests.php`:331 — `$hasSellerName = honey_pos_column_exists($pdo, 'pos_sales', 'seller_name');`
- `includes/pos_refund.php`:180 — `if (honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant')`
- `includes/pos_refund.php`:181 — `&& honey_pos_column_exists($pdo, 'pos_receipt_items', 'size')) {`
- `includes/pos_refund.php`:243 — `if (honey_pos_column_exists($pdo, 'pos_sales', 'subtotal_amount')) {`
- `includes/pos_sales_helpers.php`:40 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'seller_name')) {`
- `includes/pos_sales_helpers.php`:53 — `if (honey_pos_column_exists($pdo, 'pos_sales', 'sold_by_user_id')) {`
- `includes/pos_unified.php`:439 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/pos_unified.php`:565 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/pos_unified.php`:602 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`

## honey_pos_checkout {#honey_pos_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:278 — `$result = honey_pos_checkout(ims_pdo(), $pinId, $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `includes/pos_discount_requests.php`:258 — `$result = honey_pos_checkout(`

## honey_pos_ar_checkout {#honey_pos_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:322 — `$result = honey_pos_ar_checkout(ims_pdo(), $pinId, $items, [`

## pos_sale_recalc_totals {#pos_sale_recalc_totals}

**Tables:** `pos_receipt_items`, `pos_sales`

**Callers (1):**

- `includes/honey_pos.php`:871 — `pos_sale_recalc_totals($pdo, $saleId);`

## pos_sale_reverse_one_unit {#pos_sale_reverse_one_unit}

**Tables:** `pos_sales`, `pos_receipt_items`, `products`

**Callers (5):**

- `includes/honey_pos.php`:954 — `pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [`
- `includes/pack_cancel_restock.php`:547 — `pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [`
- `includes/pack_cancel_restock.php`:598 — `pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [`
- `includes/pos_refund.php`:112 — `pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [`
- `includes/pos_refund.php`:172 — `pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [`

## pos_sale_id_for_honey_unit {#pos_sale_id_for_honey_unit}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/pack_cancel_restock.php`:523 — `$saleId = pos_sale_id_for_honey_unit(`

## pos_sale_heal_returned_units {#pos_sale_heal_returned_units}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `honey_bottle_units`

**Callers (1):**

- `includes/honey_sales_report.php`:637 — `pos_sale_heal_returned_units($pdo, $saleId);`

