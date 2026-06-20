---
status: approved
created: 2026-06-05
owner: team
---

# Feature: Dept products — Paragis Tea, Black Rice Coffee, Jar Cacao

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

PT, BRC, and Jar Cacao exist in RTS and parcel parsing as `other_items` but have no canonical catalog, no unified stock-in, no costing rows, no parcel deduct, and POS categories are disabled.

## Desired outcome

- [x] Canonical catalog (`pt`, `brc`, `jar_cacao`) with single-stage dept inventory (stock-in only; no supplier code)
- [x] Costing: stocks + products + 1:1 formulas; unit cost from stock-in
- [x] Online parcel ship deducts dept products (including mixed honey + dept lines)
- [x] POS sells PT/BRC/Jar Cacao with inventory deduct

## Behavior

1. Stock-in updates `dept_inventory_rows` on-hand and `stocks` price + `stock_entries` audit.
2. Parcel ship resolves `other_items` to catalog keys; fail-hard if insufficient stock.
3. POS checkout deducts dept rows; uses `products.selling_price`.

## Out of scope (v1)

- Combo SKUs (PT w/ Honey, Jar Cacao w/ Honey)
- Courier cancel-at-pickup restore

## v2 — Paragis Tea (shipped 2026-06-05)

- [x] **PT Supplier Master + BOM** — [[problems/records/2026-06-05_pt-supplier-bom-costing]]; math [[specs/2026-06-05_paragis-tea-bom-math]]
- [x] **BRC Supplier Master + BOM** — [[problems/records/2026-06-05_brc-supplier-bom-costing]]; math [[specs/2026-06-05_brc-bom-math]]
- [ ] Jar Cacao — Supplier Master + BOM TBD (still 1:1 placeholder)

## Approval

- [x] Spec reviewed — implement per attached plan (2026-06-05)
