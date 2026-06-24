# Problem: Leak RTS had no damaged vs reusable split

## Problem

Leak returns were recorded with a single product-line list. All quantities synced to sellable RTS inventory, with no way to track what was damaged/leaked separately or manage a damaged pool.

## Cause

v1 swapped/leak disposition scoped leak cases to one `lines` array and `rts_inventory_sync_run` treated every leak line as sellable.

## Fix

- `rts_line_items.line_role` (`reusable` | `damaged`) + leak form two builders on Record RTS
- `rts_inventory_rows.inventory_pool` (`sellable` | `damaged`) + sync routes by `line_role`
- `/rts/damaged` inventory page + `use_damaged` API (`mix_production`, `scrap`)
- `rts_damaged_use_events` audit table

## Files

- `sql/migration_phase45_rts_line_role.sql`, `sql/migration_phase46_rts_damaged_pool.sql`
- `includes/rts_case_handlers.php`, `includes/rts_schema.php`, `includes/rts_inventory_sync.php`, `includes/rts_damaged_use.php`
- `api/v1/rts.php`
- `app/src/app/pages/rts/RTSRecordPage.tsx`, `RTSDamagedInventoryPage.tsx`, `RtsCaseMediaUi.tsx`, `RtsLineBuilder.tsx`
- `app/src/app/routes.ts`, `app/src/app/components/Sidebar.tsx`, `app/src/app/lib/rtsApi.ts`
