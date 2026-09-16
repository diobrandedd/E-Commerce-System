---
date: 2026-09-16
type: cause
area: sales-report
tags: [cause]
---

# Cause: Dept Sales shipped counted stickers only

`honey_sales_dept_breakdown()` summed `dept_pouch_units` consumed by `pack_session`. Unique pouch stickers create those rows; **SKU qty pack lines** only call `sku_barcode_dept_deduct()` (inventory) and leave `honey_bottle_pack_session_dept_items` with `sku_id` + `qty` and **no** `unit_id`.

Honey was fine: `sku_barcode_honey_deduct()` already writes `honey_sales_log` with the full SKU qty.
