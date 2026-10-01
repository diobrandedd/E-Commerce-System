---
date: 2026-10-01
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Empty production lots cluttered the main list forever

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Production gallons kept every 0-gal supplier lot in the expand list. Over months Light/Amber/Dark grew long with empty shells even after auto-deactivate.

## Cause

→ [[causes/honey-production-empty-lots-no-void]]

## Fix

→ [[fixes/honey-production-voided-lots]]

## Files

| Role | Path / note |
|------|-------------|
| Schema | `sql/migration_phase89_honey_production_voided.sql` |
| Apply | `tools/apply_migration_phase89.php` |
| Primary | `includes/honey_inventory.php` (void helpers, sync, variant summary) |
| API | `api/v1/honey.php` (`production_voided_list`, `production_void`, `production_unvoid`) |
| SPA | `HoneyProductionPage.tsx` |

## Deploy

- Run `php tools/apply_migration_phase89.php`
- `cd app && npm run build` + hard refresh Production

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
- **Related:** [[problems/records/2026-09-30_honey-production-auto-deactivate]]

## AI rules (optional)

Void is soft (`voided_at`). Never delete production rows — refill must still find `daily_group_key`. Auto-void = empty + inactive + `depleted_at` ≥ 2 days.
