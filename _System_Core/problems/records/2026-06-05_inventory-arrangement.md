---
date: 2026-06-05
type: problem-record
status: fixed
area: inventory
tags: [problem]
---

# Problem: Inventory navigation flat; no packaging/supplies on-hand

> Reorganized SPA inventory into four branches with material catalog and request workflow.

## Symptom

Inventory sidebar grouped only Honey / Paragis / Cacao-BRC. Packaging materials and supplies had no dedicated pages or on-hand tracking. Production managers could not request material add/deduct separately from honey corrections.

## Cause

Operational truth was split across honey stage tables, `dept_inventory_rows`, and costing `stocks` without `category` or unified material balances.

## Fix

- Migration `sql/migration_phase20_inventory_arrangement.sql` — `stocks.category`, `on_hand`, `material_adjustment_requests`
- `includes/material_inventory.php` + `api/v1/materials.php`
- SPA routes/sidebar: Raw Materials, Packaging, Supplies, Products (honey W/P/P + split dept products)
- Requests page: packaging/supplies adjustment approve/reject

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase20_inventory_arrangement.sql` |
| Backend | `includes/material_inventory.php`, `api/v1/materials.php` |
| SPA | `app/src/app/routes.ts`, `Sidebar.tsx`, `pages/inventory/*`, `lib/materials.ts` |
| Plan | [[plans/archive/2026-06-05_inventory-arrangement_plan]] |

## Deploy

- Run `migration_phase20_inventory_arrangement.sql` on prod
- `cd app && npm run build` + hard refresh
- `php tools/memory_bank/generate_all.php`

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- **Spec:** [[specs/Inventory Arrangement change]]

## AI rules (optional)

Do not sync honey warehouse gallons into `stocks.on_hand`. Auto-deduct packaging on bottling is out of scope until spec'd.
