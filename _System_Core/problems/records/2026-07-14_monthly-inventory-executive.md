# 2026-07-14 — Monthly inventory (executive create/set + edit logs)

## Problem
Physical month-end stock did not match system; executives needed one place to set absolute counts, add missing lots (with supplier codes), and review edits. Raw bags and RTS had no absolute set path; materials set-on-hand was not mirrored into `inventory_edit_log`; no SPA Logs UI.

## Cause
Inventory mutate paths were split across domains; `inventory_edit_log` existed for honey executive edit only and had no list API/UI.

## Fix
- Shared `includes/inventory_edit_log.php` write + list helpers; `source` column (phase 52).
- Executive seed production/packaging; `dept_raw_set_bags_on_hand`; RTS set honey/other totals.
- Mirror packaging/supply `set_on_hand` into edit log.
- SPA `/inventory/monthly-count` (executive) with domain tabs + Edit Logs.

## Files
- `includes/inventory_edit_log.php`, `honey_inventory.php`, `dept_inventory.php`, `material_inventory.php`, `rts_inventory_sync.php`
- `api/v1/honey.php`, `api/v1/rts.php`
- `sql/migration_phase52_inventory_edit_log_source.sql`
- `app/src/app/pages/exec/MonthlyInventoryPage.tsx`, `routes.ts`, `Sidebar.tsx`
- `_System_Core/specs/2026-07-14_monthly-inventory-executive.md`