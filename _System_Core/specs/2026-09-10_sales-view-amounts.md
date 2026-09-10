---
status: shipped
created: 2026-09-10
owner: ops
area: sales
tags: [sales, permissions, amounts]
---

# Sales: permission to view peso amounts

> Users with View sales see units; ₱ totals only with View sales amounts. API strips money when denied.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[plans/archive/2026-09-10_sales-view-amounts_plan]] · [[problems/records/2026-09-10_sales-view-amounts]]

## Locked behavior

1. New key `sales.view_amounts` — View sales amounts.
2. Page gate remains `sales.view`.
3. One-time grant: existing `sales.view` holders also get `sales.view_amounts`.
4. API strips revenue/checkout/item money + `can_view_amounts` flag.
5. SPA hides ₱ lines and AR / Discount given cards when denied.

## Success criteria

- [x] Catalog + Staff permissions show View sales amounts.
- [x] Without permission: no ₱ in Sales UI or API payload money fields.
- [x] With permission (or executive): current peso UI unchanged.
- [x] Existing sales.view users still see ₱ after migration.

## Non-goals

- POS sell-screen prices; Reports/Costing money.
