---
date: 2026-09-15
type: problem-record
status: fixed
area: honey-production
tags: [problem, honey, production, stock-out, merge]
---

# Problem: Part-by-part stock-out filled Production with duplicate supplier codes

> Main hub note — links to **Cause**, **Fix**, and **Files**.

## Symptom

Workers stock out warehouse gallons part by part. Production showed many rows / dropdown options with the same supplier code (e.g. `JR090426`), so packers struggled to pick the right lot for bottle scan-in.

## Cause

→ [[causes/honey-manual-stock-out-no-daily-merge]]

## Fix

→ [[fixes/honey-production-daily-lot-merge]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_inventory.php` (`honey_production_upsert_daily_lot`, `honey_warehouse_stock_out`) |
| Scan path | `includes/honey_warehouse_barcodes.php` |
| API | `api/v1/honey.php` `production_list` |
| SPA | `HoneyBottleBarcodePanels.tsx`, `HoneyProductionPage.tsx` |
| Tool | `tools/consolidate_honey_production_daily_lots.php` |
| Spec | [[specs/2026-09-15_honey-production-lot-merge]] |

## Deploy

- Deploy PHP + rebuild SPA (`cd app && npm run build`) + hard refresh
- Optional: dry-run then apply consolidate tool on prod for legacy same-day dupes
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Online order parcels + ship deduct]] (honey production daily lot merge bullet)
- **Prior:** [[records/2026-08-28_honey-warehouse-supplier-barcodes]]
- **Plan:** [[plans/archive/2026-09-15_honey-production-lot-merge_plan]]

## AI rules

Keep merge key including **variant** and stock-out date; never collapse Light/Amber/Dark under one supplier code.
