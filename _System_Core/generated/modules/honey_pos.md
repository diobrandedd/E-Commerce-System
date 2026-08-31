---
title: honey_pos
source: includes/honey_pos.php
generated: 2026-08-31T07:08:53+02:00
type: module
function_count: 16
---


# honey_pos

> Source: `includes/honey_pos.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/ar_credits.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/honey_bottle_barcodes.php`
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
- `includes/pos_unified.php`:69 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/sku_barcodes.php`:189 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`
- `includes/sku_barcodes.php`:230 — `$deptId = honey_pos_resolve_honey_dept_id($pdo);`

## honey_pos_packaging_availability {#honey_pos_packaging_availability}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/honey_pos.php`:107 — `$availability = honey_pos_packaging_availability($pdo);`

## honey_pos_product_map {#honey_pos_product_map}

**Tables:** `products`

**Callers (4):**

- `includes/honey_pos.php`:108 — `$products = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:316 — `$productMap = honey_pos_product_map($pdo);`
- `includes/honey_pos.php`:547 — `$productMap = honey_pos_product_map($pdo);`
- `includes/pos_discount_requests.php`:71 — `$productMap = honey_pos_product_map($pdo);`

## honey_pos_honey_catalog {#honey_pos_honey_catalog}

**Callers (1):**

- `api/v1/pos.php`:68 — `api_ok(honey_pos_honey_catalog(ims_pdo()));`

## honey_pos_available_qty {#honey_pos_available_qty}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_pos.php`:165 — `$available = honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:350 — `: honey_pos_available_qty($pdo, $variant, $size);`
- `includes/honey_pos.php`:581 — `: honey_pos_available_qty($pdo, $variant, $size);`

## honey_pos_deduct_packaging_aggregate {#honey_pos_deduct_packaging_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_parcel_inventory.php`:69 — `$used = honey_pos_deduct_packaging_aggregate($pdo, $variant, $size, $qty);`
- `includes/honey_pos.php`:429 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`
- `includes/honey_pos.php`:656 — `honey_pos_deduct_packaging_aggregate($pdo, $line['variant'], $line['size'], $line['qty']);`

## honey_pos_log_sale {#honey_pos_log_sale}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_pos.php`:427 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:430 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:654 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`
- `includes/honey_pos.php`:657 — `honey_pos_log_sale($pdo, $line['variant'], $line['size'], $line['qty'], $saleId, $posPinId);`

## honey_pos_line_unit_ids {#honey_pos_line_unit_ids}

**Callers (3):**

- `includes/honey_pos.php`:325 — `$unitIds = honey_pos_line_unit_ids($entry);`
- `includes/honey_pos.php`:556 — `$unitIds = honey_pos_line_unit_ids($entry);`
- `includes/pos_unified.php`:275 — `$unitIds = honey_pos_line_unit_ids($entry);`

## honey_pos_column_exists {#honey_pos_column_exists}

**Callers (16):**

- `includes/dept_pos.php`:335 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:218 — `$hasPinCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'sold_by_pin_id');`
- `includes/honey_pos.php`:219 — `$hasSaleCol = honey_pos_column_exists($pdo, 'honey_sales_log', 'pos_sale_id');`
- `includes/honey_pos.php`:406 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:526 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/honey_pos.php`:632 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:749 — `$hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');`
- `includes/honey_pos.php`:769 — `$hasSub = honey_pos_column_exists($pdo, 'pos_sales', 'subtotal_amount');`
- `includes/honey_pos.php`:770 — `$hasDiscAmt = honey_pos_column_exists($pdo, 'pos_sales', 'discount_amount');`
- `includes/honey_pos.php`:795 — `$hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/honey_pos.php`:796 — `$hasList = honey_pos_column_exists($pdo, 'pos_receipt_items', 'list_unit_price');`
- `includes/honey_pos.php`:925 — `$hasVariant = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/pos_unified.php`:46 — `if (honey_pos_column_exists($pdo, 'pos_sales', 'sold_by_user_id')) {`
- `includes/pos_unified.php`:467 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`
- `includes/pos_unified.php`:591 — `if (!honey_pos_column_exists($pdo, 'pos_sales', 'is_ar')) {`
- `includes/pos_unified.php`:628 — `$hasVariantCol = honey_pos_column_exists($pdo, 'pos_receipt_items', 'variant');`

## honey_pos_checkout {#honey_pos_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:224 — `$result = honey_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds, [`
- `includes/pos_discount_requests.php`:258 — `$result = honey_pos_checkout(`

## honey_pos_ar_checkout {#honey_pos_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:262 — `$result = honey_pos_ar_checkout(ims_pdo(), $pinId, $items, [`

## pos_sale_recalc_totals {#pos_sale_recalc_totals}

**Tables:** `pos_receipt_items`, `pos_sales`

**Callers (1):**

- `includes/honey_pos.php`:863 — `pos_sale_recalc_totals($pdo, $saleId);`

## pos_sale_reverse_one_unit {#pos_sale_reverse_one_unit}

**Tables:** `pos_sales`, `pos_receipt_items`, `products`

**Callers (3):**

- `includes/honey_pos.php`:946 — `pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [`
- `includes/pack_cancel_restock.php`:547 — `pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [`
- `includes/pack_cancel_restock.php`:598 — `pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [`

## pos_sale_id_for_honey_unit {#pos_sale_id_for_honey_unit}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/pack_cancel_restock.php`:523 — `$saleId = pos_sale_id_for_honey_unit(`

## pos_sale_heal_returned_units {#pos_sale_heal_returned_units}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `honey_bottle_units`

**Callers (1):**

- `includes/honey_sales_report.php`:525 — `pos_sale_heal_returned_units($pdo, $saleId);`

