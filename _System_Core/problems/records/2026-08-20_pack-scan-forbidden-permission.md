---
date: 2026-08-20
type: problem-record
status: fixed
area: deliveries-pack-scan
tags: [problem, permissions]
---

# Problem: Pack scan page shows Forbidden for staff with Pack scan permission

## Symptom

Staff granted **Pack scan** in Staff Management could open Deliveries → Pack scan (SPA route and sidebar visible) but saw a red **Forbidden.** banner on load. The scan UI rendered but `pack_start` failed with HTTP 403.

## Cause

→ [[causes/pack-scan-api-permission-mismatch]]

SPA route guard uses `deliveries.pack_scan`. Pack scan API endpoints (`pack_start`, `pack_scan`, etc.) called `api_bottle_require_scan()` which only checked `honey.bottle.scan` (Honey production stock-in scan), not the deliveries permission.

## Fix

→ [[fixes/pack-scan-api-deliveries-permission]]

Added `honey_bottle_can_pack_scan()` (`deliveries.pack_scan`) and `api_bottle_require_pack_scan()` for all `pack_*` API actions. Accepts either `deliveries.pack_scan` or legacy `honey.bottle.scan`.

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/honey_bottle_barcodes.php` |
| API | `api/v1/bottle_barcodes.php` |
| SPA route | `app/src/app/routes.ts` (`deliveries.pack_scan`) |

## Deploy

- PHP only; hard refresh for affected staff sessions

## Links

- **Pattern:** Same as POS — route permission must match API gate (`pos.sell` fix 2026-08-18)

## AI rules (optional)

When adding a new permission-gated SPA page that calls an existing API, verify the API handler checks the **same** permission key, not a legacy role or sibling permission.
