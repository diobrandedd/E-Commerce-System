---
date: 2026-09-17
type: problem-record
status: fixed
area: honey-packaging
tags: [problem]
---

# Problem: Honey packaging stock-in required lot pickers; inventory was lot-row sprawl

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Ops had to pick a production lot (and historically batch/bottling fields) when scanning finished honey into packaging. Packaging UI listed dozens of dated lot rows instead of raw finished counts by Light / Amber / Dark. Production showed a flat lot table instead of variant rollups.

## Cause

→ [[causes/honey-packaging-lot-centric-stock-in]]

## Fix

→ [[fixes/honey-packaging-variant-pools]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase79_honey_packaging_variant_pools.sql` |
| Helpers | `includes/honey_inventory.php` (`honey_packaging_ensure_variant_pool`, `honey_production_fifo_deduct_gallons`, `honey_packaging_pool_stock_in`, snapshots) |
| API | `api/v1/honey.php` (`packaging_variant_pools`, `production_variant_list`); `api/v1/bottle_barcodes.php` `scan_stock_in` |
| Unique path | `includes/honey_bottle_barcodes.php` (pool attach) |
| SPA | `HoneyPackagingPage.tsx`, `HoneyProductionPage.tsx`, `HoneyBottleBarcodePanels.tsx` |
| Spec | [[specs/2026-09-17_honey-packaging-pool-no-lots]] |

## Deploy

- Run phase 79 once on prod (idempotent fold into POOL)
- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- **Spec:** [[specs/2026-09-17_honey-packaging-pool-no-lots]]
