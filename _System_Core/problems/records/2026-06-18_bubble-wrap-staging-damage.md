---
date: 2026-06-18
type: problem-record
status: fixed
area: fulfillment-packaging
tags: [problem, bubble-wrap, staging]
---

# Problem: Bubble wrap warehouse SKUs were sized; no staging damage path

> Warehouse stocked bubble as 13 / 17 / 22 cm rolls, but TL assigns width at issue. Staging had no way to report torn/unusable rolls.

## Symptom

Bubble wrap was stocked in three width-specific warehouse SKUs. Team leaders issue materials to packing but width is chosen at issue time, not at stock-in. Damaged staging rolls could not be written off without returning to warehouse.

## Cause

Packing issue deducted warehouse `stocks.on_hand` directly on sized bubble SKUs. Staging balances only tracked `issued_qty` and `consumed_qty` with no `damaged_qty`.

## Fix

Generic warehouse SKU `Bubble wrap roll`; issue splits to sized staging (13 / 17 / 22 cm). `damaged_qty` on staging balances + `fulfillment_packing_damage_events`. TL roles (`team_leader`, `csr_tl`) can issue and report damage. Sized bubble hidden from warehouse stock-in and catalog. **Parcel costing:** one generic roll WAC ÷ yield per width (`material_packaging_generic_bubble_roll_unit_cost`).

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase38_bubble_wrap_staging_damage.sql` |
| Backend | `includes/fulfillment_packing.php`, `includes/material_inventory.php`, `includes/parcel_pack_costing.php` |
| API | `api/v1/fulfillment_packing.php` |
| SPA | `PackingMaterialsStagingPage.tsx`, `PackagingMaterialsPage.tsx`, `fulfillmentPackingApi.ts`, `routes.ts`, `Sidebar.tsx` |
| Spec | [[specs/2026-06-18_bubble-wrap-staging-damage]] |

## Deploy

- Run `migration_phase38_bubble_wrap_staging_damage.sql`
- `cd app && npm run build`
- `php tools/memory_bank/generate_all.php`

## Links

- **Spec:** [[specs/2026-06-18_bubble-wrap-staging-damage]]
- **Plan:** [[plans/archive/bubble_wrap_staging_damage_plan]]

## AI rules

Do not stock-in sized `Bubble wrap roll - XX cm` SKUs; recipes still reference sized staging IDs. Remaining staging carries over — no end-of-day action. Do not set separate prices on sized bubble — costing reads generic roll WAC only.
