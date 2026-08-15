---
date: 2026-08-15
spec: specs/2026-08-15_sales-shipped-and-dept-products
approved: true
---

# Plan: Sales page — shipped wording + dept product units

## Phase 1 — Backend (`includes/honey_sales_report.php`)

1. `require_once dept_products.php`.
2. `honey_sales_dept_breakdown(PDO, ?range, channel)` — counts `dept_pouch_units` per `product_key`:
   - walk-in: `status = 'sold' AND consumed_ref_type = 'pos_sale'`
   - shipped: `status IN ('packed','shipped') AND consumed_ref_type = 'pack_session'`
   - date filter on `consumed_at`; returns all catalog keys (zero-filled) plus totals.
   Pack-cancel restock clears `consumed_at`, so restocked units drop out automatically.
3. `honey_sales_checkout_where_clause()` — EXISTS-based: a `pos_sales` row qualifies if it has a
   packaging walk-in `honey_sales_log` row **or** a receipt line whose product has `dept_product_key`.
   `honey_sales_fetch_checkouts()` uses it (drops the honey INNER JOIN).
   The peso summary keeps the honey-only `honey_sales_pos_checkout_where_clause()`.
4. `honey_sales_serialize_receipt_item()` gains a `label` (variant + size, else product name).
5. `honey_sales_sold_snapshot()` payload adds `dept` (products + totals) and `total_units_sold`;
   `channel_share` adds dept walk-in/shipped units.

## Phase 2 — SPA (`app/src/app/pages/exec/SalesPage.tsx`)

1. Channel chip "Online" → "Shipped"; share line wording; API value stays `online`.
2. Headline → `N total units`, caption "Sold at the register + packed for shipping".
3. New **By product** section (`AppSectionEnter` + `appHoverCard`): Honey card + one card per dept
   product with total, walk-in and shipped units.
4. Peso summary cards prefixed "Honey"; variant grid heading "Honey by variant and size".
5. Receipt line rendering uses `item.label` so dept lines show the product name.

## Phase 3 — Verify

`tools/verify.ps1` via verifier sub-agent; SPA build; regenerate memory bank; problem record.
