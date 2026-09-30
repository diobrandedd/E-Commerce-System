---
date: 2026-09-30
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Empty production lots stayed active forever

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Production gallons listed many 0 / overdrawn supplier lots as still active, cluttering Amber/Light/Dark expansions long after they were spent.

## Cause

→ [[causes/honey-production-empty-lots-no-auto-deactivate]]

## Fix

→ [[fixes/honey-production-depleted-at-auto-deactivate]]

## Files

| Role | Path / note |
|------|-------------|
| Schema | `sql/migration_phase88_honey_production_depleted_at.sql` |
| Apply | `tools/apply_migration_phase88.php` |
| Primary | `includes/honey_inventory.php` (`depleted_at` sync, 1h lazy deactivate, refill reactivate) |
| SPA | `HoneyProductionPage.tsx` (guide copy) |

## Deploy

- Run `php tools/apply_migration_phase88.php` on each environment
- PHP + optional SPA rebuild for guide text; hard refresh Production page

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory / stock truth]]
- **Related:** [[problems/records/2026-09-16_honey-production-lot-active]] · [[problems/records/2026-09-30_honey-production-fifo-multi-lot]]

## AI rules (optional)

Empty/overdrawn production lots stamp `depleted_at`; after 1 hour lazy sweep sets `is_active=0`. Refill clears and reactivates. Do not require cron.
