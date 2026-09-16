---
date: 2026-09-16
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Production scan lot list cluttered; no exec deactivate/qty

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Production lot scanner dropdown listed out-of-stock and spent lots alongside usable ones. Executives had no Production-page control to deactivate a lot or correct gallon qty per row.

## Cause

→ [[causes/honey-production-lot-no-active-flag]]

## Fix

→ [[fixes/honey-production-lot-active-scan-filter]]

## Files

| Role | Path / note |
|------|-------------|
| Schema | `sql/migration_phase83_honey_production_is_active.sql` |
| Apply | `tools/apply_migration_phase83.php` |
| Primary | `includes/honey_inventory.php` (`honey_executive_edit_row`, stock-in guards) |
| API | `api/v1/honey.php` (`production_list` → `is_active`) |
| SPA | `HoneyProductionPage.tsx`, `ExecutiveRowEditModal.tsx`, `HoneyBottleBarcodePanels.tsx` |
| Related | [[generated/modules/honey_inventory]] |

## Deploy

- Run `php tools/apply_migration_phase83.php` (or apply SQL manually)
- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
- **Related:** [[problems/records/2026-09-16_honey-production-lot-refill]]
