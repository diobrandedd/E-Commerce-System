# RTS case edit blocked by inventory FK

## Problem

Editing an RTS case to fix leak line placement failed with:

`Cannot delete or update a parent row: foreign key constraint fails (rts_inventory_rows → rts_line_items)`

## Cause

`rts_case_handler_update()` deleted all `rts_line_items` and re-inserted them, but synced `rts_inventory_rows` still referenced the old line ids (`ON DELETE RESTRICT`).

## Fix

- `rts_case_replace_line_items()` removes untouched mirrored inventory rows for the case, then replaces line items.
- Blocks edit when stock was sold, used (damaged use events), or quantities were adjusted.
- After save, `api_rts_cases_maybe_sync()` re-syncs new line ids into the correct pool.
- Record RTS: switching to Leak moves prefilled lines into Damaged / leaked; validation warns if leak case only has reusable lines.

## Files

- `includes/rts_inventory_sync.php`
- `includes/rts_case_handlers.php`
- `app/src/app/pages/rts/RTSRecordPage.tsx`
