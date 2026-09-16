---
date: 2026-09-16
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Same supplier code stock-out created a second Production lot

## Symptom

Stocking out AA091226 Light again (different day) added a new Production row (e.g. 10 gal Available) instead of refilling the existing overdrawn lot from the prior stock-out day.

## Cause

→ [[causes/honey-production-merge-by-stock-out-day]]

## Fix

→ [[fixes/honey-production-lot-refill]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_inventory.php` (`honey_production_daily_group_key`, `honey_production_upsert_daily_lot`) |
| Scan path | `includes/honey_warehouse_barcodes.php` |
| Consolidate | `tools/consolidate_honey_production_daily_lots.php` |
| UI copy | `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` |

## Deploy

- PHP only for merge behavior; hard refresh SPA for copy. Optional: `php tools/consolidate_honey_production_daily_lots.php --apply` to fold existing day-split lots.

## Links

- **Fragile edges:** [[003_Fragile_Edges]] (Honey production lot merge)
- Spec open question answered: [[specs/2026-09-15_honey-production-lot-merge]]
