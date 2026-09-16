---
title: pos_refund
source: includes/pos_refund.php
generated: 2026-09-16T07:31:19+02:00
type: module
function_count: 2
---


# pos_refund

> Source: `includes/pos_refund.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/honey_pos.php`
- `includes/sku_barcodes.php`
- `includes/ar_credits.php`
- `includes/api_tokens.php`
- `includes/rts_schema.php`
- `includes/dept_inventory.php`

## pos_refund_credit_honey_packaging {#pos_refund_credit_honey_packaging}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/pos_refund.php`:105 — `$packId = pos_refund_credit_honey_packaging(`
- `includes/pos_refund.php`:197 — `$packId = pos_refund_credit_honey_packaging($pdo, $variant, $size, (float) $qtyLeft, 0);`

## pos_sale_full_refund {#pos_sale_full_refund}

**Tables:** `pos_sales`, `honey_bottle_units`, `pos_receipt_items`, `ar_credits`, `honey_packaging_rows`, `products`, `dept_inventory_rows`

**Callers (1):**

- `api/v1/pos.php`:617 — `$result = pos_sale_full_refund(ims_pdo(), $saleId, $reason);`

