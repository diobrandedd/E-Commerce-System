---
title: pos_refund
source: includes/pos_refund.php
generated: 2026-09-12T11:14:53+02:00
type: module
function_count: 1
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
- `includes/rts_inventory_sync.php`
- `includes/dept_inventory.php`
- `includes/dept_inventory.php`

## pos_sale_full_refund {#pos_sale_full_refund}

**Tables:** `pos_sales`, `honey_bottle_units`, `honey_sales_log`, `pos_receipt_items`, `ar_credits`, `dept_inventory_rows`

**Callers (1):**

- `api/v1/pos.php`:608 — `$result = pos_sale_full_refund(ims_pdo(), $saleId, $reason);`

