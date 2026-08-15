---
date: 2026-08-15
area: sales-report
spec: specs/2026-08-15_sales-shipped-and-dept-products
---

# Sales page counted packed parcels as sold and hid dept products

## Problem

Executives read the Sales page as revenue. Two things misled them:

1. The **Online** channel was pack-scan output — units packed for a parcel, not sold at a register —
   yet it sat inside "total bottles sold" with no peso behind it.
2. Paragis Tea, Black Rice Coffee, Jar Cacao and Honey Comb never appeared, even after their POS and
   pack-scan flows shipped.

## Cause

`honey_sales_sold_snapshot()` read `honey_sales_log` only. `honey_bottle_pack_session_confirm()` writes
those parcel rows with `sale_type = 'parcel'`, which the page labelled Online. Dept POS writes
`pos_sales`/`pos_receipt_items` (no `honey_sales_log` row) and dept pack scans write
`dept_pouch_units` + `honey_bottle_pack_session_dept_items`, so no dept path reached the report. The
checkout list also `INNER JOIN`ed `honey_sales_log`, which dropped dept-only receipts.

## Fix

- `honey_sales_dept_breakdown()` — dept units per product: walk-in from POS receipt lines joined on
  `products.dept_product_key` (covers sticker and legacy quantity sells), shipped from
  `dept_pouch_units` consumed by a `pack_session`. Pack-cancel restock clears `consumed_at`, so
  restocked units leave the count on their own. Missing `dept_pouch_units` degrades to sold-only.
- `honey_sales_checkout_where_clause()` — EXISTS-based qualifier (honey packaging walk-in **or** a dept
  receipt line) replaces the honey join in `honey_sales_fetch_checkouts()`. The peso summary keeps the
  honey-only clause, since dept is reported units-only.
- Payload adds `honey`, `dept` and `total_units_sold`; `channel_share` includes dept units.
- Receipt items gain a `label` so dept lines render a product name instead of a blank variant.
- SPA: channel chip and share line say **Shipped**; headline counts total units; new **By product**
  card row (honey + four dept products, zero rows included); honey-only cards relabelled.

## Files

- `includes/honey_sales_report.php`
- `app/src/app/pages/exec/SalesPage.tsx`
- `_System_Core/specs/2026-08-15_sales-shipped-and-dept-products.md`
- `_System_Core/plans/archive/2026-08-15_sales-shipped-and-dept-products_plan.md`
