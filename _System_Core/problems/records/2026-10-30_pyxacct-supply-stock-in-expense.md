---
date: 2026-10-30
type: problem-record
status: fixed
area: settings
tags: [problem, pyxacct, supplies, settings]
---

# Problem: Supply stock-ins did not reach PyxAcct expenses

> Main hub note. Cause, fix, and files are below.

## Symptom

Priced supply stock-ins in Tracker cost money, but PyxAcct had no record of them. Books under-counted supply spending.

## Cause

Only honey stock-ins (accounts payable) and walk-in receipts (revenue) were pushed to PyxAcct. Supply lots had no sync.

## Fix

- After a supply lot stock-in commits, `api_materials_stock_in_lot` calls `pyxacct_sync_supply_lot()`. That function POSTs `ingest_supply` to the PyxAcct cloud app with `source_key` `rts:supply_lot:{entry_id}`, the item name, and the lot total.
- PyxAcct files it as an expense under Packaging → Supplies, with the item name as the description.
- Posted only when the item is a supply, the lot total is above 0, and it is not a monthly inventory stock-in. Packaging lots and on-hand corrections are not posted. Nothing is backfilled.
- A new Tracker switch **Sync supply stock-ins → expense** (`pyxacct_supply_sync_enabled`, default off) sits on the PyxAcct integration card. PyxAcct has its own **Accept Tracker supply stock-ins** switch.
- Uses the same base URL and token as honey sync. A failed POST is logged and does not undo the stock-in.

## Files

| Role | Path / note |
|------|-------------|
| Sync | `includes/pyxacct_supply_sync.php` |
| Settings payload | `includes/pyxacct_ap_sync.php` `pyxacct_api_settings_public()` adds `supply_enabled` |
| API | `api/v1/settings.php` `pyxacct_supply_save` |
| Hook | `api/v1/materials.php` `api_materials_stock_in_lot` (after commit) |
| SPA | `app/src/app/pages/exec/SettingsPage.tsx` PyxAcct card |
| PyxAcct | `PyxAcct/web` `entries` `ingest_supply`, migration `009_supply_sync.sql` |

## Deploy

- PHP plus `cd app && npm run build` and a hard refresh.
- Run PyxAcct migration 009 in Supabase, then turn on the supply switch in both apps.
- Only the cloud PyxAcct supports `ingest_supply`. The local PHP PyxAcct does not.

## AI rules (optional)

Supply lots cannot be edited or deleted in Tracker, so there is no void path. Do not add a backfill without a double-count guard against manual Supplies entries.
