---
date: 2026-06-05
type: problem-record
status: fixed
area: dept-inventory-costing-pos
tags: [problem, paragis, brc, cacao]
---

# Problem: PT / BRC / Jar Cacao not in inventory, costing, parcel deduct, or POS

## Symptom

Paragis Tea, Black Rice Coffee, and Jar Cacao appeared in parcel parsing as `other_items` only — no stock-in, no costing SKUs, no ship deduct, POS categories disabled.

## Cause

→ [[causes/dept-products-partial-stubs-only]]

## Fix

→ [[fixes/dept-products-full-catalog-rollout]]

## Files

| Role | Path / note |
|------|-------------|
| Catalog | `includes/dept_products.php`, `app/src/app/lib/deptProducts.ts` |
| Inventory | `includes/dept_inventory.php`, `api/v1/honey.php` (`dept_catalog`, `dept_stock_in`) |
| Migration | `sql/migration_phase16_dept_products.sql` |
| Parcel deduct | `includes/honey_parcel_inventory.php` |
| POS | `includes/dept_pos.php`, `api/v1/pos.php`, `POSDeptPage.tsx` |
| Costing | `CostingPage.tsx`, `costingProducts.ts` |
| SPA inventory | `DeptInventoryPage.tsx` |

## Deploy

- Run `sql/migration_phase16_dept_products.sql` on DB (required before E2E)
- `cd app && npm run build`
- `php tools/memory_bank/generate_all.php`

## Shipped (v1)

- Catalog keys `pt`, `brc`, `jar_cacao` — `includes/dept_products.php`
- Single-stage stock-in: qty + **unit cost on receipt** (no supplier picker yet)
- Costing seed: 3 stocks, 3 products, 1:1 formulas; executive sets **selling price** on Costing page
- Parcel ship + revert deducts `dept_inventory_rows` via `online_parcel_dept_deduct_lines`
- POS: `/pos/paragis`, `/pos/brc`, `/pos/cacao` → `dept_checkout`
- Plan archived: [[plans/archive/2026-06-05_dept_products_plan]]

## Follow-up (next session)

**Align dept pricing with honey / Supplier Master** (user confirmed intent 2026-06-05):

- Honey: executive sets ₱/gallon in **Supplier Master** → syncs `stocks` → warehouse stock-in picks supplier (no typed unit cost).
- Dept v1: unit cost typed on each stock-in in `DeptInventoryPage`; Supplier Master has categories `paragis` / `black_rice` / `cacao` but **no price list UI** (only honey has `supplier_honey_prices`).
- **Tomorrow:** add `supplier_dept_prices` (or equivalent), inline editor on Suppliers page, stock-in = supplier + qty + date (price from list). “No supplier code” = no batch code on rows, not “no supplier.”
- Draft spec tweak: move “Supplier master for dept products” from out-of-scope → v2 in [[specs/2026-06-05_dept-products-pt-brc-cacao]].

## Links

- **Spec:** [[specs/2026-06-05_dept-products-pt-brc-cacao]]
- **Fragile edges:** [[003_Fragile_Edges#Dept catalog products (PT / BRC / Cacao)]]
