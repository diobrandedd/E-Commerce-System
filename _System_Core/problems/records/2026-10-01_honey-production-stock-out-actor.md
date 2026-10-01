---
date: 2026-10-01
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Production lots did not show who moved WH → production

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Expanded production lots showed WH arrival / stock-out dates but not which user last stocked gallons out of warehouse into that production row.

## Cause

→ [[causes/honey-production-no-stock-out-actor]]

## Fix

→ [[fixes/honey-production-attach-stock-out-actors]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_inventory.php` — `honey_production_lots_attach_stock_out_actors` |
| Callers | `honey_production_variant_summary`, `honey_production_voided_list` |
| Events | `honey_warehouse_stock_out_events.stocked_out_by` + `users` |
| SPA | `app/src/app/pages/honey/HoneyProductionPage.tsx` |
| Related | [[generated/modules/honey_inventory]] |

## Deploy

- PHP + SPA build + hard refresh; regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Honey production]]
- **Related:** [[problems/records/2026-10-01_honey-production-voided-lots]]
