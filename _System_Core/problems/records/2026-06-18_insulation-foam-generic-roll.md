---
date: 2026-06-18
type: problem-record
status: fixed
area: fulfillment-packaging
tags: [problem, insulation-foam, staging]
---

# Problem: Insulation foam required cut size at stock issue

> Ops stocks one foam roll; 9×12 vs 12.5×12 cuts depend on cacao parcel recipe at ship scan — not when TL moves rolls to packing.

## Symptom

Sized `Insulation foam roll - 9x12` / `12.5x12` were warehouse SKUs. TL had to pick cut size when issuing to packing, unlike ops reality (same roll, cut when packing cacao).

## Cause

Phase 37 modeled foam like fixed-size pool rolls. No generic warehouse + recipe-driven ship deduct (bubble wrap had generic warehouse but width still chosen at issue).

## Fix

→ [[fixes/insulation-foam-generic-roll-ship-cut]]

## Files

| Role | Path |
|------|------|
| Migration | `sql/migration_phase41_insulation_foam_staging.sql` |
| Backend | `includes/fulfillment_packing.php`, `includes/material_inventory.php`, `includes/parcel_pack_costing.php` |
| SPA | `PackingMaterialsStagingPage.tsx`, `PackagingMaterialsPage.tsx`, `fulfillmentPackingApi.ts` |

## Deploy

- Run `migration_phase41_insulation_foam_staging.sql`
- Stock-in **Insulation foam roll** (generic) only; set `pool_yield_qty` on sized SKUs for costing/ship math
- `cd app && npm run build` if SPA changed

## AI rules

Recipes keep sized foam lines (`foam_piece`). Ship deduct consumes **generic** staging using sized SKU yield. Do not stock-in sized foam SKUs. **Session:** [[specs/2026-06-18_costing-parcel-packaging-session]].
