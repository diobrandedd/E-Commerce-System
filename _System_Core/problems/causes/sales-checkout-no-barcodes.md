---
date: 2026-08-26
type: cause
area: sales-checkouts
tags: [cause]
---

# Cause: Checkout API serialized receipt items without unit barcodes

`pos_receipt_items` stores product/qty/price only. Scanned codes live on `honey_bottle_units` / `dept_pouch_units` (and honey `honey_sales_log.bottle_unit_id`) but `honey_sales_fetch_checkouts` never joined them onto line payloads.
